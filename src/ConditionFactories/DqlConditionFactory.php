<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\ConditionFactories;

use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;
use EDT\DqlQuerying\Functions\AllTrue;
use EDT\DqlQuerying\Functions\AnyTrue;
use EDT\DqlQuerying\Functions\Greater;
use EDT\DqlQuerying\Functions\GreaterEquals;
use EDT\DqlQuerying\Functions\InvertedBoolean;
use EDT\DqlQuerying\Functions\Smaller;
use EDT\DqlQuerying\Functions\SmallerEquals;
use EDT\DqlQuerying\Functions\StringEndsWith;
use EDT\DqlQuerying\Functions\StringStartsWith;
use EDT\DqlQuerying\Functions\AllEqual;
use EDT\DqlQuerying\Functions\BetweenInclusive;
use EDT\DqlQuerying\Functions\Constant;
use EDT\DqlQuerying\Functions\StringContains;
use EDT\DqlQuerying\Functions\IsMemberOf;
use EDT\DqlQuerying\Functions\IsNull;
use EDT\DqlQuerying\Functions\LowerCase;
use EDT\DqlQuerying\Functions\OneOf;
use EDT\DqlQuerying\Functions\Property;
use EDT\DqlQuerying\Functions\Size;
use EDT\DqlQuerying\Functions\Value;
use EDT\Querying\Contracts\ConditionFactoryInterface;
use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\PropertyPaths\PropertyPath;

class DqlConditionFactory implements ConditionFactoryInterface
{
    /**
     * @return ClauseFunctionInterface<bool>
     */
    public function false(): FunctionInterface
    {
        $condition = new Constant(false);
        // using 'false' here does not work with some database drivers
        $condition->setDqlValue('1 = 2');
        return $condition;
    }

    /**
     * @return ClauseFunctionInterface<bool>
     */
    public function true(): FunctionInterface
    {
        $condition = new Constant(true);
        // using 'true' here does not work with some database drivers
        $condition->setDqlValue('1 = 1');
        return $condition;
    }

    /**
     * @param ClauseFunctionInterface<bool> $firstClause
     * @param ClauseFunctionInterface<bool> ...$additionalClauses
     *
     * @return ClauseFunctionInterface<bool>
     */
    public function allConditionsApply(FunctionInterface $firstClause, FunctionInterface ...$additionalClauses): FunctionInterface
    {
        return new AllTrue($firstClause, ...$additionalClauses);
    }

    /**
     * @param ClauseFunctionInterface<bool> $firstFunction
     * @param ClauseFunctionInterface<bool>                                       ...$additionalFunctions
     *
     * @return ClauseFunctionInterface<bool>
     */
    public function anyConditionApplies(FunctionInterface $firstFunction, FunctionInterface ...$additionalFunctions): FunctionInterface
    {
        return new AnyTrue($firstFunction, ...$additionalFunctions);
    }

