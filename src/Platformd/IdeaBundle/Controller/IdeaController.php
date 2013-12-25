<?php

namespace Platformd\IdeaBundle\Controller;

use Platformd\GroupBundle\Entity\Group;
use Platformd\EventBundle\Entity\GroupEvent;
use Symfony\Component\Form\Exception\NotValidException;
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

	const SIDEBAR_NONE  = 0;
    const SIDEBAR_JUDGE = 1;
    const SIDEBAR_ADMIN = 2;


    public function entrySetViewAction(Request $request, $entrySetId)
    {
        $entrySet   = $this->getEntrySet($entrySetId);

        list($group, $event, $entrySet, $idea) = $this->getHierarchy($entrySet);

        $tag         	= $request->query->get('tag');
        $viewPrivate 	= $request->query->get('viewPrivate', false);
        $sortBy      	= $request->query->get('sortBy', 'vote');
        $showAllRounds  = $request->query->get('showAllRounds', 'false');

        //filter the idea list using the query parameters
        $userParam  = $viewPrivate ? $this->getCurrentUser() : null;
        $round = null;

        $canSubmit = $entrySet->getIsSubmissionActive();
        if ($event) {
            $round = $event->getCurrentRound();
            $roundParam = $showAllRounds == 'true' ? null : $round;
            $canSubmit = $canSubmit && ($event->isUserAttending($this->getCurrentUser()) || $event->getUser() == $this->getCurrentUser());
        } else {
            $roundParam = null;
            if ($group){
                $canSubmit = $canSubmit && ($group->isMember($this->getCurrentUser()) || $group->isOwner($this->getCurrentUser()) );
            }
        }

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

        $attendance = $this->getCurrentUserApproved($entrySet);

        $params = array(
            'group'         => $group,
            'event'         => $event,
            'entrySet'      => $entrySet,
            'ideas'         => $ideaList,
            'breadCrumbs'   => $this->getBreadCrumbsString($entrySet),
            'round'         => $round,
            'canSubmit'     => $canSubmit,
            'tag'           => $tag,
            'sidebar'       => true,
            'attendance'    => $attendance,
            'viewPrivate'   => $viewPrivate,
            'sortBy'        => $sortBy,
            'isAdmin'       => $isAdmin,
            'isJudge'       => $this->isJudge($entrySet),
            'showAllRounds' => $showAllRounds,
        );

        return $this->render('IdeaBundle:Idea:entrySetView.html.twig', $params);
    }


    public function showAction($entrySetId, $entryId)
    {
        $idea = $this->getEntry($entryId);
        list($group, $event, $entrySet, $idea) = $this->getHierarchy($idea);

        $attendance = $this->getCurrentUserApproved($entrySet);
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $params = array(
            'group' 			=> $group,
            'event' 			=> $event,
            'entrySet'          => $entrySet,
            'idea' 				=> $idea,
            'breadCrumbs'       => $this->getBreadCrumbsString($idea),
            'canEdit' 			=> $this->canEditIdea($entrySet, $idea),
			'canRemoveComments' => $this->canRemoveComment($idea),
            'sidebar' 			=> true,
            'attendance' 		=> $attendance,
            'isAdmin'       	=> $isAdmin,
        );


        // Do vote sidebar stuff
        $sidebarState = $this->getSidebarState($entrySet, $idea);

        //Disable Judge mode if no criteria defined yet
        if ($event)
        {
            $currentRound = $event->getCurrentRound();
            $criteriaList = $this->getDoctrine()->getRepository('IdeaBundle:VoteCriteria')->findByEventId($event->getId());
            if($sidebarState == IdeaController::SIDEBAR_JUDGE && count($criteriaList) <= 0)
            {
                $sidebarState = IdeaController::SIDEBAR_NONE;
            }
        }
        else {
            $sidebarState = IdeaController::SIDEBAR_NONE;
        }

        //pass state into twig
        $params['sidebarState'] = $sidebarState;

        $user = $this->getCurrentUser();

        $doctrine = $this->getDoctrine();
        $ideaRepo = $doctrine->getRepository('IdeaBundle:Idea');

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


    public function createFormAction($entrySetId) {

        $this->enforceUserSecurity();

        $entrySet = $this->getEntrySet($entrySetId);
        list($group, $event, $entrySet, $idea) = $this->getHierarchy($entrySet);

        $attendance = $this->getCurrentUserApproved($entrySet);
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Idea:createForm.html.twig', array(
                'group'      => $group,
                'event'      => $event,
                'entrySet'   => $entrySet,
                'breadCrumbs'=> $this->getBreadCrumbsString($entrySet),
                'sidebar'    => true,
                'attendance' => $attendance,
                'isAdmin'    => $isAdmin,
            ));
    }

    public function createAction(Request $request, $entrySetId) {

        $this->enforceUserSecurity();

        $entrySet = $this->getEntrySet($entrySetId);
        $parent = $this->getParentByEntrySet($entrySet);

        if (!$this->canCreate($entrySet)) {
            return new RedirectResponse($this->generateUrl('entry_set_view', array(
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

        if ($parent instanceof GroupEvent){
            $idea->setHighestRound($parent->getCurrentRound());
        }
        else {
            $idea->setHighestRound(1);
        }

        $em = $this->getDoctrine()->getEntityManager();
        $em->persist($idea);
        $em->flush();

        $ideaUrl = $this->generateUrl('idea_show', array(
            'entrySetId'=> $entrySetId,
            'entryId'   => $idea->getId(),
            ));
        return new RedirectResponse($ideaUrl);
    }


    public function editFormAction($entrySetId, $entryId) {

        $this->enforceUserSecurity();

        $idea = $this->getEntry($entryId);
        list($group, $event, $entrySet, $idea) = $this->getHierarchy($idea);

        if(!$this->canEditIdea($entrySet, $idea)) {
            throw new AccessDeniedException();
        }

        $attendance = $this->getCurrentUserApproved($entrySet);
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Idea:createForm.html.twig', array(
                'group'      => $group,
                'event'      => $event,
                'entrySet'   => $entrySet,
                'idea'       => $idea,
                'breadCrumbs'=> $this->getBreadCrumbsString($idea),
                'sidebar'    => true,
                'attendance' => $attendance,
                'isAdmin'    => $isAdmin,
            ));
    }


    public function editAction($entrySetId, $entryId)
    {
        $this->enforceUserSecurity();

        $idea     = $this->getEntry($entryId);
        $entrySet = $this->getEntrySet($entrySetId);

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
                'entrySetId'  => $entrySetId,
                'entryId'     => $entryId,
            ));
        return new RedirectResponse($ideaUrl);
    }


    public function uploadAction($entrySetId, $entryId)
    {
        $this->enforceUserSecurity();

        $idea = $this->getEntry($entryId);
        list($group, $event, $entrySet, $idea) = $this->getHierarchy($idea);

        $document = new Document();
        $form = $this->container->get('form.factory')->createNamedBuilder('form', 'image', $document)
            ->add('file')
            ->getForm()
        ;

        if ($this->getRequest()->getMethod() === 'POST') {

            $form->bindRequest($this->getRequest());

            if ($form->isValid()) {


                if ($document->isValid()){
                    $document->upload($entryId);
                }
                else{

                    $this->setFlash('error', 'You must select an image file');

                    return new RedirectResponse($this->generateUrl('idea_upload_form', array(
                            'entrySetId'=> $entrySetId,
                            'entryId'   => $entryId,
                        )));
                }

                $idea->setImage($document);
                $document->setIdea($idea);

                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($document);
                $em->flush();

                $ideaUrl = $this->generateUrl('idea_show', array(
                        'entrySetId'=> $entrySetId,
                        'entryId'   => $entryId,
                    ));
                return new RedirectResponse($ideaUrl);
            }
        }

        $attendance = $this->getCurrentUserApproved($entrySet);
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Idea:upload.html.twig', array(
                'group'     => $group,
                'event'     => $event,
                'entrySet'  => $entrySet,
                'idea'      => $idea,
                'breadCrumbs'=> $this->getBreadCrumbsString($idea),
                'form'      => $form->createView(),
                'sidebar'   => true,
                'attendance'=> $attendance,
                'isAdmin'   => $isAdmin,
            ));
    }

    public function deleteImageAction($entrySetId, $entryId)
    {
        $this->enforceUserSecurity();

        $idea = $this->getEntry($entryId);

        $image = $idea->getImage();
        $image->delete();
        $idea->removeImage();

        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($image);
        $em->flush();

        $ideaUrl = $this->generateUrl('idea_show', array(
                'entrySetId'=> $entrySetId,
                'entryId'   => $entryId,
            ));
        return new RedirectResponse($ideaUrl);
    }

    public function addLinkAction($entrySetId, $entryId)
    {
        $this->enforceUserSecurity();

        $idea = $this->getEntry($entryId);
        list($group, $event, $entrySet, $idea) = $this->getHierarchy($idea);

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

                $idea->addLink($link);
                $link->setIdea($idea);

                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($link);
                $em->flush();

                $ideaUrl = $this->generateUrl('idea_show', array(
                        'entrySetId'=> $entrySetId,
                        'entryId'   => $entryId,
                    ));
                return new RedirectResponse($ideaUrl);
            }
        }

        $attendance = $this->getCurrentUserApproved($entrySet);
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Idea:addLink.html.twig', array(
                'group'     => $group,
                'event'     => $event,
                'entrySet'  => $entrySet,
                'idea'      => $idea,
                'breadCrumbs'=> $this->getBreadCrumbsString($idea),
                'form'      => $form->createView(),
                'sidebar'   => true,
                'attendance'=> $attendance,
                'isAdmin'   => $isAdmin,
            ));
    }

    public function deleteLinkAction(Request $request, $entrySetId, $entryId)
    {
        $this->enforceUserSecurity();

        $idea = $this->getEntry($entryId);

        $linkId = $request->get('link');
        $link = $this->getDoctrine()->getRepository('IdeaBundle:Link')->find($linkId);

        $idea->removeLink($link);

        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($link);
        $em->flush();

        $ideaUrl = $this->generateUrl('idea_show', array(
                'entrySetId'=> $entrySetId,
                'entryId'   => $entryId,
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

        $em = $this->getDoctrine()->getEntityManager();

        //see if this voter has already voted on this idea
        $votes = $this->getDoctrine()->getRepository('IdeaBundle:Vote')->findBy(
            array('idea'  => $entryId,
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
                'entrySetId'=> $entrySetId,
                'entryId'   => $entryId,
            ));
        return new RedirectResponse($ideaUrl);
    }


    public function commentAction(Request $request, $entrySetId, $entryId) {

        $this->enforceUserSecurity();

        $commentText = $request->get('comment');
        $idea = $this->getEntry($entryId);

        $comment = new Comment($this->getCurrentUser(), $commentText, $idea);

        $em = $this->getDoctrine()->getEntityManager();
        $em->persist($comment);
        $em->flush();

        $ideaUrl = $this->generateUrl('idea_show', array(
                'entrySetId'=> $entrySetId,
                'entryId'   => $entryId,
            ));
        return new RedirectResponse($ideaUrl);
    }

    public function commentDeleteAction(Request $request, $entrySetId, $entryId)
    {
        $this->enforceUserSecurity();
        $commentId = $request->get('comment');

        $idea = $this->getEntry($entryId);
        $comment = $this->getDoctrine()->getRepository('IdeaBundle:Comment')->find($commentId);

        if(!$this->canRemoveComment($idea)) {
            throw new AccessDeniedException();
        }

        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($comment);
        $em->flush();

        $ideaUrl = $this->generateUrl('idea_show', array(
                'entrySetId'=> $entrySetId,
                'entryId'   => $entryId,
            ));
        return new RedirectResponse($ideaUrl);
    }

    public function followAction(Request $request, $entrySetId, $entryId) {

        $this->enforceUserSecurity();

        $params   = $request->request->all();
        $source   = $params['source'];

        $idea     = $this->getEntry($entryId);
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

        if ($source == 'detail') {
            $url = $this->generateUrl('idea_show', array(
                    'entrySetId'=> $entrySetId,
                    'entryId'   => $entryId,
                ));
        }
        elseif ($source == 'list') {
            $url = $this->generateUrl('entry_set_view', array(
                    'entrySetId'=> $entrySetId,
                    'tag'       => $params['tag'],
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

        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($idea);
        $em->flush();

        $ideaListUrl = $this->generateUrl('entry_set_view', array(
                'entrySetId'=> $entrySetId,
            ));
        return new RedirectResponse($ideaListUrl);
    }


    public function profileAction($username = null)
    {
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

        $parents = array();
        foreach($user->getIdeas() as $idea) {
            $parent = $this->getParentByIdea($idea);
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

    public function getEntry($entryId)
    {
        $entryRepo = $this->getDoctrine()->getRepository('IdeaBundle:Idea');
        $entry = $entryRepo->find($entryId);

        if (!$entry){
            throw new NotFoundHttpException('Entry '.$entryId.' not found');
        }

        return $entry;
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


    public function getCurrentUserApproved($entrySet)
    {
        $parent = $this->getParentByEntrySet($entrySet);
        if ($parent instanceof GroupEvent){
            $rsvpRepo = $this->getDoctrine()->getRepository('EventBundle:GroupEventRsvpAction');
            $user = $this->getCurrentUser();
            $attendance = $rsvpRepo->getUserApprovedStatus($parent, $user);
        }
        else {
            $attendance = 'approved';
        }

        return $attendance;
    }

    public function getParentByIdea($idea)
    {
        $esRegistration = $idea->getParentRegistration();
        $esRegRepo = $this->getDoctrine()->getRepository('IdeaBundle:EntrySetRegistry');

        return $esRegRepo->getContainer($esRegistration);
    }
    public function getParentByEntrySet($entrySet)
    {
        $parentRegistration = $entrySet->getEntrySetRegistration();
        $esRegRepo = $this->getDoctrine()->getRepository('IdeaBundle:EntrySetRegistry');

        return $esRegRepo->getContainer($parentRegistration);
    }

    public function getBreadCrumbsString($scope)
    {
        $breadCrumbs = $this->getHierarchy($scope);

        $breadCrumbsHtml = "";

        foreach ($breadCrumbs as $crumb) {
            if ($crumb && $crumb != $scope){
                $breadCrumbsHtml = $breadCrumbsHtml."> <a href=\"".$this->generateUrl($crumb->getLinkableRouteName(), $crumb->getLinkableRouteParameters())."\">".$crumb->getName()."</a> ";
            }
        }

        return $breadCrumbsHtml;
    }

    public function getHierarchy($scope)
    {
        $group    = null;
        $event    = null;
        $entrySet = null;
        $entry    = null;

        $entrySetParent   = null;

        if ($scope instanceof Idea) {
            $entry          = $scope;
            $entrySet       = $entry->getEntrySet();
            $entrySetParent = $this->getParentByEntrySet($entrySet);
        }
        elseif ($scope instanceof EntrySet) {
            $entrySet       = $scope;
            $entrySetParent = $this->getParentByEntrySet($entrySet);
        }
        elseif ($scope instanceof GroupEvent) {
            $event          = $scope;
            $group          = $event->getGroup();
        }
        elseif ($scope instanceof Group) {
            $group          = $scope;
        }

        if ($entrySetParent instanceof GroupEvent) {
            $event = $entrySetParent;
            $group = $event->getGroup();
        }
        elseif ($entrySetParent instanceof Group) {
            $group = $entrySetParent;
        }

        return array(
            $group,
            $event,
            $entrySet,
            $entry,
        );
    }

}
?>
