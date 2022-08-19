<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;


use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;
use EDT\Querying\Utilities\Iterables;

/**
 * @template-implements ClauseFunctionInterface<mixed>
 */
class Property extends \EDT\Querying\Functions\Property implements ClauseFunctionInterface
{
    public function getClauseValues(): array
    {
        return [];
    }

    public function asDql(array $valueReferences, array $propertyAliases): string
    {
        Iterables::assertCount(0, $valueReferences);
        return Iterables::getOnlyValue($propertyAliases);
    }
}
