<?php

namespace Platformd\IdeaBundle\Controller;

use DateTime;
use Platformd\EventBundle\Entity\Event;
use Platformd\EventBundle\Entity\EventRsvpAction;
use Platformd\EventBundle\Entity\EventSession;
use Platformd\EventBundle\Entity\GlobalEvent;
use Platformd\EventBundle\Entity\GroupEvent;
use Platformd\EventBundle\Entity\GroupEventRsvpAction;
use Platformd\GroupBundle\Entity\Group;
use Platformd\IdeaBundle\Entity\HtmlPage;
use Platformd\IdeaBundle\Entity\EntrySet;
use Platformd\IdeaBundle\Entity\VoteCriteria;
use Platformd\IdeaBundle\Entity\RegistrationField;
use Platformd\IdeaBundle\Form\Type\RegistrationFieldFormType;
use Platformd\MediaBundle\Entity\Media;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SpoutletBundle\Controller\Controller;
use Platformd\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclAlreadyExistsException;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Doctrine\Common\Collections\ArrayCollection;

class AdminController extends Controller
{
    /**
     * @param Request $request
     * @param string $groupSlug
     * @param int $eventId
     */
    public function eventSessionAction(Request $request, $groupSlug, $eventId, $sessionId = null)
    {
        if ($request->get('cancel') == 'Cancel') {
            return $this->redirect($this->generateUrl('group_event_view', array(
                'groupSlug'  => $groupSlug,
                'eventId'    => $eventId,
            )));
        }

        $event = $this->getEvent($groupSlug, $eventId);

        if (!$event) {
            throw new NotFoundHttpException('Event not found.');
        }

        $isNew = false;

        if ($sessionId) {
            $this->validateAuthorization($event);
            $evtSession = $this->getEventSession($groupSlug, $eventId, $sessionId);
        }
        else {
            $evtSession = new EventSession($event);
            $isNew = true;
        }

        $form = $this->container->get('form.factory')->createNamedBuilder('form', 'evtSession', $evtSession)
            ->add('name',               'text',             array('attr'    => array('class' => 'formRowWidth')))
            ->add('description',        'textarea',         array('attr'    => array('class' => 'formRowWidth', 'rows' => '6')))
            ->add('speaker',            'entity',           array('class'   => 'UserBundle:User',
                                                                  'choices' => $event->getAttendeesAlphabetical(), 'required' => false))
            ->add('speakerBio',         'textarea',         array('attr'    => array('class' => 'formRowWidth', 'rows' => '6')))
            ->add('room',               'text',             array('attr'    => array('class' => 'formRowWidth'), 'required' => false))
            ->add('date',               'date',             array('widget'  => 'single_text',
                                                                  'format'  => 'L/dd/yyyy',
                                                                  'required' => false))
            ->add('startsAt',           'time',             array('widget'  => 'single_text', 'required' => false))
            ->add('endsAt',             'time',             array('widget'  => 'single_text', 'required' => false))
            ->add('slidesLink',         'text',             array('attr'    => array('class' => 'formRowWidth', 'placeholder' => 'http://'),
                                                                  'required' => false))
            ->add('publicNotesLink',    'text',             array('attr'    => array('class' => 'formRowWidth', 'placeholder' => 'http://'),
                                                                  'required' => false))
            ->getForm();

        if ($request->getMethod() == 'POST')
        {
            $form->bindRequest($request);
            $ideaId = $request->get('ideaId');

            if($form->isValid() || $ideaId)
            {
                $em = $this->getDoctrine()->getEntityManager();
                $url = null;

                if ($ideaId){
                    $idea = $em->getRepository('IdeaBundle:Idea')->find($ideaId);
                    if (!$idea){
                        throw new NotFoundHttpException('Idea not found.');
                    }

                    $evtSession->setSourceIdea($idea);
                    $evtSession->setName($idea->getName());
                    $evtSession->setDescription($idea->getDescription());
                    $evtSession->setSpeaker($idea->getCreator());
//                    $evtSession->addTags($idea->getTags());

                    $eventStart = $event->getStartsAt();

                    if ($eventStart) {

                        $sessionDate = new DateTime();
                        $sessionDate->setDate($eventStart->format('Y'),  $eventStart->format('n'), $eventStart->format('d'));
                        $sessionDate->setTime($eventStart->format('H'), $eventStart->format('i'));

                        $evtSession->setDate($sessionDate);
                    }
                    $this->setFlash('success', $evtSession->getName().' has been successfully added to the session schedule.');
                    $url = $this->generateUrl($idea->getEntrySet()->getLinkableRouteName(), $idea->getEntrySet()->getLinkableRouteParameters());
                }
                else {

                    $date = $evtSession->getDate();
                    $year = $date->format('Y');
                    $month = $date->format('n');
                    $day = $date->format('d');

                    // Set date for start and end times from date input
                    $evtSession->getStartsAt()->setDate($year, $month, $day);
                    $evtSession->getEndsAt()->setDate($year, $month, $day);

//                    $tagString = $request->get('tags');

//                    if (!$isNew) {
//                        $evtSession->removeAllTags();
//                    }

//                    $evtSession->addTags($this->getIdeaService()->processTags($tagString));
                }

                if ($isNew){
                    $event->addSession($evtSession);
                    $em->persist($evtSession);
                }

                $em->flush();

                if (!$url){
                    $url = $this->generateUrl($evtSession->getLinkableRouteName(), $evtSession->getLinkableRouteParameters());
                }

                return $this->redirect($url);
            }
        }

        return $this->render('IdeaBundle:Admin:sessionForm.html.twig', array(
            'form'      => $form->createView(),
            'isNew'     => $isNew,
            'group'     => $event->getGroup(),
            'event'     => $event,
            'evtSession'=> $evtSession,
            'breadCrumbs' => $this->getBreadCrumbsString($evtSession),
        ));
    }

