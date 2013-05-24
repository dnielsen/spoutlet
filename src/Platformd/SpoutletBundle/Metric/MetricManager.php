<?php

namespace Platformd\SpoutletBundle\Metric;

use DateTime,
    DateTimeZone
;

use Symfony\Component\Form\FormFactoryInterface;
use Doctrine\ORM\EntityManager;

use Platformd\GiveawayBundle\Entity\Giveaway,
    Platformd\GiveawayBundle\Entity\Deal,
    Platformd\GroupBundle\Entity\Group,
    Platformd\GroupBundle\Entity\GroupDiscussion,
    Platformd\SpoutletBundle\Entity\SiteRepository,
    Platformd\GroupBundle\Entity\Metric\GroupMetric,
    Platformd\GroupBundle\Entity\Metric\GroupDiscussionMetric
;

class MetricManager
{
    /**
     * @var \Platformd\SpoutletBundle\Entity\SiteRepository
     */
    private $siteRepository;

    /**
     * @var \Platformd\SpoutletBundle\Entity\GlobalActivityRepository
     */
    private $globalActivityRepository;

    /**
     * @var \Platformd\UserBundle\Entity\UserRepository
     */
    private $userRepo;

    /**
     * @var \Platformd\GiveawayBundle\Entity\Repository\GiveawayKeyRepository
     */
    private $giveawayKeyRepository;

    /**
     * @var \Platformd\GiveawayBundle\Entity\DealCodeRepository
     */
    private $dealCodeRepository;

    /**
     * @var \Platformd\GroupBundle\Entity\GroupRepository
     */
    private $groupRepository;

    /**
     * @var \Platformd\GroupBundle\Entity\Metric\GroupMetricRepository
     */
    private $groupMetricRepository;

    /**
     * @var \Platformd\GroupBundle\Entity\GroupDiscussionRepository
     */
    private $groupDiscussionRepository;

    /**
     * @var \Platformd\GroupBundle\Entity\Metric\GroupDiscussionMetricRepository
     */
    private $groupDiscussionMetricRepository;

    private $regionRepo;

    private $regionRepo;

    /**
     * An array of all available site keys and their names
     *
     * @var array
     */
    private $sites;

    public function __construct(EntityManager $em, array $sites)
    {
        $this->siteRepository = $em->getRepository('SpoutletBundle:Site');
        $this->userRepo = $em->getRepository('UserBundle:User');
        $this->globalActivityRepository = $em->getRepository('SpoutletBundle:GlobalActivity');
        $this->giveawayKeyRepository = $em->getRepository('GiveawayBundle:GiveawayKey');
        $this->dealCodeRepository = $em->getRepository('GiveawayBundle:DealCode');
        $this->groupRepository = $em->getRepository('GroupBundle:Group');
        $this->groupMetricRepository = $em->getRepository('GroupBundle:Metric\GroupMetric');
        $this->groupDiscussionRepository = $em->getRepository('GroupBundle:GroupDiscussion');
        $this->groupDiscussionMetricRepository = $em->getRepository('GroupBundle:Metric\GroupDiscussionMetric');
        $this->regionRepo = $em->getRepository('SpoutletBundle:Region');
        $this->sites = $sites;
    }

    /**
     * Returns an array of the sites that are reported on
     *
     * @return array
     */
    public function getSites()
    {
        return $this->sites;
    }

    /**
     * Creates an array report about this giveaway with the following fields:
     *
     *   * name
     *   * total
     *   * assigned
     *   * remaining
     *   * sites
     *      site_key => # assigned
     *
     * @param \Platformd\GiveawayBundle\Entity\Giveaway $giveaway
     * @param \DateTime $since
     * @return array
     */
    public function createGiveawaysReport(Giveaway $giveaway, $from, $to)
    {
        // the total numbers are not affected by the "since" - they are full totals
        $total = $this->giveawayKeyRepository->getTotalForGiveaway($giveaway);
        $assigned = $this->giveawayKeyRepository->getAssignedForGiveaway($giveaway);
        $remaining = $total - $assigned;

        $data = array(
            'name'  => $giveaway->getName(),
            'total' => $total,
            'assigned' => $assigned,
            'remaining' => $remaining,
            'sites' => array(),
        );

        // go through all the sites and populate their data
        foreach($this->sites as $key => $name) {
            $data['sites'][$key] = $this->giveawayKeyRepository->getAssignedForGiveawayAndSite($giveaway, $key, $from, $to);
        }

        return $data;
    }

