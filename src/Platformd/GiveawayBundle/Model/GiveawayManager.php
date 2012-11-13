<?php

namespace Platformd\GiveawayBundle\Model;

use Doctrine\Common\Persistence\ObjectManager;
use Platformd\UserBundle\Entity\User;
use Platformd\GiveawayBundle\Model\GiveawayKeyRequest;
use Platformd\GiveawayBundle\Entity\MachineCodeEntry;
use Platformd\GiveawayBundle\Model\Exception\MissingKeyException;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Routing\RouterInterface;
use Platformd\SpoutletBundle\Model\EmailManager;
use Platformd\SpoutletBundle\Entity\Site;

/**
 * Service class for dealing with the giveaway system
 */
class GiveawayManager
{
    private $em;

    private $router;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    private $translator;

    private $fromAddress;

    private $fromName;

    private $emailManager;

    public function __construct(ObjectManager $em, TranslatorInterface $translator, RouterInterface $router, EmailManager $emailManager, $fromAddress, $fromName)
    {
        $this->em = $em;
        $this->translator = $translator;
        $this->router = $router;
        $this->emailManager = $emailManager;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
    }

    /**
     * Returns all the giveaway key requests for a user:
     *
     *      a) All GiveawayKey objects assigned to this user
     * PLUS
     *      b) All MachineCodeEntry objects assigned to this user but not approved
     *
     * @param \Platformd\UserBundle\Entity\User $user
     * @return \Platformd\GiveawayBundle\Entity\GiveawayKeyRequest[]
     */
    public function getGiveawayKeyRequestsForUser(User $user)
    {
        $keys = $this->getGiveawayKeyRepository()->findAssignedToUser($user);
        $machineCodes = $this->getMachineCodeEntryRepository()->findAssignedToUserWithoutGiveawayKey($user);

        return array_merge(
            $requests = $this->convertKeysToRequests($keys),
            $this->convertMachineCodesToRequests($machineCodes)
        );
    }

    /**
     * Approves the machine code entry and associates it with a GiveawayKey
     *
     * @param \Platformd\GiveawayBundle\Entity\MachineCodeEntry $machineCode
     */
    public function approveMachineCode(MachineCodeEntry $machineCode, Site $site)
    {
        // see if it's already assigned to a key
        if ($machineCode->getKey()) {
            return;
        }

        $pool = $machineCode->getGiveaway()->getActivePool();

        $key = $this->getGiveawayKeyRepository()->getUnassignedKey($pool);
        if (!$key) {
            throw new MissingKeyException();
        }

        // attach the key, then attach it to the machine code
        $key->assign($machineCode->getUser(), $machineCode->getIpAddress(), $site->getDefaultLocale());
        $machineCode->attachToKey($key);

        $this->sendNotificationEmail($machineCode);

        $this->em->persist($key);
        $this->em->persist($machineCode);
        $this->em->flush();
    }

    /**
     * Denies the machine code entry and sends notification email
     *
     * @param \Platformd\GiveawayBundle\Entity\MachineCodeEntry $machineCode
     */
    public function denyMachineCode(MachineCodeEntry $machineCode)
    {
        // see if it's already assigned to a key
        if ($machineCode->getKey()) {
            return;
        }

        $machineCode->markAsDenied();

        $this->sendDeniedNotificationEmail($machineCode);

        $this->em->persist($machineCode);
        $this->em->flush();
    }


    /**
     * Has the user applied to this giveaway yet?
     *
     * @param \Platformd\UserBundle\Entity\User $user
     * @param \Platformd\GiveawayBundle\Entity\Giveaway $giveaway
     * @return bool
     */
    public function hasUserAppliedToGiveaway(User $user, Giveaway $giveaway)
    {
        $entries = $this->getMachineCodeEntryRepository()->findAssignedToUserForGiveaway($user, $giveaway);

        return (count($entries) > 0);
    }

    /**
     * @param \Platformd\GiveawayBundle\Entity\GiveawayKey[] $keys
     * @return \Platformd\GiveawayBundle\Entity\GiveawayKeyRequest[]
     */
    private function convertKeysToRequests(array $keys)
    {
        $requests = array();

        foreach ($keys as $key) {
            $requests[] = new GiveawayKeyRequest(
                $key->getValue(),
                $key->getPool()->getGiveaway(),
                MachineCodeEntry::STATUS_APPROVED,
                $key->getAssignedAt()
            );
        }

        return $requests;
    }

