<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Functions;

use EDT\DqlQuerying\Contracts\ClauseInterface;
use Webmozart\Assert\Assert;

trait ClauseTrait
{
    /**
     * Can be used if a single clause was passed to {@link AbstractClauseFunction::setClauses()} to
     * get its DQL directly. If not exactly one clause was passed in the setter then this
     * function call will throw an exception.
     */
    protected function getOnlyClause(): ClauseInterface
    {
        Assert::count($this->clauses, 1);
        return $this->clauses[0];
    }
}
