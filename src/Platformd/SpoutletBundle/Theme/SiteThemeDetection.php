<?php

namespace Platformd\SpoutletBundle\Theme;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Liip\ThemeBundle\ActiveTheme;
use Platformd\SpoutletBundle\Util\SiteUtil;

class SiteThemeDetection
{
    /** @var \Liip\ThemeBundle\ActiveTheme */
    protected $activeTheme;

    /** @var \Platformd\SpoutletBundle\Util\SiteUtil */
    protected $siteUtil;

    /**
     * Constructor
     *
     * @param \Liip\ThemeBundle\ActiveTheme $activeTheme
     * @param \Platformd\SpoutletBundle\Util\SiteUtil $siteUtil
     */
    public function __construct(ActiveTheme $activeTheme, SiteUtil $siteUtil)
    {
        $this->activeTheme = $activeTheme;
        $this->siteUtil   = $siteUtil;
    }

    /**
     * Listens to kernel.request and assigns the active theme
     * on a per site basis
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($theme = $this->siteUtil->getCurrentSiteCached()->getTheme()) {
            $this->activeTheme->setName($theme);
        }
    }
}
