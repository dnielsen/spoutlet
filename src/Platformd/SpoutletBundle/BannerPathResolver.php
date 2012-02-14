<?php

namespace Platformd\SpoutletBundle;

use Platformd\SpoutletBundle\PathResolver;
use Platformd\SpoutletBundle\Entity\AbstractEvent;

/**
* Path resolver for the event images
*/
class BannerPathResolver extends PathResolver
{

  /**
   * Returns the path to either the banner image or the general image
   *
   * @param $event
   * @param array $options
   * @return string
   * @throws \InvalidArgumentException
   */
  public function getPath($event, array $options)
  {
    /** @var $event \Platformd\SpoutletBundle\Entity\AbstractEvent */

    $type = isset($options['type']) ? $options['type'] : 'banner';

    switch ($type) {
        // TODO - both of these prefixes are repeated in EventManager
        case 'general':
            $path = AbstractEvent::PREFIX_PATH_GENERAL.$event->getGeneralImage();
            break;
        case 'banner':
            $path = AbstractEvent::PREFIX_PATH_BANNER.$event->getBannerImage();
            break;
        default:
            throw new \InvalidArgumentException('Invalid type '.$type);
    }

    return parent::getPath($path, $options);
  }

  /**
   * {@inheritDoc}
   */
  public function supports($media, array $options)
  {

    return $media instanceof AbstractEvent;
  }

}