    /**
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function propertiesEqual(array $leftPropertyPath, array $rightPropertyPath): FunctionInterface
    {
        $leftPropertyPathInstance = new PropertyPath('', PropertyPathAccessInterface::UNPACK, ...$leftPropertyPath);
        $rightPropertyPathInstance = new PropertyPath('', PropertyPathAccessInterface::UNPACK, ...$rightPropertyPath);
        return new AllEqual(
            new Property($leftPropertyPathInstance),
            new Property($rightPropertyPathInstance)
        );
    }

    /**
     * @param mixed $min
     * @param mixed $max
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function propertyBetweenValuesInclusive($min, $max, string $property, string ...$propertyPath): FunctionInterface
    {
        $propertyPathInstance = new PropertyPath('', PropertyPathAccessInterface::UNPACK, $property, ...$propertyPath);
        return new BetweenInclusive(
            new Value($min),
            new Value($max),
            new Property($propertyPathInstance)
        );
    }

    /**
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function propertyHasAnyOfValues(array $values, string $property, string ...$properties): FunctionInterface
    {
        $propertyPath = new PropertyPath('', PropertyPathAccessInterface::UNPACK, $property, ...$properties);
        if ([] === $values) {
            return $this->false();
        }

        return new OneOf(new Value($values), new Property($propertyPath));
    }

    /**
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function propertyHasSize(int $size, string $property, string ...$properties): FunctionInterface
    {
        $propertyPath = new PropertyPath('', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new AllEqual(
            new Size(new Property($propertyPath)),
            new Value($size)
        );
    }

    /**
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function propertyHasStringContainingCaseInsensitiveValue(string $value, string $property, string ...$properties): FunctionInterface
    {
        $propertyPath = new PropertyPath('', PropertyPathAccessInterface::UNPACK, $property, ...$properties);
        return new StringContains(
            new LowerCase(new Property($propertyPath)),
            new LowerCase(new Value("$value"))
        );
    }

    /**
     * @param mixed $value
     *
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function propertyHasValue($value, string $property, string ...$properties): FunctionInterface
    {
        $propertyPath = new PropertyPath('', PropertyPathAccessInterface::UNPACK, $property, ...$properties);
        return new AllEqual(
            new Property($propertyPath),
            new Value($value)
        );
    }

    /**
     * Accesses a property to evaluate if it is set to `null` or not.
     * This is done using this separate condition class and not {@link AllEqual}
     * as in SQL (and DQL)
     * `null` denotes an unknown value and thus `null` does never equals
     * `null`. Using {@link AllEqual} with `null` as value would thus always
     * evaluate to `false`.
     *
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function propertyIsNull(string $property, string ...$properties): FunctionInterface
    {
        /**
         * In theory the condition `Person.birthplace is null` does not need a join to the `Address`
         * entity and thus {@link PropertyPathAccessInterface::DIRECT} could be used, but due to the
         * current Doctrine implementation the join is still needed in case of a one-to-one
         * relationship with the right side being the owning side.
         */
        $propertyPath = new PropertyPath('', PropertyPathAccessInterface::UNPACK, $property, ...$properties);
        return new IsNull(new Property($propertyPath));
    }

    /**
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function propertyHasStringAsMember(string $value, string $property, string ...$properties): FunctionInterface
    {
        $propertyPath = new PropertyPath('', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new IsMemberOf(
            new Property($propertyPath),
            new Value($value)
        );
    }

    /**
     * @param mixed $value
     *
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function valueGreaterThan($value, string $property, string ...$properties): FunctionInterface
    {
        $propertyPath = new PropertyPath('', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new Greater(
            new Property($propertyPath),
            new Value($value)
        );
    }

    /**
     * @param mixed $value
     *
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function valueGreaterEqualsThan($value, string $property, string ...$properties): FunctionInterface
    {
        $propertyPath = new PropertyPath('', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new GreaterEquals(
            new Property($propertyPath),
            new Value($value)
        );
    }

    /**
     * @param mixed $value
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function valueSmallerThan($value, string $property, string ...$properties): FunctionInterface
    {
        $propertyPath = new PropertyPath('', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new Smaller(
            new Property($propertyPath),
            new Value($value)
        );
    }

    /**
     * @param mixed $value
     *
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function valueSmallerEqualsThan($value, string $property, string ...$properties): FunctionInterface
    {
        $propertyPath = new PropertyPath('', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new SmallerEquals(
            new Property($propertyPath),
            new Value($value)
        );
    }

    /**
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function propertyStartsWithCaseInsensitive(string $value, string $property, string ...$properties): FunctionInterface
    {
        $propertyPath = new PropertyPath('', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new StringStartsWith(
            new LowerCase(new Property($propertyPath)),
            new LowerCase(new Value($value))
        );
    }

    /**
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function propertyEndsWithCaseInsensitive(string $value, string $property, string ...$properties): FunctionInterface
    {
        $propertyPath = new PropertyPath('', PropertyPathAccessInterface::DIRECT, $property, ...$properties);
        return new StringEndsWith(
            new LowerCase(new Property($propertyPath)),
            new LowerCase(new Value($value))
        );
    }

    public function allValuesPresentInMemberListProperties(array $values, string $property, string ...$properties): FunctionInterface
    {
        $propertyPaths = PropertyPath::createIndexSaltedPaths(count($values), PropertyPath::DIRECT, $property, ...$properties);
        $equalityConditions = array_map(static function ($value, PropertyPathAccessInterface $propertyPath): AllEqual {
            return new AllEqual(new Property($propertyPath), new Value($value));
        }, $values, $propertyPaths);

        return new AllTrue(...$equalityConditions);
    }

    public function propertyHasNotAnyOfValues(array $values, string $property, string ...$properties): FunctionInterface
    {
        return new InvertedBoolean($this->propertyHasAnyOfValues($values, $property, ...$properties));
    }

    public function propertyHasNotSize(int $size, string $property, string ...$properties): FunctionInterface
    {
        return new InvertedBoolean($this->propertyHasSize($size, $property, ...$properties));
    }

    public function propertyNotBetweenValuesInclusive($min, $max, string $property, string ...$properties): FunctionInterface
    {
        return new InvertedBoolean($this->propertyBetweenValuesInclusive($min, $max, $property, ...$properties));
    }

    public function propertyHasNotValue($value, string $property, string ...$properties): FunctionInterface
    {
        return new InvertedBoolean($this->propertyHasValue($value, $property, ...$properties));
    }

    public function propertyIsNotNull(string $property, string ...$properties): FunctionInterface
    {
        return new InvertedBoolean($this->propertyIsNull($property, ...$properties));
    }

    public function propertyHasNotStringAsMember(string $value, string $property, string ...$properties): FunctionInterface
    {
        return new InvertedBoolean($this->propertyHasStringAsMember($value, $property, ...$properties));
    }
}
