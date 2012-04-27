<?php

namespace Platformd\SweepstakesBundle\Features\Context;

use Behat\BehatBundle\Context\BehatContext,
    Behat\BehatBundle\Context\MinkContext;
use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Platformd\SweepstakesBundle\Entity\Entry;


use Platformd\SpoutletBundle\Features\Context\FeatureContext as BaseContext;

/**
 * Feature context.
 */
class FeatureContext extends BaseContext
{
    protected $currentSweepstakes;

    /**
     * @Given /^there is a sweepstakes$/
     */
    public function thereIsASweepstakes()
    {
        $sweepstakes = new Sweepstakes();
        $sweepstakes->setName('testing');
        $sweepstakes->setContent('testing sweepstakes');
        $sweepstakes->setLocale('en');

        $em = $this->getEntityManager();
        $em->persist($sweepstakes);
        $em->flush();

        $this->currentSweepstakes = $sweepstakes;
    }

    /**
     * @Given /^some people are entered into the sweepstakes$/
     */
    public function somePeopleAreEnteredIntoTheSweepstakes()
    {
        if (!$this->currentSweepstakes) {
            throw new \Exception('Create a current sweepstakes first');
        }

        $user = $this->getEntityManager()
            ->getRepository('UserBundle:User')
            ->createQueryBuilder('u')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;

        if (!$user) {
            throw new \Exception('Could not find any users to enter into the sweepstakes');
        }

        $em = $this->getEntityManager();

        for ($i = 0; $i <= 3; $i++) {
            $entry = new Entry();
            $entry->setSweepstakes($this->currentSweepstakes);
            $entry->setUser($user);
            $entry->setIpAddress('127.0.0.1');
            $em->persist($entry);
        }

        $em->flush();
    }


}
