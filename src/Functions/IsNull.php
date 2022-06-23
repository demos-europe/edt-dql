<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-implements ClauseFunctionInterface<bool>
 */
class IsNull extends \EDT\Querying\Functions\IsNull implements ClauseFunctionInterface
{
    use ClauseBasedTrait;

    /**
     * @param ClauseFunctionInterface<mixed> $baseFunction
     */
    public function __construct(ClauseFunctionInterface $baseFunction)
    {
        parent::__construct($baseFunction);
        $this->setClauses($baseFunction);
    }

    public function asDql(array $valueReferences, array $propertyAliases)
    {
        $maybeNull = $this->getOnlyClause()->asDql($valueReferences, $propertyAliases);
        return (new Expr())->isNull($maybeNull);
    }
}
