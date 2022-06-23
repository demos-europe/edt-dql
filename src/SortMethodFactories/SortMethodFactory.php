<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\SortMethodFactories;

use EDT\DqlQuerying\Functions\Property;
use EDT\DqlQuerying\SortMethods\Ascending;
use EDT\DqlQuerying\SortMethods\Descending;
use EDT\DqlQuerying\Contracts\OrderBySortMethodInterface;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\Contracts\SortMethodFactoryInterface;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Querying\PropertyPaths\PropertyPath;

class SortMethodFactory implements SortMethodFactoryInterface
{
    /**
     * @return OrderBySortMethodInterface
     * @throws PathException
     */
    public function propertyAscending(string $property, string ...$properties): SortMethodInterface
    {
        $propertyPath = new PropertyPath('', PropertyPathAccessInterface::UNPACK_RECURSIVE, $property, ...$properties);
        return new Ascending(new Property($propertyPath));
    }

    /**
     * @return OrderBySortMethodInterface
     * @throws PathException
     */
    public function propertyDescending(string $property, string ...$properties): SortMethodInterface
    {
        $propertyPath = new PropertyPath('', PropertyPathAccessInterface::UNPACK_RECURSIVE, $property, ...$properties);
        return new Descending(new Property($propertyPath));
    }
}
