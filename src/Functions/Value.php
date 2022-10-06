<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;
use EDT\Querying\Utilities\Iterables;

/**
 * @template TOutput
 * @template-implements ClauseFunctionInterface<TOutput>
 * @template-extends \EDT\Querying\Functions\Value<TOutput>
 */
class Value extends \EDT\Querying\Functions\Value implements ClauseFunctionInterface
{
    public function getClauseValues(): array
    {
        return [$this->value];
    }

    public function asDql(array $valueReferences, array $propertyAliases): string
    {
        Iterables::assertCount(0, $propertyAliases);
        return Iterables::getOnlyValue($valueReferences);
    }
}
