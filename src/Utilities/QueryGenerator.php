<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Utilities;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use EDT\DqlQuerying\Contracts\ClauseInterface;
use EDT\DqlQuerying\Contracts\MappingException;
use EDT\DqlQuerying\Contracts\OrderByInterface;
use EDT\Querying\Contracts\SliceException;

class QueryGenerator
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param class-string                            $entityClass
     * @param list<ClauseInterface>                   $conditions
     * @param list<OrderByInterface>                  $sortMethods
     * @param array<non-empty-string,ClauseInterface> $selections
     *
     * @throws MappingException
     * @throws SliceException
     */
    public function generateQueryBuilder(string $entityClass, array $conditions, array $sortMethods = [], int $offset = 0, int $limit = null, array $selections = []): QueryBuilder
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $metadataFactory = $this->entityManager->getMetadataFactory();
        $builderPreparer = new QueryBuilderPreparer($entityClass, $metadataFactory);
        $builderPreparer->setSelectExpressions($selections);
        $builderPreparer->setWhereExpressions($conditions);
        $builderPreparer->setOrderByExpressions(...$sortMethods);
        $builderPreparer->fillQueryBuilder($queryBuilder);

        // add offset if needed
        if (0 !== $offset) {
            if (0 > $offset) {
                throw SliceException::negativeOffset($offset);
            }
            $queryBuilder->setFirstResult($offset);
        }

        // add limit if needed
        if (null !== $limit) {
            if (0 > $limit) {
                throw SliceException::negativeLimit($limit);
            }
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder;
    }
}
