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

        $contests = $em->getRepository('SpoutletBundle:Contest')->findAllForSiteAlphabetically($site);

        return $this->render('SpoutletBundle:ContestAdmin:list.html.twig', array(
            'contests' => $contests,
            'site'     => $site,
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
        $contest            = $contestRepo->findOneBy(array('slug' => $slug));

        if (!$contest) {
            throw $this->createNotFoundException('Unable to find contest.');
        }

        $this->addContestsBreadcrumb()->addChild($contest->getSlug());
        $this->getBreadcrumbs()->addChild('Select Winners');

        $entries = $galleryMediaRepo->findMediaForContest($contest);

        $voteData = $voteRepo->getVotesForContest($contest);
        $votes = array();

        foreach ($voteData as $itemVotes) {
            $votes[$itemVotes['id']] = $itemVotes['vote_count'];
        }

        return $this->render('SpoutletBundle:ContestAdmin:chooseWinner.html.twig', array(
            'entries'   => $entries,
            'contest'   => $contest,
            'votes'     => $votes,
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
        $em         = $this->getDoctrine()->getEntityManager();
        $contests   = $em->getRepository('SpoutletBundle:Contest')->findAllAlphabetically();
        $voteResult = $em->getRepository('SpoutletBundle:Vote')->getVotesForContests();
        $this->getBreadcrumbs()->addChild('Metrics');
        $this->getBreadcrumbs()->addChild('Contests');

        $entryCounts = array();
        $votes       = array();

        $contestEntryRepo   = $em->getRepository('SpoutletBundle:ContestEntry');
        $mediaCounts        = $contestEntryRepo->findMediaCountsForContests();

        foreach ($mediaCounts as $count) {
            $entryCounts[$count['id']] = $count['entry_count'];
        }

        foreach ($voteResult as $vote) {
            $votes[$vote['id']] = $vote['vote_count'];
        }

        return $this->render('SpoutletBundle:ContestAdmin:metrics.html.twig', array(
            'contests'      => $contests,
            'entryCounts'   => $entryCounts,
            'votes'         => $votes,
        ));
    }

    public function entryMetricsAction($slug)
    {
        $contestEntryRepo   = $this->getDoctrine()->getRepository('SpoutletBundle:ContestEntry');
        $contestRepo        = $this->getDoctrine()->getRepository('SpoutletBundle:Contest');
        $contest            = $contestRepo->findOneBySlug($slug);
        $likes              = array();
        $upVotes            = array();
        $downVotes          = array();

        if(!$contest) {
            throw $this->createNotFoundException('Unable to find contest.');
        }

        $entries            = $contestEntryRepo->findAllNotDeletedForContest($contest);

        foreach ($entries as $entry) {
            foreach ($entry->getMedias() as $media) {
                $likes[$media->getId()] = $this->getEntryLikeCount($media);

                $totalVotes =  $media->getVotes()->count();

                if ($totalVotes) {
                    $upCount = $media->getVotes()
                        ->filter(function($x) {
                            return
                            $x->getVoteType() == "up"; })
                        ->count();

                    $upVotes[$media->getId()] = round(($upCount/$totalVotes)*100);
                    $downVotes[$media->getId()] = round((($totalVotes - $upCount)/$totalVotes)*100);
                } else {
                    $upVotes[$media->getId()] = 0;
                    $downVotes[$media->getId()] = 0;
                }


            }
        }

        $this->getBreadcrumbs()->addChild('Metrics');
        $this->getBreadcrumbs()->addChild('Contests', array(
            'route' => 'admin_contest_metrics'
        ));
        $this->getBreadcrumbs()->addChild($contest->getName());
        $this->getBreadcrumbs()->addChild('Entries');

        return $this->render('SpoutletBundle:ContestAdmin:entries.html.twig', array(
            'contest'   => $contest,
            'entries'   => $entries,
            'slug'      => $slug,
            'likes'     => $likes,
            'upVotes'   => $upVotes,
            'downVotes' => $downVotes,
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
                        $contestEntry = $galleryMedia->getContestEntry();
                        $contestEntry->setDeleted(true);
                        $contestEntry->setDeletedAt(new \DateTime('now'));

                        $em->persist($contestEntry);
                    }
                }
            }

            if (count($mediaToDelete) > 0) {
                foreach ($mediaToDelete as $galleryMediaId => $value) {
                    $galleryMedia = $galleryMediaRepo->find($galleryMediaId);

                    if ($galleryMedia && $value == "on") {
                        $contestEntry = $galleryMedia->getContestEntry();
                        $contestEntry->setDeleted(true);
                        $contestEntry->setDeletedAt(new \DateTime('now'));

                        $em->persist($contestEntry);

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
            'Likes',
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
                    $this->getEntryLikeCount($media),
                ));
            }
        }

        $exportFilename = '['.$contest->getName().']_Contest_Entry_Export.csv';
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
            'IP Address',
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

        $exportFilename = '['.$vote->getGalleryMedia()->getTitle().']_Votes_Export.csv';
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
                    $rule->setRuleset($ruleset);
                    $newRulesArray[] = $rule;

                    $defaultAllow = $rule->getRuleType() == "allow" ? false : true;
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
