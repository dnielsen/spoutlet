<?php

namespace Platformd\SpoutletBundle\Features\Context;

use Behat\BehatBundle\Context\MinkContext;
use Behat\Behat\Context\ClosuredContextInterface,
Behat\Behat\Context\TranslatedContextInterface,
Behat\Mink\Exception\ElementNotFoundException,
Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
Behat\Gherkin\Node\TableNode;

use Behat\Behat\Event\ScenarioEvent;
use Behat\Behat\Context\Step\Given;
use Behat\Behat\Context\Step\When;
use Behat\Behat\Context\Step\Then;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Behat\Mink\Driver\GoutteDriver;

use Platformd\GameBundle\Entity\Game;
use Platformd\GameBundle\Entity\GamePage;
use Platformd\SpoutletBundle\Entity\Contest;
use Platformd\SpoutletBundle\Entity\Gallery;
use Platformd\GiveawayBundle\Entity\Deal;
use Platformd\GroupBundle\Entity\Group;
use Platformd\GroupBundle\Entity\GroupApplication;
use Platformd\GroupBundle\Entity\GroupNews;
use Platformd\SpoutletBundle\Entity\Comment;
use Platformd\SpoutletBundle\Entity\Thread;
use Platformd\EventBundle\Entity\GroupEvent;
use Platformd\EventBundle\Entity\GlobalEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Platformd\SpoutletBundle\Entity\BackgroundAd;
use Platformd\SpoutletBundle\Entity\BackgroundAdSite;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Base Feature context.
 */
class AbstractFeatureContext extends MinkContext
{
    protected $currentUser;
    protected $currentSite = NULL;
    protected $entityManager;
    protected $cacheUtil;
    protected $purger;
    protected $dbConnection;
    protected $queueUtilMock;

    public function __construct(HttpKernelInterface $kernel) {
        parent::__construct($kernel);

        $this->entityManager = $this->getContainer()->get('doctrine')->getEntityManager();
        $this->currentSite   = $this->entityManager->getRepository('SpoutletBundle:Site')->findOneByName('Demo');
        $this->cacheUtil     = $this->getContainer()->get('platformd.util.cache_util');
        $this->purger        = new ORMPurger($this->entityManager);
        $this->dbConnection  = $this->entityManager->getConnection();
        $this->queueUtilMock = $this->getContainer()->get('platformd.util.queue_util');
    }

    /**
     * @BeforeScenario
     */
    public function emptyQueue() {
        $this->queueUtilMock->emptyWorkingFile();
    }

