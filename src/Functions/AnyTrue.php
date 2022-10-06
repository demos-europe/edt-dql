<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr\Orx;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-extends AbstractClauseFunction<bool>
 */
class AnyTrue extends AbstractClauseFunction
{
    /**
     * @param ClauseFunctionInterface<bool> $firstFunction
     * @param ClauseFunctionInterface<bool> ...$additionalFunctions
     */
    public function __construct(ClauseFunctionInterface $firstFunction, ClauseFunctionInterface ...$additionalFunctions)
    {
        parent::__construct(
            new \EDT\Querying\Functions\AnyTrue($firstFunction, ...$additionalFunctions),
            $firstFunction, ...$additionalFunctions
        );
    }

    /**
     * @return Orx
     */
    public function asDql(array $valueReferences, array $propertyAliases): Orx
    {
        return new Orx($this->getDqls($valueReferences, $propertyAliases));
    }
}
