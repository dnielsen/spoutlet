<?php

namespace Knp\MediaBundle\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Knp\MediaBundle\Entity\Media;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Knp\MediaBundle\Model\MediaOwnerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Gaufrette\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Handles all actions taken on a Media on persistence:
 *      * Actually processes and moves the file upload
 *      * Sets the owner
 */
class MediaPersistenceListener implements EventSubscriber
{
    /**
     * The "safe" character to replace bad characters within a filename
     *
     * @var string
     */
    public static $replaceCharacter = '-';

    /**
     * @var \Gaufrette\Filesystem
     */
    private $filesystem;

    private $container;

    /**
     * Container is passed in to avoid a circular reference on security.context
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $containerInterface
     */
    public function __construct(Filesystem $mediaFilesystem, ContainerInterface $containerInterface)
    {
        $this->filesystem = $mediaFilesystem;
        $this->container = $containerInterface;
    }

    public function getSubscribedEvents()
    {
        return array(
            Events::prePersist,
            Events::preUpdate,
        );
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();

        if ($entity instanceof Media) {
            $this->processUpload($entity, $entityManager, false);

            $this->assignOwner($entity);
        }
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();

        if ($entity instanceof Media) {
            $hasChanged = false;

            if ($this->processUpload($entity)) {
                $hasChanged = true;
            }

            /*
             * the changeset has already been computed by now (amazingly),
             * so the only way I can see to make the field name change seen
             * is to manually trigger it by, frankly, doing some things I
             * shouldn't be doing.
             */
            if ($hasChanged) {
                $classMetadata = $entityManager->getClassMetadata(get_class($entity));
                $entityManager->getUnitOfWork()->recomputeSingleEntityChangeSet($classMetadata, $entity);
            }
        }
    }

    /**
     * Actually saves any new file objects, sets the path correctly
     *
     * @param \Knp\MediaBundle\Entity\Media $media
     * @return bool Was the entity updated?
     */
    private function processUpload(Media $media)
    {
        if ($file = $media->getFileObject()) {
            // todo - see if there is an old file object
            // this can be done by seeing if their is an existing filename field

            // get a unique filename and then write the contents
            $targetFilename = $this->generateFilename($file);
            $size = $this->filesystem->write($targetFilename, file_get_contents($file->getPathname()));

            // set some metadata
            $media->setFilename($targetFilename);
            $media->setMimeType($file->getMimeType());
            $media->setSize($size);

            return true;
        }
    }

    /**
     * Sets the owner in the Media if not set correctly
     *
     * @param \Knp\MediaBundle\Entity\Media $media
     * @return bool Was the entity updated?
     */
    private function assignOwner(Media $media)
    {
        if (!$media instanceof MediaOwnerInterface) {
            return false;
        }

        if (!$media->getOwner() && $this->getUser()) {
            $media->setOwner($this->getUser());

            return true;
        }
    }

    /**
     * @return \Symfony\Component\Security\Core\User\UserInterface
     */
    private function getUser()
    {
        $token = $this->container->get('security.context')->getToken();

        return $token ? $token->getUser() : null;
    }

    /**
     * Returns a unique name based on moving the File into the filesystem
     *
     * @param \Symfony\Component\HttpFoundation\File\File $file
     * @return string
     */
    protected function generateFilename(File $file)
    {
        if ($file instanceof UploadedFile) {
            $cleanedName = $this->cleanFilename($file->getClientOriginalName());
        } else {
            $cleanedName = $this->cleanFilename($file->getFilename());
        }

        // if no file by the cleaned original name already exists, just return the originally uploaded filename
        if (!$this->filesystem->has($cleanedName)) {
            return $cleanedName;
        }

        // iterate until we find a unique filename of format original_filename-n.ext where n is an integer
        $i = 1;
        while (true) {
            $basename = str_replace('.'.$file->guessExtension(), '', $cleanedName);
            $newFilename = sprintf('%s-%s.%s', $basename, ++$i, $file->guessExtension());
            if (!$this->filesystem->has($newFilename)) {
                return $newFilename;
            }
        }
    }

    /**
     * @param string $raw
     * @return string
     */
    protected function cleanFilename($raw)
    {
        // find and then remove all of the bad character
        $pattern="/([[:alnum:]_\.-]*)/";
        $cleaned = str_replace(str_split(preg_replace($pattern, self::$replaceCharacter, $raw)), self::$replaceCharacter, $raw);

        // remove duplicate $replace characters
        $pattern = '#('.preg_quote(self::$replaceCharacter).'){2,}#';

        return preg_replace($pattern, self::$replaceCharacter, $cleaned);
    }
}