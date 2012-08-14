<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Group;
use Platformd\SpoutletBundle\Entity\GroupRepository;
use Platformd\SpoutletBundle\Form\Type\GroupFindType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;
use DateTime;

/**
 * Group admin controller.
 *
 */
class GroupAdminController extends Controller
{

    /**
     * Lists all locales - a gateway to the "list" action
     */
    public function indexAction()
    {
        $this->addGroupsBreadcrumb();

        return $this->render('SpoutletBundle:GroupAdmin:index.html.twig', array(
            'sites' => MultitenancyManager::getSiteChoices()
        ));
    }

    public function findAction(Request $request) {
        $this->addFindGroupsBreadcrumb();

        $form = $this->createForm(new GroupFindType());
        $results = $this->processFindForm($form, $request);

        if($results) {

            return $this->render('SpoutletBundle:GroupAdmin:find.html.twig', array(
                'results' => $results,
                'form' => $form->createView()
            ));
        }

        return $this->render('SpoutletBundle:GroupAdmin:find.html.twig', array(
            'results' => $results,
            'form' => $form->createView()
        ));
    }

    public function showAction($id, Request $request) {
        $em = $this->getDoctrine()->getEntityManager();

        $group = $em->getRepository('SpoutletBundle:Group')->find($id);

        if(!$group) {
            throw $this->createNotFoundException('Unable to find group.');
        }

        $this->addFindGroupsBreadcrumb()->addChild($group->getName());

        return $this->render('SpoutletBundle:GroupAdmin:show.html.twig', array(
            'group' => $group
        ));
    }

    private function processFindForm(Form $form, Request $request)
    {
        $groupRepo = $this->getDoctrine()->getRepository('SpoutletBundle:Group');
        $groups = array();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                if($data['sites'] != '') {
                    return $groupRepo->findGroupsByNameAndSite($data['groupName'], $data['sites']);
                }

                return $groupRepo->findGroupsByName($data['groupName']);
            }
        }

        return null;
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addGroupsBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Groups', array(
            'route' => 'admin_group'
        ));

        return $this->getBreadcrumbs();
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addFindGroupsBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Find Groups', array(
            'route' => 'admin_group_find'
        ));

        return $this->getBreadcrumbs();
    }
}
