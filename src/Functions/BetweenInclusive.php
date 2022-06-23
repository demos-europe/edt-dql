<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-implements ClauseFunctionInterface<bool>
 */
class BetweenInclusive extends \EDT\Querying\Functions\BetweenInclusive implements ClauseFunctionInterface
{
    use ClauseBasedTrait;

    /**
     * @phpstan-param ClauseFunctionInterface<numeric> $min
     * @phpstan-param ClauseFunctionInterface<numeric> $max
     * @phpstan-param ClauseFunctionInterface<numeric> $value
     */
    public function __construct(ClauseFunctionInterface $min, ClauseFunctionInterface $max, ClauseFunctionInterface $value) {
        parent::__construct($min, $max, $value);
        $this->setClauses($min, $max, $value);
    }

    public function asDql(array $valueReferences, array $propertyAliases)
    {
        [$min, $max, $value] = $this->getDqls($valueReferences, $propertyAliases);
        return (new Expr())->between($value, $min, $max);
    }
}
