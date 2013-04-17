<?php

namespace Platformd\SpoutletBundle;

use Platformd\SpoutletBundle\PathResolver;
use Platformd\SpoutletBundle\Entity\AbstractEvent;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\GiveawayBundle\Entity\GiveawayTranslation;

/**
 * Path resolver for the giveaways
 */
class BackgroundPathResolver extends PathResolver
{
    /**
     * Returns the path to the background image
     *
     * @param $event
     * @param array $options
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getPath($event, array $options)
    {
        $path = AbstractEvent::PREFIX_PATH_BACKGROUND.$event->getBackgroundImagePath();

        return parent::getPath($path, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($media, array $options)
    {
        return ($media instanceof Giveaway || $media instanceof GiveawayTranslation);
    }

}
