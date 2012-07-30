<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Group;
use Platformd\SpoutletBundle\Form\Type\GroupType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;

/**
 * Group controller.
 *
 */
class GroupController extends Controller
{
    private function getGroup($id) {
        return $this
        ->getDoctrine()
        ->getEntityManager()
        ->getRepository('SpoutletBundle:Group')
        ->find($id);
    }

    private function getCurrentUser() {
        return $this->get('security.context')->getToken()->getUser();
    }

    /**
     * Lists all Group entities.
     *
     */
    public function indexAction()
    {
        $this->addGroupsBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        $entities = $em->getRepository('SpoutletBundle:Group')->findAllPublicGroupsForSite($this->getLocale());

        return $this->render('SpoutletBundle:Group:index.html.twig', array(
            'entities' => $entities
        ));
    }

    /**
     * Leave group.
     *
     */
    public function leaveAction($id)
    {
        $group = $this->getGroup($id);

        $mgr = $this->getGroupManager();

        $mgr->ensureGroupIsVisible($group);

        $user = $this->getCurrentUser();

        if ($group->getOwner() === $user) {
            $this->setFlash('error', 'You are the group owner, you are not allowed to leave the group!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        if (!$group->getMembers()->contains($user)) {
            $this->setFlash('error', 'You are not a member of this group!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        $group->getMembers()->removeElement($user);

        $mgr->saveGroup($group);

        $this->setFlash('success', 'You have successfully left this group!');

        return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
    }

    /**
     * Join group.
     *
     */
    public function joinAction($id)
    {
        $mgr    = $this->getGroupManager();
        $group  = $this->getGroup($id);
        $user   = $this->getUser();

        $mgr->ensureGroupIsVisible($group);

        if ($group->isMember($user)) {
             $this->setFlash('error', 'You are already a member of this group!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        $group->getMembers()->add($user);

        $mgr->saveGroup($group);

        $this->setFlash('success', 'You have successfully joined this group!');

        return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
    }

    /**
     * Shows a Group entitie.
     *
     */
    public function showAction($id)
    {
        $this->addGroupsBreadcrumb();

        $group = $this->getGroup($id);

        $mgr = $this->getGroupManager();

        $mgr->ensureGroupIsVisible($group);

        return $this->render('SpoutletBundle:Group:show.html.twig', array(
            'group' => $group
        ));
    }

     /**
     * Creates a new Group (getting started).
     *
     */
    public function newGettingStartedAction(Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        return $this->render('SpoutletBundle:Group:newGettingStarted.html.twig');
    }

    /**
     * Creates a new Group.
     *
     */
    public function newAction(Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $this->addGroupsBreadcrumb()->addChild('New Group');

        $group  = new Group();
        $form    = $this->createForm(new GroupType(), $group);

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', 'The group was created!');

            return $this->redirect($this->generateUrl('group_edit', array('id' => $group->getId())));
        }

        return $this->render('SpoutletBundle:Group:new.html.twig', array(
            'entity' => $group,
            'form'   => $form->createView()
        ));
    }

    /**
     * Edits an existing Group group.
     *
     */
    public function editAction($id, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $this->addGroupsBreadcrumb()->addChild('Edit Group');

        $group = $this->getGroup($id);

        $user = $this->getUser();

        if ($group->getOwner() !== $user) {
            $this->setFlash('error', 'You are not the owner of this group!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $id)));
        }

        $mgr = $this->getGroupManager();

        $mgr->ensureGroupIsVisible($group);

        $editForm   = $this->createForm(new GroupType(), $group);
        $deleteForm = $this->createDeleteForm($id);

        if ($this->processForm($editForm, $request)) {
            $this->setFlash('success', 'The group was saved!');

            return $this->redirect($this->generateUrl('group_edit', array('id' => $id)));
        }

        return $this->render('SpoutletBundle:Group:edit.html.twig', array(
            'group'      => $group,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    /**
     * Delete entity
     */
    public function deleteAction($id)
    {
        $group = $this->getGroup($id);
        $user = $this->getUser();

        if ($group->getOwner() !== $user) {
            $this->setFlash('error', 'You are not the owner of this group!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $id)));
        }

        $mgr = $this->getGroupManager();

        $mgr->ensureGroupIsVisible($group);

        $group->setDeleted(true);

        $mgr->saveGroup($group);

        $this->setFlash('success', 'The group was successfully deleted!');

        return $this->redirect($this->generateUrl('group'));
    }

    private function processForm(Form $form, Request $request)
    {

        $em = $this->getDoctrine()->getEntityManager();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {
                /** @var $group \Platformd\SpoutletBundle\Entity\Group */
                $group = $form->getData();

                $this->getGroupManager()->saveGroup($group);

                return true;
            }
        }

        return false;
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addGroupsBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Groups', array(
            'route' => 'group'
        ));

        return $this->getBreadcrumbs();
    }

    /**
     * @return \Platformd\SpoutletBundle\Model\GroupManager
     */
    private function getGroupManager()
    {
        return $this->get('platformd.model.group_manager');
    }
}
