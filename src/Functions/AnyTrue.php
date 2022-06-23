<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr\Orx;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-implements ClauseFunctionInterface<bool>
 */
class AnyTrue extends \EDT\Querying\Functions\AnyTrue implements ClauseFunctionInterface
{
    use ClauseBasedTrait;

    /**
     * @phpstan-param ClauseFunctionInterface<bool> $firstFunction
     * @phpstan-param ClauseFunctionInterface<bool> ...$additionalFunctions
     */
    public function __construct(ClauseFunctionInterface $firstFunction, ClauseFunctionInterface ...$additionalFunctions)
    {
        parent::__construct($firstFunction, ...$additionalFunctions);
        $this->setClauses($firstFunction, ...$additionalFunctions);
    }

    /**
     * @return Orx
     */
    public function asDql(array $valueReferences, array $propertyAliases): Orx
    {
        return new Orx($this->getDqls($valueReferences, $propertyAliases));
    }
}
