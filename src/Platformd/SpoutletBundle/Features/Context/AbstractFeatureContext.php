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
use Platformd\SpoutletBundle\Entity\Deal;

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
        $em = $this->getEntityManager();

        $purger = new ORMPurger($em);
        $purger->purge();

        $em->flush();

        $con = $em->getConnection();

        $con
            ->prepare("ALTER TABLE `country` AUTO_INCREMENT = 1")
            ->execute();

        $con
            ->prepare("INSERT INTO `country` VALUES (1,'AF','Afghanistan'),(2,'AX','[DO NOT USE] Åland Islands'),(3,'AL','Albania'),(4,'DZ','Algeria'),(5,'AS','American Samoa'),(6,'AD','Andorra'),(7,'AO','Angola'),(8,'AI','Anguilla'),(9,'AQ','Antarctica'),(10,'AG','Antigua and Barbuda'),(11,'AR','Argentina'),(12,'AM','Armenia'),(13,'AW','Aruba'),(14,'AC','[DO NOT USE] Ascension Island'),(15,'AU','Australia'),(16,'AT','Austria'),(17,'AZ','Azerbaijan'),(18,'BS','Bahamas'),(19,'BH','Bahrain'),(20,'BD','Bangladesh'),(21,'BB','Barbados'),(22,'BY','Belarus'),(23,'BE','Belgium'),(24,'BZ','Belize'),(25,'BJ','Benin'),(26,'BM','Bermuda'),(27,'BT','Bhutan'),(28,'BO','Bolivia'),(29,'BA','Bosnia and Herzegovina'),(30,'BW','Botswana'),(31,'BV','Bouvet Island'),(32,'BR','Brazil'),(33,'IO','British Indian Ocean Territory'),(34,'VG','British Virgin Islands'),(35,'BN','Brunei'),(36,'BG','Bulgaria'),(37,'BF','Burkina Faso'),(38,'BI','Burundi'),(39,'KH','Cambodia'),(40,'CM','Cameroon'),(41,'CA','Canada'),(42,'IC','[DO NOT USE] Canary Islands'),(43,'CV','Cape Verde'),(44,'KY','Cayman Islands'),(45,'CF','Central African Republic'),(46,'EA','[DO NOT USE] Ceuta and Melilla'),(47,'TD','Chad'),(48,'CL','Chile'),(49,'CN','China'),(50,'CX','Christmas Island'),(51,'CP','[DO NOT USE] Clipperton Island'),(52,'CC','Cocos [Keeling] Islands'),(53,'CO','Colombia'),(54,'KM','Comoros'),(55,'CG','Congo - Brazzaville'),(56,'CD','Congo - Kinshasa'),(57,'CK','Cook Islands'),(58,'CR','Costa Rica'),(59,'CI','Côte d’Ivoire'),(60,'HR','Croatia'),(61,'CU','Cuba'),(62,'CY','Cyprus'),(63,'CZ','Czech Republic'),(64,'DK','Denmark'),(65,'DG','[DO NOT USE] Diego Garcia'),(66,'DJ','Djibouti'),(67,'DM','Dominica'),(68,'DO','Dominican Republic'),(69,'EC','Ecuador'),(70,'EG','Egypt'),(71,'SV','El Salvador'),(72,'GQ','Equatorial Guinea'),(73,'ER','Eritrea'),(74,'EE','Estonia'),(75,'ET','Ethiopia'),(76,'EU','[DO NOT USE] European Union'),(77,'FK','Falkland Islands'),(78,'FO','Faroe Islands'),(79,'FJ','Fiji'),(80,'FI','Finland'),(81,'FR','France'),(82,'GF','French Guiana'),(83,'PF','French Polynesia'),(84,'TF','French Southern Territories'),(85,'GA','Gabon'),(86,'GM','Gambia'),(87,'GE','Georgia'),(88,'DE','Germany'),(89,'GH','Ghana'),(90,'GI','Gibraltar'),(91,'GR','Greece'),(92,'GL','Greenland'),(93,'GD','Grenada'),(94,'GP','Guadeloupe'),(95,'GU','Guam'),(96,'GT','Guatemala'),(97,'GG','[DO NOT USE] Guernsey'),(98,'GN','Guinea'),(99,'GW','Guinea-Bissau'),(100,'GY','Guyana'),(101,'HT','Haiti'),(102,'HM','Heard Island and McDonald Islands'),(103,'HN','Honduras'),(104,'HK','Hong Kong SAR China'),(105,'HU','Hungary'),(106,'IS','Iceland'),(107,'IN','India'),(108,'ID','Indonesia'),(109,'IR','Iran'),(110,'IQ','Iraq'),(111,'IE','Ireland'),(112,'IM','[DO NOT USE] Isle of Man'),(113,'IL','Israel'),(114,'IT','Italy'),(115,'JM','Jamaica'),(116,'JP','Japan'),(117,'JE','[DO NOT USE] Jersey'),(118,'JO','Jordan'),(119,'KZ','Kazakhstan'),(120,'KE','Kenya'),(121,'KI','Kiribati'),(122,'KW','Kuwait'),(123,'KG','Kyrgyzstan'),(124,'LA','Laos'),(125,'LV','Latvia'),(126,'LB','Lebanon'),(127,'LS','Lesotho'),(128,'LR','Liberia'),(129,'LY','Libya'),(130,'LI','Liechtenstein'),(131,'LT','Lithuania'),(132,'LU','Luxembourg'),(133,'MO','Macau SAR China'),(134,'MK','Macedonia'),(135,'MG','Madagascar'),(136,'MW','Malawi'),(137,'MY','Malaysia'),(138,'MV','Maldives'),(139,'ML','Mali'),(140,'MT','Malta'),(141,'MH','Marshall Islands'),(142,'MQ','Martinique'),(143,'MR','Mauritania'),(144,'MU','Mauritius'),(145,'YT','Mayotte'),(146,'MX','Mexico'),(147,'FM','Micronesia'),(148,'MD','Moldova'),(149,'MC','Monaco'),(150,'MN','Mongolia'),(151,'ME','Montenegro'),(152,'MS','Montserrat'),(153,'MA','Morocco'),(154,'MZ','Mozambique'),(155,'MM','Myanmar [Burma]'),(156,'NA','Namibia'),(157,'NR','Nauru'),(158,'NP','Nepal'),(159,'NL','Netherlands'),(160,'AN','Netherlands Antilles'),(161,'NC','New Caledonia'),(162,'NZ','New Zealand'),(163,'NI','Nicaragua'),(164,'NE','Niger'),(165,'NG','Nigeria'),(166,'NU','Niue'),(167,'NF','Norfolk Island'),(168,'KP','North Korea'),(169,'MP','Northern Mariana Islands'),(170,'NO','Norway'),(171,'OM','Oman'),(172,'QO','[DO NOT USE] Outlying Oceania'),(173,'PK','Pakistan'),(174,'PW','Palau'),(175,'PS','Palestinian Territories'),(176,'PA','Panama'),(177,'PG','Papua New Guinea'),(178,'PY','Paraguay'),(179,'PE','Peru'),(180,'PH','Philippines'),(181,'PN','Pitcairn Islands'),(182,'PL','Poland'),(183,'PT','Portugal'),(184,'PR','Puerto Rico'),(185,'QA','Qatar'),(186,'RE','Réunion'),(187,'RO','Romania'),(188,'RU','Russia'),(189,'RW','Rwanda'),(190,'BL','[DO NOT USE] Saint Barthélemy'),(191,'SH','Saint Helena'),(192,'KN','Saint Kitts and Nevis'),(193,'LC','Saint Lucia'),(194,'MF','[DO NOT USE] Saint Martin'),(195,'PM','Saint Pierre and Miquelon'),(196,'VC','Saint Vincent and the Grenadines'),(197,'WS','Samoa'),(198,'SM','San Marino'),(199,'ST','São Tomé and Príncipe'),(200,'SA','Saudi Arabia'),(201,'SN','Senegal'),(202,'RS','Serbia'),(203,'CS','[DO NOT USE] Serbia and Montenegro'),(204,'SC','Seychelles'),(205,'SL','Sierra Leone'),(206,'SG','Singapore'),(207,'SK','Slovakia'),(208,'SI','Slovenia'),(209,'SB','Solomon Islands'),(210,'SO','Somalia'),(211,'ZA','South Africa'),(212,'GS','South Georgia and the South Sandwich Islands'),(213,'KR','South Korea'),(214,'ES','Spain'),(215,'LK','Sri Lanka'),(216,'SD','Sudan'),(217,'SR','Suriname'),(218,'SJ','Svalbard and Jan Mayen'),(219,'SZ','Swaziland'),(220,'SE','Sweden'),(221,'CH','Switzerland'),(222,'SY','Syria'),(223,'TW','Taiwan'),(224,'TJ','Tajikistan'),(225,'TZ','Tanzania'),(226,'TH','Thailand'),(227,'TL','[DO NOT USE] Timor-Leste'),(228,'TG','Togo'),(229,'TK','Tokelau'),(230,'TO','Tonga'),(231,'TT','Trinidad and Tobago'),(232,'TA','[DO NOT USE] Tristan da Cunha'),(233,'TN','Tunisia'),(234,'TR','Turkey'),(235,'TM','Turkmenistan'),(236,'TC','Turks and Caicos Islands'),(237,'TV','Tuvalu'),(238,'UM','U.S. Minor Outlying Islands'),(239,'VI','U.S. Virgin Islands'),(240,'UG','Uganda'),(241,'UA','Ukraine'),(242,'AE','United Arab Emirates'),(243,'UK','United Kingdom'),(244,'US','United States'),(245,'UY','Uruguay'),(246,'UZ','Uzbekistan'),(247,'VU','Vanuatu'),(248,'VA','Vatican City'),(249,'VE','Venezuela'),(250,'VN','Vietnam'),(251,'WF','Wallis and Futuna'),(252,'EH','Western Sahara'),(253,'YE','Yemen'),(254,'ZM','Zambia'),(255,'ZW','Zimbabwe');")
            ->execute();
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

    private function getMainNav() {

        $mainNav = $this->getPage()->find('css', '#nav-wrapper > ul');

        if (!$mainNav) {
            throw new \Exception('Cannot find the main navigation menu.');
        }

        return $mainNav;
    }

    private function getUserNav() {

        $userNav = $this->getPage()->find('css', '#accountHeader > ul');

        if (!$userNav) {
            throw new \Exception('Cannot find the user navigation menu.');
        }

        return $userNav;
    }

    private function getNavItems($navItems) {

        $items = $navItems->findAll('css', 'li');

        for ($i=0; $i < count($items); $i++) {
            if ($items[$i]->getAttribute('class') === 'last') {
                unset($items[$i]);
            }
        }

        return $items;
    }

    private function isNavSubItem($item) {

        if (!$item) {
            return false;
        }

        $parentClasses = $item
            ->getParent()
            ->getParent()
            ->getAttribute('class');

        return strpos($parentClasses, 'more') !== false;
    }

    private function getNavSubItemHeading($item) {

        if (!$item) {
            return false;
        }

        $aTags = $item
            ->getParent()
            ->getParent()
            ->findAll('css', 'a');

        return $aTags[0]->getHtml();
    }

    private function isNavHeading($item) {

        if (!$item) {
            return false;
        }

        $classes = $item->getAttribute('class');

        return strpos($classes, 'more') !== false;
    }

    private function ensureNavItemsMatch($actual, $expected, $counter) {

        $expectedText           = $expected['Link'];
        $expectedDestination    = $expected['Target'];

        if (!$actual) {
            throw new \Exception(sprintf('Navigation menu item missing.  Expected link text "%s" but there are no more navigations links was found for item number "%d".', $expectedText, $counter + 1));
        }

        $searchLink = $actual->find('css', 'a');

        if (!$searchLink) {
            throw new \Exception(sprintf('Navigation menu item missing.  Expected link text "%s" but there are no more navigations links was found for item number "%d".', $expectedText, $counter + 1));
        }

        $actualDestination = $searchLink->getAttribute('href');

        if ($this->isNavSubItem($actual)) {
            $actualText = $this->getNavSubItemHeading($actual).' >> '.$actual->getText();
        } else {
            $actualText = $actual->getText();
        }

        if ($actualText !== $expectedText) {
            throw new \Exception(sprintf('Navigation menu item mismatch.  Expected link text "%s" but got "%s" on item number "%d".', $expectedText, $actualText, $counter + 1));
        }

        if ($actualDestination !== $expectedDestination) {
            throw new \Exception(sprintf('Navigation menu item mismatch.  Expected link destination "%s" but got "%s" on item number "%d". Link text was "%s".', $expectedDestination, $actualDestination, $counter + 1, $actualText));
        }
    }

     /**
     * @Given /^I re-login as the user "([^"]*)"$/
     */
    public function iReLoginAsTheUser($username)
    {
        $this->getSession()->reset();

        return $this->iAmLoggedInAsTheUser($username);
    }

    /**
     * @Given /^I am logged in as the user "([^"]*)"$/
     */
    public function iAmLoggedInAsTheUser($username) {

        $um = $this->getUserManager();
        $user = $um->findUserByUsername($username);

        if ($user == null) {
            throw new \Exception(sprintf('Could not find user with the username "%s".', $username));
        }

        $this->currentUser = $user;

        // enforce the right role
        $this->currentUser->setRoles(array(
            'ROLE_USER',
        ));

        $um->updateUser($this->currentUser);

        return $this->iAmAuthenticated();
    }

    /**
     * @Given /^I am authenticated as a user$/
     */
    public function iAmAuthenticatedAsAUser()
    {
        // guarantee there is a user, because we wouldn't normally say it beforehand...
        if (!$this->currentUser) {
            $this->IHaveAnAccount();
        }

        // enforce the right role
        $this->currentUser->setRoles(array(
            'ROLE_USER',
        ));
        $this->getUserManager()->updateUser($this->currentUser);

        return $this->iAmAuthenticated();
    }

    private function ensureNavigationItemsAreCorrect($navMenu, $expectedNavItems) {

        $navItems = $this->getNavItems($navMenu);

        $counter = 0;

        foreach ($expectedNavItems as $expected) {

            if (!isset($navItems[$counter])) {
                throw new \Exception(sprintf('Navigation menu item missing.  Expected link text "%s" but there are no more navigations links was found for item number "%d".', $expected['Link'], $counter + 1));
            }

            if ($this->isNavHeading($navItems[$counter])) {
                $counter++; // increment past the navigation heading
            }

            $this->ensureNavItemsMatch($navItems[$counter], $expected, $counter);

            $counter++;
        }

        if (isset($navItems[$counter])) {
            throw new \Exception(sprintf('Extra navigation menu items detected. Was not expecting any more navigation items, but found "%s".', $navItems[$counter]->getText()));
        }
    }

     /**
     * @Then /^the main navigation menu should be:$/
     */
    public function theMainNavigationMenuShouldBe(TableNode $table) {
        $this->ensureNavigationItemsAreCorrect($this->getMainNav(), $table->getHash());
    }

    /**
     * @Then /^the user navigation menu should be:$/
     */
    public function theUserNavigationMenuShouldBe(TableNode $table)
    {
        $this->ensureNavigationItemsAreCorrect($this->getUserNav(), $table->getHash());
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

            if (isset($data['cevo id'])) {
                $user->setCevoUserId($data['cevo id']);
            }

            if (isset($data['cevo country'])) {
                $user->setCountry($data['cevo country']);
            }

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
            ->saveGamePage($page);
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
     * @Then /^I should be on the game page for "([^"]*)" in "([^"]*)"$/
     */
    public function iShouldBeOnTheGamePageFor($gameName, $siteName)
    {
        $em     = $this->getEntityManager();
        $game   = $em->getRepository('SpoutletBundle:Game')->findOneBy(array('name' => $gameName));

        if (!$game) {
            throw new \Exception('Could not find game in the database');
        }

        $gamePage = $em->getRepository('SpoutletBundle:GamePage')->findOneByGame($game, $siteName);

        if (!$gamePage) {
            throw new \Exception('Could not find the game page for this game in the database');
        }

        $session    = $this->getSession();
        $currentUrl = $session->getCurrentUrl();
        $slug       = $gamePage->getSlug();

        if (strpos($currentUrl, $slug) === false) {
            throw new \Exception(sprintf('Not currently on the game\'s Game Page.  Expected URL was "%s" but currently on "%s"', $slug, $currentUrl));
        }

        $statusCode = $session->getStatusCode();
        $httpOk = 200;

        if ($statusCode != $httpOk) {
            throw new \Exception(sprintf('Currently on the correct URL, but the HTTP Status Code was non-OK.  Expected code "200" actual code was "%d"', $slug, $currentUrl));
        }
    }

    /**
     * @Then /^I should be on the deal called "([^"]*)" in "([^"]*)"$/
     */
    public function iShouldBeOnTheDealCalledIn($dealName, $locale)
    {
        $em     = $this->getEntityManager();
        /** @var $deal \Platformd\SpoutletBundle\Entity\Deal */
        $deal   = $em->getRepository('SpoutletBundle:Deal')->findOneByNameForSite($dealName, $locale);

        if (!$deal) {
            throw new \Exception('Could not find the deal in the database');
        }

        $session    = $this->getSession();
        $currentUrl = $session->getCurrentUrl();
        $slug       = $deal->getSlug();

        if (strpos($currentUrl, $slug) === false) {
            throw new \Exception(sprintf('Not currently on the Deal.  Expected URL was "%s" but currently on "%s"', $slug, $currentUrl));
        }

        $statusCode = $session->getStatusCode();
        $httpOk = 200;

        if ($statusCode != $httpOk) {
            throw new \Exception(sprintf('Currently on the correct URL, but the HTTP Status Code was non-OK.  Expected code "200" actual code was "%d"', $slug, $currentUrl));
        }
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

        if (!$h1) {
            throw new \Exception(sprintf('Title was not found on the page. Looking for "%s" on page "%s".', $headline, $this->getSession()->getCurrentUrl()));
        }

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
     * @Given /^I (?:|have )verif(?:ied|y) my age$/
     */
    public function iHaveVerifiedMyAge()
    {
        $currentUrl             = $this->getSession()->getCurrentUrl();
        $onAgeVerifyPageAlready = strpos($currentUrl, 'age/verify') !== false;

        if ($onAgeVerifyPageAlready) {
            $currentUrl = null;
        }

        $ra[] = new When('I go to "/age/verify"');
        $ra[] = new When('I select "1984" from "birthday[year]"');
        $ra[] = new When('I select "6" from "birthday[month]"');
        $ra[] = new When('I select "5" from "birthday[day]"');
        $ra[] = new When('I press "Confirm"');
        $ra[] = new When('I go to "/games"');
        $ra[] = new Then('I should not see "Content Intended for Mature Audiences"');
        $ra[] = new Then('I should see "GAMES AND TRAILERS"');

        if ($currentUrl)
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
     * @Given /^there is a deal called "([^"]*)" in "([^"]*)"$/
     */
    public function thereIsADealCalledIn($dealName, $locale)
    {
        if ($deal = $this->getEntityManager()->getRepository('SpoutletBundle:Deal')->findOneBy(array('name' => $dealName))) {
            $this->getEntityManager()->remove($deal);
            $this->getEntityManager()->flush();
        }

        $deal = new Deal();
        $deal->setName($dealName);
        $deal->setLocales(array($locale));
        $deal->setStatus(Deal::STATUS_PUBLISHED);
        $deal->setRedemptionInstructionsArray(array('Do something'));

        $this->getContainer()->get('platformd.model.deal_manager')
            ->saveDeal($deal);

        return $deal;
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