    public function eventSessionDeleteAction($groupSlug, $eventId, $sessionId) {

        $event = $this->getEvent($groupSlug, $eventId);

        if (!$event) {
            throw new NotFoundHttpException('Event not found.');
        }

        $this->validateAuthorization($event);

        $evtSession = $this->getEventSession($groupSlug, $eventId, $sessionId);

        if (!$evtSession) {
            throw new NotFoundHttpException('Session not found.');
        }

        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($evtSession);
        $em->flush();

        $this->setFlash('success', 'Session \''.$evtSession->getName().'\' has been deleted.');

        return $this->redirect($this->generateUrl('event_session_schedule', $event->getLinkableRouteParameters()));
    }

    public function importEventbriteEventAction(Request $request, $groupSlug, $ebEventId)
    {
        if (!$this->isAdmin()) {
            throw new AccessDeniedException;
        }

        $event_data = null;
        try {
            $event_data = $this->getIdeaService()->getEventbriteEvent($ebEventId);
        } catch (\Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            return new Response();
        }

        if( $event_data == null ) {
            echo "Response does not contain an event, aborting";
            return new Response();
        }

        $group = $this->getGroup($groupSlug);
        $event = new GroupEvent($group);
        $group->addEvent($event);

        $user  = $this->getCurrentUser();
        $event->setUser($user);
        $event->setActive(true);
        $event->setApproved(true);
        $event->setExternal(0);
        $event->setRegistrationOption(Event::REGISTRATION_DISABLED);

        $title = $event_data['title'];
        if(strlen($title) !== 0) $event->setName($title);

        $start_date = $event_data['start_date'];
        if(strlen($start_date) !== 0) $event->setStartsAt(new \DateTime($start_date));

        $end_date = $event_data['end_date'];
        if(strlen($end_date) !== 0) $event->setEndsAt(new \DateTime($end_date));

        $timezone = $event_data['timezone'];
        if(strlen($timezone) !== 0) $event->setTimezone($timezone);

        $description = $event_data['description'];
        if(strlen($description) !== 0) $event->setContent($description);

        $tags = $event_data['tags'];
        if(strlen($tags) !== 0) $event->setEBTags($tags);


        $venue = $event_data['venue'];

        $venue_name = $venue['name'];
        if(strlen($venue_name) !== 0) $event->setLocation($venue_name);

        $venue_address1 = $venue['address'];
        if(strlen($venue_address1) !== 0) $event->setAddress1($venue_address1);

        $venue_address2 = $venue['address_2'];
        if(strlen($venue_address2) !== 0) $event->setAddress2($venue_address2);

        $longitude = $venue['longitude'];
        if(strlen($longitude) !== 0) $event->setLongitude(''.$longitude);

        $latitude = $venue['latitude'];
        if(strlen($latitude) !== 0) $event->setLatitude(''.$latitude);

        $city = $venue['city'];
        if(strlen($city) !== 0) $event->setCity($city);

        $state = $venue['region'];
        if(strlen($state) !== 0) $event->setState($state);

        $country = $venue['country'];
        if(strlen($country) !== 0) $event->setCountry($country);

        $postal_code = $venue['postal_code'];
        if(strlen($postal_code) !== 0) $event->setPostalCode($postal_code);

        $this->getGroupEventService()->createEvent($event, false);

        $em = $this->getDoctrine()->getEntityManager();
        $em->persist($event);
        $em->flush();

        // Registration needs to be created after event is persisted, relies on generated event ID
        $esReg = $event->createEntrySetRegistration();
        $em->persist($esReg);

        $this->getGroupEventService()->register($event, $event->getUser());
        $em->flush();

        return $this->redirect($this->generateUrl($event->getLinkableRouteName(), $event->getLinkableRouteParameters()));
    }

