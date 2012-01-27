<?php

namespace Platformd\CEVOBundle;

use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class is a central spot for doing things related to CEVO's authentication
 */
class CEVOAuthManager
{
    const REGISTER_PATH = '/account/register';
    const LOGIN_PATH    = '/account/login';
    const LOGOUT_PATH   = '/cmd/account/logout';

    /**
     * Map of how different locales should be prefixed when sent to CEVO
     *
     * @var array
     */
    static private $localePathMap = array(
        'zh' => '/china',
        'ja' => '/japan',
        'en' => false,
    );

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * The base URL to the CEVO site - http://alienwarearena.com
     *
     * @var string
     */
    private $cevoSiteUrl;

    public function __construct($cevoSiteUrl, ContainerInterface $container)
    {
        // strip the trailing slash if there is one
        $this->cevoSiteUrl = rtrim($cevoSiteUrl, '/');

        $this->container = $container;
    }

    /**
     * Generates a URL to the main CEVO site
     *
     * This is a locale-aware action, so if needed, something like /japan
     * or /china will be prepended to the path
     *
     * @param string $path The path (e.g. /account/register)
     * @param string $returnUrl Optional return url = used for ?return=
     * @param bool $withPrefix whether to add the language prefix or not
     * @return mixed
     */
    public function generateCevoUrl($path, $returnUrl = null, $withPrefix = true)
    {
        $prefix = $withPrefix ? $this->getLocalePrefix() : '';

        if ($returnUrl && strpos($returnUrl, 'http') !== 0) {
            $returnUrl = $this->getRequest()->getUriForPath($returnUrl);
        }

        $url = sprintf('%s%s%s', $this->getCevoBaseUrl(), $prefix, $path);

        if ($returnUrl) {
            $url .= '?return='.urlencode($returnUrl);
        }

        return $url;
    }

    /**
     * Returns the string that needs to be prefixed to any URL generated to CEVO
     * based on the locale
     *
     * @return string
     */
    private function getLocalePrefix()
    {
        $locale = $this->getSession()->getLocale();

        return isset(self::$localePathMap[$locale]) ? self::$localePathMap[$locale] : null;
    }

    /**
     * Returns the CEVO site URL, normalized
     *
     * @return string
     */
    private function getCevoBaseUrl()
    {
        // allows us to not specificy a host, and it default to the current host
        if (strpos($this->cevoSiteUrl, 'http://') !== 0) {
            $request = $this->getRequest();

            $this->cevoSiteUrl = $request->getScheme().'://'.$request->getHttpHost().$request->getBaseUrl().$this->cevoSiteUrl;
        }

        return $this->cevoSiteUrl;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Session
     */
    public function getSession()
    {
        return $this->container->get('session');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->container->get('request');
    }
}