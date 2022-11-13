<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Contracts;

use Exception;

class MappingException extends Exception
{
    public static function relationshipUnavailable(string $relationshipName, string $entityName, Exception $cause = null): self
    {
        $message = "The relationship '$relationshipName' is not available in the entity '$entityName'";

        return new self($message, 0, $cause);
    }

    public static function joinTypeUnavailable(string $joinType): self
    {
        return new self("Only LEFT JOIN and INNER JOIN are supported: $joinType");
    }

    /**
     * @param class-string $existingContext
     * @param class-string $context
     */
    public static function conflictingContext(
        string $existingContext,
        string $context,
        string $contextAlias
    ): self {
        return new self("Path defines the class context '$context' with the alias '$contextAlias', but that alias is already in use for the class context '$existingContext'.");
    }

    /**
     * @param non-empty-string $alias
     * @param non-empty-string $pathSegment
     */
    public static function duplicatedAlias(string $alias, string $pathSegment): self
    {
        return new self("Found more than one join with the same alias '$alias' in one path for the path segment '$pathSegment'.");
    }

    /**
     * @param class-string $name
     */
    public static function disallowedToMany(string $name, string $property): self
    {
        return new self("The processed path accesses the to-many relationship '$property' in class '$name', while being used in a context where to-many relationships are not allowed.");
    }

    /**
     * @param non-empty-list<non-empty-string> $path
     * @param string $salt
     */
    public static function joinProcessingFailed(array $path, string $salt, Exception $exception): self
    {
        $pathString = implode('.', $path);

        return new self("Join processing failed for the path '$pathString' with the salt '$salt'.", 0, $exception);
    }
}
