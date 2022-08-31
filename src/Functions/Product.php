<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-extends AbstractClauseFunction<numeric>
 */
class Product extends AbstractClauseFunction
{
    /**
     * @phpstan-param ClauseFunctionInterface<numeric> $firstFactor
     * @phpstan-param ClauseFunctionInterface<numeric> $secondFactor
     * @phpstan-param ClauseFunctionInterface<numeric> ...$additionalFactors
     */
    public function __construct(ClauseFunctionInterface $firstFactor, ClauseFunctionInterface $secondFactor, ClauseFunctionInterface ...$additionalFactors)
    {
        parent::__construct(
            new \EDT\Querying\Functions\Product($firstFactor, $secondFactor, ...$additionalFactors),
            $firstFactor, $secondFactor, ...$additionalFactors
        );
    }

    public function asDql(array $valueReferences, array $propertyAliases)
    {
        $dqlClauses = $this->getDqls($valueReferences, $propertyAliases);
        $initial = array_shift($dqlClauses);
        // do not typehint the callback parameters as strings, as we must not implicitly invoke __toString
        return array_reduce($dqlClauses, [$this->expr, 'prod'], $initial);
    }
}
