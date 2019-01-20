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
}
