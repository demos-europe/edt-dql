<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Contracts;

use EDT\Querying\Contracts\FunctionInterface;

/**
 * @template R
 * @template-extends FunctionInterface<R>
 */
interface ClauseFunctionInterface extends ClauseInterface, FunctionInterface
{
}
