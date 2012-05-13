<?php

namespace Platformd\SpoutletBundle\Twig;

use Twig_Extension;
use Twig_Filter_Method;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig_Test_Method;
use Platformd\SpoutletBundle\Util\HttpUtil;

use Twig_Function_Method;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\UserBundle\Entity\User;

/**
 * Twig extension for generic things
 */
class SpoutletExtension extends Twig_Extension
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFilters()
    {
        return array(
            'pd_link' => new Twig_Filter_Method($this, 'linkToObject'),
            'site_name' => new Twig_Filter_Method($this, 'translateSiteName')
        );
    }

    public function getTests()
    {
        return array(
            'external' => new Twig_Test_Method($this, 'testExternal')
        );
    }

    public function getFunctions()
    {
        return array(
            'has_user_applied_to_giveaway' => new Twig_Function_Method(
                $this,
                'hasUserAppliedToGiveaway'
            ),
            'target_blank'                 => new Twig_Function_Method(
                $this,
                'getTargetBlank',
                array('is_safe' => array('html'))
            ),
        );
    }

    /**
     * @param $obj
     * @return string
     */
    public function linkToObject($obj)
    {
        if (!$obj instanceof LinkableInterface) {
            $type = is_object($obj) ? get_class($obj) : gettype($obj);

            throw new \InvalidArgumentException(sprintf('You must pass an object that implements LinkableInterface to the pd_link filter. "%s" given', $type));
        }

        return $this->getLinkableManager()->link($obj);
    }

    /**
     * Tests whether a URL string (or Linkable object) is an external URL
     *
     * @param $url
     * @return bool
     */
    public function testExternal($url)
    {
        if ($url instanceof LinkableInterface) {
            $url = $this->linkToObject($url);
        }

        $currentHost = $this->container->get('request')->getHost();

        return HttpUtil::isUrlExternal($url, $currentHost);
    }

    /**
     * Pass either a URL or a LinkableInterface object - this prints the target="_blank" if necessary
     *
     * @param string|LinkableInterface $url
     * @return string
     */
    public function getTargetBlank($url)
    {
        return $this->testExternal($url) ? ' target="_blank"' : '';
    }

    /**
     * Translates a site "key" (en) into a site name (Demo)
     *
     * @param $key
     * @return string
     */
    public function translateSiteName($key)
    {
        return MultitenancyManager::getSiteName($key);
    }

    /**
     * @param \Platformd\GiveawayBundle\Entity\Giveaway $giveaway
     * @return bool
     */
    public function hasUserAppliedToGiveaway(Giveaway $giveaway)
    {
        if (!$user = $this->getCurrentUser()) {
            return false;
        }

        return $this->getGiveawayManager()->hasUserAppliedToGiveaway($user, $giveaway);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'spoutlet';
    }

    /**
     * @return \Platformd\SpoutletBundle\Link\LinkableManager
     */
    private function getLinkableManager()
    {
        return $this->container->get('platformd.link.linkable_manager');
    }

    private function getCurrentUser()
    {
        $securityContext = $this->container->get('security.context');
        $token = $securityContext->getToken();

        return $token ? $token->getUser() : null;
    }

    /**
     * @return \Platformd\GiveawayBundle\Model\GiveawayManager
     */
    private function getGiveawayManager()
    {
        return $this->container->get('pd_giveaway.giveaway_manager');
    }
}