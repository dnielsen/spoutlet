<?php

namespace Platformd\SpoutletBundle\Model;

use Doctrine\ORM\EntityManager;

use Platformd\SpoutletBundle\Entity\SentEmail;
use Platformd\SpoutletBundle\Entity\MassEmail;

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

    public function sendHtmlEmail($to, $subject, $body, $emailType = null, $site = null, $fromName = null, $fromEmail = null)
    {
        $params = $this->setupEmail($to, $subject, $body, $emailType, $site, $fromName, $fromEmail);
        $finalEmail     = array('Subject'  => array('Data' => $params['subject'], 'Charset' => 'UTF-8'), 'Body' => array('Html' => array('Data' => $params['body'], 'Charset' => 'UTF-8')));

        $this->processEmail($params, $finalEmail);
    }

    public function sendEmail($to, $subject, $body, $emailType = null, $site = null, $fromName = null, $fromEmail = null)
    {
        $params = $this->setupEmail($to, $subject, $body, $emailType, $site, $fromName, $fromEmail);
        $finalEmail     = array('Subject'  => array('Data' => $params['subject'], 'Charset' => 'UTF-8'), 'Body' => array('Text' => array('Data' => $params['body'], 'Charset' => 'UTF-8')));

        $this->processEmail($params, $finalEmail);
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

    private function processEmail($params, $finalEmail)
    {
        $response       = $this->ses->send_email($params['from'], $params['finalTo'], $finalEmail);

        $messageId      = $response->body->SendEmailResult->MessageId;
        $status         = $response->isOk();

        $sentEmail      = new SentEmail();

        $sentEmail->setRecipient($params['to']);
        $sentEmail->setFromFull($params['from']);
        $sentEmail->setSubject($params['subject']);
        $sentEmail->setBody($params['body']);
        $sentEmail->setSesMessageId($messageId);
        $sentEmail->setSendStatusCode((int)$response->status);
        $sentEmail->setSendStatusOk($status);
        $sentEmail->setSiteEmailSentFrom($params['site']);
        $sentEmail->setEmailType($params['emailType']);

        $this->em->persist($sentEmail);
        $this->em->flush();

        return $sentEmail;
    }

    public function sendMassEmail(MassEmail $email, $type=null)
    {
        $subject    = $email->getSubject();
        $message    = $email->getMessage();

        $fromName   = $email->getSender() ? ($email->getSender()->getAdminLevel() ? null : $email->getSender()->getUsername()) : null;
        $site       = $email->getSite() ? $email->getSite()->getDefaultLocale() : null;
        $emailType  = $type ?: $email->getEmailType();
        $sendCount  = 0;

        foreach ($email->getRecipients() as $recipient) {
            $emailTo = $recipient->getEmail();
            $this->sendHtmlEmail($emailTo, $subject, $message, $emailType, $site, $fromName);
            $sendCount++;
        }

        $this->em->persist($email);
        $this->em->flush();

        return $sendCount;
    }

    public function hasUserHitEmailLimit($user)
    {
        $limit = MassEmail::EMAIL_LIMIT_COUNT;

        $recentGroupMassEmailCount       = $this->em->getRepository('GroupBundle:GroupMassEmail')->getRecentEmailCountForUser($user);
        $recentGroupEventMassEmailCount  = $this->em->getRepository('EventBundle:GroupEventEmail')->getRecentEmailCountForUser($user);
        $recentGlobalEventMassEmailCount = $this->em->getRepository('EventBundle:GlobalEventEmail')->getRecentEmailCountForUser($user);

        $emailCount = array_sum(array(
            $recentGroupMassEmailCount,
            $recentGroupEventMassEmailCount,
            $recentGlobalEventMassEmailCount,
        ));

        return $emailCount >= $limit;
    }
}
