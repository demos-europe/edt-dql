<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Contracts;

interface OrderByInterface extends ClauseInterface
{
    public const ASCENDING = 'ASC';
    public const DESCENDING = 'DESC';

    /**
     * @return string Either {@link OrderByInterface::ASCENDING} or {@link OrderByInterface::DESCENDING}.
     */
    public function getDirection(): string;
}
