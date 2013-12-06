<?php

namespace Platformd\IdeaBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContext;

use Platformd\SpoutletBundle\Controller\Controller;
use Platformd\IdeaBundle\Entity\Idea;
use Platformd\IdeaBundle\Entity\Comment;
use Platformd\IdeaBundle\Entity\Tag;
use Platformd\IdeaBundle\Entity\Vote;
use Platformd\IdeaBundle\Entity\FollowMapping;
use Platformd\IdeaBundle\Entity\Document;
use Platformd\IdeaBundle\Entity\Link;
use Platformd\IdeaBundle\Entity\EntrySet;
use Platformd\IdeaBundle\Entity\EntrySetRegistryRepository;
use Platformd\EventBundle\Entity\Event;

class IdeaController extends Controller
{

	const SIDEBAR_NONE = 0;
    const SIDEBAR_JUDGE = 1;
    const SIDEBAR_ADMIN = 2;

    public function showAllAction(Request $request, $groupSlug, $eventSlug, $entrySetId)
    {
        $group      = $this->getGroup($groupSlug);
        $event      = $this->getEvent($groupSlug, $eventSlug);
        $entrySet   = $this->getEntrySet($entrySetId);

        $tag         	= $request->query->get('tag');
        $viewPrivate 	= $request->query->get('viewPrivate', false);
        $sortBy      	= $request->query->get('sortBy', 'vote');
        $showAllRounds  = $request->query->get('showAllRounds', 'false');

        //filter the idea list using the query parameters
        $userParam  = $viewPrivate ? $this->getCurrentUser() : null;
        $roundParam = $showAllRounds == 'true' ? null : $event->getCurrentRound();

        $ideaRepo 	= $this->getDoctrine()->getRepository('IdeaBundle:Idea');
        $ideaList 	= $ideaRepo->filter($entrySet, $roundParam, $tag, $userParam);

        $isAdmin    = $this->isGranted('ROLE_ADMIN');

        //For admin remove the public ideas from the full list to just show private ideas
        if ($viewPrivate && $isAdmin) {
            $publicList 	= $ideaRepo->filter($entrySet, $roundParam, $tag, null);
            foreach($publicList as $publicIdea) {
                $index = array_search($publicIdea,$ideaList);
                unset($ideaList[$index]);
            }
        }

        if ($sortBy == 'vote') {
            $ideaRepo->sortByFollows($ideaList);
        }
        else if ($sortBy == 'createdAt') {
            $ideaRepo->sortByCreatedAt($ideaList);
        }

        $attendance = $this->getCurrentUserApproved($event);

        $params = array(
            'group'         => $group,
            'event'         => $event,
            'ideas'         => $ideaList,
            'submitActive'  => $entrySet->getIsSubmissionActive(),
            'tag'           => $tag,
            'round'         => $event->getCurrentRound(),
            'sidebar'       => true,
            'attendance'    => $attendance,
            'viewPrivate'   => $viewPrivate,
            'sortBy'        => $sortBy,
            'isAdmin'       => $isAdmin,
            'isJudge'       => $this->isJudge($entrySet),
            'showAllRounds' => $showAllRounds,
            'entrySet'      => $entrySet,
        );

        return $this->render('IdeaBundle:Idea:showAll.html.twig', $params);
    }


