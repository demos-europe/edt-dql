<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Countable;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-extends AbstractClauseFunction<int>
 */
class Size extends AbstractClauseFunction
{
    /**
     * @param ClauseFunctionInterface<Countable|array<int|string, mixed>> $baseFunction
     */
    public function __construct(ClauseFunctionInterface $baseFunction)
    {
        parent::__construct(
            new \EDT\Querying\Functions\Size($baseFunction),
            $baseFunction
        );
    }

    public function asDql(array $valueReferences, array $propertyAliases): string
    {
        $baseDql = $this->getOnlyClause()->asDql($valueReferences, $propertyAliases);
        return "SIZE($baseDql)";
    }
}
