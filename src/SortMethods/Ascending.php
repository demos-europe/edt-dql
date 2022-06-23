<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\SortMethods;

use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;
use EDT\DqlQuerying\Contracts\OrderBySortMethodInterface;
use EDT\Querying\Utilities\Iterables;

class Ascending extends \EDT\Querying\SortMethods\Ascending implements OrderBySortMethodInterface
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
        return self::ASCENDING;
    }

    public function getClauseValues(): iterable
    {
        return [];
    }
}
