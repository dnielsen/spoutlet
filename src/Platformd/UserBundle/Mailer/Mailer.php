<?php

namespace Platformd\UserBundle\Mailer;

use FOS\UserBundle\Mailer\Mailer as BaseMailer;

/**
 * Overridden to add HTML emails
 */
class Mailer extends BaseMailer
{
    /**
     * Overridden to send an HTML email.
     *
     * This means that the body must be HTML - not text like normal
     *
     * @param $renderedTemplate
     * @param $fromEmail
     * @param $toEmail
     */
    protected function sendEmailMessage($renderedTemplate, $fromEmail, $toEmail)
    {
        // Render the email, use the first line as the subject, and the rest as the body
        $renderedLines = explode("\n", trim($renderedTemplate));
        $subject = $renderedLines[0];
        $body = implode("\n", array_slice($renderedLines, 1));

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($fromEmail)
            ->setTo($toEmail)
            ->setBody($body);

        $message->setContentType("text/html");

        $this->mailer->send($message);
    }
}