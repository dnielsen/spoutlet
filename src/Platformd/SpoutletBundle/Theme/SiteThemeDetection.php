<?php

namespace Platformd\SpoutletBundle\Theme;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Liip\ThemeBundle\ActiveTheme;

class SiteThemeDetection
{
    protected $activeTheme;

    protected $themeName;

    public function __construct(ActiveTheme $activeTheme, $themeName = null)
    {
        $this->activeTheme = $activeTheme;
        $this->themeName   = $themeName;
    }

    /**
     * Listens to kernel.request
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($theme = $this->themeName) {
            $this->activeTheme->setName($theme);
        }
    }
}
