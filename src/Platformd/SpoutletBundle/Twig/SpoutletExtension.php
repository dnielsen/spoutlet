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
use Symfony\Component\Translation\TranslatorInterface;
use Platformd\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Session;

/**
 * Twig extension for generic things
 */
class SpoutletExtension extends Twig_Extension
{
    private $container;
    private $translator;
    private $session;

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
            'absolute_url' => new Twig_Filter_Method($this, 'getAbsoluteUrl'),
            'wrap' => new Twig_Filter_Method($this, 'wrap'),
            'add_links' => new Twig_Filter_Method($this, 'addLinks'),
            'add_ordinal_suffix' => new Twig_Filter_Method($this, 'addOrdinalSuffix'),
        );
    }

    public function getTests()
    {
        return array(
            'external' => new Twig_Test_Method($this, 'testExternal'),
        );
    }

    private function trans($key) {

        if (!$this->translator) {
            $this->translator = $this->container->get('translator');
        }

        if (!$this->session) {
            $this->session = $this->container->get('session');
        }

        if (!$this->translator || !$this->session) {
            return $key;
        }

        return $this->translator->trans($key, array(), 'messages', $this->session->getLocale());
    }

    public function wrap($obj, $length = 75, $breakWith = '<br />', $cut = true) {
        return wordwrap($obj, $length, $breakWith, $cut);
    }

    public function addLinks($string)
    {
        return preg_replace("/(http:\/\/[^\s]+)/", "<a href=\"$1\">$1</a>", $string);
    }

    public function getFunctions()
    {
        return array(
            'get_current_site'                  => new Twig_Function_Method(
                $this,
                'getCurrentSite'
                ),
            'can_user_apply_to_giveaway'  => new Twig_Function_Method(

                $this,
                'canUserApplyToGiveaway'
            ),
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
            'ends_with'                => new Twig_Function_Method(
                $this,
                'endsWith'
            ),
            'media_path_nice'           => new Twig_Function_Method(
                $this,
                'mediaPathNice'
            ),
            'cevo_account_link'         => new Twig_Function_Method(
                $this,
                'cevoAccountLink'
            ),

            'change_link_subdomain'     => new Twig_Function_Method(
                $this,
                'changeLinkSubdomain'
            ),
        );
    }

    public function getCurrentSite() {

        $currentHost    = $this->getRequest()->getHost();
        $subDomain      = str_replace('staging', '', substr($currentHost, 0, stripos($currentHost, '.')));

        return $this->getSiteFromSubDomain($subDomain);
    }

    private function getRequest() {
        return $this->container->get('request');
    }

    private function getEntityManager() {
        return $this->container->get('doctrine.orm.entity_manager');
    }

    private function getSiteFromSubDomain($subDomain) {
        return $this->getEntityManager()->getRepository('SpoutletBundle:Site')->findOneBySubDomain($subDomain);
    }

    private function getSiteFromDefaultLocale($locale) {
        return $this->getEntityManager()->getRepository('SpoutletBundle:Site')->findOneByDefaultLocale($locale);
    }

    public function mediaPathNice($media) {

        $bucketName = $this->container->getParameter('s3_bucket_name');

        if ($bucketName == "platformd") {
            $cf = "http://media.alienwarearena.com";
        } else {
            $cf = "http://mediastaging.alienwarearena.com";
        }

        return sprintf('%s/media/%s', $cf, $media->getFilename());
    }

    public function cevoAccountLink($username)
    {
        $userManager    = $this->container->get('fos_user.user_manager');
        $user           = $userManager->loadUserByUsername($username);
        $cevoUserId     = $user->getCevoUserId();
        $locale         = $this->container->get('session')->getLocale();

        switch ($locale) {
            case 'ja':
                $subdomain = '/japan';
                break;

            case 'zh':
                $subdomain = '/china';
                break;

            case 'es':
                $subdomain = '/latam';
                break;

            default:
                $subdomain = '';
                break;
        }

        if ($cevoUserId && $cevoUserId > 0) {
            return sprintf('http://www.alienwarearena.com%s/member/%d', $subdomain , $cevoUserId);
        }

        return 'http://www.alienwarearena.com/account/profile';
    }

    public function endsWith($haystack, $needle) {

        $length = strlen($needle);

        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    public function changeLinkSubdomain($link, $subdomain)
    {
        if(parse_url($link, PHP_URL_SCHEME) == '') {
            return $link;
        }

        $parsedUrl = parse_url($link);

        $parts = explode('.', $parsedUrl['host']);

        if (strpos($parts[0], 'staging')) {
            $parts[0] = $subdomain.'staging';
        } else {
            $parts[0] = $subdomain;
        }

        $host = implode('.', $parts);

        $query = array_key_exists('query', $parsedUrl) != "" ? "?".$parsedUrl['query'] : '';
        $anchor = array_key_exists('fragment', $parsedUrl) != "" ? "#".$parsedUrl['fragment'] : '';

        $url = $parsedUrl['scheme'].'://'.$host.$parsedUrl['path'].$query.$anchor;

        return $url;
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
     * @return string
     */
    public function addOrdinalSuffix($num) {
        if (!in_array(($num % 100),array(11,12,13))){
          switch ($num % 10) {
            case 1:  return $num.'st';
            case 2:  return $num.'nd';
            case 3:  return $num.'rd';
          }
        }
        return $num.'th';
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
    public function canUserApplyToGiveaway(Giveaway $giveaway)
    {
        if (!$user = $this->getCurrentUser()) {
            return false;
        }

        return $this->getGiveawayManager()->canUserApplyToGiveaway($user, $giveaway);
    }

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
            case 'ALIENWARE_LINK':                  return $this->GetAlienwareLink($locale);
            case 'ALIENWARE_LINK_BOTTOM_RIGHT':     return $this->GetAlienwareBottomRightLink($locale);
            case 'ALIENWARE_LINK_ADDRESS':          return $this->GetAlienwareLinkAddress($locale);
            case 'FACEBOOK':                        return $this->GetFacebookLink($locale);
            case 'EVENTS':                          return $this->GetEventsLink($locale);
            case 'VIDEOS':                          return $this->GetVideosLink($locale);
            case 'WALLPAPERS':                      return $this->GetWallpapersLink($locale);
            case 'TWITTER':                         return $this->GetTwitterLink($locale);
            case 'USER_EVENT':                      return $this->GetUserEventLink($locale);
            case 'USER_GAME_ID':                    return $this->GetUserGameIdLink($locale);
            case 'USER_PROFILE':                    return $this->GetUserProfileLink($locale);
            case 'USER_GIVEAWAY':                   return $this->GetUserGiveawayLink($locale);
            case 'SOCIAL_MEDIA_STRIP':              return $this->GetSocialMediaStripForHeaderAndFooter($locale);
            case 'PHOTOS':                          return $this->GetPhotosLink($locale);
            case 'CONTESTS':                        return $this->GetContestsLink($locale);

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

    private function GetAlienwareBottomRightLink($locale) {

        $link = $this->GetAlienwareLinkAddress($locale);

        if (!$link) {
            return false;
        }

        switch($locale) {
            case 'ja':       $text = 'ALIENWARE.JPへ移動する'; break;
            case 'zh':       $text = '需要一个牛逼的装备? 请看看 ALIENWARE'; break;
            case 'es':       $text = '¿Busca un equipo poderoso?<br />¡Encuéntrelo en Alienware!'; break;

            case 'en_SG':
            case 'en_AU':
            case 'en_GB':
            case 'en_IN':
            case 'en_US':
            case 'en':

                $text = 'Need a kickass rig? Check out Alienware';
                break;

            default:

                return false;
        }

        return sprintf('<a href="%s" target="_blank">%s</a>', $link, $text);
    }

    private function GetAlienwareLink($locale) {

        $link = $this->GetAlienwareLinkAddress($locale);

        if (!$link) {
            return false;
        }

        return sprintf('<a href="%s" target="_blank">'.$this->trans('platformd.alienware').'</a>', $link);
    }

    private function GetAlienwareLinkAddress($locale) {

        switch($locale) {
            case 'ja':      return 'http://alienware.jp/';
            case 'zh':      return 'http://alienware.com.cn/';
            case 'es':      return 'http://www.alienware.com/mx/';
            case 'en_SG':   return 'http://allpowerful.com/asia';
            case 'en_AU':   return 'http://www.alienware.com.au/';
            case 'en_GB':   return 'http://www1.euro.dell.com/content/topics/segtopic.aspx/alienware?c=uk&cs=ukdhs1&l=en&s=dhs&~ck=mn';
            case 'en_IN':   return 'http://www.alienware.co.in/';

            case 'en_US':
            case 'en':

                return 'http://www.alienware.com/';

            default:

                return false;
        }
    }

    private function GetSocialMediaStripForHeaderAndFooter($locale) {

        switch($locale) {
            case 'zh':      return $this->GetSocialMediaStripForHeaderAndFooterChina();
            default:        return false;
        }
    }

    private function GetSocialMediaStripForHeaderAndFooterChina() {

        $output  = ' <a href="http://www.dell.com.cn/aw" target="_blank"><img src="/bundles/spoutlet/images/china-social-media/1_btn_jkgm_hover.png" style="height: 16px;" /></a> ';
        $output .= '<a href="http://e.weibo.com/alienwareallpowerful" target="_blank"><img src="/bundles/spoutlet/images/china-social-media/2_ico_sina.png" style="height: 16px;" /></a> ';
        $output .= '<a href="http://t.qq.com/alienware" target="_blank"><img src="/bundles/spoutlet/images/china-social-media/3_ico_tencent.png" style="height: 16px;" /></a> ';
        $output .= '<a href="http://bbs.alienfans.net/" target="_blank"><img src="/bundles/spoutlet/images/china-social-media/4_ico_aw.png" style="height: 16px;" /></a> ';

        return $output;
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
        $format = '<a href="http://www.alienwarearena.com%s/account/events/">'.$this->trans('platformd.user.account.my_events').'</a>';

        switch($locale) {
            case 'ja':      return sprintf($format, '/japan');
            case 'zh':      return sprintf($format, '/china');
            case 'en_US':   return sprintf($format, '');
            case 'en_SG':   return sprintf($format, '/sg');

            default:        return false;
        }
    }

    private function GetCevoCountryLookup($locale, $blankForNorthAmerica = true) {

        switch($locale) {
            case 'ja':      return 'japan';
            case 'zh':      return 'china';
            case 'es':      return 'latam';
            case 'en_SG':   return 'sg';
            case 'en_AU':   return 'anz';
            case 'en_GB':   return 'europe';
            case 'en_IN':   return 'in';
            case 'en_US':   return $blankForNorthAmerica ? '' : 'na';
        }

        return '';
    }

    private function GetUserProfileLink($locale) {

        $format         = '<a href="%s">'.$this->trans('platformd.user.account.profile').'</a>';
        $internalUrl    = $this->container->get('router')->generate('accounts_profile');
        $externalUrl    = 'http://www.alienwarearena.com/';
        $cevoCountry    = $this->GetCevoCountryLookup($locale);

        if ($cevoCountry) {
            $externalUrl .= $cevoCountry.'/';
        }

        $externalUrl .= 'member/'.$this->getCurrentUser()->getCevoUserId().'/';

        switch($locale) {

            case 'ja':      return sprintf($format, $internalUrl);
            case 'zh':      return sprintf($format, $internalUrl);

            default:        return sprintf($format, $externalUrl);
        }
    }

    private function GetWallpapersLink($locale) {

        $format         = '<a href="%s">'.$this->trans('platformd.layout.main_menu.wallpapers').'</a>';
        $url            = $this->container->get('router')->generate('wallpapers');

        switch($locale) {

            default:

                return sprintf($format, $url);
        }
    }

    private function GetVideosLink($locale) {

        $format         = '<a href="%s">'.$this->trans('platformd.layout.main_menu.video').'</a>';

        switch($locale) {

            case 'ja':
            case 'zh':

                return sprintf($format, '/video');

            default:

                return sprintf($format, 'http://video.alienwarearena.com/');
        }
    }

    private function GetEventsLink($locale) {

        $format         = '<a href="%s">'.$this->trans('platformd.layout.main_menu.events').'</a>';
        $internalUrl    = $this->container->get('router')->generate('events_index');
        $externalUrl    = 'http://www.alienwarearena.com/';
        $cevoCountry    = $this->GetCevoCountryLookup($locale);

        if ($cevoCountry) {
            $externalUrl .= $cevoCountry.'/';
        }

        $externalUrl .= 'event/';

        switch($locale) {

            case 'ja':      return sprintf($format, $internalUrl);
            case 'zh':      return sprintf($format, $internalUrl);

            case 'es':      return sprintf($format, $externalUrl);
            case 'en_SG':
            case 'en_AU':
            case 'en_GB':
            case 'en_IN':
            case 'en_US':
            case 'en':

                return sprintf($format, $externalUrl);

            default:        return false;
        }
    }

    private function GetUserGiveawayLink($locale) {

        $format         = '<a href="%s">'.$this->trans('platformd.user.account.my_giveaways').'</a>';
        $internalUrl    = $this->container->get('router')->generate('accounts_giveaways');
        $externalUrl    = 'http://www.alienwarearena.com/';
        $cevoCountry    = $this->GetCevoCountryLookup($locale);

        if ($cevoCountry) {
            $externalUrl .= $cevoCountry.'/';
        }

        $externalUrl .= 'account/my-giveaway-keys/';

        switch($locale) {

            case 'ja':      return sprintf($format, $internalUrl);
            case 'zh':      return sprintf($format, $internalUrl);
            case 'en_SG':

                return '<li class="more">
                    <a class="blue" style="background: url(\'/bundles/spoutlet/images/nav-arrow-1.png\') right center no-repeat; padding-right: 15px; margin-right: 5px; cursor: pointer;">Giveaways</a>
                    <ul style="padding: 3px; position: absolute; background: #393939; width: 50px;">
                        <li><a href="http://www.alienwarearena.com/sg/account/my-giveaway-keys/">Giveaway Keys</a></li>
                        <li><a href="'.$this->container->get('router')->generate('accounts_giveaways').'">System Tag Keys</a></li>
                    </ul>
                </li>';

            case 'es':
            case 'en_AU':
            case 'en_GB':
            case 'en_IN':
            case 'en_US':
            case 'en':

                return sprintf($format, $externalUrl);

            default:        return false;
        }
    }

    private function GetGroupsLink($locale)
    {
        $format         = '<a href="%s">'.$this->trans('platformd.layout.main_menu.groups').'</a>';
        $url            = $this->container->get('router')->generate('gallery_index');

        switch($locale) {

            default:

                return sprintf($format, $url);
        }
    }

    private function GetPhotosLink($locale)
    {
        $format         = '<a href="%s"><span style="color: #ff5711;padding-right: 2px;">'.$this->trans('deals_new').'</span>'.$this->trans('platformd.layout.main_menu.photos').'</a>';
        $url            = $this->container->get('router')->generate('gallery_index');

        switch($locale) {

            default:

                return sprintf($format, $url);
        }
    }

    private function GetContestsLink($locale)
    {
        $format         = '<a href="%s"><span style="color: #ff5711;padding-right: 2px;">'.$this->trans('deals_new').'</span>'.$this->trans('platformd.layout.main_menu.contests').'</a>';
        $url            = $this->container->get('router')->generate('contest_index');

        switch($locale) {

            default:

                return sprintf($format, $url);
        }
    }

    private function GetUserGameIdLink($locale) {
        $format = '<a href="http://www.alienwarearena.com/%s/account/ids/">'.$this->trans('platformd.user.account.game_ids').'</a>';

        switch($locale) {
            case 'ja':      return sprintf($format, 'japan');
            case 'zh':      return sprintf($format, 'china');

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
        $northAmerica = in_array($locale, array('en_US', 'en'));
        $northAmericaOrEurope = in_array($locale, array('en_US', 'en_GB', 'en'));
        $demoOnly = in_array($locale, array('en'));
        $northAmericaEuropeAnzOnly = in_array($locale, array('en_US', 'en_GB', 'en_AU', 'en'));
        $china = in_array($locale, array('zh'));
        $northAmericaEuropeLatamOnly = in_array($locale, array('en_US', 'en_GB', 'es', 'en'));

        switch ($feature) {
            case 'EXTRA_NAVIGATION':            return !$chinaOrJapan;
            case 'VIDEO':                       return true;
            case 'STEAM_XFIRE_COMMUNITIES':     return !$chinaOrJapan;
            case 'SWEEPSTAKES':                 return false;
            case 'FORUMS':                      return !$chinaOrJapan;
            case 'ARP':                         return !$chinaOrJapan;
            case 'NEWS':                        return $chinaOrJapan;
            case 'DEALS':                       return $northAmericaOrEurope;
            case 'GAMES':                       return !$chinaOrJapan;
            case 'GAMES_NAV_DROP_DOWN':         return !$chinaOrJapan;
            case 'MESSAGES':                    return !$chinaOrJapan;
            case 'GROUPS':                      return $northAmericaOrEurope;
            case 'WALLPAPERS':                  return !$japan;
            case 'MICROSOFT':                   return !$japan;
            case 'PHOTOS':                      return $northAmericaEuropeLatamOnly;
            case 'CONTESTS':                    return $northAmericaEuropeLatamOnly;
            case 'COMMENTS':                    return $northAmericaEuropeLatamOnly;
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
