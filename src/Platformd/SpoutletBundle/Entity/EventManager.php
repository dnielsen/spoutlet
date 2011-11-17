<?php

namespace Platformd\SpoutletBundle\Entity;

use Gaufrette\Filesystem;
use Doctrine\ORM\EntityManager;

/**
* 
*/
class EventManager
{
    private $filesystem;

    private $manager;
    
    public function __construct(Filesystem $filesystem, EntityManager $manager)
    {
        $this->filesystem = $filesystem;
        $this->manager = $manager;
    }

    public function save(Event $event)
    {
        // Todo : handle upload to S3
        $event->updateBannerImage();
        $this->manager->persist($event);
        $this->manager->flush();
    }
}