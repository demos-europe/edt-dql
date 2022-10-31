<?php

declare(strict_types=1);

namespace Tests\data\DqlModel;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Person
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private int $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $name;

    private ?string $pseudonym = null;

    /**
     * @ORM\OneToOne(targetEntity="Birth")
     */
    private Birth $birth;

    /**
     * @ORM\OneToMany(targetEntity="Book", mappedBy="author")
     * @var Collection<int, Book>
     */
    private Collection $books;
}