    /**
     * @todo - this should be united with createGiveawaysReport by using some common interface
     * on Giveaway and Deal
     *
     * Creates an array report about this giveaway with the following fields:
     *
     *   * name
     *   * total
     *   * assigned
     *   * remaining
     *   * sites
     *      site_key => # assigned
     *
     * @param \Platformd\GiveawayBundle\Entity\Deal $deal
     * @param \DateTime $since
     * @return array
     */
    public function createDealReport(Deal $deal, $from, $to)
    {
        // the total numbers are not affected by the "since" - they are full totals
        $total = $this->dealCodeRepository->getTotalForDeal($deal);
        $assigned = $this->dealCodeRepository->getAssignedForDeal($deal);
        $remaining = $total - $assigned;

        $data = array(
            'name'  => $deal->getName(),
            'total' => $total,
            'assigned' => $assigned,
            'remaining' => $remaining,
            'sites' => array(),
        );

        // go through all the sites and populate their data
        foreach($this->sites as $key => $name) {
            $data['sites'][$key] = $this->dealCodeRepository->getAssignedForDealAndSite($deal, $key, $from, $to);
        }

        return $data;
    }

    /**
     * Returns an array of metric data (each as an array) for each site
     * in the system. The key to the array is the site key, and each array
     * contains several fields (see below).
     */
    public function createMembershipByCountryReport(DateTime $since = null, $site = null)
    {
        $data = array();

        $sites = $site ? array($site->getDefaultLocale() => $site->getName()) : $this->sites;

        foreach ($sites as $key => $name) {

            $totalUsers = $this->userRepo->getTotalUsersForSite($key);

            $arenaOptIn = $this->userRepo->getArenaOptInForSite($key);
            $arenaPercentage = ($arenaOptIn == 0) ? 0 : number_format(100 * ($arenaOptIn / $totalUsers), 2);

            $dellOptIn = $this->userRepo->getDellOptInForSite($key);
            $dellPercentage = ($dellOptIn == 0) ? 0 : number_format(100 * ($dellOptIn / $totalUsers), 2);

            $newUsers = $this->userRepo->countNewRegistrants($since, $key);

            $data[$key] = array(
                'siteName'             => $name,
                'count'                => $totalUsers,
                'arenaOptIn'           => $arenaOptIn,
                'arenaOptInPercentage' => $arenaPercentage,
                'dellOptIn'            => $dellOptIn,
                'dellOptInPercentage'  => $dellPercentage,
                'newUsers'             => $newUsers
            );
        }

        return $data;
    }

