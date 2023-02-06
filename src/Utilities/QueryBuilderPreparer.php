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
use EDT\Querying\PropertyPaths\PathInfo;
use ReflectionException;
use function array_key_exists;
use function array_slice;
use function count;

/**
 * @internal
 */
class QueryBuilderPreparer
{
    /**
     * The alias of each join must be unique in the array.
     *
     * Will be filled while {@link QueryBuilderPreparer::processClause() processing the clauses}.
     *
     * @var array<non-empty-string, Join> mapping from the join alias to the {@link Join} instance
     */
    private array $joinClauses = [];

    /**
     * Will be filled while {@link QueryBuilderPreparer::processClause() processing the clauses}.
     *
     * @var array<non-empty-string, class-string> mapping from the alias to the entity type
     */
    private array $fromClauses = [];

    /**
     * Mapping from (integer) parameter key to parameter value.
     * Using {@see Parameter} would be possible too but
     * seems more complex and not necessary.
     *
     * Will be filled while creating the {@link QueryBuilderPreparer::$conditions}.
     *
     * @var list<mixed>
     */
    private array $parameters = [];

    /**
     * Provides all needed information to choose the correct entity type and mappings to translate
     * the group into DQL data.
     */
    private ClassMetadataInfo $mainClassMetadata;

    /**
     * @var list<ClauseInterface>
     */
    private array $conditions = [];

    /**
     * @var list<OrderByInterface>
     */
    private array $sortMethods = [];

    /**
     * @var array<string, ClauseInterface> keys are used as aliases
     */
    private array $selections = [];

    /**
     * Transform the given group into raw DQL query data using the given entity definition.
     *
     * The query data will have the entity type and alias to return from the query set to those
     * defined in the entity definition. The result will be limited to the conditions defined in the
     * given group. The joins required to limit the result will be automatically generated
     * using the group and entity definition.
     *
     * @param class-string $mainEntityClass the entity class to fetch instances of
     */
    public function __construct(
        string $mainEntityClass,
        private readonly ClassMetadataFactory $metadataFactory,
        private readonly JoinFinder $joinFinder
    ) {
        $this->mainClassMetadata = $metadataFactory->getMetadataFor($mainEntityClass);
    }

    /**
     * Overwrites the currently set clauses with the select expressions to use to when fetching the
     * data.
     * If called with no parameters then previously set conditions will be removed.
     * If not called at all it defaults to the main entity class.
     *
     * @param array<string, ClauseInterface> $selections
     */
    public function setSelectExpressions(array $selections): void
    {
        $this->selections = $selections;
    }

    /**
     * Overwrites the currently set clauses with the conditions to use to limit the result returned
     * by the final query.
     * If called with no parameters then previously set conditions will be removed.
     *
     * @param list<ClauseInterface> $conditions
     */
    public function setWhereExpressions(array $conditions): void
    {
        $this->conditions = $conditions;
    }

    /**
     * Overwrites the currently set order-by definitions.
     * If called with no parameters then previously set order-by definitions will be removed.
     *
     * @param list<OrderByInterface> $sortMethods
     */
    public function setOrderByExpressions(array $sortMethods): void
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
        // Side effects! Execution order matters!
        // Process all conditions, sort methods and selects to collect `from`s, joins and
        // parameters before setting those.
        $selectExpressions = array_map([$this, 'processClause'], $this->selections);
        $whereExpressions = array_map([$this, 'processClause'], $this->conditions);
        $orderExpressions = array_map([$this, 'processClause'], $this->sortMethods);
        $entityAlias = $this->mainClassMetadata->getTableName();

        // start filling the actual query

        // set `SELECT`s
        if ([] === $selectExpressions) {
            $queryBuilder->select($entityAlias);
        }
        $selectExpressions = array_map(
            static fn ($expression, string $alias): string => "$expression AS $alias",
            $selectExpressions,
            array_keys($selectExpressions)
        );
        $queryBuilder->addSelect($selectExpressions);

        // set `FROM`s
        $queryBuilder->from($this->mainClassMetadata->getName(), $entityAlias);
        array_map([$queryBuilder, 'from'], $this->fromClauses, array_keys($this->fromClauses));

        // set `JOIN`s
        $this->setJoins($queryBuilder);

        // set `WHERE`s
        if ([] !== $whereExpressions) {
            // Set the 'WHERE' expressions that resulted from the given clause.
            // Each expression includes all nested conditions if any are present.
            $queryBuilder->where(...$whereExpressions);
        }

        // set `ORDER BY`s
        $orderings = array_map([$this, 'createOrderBy'], $orderExpressions, $this->sortMethods);
        array_map([$queryBuilder, 'addOrderBy'], $orderings);

        // set parameters
        $queryBuilder->setParameters($this->parameters);