    /**
     * @BeforeScenario
     */
    public function purgeDatabase()
    {
        $this->purger->purge();
        $this->entityManager->flush();

        $dsn = 'mysql:dbname=;host='.$this->getContainer()->getParameter('database_host');
        $user = $this->getContainer()->getParameter('database_user');
        $password = $this->getContainer()->getParameter('database_password');
        $dbh = new \PDO($dsn, $user, $password);

        $dbh->prepare('DROP DATABASE `'.$this->getContainer()->getParameter('test_acl_database').'`;')->execute();
        $dbh->prepare('CREATE DATABASE `'.$this->getContainer()->getParameter('test_acl_database').'`;')->execute();

        exec($this->getContainer()->getParameter('kernel.root_dir').'/console init:acl --env=test');

        $this->dbConnection
            ->prepare("ALTER TABLE `pd_site` AUTO_INCREMENT = 1")
            ->execute();

        $this->dbConnection
            ->prepare("INSERT INTO `pd_site` (`name`, `defaultLocale`, `fullDomain`, `theme`) VALUES
            ('Demo', 'en', 'demo.alienwarearena.local', 'default'),
            ('Japan', 'ja', 'japan.alienwarearena.local', 'default'),
            ('China', 'zh', 'china.alienwarearena.local', 'default'),
            ('North America', 'en_US', 'na.alienwarearena.local', 'default'),
            ('Europe', 'en_GB', 'eu.alienwarearena.local', 'default'),
            ('Latin America', 'es', 'latam.alienwarearena.local', 'default'),
            ('India', 'en_IN', 'in.alienwarearena.local', 'default'),
            ('Singapore', 'en_SG', 'mysg.alienwarearena.local', 'default'),
            ('Australia / New Zealand', 'en_AU', 'anz.alienwarearena.local', 'default')")
            ->execute();

        $this->dbConnection
            ->prepare("INSERT INTO `pd_site_features` (`id`, `site_id`, `has_video`, `has_steam_xfire_communities`, `has_sweepstakes`,
                `has_forums`, `has_arp`, `has_news`, `has_deals`, `has_games`, `has_games_nav_drop_down`, `has_messages`, `has_groups`,
                `has_wallpapers`, `has_microsoft`, `has_photos`, `has_contests`, `has_comments`, `has_giveaways`, `has_events`, `has_html_widgets`,
                `has_facebook`, `has_google_analytics`, `has_tournaments`, `has_match_client`, `has_profile`, `has_forward_on_404`, `has_index`,
                `has_about`, `has_contact`) VALUES
            (1,1,1,1,0,1,1,1,1,1,0,1,1,1,1,1,1,1,0,1,0,1,1,1,1,1,0,1,1,1),
            (2,2,1,0,0,0,0,1,0,0,0,0,0,0,0,0,0,1,1,1,0,1,1,1,0,1,0,1,1,1),
            (3,3,1,0,0,0,0,1,0,0,0,0,0,1,1,0,0,0,0,0,0,1,1,1,0,1,0,1,1,1),
            (4,4,1,1,0,1,1,0,1,1,0,1,1,1,1,1,1,1,0,1,0,1,1,1,1,1,1,0,0,0),
            (5,5,1,1,0,1,1,0,1,1,0,1,1,1,1,1,1,1,0,1,0,1,1,1,1,1,1,0,0,0),
            (6,6,1,1,0,1,1,0,0,1,0,1,0,1,1,1,1,1,0,1,0,1,1,1,1,1,1,0,0,0),
            (7,7,1,1,0,1,1,0,0,1,0,1,0,1,1,0,0,0,0,1,0,1,1,1,1,1,1,0,0,0),
            (8,8,1,1,0,1,1,0,0,1,0,1,0,1,1,0,0,0,0,1,0,1,1,1,1,1,1,0,0,0),
            (9,9,1,1,0,1,1,0,0,1,0,1,0,1,1,0,0,0,0,1,0,1,1,1,1,1,1,0,0,0)")
            ->execute();

        $this->dbConnection
            ->prepare('INSERT INTO `pd_site_config` (`id`, `site_id`, `supportEmailAddress`, `automatedEmailAddress`, `emailFromName`, `birthdateRequired`, `forward_base_url`, `forwarded_paths`) VALUES
            (1,1,"demo@alienwarearena.local","demo@alienwarearena.local","Alienware Arena",1,"http://www.alienwarearena.com", null),
            (2,2,"japan@alienwarearena.local","japan@alienwarearena.local","Alienware Arena",1,"http://www.alienwarearena.com", null),
            (3,3,"china@alienwarearena.local","china@alienwarearena.local","Alienware Arena",1,"http://www.alienwarearena.com", null),
            (4,4,"na@alienwarearena.local","na@alienwarearena.local","Alienware Arena",1,"http://www.alienwarearena.com", \'a:5:{i:0;s:3:"^/$";i:1;s:5:"^/arp";i:2;s:8:"^/forums";i:3;s:9:"^/contact";i:4;s:7:"^/about";}\'),
            (5,5,"eu@alienwarearena.local","eu@alienwarearena.local","Alienware Arena",1,"http://www.alienwarearena.com", \'a:5:{i:0;s:3:"^/$";i:1;s:5:"^/arp";i:2;s:8:"^/forums";i:3;s:9:"^/contact";i:4;s:7:"^/about";}\'),
            (6,6,"latam@alienwarearena.local","latam@alienwarearena.local","Alienware Arena",1,"http://www.alienwarearena.com", \'a:5:{i:0;s:3:"^/$";i:1;s:5:"^/arp";i:2;s:8:"^/forums";i:3;s:9:"^/contact";i:4;s:7:"^/about";}\'),
            (7,7,"in@alienwarearena.local","in@alienwarearena.local","Alienware Arena",1,"http://www.alienwarearena.com", \'a:5:{i:0;s:3:"^/$";i:1;s:5:"^/arp";i:2;s:8:"^/forums";i:3;s:9:"^/contact";i:4;s:7:"^/about";}\'),
            (8,8,"mysg@alienwarearena.local","mysg@alienwarearena.local","Alienware Arena",1,"http://www.alienwarearena.com", \'a:5:{i:0;s:3:"^/$";i:1;s:5:"^/arp";i:2;s:8:"^/forums";i:3;s:9:"^/contact";i:4;s:7:"^/about";}\'),
            (9,9,"anz@alienwarearena.local","anz@alienwarearena.local","Alienware Arena",1,"http://www.alienwarearena.com", \'a:5:{i:0;s:3:"^/$";i:1;s:5:"^/arp";i:2;s:8:"^/forums";i:3;s:9:"^/contact";i:4;s:7:"^/about";}\')')
            ->execute();

        $this->dbConnection
            ->prepare("ALTER TABLE `pd_gallery_category` AUTO_INCREMENT = 1")
            ->execute();

        $this->dbConnection
            ->prepare("INSERT INTO `pd_gallery_category` (`id`, `name`) VALUES (2, 'video'), (1, 'image')")
            ->execute();

        $this->dbConnection
            ->prepare("ALTER TABLE `country` AUTO_INCREMENT = 1")
            ->execute();

        $this->dbConnection
            ->prepare("INSERT INTO `country` VALUES (1,'AF','Afghanistan'),(2,'AX','[DO NOT USE] Åland Islands'),(3,'AL','Albania'),(4,'DZ','Algeria'),(5,'AS','American Samoa'),(6,'AD','Andorra'),(7,'AO','Angola'),(8,'AI','Anguilla'),(9,'AQ','Antarctica'),(10,'AG','Antigua and Barbuda'),(11,'AR','Argentina'),(12,'AM','Armenia'),(13,'AW','Aruba'),(14,'AC','[DO NOT USE] Ascension Island'),(15,'AU','Australia'),(16,'AT','Austria'),(17,'AZ','Azerbaijan'),(18,'BS','Bahamas'),(19,'BH','Bahrain'),(20,'BD','Bangladesh'),(21,'BB','Barbados'),(22,'BY','Belarus'),(23,'BE','Belgium'),(24,'BZ','Belize'),(25,'BJ','Benin'),(26,'BM','Bermuda'),(27,'BT','Bhutan'),(28,'BO','Bolivia'),(29,'BA','Bosnia and Herzegovina'),(30,'BW','Botswana'),(31,'BV','Bouvet Island'),(32,'BR','Brazil'),(33,'IO','British Indian Ocean Territory'),(34,'VG','British Virgin Islands'),(35,'BN','Brunei'),(36,'BG','Bulgaria'),(37,'BF','Burkina Faso'),(38,'BI','Burundi'),(39,'KH','Cambodia'),(40,'CM','Cameroon'),(41,'CA','Canada'),(42,'IC','[DO NOT USE] Canary Islands'),(43,'CV','Cape Verde'),(44,'KY','Cayman Islands'),(45,'CF','Central African Republic'),(46,'EA','[DO NOT USE] Ceuta and Melilla'),(47,'TD','Chad'),(48,'CL','Chile'),(49,'CN','China'),(50,'CX','Christmas Island'),(51,'CP','[DO NOT USE] Clipperton Island'),(52,'CC','Cocos [Keeling] Islands'),(53,'CO','Colombia'),(54,'KM','Comoros'),(55,'CG','Congo - Brazzaville'),(56,'CD','Congo - Kinshasa'),(57,'CK','Cook Islands'),(58,'CR','Costa Rica'),(59,'CI','Côte d’Ivoire'),(60,'HR','Croatia'),(61,'CU','Cuba'),(62,'CY','Cyprus'),(63,'CZ','Czech Republic'),(64,'DK','Denmark'),(65,'DG','[DO NOT USE] Diego Garcia'),(66,'DJ','Djibouti'),(67,'DM','Dominica'),(68,'DO','Dominican Republic'),(69,'EC','Ecuador'),(70,'EG','Egypt'),(71,'SV','El Salvador'),(72,'GQ','Equatorial Guinea'),(73,'ER','Eritrea'),(74,'EE','Estonia'),(75,'ET','Ethiopia'),(76,'EU','[DO NOT USE] European Union'),(77,'FK','Falkland Islands'),(78,'FO','Faroe Islands'),(79,'FJ','Fiji'),(80,'FI','Finland'),(81,'FR','France'),(82,'GF','French Guiana'),(83,'PF','French Polynesia'),(84,'TF','French Southern Territories'),(85,'GA','Gabon'),(86,'GM','Gambia'),(87,'GE','Georgia'),(88,'DE','Germany'),(89,'GH','Ghana'),(90,'GI','Gibraltar'),(91,'GR','Greece'),(92,'GL','Greenland'),(93,'GD','Grenada'),(94,'GP','Guadeloupe'),(95,'GU','Guam'),(96,'GT','Guatemala'),(97,'GG','[DO NOT USE] Guernsey'),(98,'GN','Guinea'),(99,'GW','Guinea-Bissau'),(100,'GY','Guyana'),(101,'HT','Haiti'),(102,'HM','Heard Island and McDonald Islands'),(103,'HN','Honduras'),(104,'HK','Hong Kong SAR China'),(105,'HU','Hungary'),(106,'IS','Iceland'),(107,'IN','India'),(108,'ID','Indonesia'),(109,'IR','Iran'),(110,'IQ','Iraq'),(111,'IE','Ireland'),(112,'IM','[DO NOT USE] Isle of Man'),(113,'IL','Israel'),(114,'IT','Italy'),(115,'JM','Jamaica'),(116,'JP','Japan'),(117,'JE','[DO NOT USE] Jersey'),(118,'JO','Jordan'),(119,'KZ','Kazakhstan'),(120,'KE','Kenya'),(121,'KI','Kiribati'),(122,'KW','Kuwait'),
                (123,'KG','Kyrgyzstan'),(124,'LA','Laos'),(125,'LV','Latvia'),(126,'LB','Lebanon'),(127,'LS','Lesotho'),(128,'LR','Liberia'),(129,'LY','Libya'),(130,'LI','Liechtenstein'),(131,'LT','Lithuania'),(132,'LU','Luxembourg'),(133,'MO','Macau SAR China'),(134,'MK','Macedonia'),(135,'MG','Madagascar'),(136,'MW','Malawi'),(137,'MY','Malaysia'),(138,'MV','Maldives'),(139,'ML','Mali'),(140,'MT','Malta'),(141,'MH','Marshall Islands'),(142,'MQ','Martinique'),(143,'MR','Mauritania'),(144,'MU','Mauritius'),(145,'YT','Mayotte'),(146,'MX','Mexico'),(147,'FM','Micronesia'),(148,'MD','Moldova'),(149,'MC','Monaco'),(150,'MN','Mongolia'),(151,'ME','Montenegro'),(152,'MS','Montserrat'),(153,'MA','Morocco'),(154,'MZ','Mozambique'),(155,'MM','Myanmar [Burma]'),(156,'NA','Namibia'),(157,'NR','Nauru'),(158,'NP','Nepal'),(159,'NL','Netherlands'),(160,'AN','Netherlands Antilles'),(161,'NC','New Caledonia'),(162,'NZ','New Zealand'),(163,'NI','Nicaragua'),(164,'NE','Niger'),(165,'NG','Nigeria'),(166,'NU','Niue'),(167,'NF','Norfolk Island'),(168,'KP','North Korea'),(169,'MP','Northern Mariana Islands'),(170,'NO','Norway'),(171,'OM','Oman'),(172,'QO','[DO NOT USE] Outlying Oceania'),(173,'PK','Pakistan'),(174,'PW','Palau'),(175,'PS','Palestinian Territories'),(176,'PA','Panama'),(177,'PG','Papua New Guinea'),(178,'PY','Paraguay'),(179,'PE','Peru'),(180,'PH','Philippines'),(181,'PN','Pitcairn Islands'),(182,'PL','Poland'),(183,'PT','Portugal'),(184,'PR','Puerto Rico'),(185,'QA','Qatar'),(186,'RE','Réunion'),(187,'RO','Romania'),(188,'RU','Russia'),(189,'RW','Rwanda'),(190,'BL','[DO NOT USE] Saint Barthélemy'),(191,'SH','Saint Helena'),(192,'KN','Saint Kitts and Nevis'),(193,'LC','Saint Lucia'),(194,'MF','[DO NOT USE] Saint Martin'),(195,'PM','Saint Pierre and Miquelon'),(196,'VC','Saint Vincent and the Grenadines'),(197,'WS','Samoa'),(198,'SM','San Marino'),(199,'ST','São Tomé and Príncipe'),(200,'SA','Saudi Arabia'),(201,'SN','Senegal'),(202,'RS','Serbia'),(203,'CS','[DO NOT USE] Serbia and Montenegro'),(204,'SC','Seychelles'),(205,'SL','Sierra Leone'),(206,'SG','Singapore'),(207,'SK','Slovakia'),(208,'SI','Slovenia'),(209,'SB','Solomon Islands'),(210,'SO','Somalia'),(211,'ZA','South Africa'),(212,'GS','South Georgia and the South Sandwich Islands'),(213,'KR','South Korea'),(214,'ES','Spain'),(215,'LK','Sri Lanka'),(216,'SD','Sudan'),(217,'SR','Suriname'),(218,'SJ','Svalbard and Jan Mayen'),(219,'SZ','Swaziland'),(220,'SE','Sweden'),(221,'CH','Switzerland'),(222,'SY','Syria'),(223,'TW','Taiwan'),(224,'TJ','Tajikistan'),(225,'TZ','Tanzania'),(226,'TH','Thailand'),(227,'TL','[DO NOT USE] Timor-Leste'),(228,'TG','Togo'),(229,'TK','Tokelau'),(230,'TO','Tonga'),(231,'TT','Trinidad and Tobago'),(232,'TA','[DO NOT USE] Tristan da Cunha'),(233,'TN','Tunisia'),(234,'TR','Turkey'),(235,'TM','Turkmenistan'),(236,'TC','Turks and Caicos Islands'),(237,'TV','Tuvalu'),(238,'UM','U.S. Minor Outlying Islands'),(239,'VI','U.S. Virgin Islands'),(240,'UG','Uganda'),
                (241,'UA','Ukraine'),(242,'AE','United Arab Emirates'),(243,'GB','United Kingdom'),(244,'US','United States'),(245,'UY','Uruguay'),(246,'UZ','Uzbekistan'),(247,'VU','Vanuatu'),(248,'VA','Vatican City'),(249,'VE','Venezuela'),(250,'VN','Vietnam'),(251,'WF','Wallis and Futuna'),(252,'EH','Western Sahara'),(253,'YE','Yemen'),(254,'ZM','Zambia'),(255,'ZW','Zimbabwe');")
                ->execute();

        $this->dbConnection
            ->prepare('INSERT INTO `region` (`id`,`site_id`,`name`, `is_metrics_region`) VALUES (2,"2","Japan",0), (3,"3","China",0),
                (4,"4","North America",0), (5,"5","Europe",0), (6,"6","Latin America",0), (7,"7","India",0), (8,"8","Singapore",0), (9,"9","Australia / New Zealand",0),
                (10,null,"APJ",1), (11,null,"WE",1), (12,null,"CE",1), (13,null,"ANZ",1), (14,null,"SA",1), (15,null,"EMEA",1), (16,null,"Priority EMEA",1), (17,null,"NA",1),
                (18,null,"LATAM",1), (19,null,"Priority LATAM",1), (20,null,"Non Priority LATAM",1), (21,null,"Other",1)')
            ->execute();

        $this->dbConnection
            ->prepare('INSERT IGNORE INTO `region_country` (`region_id`, `country_id`) VALUES
                (2, 116),
                (3, 49),
                (4, 41), (4, 244), (4, 195), (4, 191),
                (5, 3), (5, 12), (5, 16), (5, 17), (5, 22), (5, 23), (5, 29), (5, 36), (5, 60), (5, 62), (5, 63), (5, 64), (5, 74), (5, 80), (5, 81), (5, 87), (5, 88), (5, 91), (5, 105), (5, 106), (5, 111), (5, 114), (5, 119), (5, 123), (5, 125), (5, 131), (5, 132), (5, 134), (5, 148), (5, 202), (5, 159), (5, 170), (5, 182), (5, 183), (5, 187), (5, 188), (5, 207), (5, 208), (5, 214), (5, 220), (5, 221), (5, 224), (5, 235), (5, 241), (5, 243), (5, 246), (5, 6), (5, 242), (5, 1), (5, 160), (5, 7), (5, 9), (5, 5), (5, 37), (5, 19), (5, 38), (5, 25), (5, 31), (5, 30), (5, 55), (5, 45), (5, 59), (5, 40), (5, 43), (5, 66), (5, 4), (5, 70), (5, 252), (5, 73), (5, 75), (5, 78), (5, 85), (5, 97), (5, 89), (5, 90), (5, 92), (5, 98), (5, 99), (5, 102), (5, 113), (5, 112), (5, 33), (5, 110), (5, 109), (5, 117), (5, 118), (5, 120), (5, 121), (5, 122), (5, 126), (5, 130), (5, 215), (5, 128), (5, 127), (5, 129), (5, 153), (5, 149), (5, 135), (5, 139), (5, 143), (5, 140), (5, 144), (5, 138), (5, 136), (5, 154), (5, 156), (5, 164), (5, 165), (5, 171), (5, 173), (5, 185), (5, 186), (5, 189), (5, 200), (5, 204), (5, 216), (5, 218), (5, 205), (5, 198), (5, 201), (5, 210), (5, 199), (5, 222), (5, 219), (5, 47), (5, 84), (5, 228), (5, 233), (5, 230), (5, 234), (5, 225), (5, 240), (5, 248), (5, 253), (5, 145), (5, 211), (5, 254), (5, 255),
                (6, 10), (6, 8), (6, 11), (6, 13), (6, 21), (6, 26), (6, 28), (6, 18), (6, 24), (6, 146), (6, 48), (6, 53), (6, 58), (6, 67), (6, 68), (6, 69), (6, 93), (6, 96), (6, 100), (6, 103), (6, 101), (6, 115), (6, 192), (6, 44), (6, 193), (6, 163), (6, 176), (6, 179), (6, 184), (6, 178), (6, 217), (6, 71), (6, 231), (6, 237), (6, 236), (6, 245), (6, 196), (6, 249), (6, 34), (6, 239), (6, 32), (6, 57), (6, 61), (6, 77), (6, 82), (6, 94), (6, 212), (6, 142), (6, 152), (6, 238),
                (7, 107),
                (8, 108), (8, 213), (8, 137), (8, 206),
                (9, 15), (9, 162),
                (10,15), (10,49), (10,107), (10,108), (10,116), (10,137), (10,162), (10,206), (10,213),
                (11, 16), (11, 23), (11, 64), (11, 80), (11, 111), (11, 114), (11, 132), (11, 159), (11, 170), (11, 183), (11, 214), (11, 220), (11, 221),
                (12, 3), (12, 12), (12, 17), (12, 22), (12, 29), (12, 36), (12, 60), (12, 62), (12, 63), (12, 74), (12, 87), (12, 91), (12, 105), (12, 106), (12, 119), (12, 123), (12, 125), (12, 131), (12, 134), (12, 148), (12, 151), (12, 182), (12, 187), (12, 188), (12, 202), (12, 207), (12, 208), (12, 224), (12, 235), (12, 241), (12, 246),
                (13,15), (13,162),
                (14, 107), (14, 137), (14, 206), (14, 213),
                (15, 16), (15, 23), (15, 64), (15, 80), (15, 111), (15, 114), (15, 132), (15, 159), (15, 170), (15, 183), (15, 214), (15, 220), (15, 221), (15, 3), (15, 12), (15, 17), (15, 22), (15, 29), (15, 36), (15, 60), (15, 62), (15, 63), (15, 74), (15, 87), (15, 91), (15, 105), (15, 106), (15, 119), (15, 123), (15, 125), (15, 131), (15, 134), (15, 148), (15, 151), (15, 182), (15, 187), (15, 188), (15, 202), (15, 207), (15, 208), (15, 224), (15, 235), (15, 241), (15, 246), (15, 81), (15, 88), (15, 243),
                (16, 81), (16, 88), (16, 243),
                (17, 41), (17, 244),
                (18, 11), (18, 48), (18, 53), (18, 146), (18, 179), (18, 8), (18, 10), (18, 13), (18, 18), (18, 21), (18, 24), (18, 26), (18, 28), (18, 34), (18, 44), (18, 58), (18, 67), (18, 68), (18, 69), (18, 71), (18, 93), (18, 96), (18, 100), (18, 101), (18, 103), (18, 115), (18, 163), (18, 176), (18, 178), (18, 184), (18, 192), (18, 193), (18, 196), (18, 217), (18, 231), (18, 236), (18, 237), (18, 239), (18, 245), (18, 249),
                (19, 11), (19, 48), (19, 53), (19, 146), (19, 179),
                (20, 8), (20, 10), (20, 13), (20, 18), (20, 21), (20, 24), (20, 26), (20, 28), (20, 34), (20, 44), (20, 58), (20, 67), (20, 68), (20, 69), (20, 71), (20, 93), (20, 96), (20, 100), (20, 101), (20, 103), (20, 115), (20, 163), (20, 176), (20, 178), (20, 184), (20, 192), (20, 193), (20, 196), (20, 217), (20, 231), (20, 236), (20, 237), (20, 239), (20, 245), (20, 249),
                (21, 1), (21, 2), (21, 4), (21, 5), (21, 6), (21, 7), (21, 9), (21, 14), (21, 19), (21, 20), (21, 25), (21, 27), (21, 30), (21, 31), (21, 32), (21, 33), (21, 35), (21, 37), (21, 38), (21, 39), (21, 40), (21, 42), (21, 43), (21, 45), (21, 46), (21, 47), (21, 50), (21, 51), (21, 52), (21, 54), (21, 55), (21, 56), (21, 57), (21, 59), (21, 61), (21, 65), (21, 66), (21, 70), (21, 72), (21, 73), (21, 75), (21, 76), (21, 77), (21, 78), (21, 79), (21, 82), (21, 83), (21, 84), (21, 85), (21, 86), (21, 89), (21, 90), (21, 92), (21, 94), (21, 95), (21, 97), (21, 98), (21, 99), (21, 102), (21, 104), (21, 109), (21, 110), (21, 112), (21, 113), (21, 117), (21, 118), (21, 120), (21, 121), (21, 122), (21, 124), (21, 126), (21, 127), (21, 128), (21, 129), (21, 130), (21, 133), (21, 135), (21, 136), (21, 138), (21, 139), (21, 140), (21, 141), (21, 142), (21, 143), (21, 144), (21, 145), (21, 147), (21, 149), (21, 150), (21, 152), (21, 153), (21, 154), (21, 155), (21, 156), (21, 157), (21, 158), (21, 160), (21, 161), (21, 164), (21, 165), (21, 166), (21, 167), (21, 168), (21, 169), (21, 171), (21, 172), (21, 173), (21, 174), (21, 175), (21, 177), (21, 180), (21, 181), (21, 185), (21, 186), (21, 189), (21, 190), (21, 191), (21, 194), (21, 195), (21, 197), (21, 198), (21, 199), (21, 200), (21, 201), (21, 203), (21, 204), (21, 205), (21, 209), (21, 210), (21, 211), (21, 212), (21, 215), (21, 216), (21, 218), (21, 219), (21, 222), (21, 223), (21, 225), (21, 226), (21, 227), (21, 228), (21, 229), (21, 230), (21, 232), (21, 233), (21, 234), (21, 238), (21, 240), (21, 242), (21, 247), (21, 248), (21, 250), (21, 251), (21, 252), (21, 253), (21, 254), (21, 255)
                ')
            ->execute();
    }

    /**
     * @Given /^I am located in "([^"]*)"$/
     */
    public function iAmLocatedIn($countryCode)
    {
        $file = $this->getContainer()->getParameter('ip2location_lookup_directory').'overrideCountry';
        file_put_contents($file, trim($countryCode));
    }

    /**
     * @Given /^the response is JSON$/
     */
    public function theResponseIsJson()
    {
        $data = json_decode($this->response);

        if (empty($data)) {
            throw new Exception("Response was not JSON\n" . $this->response);
        }
    }

    /**
     * @Given /^I select the "([^"]*)" radio button$/
     */
    public function iSelectTheRadioButton($label) {

        $radio_button = $this->getPage()->find('css', sprintf('label:contains("%s")', $label));

          if (null === $radio_button) {
            throw new ElementNotFoundException(
              $this->getSession(), 'form field', 'id|name|label|value', $label
            );
          }

          $this->fillField($label, 1);
    }

    /**
     * @Given /^I have the following group news articles:$/
     */
    public function iHaveTheFollowingGroupNewsArticles(TableNode $table)
    {

        $em         = $this->getEntityManager();
        $um         = $this->getUserManager();
        $groupRepo  = $em->getRepository('GroupBundle:Group');

        foreach ($table->getHash() as $data) {

            $group = $groupRepo->findOneByName($data['group']);

            $news = new GroupNews();

            $news->setTitle($data['title']);
            $news->setArticle($data['article']);
            $news->setAuthor($um->findUserByUsername($data['author']));
            $news->setGroup($group);

            $em->persist($news);
        }

        $em->flush();
    }

    /**
     * @Given /^the JSON contains "([^"]*)" equal to "([^"]*)"$/
     */
    public function theJsonContainsEqualTo($jsonName, $jsonValue)
    {
        $data = json_decode($this->response);

        if (count($data) < 1) {
            throw new Exception("Response did not contain any data");
        }

        $first = $data[0];
        $value = $first[$jsonName];

        if ($value != $jsonValue) {
            throw new Exception(sprintf("Response did not contain '%s' data", $jsonName));
        }
    }

    /**
     * @Given /^I am authenticated$/
     */
    public function iAmAuthenticated()
    {
        if (!$this->currentUser) {
            $this->IHaveAnAccount();
        }

        return array(
            new When('I go to "/logout"'),
            new When('I go to "/login"'),
            new When('I fill in "login-username" with "'.$this->currentUser->getUsername().'"'),
            new When('I fill in "login-password" with "'.$this->currentUser->getUsername().'"'),
            new When('I press "_submit"'),
        );
    }

    public function NavigateTo($namedRoute, $parameters=array(), $absolute = false)
    {
        $url = $this->getContainer()->get('router')->generate($namedRoute, $parameters, $absolute);
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
     * @Given /^I am authenticated as an admin$/
     */
    public function iAmAuthenticatedAsAnAdmin()
    {
        // guarantee there is a user, because we wouldn't normally say it beforehand...
        if (!$this->currentUser) {
            $this->IHaveAnAccount();
        }

        // enforce the right role
        $this->currentUser->setRoles(array(
            'ROLE_SUPER_ADMIN',
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

        return $aTags[0]->getText();
    }

    private function isNavHeading($item) {

        if (!$item) {
            return false;
        }

        $classes = $item->getAttribute('class');

        return strpos($classes, 'more') !== false;
    }

    public function canIntercept()
    {
        $driver = $this->getSession()->getDriver();
        if (!$driver instanceof GoutteDriver) {
            throw new UnsupportedDriverActionException(
                'You need to tag the scenario with '.
                '"@mink:goutte" or "@mink:symfony". '.
                'Intercepting the redirections is not '.
                'supported by %s', $driver
            );
        }
    }

    /**
     * @Given /^(.*) without redirection$/
     */
    public function theRedirectionsAreIntercepted($step)
    {
        $this->canIntercept();
        $this->getSession()->getDriver()->getClient()->followRedirects(false);

        return new Step\Given($step);
    }

    /**
     * @When /^I follow the redirection$/
     * @Then /^I should be redirected$/
     */
    public function iFollowTheRedirection()
    {
        $this->canIntercept();
        $client = $this->getSession()->getDriver()->getClient();
        $client->followRedirects(true);
        $client->followRedirect();
    }

    private function isExternalUrl($url) {
        return strpos($url, 'http://www.alienware') === 0 ||
                strpos($url, 'http://alienware') === 0 ||
                strpos($url, 'http://www1.euro') === 0 ||
                strpos($url, 'http://allpowerful.com') === 0;
    }

    private function ensureNavItemsMatch($actual, $expected, $counter) {

        $expectedText           = $expected['Link'];
        $expectedDestination    = $expected['Target'];
        $expectedFinal          = trim($expected['Destination']) == "" ? $expectedDestination : $expected['Destination'];
        $compareWithRedirects   = array_key_exists("CompareWithRedirects", $expected) ? $expected['CompareWithRedirects'] == "yes" : false;

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

        if ($actualDestination && $expectedFinal && ($this->isExternalUrl($actualDestination))) {
            #echo "Didn't check $actualDestination\n";
            echo ".";
            return;
        }

        if ($actualDestination && strpos($actualDestination, 'http') === false) {
            $actualDestination = 'http://'.$this->currentSite->getFullDomain().$actualDestination;
        }

        $session = $this->getSession();
        $lastUrl = $session->getCurrentUrl();

        if ($compareWithRedirects) {

            #echo "comparing with redirects...\n";

            $goutte = $session->getDriver()->getClient();
            $goutte->followRedirects(false);

            $session->visit($actualDestination);

            $currentUrl = "";

            while (true) {

                $lastStep = $currentUrl;
                $currentUrl = $session->getCurrentUrl();

                if ($currentUrl == $lastStep) { # reached the end of a redirection trail
                    #echo "REACHED END with $currentUrl\n";
                    break;
                }

                if ($this->isExternalUrl($currentUrl) && $currentUrl == $expectedFinal) { # if the link is external and we matched with the expected, just stop, its not our system to troubleshoot past this
                    #echo "EXTERNAL MATCHES, dont need to continue $currentUrl\n";
                    break;
                }

                #echo "following, current = '".$session->getCurrentUrl()."'.\n";

                try {
                    $goutte->followRedirect();
                } catch (\LogicException $e) {
                    throw new \Exception(sprintf('Navigation menu item mismatch. [CompareWithRedirects] The expected ultimate destination was "%s" but was navigated to "%s" on item number "%d". Link text was "%s".', $expectedFinal, $currentUrl, $counter + 1, $actualText));
                }
            }

            $goutte->followRedirects(true);
        } else {
            $session->visit($actualDestination);
        }

        $currentUrl = $session->getCurrentUrl();

        if ($currentUrl != $expectedFinal) {
            throw new \Exception(sprintf('Navigation menu item mismatch.  The expected ultimate destination was "%s" but was navigated to "%s" on item number "%d". Link text was "%s".', $expectedFinal, $currentUrl, $counter + 1, $actualText));
        }

        $session->visit($lastUrl);

        #echo "** Did check $actualDestination\n";
        echo ".";
    }

    private function getNewGoutteSession() {
        $zendOptions    = array();
        $serverOptions  = array();
        $client         = new \Goutte\Client($zendOptions, $serverOptions);
        $driver         = new \Behat\Mink\Driver\GoutteDriver($client);
        $newSession     = new \Behat\Mink\Session($driver);

        $client->followRedirects(true);

        return $newSession;
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
        $user->setCountry('US');
        $user->setEnabled(true);
        $user->setFirstname('User');
        $user->setLastname('User');
        $user->setBirthdate(new \DateTime('1980-01-01'));
        $user->setHasAlienwareSystem(false);
        $user->setCevoUserId(55);

        $um->updateUser($user);

        $this->currentUser = $user;
    }

    /**
     * @Given /^I have the "([^"]*)" role$/
     */
    public function iHaveTheRole($role)
    {
        if (!$this->currentUser) {
            $this->IHaveAnAccount();
        }

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

            if (isset($data['username'])) {
                $user->setUsername($data['username']);
                $user->setPlainPassword($data['username']);
                $user->setFirstname($data['username']);
                $user->setLastname($data['username']);
            }

            if (isset($data['email'])) {
                $user->setEmail($data['email']);
            } else {
                $user->setEmail('user@example.com');
            }

            if (isset($data['cevo id'])) {
                $user->setCevoUserId($data['cevo id']);
            }

            if (isset($data['cevo country'])) {
                $user->setCountry($data['cevo country']);
            }

            $user->setBirthdate(new \DateTime('1980-01-01'));
            $user->setHasAlienwareSystem(false);
            $user->setEnabled(true);

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
        if ($game = $this->getEntityManager()->getRepository('GameBundle:Game')->findOneBy(array('name' => $name))) {
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

        $em = $this->getEntityManager();

        $site = $em->getRepository('SpoutletBundle:Site')->findOneBy(array('defaultLocale' => $siteName));

        $page = new GamePage();
        $page->setGame($game);
        $page->setSites(array($site));
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
        $rows = $this->getPage()->findAll('css', 'table tbody tr');
        assertEquals($num, count($rows));
    }

    /**
     * @Then /^I should see (\d+) "([^"]*)" data rows$/
     */
    public function iShouldSeeClassedDataRows($num, $class)
    {
        $rows = $this->getPage()->findAll('css', 'table tbody tr.'.$class);
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
        $em   = $this->getEntityManager();
        $game = $em->getRepository('GameBundle:Game')->findOneBy(array('name' => $gameName));

        if (!$game) {
            throw new \Exception('Could not find game in the database');
        }

        $site     = $em->getRepository('SpoutletBundle:Site')->findOneByDefaultLocale($siteName);
        $gamePage = $em->getRepository('GameBundle:GamePage')->findOneByGame($game, $site);

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
        $site = $em->getRepository('SpoutletBundle:Site')->findOneByDefaultLocale($locale);

        if (!$site) {
            throw new \Exception(sprintf('Site not found for locale "%s"', $locale));
        }

        $deal   = $em->getRepository('GiveawayBundle:Deal')->findOneByNameForSite($dealName, $site);

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
     * @Then /^I should be on the contest called "([^"]*)" in "([^"]*)"$/
     */
    public function iShouldBeOnTheContestCalledIn($contestName, $locale)
    {
        $em     = $this->getEntityManager();
        $site = $em->getRepository('SpoutletBundle:Site')->findOneByDefaultLocale($locale);

        if (!$site) {
            throw new \Exception(sprintf('Site not found for locale "%s"', $locale));
        }

        $contest   = $em->getRepository('SpoutletBundle:Contest')->findOneByName($contestName);

        if (!$contest) {
            throw new \Exception('Could not find the contest in the database');
        }

        if (!$contest->getSites()->contains($site)){
            throw new \Exception('Contest is not enabled on this site');
        }

        $session    = $this->getSession();
        $currentUrl = $session->getCurrentUrl();
        $slug       = $contest->getSlug();

        if (strpos($currentUrl, $slug) === false) {
            throw new \Exception(sprintf('Not currently on the Contest.  Expected URL was "%s" but currently on "%s"', $slug, $currentUrl));
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

            $game->setCategory($category);
            $gamePage->setStatus($status);

            $siteArray = isset($row['sites']) ? explode(',', $row['sites']) : array('en');
            $siteRepo = $em->getRepository('SpoutletBundle:Site');

            foreach ($siteArray as $site) {
                $gamePage->getSites()->add($siteRepo->findOneByDefaultLocale($site));
            }

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
        $onAgeVerifyPageAlready = strpos($currentUrl, 'age/verify/return') !== false;

        if ($onAgeVerifyPageAlready) {
            $currentUrl = null;
        }

        $ra[] = new When('I go to "/age/verify/return"');
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
        $em = $this->getEntityManager();
        $siteRepo = $em->getRepository('SpoutletBundle:Site');
        $this->currentSite = $siteRepo->findOneByName($siteName);
    }

    /**
     * @Given /^I should still be on the "([^"]*)" site$/
     */
    public function iShouldStillBeOnTheSite($siteName)
    {
        $em = $this->getEntityManager();
        $siteRepo = $em->getRepository('SpoutletBundle:Site');

        $this->currentSite = $siteRepo->findOneByName($siteName);

        $baseUrl = $this->currentSite->getFullDomain();

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
        $em = $this->getEntityManager();

        if ($deal = $em->getRepository('GiveawayBundle:Deal')->findOneBy(array('name' => $dealName))) {
            $em->remove($deal);
            $em->flush();
        }

        $site = $em->getRepository('SpoutletBundle:Site')->findOneBy(array('defaultLocale' => $locale));

        $deal = new Deal();
        $deal->setName($dealName);
        $deal->setSites(array($site));
        $deal->setStatus(Deal::STATUS_PUBLISHED);
        $deal->setRedemptionInstructionsArray(array('Do something'));

        $this->getContainer()->get('platformd.model.deal_manager')
            ->saveDeal($deal);

        return $deal;
    }

    /**
     * @Given /^there is a gallery called "([^"]*)" in "([^"]*)"$/
     */
    public function thereIsAGalleryCalledIn($galleryName, $locale)
    {
        $em = $this->getEntityManager();

        if ($gallery = $em->getRepository('SpoutletBundle:Gallery')->findOneBy(array('name' => $galleryName))) {
            $em->remove($gallery);
            $em->flush();
        }

        $site = $em->getRepository('SpoutletBundle:Site')->findOneBy(array('defaultLocale' => $locale));
        $category = $em->getRepository('SpoutletBundle:GalleryCategory')->findOneBy(array('name' => 'image'));

        $gallery = new Gallery();
        $gallery->setName($galleryName);
        $gallery->setSites(array($site));
        $gallery->setDeleted(false);
        $gallery->setCategories(array($category));

        $em->persist($gallery);
        $em->flush();

        return $gallery;
    }

    /**
     * @Given /^I have the following contests:$/
     */
    public function iHaveTheFollowingContests(TableNode $table)
    {
        $em = $this->getEntityManager();

        $counter = 0;

        foreach ($table->getHash() as $data) {

            if (isset($data['name'])) {
                if ($contest = $em->getRepository('SpoutletBundle:Contest')->findOneBy(array('name' => $data['name']))) {
                    $em->remove($contest);
                    $em->flush();
                }

                $contest = new Contest();
                $contest->setName($data['name']);
            } else {
                $contest = new Contest();
                $contest->setName('Default Contest'.$counter);
            }

            $contest->setSlug(isset($data['slug']) ? $data['slug'] : "default-contest-".$counter);

            if (isset($data['site'])) {
                $site = $em->getRepository('SpoutletBundle:Site')->findOneBy(array('defaultLocale' => $data['site']));
                $contest->setSites(array($site));
            } else {
                $site = $em->getRepository('SpoutletBundle:Site')->findOneBy(array('defaultLocale' => "en"));
                $contest->setSites(array($site));
            }

            $contest->setSubmissionStart(   isset($data['submission_start'])    ? new \DateTime($data['submission_start'])  : new \DateTime('-2 days'));
            $contest->setSubmissionEnd(     isset($data['submission_end'])      ? new \DateTime($data['submission_end'])    : new \DateTime('+2 days'));
            $contest->setVotingStart(       isset($data['voting_start'])        ? new \DateTime($data['voting_start'])      : new \DateTime('-2 days'));
            $contest->setVotingEnd(         isset($data['voting_end'])          ? new \DateTime($data['voting_end'])        : new \DateTime('+2 days'));
            $contest->setCategory(          isset($data['category'])            ? $data['category']                         : "image");
            $contest->setMaxEntries(        isset($data['max_entries'])         ? $data['max_entries']                      : 0);

            if (isset($data['status']) && $data['status'] == Contest::STATUS_PUBLISHED) {
                $contest->setStatus(Contest::STATUS_PUBLISHED);
            } else {
                $contest->setStatus(Contest::STATUS_UNPUBLISHED);
            }

            $contest->setRules('Rules');
            $contest->setEntryInstructions('Entry Instructions');
            $contest->setVoteInstructions('Vote Instructions');
            $contest->setRedemptionInstructionsArray(array('Do something'));

            $em->persist($contest);
            $em->flush();

            $counter++;
        }
    }

    /**
     * @Given /^I have the following groups:$/
     */
    public function iHaveTheFollowingGroups(TableNode $table)
    {
        $em = $this->getEntityManager();

        $counter = 0;

        foreach ($table->getHash() as $data) {

            if (isset($data['name'])) {
                if ($group = $em->getRepository('GroupBundle:Group')->findOneBy(array('name' => $data['name']))) {
                    $em->remove($group);
                    $em->flush();
                }

                $group = new Group();
                $group->setName($data['name']);
            } else {
                $group = new Group();
                $group->setName('Default Group '.$counter);
            }

            $group->setSlug(isset($data['slug']) ? $data['slug'] : "default-group-".$counter);

            if (isset($data['site'])) {
                $site = $em->getRepository('SpoutletBundle:Site')->findOneBy(array('defaultLocale' => $data['site']));
                $group->setSites(array($site));
            } else {
                $site = $em->getRepository('SpoutletBundle:Site')->findOneBy(array('defaultLocale' => "en"));
                $group->setSites(array($site));
            }

            $group->setCategory(    isset($data['category'])    ? $data['category']             : "topic");
            $group->setDescription( isset($data['description']) ? $data['description']          : "default description");
            $group->setIsPublic(    isset($data['public'])      ? $data['public'] == "yes"      : 1);
            $group->setFeatured(    isset($data['featured'])    ? $data['featured'] == "yes"    : 0);
            $group->setFeaturedAt(  isset($data['featured'])    ? new \DateTime('now') : null);

            $group->setCreatedAt(new \DateTime('now'));
            $group->setUpdatedAt(new \DateTime('now'));

            $owner = isset($data['owner']) ? $em->getRepository('UserBundle:User')->findOneBy(array('username' => $data['owner'])) : null;
            $group->setOwner($owner ?: $this->getCurrentUser());

            $this->getContainer()->get('platformd.model.group_manager')
            ->saveGroup($group);

            $counter++;
        }
    }

     /**
     * @Given /^the group "([^"]*)" has an outstanding application from "([^"]*)"$/
     */
    public function theGroupHasOutstandingApplication($groupName, $username)
    {
        $em = $this->getEntityManager();
        $group = $em->getRepository('GroupBundle:Group')->findOneBy(array('name' => $groupName));

        if ($group) {

            if (!$applicant = $this->getUserManager()->findUserByUsername($username)) {
                $applicant = $this->getUserManager()->createUser();
                $applicant->setUsername($username);
                $applicant->setPassword("password");
                $applicant->setEmail("email@email.com");
                $applicant->setCevoUserId(123);
                $applicant->setCountry("US");
                $this->getUserManager()->updateUser($applicant);
            }

            $app = new GroupApplication();

            $site = $this->getCurrentSite();

            $app->setCreatedAt(new \DateTime('now'));
            $app->setUpdatedAt(new \DateTime('now'));
            $app->setApplicant($applicant);
            $app->setGroup($group);
            $app->setSite($site);
            $app->setReason('Test application');

            $em->persist($app);
            $em->flush();
        } else {
            throw new \Exception('Cannot find the group called '.$groupName);
        }
    }

     /**
     * @Given /^I add "([^"]*)" for group "([^"]*)"$/
     */
    public function iAddForGroup($mediaType, $groupName)
    {
        $em = $this->getEntityManager();
        $group = $em->getRepository('GroupBundle:Group')->findOneBy(array('name' => $groupName));

        if ($group) {
            switch ($mediaType) {
                case 'news':
                    $url = $this->getContainer()->get('router')->generate('group_add_news', array('slug' => $group->getSlug()));
                    $this->getSession()->visit($url);
                    break;

                case 'video':
                    $url = $this->getContainer()->get('router')->generate('youtube_submit', array('groupId' => $group->getId()));
                    $this->getSession()->visit($url);
                    break;

                case 'discussion':
                    $url = $this->getContainer()->get('router')->generate('group_add_discussion', array('slug' => $group->getSlug()));
                    $this->getSession()->visit($url);
                    break;

                case 'event':
                    $url = $this->getContainer()->get('router')->generate('group_event_new', array('groupSlug' => $group->getSlug()));
                    $this->getSession()->visit($url);
                    break;

                default:
                    throw new \Exception('Unknown media type: '.$mediaType);
                    break;
            }
        } else {
            throw new \Exception('Cannot find the group called '.$groupName);
        }
    }

    /**
     * @Given /^I ([^"]*) an application to "([^"]*)"$/
     */
    public function iProcessAnApplicationTo($action, $groupName)
    {
        $em = $this->getEntityManager();
        $group = $em->getRepository('GroupBundle:Group')->findOneBy(array('name' => $groupName));

        if ($group){

            $applications = $em->getRepository('GroupBundle:GroupApplication')->findBy(array('group' => $group->getId()));

            if ($applications) {
                $application = $applications[0];
            }

           $url = $action == "accept"
                ? $this->getContainer()->get('router')->generate('group_accept_application', array('slug' => $group->getSlug(), 'applicationId' => $application->getId()))
                : $url = $this->getContainer()->get('router')->generate('group_reject_application', array('slug' => $group->getSlug(), 'applicationId' => $application->getId()));

            $this->getSession()->visit($url);

        } else {
            throw new \Exception('Cannot find the group called '.$groupName);
        }
    }

    /**
     * @Given /^I go to the "([^"]*)" page of "([^"]*)"$/
     */
    public function iGoToThePageOf($pageType, $groupName)
    {
        $route = "group_".$pageType;

        $em = $this->getEntityManager();
        $group = $em->getRepository('GroupBundle:Group')->findOneBy(array('name' => $groupName));

        if ($group){

            $url = $this->getContainer()->get('router')->generate($route, array('slug' => $group->getSlug()));
            return array(
                new When('I go to "'.$url.'"'),
            );
        } else {
            throw new \Exception('Cannot find the group called '.$groupName);
        }
    }


    /**
     * @Given /^I should see "([^"]*)" on the "([^"]*)" page of "([^"]*)"$/
     */
    public function iShouldSeeOnThePageOf($string, $pageType, $groupName)
    {
        $route = "group_".$pageType;

        $em = $this->getEntityManager();
        $group = $em->getRepository('GroupBundle:Group')->findOneBy(array('name' => $groupName));

        if ($group){

            $url = $this->getContainer()->get('router')->generate($route, array('slug' => $group->getSlug()));
            return array(
                new When('I go to "'.$url.'"'),
                new Then('I should see "'.$string.'"'),
            );
        } else {
            throw new \Exception('Cannot find the group called '.$groupName);
        }
    }

     /**
     * @Given /^"([^"]*)" has the following members:$/
     */
    public function hasTheFollowingMembers($groupName, TableNode $table)
    {
        $em = $this->getEntityManager();
        $group = $em->getRepository('GroupBundle:Group')->findOneBy(array('name' => $groupName));

        if ($group) {

            foreach ($table->getHash() as $data) {

                if (!$member = $this->getUserManager()->findUserByUsername($data['username'])) {
                    $member = $this->getUserManager()->createUser();
                    $member->setUsername($data['username']);
                    $member->setPassword("password");
                    $member->setEmail("email@email.com");
                    $member->setCevoUserId(123);
                    $member->setCountry("US");
                    $this->getUserManager()->updateUser($member);
                }

                $member->getPdgroups()->add($group);
                $em->persist($member);
                $em->flush();
            }
        } else {
            throw new \Exception('Cannot find the group called '.$groupName);
        }
    }

    /**
     * @Then /^the "([^"]*)" count should be (\d+)$/
     */
    public function theCountShouldBe($string, $count)
    {
        $countLabel = $this->getPage()->find('css', sprintf('td:contains("%s")', $string));
        if (!$countLabel) {
            throw new \Exception('Cannot find a label named '.$string);
        }

        $rowElements = $countLabel->getParent()->findAll('css', 'td');

        assertEquals($count, end($rowElements)->getText());
    }

    /**
     * @Given /^group "([^"]*)" has the following comments:$/
     */
    public function hasTheFollowingComments($groupName, TableNode $table)
    {
        $em = $this->getEntityManager();
        $group = $em->getRepository('GroupBundle:Group')->findOneBy(array('name' => $groupName));

        if ($group) {
            $thread     = $em->getRepository('SpoutletBundle:Thread')->find($group->getThreadId());
            if (!$thread) {
                $thread = new Thread();
                $thread->setId($group->getThreadId());
                $thread->setPermalink($this->getSession()->getCurrentUrl().'#comments');

                $em->persist($thread);
                $em->flush();
            }

            foreach ($table->getHash() as $data) {

                $user = $this->getUserManager()->findUserByUsername($data['username']);

                $comment = new Comment();
                $comment->setThread($thread);
                $comment->setAuthor($user ?: $this->getCurrentUser());
                $comment->setBody(isset($data['comment']) ? $data['comment'] : "Default comment");
                $comment->setCreatedAt(new \DateTime('now'));

                $em->persist($comment);

                $thread->incrementCommentCount();
            }

            $em->flush();

        } else {
            throw new \Exception('Cannot find the group called '.$groupName);

        }
    }

    /**
     * @Given /^I should see "([^"]*)" on "([^"]*)"$/
     */
    public function iShouldSeeOn($string, $url)
    {
        return array(
            new When('I go to "'.$url.'"'),
            new Then('I should see "'.$string.'"'),
        );
    }


    /**
     * Used in the admin to count rows in a table sith the specified id
     *
     * @Then /^I should see (\d+) data rows in "([^"]*)"$/
     */
    public function iShouldSeeDataRowsIn($num, $id)
    {
        $rows = $this->getPage()->findAll('css', 'table#'.$id.' tbody tr');

        assertEquals($num, count($rows));
    }

    /**
     * Overridden to handle the base URL for different sites
     */
    public function getParameter($name)
    {
        // if we're not on the "demo" site, then we need to modify the base URL
        if ($name == 'base_url' && $this->currentSite && $this->currentSite->getName() != "Demo") {
            return 'http://'.$this->currentSite->getFullDomain();
        }

        return parent::getParameter($name);
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
        return $this->entityManager;
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

    /**
     * @Given /^I have the following events:$/
     */
    public function iHaveTheFollowingEvents(TableNode $table)
    {
        $em = $this->getEntityManager();

        $counter = 0;

        foreach ($table->getHash() as $data) {

            $group = isset($data['group']) ? $em->getRepository('GroupBundle:Group')->findOneByName($data['group']) : null;
            $event = $group ? new GroupEvent($group) : new GlobalEvent();

            $event->setName(isset($data['name']) ? $data['name'] : 'Test Event '.$counter);
            $event->setSlug(isset($data['slug']) ? $data['slug'] : "test-event-".$counter);

            if (isset($data['site'])) {
                $site = $em->getRepository('SpoutletBundle:Site')->findOneByDefaultLocale($data['site']);
                $event->getSites()->clear();
                $event->getSites()->add($site);
            }

            $event->setStartsAt(    isset($data['start'])       ? new \DateTime($data['start']) : new \DateTime('-2 days'));
            $event->setEndsAt(      isset($data['end'])         ? new \DateTime($data['end'])   : new \DateTime('+2 days'));
            $event->setContent(     isset($data['description']) ? $data['description']          : "default description");
            $event->setPublished(   isset($data['published'])   ? $data['published']            : 1);
            $event->setApproved(    isset($data['approved'])    ? $data['approved']             : 1);
            $event->setActive(      isset($data['active'])      ? $data['active']               : 1);
            $event->setOnline(      isset($data['online'])      ? $data['online']               : 1);

            $event->setRegistrationOption(GlobalEvent::REGISTRATION_ENABLED);

            if ($group) {
                $event->setPrivate(isset($data['private']) ? $data['private'] : 0);
            }

            $owner = isset($data['owner']) ? $em->getRepository('UserBundle:User')->findOneBy(array('username' => $data['owner'])) : null;
            $event->setUser($owner ?: $this->getCurrentUser());

            $this->getContainer()->get('platformd_event.service.global_event')->createEvent($event);

            $counter++;
        }
    }

    /**
     * @Then /^I should be on the "([^"]*)" event called "([^"]*)" on "([^"]*)"$/
     */
    public function iShouldBeOnTheEventCalledIn($type, $eventName, $locale)
    {
        $em     = $this->getEntityManager();
        $site = $em->getRepository('SpoutletBundle:Site')->findOneByDefaultLocale($locale);

        if (!$site) {
            throw new \Exception(sprintf('Site not found for locale "%s"', $locale));
        }

        $entity = $type == "group" ? 'GroupEvent' : 'GlobalEvent';

        $event   = $em->getRepository('EventBundle:'.$entity)->findOneByName($eventName);

        if (!$event) {
            throw new \Exception('Could not find the event in the database');
        }

        if (!$event->getSites()->contains($site)){
            throw new \Exception('Event is not enabled on this site');
        }

        $session    = $this->getSession();
        $currentUrl = $session->getCurrentUrl();
        $slug       = $event->getSlug();

        if (strpos($currentUrl, $slug) === false) {
            throw new \Exception(sprintf('Not currently on the Event.  Expected URL was "%s" but currently on "%s"', $slug, $currentUrl));
        }

        $statusCode = $session->getStatusCode();
        $httpOk = 200;

        if ($statusCode != $httpOk) {
            throw new \Exception(sprintf('Currently on the correct URL, but the HTTP Status Code was non-OK.  Expected code "200" actual code was "%d"', $slug, $currentUrl));
        }
    }

    /**
     * @Given /^I attach a background ad image$/
     */
    public function iAttachABackgroundAdImage()
    {
        $this->attachFileToField('Image', __DIR__.'/image.png');
    }

    /**
     * @Given /^I fill in background ad's date with "([^""]*)"$/
     */
    public function iFillInDateWith($date)
    {
        $dt = new \DateTime($date);

        $this->fillField('admin_background_ad_date_date_month', $dt->format('m'));
        $this->fillField('admin_background_ad_date_date_day', $dt->format('d'));
        $this->fillField('admin_background_ad_date_date_year', $dt->format('Y'));
    }

    /**
     * @Given /^there is an already existing background ad at date "([^""]*)" - "([^""]*)" for site "([^""]*)"(?: with url "([^""]*)")?$/
     */
    public function thereIsAnAlreadyExistingBackgroundAdAtDate($dateStart, $dateEnd, $siteName, $url = null)
    {
        $ad = new BackgroundAd('test');
        $ad->setDateStart(new \DateTime($dateStart));
        $ad->setDateEnd(new \DateTime($dateEnd));
        $ad->setTimezone('Europe/Paris');

        $em   = $this->getEntityManager();
        $site = $em->getRepository('SpoutletBundle:Site')->findOneBy(array('name' => $siteName));
        $ad->addAdSite(new BackgroundAdSite($site, $url));

        $this->getEntityManager()->persist($ad);
        $this->getEntityManager()->flush();
    }

    /**
     * @When /I click background/
     */
    public function iClickBackground()
    {
        $this->getSession()->getPage()->find('css', '.background-takeover')->click();
    }

    /**
     * @Given /^I have the following galleries:$/
     */
    public function iHaveTheFollowingGalleries(TableNode $table)
    {
        $em = $this->getEntityManager();

        $counter = 0;

        foreach ($table->getHash() as $data) {
            if ($gallery = $em->getRepository('SpoutletBundle:Gallery')->findOneBy(array('name' => $data['name']))) {
                $em->remove($gallery);
                $em->flush();
            }

            $site = $em->getRepository('SpoutletBundle:Site')->findOneBy(array('defaultLocale' => 'en'));
            $category = $em->getRepository('SpoutletBundle:GalleryCategory')->findOneBy(array('name' => 'video'));

            $gallery = new Gallery();
            $gallery->setName($data['name']);
            $gallery->setSlug('default-gallery-slug-'.$counter);
            $gallery->setSites(array($site));
            $gallery->setDeleted(false);
            $gallery->setCategories(array($category));

            $sitesPositions = $gallery->getSitesPositions();

            foreach ($gallery->getSites() as $site) {
                $sitesPositions[$site->getId()] = 0;
            }

            $gallery->setSitesPositions($sitesPositions);

            $em->persist($gallery);
            $em->flush();

            $counter++;
        }
    }
}
