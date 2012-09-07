<?php

namespace Platformd\SpoutletBundle\Tenant;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Platformd\CEVOBundle\CEVOAuthManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Responsible for preventing anyone from a non-implemented site from viewing
 * any pages they shouldn't.
 *
 * For example, for the Latin American site, at this time, only /games/*
 * pages are allowed to be on this site. So, this listener will catch any
 * URLs going elsewhere and make sure they are redirected back to AlienwareArena.com
 */
class SiteProtectionListener
{
    private $allowedSites;

    private $cevoAuthManager;

    private $allowCevoForwarding;

    /**
     * An array of allowed URL regular expressions
     *
     * @var array
     */
    static private $allowedPatterns = array(
        '^/games',
        '^/age',
        '^/admin',
        '^/deal',
        // our little "fake" CEVO - without this, we'll get a redirect loop locally
        '^/cevo/api/stub',
        '^/media',
        '^/login',
        '^/account',
        // we have to "ok" comments, since they're used on some features for everyone
        '^/comments',
        '^/groups',
        '^/wallpapers'
    );

    static private $urlMap = array(
        '/login'    => '/account/login/',
        '/about'    => '/pages/about',
        '/contact'  => '/pages/contact',
        '/events'   => '/event/',
    );

    /**
     * @param array $allowedSites
     */
    public function __construct(array $allowedSites, CEVOAuthManager $cevoAuthManager, $allowCevoForwarding)
    {
        $this->allowedSites = $allowedSites;
        $this->cevoAuthManager = $cevoAuthManager;
        $this->allowCevoForwarding = $allowCevoForwarding;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $session = $request->getSession();
        $locale = $session->getLocale();

        $matcher = new RequestMatcher();

        $matcher->matchPath('^/account/profile');

        if ($matcher->matches($request) && ($locale == 'ja' || $locale == 'zh')) {
            $this->forwardToCevo($event, $request);
            return;
        }

        // if we're on of the "allowed" sites, then we're totally good
        if (in_array($locale, $this->allowedSites)) {
            return;
        }

        foreach (self::$allowedPatterns as $allowedPattern) {
            $matcher->matchPath($allowedPattern);

            // if we match, then we're definitely good
            if ($matcher->matches($request)) {
                return;
            }
        }

        $this->forwardToCevo($event, $request);
    }

    private function forwardToCevo(GetResponseEvent $event, $request) {
        // at this point, we don't match, so we need to redirect back to CEVO
        $url = $this->translateToCEVOUrl($request->getPathInfo());

        if ($this->allowCevoForwarding == false) {
            throw new NotFoundHttpException(sprintf('CEVO forwarding is currently off and there is no access to this URL on this site. If CEVO forwarding was turned on, we would redirect to the main CEVO site at "<a href="http://www.alienwarearena.com%s">http://www.alienwarearena.com%s</a>"', $url, $url));
        }

        $absoluteUrl = $this->cevoAuthManager->generateCevoUrl(
            $url,
            null,
            false
        );

        $response = new RedirectResponse($absoluteUrl);
        $event->setResponse($response);
    }

    /**
     * Attempts to take one of our URLs and translate it to CEVO
     *
     * e.g. /login for us might be /account/login
     *
     * @param string $url
     * @return string
     */
    private function translateToCEVOUrl($url)
    {
        if (isset(self::$urlMap[$url])) {
            return self::$urlMap[$url];
        }

        return $url;
    }
}
