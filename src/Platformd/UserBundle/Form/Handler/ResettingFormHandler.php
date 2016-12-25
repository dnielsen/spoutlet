<?php

namespace Platformd\UserBundle\Form\Handler;

use FOS\UserBundle\Form\Model\ChangePassword;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Form\Handler\ResettingFormHandler as BaseHandler;

class ResettingFormHandler extends BaseHandler
{
    public function process(UserInterface $user)
    {
        $this->form->setData(new ChangePassword());

        if ('POST' === $this->request->getMethod()) {
            $this->form->handleRequest($this->request);

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

        $this->userManager->updateApiPassword($user, $this->getNewPassword());
        return true;
    }
}
