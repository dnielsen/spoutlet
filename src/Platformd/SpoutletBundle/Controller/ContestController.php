<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Contest;
use Platformd\SpoutletBundle\Entity\ContestRepository;
use Platformd\SpoutletBundle\Entity\ContestEntry;
use Platformd\SpoutletBundle\Entity\ContestEntryRepository;
use Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRule;
use Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRuleset;
use Platformd\SpoutletBundle\Form\Type\ContestType;
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

    public function showAction($slug)
    {
        $user = $this->getCurrentUser();

        $contest = $this->getContestRepository()->findOneBy(array('slug' => $slug));

        $this->ensureContestIsValid($contest);

        $instructions = $contest->getCleanedRedemptionInstructionsArray();

        $isEligible = false;

        $entry = $this->getContestEntryRepository()->findOneByUserAndContest($user, $contest);

        if(!$entry)
        {
            $isEligible = true;
        }

        return $this->render('SpoutletBundle:Contest:show.html.twig', array(
            'contest'       => $contest,
            'instructions'  => $instructions,
            'isEligible'    => $isEligible,
        ));
    }

    public function enterAction($slug, Request $request)
    {
        $user = $this->getCurrentUser();

        $contest = $this->getContestRepository()->findOneBy(array('slug' => $slug));

        $this->ensureContestIsValid($contest);

        $entry = $this->getContestEntryRepository()->findOneByUserAndContest($user, $contest);

        if($entry)
        {
            $this->setFlash('error', $this->trans('contests.enter_page_already_entered'));
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

    public function submitAction($slug, Request $request)
    {
        $this->basicSecurityCheck(array('ROLE_USER'));

        $contest = $this->getContestRepository()->findOneBy(array('slug' => $slug));

        $this->ensureContestIsValid($contest);

        $form = $this->createForm(new SubmitImageType($user));

        $galleries = $this->getGalleryRepository()->findAllGalleriesByCategory('image');

        return $this->render('SpoutletBundle:Contest:submit.html.twig', array(
            'contest' => $contest
        ));
    }

    public function voteAction($slug)
    {
        $contest = $this->getContestRepository()->findOneBy(array('slug' => $slug));

        $this->ensureContestIsValid($contest);

        return $this->render('SpoutletBundle:Contest:vote.html.twig', array(
            'contest' => $contest,
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
