<?php

namespace DoctrineRepoHelperTest\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Box
 * @package DoctrineRepoHelper\Entity
 * @ORM\Entity(repositoryClass="DoctrineRepoHelperTest\Repository\BoxRepository")
 */
class Box
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;
}
