<?php

namespace Platformd\GiveawayBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Platformd\GroupBundle\Entity\GroupMembershipAction;
use Platformd\GroupBundle\Event\GroupEvent;
use Platformd\GroupBundle\GroupEvents;
use Platformd\CEVOBundle\Api\ApiException;
use Symfony\Component\Finder\Finder;

use Platformd\GiveawayBundle\QueueMessage\KeyRequestQueueMessage;
use Platformd\GiveawayBundle\Entity\KeyRequestState;

class KeyRequestProcessorCommand extends ContainerAwareCommand
{
    const DELAY_BETWEEN_KEYS_MILLISECONDS = 50;

    private $em;
    private $logger;

    protected function getRepo($key) {
        return $this->em->getRepository($key);
    }

    protected function output($indentationLevel = 0, $message = null, $withNewLine = true) {

        if ($message === null) {
            echo '';
        }

        echo str_repeat(' ', $indentationLevel).$message.($withNewLine ? "\n" : '');
        $this->logger->info($message);
    }

    protected function configure()
    {
        $this
            ->setName('pd:keyRequestQueue:process')
            ->setDescription('Process the key requests that are currently in the queue.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command retrieves items from the key request queue and processes them.

<info>php %command.full_name%</info>
EOT
        );
    }

    protected function findWithOutput($settings) {

        $repoFunction = isset($settings['repoFunction']) ? $settings['repoFunction'] : 'find';
        $result       = $settings['repo']->$repoFunction($settings['id']);

        if (!$result) {
            $this->output(2, $settings['type'].' with ID = "'.$settings['id'].'" not found.');
        } else {
            $this->output(2, $result); # note __toString has to be implemented on the result object
        }

        return $result;
    }

    protected function rejectRequestWithOutput($indentationLevel, $outputMessage, $state, $reason, $sqsMessage, $rejectedDueToInvalidMessage = false)
    {
        $this->output($indentationLevel, $outputMessage);

        if ($state) {
            $this->output($indentationLevel, 'Old '.$state);

            $state->setCurrentState($rejectedDueToInvalidMessage ? KeyRequestState::STATE_REQUEST_PROBLEM : KeyRequestState::STATE_REJECTED);
            $state->setStateReason($reason);

            $this->em->persist($state);
            $this->em->flush();

            $this->output($indentationLevel, 'New '.$state);
        }

        $this->deleteMessageWithOutput($sqsMessage);

    }

