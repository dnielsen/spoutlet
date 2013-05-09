<?php

namespace Platformd\GroupBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Platformd\GroupBundle\Entity\Group;
use Platformd\GroupBundle\Entity\GroupRepository;
use Platformd\GroupBundle\Entity\GroupVideoRepository;
use Platformd\GroupBundle\Entity\GroupImageRepository;
use Platformd\GroupBundle\Entity\GroupNewsRepository;
use Platformd\GroupBundle\Entity\GroupMembershipAction;
use Platformd\SpoutletBundle\Entity\DiscussionFindWrapper;
use Platformd\GroupBundle\Form\Type\GroupFindType;
use Platformd\SpoutletBundle\Form\Type\DiscussionFindType;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;
use Platformd\SpoutletBundle\Util\CsvResponseFactory;
use Platformd\SpoutletBundle\Metric\MetricManager;
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
        return $session->get('formValuesGroups', array());
    }

    private function setFilterFormData(array $data)
    {
        $session = $this->getRequest()->getSession();
        $session->set('formValuesGroups', $data);
    }

    public function findAction(Request $request)
    {
        $this->addFindGroupsBreadcrumb();

        $groupRepo = $this->getDoctrine()->getRepository('GroupBundle:Group');
        $filters = $this->getFilterFormData();

        if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
            $filters['sites'] = array('ja');
        }

        $qb = $groupRepo->findGroupStatsQB($filters);

        $pager = new Pagerfanta(new DoctrineORMAdapter($qb, true));
        $pager->setMaxPerPage(10);
        $pager->setCurrentPage((int)$this->get('request')->query->get('page', 1));
        $form = $this->createForm(new GroupFindType(), $filters);

        $idArray = array();

        foreach ($pager->getCurrentPageResults() as $result) {
            $idArray[] = $result->getId();
        }

        $groupMemberCounts  = $groupRepo->findGroupMemberCountsIn($idArray);
        $memberCounts       = array();

        foreach ($groupMemberCounts as $group) {
            $memberCounts[$group['id']] = $group['membercount'];
        }

        $mediaCounts = $groupRepo->findGroupMediaCountsIn($idArray);

        return $this->render('GroupBundle:GroupAdmin:find.html.twig', array(
            'results'       => $pager,
            'memberCounts'  => $memberCounts,
            'mediaCounts'   => $mediaCounts,
            'form'          => $form->createView(),
        ));
    }

    public function showAction($id, Request $request) {
        $em = $this->getDoctrine()->getEntityManager();

        $group = $em->getRepository('GroupBundle:Group')->find($id);

        if(!$group) {
            throw $this->createNotFoundException('Unable to find group.');
        }

        $this->addFindGroupsBreadcrumb()->addChild($group->getName());

        return $this->render('GroupBundle:GroupAdmin:show.html.twig', array(
            'group' => $group
        ));
    }

    public function summaryAction() {

        return $this->generateGroupsSummaryCsv();
    }

    public function contentReportAction($id, Request $request)
    {
        $groupRepo = $this->getDoctrine()->getRepository('GroupBundle:Group');

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

    /**
     * Shows detailed metrics for group
     *
     * @param $id
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function detailsAction($id, Request $request)
    {
        $groupRepo = $this->getDoctrine()->getRepository('GroupBundle:Group');

        $group = $groupRepo->find($id);

        if(!$group) {
            throw $this->createNotFoundException('Unable to find group.');
        }

        if ($group->getAllLocales()) {
            $group->Region = 'All Sites';
        } else {
            foreach ($group->getSites() as $site) {
                $group->Region .= '[' . $site->getName() . ']';
            }
        }

        $this->addFindGroupsBreadcrumb()->addChild($group->getName());

        $from = $request->get('from') == null ? new DateTime('-1 month midnight') : new DateTime($request->get('from'));
        $thru = $request->get('thru') == null ? new DateTime('today midnight') : new DateTime($request->get('thru'));

        $groupMetrics = $this->getMetricManager()->getGroupMetricsForPeriod($group, $from, $thru);

        $groupNewMembersArray = array();
        $groupNewDiscussionsArray = array();
        $groupDeletedDiscussionsArray = array();

        foreach($groupMetrics as $groupMetric) {
            $groupNewMembersArray[] = array(($groupMetric->getDate()->getTimestamp() * 1000), $groupMetric->getNewMembers());
            $groupNewDiscussionsArray[] = array(($groupMetric->getDate()->getTimestamp() * 1000), $groupMetric->getNewDiscussions());
            $groupDeletedDiscussionsArray[] = array(($groupMetric->getDate()->getTimestamp() * 1000), $groupMetric->getDeletedDiscussions());
        }

        $discussionMetricsArray = $this->getMetricManager()->getDiscussionMetricsForGroup($group, $from, $thru);

        return $this->render('GroupBundle:GroupAdmin:details.html.twig', array(
            'group' => $group,
            'from' => $from,
            'thru' => $thru,
            'groupNewMembers' => json_encode($groupNewMembersArray),
            'groupNewDiscussions' => json_encode($groupNewDiscussionsArray),
            'groupDeletedDiscussions' => json_encode($groupDeletedDiscussionsArray),
            'discussionMetricsArray' => $discussionMetricsArray
        ));
    }

    /**
     * Generates group discussion summary report for a specific date range
     *
     * @param $id
     * @param \DateTime $from
     * @param \DateTime $thru
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function generateGroupDiscussionCsvAction($id, Request $request)
    {
        $groupRepo = $this->getDoctrine()->getRepository('GroupBundle:Group');

        $group = $groupRepo->find($id);

        if(!$group) {
            throw $this->createNotFoundException('Unable to find group.');
        }

        if ($group->getAllLocales()) {
            $group->Region = 'All Sites';
        } else {
            foreach ($group->getSites() as $site) {
                $group->Region .= '[' . $site->getName() . ']';
            }
        }

        $from = $request->get('from') == null ? new DateTime('-1 month midnight') : new DateTime($request->get('from'));
        $thru = $request->get('thru') == null ? new DateTime('today midnight') : new DateTime($request->get('thru'));

        $discussionMetricsArray = $this->getMetricManager()->getDiscussionMetricsForGroup($group, $from, $thru);

        $factory = new CsvResponseFactory();

        $factory->addRow(array(
            'Date Range',
            'Region',
            'Group Name',
            'Discussions Added',
            'Replies Added',
            'Discussions Deleted',
            'Total discussions',
            'Total replies',
            'Active users',
            'Discussion/day',
            'Replies/day'
        ));

        $factory->addRow(array(
            date_format($from, 'Y/m/d') . '-' . date_format($thru, 'Y/m/d'),
            $group->Region,
            $group->getName(),
            $discussionMetricsArray['discussionsAdded'],
            $discussionMetricsArray['repliesAdded'],
            $discussionMetricsArray['discussionsDeleted'],
            $discussionMetricsArray['discussions'],
            $discussionMetricsArray['replies'],
            $discussionMetricsArray['activeUsers'],
            $discussionMetricsArray['avgDiscussions'],
            $discussionMetricsArray['avgReplies']
        ));

        return $factory->createResponse('Group_Discussions_Summary.csv');
    }

    /**
     * Finds a discussion
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function findDiscussionAction(Request $request)
    {
        $request = $this->getRequest();
        $page = $request->query->get('page', 1);

        $this->addFindDiscussionsBreadcrumb();

        $this->resetDiscussionFilterFormData();

        $data = new DiscussionFindWrapper();
        $form = $this->createForm(new DiscussionFindType(), $data);

        if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
            $data->setSites(array('ja'));
            $form->setData($data);
        }

        if ('POST' == $request->getMethod()) {
            $form->bindRequest($request);
            if ($form->isValid()) {
                $data = $form->getData();

                if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
                    $data->setSites(array('ja'));
                    $form->setData($data);
                }

                if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
                    $data->setSites(array('ja'));
                }

                $this->setDiscussionsFilterFormData(array(
                    'discussionName' => $data->getDiscussionName(),
                    'deleted' => $data->getDeleted(),
                    'sites' => $data->getSites(),
                    'from' => $data->getFrom(),
                    'thru' => $data->getThru()
                ));
            }
        }

        $pager = $this->getDoctrine()->getRepository('GroupBundle:GroupDiscussion')->findDiscussionStats(array(
            'discussionName' => $data->getDiscussionName(),
            'deleted' => $data->getDeleted(),
            'sites' => $data->getSites(),
            'from' => $data->getFrom(),
            'thru' => $data->getThru(),
            'page' => $page
        ));

        foreach ($pager->getCurrentPageResults() as $groupDiscussion) {
            $group = $groupDiscussion->getGroup();
            if ($group->getAllLocales()) {
                $group->Region = 'All Sites';
            } else {
                foreach ($group->getSites() as $site) {
                    $group->Region .= '[' . $site->getName() . ']';
                }
            }
            $groupDiscussion->status = $groupDiscussion->getDeleted() ? 'Inactive' : 'Active';
        }

        return $this->render('GroupBundle:GroupAdmin:findDiscussion.html.twig', array(
            'pager' => $pager,
            'form' => $form->createView(),
        ));
    }

    private function resetDiscussionFilterFormData()
    {
        $this->setDiscussionsFilterFormData(array());
    }

    private function getDiscussionsFilterFormData()
    {
        $session = $this->getRequest()->getSession();
        return $session->get('discussionsFormValues', array());
    }

    private function setDiscussionsFilterFormData($data)
    {
        $session = $this->getRequest()->getSession();
        $session->set('discussionsFormValues', $data);
    }


    public function discussionSummaryAction() {

        return $this->generateGroupsDiscussionsSummaryCsv();
    }

    private function generateGroupsDiscussionsSummaryCsv()
    {
        $factory = new CsvResponseFactory();
        $groupDiscussionRepo = $this->getDoctrine()->getRepository('GroupBundle:GroupDiscussion');

        $factory->addRow(array(
            'Discussion Name',
            'Group',
            'Region',
            'Created',
            'Status',
            'Author',
            'Replies',
            'Views',
        ));

        $filters = array_merge(
            array('discussionName' => '', 'deleted' => '', 'sites' => array(), 'from' => '', 'thru' => ''),
            $this->getDiscussionsFilterFormData()
        );

        $results = $groupDiscussionRepo->findDiscussionStats($filters);

        foreach ($results as $discussion) {

            $region = '';
            if ($discussion->getGroup()->getAllLocales()) {
                $region          = 'All Sites';
            } else {
                foreach ($discussion->getGroup()->getSites() as $site) {
                   $region .=  '['.$site->getName().']';
                }
            }

            $status = $discussion->getDeleted() ? 'Inactive' : 'Active';

            $factory->addRow(array(
                $discussion->getTitle(),
                $discussion->getGroup()->getName(),
                $region,
                $discussion->getCreatedAt()->format('Y-m-d H:i:s'),
                $status,
                $discussion->getAuthor()->getUsername(),
                $discussion->getReplyCount(),
                $discussion->getViewCount(),
            ));
        }

        return $factory->createResponse('Groups_Discussions_Summary.csv');
    }

    /**
     * Generates a detailed report for a discussion and for a specific date range
     *
     * @param $id
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function generateGroupDiscussionDetailsCsvAction($id, Request $request)
    {
        $groupDiscussionRepo = $this->getDoctrine()->getRepository('GroupBundle:GroupDiscussion');

        $groupDiscussion = $groupDiscussionRepo->find($id);

        if(!$groupDiscussion) {
            throw $this->createNotFoundException('Unable to find discussion.');
        }

        $from = $request->get('from') == null ? null : new DateTime($request->get('from'));
        $thru = $request->get('thru') == null ? null : new DateTime($request->get('thru'));;

        $discussionMetricsArray = $this->getMetricManager()->getDiscussionMetricsDetails($groupDiscussion);

        $factory = new CsvResponseFactory();

        $group = $groupDiscussion->getGroup();
        $region = '';
        if ($group->getAllLocales()) {
            $region = 'All Sites';
        } else {
            foreach ($group->getSites() as $site) {
                $region .= '[' . $site->getName() . ']';
            }
        }

        $firstRow = array(
            'Date Range',
            'Discussion Name',
            'Region',
            'Group Name',
            'Views',
            'Replies',
            'Active Users',
            'Author Username',
            'Creation Date',
        );

        $factory->addRow($firstRow);

        $dataRow = array(
            $groupDiscussion->getTitle(),
            $region,
            $group->getName(),
            $discussionMetricsArray['views'],
            $discussionMetricsArray['replies'],
            $discussionMetricsArray['activeUsers'],
            $groupDiscussion->getAuthor()->getUsername(),
            date_format($groupDiscussion->getCreatedAt(), 'Y/m/d')
        );

        if ($from !== null && $thru !== null) {
            array_unshift($dataRow, date_format($from, 'Y/m/d') . '-' . date_format($thru, 'Y/m/d'));
        } else {
            array_unshift($dataRow, 'All Time');
        }

        $factory->addRow($dataRow);

        return $factory->createResponse('Group_Discussion_Detailed'. $groupDiscussion->getId() .'.csv');
    }

    /**
     * Generates a spreadsheet for a discussion with content for all replies
     *
     * @param $id
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function generateGroupDiscussionRepliesCsvAction($id, Request $request)
    {
        $groupDiscussionRepo = $this->getDoctrine()->getRepository('GroupBundle:GroupDiscussion');

        $groupDiscussion = $groupDiscussionRepo->find($id);

        if(!$groupDiscussion) {
            throw $this->createNotFoundException('Unable to find discussion.');
        }

        $factory = new CsvResponseFactory();

        $group = $groupDiscussion->getGroup();
        $region = '';
        if ($group->getAllLocales()) {
            $region = 'All Sites';
        } else {
            foreach ($group->getSites() as $site) {
                $region .= '[' . $site->getName() . ']';
            }
        }

        $firstRow = array(
            'Discussion Name',
            'Region',
            'Group Name',
            'Views',
            'Replies',
            'Author Username',
            'Creation Date',
        );
        $factory->addRow($firstRow);

        $secondRow = array(
            $groupDiscussion->getTitle(),
            $region,
            $group->getName(),
            $groupDiscussion->getViewCount(),
            $groupDiscussion->getReplyCount(),
            $groupDiscussion->getAuthor()->getUsername(),
            date_format($groupDiscussion->getCreatedAt(), 'Y/m/d')
        );
        $factory->addRow($secondRow);

        $factory->addRow(array());
        $factory->addRow(array('Replies'));

        $fourthRow = array(
            'Username',
            'Creation Date',
            'Content'
        );
        $factory->addRow($fourthRow);

        $groupDiscussionPostRepo = $this->getDoctrine()->getEntityManager()->getRepository('GroupBundle:GroupDiscussionPost');
        $groupDiscussionPosts = $groupDiscussionPostRepo->getDiscussionPosts($groupDiscussion);

        foreach ($groupDiscussionPosts as $groupDiscussionPost) {
            $dataRow = array(
                $groupDiscussionPost->getAuthor()->getUsername(),
                date_format($groupDiscussionPost->getCreated(), 'Y/m/d'),
                $groupDiscussionPost->getContent()
            );

            $factory->addRow($dataRow);
        }

        return $factory->createResponse('Group_Discussion_Detailed'. $groupDiscussion->getId() .'.csv');
    }

    private function getGroupVideoCount($group, $fromDate, $thruDate)
    {
        $repo = $this->getDoctrine()->getRepository('GroupBundle:GroupVideo');
        return $repo->getVideoCountForGroup($group, $fromDate, $thruDate);
    }

    private function getGroupImageCount($group, $fromDate, $thruDate)
    {
        $repo = $this->getDoctrine()->getRepository('GroupBundle:GroupImage');
        return $repo->getImageCountForGroup($group, $fromDate, $thruDate);
    }

    private function getGroupNewsCount($group, $fromDate, $thruDate)
    {
        $repo = $this->getDoctrine()->getRepository('GroupBundle:GroupNews');
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
        $repo = $this->getDoctrine()->getRepository('GroupBundle:GroupMembershipAction');

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
        $groupRepo = $this->getDoctrine()->getRepository('GroupBundle:Group');

        $formValues = $this->getFilterFormData();

        $factory->addRow(array(
            'Group Name',
            'Category',
            'Type',
            'Region',
            'Created',
            'Status',
            'Organizer',
            'Organizer Email',
            'Total Members',
            'New Members',
            'Video',
            'Photo',
            'News',
            'Discussions',
            'Likes',
            'Leaves',
        ));

        $filters = array_merge(
            array('groupName' => '', 'category' => '', 'deleted' => '', 'sites' => array(), 'startDate' => '', 'endDate' => ''),
            $this->getFilterFormData()
        );

        $results = $groupRepo->findGroups($filters);

        $idArray = array();
        foreach ($results as $group) {
            $idArray[] = $group->getId();
        }

        $groupMemberCounts  = $groupRepo->findGroupMemberCountsIn($idArray);

        foreach ($groupMemberCounts as $group) {
            $memberCounts[$group['id']] = $group['membercount'];
        }

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

            $newMemberCount = $this->getGroupManager()->getMembersJoinedCountByGroup($group, new \DateTime('-30 days'));
            $leftMemberCount = $this->getGroupManager()->getMembersLeftCountByGroup($group, new \DateTime('-30 days'));

            $factory->addRow(array(
                $group->getName(),
                $group->getCategory(),
                $type,
                $region,
                $group->getCreatedAt()->format('Y-m-d H:i:s'),
                $status,
                $group->getOwner()->getUsername(),
                $group->getOwner()->getEmail(),
                array_key_exists($group->getId(), $memberCounts) ? $memberCounts[$group->getId()] : 0,
                $newMemberCount,
                $group->getVideos()->count(),
                $group->getImages()->count(),
                $group->getNewsArticles()->count(),
                $group->getDiscussions()->count(),
                $group->getFacebookLikes(),
                $leftMemberCount,
            ));
        }

        return $factory->createResponse('Groups_Summary.csv');
    }

    public function generateExportCsvAction($type, $groupId)
    {
        $groupRepo = $this->getDoctrine()->getRepository('GroupBundle:Group');

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

            case 'discussions':

                $group          = $groupRepo->getGroupDiscussionsForExport($groupId);
                $itemCollection = $group->getDiscussions();
                $factory        = $this->buildGroupMediaExportCsv($group, $itemCollection);
                $exportFilename = 'Group_Discussions_Export.csv';

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
        $groupRepo = $this->getDoctrine()->getRepository('GroupBundle:Group');
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

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addFindDiscussionsBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Find Discussions', array(
            'route' => 'admin_group_discussion_find'
        ));

        return $this->getBreadcrumbs();
    }

    /**
     * @return MetricManager
     */
    private function getMetricManager()
    {
        return $this->get('platformd.metric_manager');
    }

    private function getGroupManager()
    {
        return $this->get('platformd.model.group_manager');
    }
}
