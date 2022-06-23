<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Func;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-implements ClauseFunctionInterface<string>
 */
class UpperCase extends \EDT\Querying\Functions\UpperCase implements ClauseFunctionInterface
{
    use ClauseBasedTrait;

    /**
     * @param ClauseFunctionInterface<string> $baseFunction
     */
    public function __construct(ClauseFunctionInterface $baseFunction)
    {
        parent::__construct($baseFunction);
        $this->setClauses($baseFunction);
    }

    public function asDql(array $valueReferences, array $propertyAliases): Func
    {
        return (new Expr())->upper($this->getOnlyClause()->asDql($valueReferences, $propertyAliases));
    }
}
