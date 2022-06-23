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
class Product extends \EDT\Querying\Functions\Product implements ClauseFunctionInterface
{
    use ClauseBasedTrait;

    /**
     * @var Expr
     */
    private $expr;

    /**
     * @phpstan-param ClauseFunctionInterface<numeric> $firstFactor
     * @phpstan-param ClauseFunctionInterface<numeric> $secondFactor
     * @phpstan-param ClauseFunctionInterface<numeric> ...$additionalFactors
     */
    public function __construct(ClauseFunctionInterface $firstFactor, ClauseFunctionInterface $secondFactor, ClauseFunctionInterface ...$additionalFactors)
    {
        parent::__construct($firstFactor, $secondFactor, ...$additionalFactors);
        $this->setClauses($firstFactor, $secondFactor, ...$additionalFactors);
        $this->expr = new Expr();
    }

    public function asDql(array $valueReferences, array $propertyAliases)
    {
        $dqlClauses = $this->getDqls($valueReferences, $propertyAliases);
        $initial = array_shift($dqlClauses);
        return array_reduce($dqlClauses, [$this, 'prodReduce'], $initial);
    }

    /**
     * @param Composite|Math|Func|Comparison|string $carry
     * @param Composite|Math|Func|Comparison|string $dqlClause
     */
    // do not typehint the function parameters as strings, as we must not implicitly invoke __toString
    private function prodReduce($carry, $dqlClause): Math
    {
        return $this->expr->prod($carry, $dqlClause);
    }
}