    public function showAction($groupSlug, $eventSlug, $entrySetId, $id) {

        $group      = $this->getGroup($groupSlug);
        $event      = $this->getEvent($groupSlug, $eventSlug);
        $entrySet   = $this->getEntrySet($entrySetId);

        $currentRound = $event->getCurrentRound();

        $doctrine = $this->getDoctrine();
        $ideaRepo = $doctrine->getRepository('IdeaBundle:Idea');

        $idea = $ideaRepo->find($id);

        if (!$idea) {
            throw $this->createNotFoundException('No idea found for id '.$id);
        }

        $attendance = $this->getCurrentUserApproved($event);
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $params = array(
            'group' 			=> $group,
            'event' 			=> $event,
            'entrySet'          => $entrySet,
            'idea' 				=> $idea,
            'canEdit' 			=> $this->canEditIdea($entrySet, $idea),
			'canRemoveComments' => $this->canRemoveComment($idea),
            'sidebar' 			=> true,
            'attendance' 		=> $attendance,
            'isAdmin'       	=> $isAdmin,
        );


        // Do vote sidebar stuff
        $sidebarState = $this->getSidebarState($entrySet, $idea);

        //Disable Judge mode if no criteria defined yet
        $criteriaList = $doctrine->getRepository('IdeaBundle:VoteCriteria')->findByEventId($event->getId());
        if($sidebarState == IdeaController::SIDEBAR_JUDGE && count($criteriaList) <= 0)
            $sidebarState = IdeaController::SIDEBAR_NONE;

        //pass state into twig
        $params['sidebarState'] = $sidebarState;

        $user = $this->getCurrentUser();

        //For Admin sidebar
        if( $sidebarState == IdeaController::SIDEBAR_ADMIN) {

            $ideas = $ideaRepo->filter($entrySet, $currentRound, null, $user);

            // determine previous idea, next idea
            $ideaRepo->sortByFollows($ideas);

            list($previousIdea, $nextIdea) = $this->findNextAndPrevious($ideas, $idea);

            if($nextIdea){
                $params['next'] = $nextIdea->getId();
            }
            if($previousIdea){
                $params['previous'] = $previousIdea->getId();
            }

            $userRepo = $doctrine->getRepository('UserBundle:User');

            //Get list of event judges and populate form widget
            $choices = array();
            $allowedVoterString = $entrySet->getAllowedVoters();
            if($allowedVoterString != "") {
                $allowedVoters = array_map('trim',explode(",",$allowedVoterString));
                foreach($allowedVoters as $voter) {
                    $choices[$voter] = $userRepo->findOneBy(array('username' => $voter))->getName();
                }
            }

            $selected = array();
            foreach($idea->getJudges() as $judge) {
                $selected[] = array_search($judge->getName(),$choices);
            }

            $numRows = count($choices) <= 20 ? count($choices) : 20;
            $formAttributes = array('multiple' => 'true', 'style' => 'width: 100%', 'size' => $numRows);
            $choiceOptions = array(
                'choices' => $choices,
                'attr' => $formAttributes,
                'multiple' => 'true',
                'data' => $selected
            );
            $form = $this->container->get('form.factory')->createNamedBuilder('form', 'judgeAssignment')
                ->add('judges', 'choice', $choiceOptions)
                ->getForm();

            $params['form'] = $form->createView();

        } elseif( $sidebarState == IdeaController::SIDEBAR_JUDGE ) {
            // determine previous idea, next idea
            $ideas = $ideaRepo->filter($event, $currentRound, null, $user);
            $ideaRepo->sortByFollows($ideas);

            list($previousIdea, $nextIdea) = $this->findNextAndPrevious($ideas, $idea);

            if($nextIdea){
                $params['next'] = $nextIdea->getId();
            }
            if($previousIdea){
                $params['previous'] = $previousIdea->getId();
            }

            //Pass all VoteCriteria to template for rendering
            $params['criteriaList'] = $criteriaList;

            //Pass previous vote values to the template keyed by category

            $userName = $user->getUsername();

            $voteRepo = $doctrine->getRepository('IdeaBundle:Vote');

            $votes = $voteRepo->findBy(array('idea' => $idea->getId(), 'voter' => $userName, 'round' => $currentRound));

            if(count($votes) > 0) {
                $valuesByCriteria = array();
                foreach($votes as $criteriaVote) {
                    $valuesByCriteria[strval($criteriaVote->getCriteria()->getId())] = $criteriaVote->getValue();
                }
                $params['values'] = $valuesByCriteria;
            }
        }
        return $this->render('IdeaBundle:Idea:show.html.twig', $params);
    }


