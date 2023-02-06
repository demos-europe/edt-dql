<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr\Comparison;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-extends AbstractClauseFunction<bool>
 */
class Greater extends AbstractClauseFunction
{
    public function __construct(ClauseFunctionInterface $left, ClauseFunctionInterface $right)
    {
        parent::__construct(
            new \EDT\Querying\Functions\Greater($left, $right),
            $left, $right
        );
    }

    public function asDql(array $valueReferences, array $propertyAliases): Comparison
    {
        [$left, $right] = $this->getDqls($valueReferences, $propertyAliases);
        return $this->expr->gt($left, $right);
    }
}
