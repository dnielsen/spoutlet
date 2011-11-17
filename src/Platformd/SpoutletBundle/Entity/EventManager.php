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
        $this->updateBannerImage($event);
        $this->manager->persist($event);
        $this->manager->flush();
    }
    
    /** 
     * Update an event's banner image
     *
     * @param \Platformd\SpoutletBundle\Entity\Event $event
     */
    protected function updateBannerImage(Event $event)
    {   
        $file = $event->getBannerImageFile();

        if (null == $file) {

            return;
        }

        $filename = sha1($event->getId().'-'.uniqid()).'.'.$file->guessExtension();
        $this->filesystem->write('banner/'.$filename, file_get_contents($file->getPathname()));
        $event->setBannerImage($filename);
    }
}