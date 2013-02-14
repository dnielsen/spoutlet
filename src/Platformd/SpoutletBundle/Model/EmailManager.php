<?php

namespace Platformd\SpoutletBundle\Model;

use Doctrine\ORM\EntityManager;
use Platformd\SpoutletBundle\Entity\SentEmail;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;
use AmazonSES;

class EmailManager
{
    private $container;
    private $em;
    private $ses;

    public function __construct(ContainerInterface $container, EntityManager $em, AmazonSES $ses)
    {
        $this->container        = $container;
        $this->em               = $em;
        $this->ses              = $ses;
    }

    public function sendEmail($to, $subject, $body, $emailType = null, $site = null, $fromName = null, $fromEmail = null)
    {
        if ($to == null) {
            $to = "emailNotSet@example.com";
        }

        if ($subject == null) {
            $subject = "";
        }

        if ($body == null) {
            $body = "";
        }

        if ($site == null) {
            $site = "Not Specified";
        }

        if (!$emailType) {
            $emailType = "Not Specified";
        }

        if (!$fromName) {
            $fromName = $this->container->getParameter('sender_email_name');
        }

        if (!$fromEmail) {
            $fromEmail = $this->container->getParameter('sender_email_address');
        }

        if ($this->container->getParameter('email_destination_override') === true) {
            $to = $this->container->getParameter('email_destination_override_with');
        }

        $finalEmail     = array('Subject'  => array('Data' => $subject, 'Charset' => 'UTF-8'), 'Body' => array('Html' => array('Data' => $body, 'Charset' => 'UTF-8')));
        $finalFrom      = $fromName.' <'.$fromEmail.'>';
        $finalTo        = array('ToAddresses'  => array($to));

        $response       = $this->ses->send_email($finalFrom, $finalTo, $finalEmail);

        $messageId      = $response->body->SendEmailResult->MessageId;
        $status         = $response->isOk();

        $sentEmail      = new SentEmail();

        $sentEmail->setRecipient($to);
        $sentEmail->setFromFull($finalFrom);
        $sentEmail->setSubject($subject);
        $sentEmail->setBody($body);
        $sentEmail->setSesMessageId($messageId);
        $sentEmail->setSendStatusCode((int)$response->status);
        $sentEmail->setSendStatusOk($status);
        $sentEmail->setSiteEmailSentFrom($site);
        $sentEmail->setEmailType($emailType);

        $this->em->persist($sentEmail);
        $this->em->flush();

        return $sentEmail;
    }
}
