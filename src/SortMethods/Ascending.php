<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\SortMethods;

use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;
use EDT\DqlQuerying\Contracts\OrderBySortMethodInterface;
use Webmozart\Assert\Assert;

class Ascending extends \EDT\Querying\SortMethods\Ascending implements OrderBySortMethodInterface
{
    /**
     * @param ClauseFunctionInterface<mixed> $target
     */
    public function __construct(ClauseFunctionInterface $target)
    {
        parent::__construct($target);
    }

    /**
     * @return non-empty-string
     */
    public function asDql(array $valueReferences, array $propertyAliases, string $mainEntityAlias): string
    {
        Assert::count($valueReferences, 0);
        Assert::count($propertyAliases, 1);
        $propertyAlias = array_pop($propertyAliases);
        Assert::stringNotEmpty($propertyAlias);

        return $propertyAlias;
    }

    /**
     * @return self::ASCENDING
     */
    public function getDirection(): string
    {
        return self::ASCENDING;
    }

    public function getClauseValues(): array
    {
        return [];
    }
}
