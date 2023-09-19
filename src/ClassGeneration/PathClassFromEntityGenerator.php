<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\ClassGeneration;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use EDT\PathBuilding\End;
use EDT\PathBuilding\PropertyAutoPathInterface;
use EDT\PathBuilding\PropertyAutoPathTrait;
use Nette\PhpGenerator\PhpFile;
use ReflectionClass;
use ReflectionProperty;
use Webmozart\Assert\Assert;

/**
 * Generates source code for a class implementing {@link PropertyAutoPathInterface}.
 * The generated class corresponds to a Doctrine entity class and will expose all
 * Doctrine properties as path segments.
 *
 * Properties in parents of the given entity class may or may not be supported.
 */
class PathClassFromEntityGenerator
{
    use EntityBasedGeneratorTrait;

    /**
     * Generates a class with methods to start each given path class.
     *
     * @param list<array{0: non-empty-string, 1: non-empty-string}> $pathClasses
     * @param non-empty-string $targetName
     * @param non-empty-string $targetNamespace
     */
    public function generateEntryPointClass(array $pathClasses, string $targetName, string $targetNamespace): PhpFile
    {
        $newFile = new PhpFile();
        $newFile->setStrictTypes();

        $namespace = $newFile->addNamespace($targetNamespace);
        $class = $namespace->addClass($targetName);

        $class->addComment('WARNING: THIS CLASS IS AUTOGENERATED.');
        $class->addComment("MANUAL CHANGES WILL BE LOST ON RE-GENERATION.\n");

        // Iterate over the properties of the entity class
        foreach ($pathClasses as $pathClass) {
            $fqcn = "$pathClass[0]\\$pathClass[1]";

            $method = $class->addMethod(lcfirst($pathClass[1]));
            $method->setStatic();
            $method->setPublic();

            $method->addBody("return $fqcn::startPath();");
            $method->setReturnType($fqcn);
        }

        return $newFile;
    }
    
    /**
     * Generate a path class from the given base class.
     *
     * @param ReflectionClass<object> $reflectionClass
     * @param non-empty-string $targetName
     * @param non-empty-string $targetNamespace
     */
    public function generatePathClass(ReflectionClass $reflectionClass, string $targetName, string $targetNamespace): PhpFile
    {
        $newFile = new PhpFile();
        $newFile->setStrictTypes();

        $namespace = $newFile->addNamespace($targetNamespace);
        $class = $namespace->addClass($targetName);

        $class->addComment('WARNING: THIS CLASS IS AUTOGENERATED.');
        $class->addComment("MANUAL CHANGES WILL BE LOST ON RE-GENERATION.\n");
        $class->addComment('To add additional properties, you may want to');
        $class->addComment("create an extending class and add them there.\n");

        $class->addImplement(PropertyAutoPathInterface::class);
        $class->addTrait(PropertyAutoPathTrait::class);

        $this->processProperties(
            $reflectionClass->getProperties(),
            function (
                ReflectionProperty $property,
                Column|OneToMany|OneToOne|ManyToOne|ManyToMany $doctrineClass
            ) use ($class): void {
                $propertyType = $this->mapToClass($doctrineClass);
                $class->addComment("@property-read $propertyType \${$property->getName()}");
            }
        );

        return $newFile;
    }

    /**
     * @return non-empty-string
     */
    protected function mapToClass(Column|OneToMany|OneToOne|ManyToOne|ManyToMany $annotationOrAttribute): string
    {
        if ($annotationOrAttribute instanceof Column) {
            return End::class;
        }

        /** @var OneToMany|OneToOne|ManyToOne|ManyToMany $annotationOrAttribute */
        return "{$annotationOrAttribute->targetEntity}Path";
    }
}
