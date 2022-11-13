<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Math;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;
use Webmozart\Assert\Assert;

/**
 * @template V of Composite|Math|Func|Comparison|string
 * @template TOutput
 * @template-implements ClauseFunctionInterface<TOutput>
 * @template-extends \EDT\Querying\Functions\Value<TOutput>
 */
class Constant extends \EDT\Querying\Functions\Value implements ClauseFunctionInterface
{
    /**
     * @var V
     */
    private $dqlValue;

    /**
     * @param TOutput $phpValue
     * @param V $dqlValue
     */
    public function __construct($phpValue, $dqlValue)
    {
        parent::__construct($phpValue);
        $this->dqlValue = $dqlValue;
    }

    public function getClauseValues(): array
    {
        return [];
    }

    /**
     * @return V
     */
    public function asDql(array $valueReferences, array $propertyAliases)
    {
        Assert::count($valueReferences, 0);
        Assert::count($propertyAliases, 0);
        return $this->dqlValue;
    }
}
