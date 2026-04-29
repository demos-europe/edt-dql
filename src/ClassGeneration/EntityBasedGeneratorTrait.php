<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\ClassGeneration;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use EDT\Parsing\Utilities\Types\ClassOrInterfaceType;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionProperty;
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

        foreach ($properties as $property) {
            $attributes = $this->parseAttributes($property);

            Assert::lessThanEq(
                count($attributes),
                1,
                "Found multiple relevant attributes on property `{$property->getName()}`, expected none or one."
            );

            if ([] === $attributes) {
                continue;
            }

            $doctrineClass = array_pop($attributes);
            $result[$property->getName()] = $callback($property, $doctrineClass);
        }

        return $result;
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

        return array_values(array_filter(
            $attributes,
            static fn (object $input): bool => $input instanceof Column
                || $input instanceof OneToMany
                || $input instanceof OneToOne
                || $input instanceof ManyToOne
                || $input instanceof ManyToMany
        ));
    }

    /**
     * @param non-empty-string $propertyName
     *
     * @return non-empty-string
     */
    protected function buildReference(ClassOrInterfaceType $entityType, string $propertyName): string
    {
        $referencedClass = $entityType->getShortClassName();

        return "{@link $referencedClass::$propertyName}";
    }

    /**
     * @param non-empty-string $entityClass
     * @param non-empty-string $propertyName
     *
     * @return class-string
     */
    protected function getTargetEntityClass(string $entityClass, string $propertyName, OneToMany|ManyToOne|OneToOne|ManyToMany $annotationOrAttribute): string
    {
        $targetEntityClass = $annotationOrAttribute->targetEntity;
        Assert::notNull($targetEntityClass);
        if (!interface_exists($targetEntityClass) && !class_exists($targetEntityClass)) {
            throw new InvalidArgumentException(
                "Doctrine relationship was defined via annotation/attribute, but the type set as target entity (`$targetEntityClass`) could not be found. Make sure it uses the fully qualified name. The problematic relationship is: `$entityClass::$propertyName`."
            );
        }

        return $targetEntityClass;
    }
}
