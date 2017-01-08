<?php

namespace Platformd\IdeaBundle\Controller;

use Platformd\EventBundle\Entity\GroupEvent;
use Platformd\GroupBundle\Entity\Group;
use Platformd\IdeaBundle\Entity\Comment;
use Platformd\IdeaBundle\Entity\EntrySet;
use Platformd\IdeaBundle\Entity\FollowMapping;
use Platformd\IdeaBundle\Entity\Idea;
use Platformd\IdeaBundle\Entity\Link;
use Platformd\IdeaBundle\Entity\SponsorRegistry;
use Platformd\IdeaBundle\Entity\Vote;
use Platformd\IdeaBundle\Entity\Sponsor;
use Platformd\IdeaBundle\Entity\RegistrationAnswer;
use Platformd\IdeaBundle\Entity\RegistrationField;
use Platformd\IdeaBundle\Entity\EventRecommendation;
use Platformd\IdeaBundle\Entity\GroupRecommendation;
use Platformd\IdeaBundle\Entity\WatchedEventMapping;
use Platformd\IdeaBundle\Entity\WatchedGroupMapping;
use Platformd\IdeaBundle\Entity\IdeaSpeaker;
use Platformd\SpoutletBundle\Controller\Controller;
use Platformd\MediaBundle\Entity\Media;
use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use DateTime;

class IdeaController extends Controller
{
    const SIDEBAR_NONE = 0;
    const SIDEBAR_JUDGE = 1;
    const SIDEBAR_ADMIN = 2;


    public function entrySetViewAction(Request $request, $entrySetId)
    {
        $entrySet = $this->getEntrySet($entrySetId);

        list($group, $event, $entrySet, $idea) = $this->getHierarchy($entrySet);

        $tag = $request->query->get('tag');
        $viewPrivate = $request->query->get('viewPrivate', false);
        $sortBy = $request->query->get('sortBy', 'vote');
        $showAllRounds = $request->query->get('showAllRounds', 'false');
        $viewCompleted = $request->query->get('viewCompleted', false);

        //filter the idea list using the query parameters
        $userParam = $viewPrivate ? $this->getCurrentUser() : null;
        $round = null;

        $canSubmit = $entrySet->getIsSubmissionActive();
        $isAdmin = $this->isAuthorized($entrySet);

        if ($event) {
            $round = $event->getCurrentRound();
            $roundParam = $showAllRounds == 'true' ? null : $round;
            $canSubmit = $canSubmit && ($event->isUserAttending($this->getCurrentUser()) || $isAdmin);
        } else {
            $roundParam = null;
            if ($group) {
                $canSubmit = $canSubmit && ($group->isMember($this->getCurrentUser()) || $isAdmin);
            }
        }

        $ideaRepo = $this->getDoctrine()->getRepository('IdeaBundle:Idea');
        $ideaList = $ideaRepo->filter($entrySet, $roundParam, $tag, $userParam, $isAdmin);


        // For admin remove the public ideas from the full list to just show private ideas
        if ($viewPrivate && $isAdmin) {
            $publicList = $ideaRepo->filter($entrySet, $roundParam, $tag, null);
            foreach ($publicList as $publicIdea) {
                $index = array_search($publicIdea, $ideaList);
                unset($ideaList[$index]);
            }
        }

        if ($sortBy == 'vote') {
            $ideaRepo->sortByFollows($ideaList);
        } else if ($sortBy == 'createdAt') {
            $ideaRepo->sortByCreatedAt($ideaList);
        }

        $attendance = $this->getCurrentUserApproved($entrySet);

        $params = array(
            'group' => $group,
            'event' => $event,
            'entrySet' => $entrySet,
            'ideas' => $ideaList,
            'breadCrumbs' => $this->getBreadCrumbsString($entrySet),
            'round' => $round,
            'canSubmit' => $canSubmit,
            'tag' => $tag,
            'sidebar' => true,
            'attendance' => $attendance,
            'viewPrivate' => $viewPrivate,
            'viewCompleted' => $viewCompleted,
            'sortBy' => $sortBy,
            'isAdmin' => $isAdmin,
            'isJudge' => $this->isJudge($entrySet),
            'showAllRounds' => $showAllRounds,
        );

        return $this->render('IdeaBundle:Idea:entrySetView.html.twig', $params);
    }


    public function showAction($entrySetId, $entryId)
    {
        $idea = $this->getEntry($entryId);
        list($group, $event, $entrySet, $idea) = $this->getHierarchy($idea);

        $attendance = $this->getCurrentUserApproved($entrySet);
        $isAdmin = $this->isAuthorized($idea);

        $params = array(
            'group' => $group,
            'event' => $event,
            'entrySet' => $entrySet,
            'idea' => $idea,
            'breadCrumbs' => $this->getBreadCrumbsString($idea),
            'canEdit' => $this->canEditIdea($entrySet, $idea),
            'canRemoveComments' => $this->canRemoveComment($idea),
            'attendance' => $attendance,
            'isAdmin' => $isAdmin,
        );


        // Do vote sidebar stuff
        $sidebarState = $this->getSidebarState($entrySet, $idea);

        //Disable Judge mode if no criteria defined yet
        if ($event) {
            $currentRound = $event->getCurrentRound();
            $criteriaList = $this->getDoctrine()->getRepository('IdeaBundle:VoteCriteria')->findByEventId($event->getId());
            if ($sidebarState == IdeaController::SIDEBAR_JUDGE && count($criteriaList) <= 0) {
                $sidebarState = IdeaController::SIDEBAR_NONE;
            }
        } else {
            $sidebarState = IdeaController::SIDEBAR_NONE;
        }

        //pass state into twig
        $params['sidebarState'] = $sidebarState;

        $user = $this->getCurrentUser();

        $doctrine = $this->getDoctrine();
        $ideaRepo = $doctrine->getRepository('IdeaBundle:Idea');

        //For Admin sidebar
        if ($sidebarState == IdeaController::SIDEBAR_ADMIN) {

            $ideas = $ideaRepo->filter($entrySet, $currentRound, null, $user, $isAdmin);

            // determine previous idea, next idea
            $ideaRepo->sortByFollows($ideas);

            list($previousIdea, $nextIdea) = $this->findNextAndPrevious($ideas, $idea);

            if ($nextIdea) {
                $params['next'] = $nextIdea->getId();
            }
            if ($previousIdea) {
                $params['previous'] = $previousIdea->getId();
            }

            $userRepo = $doctrine->getRepository('UserBundle:User');

            //Get list of event judges and populate form widget
            $choices = array();
            $allowedVoterString = $entrySet->getAllowedVoters();
            if ($allowedVoterString != "") {
                $allowedVoters = array_map('trim', explode(",", $allowedVoterString));
                foreach ($allowedVoters as $voter) {
                    $choices[$userRepo->findOneBy(array('username' => $voter))->getName()] = $voter;
                }
            }

            $selected = array();
            foreach ($idea->getJudges() as $judge) {
                $selected[] = array_search($judge->getName(), $choices);
            }

            $numRows = count($choices) <= 20 ? count($choices) : 20;
            $formAttributes = array('multiple' => 'true', 'style' => 'width: 100%', 'size' => $numRows);
            $choiceOptions = array(
                'choices' => $choices,
                'attr' => $formAttributes,
                'multiple' => 'true',
                'data' => $selected,
                'choices_as_values' => true,
            );
            $form = $this->container->get('form.factory')->createNamedBuilder('judgeAssignment', FormType::class)
                ->add('judges', ChoiceType::class, $choiceOptions)
                ->getForm();

            $params['form'] = $form->createView();

        } elseif ($sidebarState == IdeaController::SIDEBAR_JUDGE) {
            // determine previous idea, next idea
            $ideas = $ideaRepo->filter($event, $currentRound, null, $user, $isAdmin);
            $ideaRepo->sortByFollows($ideas);

            list($previousIdea, $nextIdea) = $this->findNextAndPrevious($ideas, $idea);

            if ($nextIdea) {
                $params['next'] = $nextIdea->getId();
            }
            if ($previousIdea) {
                $params['previous'] = $previousIdea->getId();
            }

            //Pass all VoteCriteria to template for rendering
            $params['criteriaList'] = $criteriaList;

            //Pass previous vote values to the template keyed by category

            $userName = $user->getUsername();

            $voteRepo = $doctrine->getRepository('IdeaBundle:Vote');

            $votes = $voteRepo->findBy(array('idea' => $idea->getId(), 'voter' => $userName, 'round' => $currentRound));

            if (count($votes) > 0) {
                $valuesByCriteria = array();
                foreach ($votes as $criteriaVote) {
                    $valuesByCriteria[strval($criteriaVote->getCriteria()->getId())] = $criteriaVote->getValue();
                }
                $params['values'] = $valuesByCriteria;
            }
        }
        return $this->render('IdeaBundle:Idea:show.html.twig', $params);
    }


