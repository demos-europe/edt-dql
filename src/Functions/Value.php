<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;
use Webmozart\Assert\Assert;

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

    public function asDql(array $valueReferences, array $propertyAliases, string $mainEntityAlias): string
    {
        Assert::count($propertyAliases, 0);
        Assert::count($valueReferences, 1);
        return array_pop($valueReferences);
    }
}
