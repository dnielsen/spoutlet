<?php
namespace Platformd\CommentBundle\Controller;

use FOS\CommentBundle\Controller\CommentController as BaseCommentController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Platformd\SpoutletBundle\Entity\Event;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\SweepstakesBundle\Entity\Sweepstakes;

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
        $threadSlug = $comment->getThread()->getId();

        $route = !is_null($this->findGiveawayBySlug($threadSlug)) ? 'giveaway_show' : 'events_detail';
        
        $url = $this->container->get('router')->generate($route, array('slug' => $threadSlug));
        
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

        // Did we post a comment on a giveway or an event ?

        $event = $this->findGiveawayBySlug($threadSlug);
        if ($event instanceof Event) {
            $route = 'events_detail';
        } elseif ($event instanceof Giveaway) {
            $route = 'giveaway_show';
        } elseif ($event instanceof Sweepstakes) {
            $route = 'sweepstakes_show';
        } else {
            $route = 'homepage';
        }

        $url = $this->container->get('router')->generate($route, array('slug' => $threadSlug));

        // append the dom ID to the comment, for auto-scroll
        $url .= '#comment-message-'.$form->getData()->getId();

        return new RedirectResponse($url);
    }

    /** 
     * Syntactic sugar to retrieve a giveaway using its slug
     * 
     * @param string $slug
     * @return Giveaway|null
     */
    private function findGiveawayBySlug($slug) 
    {

        return $this->container->get('doctrine.orm.entity_manager')
            ->getRepository('SpoutletBundle:AbstractEvent')
            ->findOneBy(array('slug' => $slug));
    }
}
