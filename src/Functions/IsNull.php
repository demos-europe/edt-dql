<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-extends AbstractClauseFunction<bool>
 */
class IsNull extends AbstractClauseFunction
{
    /**
     * @param ClauseFunctionInterface<mixed> $baseFunction
     */
    public function __construct(ClauseFunctionInterface $baseFunction)
    {
        parent::__construct(
            new \EDT\Querying\Functions\IsNull($baseFunction),
            $baseFunction
        );
    }

    public function asDql(array $valueReferences, array $propertyAliases)
    {
        $maybeNull = $this->getOnlyClause()->asDql($valueReferences, $propertyAliases);
        return $this->expr->isNull($maybeNull);
    }
}
