<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\ClassGeneration;

use EDT\Parsing\Utilities\Types\ClassOrInterfaceType;
use EDT\Parsing\Utilities\Types\TypeInterface;
use ReflectionClass;
use Webmozart\Assert\Assert;

abstract class AbstractTypeFromInterfaceDetector
{
    public function __invoke(TypeInterface $type): TypeInterface
    {
        $fqcn = $type->getFullyQualifiedName();
        Assert::notNull($fqcn);

        $reflectionClass = new ReflectionClass($fqcn);
        $interfaces = $reflectionClass->getInterfaceNames();
        Assert::allInterfaceExists($interfaces);

        $interfaces = array_filter(
            $interfaces,
            fn(string $interface): bool => $this->isCorrectInterface($interface, $reflectionClass)
        );

        Assert::minCount($interfaces, 1, "Found no matching interface for class `$fqcn`.");
        Assert::count($interfaces, 1, "Expected one matching interface for class `$fqcn`, got: ".implode(',', $interfaces));

        // TODO: detect template parameters used for interface
        return ClassOrInterfaceType::fromFqcn(array_pop($interfaces), []);
    }

    /**
     * @param class-string $interface
     * @param ReflectionClass<object> $class
     */
    abstract protected function isCorrectInterface(string $interface, ReflectionClass $class): bool;
}
