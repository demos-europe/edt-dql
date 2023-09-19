<?php

declare(strict_types=1);

namespace Tests\ClassGeneration;

use Doctrine\ORM\Mapping as ORM;

class EntityB
{
    #[ORM\Column]
    protected $propertyA;
}
