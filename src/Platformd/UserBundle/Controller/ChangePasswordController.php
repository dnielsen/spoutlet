<?php

namespace Platformd\UserBundle\Controller;

use FOS\UserBundle\Controller\ChangePasswordController as BaseChangePasswordController;
use Platformd\CEVOBundle\CEVOAuthManager;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ChangePasswordController extends BaseChangePasswordController
{
    public function changePasswordAction()
    {
        $cevoUrl =  $this->container->get('pd.cevo.cevo_auth_manager')->generateCEVOUrl(
            CEVOAuthManager::CHANGE_PASSWORD_PATH,
            $this->container->get('router')->generate('accounts_index', array(), true)
        );

        return new RedirectResponse($cevoUrl);
    }
}