<?php

namespace DoctrineRepoHelperTest\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class AnotherExample
 * @package DoctrineRepoHelper\Entity
 * @ORM\Entity(repositoryClass="DoctrineRepoHelperTest\Repository\AnotherExampleRepository")
 */
class AnotherExample
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $string;

    /**
     * @var integer
     * @ORM\Column(type="bigint")
     */
    private $bigint;

    /**
     * @var
     * @ORM\Column(type="binary")
     */
    private $binary;

    /**
     * @var
     * @ORM\Column(type="blob")
     */
    private $blob;

    /**
     * @var
     * @ORM\Column(type="boolean")
     */
    private $boolean;

    /**
     * @var
     * @ORM\Column(type="date", nullable=true)
     */
    private $date;

    /**
     * @var
     * @ORM\Column(type="datetime_immutable")
     */
    private $datetime_immutable;

    /**
     * @var
     * @ORM\Column(type="date_immutable")
     */
    private $date_immutable;

    /**
     * @var
     * @ORM\Column(type="text", nullable=true)
     */
    private $text;

    /**
     * @var
     * @ORM\Column(type="array")
     */
    private $array;

    /**
     * @var Box
     * @ORM\OneToOne(targetEntity="DoctrineRepoHelperTest\Entity\Box")
     * @ORM\JoinColumn(name="box_id", referencedColumnName="id")
     */
    private $box;
}
