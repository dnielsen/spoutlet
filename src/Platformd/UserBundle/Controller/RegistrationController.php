<?php

namespace Platformd\UserBundle\Controller;

use FOS\UserBundle\Controller\RegistrationController as BaseRegistrationController;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Overridden registration controller
 */
class RegistrationController extends BaseRegistrationController
{
    public function registerAction()
    {
        $response = parent::registerAction();

        $request = $this->container->get('request');

        /*
         * Intercept failed validations
         *
         * This is because we have normal validation to not let users 13 and
         * under register. But instead of throwing a validation error, we
         * need to redirect them to a whole other page
         */
        if ($request->getMethod() == 'POST' && !($response instanceof RedirectResponse)) {
            $form = $this->container->get('fos_user.registration.form');

            if ($form->getData()->isAMinor()) {
                $url = $this->container
                    ->get('router')
                    ->generate('user_registration_too_young')
                ;

                return new RedirectResponse($url);
            }
        }

        return $response;
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
}