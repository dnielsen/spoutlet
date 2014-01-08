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
use Platformd\GroupBundle\Entity\Group;
use Platformd\MediaBundle\Entity\Media;
use Platformd\MediaBundle\Form\Type\MediaType;

use Symfony\Component\EventDispatcher\EventDispatcher,
    Symfony\Component\Security\Core\SecurityContextInterface,
    Symfony\Component\Security\Acl\Model\MutableAclProviderInterface as aclProvider,
    Symfony\Component\Security\Acl\Exception\AclAlreadyExistsException,
    Symfony\Component\Security\Acl\Domain\ObjectIdentity,
    Symfony\Component\Security\Acl\Domain\UserSecurityIdentity,
    Symfony\Component\Security\Acl\Permission\MaskBuilder,
    Symfony\Component\Routing\RouterInterface
;

class AdminController extends Controller
{

    public function eventAction(Request $request, $groupSlug, $eventId) {

        // test if submission is from a 'cancel' button press
        $event = $this->getEvent($groupSlug, $eventId);
         
        if ($request->get('cancel') == 'Cancel') {
            if ($eventId == 'newEvent'){
                return $this->redirect($this->generateUrl('group_show', array(
                    'slug'  => $groupSlug,
                )));
            } else {
                return $this->redirect($this->generateUrl('group_event_view', array(
                    'groupSlug' => $groupSlug,
                    'eventId' =>   $eventId,
                )));
            }
        }

        $group = $this->getGroup($groupSlug);

        $isNew = false;

        if (!$event) {
            $event = new GroupEvent($group);
            $isNew = true;
        }

        $form = $this->container->get('form.factory')->createNamedBuilder('form', 'event', $event)
            ->add('name',               'text',             array('attr'    => array('size'  => '60%')))
            ->add('content',            'purifiedTextarea', array('attr'    => array('class' => 'ckeditor')))
            ->add('online',             'choice',           array('choices' => array('1' => 'Yes', '0' => 'No')))
            ->add('private',            'choice',           array('choices' => array('0' => 'No', '1' => 'Yes')))
            ->add('startsAt',           'datetime',         array('attr'    => array('size' => '60%'), 'required' => '0'))
            ->add('endsAt',             'datetime',         array('attr'    => array('size' => '60%'), 'required' => '0'))
            ->add('location',           'text',             array('attr'    => array('size' => '60%'), 'required' => '0'))
            ->add('address1',           'text',             array('attr'    => array('size' => '60%'), 'required' => '0'))
            ->add('address2',           'text',             array('attr'    => array('size' => '60%'), 'required' => '0'))

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

                // Registration needs to be created after event is persisted, relies on generated event ID
                $esReg = $event->getEntrySetRegistration();
                if ($esReg == null){
                    $esReg = $event->createEntrySetRegistration();
                    $em->persist($esReg);
                    $em->flush();
                }

                // ACLs
                $aclProvider = $this->container->get('security.acl.provider');
                $objectIdentity = ObjectIdentity::fromDomainObject($event);
                $securityIdentity = UserSecurityIdentity::fromAccount($event->getUser());
                try {
                    $acl = $aclProvider->createAcl($objectIdentity);
                    $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
                    $aclProvider->updateAcl($acl);
                } catch(AclAlreadyExistsException $e) {}

                return $this->redirect($this->generateUrl('group_event_view', array(
                        'groupSlug' => $groupSlug,
                        'eventId' => $event->getId(),
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

    public function fixEventACLsAction(Request $request) {
        $eventEm = $this->getDoctrine()->getRepository('EventBundle:GroupEvent');
        $aclProvider = $this->container->get('security.acl.provider');

        $output = "";
        $allEvents = $eventEm->findAll();
        $index = 0;
        foreach($allEvents as $event) {
            $index++;

            $objectIdentity = ObjectIdentity::fromDomainObject($event);
            $securityIdentity = new UserSecurityIdentity($event->getUser()->getUsername(),'Platformd\UserBundle\Entity\User');

            try {
                $acl = $aclProvider->createAcl($objectIdentity);
                $output .= $index.'. creating acl for \''.$event->getName().'\': '.$event->getUser()->getUsername().'<br>';
            } catch(AclAlreadyExistsException $e) {
                $acl = $aclProvider->findAcl($objectIdentity,array($securityIdentity));
                $output .= $index.': updating acl for \''.$event->getName().'\': '.$event->getUser()->getUsername().'<br>';
            }
            $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
            $aclProvider->updateAcl($acl);
        }
        $this->setFlash('success', $output);

        return $this->redirect($this->generateUrl('default_index'));
    }

    public function adminAction(Request $request, $groupSlug, $eventId) {

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventId);

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

    public function entrySetAction(Request $request, $entrySetId)
    {
        $esRegRepo = $this->getDoctrine()->getRepository('IdeaBundle:EntrySetRegistry');

        if( $entrySetId == 'new' )
        {
            $entrySet             = new EntrySet();
            $registrationId       = $request->get('registrationId');
            $entrySetRegistration = $esRegRepo->find($registrationId);
            $cancelTarget         = $esRegRepo->getContainer($entrySetRegistration);
        }
        else
        {
            $entrySet             = $this->getDoctrine()->getRepository('IdeaBundle:EntrySet')->find($entrySetId);
            $entrySetRegistration = $entrySet->getEntrySetRegistration();
            $registrationId       = $entrySetRegistration->getId();
            $cancelTarget         = $entrySet;
        }

        if ($request->get('cancel') == 'Cancel') {
            return $this->redirect($this->generateUrl($cancelTarget->getLinkableRouteName(), $cancelTarget->getLinkableRouteParameters()));
        }

        $form = $this->container->get('form.factory')->createNamedBuilder('form', 'entrySet', $entrySet)
            ->add('name',               'text',     array('attr'    => array('size'  => '60%')))
            ->add('type',               'choice',   array('choices' => array(EntrySet::TYPE_IDEA      => 'Ideas',
                                                                             EntrySet::TYPE_SESSION   => 'Sessions',
                                                                             EntrySet::TYPE_THREAD    => 'Threads',)))
            ->add('isSubmissionActive', 'choice',   array('choices' => array('1' => 'Yes', '0' => 'No')))
            ->add('isVotingActive',     'choice',   array('choices' => array('0' => 'No', '1' => 'Yes')))
            ->add('allowedVoters',      'text',     array('max_length' => '5000', 'attr'    => array('size' => '60%', 'placeholder' => 'username1, username2, ...'), 'required' => '0',))
            ->getForm();

        if($request->getMethod() == 'POST') {

            $form->bindRequest($request);

            if($form->isValid()) {


                //validate and clean up allowedVoters
                $validatedJudges = array();
                $candidateJudges = array_map('trim', explode(",", $entrySet->getAllowedVoters()));

                $userRepo = $this->getDoctrine()->getRepository('UserBundle:User');

                foreach($candidateJudges as $candidate) {
                    if($userRepo->findOneBy(array('username' => $candidate)) != null) {
                        $validatedJudges[] = $candidate;
                    }
                }

                $entrySet->setEntrySetRegistration($entrySetRegistration);
                $entrySet->setAllowedVoters(implode(",", $validatedJudges));

                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($entrySet);
                $em->flush();

                $redirectUrl = $this->generateUrl($entrySet->getLinkableRouteName(), $entrySet->getLinkableRouteParameters());
                return $this->redirect($redirectUrl);
            }
        }

        return $this->render('IdeaBundle:Admin:entrySet.html.twig', array(
            'form'           => $form->createView(),
            'entrySetId'     => $entrySetId,
            'registrationId' => $registrationId,
        ));
    }

    public function entrySetDeleteAction($entrySetId)
    {
        $this->enforceUserSecurity();

        $entrySet = $this->getEntrySet($entrySetId);
        $parent   = $this->getParentByEntrySet($entrySet);

        if ($this->canEditEntrySet($entrySet)) {

            $em = $this->getDoctrine()->getEntityManager();
            $em->remove($entrySet);
            $em->flush();

            $this->setFlash('success', 'List \''.$entrySet->getName().'\' has been deleted.');

            $url = $this->generateUrl($parent->getLinkableRouteName(), $parent->getLinkableRouteParameters());
            return $this->redirect($url);
        }
        else {
            throw new AccessDeniedException;
        }
    }

    // Edit requets will provide id using GET
    // New request will not provide id using GET
    // Save request will have displayName and description parameters using POST
    public function criteriaAction(Request $request, $groupSlug, $eventId, $id = null) {

        // test if submission is from a 'cancel' button press
        if($request->get('cancel') == 'Cancel') {
            return $this->redirect($this->generateUrl('idea_admin_criteria_all', array(
                    'groupSlug' => $groupSlug,
                    'eventId' => $eventId,
                )));
        }

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventId);

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
                            'eventId' => $eventId,
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


    public function criteriaListAction(Request $request, $groupSlug, $eventId) {

        // test if submission is from a 'new' button press
        if($request->get('new') == 'New') {
            return $this->redirect($this->generateUrl('idea_admin_criteria', array(
                    'groupSlug' => $groupSlug,
                    'eventId' => $eventId,
                )));
        }

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventId);

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
                            'eventId' => $eventId,
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
                    'eventId' => $eventId,
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

    public function summaryAction(Request $request, $groupSlug, $eventId) {

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventId);
        $entrySets = $event->getEntrySets();

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
        
        $params['criteriaList'] = $vcRepo->findByEventId($this->getEvent($groupSlug, $event->getSlug()));

        //retrieve tag filter parameter
        $tag = $request->query->get('tag');
        $params['tag'] = $tag;

        $currentRound = $event->getCurrentRound();

        $round = $request->query->get('round', $currentRound);
        $params['round'] = $round;
        $params['currentRound'] = $currentRound;

        //perform filter and sort
        $ideaRepo = $this->getDoctrine()->getRepository('IdeaBundle:Idea');

        $ideaList = array();
        foreach($entrySets as $entrySet){
            $ideaList = array_merge($ideaList, $ideaRepo->filter($entrySet, $round, $tag, $this->getCurrentUser()));
        }

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

    public function advanceAction($groupSlug, $eventId) {

        $event = $this->getEvent($groupSlug, $eventId);

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
                'eventId' => $eventId,
            )));
    }

    public function imagesAction($groupSlug, $eventId, Request $request) {

        $newImage = new Media();
        $form = $this->createForm(new MediaType(), $newImage, array('image_label' => 'Image File:'));

        $event = $this->getEvent($groupSlug, $eventId);

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

    public function approvalsAction($groupSlug, $eventId) {

        $event = $this->getEvent($groupSlug, $eventId);
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

    public function processApprovalAction($groupSlug, $eventId, $userId, $approval) {

        $eventId = $this->getEvent($groupSlug, $eventId)->getId();
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
            'eventId' => $eventId,
        )));

    }

