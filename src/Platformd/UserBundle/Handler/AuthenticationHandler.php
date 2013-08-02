<?php

namespace Platformd\UserBundle\Handler;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

use Platformd\SpoutletBundle\Util\SiteUtil;

class AuthenticationHandler implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface, LogoutSuccessHandlerInterface
{
    private $router;
    private $userManager;
    private $siteUtil;
    private $apiAuth;
    private $transUtil;
    private $baseHost;

    public function __construct(Router $router, $userManager, SiteUtil $siteUtil, $apiAuth, $transUtil, $baseHost)
    {
        $this->router      = $router;
        $this->userManager = $userManager;
        $this->siteUtil    = $siteUtil;
        $this->apiAuth     = $apiAuth;
        $this->transUtil   = $transUtil;
        $this->baseHost    = $baseHost;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $user = $token->getUser();
        $this->userManager->addLoginRecord($user, $request);

        if ($this->apiAuth && !$user->getApiSuccessfulLogin()) {
            $this->userManager->updateUserAndApi($user);
        }

        if ($request->isXmlHttpRequest()) {
            // handle ajax login success here
            $response       = new Response();
            $referer        = $request->headers->get('referer');
            $homePath       = $this->router->generate('default_index', array(), true);
            $checkEmailPath = $this->router->generate('fos_user_registration_check_email', array(), true);

            // to avoid confusion for people who hang out on /check-email after clicking the confirm email link, we need to just send them to the homepage :|
            if ($referer == $checkEmailPath) {
                $referer = $homePath;
            }

            $result     = array('success' => true, 'referer' => $referer);

            $response->headers->set('Content-type', 'text/json; charset=utf-8');
            $response->setContent(json_encode($result));

            return $response;
        } else {

            if ($targetPath = $request->getSession()->get('_security.target_path')) {
                $url = $targetPath;
            } else {
                $url = $this->router->generate('default_index');
            }

            return new RedirectResponse($url);
        }
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($request->isXmlHttpRequest()) {
            // sup bros, ajax login failure here.
            $response   = new Response();
            $error      = $this->transUtil->trans($exception->getMessage(), array(), 'FOSUserBundle', $this->siteUtil->getCurrentSite()->getDefaultLocale());
            $result     = array('success' => false, 'error' => $error);

            $response->headers->set('Content-type', 'text/json; charset=utf-8');
            $response->setContent(json_encode($result));
            return $response;
        } else {
            $request->getSession()->set(SecurityContext::AUTHENTICATION_ERROR, $exception);
            $url = $this->router->generate('fos_user_security_login');

            return new RedirectResponse($url);
        }
    }

    public function onLogoutSuccess(Request $request)
    {
        $site       = $this->siteUtil->getCurrentSite();
        $response   = new RedirectResponse($this->router->generate('default_index'));

        $response->headers->clearCookie('fbsr_'.$site->getSiteConfig()->getFacebookAppId(), '/', $this->baseHost);
        $response->headers->clearCookie('awa_session_key', '/', $this->baseHost);

        return $response;
    }

}
