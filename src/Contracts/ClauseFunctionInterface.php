<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Contracts;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template TOutput
 * @template-extends FunctionInterface<TOutput>
 */
interface ClauseFunctionInterface extends ClauseInterface, FunctionInterface
{
}
