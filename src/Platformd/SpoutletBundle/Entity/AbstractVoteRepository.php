<?php

namespace Platformd\SpoutletBundle\Entity;

use Doctrine\ORM\EntityRepository;

class AbstractVoteRepository extends EntityRepository
{
    public function saveVote(Vote $vote)
    {
        $this->_em->persist($vote);
        $this->_em->flush();
    }
}
