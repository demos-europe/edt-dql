<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Math;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-implements ClauseFunctionInterface<numeric>
 */
class Sum extends \EDT\Querying\Functions\Sum implements ClauseFunctionInterface
{
    use ClauseBasedTrait;

    /**
     * @var Expr
     */
    private $expr;

    /**
     * @phpstan-param ClauseFunctionInterface<numeric> $firstAddend
     * @phpstan-param ClauseFunctionInterface<numeric> $secondAddend
     * @phpstan-param ClauseFunctionInterface<numeric> ...$additionalAddends
     */
    public function __construct(ClauseFunctionInterface $firstAddend, ClauseFunctionInterface $secondAddend, ClauseFunctionInterface ...$additionalAddends)
    {
        parent::__construct($firstAddend, $secondAddend, ...$additionalAddends);
        $this->setClauses($firstAddend, $secondAddend, ...$additionalAddends);
        $this->expr = new Expr();
    }

    public function asDql(array $valueReferences, array $propertyAliases)
    {
        $dqlClauses = $this->getDqls($valueReferences, $propertyAliases);
        $initial = array_shift($dqlClauses);
        return array_reduce($dqlClauses, [$this, 'sumReduce'], $initial);
    }

    /**
     * @param Composite|Math|Func|Comparison|string $carry
     * @param Composite|Math|Func|Comparison|string $dqlClause
     */
    // do not typehint the function parameters as strings, as we must not implicitly invoke __toString
    private function sumReduce($carry, $dqlClause): Math
    {
        return $this->expr->sum($carry, $dqlClause);
    }
}
