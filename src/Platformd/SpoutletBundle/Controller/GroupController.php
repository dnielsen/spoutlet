<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Group;
use Platformd\SpoutletBundle\Entity\GroupNews;
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
        $em = $this->getEntityManager();

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
     * Add group news.
     *
     */
    public function addNewsAction($id, Request $request)
    {
        $gm    = $this->getGroupManager();
        $group  = $this->getGroup($id);
        $user   = $this->getUser();

        $gm->ensureGroupIsVisible($group);

        if (!$gm->isCurrentUserAllowedToEditGroup($group)) {
            throw new AccessDeniedException();
        }

        $groupNews = new GroupNews();

        $form = $this->createFormBuilder($groupNews)
            ->add('title', 'text')
            ->add('article', 'textarea')
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $groupNews->setGroup($group);

                $gm->saveGroupNews($groupNews);

                $this->setFlash('success', 'New article posted successfully.');

                return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('SpoutletBundle:Group:show.html.twig', array(
            'group' => $group,
            'newsForm' => $form->createView(),
            'newsFormAction' => $this->generateUrl('group_add_news', array('id' => $id))
        ));
    }

    /**
     * Edit group news.
     *
     */
    public function editNewsAction($id, $newsId, Request $request)
    {
        $gm    = $this->getGroupManager();
        $group  = $this->getGroup($id);
        $user   = $this->getUser();

        $gm->ensureGroupIsVisible($group);

        if (!$gm->isCurrentUserAllowedToEditGroup($group)) {
            throw new AccessDeniedException();
        }

        $gr = $this->getGroupNewsRepository();
        $newsArticle = $gr->find($newsId);

        if (!$newsArticle) {
            $this->setFlash('error', 'News article does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        $form = $this->createFormBuilder($newsArticle)
            ->add('title', 'text')
            ->add('article', 'textarea')
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            if ($form->isValid()) {

                $newsArticle->setGroup($group);

                $gm->saveGroupNews($newsArticle);

                $this->setFlash('success', 'New article updated successfully.');

                return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
            }

            $this->setFlash('error', 'Please correct the following errors and try again!');
        }

        return $this->render('SpoutletBundle:Group:show.html.twig', array(
            'group' => $group,
            'newsForm' => $form->createView(),
            'newsFormAction' => $this->generateUrl('group_edit_news', array('id' => $id, 'newsId' => $newsId))
        ));
    }

    /**
     * Edit group news.
     *
     */
    public function deleteNewsAction($id, $newsId, Request $request)
    {
        $gm    = $this->getGroupManager();
        $group  = $this->getGroup($id);
        $user   = $this->getUser();

        $gm->ensureGroupIsVisible($group);

        if (!$gm->isCurrentUserAllowedToEditGroup($group)) {
            throw new AccessDeniedException();
        }

        $gr = $this->getGroupNewsRepository();
        $newsArticle = $gr->find($newsId);

        if (!$newsArticle) {
            $this->setFlash('error', 'News article does not exist!');
            return $this->redirect($this->generateUrl('group_show', array('id' => $group->getId())));
        }

        $em = $this->getEntityManager();
        $em->remove($newsArticle);
        $em->flush();

        $this->setFlash('success', 'News article was deleted successfully!');

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

        $userIsAdminOrOwner = $mgr->isCurrentUserAllowedToEditGroup($group);

        $gr = $this->getGroupNewsRepository();

        $groupNews = $gr->getNewsForGroupMostRecentFirst($group);

        return $this->render('SpoutletBundle:Group:show.html.twig', array(
            'group' => $group,
            'groupNews' => $groupNews,
            'userIsAdminOrOwner' => $userIsAdminOrOwner
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

        $em = $this->getEntityManager();

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

    private function getEntityManager() {
        return $this->getDoctrine()->getEntityManager();
    }

    private function getGroupNewsRepository()
    {
        return $this->getEntityManager()->getRepository('SpoutletBundle:GroupNews');
    }
}
