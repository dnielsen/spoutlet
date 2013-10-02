<?php

namespace Platformd\SpoutletBundle\Model;

use Doctrine\ORM\EntityManager;

use Platformd\SpoutletBundle\Entity\SentEmail;
use Platformd\SpoutletBundle\Entity\MassEmail;
use Platformd\SpoutletBundle\QueueMessage\MassEmailQueueMessage;
use Platformd\SpoutletBundle\QueueMessage\ChunkedMassEmailQueueMessage;

use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;

use AmazonSES;
use DateTime;

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

    public function sendHtmlEmail($to, $subject, $body, $emailType = null, $site = null, $fromName = null, $fromEmail = null, $andFlush = true)
    {
        $params = $this->setupEmail($to, $subject, $body, $emailType, $site, $fromName, $fromEmail);
        $finalEmail     = array('Subject'  => array('Data' => $params['subject'], 'Charset' => 'UTF-8'), 'Body' => array('Html' => array('Data' => $params['body'], 'Charset' => 'UTF-8')));

        $this->processEmail($params, $finalEmail, $andFlush);
    }

    public function sendEmail($to, $subject, $body, $emailType = null, $site = null, $fromName = null, $fromEmail = null, $andFlush = true)
    {
        $params = $this->setupEmail($to, $subject, $body, $emailType, $site, $fromName, $fromEmail);
        $finalEmail     = array('Subject'  => array('Data' => $params['subject'], 'Charset' => 'UTF-8'), 'Body' => array('Text' => array('Data' => $params['body'], 'Charset' => 'UTF-8')));

        $this->processEmail($params, $finalEmail, $andFlush);
    }

    private function setupEmail($to, $subject, $body, $emailType = null, $site = null, $fromName = null, $fromEmail = null) {

        $params = array();

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

        $currentSite = $this->container->get('platformd.util.site_util')->getCurrentSite();

        if (!$fromName) {
            $fromName = $currentSite ? $currentSite->getSiteConfig()->getEmailFromName() : $this->container->getParameter('sender_email_name');
        }

        if (!$fromEmail) {
            $fromEmail = $currentSite ? $currentSite->getSiteConfig()->getAutomatedEmailAddress() : $this->container->getParameter('sender_email_address');
        }

        if ($this->container->getParameter('email_destination_override') === true) {
            $to = $this->container->getParameter('email_destination_override_with');
        }

        $params['to'] = $to;
        $params['subject'] = $subject;
        $params['body'] = $body;
        $params['site'] = $site;
        $params['emailType'] = $emailType;
        $params['from'] = $fromName.' <'.$fromEmail.'>';
        $params['finalTo'] = array('ToAddresses'  => array($to));

        return $params;

    }

    private function processEmail($params, $finalEmail, $andFlush)
    {
        $sentEmail = new SentEmail();

        try {
            $response  = $this->ses->send_email($params['from'], $params['finalTo'], $finalEmail);
            $messageId = $response->body->SendEmailResult->MessageId;
            $status    = $response->isOk();

            $sentEmail->setSendStatusCode((int)$response->status);
            $sentEmail->setSendStatusOk($status);

        } catch (\Exception $e) {
            $sentEmail->setSendStatusCode(-500); // Likely a curl exception
            $sentEmail->setSendStatusOk(false);
        }

        $sentEmail->setRecipient($params['to']);
        $sentEmail->setFromFull($params['from']);
        $sentEmail->setSubject($params['subject']);
        $sentEmail->setBody($params['body']);
        $sentEmail->setSesMessageId($messageId);
        $sentEmail->setSiteEmailSentFrom($params['site']);
        $sentEmail->setEmailType($params['emailType']);

        $this->em->persist($sentEmail);

        if ($andFlush) {
            $this->em->flush();
        }

        return $sentEmail;
    }

    public function queueMassEmail(MassEmail $email)
    {
        // We persist the email to the DB first so we can use its ID in the QueueMessage
        $this->em->persist($email);
        $this->em->flush();

        $message            = new MassEmailQueueMessage();
        $message->senderId  = $email->getSender()->getId();
        $message->emailType = $email->getEmailType();
        $message->emailId   = $email->getId();

        $result = $this->container->get('platformd.util.queue_util')->addToQueue($message);

        return $result;
    }

    public function queueEmails(MassEmail $email)
    {
        $emailId = $email->getId();

        if ($email->getSentToAll()) {
            $recipientIds = $this->em->createQueryBuilder('e')
                ->select('u.id')
                ->from($email->getLinkedEntityClass(), 'e')
                ->leftJoin('e.'.$email->getLinkedEntityAllRecipientsField(), 'u')
                ->andWhere('e = :linkedEntity')
                ->setParameter('linkedEntity', $email->getLinkedEntity())
                ->getQuery()
                ->getResult();
        } else {
            $recipientIds = $this->em->createQueryBuilder('e')
                ->select('u.id')
                ->from(get_class($email), 'e')
                ->leftJoin('e.recipients', 'u')
                ->andWhere('e = :email')
                ->setParameter('email', $email)
                ->getQuery()
                ->getResult();
        }

        $message            = new ChunkedMassEmailQueueMessage();
        $message->emailId   = $emailId;
        $message->senderId  = $email->getSender()->getId();
        $message->emailType = $email->getEmailType();
        $recipientCount     = 0;

        foreach ($recipientIds as $user) {
            $message->recipientIds[] = $user['id'];
            $recipientCount++;

            if ($recipientCount >= ChunkedMassEmailQueueMessage::RECIPIENT_CHUNK_SIZE) {
                $result = $this->container->get('platformd.util.queue_util')->addToQueue($message);

                $message            = new ChunkedMassEmailQueueMessage();
                $message->emailId   = $emailId;
                $message->senderId  = $email->getSender()->getId();
                $message->emailType = $email->getEmailType();

                $recipientCount     = 0;
            }
        }

        if ($recipientCount > 0) {
            $result = $this->container->get('platformd.util.queue_util')->addToQueue($message);
        }
    }

    public function sendMassEmail(MassEmail $email, ChunkedMassEmailQueueMessage $queueMessage)
    {
        $subject    = $email->getSubject();
        $message    = $email->getMessage();

        $fromName   = $email->getSender() ? ($email->getSender()->getAdminLevel() ? null : $email->getSender()->getUsername()) : null;
        $site       = $email->getSite() ? $email->getSite()->getDefaultLocale() : null;
        $emailType  = $email->getEmailType();
        $sendCount  = 0;

        $recipientEmails = $this->em->createQueryBuilder('e')
            ->select('u.email')
            ->from('UserBundle:User', 'u')
            ->andWhere('u.id IN (:ids)')
            ->setParameter('ids', $queueMessage->recipientIds)
            ->getQuery()
            ->getResult();

        foreach ($recipientEmails as $recipient) {
            $this->sendHtmlEmail($recipient['email'], $subject, $message, $emailType, $site, $fromName, null, false);
            $sendCount++;
        }

        $email->setSentAt(new DateTime());

        $this->em->persist($email);
        $this->em->flush();

        return $sendCount;
    }
}
