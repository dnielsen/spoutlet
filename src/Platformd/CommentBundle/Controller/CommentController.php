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
use FOS\CommentBundle\Model\ThreadInterface;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;

/**
 * @author Ryan Weaver <ryan@knplabs.com>
 */
class CommentController extends BaseCommentController
{
    public function flatPagedAction($page, ThreadInterface $thread, $sorter = null)
    {
        $qb = $this->container->get('fos_comment.manager.comment')->findCommentsByThreadQB($thread, null, $sorter);
        $object = $this->container->get('fos_comment.manager.comment')->getObjectFromThread($thread);

        $pager = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pager->setMaxPerPage(2);
        $pager->setCurrentPage((int)$page);

        return $this->container->get('templating')->renderResponse(
            'FOSCommentBundle:Comment:flat.html.'.$this->container->getParameter('fos_comment.template.engine'),
            array(
                'pager' => $pager,
                'sorter' => $sorter,
                'object' => $object,
                'routeName' => $this->getRouteForObject($object),
                'routeParams' => $this->getRouteParamsForObject($object),
            )
        );
    }

    public function deleteAction($id)
    {
        $manager = $this->container->get('fos_comment.manager.comment');
        $router = $this->container->get('router');
        $em = $this->container->get('doctrine.orm.entity_manager');

        /** @var $comment \Platformd\CommentBundle\Entity\Comment */
        if (!$comment = $manager->findCommentById($id)) {
            throw new NotFoundHttpException('Comment not found.');
        }

        $thread = $comment->getThread();
        $thread->setNumComments($thread->getNumComments() - 1);
        $em->persist($thread);
        $em->remove($comment);
        $em->flush();

        // Not sure if the CommentBundle provides a way to delete comments easily
        // so we just use the ORM directly
        $threadSlug = $comment->getThread()->getId();

        $this->getCache()->delete($threadSlug);

        if($threadSlug == 'custom-military-page') {
            return new RedirectResponse($router->generate('military').'#commentsView');
        }

        $url = $this->getUrlForObject($this->getCommentManager()->getObjectFromThread($comment->getThread()));
        $url .= '#commentsView';

        return new RedirectResponse($url);
    }

    /**
     * Overridden so that we can redirect the user back to the event page
     */
    protected function onCreateSuccess(Form $form)
    {
        $threadId = $form->getData()->getThread()->getId();

        $this->getCache()->delete($threadId);

        // temporarily here for custom military page. will be removed when groups are implemented.
        if($threadId == 'custom-military-page') {
            $url = $this->container->get('router')->generate('military');
            return new RedirectResponse($url);
        }

        // Did we post a comment on a giveway or an event, news maybe ?
        $obj = $this->getCommentManager()->getObjectFromThread($form->getData()->getThread());

        $url = $this->getUrlForObject($obj);

        // append the dom ID to the comment, for auto-scroll
        $url .= '#comment-message-'.$form->getData()->getId();

        return new RedirectResponse($url);
    }

    protected function onCreateError(Form $form)
    {
        $threadId = $form->getData()->getThread()->getId();

        // temporarily here for custom military page. will be removed when groups are implemented.
        if($threadId == 'custom-military-page') {
            $url = $this->container->get('router')->generate('military');
            return new RedirectResponse($url);
        }

        // Did we post a comment on a giveway or an event, news maybe ?
        $obj = $this->getCommentManager()->getObjectFromThread($form->getData()->getThread());

        $url = $this->getUrlForObject($obj);

        $this->container->get('session')->setFlash('error', 'An error occurred with comment submission');

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

    public function getRouteForObject($obj)
    {
        if ($obj instanceof LinkableInterface) {
            return $obj->getLinkableRouteName();
        }

        if ($obj instanceof Event) {
            $route = 'events_detail';
        } elseif ($obj instanceof Giveaway) {
            $route = 'giveaway_show';
        } elseif ($obj instanceof Sweepstakes) {
            $route = 'sweepstakes_show';
        } else {
            throw new \InvalidArgumentException('Cannot figure out how to link to this type of item');
        }

        return $route;
    }

    public function getRouteParamsForObject($obj)
    {
        if ($obj instanceof LinkableInterface) {
            return $obj->getLinkableRouteParameters();
        }

        if ($obj instanceof Event) {
            return array('slug' => $obj->getSLug());
        } elseif ($obj instanceof Giveaway) {
            return array('slug' => $obj->getSLug());
        } elseif ($obj instanceof Sweepstakes) {
            return array('slug' => $obj->getSLug());
        }

        throw new \InvalidArgumentException('Cannot figure out how to link to this type of item');
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

    /**
     * @return \Platformd\CommentBundle\Model\CommentManager
     */
    protected function getCommentManager()
    {
        return $this->container->get('fos_comment.manager.comment');
    }

    private function getCache()
    {
        return $this->container->get('platformd.cache.comment');
    }
}
