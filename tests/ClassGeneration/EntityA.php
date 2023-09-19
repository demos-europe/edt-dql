<?php

declare(strict_types=1);

namespace Tests\ClassGeneration;

use Doctrine\ORM\Mapping as ORM;

class EntityA
{
    #[ORM\Column]
    protected $propertyA;

    #[ORM\ManyToMany(targetEntity: EntityB::class)]
    protected $propertyB;

    #[ORM\OneToMany(targetEntity: EntityB::class)]
    protected $propertyC;

    #[ORM\ManyToOne(targetEntity: EntityB::class)]
    protected $propertyD;

    #[ORM\OneToOne(targetEntity: EntityB::class)]
    protected $propertyE;

    /**
     * @ORM\Column
     */
    protected $propertyF;

    /**
     * @ORM\ManyToMany(targetEntity=EntityB::class)
     */
    protected $propertyG;

    /**
     * @ORM\OneToMany(targetEntity=EntityB::class)
     */
    protected $propertyH;

    /**
     * @ORM\ManyToOne(targetEntity=EntityB::class)
     */
    protected $propertyI;

    /**
     * @ORM\OneToOne(targetEntity=EntityB::class)
     */
    protected $propertyJ;

    protected $propertyK;

    public function getName(): string
    {

    }
}
