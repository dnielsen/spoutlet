<?php

namespace Knp\MediaBundle\Util;

use Knp\MediaBundle\Entity\Media;
use Doctrine\ORM\EntityManager;

/**
 * General purpose utility class for media
 */
class MediaUtil
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Should be called when saving object A, which relates to a Media object
     * on a property called, for example, "mainImage":
     *
     * // the object with the media relation on mainImage
     * $someObjectA = ....
     *
     * $mediaUtil = $this->container->get('knp_media.util.media_util');
     * if (!$mediaUtil->persistRelatedMedia($someObjectA->getMainImage())) {
     *     $someObjectA->setMainImage(null)
     * }
     *
     * The purpose is to either to persist the media (if it has a FileObject
     * on it, OR if it was previously saved), or to return false so you
     * can unset the empty object entirely.
     *
     * @param \Knp\MediaBundle\Entity\Media $media
     * @return bool
     */
    public function persistRelatedMedia(Media $media = null)
    {
        if ($media == null) {
            return false;
        }

        // if it's already processed and legit (previous image) or it has a new image
        if ($media->getFilename() || $media->getFileObject()) {
            $this->em->persist($media);

            return true;
        }

        return false;
    }
}
