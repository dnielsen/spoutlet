<?php
namespace Platformd\CommentBundle\Controller;

use FOS\CommentBundle\Controller\CommentController as BaseCommentController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @author Ryan Weaver <ryan@knplabs.com>
 */
class CommentController extends BaseCommentController
{
    
    public function deleteAction($id) 
    {
        $manager = $this->container->get('fos_comment.manager.comment');

        if (!$comment = $manager->findCommentById($id)) {
            
            throw new NotFoundHttpException('Comment not found.');
        }
        
        // Not sure if the CommentBundle provides a way to delete comments easily
        // so we just use the ORM directly

        $url = $this->container->get('router')->generate('events_detail', array('slug' => $comment->getThread()->getId()));
        
        $em = $this->container->get('doctrine.orm.entity_manager');

        $thread = $comment->getThread();
        $thread->setNumComments($thread->getNumComments() - 1);
        $em->persist($thread);

        $em->remove($comment);

        $em->flush();

        return new RedirectResponse($url);
    }

    /**
     * Overridden so that we can redirect the user back to the event page
     */
    protected function onCreateSuccess(Form $form)
    {
        $threadSlug = $form->getData()->getThread()->getId();
        $url = $this->container->get('router')->generate('events_detail', array('slug' => $threadSlug));

        // append the dom ID to the comment, for auto-scroll
        $url .= '#comment-message-'.$form->getData()->getId();

        return new RedirectResponse($url);
    }
}
