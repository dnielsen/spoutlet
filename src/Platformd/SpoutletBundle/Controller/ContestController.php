<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Contest;
use Platformd\SpoutletBundle\Entity\ContestRepository;
use Platformd\SpoutletBundle\Entity\ContestEntry;
use Platformd\SpoutletBundle\Entity\ContestEntryRepository;
use Platformd\SpoutletBundle\Entity\GalleryMedia;
use Platformd\SpoutletBundle\Entity\GalleryMediaRepository;
use Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRule;
use Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRuleset;
use Platformd\SpoutletBundle\Form\Type\ContestType;
use Platformd\SpoutletBundle\Form\Type\SubmitImageType;
use Platformd\CEVOBundle\Api\ApiException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Form;
use Knp\MediaBundle\Util\MediaUtil;
use Platformd\UserBundle\Entity\User;
use Platformd\UserBundle\Entity\RegistrationSource;
use Platformd\SpoutletBundle\Entity\Vote;
use Platformd\SpoutletBundle\Util\TimeZoneUtil as TzUtil;
use DateTime;
use DateTimezone;

class ContestController extends Controller
{
    public function indexAction()
    {
        $site     = $this->getCurrentSite();
        $contests = $this->getContestRepository()->findAllBySite($site);

        return $this->render('SpoutletBundle:Contest:index.html.twig', array(
            'contests'      => $contests,
            'regSourceData' => array('type'=>RegistrationSource::REGISTRATION_SOURCE_TYPE_CONTEST),
        ));
    }

    public function showAction($slug, Request $request)
    {
        $user = $this->getCurrentUser();

        $contest = $this->getContestRepository()->findOneBy(array('slug' => $slug));

        $this->ensureContestIsValid($contest);

        $instructions = $contest->getCleanedRedemptionInstructionsArray();

        $isEligible = true;
        $isEntered = true;

        if ($user instanceof User) {
            $entry = $this->getContestEntryRepository()->findOneByUserAndContest($user, $contest);

            $isEntered = $entry ? true : false;

            if ($contest->getRuleset() && !$contest->getRuleset()->doesUserPassRules($user, $this->getCurrentCountry())) {
                $isEligible = false;
            }

            $mediaCount = $entry ? $entry->getMedias()
            ->filter(function($x) {
                return $x->getDeleted() != 1;
            })->count() : 0;

        } else {
            $isEligible = false;
            $isEntered  = false;
            $mediaCount = 0;
        }

        $entriesLeft        = $contest->getMaxEntries() - $mediaCount;
        $isUnlimited        = $contest->getMaxEntries() == 0;

        $agreeText = $this->trans('contests.show_page_agree_text');
        $canVote = $contest->getVotingStartTz() < new DateTime('now', new DateTimeZone($contest->getTimezone()));

        return $this->render('SpoutletBundle:Contest:show.html.twig', array(
            'contest'       => $contest,
            'instructions'  => $instructions,
            'isEligible'    => $isEligible,
            'agreeText'     => $agreeText,
            'isEntered'     => $isEntered,
            'canVote'       => $canVote,
            'entryCount'    => $mediaCount,
            'entriesLeft'   => $entriesLeft,
            'isUnlimited'   => $isUnlimited,
            'nowInTz'       => new DateTime('now', new DateTimeZone($contest->getTimezone())),
            'regSourceData' => array('type'=>RegistrationSource::REGISTRATION_SOURCE_TYPE_CONTEST, 'id'=>$contest->getId()),
        ));
    }

