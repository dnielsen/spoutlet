<?php

namespace Platformd\UserBundle\Controller;

use FOS\UserBundle\Controller\RegistrationController as BaseRegistrationController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Platformd\SpoutletBundle\Exception\InsufficientAgeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RegistrationController extends BaseRegistrationController
{
    public function registerAction()
    {
        $this->enforceAgeProtection();

        $form                   = $this->container->get('fos_user.registration.form');
        $formHandler            = $this->container->get('fos_user.registration.form.handler');
        $confirmationEnabled    = $this->container->getParameter('fos_user.registration.confirmation.enabled');
        $timedout               = $this->container->get('request')->get('timedout') ? true : false;

        $process = $formHandler->process($confirmationEnabled);
        if ($process) {
            $user       = $form->getData();
            $authUser   = false;

            if ($confirmationEnabled) {
                $this->container->get('session')->set('fos_user_send_confirmation_email/email', $user->getEmail());
                $route = 'fos_user_registration_check_email';
            } else {
                $authUser   = true;
                $route      = 'fos_user_registration_confirmed';
            }

            //$this->container->get('platformd.util.flash_util')->setFlash('fos_user_success', 'platformd.user.register.success');
            $url        = $this->container->get('router')->generate($route);
            $response   = new RedirectResponse($url);

            if ($authUser) {
                $this->authenticateUser($user, $response);
            }

            return $response;
        }

        return $this->container->get('templating')->renderResponse('UserBundle:Registration:register.html.'.$this->getEngine(), array(
            'form'      => $form->createView(),
            'theme'     => $this->container->getParameter('fos_user.template.theme'),
            'errors'    => $form->getErrors(),
            'config'    => $this->getCurrentSite()->getSiteConfig(),
            'locale'    => $this->getCurrentSite()->getDefaultLocale(),
            'timedout'  => $timedout,
        ));
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

    private function getCurrentSite()
    {
        return $this->container->get('platformd.util.site_util')->getCurrentSite();
    }


    private function enforceAgeProtection()
    {
        $ageManager = $this->container->get('platformd.age.age_manager');

        if($ageManager->getUsersAge()) {
            if ($ageManager->getUsersAge() < $this->getCurrentSite()->getSiteConfig()->getMinAgeRequirement()) {
                throw new InsufficientAgeException();
            }
        }
    }

    public function confirmAction($token)
    {
        $user = $this->container->get('fos_user.user_manager')->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException();
        }

        $user->setConfirmationToken(null);
        $user->setEnabled(true);
        $user->setLastLogin(new \DateTime());

        $this->container->get('fos_user.user_manager')->updateUser($user);

        $this->setFlash('success', $this->trans('platformd.user.register.confirmed_success'));

        $response = new RedirectResponse($this->container->get('router')->generate('fos_user_security_login', array('f' => 'reg')));
        //$this->authenticateUser($user, $response);

        return $response;
    }

    protected function setFlash($key, $message)
    {
        $this->getFlashUtil()->setFlash($key, $message);
    }

    protected function getFlashUtil()
    {
        return $this->container->get('platformd.util.flash_util');
    }

    /**
     * @param $key
     * @param array $params
     * @param string $domain
     * @return mixed
     */
    protected function trans($key, $params = array(), $domain = 'messages', $locale = null)
    {
        return $this->container->get('platformd.model.translator')->trans($key, $params, $domain, $locale);
    }
}
