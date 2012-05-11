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
use Behat\Mink\Driver\GoutteDriver;
use Platformd\SpoutletBundle\Entity\Game;

/**
 * Base Feature context.
 */
class AbstractFeatureContext extends MinkContext
{
    /**
     * @var \Platformd\UserBundle\Entity\User
     */
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
     * @Given /^I am authenticated$/
     */
    public function iAmAuthenticated()
    {
        $user = $this->getCurrentUser();

        return array(
            new When(sprintf('I am on "/?username=%s"', $user->getCevoUserId())),
        );

        // we go to /login, the stub API logs us in, we click Continue, done.
        return array(
            new When('I am on "/login"'),
            new When(sprintf('I follow "Continue"')),
        );
    }

    /**
     * @Given /^I am authenticated as an organizer$/
     */
    public function iAmAuthenticatedAsAnOrganizer()
    {
        // guarantee there is a user, because we wouldn't normally say it beforehand...
        if (!$this->currentUser) {
            $this->IHaveAnAccount();
        }

        // enforce the right role
        $this->currentUser->setRoles(array(
            'ROLE_ORGANIZER',
        ));
        $this->getUserManager()->updateUser($this->currentUser);

        return $this->iAmAuthenticated();
    }

    /**
     * @Given /^I am authenticated as a Dell Contact$/
     */
    public function iAmAuthenticatedAsDellContact()
    {
        // guarantee there is a user, because we wouldn't normally say it beforehand...
        if (!$this->currentUser) {
            $this->IHaveAnAccount();
        }

        // enforce the right role
        $this->currentUser->setRoles(array(
            'ROLE_ORGANIZER',
        ));
        $this->getUserManager()->updateUser($this->currentUser);

        return $this->iAmAuthenticated();
    }

    /**
     * @AfterScenario
     */
    public function printLastResponseOnError(ScenarioEvent $scenarioEvent)
    {
        if ($scenarioEvent->getResult()) {
            if ($this->getSession()->getDriver() instanceof GoutteDriver
                && $this->getSession()->getDriver()->getClient()->getRequest()) {

                $this->printLastResponse();
            }
        }
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
     * @return \Behat\Mink\Session
     */
    public function getSession($name = null)
    {
        return parent::getSession($name);
    }

    /**
     * @return \Behat\Mink\Mink
     */
    public function getMink()
    {
        return parent::getMink();
    }

    /**
     * @Given /^I have an account/
     */
    public function IHaveAnAccount()
    {
        $um = $this->getUserManager();

        $this->iHaveNoInTheDatabase('UserBundle:User');

        $user = $um->createUser();
        $user->setUsername('user');
        $user->setPlainPassword('user');
        $user->setEmail('user@user.com');
        $user->setCevoUserId(55);

        $um->updateUser($user);

        $this->currentUser = $user;
    }

    /**
     * @Given /^I have the "([^"]*)" role$/
     */
    public function iHaveTheRole($role)
    {
        $this->currentUser->addRole($role);

        $this->getEntityManager()->persist($this->currentUser);
        $this->getEntityManager()->flush();
    }

    /**
     * @Given /^I have the following users:$/
     */
    public function iHaveTheFollowingUsers(TableNode $table)
    {
        foreach ($table->getHash() as $data) {
            $user = $this->getUserManager()->createUser();

            $user->setUsername($data['username']);
            $user->setEmail($data['email']);

            $user->setPassword('foo');

            $this->getUserManager()->updateUser($user);
        }
    }

    /**
     * @Given /^I have the "([^"]*)" permissions$/
     */
    public function iHaveThePermissions($roles)
    {
        $roles = explode(',', $roles);

        $user = $this->getCurrentUser();

        foreach ($roles as $role) {
            $user->addRole(trim($role));
        }

        $this->getUserManager()->updateUser($user);
    }

    /**
     * @When /^I click to add new "([^"]*)"$/
     */
    public function iClickToAddNew($section)
    {
        $sidebar = $this->getPage()->find('css', '.sidebar .well');
        if (!$sidebar) {
            throw new \Exception('Cannot find the sidebar! Are you on the wrong page?');
        }
        $sectionEle = $sidebar->findLink($section);

        if (!$sectionEle) {
            throw new \Exception(sprintf('Could not found sidebar link called "%s"', $section));
        }


        $sectionEle->getParent()->clickLink('Add new');
    }

    /**
     * @When /^I click (?:|on )"([^"]*)"$/
     */
    public function iClick($link)
    {
        return new When(sprintf('I follow "%s"', $link));
    }

    /**
     * @Given /^there is a game called "([^"]*)"$/
     */
    public function thereIsAGameCalled($name)
    {
        $game = new Game();
        $game->setName($name);

        $this->getEntityManager()->persist($game);
        $this->getEntityManager()->flush();
    }

    /**
     * Used in the admin to count rows in a table
     *
     * @Then /^I should see (\d+) data rows$/
     */
    public function iShouldSeeDataRows($num)
    {
        $rows = $this->getPage()->find('css', 'table.table tbody tr');

        assertCount($num, $rows);
    }

    /**
     * @return \FOS\UserBundle\Model\UserManagerInterface
     */
    protected function getUserManager()
    {
        return $this->getContainer()->get('fos_user.user_manager');
    }

    /**
     * @return \Platformd\UserBundle\Entity\User
     * @throws \Exception
     */
    protected function getCurrentUser()
    {
        if (!$this->currentUser) {
            throw new \Exception('Please call "I have an account" first');
        }

        return $this->currentUser;
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

    /**
     * @param $repo
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getRepository($repo)
    {
        return $this->getEntityManager()
            ->getRepository($repo)
        ;
    }

    protected function getCurrentSite()
    {
        return $this->currentSite;
    }

    /**
     * @Given /^I have no "([^"]*)" in the database$/
     */
    public function iHaveNoInTheDatabase($model)
    {
        $this->getEntityManager()
            ->createQuery(sprintf('DELETE FROM %s', $model))
            ->execute()
        ;
    }
}