    protected function deleteMessageWithOutput($message)
    {
        $queueUtil    = $this->getContainer()->get('platformd.util.queue_util');

        $this->output(2, 'Deleting message from queue.');
        $this->output(2, ($queueUtil->deleteFromQueue($message) ? 'Message deleted successfully.' : 'Unable to delete message.'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->logger = $this->getContainer()->get('pd_giveaway.logger.key_request_processor_logger');

        $queueUtil    = $this->getContainer()->get('platformd.util.queue_util');
        $router       = $this->getContainer()->get('router');
        $giveawayRepo = $this->getRepo('GiveawayBundle:Giveaway');
        $dealRepo     = $this->getRepo('GiveawayBundle:Deal');
        $siteRepo     = $this->getRepo('SpoutletBundle:Site');
        $userRepo     = $this->getRepo('UserBundle:User');
        $countryRepo  = $this->getRepo('SpoutletBundle:Country');
        $stateRepo    = $this->getRepo('GiveawayBundle:KeyRequestState');
        $ipLookupUtil = $this->getContainer()->get('platformd.model.ip_lookup_util');
        $groupManager = $this->getContainer()->get('platformd.model.group_manager');

        $this->output(0, 'Processing queue for the Key Requests.');

        while ($message = $queueUtil->retrieveFromQueue(new KeyRequestQueueMessage())) {

            usleep(self::DELAY_BETWEEN_KEYS_MILLISECONDS);

            $this->output();
            $this->output(1, 'Processing message.');

            $this->output(2, $message);

            if (!$message->hasValidKeyRequestType()) {
                $this->output(2, 'Unknown message type = "'.$message->keyRequestType.'".');
                $this->deleteMessageWithOutput($message);
                continue;
            }

            $user = $this->findWithOutput(array(
                'type' => 'User',
                'repo' => $userRepo,
                'id'   => $message->userId,
            ));

            if (!$user) {
                $this->output(2, 'Invalid user.');
                $this->deleteMessageWithOutput($message);
                continue;
            }

            switch ($message->keyRequestType) {

                case KeyRequestQueueMessage::KEY_REQUEST_TYPE_GIVEAWAY:

                    $keyRepo   = $this->getRepo('GiveawayBundle:GiveawayKey');

                    $promotion = $this->findWithOutput(array(
                        'type'       => 'Giveaway',
                        'repo'       => $giveawayRepo,
                        'id'         => $message->promotionId,
                    ));

                    if (!$promotion) {
                        $this->rejectRequestWithOutput(3, 'Could not find promotion.', null, KeyRequestState::REASON_INVALID_PROMOTION, $message, true);
                        continue 2;
                    }

                    $state = $stateRepo->findForUserIdAndGiveawayId($message->userId, $message->promotionId);

                    if (!$state) {
                        $state = new KeyRequestState();

                        $state->setDeal($promotion);
                        $state->setUser($user);
                        $state->setPromotionType(KeyRequestState::PROMOTION_TYPE_GIVEAWAY);
                        $state->setCurrentState(KeyRequestState::STATE_IN_QUEUE);
                    }

                    if ($promotion->getStatus() != 'active' && !($promotion->getTestOnly() && $user->getIsSuperAdmin())) {
                        $this->rejectRequestWithOutput(3, 'This promotion is not active. Additionally the promotion\'s settings and user\'s roles don\'t allow for admin testing.', $state, KeyRequestState::REASON_INACTIVE_PROMOTION, $message, true);
                        continue 2;
                    }

                    if (!$promotion->allowKeyFetch()) {
                        $this->rejectRequestWithOutput(3, 'This promotion does not allow key fetching (this most likely means that the promotion is a system tag promotion, which isn\'t currently not supported).', $state, KeyRequestState::REASON_KEY_FETCH_DISALLOWED, $message, true);
                        continue 2;
                    }

                    $userAlreadyHasAKey = $keyRepo->doesUserHaveKeyForGiveaway($user, $promotion);

                    if ($userAlreadyHasAKey) {
                        $this->rejectRequestWithOutput(4, 'This user already has a key assigned for this promotion.', $state, KeyRequestState::REASON_ALREADY_ASSIGNED, $message);
                        continue 2;
                    }

                    $urlToShowPage = $router->generate('giveaway_show', array('slug' => $promotion->getSlug()));

                    break;

                case KeyRequestQueueMessage::KEY_REQUEST_TYPE_DEAL:

                    $keyRepo   = $this->getRepo('GiveawayBundle:DealCode');

                    $promotion = $this->findWithOutput(array(
                        'repo'       => $dealRepo,
                        'id'         => $message->promotionId,
                    ));

                    if (!$promotion) {
                        $this->rejectRequestWithOutput(3, 'Could not find promotion.', null, KeyRequestState::REASON_INVALID_PROMOTION, $message, true);
                        continue 2;
                    }

                    $state = $stateRepo->findForUserIdAndDealId($message->userId, $message->promotionId);

                    if (!$state) {
                        $state = new KeyRequestState();

                        $state->setDeal($promotion);
                        $state->setUser($user);
                        $state->setPromotionType(KeyRequestState::PROMOTION_TYPE_DEAL);
                        $state->setCurrentState(KeyRequestState::STATE_IN_QUEUE);
                    }

                    if ($promotion->getStatus() != 'published' && !($promotion->getTestOnly() && $user->getIsSuperAdmin())) {
                        $this->rejectRequestWithOutput(3, 'This promotion is not active. Additionally the promotion\'s settings and user\'s roles don\'t allow for admin testing.', $state, KeyRequestState::REASON_INACTIVE_PROMOTION, $message, true);
                        continue 2;
                    }

                    $userAlreadyHasACode = $keyRepo->doesUserHaveCodeForDeal($user, $promotion);

                    if ($userAlreadyHasACode) {
                        $this->rejectRequestWithOutput(4, 'This user already has a code assigned for this promotion.', $state, KeyRequestState::REASON_ALREADY_ASSIGNED, $message);
                        continue 2;
                    }

                    $urlToShowPage = $router->generate('deal_show', array('slug' => $promotion->getSlug()));

                    break;

                default:
                    $this->output(2, 'Unable to retrieve state information for message type - "'.$message->keyRequestType.'"');
                    $this->deleteMessageWithOutput($message);
                    continue 2;

            }

            $this->output(2, $state);

            $site = $this->findWithOutput(array(
                'type'       => 'Site',
                'repo'       => $siteRepo,
                'id'         => $message->siteId,
            ));

            if (!$site) {
                $this->rejectRequestWithOutput(2, 'Invalid site.', $state, KeyRequestState::REASON_INVALID_SITE, $message, true);
                continue;
            }

            $clientIp = $message->ipAddress;

            if (!$clientIp) {
                $this->rejectRequestWithOutput(2, 'Client IP was null.', $state, KeyRequestState::REASON_CLIENT_IP_NULL, $message, true);
                continue;
            }

            $this->output(2, 'ClientIP => { IP = '.$clientIp.' }');

            $countryCode = $ipLookupUtil->getCountryCode($clientIp);

            if (!$countryCode) {
                $this->rejectRequestWithOutput(2, 'Country code was null.', $state, KeyRequestState::REASON_COUNTRY_CODE_NULL, $message, true);
                continue;
            }

            $this->output(2, 'CountryCode => { Code = '.$countryCode.' }');

            $country = $this->findWithOutput(array(
                'type'         => 'Country',
                'repo'         => $countryRepo,
                'repoFunction' => 'findOneByCode',
                'id'           => strtoupper($countryCode),
            ));

            if (!$country) {
                $this->rejectRequestWithOutput(2, 'Invalid country.', $state, KeyRequestState::REASON_INVALID_COUNTRY, $message, true);
                continue;
            }

            # get country and age restriction rule set

            $ruleSet = $promotion->getRuleset();

            if ($ruleSet && !$ruleSet->doesUserPassRules($user, $country)) {
                $this->rejectRequestWithOutput(3, 'Not allowed key based on age or country specific rules.', $state, KeyRequestState::REASON_INVALID_COUNTRY_AGE, $message);
                continue;
            }

            $pools = $promotion->getPools();
            $key   = null;

            foreach ($pools as $pool) {

                $this->output(3, $pool);

                if (!$keyRepo->canIpHaveMoreKeys($clientIp, $pool)) {
                    $this->output(4, 'This IP has hit the max per IP setting for this pool.');
                    continue;
                }

                if (!$pool->isEnabledForCountry($country)) {
                    $this->output(4, 'Pool not enabled for the user\'s country');
                    continue;
                }

                $key = $keyRepo->getUnassignedKey($pool);

                if (!$key) {
                    $this->output(4, 'No more keys left for this pool.');
                    continue;
                }

                $this->output(4, $key);
                $key->assign($user, $clientIp, $site->getDefaultLocale());

                break;
            }

            if (!$key) {
                $this->rejectRequestWithOutput(5, 'No keys left for user.', $state, KeyRequestState::REASON_NO_KEYS_LEFT, $message);
                continue;
            }

            $this->output(5, 'Old '.$state);

            $state->setCurrentState(KeyRequestState::STATE_ASSIGNED);
            $state->setStateReason(null);
            $this->em->persist($state);

            $this->output(5, 'New '.$state);

            $group = $promotion->getGroup();

            if($site->getSiteFeatures()->getHasGroups() && $group) {

                $this->output(2, 'Auto join user to group.');
                $this->output(3, $group);

                if ($groupManager->isAllowedTo($user, $group, $site, 'JoinGroup')) {
                    $joinAction = new GroupMembershipAction();

                    $joinAction->setGroup($group);
                    $joinAction->setUser($user);
                    $joinAction->setAction(GroupMembershipAction::ACTION_JOINED);

                    $group->getMembers()->add($user);
                    $group->getUserMembershipActions()->add($joinAction);

                    $dispatcher = $this->getContainer()->get('event_dispatcher');
                    $event      = new GroupEvent($group, $user);
                    $dispatcher->dispatch(GroupEvents::GROUP_JOIN, $event);

                    $groupManager->saveGroup($group);

                    if($group->getIsPublic()) {
                        try {
                            $response = $this->getContainer()->get('pd.cevo.api.api_manager')->GiveUserXp('joingroup', $user->getCevoUserId());
                        } catch (ApiException $e) {

                        }
                    }
                }
            }

            $this->em->flush();

            $this->output(5, 'Key assigned successfully.');
            $this->output(5, 'Sending user email.');

            $this->emailUser($user, $promotion->getName(), $key->getValue(), $urlToShowPage, $site);

            $this->output(5, 'Email sent.');

            $this->deleteMessageWithOutput($message);
        }

        $this->output();
        $this->output(1, 'No more messages in queue.');
    }

    private function emailUser($user, $promotionTitle, $promotionKey, $promotionShowPage, $site) {

        $emailManager = $this->getContainer()->get('platformd.model.email_manager');
        $translator   = $this->getContainer()->get('translator');
        $locale       = $site->getDefaultLocale();

        $messageReplacements = array(
            '%promotion_key%'       => $promotionKey,
            '%promotion_title%'     => $promotionTitle,
            '%promotion_show_page%' => 'http://'.$site->getFullDomain().$promotionShowPage
        );

        $emailTo = $user->getEmail();
        $subject = $translator->trans('platformd.key_request_processor_command.key_assigned_email_subject', array(), 'messages', $locale);
        $message = $translator->trans('platformd.key_request_processor_command.key_assigned_email_body', $messageReplacements, 'messages', $locale);

        $emailManager->sendHtmlEmail($emailTo, $subject, $message, 'promotion_assigned', $site->getName());
    }
}
