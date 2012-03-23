<?php
namespace Platformd\CommentBundle\Controller;

use FOS\CommentBundle\Controller\CommentController as BaseCommentController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Platformd\SpoutletBundle\Entity\Event;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Platformd\SpoutletBundle\Link\LinkableInterface;
use FOS\CommentBundle\Entity\Thread;

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
        $threadId = $form->getData()->getThread()->getId();

        // Did we post a comment on a giveway or an event, news maybe ?
        $obj = $this->getObjectFromThread($form->getData()->getThread());

        if ($obj instanceof LinkableInterface) {
            $url = $this->getLinkableUrl($obj);
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

            $url = $this->container->get('router')->generate($route, array('slug' => $threadId));
        }

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

    /**
     * Attempts to look at the Thread and find the right object
     *
     * todo - this will eventually need to be more elegant
     *
     * @param \FOS\CommentBundle\Entity\Thread $thread
     */
    private function getObjectFromThread(Thread $thread)
    {
        $id = $thread->getId();

        // case news
        if (strpos($id, 'news-') === 0) {
            // this is a news item (news-zh-15)
            $pieces = explode('-', $id);
            if (count($pieces) != 3) {
                throw new \InvalidArgumentException('Invalid comment id format: '.$id);
            }

            $newsId = $pieces[2];
            $news = $this->container->get('doctrine.orm.entity_manager')
                ->getRepository('NewsBundle:News')
                ->find($newsId)
            ;

            if (!$news) {
                throw new NotFoundHttpException(sprintf('Cannot find News from thread id "%s"', $id));
            }

            return $news;
        }

        // everything else is an abstract event and stores *just* the slug as the id
        $event = $this->findGiveawayBySlug($id);

        if (!$event) {
            throw new NotFoundHttpException(sprintf('Cannot find abstract event form thread id "%s"', $id));
        }

        return $event;
    }

    /**
     * Returns the URL for a Linkable object
     *
     * @param \Platformd\SpoutletBundle\Link\LinkableInterface $linkableObj
     * @return string
     */
    protected function getLinkableUrl(LinkableInterface $linkableObj)
    {
        return $this->container->get('platformd.link.linkable_manager')
            ->link($linkableObj);
    }
}
