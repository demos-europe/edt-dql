<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Utilities;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Expr\Math;
use EDT\DqlQuerying\Contracts\ClauseInterface;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use EDT\DqlQuerying\Contracts\MappingException;
use EDT\DqlQuerying\Contracts\OrderByInterface;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\Utilities\Iterables;
use function array_slice;
use function count;

class QueryBuilderPreparer
{
    /**
     * @var JoinFinder
     */
    private $joinFinder;

    /**
     * The alias of each join must be unique in the array.
     *
     * Will be filled while {@link QueryBuilderPreparer::processClause() processing the clauses}.
     *
     * @var array<string,Join>
     */
    private $joinClauses = [];

    /**
     * Mapping from (integer) parameter key to parameter value.
     * Using {@see Parameter} would be possible too but
     * seems more complex and not necessary.
     *
     * Will be filled while creating the {@link QueryBuilderPreparer::$conditions}.
     *
     * @var array<int,mixed>
     */
    private $parameters = [];

    /**
     * @var ClassMetadataInfo
     */
    private $classMetadata;

    /**
     * @var array<int,ClauseInterface>
     */
    private $conditions;

    /**
     * @var OrderByInterface[]
     */
    private $sortMethods;

    /**
     * Transform the given group into raw DQL query data using the given entity definition.
     *
     * The query data will have the entity type and alias to return from the query set to those
     * defined in the entity definition. The result will be limited to the conditions defined in the
     * given group. The joins required to limit the result will be automatically generated
     * using the group and entity definition.
     *
     * @param ClassMetadataInfo $classMetadata Provides all needed information to
     *                                         choose the correct entity type and
     *                                         mappings to translate the group
     *                                         into DQL data.
     */
    public function __construct(ClassMetadataInfo $classMetadata, ClassMetadataFactory $metadataFactory)
    {
        $this->joinFinder = new JoinFinder($metadataFactory);
        $this->classMetadata = $classMetadata;
    }

    /**
     * Overwrites the currently set clauses with the conditions to use to limit the result returned
     * by the final query.
     * If called with no parameters then previously set conditions will be removed.
     */
    public function setWhereExpressions(ClauseInterface ...$conditions): void
    {
        $this->conditions = $conditions;
    }

    /**
     * Overwrites the currently set order-by definitions.
     * If called with no parameters then previously set order-by definitions will be removed.
     */
    public function setOrderByExpressions(OrderByInterface ...$sortMethods): void
    {
        $this->sortMethods = $sortMethods;
    }

    /**
     * Fills a given QueryBuilder with the data set in this instance and returns it without checking
     * any validity or semantics.
     *
     * @throws MappingException If a different join type than INNER or LEFT is requested.
     */
    public function fillQueryBuilder(QueryBuilder $queryBuilder): void
    {
        // start filling the actual query
        $entityAlias = $this->classMetadata->getTableName();
        $queryBuilder->select($entityAlias);
        $queryBuilder->from($this->classMetadata->getName(), $entityAlias);

        // Side effects! Execution order matters!
        // While setting WHERE and ORDER BY the joins and parameters are determined.
        $this->setWhere($queryBuilder);
        $this->setOrderBy($queryBuilder);
        $this->setParameters($queryBuilder);
        $this->setJoins($queryBuilder);
        $this->resetTemporaryState();
    }

    /**
     * @throws MappingException
     */
    protected function processSortingClause(OrderByInterface $sortClause): OrderBy
    {
        $dql = $this->processClause($sortClause);
        $direction = $sortClause->getDirection();
        return new OrderBy((string)$dql, $direction);
    }

    /**
     * @return Composite|Math|Func|Comparison|string
     *
     * @throws MappingException
     */
    protected function processClause(ClauseInterface $clause)
    {
        $clauseValues = Iterables::asArray($clause->getClauseValues());
        $valueIndices = array_map([$this, 'addToParameters'], $clauseValues);
        $columnNames = array_map(function (PropertyPathAccessInterface $path): string {
            return $this->processPath($path->getSalt(), $path->getAccessDepth(), ...iterator_to_array($path));
        }, Iterables::asArray($clause->getPropertyPaths()));

        return $clause->asDql($valueIndices, $columnNames);
    }

