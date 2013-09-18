<?php

namespace Platformd\IdeaBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContext;

use Platformd\IdeaBundle\Entity\VoteCriteria;
use Platformd\IdeaBundle\Entity\Idea;
use Platformd\IdeaBundle\Entity\Comment;
use Platformd\IdeaBundle\Entity\Tag;
use Platformd\EventBundle\Entity\Event;
use Platformd\EventBundle\Entity\GroupEvent;

class AdminController extends Controller
{

    public function eventAction(Request $request, $groupSlug, $eventSlug) {

		// test if submission is from a 'cancel' button press
		if ($request->get('cancel') == 'Cancel') {
            return $this->redirect($this->generateUrl('idea_admin', array(
                        'groupSlug' => $groupSlug,
                        'eventSlug' => $eventSlug,
                    )));
		}

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventSlug);

        $isNew = false;

        if (!$event) {
            $event = new GroupEvent($group);
            $isNew = true;
        }


		$form = $this->createFormBuilder($event)
			->add('name', 'text', array('label' => 'Event Title', 'attr' => array('size' => '60%')))
			->add('content', 'textarea', array('label' => 'Welcome Description', 'attr' => array('cols' => '75%', 'rows' => '4')))
			->add('startsAt', 'date', array('label' => 'Start of Event', 'attr' => array('size' => '60%')))
			->add('endsAt', 'date', array('label' => 'End of Event', 'attr' => array('size' => '60%')))
            ->add('location', 'text', array('label' => 'Location', 'attr' => array('size' => '60%')))
			->add('address1', 'text', array('label' => 'Address', 'attr' => array('size' => '60%')))
			->add('address2', 'text', array('label' => 'Address Line 2', 'attr' => array('size' => '60%')))
			->add('allowedVoters', 'text', array('label' => 'Current Judges', 'attr' => array('size' => '60%')))
            ->add('isSubmissionActive', 'choice', array('choices' => array('1' => 'Enabled', '0' => 'Disabled'), 'label' => 'Submissions'))
            ->add('isVotingActive', 'choice', array('choices' => array('1' => 'Enabled', '0' => 'Disabled'), 'label' => 'Voting'))
            ->add('registrationOption', 'text', array('attr' => array('value'=>Event::REGISTRATION_ENABLED,'style'=>'display:none')))
            ->add('private', 'text', array('attr' => array('value' => '1', 'style'=>'display:none')))
			->getForm();

		if($request->getMethod() == 'POST') {
			$form->bindRequest($request);
			if($form->isValid()) {
                $group->addEvent($event);

                $event->setOnline(false);
                $event->setTimezone('UTC');

				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($event);
				$em->flush();

				return $this->redirect($this->generateUrl('idea_admin', array(
                            'groupSlug' => $groupSlug,
                            'eventSlug' => $event->getSlug(),
                        )));
			}
		}

