<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\SortMethods;

use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;
use EDT\DqlQuerying\Contracts\OrderBySortMethodInterface;
use EDT\Querying\Utilities\Iterables;

class Descending extends \EDT\Querying\SortMethods\Descending implements OrderBySortMethodInterface
{
    /**
     * @param ClauseFunctionInterface<mixed> $target
     */
    public function __construct(ClauseFunctionInterface $target)
    {
        parent::__construct($target);
    }

    public function asDql(array $valueReferences, array $propertyAliases)
    {
        Iterables::assertCount(0, $valueReferences);
        return Iterables::getOnlyValue($propertyAliases);
    }

    public function getDirection(): string
    {
        return self::DESCENDING;
    }

    public function getClauseValues(): array
    {
        return [];
    }
}
