<?php

namespace Platformd\IdeaBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * VoteRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class VoteRepository extends EntityRepository
{
	public function findAllByIdea($idea, $round = null) {
		$queryString = 'SELECT v FROM IdeaBundle:Vote v WHERE v.idea = :idea' . ($round != null ? ' AND v.round = :round' : '');

		$query = $this->getEntityManager()
            ->createQuery($queryString)
            ->setParameter('idea', $idea);

		if($round != null)
            $query->setParameter('round', $round);

		return $query->getResult();
	}

	//Removed because cascade delete handles this in Idea entity
// 	//removes all votes associated to the idea regardless of round.
// 	public function removeAllByIdea(Idea $idea) {
// 		$votes = $this->findAllByIdea($idea);
// 		$em = $this->getEntityManager();
// 		foreach($votes as $vote) {
// 			$em->remove($vote);
// 		}
// 	}

	public function findAllByCriteria($criteria, $round = null) {
		$queryString = 'SELECT v FROM IdeaBundle:Vote v WHERE v.criteria = :criteria' . ($round != null ? ' AND v.round = :round' : '');

		$query = $this->getEntityManager()
			->createQuery($queryString)
			->setParameter('criteria', $criteria);

		if($round != null)
			$query->setParameter('round', $round);

		return $query->getResult();
	}

	//removes all votes associated with the criteria regardless of round
	public function removeAllByCriteria(VoteCriteria $criteria) {
		$votes = $this->findAllByCriteria($criteria);
		$em = $this->getEntityManager();
		foreach($votes as $vote) {
			$em->remove($vote);
		}
	}

	public function getAvgsByCriteria($idea, $numCriteria, $round) {
		$ideaId = $idea->getId();
		$ideaVotes = $this->findAllByIdea($idea, $round);

// 		if(count(ideaVotes) == 0)
// 			return null;

		//votes can be from any criteria for the current idea
		//for each vote add an entry in totals and count if one doesn't exist
		//if it does exist increment count by 1 and totals by $vote->getValue()
		$criteriaVoteTotals = array();
		$criteriaVoteCounts = array();
		foreach( $ideaVotes as $vote) {
			$criteriaId = strval($vote->getCriteria()->getId());
			$voteValue = $vote->getValue();

			//assume if it exists in vote tots then it also exists in vote counts
			if( !array_key_exists($criteriaId, $criteriaVoteTotals) ) {
				//echo 'key not found<br>';
				$criteriaVoteTotals[$criteriaId] = $voteValue;
				$criteriaVoteCounts[$criteriaId] = 1;
			} else {
				//echo 'key found<br>';
				$criteriaVoteTotals[$criteriaId] += $voteValue;
				$criteriaVoteCounts[$criteriaId]++;
			}
		}

		//Now we have counts and totals for each criteria voted on for the current idea
		$criteriaVoteAvgs = array();
		$sumOfAvgs = 0;
		foreach($criteriaVoteTotals as $criteriaId => $total) {
			$avg = number_format($total / $criteriaVoteCounts[$criteriaId], 1);
			$sumOfAvgs += $avg;
			$criteriaVoteAvgs[$criteriaId] = $avg;
		}
		$criteriaVoteAvgs['avgScore'] = number_format($sumOfAvgs / $numCriteria, 2);

		return $criteriaVoteAvgs;
	}

	public function getIdeaCriteriaTable($ideaList, $numCriteria, $round) {
		$ideaCriteriaAvgs = array();
		foreach( $ideaList as $idea) {
			$ideaCriteriaAvgs[$idea->getId()] = $this->getAvgsByCriteria($idea, $numCriteria, $round);
		}

// 		//debug output prints table
// 		foreach( $ideaCriteriaAvgs as $ideaId => $criteriaAvgs) {
// 			foreach($criteriaAvgs as $criteriaId => $avg) {
// 				//check for special avg of avgs key
// 				if($criteriaId == 'avgScore') {
// 					echo $ideaId.' avg:'.$avg.'<br>';
// 					continue;
// 				}
// 				echo $ideaId.':'.$criteriaId.' avg:'.$avg.'<br>';
// 			}
// 		}

		return $ideaCriteriaAvgs;
	}

    public function toCSV($event) {
        $ideas = $event->getIdeas();

        $votes = array();
        foreach($ideas as $idea){
            $votes = array_merge($votes, $this->findAllByIdea($idea));
        }

        $votesArray = array();

        //Add header
        $headerArray = array(
            "Round", "Judge", "Idea", "Criteria", "Score",
        );

        $votesArray[] = $headerArray;

        foreach($votes as $vote) {

            $voteArray = array(
                $vote->getRound(),
                $vote->getVoter(),
                $vote->getIdea()->getName(),
                $vote->getCriteria()->getDisplayName(),
                $vote->getValue(),
            );
            $votesArray[] = $voteArray;
        }
        return $this->createCsvString($votesArray);
    }


    private function createCsvString($data) {
        // Open temp file pointer
        if (!$fp = fopen('php://temp', 'w+')) return FALSE;

        // Loop data and write to file pointer
        foreach ($data as $line) fputcsv($fp, $line);

        // Place stream pointer at beginning
        rewind($fp);

        // Return the data
        return stream_get_contents($fp);
    }

}

?>
