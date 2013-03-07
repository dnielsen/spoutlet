<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Contest;
use Platformd\SpoutletBundle\Entity\ContestRepository;
use Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRule;
use Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRuleset;
use Platformd\SpoutletBundle\Form\Type\ContestType;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;
use Platformd\SpoutletBundle\Util\CsvResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Form;
use Knp\MediaBundle\Util\MediaUtil;

class ContestAdminController extends Controller
{
    public function indexAction()
    {
        $this->addContestsBreadcrumb();

        return $this->render('SpoutletBundle:ContestAdmin:index.html.twig', array(
            'sites' => MultitenancyManager::getSiteChoices()
        ));
    }

    public function listAction($site)
    {
        $this->addContestsBreadcrumb();
        $em = $this->getDoctrine()->getEntityManager();

        $imageContests   = $em->getRepository('SpoutletBundle:Contest')->findAllByCategoryAndSite('image', $this->getCurrentSite(), Contest::getValidStatuses());
        $groupContests   = $em->getRepository('SpoutletBundle:Contest')->findAllByCategoryAndSite('group', $this->getCurrentSite(), Contest::getValidStatuses());

        return $this->render('SpoutletBundle:ContestAdmin:list.html.twig', array(
            'imageContests' => $imageContests,
            'groupContests' => $groupContests,
            'site'          => $site,
        ));
    }

    public function newAction(Request $request)
    {
        $this->addContestsBreadcrumb()->addChild('New Contest');

        $em                 = $this->getDoctrine()->getEntityManager();
        $existingContests   = $em->getRepository('SpoutletBundle:Contest')->findAllForSiteAlphabetically($this->getCurrentSite());
        $importId           = $request->get('existing_contest_select');
        $importedContest    = $em->getRepository('SpoutletBundle:Contest')->find($importId);

        if ($importId) {
            $contest = $importedContest ? : new Contest();

            $contest->setName(null);
            $contest->setSlug(null);
            $contest->setBanner(null);
            $contest->setOpenGraphOverride(null);
            $contest->setWinners(null);

        } else {
            $contest = new Contest();
        }

        $form    = $this->createForm(new ContestType(), $contest);

        if (!$importedContest && $this->processForm($form, $request)) {
            $this->setFlash('success', 'The contest was created!');

            return $this->redirect($this->generateUrl('admin_contest_index'));
        }

        return $this->render('SpoutletBundle:ContestAdmin:new.html.twig', array(
            'contest'           => $contest,
            'form'              => $form->createView(),
            'existingContests'  => $existingContests,
        ));
    }

    public function editAction($slug, Request $request)
    {
        $this->addContestsBreadcrumb()->addChild('Edit Contest');
        $em = $this->getDoctrine()->getEntityManager();

        $contest = $em->getRepository('SpoutletBundle:Contest')->findOneBy(array('slug' => $slug));

        if (!$contest) {
            throw $this->createNotFoundException('Unable to find contest.');
        }

        $test   = $contest->getTestOnly();
        if ($test === null) {
            $contest->setTestOnly(0);
        }

        $editForm   = $this->createForm(new ContestType(), $contest);

        if ($this->processForm($editForm, $request)) {
            $this->setFlash('success', 'The contest was saved!');

            return $this->redirect($this->generateUrl('admin_contest_index'));
        }

        return $this->render('SpoutletBundle:ContestAdmin:edit.html.twig', array(
            'contest'       => $contest,
            'edit_form'     => $editForm->createView(),
        ));
    }

    public function chooseWinnersAction($slug)
    {
        $em                 = $this->getDoctrine()->getEntityManager();
        $contestRepo        = $em->getRepository('SpoutletBundle:Contest');
        $galleryMediaRepo   = $em->getRepository('SpoutletBundle:GalleryMedia');
        $voteRepo           = $em->getRepository('SpoutletBundle:Vote');
        $contestEntryRepo   = $em->getRepository('SpoutletBundle:ContestEntry');
        $memberActionRepo   = $em->getRepository('GroupBundle:GroupMembershipAction');
        $contest            = $contestRepo->findOneBy(array('slug' => $slug));


        if (!$contest) {
            throw $this->createNotFoundException('Unable to find contest.');
        }

        $this->addContestsBreadcrumb()->addChild($contest->getSlug());
        $this->getBreadcrumbs()->addChild('Select Winners');

        switch($contest->getCategory()) {
            case 'image':
                $entries = $galleryMediaRepo->findMediaForContest($contest);
                break;
            case 'group':
                $contestEntries = $contestEntryRepo->findGroupsByContest($contest);

                $entries = array();

                foreach ($contestEntries as $entry) {
                    foreach ($entry->getGroups() as $group) {
                        $members = $memberActionRepo->getMembersJoinedCountByGroup($group, $contest->getVotingStart(), $contest->getVotingEnd());
                        array_push($entries, array('group' => $group, 'member_count' => $members));
                    }
                }
                break;
        }



        $voteData = $voteRepo->getVotesForContest($contest);
        $likes = array();
        $dislikes = array();

        foreach ($voteData['up'] as $upVotes) {
            $likes[$upVotes['id']] = $upVotes['vote_count'];
        }

        foreach ($voteData['down'] as $downVotes) {
            $dislikes[$downVotes['id']] = $downVotes['vote_count'];
        }

        return $this->render('SpoutletBundle:ContestAdmin:chooseWinner.html.twig', array(
            'entries'   => $entries,
            'contest'   => $contest,
            'likes'     => $likes,
            'dislikes'  => $dislikes,
        ));
    }

