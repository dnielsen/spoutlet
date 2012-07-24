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
            'pd_link_target' => new Twig_Filter_Method($this, 'linkToObjectTarget', array('is_safe' => array('html'))),
            'pd_link_full' => new Twig_Filter_Method($this, 'linkToObjectFull', array('is_safe' => array('html'))),
            'site_name' => new Twig_Filter_Method($this, 'translateSiteName'),
            'absolute_url' => new Twig_Filter_Method($this, 'getAbsoluteUrl')
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
            'has_user_applied_to_giveaway'  => new Twig_Function_Method(
                $this,
                'hasUserAppliedToGiveaway'
            ),
            'target_blank'                  => new Twig_Function_Method(
                $this,
                'getTargetBlank',
                array('is_safe' => array('html'))
            ),
            'is_admin_page'                 => new Twig_Function_Method(
                $this,
                'isAdminPage'
            ),
            'site_has_feature'              => new Twig_Function_Method(
                $this,
                'siteHasFeature'
            ),
            'site_link'                     => new Twig_Function_Method(
                $this,
                'siteLink', array('is_safe' => array('html'))
            ),
            'get_avatar_url'                => new Twig_Function_Method(
                $this,
                'getAvatarUrl'
            ),
        );
    }

    /**
     * @return string
     */

    public function getAbsoluteUrl($obj)
    {
        # look at the url that is being passed in and if it is relative, return base path + url... if it is not, then return obj

        $request = $this->container->get('request');
        $base = $request->getScheme() . '://' . $request->getHost();
        $path = $obj[0] == '/' ? $obj : '/' . $obj;

        /* return if already absolute URL */
        if (parse_url($obj, PHP_URL_SCHEME) != '') {
            return $obj;
        }

        return $base.$path;
    }

     /**
     * @param $obj
     */
    private function ensureLinkable($obj)
    {
        if (!$obj instanceof LinkableInterface) {
            $type = is_object($obj) ? get_class($obj) : gettype($obj);

            throw new \InvalidArgumentException(sprintf('You must pass an object that implements LinkableInterface to a pd_link* filter. "%s" given', $type));
        }
    }

    /**
     * @param $obj
     * @return string
     */
    public function linkToObjectTarget($obj)
    {
        $linkToObject = $this->linkToObject($obj);

        return $this->testExternal($linkToObject) ? 'target="_blank"' : '';
    }

    /**
     * @param $obj
     * @return string
     */
    public function linkToObject($obj)
    {
        $this->ensureLinkable($obj);

        return $this->getLinkableManager()->link($obj);
    }

    /**
     * @param $obj
     * @return string
     */
    public function linkToObjectFull($obj, $urlText = null)
    {
        $this->ensureLinkable($obj);

        $url        = $this->getLinkableManager()->link($obj);
        $target     = $this->linkToObjectTarget($obj);
        $urlText    = $urlText ?: $url;

        if (strlen($target) > 0) {
            $target = ' '.$target;
        }

        return sprintf('<a href="%s"%s>%s</a>', $url, $target, $urlText);
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
     * Determines if we're on admin page, simply by looking for a "/admin*" URL
     *
     * @return bool
     */
    public function isAdminPage()
    {
        $pathInfo = $request = $this->container->get('request')->getPathInfo();

        return (strpos($pathInfo, '/admin') === 0);
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
     * A small function to allow the various link types that are common across all AWA sites to have their own unique
     * destinations and text.  This was in translations before but that forces all sites that share a common language
     * to have the same links.  This should definately be moved elsewhere but I wanted to discuss the best location
     * for site specific data-related settings first.
     *
     * @param string $linkType
     * @return string
     * @throws \InvalidArgumentException
     */
    public function SiteLink($linkType) {

        $locale = $this->container->get('session')->getLocale();

        switch ($linkType) {
            case 'ALIENWARE':       return $this->GetAlienwareLink($locale);
            case 'ALIENWARE_BARE':  return $this->GetAlienwareBareLink($locale);
            case 'FACEBOOK':        return $this->GetFacebookLink($locale);
            case 'TWITTER':         return $this->GetTwitterLink($locale);
            case 'USER_EVENT':      return $this->GetUserEventLink($locale);
            case 'USER_GAME_ID':    return $this->GetUserGameIdLink($locale);

            default:
                throw new \InvalidArgumentException(sprintf('Unknown link type "%s"', $linkType));
        }
    }

    /**
     * Use this to find the proper avatar URL for a user, and wrap it in an asset call
     *
     *     asset(get_avatar_url(comment.author))
     *
     * @param \Platformd\UserBundle\Entity\User $user
     * @param string $default
     * @return string
     */
    public function getAvatarUrl(User $user, $default = '/images/profile-default.png')
    {
        if ($user->getCevoAvatarUrl()) {
            return $user->getCevoAvatarUrl();
        }

        if ($user->getAvatar() && $user->isAvatarApproved()) {
            return $this->getMediaExposer()->getPath($user);
        }

        return $default;
    }

    private function GetAlienwareLink($locale) {
        $format = '<a href="%s" target="_blank">%s</a>';
        $enLink = 'http://alienware.com';
        $enText = 'Need a kickass rig? Check out Alienware';

        switch($locale) {
            case 'ja':      return sprintf($format, 'http://alienware.jp/', 'ALIENWARE.JPへ移動する');
            case 'zh':      return sprintf($format, 'http://alienware.com.cn/', '需要一个牛逼的装备? 请看看 Alienware');
            case 'es':      return sprintf($format, 'http://www.alienware.com/mx/', '¿Busca un equipo poderoso?<br />¡Encuéntrelo en Alienware!');
            case 'en_SG':   return sprintf($format, 'http://allpowerful.com/asia', $enText);
            case 'en_AU':   return sprintf($format, 'http://www.alienware.com.au/', $enText);
            case 'en_GB':   return sprintf($format, 'http://www1.euro.dell.com/content/topics/segtopic.aspx/alienware?c=uk&cs=ukdhs1&l=en&s=dhs&~ck=mn', $enText);
            case 'en_IN':   return sprintf($format, 'http://www.alienware.co.in/', $enText);
            case 'en_US':   return sprintf($format, $enLink, $enText);

            default:        return false;
        }
    }

    private function GetAlienwareBareLink($locale) {
        $format = '<a href="%s" target="_blank">%s</a>';
        $enLink = 'http://alienware.com';
        $enText = 'Alienware';

        switch($locale) {
            case 'ja':      return sprintf($format, 'http://alienware.jp/', $enText);
            case 'zh':      return sprintf($format, 'http://alienware.com.cn/', $enText);
            case 'es':      return sprintf($format, 'http://www.alienware.com/mx/', $enText);
            case 'en_SG':   return sprintf($format, 'http://allpowerful.com/asia', $enText);
            case 'en_AU':   return sprintf($format, 'http://www.alienware.com.au/', $enText);
            case 'en_GB':   return sprintf($format, 'http://www1.euro.dell.com/content/topics/segtopic.aspx/alienware?c=uk&cs=ukdhs1&l=en&s=dhs&~ck=mn', $enText);
            case 'en_IN':   return sprintf($format, 'http://www.alienware.co.in/', $enText);
            case 'en_US':   return sprintf($format, $enLink, $enText);
            case 'en':   return sprintf($format, $enLink, $enText);

            default:        return false;
        }
    }

    private function GetFacebookLink($locale) {
        $format = '<a href="http://www.facebook.com/%s" target="_blank"><img src="/bundles/spoutlet/images/icons/icon-fb-14.png" alt="%s" /></a>';
        $enLink = 'Alienware';
        $enAltText = 'Facebook';

        switch($locale) {
            case 'ja':      return sprintf($format, 'DellJapan', $enAltText);
            case 'es':      return sprintf($format, 'AlienwareLatinoamerica', $enAltText);
            case 'en_IN':   return sprintf($format, 'alienwareindia', $enAltText);
            case 'en_US':   return sprintf($format, $enLink, $enAltText);

            default:        return false;
        }
    }

    private function GetTwitterLink($locale) {
        $format = '<a href="http://twitter.com/#!/%s" target="_blank"><img src="/bundles/spoutlet/images/icons/icon-tw-14.png" alt="%s" /></a>';
        $enLink = 'alienware';
        $enAltText = 'Twitter';

        switch($locale) {
            case 'ja':      return sprintf($format, 'Alienware_JP', $enAltText);
            case 'es':      return sprintf($format, 'AlienwareLatAm', $enAltText);
            case 'en_US':   return sprintf($format, $enLink, $enAltText);

            default:        return false;
        }
    }

    private function GetUserEventLink($locale) {
        $format = '<a href="http://www.alienwarearena.com/%s/account/events/" target="_blank">%s</a>';
        $enLink = $this->container->get('router')->generate('accounts_events');
        $enLinkText = 'Events';

        switch($locale) {
            case 'ja':      return sprintf($format, 'japan', '参加済みイベント');
            case 'zh':      return sprintf($format, 'china', '我的活动');
            case 'en_US':   return sprintf($format, $enLink, $enLinkText);

            default:        return false;
        }
    }

    private function GetUserGameIdLink($locale) {
        $format = '<a href="http://www.alienwarearena.com/%s/account/ids/" target="_blank">%s</a>';

        switch($locale) {
            case 'ja':      return sprintf($format, 'japan', 'Game IDs');
            case 'zh':      return sprintf($format, 'china', 'Game IDs');

            default:        return false;
        }
    }

    /**
     * A little temporary function (temporary because it will be much more
     * built-out in the future).
     *
     * Currently-supported features:
     *  * EXTRA_NAVIGATION: do we show the extra navigation items in the layout?
     *  * STEAM_XFIRE_COMMUNITIES: do we show the steam/xfire community links in the footer?
     *
     * @param string $feature
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function siteHasFeature($feature)
    {
        $locale = $this->container->get('session')->getLocale();
        $japan = in_array($locale, array('ja'));
        $chinaOrJapan = in_array($locale, array('zh', 'ja'));
        $chinaOrJapanOrLatam = in_array($locale, array('zh', 'ja', 'es'));
        $northAmerica = in_array($locale, array('en_US'));
        $northAmericaOrEurope = in_array($locale, array('en_US', 'en_GB', 'en'));

        switch ($feature) {
            case 'EXTRA_NAVIGATION':            return !$chinaOrJapan;
            case 'VIDEO':                       return !$chinaOrJapan;
            case 'WALLPAPER':                   return !$chinaOrJapan;
            case 'STEAM_XFIRE_COMMUNITIES':     return !$chinaOrJapan;
            case 'SWEEPSTAKES':                 return $northAmerica;
            case 'FORUMS':                      return !$chinaOrJapan;
            case 'ARP':                         return !$chinaOrJapan;
            case 'NEWS':                        return $chinaOrJapan;
            case 'DEALS':                       return $northAmericaOrEurope;
            case 'GAMES':                       return $chinaOrJapan;
            case 'GAMES_NAV_DROP_DOWN':         return !$chinaOrJapanOrLatam;
            case 'MESSAGES':                    return !$chinaOrJapan;
        }

        throw new \InvalidArgumentException(sprintf('Unknown feature "%s"', $feature));
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

    /**
     * @return \MediaExposer\Exposer
     */
    private function getMediaExposer()
    {
        return $this->container->get('media_exposer');
    }
}
