<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;
use Webmozart\Assert\Assert;

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

    /**
     * @return non-empty-string
     */
    public function asDql(array $valueReferences, array $propertyAliases): string
    {
        $maybeNull = $this->getOnlyClause()->asDql($valueReferences, $propertyAliases);
        $isNull = $this->expr->isNull($maybeNull);
        Assert::stringNotEmpty($isNull);

        return $isNull;
    }
}