    public function confirmWinnersAction($slug, Request $request)
    {
        $em                 = $this->getDoctrine()->getEntityManager();
        $contestRepo        = $em->getRepository('SpoutletBundle:Contest');
        $galleryMediaRepo   = $em->getRepository('SpoutletBundle:GalleryMedia');
        $contest            = $contestRepo->findOneBy(array('slug' => $slug));

        if ($request->getMethod() == 'POST') {
            if (!$contest) {
                throw $this->createNotFoundException('Unable to find contest.');
            }

            if (!$contest->isFinished()) {
                $this->setFlash('error', 'This contest has not finished yet!');
                return $this->redirect($this->generateUrl('admin_contest_index'));
            }

            $winners = array($request->request->get('first_place'));

            $winners[] = $request->request->get('second_place') ? : null;
            $winners[] = $request->request->get('third_place') ? : null;

            $contest->setWinners($winners);
            $em->persist($contest);
            $em->flush();

            $this->setFlash('success', 'Winner successfully chosen!');
            return $this->redirect($this->generateUrl('admin_contest_index'));
        }

        $this->setFlash('error', 'Something went wrong!');
        return $this->redirect($this->generateUrl('admin_contest_select_winner', array('slug' => $slug)));
    }

    public function metricsAction()
    {
        $this->getBreadcrumbs()->addChild('Metrics');
        $this->getBreadcrumbs()->addChild('Contests');

        $em              = $this->getDoctrine()->getEntityManager();
        $imageContests   = $em->getRepository('SpoutletBundle:Contest')->findAllByCategoryAndSite('image', $this->getCurrentSite());
        $groupContests   = $em->getRepository('SpoutletBundle:Contest')->findAllByCategoryAndSite('group', $this->getCurrentSite());
        $voteResult      = $em->getRepository('SpoutletBundle:Vote')->getVotesForContests();

        $entryCounts      = array();
        $groupEntryCounts = array();
        $votes            = array();

        $contestEntryRepo   = $em->getRepository('SpoutletBundle:ContestEntry');
        $mediaCounts        = $contestEntryRepo->findMediaCountsForContests();
        $groupCounts        = $contestEntryRepo->findGroupCountsForContests();

        foreach ($mediaCounts as $count) {
            $entryCounts[$count['id']] = $count['entry_count'];
        }

        foreach ($groupCounts as $count) {
            $groupEntryCounts[$count['id']] = $count['entry_count'];
        }

        foreach ($voteResult as $vote) {
            $votes[$vote['id']] = $vote['vote_count'];
        }

        return $this->render('SpoutletBundle:ContestAdmin:metrics.html.twig', array(
            'imageContests' => $imageContests,
            'groupContests' => $groupContests,
            'entryCounts'   => $entryCounts,
            'groupEntryCounts' => $groupEntryCounts,
            'votes'         => $votes,
        ));
    }

    public function entryMetricsAction($slug)
    {
        $contestEntryRepo   = $this->getDoctrine()->getRepository('SpoutletBundle:ContestEntry');
        $contestRepo        = $this->getDoctrine()->getRepository('SpoutletBundle:Contest');
        $contest            = $contestRepo->findOneBySlug($slug);

        $this->getBreadcrumbs()->addChild('Metrics');
        $this->getBreadcrumbs()->addChild('Contests', array(
            'route' => 'admin_contest_metrics'
        ));
        $this->getBreadcrumbs()->addChild($contest->getName());
        $this->getBreadcrumbs()->addChild('Entries');

        if($contest->getCategory() == 'image') {
            return $this->renderImageMetrics($contest, $contestEntryRepo, $contestRepo, $slug);
        } elseif ($contest->getCategory() == 'group') {
            return $this->renderGroupMetrics($contest, $contestEntryRepo, $contestRepo, $slug);
        }

    }

