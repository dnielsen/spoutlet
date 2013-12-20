<?php

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\EntityRepository;

use FOS\UserBundle\Propel\Group as G;
use Platformd\GroupBundle\Event\GroupEvent;
use Platformd\IdeaBundle\Entity\VoteCriteria;
use Platformd\SpoutletBundle\Entity\Site;

/**
 * EntrySetRegistryRepository
 *
 */
class EntrySetRegistryRepository extends EntityRepository
{
    public function getContainer(EntrySetRegistry $registration) {

        if($registration == null) {
            return null;
        }

        $containerId = $registration->getContainerId();
        $className = $registration->getScope();


//        Platformd\EventBundle\Entity\GroupEvent --> EventBundle:GroupEvent
//        Platformd\GroupBundle\Entity\Group --> GroupBundle:Group
//        SpoutletBundle:Site
//        IdeaBundle:Session

        $queryString = 'SELECT s FROM '.$className.' s WHERE s.id = :containerId';
        $query = $this->getEntityManager()
            ->createQuery($queryString)
            ->setParameter('containerId', $containerId);

        $result = $query->getSingleResult();
        return $result;
    }

}
