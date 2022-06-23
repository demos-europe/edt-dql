<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\ObjectProviders;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;
use EDT\DqlQuerying\Contracts\MappingException;
use EDT\DqlQuerying\Utilities\QueryGenerator;
use EDT\DqlQuerying\Contracts\OrderByInterface;
use EDT\Querying\Contracts\ObjectProviderInterface;
use EDT\Querying\Contracts\SliceException;
use EDT\Querying\Contracts\SortMethodInterface;

/**
 * @template T of object
 * @template-implements ObjectProviderInterface<T>
 */
class DoctrineOrmEntityProvider implements ObjectProviderInterface
{
    /**
     * @var QueryGenerator
     */
    private $queryGenerator;
    /**
     * @var class-string<T>
     */
    private $className;

    /**
     * @phpstan-param class-string<T> $className
     */
    public function __construct(string $className, EntityManager $entityManager)
    {
        $this->className = $className;
        $this->queryGenerator = new QueryGenerator($entityManager);
    }

    /**
     * @param array<int,ClauseFunctionInterface<bool>>        $conditions
     * @param array<int,SortMethodInterface|OrderByInterface> $sortMethods
     *
     * @return iterable<T>
     *
     * @throws MappingException
     */
    public function getObjects(array $conditions, array $sortMethods = [], int $offset = 0, int $limit = null): iterable
    {
        $queryBuilder = $this->generateQueryBuilder($conditions, $sortMethods, $offset, $limit);
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param array<int,ClauseFunctionInterface<bool>>        $conditions
     * @param array<int,SortMethodInterface|OrderByInterface> $sortMethods
     *
     * @throws MappingException
     * @throws SliceException
     */
    public function generateQueryBuilder(array $conditions, array $sortMethods = [], int $offset = 0, int $limit = null): QueryBuilder
    {
        return $this->queryGenerator->generateQueryBuilder($this->className, $conditions, $sortMethods, $offset, $limit);
    }
}
