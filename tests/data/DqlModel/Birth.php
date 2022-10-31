<?php

declare(strict_types=1);

namespace Tests\data\DqlModel;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Birth
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private int $id;

    private string $country;

    private ?string $region = null;

    private string $locality;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $street;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $streetNumber;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private int $day;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private int $month;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private int $year;
}
