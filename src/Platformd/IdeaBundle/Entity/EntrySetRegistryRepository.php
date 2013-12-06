<?php

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\EntityRepository;

use FOS\UserBundle\Propel\Group;
use Platformd\GroupBundle\Event\GroupEvent;
use Platformd\IdeaBundle\Entity\VoteCriteria;
use Platformd\SpoutletBundle\Entity\Site;

/**
 * EntrySetRegistryRepository
 *
 */
class EntrySetRegistryRepository extends EntityRepository
{
    public function getContainerByRegistryId(EntrySetRegistry $registration) {
        if($registration == null) {
            return null;
        }

        $containerId = $registration->getContainerId();
        $className = $registration->getScope();

//        EventBundle:GroupEvent
//        GroupBundle:Group
//        SpoutletBundle:Site
//        IdeaBundle:Session

        $containerClass = "";
        if(strpos($className,'GroupEvent') != false) {
            $containerClass = "EventBundle:GroupEvent";
        }

        $queryString = 'SELECT s FROM '.$containerClass.' s WHERE s.id = :containerId';
        $query = $this->getEntityManager()
            ->createQuery($queryString)
            ->setParameter('containerId', $containerId);

        $result = $query->getSingleResult();
//        var_dump($result);
        return $result;
    }

}
