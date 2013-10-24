<?php

namespace Platformd\IdeaBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
use Platformd\UserBundle\Entity\User;
use Platformd\EventBundle\Entity\Event;

class IdeaController extends Controller
{

	public function showAllAction($groupSlug, $eventSlug) {

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventSlug);

		$tag = $this->getRequest()->query->get('tag');
        $submitActive = $event->getIsSubmissionActive();

		$ideaRepo = $this->getDoctrine()->getRepository('IdeaBundle:Idea');
        $ideaList = $ideaRepo->filter($event->getId(), $event->getCurrentRound(), $tag);
        $ideaRepo->sortByFollows($ideaList);

        $attendance = $this->getCurrentUserApproved($event);
        $isAdmin = $this->getSecurity()->isGranted('ROLE_ADMIN');

        $params = array(
            'group'         => $group,
            'event'         => $event,
            'ideas'         => $ideaList,
            'submitActive'  => $submitActive,
            'tag'           => $tag,
            'round'         => $event->getCurrentRound(),
            'sidebar'       => true,
            'attendance'    => $attendance,
            'isAdmin'       => $isAdmin,
        );

        return $this->render('IdeaBundle:Idea:showAll.html.twig', $params);
	}


	public function showAction($groupSlug, $eventSlug, $id) {

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventSlug);

		$currentRound = $event->getCurrentRound();

        $doctrine = $this->getDoctrine();
		$ideaRepo = $doctrine->getRepository('IdeaBundle:Idea');

		$idea = $ideaRepo->find($id);

		if (!$idea) {
			throw $this->createNotFoundException('No idea found for id '.$id);
		}

        $attendance = $this->getCurrentUserApproved($event);
        $isAdmin = $this->getSecurity()->isGranted('ROLE_ADMIN');

        $params = array(
            'group' => $group,
            'event' => $event,
            'idea' => $idea,
            'canEdit' => $this->canEdit($idea, $event),
            'sidebar' => true,
            'attendance' => $attendance,
            'isAdmin'       => $isAdmin,
        );


        // Do vote sidebar stuff
        $criteriaList = $doctrine->getRepository('IdeaBundle:VoteCriteria')->findByEventId($event->getId());
        $params['isVoting'] = $this->canJudge($event) && count($criteriaList) > 0;
		if( $params['isVoting'] ) {

            // determine previous idea, next idea
            $ideas = $ideaRepo->filter($event->getId(), $currentRound);
            $ideaRepo->sortByFollows($ideas);

            $currentIdeaFound = false;
            $previousIdea = null;
            $nextIdea = null;

            foreach($ideas as $currentIdea) {
                if($currentIdeaFound) {
                    $nextIdea = $currentIdea;
                    break;
                }

                if($idea->getId() == $currentIdea->getId()) {
                    $currentIdeaFound = true;
                } else {
                    $previousIdea = $currentIdea;
                }
            }

            if($nextIdea){
                $params['next'] = $nextIdea->getId();
            }
            if($previousIdea){
                $params['previous'] = $previousIdea->getId();
            }

            //Pass all VoteCriteria to template for rendering
            $params['criteriaList'] = $criteriaList;

            //Pass previous vote values to the template keyed by category

            $userName = $this->get('security.context')->getToken()->getUser()->getUsername();

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


	public function createFormAction($groupSlug, $eventSlug) {

        $this->basicSecurityCheck('ROLE_USER');

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventSlug);

        $attendance = $this->getCurrentUserApproved($event);
        $isAdmin = $this->getSecurity()->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Idea:createForm.html.twig', array(
                'group' => $group,
                'event' => $event,
                'sidebar' => true,
                'attendance' => $attendance,
                'isAdmin'       => $isAdmin,
            ));
	}

	public function createAction(Request $request, $groupSlug, $eventSlug) {

        $this->basicSecurityCheck('ROLE_USER');

        $event = $this->getEvent($groupSlug, $eventSlug);

        if (!$this->canCreate($event)) {
            return new RedirectResponse($this->generateUrl('idea_create_form', array(
                    'groupSlug' => $groupSlug,
                    'eventSlug' => $eventSlug)));
        }

		$params = $request->request->all();

		$idea = new Idea();

        $idea->setEvent($event);
        $idea->setCreator($this->getCurrentUser());
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
            ));
        return new RedirectResponse($ideaUrl);
	}


    public function editFormAction($groupSlug, $eventSlug, $id) {

        $this->basicSecurityCheck('ROLE_USER');

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventSlug);

        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($id);

        if (!$idea) {
            throw $this->createNotFoundException('No idea found for id '.$id);
        }

        if(!$this->canEdit($idea, $event)) {
            throw new AccessDeniedException();
        }

        $attendance = $this->getCurrentUserApproved($event);
        $isAdmin = $this->getSecurity()->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Idea:createForm.html.twig', array(
                'idea' => $idea,
                'group' => $group,
                'event' => $event,
                'sidebar' => true,
                'attendance' => $attendance,
                'isAdmin'       => $isAdmin,
            ));
    }


    public function editAction($groupSlug, $eventSlug, $id) {

        $this->basicSecurityCheck('ROLE_USER');

        $event = $this->getEvent($groupSlug, $eventSlug);

        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($id);

        if(!$this->canEdit($idea, $event)) {
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
            ));
        return new RedirectResponse($ideaUrl);
    }


    public function uploadAction($groupSlug, $eventSlug, $id = null){

        $this->basicSecurityCheck('ROLE_USER');

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventSlug);

        $document = new Document();
        $form = $this->createFormBuilder($document)
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
        $isAdmin = $this->getSecurity()->isGranted('ROLE_ADMIN');

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
        $this->basicSecurityCheck('ROLE_USER');

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

        $this->basicSecurityCheck('ROLE_USER');

        $group = $this->getGroup($groupSlug);
        $event = $this->getEvent($groupSlug, $eventSlug);

        $link = new Link();
        $form = $this->container->get('form.factory')->createNamedBuilder('form', 'link', $link)
            ->add('title')
            ->add('linkDescription', 'textarea', array('attr' => array('cols' => '60%')))
            ->add('url','text', array('attr' => array('size' => '60%')))
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
        $isAdmin = $this->getSecurity()->isGranted('ROLE_ADMIN');

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

        $this->basicSecurityCheck('ROLE_USER');

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


    public function voteAction($groupSlug, $eventSlug) {

        $this->basicSecurityCheck('ROLE_USER');

        $event = $this->getEvent($groupSlug, $eventSlug);

        //check for judge role here
        if (!$this->canJudge($event)) {
            throw new AccessDeniedException();
        }

        $params = $this->getRequest()->request->all();
        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($params['id']);
        $userName = $this->getCurrentUser()->getUsername();
        $currentRound = $event->getCurrentRound();

        $em = $this->getDoctrine()->getEntityManager();

        //see if this voter has already voted on this idea
        $votes = $this->getDoctrine()->getRepository('IdeaBundle:Vote')->findBy(array('idea' => $idea->getId(), 'voter' => $userName));

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

        $this->basicSecurityCheck('ROLE_USER');

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

        $this->basicSecurityCheck('ROLE_USER');

        $params = $this->getRequest()->request->all();

        $ideaId = $params['idea'];
        $commentId = $params['comment'];

        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($ideaId);
        $comment = $this->getDoctrine()->getRepository('IdeaBundle:Comment')->find($commentId);

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

        $this->basicSecurityCheck('ROLE_USER');

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


    public function deleteAction($groupSlug, $eventSlug) {

        $this->basicSecurityCheck('ROLE_USER');

        $event = $this->getEvent($groupSlug, $eventSlug);

        $id = $this->getRequest()->request->get('id');
        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($id);

        if (!$this->canEdit($idea, $event)) {
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
        $isAdmin = $this->getSecurity()->isGranted('ROLE_ADMIN');

        return $this->render('IdeaBundle:Idea:profile.html.twig', array(
                'user'       => $user,
                'ownProfile' => $ownProfile,
                'isAdmin'    => $isAdmin,
                'sidebar'    => true,
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

        $isAdmin = $this->getSecurity()->isGranted('ROLE_ADMIN');

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
        $isAdmin = $this->getSecurity()->isGranted('ROLE_ADMIN');

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
        return $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY');
    }

    public function canJudge($event) {

        if(!$this->isLoggedIn())
            return false;

        if (!$event->getIsVotingActive())
            return false;

        $username = $this->getCurrentUser()->getUsername();

        return $event->containsVoter($username);
    }

    public function canCreate($event) {

        if (!$event->getIsSubmissionActive()){
            return false;
        }

        //TODO: Check to see if user is member of group/has joined event
        return true;
    }

    public function canEdit($idea, $event)
    {
        if(!$this->isLoggedIn() or !$this->canCreate($event))
            return false;

        $security = $this->getSecurity();
        $username = $security->getToken()->getUser()->getUsername();

        if($username === $idea->getCreator()->getUsername() || $security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return false;
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
        $groupEm = $this->getDoctrine()->getRepository('GroupBundle:Group');
        $group = $groupEm->findOneBySlug($groupSlug);

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


    public function getCurrentUserApproved($event)
    {
        $rsvpRepo = $this->getDoctrine()->getRepository('EventBundle:GroupEventRsvpAction');
        $user = $this->getCurrentUser();
        $attendance = $rsvpRepo->getUserApprovedStatus($event, $user);

        return $attendance;
    }

}
?>
