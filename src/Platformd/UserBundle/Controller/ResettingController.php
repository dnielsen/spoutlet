<?php

namespace Platformd\UserBundle\Controller;

use FOS\UserBundle\Controller\ResettingController as BaseController;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use FOS\UserBundle\Model\UserInterface;
use Platformd\UserBundle\Exception\ApiRequestException;

class ResettingController extends BaseController
{

    /**
     * Reset user password
     */
    public function resetAction($token)
    {
        $user = $this->container->get('fos_user.user_manager')->findUserByConfirmationToken($token);
        $request = $this->container->get('request');
        $error = null;

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with "confirmation token" does not exist for value "%s"', $token));
        }

        if (!$user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
            return new RedirectResponse($this->container->get('router')->generate('fos_user_resetting_request'));
        }

        $form = $this->container->get('fos_user.resetting.form');
        $formHandler = $this->container->get('fos_user.resetting.form.handler');

        try {
            $process = $formHandler->process($user);

            if ($process) {
                $response = new RedirectResponse($this->getRedirectionUrl($user));
                $this->authenticateUser($user, $response);
                $this->getFlashUtil()->setFlash('success', $this->trans('resetting.flash.success', array(), 'FOSUserBundle'));
                return $response;
            } elseif ('POST' === $request->getMethod()) {
                $error = $this->trans('resetting.flash.error', array(), 'FOSUserBundle');
            }

        } catch (ApiRequestException $e) {
            $error = $this->trans('resetting.flash.api_error', array(), 'FOSUserBundle');
        }

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:reset.html.'.$this->getEngine(), array(
            'token' => $token,
            'form' => $form->createView(),
            'theme' => $this->container->getParameter('fos_user.template.theme'),
            'error' => $error,
        ));
    }

    /**
     * Tell the user to check his email provider
     */
    public function checkEmailAction()
    {
        $session = $this->container->get('session');
        $email = $session->get(static::SESSION_EMAIL);
        $session->remove(static::SESSION_EMAIL);

        if (empty($email)) {
            // the user does not come from the sendEmail action
            return new RedirectResponse($this->container->get('router')->generate('fos_user_resetting_request'));
        }


        $this->setFlash('success', $this->trans('resetting.check_email', array(), 'FOSUserBundle'));

        return new RedirectResponse($this->container->get('router')->generate('fos_user_security_login', array('f' => 'pr')));
    }

    /**
     * Generate the redirection url when the resetting is completed.
     *
     * @param \FOS\UserBundle\Model\UserInterface $user
     *
     * @return string
     */
    protected function getRedirectionUrl(UserInterface $user)
    {
        return $this->container->get('router')->generate('accounts_index');
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
