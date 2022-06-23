<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-implements ClauseFunctionInterface<bool>
 */
class InvertedBoolean extends \EDT\Querying\Functions\InvertedBoolean implements ClauseFunctionInterface
{
    use ClauseBasedTrait;

    /**
     * @param ClauseFunctionInterface<bool> $baseFunction
     */
    public function __construct(ClauseFunctionInterface $baseFunction)
    {
        parent::__construct($baseFunction);
        $this->setClauses($baseFunction);
    }

    public function asDql(array $valueReferences, array $propertyAliases)
    {
        return (new Expr())->not($this->getOnlyClause()->asDql($valueReferences, $propertyAliases));
    }
}
