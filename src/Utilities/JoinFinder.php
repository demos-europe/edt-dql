<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Utilities;

use Doctrine\ORM\Query\Expr\Join;
use EDT\DqlQuerying\Contracts\MappingException;
use InvalidArgumentException;

class JoinFinder
{
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
     * @param DeepClassMetadata $classMetadata
     * @param string[]          $path
     *
     * @return array<string, Join>|Join[] The needed joins. The key will be the alias of
     *                       {@link Join::getAlias()} to ensure uniqueness of the joins returned.
     *                       The count indicates if the last property was a relationship or an attribute.
     *                       In case of an non-relationship the number of returned joins is exactly one less
     *                       than the length of the provided path. In case of a relationship the number
     *                       of returned joins is equal to the length of the provided path.
     * @throws MappingException
     */
    public function findNecessaryJoins(string $salt, DeepClassMetadata $classMetadata, array $path): array
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
    private function findJoinsRecursive(string $salt, DeepClassMetadata $classMetadata, string $previousPathHash, string $currentPathPart, string ...$morePathParts): array
    {
        if (!$classMetadata->hasField($currentPathPart)) {
            throw new InvalidArgumentException("Current property '$currentPathPart' was not found in entity '{$classMetadata->getName()}'");
        }

        if ($classMetadata->hasAssociation($currentPathPart)) {
            $currentAliasPrefix = $this->getPathHash($salt, $currentPathPart, $previousPathHash);
            $targetClassMetadata = $classMetadata->getTargetClassMetadata($currentPathPart);
            $relationshipAlias = "{$currentAliasPrefix}{$targetClassMetadata->getTableName()}";
            $join = "{$previousPathHash}{$classMetadata->getTableName()}.{$currentPathPart}";
            $neededJoin = new Join(Join::LEFT_JOIN, $join, $relationshipAlias);

            if ([] === $morePathParts) {
                return [$neededJoin];
            }

            $additionallyNeededJoins = $this->findJoinsRecursive(
                $salt,
                $classMetadata->getTargetClassMetadata($currentPathPart),
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
}
