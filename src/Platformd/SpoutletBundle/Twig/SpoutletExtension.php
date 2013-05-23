<?php

namespace Platformd\SpoutletBundle\Twig;

use Twig_Extension;
use Twig_Filter_Method;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig_Test_Method;
use Platformd\SpoutletBundle\Util\HttpUtil;

use Twig_Function_Method;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Symfony\Component\Translation\TranslatorInterface;
use Platformd\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Platformd\SpoutletBundle\Entity\Site;

/**
 * Twig extension for generic things
 */
class SpoutletExtension extends Twig_Extension
{
    private $bucketName;
    private $currentSiteFeatures = NULL;
    private $currentSite = NULL;
    private $currentUser = NULL;
    private $giveawayManager;
    private $linkableManager;
    private $request = NULL;
    private $router;
    private $session = NULL;
    private $translator;
    private $userManager;
    private $contentReportRepo;
    private $backgroundAdRepo;
    private $localAuth;

    public function __construct($bucketName, $giveawayManager, $linkableManager, $mediaExposer, $router, $securityContext, $siteUtil, $translator, $userManager, $contentReportRepo, $backgroundAdRepo, $localAuth)
    {
        $this->bucketName          = $bucketName;
        $this->giveawayManager     = $giveawayManager;
        $this->linkableManager     = $linkableManager;
        $this->mediaExposer        = $mediaExposer;
        $this->router              = $router;
        $this->securityContext     = $securityContext;
        $this->siteUtil            = $siteUtil;
        $this->translator          = $translator;
        $this->userManager         = $userManager;
        $this->contentReportRepo   = $contentReportRepo;
        $this->backgroundAdRepo    = $backgroundAdRepo;
        $this->localAuth           = $localAuth;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->request  = $event->getRequest();
        $exception      = $this->request->get('exception');
        $isException    = $exception ? in_array($exception->getStatusCode(), array(403, 404), true) : false;

        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType() && !$isException) {
            return;
        }