    public function removeImageAction($groupSlug, $eventId, $imageId) {

        $image = $this->getDoctrine()->getRepository('MediaBundle:Media')->find($imageId);

        if (!$image) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($image);
        $em->flush();

        return $this->redirect($this->generateUrl('idea_admin_images', array(
            'groupSlug' => $groupSlug,
            'eventId' => $eventId,
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

    public function getEvent($groupSlug, $eventId)
    {
        $group = $this->getGroup($groupSlug);
        
        if (!$group){
            return false;
        }

        $eventEm = $this->getDoctrine()->getRepository('EventBundle:GroupEvent');
        $event = $eventEm->findOneBy(
            array(
                'group' => $group->getId(),
                'id' => $eventId,
            )
        );
        if ($event == null){
            return false;
        }
        return $event;
    }

    public function getParentByIdea($idea){
        $esRegistration = $idea->getParentRegistration();
        $esRegRepo = $this->getDoctrine()->getRepository('IdeaBundle:EntrySetRegistry');

        return $esRegRepo->getContainer($esRegistration);
    }

    public function assignJudgesAction(Request $request, $groupSlug, $eventId, $ideaId)
    {
        $doc = $this->getDoctrine();

        if (!$this->canEditEvent($this->getEvent($groupSlug, $eventId))) {
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
            'eventId' => $eventId,
            'id' => $ideaId,
        )));
    }


    public function getEntrySet($entrySetId)
    {
        $entrySetRepo = $this->getDoctrine()->getRepository('IdeaBundle:EntrySet');
        $entrySet = $entrySetRepo->find($entrySetId);

        if (!$entrySet){
            throw new NotFoundHttpException('Entry Set not found');
        }

        return $entrySet;
    }

    public function getParentByEntrySet($entrySet)
    {
        $parentRegistration = $entrySet->getEntrySetRegistration();
        $esRegRepo = $this->getDoctrine()->getRepository('IdeaBundle:EntrySetRegistry');

        return $esRegRepo->getContainer($parentRegistration);
    }

    public function canEditEntrySet($entrySet)
    {
        $parent = $this->getParentByEntrySet($entrySet);

        if ($parent instanceof GroupEvent){
            return $this->canEditEvent($parent);
        }
        elseif ($parent instanceof Group){
            return ($this->isAdmin() || $parent->isOwner($this->getCurrentUser()) );
        }

        return false;
    }


	public function exportIdeasAction($groupSlug, $eventId) {
        $ideaRepo = $this->getDoctrine()->getRepository('IdeaBundle:Idea');
        $csvString = $ideaRepo->toCSV();

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$eventId.'-ideas.csv"');
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

    public function exportVotesAction($groupSlug, $eventId) {
        $event = $this->getEvent($groupSlug, $eventId);

        $voteRepo = $this->getDoctrine()->getRepository('IdeaBundle:Vote');
        $csvString = $voteRepo->toCSV($event);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$eventId.'-scores.csv"');
        $response->setContent($csvString);
        return $response;
    }
}