    private function renderGroupMetrics($contest, $contestEntryRepo, $contestRepo, $slug)
    {
        $groups = array();

        if(!$contest) {
            throw $this->createNotFoundException('Unable to find contest.');
        }

        $entries = $contestEntryRepo->findAllNotDeletedForContest($contest);

        foreach ($entries as $entry) {
            foreach($entry->getGroups() as $group) {
                array_push($groups, $group);
            }
        }

        return $this->render('SpoutletBundle:ContestAdmin:groupEntries.html.twig', array(
            'contest'   => $contest,
            'entries'   => $entries,
            'slug'      => $slug,
        ));
    }

    private function renderImageMetrics($contest, $contestEntryRepo, $contestRepo, $slug)
    {
        $likes              = array();
        $upVotes            = array();
        $downVotes          = array();

        if(!$contest) {
            throw $this->createNotFoundException('Unable to find contest.');
        }

        $entries = $contestEntryRepo->findAllNotDeletedForContest($contest);

        foreach ($entries as $entry) {
            foreach ($entry->getMedias() as $media) {
                $likes[$media->getId()] = $this->getEntryLikeCount($media);

                $votes =  $media->getVotes();

                if ($votes->count()) {
                    $upCount = $votes
                        ->filter(function($x) {
                            return
                            $x->getVoteType() == "up"; })
                        ->count();

                    $upVotes[$media->getId()] = $upCount;
                } else {
                    $upVotes[$media->getId()] = 0;
                }
            }
        }

        return $this->render('SpoutletBundle:ContestAdmin:entries.html.twig', array(
            'contest'   => $contest,
            'entries'   => $entries,
            'slug'      => $slug,
            'likes'     => $likes,
            'upVotes'   => $upVotes,
        ));
    }

    public function updateEntriesAction($slug, Request $request)
    {
        $em                 = $this->getDoctrine()->getEntityManager();
        $contestEntryRepo   = $em->getRepository('SpoutletBundle:ContestEntry');
        $galleryMediaRepo   = $em->getRepository('SpoutletBundle:GalleryMedia');

        if ($request->getMethod() == 'POST') {

            $entriesToDelete    = $request->request->get('contest_entry_delete');
            $mediaToDelete      = $request->request->get('site_media_delete');

            if (count($entriesToDelete) > 0) {
                foreach ($entriesToDelete as $galleryMediaId => $value) {
                    $galleryMedia = $galleryMediaRepo->find($galleryMediaId);

                    if ($galleryMedia && $value == "on") {
                        $galleryMedia->setContestEntry(null);
                        $em->persist($galleryMedia);
                    }
                }
            }

            if (count($mediaToDelete) > 0) {
                foreach ($mediaToDelete as $galleryMediaId => $value) {
                    $galleryMedia = $galleryMediaRepo->find($galleryMediaId);

                    if ($galleryMedia && $value == "on") {
                        $galleryMedia->setContestEntry(null);
                        $galleryMedia->setDeleted(true);
                        $galleryMedia->setDeletedReason('REMOVED_BY_ADMIN');

                        $em->persist($galleryMedia);
                    }
                }
            }


            $em->flush();

            $this->setFlash('success', 'Entries updated successfully!');
            return $this->redirect($this->generateUrl('admin_contest_entries', array('slug' => $slug)));
        }

        $this->setFlash('error', 'Something went wrong!');
        return $this->redirect($this->generateUrl('admin_contest_entries', array('slug' => $slug)));
    }

    public function generateContestEntriesCsvAction($slug)
    {
        $contestEntryRepo   = $this->getDoctrine()->getRepository('SpoutletBundle:ContestEntry');
        $contestRepo        = $this->getDoctrine()->getRepository('SpoutletBundle:Contest');
        $contest            = $contestRepo->findOneBySlug($slug);

        if(!$contest) {
            throw $this->createNotFoundException('Unable to find contest.');
        }

        $entries    = $contestEntryRepo->findAllNotDeletedForContest($contest);
        $factory    = new CsvResponseFactory();

        $factory->addRow(array(
            'Title',
            'URL',
            'Username',
            'First Name',
            'Last Name',
            'Entry Date',
            'Votes',
            'IP Address',
            'Country Registered',
        ));

        foreach($entries as $entry) {
            foreach($entry->getMedias() as $media) {
                $factory->addRow(array(
                    $media->getTitle(),
                    $this->mediaPathNice($media),
                    $media->getAuthor()->getUsername(),
                    $media->getAuthor()->getFirstname(),
                    $media->getAuthor()->getLastname(),
                    $media->getCreatedAt()->format('Y-m-d H:i:s'),
                    $media->getVotes()->count(),
                    $entry->getIpAddress(),
                    $media->getAuthor()->getCountry(),
                ));
            }
        }

        $exportFilename = $contest->getName().'-Contest_Entry_Export.csv';
        return $factory->createResponse($exportFilename);
    }