        $token                     = $this->securityContext->getToken();
        $this->currentUser         = $token ? $token->getUser() : null;
        $this->session             = $this->request->getSession();
        $this->currentSite         = $this->siteUtil->getCurrentSite();
        $this->currentSiteFeatures = $this->currentSite->getSiteFeatures();
        $this->currentSiteConfig   = $this->currentSite->getSiteConfig();
    }

    public function getFilters()
    {
        return array(
            'absolute_url'       => new Twig_Filter_Method($this, 'getAbsoluteUrl'),
            'add_links'          => new Twig_Filter_Method($this, 'addLinks'),
            'add_ordinal_suffix' => new Twig_Filter_Method($this, 'addOrdinalSuffix'),
            'pd_link_full'       => new Twig_Filter_Method($this, 'linkToObjectFull',   array('is_safe' => array('html'))),
            'pd_link'            => new Twig_Filter_Method($this, 'linkToObject'),
            'pd_link_target'     => new Twig_Filter_Method($this, 'linkToObjectTarget', array('is_safe' => array('html'))),
            'wrap'               => new Twig_Filter_Method($this, 'wrap'),
            'date_translate'     => new Twig_Filter_Method($this, 'dateTranslate'),
            'pd_trans'           => new Twig_Filter_Method($this, 'themedTranslate'),
        );
    }

    public function getFunctions()
    {
        return array(
            'cevo_account_link'              => new Twig_Function_Method($this, 'cevoAccountLink'),
            'cevo_account_giveaway_link'     => new Twig_Function_Method($this, 'cevoAccountGiveawayPageLink'),
            'current_user_cevo_account_link' => new Twig_Function_Method($this, 'currentUserCevoAccountLink'),
            'can_user_apply_to_giveaway'   => new Twig_Function_Method($this, 'canUserApplyToGiveaway'),
            'account_link'                 => new Twig_Function_Method($this, 'accountLink'),
            'change_link_domain'           => new Twig_Function_Method($this, 'changeLinkDomain'),
            'ends_with'                    => new Twig_Function_Method($this, 'endsWith'),
            'get_avatar_url'               => new Twig_Function_Method($this, 'getAvatarUrl'),
            'has_user_applied_to_giveaway' => new Twig_Function_Method($this, 'hasUserAppliedToGiveaway'),
            'is_admin_page'                => new Twig_Function_Method($this, 'isAdminPage'),
            'media_path_nice'              => new Twig_Function_Method($this, 'mediaPathNice'),
            'site_link'                    => new Twig_Function_Method($this, 'siteLink', array('is_safe' => array('html'))),
            'target_blank'                 => new Twig_Function_Method($this, 'getTargetBlank', array('is_safe' => array('html'))),
            'can_user_report'              => new Twig_Function_Method($this, 'canReport'),
            'login_link'                   => new Twig_Function_Method($this, 'getLoginUrl'),
            'account_home_link'            => new Twig_Function_Method($this, 'getAccountHomeUrl'),
            'current_background_ad_url'      => new Twig_Function_Method($this, 'getCurrentBackgroundUrl'),
            'current_background_ad_link'     => new Twig_Function_Method($this, 'getCurrentBackgroundLink'),
        );
    }

    public function getTests()
    {
        return array(
            'external' => new Twig_Test_Method($this, 'testExternal'),
        );
    }

    public function getGlobals()
    {
        return array(
            'site'      => $this->currentSite,
            'features'  => $this->currentSiteFeatures,
            'config'    => $this->currentSiteConfig,
            'user'      => $this->currentUser,
            'auth_type' => $this->localAuth ? 'local' : 'remote',
        );
    }

    private function trans($key) {

        return $this->themedTranslate($key);
    }

    public function wrap($obj, $length = 75, $breakWith = '<br />', $cut = true) {
        return wordwrap($obj, $length, $breakWith, $cut);
    }

    public function addLinks($string)
    {
        return preg_replace("/(http:\/\/[^\s]+)/", "<a href=\"$1\">$1</a>", $string);
    }

    public function getCurrentBackgroundLink($link = null, $timezone = 'UTC')
    {
        $format = 'data-background-link=%s';

        if (!empty($link)) {
            return sprintf($format, $link);
        }

        $adSite = $this->getCurrentBackgroundAdSite($this->currentSite, $timezone);

        if ($adSite && $adSite->getUrl()) {
            return sprintf($format, $adSite->getUrl());
        }
    }

    public function getCurrentBackgroundUrl($url = null, $timezone = 'UTC')
    {
        if ($url === "default") {
            return;
        }

        if (empty($url)) {
            if ($adSite = $this->getCurrentBackgroundAdSite($this->currentSite, $timezone)) {
                if ($file =  $adSite->getAd()->getFile()) {
                    $url = $this->mediaExposer->getPath($file);
                }
            }
        }

        if (!$url) {
            return;
        }

        return sprintf('style="background-image: url(\'%s\');"', $url);
    }

    private function getCurrentBackgroundAdSite(Site $site = null, $timezone = 'UTC')
    {
        return $this->backgroundAdRepo->getCurrentBackgroundAdSite($site, $timezone);
    }

    public function mediaPathNice($media) {

        if ($this->bucketName == "platformd") {
            $cf = "http://media.alienwarearena.com";
        } else {
            $cf = "http://mediastaging.alienwarearena.com";
        }

        return sprintf('%s/media/%s', $cf, $media->getFilename());
    }

    public function currentUserCevoAccountLink() {

        if (!$this->currentUser || !$this->currentUser->hasRole('ROLE_USER')) {
            return null;
        }

        return $this->cevoAccountLinkFromCevoUserId($this->currentUser->getCevoUserId());
    }

    public function cevoAccountLink($user)
    {
        $isCurrentUser = $user instanceof User && $user->getId() == $this->currentUser->getId();

        if ($isCurrentUser) {
            return $this->currentUserCevoAccountLink();
        }

        $user = $user instanceof User ? $user : $this->userManager->loadUserByUsername($user);

        return $this->cevoAccountLinkFromCevoUserId($user->getCevoUserId());
    }

    public function cevoAccountGiveawayPageLink($site = null) {
        $site = $site ?: $this->currentSite;
        $baseUrl = $this->cevoBaseUrlGivenSite($site);

        return $baseUrl.'/account/my-giveaway-keys';
    }

    private function cevoBaseUrlGivenSite($site) {

        $base   = 'http://www.alienwarearena.com';

        switch ($site->getDefaultLocale()) {
            case 'ja': return $base.'/japan';
            case 'zh': return $base.'/china';
            case 'es': return $base.'/latam';
            default:   return $base;
        }
    }

    private function cevoAccountLinkFromCevoUserId($cevoUserId) {
        $locale         = $this->session->getLocale();

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

    public function accountLink($username)
    {
        $user           = $this->userManager->loadUserByUsername($username);

        if (!$this->localAuth) {
            $cevoUserId     = $user->getCevoUserId();
            $locale         = $this->session->getLocale();

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
        } else {
            return $this->router->generate('accounts_profile', array(
                'username' => $user ? $username : null,
            ));
        }
    }

    public function endsWith($haystack, $needle) {

        $length = strlen($needle);

        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    public function changeLinkDomain($link, $fullDomain)
    {
        if(parse_url($link, PHP_URL_SCHEME) == '') {
            return $link;
        }

        $parsedUrl = parse_url($link);
        $query     = isset($parsedUrl['query']) ? '?'.$parsedUrl['query'] : '';
        $anchor    = isset($parsedUrl['fragment']) ? '#'.$parsedUrl['fragment'] : '';
        $url       = $parsedUrl['scheme'].'://'.$fullDomain.$parsedUrl['path'].$query.$anchor;

        return $url;
    }

    /**
     * @return string
     */

    public function getAbsoluteUrl($obj)
    {
        # look at the url that is being passed in and if it is relative, return base path + url... if it is not, then return obj

        $base = $this->request->getScheme() . '://' . $this->request->getHost();
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

        return $this->linkableManager->link($obj);
    }

    /**
     * @param $obj
     * @return string
     */
    public function linkToObjectFull($obj, $urlText = null, $classes=null)
    {
        $this->ensureLinkable($obj);

        $url        = $this->linkableManager->link($obj);
        $target     = $this->linkToObjectTarget($obj);
        $urlText    = $urlText ?: $url;
        $classes    = is_array($classes) ? 'class=' . implode(' ', $classes) : '';

        if (strlen($target) > 0) {
            $target = ' '.$target;
        }

        return sprintf('<a href="%s"%s %s>%s</a>', $url, $target, $classes, $urlText);
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

        $currentHost = $this->request->getHost();

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
     * @param \Platformd\GiveawayBundle\Entity\Giveaway $giveaway
     * @return bool
     */
    public function canUserApplyToGiveaway(Giveaway $giveaway)
    {
        if (!$user = $this->currentUser) {
            return false;
        }

        return $this->giveawayManager->canUserApplyToGiveaway($user, $giveaway);
    }

    public function hasUserAppliedToGiveaway(Giveaway $giveaway)
    {
        if (!$user = $this->currentUser) {
            return false;
        }

        return $this->giveawayManager->hasUserAppliedToGiveaway($user, $giveaway);
    }

    /**
     * Determines if we're on admin page, simply by looking for a "/admin*" URL
     *
     * @return bool
     */
    public function isAdminPage()
    {
        return (strpos($this->request->getPathInfo(), '/admin') === 0);
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

        $locale = $this->session->getLocale();

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
            case 'CONTESTS_IMAGE':                   return $this->GetContestsLink($locale, 'image');
            case 'CONTESTS_GROUP':                   return $this->GetContestsLink($locale, 'group');
            case 'GIVEAWAYS':                       return $this->GetGiveawaysLink($locale);

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
    public function getAvatarUrl(User $user)
    {
        if ($user->getCevoAvatarUrl()) {
            return $user->getCevoAvatarUrl();
        }

        if ($user->getAvatar() && $user->isAvatarApproved()) {
            return $this->mediaExposer->getPath($user);
        }

        return false;
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

        $format         = '<a href="http://www.alienwarearena.com%s/account/events/">'.$this->trans('platformd.layout.page_content.competitions').'</a>';

        switch($locale) {
            case 'zh':      return sprintf($format, '/china');
            case 'en_US':   return sprintf($format, '');
            case 'en_SG':   return sprintf($format, '/sg');
            case 'es':      return sprintf($format, '/latam');
            case 'en_GB':   return sprintf($format, '');
            case 'en_AU':   return sprintf($format, '/anz');
            case 'en_IN':   return sprintf($format, '/in');

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
        $internalUrl    = $this->router->generate('accounts_profile');
        $externalUrl    = 'http://www.alienwarearena.com/';
        $cevoCountry    = $this->GetCevoCountryLookup($locale);

        if ($cevoCountry) {
            $externalUrl .= $cevoCountry.'/';
        }

        $externalUrl .= 'member/'.$this->currentUser->getCevoUserId().'/';

        switch($locale) {

            //case 'ja':      return sprintf($format, $internalUrl);
            //case 'zh':      return sprintf($format, $internalUrl);

            default:        return sprintf($format, $externalUrl);
        }
    }

    private function GetWallpapersLink($locale) {

        $format         = '<a href="%s">'.$this->trans('platformd.layout.main_menu.wallpapers').'</a>';
        $url            = $this->router->generate('wallpapers');

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

        $internalUrl    = $this->router->generate('global_events_index');
        $externalUrl    = 'http://www.alienwarearena.com/';
        $cevoCountry    = '';//$this->GetCevoCountryLookup($locale);

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

        $format         = '<li><a href="%s">'.$this->trans('platformd.user.account.my_giveaways').'</a></li>';
        $internalUrl    = $this->router->generate('accounts_giveaways');
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
            case 'es':
            case 'en_AU':
            case 'en_GB':
            case 'en_IN':
            case 'en_US':
            case 'en':

                return sprintf($format, $internalUrl);

            default:        return false;
        }
    }

    private function GetGroupsLink($locale)
    {
        $format         = '<a href="%s">'.$this->trans('platformd.layout.main_menu.groups').'</a>';
        $url            = $this->router->generate('gallery_index');

        switch($locale) {

            default:

                return sprintf($format, $url);
        }
    }

    private function GetPhotosLink($locale)
    {
        $format         = '<a href="%s"><span style="color: #ff5711;padding-right: 2px;">'.$this->trans('deals_new').'</span>'.$this->trans('platformd.layout.main_menu.photos').'</a>';
        $url            = $this->router->generate('gallery_index');

        switch($locale) {

            default:

                return sprintf($format, $url);
        }
    }

    private function GetContestsLink($locale, $category=null)
    {
        $format         = '<a href="%s"><span style="color: #ff5711;padding-right: 2px;">'.$this->trans('deals_new').'</span>'.$this->trans('platformd.layout.main_menu.contests').'</a>';
        $url            = $category == null ? $this->router->generate('contest_index') : $this->router->generate('contest_index', array('category' => $category));

        switch($locale) {

            default:

                return sprintf($format, $url);
        }
    }

    private function GetUserGameIdLink($locale) {
        $format = '<a href="http://www.alienwarearena.com%s/account/ids/">'.$this->trans('platformd.user.account.game_ids').'</a>';

        switch($locale) {
            case 'ja':      return sprintf($format, '/japan');
            case 'zh':      return sprintf($format, '/china');
            case 'en_SG':   return sprintf($format, '');
            case 'en_US':   return sprintf($format, '');
            case 'es':      return sprintf($format, '');
            case 'en_GB':   return sprintf($format, '');
            case 'en_AU':   return sprintf($format, '');
            case 'en_IN':   return sprintf($format, '');

            default:        return false;
        }
    }


    private function GetGiveawaysLink($locale) {

        $format         = '<a href="%s">'.$this->trans('platformd.layout.page_content.giveaways').'</a>';
        $internalUrl    = $this->router->generate('giveaway_index');
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
            case 'en':      return sprintf($format, $internalUrl);

                return sprintf($format, $externalUrl);

            default:        return false;
        }
    }

    public function canReport()
    {
        return !$this->contentReportRepo->hasUserReportedRecently($this->currentUser);

    }

    public function dateTranslate($datetime)
    {
        return $datetime->format($this->themedTranslate('date_format', array(), $this->session->getLocale()));
    }

    public function themedTranslate($transKey, $variables = array(), $domain = 'messages', $locale = null)
    {
        return $this->translator->trans($transKey, $variables, $domain, $locale);
    }

    public function getLoginUrl($returnUrl) {

        $prefix     = $this->localAuth ? $this->router->generate('fos_user_security_login') : 'http://alienwarearena.com/account/login';
        $return     = $returnUrl ? '?return='.urlencode($returnUrl) : '';

        return $prefix.$return;
    }

    public function getAccountHomeUrl() {
        return $this->localAuth ? $this->router->generate('accounts_index') : 'http://alienwarearena.com/account/';
    }
}
