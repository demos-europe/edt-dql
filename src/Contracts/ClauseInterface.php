<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Contracts;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Math;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyPathAccessInterface;

/**
 * Implementing classes need to implement {@link ClauseInterface::getClauseValues} and {@link ClauseInterface::getPropertyPaths} to provide their
 * input data to the query generator to be converted. The generator will then call the the
 * {@link ClauseInterface::asDql} method with the converted data.
 */
interface ClauseInterface extends PathsBasedInterface
{
    /**
     * The paths to the properties that are needed in {@link ClauseInterface::asDql()}.
     * The {@link PropertyPathAccessInterface::getAccessDepth()} matters:
     * * 0: The entity aliases given to {@link ClauseInterface::asDql} will look something like `Book.authors` or
     * `t_301d3a58_Book.authors`.
     * * 1: A join to the target column will be executed (e.g. `Book.authors` to the `Person` entity in case of the path `book.authors`)
     * The aliases given to {@link ClauseInterface::asDql} will look something like `t_8f2d7d21_Person`.
     *
     * @return iterable<PropertyPathAccessInterface>
     */
    public function getPropertyPaths(): iterable;
    /**
     * Must return the raw input values needed by this condition. The returned array may be
     * * empty if no values are needed (eg. in case of a null comparison)
     * * contain a single value (eg. in case of an equals comparison)
     * * may contain multiple values (eg. in case of a between comparison)
     *
     * @return iterable<mixed>
     */
    public function getClauseValues(): iterable;

    /**
     * @param string[] $valueReferences The values returned by
     *                                  {@link ClauseInterface::getClauseValues()} converted to
     *                                  index references valid in the complete and final DQL.
     * @param string[] $propertyAliases The paths returned by {@link ClauseInterface::getPropertyPaths()} after they have been processed into joins with
     *                                  the values provided here being the entity alias of the destination
     *                                  of the path.
     *
     * @return Composite|Math|Func|Comparison|string Must be a part of a DQL <code>WHERE</code> condition that can be
     *                                grouped using <code>AND</code> or <code>OR</code>.
     */
    public function asDql(array $valueReferences, array $propertyAliases);
}
