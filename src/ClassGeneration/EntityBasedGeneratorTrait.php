<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\ClassGeneration;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Webmozart\Assert\Assert;

trait EntityBasedGeneratorTrait
{
    /**
     * @template TResult
     *
     * @param list<ReflectionProperty> $properties
     * @param callable(ReflectionProperty, Column|OneToMany|OneToOne|ManyToOne|ManyToMany): TResult $callback mapping from the property name to the result of the callback
     *
     * @return array<non-empty-string, TResult>
     */
    protected function processProperties(array $properties, callable $callback): array
    {
        $result = [];

        // Iterate over the properties of the entity class
        foreach ($properties as $property) {
            $annotations = $this->parseAnnotations($property);
            $attributes = $this->parseAttributes($property);

            Assert::lessThanEq(count($annotations) + count($attributes), 1);
            if ([] !== $annotations) {
                $used = $annotations;
            } elseif ([] !== $attributes) {
                $used = $attributes;
            } else {
                continue;
            }

            $doctrineClass = array_pop($used);
            $result[$property->getName()] = $callback($property, $doctrineClass);
        }

        return $result;
    }

    /**
     * @return list<Column|OneToMany|OneToOne|ManyToOne|ManyToMany>
     */
    protected function parseAnnotations(ReflectionProperty $property): array
    {
        $annotationReader = new AnnotationReader();
        $cacheProvider = new ArrayAdapter();
        $reader = new PsrCachedReader($annotationReader, $cacheProvider);

        return array_filter(
            array_values($reader->getPropertyAnnotations($property)),
            static fn (object $input): bool => $input instanceof Column
                || $input instanceof OneToMany
                || $input instanceof OneToOne
                || $input instanceof ManyToOne
                || $input instanceof ManyToMany
        );
    }

    /**
     * @return list<Column|OneToMany|OneToOne|ManyToOne|ManyToMany>
     */
    protected function parseAttributes(ReflectionProperty $property): array
    {
        $attributes = array_map(
            static fn (
                ReflectionAttribute $reflectionAttribute
            ): object => $reflectionAttribute->newInstance(),
            array_values($property->getAttributes())
        );

        return array_filter(
            $attributes,
            static fn (object $input): bool => $input instanceof Column
                || $input instanceof OneToMany
                || $input instanceof OneToOne
                || $input instanceof ManyToOne
                || $input instanceof ManyToMany
        );
    }
}
