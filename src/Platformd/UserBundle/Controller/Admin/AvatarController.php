<?php

namespace Platformd\UserBundle\Controller\Admin;

use Platformd\SpoutletBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

class AvatarController extends Controller
{
    public function listRemainingAction(Request $request)
    {
        $qb = $this->getRepository()->findAllQB();
        $avatars = new Pagerfanta(new DoctrineORMAdapter($qb, true));
        $avatars->setMaxPerPage(50);
        $avatars->setCurrentPage((int)$this->get('request')->query->get('page', 1));

        return $this->render('FOSUserBundle:Admin/Avatar:list.html.twig', array(
            'avatars' => $avatars,
        ));
    }

    public function batchSetApproveAction(Request $request)
    {
        $this->getRepository()->setApprovals(
            $request->request->get('approved', array()),
            $request->request->get('disapproved', array())
        );

        return $this->redirect($this->generateUrl('Platformd_UserBundle_admin_avatar_list_remaining'));
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
