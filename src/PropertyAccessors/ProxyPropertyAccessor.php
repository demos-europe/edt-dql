<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\PropertyAccessors;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\Proxy;
use EDT\Querying\PropertyAccessors\ReflectionPropertyAccessor;
use ReflectionException;
use Webmozart\Assert\Assert;
use function is_array;

class ProxyPropertyAccessor extends ReflectionPropertyAccessor
{
    public function __construct(
        protected readonly ObjectManager $objectManager
    ) {}

    /**
     * Sets a property values of the given target.
     *
     * When handling to-many relationships they are mostly passed around as `array`. However, the actual to-many
     * relationship properties in Doctrine entities are of the type {@link Collection}. This implementation will check
     * if a to-many relationship is to be set to an `array` value and automatically wraps the array inside a
     * {@link ArrayCollection}.
     *
     * @throws ReflectionException
     */
    public function setValue(object $target, mixed $value, string $propertyName): void
    {
        $targetClass = $this->getClass($target);

        if (is_array($value) && !$this->objectManager->getMetadataFactory()->isTransient($targetClass)) {
            $metadata = $this->objectManager->getClassMetadata($targetClass);
            Assert::isInstanceOf($metadata, ClassMetadataInfo::class);
            $mappingType = $metadata->associationMappings[$propertyName]['type'] ?? null;
            $isToManyRelationship = match ($mappingType) {
                ClassMetadataInfo::ONE_TO_MANY, ClassMetadataInfo::MANY_TO_MANY => true,
                default => false
            };
            if ($isToManyRelationship) {
                $value = new ArrayCollection($value);
            }
        }

        $reflectionProperty = $this->getReflectionProperty($targetClass, $propertyName);
        $reflectionProperty->setValue($target, $value);
    }

    /**
     * Will determine the correct class, even if the `$target` is wrapped into a Doctrine {@link Proxy} instance.
     *
     * Will also bust lazy loading in case of a {@link Proxy}, to allow correct property access via reflection.
     *
     * @throws ReflectionException
     */
    protected function getClass(object $target): string
    {
        $class = parent::getClass($target);
        if ($target instanceof Proxy) {
            // Bust lazy loading, to allow correct property access via reflection.
            $target->__load();
            // If the instance is wrapped in a proxy we need to get the actual class name out of it
            $classMetadata = $this->objectManager->getClassMetadata($class);
            if (!$classMetadata instanceof ClassMetadataInfo) {
                $metadataClass = $classMetadata::class;
                throw new ReflectionException("Unable to determine actual class of target for reflection access. Target is a doctrine proxy object but the corresponding metadata class ($metadataClass) did not the necessary 'rootEntityName' field.");
            }
            $class = $classMetadata->rootEntityName;
        }
        return $class;
    }
}