    public function enterAction($slug, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));
        $user = $this->getCurrentUser();
        $contest = $this->getContestRepository()->findOneBy(array('slug' => $slug));

        $this->ensureContestIsValid($contest);

        if ($contest->getRuleset() && !$contest->getRuleset()->doesUserPassRules($user, $this->getCurrentCountry())) {
            $this->setFlash('error', $this->trans('contests.contest_not_eligible'));
            return $this->redirect($this->generateUrl('contest_show', array('slug' => $slug)));
        }

        $entry = $this->getContestEntryRepository()->findOneByUserAndContest($user, $contest);

        if($entry)
        {
            $this->setFlash('error', $this->trans('contests.enter_page_already_entered'));
            return $this->redirect($this->generateUrl('contest_show', array('slug' => $slug)));
        }

        if ($request->getMethod() == 'POST') {
            $agreed = $request->get('contest-agree');
            if(!$agreed) {
                $this->setFlash('error', $this->trans('contests.enter_page_you_must_agree'));
                return $this->redirect($this->generateUrl('contest_show', array('slug' => $slug)));
            }

            // user has not entered yet so we process the entry
            $em = $this->getEntityManager();

            $entry = new ContestEntry();
            $entry->setUser($user);
            $entry->setContest($contest);
            $entry->setIpAddress($this->getClientIp($request));

            $em->persist($entry);
            $em->flush();

            // arp - enteredsweepstakes
            try {
                $response = $this->getCEVOApiManager()->GiveUserXp('enteredsweepstakes', $user->getCevoUserId());
            } catch (ApiException $e) {

            }

            return $this->redirect($this->generateUrl('contest_submit', array('slug' => $slug)));
        }
    }

    public function submitAction($slug, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $user       = $this->getCurrentUser();
        $contest    = $this->getContestRepository()->findOneBy(array('slug' => $slug));

        $this->ensureContestIsValid($contest);

        $entry = $this->getContestEntryRepository()->findOneByUserAndContest($user, $contest);

        if(!$entry)
        {
            $this->setFlash('error', sprintf($this->trans('contests.submit_page_no_entry'), $contest->getCategory()));
            return $this->redirect($this->generateUrl('contest_show', array('slug' => $slug)));
        }

        $site       = $this->getCurrentSite();
        $form       = $this->createForm(new SubmitImageType($user));
        $medias     = $this->getGalleryMediaRepository()->findAllUnpublishedByUserForContest($user, $contest);
        $galleries  = $this->getGalleryRepository()->findAllGalleriesByCategoryForSite($site, 'image');
        $groups     = $contest->getCategory() == 'image' ? $this->getGroupRepository()->getAllGroupsForUser($user) : $this->getGroupRepository()->findAllOwnedGroupsForContest($user, $entry, $site);
        $mediaCount = $entry->getMedias()
            ->filter(function($x) {
                return $x->getDeleted() != 1;
            });

        $entriesLeft        = $contest->getCategory() == 'image' ? $contest->getMaxEntries() - count($mediaCount) : $contest->getMaxEntries() - count($entry->getGroups());
        $isUnlimited        = $contest->getMaxEntries() == 0;
        $submissionEnded    = new \DateTime("now") > $contest->getSubmissionEndUtc();

        if ($request->getMethod() == 'POST')
        {
            $em = $this->getEntityManager();
            $form->handleRequest($request);
            $images = $form->getData();

            foreach ($images['galleryImages'] as $image)
            {
                $image->setOwner($user);

                $em->persist($image);

                $media = new GalleryMedia();
                $media->setImage($image);
                $media->setAuthor($user);
                $media->setCategory('image');
                $media->setTitle($image->getFileName());
                $media->setContestEntry($entry);
                $em->persist($media);
            }

            $em->flush();

            $this->setFlash('success', $this->trans('galleries.submit_photo_upload_success'));
            return $this->redirect($this->generateUrl('contest_submit', array('slug' => $slug)));
        }

        return $this->render('SpoutletBundle:Contest:submit.html.twig', array(
            'form'              => $form->createView(),
            'contest'           => $contest,
            'medias'            => $medias,
            'galleries'         => $galleries,
            'entriesLeft'       => $entriesLeft,
            'isUnlimited'       => $isUnlimited,
            'submissionEnded'   => $submissionEnded,
            'groups'            => $groups,
            'groupsEntered'     => $entry->getGroups()
        ));
    }

    public function groupSubmitAction($slug, Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        if (!$this->isGranted('ROLE_USER')) {
            $response->setContent(json_encode(array("success" => false, "Authorization failed.")));
            return $response;
        }

        $params = array();
        $content = $request->getContent();

        if (empty($content)) {
            $response->setContent(json_encode(array("success" => false, "details" => "There were no details sent.")));
            return $response;
        }

        $params   = json_decode($content, true);

        if (!isset($params['slug']) || !isset($params['groups'])) {
            $response->setContent(json_encode(array("success" => false, "details" => "There was some missing information.")));
            return $response;
        }

        $user       = $this->getCurrentUser();
        $contest    = $this->getContestRepository()->findOneBy(array('slug' => $params['slug']));

        $entry = $this->getContestEntryRepository()->findOneByUserAndContest($user, $contest);

        if(!$entry)
        {
            $response->setContent(json_encode(array("success" => false, "details" => "There was no entry found.")));
            return $response;
        }

        $em              = $this->getEntityManager();
        $postedGroups    = $params['groups'];
        $groups          = $this->getGroupRepository()->getAllGroupsForUser($user);
        $groupsForEntry  = $this->getGroupRepository()->findAllGroupsWhereIdInForSite($postedGroups, $this->getCurrentSite());

        $entry->setGroups($groupsForEntry);
        $em->persist($entry);
        $em->flush();

        $this->setFlash('success', $this->trans('contests.entry_success'));

        $response->setContent(json_encode(array("success" => true, "details" => $this->trans('contests.entry_success'))));
        return $response;
    }

    public function voteAction($slug, Request $request)
    {
        $contest = $this->getContestRepository()->findOneBy(array('slug' => $slug));
        $user = $this->getUser();

        $this->ensureContestIsValid($contest);
        $mediaId = (int)$request->query->get('vote');

        if ($mediaId && $this->isGranted('ROLE_USER')) {
            $media = $this->getGalleryMediaRepository()->find($mediaId);

            if ($media && !$media->hasUserVoted($this->getUser())) {
                $vote = new Vote();
                $vote->setUser($this->getUser());
                $vote->setGalleryMedia($media);
                $vote->setVoteType('up');
                $vote->setIpAddress($this->getClientIp($request));

                $em = $this->getEntityManager();

                $em->persist($vote);

                $media->getVotes()->add($vote);
                $em->persist($media);
                $em->flush();
            }
        }

        $medias = $this->getGalleryMediaRepository()->findMediaForContest($contest);
        $entries = $this->getContestEntryRepository()->findGroupsByContest($contest);

        $groups = array();

        foreach ($entries as $entry) {
            foreach ($entry->getGroups() as $group) {
                array_push($groups, $group);
            }
        }

        return $this->render('SpoutletBundle:Contest:vote.html.twig', array(
            'contest'       => $contest,
            'medias'        => $medias,
            'groups'        => $groups,
            'regSourceData' => array('type'=>RegistrationSource::REGISTRATION_SOURCE_TYPE_CONTEST, 'id'=>$contest->getId()),
        ));
    }

    public function rulesAction($slug)
    {
        $contest = $this->getContestRepository()->findOneBy(array('slug' => $slug));

        $this->ensureContestIsValid($contest);

        return $this->render('SpoutletBundle:Contest:rules.html.twig', array(
            'contest' => $contest,
        ));
    }

    public function winnersAction($slug)
    {
        $contest = $this->getContestRepository()->findOneBy(array('slug' => $slug));

        $this->ensureContestIsValid($contest);

        $winners = $contest->getCategory() == 'image' ? $this->getGalleryMediaRepository()->findMediaForContestWinners($contest) : $this->getGroupRepository()->findGroupWinnersForContest($contest);

        return $this->render('SpoutletBundle:Contest:winners.html.twig', array(
            'contest' => $contest,
            'winners' => $winners,
        ));
    }

    private function ensureContestIsValid($contest)
    {
        if(!$contest)
        {
            throw $this->createNotFoundException('Contest not found.');
        }

        $canTest = $contest->getTestOnly() && $this->isGranted(array('ROLE_ADMIN', 'ROLE_SUPER_ADMIN'));
        if($contest->getStatus() != 'published' && !$canTest)
        {
            throw $this->createNotFoundException('Contest not found.');
        }
    }

    private function getEntityManager()
    {
        return $this->getDoctrine()->getEntityManager();
    }

    private function getGalleryMediaRepository()
    {
        return $this->getEntityManager()->getRepository('SpoutletBundle:GalleryMedia');
    }

    private function getGalleryRepository()
    {
        return $this->getEntityManager()->getRepository('SpoutletBundle:Gallery');
    }

    private function getGroupRepository()
    {
        return $this->getEntityManager()->getRepository('GroupBundle:Group');
    }

    private function getContestRepository()
    {
        return $this->getEntityManager()->getRepository('SpoutletBundle:Contest');
    }

    private function getCountryRepository()
    {
        return $this->getEntityManager()->getRepository('SpoutletBundle:Country');
    }

    private function getContestEntryRepository()
    {
        return $this->getEntityManager()->getRepository('SpoutletBundle:ContestEntry');
    }

    private function getCEVOApiManager()
    {
        return $this->get('pd.cevo.api.api_manager');
    }
}
