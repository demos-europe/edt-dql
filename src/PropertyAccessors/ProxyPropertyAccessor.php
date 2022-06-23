<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\PropertyAccessors;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\Proxy;
use EDT\Querying\PropertyAccessors\ReflectionPropertyAccessor;
use ReflectionException;

class ProxyPropertyAccessor extends ReflectionPropertyAccessor
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
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
                $metadataClass = get_class($classMetadata);
                throw new ReflectionException("Unable to determine actual class of target for reflection access. Target is a doctrine proxy object but the corresponding metadata class ($metadataClass) did not the necessary 'rootEntityName' field.");
            }
            $class = $classMetadata->rootEntityName;
        }
        return $class;
    }
}
