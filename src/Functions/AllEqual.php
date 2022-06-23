<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Andx;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-implements ClauseFunctionInterface<bool>
 */
class AllEqual extends \EDT\Querying\Functions\AllEqual implements ClauseFunctionInterface
{
    use ClauseBasedTrait;

    /**
     * @param ClauseFunctionInterface<mixed> $firstFunction
     * @param ClauseFunctionInterface<mixed> $secondFunction
     * @param ClauseFunctionInterface<mixed> ...$additionalFunctions
     */
    public function __construct(ClauseFunctionInterface $firstFunction, ClauseFunctionInterface $secondFunction, ClauseFunctionInterface ...$additionalFunctions)
    {
        parent::__construct($firstFunction, $secondFunction, ...$additionalFunctions);
        $this->setClauses($firstFunction, $secondFunction, ...$additionalFunctions);
    }

    public function asDql(array $valueReferences, array $propertyAliases): Andx
    {
        $dqls = $this->getDqls($valueReferences, $propertyAliases);

        $expr = new Expr();
        $firstDql = array_shift($dqls);
        $eqs = array_map(static function ($dql) use ($expr, $firstDql): Expr\Comparison {
            return $expr->eq($firstDql, $dql);
        }, $dqls);
        return new Andx($eqs);
    }
}
