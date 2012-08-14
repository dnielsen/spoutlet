<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Group;
use Platformd\SpoutletBundle\Entity\GroupRepository;
use Platformd\SpoutletBundle\Entity\GroupLocaleRepository;
use Platformd\SpoutletBundle\Form\Type\GroupFindType;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;
use Platformd\SpoutletBundle\Util\CsvResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
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

    public function listAction()
    {
        return $this->render('SpoutletBundle:GroupAdmin:list.html.twig');
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

    public function summaryAction(Request $request) {
        $groupRepo = $this->getDoctrine()->getRepository('SpoutletBundle:Group');
        $groupLocalRepo = $this->getDoctrine()->getRepository('SpoutletBundle:GroupLocale');

        $sites = MultitenancyManager::getSiteChoices();
        $summary = array();

        foreach($sites as $key => $value) {
            $groupCount = $groupLocalRepo->getGroupCountForSite($key);
            $memberCount = $this->getMembershipCountForGroup($groupRepo->findAllPublicAndPrivateGroupsForSite($key));
            $summary[] = array('region' => $value, 'group_count' => $groupCount, 'member_count' => $memberCount);
        }

        return $this->generateGroupsSummaryCsv($summary);

        /*
        return $this->render('SpoutletBundle:GroupAdmin:summary.html.twig', array(
            'summary' => $summary,
        ));
        */
    }

    private function getMembershipCountForGroup($groups)
    {
        $total = 0;
        foreach($groups as $group)
        {
            $total += $group->getMembers()->count();
        }

        return $total;
    }

    private function generateGroupsSummaryCsv($groupsSummary)
    {
        $factory = new CsvResponseFactory();

        $factory->addRow(array(
            'Region',
            'Total Groups',
            'Total Members',
        ));

        foreach($groupsSummary as $summary) {
            $factory->addRow(array(
                $summary['region'],
                $summary['group_count'],
                $summary['member_count'],
            ));
        }

        return $factory->createResponse('Groups_Summary.csv');
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
