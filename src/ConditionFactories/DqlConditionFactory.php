<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\ConditionFactories;

use EDT\ConditionFactory\PathsBasedConditionGroupFactoryInterface;
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
use EDT\ConditionFactory\PathsBasedConditionFactoryInterface;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Querying\PropertyPaths\PropertyPath;
use function count;

/**
 * @template-implements PathsBasedConditionFactoryInterface<ClauseFunctionInterface<bool>>
 * @template-implements PathsBasedConditionGroupFactoryInterface<ClauseFunctionInterface<bool>>
 */
class DqlConditionFactory implements PathsBasedConditionFactoryInterface, PathsBasedConditionGroupFactoryInterface
{
    /**
     * @return ClauseFunctionInterface<bool>
     */
    public function false(): PathsBasedInterface
    {
        // using 'false' here as DQL value does not work with some database drivers
        return new Constant(false, '1 = 2');
    }

    /**
     * @return ClauseFunctionInterface<bool>
     */
    public function true(): PathsBasedInterface
    {
        // using 'true' here does not work with some database drivers
        return new Constant(true, '1 = 1');
    }

    /**
     * @param ClauseFunctionInterface<bool> $firstCondition
     * @param ClauseFunctionInterface<bool> ...$additionalConditions
     *
     * @return ClauseFunctionInterface<bool>
     */
    public function allConditionsApply($firstCondition, ...$additionalConditions): PathsBasedInterface
    {
        return new AllTrue($firstCondition, ...$additionalConditions);
    }

    /**
     * @param ClauseFunctionInterface<bool> $firstCondition
     * @param ClauseFunctionInterface<bool> ...$additionalConditions
     *
     * @return ClauseFunctionInterface<bool>
     */
    public function anyConditionApplies($firstCondition, ...$additionalConditions): PathsBasedInterface
    {
        return new AnyTrue($firstCondition, ...$additionalConditions);
    }

