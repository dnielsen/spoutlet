<?php

namespace Platformd\IdeaBundle\Controller;

use Platformd\EventBundle\Entity\EventRsvpAction;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Platformd\SpoutletBundle\Controller\Controller;
use Platformd\IdeaBundle\Entity\VoteCriteria;
use Platformd\EventBundle\Entity\Event;
use Platformd\IdeaBundle\Entity\EntrySet;
use Platformd\EventBundle\Entity\GroupEvent;
use Platformd\MediaBundle\Entity\Media;
use Platformd\MediaBundle\Form\Type\MediaType;

class AdminController extends Controller
{

    public function eventAction(Request $request, $groupSlug, $eventSlug) {

        // test if submission is from a 'cancel' button press
        if ($request->get('cancel') == 'Cancel') {
            if ($eventSlug == 'newEvent'){
                return $this->redirect($this->generateUrl('group_show', array(
                    'slug'  => $groupSlug,
                )));
            } else {
                return $this->redirect($this->generateUrl('idea_admin', array(
                    'groupSlug' => $groupSlug,
                    'eventSlug' => $eventSlug,
                )));
            }
        }

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventSlug);

        $isNew = false;

        if (!$event) {
            $event = new GroupEvent($group);
            $isNew = true;
        }

        $form = $this->container->get('form.factory')->createNamedBuilder('form', 'event', $event)
            ->add('name',               'text',             array('attr'    => array('size'  => '60%')))
            ->add('content',            'purifiedTextarea', array('attr'    => array('class' => 'ckeditor')))
//            ->add('type',               'choice',           array('choices' => array(EntrySet::TYPE_IDEA      => 'Ideathon',
//                                                                                     EntrySet::TYPE_SESSION   => 'Unconference',
//                                                                                     EntrySet::TYPE_THREAD    => 'Forum',
//                                                                                    )))
            ->add('online',             'choice',           array('choices' => array('1' => 'Yes', '0' => 'No')))
            ->add('private',            'choice',           array('choices' => array('0' => 'No', '1' => 'Yes')))
            ->add('startsAt',           'datetime',         array('attr'    => array('size' => '60%'), 'required' => '0'))
            ->add('endsAt',             'datetime',         array('attr'    => array('size' => '60%'), 'required' => '0'))
            ->add('location',           'text',             array('attr'    => array('size' => '60%'), 'required' => '0'))
            ->add('address1',           'text',             array('attr'    => array('size' => '60%'), 'required' => '0'))
            ->add('address2',           'text',             array('attr'    => array('size' => '60%'), 'required' => '0'))
//            ->add('allowedVoters',      'text',             array('max_length' => '5000', 'attr'    => array('size' => '60%', 'placeholder' => 'username1, username2, ...'), 'required' => '0',))
//            ->add('isSubmissionActive', 'choice',           array('choices' => array('1' => 'Enabled', '0' => 'Disabled')))
//            ->add('isVotingActive',     'choice',           array('choices' => array('1' => 'Enabled', '0' => 'Disabled')))

            ->getForm();

