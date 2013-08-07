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
use Symfony\Component\HttpFoundation\Cookie;

use Platformd\SpoutletBundle\Util\SiteUtil;

class AuthenticationHandler implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface, LogoutSuccessHandlerInterface
{
    private $router;
    private $userManager;
    private $siteUtil;
    private $apiAuth;
    private $transUtil;
    private $baseHost;
    private $apiManager;

    public function __construct(Router $router, $userManager, SiteUtil $siteUtil, $apiAuth, $transUtil, $baseHost, $apiManager)
    {
        $this->router      = $router;
        $this->userManager = $userManager;
        $this->siteUtil    = $siteUtil;
        $this->apiAuth     = $apiAuth;
        $this->transUtil   = $transUtil;
        $this->baseHost    = $baseHost;
        $this->apiManager  = $apiManager;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $user = $token->getUser();
        $this->userManager->addLoginRecord($user, $request);

        if ($request->isXmlHttpRequest()) {
            // handle ajax login success here
            $locale         = $this->siteUtil->getCurrentSite()->getDefaultLocale();
            $response       = new Response();
            $referer        = $request->headers->get('referer');
            $homePath       = $this->router->generate(sprintf('%s_default_index', $locale), array(), true);
            $checkEmailPath = $this->router->generate(sprintf('%s_fos_user_registration_check_email', $locale), array(), true);
            $registerPath   = $this->router->generate(sprintf('%s_fos_user_registration_register', $locale), array(), true);

            // to avoid confusion for people who hang out on /check-email after clicking the confirm email link, we need to just send them to the homepage :|
            $targetPath = ($referer == $checkEmailPath || $referer == $registerPath) ? $homePath : $referer;
            $result     = array('success' => true, 'referer' => $targetPath, 'r' => $request->headers->get('referer'), 'cep' => $checkEmailPath, 'rp' => $registerPath);

            $response->headers->set('Content-type', 'text/json; charset=utf-8');
            $response->setContent(json_encode($result));

            $cookieInfo = $this->apiManager->getSessionInfo($user->sessionUuid);

            if (!$cookieInfo || (isset($cookieInfo['metaData']) && $cookieInfo['metaData']['status'] != 200)) {
                return $response;
            }

            $cookieName     = 'awa_session_key';
            $cookieValue    = $user->sessionUuid;
            $cookieExpiry   = new \DateTime($cookieInfo['data']['expires']);
            $cookiePath     = '/';
            $cookieHost     = '.'.$this->baseHost;

            $cookie = new Cookie($cookieName, $cookieValue, $cookieExpiry, $cookiePath, $cookieHost, false, false);
            $response->headers->setCookie($cookie);

            return $response;
        } else {

            if ($targetPath = $request->getSession()->get('_security.target_path')) {
                $url = $targetPath;
            } else {
                $url = $this->router->generate('default_index');
            }

            $interimUrl = $this->router->generate('api_session_cookie', array(
                'uuid' => $user->sessionUuid,
                'return' => urlencode($url),
            ));

            return new RedirectResponse($interimUrl);
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

        $sessionKey = $request->cookies->get('awa_session_key');

        if ($sessionKey) {
            $response->headers->clearCookie('awa_session_key', '/', $this->baseHost);
            $this->apiManager->deleteSession($sessionKey);
        }

        $response->headers->clearCookie('fbsr_'.$site->getSiteConfig()->getFacebookAppId(), '/', $this->baseHost);
        $response->headers->clearCookie('PHPSESSID', '/', $this->baseHost);

        return $response;
    }

}
