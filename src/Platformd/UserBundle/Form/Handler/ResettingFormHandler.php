<?php

namespace Platformd\UserBundle\Form\Handler;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Form\Model\ResetPassword;
use FOS\UserBundle\Form\Handler\ResettingFormHandler as BaseHandler;

use Platformd\UserBundle\Exception\ApiRequestException;

class ResettingFormHandler extends BaseHandler
{
    public function process(UserInterface $user)
    {
        $this->form->setData(new ResetPassword());

        if ('POST' === $this->request->getMethod()) {
            $this->form->bindRequest($this->request);

            if ($this->form->isValid()) {
                return $this->onSuccess($user);
            }
        }

        return false;
    }

    protected function onSuccess(UserInterface $user)
    {
        $user->setPlainPassword($this->getNewPassword());
        $user->setConfirmationToken(null);
        $user->setPasswordRequestedAt(null);
        $user->setEnabled(true);

        try {
            $this->userManager->updateApiPassword($user, $this->getNewPassword());
            return true;
        } catch (ApiRequestException $e) {
            return false;
        }
    }
}
