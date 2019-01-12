<?php

namespace DoctrineRepoHelperTest\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Example
 * @package DoctrineRepoHelper\Entity
 * @ORM\Entity(repositoryClass="DoctrineRepoHelperTest\Repository\ExampleRepository")
 */
class Example
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;
}