<?php

namespace Platformd\SpoutletBundle\Features\Context;

use Behat\BehatBundle\Context\MinkContext;
use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

use Behat\Behat\Event\ScenarioEvent;
use Behat\Behat\Context\Step\Given;
use Behat\Behat\Context\Step\When;
use Behat\Behat\Context\Step\Then;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Behat\Mink\Driver\GoutteDriver;
use Platformd\SpoutletBundle\Entity\Game;
use Platformd\SpoutletBundle\Entity\GamePage;

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

    public function NavigateTo($namedRoute, $parameters)
    {
        $url = $this->getContainer()->get('router')->generate($namedRoute, $parameters);
        $this->getSession()->visit($url);
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
        if ($game = $this->getEntityManager()->getRepository('SpoutletBundle:Game')->findOneBy(array('name' => $name))) {
            $this->getEntityManager()->remove($game);
            $this->getEntityManager()->flush();
        }

        $game = new Game();
        $game->setName($name);
        $game->setCategory('rpg');

        $this->getEntityManager()->persist($game);
        $this->getEntityManager()->flush();

        return $game;
    }

    /**
     * @Given /^there is a game page for "([^"]*)" in "([^"]*)"$/
     */
    public function thereIsAGamePageFor($gameName, $siteName)
    {
        $game = $this->thereIsAGameCalled($gameName);

        $page = new GamePage();
        $page->setGame($game);
        $page->setLocales(array($siteName));
        $page->setStatus(GamePage::STATUS_PUBLISHED);

        $this->getContainer()->get('platformd.model.game_page_manager')
            ->saveGamePage($page)
        ;
    }

    /**
     * Used in the admin to count rows in a table
     *
     * @Then /^I should see (\d+) data rows$/
     */
    public function iShouldSeeDataRows($num)
    {
        $rows = $this->getPage()->findAll('css', 'table.table tbody tr');

        assertEquals($num, count($rows));
    }

    /**
     * Checks a checkbox in a "many" choice field.
     *
     * This is do, I believe, to some custom way we're rendering our labels
     * for a group of collection boxes.
     *
     * @Given /^I check the "([^"]*)" option for "([^"]*)"$/
     */
    public function iCheckTheOptionFor($optionName, $fieldLabelName)
    {
        $label = $this->getPage()->find('css', sprintf('label:contains("%s")', $fieldLabelName));
        if (!$label) {
            throw new \Exception('Cannot find label with text '.$fieldLabelName);
        }

        /** @var $optionEle \Behat\Mink\Element\NodeElement */
        $optionEle = $label->getParent()->findField($optionName);
        if (!$optionEle) {
            throw new \Exception(sprintf('Cannot find option named "%s" under "%s"', $optionName, $fieldLabelName));
        }

        $optionEle->check();
    }

    /**
     * Used to click on the frontend "show" URL when in an admin list section
     *
     * @Given /^I click on the URL for "([^"]*)"$/
     */
    public function iClickOnTheUrlFor($itemName)
    {
        $row = $this->getPage()->find('css', sprintf('table.table tbody tr:contains("%s")', $itemName));
        if (!$row) {
            throw new \Exception(sprintf('Could not find any data row matching an item "%s"', $itemName));
        }

        // now that we have the row, we need to find the public link
        // which, is probably just a link that starts with "http://"
        $aEle = $row->find('css', 'a[href*="http"]');
        if (!$aEle) {
            throw new \Exception('Cannot find the link!!!');
        }

        $aEle->click();
    }

    /**
     * Tries to match to the first h1
     *
     * @Then /^the headline should contain "([^"]*)"$/
     */
    public function theHeadlineShouldContain($headline)
    {
        $h1 = $this->getPage()->find('css', 'h1');
        assertRegExp('/'.preg_quote($headline).'/', $h1->getText());
    }

    /**
     * @Given /^I have the following games pages:$/
     */
    public function iHaveTheFollowingGamesPages(TableNode $table)
    {
        $em = $this->getEntityManager();

        foreach ($table->getHash() as $row) {
            $game = $this->thereIsAGameCalled($row['name']);
            $gamePage = new GamePage();
            $gamePage->setGame($game);

            $category = isset($row['category']) ? $row['category'] : 'rpg';
            $status = isset($row['status']) ? $row['status'] : GamePage::STATUS_PUBLISHED;
            $sites = isset($row['sites']) ? $row['sites'] : 'en';

            $game->setCategory($category);
            $gamePage->setStatus($status);
            $gamePage->setLocales(explode(',', $sites));

            $em->persist($game);
            $em->flush();

            $this->getGamePageManager()->saveGamePage($gamePage);
        }
    }

    /**
     * @Given /^I have verified my age$/
     */
    public function iHaveVerifiedMyAge()
    {
        $currentUrl = $this->getSession()->getCurrentUrl();

        if (strlen($currentUrl) == 0) {
            $currentUrl = '/games';
        }

        $ra[] = new When('I go to "/age/verify"');
        $ra[] = new When('I select "1984" from "birthday[year]"');
        $ra[] = new When('I select "6" from "birthday[month]"');
        $ra[] = new When('I select "5" from "birthday[day]"');
        $ra[] = new When('I press "Confirm"');
        $ra[] = new When('I go to "/games"');
        $ra[] = new Then('the headline should contain "GAMES AND TRAILERS"');

        if ($currentUrl != '/games')
        {
            $ra[] = new When(sprintf('I go to "%s"', $currentUrl));
        }

        return $ra;
    }

    /**
     * Used when looking at the games page
     *
     * @Then /^I should see (\d+) game(?:|s) under the "([^"]*)" category$/
     */
    public function iShouldSeeGamesUnderTheCategory($count, $category)
    {
        $h3Ele = $this->getPage()->find('css', sprintf('h3:contains("%s")', $category));
        if (!$h3Ele) {
            throw new \Exception('Cannot find a category named '.$category);
        }

        $liElements = $h3Ele->getParent()->findAll('css', 'ul.games li');

        assertEquals($count, count($liElements));
    }

    /**
     * @Given /^I shouldn\'t see any games under the "([^"]*)" category$/
     */
    public function iShouldNotSeeAnyGamesUnderTheCategory($category)
    {
        $h3Ele = $this->getPage()->find('css', sprintf('h3:contains("%s")', $category));
        if ($h3Ele) {
            throw new \Exception(sprintf('Found category for "%s", but should not have', $category));
        }
    }

    /**
     * @Then /^I should see (\d+) game in the archived list$/
     */
    public function iShouldSeeGameInTheArchivedList($count)
    {
        $liEles = $this->getPage()->findAll('css', '.games-list-page .right ul.games li');

        assertEquals($count, count($liEles));
    }

    /**
     * Changes the base URL to be a different site (is demo by default)
     *
     * @Given /^I am on the "([^"]*)" site$/
     */
    public function iAmOnTheACertainSite($siteName)
    {
        $this->currentSite = $this->getSiteKeyFromSiteName($siteName);
    }

    /**
     * @Given /^I should still be on the "([^"]*)" site$/
     */
    public function iShouldStillBeOnTheSite($siteName)
    {
        $key = $this->getSiteKeyFromSiteName($siteName);
        $baseUrl = $this->getBaseUrlFromSiteKey($key);

        assertRegExp('/'.preg_quote($baseUrl).'/', $this->getSession()->getCurrentUrl());
    }

    /**
     * Test that we're sent back to CEVO's site
     *
     * @Then /^I should be on the main site$/
     */
    public function iShouldBeOnTheMainSite()
    {
        $cevoUrl = $this->getContainer()->getParameter('cevo_base_url');
        assertRegExp('#'.preg_quote($cevoUrl).'#', $this->getSession()->getCurrentUrl(), 'The URL does not contain our faked "CEVO" path - we are not on the main CEVO site.');
    }

    /**
     * Overridden to handle the base URL for different sites
     */
    public function getParameter($name)
    {
        // if we're not on the "demo" site, then we need to modify the base URL
        if ($name == 'base_url' && $this->currentSite != 'en') {
            return 'http://'.$this->getBaseUrlFromSiteKey($this->currentSite);
        }

        return parent::getParameter($name);
    }

    /**
     * Given "en", this returns "demo.yourbasehost.com"
     *
     * @param $siteKey
     * @return mixed
     * @throws \Exception
     */
    private function getBaseUrlFromSiteKey($siteKey)
    {
        $urlMap = $this->getContainer()->getParameter('site_host_map');
        if (!isset($urlMap[$siteKey])) {
            throw new \Exception('Cannot find the proper base URL for site '.$siteKey);
        }

        return $urlMap[$siteKey];
    }

    /**
     * Given "Europe", this returns "en_GB"
     *
     * @param $siteName
     * @return mixed
     * @throws \Exception
     */
    private function getSiteKeyFromSiteName($siteName)
    {
        $sites = $this->getContainer()->getParameter('platformd_sites');
        $key = array_search($siteName, $sites);
        if ($key === false) {
            throw new \Exception('Cannot find site '.$siteName.'. Available sites: '.explode(',', $sites));
        }

        return $key;
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
     * @return \Platformd\SpoutletBundle\Model\GamePageManager
     */
    protected function getGamePageManager()
    {
        return $this->getContainer()->get('platformd.model.game_page_manager');
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