    /**
     * @param class-string|null $rightEntityClass
     *
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function propertiesEqual(array $leftProperties, array $rightProperties, string $rightEntityClass = null, string $salt = ''): PathsBasedInterface
    {
        $leftPropertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, $leftProperties);
        $rightPropertyPathInstance = new PropertyPath($rightEntityClass, $salt, PropertyPathAccessInterface::UNPACK, $rightProperties);
        return new AllEqual(
            new Property($leftPropertyPathInstance),
            new Property($rightPropertyPathInstance)
        );
    }

    /**
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function propertyBetweenValuesInclusive(
        string|int|float $min,
        string|int|float $max,
        string|array|PropertyPathInterface $properties
    ): PathsBasedInterface {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, $properties);
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
    public function propertyHasAnyOfValues(array $values, string|array|PropertyPathInterface $properties): PathsBasedInterface
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, $properties);
        if ([] === $values) {
            return $this->false();
        }

        return new OneOf(new Value($values), new Property($propertyPath));
    }

    /**
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function propertyHasSize(int $size, string|array|PropertyPathInterface $properties): PathsBasedInterface
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $properties);
        return new AllEqual(
            new Size(new Property($propertyPath)),
            new Value($size)
        );
    }

    /**
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function propertyHasStringContainingCaseInsensitiveValue(string $value, string|array|PropertyPathInterface $properties): PathsBasedInterface
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, $properties);
        return new StringContains(
            new LowerCase(new Property($propertyPath)),
            new LowerCase(new Value($value))
        );
    }

    /**
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function propertyHasValue(string|int|float|bool $value, string|array|PropertyPathInterface $properties): PathsBasedInterface
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, $properties);
        return new AllEqual(
            new Property($propertyPath),
            new Value($value)
        );
    }

    /**
     * Accesses a property to evaluate if it is set to `null` or not.
     * This is done using this separate condition class and not {@link AllEqual}
     * as in SQL (and DQL) `null` denotes an unknown value and thus `null` does never equal
     * `null`. Using {@link AllEqual} with `null` as value would thus always
     * evaluate to `false`.
     *
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function propertyIsNull(string|array|PropertyPathInterface $properties): PathsBasedInterface
    {
        /**
         * In theory the condition `Person.birthplace is null` does not need a join to the `Address`
         * entity and thus {@link PropertyPathAccessInterface::DIRECT} could be used, but due to the
         * current Doctrine implementation the join is still needed in case of a one-to-one
         * relationship with the right side being the owning side.
         */
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, $properties);
        return new IsNull(new Property($propertyPath));
    }

    /**
     * @return ClauseFunctionInterface<bool>
     * @throws PathException
     */
    public function propertyHasStringAsMember(string $value, string|array|PropertyPathInterface $properties): PathsBasedInterface
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $properties);
        return new IsMemberOf(
            new Property($propertyPath),
            new Value($value)
        );
    }

    public function valueGreaterThan(string|int|float $value, string|array|PropertyPathInterface $properties): PathsBasedInterface
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $properties);
        return new Greater(
            new Property($propertyPath),
            new Value($value)
        );
    }

    public function valueGreaterEqualsThan(string|int|float $value, string|array|PropertyPathInterface $properties): PathsBasedInterface
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $properties);
        return new GreaterEquals(
            new Property($propertyPath),
            new Value($value)
        );
    }

    public function valueSmallerThan(string|int|float $value, string|array|PropertyPathInterface $properties): PathsBasedInterface
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $properties);
        return new Smaller(
            new Property($propertyPath),
            new Value($value)
        );
    }

    public function valueSmallerEqualsThan(string|int|float $value, string|array|PropertyPathInterface $properties): PathsBasedInterface
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $properties);
        return new SmallerEquals(
            new Property($propertyPath),
            new Value($value)
        );
    }

    public function propertyStartsWithCaseInsensitive(string $value, string|array|PropertyPathInterface $properties): PathsBasedInterface
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $properties);
        return new StringStartsWith(
            new LowerCase(new Property($propertyPath)),
            new LowerCase(new Value($value))
        );
    }

    public function propertyEndsWithCaseInsensitive(string $value, string|array|PropertyPathInterface $properties): PathsBasedInterface
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, $properties);
        return new StringEndsWith(
            new LowerCase(new Property($propertyPath)),
            new LowerCase(new Value($value))
        );
    }

    /**
     * This class generates conditions that can be converted to DQL and thus are logically
     * based on the relational model.
     *
     * Because a condition needs all information in a single row, we need to create a separate
     * join for each required value. To clarify this, consider the following tables, continuing
     * the example in {@link PathsBasedConditionFactoryInterface::allValuesPresentInMemberListProperties()}:
     *
     * ```
     * author
     * | author_id | author_name |
     * |-----------|-------------|
     * |         1 |         Joe |
     * |         2 |         Bob |
     *
     * book
     * | author_id | book_title |
     * |-----------|------------|
     * |         1 |          A |
     * |         1 |          B |
     * |         1 |          C |
     * |         2 |          A |
     * |         2 |          C |
     * ```
     *
     * We want to retrieve the authors that wrote books with the titles `A` *and* `B`. To get
     * all titles for the condition into a single row we need to execute as many joins from the
     * `author` table to the `book` table as there are values to check for. In this example we
     * need two joins to the `book` table. One for `A` (`book_title_0`) and one for `B`
     * (`book_title_1`), resulting in the following:
     *
     * ```
     * author_book_join
     * | author_id | author_name | book_title_0 | book_title_1 |
     * |-----------|-------------|--------------|--------------|
     * |         1 |         Joe |            A |            A |
     * |         1 |         Joe |            A |            B |
     * |         1 |         Joe |            A |            C |
     * |         1 |         Joe |            B |            A |
     * |         1 |         Joe |            B |            B |
     * |         1 |         Joe |            B |            C |
     * |         1 |         Joe |            C |            A |
     * |         1 |         Joe |            C |            B |
     * |         1 |         Joe |            C |            C |
     * |         2 |         Bob |            A |            A |
     * |         2 |         Bob |            A |            C |
     * |         2 |         Bob |            C |            A |
     * |         2 |         Bob |            C |            C |
     * ```
     *
     * Based on this we can generate a condition that results in
     * `… WHERE book_title_0 = 'A' AND book_title_1 = 'B'`, which matches only `Joe`,
     * as it should be.
     *
     * @throws PathException
     */
    public function allValuesPresentInMemberListProperties(array $values, string|array|PropertyPathInterface $properties): PathsBasedInterface
    {
        // When building the DQL joins duplications will be avoided by default. I.e. if the
        // same property path is used in multiple conditions the corresponding join is
        // created only once and all conditions using the same path would access the same value
        // when executed on a row. However, this is not what we want here. We want to create
        // a separate join and corresponding condition for each given value. By setting a different
        // salt for each PropertyPath they will result in separate joins to different columns, even
        // though they are all based on the same given property path.
        $propertyPaths = PropertyPath::createIndexSaltedPaths(count($values), PropertyPathAccessInterface::DIRECT, $properties);
        // Each $propertyPath now corresponds to a different value in $values and accesses a
        // different column as explained above. Hence, we can create a separate condition for each
        // one, each being responsible for a single value in $values.
        $equalityConditions = array_map(
            static fn ($value, PropertyPathAccessInterface $propertyPath): AllEqual => new AllEqual(new Property($propertyPath), new Value($value)),
            $values,
            $propertyPaths
        );

        // Because we want to check if *all* values are present, we combine the created conditions
        // via a logical `AND` operator.
        return new AllTrue(...$equalityConditions);
    }

    public function propertyHasNotAnyOfValues(array $values, string|array|PropertyPathInterface $properties): PathsBasedInterface
    {
        return new InvertedBoolean($this->propertyHasAnyOfValues($values, $properties));
    }

    public function propertyHasNotSize(int $size, string|array|PropertyPathInterface $properties): PathsBasedInterface
    {
        return new InvertedBoolean($this->propertyHasSize($size, $properties));
    }

    public function propertyNotBetweenValuesInclusive(
        string|int|float $min,
        string|int|float $max,
        string|array|PropertyPathInterface $properties
    ): PathsBasedInterface {
        return new InvertedBoolean($this->propertyBetweenValuesInclusive($min, $max, $properties));
    }

    public function propertyHasNotValue(
        string|int|float|bool $value,
        string|array|PropertyPathInterface $properties
    ): PathsBasedInterface {
        return new InvertedBoolean($this->propertyHasValue($value, $properties));
    }

    public function propertyIsNotNull(string|array|PropertyPathInterface $properties): PathsBasedInterface
    {
        return new InvertedBoolean($this->propertyIsNull($properties));
    }

    public function propertyHasNotStringAsMember(
        string $value,
        string|array|PropertyPathInterface $properties
    ): PathsBasedInterface {
        return new InvertedBoolean($this->propertyHasStringAsMember($value, $properties));
    }
}
