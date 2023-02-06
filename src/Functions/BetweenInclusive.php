<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;
use Webmozart\Assert\Assert;

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

    /**
     * @return non-empty-string
     */
    public function asDql(array $valueReferences, array $propertyAliases): string
    {
        [$min, $max, $value] = $this->getDqls($valueReferences, $propertyAliases);
        $between = $this->expr->between($value, $min, $max);
        Assert::stringNotEmpty($between);

        return $between;
    }
}
