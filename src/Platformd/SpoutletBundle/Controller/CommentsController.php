<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Comment;
use Platformd\SpoutletBundle\Entity\Thread;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Platformd\SpoutletBundle\Entity\Event;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Platformd\SpoutletBundle\Form\Type\CommentType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Acl\Exception\NoAceFoundException;

class CommentsController extends Controller
{

    public function newAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        if (!$this->isGranted('ROLE_USER')) {
            $response->setContent(json_encode(array("success" => false)));
            return $response;
        }

        $comment  = new Comment();

        $params   = array();
        $content  = $request->getContent();

        if (empty($content)) {
            $response->setContent(json_encode(array("success" => false)));
            return $response;
        }

        $params   = json_decode($content, true);

        if (!isset($params['thread']) || !isset($params['body']) || empty($params['body'])) {
            $response->setContent(json_encode(array("success" => false)));
            return $response;
        }

        $em     = $this->getDoctrine()->getEntityManager();

        $thread = $em->getRepository('SpoutletBundle:Thread')->find($params['thread']);

        if (!$thread) {
            $response->setContent(json_encode(array("success" => false)));
            return $response;
        }

        $parent = $params['parent'] ? $em->getRepository('SpoutletBundle:Comment')->find((int) $params['parent']) : null;
        $author = $this->getUser();
        $body   = $params['body'];
        $threadId = $thread->getId();
        $commentCount = $params['commentCount'];

        if ($parent !== null) {
            $comment->setParent($parent);
        }

        $comment->setAuthor($author);
        $comment->setBody($body);
        $comment->setThread($thread);
        $comment->setCreatedAt(new \DateTime('now'));

        $thread->incrementCommentCount();

        $em->persist($comment);
        $em->persist($thread);
        $em->flush();

        $this->createAcl($comment);

        $comments   = $em->getRepository('SpoutletBundle:Comment')->findCommentsForThreadSortedByVotes($threadId, $commentCount);

