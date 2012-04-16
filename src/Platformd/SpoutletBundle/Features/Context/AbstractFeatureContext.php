<?php

namespace Platformd\SpoutletBundle\Features\Context;

use Behat\BehatBundle\Context\MinkContext;
use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

use Behat\Behat\Event\ScenarioEvent;
use Behat\Behat\Context\Step\When;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * Base Feature context.
 */
class AbstractFeatureContext extends MinkContext
{
    protected $currentUser;

    protected $currentSite = 'en';

    /**
     * @BeforeScenario
     */
    public function purgeDatabase()
    {
        $purger = new ORMPurger($this->getEntityManager());
        $purger->purge();
    }

    /**
     * @Given /^I am authenticated as "([^"]*)"$/
     */
    public function iAmAuthenticatedAs($user)
    {
        /*
         * Outdated, since we don't really have a login now
         *
        return array(
            new When('I am on "/login"'),
            new When(sprintf('I fill in "Email:" with "%s"', $user)),
            new When(sprintf('I fill in "Password:" with "%s"', $user)),
            new When('I press "Login"'),
        );
        */

        // we go to /login, the stub API logs us in, we click Continue, done.
        return array(
            new When('I am on "/login"'),
            new When(sprintf('I follow "Continue"')),
        );
    }

    /**
     * @Given /^I am authenticated$/
     */
    public function iAmAuthenticated()
    {
        $steps = $this->iAmAuthenticatedAs('foo');

        $steps[] = function() {
            $user = $this->getEntityManager()
                ->getRepository('UserBundle:User')
                ->findOneBy(array('username' => 'admin'))
            ;

            if (!$user) {
                throw new \Exception('Cannot find admin user');
            }

            $this->currentUser = $user;
        };

        return $steps;
    }

    /**
     * @AfterScenario
     */
    public function printLastResponseOnError(ScenarioEvent $scenarioEvent)
    {
        if ($scenarioEvent->getResult() != 0) {
            $this->printLastResponse();
        }
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getEntityManager()
            ;
    }

    protected function getCurrentSite()
    {
        return $this->currentSite;
    }

    /**
     * Overridden so that PHPdoc is properly recognized
     *
     * @return \Behat\Mink\Element\DocumentElement
     */
    protected function getPage()
    {
        return $this->getSession()->getPage();
    }

    /**
     * @return \Behat\Mink\Mink
     */
    public function getMink()
    {
        return parent::getMink();
    }
}
