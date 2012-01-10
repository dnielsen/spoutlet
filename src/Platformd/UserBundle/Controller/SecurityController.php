<?php

namespace Platformd\UserBundle\Controller;

use FOS\UserBundle\Controller\SecurityController as BaseController;
use Symfony\Component\HttpFoundation\Request;
use Platformd\UserBundle\EventListener\AwaVideoLoginRedirectListener;

/**
 * Overrides controller for login actions
 */
class SecurityController extends BaseController
{
    public function loginAction()
    {
        $this->processAlienwareVideoReturnUrlParameter($this->container->get('request'));

        return parent::loginAction();
    }

    /**
     * The Alienware video site expects to send us a ?return=, and we'll go
     * back to that URL afterwards.
     *
     * We use this to store it on the session.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    private function processAlienwareVideoReturnUrlParameter(Request $request)
    {
        if ($returnUrl = $request->query->get('return')) {
            $request->getSession()->set(
                AwaVideoLoginRedirectListener::RETURN_SESSION_PARAMETER_NAME,
                $returnUrl
            );
        }
    }
}
