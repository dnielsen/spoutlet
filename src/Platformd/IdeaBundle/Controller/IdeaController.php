<?php

namespace Platformd\IdeaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContext;

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

    public function indexAction($groupSlug, $eventSlug) {

		$event = $this->getEvent($groupSlug, $eventSlug);

		if(!$event) {
			return  $this->redirect($this->generateUrl('idea_admin_event', array(
                'groupSlug' => $groupSlug,
            )));
		}

    	$params = array(
            'groupSlug' => $groupSlug,
            'eventSlug' => $eventSlug,
            'no_sidebar' => true,
            'event' => $event,
        );
        return $this->render('IdeaBundle:Idea:index.html.twig', $params);
    }


	public function showAllAction($groupSlug, $eventSlug) {

        $event = $this->getEvent($groupSlug, $eventSlug);

		if(!$event) {
			return  $this->redirect($this->generateUrl('idea_admin_event', array(
                'groupSlug' => $groupSlug,
            )));
		}

		$tag = $this->getRequest()->query->get('tag');
        $submitActive = $event->getIsSubmissionActive();

		$ideaRepo = $this->getDoctrine()->getRepository('IdeaBundle:Idea');
        $ideaList = $ideaRepo->filter($event->getId(), $event->getCurrentRound(), $tag);
        $ideaRepo->sortByFollows($ideaList);

        $params = array(
            'groupSlug' => $groupSlug,
            'eventSlug' => $eventSlug,
            'ideas' => $ideaList,
            'submitActive' => $submitActive,
            'tag' => $tag,
            'round' => $event->getCurrentRound(),
        );

        return $this->render('IdeaBundle:Idea:showAll.html.twig', $params);
	}


	public function showAction($groupSlug, $eventSlug, $id) {

        $doctrine = $this->getDoctrine();
        $event = $this->getEvent($groupSlug, $eventSlug);

        if(!$event) {
            return  $this->redirect($this->generateUrl('idea_admin_event', array(
                        'groupSlug' => $groupSlug,
                    )));
        }

		$currentRound = $event->getCurrentRound();

		$ideaRepo = $doctrine->getRepository('IdeaBundle:Idea');

		$idea = $ideaRepo->find($id);

		if (!$idea) {
			throw $this->createNotFoundException('No idea found for id '.$id);
		}

        $params = array(
            'groupSlug' => $groupSlug,
            'eventSlug' => $eventSlug,
            'idea' => $idea,
            'canEdit' => $this->canEdit($idea, $event),
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
        $event = $this->getEvent($groupSlug, $eventSlug);
        if(!$event) {
            return  $this->redirect($this->generateUrl('idea_admin_event', array(
                        'groupSlug' => $groupSlug,
                    )));
        }

        if (!$this->canCreate($event)) {
            //throw new AccessDeniedException();
            return new RedirectResponse($this->generateUrl('login'));
        }
        return $this->render('IdeaBundle:Idea:createForm.html.twig', array(
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
            ));
	}

	public function createAction(Request $request, $groupSlug, $eventSlug) {

        $event = $this->getEvent($groupSlug, $eventSlug);

        if(!$event) {
            return  $this->redirect($this->generateUrl('idea_admin_event', array(
                        'groupSlug' => $groupSlug,
                    )));
        }

        if (!$this->canCreate($event)) {
            //throw new AccessDeniedException();
            return new RedirectResponse($this->generateUrl('login'));
        }

		$params = $request->request->all();

		$idea = new Idea();

        $idea->setEvent($event);
        $idea->setName($params['title']);
        $idea->setMembers($params['members']);
        $idea->setDescription($params['desc']);
        $idea->setStage($params['stage']);

        if (array_key_exists('forCourse', $params)){
            $idea->setForCourse(true);
            $idea->setProfessors($params['professors']);
        }
        else{
            $idea->setForCourse(false);
            $idea->setProfessors('');
        }

        if (!empty($params['amount'])){
            $idea->setAmount($params['amount']);
        }
        else{
            $idea->setAmount(0);
        }

        $idea->addTags($this->parseTags($params['tags']));

        if (isset($params['isPrivate'])){
            $idea->setIsPrivate(true);
        }

        $user = $this->get('security.context')->getToken()->getUser();
        $idea->setCreator($user);

        $currentRound = $event->getCurrentRound();
        $idea->setHighestRound($currentRound);

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

        $event = $this->getEvent($groupSlug, $eventSlug);

        if(!$event) {
            return  $this->redirect($this->generateUrl('idea_admin_event', array(
                        'groupSlug' => $groupSlug,
                    )));
        }

        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($id);

        if(!$this->canEdit($idea, $event)) {
            throw new AccessDeniedException();
        }

        if (!$idea) {
            throw $this->createNotFoundException('No idea found for id '.$id);
        }
        return $this->render('IdeaBundle:Idea:createForm.html.twig', array(
                'idea' => $idea,
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
            ));
    }


    public function editAction($groupSlug, $eventSlug, $id) {

        $event = $this->getEvent($groupSlug, $eventSlug);
        if(!$event) {
            return  $this->redirect($this->generateUrl('idea_admin_event', array(
                        'groupSlug' => $groupSlug,
                    )));
        }

        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($id);

        if(!$this->canEdit($idea, $event)) {
            throw new AccessDeniedException();
        }

        $params = $this->getRequest()->request->all();

        $idea->setName($params['title']);
        $idea->setMembers($params['members']);
        $idea->setDescription($params['desc']);
        $idea->setStage($params['stage']);

        if (array_key_exists('forCourse', $params)){
            $idea->setForCourse(true);
            $idea->setProfessors($params['professors']);
        }
        else{
            $idea->setForCourse(false);
            $idea->setProfessors('');
        }

        if (!empty($params['amount'])){
            $idea->setAmount($params['amount']);
        }
        else {
            $idea->setAmount(0);
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

        return $this->render('IdeaBundle:Idea:upload.html.twig', array(
                'form'=>$form->createView(),
                'id'=>$id,
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
            ));
    }

    public function deleteImageAction($groupSlug, $eventSlug)
    {
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

        $link = new Link();
        $form = $this->createFormBuilder($link)
            ->add('title')
            ->add('linkDescription', 'textarea')
            ->add('url')
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

        return $this->render('IdeaBundle:Idea:addLink.html.twig', array(
                'form'=>$form->createView(),
                'id'=>$id,
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
            ));
    }

    public function deleteLinkAction($groupSlug, $eventSlug) {
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

        $event = $this->getEvent($groupSlug, $eventSlug);
        if(!$event) {
            return  $this->redirect($this->generateUrl('idea_admin_event', array(
                        'groupSlug' => $groupSlug,
                    )));
        }

        //check for judge role here
        if (!$this->canJudge($event)) {
           throw new AccessDeniedException();
        }
        $params = $this->getRequest()->request->all();
        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($params['id']);
        $userName = $this->get('security.context')->getToken()->getUser()->getUsername();
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
                'id' => $idea->getId(),
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
            ));
        return new RedirectResponse($ideaUrl);

    }


    public function commentAction($groupSlug, $eventSlug) {

    	if (!$this->isLoggedIn()) {
            return new RedirectResponse($this->generateUrl('login'));
        }

        $params = $this->getRequest()->request->all();

        $commentText = $params['comment'];
        $id = $params['idea'];

        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($id);
        $user = $this->get('security.context')->getToken()->getUser();

        $comment = new Comment($user, $commentText, $idea);

        $em = $this->getDoctrine()->getEntityManager();
        $em->persist($comment);
        $em->flush();

        $ideaUrl = $this->generateUrl('idea_show', array(
                'id' => $id,
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
            ));
        return new RedirectResponse($ideaUrl);

    }

    public function commentDeleteAction($groupSlug, $eventSlug) {

        $params = $this->getRequest()->request->all();

        $ideaId = $params['idea'];
        $commentId = $params['comment'];

        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($ideaId);
        $comment = $this->getDoctrine()->getRepository('IdeaBundle:Comment')->find($commentId);

        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($comment);
        $em->flush();

        $ideaUrl = $this->generateUrl('idea_show', array(
                'id' => $ideaId,
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
            ));
        return new RedirectResponse($ideaUrl);
    }

    public function followAction($groupSlug, $eventSlug, Request $request) {

        if (!$this->isLoggedIn()) {
            return new RedirectResponse($this->generateURL('login'));
        }

        $params = $request->request->all();
        $ideaId = $params['id'];
        $source = $params['source'];
        $tag = $params['tag'];

        $idea = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($ideaId);
        $userName = $this->get('security.context')->getToken()->getUser()->getUsername();


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
                    'id' => $ideaId,
                    'groupSlug' => $groupSlug,
                    'eventSlug' => $eventSlug,
                ));
        elseif ($source == 'list')
            $url = $this->generateUrl('idea_show_all', array(
                    'tag' => $tag,
                    'groupSlug' => $groupSlug,
                    'eventSlug' => $eventSlug,
                ));

        return new RedirectResponse($url);
    }


    public function deleteAction($groupSlug, $eventSlug) {
        $event = $this->getEvent($groupSlug, $eventSlug);
        if(!$event) {
            return  $this->redirect($this->generateUrl('idea_admin_event', array(
                        'groupSlug' => $groupSlug,
                    )));
        }

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


	public function loginAction() {
        $request = $this->getRequest();
        $session = $request->getSession();

        // get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(
                SecurityContext::AUTHENTICATION_ERROR
            );
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }

        return $this->render(
            'IdeaBundle:Idea:login.html.twig', array(
                'last_username' => $session->get(SecurityContext::LAST_USERNAME),
                'error'         => $error,
                'groupSlug'     => $groupSlug,
                'eventSlug'     => $eventSlug,
            )
        );
    }


    public function profileAction($groupSlug, $username = null) {

        $currentUser = $this->get('security.context')->getToken()->getUser();

        if ($username == null){
            $user = $currentUser;
        }
        else{
            $userRepo = $this->getDoctrine()->getRepository('UserBundle:User');
            $user = $userRepo->findOneBy(array('username'=>$username));
        }

        $ownProfile = ($currentUser == $user);

        return $this->render('IdeaBundle:Idea:profile.html.twig', array(
                'user'=>$user,
                'ownProfile'=>$ownProfile,
                'groupSlug' => $groupSlug,
            ));
    }

    public function infoAction($groupSlug, $eventSlug, $page) {
        return $this->render('IdeaBundle:Idea:info'.$page.'.html.twig', array(
                'groupSlug' => $groupSlug,
                'eventSlug' => $eventSlug,
            ));
    }


    //TODO: Move this to a model file
    /******************************************************
    ****************    MODEL STUFF HERE    ***************
    *******************************************************/


    public function isLoggedIn() {
        return $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY');
    }

    public function canJudge($event) {
    	if(!$this->isLoggedIn())
    		return false;

        $securityContext = $this->get('security.context');
        if (!$event->getIsVotingActive())
            return false;

        $username = $securityContext->getToken()->getUser()->getUsername();
        return $event->containsVoter($username);
    }

    public function canCreate($event) {
        if (!$event->getIsSubmissionActive())
            return false;
        return $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY');
    }

    public function canEdit($idea, $event) {

        if(!$this->isLoggedIn())
            return false;

        if (!$event->getIsSubmissionActive())
            return false;

        $securityContext = $this->get('security.context');
        $username = $securityContext->getToken()->getUser()->getUsername();

        $isUserAllowed = false;
        if($username === $idea->getCreator()->getUsername() || $securityContext->isGranted('ROLE_ADMIN')) {
            $isUserAllowed = true;
        }
        return $isUserAllowed;
    }

    /**
     * Takes the user submitted string of tags, parses it, and returns an array of new tag objects
     */
    public function parseTags($tagString)
    {
        $newTags = array();

        $tagString = trim($tagString);

        if(empty($tagString)){
            return $newTags;
        }

        $tagStrings = preg_split("/[\s,]+/", $tagString);
        $allTagNames = $this->getAllTagNames();

		$em = $this->getDoctrine()->getEntityManager();

        foreach ($tagStrings as $tagString)
        {
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

}
?>