    public function createMembershipByCountryAndRegionReport($countries, $regions, $countryDates, $regionDates)
    {
        $data = array();

        foreach ($countries as $country) {
            $countryCode = $country->getCode();

            $totalUsers = $this->userRepo->getTotalUsersForCountry($countryCode);
            $arenaOptIn = $this->userRepo->getArenaOptInForCountry($countryCode);
            $dellOptIn = $this->userRepo->getDellOptInForCountry($countryCode);
            $newUsers = $this->userRepo->countNewRegistrantsForCountry($countryCode, $countryDates['from'], $countryDates['to']);

            $arenaPercentage = ($arenaOptIn == 0) ? 0 : number_format(100 * ($arenaOptIn / $totalUsers), 2);
            $dellPercentage = ($dellOptIn == 0) ? 0 : number_format(100 * ($dellOptIn / $totalUsers), 2);

            $data['country'][$country->getId()] = array(
                'name'                 => $country->getName(),
                'count'                => $totalUsers,
                'arenaOptIn'           => $arenaOptIn,
                'arenaOptInPercentage' => $arenaPercentage,
                'dellOptIn'            => $dellOptIn,
                'dellOptInPercentage'  => $dellPercentage,
                'newUsers'             => $newUsers
            );
        }

        foreach ($regions as $region) {

            $countries = array();

            foreach ($region->getCountries() as $country) {
                $countries[] = $country->getCode();
            }

            $totalUsers = $this->userRepo->getTotalUsersForCountries($countries);
            $arenaOptIn = $this->userRepo->getArenaOptInForCountries($countries);
            $dellOptIn = $this->userRepo->getDellOptInForCountries($countries);
            $newUsers = $this->userRepo->countNewRegistrantsForCountries($countries, $regionDates['from'], $regionDates['to']);

            $arenaPercentage = ($arenaOptIn == 0) ? 0 : number_format(100 * ($arenaOptIn / $totalUsers), 2);
            $dellPercentage = ($dellOptIn == 0) ? 0 : number_format(100 * ($dellOptIn / $totalUsers), 2);

            $data['region'][$region->getId()] = array(
                'name'                 => $region->getName(),
                'count'                => $totalUsers,
                'arenaOptIn'           => $arenaOptIn,
                'arenaOptInPercentage' => $arenaPercentage,
                'dellOptIn'            => $dellOptIn,
                'dellOptInPercentage'  => $dellPercentage,
                'newUsers'             => $newUsers
            );
        }

        return $data;
    }

    /**
     * Generates group metrics
     *
     * @param \DateTime $date
     * @return array
     */
    public function generateGroupMetrics($full = false)
    {
        foreach ($this->sites as $key => $name) {

            $site   = $this->siteRepository->findOneBy(array('name' => $name));

            // We fetch all groups for this site
            $groups = $this->groupRepository->findAllGroupsRelevantForSite($site);

            if (count($groups) > 0) {
                foreach ($groups as $group) {
                    /** @var GroupMetric $lastMetric */
                    $lastMetric = $this->groupMetricRepository->findLastMetricForGroup($group);

                    if ($full || empty($lastMetric)) {
                        $this->groupMetricRepository->deleteAllFromGroup($group);

                        /** @var DateTime $date */
                        $date = $this->globalActivityRepository->getOriginOfTimes();
                        $date->modify('midnight');

                    } else {
                        // We make sure all metrics are fully processed
                        $this->completeGroupMetrics($group);

                        $date = $lastMetric->getDate();
                        $date->modify('+1 day');
                    }

                    $this->generateGroupMetricsFromDate($group, $date);
                }
            }
        }
    }

    /**
     * Processes one metric
     *
     * @param \Platformd\GroupBundle\Entity\Metric\GroupMetric $groupMetric
     */
    private function processGroupMetric(GroupMetric $groupMetric)
    {
        $start = $groupMetric->getDate();
        $thru = clone $start;
        $thru->modify('+1 day');

        $groupMetric->setNewDiscussions($this->globalActivityRepository->getCountNewGroupDiscussions($groupMetric->getGroup(), $start, $thru));
        $groupMetric->setDeletedDiscussions($this->globalActivityRepository->getCountDeletedGroupDiscussions($groupMetric->getGroup(), $start, $thru));
        $groupMetric->setNewMembers($this->globalActivityRepository->getCountNewGroupMembers($groupMetric->getGroup(), $start, $thru));

        $this->groupMetricRepository->save($groupMetric);
    }

    /**
     * Checks if all existing metrics are fully processed,
     * if not - processes them
     *
     * @param \Platformd\GroupBundle\Entity\Group $group
     */
    private function completeGroupMetrics(Group $group)
    {
        $incompleteGroupMetrics = $this->groupMetricRepository->findIncompleteMetricsForGroup($group);

        if (count($incompleteGroupMetrics) > 0) {
            foreach ($incompleteGroupMetrics as $incompleteGroupMetric) {
                $this->processGroupMetric($incompleteGroupMetric);
            }
        }
    }

