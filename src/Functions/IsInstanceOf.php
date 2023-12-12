<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr\Comparison;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;
use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template-extends AbstractClauseFunction<bool>
 *
 * Does not usage as {@link FunctionInterface}.
 */
class IsInstanceOf extends AbstractClauseFunction
{
    /**
     * @param ClauseFunctionInterface<non-empty-string> $valueClause
     * @param ClauseFunctionInterface<class-string> $typeClause
     */
    public function __construct(
        protected readonly ClauseFunctionInterface $valueClause,
        protected readonly ClauseFunctionInterface $typeClause,
    ) {
        parent::__construct(
            new \EDT\Querying\Functions\IsInstanceOf(
                $this->valueClause,
                $typeClause
            ),
            $this->valueClause,
            $this->typeClause
        );
    }

    public function asDql(array $valueReferences, array $propertyAliases, string $mainEntityAlias): Comparison
    {
        [$left, $right] = $this->getDqls($valueReferences, $propertyAliases, $mainEntityAlias);
        return $this->expr->isInstanceOf((string) $left, (string) $right);
    }

    public function __toString(): string
    {
        return ((string) $this->valueClause) . 'INSTANCE OF' . ((string) $this->typeClause);
    }
}
