<?php

namespace Platformd\GiveawayBundle\Metric;
use Doctrine\ORM\EntityManager;
use Platformd\GiveawayBundle\Entity\Giveaway;

class GiveawayMetricManager
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
     * An array of all available site keys and their names
     *
     * @var array
     */
    private $sites;

    public function __construct(EntityManager $em, array $sites)
    {
        $this->giveawayKeyRepository = $em->getRepository('GiveawayBundle:GiveawayKey');
        $this->giveawayPoolRepository = $em->getRepository('GiveawayBundle:GiveawayPool');
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
     * @return array
     */
    public function createGiveawaysReport(Giveaway $giveaway)
    {
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
            $data['sites'][$key] = $this->giveawayKeyRepository->getAssignedForGiveawayAndSite($giveaway, $key);
        }

        return $data;
    }
}