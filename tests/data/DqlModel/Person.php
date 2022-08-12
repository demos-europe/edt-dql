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
     *
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;
    /**
     * @var string|null
     */
    private $pseudonym;
    /**
     * @ORM\OneToOne(targetEntity="Birth")
     * @var Birth
     */
    private $birth;

    /**
     * @ORM\OneToMany(targetEntity="Book", mappedBy="author")
     * @var Collection<int,Book>
     */
    private $books;
}
