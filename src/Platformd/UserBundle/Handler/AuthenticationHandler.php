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

    public function __construct(Router $router, $userManager, SiteUtil $siteUtil)
    {
        $this->router      = $router;
        $this->userManager = $userManager;
        $this->siteUtil = $siteUtil;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $user = $token->getUser();
        $this->userManager->addLoginRecord($user, $request);

        if ($request->isXmlHttpRequest()) {
            // handle ajax login success here
            $response   = new Response();
            $result     = array('success' => true, 'referer' => $request->headers->get('referer'));

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
            $error      = $exception->getMessage();
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
        $response   = new Response();

        $response->headers->clearCookie('fbsr_'.$site->getSiteConfig()->getFacebookAppId());

        return new RedirectResponse($this->router->generate('default_index'));
    }
}