    /**
     * Generate Group Metrics
     *
     * @param \Platformd\GroupBundle\Entity\Group $group
     * @param \DateTime $date
     */
    private function generateGroupMetricsFromDate(Group $group, DateTime $date)
    {
        $today = new DateTime('today midnight', new DateTimeZone('UTC'));

        while ($date <= $today) {
            $groupMetric = new GroupMetric($group);
            $groupMetric->setDate($date);

            $this->processGroupMetric($groupMetric);

            $date->modify('+1 day');
        }
    }

    /**
     * Get an array of metrics for a specified period of time
     *
     * @param \Platformd\GroupBundle\Entity\Group $group
     * @param \DateTime $from
     * @param \DateTime $thru
     * @return mixed
     */
    public function getGroupMetricsForPeriod(Group $group, DateTime $from = null, DateTime $thru = null)
    {
        $from = $from ? $from : new DateTime('-1 month midnight', new DateTimeZone('UTC'));
        $thru = $thru ? $thru : new DateTime('today midnight', new DateTimeZone('UTC'));

        return $this->groupMetricRepository->findMetricsForPeriod($group, $from, $thru);
    }

    /**
     * Creates a discussions metrics summary array for a specific group
     *
     * @param \Platformd\GroupBundle\Entity\Group $group
     * @param \DateTime $from
     * @param \DateTime $thru
     */
    public function getDiscussionMetricsForGroup(Group $group, DateTime $from, DateTime $thru)
    {
        $arr = array(
            'replies' => 0,
            'repliesAdded' => 0,
            'activeUsers' => 0,
            'discussionsAdded' => 0,
            'discussionsDeleted' => 0
        );

        $numberOfDays = $thru->diff($from)->days;

        $arr['discussions'] = $group->getDiscussions()->count();

        $groupMetrics = $this->groupMetricRepository->findMetricsForPeriod($group, $from, $thru);
        foreach ($groupMetrics as $groupMetric) {
            $arr['discussionsAdded'] += $groupMetric->getNewDiscussions();
            $arr['discussionsDeleted'] += $groupMetric->getDeletedDiscussions();
        }

        foreach($group->getDiscussions() as $discussion) {
            $arr['replies'] += $discussion->getReplyCount();

            $discussionMetrics = $this->groupDiscussionMetricRepository->findMetricsForPeriod($discussion, $from, $thru);
            foreach ($discussionMetrics as $discussionMetric) {
                $arr['repliesAdded'] += $discussionMetric->getReplies();
                $arr['activeUsers'] += $discussionMetric->getActiveUsers();
            }
        }

        $arr['avgDiscussions'] = round(($arr['discussionsAdded'] / $numberOfDays), 1);
        $arr['avgReplies'] = round(($arr['repliesAdded'] / $numberOfDays), 1);

        return $arr;
    }

    /**
     * Creates a detailed
     *
     * @param \Platformd\GroupBundle\Entity\GroupDiscussion $groupDiscussion
     * @param $from
     * @param $thru
     */
    public function getDiscussionMetricsDetails(GroupDiscussion $groupDiscussion, DateTime $from = null, DateTime $thru = null)
    {
        $arr = array(
            'replies' => 0,
            'activeUsers' => 0,
            'views' => 0
        );

        $discussionMetrics = $this->groupDiscussionMetricRepository->findMetricsForPeriod($groupDiscussion, $from, $thru);

        foreach ($discussionMetrics as $discussionMetric) {
            $arr['replies'] += $discussionMetric->getReplies();
            $arr['activeUsers'] += $discussionMetric->getActiveUsers();
            $arr['views'] += $discussionMetric->getViews();
        }

        return $arr;
    }

