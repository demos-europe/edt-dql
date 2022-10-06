<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-extends AbstractClauseFunction<bool>
 */
class BetweenInclusive extends AbstractClauseFunction
{
    /**
     * @param ClauseFunctionInterface<numeric> $min
     * @param ClauseFunctionInterface<numeric> $max
     * @param ClauseFunctionInterface<numeric> $value
     */
    public function __construct(ClauseFunctionInterface $min, ClauseFunctionInterface $max, ClauseFunctionInterface $value) {
        parent::__construct(
            new \EDT\Querying\Functions\BetweenInclusive($min, $max, $value),
            $min, $max, $value
        );
    }

    public function asDql(array $valueReferences, array $propertyAliases)
    {
        [$min, $max, $value] = $this->getDqls($valueReferences, $propertyAliases);
        return $this->expr->between($value, $min, $max);
    }
}
