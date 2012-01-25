<?php

namespace Platformd\UserBundle\Controller;

use FOS\UserBundle\Controller\RegistrationController as BaseRegistrationController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Platformd\UserBundle\EventListener\AwaVideoLoginRedirectListener;

/**
 * Overridden registration controller
 * 
 */
class RegistrationController extends BaseRegistrationController
{
    public function registerAction()
    {
        // this *would* cause the user to be redirected to the video site
        // assuming that the confirmation email were not part of the process
        $this->processAlienwareVideoReturnUrlParameter($this->container->get('request'));

        $base_url = 'alienwarearena.com'; // for production
        //$base_url = 'platformd'; // for development
        
        $locale = '';
        $dropoff = 'demo.' . $base_url;
        
        // Couldn't get swtich to work dynamically
        if ($_SERVER["HTTP_HOST"] == "japan.${base_url}") {
            $locale = 'japan';
            $dropoff = $locale . "." . $base_url;
        }
        
        if ($_SERVER["HTTP_HOST"] == "china.${base_url}") {
            $locale = 'china';
            $dropoff = $locale . "." . $base_url;
        }


        $url = "http://alienwarearena.com/${locale}/account/register/?return=http://${dropoff}";
        
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