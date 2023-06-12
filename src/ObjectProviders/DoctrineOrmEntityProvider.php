<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\ObjectProviders;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use EDT\DqlQuerying\Contracts\ClauseInterface;
use EDT\DqlQuerying\Contracts\MappingException;
use EDT\DqlQuerying\Utilities\QueryBuilderPreparer;
use EDT\DqlQuerying\Contracts\OrderByInterface;
use EDT\Querying\Pagination\OffsetPagination;
use EDT\Querying\Contracts\OffsetEntityProviderInterface;
use EDT\Querying\Contracts\PaginationException;
use Webmozart\Assert\Assert;

/**
 * @template TEntity of object
 * @template-implements OffsetEntityProviderInterface<ClauseInterface, OrderByInterface, TEntity>
 */
class DoctrineOrmEntityProvider implements OffsetEntityProviderInterface
{
    /**
     * @param class-string<TEntity> $entityClass
     */
    public function __construct(
        protected readonly EntityManager $entityManager,
        protected readonly QueryBuilderPreparer $builderPreparer,
        protected readonly string $entityClass
    ) {}

    public function getEntities(array $conditions, array $sortMethods, ?OffsetPagination $pagination): array
    {
        if (null === $pagination) {
            $offset = 0;
            $limit = null;
        } else {
            $offset = $pagination->getOffset();
            $limit = $pagination->getLimit();
        }

        $queryBuilder = $this->generateQueryBuilder($conditions, $sortMethods, $offset, $limit);
        $result = $queryBuilder->getQuery()->getResult();
        Assert::isList($result);
        Assert::allIsInstanceOf($result, $this->entityClass);

        return $result;
    }

    /**
     * @param list<ClauseInterface>  $conditions
     * @param list<OrderByInterface> $sortMethods
     * @param int<0, max>            $offset
     * @param int<0, max>|null       $limit
     *
     * @throws MappingException
     * @throws PaginationException
     */
    public function generateQueryBuilder(
        array $conditions,
        array $sortMethods = [],
        int $offset = 0,
        int $limit = null
    ): QueryBuilder {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $this->builderPreparer->setWhereExpressions($conditions);
        $this->builderPreparer->setOrderByExpressions($sortMethods);
        $this->builderPreparer->fillQueryBuilder($queryBuilder);

        // add offset if needed
        if (0 !== $offset) {
            $queryBuilder->setFirstResult($offset);
        }

        // add limit if needed
        if (null !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder;
    }
}