    /**
     * Generates group discussions metrics
     *
     * @param \DateTime $date
     * @return array
     */
    public function generateGroupDiscussionMetrics($full = null)
    {
        foreach ($this->sites as $key => $name) {

            $site   = $this->siteRepository->findOneBy(array('name' => $name));

            // We fetch all group discussions for this site
            $groupDiscussions = $this->groupDiscussionRepository->findAllGroupDiscussionsRelevantForSite($site);

            if (count($groupDiscussions) > 0) {
                foreach ($groupDiscussions as $groupDiscussion) {
                    /** @var GroupMetric $lastMetric */
                    $lastMetric = $this->groupDiscussionMetricRepository->findLastMetricForGroup($groupDiscussion);

                    if ($full || empty($lastMetric)) {
                        $this->groupDiscussionMetricRepository->deleteAllFromGroupDiscussion($groupDiscussion);

                        /** @var DateTime $date */
                        $date = $this->globalActivityRepository->getOriginOfTimes();
                        $date->modify('midnight');

                    } else {
                        // We make sure all metrics are fully processed
                        $this->completeGroupDiscussionMetrics($groupDiscussion);

                        $date = $lastMetric->getDate();
                        $date->modify('+1 day');
                    }

                    $this->generateGroupDiscussionMetricsFromDate($groupDiscussion, $date);
                }
            }
        }
    }

    private function completeGroupDiscussionMetrics(GroupDiscussion $groupDiscussion)
    {
        $incompleteGroupDiscussionMetrics = $this->groupDiscussionMetricRepository->findIncompleteMetricsForGroupDiscussion($groupDiscussion);

        if (count($incompleteGroupDiscussionMetrics) > 0) {
            foreach ($incompleteGroupDiscussionMetrics as $incompleteGroupDiscussionMetric) {
                $this->processGroupDiscussionMetric($incompleteGroupDiscussionMetric);
            }
        }
    }

    /**
     * Processes one metric
     *
     * @param \Platformd\GroupBundle\Entity\Metric\GroupDiscussionMetric $groupDiscussionMetric
     */
    private function processGroupDiscussionMetric(GroupDiscussionMetric $groupDiscussionMetric)
    {
        $start = $groupDiscussionMetric->getDate();
        $thru = clone $start;
        $thru->modify('+1 day');

        $groupDiscussionMetric->setReplies($this->globalActivityRepository->getCountGroupDiscussionReplies($groupDiscussionMetric->getGroupDiscussion(), $start, $thru));
        $groupDiscussionMetric->setViews($this->globalActivityRepository->getCountGroupDiscussionViews($groupDiscussionMetric->getGroupDiscussion(), $start, $thru));
        $groupDiscussionMetric->setActiveUsers($this->globalActivityRepository->getCountGroupDiscussionActiveUsers($groupDiscussionMetric->getGroupDiscussion(), $start, $thru));

        $this->groupDiscussionMetricRepository->save($groupDiscussionMetric);
    }

    /**
     * Generate Group Metrics
     *
     * @param \Platformd\GroupBundle\Entity\Group $group
     * @param \DateTime $date
     */
    private function generateGroupDiscussionMetricsFromDate(GroupDiscussion $groupDiscussion, DateTime $date)
    {
        $today = new DateTime('today midnight', new DateTimeZone('UTC'));

        while ($date <= $today) {
            $groupDiscussionMetric = new GroupDiscussionMetric($groupDiscussion);
            $groupDiscussionMetric->setDate($date);

            $this->processGroupDiscussionMetric($groupDiscussionMetric);

            $date->modify('+1 day');
        }
    }


    /**
     * @param FormFactoryInterface $formFactory
     * @return \Symfony\Component\Form\FormBuilder
     */
    public function createFilterFormBuilder(FormFactoryInterface $formFactory)
    {
        return $formFactory->createBuilder(
            'form',
            array(),
            array('csrf_protection' => false)
        )
        ->add('startDate', 'date', array(
            'widget' => 'single_text',
            'attr'   => array(
                'class' => 'date-picker'
            ),
            'format' => 'yyyy-MM-dd',
        ))
        ->add('endDate', 'date', array(
            'widget' => 'single_text',
            'attr'   => array(
                'class' => 'date-picker'
            ),
            'format' => 'yyyy-MM-dd',
        ));

    }
}
