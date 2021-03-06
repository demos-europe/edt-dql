<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Contracts;

use Exception;

class MappingException extends Exception
{
    public static function relationshipUnavailable(string $relationshipName, string $entityName, Exception $cause = null): self
    {
        $message = "The relationship '{$relationshipName}' is not available in the entity '{$entityName}'";

        return new self($message, 0, $cause);
    }

    public static function joinTypeUnavailable(string $joinType): self
    {
        return new self("Only LEFT JOIN and INNER JOIN are supported: {$joinType}");
    }
}