    /**
     * Processes the path to find all necessary joins. The joins found are added to
     * {@link QueryBuilderPreparer::$joinClauses}.
     *
     * @param int $accessDepth 1 if the last property in the given path is a relationship
     *                         and a join needs to be created from that relationship property
     *                         to its target entity. In that case the alias to the target entity
     *                         will be returned (without appended property name).
     *                         0 if the last property in the given path is a relationship
     *                         and no join should be created from that relationship property
     *                         to its target entity. In that case the alias to the join
     *                         will be returned (with appended property name).
     *
     * @return string The alias of the entity at the end of the path with or without appended property name.
     *
     * @throws MappingException
     */
    protected function processPath(string $salt, int $accessDepth, string $property, string ...$properties): string
    {
        array_unshift($properties, $property);
        $originalPathLength = count($properties);

        /**
         * If the condition acts on the relationship name (ie. does not need a join to the target
         * entity) we do not look for joins at the path parts after the relationship and thus remove
         * it here. For more information see {@link PropertyPathAccessInterface::getAccessDepth()}.
         */
        $dropProperties = 0 >= $accessDepth;
        if ($dropProperties) {
            if (0 > $accessDepth) {
                $properties = array_slice($properties, 0, $accessDepth);
            }
            $lastProperty = array_pop($properties);
        } else {
            $lastProperty = $properties[array_key_last($properties)];
        }

        $neededJoins = $this->joinFinder->findNecessaryJoins($salt, $this->classMetadata, $properties);
        $lastPropertyWasRelationship = count($neededJoins) === $originalPathLength;

        if (0 !== count($neededJoins)) {
            // Add the joins found for this condition to all joins found so far.
            // Will override duplicated keys, this is ok, as we expect the key
            // to be the join alias and the join alias to be unique except
            // it actually corresponds to the exactly same join clause.
            $this->joinClauses = array_merge($this->joinClauses, $this->useAliasAsKey($neededJoins));

            // As there were joins needed to access the property the accessed entity is now the last
            // join determined above.
            /** @var Join $lastJoin */
            $lastJoin = array_pop($neededJoins);
            $entityAlias = $lastJoin->getAlias();

            // If the condition needs to access a property directly on the entity we append the
            // property name to the entity alias.
            if (!$lastPropertyWasRelationship || $dropProperties) {
                return "{$entityAlias}.{$lastProperty}";
            }

            /**
             * If the last property is a relationship for which a join to the target entity was
             * executed we return the alias of that target entity without any properties appended.
             *
             * Example: IS NULL needs a join to a target relationship and accesses
             * the target relationships entity alias. But in case of a non-relationship as last
             * property in the path no such join is executed and we still need to access the
             * property on the entity.
             */
            return $entityAlias;
        }

        // If no joins are needed for this condition we can simply use the root entity alias with
        // the accessed property appended.
        return "{$this->classMetadata->getTableName()}.$lastProperty";
    }

    /**
     * @param array<int,Join>|Join[] $joins
     *
     * @return array<string,Join>|Join[]
     */
    protected function useAliasAsKey(array $joins): array
    {
        $result = [];
        foreach ($joins as $join) {
            $result[$join->getAlias()] = $join;
        }

        return $result;
    }

    /**
     * Adds the given value to the end of the {@see parameters} array and
     * returns the int index of the added value.
     *
     * @param mixed $value
     *
     * @return string Index reference ("?1", "?2", ...) of the added parameter in the array.
     */
    protected function addToParameters($value): string
    {
        $parameterIndex = array_push($this->parameters, $value) - 1;
        return "?{$parameterIndex}";
    }

    /**
     * @throws MappingException
     */
    private function setWhere(QueryBuilder $queryBuilder): void
    {
        if ([] !== $this->conditions) {
            // The 'WHERE' expressions that resulted from the given clause.
            // Each expression includes all nested conditions if any are present.
            $whereExpressions = array_map([$this, 'processClause'], $this->conditions);
            $queryBuilder->where(...$whereExpressions);
        }
    }

    /**
     * @throws MappingException
     */
    private function setOrderBy(QueryBuilder $queryBuilder): void
    {
        if ([] !== $this->sortMethods) {
            $orderings = array_map([$this, 'processSortingClause'], $this->sortMethods);
            array_map([$queryBuilder, 'addOrderBy'], $orderings);
        }
    }

    private function setParameters(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->setParameters($this->parameters);
    }

    /**
     * @throws MappingException
     */
    private function setJoins(QueryBuilder $queryBuilder): void
    {
        foreach ($this->joinClauses as $joinObject) {
            $joinType = $joinObject->getJoinType();
            $join = $joinObject->getJoin();
            $alias = $joinObject->getAlias();
            $conditionType = $joinObject->getConditionType();
            $condition = $joinObject->getCondition();
            $indexBy = $joinObject->getIndexBy();
            switch ($joinType) {
                case Join::INNER_JOIN:
                    $queryBuilder->innerJoin($join, $alias, $conditionType, $condition, $indexBy);
                    break;
                case Join::LEFT_JOIN:
                    $queryBuilder->leftJoin($join, $alias, $conditionType, $condition, $indexBy);
                    break;
                default:
                    throw MappingException::joinTypeUnavailable($joinType);
            }
        }
    }

    /**
     * Deletes the temporary state in the {@link QueryBuilderPreparer::$joinClauses} and
     * {@link QueryBuilderPreparer::$parameters} variables. This avoids invalid states when
     * the {@link QueryBuilderPreparer::setWhereExpressions()} or {@link QueryBuilderPreparer::setOrderByExpressions()}
     * is used after {@link QueryBuilderPreparer::fillQueryBuilder()}.
     */
    private function resetTemporaryState(): void
    {
        $this->joinClauses = [];
        $this->parameters = [];
    }
}
