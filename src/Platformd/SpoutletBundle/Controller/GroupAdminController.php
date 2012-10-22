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

/**
 * Group admin controller.
 *
 */
class GroupAdminController extends Controller
{
    public function findAction(Request $request) {
        $this->addFindGroupsBreadcrumb();

        $form = $this->createForm(new GroupFindType());
        $results = $this->processFindForm($form, $request);

        $resultTable = array();

        if($results) {

            $groupRepo = $this->getDoctrine()->getRepository('SpoutletBundle:Group');
            $results = $groupRepo->findGroupStats($results);

            foreach ($results as $group) {

                $row = array();

                $row['GroupId']             = $group->getId();
                $row['GroupName']           = $group->getName();
                $row['GroupLink']           = $this->container->get('router')->generate('group_show', array('slug' => $group->getSlug()));;
                $row['Category']            = $group->getCategory();
                $row['Type']                = $group->getIsPublic() ? 'Public' : 'Private';

                $row['Region']              = "";

                if ($group->getAllLocales()) {
                    $row['Region']          = 'All Sites';
                } else {
                    foreach ($group->getSites() as $site) {
                        $row['Region'] .=  '['.$site->getName().']';
                    }
                }

                $row['CreatedAt']           = $group->getCreatedAt();
                $row['Status']              = $group->getDeleted() ? 'Inactive' : 'Active';
                $row['Organizer']           = $group->getOwner()->getUsername();
                $row['MemberCount']         = $group->getMembers()->count();
                $row['VideoCount']          = $group->getVideos()->count();
                $row['ImageCount']          = $group->getImages()->count();
                $row['NewsArticleCount']    = $group->getNewsArticles()->count();

                $row['NewMemberCount'] = $group->getMembershipActions()
                    ->filter(function($x) {
                        return
                        $x->getCreatedAt() >= new DateTime('-30 days') &&
                        ($x->getAction() == GroupMembershipAction::ACTION_JOINED ||
                        $x->getAction() == GroupMembershipAction::ACTION_JOINED_APPLICATION_ACCEPTED); })
                    ->count();

                $row['LeftMemberCount'] = $group->getMembershipActions()
                    ->filter(function($x) {
                        return
                        $x->getCreatedAt() >= new DateTime('-30 days') &&
                        $x->getAction() == GroupMembershipAction::ACTION_LEFT; })
                    ->count();

                $row['FacebookLikes'] = $this->getGroupLikeCount($group);

                $resultTable[] = $row;
            }
        }

        return $this->render('SpoutletBundle:GroupAdmin:find.html.twig', array(
            'results' => $resultTable,
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

        $summary = $groupRepo->getGroupAndMemberCountByRegion();

        return $this->generateGroupsSummaryCsv($summary);
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
                'likes'     => $this->getGroupLikeCount($group),
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
        $thread = $this->getDoctrine()
            ->getRepository('CommentBundle:Thread')
            ->findOneById($groupId);

        $total = $this->getDoctrine()
            ->getRepository('CommentBundle:Comment')
            ->getCommentCountByThread($thread, $fromDate, $thruDate);

        return $total;
    }

    private function getGroupMembersLeftCount($group, $fromDate, $thruDate)
    {
        $repo = $this->getDoctrine()->getRepository('SpoutletBundle:GroupMembershipAction');

        return $repo->getMembersLeftCountByGroup($group, $fromDate, $thruDate);
    }

    private function getGroupLikeCount($group)
    {
        /*
        format for getting open graph data:
        http://graph.facebook.com/?ids=http://[site].alienwarearena.com/groups/[$group->getId()]/show/
        */

        $total = 0;

        $url = 'http://graph.facebook.com/?ids=';

        $sites = $group->getSites();

        foreach($sites as $site)
        {
            $url .= sprintf('http://%s.alienwarearena.com/groups/%s/show/,', $site->getSubDomain(), $group->getId());
        }

        $url = substr($url, 0, -1);

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:'));

        $results = json_decode(curl_exec($curl), true);

        foreach($results as $result)
        {
            if(isset($result))
            {
                if(array_key_exists('likes', $result))
                {
                    $total += $result['likes'];
                }
            }
        }

        return $total;
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
                $summary['groups'],
                $summary['members'],
            ));
        }

        return $factory->createResponse('Groups_Summary.csv');
    }

    public function generateExportCsvAction($type, $groupId)
    {
        $factory = new CsvResponseFactory();

        $groupRepo = $this->getDoctrine()->getRepository('SpoutletBundle:Group');

        switch ($type) {
            case 'members':

                $result = $groupRepo->getGroupMemberListForExport($groupId);

                $factory->addRow(array(
                    'Username',
                    'First Name',
                    'Last Name',
                    'User ID',
                    'Group Name',
                    //'Date Joined',
                    'Country Registered',
                ));

                foreach($result as $member) {
                    $factory->addRow(array(
                        $member['username'],
                        $member['firstname'],
                        $member['lastname'],
                        $member['id'],
                        $member['name'],
                        //$member['createdAt'],
                        $member['country'],
                    ));
                }

                break;

            case 'videos':

                $result = $groupRepo->getGroupVideosForExport($groupId);

                $factory->addRow(array(
                    'Username',
                    'First Name',
                    'Last Name',
                    'User ID',
                    'Group Name',
                    //'Date Joined',
                    'Country Registered',
                    'Title',
                    'Number of Times Reported'
                ));

                foreach($result as $video) {
                    $factory->addRow(array(
                        $video['username'],
                        $video['firstname'],
                        $video['lastname'],
                        $video['id'],
                        $video['name'],
                        //$video['createdAt'],
                        $video['country'],
                        $video['title'],
                        $video[1],
                    ));
                }

                break;

            case 'images':

                $result = $groupRepo->getGroupImagesForExport($groupId);

                $factory->addRow(array(
                    'Username',
                    'First Name',
                    'Last Name',
                    'User ID',
                    'Group Name',
                    //'Date Joined',
                    'Country Registered',
                    'Title',
                    'Number of Times Reported'
                ));

                foreach($result as $image) {
                    $factory->addRow(array(
                        $image['username'],
                        $image['firstname'],
                        $image['lastname'],
                        $image['id'],
                        $image['name'],
                        //$image['createdAt'],
                        $image['country'],
                        $image['title'],
                        $image[1],
                    ));
                }

                break;

            case 'newsArticles':

                $result = $groupRepo->getGroupNewsArticlesForExport($groupId);

                $factory->addRow(array(
                    'Username',
                    'First Name',
                    'Last Name',
                    'User ID',
                    'Group Name',
                    //'Date Joined',
                    'Country Registered',
                    'Title',
                    'Number of Times Reported'
                ));
var_dump($result);exit;
                foreach($result as $newsArticle) {
                    $factory->addRow(array(
                        $newsArticle['username'],
                        $newsArticle['firstname'],
                        $newsArticle['lastname'],
                        $newsArticle['id'],
                        $newsArticle['name'],
                        //$video['createdAt'],
                        $newsArticle['country'],
                        $newsArticle['title'],
                        $newsArticle[1],
                    ));
                }

                break;

            default:

                break;
        }



        return $factory->createResponse('Group_Member_Export.csv');
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