        $this->resetTemporaryState();
    }

    /**
     * @throws MappingException
     */
    protected function createOrderBy(Composite|Math|Func|Comparison|string $orderByDql, OrderByInterface $sortClause): OrderBy
    {
        $direction = $sortClause->getDirection();
        return new OrderBy((string)$orderByDql, $direction);
    }

    /**
     * @throws MappingException
     */
    protected function processClause(ClauseInterface $clause): Composite|Math|Func|Comparison|string
    {
        $valueIndices = array_map([$this, 'addToParameters'], $clause->getClauseValues());
        $columnNames = array_map(function (PathInfo $pathInfo): string {
            $path = $pathInfo->getPath();

            return $this->processPath(
                $pathInfo->isToManyAllowed(),
                $path->getSalt(),
                $path->getAccessDepth(),
                $path->getContext(),
                $path->getAsNames()
            );
        }, $clause->getPropertyPaths());

        return $clause->asDql($valueIndices, $columnNames);
    }

    /**
     * Processes the path to find all necessary joins and `from` clauses. The joins found are added to
     * {@link QueryBuilderPreparer::$joinClauses}. The `from` clauses found are added to
     * {@link QueryBuilderPreparer::$fromClauses}.
     *
     * @param int $accessDepth 1 if the last property in the given path is a relationship
     *                         and a join needs to be created from that relationship property
     *                         to its target entity. In that case the alias to the target entity
     *                         will be returned (without appended property name).
     *                         0 if the last property in the given path is a relationship
     *                         and no join should be created from that relationship property
     *                         to its target entity. In that case the alias to the join
     *                         will be returned (with appended property name).
     * @param class-string|null $context non-`null` if a different context (i.e. a separate `from`
     *                                   clause should be used for the current path
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return string The alias of the entity at the end of the path with or without appended property name.
     *
     * @throws MappingException
     * @throws \Doctrine\Persistence\Mapping\MappingException
     * @throws ReflectionException
     */
    protected function processPath(
        bool $isToManyAllowed,
        string $salt,
        int $accessDepth,
        ?string $context,
        array $properties
    ): string {
        $originalPathLength = count($properties);

        /**
         * If the condition acts on the relationship name (i.e. does not need a join to the target
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

        if (null === $context) {
            // in main context
            $classMetadata = $this->mainClassMetadata;
        } else {
            // not in main context
            $classMetadata = $this->metadataFactory->getMetadataFor($context);
            $this->addFromClause($context, $this->joinFinder->createTableAlias($salt, $classMetadata));
        }


        // For the main context the simple table name will be used to match the alias in the main `from` clause.
        // Separate contexts will be prefixed to distinguish them if they use the same table name as the main context.
        $entityAlias = null === $context
            ? $classMetadata->getTableName()
            : $this->joinFinder->createTableAlias($salt, $classMetadata);

        $neededJoins = $this->joinFinder->findNecessaryJoins(
            $isToManyAllowed,
            $salt,
            $classMetadata,
            $properties,
            $entityAlias
        );
        $joinCount = count($neededJoins);
        $lastPropertyWasRelationship = $joinCount === $originalPathLength;

        if (0 !== $joinCount) {
            // Add the joins found for this condition to all joins found so far.
            // Will override duplicated keys, this is ok, as we expect the join alias to be unique
            // except it actually corresponds to the exactly same join clause. This is not just
            // an assumption but controlled by the provided paths and their usage of salts.
            $this->joinClauses = array_merge($this->joinClauses, $neededJoins);

            // As there were joins needed to access the property the accessed entity is now the
            // alias of the last join determined above.
            $entityAlias = array_key_last($neededJoins);

            // If the condition needs to access a property directly on the entity we append the
            // property name to the entity alias.
            if (!$lastPropertyWasRelationship || $dropProperties) {
                return "$entityAlias.$lastProperty";
            }

            /**
             * If the last property is a relationship for which a join to the target entity was
             * executed we return the alias of that target entity without any properties appended.
             *
             * Example: IS NULL needs a join to a target relationship and accesses
             * the target relationships entity alias. But in case of a non-relationship as last
             * property in the path no such join is executed, and we still need to access the
             * property on the entity.
             */
            return $entityAlias;
        }

        // If no joins are needed for this condition we can simply use the root entity alias with
        // the accessed property appended.
        return "$entityAlias.$lastProperty";
    }

    /**
     * Adds the given value to the end of the {@see parameters} array and
     * returns the int index of the added value.
     *
     * @return string Index reference ("?1", "?2", ...) of the added parameter in the array.
     */
    protected function addToParameters(mixed $value): string
    {
        $parameterIndex = array_push($this->parameters, $value) - 1;
        return "?$parameterIndex";
    }

    /**
     * @throws MappingException
     */
    private function setJoins(QueryBuilder $queryBuilder): void
    {
        foreach ($this->joinClauses as $alias => $joinObject) {
            $joinType = $joinObject->getJoinType();
            $join = $joinObject->getJoin();
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
        $this->fromClauses = [];
    }

    /**
     * @param class-string     $context
     * @param non-empty-string $tableAlias
     *
     * @throws MappingException
     */
    private function addFromClause(string $context, string $tableAlias): void
    {
        if (array_key_exists($tableAlias, $this->fromClauses)) {
            $existingContext = $this->fromClauses[$tableAlias];
            if ($existingContext !== $context) {
                throw MappingException::conflictingContext($existingContext, $context, $tableAlias);
            }
        } else {
            $this->fromClauses[$tableAlias] = $context;
        }
    }
}
