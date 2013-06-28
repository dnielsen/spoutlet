<?php

namespace Platformd\UserBundle\Controller;

use FOS\UserBundle\Controller\SecurityController as BaseController;

use Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\EventDispatcher\EventDispatcher,
    Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken,
    Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

use Platformd\UserBundle\Entity\User,
    Platformd\CEVOBundle\CEVOAuthManager,
    Platformd\UserBundle\Security\User\Provider\FacebookProvider,
    Platformd\UserBundle\Form\Type\IncompleteAccountType;

/**
 * Overrides controller for login actions
 */
class SecurityController extends BaseController
{
    public function loginAction()
    {
        return parent::loginAction();
    }

    public function facebookSecurityCheckAction()
    {
        $user           = $this->getCurrentUser();
        $request        = $this->container->get('request');
        $fbProvider     = $this->getFacebookProvider();

        // not logged into facebook or platformd, redirect them to login page
        if(!$fbProvider->isUserAuthenticated() && !$user) {
            return new RedirectResponse($this->container->get('router')->generate('fos_user_security_login'));
        }

        // user is logged in with platformd and are joining their facebook account with their platformd account
        if($user instanceof User) {

            $user = $fbProvider->loadUserByUsername($user->getUsername());

            return new RedirectResponse($this->container->get('router')->generate('accounts_settings'));
        }

        // this user has authenticated our facebook app and is not logged into platformd, so we create the user using facebook data and log them in
        $facebookId = $user ? $user->getFacebookId() : $fbProvider->getFacebookId();
        $context    = $this->container->get('security.context');
        $user       = $fbProvider->loadUserByFacebookId($facebookId);
        $token      = new UsernamePasswordToken($user, $user->getPassword(), 'main', $user->getRoles());

        $context->setToken($token);

        $event = new InteractiveLoginEvent($request, $token);
        $this->container->get('event_dispatcher')->dispatch('security.interactive_login', $event);

        $this->container->get('session')->setFlash('success', 'platformd.facebook.account_created');

        return new RedirectResponse($this->container->get('router')->generate('accounts_settings'));
    }

    public function facebookDeauthorizeAction()
    {
        $fbProvider = $this->getFacebookProvider();

        $fbProvider->deauthorize();

        return new Response(json_encode(array('message' => 'success')));
    }

    public function incompleteAction(Request $request)
    {
        $form = $this->container->get('platformd_incomplete_account', $this->getCurrentUser());

        return $this->render('SpoutletBundle:Account:incomplete.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    private function getCurrentUser() {
        $token = $this->container->get('security.context')->getToken();
        $user  = $token === null ? null : $token->getUser();

        if ($user === 'anon.') {
            return null;
        }

        return $user;
    }

    /**
     * @return \Platformd\SpoutletBundle\Security\User\Provider\FacebookProvider
     */
    protected function getFacebookProvider()
    {
        return $this->container->get('platformd.facebook.provider');
    }
}
