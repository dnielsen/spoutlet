<?php

namespace Platformd\UserBundle\Controller;

use FOS\UserBundle\Controller\RegistrationController as BaseRegistrationController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Platformd\UserBundle\EventListener\AwaVideoLoginRedirectListener;
use Platformd\CEVOBundle\CEVOAuthManager;

/**
 * Overridden registration controller
 * 
 */
class RegistrationController extends BaseRegistrationController
{
    /**
     * Simply redirects to the CEVO site with the correct URL
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function registerAction()
    {
        // store the return URL that's on the request in the session, return it
        $returnUrl = $this->processAlienwareVideoReturnUrlParameter($this->container->get('request'));

        // if there is no return URL, we'll ultimately send back to the homepage
        $returnUrl = $returnUrl ? $returnUrl : '/';

        $url = $this->getCevoAuthManager()->generateCevoUrl(
            CEVOAuthManager::REGISTER_PATH,
            $returnUrl
        );
        
        return new RedirectResponse($url);
        
    }

    /**
     * Page that shows a message to people that are too young
     */
    public function tooYoungMessageAction()
    {
        return $this->container
            ->get('templating')
            ->renderResponse('UserBundle:Registration:tooYoung.html.twig')
        ;
    }

    /**
     * The Alienware video site expects to send us a ?return=, and we'll go
     * back to that URL afterwards.
     *
     * We use this to store it on the session.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string The URL that we've stored that should be returned to
     */
    private function processAlienwareVideoReturnUrlParameter(Request $request)
    {
        if ($returnUrl = $request->query->get('return')) {
            $request->getSession()->set(
                AwaVideoLoginRedirectListener::RETURN_SESSION_PARAMETER_NAME,
                $returnUrl
            );

            return $returnUrl;
        }
    }

    /**
     * @return \Platformd\CEVOBundle\CEVOAuthManager
     */
    private function getCevoAuthManager()
    {
        return $this->container->get('pd.cevo.cevo_auth_manager');
    }
}