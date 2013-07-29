<?php

namespace Platformd\UserBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Platformd\UserBundle\Form\Type\SuspendUserType;

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
            $this->get('doctrine')->getEntityManager()->flush($user);
            if ($user->getExpiredUntil()) {
                $this->setFlash('success', 'This user is suspended through '.$user->getExpiredUntil()->format('Y-m-d H:i:s'));
            }
            else {
                $this->setFlash('success', 'This user is not suspended anymore');
            }
        }

        return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_edit', array('id' => $id)));
    }

    private function getUserRepository()
    {
        return $this->get('doctrine')->getRepository('UserBundle:User');
    }
}

