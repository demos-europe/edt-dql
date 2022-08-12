<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Utilities;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException as OrmMappingException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\Mapping\MappingException as PersistenceMappingException;
use EDT\DqlQuerying\Contracts\MappingException;
use InvalidArgumentException;

class JoinFinder
{
    /**
     * @var ClassMetadataFactory
     */
    private $metadataFactory;

    public function __construct(ClassMetadataFactory $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * Find the joins needed for the actual 'where' expression from the property path.
     * The joins will be retrieved from the given entity definition. However
     * the alias of each join will be prefixed with an identifier generated from the
     * path leading to that join. This way it is ensured that duplicated
     * aliases correspond to duplicated join clauses from which all but one can be
     * removed.
     *
     * The left side of the joins ({@link Join::getJoin()}) will have the original entity or a join
     * alias first, which is followed by the source relationship name:
     * * original entity example: `Book.authors`
     * * nested join example: `t_d18ab622_Book.authors`
     *
     * The right side of the joins ({@link Join::getAlias()}) will be the alias of the entity
     * that can be used in WHERE clauses. The join alias will be prefixed with a hash identifying
     * the previous path to not mix up aliases with the same backing entity that resulted from
     * different paths:
     * * `t_d18ab622_authors` in case of a join to the `authors` of a `Book` entity
     *
     * @param string[] $path
     *
     * @return array<string, Join> The needed joins. The key will be the alias of
     *                       {@link Join::getAlias()} to ensure uniqueness of the joins returned.
     *                       The count indicates if the last property was a relationship or an attribute.
     *                       In case of an non-relationship the number of returned joins is exactly one less
     *                       than the length of the provided path. In case of a relationship the number
     *                       of returned joins is equal to the length of the provided path.
     * @throws MappingException
     */
    public function findNecessaryJoins(string $salt, ClassMetadataInfo $classMetadata, array $path): array
    {
        if ([] === $path) {
            return [];
        }
        return $this->findJoinsRecursive($salt, $classMetadata, '', ...$path);
    }

    /**
     * @return array<int,Join>
     * @throws MappingException
     */
    private function findJoinsRecursive(string $salt, ClassMetadataInfo $classMetadata, string $previousPathHash, string $currentPathPart, string ...$morePathParts): array
    {
        $isAttribute = $classMetadata->hasField($currentPathPart);
        $isRelationship = $classMetadata->hasAssociation($currentPathPart);

        if (!$isAttribute && !$isRelationship) {
            throw new InvalidArgumentException("Current property '$currentPathPart' was not found in entity '{$classMetadata->getName()}'");
        }

        if ($isRelationship) {
            $currentAliasPrefix = $this->getPathHash($salt, $currentPathPart, $previousPathHash);
            $targetClassMetadata = $this->getTargetClassMetadata($currentPathPart, $classMetadata);
            $tableName = $classMetadata->getTableName();
            $targetTableName = $targetClassMetadata->getTableName();
            $relationshipAlias = "$currentAliasPrefix$targetTableName";
            $join = "$previousPathHash$tableName.$currentPathPart";
            $neededJoin = new Join(Join::LEFT_JOIN, $join, $relationshipAlias);

            if ([] === $morePathParts) {
                return [$neededJoin];
            }

            $additionallyNeededJoins = $this->findJoinsRecursive(
                $salt,
                $targetClassMetadata,
                $currentAliasPrefix,
                ...$morePathParts
            );

            array_unshift($additionallyNeededJoins, $neededJoin);

            return $additionallyNeededJoins;
        }

        if ([] !== $morePathParts) {
            $properties = implode(',', $morePathParts);
            throw new InvalidArgumentException("Current property '$currentPathPart' is not an association but the path continues with the following properties: $properties");
        }

        return [];
    }

    /**
     * @param string $currentPathPart  The current path part
     * @param string $previousPathHash The single hash generated from all previous path parts
     *
     * @return string The new hash generated from all previous path parts and the current one.
     */
    protected function getPathHash(string $salt, string $currentPathPart, string $previousPathHash): string
    {
        return "t{$salt}_".hash('crc32b', $previousPathHash.$currentPathPart).'_';
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
                $type = get_class($classMetadata);
                throw new InvalidArgumentException("Expected ClassMetadataInfo, got $type");
            }

            return $classMetadata;
        } catch (OrmMappingException | PersistenceMappingException | \ReflectionException $e) {
            throw MappingException::relationshipUnavailable($relationshipName, $metadata->getName(), $e);
        }
    }
}