        if($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            if($form->isValid()) {

                $em = $this->getDoctrine()->getEntityManager();

                $group->addEvent($event);

                $event->setUser($this->getCurrentUser());
                $event->setTimezone('UTC');
                $event->setActive(true);
                $event->setApproved(true);
                $event->setRegistrationOption(Event::REGISTRATION_ENABLED);

                $em->persist($event);
                $em->flush();

                //validate and clean up allowedVoters
//                $validatedJudges = array();
//                $candidateJudges = array_map('trim',explode(",",$event->getAllowedVoters()));
//                $userRepo = $this->getDoctrine()->getRepository('UserBundle:User');
//                foreach($candidateJudges as $candidate) {
//                    if($userRepo->findOneBy(array('username'=> $candidate)) != null)
//                        $validatedJudges[] = $candidate;
//                }


                $esReg = $event->createEntrySetRegistration();
                $newEntrySet = new EntrySet();
                $newEntrySet->setName('Entries');
                $newEntrySet->setType(EntrySet::TYPE_IDEA);
                $newEntrySet->setContainer($esReg);
                $newEntrySet->setAllowedVoters(''); //->setAllowedVoters(implode(",",$validatedJudges));
                $newEntrySet->setIsSubmissionActive(1);
                $newEntrySet->setIsVotingActive(1);

                $em->persist($newEntrySet);
                $em->flush();

                return $this->redirect($this->generateUrl('idea_admin', array(
                            'groupSlug' => $groupSlug,
                            'eventSlug' => $event->getSlug(),
                        )));
            }
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Admin:eventForm.html.twig', array(
                'form' => $form->createView(),
                'isNew' => $isNew,
                'group' => $group,
                'event' => $event,
                'isAdmin'    => $isAdmin,
            ));
    }

    public function adminAction(Request $request, $groupSlug, $eventSlug) {

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventSlug);

        if (!$this->canEditEvent($event)) {
            throw new AccessDeniedException();
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Admin:admin.html.twig', array(
                'group'     => $group,
                'event'     => $event,
                'isAdmin'   => $isAdmin,
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

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventSlug);

        $vcRepo = $this->getDoctrine()->getRepository('IdeaBundle:VoteCriteria');

        //retrieve criteria id if available
        $vc = null;
        if(!is_null($id)) {
            $vc = $vcRepo->find($id);
        } else {
            $vc = new VoteCriteria();
        }

        $form = $this->container->get('form.factory')->createNamedBuilder('form', 'criteria', $vc)
            ->add('displayName', 'text', array('label' => 'criteria_displayName'))
            ->add('description', 'textarea', array('label' => 'criteria_description', 'attr' => array('cols' => '60%', 'rows' => '3')))
            ->add('id', 'hidden')
            ->getForm();

        if($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            if($form->isValid()) {

                $em = $this->getDoctrine()->getEntityManager();

                if($vc->getId() == null) {
                    $vc->setEvent($event);
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

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Admin:criteriaForm.html.twig', array(
                'group'     => $group,
                'event'     => $event,
                'form'      => $form->createView(),
                'id'        => $id,
                'isAdmin'   => $isAdmin,
        ));
    }


    public function criteriaListAction(Request $request, $groupSlug, $eventSlug) {

        // test if submission is from a 'new' button press
        if($request->get('new') == 'New') {
            return $this->redirect($this->generateUrl('idea_admin_criteria', array(
                    'groupSlug' => $groupSlug,
                    'eventSlug' => $eventSlug,
                )));
        }

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventSlug);

        $doc = $this->getDoctrine();
        $vcRepo = $doc->getRepository('IdeaBundle:VoteCriteria');

        $criteriaList = $vcRepo->findByEventId($event->getId());

        $choices = array();
        foreach($criteriaList as $criteria) {
            $choices[$criteria->getId()] = $criteria->getDisplayName();
        }
        $formAttributes = array('size' => count($choices) <= 10 ? count($choices) : 10, 'style' => 'width: 50%');

        $form = $this->container->get('form.factory')->createNamedBuilder('form', 'criteria')
            ->add('displayName', 'choice', array('choices' => $choices,
                                                 'label' => 'criteria_displayName',
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

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Admin:criteriaAll.html.twig', array(
                'form'      => $form->createView(),
                'group'     => $group,
                'event'     => $event,
                'isAdmin'   => $isAdmin,
            ));
    }

    public function summaryAction(Request $request, $groupSlug, $eventSlug) {

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventSlug);

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $params = array(
            'group'     => $group,
            'event'     => $event,
            'isAdmin'   => $isAdmin,
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

        $currentRound = $event->getCurrentRound();

        $round = $request->query->get('round', $currentRound);
        $params['round'] = $round;
        $params['currentRound'] = $currentRound;

        //perform filter and sort
        $ideaRepo = $this->getDoctrine()->getRepository('IdeaBundle:Idea');
        $ideaList = $ideaRepo->filter($event, $round, $tag, $this->getCurrentUser());
        $ideaRepo->sortByVotes($ideaList, true, $sortCriteria);

        //save the resulting ordered list of ideas
        $params['ideas'] = $ideaList;
        $params['firstN'] = $request->query->get('firstN');

        //caluclate table values if criteria exist
        $voteRepo = $this->getDoctrine()->getRepository('IdeaBundle:Vote');
        $criteriaCount =  count($params['criteriaList']);

        if($criteriaCount > 0) {
            $params['avgScore'] = $voteRepo->getIdeaCriteriaTable($ideaList, $criteriaCount, $round);
        }

        return $this->render('IdeaBundle:Admin:summary.html.twig', $params);
    }

    public function advanceAction($groupSlug, $eventSlug) {

        $event = $this->getEvent($groupSlug, $eventSlug);

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

    public function imagesAction($groupSlug, $eventSlug, Request $request) {

        $newImage = new Media();
        $form = $this->createForm(new MediaType(), $newImage, array('image_label' => 'Image File:'));

        $event = $this->getEvent($groupSlug, $eventSlug);

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $params = array(
            'group'     => $this->getGroup($groupSlug),
            'event'     => $event,
            'form'      => $form->createView(),
            'isAdmin'   => $isAdmin,
        );

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $image = $form->getData();

                if ($image->getFileObject() == null) {
                    $this->setFlash('error', 'You must select an image file');
                }
                else {
                    $mUtil = $this->getMediaUtil();
                    $mUtil->persistRelatedMedia($image);

                    $event->getRotatorImages()->add($image);

                    $em = $this->getDoctrine()->getEntityManager();
                    $em->flush();
                }
            }
        }

        return $this->render('IdeaBundle:Admin:images.html.twig', $params);
    }

    public function approvalsAction($groupSlug, $eventSlug) {

        $event = $this->getEvent($groupSlug, $eventSlug);
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $attendees = $event->getAttendees();
        $awaitingApproval = array();

        $rsvpRepo = $this->getDoctrine()->getRepository('EventBundle:GroupEventRsvpAction');

        foreach ($attendees as $attendee) {
            $userRsvpStatus = $rsvpRepo->getUserApprovedStatus($event, $attendee);

            if ($userRsvpStatus == 'pending'){
                $awaitingApproval[] = $attendee;
            }

        }

        $params = array(
            'group'             => $this->getGroup($groupSlug),
            'event'             => $event,
            'awaitingApproval'  => $awaitingApproval,
            'isAdmin'           => $isAdmin,
        );

        return $this->render('IdeaBundle:Admin:approvals.html.twig', $params);
    }

    public function processApprovalAction($groupSlug, $eventSlug, $userId, $approval) {

        $eventId = $this->getEvent($groupSlug, $eventSlug)->getId();
        $user = $this->getDoctrine()->getRepository('UserBundle:User')->findOneBy(array('id'=>$userId));

        $rsvpRepo = $this->getDoctrine()->getRepository('EventBundle:GroupEventRsvpAction');
        $userRsvpStatus = $rsvpRepo->findOneBy( array('user' => $userId,'event' => $eventId) );

        $em = $this->getDoctrine()->getEntityManager();

        if ($userRsvpStatus){
            if ($approval == 'approve'){
                $userRsvpStatus->setAttendance(EventRsvpAction::ATTENDING_YES);
                $em->flush();
                $this->setFlash('success', $user->getName().' has been approved for the event.');
            }
            else {
                $userRsvpStatus->setAttendance(EventRsvpAction::ATTENDING_REJECTED);
                $em->flush();
                $this->setFlash('success', $user->getName().' has been rejected for the event.');
            }
        } else {
            $this->setFlash('error', $user->getName().' is not attending this event.');
        }

        return $this->redirect($this->generateUrl('idea_admin_member_approvals', array(
            'groupSlug' => $groupSlug,
            'eventSlug' => $eventSlug,
        )));

    }

    public function removeImageAction($groupSlug, $eventSlug, $imageId) {

        $image = $this->getDoctrine()->getRepository('MediaBundle:Media')->find($imageId);

        if (!$image) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($image);
        $em->flush();

        return $this->redirect($this->generateUrl('idea_admin_images', array(
            'groupSlug' => $groupSlug,
            'eventSlug' => $eventSlug,
        )));
    }


    //------------------------ Helper Functions -----------------------------------
    public function isAdmin()
    {
        return $this->isGranted('ROLE_ADMIN');

    }

    public function canEditEvent(Event $event)
    {
        if ($this->isAdmin() or $this->getCurrentUser() == $event->getUser()){
            return true;
        }
        return false;
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

    public function assignJudgesAction(Request $request, $groupSlug, $eventSlug, $ideaId)
    {
        $doc = $this->getDoctrine();

        if (!$this->canEditEvent($this->getEvent($groupSlug, $eventSlug))) {
            throw new AccessDeniedException();
        }

        $judgeAssignment = $request->request->get('judgeAssignment');
        $idea = $doc->getRepository('IdeaBundle:Idea')->findOneBy(array('id' => $ideaId));

        $judges = array();

        //Check if any judges are being assigned at all
        if ( array_key_exists('judges',$judgeAssignment)) {

            $judgeUserNames = $judgeAssignment['judges'];

            $userRepo = $doc->getRepository('UserBundle:User');
            foreach($judgeUserNames as $judgeUsername) {
                $judge = $userRepo->findOneBy(array('username' => $judgeUsername));
                if($judge != null)
                    $judges[] = $judge;
            }

        }

        $idea->setJudges($judges);

        $em = $doc->getEntityManager();
        $em->flush();

        return  $this->redirect($this->generateUrl('idea_show', array(
            'groupSlug' => $groupSlug,
            'eventSlug' => $eventSlug,
            'id' => $ideaId,
        )));
    }

	public function exportIdeasAction($groupSlug, $eventSlug) {
        $ideaRepo = $this->getDoctrine()->getRepository('IdeaBundle:Idea');
        $csvString = $ideaRepo->toCSV();

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$eventSlug.'-ideas.csv"');
        $response->setContent($csvString);
        return $response;
    }

    public function exportUsersAction() {
        $userRepo = $this->getDoctrine()->getRepository('UserBundle:User');
        $csvString = $userRepo->toCSV();

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="users.csv"');
        $response->setContent($csvString);
        return $response;
    }

    public function exportVotesAction($groupSlug, $eventSlug) {
        $event = $this->getEvent($groupSlug, $eventSlug);

        $voteRepo = $this->getDoctrine()->getRepository('IdeaBundle:Vote');
        $csvString = $voteRepo->toCSV($event);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$eventSlug.'-scores.csv"');
        $response->setContent($csvString);
        return $response;
    }
}

