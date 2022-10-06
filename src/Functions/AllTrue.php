<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr\Andx;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-extends AbstractClauseFunction<bool>
 */
class AllTrue extends AbstractClauseFunction
{
    /**
     * @param ClauseFunctionInterface<bool> $firstFunction
     * @param ClauseFunctionInterface<bool> ...$additionalFunctions
     */
    public function __construct(ClauseFunctionInterface $firstFunction, ClauseFunctionInterface ...$additionalFunctions)
    {
        parent::__construct(
            new \EDT\Querying\Functions\AllEqual(
                new \EDT\Querying\Functions\Value(true),
                $firstFunction,
                ...$additionalFunctions
            ),
            $firstFunction,
            ...$additionalFunctions
        );
    }

    /**
     * @return Andx
     */
    public function asDql(array $valueReferences, array $propertyAliases): Andx
    {
        return new Andx($this->getDqls($valueReferences, $propertyAliases));
    }
}
