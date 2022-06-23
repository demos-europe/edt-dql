<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Countable;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-implements ClauseFunctionInterface<int>
 */
class Size extends \EDT\Querying\Functions\Size implements ClauseFunctionInterface
{
    use ClauseBasedTrait;

    /**
     * @param ClauseFunctionInterface<Countable|array<mixed>> $baseFunction
     */
    public function __construct(ClauseFunctionInterface $baseFunction)
    {
        parent::__construct($baseFunction);
        $this->setClauses($baseFunction);
    }

    public function asDql(array $valueReferences, array $propertyAliases): string
    {
        $baseDql = $this->getOnlyClause()->asDql($valueReferences, $propertyAliases);
        return "SIZE($baseDql)";
    }
}
