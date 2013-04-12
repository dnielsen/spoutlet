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
use Gaufrette\Filesystem;

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

    private $filesystem;

    public function __construct(ObjectManager $em, TranslatorInterface $translator, RouterInterface $router, EmailManager $emailManager, $fromAddress, $fromName, Filesystem $filesystem)
    {
        $this->em = $em;
        $this->translator = $translator;
        $this->router = $router;
        $this->emailManager = $emailManager;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
        $this->filesystem = $filesystem;
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

        $result = array_merge(
            $requests = $this->convertKeysToRequests($keys),
            $this->convertMachineCodesToRequests($machineCodes)
        );

        $counter            = 1;
        $previousGiveaway   = null;
        $approvedGiveaways  = array();

        foreach ($result as $key => $request) {

            $currentGiveaway = $request->getGiveaway()->getId();

            if ($request->getMachineCode()) {

                // getAssignedAt() actually returns the deniedAt datetime for machinecodes in this case. This is a workaraound due to the way the arrays are merged above.
                if ($request->getAssignedAt() && $currentGiveaway == $previousGiveaway) {
                    $counter++;

                    if ($counter > 5) {
                        unset($result[$key]);
                    }
                } else {
                    $counter = 1;
                }

                $previousGiveaway = $currentGiveaway;
            }

            if ($request->getValue()) {
                $approvedGiveaways[] = $currentGiveaway;
            }
        }

        foreach ($result as $key => $request) {
            if ((in_array($request->getGiveaway()->getId(), $approvedGiveaways)) && (!$request->getValue())) {
                unset($result[$key]);
            }
        }

        return $result;
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

        $locale = $site->getDefaultLocale();

        // attach the key, then attach it to the machine code
        $key->assign($machineCode->getUser(), $machineCode->getIpAddress(), $locale);
        $machineCode->attachToKey($key);

        $this->sendNotificationEmail($machineCode, $site);

        $this->em->persist($key);
        $this->em->persist($machineCode);
        $this->em->flush();
    }

    /**
     * Denies the machine code entry and sends notification email
     *
     * @param \Platformd\GiveawayBundle\Entity\MachineCodeEntry $machineCode
     */
    public function denyMachineCode(MachineCodeEntry $machineCode, Site $site)
    {
        // see if it's already assigned to a key
        if ($machineCode->getKey()) {
            return;
        }

        $machineCode->markAsDenied();

        $this->sendDeniedNotificationEmail($machineCode, $site);

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

    public function canUserApplyToGiveaway(User $user, Giveaway $giveaway)
    {
        $entries = $this->getMachineCodeEntryRepository()->findAllActiveOrPendingForUserAndGiveaway($user, $giveaway);

        return (count($entries) < 1);
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
                null,
                $key->getAssignedSite(),
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
            $deniedAt = $code->getDeniedAt() ? : null;

            $site = $code->getSiteAppliedFrom() ? $code->getSiteAppliedFrom()->getDefaultLocale() : null;

            $requests[] = new GiveawayKeyRequest(
                null,
                $code->getGiveaway(),
                $code->getStatus(),
                $code->getMachineCode(),
                $site,
                $deniedAt
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
    private function sendNotificationEmail(MachineCodeEntry $machineCodeEntry, Site $site)
    {
        // don't send more than once
        if ($machineCodeEntry->getNotificationEmailSentAt()) {
            return;
        }

        $giveaway = $machineCodeEntry->getGiveaway();

        $appliedSite    = $machineCodeEntry->getSiteAppliedFrom() ?: $site;
        $locale         = $appliedSite->getDefaultLocale();

        $user = $machineCodeEntry->getUser();

        $accountUrl = $this->router->generate('accounts_giveaways', array(
            '_locale' => $locale
        ), true);

        // translate the message into the user's locale
        $message = $this->translator->trans('email.giveaway_machine_code_approve', array(
            '%giveawayName%'            => $giveaway->getName(),
            '%userFirstName%'           => $user->getFirstname(),
            '%userLastName%'            => $user->getLastname(),
            '%accountUrl%'              => $accountUrl,
            '%supportEmailAddress%'     => $appliedSite->getSupportEmailAddress(),
        ), 'messages', $locale);

        $subject = $this->translator->trans('email.subject.giveaway_machine_code_approve', array(
            '%giveawayName%'  => str_replace(array("\r\n"), ' ', $giveaway->getName()),
        ), 'messages', $locale);

        $emailTo = $user->getEmail();

        if (!$emailTo) {
            return;
        }

        $result = $this->emailManager->sendEmail($emailTo, $subject, $message, "Giveaway Machine Code Approved", $appliedSite->getFullDomain(), $this->fromName, $this->fromAddress);

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
    private function sendDeniedNotificationEmail(MachineCodeEntry $machineCodeEntry, Site $site)
    {
        // don't send more than once
        if ($machineCodeEntry->getNotificationEmailSentAt()) {
            return;
        }

        $giveaway = $machineCodeEntry->getGiveaway();
        $user = $machineCodeEntry->getUser();

        $appliedSite    = $machineCodeEntry->getSiteAppliedFrom() ? : $site;
        $locale         = $appliedSite->getDefaultLocale();

        $giveawayUrl = $this->router->generate($giveaway->getLinkableRouteName(), array(
            'slug' => $giveaway->getSlug(),
            '_locale' => $locale,
        ), true);

        // translate the message into the user's locale
        $message = $this->translator->trans('email.giveaway_machine_code_deny', array(
            '%giveawayName%'            => $giveaway->getName(),
            '%userFirstName%'           => $user->getFirstname(),
            '%userLastName%'            => $user->getLastname(),
            '%giveawayUrl%'             => $giveawayUrl,
            '%systemTag%'               => $machineCodeEntry->getMachineCode(),
            '%supportEmailAddress%'     => $appliedSite->getSupportEmailAddress(),
        ), 'messages', $locale);

        $subject = $this->translator->trans('email.subject.giveaway_machine_code_deny', array(
            '%giveawayName%'  => str_replace(array("\r\n"), ' ', $giveaway->getName()),
        ), 'messages', $locale);

        $emailTo = $user->getEmail();

        if (!$emailTo) {
            return;
        }

        $result = $this->emailManager->sendEmail($emailTo, $subject, $message, "Giveaway Machine Code Denied", $appliedSite->getFullDomain(), $this->fromName, $this->fromAddress);

        if (!$result || !$result->getSendStatusOk()) {
            return;
        }

        // mark the notification email as sent
        $machineCodeEntry->setNotificationEmailSentAt(new \DateTime());
    }

    public function save(Giveaway $giveaway)
    {
        $threadRepo     = $this->em->getRepository('SpoutletBundle:Thread');
        $commentRepo    = $this->em->getRepository('SpoutletBundle:Comment');

        $unit = $this->em->getUnitOfWork();
        $unit->computeChangeSets();
        $changeset = $unit->getEntityChangeSet($giveaway);

        if (array_key_exists('slug', $changeset) && $changeset['slug'][0] != $changeset['slug'][1]) {

            $newThread = new Thread();
            $thread = $threadRepo->find($changeset['slug'][0]);

            if ($thread) {
                $newThread->setIsCommentable($thread->isCommentable());
                $newThread->setLastCommentAt($thread->getLastCommentAt());
                $newThread->setCommentCount($thread->getCommentCount());

                $permalink = str_replace($changeset['slug'][0], $changeset['slug'][1], $thread->getPermalink());

                $newThread->setPermalink($permalink);
                $newThread->setId($changeset['slug'][1]);
                $this->em->persist($newThread);

                $comments = $commentRepo->findByThread($changeset['slug'][0]);

                if ($comments) {
                    foreach ($comments as $comment) {
                        $comment->setThread($newThread);
                        $this->em->persist($comment);
                    }
                }

                $this->em->flush();
                $this->em->remove($thread);
                $this->em->flush();
            }
        }

        // Todo : handle upload to S3
        $this->updateBannerImage($giveaway);
        $this->updateGeneralImage($giveaway);
        $this->em->persist($giveaway);
        $this->em->flush();
    }

    /**
     * Update an giveaway's banner image
     *
     * @param \Platformd\GiveawayBundle\Entity\Giveaway $giveaway
     */
    protected function updateBannerImage(Giveaway $giveaway)
    {
        $file = $giveaway->getBannerImageFile();

        if (null == $file) {
            return;
        }

        $filename = sha1($giveaway->getId().'-'.uniqid()).'.'.$file->guessExtension();
        // prefix repeated in BannerPathResolver
        $this->filesystem->write($giveaway::PREFIX_PATH_BANNER.$filename, file_get_contents($file->getPathname()));
        $giveaway->setBannerImage($filename);
    }

    /**
     * Update a giveaway's general image
     *
     * @param \Platformd\GiveawayBundle\Entity\Giveaway $giveaway
     */
    protected function updateGeneralImage(Giveaway $giveaway)
    {
        $file = $giveaway->getGeneralImageFile();

        if (null == $file) {
            return;
        }

        $filename = sha1($giveaway->getId().'-'.uniqid()).'.'.$file->guessExtension();
        // prefix repeated in BannerPathResolver
        $this->filesystem->write($giveaway::PREFIX_PATH_GENERAL .$filename, file_get_contents($file->getPathname()));
        $giveaway->setGeneralImage($filename);
    }
}