    public function eventAction(Request $request, $groupSlug, $eventId)
    {
        // test if submission is from a 'cancel' button press
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
        $event = $this->getEvent($groupSlug, $eventId);

        $originalRegistrationFields = new ArrayCollection();

        if (!$event) {
            $isNew = true;
            $event = new GroupEvent($group);

            $regQ1 = new RegistrationField('What questions do you have?');
            $regQ2 = new RegistrationField('What topics are you interested in?');
            $regQ3 = new RegistrationField('Do you want to receive information about our sponsors?', RegistrationField::TYPE_CHECKBOX);

            $event->addRegistrationField($regQ1);
            $event->addRegistrationField($regQ2);
            $event->addRegistrationField($regQ3);

            $event->setNoDate(true);

        }
        else {
            $this->validateAuthorization($event);

            $isNew = false;
            foreach ($event->getRegistrationFields() as $field){
                $originalRegistrationFields->add($field);
            }
        }

        $form = $this->container->get('form.factory')->createNamedBuilder('form', 'event', $event)
            ->add('name',               'text',             array('attr'    => array('size'  => '60%')))
            ->add('content',            'purifiedTextarea', array('attr'    => array('class' => 'ckeditor')))
            ->add('noDate',             'checkbox',         array('required' => false))
            ->add('startsAt',           'datetime',         array())
            ->add('endsAt',             'datetime',         array())
            ->add('external',           'choice',           array('choices' => array('1' => 'No', '0' => 'Yes')))
            ->add('registrationOption', 'choice',           array('choices' => array(Event::REGISTRATION_ENABLED   => 'Enabled',
                                                                                     Event::REGISTRATION_DISABLED  => 'Disabled',)))
            ->add('registrationFields', 'collection',       array('type'            => new RegistrationFieldFormType(),
                                                                  'allow_add'       => true,
                                                                  'allow_delete'    => true,
                                                                  'by_reference'    => false))
            ->add('externalUrl',        'text',             array('attr'    => array('size' => '60%', 'placeholder' => 'http://')))
            ->add('location',           'text',             array('attr'    => array('size' => '60%'), 'required' => '0'))
            ->add('address1',           'text',             array('attr'    => array('size' => '60%'), 'required' => '0'))
            ->add('address2',           'text',             array('attr'    => array('size' => '60%'), 'required' => '0'))
            ->add('online',             'checkbox',         array('required'=> false))
            ->add('private',            'checkbox',         array('required'=> false))
            ->getForm();

        if($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            if($form->isValid()) {

                if ($event->isExternal()) {
                    $event->setRegistrationOption(Event::REGISTRATION_DISABLED);
                }

                $em = $this->getDoctrine()->getEntityManager();

                if ($isNew) {

                    $group->addEvent($event);

                    $event->setUser($this->getCurrentUser());
                    $event->setTimezone('UTC');
                    $event->setActive(true);
                    $event->setApproved(true);
                    $em->persist($event);
                    $em->flush();

                    // Registration needs to be created after event is persisted, relies on generated event ID
                    $esReg = $event->createEntrySetRegistration();
                    $em->persist($esReg);

                    $this->getGroupEventService()->register($event, $event->getUser());
                }
                else {
                    // If the form no longer contains fields that were in the original list, delete them
                    foreach ($originalRegistrationFields as $field) {
                        if ($event->getRegistrationFields()->contains($field) === false) {
                            $em->remove($field);
                        }
                    }
                }

                $em->flush();

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
            else {
                $errorString = '';
                foreach ($form->getErrors() as $key => $error) {
                    $template = $error->getMessageTemplate();
                    $parameters = $error->getMessageParameters();

                    foreach($parameters as $var => $value){
                        $template = str_replace($var, $value, $template);
                    }

                    $errorString .= $template.'<br/>';
                }
                if (!$errorString) {
                    $errorString = 'Please see fields below for errors';
                }
                $this->setFlash('error', $errorString);
            }
        }

        return $this->render('IdeaBundle:Admin:eventForm.html.twig', array(
                'form'      => $form->createView(),
                'isNew'     => $isNew,
                'group'     => $group,
                'event'     => $event,
                'isAdmin'   => $this->isGranted('ROLE_ADMIN'),
            ));
    }

    public function globalEventAction(Request $request, $global_eventId) {

        if ($global_eventId == 'new') {
            $event = new GlobalEvent();
            $isNew = true;
            $event->setNoDate(true);
        } else {
            $event = $this->getGlobalEventService()->find($global_eventId);
            $this->validateAuthorization($event);
            $isNew = false;
        }

        $form = $this->container->get('form.factory')->createNamedBuilder('form', 'event', $event)
            ->add('name',               'text',             array('attr'    => array('size'  => '60%')))
            ->add('content',            'purifiedTextarea', array('attr'    => array('class' => 'ckeditor')))
            ->add('noDate',             'checkbox',         array('required' => false))
            ->add('externalUrl',        'text',             array('attr'    => array('size' => '60%', 'placeholder' => 'http://'), 'required' => true))
            ->add('startsAt',           'datetime',         array())
            ->add('endsAt',             'datetime',         array())
            ->add('external',           'choice',           array('choices' => array('1' => 'No', '0' => 'Yes')))
            ->add('registrationOption', 'choice',           array('choices' => array(Event::REGISTRATION_ENABLED   => 'Enabled',
                                                                                     Event::REGISTRATION_DISABLED  => 'Disabled',)))
            ->add('externalUrl',        'text',             array('attr'    => array('size' => '60%', 'placeholder' => 'http://')))
            ->add('location',           'text',             array('attr'    => array('size' => '60%'), 'required' => '0'))
            ->add('address1',           'text',             array('attr'    => array('size' => '60%'), 'required' => '0'))
            ->add('address2',           'text',             array('attr'    => array('size' => '60%'), 'required' => '0'))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            
            if ($event->isExternal()) {
                $event->setRegistrationOption(Event::REGISTRATION_DISABLED);
            }

            if ($event->getLocation() || $event->getAddress1() || $event->getAddress2()) {
                $event->setOnline(false);
            } else {
                $event->setOnline(true);
            }

            if ($form->isValid()) {

                $em = $this->getDoctrine()->getEntityManager();


                if ($isNew) {
                    $event->setUser($this->getCurrentUser());
                    $event->setTimezone('UTC');
                    $event->setActive(true);
                    $event->setApproved(true);
                    $event->setPublished(true);
                    $event->addSite($this->getCurrentSite());

                    $this->getGlobalEventService()->createEvent($event);

                    // Registration needs to be created after event is persisted, relies on generated event ID
                    $esReg = $event->createEntrySetRegistration();
                    $em->persist($esReg);
                    
                    $flashMessage = 'New event posted successfully!';

                } else {
                    $this->getGlobalEventService()->updateEvent($event);
                    $flashMessage = 'Event successfully updated!';
                }

                $em->flush();

                $this->setFlash('success', $flashMessage);

                return $this->redirect($this->generateUrl('global_event_view', array(
                        'id' => $event->getId(),
                )));
            }
        }

        return $this->render('IdeaBundle:Admin:globalEventForm.html.twig', array(
            'form'      => $form->createView(),
            'event'     => $event,
            'isNew'     => $isNew,
        ));
    }

