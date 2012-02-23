<?php

namespace Platformd\SpoutletBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AccountController extends Controller
{
	public function profileAction($username = null)
    {
        $context = $this->get('security.context');

        if ($username) {
            $manager = $this->get('fos_user.user_manager');
            if (!$user = $manager->findUserByUsername($username)) {
                
                throw $this->createNotFoundException(sprintf('Unable to find an user with username "%s"', $username));
            }
        } else if ($context->isGranted('IS_AUTHENTICATED_FULLY')) {
            $user = $this->get('security.context')->getToken()->getUser();
        } else {
            
            throw $this->createNotFoundException();
        }

		return $this->render('FOSUserBundle:Profile:show.html.twig', array('user' => $user));		
	}


	public function accountAction()
	{
        $this->checkSecurity();

        // we have a little, temporary message until around April 1st
        $isJapan = $this->getRequest()->getSession()->getLocale() == 'ja';
        // this is April 1st at midnight EST - not sure what the EXACT time should be
        $isItAprilYet = time() < 1333256400;
        $showSweepstakesMessage = ($isJapan && $isItAprilYet);

		return $this->render('SpoutletBundle:Account:account.html.twig', array(
            'showSweepstakesMessage' => $showSweepstakesMessage,
        ));
	}

    public function eventsAction()
    {
        $this->checkSecurity();

        return $this->render('SpoutletBundle:Account:events.html.twig');
    }

    public function videosAction()
    {
        return $this->redirect('/video/edit');
    }
    
    public function giveawaysAction()
    {
        $this->checkSecurity();

        return $this->render('SpoutletBundle:Account:giveaways.html.twig', array(
            'giveawayKeys' => $this->getGiveawayKeyRepository()->findAssignedToUser($this->getUser())
        ));
    }

    protected function checkSecurity()
    {
        if (!$this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @return \Platformd\GiveawayBundle\Entity\Repository\GiveawayKeyRepository
     */
    protected function getGiveawayKeyRepository()
    {
        return $this->getDoctrine()
            ->getRepository('GiveawayBundle:GiveawayKey')
        ;
    }
}
