<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Contracts;

interface OrderByInterface extends ClauseInterface
{
    public const ASCENDING = 'ASC';
    public const DESCENDING = 'DESC';

    /**
     * @return OrderByInterface::ASCENDING|OrderByInterface::DESCENDING
     */
    public function getDirection(): string;
}
