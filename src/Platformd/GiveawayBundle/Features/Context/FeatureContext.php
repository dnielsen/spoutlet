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

use Platformd\SpoutletBundle\Features\Context\AbstractFeatureContext;

/**
 * Feature context.
 */
class FeatureContext extends AbstractFeatureContext
{
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

                foreach ($keys as $key) {
                    $gKey = new GiveawayKey($key);
                    $gKey->setPool($pool);

                    $em->persist($gKey);
                }

                $em->persist($pool);
            }

            $em->persist($giveaway);
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
        throw new PendingException('Have not implemented machine code stuff yet');
    }

    /**
     * @Given /^my machine code entry is approved$/
     */
    public function myMachineCodeEntryIsApproved()
    {
        throw new PendingException('Have not implemented machine code stuff yet');
    }
}
