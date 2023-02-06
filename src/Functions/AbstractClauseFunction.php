<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Base;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Math;
use EDT\DqlQuerying\Contracts\ClauseFunctionInterface;
use EDT\DqlQuerying\Contracts\ClauseInterface;
use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Utilities\Iterables;
use function count;

/**
 * @template TOutput
 * @template-implements ClauseFunctionInterface<TOutput>
 */
abstract class AbstractClauseFunction implements ClauseFunctionInterface
{
    use ClauseTrait;

    /**
     * @var list<ClauseInterface>
     */
    protected array $clauses = [];

    protected Expr $expr;

    /**
     * Will set the clauses of this class. By calling {@link AbstractClauseFunction::getDqls()}
     * the {@link ClauseInterface::asDql()} of all clauses will be invoked and the results
     * returned inside an array. E.g. if you passed a single clause the returned array will contain
     * one element being the result of the clause. If you passed two clauses the returned array will
     * contain two elements, each being the result of the corresponding clause.
     *
     * @param FunctionInterface<TOutput> $function
     */
    public function __construct(
        private readonly FunctionInterface $function,
        ClauseInterface ...$clauses
    ) {
        $this->clauses = $clauses;
        $this->expr = new Expr();
    }

    public function getPropertyPaths(): array
    {
        return $this->function->getPropertyPaths();
    }

    public function apply(array $propertyValues)
    {
        return $this->function->apply($propertyValues);
    }

    public function __toString(): string
    {
        return (string) $this->function;
    }

    public function getClauseValues(): array
    {
        return Iterables::mapFlat(
            static fn (ClauseInterface $clause): array => $clause->getClauseValues(),
            $this->clauses
        );
    }

    /**
     * Will return all DQL results of the clauses passed in {@link AbstractClauseFunction::setClauses()}.
     *
     * @param string[] $valueReferences
     * @param string[] $propertyAliases
     *
     * @return list<Comparison|Func|Math|Base|string>
     */
    protected function getDqls(array $valueReferences, array $propertyAliases): array
    {
        $nestedValueReferences = $this->unflatClauseReferences(...$valueReferences);
        $nestedPropertyAliases = $this->unflatPropertyAliases(...$propertyAliases);
        return array_map(
            static fn (
                ClauseInterface $clause,
                array $valueReferences,
                array $propertyAliases
            ) => $clause->asDql($valueReferences, $propertyAliases),
            $this->clauses,
            $nestedValueReferences,
            $nestedPropertyAliases
        );
    }

    /**
     * Splits a flat array of value references into a nested array with each index
     * of the outer array corresponding to the same index in {@link AbstractClauseFunction::clauses}.
     *
     * @return list<list<string>>
     */
    protected function unflatClauseReferences(string ...$valueReferences): array
    {
        $clauseValueCountables = array_map(
            static fn (ClauseInterface $clause): int => count($clause->getClauseValues()),
            $this->clauses
        );

        return array_map('array_values', Iterables::split($valueReferences, ...$clauseValueCountables));
    }

    /**
     * Splits a flat array of property aliases into a nested array with each index
     * of the outer array corresponding to the same index in {@link AbstractClauseFunction::clauses}.
     *
     * @return list<list<string>>
     */
    private function unflatPropertyAliases(string ...$propertyAliases): array
    {
        $propertyAliasCountables = array_map(
            static fn (PathsBasedInterface $pathsBased): int => count($pathsBased->getPropertyPaths()),
            $this->clauses
        );

        return array_map('array_values', Iterables::split($propertyAliases, ...$propertyAliasCountables));
    }
}
