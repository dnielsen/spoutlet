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
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Form;
use Knp\MediaBundle\Util\MediaUtil;

class ContestController extends Controller
{
    public function indexAction()
    {
        $site = $this->getCurrentSite();
        $contests = $this->getContestRepository()->findAllForSiteAlphabetically($site->getDefaultLocale());

        return $this->render('SpoutletBundle:Contest:index.html.twig', array(
            'contests' => $contests,
        ));
    }

    public function showAction($slug, Request $request)
    {
        $user = $this->getCurrentUser();

        $contest = $this->getContestRepository()->findOneBy(array('slug' => $slug));

        $this->ensureContestIsValid($contest);

        $instructions = $contest->getCleanedRedemptionInstructionsArray();

        $isEligible = false;
        $isEntered = true;

        $entry = $this->getContestEntryRepository()->findOneByUserAndContest($user, $contest);

        if(!$entry)
        {
            $isEligible = true;
            $isEntered = false;
        }

        $agreeText = $this->trans('contests.show_page_agree_text');

        return $this->render('SpoutletBundle:Contest:show.html.twig', array(
            'contest'       => $contest,
            'instructions'  => $instructions,
            'isEligible'    => $isEligible,
            'agreeText'     => $agreeText,
            'isEntered'     => $isEntered,
        ));
    }

    public function enterAction($slug, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $user = $this->getCurrentUser();

        $contest = $this->getContestRepository()->findOneBy(array('slug' => $slug));

        $this->ensureContestIsValid($contest);

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
            $entry->setIpAddress($request->getClientIp());

            $em->persist($entry);
            $em->flush();

            $this->setFlash('success', sprintf($this->trans('contests.enter_page_success'), $contest->getName(), $contest->getCategory()));
            return $this->redirect($this->generateUrl('contest_show', array('slug' => $slug)));
        }
    }

    public function submitAction($slug, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $user       = $this->getCurrentUser();
        $contest    = $this->getContestRepository()->findOneBy(array('slug' => $slug));

        $this->ensureContestIsValid($contest);

        $form       = $this->createForm(new SubmitImageType($user));

        $medias     = $this->getGalleryMediaRepository()->findAllUnpublishedByUser($user);
        $galleries  = $this->getGalleryRepository()->findAllGalleriesByCategory('image');
        $groups     = $this->getGroupRepository()->getAllGroupsForUser($user);

        $entry = $this->getContestEntryRepository()->findOneByUserAndContest($user, $contest);

        if(!$entry)
        {
            $this->setFlash('error', sprintf($this->trans('contests.submit_page_no_entry', $contest->getCategory())));
            return $this->redirect($this->generateUrl('contest_show', array('slug' => $slug)));
        }

        $mediaCount = $entry->getMedias()
            ->filter(function($x) {
                return $x->getDeleted() != 1;
            });

        $entriesLeft        = $contest->getMaxEntries() - count($mediaCount);
        $isUnlimited        = $contest->getMaxEntries() == 0;
        $submissionEnded    = new \DateTime("now") > $contest->getSubmissionEnd();

        if ($request->getMethod() == 'POST')
        {
            $em = $this->getEntityManager();
            $form->bindRequest($request);
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
        ));
    }

    public function voteAction($slug)
    {
        $contest = $this->getContestRepository()->findOneBy(array('slug' => $slug));

        $this->ensureContestIsValid($contest);

        $medias = $this->getGalleryMediaRepository()->findMediaForContest($contest);

        return $this->render('SpoutletBundle:Contest:vote.html.twig', array(
            'contest' => $contest,
            'medias'  => $medias,
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

        $winners = $this->getGalleryMediaRepository()->findMediaForContestWinners($contest);

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

        if($contest->getStatus() != 'published')
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
        return $this->getEntityManager()->getRepository('SpoutletBundle:Group');
    }

    private function getContestRepository()
    {
        return $this->getEntityManager()->getRepository('SpoutletBundle:Contest');
    }

    private function getContestEntryRepository()
    {
        return $this->getEntityManager()->getRepository('SpoutletBundle:ContestEntry');
    }

    private function getCurrentUser()
    {
        return $this->get('security.context')->getToken()->getUser();
    }
}
