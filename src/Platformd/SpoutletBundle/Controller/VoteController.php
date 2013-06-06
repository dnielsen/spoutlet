<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Entity\AbstractVote;
use Platformd\SpoutletBundle\Entity\CommentVote;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class VoteController extends Controller
{
    public function voteAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        $params   = array();
        $content  = $request->getContent();

        if (empty($content)) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "No information was not passed.")));
            return $response;
        }

        $params = json_decode($content, true);

        if (!isset($params['id']) || !isset($params['voteType']) || !isset($params['contentType'])) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "Some required information was not passed.")));
            return $response;
        }

        $contentId      = (int) $params['id'];
        $voteType       = $params['voteType'];
        $contentType    = $params['contentType'];
        $user           = $this->getCurrentUser();
        $em             = $this->getDoctrine()->getEntityManager();

        if (!$this->container->get('security.context')->isGranted(array('ROLE_USER'))) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => 'FORCE_LOGIN_TO_VOTE')));
            return $response;
        }

        if (!in_array($voteType, AbstractVote::getValidVoteTypes())) {
            $response->setContent(json_encode(array("success" => false, "messageForUser" => "Valid vote type (up/down) not given.")));
            return $response;
        }

        switch ($contentType) {
            case 'Comment':

                $comment    = $em->getRepository('SpoutletBundle:Comment')->find($contentId);
                $voteRepo   = $em->getRepository('SpoutletBundle:CommentVote');

                $vote = $voteRepo->findOneBy(array('user' => $user->getId(), 'comment' => $comment->getId()));

                if ($vote) {
                    $vote->setVoteType($voteType);
                } else {
                    $vote = new CommentVote(
                        $user,
                        $comment,
                        $voteType,
                        $this->getClientIp($request)
                    );
                }

                $em->persist($vote);
                $em->flush();

                $votes      = $voteRepo->getVoteCountsForComment($comment);

                break;

            default:
                throw $this->createNotFoundException('Something went wrong.');
        }

        $response->setContent(json_encode(array("success" => true, "messageForUser" => $votes)));
        return $response;
    }
}
