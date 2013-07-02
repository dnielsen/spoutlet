<?php

namespace Platformd\UserBundle\Controller;

use FOS\UserBundle\Controller\SecurityController as BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Platformd\CEVOBundle\CEVOAuthManager;

/**
 * Overrides controller for login actions
 */
class SecurityController extends BaseController
{
    public function loginAction()
    {
        return parent::loginAction();
    }

/*    public function checkAction()
    {
        $request    = $this->container->get('request');
        $isAjax     = $request->isXmlHttpRequest();

        if($isAjax) {
            $response   = new Response();
            $response->headers->set('Content-type', 'text/json; charset=utf-8');
            $username   = $request->get('_username');
            $password   = $request->get('_password');

            $user = $this->container->get('fos_user.user_manager')->loadUserByUsername($username);

            try {
                $this->container->get('fos_user.security.login_manager')->loginUser(
                    $this->container->getParameter('fos_user.firewall_name'),
                    $user,
                    $response);
            } catch (AccountStatusException $ex) {
                // We simply do not authenticate users which do not pass the user
                // checker (not enabled, expired, etc.).
                $response->setContent(json_encode(array('success' => false, 'message' => 'platformd.user.login.error')));
                return $response;
            }

            $response->setContent(json_encode(array('success' => true, 'message' => '')));
            return $response;
        }

        return parent::checkAction();
    }*/
}
