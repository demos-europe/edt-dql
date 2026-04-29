<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\PropertyAccessors;

use Carbon\Carbon;
use DateTimeInterface;
use ReflectionProperty;

/**
 * Adjusts the value read from Doctrine `datetime` columns to be ISO 8601 formatted strings.
 * Otherwise, it has the same capabilities as the parent class.
 */
class Iso8601PropertyAccessor extends ProxyPropertyAccessor
{
    protected function adjustReturnValue(mixed $value, ReflectionProperty $reflectionProperty): mixed
    {
        if (!$value instanceof DateTimeInterface) {
            return $value;
        }

        $declaringClass = $reflectionProperty->getDeclaringClass()->getName();
        $propertyName = $reflectionProperty->getName();
        $metadata = $this->objectManager->getClassMetadata($declaringClass);

        if ($metadata->hasField($propertyName) && 'datetime' === $metadata->getTypeOfField($propertyName)) {
            return Carbon::instance($value)->toIso8601String();
        }

        return $value;
    }
}
