<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\PropertyAccessors;

use Carbon\Carbon;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Column;
use Doctrine\Persistence\ObjectManager;
use ReflectionProperty;

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
        if ('datetime' === $annotation?->type) {
            $value = Carbon::instance($value)->toIso8601String();
        }

        return $value;
    }
}
