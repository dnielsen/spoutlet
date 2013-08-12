<?php

namespace Platformd\UserBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Platformd\UserBundle\Form\Type\SuspendUserType;
use Platformd\UserBundle\Exception\ApiRequestException;

class SuspendController extends Controller
{
    public function suspendAction($id, Request $request)
    {
        if (! $user = $this->getUserRepository()->find($id)) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(new SuspendUserType, $user);
        $form->bindRequest($request);

        if ($form->isValid()) {

            if ($this->getApiAuth()) {
                $apiSuccess = $this->getApiManager()->updateRemoteUserData(array(
                    'uuid'            => $user->getUuid(),
                    'suspended_until' => $user->getExpiredUntil() ? $user->getExpiredUntil()->format('Y-m-d H:i:s') : null,
                ));

                if (!$apiSuccess) {
                    $this->setFlash('error', 'There was a problem suspending this user. Please try again soon.');
                    return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_edit', array('id' => $id)));
                }
            }

            $this->getUserManager()->updateUser($user);

            if ($user->getExpiredUntil()) {
                $this->setFlash('success', 'This user is suspended through '.$user->getExpiredUntil()->format('Y-m-d H:i:s'));
            } else {
                $this->setFlash('success', 'This user is not suspended anymore');
            }
        }

        return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_edit', array('id' => $id)));
    }

    public function banAction($id, Request $request)
    {
        if (! $user = $this->getUserRepository()->find($id)) {
            throw $this->createNotFoundException();
        }

        if ($this->getApiAuth()) {
            try {
                $this->getApiManager()->banUser($user);
            } catch (ApiRequestException $e) {
                $this->setFlash('error', 'There was a problem suspending this user. Please try again soon.');
                return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_edit', array('id' => $id)));
            }
        }

        $user->setExpired(true);
        $this->getUserManager()->updateUser($user);
        $this->setFlash('success', 'This user is suspended indefinitely.');

        return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_edit', array('id' => $id)));
    }

    public function unbanAction($id, Request $request)
    {
        if (! $user = $this->getUserRepository()->find($id)) {
            throw $this->createNotFoundException();
        }

        if ($this->getApiAuth()) {
            $apiSuccess = $this->getApiManager()->updateRemoteUserData(array(
                'uuid'            => $user->getUuid(),
                'suspended_until' => null,
                'banned'          => false,
            ));

            if (!$apiSuccess) {
                $this->setFlash('error', 'There was a problem unbanning this user. Please try again soon.');
                return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_edit', array('id' => $id)));
            }
        }

        $user->setExpired(false);
        $user->setExpiredUntil(null);
        $this->getUserManager()->updateUser($user);
        $this->setFlash('success', 'This user is unbanned.');

        return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_edit', array('id' => $id)));
    }

    private function getUserRepository()
    {
        return $this->get('doctrine')->getRepository('UserBundle:User');
    }
}

