<?php

namespace Platformd\SpoutletBundle\Entity;

use Gaufrette\Filesystem;
use Doctrine\ORM\EntityManager;
use Platformd\SpoutletBundle\Entity\AbstractEvent;
use Platformd\SpoutletBundle\Entity\Thread;

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
        /*
            Required because event comment thread's ids
            are just the event slug, meaning that if the
            slug changes, the thread id must also be changed.
        */
        $threadRepo     = $this->manager->getRepository('SpoutletBundle:Thread');
        $commentRepo    = $this->manager->getRepository('SpoutletBundle:Comment');

        $unit = $this->manager->getUnitOfWork();
        $unit->computeChangeSets();
        $changeset = $unit->getEntityChangeSet($event);

        if (array_key_exists('slug', $changeset) && $changeset['slug'][0] != $changeset['slug'][1]) {

            $newThread = new Thread();
            $thread = $threadRepo->find($changeset['slug'][0]);

            if ($thread) {
                $newThread->setIsCommentable($thread->isCommentable());
                $newThread->setLastCommentAt($thread->getLastCommentAt());
                $newThread->setCommentCount($thread->getCommentCount());

                $permalink = str_replace($changeset['slug'][0], $changeset['slug'][1], $thread->getPermalink());

                $newThread->setPermalink($permalink);
                $newThread->setId($changeset['slug'][1]);
                $this->manager->persist($newThread);

                $comments = $commentRepo->findByThread($changeset['slug'][0]);

                if ($comments) {
                    foreach ($comments as $comment) {
                        $comment->setThread($newThread);
                        $this->manager->persist($comment);
                    }
                }

                $this->manager->flush();
                $this->manager->remove($thread);
                $this->manager->flush();
            }
        }

        // Todo : handle upload to S3
        $this->updateBannerImage($event);
        $this->updateGeneralImage($event);
        $this->manager->persist($event);
        $this->manager->flush();
    }

    /**
     * Update an event's banner image
     *
     * @param \Platformd\SpoutletBundle\Entity\AbstractEvent $event
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
     * @param \Platformd\SpoutletBundle\Entity\AbstractEvent $event
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
