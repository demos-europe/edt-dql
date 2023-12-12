<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use EDT\DqlQuerying\Contracts\ClauseInterface;
use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Functions\Unsupported;
use EDT\Querying\Utilities\ConditionEvaluator;
use Webmozart\Assert\Assert;

/**
 * Even though this class is a {@link FunctionInterface} according to its type, it does not support any usage in that
 * regard and thus can not be used in a {@link ConditionEvaluator}. **It must be used for execution via Doctrine only.**
 *
 * This is because it needs to be (type) compatible with other implementations of {@link ClauseInterface}, which require
 * their input to implement {@link FunctionInterface} too, while this class is currently unable to access the target entity
 * when used as {@link FunctionInterface}.
 *
 * @template-extends AbstractClauseFunction<class-string>
 */
class TargetEntity extends AbstractClauseFunction
{
    public function __construct()
    {
        parent::__construct(new Unsupported());
    }

    public function getPropertyPaths(): array
    {
        return [];
    }

    public function getClauseValues(): array
    {
        return [];
    }

    public function asDql(array $valueReferences, array $propertyAliases, string $mainEntityAlias): string
    {
        Assert::count($valueReferences, 0);
        Assert::count($propertyAliases, 0);
        return $mainEntityAlias;
    }

    public function __toString(): string
    {
        return static::class;
    }
}
