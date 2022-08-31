<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-extends AbstractClauseFunction<string|null>
 */
class LowerCase extends AbstractClauseFunction
{
    /**
     * @param ClauseFunctionInterface<string|null> $baseFunction
     */
    public function __construct(ClauseFunctionInterface $baseFunction)
    {
        parent::__construct(
            new \EDT\Querying\Functions\LowerCase($baseFunction),
            $baseFunction
        );
    }

    public function asDql(array $valueReferences, array $propertyAliases)
    {
        return $this->expr->lower($this->getOnlyClause()->asDql($valueReferences, $propertyAliases));
    }
}
