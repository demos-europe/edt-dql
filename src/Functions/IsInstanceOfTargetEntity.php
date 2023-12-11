<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr;
use EDT\DqlQuerying\Contracts\ClauseInterface;

class IsInstanceOfTargetEntity implements ClauseInterface
{
    private Expr $expr;

    public function __construct(
        protected readonly ClauseInterface $valueClause,
    ) {
        $this->expr = new Expr();
    }

    public function asDql(array $valueReferences, array $propertyAliases, string $mainEntityAlias): Comparison
    {
        $value = $this->valueClause->asDql($valueReferences, $propertyAliases, $mainEntityAlias);

        return $this->expr->isInstanceOf((string) $value, $mainEntityAlias);
    }

    public function getPropertyPaths(): array
    {
        return $this->valueClause->getPropertyPaths();
    }

    public function getClauseValues(): array
    {
        return $this->valueClause->getClauseValues();
    }
}