        return $this->render('SpoutletBundle:Comments:_thread.html.twig', array(
            'thread'    => $threadId,
            'comments'  => $comments,
            'offset'    => $commentCount,
            'permalink' => $thread->getPermalink(),
        ));
    }

    public function editAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        if (!$this->isGranted('ROLE_USER')) {
            $response->setContent(json_encode(array("success" => false, "details" => 'not logged in')));
            return $response;
        }

        $params   = array();
        $content  = $request->getContent();

        if (empty($content)) {
            $response->setContent(json_encode(array("success" => false, "details" => 'no content passed')));
            return $response;
        }

        $params   = json_decode($content, true);

        if(!isset($params['id']) || !isset($params['body'])) {
            $response->setContent(json_encode(array("success" => false, "details" => "There was an error processing your comment.")));
            return $response;
        }


        $id         = (int)$params['id'];
        $body       = $params['body'];
        $em         = $this->getDoctrine()->getEntityManager();
        $comment    = $em->getRepository('SpoutletBundle:Comment')->find($id);



        if (!$comment) {
            $response->setContent(json_encode(array("success" => false, "details" => 'comment not found')));
            return $response;
        }

        if (!$this->checkAcl('EDIT', $comment)) {
            $response->setContent(json_encode(array("success" => false, "details" => 'permission check failure')));
            return $response;
        }

        if(empty($body)) {
            $response->setContent(json_encode(array("success" => false, "details" => "The comment must contain at least two characters.")));
            return $response;
        }

        $comment->setBody($body);
        $em->persist($comment);
        $em->flush();

        $response->setContent(json_encode(array("success" => true, "details" => nl2br($body))));
        return $response;
    }

    public function deleteAction(Request $request) {

        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        if (!$this->isGranted('ROLE_USER')) {
            $response->setContent(json_encode(array("success" => false, "details" => 'not logged in')));
            return $response;
        }

        $params   = array();
        $content  = $request->getContent();

        if (empty($content)) {
            $response->setContent(json_encode(array("success" => false, "details" => 'no content passed')));
            return $response;
        }

        $params   = json_decode($content, true);

        if (!isset($params['commentId'])) {
            $response->setContent(json_encode(array("success" => false, "details" => 'no comment id set')));
            return $response;
        }

        $em         = $this->getDoctrine()->getEntityManager();
        $comment    = $em->getRepository('SpoutletBundle:Comment')->find($params['commentId']);

        if (!$comment) {
            $response->setContent(json_encode(array("success" => false, "details" => 'comment not found')));
            return $response;
        }

        if (!$this->checkAcl('DELETE', $comment)) {
            $response->setContent(json_encode(array("success" => false, "details" => 'permission check failure')));
            return $response;
        }

        $comment->setDeleted(true);
        $comment->setDeletedReason(Comment::DELETED_BY_OWNER);

        $em->persist($comment);
        $em->flush();

        $commentCount = $comment->getParent() ? $params['commentCount'] : $params['commentCount'] - 1;

        $threadId = $comment->getThread()->getId();

        $comments   = $em->getRepository('SpoutletBundle:Comment')->findCommentsForThreadSortedByVotes($threadId, $commentCount);

        return $this->render('SpoutletBundle:Comments:_thread.html.twig', array(
            'thread'    => $threadId,
            'comments'  => $comments,
            'offset'    => $commentCount,
            'permalink' => $thread->getPermalink(),
        ));
    }

    public function threadAction($threadId, $object, $commentLimit=5)
    {
        $em         = $this->getDoctrine()->getEntityManager();
        $thread     = $em->getRepository('SpoutletBundle:Thread')->find($threadId);

        if (!$thread) {
            $thread = $this->createThread($threadId, $object);
        }

        $correctPermalink = $this->generateUrl($object->getLinkableRouteName(), $object->getLinkableRouteParameters()).'#comments';

        if ($thread->getPermalink() != $correctPermalink) {
            $thread->setPermalink($correctPermalink);
            $em->persist($thread);
            $em->flush();
        }

        $comments   = $em->getRepository('SpoutletBundle:Comment')->findCommentsForThreadSortedByVotes($threadId, $commentLimit);

        return $this->render('SpoutletBundle:Comments:_thread.html.twig', array(
            'thread'    => $threadId,
            'comments'  => $comments,
            'offset'    => $commentLimit,
            'permalink' => $thread->getPermalink(),
        ));
    }

    public function threadSortAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');
        $params   = array();
        $content  = $request->getContent();

        if (empty($content)) {
            $response->setContent(json_encode(array("message" => "error", "details" => "no content passed")));
            return $response;
        }

        $params   = json_decode($content, true);

        if (!isset($params['thread']) || !isset($params['method'])) {
            $response->setContent(json_encode(array("message" => "error", "details" => "required content missing")));
            return $response;
        }

        $threadId       = $params['thread'];
        $method         = $params['method'];
        $commentLimit   = $params['commentLimit'];

        $em         = $this->getDoctrine()->getEntityManager();
        $thread     = $em->getRepository('SpoutletBundle:Thread')->find($threadId);

        if (!$thread) {
            $response->setContent(json_encode(array("message" => "error", "details" => "comment thread not found")));
            return $response;
        }

        switch ($method) {
            case 'votes':
                $comments   = $em->getRepository('SpoutletBundle:Comment')->findCommentsForThreadSortedByVotes($threadId, $commentLimit);
                break;

            case 'recent':
                $comments   = $em->getRepository('SpoutletBundle:Comment')->findCommentsForThreadSortedByDate($threadId, $commentLimit);
                break;

            case 'oldest':
                $comments   = $em->getRepository('SpoutletBundle:Comment')->findCommentsForThreadSortedByDate($threadId, $commentLimit, 'ASC');
                break;

            default:
                $response->setContent(json_encode(array("message" => "error", "details" => "invalid method passed")));
                return $response;
                break;
        }

        return $this->render('SpoutletBundle:Comments:_thread.html.twig', array(
            'thread'    => $threadId,
            'comments'  => $comments,
            'offset'    => $commentLimit,
            'method'    => $method,
            'permalink' => $thread->getPermalink(),
        ));
    }

    public function updateThreadAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');
        $params   = array();
        $content  = $request->getContent();

        if (empty($content)) {
            $response->setContent(json_encode(array("message" => "error", "details" => "no content passed")));
            return $response;
        }

        $params   = json_decode($content, true);

        if (!isset($params['threadId']) || !isset($params['increment']) || !isset($params['offset'])) {
            $response->setContent(json_encode(array("message" => "error", "details" => "required content missing")));
            return $response;
        }

        $threadId    = $params['threadId'];
        $increment   = $params['increment'];
        $offset      = $params['offset'];

        $em         = $this->getDoctrine()->getEntityManager();
        $thread     = $em->getRepository('SpoutletBundle:Thread')->find($threadId);

        if (!$thread) {
            $response->setContent(json_encode(array("message" => "error", "details" => "thread not found")));
            return $response;
        }

        $comments   = $em->getRepository('SpoutletBundle:Comment')->findCommentsForThreadSortedByVotesWithOffset($threadId, $offset, $increment);

        if (!$comments) {
            $response->setContent(json_encode(array("message" => "no_more_comments")));
            return $response;
        }

        return $this->render('SpoutletBundle:Comments:_comments.html.twig', array(
            'thread'    => $threadId,
            'comments'  => $comments,
            'offset'    => $offset + count($comments),
            'permalink' => $thread->getPermalink(),
        ));

    }

    private function createThread($threadId, $object)
    {
        $thread = new Thread();
        $thread->setId($threadId);
        $thread->setPermalink($this->getUrlForObject($object).'#comments');

        $em = $this->getDoctrine()->getEntityManager();
        $em->persist($thread);
        $em->flush();

        return $thread;
    }

    private function createAcl($object)
    {
        // creating the ACL
        $aclProvider = $this->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($object);
        $acl = $aclProvider->createAcl($objectIdentity);

        // grant owner access
        $securityIdentity = UserSecurityIdentity::fromAccount($this->getUser());
        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);

        $aclProvider->updateAcl($acl);

        // grant admins access
        $securityIdentityAdmin = new RoleSecurityIdentity('ROLE_ADMIN');

        try {
            $acl->isGranted(array(MaskBuilder::MASK_MASTER), array($securityIdentityAdmin));
        } catch (NoAceFoundException $e) {
            $acl->insertClassAce($securityIdentityAdmin, MaskBuilder::MASK_MASTER);
            $aclProvider->updateAcl($acl);
        }

        $securityIdentitySuperAdmin = new RoleSecurityIdentity('ROLE_SUPER_ADMIN');

        try {
            $acl->isGranted(array(MaskBuilder::MASK_MASTER), array($securityIdentitySuperAdmin));
        } catch (NoAceFoundException $e) {
            $acl->insertClassAce($securityIdentitySuperAdmin, MaskBuilder::MASK_MASTER);
            $aclProvider->updateAcl($acl);
        }
    }

    /*
        Default roles are VIEW, EDIT, CREATE, DELETE, UNDELETE, OPERATOR, MASTER, OWNER
    */
    private function checkAcl($role, $object)
    {
        return $this->container->get('security.context')->isGranted($role, $object);
    }

    private function getUrlForObject($obj)
    {
        if ($obj instanceof LinkableInterface) {
            $url = $this->container->get('platformd.link.linkable_manager')->link($obj);
        } else {
            // todo - refactor everything to be a LinkableInterface
            if ($obj instanceof Event) {
                $route = 'events_detail';
            } elseif ($obj instanceof Giveaway) {
                $route = 'giveaway_show';
            } elseif ($obj instanceof Sweepstakes) {
                $route = 'sweepstakes_show';
            } else {
                throw new \InvalidArgumentException('Cannot figure out how to link to this type of item');
            }

            $url = $this->container->get('router')->generate($route, array('slug' => $obj->getSlug()));
        }

        return $url;
    }
}
