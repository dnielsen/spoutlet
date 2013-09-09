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
use Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRuleset;
use Platformd\SpoutletBundle\Entity\CountryAgeRestrictionRule;

use Symfony\Component\HttpKernel\HttpKernelInterface;
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
     * @Given /^the current giveaway has the following pools:$/
     */
    public function theGiveawayHasTheFollowingPools(TableNode $table)
    {
        $em = $this->getEntityManager();
        $countryRepo = $em->getRepository('SpoutletBundle:Country');

        foreach ($table->getHash() as $data) {
            $pool = new GiveawayPool();

            $pool->setGiveaway($this->currentGiveaway);

            if (isset($data['description'])){
                $pool->setDescription($data['description']);
            }

            if (isset($data['max_per_ip'])){
                $pool->setMaxKeysPerIp($data['max_per_ip']);
            }

            if (isset($data['active'])){
                $pool->setIsActive($data['active'] == 'yes');
            }

            if (isset($data['country'])){

                $country = $countryRepo->findOneByCode($data['country']);
                if ($country) {
                    $ruleset = new CountryAgeRestrictionRuleset();
                    $ruleset->setParentType(CountryAgeRestrictionRuleset::PARENT_TYPE_GIVEAWAY_POOL);
                    $ruleset->setDefaultAllow(false);

                    $em->persist($ruleset);
                    $em->flush();

                    $rule = new CountryAgeRestrictionRule();
                    $rule->setCountry($country);
                    $rule->setRuleType(CountryAgeRestrictionRule::RULE_TYPE_ALLOW);
                    $rule->setRuleset($ruleset);

                    $em->persist($rule);
                    $em->flush();
                }

                $pool->setRuleset($ruleset);
            }

            $em->persist($pool);
            $em->flush();

            $keyCount = isset($data['key_count']) ? $data['key_count'] : 2;

            for ($i=1; $i <= $keyCount; $i++) {
                $key = new GiveawayKey($data['description'].'_'.$i);
                $key->setPool($pool);
                $em->persist($key);
            }

            $em->flush();
        }
    }

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

            $site = $this->currentSite;
            $giveaway->getSites()->add($site);
            //$giveaway->setSites(array($this->currentSite));

            $region = $em->getRepository('SpoutletBundle:Region')->findRegionForSite($site);

            $keys = isset($data['keys']) ? explode(',', $data['keys']) : array();
            if (count($keys) > 0) {
                $pool = new GiveawayPool();
                $pool->setGiveaway($giveaway);
                $pool->setIsActive(true);

                if ($region) {
                    $pool->getRegions()->add($region);
                }

                // make sure to set the inverse side of the relationship...
                $giveaway->getPools()->add($pool);

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
        $ipLookupUtil = $this->getContainer()->get('platformd.model.ip_lookup_util');
        $request = $this->getSession()->getDriver()->getClient()->getRequest();

        $ipAddress = $ipLookupUtil->getClientIp($request);
        $country = $this->getContainer()->get('platformd.model.ip_lookup_util')->getCountryCode($ipAddress);

        $this->getGiveawayManager()->approveMachineCode($this->currentMachineCode, $this->getCurrentSite(), $country);
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

        $this->setSitehost('www');
        $this->NavigateTo('giveaway_show', array('slug' => $giveaway->getSlug()), true);
        if ($not) {
            assertNull($this->getSession()->getPage()->find('css', sprintf('h3:contains("%s")', 'Available Keys: 0')));
        }
        else {
            assertNotNull($this->getSession()->getPage()->find('css', sprintf('h3:contains("%s")', 'Available Keys: 0')));
        }
    }

    protected function setSitehost($siteName)
    {
        $context = $this->getContainer()->get('router')->getContext();
        $context->setHost($siteName.'.'.$context->getHost());
    }

    /**
     * @Given /^the keys run out for the "([^"]*)" deal$/
     */
    public function theKeysRunOutForTheDeal($dealName)
    {
        $em   = $this->getEntityManager();
        $deal = $em->getRepository('GiveawayBundle:Deal')->findOneByName($dealName);

        if (!$deal) {
            throw new \Exception('Could not find the deal in the database');
        }

        foreach ($deal->getPools() as $pool) {
            $em->remove($pool);
        }

        $deal->getPools()->clear();
        $em->persist($deal);
        $em->flush();
    }

    /**
     * @Given /^the keys run out for the current giveaway$/
     */
    public function theKeysRunOutForTheCurrentGiveaway()
    {
        $em         = $this->getEntityManager();
        $giveaway   = $this->currentGiveaway;

        if (!$giveaway) {
            throw new \Exception('Could not find the giveaway in the database');
        }

        foreach ($giveaway->getPools() as $pool) {
            $em->remove($pool);
        }

        $giveaway->getPools()->clear();
        $em->persist($giveaway);
        $em->flush();
    }

    /**
     * @Given /^The Key Queue Processor is run$/
     */
    public function theKeyQueueProcessorIsRun()
    {
        exec($this->getContainer()->getParameter('kernel.root_dir').'/console pd:keyRequestQueue:process --env=test');
    }

    /**
     * @Given /^I set the current giveaway to "([^"]*)"$/
     */
    public function iSetTheCurrentGiveawayTo($giveawayName)
    {
        $em         = $this->getEntityManager();
        $giveaway = $em->getRepository('GiveawayBundle:Giveaway')->findOneByName($giveawayName);

        if (!$giveaway) {
            throw new \Exception('Could not find the giveaway in the database');
        }

        $this->currentGiveaway = $giveaway;
    }

}