    /**
     * @param \Platformd\GiveawayBundle\Entity\MachineCodeEntry[] $machineCodes
     * @return \Platformd\GiveawayBundle\Model\GiveawayKeyRequest[]
     */
    private function convertMachineCodesToRequests(array $machineCodes)
    {
        $requests = array();

        foreach ($machineCodes as $code) {
            $requests[] = new GiveawayKeyRequest(
                null,
                $code->getGiveaway(),
                $code->getStatus(),
                null
            );
        }

        return $requests;
    }

    /**
     * @return \Platformd\GiveawayBundle\Entity\Repository\GiveawayKeyRepository
     */
    private function getGiveawayKeyRepository()
    {
        return $this->em->getRepository('GiveawayBundle:GiveawayKey');
    }

    /**
     * @return \Platformd\GiveawayBundle\Entity\MachineCodeEntryRepository
     */
    private function getMachineCodeEntryRepository()
    {
        return $this->em->getRepository('GiveawayBundle:MachineCodeEntry');
    }

    /**
     * Sends a notification to the user about being approved for a machine
     * code entry.
     *
     * @param \Platformd\GiveawayBundle\Entity\MachineCodeEntry $machineCodeEntry
     * @return string
     */
    private function sendNotificationEmail(MachineCodeEntry $machineCodeEntry)
    {
        // don't send more than once
        if ($machineCodeEntry->getNotificationEmailSentAt()) {
            return;
        }

        $giveaway = $machineCodeEntry->getGiveaway();
        $user = $machineCodeEntry->getUser();

        $accountUrl = $this->router->generate('accounts_giveaways', array(
            '_locale' => $user->getLocale()
        ), true);

        // translate the message into the user's locale
        $message = $this->translator->trans('email.giveaway_machine_code_approve', array(
            '%giveawayName%'  => $giveaway->getName(),
            '%userFirstName%' => $user->getFirstname(),
            '%userLastName%'  => $user->getLastname(),
            '%accountUrl%'    => $accountUrl,
        ), 'messages', $giveaway->getLocale());

        $subject = $this->translator->trans('email.subject.giveaway_machine_code_approve', array(
            '%giveawayName%'  => str_replace(array("\r\n"), ' ', $giveaway->getName()),
        ), 'messages', $giveaway->getLocale());

        $emailTo = $user->getEmail();

        if (!$emailTo) {
            return;
        }

        $result = $this->emailManager->sendEmail($emailTo, $subject, $message, "Giveaway Machine Code Approved", $user->getLocale(), $this->fromName, $this->fromAddress);

        if (!$result || !$result->getSendStatusOk()) {
            return;
        }

        $machineCodeEntry->setNotificationEmailSentAt(new \DateTime());
    }

    /**
     * Sends a notification to the user about being denied for a machine
     * code entry.
     *
     * @param \Platformd\GiveawayBundle\Entity\MachineCodeEntry $machineCodeEntry
     * @return string
     */
    private function sendDeniedNotificationEmail(MachineCodeEntry $machineCodeEntry)
    {
        // don't send more than once
        if ($machineCodeEntry->getNotificationEmailSentAt()) {
            return;
        }

        $giveaway = $machineCodeEntry->getGiveaway();
        $user = $machineCodeEntry->getUser();

        $accountUrl = $this->router->generate('accounts_giveaways', array(
            '_locale' => $user->getLocale()
        ), true);

        // translate the message into the user's locale
        $message = $this->translator->trans('email.giveaway_machine_code_deny', array(
            '%giveawayName%'  => $giveaway->getName(),
            '%userFirstName%' => $user->getFirstname(),
            '%userLastName%'  => $user->getLastname(),
            '%accountUrl%'    => $accountUrl,
            '%systemTag%'     => $machineCodeEntry->getMachineCode(),
        ), 'messages', $giveaway->getLocale());

        $subject = $this->translator->trans('email.subject.giveaway_machine_code_deny', array(
            '%giveawayName%'  => str_replace(array("\r\n"), ' ', $giveaway->getName()),
        ), 'messages', $giveaway->getLocale());

        $emailTo = $user->getEmail();

        if (!$emailTo) {
            return;
        }

        $result = $this->emailManager->sendEmail($emailTo, $subject, $message, "Giveaway Machine Code Denied", $user->getLocale(), $this->fromName, $this->fromAddress);

        if (!$result || !$result->getSendStatusOk()) {
            return;
        }

        // mark the notification email as sent
        $machineCodeEntry->setNotificationEmailSentAt(new \DateTime());
    }
}