    public function generateEntryVotesCsvAction($slug, $id)
    {
        $voteRepo           = $this->getDoctrine()->getRepository('SpoutletBundle:Vote');
        $galleryMediaRepo   = $this->getDoctrine()->getRepository('SpoutletBundle:GalleryMedia');

        $galleryMedia       = $galleryMediaRepo->find($id);
        $url                = $this->mediaPathNice($galleryMedia);

        if(!$galleryMedia) {
            throw $this->createNotFoundException('Unable to find media item.');
        }

        $votes      = $voteRepo->findVotes($galleryMedia);
        $factory    = new CsvResponseFactory();

        $factory->addRow(array(
            'URL',
            'Username',
            'First Name',
            'Last Name',
            'Date of Birth',
            'IP Address',
            'Vote Date',
            'Country Registered',
        ));

        foreach($votes as $vote) {

            $birthDate = $vote->getUser()->getBirthdate() ? $vote->getUser()->getBirthdate()->format('Y-m-d H:i:s') : 'unknown';

            $factory->addRow(array(
                $url,
                $vote->getUser()->getUsername(),
                $vote->getUser()->getFirstname(),
                $vote->getUser()->getLastname(),
                $birthDate,
                $vote->getIpAddress(),
                $vote->getVotedAt()->format('Y-m-d H:i:s'),
                $vote->getUser()->getCountry(),
            ));
        }

        $exportFilename = $vote->getGalleryMedia()->getTitle().'-Votes_Export.csv';
        return $factory->createResponse($exportFilename);
    }

    private function processForm(Form $form, Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {

                $contest    = $form->getData();
                $ruleset    = $contest->getRuleset();
                $rules      = $ruleset->getRules();

                $newRulesArray = array();

                $defaultAllow = true;

                foreach ($rules as $rule) {
                    if ($rule->getMinAge() || $rule->getMaxAge() || $rule->getCountry()) {
                        $rule->setRuleset($ruleset);
                        $newRulesArray[] = $rule;

                        $defaultAllow = $rule->getRuleType() == "allow" ? false : true;
                    }
                }

                $oldRules = $em->getRepository('SpoutletBundle:CountryAgeRestrictionRule')->findBy(array('ruleset' => $ruleset->getId()));

                if ($oldRules) {
                    foreach ($oldRules as $oldRule) {
                        if (!in_array($oldRule, $newRulesArray)) {
                            $oldRule->setRuleset(null);
                        }
                    }
                }

                $contest->getRuleset()->setParentType('contest');
                $contest->getRuleset()->setDefaultAllow($defaultAllow);

                $mUtil = new MediaUtil($this->getDoctrine()->getEntityManager());

                if (!$mUtil->persistRelatedMedia($contest->getBanner())) {
                    $contest->setBanner(null);
                }

                $em->persist($contest);

                $em->flush();

                return true;
            }
        }

        return false;
    }

    private function addContestsBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Contests', array(
            'route' => 'admin_contest_index'
        ));

        return $this->getBreadcrumbs();
    }

    public function mediaPathNice($galleryMedia) {

        $bucketName = $this->container->getParameter('s3_bucket_name');

        if ($bucketName == "platformd") {
            $cf = "http://media.alienwarearena.com";
        } else {
            $cf = "http://mediastaging.alienwarearena.com";
        }

        return sprintf('%s/media/%s', $cf, $galleryMedia->getImage()->getFilename());
    }

    private function getEntryLikeCount($galleryMedia)
    {
        /*
        format for getting open graph data:
        http://graph.facebook.com/?ids=http://[site].alienwarearena.com/groups/[$group->getId()]/show/
        */

        $total = 0;

        $url = 'http://graph.facebook.com/?ids=';

        $sites = $galleryMedia->getContestEntry()->getContest()->getSites();

        foreach($sites as $site)
        {
            $url .= sprintf('http://%s.alienwarearena.com/galleries/photo/%s,', $site->getSubDomain(), $galleryMedia->getId());
        }

        $url = substr($url, 0, -1);

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:'));

        $results = json_decode(curl_exec($curl), true);

        if ($results) {
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
        }

        return $total;
    }
}