    public function adminAction(Request $request, $groupSlug, $eventId) {

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventId);

        $this->validateAuthorization($event);

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Admin:admin.html.twig', array(
                'group'     => $group,
                'event'     => $event,
                'isAdmin'   => $isAdmin,
            ));
    }

    public function htmlPageFormAction(Request $request, $id = null)
    {
        $this->enforceUserSecurity();

        if ($id) {
            $htmlPage = $this->getDoctrine()->getRepository('IdeaBundle:HtmlPage')->find($id);
            if (!$htmlPage) {
                throw new NotFoundHttpException();
            }
        } else {
            $htmlPage = new HtmlPage();
        }

        $scope       = $request->get('scope');
        $containerId = $request->get('containerId');

        $group = null;
        $event = null;
        $owner = null;

        if ($scope == 'group') {
            $group = $this->getGroupManager()->find($containerId);
            $owner = $group->getOwner();
        } elseif ($scope == 'event') {
            $event = $this->getDoctrine()->getRepository('EventBundle:GroupEvent')->find($containerId);
            $owner = $event->getUser();
        } else {
            throw new NotFoundHttpException('A page must be submitted within the context of a group or event.');
        }

        if ($this->getCurrentUser() !== $owner and !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException;
        }

        $form = $this->container->get('form.factory')->createNamedBuilder('form', 'htmlPage', $htmlPage)
            ->add('title',   'text',             array('attr'    => array('size' => '60%')))
            ->add('content', 'purifiedTextarea', array('attr'    => array('class' => 'ckeditor')))
        ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            if ($form->isValid()) {

                $htmlPage->setCreator($this->getCurrentUser());

                if ($group) {
                    $htmlPage->setGroup($group);
                } elseif ($event) {
                    $htmlPage->setEvent($event);
                }

                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($htmlPage);
                $em->flush();

                return $this->redirect($this->generateUrl('page_view', array('id' => $htmlPage->getId())));
            }
        }

        return $this->render('IdeaBundle:Admin:htmlPageForm.html.twig', array(
            'id'            => $id,
            'scope'         => $scope,
            'containerId'   => $containerId,
            'htmlPage'      => $htmlPage,
            'form'          => $form->createView(),
        ));
    }

    public function htmlPageDeleteAction(Request $request, $id) {

        $htmlPage = $this->getDoctrine()->getRepository('IdeaBundle:HtmlPage')->find($id);

        if (!$htmlPage) {
            throw new NotFoundHttpException('Page not found.');
        }

        $this->validateAuthorization($htmlPage);

        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($htmlPage);
        $em->flush();

        $this->setFlash('success', 'Page \''.$htmlPage->getTitle().'\' has been deleted.');

        $parent = $htmlPage->getParent();

        return $this->redirect($this->generateUrl($parent->getLinkableRouteName(), $parent->getLinkableRouteParameters()));
    }

    public function entrySetAction(Request $request, $entrySetId)
    {
        $this->enforceUserSecurity();
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
            ->add('name',               'text',     array('attr'    => array('style' => 'width:60%')))
            ->add('type',               'choice',   array('choices' => array(EntrySet::TYPE_IDEA      => 'Ideas',
                                                                             EntrySet::TYPE_SESSION   => 'Sessions',
                                                                             EntrySet::TYPE_THREAD    => 'Threads',
                                                                             EntrySet::TYPE_TASK      => 'Tasks',)))
            ->add('description',        'textarea', array('attr'    => array('style' => 'width:60%')))
            ->add('isSubmissionActive', 'choice',   array('choices' => array('1' => 'Yes', '0' => 'No')))
            ->add('isVotingActive',     'choice',   array('choices' => array('0' => 'No', '1' => 'Yes')))
            ->add('allowedVoters',      'text',     array('max_length' => '5000', 'attr'    => array('style' => 'width:60%', 'placeholder' => 'username1, username2, ...'), 'required' => '0',))
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

                $entrySet->setCreator($this->getCurrentUser());
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

        $this->validateAuthorization($event);

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

        $this->validateAuthorization($event);

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

        $this->validateAuthorization($event);

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

        $this->validateAuthorization($event);

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

        $this->validateAuthorization($event);

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $params = array(
            'group'       => $this->getGroup($groupSlug),
            'event'       => $event,
            'breadCrumbs' => $this->getBreadCrumbsString($event, true),
            'form'        => $form->createView(),
            'isAdmin'     => $isAdmin,
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

        $this->validateAuthorization($event);

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
            'breadCrumbs'       => $this->getBreadCrumbsString($event, true),
            'awaitingApproval'  => $awaitingApproval,
            'isAdmin'           => $isAdmin,
        );

        return $this->render('IdeaBundle:Admin:approvals.html.twig', $params);
    }

    public function processApprovalAction($groupSlug, $eventId, $userId, $action) {

        $event = $this->getEvent($groupSlug, $eventId);

        $this->validateAuthorization($event);

        $user = $this->getDoctrine()->getRepository('UserBundle:User')->findOneBy(array('id'=>$userId));

        $rsvpRepo = $this->getDoctrine()->getRepository('EventBundle:GroupEventRsvpAction');
        $userRsvpActions = $rsvpRepo->findBy(
            array('user' => $userId,'event' => $eventId),
            array('updatedAt' => 'DESC')
        );
        $userRsvpStatus = reset($userRsvpActions);

        $em = $this->getDoctrine()->getEntityManager();

        if ($userRsvpStatus){
            if ($action == 'approve'){

                $newRsvp = new GroupEventRsvpAction();
                $newRsvp->setUser($user);
                $newRsvp->setEvent($event);
                $newRsvp->setRsvpAt(new DateTime('now'));
                $newRsvp->setAttendance(EventRsvpAction::ATTENDING_YES);
                $em->persist($newRsvp);
                $em->flush();
            }
            else {
                $this->getGroupEventService()->unregister($event, $user, true);
            }
            $this->setFlash('success', $user->getName().' has been '.$action.'ed for the event.');
        } else {
            $this->setFlash('error', $user->getName().' is not attending this event.');
        }

        return $this->redirect($this->generateUrl('idea_admin_member_approvals', array(
            'groupSlug' => $groupSlug,
            'eventId' => $eventId,
        )));

    }

    public function removeImageAction($groupSlug, $eventId, $imageId) {

        $this->validateAuthorization($this->getEvent($groupSlug, $eventId));

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

    public function feedbackAction() {

        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }

        $siteId = $this->getCurrentSite()->getId();
        $siteRegistry = $this->getDoctrine()->getRepository('IdeaBundle:EntrySetRegistry')->findOneBy(array('scope'=>'SpoutletBundle:Site','containerId'=>$siteId));

        $feedbackLists = $siteRegistry->getEntrySets();

        $processedLists = array();
        $feedbackEntries = array();

        foreach ($feedbackLists as $list) {
            $incompleteEntries = $list->getIncompleteEntries();

            if (count($incompleteEntries) > 0) {
                $processedLists[] = $list;
                foreach ($incompleteEntries as $entry) {
                    $feedbackEntries[] = $entry;
                }
            }
        }

        usort($processedLists, function ($a, $b) {
            return ($b->getNumIncompleteEntries() - $a->getNumIncompleteEntries());
        });
        usort($feedbackEntries, function ($a, $b) {
            return ($b->getCreatedAt()->getTimeStamp() - $a->getCreatedAt()->getTimeStamp());
        });

        $recentFeedback = array_slice($feedbackEntries, 0, 6);
        
        return $this->render('IdeaBundle:Admin:feedback.html.twig', array('feedbackLists' => $processedLists, 'recentFeedback' => $recentFeedback));
    }


    //------------------------ Helper Functions -----------------------------------

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
        $event = $this->getDoctrine()->getRepository('EventBundle:GroupEvent')->find($eventId);
        
        if ($event == null) {
            return false;
        }
        return $event;
    }

    public function getEventSession($groupSlug, $eventId, $sessionId)
    {
        $event = $this->getEvent($groupSlug, $eventId);

        if (!$event){
            return false;
        }

        $evtSession = $this->getDoctrine()->getRepository('EventBundle:EventSession')->find($sessionId);

        if ($evtSession == null){
            return false;
        }

        return $evtSession;
    }

    public function assignJudgesAction(Request $request, $groupSlug, $eventId, $ideaId)
    {
        $doc = $this->getDoctrine();

        $this->validateAuthorization($this->getEvent($groupSlug, $eventId));

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

        $this->validateAuthorization($this->getEvent($groupSlug, $eventId));

        $ideaRepo = $this->getDoctrine()->getRepository('IdeaBundle:Idea');
        $csvString = $ideaRepo->toCSV();

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$eventId.'-ideas.csv"');
        $response->setContent($csvString);
        return $response;
    }

    public function exportUsersAction($groupSlug, $eventId) {

        $this->validateAuthorization($this->getEvent($groupSlug, $eventId));

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
        $this->validateAuthorization($event);

        $voteRepo = $this->getDoctrine()->getRepository('IdeaBundle:Vote');
        $csvString = $voteRepo->toCSV($event);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$eventId.'-scores.csv"');
        $response->setContent($csvString);
        return $response;
    }

    // Admin Scripts

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

    public function addEntrySetCreatorsAction(Request $request){

        $em = $this->getDoctrine()->getEntityManager();
        $entrySets = $this->getDoctrine()->getRepository('IdeaBundle:EntrySet')->findAll();

        $output = '';

        foreach ($entrySets as $entrySet){

            $parent = $this->getParentByEntrySet($entrySet);
            $parentOwner = null;

            if ($parent instanceof GroupEvent){
                $parentOwner = $parent->getUser();
            }
            elseif ($parent instanceof Group){
                $parentOwner = $parent->getOwner();
            }

            if ($parentOwner){
                $entrySet->setCreator($parentOwner);
                $output = $output.$entrySet->getName().'\'s creator is now '.$parentOwner->getName().'<br/>';
            }
        }
        if ($output){
            $em->flush();
            $this->setFlash('success', $output);
        }

        return $this->redirect($this->generateUrl('default_index'));
    }

    public function addEntrySetRegistrationsToAllEventsAction()
    {
        $em = $this->getDoctrine()->getEntityManager();

        $groupEvents = $this->getDoctrine()->getRepository('EventBundle:GroupEvent')->findAll();
        $globalEvents = $this->getDoctrine()->getRepository('EventBundle:GlobalEvent')->findAll();

        $events = array_merge($groupEvents, $globalEvents);

        $output = '';

        foreach ($events as $event) {
            if (!$event->getEntrySetRegistration()) {
                $esReg = $event->createEntrySetRegistration();
                $em->persist($esReg);
                $output .= $event->getName().'<br/>';
            }
        }

        if ($output) {
            $em->flush();
            $output = 'Creating EntrySet Registrations for: <br/>'.$output;
        } else {
            $output = 'No worries, your events are already valid.';
        }

        $this->setFlash('success', $output);
        return $this->redirect($this->generateUrl('default_index'));
    }

    public function createDepartmentsForAllExistingSponsorsAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $sponsors = $this->getDoctrine()->getRepository('IdeaBundle:Sponsor')->findAll();

        $groupRepo = $this->getDoctrine()->getRepository('GroupBundle:Group');
        $gm = $this->getGroupManager();

        $output = '';

        foreach ($sponsors as $sponsor) {

            // Skip any sponsor that already has a department associated to it
            if ($sponsor->getDepartment()) {
                continue;
            }

            $companyName = $sponsor->getName();
            $deptName    = $companyName.' Marketing';

            // Check to see if company already exists
            $company = $groupRepo->findOneBy(array('name'=>$companyName));

            // Otherwise create it
            if (!$company) {
                $company = new Group();
                $company->setName($companyName);
                $company->setDescription($companyName);
                $company->setOwner($sponsor->getCreator());
                $company->setGroupAvatar($sponsor->getImage());
                $company->setCategory(Group::CAT_COMPANY);
                $company->getSites()->add($this->getCurrentSite());

                $this->getGroupManager()->saveGroup($company);

                $esReg = $company->createEntrySetRegistration();
                $em->persist($esReg);

                $output .= ' Creating: '.$companyName.'<br/>';
            }

            // Check if department already exists
            $dept = $groupRepo->findOneBy(array('name'=>($deptName)));

            // Otherwise create it
            if (!$dept) {
                $dept = new Group();
                $dept->setName($deptName);
                $dept->setDescription($deptName.' Department');
                $dept->setOwner($sponsor->getCreator());
                $dept->setGroupAvatar($sponsor->getImage());
                $dept->setCategory(Group::CAT_DEPARTMENT);
                $dept->getSites()->add($this->getCurrentSite());
                $dept->setExternalUrl($sponsor->getUrl());
                $dept->setParent($company);

                $this->getGroupManager()->saveGroup($dept);

                $esReg = $dept->createEntrySetRegistration();
                $em->persist($esReg);

                $output .= ' Creating: '.$deptName.'<br/>';
            }

            // If the department is not already associated to a sponsor, attach it to this one
            if (!$dept->getSponsor()) {
                $sponsor->setDepartment($dept);
                $output .= ' Linking Sponsor to '.$deptName.'<br/>';
            }
        }

        if ($output) {
            $em->flush();
            $output = '<strong>Processing Sponsors: </strong><br/><br/>'.$output;
        } else {
            $output = 'No worries, your sponsors are already attached to departments.';
        }

        $this->setFlash('success', $output);
        return $this->redirect($this->generateUrl('default_index'));
    }
}
