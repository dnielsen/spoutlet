<?php

namespace Platformd\UserBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Platformd\UserBundle\Form\Type\EditUserFormType;
use FOS\UserBundle\Form\Model\ChangePassword;
use Platformd\UserBundle\Form\Type\AccountSettingsType;
use Platformd\UserBundle\Entity\User;

class AvatarController extends Controller
{
    public function editAction()
    {
        $form = $this->createForm(new AccountSettingsType, $this->getUser());

        return $this->render('FOSUserBundle:Avatar:edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function updateAction(Request $request)
    {
        $form = $this->createForm(new AccountSettingsType, $this->getUser());
        $form->bindRequest($request);

        if ($form->isValid()) {
            $this->get('fos_user.user_manager')->updateUser($form->getData());

            $this->get('session')->setFlash('success', 'Profile settings saved');

            return $this->redirect($this->generateUrl('user_avatar_edit'));
        }

        return $this->render('FOSUserBundle:Avatar:edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function toggleSelectionAction($id)
    {
        $avatar = $this->findAvatarOr404($id, $this->getUser());

        $this->getRepository()->toggle($avatar);
        $this->get('session')->setFlash('success', 'Avatar selection saved');

        return $this->redirect($this->generateUrl('user_avatar_edit'));
    }

    private function findAvatarOr404($id, User $user)
    {
        $avatar = $this->getRepository()->getByUserAndId($user, $id);
        if (!$avatar) {
            throw $this->createNotFoundException();
        }

        return $avatar;
    }

    private function getRepository()
    {
        return $this->get('doctrine')->getRepository('Platformd\UserBundle\Entity\UserAvatar');
    }
}
