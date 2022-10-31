<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Andx;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-extends AbstractClauseFunction<bool>
 */
class AllEqual extends AbstractClauseFunction
{
    /**
     * @param ClauseFunctionInterface<mixed> $firstFunction
     * @param ClauseFunctionInterface<mixed> $secondFunction
     * @param ClauseFunctionInterface<mixed> ...$additionalFunctions
     */
    public function __construct(ClauseFunctionInterface $firstFunction, ClauseFunctionInterface $secondFunction, ClauseFunctionInterface ...$additionalFunctions)
    {
        parent::__construct(
            new \EDT\Querying\Functions\AllEqual($firstFunction, $secondFunction, ...$additionalFunctions),
            $firstFunction, $secondFunction, ...$additionalFunctions
        );
    }

    public function asDql(array $valueReferences, array $propertyAliases): Andx
    {
        $dqls = $this->getDqls($valueReferences, $propertyAliases);

        $firstDql = array_shift($dqls);
        $eqs = array_map(fn ($dql): Expr\Comparison => $this->expr->eq($firstDql, $dql), $dqls);

        return new Andx($eqs);
    }
}
