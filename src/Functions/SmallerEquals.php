<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Comparison;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-implements ClauseFunctionInterface<bool>
 */
class SmallerEquals extends \EDT\Querying\Functions\SmallerEquals implements ClauseFunctionInterface
{
    use ClauseBasedTrait;

    public function __construct(ClauseFunctionInterface $left, ClauseFunctionInterface $right)
    {
        parent::__construct($left, $right);
        $this->setClauses($left, $right);
    }

    public function asDql(array $valueReferences, array $propertyAliases): Comparison
    {
        [$left, $right] = $this->getDqls($valueReferences, $propertyAliases);
        return (new Expr())->lte($left, $right);
    }
}
