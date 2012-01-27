<?php

namespace Platformd\SpoutletBundle\Metric;
use Doctrine\ORM\EntityManager;
use Platformd\GiveawayBundle\Entity\Giveaway;
use DateTime;

class MetricManager
{
    /**
     * @var \Platformd\GiveawayBundle\Entity\Repository\GiveawayKeyRepository
     */
    private $giveawayKeyRepository;

    /**
     * @var \Platformd\GiveawayBundle\Entity\Repository\GiveawayPoolRepository
     */
    private $giveawayPoolRepository;

    /**
     * @var \Platformd\UserBundle\Entity\UserRepository
     */
    private $userRepo;

    /**
     * An array of all available site keys and their names
     *
     * @var array
     */
    private $sites;

    public function __construct(EntityManager $em, array $sites)
    {
        $this->giveawayKeyRepository = $em->getRepository('GiveawayBundle:GiveawayKey');
        $this->giveawayPoolRepository = $em->getRepository('GiveawayBundle:GiveawayPool');
        $this->userRepo = $em->getRepository('UserBundle:User');
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
    public function createGiveawaysReport(Giveaway $giveaway, DateTime $since = null)
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
            $data['sites'][$key] = $this->giveawayKeyRepository->getAssignedForGiveawayAndSite($giveaway, $key, $since);
        }

        return $data;
    }

    /**
     * Returns an array of metric data (each as an array) for each site
     * in the system. The key to the array is the site key, and each array
     * contains several fields (see below).
     */
    public function createMembershipByCountryReport(DateTime $since = null)
    {
        $data = array();

        foreach ($this->sites as $key => $name) {

            $totalUsers = $this->userRepo->getTotalUsersForSite($key);

            $arenaOptIn = $this->userRepo->getArenaOptInForSite($key);
            $arenaPercentage = ($arenaOptIn == 0) ? 0 : number_format(100 * ($arenaOptIn / $totalUsers), 2);

            $dellOptIn = $this->userRepo->getDellOptInForSite($key);
            $dellPercentage = ($dellOptIn == 0) ? 0 : number_format(100 * ($dellOptIn / $totalUsers), 2);

            $newUsers = $this->userRepo->countNewRegistrants($since, $key);

            $data[$key] = array(
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
}