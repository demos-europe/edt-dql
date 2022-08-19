<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;


use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;
use EDT\Querying\Utilities\Iterables;

/**
 * @template-implements ClauseFunctionInterface<mixed>
 * @template-extends \EDT\Querying\Functions\Value<mixed>
 */
class Constant extends \EDT\Querying\Functions\Value implements ClauseFunctionInterface
{
    /**
     * @var mixed
     */
    private $dqlValue;

    /**
     * @var bool
     */
    private $customDqlValue = false;

    public function setDqlValue($dqlValue): void
    {
        $this->dqlValue = $dqlValue;
        $this->customDqlValue = true;
    }

    public function getClauseValues(): array
    {
        return [];
    }

    public function asDql(array $valueReferences, array $propertyAliases)
    {
        Iterables::assertCount(0, $valueReferences);
        Iterables::assertCount(0, $propertyAliases);
        return $this->customDqlValue ? $this->dqlValue : $this->value;
    }
}
