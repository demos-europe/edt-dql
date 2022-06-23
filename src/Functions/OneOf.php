<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * While the {@link \EDT\Querying\Functions\OneOf base function} covers both the cases
 * to check if a given array contains a value or a given value is present in an array,
 * this clause function will explicitly use the DQL function `IN` to check if a value is present in
 * a given array.
 *
 * An alternative {@link IsMemberOf} implementation exists explicitly covering the other case.
 *
 * @template-implements ClauseFunctionInterface<bool>
 */
class OneOf extends \EDT\Querying\Functions\OneOf implements ClauseFunctionInterface
{
    use ClauseBasedTrait;

    /**
     * @template V
     * @phpstan-param ClauseFunctionInterface<array<V>> $contains
     * @phpstan-param ClauseFunctionInterface<V> $contained
     */
    public function __construct(ClauseFunctionInterface $contains, ClauseFunctionInterface $contained)
    {
        parent::__construct($contains, $contained);
        $this->setClauses($contains, $contained);
    }

    public function asDql(array $valueReferences, array $propertyAliases)
    {
        [$contains, $contained] = $this->getDqls($valueReferences, $propertyAliases);
        return (new Expr())->in($contained, $contains);
    }
}
