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
use Monolog\Logger;
use Knp\MediaBundle\Exception\UniqueFilenameGenerationException;

/**
 * Handles all actions taken on a Media on persistence:
 *      * Actually processes and moves the file upload
 *      * Sets the owner
 */
class MediaPersistenceListener implements EventSubscriber
{
    const UNIQUE_FILENAME_TRIES_LIMIT = 10;

    /**
     * The "safe" character to replace bad characters within a filename
     *
     * @var string
     */
    public static $replaceCharacter = '-';

    private static $mimeTypeOverrides = array(
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
        'application/vnd.openxmlformats-officedocument.presentationml.template',
        'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.openxmlformats-officedocument.presentationml.slide',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        'application/vnd.ms-excel.addin.macroEnabled.12',
        'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
    );

    /**
     * @var \Gaufrette\Filesystem
     */
    private $filesystem;

    private $container;

    /**
     * @var \Monolog\Logger
     */
    private $logger;

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
     * @param \Monolog\Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
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

            // create a metadata array to be sent to S3
            $metadata = array();

            $clientMimeType = $file->getClientMimeType();
            $mimeType = in_array($clientMimeType, self::$mimeTypeOverrides) ? $clientMimeType : $file->getMimeType();

            if ($mimeType) {
                $metadata['content-type'] = $file->getMimeType();
                $media->setMimeType($mimeType);
            }

            $this->log(sprintf('About to save uploaded filename "%s"', $targetFilename));
            $size = $this->filesystem->write(
                $targetFilename,
                file_get_contents($file->getPathname()),
                false,
                $metadata
            );
            $this->log('Uploaded saving complete!');

            // set some metadata
            $media->setFilename($targetFilename);
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

        $this->log(sprintf('About to find a unique filename for "%s"', $cleanedName));

        // if no file by the cleaned original name already exists, just return the originally uploaded filename
        if (!$this->filesystem->has($cleanedName)) {
            $this->log(sprintf('Found unique filename "%s"', $cleanedName));

            return $cleanedName;
        }

        // iterate until we find a unique filename of format original_filename-n.ext where n is an integer
        $i = 1;
        while (true) {
            $pathParts = pathinfo($cleanedName);

            $basename = str_replace('.'.$pathParts['extension'], '', $cleanedName);
            $newFilename = sprintf('%s-%s.%s', $pathParts['filename'], $this->generateRandomString($i), $pathParts['extension']);
            if (!$this->filesystem->has($newFilename)) {
                $this->log(sprintf('Found unique filename "%s"', $newFilename));

                return $newFilename;
            } else {
                $this->log(sprintf('Filename "%s" was already taken, trying again', $newFilename));

                // if we try so many times, something is probably wrong and we should bail
                if ($i > self::UNIQUE_FILENAME_TRIES_LIMIT) {
                    throw new UniqueFilenameGenerationException($cleanedName);
                }
            }

            $i++;
        }
    }

    /**
     * Generates a random string or a length that's based on how many tries this is
     *
     * @param integer $attemptCount
     * @return string
     */
    private function generateRandomString($attemptCount)
    {
        // length gets longer on each "try", but only ever 3 tries or so
        $length = ceil($attemptCount / 3);
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz_';

        $string = '';
        for ($p = 0; $p < $length; $p++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        return $string;
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

    /**
     * Log a message
     *
     * @param string $msg
     * @param int $level
     */
    private function log($msg, $level = Logger::DEBUG)
    {
        if ($this->logger) {
            $this->logger->addRecord($level, $msg);
        }
    }
}
