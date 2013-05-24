<?php

namespace Platformd\VideoBundle\Validator;

use Symfony\Component\Validator\ConstraintValidator,
    Symfony\Component\Validator\Constraint
;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Platformd\VideoBundle\Model\YoutubeManager;
use Platformd\SpoutletBundle\Util\SiteUtil;

/**
 * Provides the actual validation for Groups and Categories
 */
class YoutubeGroupCategoryValidator extends ConstraintValidator
{
    protected $youtube;
    protected $siteUtil;
    protected $container;

    public function __construct(YoutubeManager $youtube, SiteUtil $siteUtil, Container $container)
    {
        $this->youtube      = $youtube;
        $this->siteUtil     = $siteUtil;
        $this->container    = $container;
    }

    public function isValid($value, Constraint $constraint)
    {
        $hasGroups  = $this->siteUtil->getCurrentSite()->getSiteFeatures()->getHasGroups();
        $message    = $hasGroups ? 'youtube.errors.no_gallery' : 'youtube.errors.no_gallery_groups_disabled';

        $this->setMessage($message, array('%string%' => $value));

        if($hasGroups) {
            if(count($value->getGalleries()) == 0 && count($value->getGroups()) == 0) {
                return false;
            }
        } else {
            if(count($value->getGalleries()) == 0) {
                return false;
            }
        }

        return true;
    }
}
