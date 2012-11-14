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
            'contests' => $contests
        ));
    }

    public function newAction(Request $request)
    {
        $this->addContestsBreadcrumb()->addChild('New Contest');

        $contest  = new Contest();
        $form    = $this->createForm(new ContestType(), $contest);

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', 'The contest was created!');

            return $this->redirect($this->generateUrl('admin_contest_index'));
        }

        return $this->render('SpoutletBundle:ContestAdmin:new.html.twig', array(
            'contest' => $contest,
            'form'   => $form->createView()
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

    public function metricsAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $contests = $em->getRepository('SpoutletBundle:Contest')->findAllForMetrics();
        $this->getBreadcrumbs()->addChild('Metrics');
        $this->getBreadcrumbs()->addChild('Contests');

        foreach ($contests as $contest) {
            $entryCounts[$contest[0]->getId()] = $contest[0]->getEntries()
                        ->filter(function($x) {
                            return
                            $x->getDeleted() != 1 ; })
                        ->count();
        }

        return $this->render('SpoutletBundle:ContestAdmin:metrics.html.twig', array(
            'contests'      => $contests,
            'entryCounts'   => $entryCounts,
        ));
    }

    public function entryMetricsAction($slug)
    {
        $contestEntryRepo   = $this->getDoctrine()->getRepository('SpoutletBundle:ContestEntry');
        $contestRepo        = $this->getDoctrine()->getRepository('SpoutletBundle:Contest');
        $contest            = $contestRepo->findOneBySlug($slug);

        if(!$contest) {
            throw $this->createNotFoundException('Unable to find contest.');
        }

        $entries            = $contestEntryRepo->findAllNotDeletedForContest($contest);

        foreach ($entries as $entry) {
            foreach ($entry->getMedias() as $media) {
                $likes[$media->getId()] = $this->getEntryLikeCount($media);
            }
        }

        $this->getBreadcrumbs()->addChild('Metrics');
        $this->getBreadcrumbs()->addChild('Contests', array(
            'route' => 'admin_contest_metrics'
        ));
        $this->getBreadcrumbs()->addChild($contest->getName());
        $this->getBreadcrumbs()->addChild('Entries');

        return $this->render('SpoutletBundle:ContestAdmin:entries.html.twig', array(
            'entries'   => $entries,
            'slug'      => $slug,
            'likes'     => $likes,
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

        $entries    = $contestEntryRepo->findAllForContest($contest);
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
            //'IP Address',
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
                //$vote->getIpAddress(),
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

                foreach ($rules as $rule) {
                    $rule->setRuleset($ruleset);
                    $newRulesArray[] = $rule;
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
                $contest->getRuleset()->setDefaultAllow(true);

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

        return sprintf('%s\\media\\%s', $cf, $galleryMedia->getImage()->getFilename());
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
