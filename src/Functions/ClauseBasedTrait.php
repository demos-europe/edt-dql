<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr\Base;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Math;
use EDT\DqlQuerying\Contracts\ClauseInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Utilities\Iterables;

trait ClauseBasedTrait
{
    /**
     * @var ClauseInterface[]
     */
    protected $clauses = [];

    public function getClauseValues(): iterable
    {
        return Iterables::flat(static function (ClauseInterface $clause): array {
            return Iterables::asArray($clause->getClauseValues());
        }, $this->clauses);
    }

    /**
     * Will set the clauses of this trait. By calling {@link ClauseBasedTrait::getDqls()}
     * the {@link \EDT\DqlQuerying\Contracts\ClauseInterface::asDql()} of all clauses will be invoked and the results
     * returned inside an array. Eg. if you passed a single clause the returned array will contain
     * one element being the result of the clause. If you passed two clauses the returned array will
     * contain two elements, each being the result of the corresponding clause.
     */
    protected function setClauses(ClauseInterface ...$clauses): void
    {
        $this->clauses = $clauses;
    }

    /**
     * Will return all DQL results of the clauses passed in {@link ClauseBasedTrait::setClauses()}.
     * @param string[] $valueReferences
     * @param string[] $propertyAliases
     *
     * @return array<int,Comparison|Func|Math|Base|string>
     */
    protected function getDqls(array $valueReferences, array $propertyAliases): array
    {
        $nestedValueReferences = $this->unflatClauseReferences(...$valueReferences);
        $nestedPropertyAliases = $this->unflatPropertyAliases(...$propertyAliases);
        return array_map(static function (ClauseInterface $clause, array $valueReferences, array $propertyAliases) {
            return $clause->asDql($valueReferences, $propertyAliases);
        }, $this->clauses, $nestedValueReferences, $nestedPropertyAliases);
    }

    /**
     * Splits a flat array of value references into a nested array with each index
     * of the outer array corresponding to the same index in {@link ClauseBasedTrait::clauses}.
     *
     * @return string[][]
     */
    protected function unflatClauseReferences(string ...$valueReferences): array
    {
        $clauseValueCountables = array_map(static function (ClauseInterface $clause): int {
            return Iterables::count($clause->getClauseValues());
        }, $this->clauses);
        return Iterables::split($valueReferences, false, ...$clauseValueCountables);
    }

    /**
     * Can be used if a single clause was passed to {@link ClauseBasedTrait::setClauses()} to
     * get its DQL directly. If not exactly one clause was passed in the setter then this
     * function call will throw an exception.
     */
    private function getOnlyClause(): ClauseInterface
    {
        Iterables::assertCount(1, $this->clauses);
        return $this->clauses[0];
    }

    /**
     * Splits a flat array of property aliases into a nested array with each index
     * of the outer array corresponding to the same index in {@link ClauseBasedTrait::clauses}.
     *
     * @return string[][]
     */
    private function unflatPropertyAliases(string ...$propertyAliases): array
    {
        $propertyAliasCountables = array_map(static function (PathsBasedInterface $pathsBased): int {
            return Iterables::count($pathsBased->getPropertyPaths());
        }, $this->clauses);
        return Iterables::split($propertyAliases, false, ...$propertyAliasCountables);
    }
}
