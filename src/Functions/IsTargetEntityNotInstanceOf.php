<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr;
use EDT\DqlQuerying\Contracts\ClauseInterface;

class IsTargetEntityNotInstanceOf implements ClauseInterface
{
    private Expr $expr;

    public function __construct(
        protected readonly ClauseInterface $typeClause,
    ) {
        $this->expr = new Expr();
    }

    public function asDql(array $valueReferences, array $propertyAliases, string $mainEntityAlias): Expr\Func
    {
        $type = $this->typeClause->asDql($valueReferences, $propertyAliases, $mainEntityAlias);

        return $this->expr->not($this->expr->isInstanceOf($mainEntityAlias, (string) $type));
    }

    public function getPropertyPaths(): array
    {
        return $this->typeClause->getPropertyPaths();
    }

    public function getClauseValues(): array
    {
        return $this->typeClause->getClauseValues();
    }
}
