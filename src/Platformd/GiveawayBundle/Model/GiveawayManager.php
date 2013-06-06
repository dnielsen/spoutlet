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
use Platformd\GiveawayBundle\Util\KeyCounterUtil;
use Platformd\SpoutletBundle\Entity\Site;
use Gaufrette\Filesystem;
use Platformd\SpoutletBundle\Util\CacheUtil;
use Platformd\SpoutletBundle\Util\SiteUtil;
use Platformd\GiveawayBundle\Entity\Repository\GiveawayKeyRepository;
use Platformd\SpoutletBundle\Entity\CountryRepository;
use Platformd\GiveawayBundle\Entity\GiveawayRepository;
use Platformd\GiveawayBundle\ViewModel\giveaway_show_data;
use Platformd\GiveawayBundle\ViewModel\giveaway_index_data;
use Doctrine\ORM\EntityManager;
use Platformd\SpoutletBundle\Entity\ThreadRepository;
use Platformd\SpoutletBundle\Entity\Thread;
use Platformd\SpoutletBundle\Link\LinkableManager;
use MediaExposer\Exposer;

class GiveawayManager
{
    private $em;
    private $emailManager;
    private $fromAddress;
    private $fromName;
    private $router;
    private $translator;
    private $cacheUtil;
    private $giveawayRepo;
    private $siteUtil;
    private $keyCounterUtil;
    private $giveawayKeyRepo;
    private $threadRepo;
    private $linkableManager;
    private $mediaExposer;
    private $filesystem;
    private $countryRepo;
    private $commentManager;

    public function __construct(ObjectManager $em, TranslatorInterface $translator, RouterInterface $router, EmailManager $emailManager, $fromAddress, $fromName, CacheUtil $cacheUtil, GiveawayRepository $giveawayRepo, SiteUtil $siteUtil, KeyCounterUtil $keyCounterUtil, GiveawayKeyRepository $giveawayKeyRepo, EntityManager $em, ThreadRepository $threadRepo, LinkableManager $linkableManager, Exposer $mediaExposer, Filesystem $filesystem, CountryRepository $countryRepo, $commentManager)
    {
        $this->emailManager    = $emailManager;
        $this->em              = $em;
        $this->fromAddress     = $fromAddress;
        $this->fromName        = $fromName;
        $this->router          = $router;
        $this->translator      = $translator;
        $this->cacheUtil       = $cacheUtil;
        $this->giveawayRepo    = $giveawayRepo;
        $this->siteUtil        = $siteUtil;
        $this->keyCounterUtil  = $keyCounterUtil;
        $this->giveawayKeyRepo = $giveawayKeyRepo;
        $this->em              = $em;
        $this->threadRepo      = $threadRepo;
        $this->linkableManager = $linkableManager;
        $this->mediaExposer    = $mediaExposer;
        $this->filesystem      = $filesystem;
        $this->countryRepo     = $countryRepo;
        $this->commentManager  = $commentManager;
    }

    public function getAnonGiveawayIndexData() {

        $giveawaysArr   = array();
        $siteId         = $this->siteUtil->getCurrentSiteCached()->getId();
        $giveaways      = $this->giveawayRepo->findActives($siteId);

        foreach ($giveaways as $giveaway) {
            $giveawaysArr[] = array(
                'slug'  => $giveaway->getSlug(),
                'name'  => $giveaway->getName(),
            );
        }

        $data            = new giveaway_index_data();
        $data->giveaways = $giveawaysArr;

        return $data;
    }

    public function getAvailableKeysForGiveaway($giveawayId, $countryCode) {

        if (!$giveawayId || !$countryCode) {
            return 0;
        }

        $giveawayId = (int) $giveawayId;

        if ($giveawayId < 1 || strlen($countryCode) < 2) {
            return 0;
        }

        $keyCounterUtil = $this->keyCounterUtil;
        $keyRepo        = $this->giveawayKeyRepo;
        $countryRepo    = $this->countryRepo;
        $giveawayRepo   = $this->giveawayRepo;

        $availableKeys = $this->cacheUtil->getOrGen(array(

            'key'                  => 'GIVEAWAY::AVAILABLE_KEY_COUNT::GIVEAWAY_ID='.$giveawayId.'::COUNTRY_CODE='.$countryCode,
            'hashKey'              => false,
            'cacheDurationSeconds' => 30,
            'genFunction'          => function () use (&$keyCounterUtil, &$giveawayId, &$countryCode, &$keyRepo, &$countryRepo, &$giveawayRepo) {

                $giveaway   = $giveawayRepo->find($giveawayId);
                $country    = $countryRepo->findOneByCode($countryCode);

                foreach($giveaway->getPools() as $pool) {

                    if ($pool->isEnabledForCountry($country) && $pool->getIsActive()) {
                        $keyCount = $keyRepo->getUnassignedForPool($pool);

                        if ($keyCount && $keyCount > 0) {
                            return $keyCounterUtil->getTrueDisplayCount($keyRepo->getTotalForPool($pool), $keyCount, $pool->getLowerLimit(), $pool->getUpperLimit());
                        }
                    }
                }

                return 0;
            }));

        return $availableKeys ? (int) $availableKeys : 0;
    }

