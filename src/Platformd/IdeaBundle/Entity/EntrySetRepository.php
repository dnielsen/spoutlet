<?php

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\EntityRepository;

use FOS\UserBundle\Propel\Group as G;
use Platformd\GroupBundle\Event\GroupEvent;
use Platformd\IdeaBundle\Entity\VoteCriteria;
use Platformd\SpoutletBundle\Entity\Site;

/**
 * EntrySetRepository
 *
 */
class EntrySetRepository extends EntityRepository
{
    public function sortByPopularity($entrySets) {

        usort($entrySets, function($a, $b) {
            $valueA = $a->getNumEntries();
            $valueB = $b->getNumEntries();

            if($valueA == $valueB ) { return 0; }

            return ($valueA < $valueB) ? 1 : -1;
        });
        return $entrySets;
    }

}
