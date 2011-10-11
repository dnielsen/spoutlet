<?php
namespace Platformd\CommentBundle\Controller;

use FOS\CommentBundle\Controller\CommentController as BaseCommentController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @author Ryan Weaver <ryan@knplabs.com>
 */
class CommentController extends BaseCommentController
{
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
