<?php

declare(strict_types=1);

namespace Tests\data\Model;

use Doctrine\ORM\Mapping as ORM;
use Tests\data\Model\Person;

/**
 * @ORM\Entity()
 */
class Book
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
     * @ORM\Column(type="string")
     */
    protected $title;

    /**
     * @var Person
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="books")
     */
    protected $author;

    /**
     * @var string[]
     * @ORM\Column(type="simple_array")
     */
    protected $tags;
}
