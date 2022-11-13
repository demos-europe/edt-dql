<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;
use Webmozart\Assert\Assert;

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
        Assert::count($valueReferences, 0);
        Assert::count($propertyAliases, 1);
        return array_pop($propertyAliases);
    }
}
