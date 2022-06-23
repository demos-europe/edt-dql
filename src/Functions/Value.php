<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;
use EDT\Querying\Utilities\Iterables;

/**
 * @template R
 * @template-implements ClauseFunctionInterface<R>
 * @template-extends \EDT\Querying\Functions\Value<R>
 */
class Value extends \EDT\Querying\Functions\Value implements ClauseFunctionInterface
{
    public function getClauseValues(): iterable
    {
        return [$this->value];
    }

    public function asDql(array $valueReferences, array $propertyAliases): string
    {
        Iterables::assertCount(0, $propertyAliases);
        return Iterables::getOnlyValue($valueReferences);
    }
}
