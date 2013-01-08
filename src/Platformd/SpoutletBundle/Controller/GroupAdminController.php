<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Group;
use Platformd\SpoutletBundle\Entity\GroupRepository;
use Platformd\SpoutletBundle\Entity\GroupVideoRepository;
use Platformd\SpoutletBundle\Entity\GroupImageRepository;
use Platformd\SpoutletBundle\Entity\GroupNewsRepository;
use Platformd\SpoutletBundle\Entity\GroupMembershipAction;
use Platformd\SpoutletBundle\Form\Type\GroupFindType;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;
use Platformd\SpoutletBundle\Util\CsvResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Form;
use DateTime;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;

/**
 * Group admin controller.
 *
 */
class GroupAdminController extends Controller
{
    public function filterAction(Request $request)
    {
        $form = $this->createForm(new GroupFindType(), $this->getFilterFormData());
        $form->bindRequest($request);

        $this->setFilterFormData($form->getData());

        return $this->redirect($this->generateUrl('admin_group_find'));
    }

    public function filterResetAction(Request $request)
    {
        $this->setFilterFormData(array());

        return $this->redirect($this->generateUrl('admin_group_find'));
    }

    private function getFilterFormData()
    {
        $session = $this->getRequest()->getSession();
        return $session->get('formValues', array());
    }

    private function setFilterFormData(array $data)
    {
        $session = $this->getRequest()->getSession();
        $session->set('formValues', $data);
    }