		return $this->render('IdeaBundle:Admin:eventForm.html.twig', array(
                'form' => $form->createView(),
                'isNew' => $isNew,
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
            ));
	}

	public function adminAction(Request $request, $groupSlug, $eventSlug) {
		$event = $this->getEvent($groupSlug, $eventSlug);
		if(!$event) {
			return  $this->redirect($this->generateUrl('idea_admin_event', array(
                    'groupSlug' => $groupSlug,
                )));
		}

		return $this->render('IdeaBundle:Admin:admin.html.twig', array(
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
            ));
	}

	// Edit requets will provide id using GET
	// New request will not provide id using GET
	// Save request will have displayName and description parameters using POST
	public function criteriaAction(Request $request, $groupSlug, $eventSlug, $id = null) {
		// test if submission is from a 'cancel' button press
		if($request->get('cancel') == 'Cancel') {
			return $this->redirect($this->generateUrl('idea_admin_criteria_all', array(
                    'groupSlug' => $groupSlug,
                    'eventSlug' => $eventSlug,
                )));
		}

		$vcRepo = $this->getDoctrine()->getRepository('IdeaBundle:VoteCriteria');

		//retrieve criteria id if available
		$vc = null;
		if(!is_null($id)) {
			$vc = $vcRepo->find($id);
		} else {
			$vc = new VoteCriteria();
		}

		$form = $this->createFormBuilder($vc)
			->add('displayName', 'text', array('label' => 'Display Name'))
			->add('description', 'textarea', array('attr' => array('cols' => '60%', 'rows' => '3')))
			->add('id', 'hidden')
			->getForm();

		if($request->getMethod() == 'POST') {
			$form->bindRequest($request);
			if($form->isValid()) {

				$em = $this->getDoctrine()->getEntityManager();

				if($vc->getId() == null) {
                    $vc->setEvent($this->getEvent($groupSlug, $eventSlug));
					$em->persist($vc);
				}
                else {
					$existingVc = $vcRepo->find($vc->getId());
					$existingVc->setDisplayName($vc->getDisplayName());
					$existingVc->setDescription($vc->getDescription());
				}

				//save to db
				$em->flush();

				return $this->redirect($this->generateUrl('idea_admin_criteria_all', array(
                            'groupSlug' => $groupSlug,
                            'eventSlug' => $eventSlug,
                        )));
			}
		}
		return $this->render('IdeaBundle:Admin:criteriaForm.html.twig', array(
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
                'form' => $form->createView(),
                'id' => $id));
	}


	public function criteriaListAction(Request $request, $groupSlug, $eventSlug) {
		// test if submission is from a 'new' button press
		if($request->get('new') == 'New') {
			return $this->redirect($this->generateUrl('idea_admin_criteria', array(
                    'groupSlug' => $groupSlug,
                    'eventSlug' => $eventSlug,
                )));
		}

		$doc = $this->getDoctrine();
		$vcRepo = $doc->getRepository('IdeaBundle:VoteCriteria');

		$criteriaList = $vcRepo->findByEventId($this->getEvent($groupSlug, $eventSlug)->getId());

		$choices = array();
		foreach($criteriaList as $criteria) {
			$choices[$criteria->getId()] = $criteria->getDisplayName();
		}
		$formAttributes = array('size' => count($choices) <= 10 ? count($choices) : 10, 'style' => 'width: 50%');

		$form = $this->createFormBuilder()->add('displayName', 'choice',
				array('choices' => $choices,
						'label' => 'Criteria Specification',
						'attr' => $formAttributes))
						->getForm();

		if($request->getMethod() == 'POST') {
			$form->bindRequest($request);
			$data = $form->getData();

			//This should never happen b/c of validation
			if(!array_key_exists('displayName',$data))
				return;

			$selectedId = $data['displayName'];

			if ($request->get('edit') == 'Edit') {
				return $this->redirect($this->generateUrl('idea_admin_criteria_get', array(
                            'id' => $selectedId,
                            'groupSlug' => $groupSlug,
                            'eventSlug' => $eventSlug,
                        )));
			}

			//by process of elimination this must be a delete operation
			$selectedCriteria = null;
			foreach($criteriaList as $criteria) {
				if($criteria->getId() == $selectedId)
					$selectedCriteria = $criteria;
			}

			//TODO: Handle id not found exception
  			$doc->getRepository('IdeaBundle:Vote')->removeAllByCriteria($selectedCriteria);
			$doc->getEntityManager()->remove($selectedCriteria);
			$doc->getEntityManager()->flush();
			return $this->redirect($this->generateUrl('idea_admin_criteria_all', array(
                    'groupSlug' => $groupSlug,
                    'eventSlug' => $eventSlug,
                )));
		}


		return $this->render('IdeaBundle:Admin:criteriaAll.html.twig', array(
                'form' => $form->createView(),
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
            ));
	}


	public function summaryAction($groupSlug, $eventSlug) {

        $event = $this->getEvent($groupSlug, $eventSlug);
		if(!$event) {
			return  $this->redirect($this->generateUrl('idea_admin_event', array(
                    'groupSlug' => $groupSlug,
                )));
		}

        $request = $this->getRequest();

		$params = array(
            'groupSlug' => $groupSlug,
            'eventSlug' => $eventSlug,
            'no_sidebar' => true,
        );


		//retrieve criteria sort parameter
		$critId = $request->query->get('crit', 0);
		$params['crit'] = $critId;

        $vcRepo = $this->getDoctrine()->getRepository('IdeaBundle:VoteCriteria');
		$sortCriteria = $vcRepo->find($critId);
        $params['criteriaList'] = $vcRepo->findByEventId($this->getEvent($groupSlug, $eventSlug));

		//retrieve tag filter parameter
		$tag = $request->query->get('tag');
		$params['tag'] = $tag;

		//perform filter and sort
		$currentRound = $event->getCurrentRound();

        $ideaRepo = $this->getDoctrine()->getRepository('IdeaBundle:Idea');
		$ideaList = $ideaRepo->filter($event->getId(), $currentRound, $tag);
		$ideaRepo->sortByVotes($ideaList, true, $sortCriteria);


		//save the resulting ordered list of ideas
		$params['ideas'] = $ideaList;

        $params['round'] = $currentRound;
        $params['firstN'] = $request->query->get('firstN');

		//caluclate table values if criteria exist
		$voteRepo = $this->getDoctrine()->getRepository('IdeaBundle:Vote');
		$criteriaCount =  count($params['criteriaList']);

		if($criteriaCount > 0) {
			$params['avgScore'] = $voteRepo->getIdeaCriteriaTable($ideaList, $criteriaCount, $currentRound);
        }

		return $this->render('IdeaBundle:Admin:summary.html.twig', $params);
	}

	public function advanceAction($groupSlug, $eventSlug) {

		$event = $this->getEvent($groupSlug, $eventSlug);
		if(!$event) {
			return  $this->redirect($this->generateUrl('idea_admin_event', array(
                    'groupSlug' => $groupSlug,
                )));
		}

		//update current round
		$currentRound = $event->getCurrentRound() + 1;
		$event->setCurrentRound($currentRound);

		//update last round for each selected idea
		$params = $this->getRequest()->request->all();
		if(count($params) > 0 ) {
			$ideaEm = $this->getDoctrine()->getRepository('IdeaBundle:Idea');
			foreach($params as $key => $value) {
				$idea = $ideaEm->find($key);
				$idea->setHighestRound($currentRound);
			}
		}

		$em = $this->getDoctrine()->getEntityManager();
		$em->flush();


		return  $this->redirect($this->generateUrl('idea_summary', array(
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
            )));
	}

	//------------------------ Helper Functions -----------------------------------
	public function isAdmin()
    {
		return $this->get('security.context')->isGranted('ROLE_ADMIN');
	}

    public function getGroup($groupSlug)
    {
        $groupEm = $this->getDoctrine()->getRepository('GroupBundle:Group');
        $group = $groupEm->findOneBySlug($groupSlug);

        if ($group == null){
            return false;
        }

        return $group;
    }

    public function getEvent($groupSlug, $eventSlug)
    {
        $group = $this->getGroup($groupSlug);

        if (!$group){
            return false;
        }

        $eventEm = $this->getDoctrine()->getRepository('EventBundle:GroupEvent');
        $event = $eventEm->findOneBy(
            array(
                'group' => $group->getId(),
                'slug' => $eventSlug,
            )
        );

        if ($event == null){
            return false;
        }

        return $event;
    }
}

