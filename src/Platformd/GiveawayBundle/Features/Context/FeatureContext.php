<?php

namespace Platformd\GiveawayBundle\Features\Context;

use Behat\BehatBundle\Context\BehatContext,
    Behat\BehatBundle\Context\MinkContext;
use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\GiveawayBundle\Entity\GiveawayKey;
use Platformd\GiveawayBundle\Entity\GiveawayPool;
use Platformd\GiveawayBundle\Entity\MachineCodeEntry;

use Platformd\SpoutletBundle\Features\Context\AbstractFeatureContext;

/**
 * Feature context.
 */
class FeatureContext extends AbstractFeatureContext
{
    protected $currentGiveaway;

    /**
     * @var \Platformd\GiveawayBundle\Entity\MachineCodeEntry
     */
    protected $currentMachineCode;

    /**
     * @Given /^the following giveaway:$/
     */
    public function theFollowingGiveaway(TableNode $table)
    {
        $em = $this->getEntityManager();

        foreach ($table->getHash() as $data) {
            $giveaway = new Giveaway();
            $giveaway->setLocale($this->getCurrentSite());
            $giveaway->setAsActive();

            if (isset($data['name'])) {
                $giveaway->setName($data['name']);
            }

            if (isset($data['type'])) {
                $giveaway->setGiveawayType($data['type']);
            }

            $keys = isset($data['keys']) ? explode(',', $data['keys']) : array();
            if (count($keys) > 0) {
                $pool = new GiveawayPool();
                $pool->setGiveaway($giveaway);
                $pool->setIsActive(true);

                // make sure to set the inverse side of the relationship...
                $giveaway->getGiveawayPools()->add($pool);

                foreach ($keys as $key) {
                    $gKey = new GiveawayKey($key);
                    $gKey->setPool($pool);

                    $em->persist($gKey);
                }

                $em->persist($pool);
            }

            $em->persist($giveaway);
        }

        // if we have 1 giveaway, make it the current one
        if (count($table->getHash() == 1)) {
            $this->currentGiveaway = $giveaway;
        }

        $em->flush();
    }

    /**
     * @Given /^there should be a "([^"]*)" machine code entry in the database$/
     */
    public function thereShouldBeAMachineCodeEntryInTheDatabase($status)
    {
        $codes = $this->getRepository('GiveawayBundle:MachineCodeEntry')
            ->findBy(array('status' => $status))
        ;

        if (empty($codes)) {
            throw new \Exception('No MachineCode found for status '.$status);
        }

        if (count($codes) > 1) {
            throw new \Exception('More than 1 machine code found for status '.$status);
        }
    }

    /**
     * @Given /^I have a "([^"]*)" machine code entry in the database$/
     */
    public function iHaveAMachineCodeEntryInTheDatabase($machineCode)
    {
        $machineCode = new MachineCodeEntry($this->currentGiveaway, $machineCode);
        $machineCode->attachToUser($this->currentUser, '127.0.0.1');

        $this->getEntityManager()->persist($machineCode);
        $this->getEntityManager()->flush();

        $this->currentMachineCode = $machineCode;
    }

    /**
     * @Given /^my machine code entry is approved$/
     */
    public function myMachineCodeEntryIsApproved()
    {
        $this->getGiveawayManager()->approveMachineCode($this->currentMachineCode);
    }

    /**
     * @return \Platformd\GiveawayBundle\Model\GiveawayManager
     */
    private function getGiveawayManager()
    {
        return $this->getContainer()->get('pd_giveaway.giveaway_manager');
    }
}
