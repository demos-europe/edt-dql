<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\PropertyAccessors;

use Carbon\Carbon;
use DateTimeInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Column;
use Doctrine\Persistence\ObjectManager;
use ReflectionProperty;

/**
 * Adjusts the value read from Doctrine `datetime` columns to be ISO 8601 formatted strings.
 * Otherwise, it has the same capabilities as the parent class.
 */
class Iso8601PropertyAccessor extends ProxyPropertyAccessor
{
    public function __construct(
        ObjectManager $objectManager,
        protected readonly AnnotationReader $annotationReader = new AnnotationReader()
    ) {
        parent::__construct($objectManager);
    }

    protected function adjustReturnValue(mixed $value, ReflectionProperty $reflectionProperty): mixed
    {
        if (null === $value) {
            return $value;
        }

        $annotation = $this->annotationReader->getPropertyAnnotation($reflectionProperty, Column::class);
        if ('datetime' === $annotation?->type && $value instanceof DateTimeInterface) {
            $value = Carbon::instance($value)->toIso8601String();
        }

        return $value;
    }
}
