<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityManager;
use Gaufrette\Filesystem;

/**
* 
*/
class HomepageBannerManager
{
  /**
   * @var Doctrine\ORM\EntityManager;
   */
  private $manager;

  /**
   * @var Gaufrette\Filesystem
   */
  private $filesystem

  public function __construct(EntityManager $manager, Filesystem $filesystem) 
  {
    $this->manager = $manager;
    $this->filesystem = $filesystem;
  }
}