<?php

namespace Platformd\SpoutletBundle\Entity;

use Gaufrette\Filesystem;
use Doctrine\ORM\EntityManager;
use Platformd\SpoutletBundle\Entity\AbstractEvent;

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

    public function save(AbstractEvent $event)
    {
        // Todo : handle upload to S3
        $this->updateBannerImage($event);
        $this->updateGeneralImage($event);
        $this->manager->persist($event);
        $this->manager->flush();
    }
    
    /** 
     * Update an event's banner image
     *
     * @param \Platformd\SpoutletBundle\Entity\Event $event
     */
    protected function updateBannerImage(AbstractEvent $event)
    {   
        $file = $event->getBannerImageFile();

        if (null == $file) {
            return;
        }

        $filename = sha1($event->getId().'-'.uniqid()).'.'.$file->guessExtension();
        // prefix repeated in BannerPathResolver
        $this->filesystem->write(AbstractEvent::PREFIX_PATH_BANNER.$filename, file_get_contents($file->getPathname()));
        $event->setBannerImage($filename);
    }

    /**
     * Update an event's general image
     *
     * @param \Platformd\SpoutletBundle\Entity\Event $event
     */
    protected function updateGeneralImage(AbstractEvent $event)
    {
        $file = $event->getGeneralImageFile();

        if (null == $file) {
            return;
        }

        $filename = sha1($event->getId().'-'.uniqid()).'.'.$file->guessExtension();
        // prefix repeated in BannerPathResolver
        $this->filesystem->write(AbstractEvent::PREFIX_PATH_GENERAL .$filename, file_get_contents($file->getPathname()));
        $event->setGeneralImage($filename);
    }
}