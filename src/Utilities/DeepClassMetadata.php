<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Utilities;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException as OrmMappingException;
use Doctrine\Persistence\Mapping\MappingException as PersistenceMappingException;
use EDT\DqlQuerying\Contracts\MappingException;
use InvalidArgumentException;
use ReflectionException;

/**
 * Uses the Doctrine mapping information to determine the properties of the given entity.
 *
 * Does not provide any access restrictions!
 */
class DeepClassMetadata extends ClassMetadataInfo
{
    /**
     * @var ClassMetadataInfo
     */
    private $baseMetadata;

    /**
     * @var ClassMetadataFactory
     */
    private $metadataFactory;

    public function __construct(ClassMetadataInfo $metadata, ClassMetadataFactory $metadataFactory)
    {
        parent::__construct($metadata->getName(), $metadata->namingStrategy);
        $this->baseMetadata = $metadata;
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * The entity type (fully qualified namespace) to return from the query.
     *
     * @return string The fully qualified identifier of the entity this definition belongs to. Will
     * be used in doctrine to select the correct entity instances to return.
     */
    public function getName(): string
    {
        return $this->baseMetadata->getName();
    }

    /**
     * @return string The alias to be used in DQL joins. Must meet doctrines requirements regarding
     * allowed characters and must be unique over all entities to avoid conflicts when joining.
     */
    public function getTableName(): string
    {
        return $this->baseMetadata->getTableName();
    }

    /**
     * @throws MappingException
     */
    public function getTargetClassMetadata(string $relationshipName): DeepClassMetadata
    {
        try {
            $entityClass = $this->baseMetadata->getAssociationTargetClass($relationshipName);
            $classMetadata = $this->metadataFactory->getMetadataFor($entityClass);
            if (!$classMetadata instanceof ClassMetadataInfo) {
                $type = get_class($classMetadata);
                throw new InvalidArgumentException("Expected ClassMetadataInfo, got $type");
            }

            return new DeepClassMetadata($classMetadata, $this->metadataFactory);
        } catch (OrmMappingException | PersistenceMappingException | ReflectionException $e) {
            throw MappingException::relationshipUnavailable($relationshipName, $this->getName(), $e);
        }
    }

    public function hasAssociation($propertyName): bool
    {
        return $this->baseMetadata->hasAssociation($propertyName);
    }
}
