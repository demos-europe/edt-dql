<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;

/**
 * @template-implements ClauseFunctionInterface<string>
 */
class LowerCase extends \EDT\Querying\Functions\LowerCase implements ClauseFunctionInterface
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

    public function asDql(array $valueReferences, array $propertyAliases)
    {
        return (new Expr())->lower($this->getOnlyClause()->asDql($valueReferences, $propertyAliases));
    }
}
