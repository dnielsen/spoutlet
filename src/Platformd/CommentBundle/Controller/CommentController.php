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

        /** @var $comment \Platformd\CommentBundle\Entity\Comment */
        if (!$comment = $manager->findCommentById($id)) {
            throw new NotFoundHttpException('Comment not found.');
        }

        $url = $this->getUrlForObject($this->getObjectFromThread($comment->getThread()));
        $url .= '#commentsView';
        
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
        // Did we post a comment on a giveway or an event, news maybe ?
        $obj = $this->getObjectFromThread($form->getData()->getThread());

        $url = $this->getUrlForObject($obj);

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
     * todo - this will eventually need to be more elegant (aka, it's damned disaster...)
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

        // case deal
        if (strpos($id, 'deal-') === 0) {
            // this is a deal (deal-15)
            $pieces = explode('-', $id);
            if (count($pieces) != 2) {
                throw new \InvalidArgumentException('Invalid comment id format: '.$id);
            }

            $dealId = $pieces[1];
            $deal = $this->container->get('doctrine.orm.entity_manager')
                ->getRepository('SpoutletBundle:Deal')
                ->find($dealId)
            ;

            if (!$deal) {
                throw new NotFoundHttpException(sprintf('Cannot find Deal from thread id "%s"', $id));
            }

            return $deal;
        }

        // everything else is an abstract event and stores *just* the slug as the id
        $event = $this->findGiveawayBySlug($id);

        if (!$event) {
            throw new NotFoundHttpException(sprintf('Cannot find abstract event form thread id "%s"', $id));
        }

        return $event;
    }

    /**
     * Returns the URL to the given object
     *
     * todo - this all needs to jsut use the linkable interface. This is done on the other branch :/
     *
     * @param $obj
     * @return string
     * @throws \InvalidArgumentException
     */
    private function getUrlForObject($obj)
    {
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

            $url = $this->container->get('router')->generate($route, array('slug' => $obj->getSlug()));
        }

        return $url;
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
