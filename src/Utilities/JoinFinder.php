<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Utilities;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException as OrmMappingException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\Mapping\MappingException as PersistenceMappingException;
use EDT\DqlQuerying\Contracts\MappingException;
use Exception;
use InvalidArgumentException;
use ReflectionException;
use function array_key_exists;

/**
 * @internal
 */
class JoinFinder
{
    public function __construct(
        private readonly ClassMetadataFactory $metadataFactory
    ) {}

    /**
     * Find the joins needed for the actual 'where' expression from the property path.
     * The joins will be retrieved from the given entity definition. However,
     * the alias of each join will be prefixed with an identifier generated from the
     * path leading to that join. This way it is ensured that duplicated
     * aliases correspond to duplicated join clauses from which all but one can be
     * removed.
     *
     * The left side of the joins ({@link Join::getJoin()}) will have the original entity or a join
     * alias first, which is followed by the source relationship name:
     * * original entity example: `Book.authors`
     * * nested join example: `t_4dba5d08_Book.authors`
     *
     * The right side of the joins ({@link Join::getAlias()}) will be the alias of the entity
     * that can be used in WHERE clauses. The join alias will be prefixed with a hash identifying
     * the previous path to not mix up aliases with the same backing entity that resulted from
     * different paths:
     * * `t_58fb870d_Person` in case of a join to the `authors` of a `Book` entity
     *
     * @param list<non-empty-string> $path
     *
     * @return array<non-empty-string, Join> The needed joins. The key will be the alias of
     *                       {@link Join::getAlias()} to ensure uniqueness of the joins returned.
     *                       The count indicates if the last property was a relationship or an attribute.
     *                       In case of a non-relationship the number of returned joins is exactly one less
     *                       than the length of the provided path. In case of a relationship the number
     *                       of returned joins is equal to the length of the provided path.
     * @throws MappingException
     */
    public function findNecessaryJoins(bool $toManyAllowed, string $salt, ClassMetadataInfo $classMetadata, array $path, string $tableAlias): array
    {
        if ([] === $path) {
            return [];
        }

        try {
            $joins = $this->findJoinsRecursive($toManyAllowed, $salt, $classMetadata, $tableAlias, ...$path);
        } catch (Exception $exception) {
            throw MappingException::joinProcessingFailed($path, $salt, $exception);
        }

        $joinInstances = [];
        foreach ($joins as $alias => $join) {
            $joinInstances[$alias] = new Join(Join::LEFT_JOIN, $join, $alias);
        }

        return $joinInstances;
    }

    /**
     * @param string $pathSalt will be used when generating the tables aliases to distinguish the
     *                         segments in this path from segments in other paths that use the same
     *                         table name
     * @param non-empty-string $pathPart
     * @param non-empty-string ...$morePathParts
     *
     * @return array<non-empty-string, non-empty-string> mapping from the join alias to
     * @throws MappingException
     * @throws OrmMappingException
     */
    private function findJoinsRecursive(
        bool $toManyAllowed,
        string $pathSalt,
        ClassMetadataInfo $classMetadata,
        string $tableAlias,
        string $pathPart,
        string ...$morePathParts
    ): array {
        if ($this->isRelationship($classMetadata, $pathPart)) {
            if (!$toManyAllowed && $this->isToManyRelationship($classMetadata, $pathPart)) {
                throw MappingException::disallowedToMany($classMetadata->getName(), $pathPart);
            }

            $nextClassMetadata = $this->getTargetClassMetadata($pathPart, $classMetadata);

            // prefix each following table alias, to distinguish if the same table is used in two
            // different paths
            $nextTablePrefix = hash('crc32b', "$pathSalt$tableAlias.$pathPart");
            $nextTableAlias = $this->createTableAlias($nextTablePrefix, $nextClassMetadata);

            $neededJoins = [$nextTableAlias => "$tableAlias.$pathPart"];

            if ([] === $morePathParts) {
                return $neededJoins;
            }

            $additionallyNeededJoins = $this->findJoinsRecursive(
                $toManyAllowed,
                '', // salt is already included in $nextTableAlias, no need to pass it down
                $nextClassMetadata,
                $nextTableAlias,
                ...$morePathParts
            );

            if (array_key_exists($nextTableAlias, $additionallyNeededJoins)) {
                throw MappingException::duplicatedAlias($nextTableAlias, $pathPart);
            }

            return array_merge($neededJoins, $additionallyNeededJoins);
        }

        if (!$this->isAttribute($classMetadata, $pathPart)) {
            throw new InvalidArgumentException("Current property '$pathPart' was not found in entity '{$classMetadata->getName()}'");
        }

        // attributes must not be followed by additional paths
        if ([] !== $morePathParts) {
            $properties = implode(',', $morePathParts);
            throw new InvalidArgumentException("Current property '$pathPart' is not an association but the path continues with the following properties: $properties");
        }

        return [];
    }

    /**
     * Prefixes the given table name with the given value.
     *
     * The prefixing allows to distinguish multiple usages of the same table in different contextes,
     * e.g. different paths or separate `from` clauses.
     *
     * @return non-empty-string
     */
    public function createTableAlias(string $prefix, ClassMetadataInfo $tableInfo): string
    {
        return "t_{$prefix}_{$tableInfo->getTableName()}";
    }

    /**
     * @throws MappingException
     */
    protected function getTargetClassMetadata(string $relationshipName, ClassMetadataInfo $metadata): ClassMetadataInfo
    {
        try {
            $entityClass = $metadata->getAssociationTargetClass($relationshipName);
            $classMetadata = $this->metadataFactory->getMetadataFor($entityClass);
            if (!$classMetadata instanceof ClassMetadataInfo) {
                $type = $classMetadata::class;
                throw new InvalidArgumentException("Expected ClassMetadataInfo, got $type");
            }

            return $classMetadata;
        } catch (OrmMappingException | PersistenceMappingException | ReflectionException $exception) {
            throw MappingException::relationshipUnavailable($relationshipName, $metadata->getName(), $exception);
        }
    }

    /**
     * @throws OrmMappingException
     */
    private function isToManyRelationship(ClassMetadataInfo $classMetadata, string $property): bool
    {
        $mapping = $classMetadata->getAssociationMapping($property);

        return (bool) ($mapping['type'] & ClassMetadataInfo::TO_MANY);
    }

    private function isRelationship(ClassMetadataInfo $classMetadata, string $property): bool
    {
        return $classMetadata->hasAssociation($property);
    }

    private function isAttribute(ClassMetadataInfo $classMetadata, string $property): bool
    {
        return $classMetadata->hasField($property);
    }
}
