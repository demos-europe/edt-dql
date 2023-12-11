<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr\Func;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-extends AbstractClauseFunction<bool>
 *
 * @internal this implementation is not usable for to-many relationships
 */
class InvertedBoolean extends AbstractClauseFunction
{
    /**
     * @param ClauseFunctionInterface<bool> $baseFunction
     */
    public function __construct(ClauseFunctionInterface $baseFunction)
    {
        parent::__construct(
            new \EDT\Querying\Functions\InvertedBoolean($baseFunction),
            $baseFunction
        );
    }

    public function asDql(array $valueReferences, array $propertyAliases, string $mainEntityAlias): Func
    {
        return $this->expr->not($this->getOnlyClause()->asDql($valueReferences, $propertyAliases, $mainEntityAlias));
    }
}
