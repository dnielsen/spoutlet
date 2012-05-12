<?php

namespace Platformd\SpoutletBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Platformd\SpoutletBundle\Util\HttpUtil;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Platformd\SpoutletBundle\Exception\InsufficientAgeException;

/**
 * Our custom base controller
 */
class Controller extends BaseController
{
    /**
     * @return string
     */
    protected function getLocale()
    {
        return $this->container->get('session')->getLocale();
    }

    /**
     * @return \Platformd\GiveawayBundle\Entity\GiveawayRepository
     */
    protected function getGiveawayRepo()
    {
        return $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('GiveawayBundle:Giveaway');
    }

    /**
     * @return \Platformd\SweepstakesBundle\Entity\SweepstakesRepository
     */
    protected function getSweepstakesRepo()
    {
        return $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('SweepstakesBundle:Sweepstakes');
    }

    /**
     * @return \Platformd\NewsBundle\Entity\NewsRepository
     */
    protected function getNewsRepo()
    {
        return $this->getDoctrine()
            ->getEntityManager()
            ->getRepository('NewsBundle:News');
    }

    protected function setFlash($key, $message)
    {
        return $this->container->get('session')->setFlash($key, $message);
    }

    /**
     * @return \Platformd\UserBundle\Entity\User
     */
    protected function getUser()
    {
        return $this->container->get('security.context')
            ->getToken()
            ->getUser();
    }

    /**
     * Shortcut for enforcing security
     *
     * @param array $roles
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    protected function basicSecurityCheck($roles)
    {
        if (!$this->container->get('security.context')->isGranted($roles)) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    protected function getBreadcrumbs()
    {
        return $this->get('platformd.breadcrumbs_admin_menu');
    }

    /**
     * @return \Platformd\UserBundle\Entity\UserManager
     */
    protected function getUserManager()
    {
        return $this->get('fos_user.user_manager');
    }

    /**
     * @param $roles
     * @return bool
     */
    protected function isGranted($roles)
    {
        return $this->container->get('security.context')->isGranted($roles);
    }

    protected function enforceUserSecurity()
    {
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw new AccessDeniedException('Not logged in!');
        }
    }

    /**
<<<<<<< HEAD
     * Checks whether a Linkable object is actually a link to another site
     *
     * @param \Platformd\SpoutletBundle\Link\LinkableInterface $linkableObj
     * @return bool
     */
    protected function isExternalLink(LinkableInterface $linkableObj)
    {
        return HttpUtil::isUrlExternal($this->getLinkableUrl($linkableObj), $this->getRequest()->getHost());
    }

    /**
     * Creates a redirect response to the URL of a Linkable object
     *
     * @param \Platformd\SpoutletBundle\Link\LinkableInterface $linkableObj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function createLinkableResponse(LinkableInterface $linkableObj)
    {
        $url = $this->getLinkableUrl($linkableObj);

        return new RedirectResponse($url);
    }

    /**
     * Returns the URL for a Linkable object
     *
     * @param \Platformd\SpoutletBundle\Link\LinkableInterface $linkableObj
     * @return string
     */
    protected function getLinkableUrl(LinkableInterface $linkableObj)
    {
        return $this->container->get('platformd.link.linkable_manager')
            ->link($linkableObj);
    }

    /**
     * @param $key
     * @param array $params
     * @param string $domain
     * @return mixed
     */
    protected function trans($key, $params = array(), $domain = 'messages')
    {
        return $this->container->get('translator')->trans($key, $params, $domain);
    }

    /**
     * Enforces that the user is a certain age and throws the InsufficientAgeException
     * if he/she is now.
     *
     * @param int $minimumAge
     * @throws \Platformd\SpoutletBundle\Exception\InsufficientAgeException
     */
    protected function enforceAgeProtection($minimumAge = 13)
    {
        /** @var $ageManager \Platformd\SpoutletBundle\Age\AgeManager */
        $ageManager = $this->container->get('platformd.age.age_manager');

        if (!$ageManager->getUsersAge() >= $minimumAge) {
            throw new InsufficientAgeException();
        }
    }

    /**
     * @return \Platformd\SpoutletBundle\Age\AgeManager
     */
    protected  function getAgeManager()
    {
        return $this->container->get('platformd.age.age_manager');
    }
}