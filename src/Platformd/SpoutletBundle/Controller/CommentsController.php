<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\Comment;
use Platformd\SpoutletBundle\Entity\Thread;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Platformd\SpoutletBundle\Form\Type\CommentType;
use Platformd\CEVOBundle\Api\ApiException;
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

        $em     = $this->getDoctrine()->getManager();
        $thread = $em->getRepository('SpoutletBundle:Thread')->find($params['thread']);

        if (!$thread) {
            $response->setContent(json_encode(array("success" => false)));
            return $response;
        }

        $parent = $params['parent'] ? $em->getRepository('SpoutletBundle:Comment')->find((int) $params['parent']) : null;
        $author = $this->getUser();
        $body   = $params['body'];

        if ($parent !== null) {
            $comment->setParent($parent);
            $replyCount = $parent->getReplies()->count();
        }

        $comment->setAuthor($author);
        $comment->setBody($body);
        $comment->setThread($thread);
        $comment->setCreatedAt(new \DateTime('now'));

        $thread->incrementCommentCount();

        $em->persist($comment);
        $em->persist($thread);
        $em->flush();

        $this->giveUserArp($comment);

        $this->createAcl($comment);

        $path = $this->generateUrl('comments_thread', array('threadId' => $params['thread']));
        $this->getVarnishUtil()->banCachedObject($path);

        if ($parent !== null) {
            return $this->render('SpoutletBundle:Comments:_reply.html.twig', array(
                'reply'        => $comment,
                'replyCounter' => $replyCount,
                'permalink'    => $thread->getPermalink(),
            ));
        }

        return $this->render('SpoutletBundle:Comments:_comment.html.twig', array(
            'comment'   => $comment,
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
        $em         = $this->getDoctrine()->getManager();
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

        $path = $this->generateUrl('comments_thread', array('threadId' => $comment->getThread()->getId()));
        $this->getVarnishUtil()->banCachedObject($path);

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

        $em         = $this->getDoctrine()->getManager();
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
        $comment->setDeletedReason($this->getCurrentUser() == $comment->getAuthor() ? Comment::DELETED_BY_OWNER : Comment::DELETED_BY_ADMIN);

        $em->persist($comment);
        $em->flush();

        $this->removeUserArp($comment);

        $path = $this->generateUrl('comments_thread', array('threadId' => $comment->getThread()->getId()));
        $this->getVarnishUtil()->banCachedObject($path);

        $response->setContent(json_encode(array("success" => true)));
        return $response;
    }

    public function threadAction($threadId)
    {
        $em     = $this->getDoctrine()->getManager();
        $thread = $em->getRepository('SpoutletBundle:Thread')->find($threadId);

        if (!$thread) {
            return new Response();
        }

        $comments = json_encode($this->getCommentManager()->getCommentData($thread));

        $response = $this->render('SpoutletBundle:Comments:_thread.html.twig', array(
            'thread'    => $thread,
            'comments'  => $comments,
            'permalink' => $thread->getPermalink(),
        ));

        $this->varnishCache($response, 2628000);
        return $response;
    }

    private function createThread($threadId, $object)
    {
        $thread = new Thread();
        $thread->setId($threadId);
        $thread->setPermalink($this->getUrlForObject($object).'#comments');

        $em = $this->getDoctrine()->getManager();
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
        return $this->container->get('security.authorization_checker')->isGranted($role, $object);
    }

    private function getUrlForObject($obj)
    {
        if ($obj instanceof LinkableInterface) {
            $url = $this->container->get('platformd.link.linkable_manager')->link($obj);
        } else {
            // todo - refactor everything to be a LinkableInterface
            if ($obj instanceof Giveaway) {
                $route = 'giveaway_show';
            } elseif ($obj instanceof Sweepstakes) {
                $route = 'sweepstakes_show';
            } else {
                throw new \Cannot('InvalidArgumentException figure out how to link to this type of item');
            }

            $url = $this->container->get('router')->generate($route, array('slug' => $obj->getSlug()));
        }

        return $url;
    }

    private function removeUserArp($comment)
    {
        try {
            $response = $this->getCEVOApiManager()->GiveUserXp('removecomment', $comment->getAuthor()->getCevoUserId());
        } catch (ApiException $e) {

        }
    }

    private function giveUserArp($comment)
    {
        try {
            $response = $this->getCEVOApiManager()->GiveUserXp('addcomment', $comment->getAuthor()->getCevoUserId());
        } catch (ApiException $e) {

        }
    }

    private function getCEVOApiManager()
    {
        return $this->get('pd.cevo.api.api_manager');
    }

    public function metricsAction(Request $request)
    {
        $metricManager = $this->container->get('platformd.metric_manager');
        $filterForm    = $metricManager->createFilterFormBuilder($this->get('form.factory'))->getForm();
        $from          = null;
        $to            = null;
        $requestData   = $request->query->get($filterForm->getName());
        $rangeString   = '';

        if (!empty($requestData)) {
            $filterForm->handleRequest($request);
            if ($filterForm->isValid()) {
                $data   = $filterForm->getData();

                $from   = $data['startDate'] ? : null;
                $to     = $data['endDate'] ? : null;

                $rangeString .= $from ? 'From '.$from->format('Y-m-d').' ' : '';
                $rangeString .= $to ? 'Until '.$to->format('Y-m-d') : '';
            }
        }

        $rangeString = $rangeString == '' ? 'All Time' : $rangeString;

        $stats = $this->getDoctrine()->getManager()->getRepository('SpoutletBundle:Comment')->getMetricsStats($from, $to);

        return $this->render('SpoutletBundle:CommentsAdmin:metrics.html.twig', array(
            'data' => $stats,
            'form' => $filterForm->createView(),
            'rangeString' => $rangeString,
        ));
    }
}
