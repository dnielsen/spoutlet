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
    /**
     * @var Giveaway
     */
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
            $giveaway->setAsActive();

            if (isset($data['name'])) {
                $giveaway->setName($data['name']);
            }

            if (isset($data['type'])) {
                $giveaway->setGiveawayType($data['type']);
            }

            $site = $em->getRepository('SpoutletBundle:Site')->findOneByDefaultLocale($this->getCurrentSite());

            $giveaway->setSites(array($site));

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
     * @Given /^there should be "([^"]*)" "([^"]*)" machine code entry in the database$/
     */
    public function thereShouldBeAMachineCodeEntryInTheDatabase($num, $status)
    {
        $codes = $this->getRepository('GiveawayBundle:MachineCodeEntry')
            ->findBy(array('status' => $status))
        ;

        if (count($codes) != $num) {
            throw new \Exception(sprintf('Expected %s machine codes to be found, instead found %s', $num, count($codes)));
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
     * @Given /^the following machine code entries:$/
     */
    public function theFollowingMachineCodeEntries(TableNode $table)
    {
        foreach ($table->getHash() as $data) {
            $machineCodeEntry = new MachineCodeEntry($this->currentGiveaway, $data['machineCode']);

            $user = $this->getUserManager()->findUserByUsername($data['username']);
            $machineCodeEntry->attachToUser($user, '127.0.0.1');

            $this->getEntityManager()->persist($machineCodeEntry);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @Given /^my machine code entry is approved$/
     */
    public function myMachineCodeEntryIsApproved()
    {
        $em = $this->getEntityManager();
        $site = $em->getRepository('SpoutletBundle:Site')->findOneByDefaultLocale($this->getCurrentSite());

        $this->getGiveawayManager()->approveMachineCode($this->currentMachineCode, $site);
    }

    /**
     * @return \Platformd\GiveawayBundle\Model\GiveawayManager
     */
    private function getGiveawayManager()
    {
        return $this->getContainer()->get('pd_giveaway.giveaway_manager');
    }

    /**
     * @Given /^there is a key giveaway called "([^"]*)"$/
     */
    public function thereIsAKeyGiveawayCalled($giveawayName)
    {
        $em = $this->getEntityManager();

        $giveaway = new Giveaway();
        $giveaway->setName($giveawayName);
        $giveaway->setLocale("en");
        $giveaway->setContent("content");
        $giveaway->setGame(NULL);

        $em->persist($giveaway);
        $em->flush();

        $this->currentGiveaway = $giveaway;
    }

    /**
     * @Given /^I am on the edit page for the key giveaway$/
     */
    public function iAmOnTheEditPageForTheKeyGiveaway()
    {
        $this->NavigateTo('admin_giveaway_edit', array('id' => $this->currentGiveaway->getId()));
    }

    /**
     * @Given /^Giveway "([^""]*)" should(?<not> not)? display remaining keys$/
     */
    public function givewayShouldDisplayRemainingKeys($name, $not = null)
    {
        $em = $this->getEntityManager();

        $giveaway = $em->getRepository('Platformd\GiveawayBundle\Entity\Giveaway')->findOneByName($name);
        assertNotNull($giveaway);

        $this->setSitehost('demo');
        $this->NavigateTo('giveaway_show', array('slug' => $giveaway->getSlug()), true);
        if ($not) {
            assertNull($this->getSession()->getPage()->find('css', sprintf('h3:contains("%s")', 'Available keys: 0')));
        }
        else {
            assertNotNull($this->getSession()->getPage()->find('css', sprintf('h3:contains("%s")', 'Available keys: 0')));
        }
    }

    protected function setSitehost($siteName)
    {
        $context = $this->getContainer()->get('router')->getContext();
        $context->setHost($siteName.'.'.$context->getHost());
    }
}