    public function createFormAction($groupSlug, $eventSlug, $entrySetId) {

        $this->enforceUserSecurity();

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventSlug);
        $entrySet = $this->getEntrySet($entrySetId);

        $attendance = $this->getCurrentUserApproved($event);
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Idea:createForm.html.twig', array(
                'group'      => $group,
                'event'      => $event,
                'entrySet'   => $entrySet,
                'sidebar'    => true,
                'attendance' => $attendance,
                'isAdmin'    => $isAdmin,
            ));
    }

    public function createAction(Request $request, $groupSlug, $eventSlug, $entrySetId) {

        $this->enforceUserSecurity();

        $event    = $this->getEvent($groupSlug, $eventSlug);
        $entrySet = $this->getEntrySet($entrySetId);

        if (!$this->canCreate($entrySet)) {
            return new RedirectResponse($this->generateUrl('idea_create_form', array(
                    'groupSlug' => $groupSlug,
                    'eventSlug' => $eventSlug,
                    'entrySetId'=> $entrySetId,
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
        }
        else{
            $idea->setForCourse(false);
        }

        if (array_key_exists('amount', $params)) {
            if (!empty($params['amount'])){
                $idea->setAmount($params['amount']);
            }
        }

        $idea->addTags($this->parseTags($params['tags']));

        if (isset($params['isPrivate'])){
            $idea->setIsPrivate(true);
        }

        $idea->setHighestRound($event->getCurrentRound());

        $em = $this->getDoctrine()->getEntityManager();
        $em->persist($idea);
        $em->flush();

        $ideaUrl = $this->generateUrl('idea_show', array(
             'id' => $idea->getId(),
             'groupSlug' => $groupSlug,
             'eventSlug' => $eventSlug,
             'entrySetId'=> $entrySetId,
            ));
        return new RedirectResponse($ideaUrl);
    }


    public function editFormAction($groupSlug, $eventSlug, $entrySetId, $id) {

        $this->enforceUserSecurity();

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventSlug);
        $entrySet = $this->getEntrySet($entrySetId);

        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($id);

        if (!$idea) {
            throw $this->createNotFoundException('No idea found for id '.$id);
        }

        if(!$this->canEditIdea($entrySet, $idea)) {
            throw new AccessDeniedException();
        }

        $attendance = $this->getCurrentUserApproved($event);
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Idea:createForm.html.twig', array(
                'idea'       => $idea,
                'group'      => $group,
                'event'      => $event,
                'entrySetId' => $entrySetId,
                'sidebar'    => true,
                'attendance' => $attendance,
                'isAdmin'    => $isAdmin,
            ));
    }


    public function editAction($groupSlug, $eventSlug, $entrySetId, $id) {

        $this->enforceUserSecurity();

        $entrySet = $this->getEntrySet($entrySetId);

        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($id);

        if(!$this->canEditIdea($entrySet, $idea)) {
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
        }
        else{
            $idea->setForCourse(false);
        }

        if (array_key_exists('amount', $params)) {
            if (!empty($params['amount'])) {
                $idea->setAmount($params['amount']);
            }
        }

        $idea->removeAllTags();
        $idea->addTags($this->parseTags($params['tags']));

        if (isset($params['isPrivate'])){
            $idea->setIsPrivate(true);
        }
        else{
            $idea->setIsPrivate(false);
        }

        $em = $this->getDoctrine()->getEntityManager();
        $em->flush();

        $ideaUrl = $this->generateUrl('idea_show', array(
                'id' => $id,
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
                'entrySetId'=> $entrySetId,
            ));
        return new RedirectResponse($ideaUrl);
    }


    public function uploadAction($groupSlug, $eventSlug, $id = null){

        $this->enforceUserSecurity();

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventSlug);

        $document = new Document();
        $form = $this->container->get('form.factory')->createNamedBuilder('form', 'image', $document)
            ->add('file')
            ->getForm()
        ;

        if ($this->getRequest()->getMethod() === 'POST') {

            $form->bindRequest($this->getRequest());

            if ($form->isValid()) {

                $em = $this->getDoctrine()->getEntityManager();

                $id = $this->getRequest()->request->get('id');
                $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($id);

                if ($document->isValid()){
                    $document->upload($id);
                }
                else{

                    $this->setFlash('error', 'You must select an image file');

                    return new RedirectResponse($this->generateUrl('idea_upload_form', array(
                            'id' => $id,
                            'groupSlug' => $groupSlug,
                            'eventSlug' => $eventSlug,
                        )));
                }

                $idea->setImage($document);
                $document->setIdea($idea);

                $em->persist($document);
                $em->flush();

                $ideaUrl = $this->generateUrl('idea_show', array(
                        'id' => $id,
                        'groupSlug' => $groupSlug,
                        'eventSlug' => $eventSlug,
                    ));
                return new RedirectResponse($ideaUrl);
            }
        }

        $attendance = $this->getCurrentUserApproved($event);
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Idea:upload.html.twig', array(
                'form'=>$form->createView(),
                'id'=>$id,
                'group' => $group,
                'event' => $event,
                'sidebar' => true,
                'attendance' => $attendance,
                'isAdmin'       => $isAdmin,
            ));
    }

    public function deleteImageAction($groupSlug, $eventSlug)
    {
        $this->enforceUserSecurity();

        $params = $this->getRequest()->request->all();

        $ideaId = $params['idea'];
        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($ideaId);

        $image = $idea->getImage();
        $image->delete();
        $idea->removeImage();

        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($image);
        $em->flush();

        $ideaUrl = $this->generateUrl('idea_show', array(
                'id' => $ideaId,
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
            ));
        return new RedirectResponse($ideaUrl);
    }

    public function addLinkAction($groupSlug, $eventSlug, $id = null)
    {

        $this->enforceUserSecurity();

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventSlug);

        $link = new Link();
        $form = $this->container->get('form.factory')->createNamedBuilder('form', 'link', $link)
            ->add('title')
            ->add('linkDescription', 'textarea', array('attr' => array('cols' => '60%')))
            ->add('url','text', array('attr' => array('size' => '60%', 'value' => 'http://')))
            ->add('type', 'choice', array(
                    'choices' => array (
                        'youtube'   =>  'YouTube',
                        'flickr'    =>  'Flickr',
                        'twitter'   =>  'Twitter',
                        'slideshare'=>  'SlideShare',
                        'other'     =>  'Other'
                    )
                ))
            ->getForm()
        ;

        if ($this->getRequest()->getMethod() === 'POST') {
            $form->bindRequest($this->getRequest());
            if ($form->isValid()) {

                $em = $this->getDoctrine()->getEntityManager();

                $id = $this->getRequest()->request->get('id');
                $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($id);

                $idea->addLink($link);
                $link->setIdea($idea);

                $em->persist($link);
                $em->flush();

                $ideaUrl = $this->generateUrl('idea_show', array(
                        'id' => $id,
                        'groupSlug' => $groupSlug,
                        'eventSlug' => $eventSlug,
                    ));
                return new RedirectResponse($ideaUrl);
            }
        }

        $attendance = $this->getCurrentUserApproved($event);
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Idea:addLink.html.twig', array(
                'form'      => $form->createView(),
                'id'        => $id,
                'group'     => $group,
                'event'     => $event,
                'sidebar'   => true,
                'attendance'=> $attendance,
                'isAdmin'   => $isAdmin,
            ));
    }

    public function deleteLinkAction($groupSlug, $eventSlug) {

        $this->enforceUserSecurity();

        $params = $this->getRequest()->request->all();

        $ideaId = $params['idea'];
        $linkId = $params['link'];
        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($ideaId);
        $link = $this->getDoctrine()->getRepository('IdeaBundle:Link')->find($linkId);
        $idea->removeLink($link);

        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($link);
        $em->flush();

        $ideaUrl = $this->generateUrl('idea_show', array(
                'id' => $ideaId,
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
            ));
        return new RedirectResponse($ideaUrl);
    }


    public function voteAction($groupSlug, $eventSlug, $entrySetId) {

        $this->enforceUserSecurity();

        $event = $this->getEvent($groupSlug, $eventSlug);
        $entrySet = $this->getEntrySet($entrySetId);

        //check for judge role here
        if (!$this->isJudge($entrySet)) {
            throw new AccessDeniedException();
        }

        $params = $this->getRequest()->request->all();
        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($params['id']);
        $userName = $this->getCurrentUser()->getUsername();
        $currentRound = $event->getCurrentRound();

        $em = $this->getDoctrine()->getEntityManager();

        //see if this voter has already voted on this idea
        $votes = $this->getDoctrine()->getRepository('IdeaBundle:Vote')->findBy(
            array('idea'  => $idea->getId(),
                  'voter' => $userName,
                  'round' => $currentRound,
            )
        );

        $criteriaList = $this->getDoctrine()->getRepository('IdeaBundle:VoteCriteria')->findByEventId($event->getId());
        foreach($criteriaList as $criteria) {
            $vote = null;
            if(count($votes) == 0) {
                //create vote object using $criteria->getid() assigned to Vote::IdeaId()
                $vote = new Vote($idea, $criteria, $currentRound);
                $vote->setVoter($userName);
            } else {
                //find the vote for this particular criteria
                foreach($votes as $criteriaVote) {
                    if($criteriaVote->getCriteria()->getId() == $criteria->getId()) {
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
                'id'        => $idea->getId(),
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
            ));
        return new RedirectResponse($ideaUrl);

    }


    public function commentAction($groupSlug, $eventSlug) {

        $this->enforceUserSecurity();

        $params = $this->getRequest()->request->all();

        $commentText = $params['comment'];
        $ideaId = $params['idea'];

        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($ideaId);

        $comment = new Comment($this->getCurrentUser(), $commentText, $idea);

        $em = $this->getDoctrine()->getEntityManager();
        $em->persist($comment);
        $em->flush();

        $ideaUrl = $this->generateUrl('idea_show', array(
                'id'        => $ideaId,
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
            ));
        return new RedirectResponse($ideaUrl);

    }

    public function commentDeleteAction($groupSlug, $eventSlug) {

        $this->enforceUserSecurity();

        $params = $this->getRequest()->request->all();

        $ideaId = $params['idea'];
        $commentId = $params['comment'];

        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($ideaId);
        $comment = $this->getDoctrine()->getRepository('IdeaBundle:Comment')->find($commentId);

        if(!$this->canRemoveComment($idea)) {
            throw new AccessDeniedException();
        }

        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($comment);
        $em->flush();

        $ideaUrl = $this->generateUrl('idea_show', array(
                'id'        => $ideaId,
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
            ));
        return new RedirectResponse($ideaUrl);
    }

    public function followAction($groupSlug, $eventSlug, Request $request) {

        $this->enforceUserSecurity();

        $params = $request->request->all();
        $ideaId = $params['id'];
        $source = $params['source'];

        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($ideaId);
        $userName = $this->getCurrentUser()->getUsername();

        $em = $this->getDoctrine()->getEntityManager();

        $followMapping = $idea->getFollowMapping($userName);

        if(!$followMapping)
        {
            $followMapping = new FollowMapping($userName, $idea);
            $idea->addFollowMapping($followMapping);
            $em->persist($followMapping);
        }
        else
        {
            $idea->removeFollowMapping($followMapping);
            $em->remove($followMapping);
        }

        $em->flush();

        if ($source == 'detail')
            $url = $this->generateUrl('idea_show', array(
                    'id'        => $ideaId,
                    'groupSlug' => $groupSlug,
                    'eventSlug' => $eventSlug,
                ));
        elseif ($source == 'list')
            $url = $this->generateUrl('idea_show_all', array(
                    'tag'       => $params['tag'],
                    'groupSlug' => $groupSlug,
                    'eventSlug' => $eventSlug,
                ));

        return new RedirectResponse($url);
    }


    public function deleteAction($groupSlug, $eventSlug, $entrySetId) {

        $this->enforceUserSecurity();

        $entrySet = $this->getEntrySet($entrySetId);

        $id = $this->getRequest()->request->get('id');
        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($id);

        if (!$this->canEditIdea($entrySet, $idea)) {
            throw new AccessDeniedException();
        }

        $user = $idea->getCreator();
        $user->removeIdea($idea);

        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($idea);
        $em->flush();

        $ideaListUrl = $this->generateUrl('idea_show_all', array(
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
            ));
        return new RedirectResponse($ideaListUrl);

    }


    public function profileAction($username = null) {

        $currentUser = $this->getCurrentUser();

        if ($username == null){
            $user = $currentUser;
        }
        else{
            $userRepo = $this->getDoctrine()->getRepository('UserBundle:User');
            $user = $userRepo->findOneBy(array('username'=>$username));
        }

        $ownProfile = ($currentUser == $user);
        $isAdmin = $this->isGranted('ROLE_ADMIN');


        $esRegRepo = $this->getDoctrine()->getRepository('IdeaBundle:EntrySetRegistry');
        $parents = array();
        foreach($user->getIdeas() as $idea) {
            $registration = $idea->getParentRegistration();
            $parent = $esRegRepo->getContainerByRegistryId($registration);
            $parents[$idea->getName()] = $parent;
        }



        return $this->render('IdeaBundle:Idea:profile.html.twig', array(
                'user'       => $user,
                'ownProfile' => $ownProfile,
                'isAdmin'    => $isAdmin,
                'sidebar'    => true,
                'parents'    => $parents,
            ));
    }

    public function profileEditAction(Request $request, $username) {

        if ($request->get('cancel') == 'Cancel') {
            return $this->redirect($this->generateUrl('profile', array(
                'username' => $username,
            )));
        }

        $currentUser = $this->getCurrentUser();

        $userRepo = $this->getDoctrine()->getRepository('UserBundle:User');
        $user = $userRepo->findOneBy(array('username'=>$username));

        if ($currentUser != $user){
            throw new AccessDeniedException();
        }
        $form = $this->container->get('form.factory')->createNamedBuilder('form', 'profile', $user, array('validation_groups' => array('ideaProfile')))
            ->add('name')
            ->add('title')
            ->add('organization')
            ->add('industry')
            ->add('aboutMe', null, array('attr' => array('rows' => '4', 'cols' => '60', 'maxlength' => '2000')))
            ->add('twitterUsername')
            ->add('professionalEmail')
            ->add('linkedIn', null, array('attr' => array('size' => '60%')))
            ->add('website', null, array('attr' => array('size' => '60%')))
            ->add('mailingAddress', null, array('attr' => array('size' => '60%')))
            ->getForm();

        if($request->getMethod() == 'POST') {

            $form->bindRequest($request);

            if($form->isValid()) {

                $em = $this->getDoctrine()->getEntityManager();
                $em->flush();

                return $this->redirect($this->generateUrl('profile', array(
                    'username' => $username,
                )));
            }
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Idea:profileForm.html.twig', array(
            'form'      => $form->createView(),
            'username'  => $username,
            'isAdmin'   => $isAdmin,
        ));

    }


    public function infoAction($groupSlug, $eventSlug, $page) {

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventSlug);

        $attendance = $this->getCurrentUserApproved($event);
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Idea:info'.$page.'.html.twig', array(
                'group'      => $group,
                'event'      => $event,
                'sidebar'    => true,
                'attendance' => $attendance,
                'isAdmin'    => $isAdmin,
            ));
    }


    //TODO: Move this to a model file?
    /******************************************************
     ****************    MODEL STUFF HERE    ***************
     *******************************************************/


    public function isLoggedIn() {
        return $this->isGranted('IS_AUTHENTICATED_REMEMBERED');
    }

    public function getSidebarState($entrySet, $idea) {

        if ($entrySet->getType() == EntrySet::TYPE_IDEA)
        {
            if($this->isGranted('ROLE_ADMIN')) {
                return IdeaController::SIDEBAR_ADMIN;
            }

            if($this->canJudge($entrySet, $idea)) {
                return IdeaController::SIDEBAR_JUDGE;
            }
        }

        return IdeaController::SIDEBAR_NONE;
    }

    public function canJudge($entrySet, $idea) {

        $user = $this->getCurrentUser();

        return $this->isJudge($entrySet) && $idea->isJudgeAssigned($user);
    }

    public function isJudge($entrySet) {

        if(!$this->isLoggedIn())
            return false;

        if (!$entrySet->getIsVotingActive())
            return false;

        $user = $this->getCurrentUser();

        return $entrySet->containsVoter($user->getUsername());
    }

    public function canCreate($entrySet) {

        if (!$entrySet->getIsSubmissionActive()){
            return false;
        }

        return $this->isLoggedIn();
    }

    public function isCreator($idea) {
        if(!$this->isLoggedIn()) {
            return false;
		}
        $username = $this->getCurrentUser()->getUsername();
        return $username === $idea->getCreator()->getUsername();
    }

    public function canEditIdea($entrySet, $idea) {

        return $this->isGranted('ROLE_ADMIN') || ($this->isCreator($idea) && $entrySet->getIsSubmissionActive());
    }

    public function canRemoveComment($idea) {

        return $this->isGranted('ROLE_ADMIN') || $this->isCreator($idea);
    }

    /**
     * Takes the user submitted string of tags, parses it, and returns an array of new tag objects
     */
    public function parseTags($allTagsString)
    {
        $newTags = array();

        $allTagsString = trim(strtolower($allTagsString));

        if(empty($allTagsString)){
            return $newTags;
        }

        $tagStrings = preg_split("/[\s,]+/", $allTagsString);
        $allTagNames = $this->getAllTagNames();

        $em = $this->getDoctrine()->getEntityManager();

        foreach ($tagStrings as $tagString)
        {
            $tagString = trim($tagString);
            if (empty($tagString)){
                continue;
            }

            if (!in_array($tagString, $allTagNames))
            {
                $newTag = new Tag($tagString);
                if(!in_array($newTag, $newTags))
                {
                    $newTags[] = $newTag;
                    $em->persist($newTag);
                }
            }
            else
            {
                $newTags[] = $this->getDoctrine()->getRepository('IdeaBundle:Tag')->find($tagString);
            }
        }
        $em->flush();
        return $newTags;
    }


    public function getGroup($groupSlug)
    {
        $group = $this->getGroupManager()->getGroupBySlug($groupSlug);

        if (!$group) {
            throw new NotFoundHttpException('Group not found.');
        }

        return $group;
    }


    public function getEvent($groupSlug, $eventSlug)
    {
        $group = $this->getGroup($groupSlug);

        $eventEm = $this->getDoctrine()->getRepository('EventBundle:GroupEvent');
        $event = $eventEm->findOneBy(
            array(
                'group' => $group->getId(),
                'slug' => $eventSlug,
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

        if (!$entrySet){
            throw new NotFoundHttpException('Entry Set not found');
        }

        return $entrySet;
    }


    /*
     * Gets array of tag name strings
     */
    public function getAllTagNames()
    {
        $tagNames = array();
        $allTags = $this->getDoctrine()->getRepository('IdeaBundle:Tag')->findAll();
        foreach ($allTags as $tag)
        {
            $tagNames[] = $tag->getTagName();
        }
        return $tagNames;
    }

    /**
     * @param $ideas
     * @param $currentIdea
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


    public function getCurrentUserApproved($event)
    {
        $rsvpRepo = $this->getDoctrine()->getRepository('EventBundle:GroupEventRsvpAction');
        $user = $this->getCurrentUser();
        $attendance = $rsvpRepo->getUserApprovedStatus($event, $user);

        return $attendance;
    }

}
?>
