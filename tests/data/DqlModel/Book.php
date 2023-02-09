<?php

declare(strict_types=1);

namespace Tests\data\DqlModel;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Book
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'string')]
    protected string $title;

    #[ORM\ManyToOne(targetEntity: Person::class, inversedBy: 'books')]
    protected Person $author;

    /**
     * @var string[]
     */
    #[ORM\Column(type: 'simple_array')]
    protected array $tags;
}
