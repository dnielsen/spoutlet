<?php

namespace Platformd\VideoBundle\Validator;

use Symfony\Component\Validator\ConstraintValidator,
    Symfony\Component\Validator\Constraint
;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Platformd\VideoBundle\Model\YoutubeManager;
use Platformd\SpoutletBundle\Util\IpLookupUtil;

/**
 * Provides the actual validation for YoutubeGeoRestriction
 */
class YoutubeRestrictionValidator extends ConstraintValidator
{
    protected $youtube;
    protected $ipLookupUtil;
    protected $container;

    public function __construct(YoutubeManager $youtube, IpLookupUtil $ipLookupUtil, Container $container)
    {
        $this->youtube      = $youtube;
        $this->ipLookupUtil = $ipLookupUtil;
        $this->container    = $container;
    }

    public function isValid($value, Constraint $constraint)
    {
        $ipAddress    = $this->container->get('request')->getClientIp(true);
        $clientLocale = $this->ipLookupUtil->getCountryCode($ipAddress);
        $restrictions = $this->youtube->getYoutubeRestrictions($value);

        $this->setMessage($constraint->message, array('%string%' => $value));

        if(isset($restrictions['type']) && isset($restrictions['relationship'])) {
            if($restrictions['type'] == 'country' && $restrictions['relationship'] == 'allow') {
                if(isset($restrictions['countries'])) {
                    $countries = explode(" ", $restrictions['countries']);
                    foreach ($countries as $country) {
                        if($clientLocale == $country) {
                            return true;
                        }
                    }
                }
            } else if ($restrictions['type'] == 'country' && $restrictions['relationship'] == 'deny') {
                if(isset($restrictions['countries'])) {
                    $countries = explode(" ", $restrictions['countries']);
                    foreach ($countries as $country) {
                        if($clientLocale == $country) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }
}
