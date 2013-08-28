<?php

namespace Platformd\SpoutletBundle\Model;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Session;

class Translator implements TranslatorInterface
{
    private $theme;
    private $translator;
    private $siteUtil;
    private $locale;
    private $session;

    function __construct(TranslatorInterface $translator, $siteUtil, Session $session) {
        $this->translator   = $translator;
        $this->siteUtil     = $siteUtil;
        $this->session     = $session;
    }

    public function trans($key, array $params = array(), $domain = 'messages', $locale = null)
    {
        $theme = $this->getTheme();

        if ($domain == 'messages' && $theme != 'default') {
            $domain = 'theme_' . $theme;
        }

        if ($locale === null) {
            $locale = $this->getLocale();
        }

        $translatedString = $this->translator->trans($key, $params, $domain, $locale);

        if ($translatedString == $key) {
            return $this->translator->trans($key, $params, 'messages', $locale);
        }

        return $translatedString;
    }

    public function transChoice($key, $number, array $parameters = array(), $domain = 'messages', $locale = null)
    {
        $theme = $this->getTheme();

        if ($domain == 'messages' && $theme != 'default') {
            $domain = 'theme_' . $theme;
        }

        if ($locale === null) {
            $locale = $this->getLocale();
        }

        $translatedString = $this->translator->transChoice($key, $number, $parameters, $domain, $locale);

        if ($translatedString == $key) {
            return $this->translator->transChoice($key, $number, $parameters, 'messages', $locale);
        }

        return $translatedString;
    }

    private function getTheme()
    {
        if (!($theme = $this->theme)) {
            $site = $this->siteUtil->getCurrentSite();
            $theme = $site ? $site->getTheme() : 'default';
            $this->theme = $theme;
        }

        return $theme;
    }

    public function themeTrans($key, $theme, array $params = array(), $locale = null)
    {
        $this->theme = $theme;
        $domain = 'theme_' . $theme;

        return $this->trans($key, $params, $domain, $locale);
    }

    public function getLocale()
    {
        if (null === $this->locale && null !== $this->session) {
            $this->locale = $this->session->getLocale();
        }

        return $this->locale;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }
}
