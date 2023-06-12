<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Math;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;
use InvalidArgumentException;

/**
 * @template-extends AbstractClauseFunction<int|float>
 */
class Sum extends AbstractClauseFunction
{
    /**
     * @param ClauseFunctionInterface<numeric> $firstAddend
     * @param ClauseFunctionInterface<numeric> $secondAddend
     * @param ClauseFunctionInterface<numeric> ...$additionalAddends
     */
    public function __construct(ClauseFunctionInterface $firstAddend, ClauseFunctionInterface $secondAddend, ClauseFunctionInterface ...$additionalAddends)
    {
        parent::__construct(
            new \EDT\Querying\Functions\Sum($firstAddend, $secondAddend, ...$additionalAddends),
            $firstAddend, $secondAddend, ...$additionalAddends
        );
    }

    public function asDql(array $valueReferences, array $propertyAliases): Composite|Math|Func|Comparison|string
    {
        $dqlClauses = $this->getDqls($valueReferences, $propertyAliases);
        $initial = array_shift($dqlClauses);
        $sumDql = array_reduce($dqlClauses, [$this, 'sumReduce'], $initial);

        return $sumDql ?? throw new InvalidArgumentException('Not enough DQL clauses to create SUM expression.');
    }

    protected function sumReduce(
        Composite|Math|Func|Comparison|string $carry,
        Composite|Math|Func|Comparison|string $dqlClause
    ): Math {
        return $this->expr->sum($carry, $dqlClause);
    }
}
