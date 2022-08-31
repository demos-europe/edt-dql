<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-extends AbstractClauseFunction<bool>
 */
class GreaterEquals extends AbstractClauseFunction
{
    public function __construct(ClauseFunctionInterface $left, ClauseFunctionInterface $right)
    {
        parent::__construct(
            new \EDT\Querying\Functions\GreaterEquals($left, $right),
            $left, $right
        );
    }

    public function asDql(array $valueReferences, array $propertyAliases)
    {
        [$left, $right] = $this->getDqls($valueReferences, $propertyAliases);
        return $this->expr->gte($left, $right);
    }
}