    public function createFormAction($entrySetId)
    {

        $this->enforceUserSecurity();

        $entrySet = $this->getEntrySet($entrySetId);
        list($group, $event, $entrySet, $idea) = $this->getHierarchy($entrySet);

        $attendance = $this->getCurrentUserApproved($entrySet);
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Idea:createForm.html.twig', array(
            'parent' => $this->getParentByEntrySet($entrySet),
            'entrySet' => $entrySet,
            'breadCrumbs' => $this->getBreadCrumbsString($entrySet, true),
            'sidebar' => true,
            'attendance' => $attendance,
            'isAdmin' => $isAdmin,
        ));
    }

    public function createAction(Request $request, $entrySetId)
    {

        $this->enforceUserSecurity();

        $entrySet = $this->getEntrySet($entrySetId);
        $parent = $this->getParentByEntrySet($entrySet);

        if (!$this->canCreate($entrySet)) {
            return new RedirectResponse($this->generateUrl('entry_set_view', array(
                'entrySetId' => $entrySetId,
            )));
        }

        $params = $request->request->all();

        $idea = new Idea();

        $idea->setName($params['title']);
        $idea->setCreator($this->getCurrentUser());
        $idea->setEntrySet($entrySet);
        $idea->setDescription($params['desc']);

        if (array_key_exists('members', $params)) {
            $idea->setMembers($params['members']);
        }

        if (array_key_exists('stage', $params)) {
            $idea->setStage($params['stage']);
        }

        if (array_key_exists('forCourse', $params)) {
            $idea->setForCourse(true);
            $idea->setProfessors($params['professors']);
        } else {
            $idea->setForCourse(false);
        }

        if (array_key_exists('amount', $params)) {
            if (!empty($params['amount'])) {
                $idea->setAmount($params['amount']);
            }
        }

        $idea->addTags($this->getIdeaService()->processTags($params['tags']));

        if (isset($params['isPrivate'])) {
            $idea->setIsPrivate(true);
        }

        if ($parent instanceof GroupEvent) {
            $idea->setHighestRound($parent->getCurrentRound());
        } else {
            $idea->setHighestRound(1);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($idea);
        $em->flush();

        $this->joinIdeaScope($idea);

        $profileUrl = $this->generateUrl('profile');

        $this->setFlash('success', 'Your ' . $entrySet->getType() . ' has been successfully proposed. Make sure your <a href="' . $profileUrl . '" class="blu">profile</a> is up to date.');

        $ideaUrl = $this->generateUrl('idea_show', array(
            'entrySetId' => $entrySetId,
            'entryId' => $idea->getId(),
        ));
        return new RedirectResponse($ideaUrl);
    }

    public function editFormAction($entrySetId, $entryId)
    {

        $this->enforceUserSecurity();

        $idea = $this->getEntry($entryId);
        list($group, $event, $entrySet, $idea) = $this->getHierarchy($idea);

        if (!$this->canEditIdea($entrySet, $idea)) {
            throw new AccessDeniedException();
        }

        $attendance = $this->getCurrentUserApproved($entrySet);
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Idea:createForm.html.twig', array(
            'parent' => $this->getParentByEntrySet($entrySet),
            'entrySet' => $entrySet,
            'idea' => $idea,
            'breadCrumbs' => $this->getBreadCrumbsString($idea),
            'sidebar' => true,
            'attendance' => $attendance,
            'isAdmin' => $isAdmin,
        ));
    }


    public function editAction($entrySetId, $entryId)
    {
        $this->enforceUserSecurity();

        $idea = $this->getEntry($entryId);
        $entrySet = $this->getEntrySet($entrySetId);

        if (!$this->canEditIdea($entrySet, $idea)) {
            throw new AccessDeniedException();
        }

        $params = $this->getRequest()->request->all();

        $idea->setName($params['title']);
        $idea->setDescription($params['desc']);

        if (array_key_exists('members', $params)) {
            $idea->setMembers($params['members']);
        }

        if (array_key_exists('stage', $params)) {
            $idea->setStage($params['stage']);
        }

        if (array_key_exists('forCourse', $params)) {
            $idea->setForCourse(true);
            $idea->setProfessors($params['professors']);
        } else {
            $idea->setForCourse(false);
        }

        if (array_key_exists('amount', $params)) {
            if (!empty($params['amount'])) {
                $idea->setAmount($params['amount']);
            }
        }

        $idea->removeAllTags();
        $idea->addTags($this->getIdeaService()->processTags($params['tags']));

        if (isset($params['isPrivate'])) {
            $idea->setIsPrivate(true);
        } else {
            $idea->setIsPrivate(false);
        }

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        $this->joinIdeaScope($idea);

        $ideaUrl = $this->generateUrl('idea_show', array(
            'entrySetId' => $entrySetId,
            'entryId' => $entryId,
        ));
        return new RedirectResponse($ideaUrl);
    }

    public function toggleCompletedAction($entrySetId, $entryId)
    {
        $user = $this->getCurrentUser();

        $entrySet = $this->getEntrySet($entrySetId);
        $entry = $this->getEntry($entryId);

        if (!$this->canEditEntrySet($entrySet)) {
            throw new AccessDeniedException();
        }

        $entry->setCompleted(!$entry->getCompleted());

        $this->getDoctrine()->getManager()->flush();

        return $this->redirect($this->generateUrl('entry_set_view', array('entrySetId' => $entrySetId)));
    }

    public function joinIdeaScope(Idea $idea)
    {
        $user = $this->getUser();

        $esRegRepo = $this->getDoctrine()->getRepository('IdeaBundle:EntrySetRegistry');
        $containerScope = $esRegRepo->getContainer($idea->getEntrySet()->getEntrySetRegistration());

        $event = null;
        $group = null;
        if ($containerScope instanceof GroupEvent) {
            $event = $containerScope;
            $group = $event->getGroup();
        } elseif ($containerScope instanceof Group) {
            $group = $containerScope;
        }

        if ($event != null) {
            $eventService = $this->getGroupEventService();
            $eventService->register($event, $user);
        }

        if ($group != null) {
            $this->getGroupManager()->autoJoinGroup($group, $user);
        }
    }

    public function uploadAction(Request $request, $entrySetId, $entryId)
    {
        $this->enforceUserSecurity();

        $entry = $this->getEntry($entryId);
        $newImage = new Media();

        $form = $this->createForm(MediaType::class, $newImage, array('image_label' => 'Image File:'));

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {

                $image = $form->getData();

                if ($image->getFileObject() == null) {
                    $this->setFlash('error', 'You must select an image file');
                } else {
                    $image->setName($entry->getName());

                    $mUtil = $this->getMediaUtil();
                    $mUtil->persistRelatedMedia($image);
                    $entry->setImage($image);

                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    $ideaUrl = $this->generateUrl('idea_show', array(
                        'entrySetId' => $entrySetId,
                        'entryId' => $entryId,
                    ));
                    return new RedirectResponse($ideaUrl);
                }
            }
        }

        return $this->render('IdeaBundle:Idea:upload.html.twig', array(
            'entrySetId' => $entrySetId,
            'entryId' => $entryId,
            'breadCrumbs' => $this->getBreadCrumbsString($entry, true),
            'form' => $form->createView(),
            'sidebar' => true,
            'attendance' => $this->getCurrentUserApproved($entry->getEntrySet()),
            'isAdmin' => $this->isGranted('ROLE_ADMIN'),
        ));
    }

    public function deleteImageAction($entrySetId, $entryId)
    {
        $this->enforceUserSecurity();

        $entry = $this->getEntry($entryId);
        $image = $entry->getImage();

        if (!$image) {
            throw new NotFoundHttpException();
        }

        $entry->removeImage();

        $em = $this->getDoctrine()->getManager();
        $em->remove($image);
        $em->flush();

        $ideaUrl = $this->generateUrl('idea_show', array(
            'entrySetId' => $entrySetId,
            'entryId' => $entryId,
        ));
        return new RedirectResponse($ideaUrl);
    }

    public function addLinkAction($entrySetId, $entryId, Request $request)
    {
        $this->enforceUserSecurity();

        $idea = $this->getEntry($entryId);
        list($group, $event, $entrySet, $idea) = $this->getHierarchy($idea);

        $link = new Link();
        $form = $this->container->get('form.factory')->createNamedBuilder('link', FormType::class, $link)
            ->add('title')
            ->add('linkDescription', TextareaType::class, array('attr' => array('cols' => '60%')))
            ->add('url', TextType::class, array('attr' => array('size' => '60%', 'value' => 'http://')))
            ->add('type', ChoiceType::class, array(
                'choices' => [
                    'URL' => 'other',
                    'YouTube' => 'youtube',
                    'Twitter' => 'twitter',
                    'Flickr' => 'flickr',
                    'SlideShare' => 'slideshare',
                ],
                'choices_as_values' => true,
            ))
            ->getForm();

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $idea->addLink($link);
                $link->setIdea($idea);

                $em = $this->getDoctrine()->getManager();
                $em->persist($link);
                $em->flush();

                $ideaUrl = $this->generateUrl('idea_show', array(
                    'entrySetId' => $entrySetId,
                    'entryId' => $entryId,
                ));

                return new RedirectResponse($ideaUrl);
            }
        }

        $attendance = $this->getCurrentUserApproved($entrySet);
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Idea:addLink.html.twig', array(
            'group' => $group,
            'event' => $event,
            'entrySet' => $entrySet,
            'idea' => $idea,
            'breadCrumbs' => $this->getBreadCrumbsString($idea),
            'form' => $form->createView(),
            'sidebar' => true,
            'attendance' => $attendance,
            'isAdmin' => $isAdmin,
        ));
    }

    public function deleteLinkAction(Request $request, $entrySetId, $entryId)
    {
        $this->enforceUserSecurity();

        $idea = $this->getEntry($entryId);

        $linkId = $request->get('linkId');
        $link = $this->getDoctrine()->getRepository('IdeaBundle:Link')->find($linkId);

        $idea->removeLink($link);

        $em = $this->getDoctrine()->getManager();
        $em->remove($link);
        $em->flush();

        $ideaUrl = $this->generateUrl('idea_show', array(
            'entrySetId' => $entrySetId,
            'entryId' => $entryId,
        ));
        return new RedirectResponse($ideaUrl);
    }


    public function voteAction($entrySetId, $entryId)
    {
        $this->enforceUserSecurity();

        $idea = $this->getEntry($entryId);

        list($group, $event, $entrySet, $idea) = $this->getHierarchy($idea);

        if (!$event) {
            throw new NotValidException("Voting should only be done in events.");
        }

        //check for judge role here
        if (!$this->isJudge($entrySet)) {
            throw new AccessDeniedException();
        }

        $params = $this->getRequest()->request->all();
        $userName = $this->getCurrentUser()->getUsername();
        $currentRound = $event->getCurrentRound();

        $em = $this->getDoctrine()->getManager();

        //see if this voter has already voted on this idea
        $votes = $this->getDoctrine()->getRepository('IdeaBundle:Vote')->findBy(
            array('idea' => $entryId,
                'voter' => $userName,
                'round' => $currentRound,
            )
        );

        $criteriaList = $this->getDoctrine()->getRepository('IdeaBundle:VoteCriteria')->findByEventId($event->getId());
        foreach ($criteriaList as $criteria) {
            $vote = null;
            if (count($votes) == 0) {
                //create vote object using $criteria->getid() assigned to Vote::IdeaId()
                $vote = new Vote($idea, $criteria, $currentRound);
                $vote->setVoter($userName);
            } else {
                //find the vote for this particular criteria
                foreach ($votes as $criteriaVote) {
                    if ($criteriaVote->getCriteria()->getId() == $criteria->getId()) {
                        $vote = $criteriaVote;
                        break;
                    }
                }
            }

            //POST params keyed by criteria id
            $value = $params[strval($criteria->getId())];
            $vote->setValue($value);

            $em->persist($vote);
        }

        $em->flush();

        $ideaUrl = $this->generateUrl('idea_show', array(
            'entrySetId' => $entrySetId,
            'entryId' => $entryId,
        ));
        return new RedirectResponse($ideaUrl);
    }


    public function commentAction(Request $request, $entrySetId, $entryId)
    {

        $this->enforceUserSecurity();

        $commentText = $request->get('comment');
        $idea = $this->getEntry($entryId);

        $comment = new Comment($this->getCurrentUser(), $commentText, $idea);

        $em = $this->getDoctrine()->getManager();
        $em->persist($comment);
        $em->flush();

        $ideaUrl = $this->generateUrl('idea_show', array(
            'entrySetId' => $entrySetId,
            'entryId' => $entryId,
        ));
        return new RedirectResponse($ideaUrl);
    }

    public function commentDeleteAction(Request $request, $entrySetId, $entryId)
    {
        $this->enforceUserSecurity();
        $commentId = $request->get('commentId');

        $idea = $this->getEntry($entryId);
        $comment = $this->getDoctrine()->getRepository('IdeaBundle:Comment')->find($commentId);

        if (!$this->canRemoveComment($idea)) {
            throw new AccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($comment);
        $em->flush();

        $ideaUrl = $this->generateUrl('idea_show', array(
            'entrySetId' => $entrySetId,
            'entryId' => $entryId,
        ));
        return new RedirectResponse($ideaUrl);
    }

    public function watchAction(Request $request, $eventId)
    {

        $this->enforceUserSecurity();

        $eventType = $request->query->get('eventType');

        $event = null;

        if ($eventType == 'global') {
            $event = $this->getDoctrine()->getRepository('EventBundle:GlobalEvent')->find($eventId);
            $returnLink = $this->generateUrl('global_event_view', array('id' => $event->getId()));
        } else {
            $eventType = 'group';
            $event = $this->getDoctrine()->getRepository('EventBundle:GroupEvent')->find($eventId);
            $returnLink = $this->generateUrl('group_event_view', array(
                'groupSlug' => $this->getRelevantSlugForGroup($event->getGroup()),
                'eventId' => $event->getId()));
        }

        if (!$event) {
            throw new NotFoundHttpException('Event not found!');
        }

        $user = $this->getCurrentUser();
        $watchCriteria = array('user' => $user->getId(), $eventType . '_event' => $event->getId());
        $watchEventEntry = $this->getDoctrine()->getRepository('IdeaBundle:WatchedEventMapping')->findOneBy($watchCriteria);

        if (!$watchEventEntry) {
            $watchEventEntry = new WatchedEventMapping($user, $event);
            $em = $this->getDoctrine()->getManager();
            $em->persist($watchEventEntry);
            $em->flush();
            $this->setFlash('success', 'You are now watching ' . $event->getName());
        } else {
            $this->setFlash('info', 'You are already watching ' . $event->getName() . '!');
        }

        return new RedirectResponse($returnLink);
    }

    public function unwatchAction(Request $request, $eventId)
    {

        $this->enforceUserSecurity();

        $eventType = $request->query->get('eventType');

        $event = null;

        if ($eventType == 'global') {
            $event = $this->getDoctrine()->getRepository('EventBundle:GlobalEvent')->find($eventId);
        } else {
            $eventType = 'group';
            $event = $this->getDoctrine()->getRepository('EventBundle:GroupEvent')->find($eventId);
        }

        if (!$event) {
            throw new NotFoundHttpException('Event not found!');
        }

        $watchCriteria = array('user' => $this->getCurrentUser()->getId(), $eventType . '_event' => $event->getId());
        $watchEventEntry = $this->getDoctrine()->getRepository('IdeaBundle:WatchedEventMapping')->findOneBy($watchCriteria);

        if ($watchEventEntry) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($watchEventEntry);
            $em->flush();
            $this->setFlash('success', 'You have successfully stopped watching ' . $event->getName());
        } else {
            $this->setFlash('info', 'You are not watching this event.');
        }

        if ($eventType == 'group') {
            return new RedirectResponse($this->generateUrl('group_event_view',
                array('groupSlug' => $this->getRelevantSlugForGroup($event->getGroup()),
                    'eventId' => $event->getId())));
        }

        return new RedirectResponse($this->generateUrl('global_events_index'));
    }

    public function watchGroupAction($groupId)
    {
        $this->enforceUserSecurity();

        $group = $this->getDoctrine()->getRepository('GroupBundle:Group')->find($groupId);

        if (!$group) {
            throw new NotFoundHttpException('Group not found!');
        }

        $user = $this->getCurrentUser();
        $watchCriteria = array('user' => $user->getId(), 'group' => $group->getId());
        $watchGroupEntry = $this->getDoctrine()->getRepository('IdeaBundle:WatchedGroupMapping')->findOneBy($watchCriteria);

        if (!$watchGroupEntry) {
            $watchGroupEntry = new WatchedGroupMapping($user, $group);
            $em = $this->getDoctrine()->getManager();
            $em->persist($watchGroupEntry);
            $em->flush();
            $this->setFlash('success', 'You are now watching ' . $group->getName());
        } else {
            $this->setFlash('info', 'You are already watching ' . $group->getName() . '!');
        }

        return new RedirectResponse($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))));
    }

    public function unwatchGroupAction($groupId)
    {
        $this->enforceUserSecurity();

        $group = $this->getDoctrine()->getRepository('GroupBundle:Group')->find($groupId);

        if (!$group) {
            throw new NotFoundHttpException('Group not found!');
        }

        $user = $this->getCurrentUser();
        $watchCriteria = array('user' => $user->getId(), 'group' => $group->getId());
        $watchGroupEntry = $this->getDoctrine()->getRepository('IdeaBundle:WatchedGroupMapping')->findOneBy($watchCriteria);

        if ($watchGroupEntry) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($watchGroupEntry);
            $em->flush();
            $this->setFlash('success', 'You have successfully stopped watching ' . $group->getName());
        } else {
            $this->setFlash('info', 'You are not watching this group.');
        }

        return new RedirectResponse($this->generateUrl('group_show', array('slug' => $this->getRelevantSlugForGroup($group))));
    }

    public function followAction(Request $request, $entrySetId, $entryId)
    {

        $this->enforceUserSecurity();

        $params = $request->request->all();
        $source = $params['source'];

        $idea = $this->getEntry($entryId);
        $userName = $this->getCurrentUser()->getUsername();

        $em = $this->getDoctrine()->getManager();

        $followMapping = $idea->getFollowMapping($userName);

        if (!$followMapping) {
            $followMapping = new FollowMapping($userName, $idea);
            $idea->addFollowMapping($followMapping);
            $em->persist($followMapping);
        } else {
            $idea->removeFollowMapping($followMapping);
            $em->remove($followMapping);
        }

        $em->flush();

        if ($source == 'detail') {
            $url = $this->generateUrl('idea_show', array(
                'entrySetId' => $entrySetId,
                'entryId' => $entryId,
            ));
        } elseif ($source == 'list') {
            $url = $this->generateUrl('entry_set_view', array(
                'entrySetId' => $entrySetId,
                'tag' => $params['tag'],
            ));
        }

        return new RedirectResponse($url);
    }


    public function deleteAction($entrySetId, $entryId)
    {
        $this->enforceUserSecurity();

        $entrySet = $this->getEntrySet($entrySetId);
        $idea = $this->getEntry($entryId);

        if (!$this->canEditIdea($entrySet, $idea)) {
            throw new AccessDeniedException();
        }

        $user = $idea->getCreator();
        $user->removeIdea($idea);

        $em = $this->getDoctrine()->getManager();
        $em->remove($idea);
        $em->flush();

        $ideaListUrl = $this->generateUrl('entry_set_view', array(
            'entrySetId' => $entrySetId,
        ));
        return new RedirectResponse($ideaListUrl);
    }

    public function HtmlPageViewAction(Request $request, $id)
    {
        $htmlPage = $this->getDoctrine()->getRepository('IdeaBundle:HtmlPage')->find($id);
        if (!$htmlPage) {
            throw new NotFoundHttpException();
        }

        $container = null;
        if (!$container = $htmlPage->getGroup()) {
            $container = $htmlPage->getEvent();
        }

        $returnLink = $this->generateUrl($container->getLinkableRouteName(), $container->getLinkableRouteParameters());

        return $this->render('IdeaBundle:Idea:htmlPageView.html.twig', array(
            'htmlPage' => $htmlPage,
            'returnLink' => $returnLink,
        ));
    }

    public function sponsorsAction(Request $request)
    {
        $scope = $request->get('scope');
        $containerId = $request->get('containerId');

        $sponsorRepo = $this->getDoctrine()->getRepository('IdeaBundle:Sponsor');

        $attachedSponsors = null;
        $returnLink = null;

        if ($scope && $containerId) {
            $attachedSponsors = $sponsorRepo->findAttachedSponsors($scope, $containerId);
            $sponsors = $sponsorRepo->findUnattachedSponsors($scope, $containerId);
            $container = $this->getIdeaService()->getContainer($scope, $containerId);
            $returnLink = $this->generateUrl($container->getLinkableRouteName(), $container->getLinkableRouteParameters());
        } else {
            $sponsors = $sponsorRepo->findAll();
        }

        return $this->render('IdeaBundle:Idea:sponsors.html.twig', array(
            'attachedSponsors' => $attachedSponsors,
            'returnLink' => $returnLink,
            'sponsors' => $sponsors,
            'scope' => $scope,
            'containerId' => $containerId,
        ));
    }

    public function sponsorViewAction(Request $request, $id)
    {
        $sponsor = $this->getDoctrine()->getRepository('IdeaBundle:Sponsor')->find($id);

        if (!$sponsor) {
            throw new NotFoundHttpException();
        }

        $sponsorRegistrations = $sponsor->getSponsorRegistrations()->toArray();

        usort($sponsorRegistrations, function ($a, $b) {
            return ($a->getLevel() - $b->getLevel());
        });

        $groups = array();
        $events = array();

        foreach ($sponsorRegistrations as $reg) {
            if ($reg->getStatus() != SponsorRegistry::STATUS_SPONSORING) {
                continue;
            }
            if ($group = $reg->getGroup()) {
                $groups[$reg->getLevel()][] = $group;
            } elseif ($event = $reg->getEvent()) {
                $events[$reg->getLevel()][] = $event;
            }
        }

        return $this->render('IdeaBundle:Idea:sponsorView.html.twig', array(
            'sponsor' => $sponsor,
            'groups' => $groups,
            'events' => $events,
        ));
    }

    public function sponsorFormAction(Request $request, $id)
    {
        $this->enforceUserSecurity();

        $scope = $request->get('scope');
        $containerId = $request->get('containerId');

        if ($request->get('cancel') == 'Cancel') {
            return $this->redirect($this->generateUrl('sponsors', array('scope' => $scope, 'containerId' => $containerId)));
        }

        if ($id == 'new') {
            $sponsor = new Sponsor();
            $imgFieldAttributes = array();
        } else {
            $sponsor = $this->getDoctrine()->getRepository('IdeaBundle:Sponsor')->find($id);
            if (!$sponsor) {
                throw new NotFoundHttpException();
            }
            $imgFieldAttributes = array('required' => false);
        }

        $form = $this->container->get('form.factory')->createNamedBuilder('sponsor', FormType::class, $sponsor)
            ->add('name', TextType::class, array('attr' => array('style' => 'width:60%')))
            ->add('url', TextType::class, array('attr' => array('style' => 'width:60%')))
            ->add('image', FileType::class, $imgFieldAttributes)
            ->getForm();

        if ($request->getMethod() == 'POST') {

            $oldImage = $sponsor->getImage();

            $form->handleRequest($request);

            if ($form->isValid()) {

                $sponsor->setCreator($this->getCurrentUser());
                $image = $sponsor->getImage();

                if ($image) {
                    $media = new Media();
                    $media->setName($sponsor->getName());
                    $media->setFileObject($image);

                    $this->getMediaUtil()->persistRelatedMedia($media);

                    $sponsor->setImage($media);
                } else {
                    $sponsor->setImage($oldImage);
                }

                $em = $this->getDoctrine()->getManager();
                $em->persist($sponsor);
                $em->flush();

                if ($scope && $id == 'new') {
                    return $this->redirect($this->generateUrl('sponsor_add_form', array(
                        'id' => $sponsor->getId(),
                        'scope' => $scope,
                        'containerId' => $containerId)));
                }

                return $this->redirect($this->generateUrl('sponsors', array('scope' => $scope, 'containerId' => $containerId)));
            }
        }

        return $this->render('IdeaBundle:Idea:sponsorForm.html.twig', array(
            'id' => $id,
            'scope' => $scope,
            'containerId' => $containerId,
            'sponsor' => $sponsor,
            'form' => $form->createView(),
        ));
    }

    public function sponsorAddFormAction(Request $request, $id)
    {
        $this->enforceUserSecurity();

        $scope = $request->get('scope');
        $containerId = $request->get('containerId');

        if ($request->get('cancel') == 'Cancel') {
            return $this->redirect($this->generateUrl('sponsors', array('scope' => $scope, 'containerId' => $containerId)));
        }

        $sponsor = $this->getDoctrine()->getRepository('IdeaBundle:Sponsor')->find($id);
        if (!$sponsor) {
            throw new NotFoundHttpException();
        }

        $sponsorRegistry = null;

        if ($scope == 'group') {
            $group = $this->getDoctrine()->getRepository('GroupBundle:Group')->find($containerId);
            $containerOwner = $group->getOwner();
            $sponsorRegistry = new SponsorRegistry($group, null, $sponsor);
        } elseif ($scope == 'event') {
            $event = $this->getDoctrine()->getRepository('EventBundle:GroupEvent')->find($containerId);
            $containerOwner = $event->getUser();
            $sponsorRegistry = new SponsorRegistry(null, $event, $sponsor);
        } elseif ($scope == 'global_event') {
            $event = $this->getDoctrine()->getRepository('EventBundle:GlobalEvent')->find($containerId);
            $containerOwner = $event->getUser();
            $sponsorRegistry = new SponsorRegistry(null, $event, $sponsor);
        }

        $form = $this->container->get('form.factory')->createNamedBuilder('sponsor_add', FormType::class, $sponsorRegistry)
            ->add('level', ChoiceType::class, array(
                'choices' => [
                    'Bronze' => SponsorRegistry::BRONZE,
                    'Silver' => SponsorRegistry::SILVER,
                    'Gold' => SponsorRegistry::SILVER,
                    'Platinum' => SponsorRegistry::PLATINUM,
                    'Venue' => SponsorRegistry::VENUE,
                ],
                'choices_as_values' => true,
            ))
            ->getForm();


        if ($request->getMethod() == 'POST') {

            $form->handleRequest($request);

            if ($form->isValid()) {

                if ($this->getCurrentUser() == $containerOwner || $this->isGranted('ROLE_ADMIN')) {
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($sponsorRegistry);
                    $em->flush();

                    $this->setFlash('success', $sponsor->getName() . ' was successfully added to your ' . $scope);
                } else {
                    $this->setFlash('error', 'You are not authorized to add this sponsor!');
                }

                return $this->redirect($this->generateUrl('sponsors', array('scope' => $scope, 'containerId' => $containerId)));
            }
        }

        return $this->render('IdeaBundle:Idea:sponsorAddForm.html.twig', array(
            'sponsor' => $sponsor,
            'scope' => $scope,
            'containerId' => $containerId,
            'form' => $form->createView(),
        ));
    }

    public function sponsorRemoveAction(Request $request, $id)
    {
        $this->enforceUserSecurity();

        $scope = $request->get('scope');
        $containerId = $request->get('containerId');

        $sponsor = $this->getDoctrine()->getRepository('IdeaBundle:Sponsor')->find($id);

        $sponsorRegistryRepo = $this->getDoctrine()->getRepository('IdeaBundle:SponsorRegistry');
        $sponsorRegistration = $sponsorRegistryRepo->findOneBy(array('sponsor' => $id,
            $scope => $containerId));

        if ($scope == 'group') {
            $group = $this->getDoctrine()->getRepository('GroupBundle:Group')->find($containerId);
            $containerOwner = $group->getOwner();
        } elseif ($scope == 'event') {
            $event = $this->getDoctrine()->getRepository('EventBundle:GroupEvent')->find($containerId);
            $containerOwner = $event->getUser();
        } elseif ($scope == 'global_event') {
            $event = $this->getDoctrine()->getRepository('EventBundle:GlobalEvent')->find($containerId);
            $containerOwner = $event->getUser();
        }

        if ($this->getCurrentUser() == $containerOwner || $this->isGranted('ROLE_ADMIN')) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($sponsorRegistration);
            $em->flush();

            $this->setFlash('success', $sponsor->getName() . ' was successfully removed.');
        } else {
            $this->setFlash('error', 'You are not authorized to remove this sponsor!');
        }

        return $this->redirect($this->generateUrl('sponsors', array('scope' => $scope, 'containerId' => $containerId)));
    }

    public function sponsorDeleteAction(Request $request, $id)
    {
        $this->enforceUserSecurity();

        $scope = $request->get('scope');
        $containerId = $request->get('containerId');

        $sponsor = $this->getDoctrine()->getRepository('IdeaBundle:Sponsor')->find($id);

        if ($this->getCurrentUser() == $sponsor->getCreator() || $this->isGranted('ROLE_ADMIN')) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($sponsor);
            $em->flush();

            $this->setFlash('success', $sponsor->getName() . ' was successfully deleted.');
        } else {
            $this->setFlash('error', 'You are not authorized to delete this sponsor!');
        }

        return $this->redirect($this->generateUrl('sponsors', array('scope' => $scope, 'containerId' => $containerId)));
    }


    public function profileAction($userId = null)
    {
        $currentUser = $this->getCurrentUser();

        if ($userId == null) {
            $this->enforceUserSecurity();
            $user = $currentUser;
        } else {
            $user = $this->getDoctrine()->getRepository('UserBundle:User')->find($userId);
            if (!$user) {
                throw new NotFoundHttpException;
            }
        }

        $ownProfile = ($currentUser == $user);
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $showProfile = ($ownProfile || $isAdmin || $user->getDisplayProfile());
        $showPrivateInfo = $this->canCurrentUserViewThisUsersPrivateInfo($user);

        return $this->render('IdeaBundle:Idea:profile.html.twig', array(
            'user' => $user,
            'ownProfile' => $ownProfile,
            'isAdmin' => $isAdmin,
            'showProfile' => $showProfile,
            'showPrivateInfo' => $showPrivateInfo,
            'sidebar' => true,
        ));
    }

    public function canCurrentUserViewThisUsersPrivateInfo($user)
    {
        // Obviously not if there's no current user
        if (!$currentUser = $this->getCurrentUser()) {
            return false;
        }
        // Admins and the users themselves can always view their private info
        if ($this->isGranted('ROLE_ADMIN') || $user == $currentUser) {
            return true;
        }
        // Not if the user said that organizers can't view their private info
        if (!$user->getDisplayPrivateInfoToOrganizers()) {
            return false;
        }
        // Grab all groups and events that the user belongs to
        $usersGroups = $this->getDoctrine()->getRepository('GroupBundle:Group')->getAllGroupsForUser($user);
        $usersGlobalEvents = $this->getGlobalEventService()->getAllEventObjectsUserIsAttending($user);
        $usersGroupEvents = $this->getGroupEventService()->getAllEventObjectsUserIsAttending($user);
        $usersEvents = array_merge($usersGlobalEvents, $usersGroupEvents);

        // Check if the current user owns any of them
        foreach ($usersGroups as $group) {
            if ($group->getOwner() == $currentUser) {
                return true;
            }
        }
        foreach ($usersEvents as $event) {
            if ($event->getUser() == $currentUser) {
                return true;
            }
        }
        return false;
    }

    public function profileEditAction(Request $request, $userId)
    {

        if ($request->get('cancel') == 'Cancel') {
            return $this->redirect($this->generateUrl('profile', array(
                'userId' => $userId,
            )));
        }

        $this->enforceUserSecurity();

        $currentUser = $this->getCurrentUser();
        $profileUser = $this->getDoctrine()->getRepository('UserBundle:User')->find($userId);

        if ($currentUser != $profileUser && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }

        $form = $this->container->get('form.factory')->createNamedBuilder('profile', FormType::class, $profileUser, array('validation_groups' => array('ideaProfile')))
            ->add('name')
            ->add('title')
            ->add('organization')
            ->add('industry')
            ->add('aboutMe', null, array('attr' => array('rows' => '4', 'cols' => '60', 'maxlength' => '2000')))
            ->add('twitterUsername')
            ->add('professionalEmail')
            ->add('linkedIn', null, array('attr' => array('size' => '60%')))
            ->add('website', null, array('attr' => array('size' => '60%')))
            ->add('tshirtSize', null, array('attr' => array('size' => '60%')))
            ->add('mailingAddress', null, array('attr' => array('size' => '60%')))
            ->add('displayProfile', CheckboxType::class, array('required' => false))
            ->add('displayPrivateInfoToOrganizers', CheckboxType::class, array('required' => false))
            ->getForm();

        if ($request->getMethod() == 'POST') {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $this->getDoctrine()->getManager()->flush();

                return $this->redirect($this->generateUrl('profile', array(
                    'userId' => $userId,
                )));
            }
        }

        return $this->render('IdeaBundle:Idea:profileForm.html.twig', array(
            'form' => $form->createView(),
            'userId' => $userId,
            'isAdmin' => $this->isGranted('ROLE_ADMIN'),
        ));

    }

    public function inviteUserAction(Request $request)
    {

        $this->enforceUserSecurity();

        $toEmail = $request->get('userEmail');
        $scope = $request->get('scope');
        $containerId = $request->get('containerId');
        $forceAdd = $request->get('force');
        $type = null;
        $flashResult = 'success';
        $flashMessage = '';

        if ($scope == 'group') {
            $container = $this->getGroupManager()->find($containerId);
        } elseif ($scope == 'event' or $scope == 'group_event') {
            $scope = 'event';
            $container = $this->getGroupEventService()->find($containerId);
        } elseif ($scope == 'global_event') {
            $container = $this->getGlobalEventService()->find($containerId);
        }

        $params = array(
            'scope' => $scope,
            'containerId' => $containerId,
            'userEmail' => $toEmail,
        );

        if ($toUser = $this->getUserManager()->findUserBy(array('email' => $toEmail))) {
            $params['userId'] = $toUser->getId();
            $toName = $toUser->getName();
        } else {

            // if an admin is trying to add a new user to a container
            if ($forceAdd && $this->isGranted('ROLE_ADMIN')) {
                $password = mt_rand(100000, 999999);
                $um = $this->getUserManager();
                $toUser = $um->createUser();
                $toUser->setEmail($toEmail);
                $toUser->setUsername($toEmail);
                $toUser->setPlainPassword($password);
                $toUser->generateConfirmationToken();
                $toUser->setPasswordRequestedAt(new DateTime());
                $toUser->setEnabled(true);
                $um->updateUser($toUser);

                $flashMessage = 'Account created for ' . $toEmail . '. ';
                $params['userId'] = $toUser->getId();
                $params['password'] = $password;
                $type = 'create';
            } else {
                $flashResult = 'info';
                $flashMessage = $toEmail . ' does not yet have a Campsite account. ';
                $params['userId'] = 'external';
            }
            $toName = $toEmail;
        }

        if ($forceAdd && $this->isGranted('ROLE_ADMIN')) {
            if ($scope == 'group') {
                $this->getGroupManager()->autoJoinGroup($container, $toUser);
            } elseif ($scope == 'event') {
                $this->getGroupEventService()->register($container, $toUser);
            } elseif ($scope == 'global_event') {
                $this->getGlobalEventService()->register($container, $toUser);
            }

            if (!$type) {
                $type = 'add';
            }

            $flashMessage .= 'You have successfully added ' . $toName . ' to this ' . $scope . '. ';

        } elseif ($toUser) {
            if ($scope == 'group') {
                $recCriteria = array('referredBy' => $this->getCurrentUser()->getId(), 'user' => $toUser->getId(), 'group' => $containerId);
                $recommendation = $this->getDoctrine()->getRepository('IdeaBundle:GroupRecommendation')->findOneBy($recCriteria);
                if (!$recommendation) {
                    $recommendation = new GroupRecommendation($toUser, $container, $this->getCurrentUser());
                }
            } elseif ($scope == 'event' || $scope == 'global_event') {
                $recCriteria = array('referredBy' => $this->getCurrentUser()->getId(), 'user' => $toUser->getId());

                if ($scope == 'event') {
                    $recCriteria['group_event'] = $containerId;
                } elseif ($scope == 'global_event') {
                    $recCriteria['global_event'] = $containerId;
                }

                $recommendation = $this->getDoctrine()->getRepository('IdeaBundle:EventRecommendation')->findOneBy($recCriteria);
                if (!$recommendation) {
                    $recommendation = new EventRecommendation($toUser, $container, $this->getCurrentUser());
                }
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($recommendation);
            $em->flush();
            $flashMessage .= $container->getName() . ' has been recommended to ' . $toName . '. ';
        }

        if ($flashMessage) {
            $this->setFlash($flashResult, $flashMessage . 'Write a custom message to let them know.');
        }

        if (!$type) {
            $type = 'invite';
        }

        $params['type'] = $type;

        return $this->redirect($this->generateUrl('contact_user', $params));
    }


    public function contactUserAction(Request $request, $userId)
    {

        $this->enforceUserSecurity();

        $fromUser = $this->getCurrentUser();

        if ($userId !== 'external') {
            $toUser = $this->getUserManager()->findUserBy(array('id' => $userId));
            $toEmail = $toUser->getEmail();
            if (!$toName = $toUser->getName()) {
                $toName = $toEmail;
            }
        } else {
            $toEmail = $request->query->get('userEmail');
            $toName = $toEmail;
        }


        $subject = null;
        $bodyText = null;
        $emailType = null;
        $formTitle = null;
        $scope = null;
        $containerId = null;
        $containerUrl = null;

        if ($type = $request->query->get('type')) {

            $scope = $request->query->get('scope');
            $containerId = $request->query->get('containerId');
            $action = '';

            if ($scope && $containerId) {
                $container = $this->getIdeaService()->getContainer($scope, $containerId);
                $containerName = $container->getName();
                $containerUrl = $this->generateUrl($container->getLinkableRouteName(), $container->getLinkableRouteParameters(), true);
            }
            $params = array(
                '%from_name%' => $fromUser->getName(),
                '%from_email%' => $fromUser->getEmail(),
                '%to_name%' => $toName,
                '%url%' => $containerUrl,
                '%name%' => $containerName,
            );

            if ($type == 'sponsor') {
                $bodyTemplate = 'platformd.email.request';
                $subject = $fromUser->getName() . ' would like to sponsor ' . $containerName;
                $formTitle = 'Sponsor Form';
                $emailType = 'Sponsor Request';
                $params['%action%'] = 'sponsor';
            } elseif ($type == 'volunteer') {
                $bodyTemplate = 'platformd.email.request';
                $subject = $fromUser->getName() . ' would like to volunteer for ' . $containerName;
                $formTitle = 'Volunteer Form';
                $emailType = 'Volunteer Request';
                $params['%action%'] = 'volunteer for';
            } elseif ($type == 'speak') {
                $bodyTemplate = 'platformd.email.request';
                $subject = $fromUser->getName() . ' would like to speak at ' . $containerName;
                $formTitle = 'Speaker Form';
                $emailType = 'Speaker Request';
                $params['%action%'] = 'speak at';
            } elseif ($type == 'invite') {
                $bodyTemplate = 'platformd.email.invite';
                $subject = $fromUser->getName() . ' would like to invite you to ' . $containerName;
                $formTitle = 'Invite Form';
                $emailType = 'Invite';
            } elseif ($type == 'add') {
                $bodyTemplate = 'platformd.email.add';
                $subject = $fromUser->getName() . ' has added you to ' . $containerName;
                $formTitle = 'Add User Form';
                $emailType = 'Add';
            } elseif ($type == 'create') {
                $bodyTemplate = 'platformd.email.create';
                $subject = $fromUser->getName() . ' has added you to ' . $containerName . ' on Campsite';
                $formTitle = 'Create User Form';
                $emailType = 'Create';
                $params['%password%'] = $request->query->get('password');
                $params['%password_reset_url%'] = $this->generateUrl('fos_user_resetting_reset', array('token' => $toUser->getConfirmationToken()), true);
            }

            $bodyText = $this->trans($bodyTemplate, $params);
        }

        if ($request->getMethod() === 'POST') {

            if ($recType = $request->request->get('recommendType')) {
                if ($scope == 'group') {
                    $recommendation = $this->getDoctrine()->getRepository('IdeaBundle:GroupRecommendation')->findOneBy(
                        array('referredBy' => $this->getCurrentUser()->getId(),
                            'user' => $toUser->getId(),
                            'group' => $containerId));
                    $speakRec = GroupRecommendation::TYPE_SPEAK;
                    $sponsorRec = GroupRecommendation::TYPE_SPONSOR;
                    $volunteerRec = GroupRecommendation::TYPE_VOLUNTEER;
                    $defaultRec = GroupRecommendation::TYPE_JOIN;

                } elseif ($scope == 'event' || $scope == 'global_event') {
                    if ($scope == 'event') {
                        $recCriteria = array('referredBy' => $this->getCurrentUser()->getId(),
                            'user' => $toUser->getId(),
                            'group_event' => $containerId);
                    } else {
                        $recCriteria = array('referredBy' => $this->getCurrentUser()->getId(),
                            'user' => $toUser->getId(),
                            'global_event' => $containerId);
                    }
                    $recommendation = $this->getDoctrine()->getRepository('IdeaBundle:EventRecommendation')->findOneBy($recCriteria);
                    $speakRec = EventRecommendation::TYPE_SPEAK;
                    $sponsorRec = EventRecommendation::TYPE_SPONSOR;
                    $volunteerRec = EventRecommendation::TYPE_VOLUNTEER;
                    $defaultRec = EventRecommendation::TYPE_ATTEND;
                }

                if ($recType == 'speak') {
                    $subject = $fromUser->getName() . ' has recommended you to speak at ' . $containerName;
                    $recommendation->setType($speakRec);
                } elseif ($recType == 'sponsor') {
                    $subject = $fromUser->getName() . ' has recommended you to sponsor ' . $containerName;
                    $recommendation->setType($sponsorRec);
                } elseif ($recType == 'volunteer') {
                    $subject = $fromUser->getName() . ' has recommended you to volunteer for ' . $containerName;
                    $recommendation->setType($volunteerRec);
                } else {
                    $recommendation->setType($defaultRec);
                }
                $this->getDoctrine()->getManager()->flush();
            }

            if (!$subject) {
                $subject = 'New message from ' . $fromUser->getName();
            }
            if (!$emailType) {
                $emailType = 'User Message';
            }

            $body = nl2br($request->request->get('body'));

            $this->getEmailManager()->sendHtmlEmail($toEmail, $subject, $body, $emailType, $this->getCurrentSite()->getDefaultLocale());
            $this->setFlash('success', 'Your message was sent to ' . $toName . '.');

            if ($containerUrl) {
                if ($container->getLinkableOverrideUrl()) {
                    $redirectUrl = $this->generateUrl('global_events_index', array('useExternal' => 'true'));
                } else {
                    $redirectUrl = $containerUrl;
                }
            } else {
                $redirectUrl = $this->generateUrl('profile', array('userId' => $userId));
            }

            return $this->redirect($redirectUrl);
        }

        if ($userId !== 'external') {
            $toEmail = null;
        }

        return $this->render('IdeaBundle:Idea:contactForm.html.twig', array(
            'toName' => $toName,
            'toEmail' => $toEmail,
            'userId' => $userId,
            'type' => $type,
            'formTitle' => $formTitle,
            'bodyText' => $bodyText,
            'scope' => $scope,
            'containerId' => $containerId,
        ));
    }

    public function dismissRecommendationAction(Request $request, $recType, $recId)
    {
        $this->enforceUserSecurity();

        if ($recType == 'event') {
            $recommendation = $this->getDoctrine()->getRepository('IdeaBundle:EventRecommendation')->find($recId);
            $returnUrl = $this->generateUrl('accounts_events');
        } else {
            $recommendation = $this->getDoctrine()->getRepository('IdeaBundle:GroupRecommendation')->find($recId);
            $returnUrl = $this->generateUrl('accounts_groups');
        }

        if (!$recommendation) {
            throw new NotFoundHttpException();
        }

        $recommendation->setDismissed(true);
        $this->getDoctrine()->getManager()->flush();
        $this->setFlash('success', 'This recommendation has been dismissed');

        return $this->redirect($returnUrl);
    }

    public function userEventRecommendationsAction()
    {
        $this->enforceUserSecurity();
        $recommendations = $this->getDoctrine()->getRepository('IdeaBundle:EventRecommendation')->findBy(array('user' => $this->getCurrentUser()->getId()));

        foreach ($recommendations as $key => $rec) {
            if ($rec->getEvent()->isUserAttending($this->getCurrentUser())) {
                unset($recommendations[$key]);
            }
        }

        return $this->render('IdeaBundle:Idea:userRecommendations.html.twig', array(
            'recommendations' => $recommendations,
        ));
    }

    public function userGroupRecommendationsAction()
    {
        $this->enforceUserSecurity();
        $recommendations = $this->getDoctrine()->getRepository('IdeaBundle:GroupRecommendation')->findBy(array('user' => $this->getCurrentUser()->getId()));

        foreach ($recommendations as $key => $rec) {
            if ($rec->getGroup()->isMember($this->getCurrentUser())) {
                unset($recommendations[$key]);
            }
        }

        return $this->render('IdeaBundle:Idea:userRecommendations.html.twig', array(
            'recommendations' => $recommendations,
        ));
    }

    public function userEntriesAction()
    {
        $this->enforceUserSecurity();
        $userEntries = $this->getCurrentUser()->getIdeas();

        $parents = array();
        foreach ($userEntries as $entry) {
            $parent = $this->getParentByIdea($entry);
            $parents[$entry->getName()] = $parent;
        }

        return $this->render('IdeaBundle:Idea:userEntries.html.twig', array(
            'entries' => $userEntries,
            'parents' => $parents,
        ));
    }

    public function userPagesAction()
    {
        $this->enforceUserSecurity();
        return $this->render('IdeaBundle:Idea:userPages.html.twig', array(
            'userPages' => $this->getCurrentUser()->getHtmlPages(),
        ));
    }

    public function userEntrySetsAction()
    {
        $this->enforceUserSecurity();
        $userEntrySets = $this->getCurrentUser()->getEntrySets();

        $parents = array();
        foreach ($userEntrySets as $entrySet) {
            $parent = $this->getParentByEntrySet($entrySet);
            $parents[$entrySet->getId()] = $parent;
        }

        return $this->render('IdeaBundle:Idea:userEntrySets.html.twig', array(
            'entrySets' => $userEntrySets,
            'parents' => $parents,
        ));
    }

    public function userSponsorshipsAction(Request $request)
    {
        $this->enforceUserSecurity();

        $myDepartments = $this->getCurrentUser()->getOwnedDepartments();
        $mySponsorships = array();

        foreach ($myDepartments as $dept) {
            foreach ($dept->getSponsor()->getSponsorRegistrations() as $sponsorship) {
                $mySponsorships[$dept->getName()][] = $sponsorship;
            }
        }

        return $this->render('IdeaBundle:Idea:userSponsorships.html.twig', array(
            'sponsorships' => $mySponsorships,
        ));
    }


    public function userRegistrationAnswersAction($groupSlug, $eventId, $userId)
    {
        $event = $this->getEvent($groupSlug, $eventId);
        $user = $this->getDoctrine()->getRepository('UserBundle:User')->find($userId);

        $regAnswerRepo = $this->getDoctrine()->getRepository('IdeaBundle:RegistrationAnswer');

        $fields = $event->getRegistrationFields();

        $answers = array();

        foreach ($fields as $field) {
            $answer = $regAnswerRepo->findOneBy(array('field' => $field->getId(), 'user' => $user->getId()));
            if ($answer) {
                $answers[$field->getQuestion()] = $answer->getAnswer();
            }
        }
        return $this->render('IdeaBundle:Idea:userRegistrationAnswers.html.twig', array(
            'group' => $event->getGroup(),
            'event' => $event,
            'user' => $user,
            'answers' => $answers,
        ));
    }

    public function eventRegistrationFormAction(Request $request, $groupSlug, $eventId)
    {
        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventId);
        $user = $this->getCurrentUser();

        if ($request->get('cancel') == 'Cancel') {
            return $this->redirect($this->generateUrl($event->getLinkableRouteName(), $event->getLinkableRouteParameters()));
        }

        $registrationFields = $event->getRegistrationFields();

        if ($request->getMethod() == 'POST') {

            $em = $this->getDoctrine()->getManager();

            foreach ($registrationFields as $field) {
                $answer = new RegistrationAnswer();
                $answer->setField($field);
                $answer->setUser($user);
                $fieldInput = $request->request->get($field->getId());
                if ($field->getType() == RegistrationField::TYPE_CHECKBOX) {
                    if ($fieldInput) {
                        $answer->setAnswer('Y');
                    } else {
                        $answer->setAnswer('N');
                    }
                } else {
                    $answer->setAnswer($request->request->get($field->getId()));
                }

                $em->persist($answer);
            }
            $em->flush();

            $wasGroupMember = $group->isMember($user);

            $this->getGroupEventService()->register($event, $user);
            $this->getGroupManager()->autoJoinGroup($group, $user);

            if ($event->getPrivate()) {
                $flashMessage = "We have received your request for private access. You will receive a response by an administrator when your account has been reviewed.";
            } elseif ($event->getExternalUrl()) {
                $flashMessage = $this->trans('platformd.events.event_show.now_attending_external');
            } elseif ($wasGroupMember || $group->isOwner($user)) {
                $flashMessage = $this->trans('platformd.events.event_show.now_attending');
            } else {
                $flashMessage = $this->trans('platformd.events.event_show.group_joined', array('%groupName%' => $group->getName()));
            }
            $this->setFlash('success', $flashMessage);

            return $this->redirect($this->generateUrl('group_event_view', array(
                'groupSlug' => $groupSlug,
                'eventId' => $event->getId(),
            )));
        }

        return $this->render('IdeaBundle:Idea:eventRegistrationForm.html.twig', array(
            'fields' => $registrationFields,
            'group' => $group,
            'event' => $event,
        ));
    }

    public function eventSessionAction($groupSlug, $eventId, $sessionId)
    {

        $event = $this->getEvent($groupSlug, $eventId);

        $eventSession = $this->getEventSession($groupSlug, $eventId, $sessionId);

        if (!$eventSession) {
            throw new NotFoundHttpException('Session not found.');
        }

        if ($currentUser = $this->getCurrentUser()) {
            $canEdit = ($this->isAdmin() || $currentUser == $eventSession->getEvent()->getUser());
        } else {
            $canEdit = false;
        }

        return $this->render('IdeaBundle:Idea:session.html.twig', array(
            'group' => $event->getGroup(),
            'event' => $event,
            'eventSession' => $eventSession,
            'sidebar' => true,
            'canEdit' => $canEdit,
            'breadCrumbs' => $this->getBreadCrumbsString($eventSession),
        ));
    }

    public function eventSessionsAction($groupSlug, $eventId)
    {
        $event = $this->getEvent($groupSlug, $eventId);

        if (!$event) {
            throw new NotFoundHttpException('Event not found.');
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN') || ($event->getUser() == $this->getCurrentUser());

        return $this->render('IdeaBundle:Idea:sessions.html.twig', array(
            'group' => $event->getGroup(),
            'event' => $event,
            'eventSessions' => $event->getSessionsByDate(),
            'isAdmin' => $isAdmin,
            'sidebar' => true,
            'breadCrumbs' => $this->getBreadCrumbsString($event, true),
        ));
    }

    public function infoPageAction($groupSlug, $page)
    {
        $group = $this->getGroup($groupSlug);
        return $this->render('IdeaBundle::' . $page . '.html.twig', array(
            'group' => $group,
        ));
    }

    public function recommendSponsorListAction(Request $request)
    {
        $eventType = $request->query->get('rec_type');
        $eventId = $request->query->get('rec_id');

        $departments = $this->getGroupManager()->getAllDepartmentsForCurrentSite();

        return $this->render('IdeaBundle:Idea:department_list.html.twig', array(
            'departments' => $departments,
            'eventType' => $eventType,
            'eventId' => $eventId,
        ));
    }

    public function recommendSponsorAction(Request $request, $department_id)
    {
        $eventType = $request->query->get('rec_type');
        $eventId = $request->query->get('rec_id');

        $dept = $this->getGroupManager()->find($department_id);

        if ($eventType == 'group') {
            $event = $this->getDoctrine()->getRepository('EventBundle:GroupEvent')->find($eventId);
            $returnUrl = $this->generateUrl('group_event_view', array(
                'groupSlug' => $this->getRelevantSlugForGroup($event->getGroup()),
                'eventId' => $event->getId()));
        } elseif ($eventType == 'global') {
            $event = $this->getDoctrine()->getRepository('EventBundle:GlobalEvent')->find($eventId);
            $returnUrl = $this->generateUrl('global_event_view', array('id' => $event->getId()));
        }

        if (!$dept || !$event) {
            throw new NotFoundHttpException('Invalid sponsorship!');
        }

        $em = $this->getDoctrine()->getManager();

        $deptSponsor = $dept->getSponsor();

        $sponsorRegistry = $em->getRepository('IdeaBundle:SponsorRegistry')->findOneBy(array('sponsor' => $deptSponsor->getId(), 'event' => $event->getId()));

        if (!$sponsorRegistry) {
            $sponsorRegistry = new SponsorRegistry(null, $event, $dept->getSponsor(), null, SponsorRegistry::STATUS_RECOMMENDED);
            $em->persist($sponsorRegistry);
            $em->flush();
            $this->setFlash('success', $event->getName() . ' has been recommended to ' . $dept->getName());

        } elseif ($sponsorRegistry->getStatus() == SponsorRegistry::STATUS_SPONSORING) {
            $this->setFlash('info', $dept->getName() . ' is already sponsoring ' . $event->getName());

        } else {
            $this->setFlash('info', $event->getName() . ' has already been recommended to ' . $dept->getName());
        }

        return new RedirectResponse($returnUrl);
    }

    public function updateSponsorshipStatusAction(Request $request, $sponsorship_id)
    {
        $this->enforceUserSecurity();

        $em = $this->getDoctrine()->getManager();
        $sponsorship = $em->getRepository('IdeaBundle:SponsorRegistry')->find($sponsorship_id);

        if (!$sponsorship) {
            throw new NotFoundHttpException('Sponsorship not found!');
        }

        $this->validateAuthorization($sponsorship->getSponsor());

        $status = $request->query->get('status');

        $sponsorship->setStatus($status);
        $em->flush();
        $this->setFlash('success', 'You are now ' . $status . ' this event.');

        return new RedirectResponse($this->generateUrl('accounts_sponsorships'));
    }

    public function addIdeaSpeakerAction(Request $request, $entrySetId, $entryId)
    {
        if ($request->get('cancel') == 'Cancel') {
            return $this->redirect($this->generateUrl('idea_show', array('entrySetId' => $entrySetId,
                'entryId' => $entryId,)));
        }

        $doc = $this->getDoctrine();
        $entry = $doc->getRepository('IdeaBundle:Idea')->find($entryId);

        if (!$entry) {
            throw new NotFoundHttpException('The entry with id: ' . $entryId . ' does not exist!');
        }

        $this->validateAuthorization($entry);

        $ideaSpeaker = null;

        if ($userId = $request->get('userId')) {
            $ideaSpeaker = $doc->getRepository('IdeaBundle:IdeaSpeaker')->findOneBy(array('idea' => $entryId, 'speaker' => $userId));
        }

        if (!$ideaSpeaker) {
            $ideaSpeaker = new IdeaSpeaker();
            $isNew = true;
        } else {
            $isNew = false;
        }

        $parent = $this->getParentByIdea($entry);

        if ($parent instanceof Group) {
            $speakerChoices = $parent->getMembersSorted();
        } elseif ($parent instanceof GroupEvent ) {
            $speakerChoices = $parent->getAttendeesAlphabetical();
        } else {
            $speakerChoices = array();
        }

        $form = $this->container->get('form.factory')->createNamedBuilder('idea_speaker', FormType::class, $ideaSpeaker)
            ->add('speaker', EntityType::class, array(
                'class' => 'UserBundle:User',
                'choices' => $speakerChoices,
            ))
            ->add('role', TextType::class, array('attr' => array('class' => 'formRowWidth')))
            ->add('biography', TextareaType::class, array('attr' => array('class' => 'formRowWidth', 'rows' => '6')))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {

                $em = $doc->getManager();

                if ($isNew) {
                    $ideaSpeaker->setIdea($entry);
                    $em->persist($ideaSpeaker);
                }

                $em->flush();

                return $this->redirectToRoute('idea_show', array(
                    'entrySetId' => $entrySetId,
                    'entryId' => $entryId,
                    )
                );
            }
        }

        $params = array(
            'entry' => $entry,
            'entrySet' => $entry->getEntrySet(),
            'parent' => $parent,
            'form' => $form->createView(),
        );

        if ($userId) {
            $params['userId'] = $userId;
        }

        return $this->render('IdeaBundle:Idea:ideaSpeakerForm.html.twig', $params);
    }

    public function removeIdeaSpeakerAction(Request $request, $entrySetId, $entryId, $userId)
    {
        $doc = $this->getDoctrine();

        $ideaSpeaker = $doc->getRepository('IdeaBundle:IdeaSpeaker')->findOneBy(array('idea' => $entryId, 'speaker' => $userId));

        if (!$ideaSpeaker) {
            throw new NotFoundHttpException('Idea speaker not found.');
        }

        $this->validateAuthorization($ideaSpeaker->getIdea());

        $em = $doc->getEntityManager();
        $em->remove($ideaSpeaker);
        $em->flush();

        $this->setFlash('success', 'Speaker ' . $ideaSpeaker->getSpeaker()->getName() . ' has been removed.');

        return $this->redirectToRoute('idea_show', array(
            'entrySetId' => $entrySetId,
            'entryId' => $entryId,
            )
        );
    }

    //TODO: Move this to Idea Service
    /******************************************************
     ****************    MODEL STUFF HERE    ***************
     *******************************************************/
    public function isLoggedIn()
    {
        return $this->isGranted('IS_AUTHENTICATED_REMEMBERED');
    }

    public function getSidebarState($entrySet, $idea)
    {
        if ($entrySet->getType() == EntrySet::TYPE_IDEA) {
            if ($this->isGranted('ROLE_ADMIN')) {
                return IdeaController::SIDEBAR_ADMIN;
            }

            if ($this->canJudge($entrySet, $idea)) {
                return IdeaController::SIDEBAR_JUDGE;
            }
        }

        return IdeaController::SIDEBAR_NONE;
    }

    public function canJudge($entrySet, $idea)
    {
        $user = $this->getCurrentUser();

        return $this->isJudge($entrySet) && $idea->isJudgeAssigned($user);
    }

    public function isJudge($entrySet)
    {

        if (!$this->isLoggedIn())
            return false;

        if (!$entrySet->getIsVotingActive())
            return false;

        $user = $this->getCurrentUser();

        return $entrySet->containsVoter($user->getUsername());
    }

    public function canCreate($entrySet)
    {

        if (!$entrySet->getIsSubmissionActive()) {
            return false;
        }

        return $this->isLoggedIn();
    }

    public function isCreator($idea)
    {
        if (!$this->isLoggedIn()) {
            return false;
        }
        $username = $this->getCurrentUser()->getUsername();
        return $username === $idea->getCreator()->getUsername();
    }

    public function canEditIdea($entrySet, $idea)
    {
        return ($this->isAuthorized($entrySet) || ($this->isAuthorized($idea) && $entrySet->getIsSubmissionActive()));
    }

    public function canRemoveComment($idea)
    {

        return $this->isAuthorized($idea);
    }

    public function getEvent($groupSlug, $eventId)
    {
        $group = $this->getGroup($groupSlug);

        $eventEm = $this->getDoctrine()->getRepository('EventBundle:GroupEvent');
        $event = $eventEm->findOneBy(
            array(
                'group' => $group->getId(),
                'id' => $eventId,
            )
        );

        if (!$event) {
            throw new NotFoundHttpException('Event not found.');
        }

        return $event;
    }

    public function getEntrySet($entrySetId)
    {
        $entrySetRepo = $this->getDoctrine()->getRepository('IdeaBundle:EntrySet');
        $entrySet = $entrySetRepo->find($entrySetId);

        if (!$entrySet) {
            throw new NotFoundHttpException('Entry Set not found');
        }

        return $entrySet;
    }

    public function getEntry($entryId)
    {
        $entryRepo = $this->getDoctrine()->getRepository('IdeaBundle:Idea');
        $entry = $entryRepo->find($entryId);

        if (!$entry) {
            throw new NotFoundHttpException('Entry ' . $entryId . ' not found');
        }

        return $entry;
    }

    public function getEventSession($groupSlug, $eventId, $sessionId)
    {
        $event = $this->getEvent($groupSlug, $eventId);

        if (!$event) {
            return false;
        }

        $evtSession = $this->getDoctrine()->getRepository('EventBundle:EventSession')->find($sessionId);

        if ($evtSession == null) {
            return false;
        }

        return $evtSession;
    }

    public function canEditEntrySet($entrySet)
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $parent = $this->getParentByEntrySet($entrySet);
        if ($parent instanceof GroupEvent) {
            return $this->canEditEvent($parent);
        } elseif ($parent instanceof Group) {
            return $parent->isOwner($this->getCurrentUser());
        }

        return false;
    }


    /**
     * @param $ideas
     * @param $currentIdea
     *
     * @return array
     */
    public function findNextAndPrevious($ideas, $idea)
    {
        $ideaFound = false;
        $previousIdea = null;
        $nextIdea = null;
        foreach ($ideas as $currentIdea) {
            if ($ideaFound) {
                $nextIdea = $currentIdea;
                break;
            }

            if ($currentIdea->getId() == $idea->getId()) {
                $ideaFound = true;
            } else {
                $previousIdea = $currentIdea;
            }
        }
        return array($previousIdea, $nextIdea);
    }


    public function getCurrentUserApproved($entrySet)
    {
        $parent = $this->getParentByEntrySet($entrySet);
        if ($parent instanceof GroupEvent) {
            $rsvpRepo = $this->getDoctrine()->getRepository('EventBundle:GroupEventRsvpAction');
            $user = $this->getCurrentUser();
            $attendance = $rsvpRepo->getUserApprovedStatus($parent, $user);
        } else {
            $attendance = 'approved';
        }

        return $attendance;
    }
}

?>
