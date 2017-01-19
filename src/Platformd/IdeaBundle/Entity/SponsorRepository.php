<?php

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * SponsorRepository
 */
class SponsorRepository extends EntityRepository
{
    public function findAttachedSponsors($scope, $containerId)
    {
        $qb = $this->createQueryBuilder('s')
            ->select      ('s')
            ->leftJoin    ('s.sponsorRegistrations', 'r')
            ->where       ('r.'.$scope.' = :containerId')
            ->andWhere    ('r.status = :sponsoring')
            ->setParameter('containerId', $containerId)
            ->setParameter('sponsoring', SponsorRegistry::STATUS_SPONSORING)
            ->orderBy     ('r.level', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findUnattachedSponsors($scope, $containerId)
    {
        $sponsors         = $this->findAll();
        $attachedSponsors = $this->findAttachedSponsors($scope, $containerId);

        $unattachedSponsors = array();

        foreach ($sponsors as $sponsor) {
            if (!in_array($sponsor, $attachedSponsors)) {
                $unattachedSponsors[] = $sponsor;
            }
        }

        return $unattachedSponsors;
    }
}
