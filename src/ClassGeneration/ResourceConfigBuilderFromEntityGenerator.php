<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\ClassGeneration;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use EDT\JsonApi\PropertyConfig\Builder\AttributeConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\Builder\ToManyRelationshipConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\Builder\ToOneRelationshipConfigBuilderInterface;
use EDT\JsonApi\ResourceConfig\Builder\MagicResourceConfigBuilder;
use Nette\PhpGenerator\PhpFile;
use ReflectionClass;
use ReflectionProperty;

class ResourceConfigBuilderFromEntityGenerator
{
    use EntityBasedGeneratorTrait;

    /**
     * @param class-string $conditionClass
     * @param class-string $sortingClass
     */
    public function __construct(
        protected readonly string $conditionClass,
        protected readonly string $sortingClass
    ) {}

    /**
     * Generate a config builder class from the given base class.
     *
     * @param ReflectionClass<object> $reflectionClass
     * @param non-empty-string $targetName
     * @param non-empty-string $targetNamespace
     */
    public function generateConfigBuilderClass(
        ReflectionClass $reflectionClass,
        string $targetName,
        string $targetNamespace
    ): PhpFile {
        $newFile = new PhpFile();
        $newFile->setStrictTypes();

        $parentClass = MagicResourceConfigBuilder::class;
        $entityClass = $reflectionClass->getName();

        $namespace = $newFile->addNamespace($targetNamespace);
        $namespace->addUse($parentClass);
        $class = $namespace->addClass($targetName);

        $class->addComment('WARNING: THIS CLASS IS AUTOGENERATED.');
        $class->addComment("MANUAL CHANGES WILL BE LOST ON RE-GENERATION.\n");
        $class->addComment('To add additional properties, you may want to');
        $class->addComment("create an extending class and add them there.\n");

        $class->addImplement($parentClass);
        $class->addComment("@template-implements $parentClass<$this->conditionClass, $this->sortingClass, $entityClass>\n");

        $this->processProperties(
            $reflectionClass->getProperties(),
            function (
                ReflectionProperty $property,
                Column|OneToMany|OneToOne|ManyToOne|ManyToMany $doctrineClass
            ) use ($class, $entityClass): void {
                $propertyType = $this->mapToClass($entityClass, $doctrineClass);
                $class->addComment("@property-read $propertyType \${$property->getName()}");
            }
        );

        return $newFile;
    }

    /**
     * @return non-empty-string
     */
    protected function mapToClass(string $entityClass, Column|OneToMany|OneToOne|ManyToOne|ManyToMany $annotationOrAttribute): string
    {
        if ($annotationOrAttribute instanceof Column) {
            $class = AttributeConfigBuilderInterface::class;
            return "$class<$this->conditionClass, $entityClass>";
        }

        $class = $annotationOrAttribute instanceof ManyToMany || $annotationOrAttribute instanceof OneToMany
            ? ToManyRelationshipConfigBuilderInterface::class
            : ToOneRelationshipConfigBuilderInterface::class;

        return "$class<$this->conditionClass, $this->sortingClass, $entityClass, $annotationOrAttribute->targetEntity>";
    }
}