    public function getAnonGiveawayShowData($slug) {

        $giveaway = $this->giveawayRepo->findOneBySlugAndSiteId($slug, $this->siteUtil->getCurrentSiteCached()->getId());

        if (!$giveaway) {
            return null;
        }

        $data                                      = new giveaway_show_data();
        $data->giveaway_name                       = $giveaway->getName();
        $data->giveaway_content                    = $giveaway->getContent();
        $data->giveaway_banner_image               = $giveaway->getBannerImage() ? $this->mediaExposer->getPath($giveaway, array('type' => 'banner')) : null;
        $data->giveaway_redemption_steps           = $giveaway->getCleanedRedemptionInstructionsArray();
        $data->giveaway_allow_machine_code_submit  = $giveaway->allowMachineCodeSubmit();
        $data->giveaway_id                         = $giveaway->getId();

        $data->giveaway_comment_thread_id          = $giveaway->getThreadId();
        $data->giveaway_comment_permalink          = $this->commentManager->checkThread($giveaway);

        $data->giveaway_background_image_path      = $giveaway->getBackgroundImagePath() ? $this->mediaExposer->getPath($giveaway, array('type' => 'background')) : null;
        $data->giveaway_background_link            = $giveaway->getBackgroundLink();

        return $data;
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
    public function approveMachineCode(MachineCodeEntry $machineCode, Site $site, $country)
    {
        // see if it's already assigned to a key
        if ($machineCode->getKey()) {
            return;
        }

        $pool = $machineCode->getGiveaway()->getActivePoolForCountry($country);

        $key = $this->getGiveawayKeyRepository()->getUnassignedKey($pool);
        if (!$key) {
            throw new MissingKeyException();
        }

        $locale = $site->getDefaultLocale();

        $country = $this->countryRepo->findOneByCode($country);

        // attach the key, then attach it to the machine code
        $key->assign($machineCode->getUser(), $machineCode->getIpAddress(), $locale, $country);
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
            '%supportEmailAddress%'     => $appliedSite->getSiteConfig()->getSupportEmailAddress(),
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
            '%supportEmailAddress%'     => $appliedSite->getSiteConfig()->getSupportEmailAddress(),
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
        $this->em->persist($giveaway);
        $this->em->flush();

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
        $this->updateBackgroundImage($giveaway);

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
        foreach ($giveaway->getTranslations() as $translation) {
            if ($translation->getRemoveBannerImage()) {
                $translation->setBannerImage(null);
            }

            $file = $translation->getBannerImageFile();
            if (null == $file) {
                continue;
            }
            $filename = sha1($translation->getId().'-'.uniqid()).'.'.$file->guessExtension();
            // prefix repeated in BannerPathResolver
            $this->filesystem->write($giveaway::PREFIX_PATH_BANNER.$filename, file_get_contents($file->getPathname()));
            $translation->setBannerImage($filename);
        }


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

    protected function updateBackgroundImage($giveaway)
    {
        foreach ($giveaway->getTranslations() as $translation) {
            if ($translation->getRemoveBackgroundImage()) {
                $translation->setBackgroundImagePath(null);
            }

            $file = $translation->getBackgroundImage();
            if (null == $file) {
                continue;
            }
            $filename = sha1($translation->getId().'-'.uniqid()).'.'.$file->guessExtension();
            // prefix repeated in BannerPathResolver
            $this->filesystem->write($giveaway::PREFIX_PATH_BACKGROUND.$filename, file_get_contents($file->getPathname()));
            $translation->setBackgroundImagePath($filename);
        }

        $file = $giveaway->getBackgroundImage();

        if (null == $file) {
            return;
        }

        $filename = sha1($giveaway->getId().'-'.uniqid()).'.'.$file->guessExtension();
        // prefix repeated in BannerPathResolver
        $this->filesystem->write($giveaway::PREFIX_PATH_BACKGROUND.$filename, file_get_contents($file->getPathname()));
        $giveaway->setBackgroundImagePath($filename);
    }
}
