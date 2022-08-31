<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr\Comparison;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-extends AbstractClauseFunction<bool>
 */
class StringEndsWith extends AbstractClauseFunction
{
    /**
     * @param ClauseFunctionInterface<string|null> $contains
     * @param ClauseFunctionInterface<string|null> $contained
     */
    public function __construct(ClauseFunctionInterface $contains, ClauseFunctionInterface $contained)
    {
        parent::__construct(
            new \EDT\Querying\Functions\StringEndsWith($contains, $contained, false),
            $contains, $contained
        );
    }

    public function asDql(array $valueReferences, array $propertyAliases): Comparison
    {
        [$contains, $contained] = $this->getDqls($valueReferences, $propertyAliases);
        return $this->expr->like($contains, $this->expr->concat('%', $contained));
    }
}
