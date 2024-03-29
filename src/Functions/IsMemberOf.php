<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr\Comparison;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * While the {@link \EDT\Querying\Functions\OneOf base function} covers both the cases
 * to check if a given array contains a value or a given value is present in an array,
 * this clause function will explicitly use the DQL function `MEMBER OF` to check if a given value
 * is present in an array.
 *
 * An {@link OneOf alternative} implementation exists explicitly covering the other case.
 *
 * @template-extends AbstractClauseFunction<bool>
 */
class IsMemberOf extends AbstractClauseFunction
{
    /**
     * @template V
     * @param ClauseFunctionInterface<array<V>> $contains
     * @param ClauseFunctionInterface<V>        $contained
     */
    public function __construct(ClauseFunctionInterface $contains, ClauseFunctionInterface $contained)
    {
        parent::__construct(
            new \EDT\Querying\Functions\OneOf($contains, $contained),
            $contains, $contained
        );
    }

    public function asDql(array $valueReferences, array $propertyAliases, string $mainEntityAlias): Comparison
    {
        [$contains, $contained] = $this->getDqls($valueReferences, $propertyAliases, $mainEntityAlias);
        return $this->expr->isMemberOf($contained, $contains);
    }
}