    public function findAction(Request $request)
    {
        $this->addFindGroupsBreadcrumb();

        $groupRepo = $this->getDoctrine()->getRepository('SpoutletBundle:Group');
        $filters = $this->getFilterFormData();
        $qb = $groupRepo->findGroupStatsQB($filters);

        $pager = new Pagerfanta(new DoctrineORMAdapter($qb, true));
        $pager->setMaxPerPage(10);
        $pager->setCurrentPage((int)$this->get('request')->query->get('page', 1));
        $form = $this->createForm(new GroupFindType(), $filters);

        return $this->render('SpoutletBundle:GroupAdmin:find.html.twig', array(
            'results' => $pager,
            'form' => $form->createView(),
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

    public function summaryAction() {

        return $this->generateGroupsSummaryCsv();
    }

    public function contentReportAction($id, Request $request)
    {
        $groupRepo = $this->getDoctrine()->getRepository('SpoutletBundle:Group');

        $group = $groupRepo->findOneById($id);

        if(!$group)
        {
            return new Response(json_encode(array('success' => false)));
        }

        $from = $request->get('from') == null ? null : new DateTime($request->get('from'));
        $thru = $request->get('thru') == null ? null : new DateTime($request->get('thru'));

        $result = array();
        $result[] = array(
            'success' => true,
            'results' => array(
                'videos'    => $this->getGroupVideoCount($group, $from, $thru),
                'images'    => $this->getGroupImageCount($group, $from, $thru),
                'news'      => $this->getGroupNewsCount($group, $from, $thru),
                'comments'  => $this->getGroupCommentTotal('group-' . $group->getId(), $from, $thru),
                'likes'     => $group->getFacebookLikes(),
                'users_left'=> $this->getGroupMembersLeftCount($group, $from, $thru),
        ));
        return new Response(json_encode($result));
    }

    private function getGroupVideoCount($group, $fromDate, $thruDate)
    {
        $repo = $this->getDoctrine()->getRepository('SpoutletBundle:GroupVideo');
        return $repo->getVideoCountForGroup($group, $fromDate, $thruDate);
    }

    private function getGroupImageCount($group, $fromDate, $thruDate)
    {
        $repo = $this->getDoctrine()->getRepository('SpoutletBundle:GroupImage');
        return $repo->getImageCountForGroup($group, $fromDate, $thruDate);
    }

    private function getGroupNewsCount($group, $fromDate, $thruDate)
    {
        $repo = $this->getDoctrine()->getRepository('SpoutletBundle:GroupNews');
        return $repo->getNewsCountForGroup($group, $fromDate, $thruDate);
    }

    private function getGroupCommentTotal($groupId, $fromDate, $thruDate)
    {
        $total = $this->getDoctrine()
            ->getRepository('CommentBundle:Comment')
            ->getCommentCountByThread($groupId, $fromDate, $thruDate);

        return $total;
    }

    private function getGroupMembersLeftCount($group, $fromDate, $thruDate)
    {
        $repo = $this->getDoctrine()->getRepository('SpoutletBundle:GroupMembershipAction');

        return $repo->getMembersLeftCountByGroup($group, $fromDate, $thruDate);
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

    private function generateGroupsSummaryCsv()
    {
        $factory = new CsvResponseFactory();
        $groupRepo = $this->getDoctrine()->getRepository('SpoutletBundle:Group');

        $formValues = $this->getFilterFormData();

        $factory->addRow(array(
            'Group Name',
            'Category',
            'Type',
            'Region',
            'Created',
            'Status',
            'Organizer',
            'Total Members',
            'New Members',
            'Video',
            'Photo',
            'News',
            'Likes',
            'Leaves',
        ));

        $results = $groupRepo->findGroups($formValues['groupName'], $formValues['category'], $formValues['deleted'], $formValues['sites'], $formValues['startDate'], $formValues['endDate']);

        foreach ($results as $group) {

            $type = $group->getIsPublic() ? 'Public' : 'Private';

            $region = '';
            if ($group->getAllLocales()) {
                $region          = 'All Sites';
            } else {
                foreach ($group->getSites() as $site) {
                   $region .=  '['.$site->getName().']';
                }
            }

            $status = $group->getDeleted() ? 'Inactive' : 'Active';

            $newMemberCount = $group->getMembershipActions()
                ->filter(function($x) {
                    return
                    $x->getCreatedAt() >= new DateTime('-30 days') &&
                    ($x->getAction() == GroupMembershipAction::ACTION_JOINED ||
                    $x->getAction() == GroupMembershipAction::ACTION_JOINED_APPLICATION_ACCEPTED); })
                ->count();

            $leftMemberCount = $group->getMembershipActions()
                ->filter(function($x) {
                    return
                    $x->getCreatedAt() >= new DateTime('-30 days') &&
                    $x->getAction() == GroupMembershipAction::ACTION_LEFT; })
                ->count();

            $factory->addRow(array(
                $group->getName(),
                $group->getCategory(),
                $type,
                $region,
                $group->getCreatedAt()->format('Y-m-d H:i:s'),
                $status,
                $group->getOwner()->getUsername(),
                $group->getMembers()->count(),
                $newMemberCount,
                $group->getVideos()->count(),
                $group->getImages()->count(),
                $group->getNewsArticles()->count(),
                $group->getFacebookLikes(),
                $leftMemberCount,
            ));
        }

        return $factory->createResponse('Groups_Summary.csv');
    }

    public function generateExportCsvAction($type, $groupId)
    {


        $groupRepo = $this->getDoctrine()->getRepository('SpoutletBundle:Group');

        switch ($type) {
            case 'members':

                $factory = new CsvResponseFactory();

                $result = $groupRepo->getGroupMemberListForExport($groupId);

                $factory->addRow(array(
                    'Username',
                    'First Name',
                    'Last Name',
                    'Email',
                    'User ID',
                    'Group Name',
                    'Date Joined',
                    'Country Registered',
                ));

                foreach($result as $member) {
                    $factory->addRow(array(
                        $member['username'],
                        $member['firstname'],
                        $member['lastname'],
                        $member['email'],
                        $member['id'],
                        $member['name'],
                        $member[1],
                        $member['country'],
                    ));
                }

                $exportFilename = 'Group_Member_Export.csv';

                break;

            case 'videos':

                $group          = $groupRepo->getGroupVideosForExport($groupId);
                $itemCollection = $group->getVideos();
                $factory        = $this->buildGroupMediaExportCsv($group, $itemCollection);

                $exportFilename = 'Group_Video_Export.csv';

                break;

            case 'images':

                $group          = $groupRepo->getGroupImagesForExport($groupId);
                $itemCollection = $group->getImages();

                $factory        = $this->buildGroupMediaExportCsv($group, $itemCollection);

                $exportFilename = 'Group_Image_Export.csv';

                break;

            case 'newsArticles':

                $group          = $groupRepo->getGroupNewsArticlesForExport($groupId);
                $itemCollection = $group->getNewsArticles();
                $factory        = $this->buildGroupMediaExportCsv($group, $itemCollection);
                $exportFilename = 'Group_News_Export.csv';

                break;

            default:

                break;
        }

        return $factory->createResponse($exportFilename);
    }

    private function buildGroupMediaExportCsv($group, $itemCollection) {

        $factory = new CsvResponseFactory();

        $joinActions = $group->getMembershipActions()->filter(function($x) {
            return $x->getAction() == GroupMembershipAction::ACTION_JOINED || $x->getAction() == GroupMembershipAction::ACTION_JOINED_APPLICATION_ACCEPTED;
        });

        $arr = array();

        foreach ($joinActions as $action) {

            $userId = $action->getUser()->getId();
            $date = $action->getCreatedAt();

            if (array_key_exists($userId, $arr)) {
                if ($arr[$userId] < $date) {
                    $arr[$userId] = $date;
                }
            } else {
                $arr[$userId] = $date;
            }
        }

        $factory->addRow(array(
            'Username',
            'First Name',
            'Last Name',
            'Email',
            'User ID',
            'Group Name',
            'Date Joined',
            'Country Registered',
            'Title',
            'Date Added',
            'Number of Times Reported'
        ));

        foreach($itemCollection as $item) {

            $author = $item->getAuthor();

            if ($author->getId() == $group->getOwner()->getId()) {
                $createdAt = $group->getCreatedAt()->format('Y-m-d H:i:s');
            } else {
                $createdAt = $arr[$author->getId()] ? $arr[$author->getId()]->format('Y-m-d H:i:s') : 'n/a';
            }

            $factory->addRow(array(
                $author->getUsername(),
                $author->getFirstName(),
                $author->getLastName(),
                $author->getEmail(),
                $author->getId(),
                $group->getName(),
                $createdAt,
                $author->getCountry(),
                $item->getTitle(),
                $item->getCreatedAt()->format('Y-m-d H:i:s'),
                $item->getContentReports()->count(),
            ));
        }

        return $factory;
    }

    private function processFindForm(Form $form, Request $request)
    {
        $groupRepo = $this->getDoctrine()->getRepository('SpoutletBundle:Group');
        $groups = array();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                $startDate = $form->get('startDate')->getData();
                $endDate = $form->get('endDate')->getData();

                return $groupRepo->findGroups($data['groupName'], $data['category'], $data['deleted'], $data['sites'], $startDate, $endDate);
            }
        }

        return null;
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
