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

        $this->dbConnection
            ->prepare("INSERT INTO `country_state` (`id`,`country_id`,`name`) VALUES (1,6,'Canillo'),(2,6,'Encamp'),(3,6,'La Massana'),(4,6,'Ordino'),(5,6,'Sant Julia De Loria'),(6,6,'Andorra La Vella'),(7,6,'Escaldes-engordany'),(8,242,'Abu Dhabi'),(9,242,'Ajman'),(10,242,'Dubai'),(11,242,'Fujairah'),(12,242,'Ras Al Khaimah'),(13,242,'Sharjah'),(14,242,'Umm Al Quwain'),(15,1,'Badakhshan'),(16,1,'Badghis'),(17,1,'Baghlan'),(18,1,'Bamian'),(19,1,'Farah'),(20,1,'Faryab'),(21,1,'Ghazni'),(22,1,'Ghowr'),(23,1,'Helmand'),(24,1,'Herat'),(25,1,'Kabol'),(26,1,'Kapisa'),(27,1,'Lowgar'),(28,1,'Nangarhar'),(29,1,'Nimruz'),(30,1,'Kandahar'),(31,1,'Kondoz'),(32,1,'Takhar'),(33,1,'Vardak'),(34,1,'Zabol'),(35,1,'Paktika'),(36,1,'Balkh'),(37,1,'Jowzjan'),(38,1,'Samangan'),(39,1,'Sar-e Pol'),(40,1,'Konar'),(41,1,'Laghman'),(42,1,'Paktia'),(43,1,'Khowst'),(44,1,'Nurestan'),(45,1,'Oruzgan'),(46,1,'Parvan'),(47,1,'Daykondi'),(48,1,'Panjshir'),(49,10,'Antigua And Barbuda'),(50,10,'Saint John'),(51,10,'Saint Mary'),(52,10,'Saint Paul'),(53,8,'Anguilla'),(54,3,'Berat'),(55,3,'Diber'),(56,3,'Durres'),(57,3,'Elbasan'),(58,3,'Fier'),(59,3,'Gjirokaster'),(60,3,'Korce'),(61,3,'Kukes'),(62,3,'Lezhe'),(63,3,'Shkoder'),(64,3,'Tirane'),(65,3,'Vlore'),(66,12,'Aragatsotn'),(67,12,'Ararat'),(68,12,'Armavir'),(69,12,'Geghark\'unik\''),(70,12,'Kotayk\''),(71,12,'Lorri'),(72,12,'Shirak'),(73,12,'Syunik\''),(74,12,'Tavush'),(75,12,'Vayots\' Dzor'),(76,12,'Yerevan'),(77,7,'Benguela'),(78,7,'Bie'),(79,7,'Cabinda'),(80,7,'Cuando Cubango'),(81,7,'Cuanza Norte'),(82,7,'Cuanza Sul'),(83,7,'Cunene'),(84,7,'Huambo'),(85,7,'Huila'),(86,7,'Malanje'),(87,7,'Namibe'),(88,7,'Moxico'),(89,7,'Uige'),(90,7,'Zaire'),(91,7,'Lunda Norte'),(92,7,'Lunda Sul'),(93,7,'Bengo'),(94,7,'Luanda'),(95,9,'Antarctica'),(96,11,'Buenos Aires'),(97,11,'Catamarca'),(98,11,'Chaco'),(99,11,'Chubut'),(100,11,'Cordoba'),(101,11,'Corrientes'),(102,11,'Distrito Federal'),(103,11,'Entre Rios'),(104,11,'Formosa'),(105,11,'Jujuy'),(106,11,'La Pampa'),(107,11,'La Rioja'),(108,11,'Mendoza'),(109,11,'Misiones'),(110,11,'Neuquen'),(111,11,'Rio Negro'),(112,11,'Salta'),(113,11,'San Juan'),(114,11,'San Luis'),(115,11,'Santa Cruz'),(116,11,'Santa Fe'),(117,11,'Santiago Del Estero'),(118,11,'Tierra Del Fuego'),(119,11,'Tucuman'),(120,5,'Eastern District'),(121,5,'Western District'),(122,16,'Burgenland'),(123,16,'Karnten'),(124,16,'Niederosterreich'),(125,16,'Oberosterreich'),(126,16,'Salzburg'),(127,16,'Steiermark'),(128,16,'Tirol'),(129,16,'Vorarlberg'),(130,16,'Wien'),(131,15,'Australian Capital Territory'),(132,15,'New South Wales'),(133,15,'Northern Territory'),(134,15,'Queensland'),(135,15,'South Australia'),(136,15,'Tasmania'),(137,15,'Victoria'),(138,15,'Western Australia'),(139,13,'Aruba (general)'),(140,2,'Eckeroe'),(141,2,'Finstroem'),(142,2,'Hammarland'),(143,2,'Jomala'),(144,2,'Lemland'),(145,2,'Mariehamn'),(146,2,'Saltvik'),(147,2,'Sund'),(148,17,'Abseron'),(149,17,'Agcabadi'),(150,17,'Agdam'),(151,17,'Agdas'),(152,17,'Agstafa'),(153,17,'Agsu'),(154,17,'Ali Bayramli'),(155,17,'Astara'),(156,17,'Baki'),(157,17,'Balakan'),(158,17,'Barda'),(159,17,'Beylaqan'),(160,17,'Bilasuvar'),(161,17,'Cabrayil'),(162,17,'Calilabad'),(163,17,'Daskasan'),(164,17,'Fuzuli'),(165,17,'Gadabay'),(166,17,'Ganca'),(167,17,'Goranboy'),(168,17,'Goycay'),(169,17,'Haciqabul'),(170,17,'Imisli'),(171,17,'Ismayilli'),(172,17,'Kalbacar'),(173,17,'Lacin'),(174,17,'Lankaran'),(175,17,'Lerik'),(176,17,'Masalli'),(177,17,'Mingacevir'),(178,17,'Naftalan'),(179,17,'Naxcivan'),(180,17,'Neftcala'),(181,17,'Oguz'),(182,17,'Qabala'),(183,17,'Qax'),(184,17,'Qazax'),(185,17,'Qobustan'),(186,17,'Quba'),(187,17,'Qubadli'),(188,17,'Qusar'),(189,17,'Saatli'),(190,17,'Sabirabad'),(191,17,'Saki'),(192,17,'Salyan'),(193,17,'Samaxi'),(194,17,'Samkir'),(195,17,'Samux'),(196,17,'Sumqayit'),(197,17,'Susa'),(198,17,'Tartar'),(199,17,'Tovuz'),(200,17,'Ucar'),(201,17,'Xacmaz'),(202,17,'Xankandi'),
                (203,17,'Xanlar'),(204,17,'Xizi'),(205,17,'Xocali'),(206,17,'Xocavand'),(207,17,'Yardimli'),(208,17,'Yevlax'),(209,17,'Zangilan'),(210,17,'Zaqatala'),(211,17,'Zardab'),(212,29,'Federation Of Bosnia And Herzegovina'),(213,29,'Republika Srpska'),(214,21,'Christ Church'),(215,21,'Saint James'),(216,21,'Saint Joseph'),(217,21,'Saint Michael'),(218,21,'Saint Peter'),(219,20,'Dhaka'),(220,20,'Khulna'),(221,20,'Rajshahi'),(222,20,'Chittagong'),(223,20,'Barisal'),(224,20,'Sylhet'),(225,20,'Rangpur'),(226,23,'Antwerpen'),(227,23,'Hainaut'),(228,23,'Liege'),(229,23,'Limburg'),(230,23,'Luxembourg'),(231,23,'Namur'),(232,23,'Oost-vlaanderen'),(233,23,'West-vlaanderen'),(234,23,'Brabant Wallon'),(235,23,'Brussels Hoofdstedelijk Gewest'),(236,23,'Vlaams-brabant'),(237,37,'Bam'),(238,37,'Boulkiemde'),(239,37,'Ganzourgou'),(240,37,'Gnagna'),(241,37,'Kouritenga'),(242,37,'Oudalan'),(243,37,'Passore'),(244,37,'Sanguie'),(245,37,'Soum'),(246,37,'Tapoa'),(247,37,'Zoundweogo'),(248,37,'Bale'),(249,37,'Banwa'),(250,37,'Bazega'),(251,37,'Bougouriba'),(252,37,'Boulgou'),(253,37,'Gourma'),(254,37,'Houet'),(255,37,'Ioba'),(256,37,'Kadiogo'),(257,37,'Kenedougou'),(258,37,'Komoe'),(259,37,'Komondjari'),(260,37,'Kompienga'),(261,37,'Kossi'),(262,37,'Koulpelogo'),(263,37,'Kourweogo'),(264,37,'Leraba'),(265,37,'Loroum'),(266,37,'Mouhoun'),(267,37,'Namentenga'),(268,37,'Naouri'),(269,37,'Nayala'),(270,37,'Noumbiel'),(271,37,'Oubritenga'),(272,37,'Poni'),(273,37,'Sanmatenga'),(274,37,'Seno'),(275,37,'Sissili'),(276,37,'Sourou'),(277,37,'Tuy'),(278,37,'Yagha'),(279,37,'Yatenga'),(280,37,'Ziro'),(281,37,'Zondoma'),(282,36,'Blagoevgrad'),(283,36,'Burgas'),(284,36,'Dobrich'),(285,36,'Gabrovo'),(286,36,'Grad Sofiya'),(287,36,'Khaskovo'),(288,36,'Kurdzhali'),(289,36,'Kyustendil'),(290,36,'Lovech'),(291,36,'Montana'),(292,36,'Pazardzhik'),(293,36,'Pernik'),(294,36,'Pleven'),(295,36,'Plovdiv'),(296,36,'Razgrad'),(297,36,'Ruse'),(298,36,'Shumen'),(299,36,'Silistra'),(300,36,'Sliven'),(301,36,'Smolyan'),(302,36,'Sofiya'),(303,36,'Stara Zagora'),(304,36,'Turgovishte'),(305,36,'Varna'),(306,36,'Veliko Turnovo'),(307,36,'Vidin'),(308,36,'Vratsa'),(309,36,'Yambol'),(310,19,'Al Muharraq'),(311,19,'Al Asimah'),(312,19,'Ash Shamaliyah'),(313,19,'Al Wusta'),(314,38,'Bubanza'),(315,38,'Bururi'),(316,38,'Cankuzo'),(317,38,'Cibitoke'),(318,38,'Gitega'),(319,38,'Karuzi'),(320,38,'Kayanza'),(321,38,'Kirundo'),(322,38,'Makamba'),(323,38,'Muyinga'),(324,38,'Ngozi'),(325,38,'Rutana'),(326,38,'Ruyigi'),(327,38,'Muramvya'),(328,38,'Mwaro'),(329,38,'Bujumbura Mairie'),(330,25,'Alibori'),(331,25,'Atakora'),(332,25,'Atlantique'),(333,25,'Borgou'),(334,25,'Collines'),(335,25,'Kouffo'),(336,25,'Donga'),(337,25,'Littoral'),(338,25,'Mono'),(339,25,'Oueme'),(340,25,'Plateau'),(341,25,'Zou'),(342,190,'Saint Barthelemy'),(343,26,'Hamilton'),(344,26,'Saint George'),(345,35,'Belait'),(346,35,'Brunei And Muara'),(347,35,'Temburong'),(348,35,'Tutong'),(349,28,'Chuquisaca'),(350,28,'Cochabamba'),(351,28,'El Beni'),(352,28,'La Paz'),(353,28,'Oruro'),(354,28,'Pando'),(355,28,'Potosi'),(356,28,'Santa Cruz'),(357,28,'Tarija'),(358,32,'Acre'),(359,32,'Alagoas'),(360,32,'Amapa'),(361,32,'Amazonas'),(362,32,'Bahia'),(363,32,'Ceara'),(364,32,'Distrito Federal'),(365,32,'Espirito Santo'),(366,32,'Mato Grosso Do Sul'),(367,32,'Maranhao'),(368,32,'Mato Grosso'),(369,32,'Minas Gerais'),(370,32,'Para'),(371,32,'Paraiba'),(372,32,'Parana'),(373,32,'Piaui'),(374,32,'Rio De Janeiro'),(375,32,'Rio Grande Do Norte'),(376,32,'Rio Grande Do Sul'),(377,32,'Rondonia'),(378,32,'Roraima'),(379,32,'Santa Catarina'),(380,32,'Sao Paulo'),(381,32,'Sergipe'),(382,32,'Goias'),(383,32,'Pernambuco'),(384,32,'Tocantins'),(385,18,'Long Island'),(386,18,'Harbour Island'),(387,18,'New Providence'),(388,18,'Freeport'),(389,18,'Fresh Creek'),(390,18,'High Rock'),(391,18,'Marsh Harbour'),(392,18,'Rock Sound'),(393,27,'Chhukha'),(394,27,'Daga'),(395,27,'Ha'),(396,27,'Mongar'),(397,27,'Paro'),(398,27,'Punakha'),(399,27,'Shemgang'),(400,27,'Thimphu'),(401,27,'Tongsa'),
                (402,27,'Gasa'),(403,27,'Trashi Yangste'),(404,30,'Central'),(405,30,'Ghanzi'),(406,30,'Kgalagadi'),(407,30,'Kgatleng'),(408,30,'Kweneng'),(409,30,'North-east'),(410,30,'South-east'),(411,30,'Southern'),(412,30,'North-west'),(413,22,'Brestskaya Voblasts\''),(414,22,'Homyel\'skaya Voblasts\''),(415,22,'Hrodzyenskaya Voblasts\''),(416,22,'Minskaya Voblasts\''),(417,22,'Mahilyowskaya Voblasts\''),(418,22,'Vitsyebskaya Voblasts\''),(419,24,'Belize'),(420,24,'Cayo'),(421,24,'Corozal'),(422,24,'Orange Walk'),(423,24,'Stann Creek'),(424,24,'Toledo'),(425,41,'Alberta'),(426,41,'British Columbia'),(427,41,'Manitoba'),(428,41,'New Brunswick'),(429,41,'Newfoundland And Labrador'),(430,41,'Nova Scotia'),(431,41,'Ontario'),(432,41,'Prince Edward Island'),(433,41,'Quebec'),(434,41,'Saskatchewan'),(435,41,'Yukon Territory'),(436,41,'Northwest Territories'),(437,41,'Nunavut'),(438,52,'Cocos Islands And Keeling Islands'),(439,56,'Bandundu'),(440,56,'Equateur'),(441,56,'Kasai-occidental'),(442,56,'Kasai-oriental'),(443,56,'Katanga'),(444,56,'Kinshasa'),(445,56,'Bas-congo'),(446,56,'Orientale'),(447,56,'Maniema'),(448,56,'Nord-kivu'),(449,56,'Sud-kivu'),(450,45,'Bamingui-bangoran'),(451,45,'Basse-kotto'),(452,45,'Haute-kotto'),(453,45,'Mambere-kadei'),(454,45,'Haut-mbomou'),(455,45,'Kemo'),(456,45,'Lobaye'),(457,45,'Mbomou'),(458,45,'Nana-mambere'),(459,45,'Ouaka'),(460,45,'Ouham'),(461,45,'Ouham-pende'),(462,45,'Cuvette-ouest'),(463,45,'Nana-grebizi'),(464,45,'Sangha-mbaere'),(465,45,'Ombella-mpoko'),(466,45,'Bangui'),(467,55,'Republic Of The Congo'),(468,55,'Bouenza'),(469,55,'Kouilou'),(470,55,'Lekoumou'),(471,55,'Likouala'),(472,55,'Niari'),(473,55,'Plateaux'),(474,55,'Sangha'),(475,55,'Pool'),(476,55,'Brazzaville'),(477,55,'Cuvette'),(478,55,'Cuvette-ouest'),(479,221,'Aargau'),(480,221,'Ausser-rhoden'),(481,221,'Basel-landschaft'),(482,221,'Basel-stadt'),(483,221,'Bern'),(484,221,'Fribourg'),(485,221,'Geneve'),(486,221,'Glarus'),(487,221,'Graubunden'),(488,221,'Inner-rhoden'),(489,221,'Luzern'),(490,221,'Neuchatel'),(491,221,'Nidwalden'),(492,221,'Obwalden'),(493,221,'Sankt Gallen'),(494,221,'Schaffhausen'),(495,221,'Schwyz'),(496,221,'Solothurn'),(497,221,'Thurgau'),(498,221,'Ticino'),(499,221,'Uri'),(500,221,'Valais'),(501,221,'Vaud'),(502,221,'Zug'),(503,221,'Zurich'),(504,221,'Jura'),(505,59,'Agneby'),(506,59,'Bafing'),(507,59,'Bas-sassandra'),(508,59,'Denguele'),(509,59,'Dix-huit Montagnes'),(510,59,'Fromager'),(511,59,'Haut-sassandra'),(512,59,'Lacs'),(513,59,'Lagunes'),(514,59,'Marahoue'),(515,59,'Moyen-cavally'),(516,59,'Moyen-comoe'),(517,59,'N\'zi-comoe'),(518,59,'Savanes'),(519,59,'Sud-bandama'),(520,59,'Sud-comoe'),(521,59,'Vallee Du Bandama'),(522,59,'Worodougou'),(523,59,'Zanzan'),(524,57,'Cook Islands'),(525,48,'Valparaiso'),(526,48,'Aisen Del General Carlos Ibanez Del Campo'),(527,48,'Antofagasta'),(528,48,'Araucania'),(529,48,'Atacama'),(530,48,'Bio-bio'),(531,48,'Coquimbo'),(532,48,'Libertador General Bernardo O\'higgins'),(533,48,'Magallanes Y De La Antartica Chilena'),(534,48,'Maule'),(535,48,'Region Metropolitana'),(536,48,'Los Lagos'),(537,48,'Tarapaca'),(538,48,'Arica Y Parinacota'),(539,48,'Los Rios'),(540,40,'Est'),(541,40,'Littoral'),(542,40,'Nord-ouest'),(543,40,'Ouest'),(544,40,'Sud-ouest'),(545,40,'Adamaoua'),(546,40,'Centre'),(547,40,'Extreme-nord'),(548,40,'Nord'),(549,40,'Sud'),(550,49,'Anhui'),(551,49,'Zhejiang'),(552,49,'Jiangxi'),(553,49,'Jiangsu'),(554,49,'Jilin'),(555,49,'Qinghai'),(556,49,'Fujian'),(557,49,'Heilongjiang'),(558,49,'Henan'),(559,49,'Hebei'),(560,49,'Hunan'),(561,49,'Hubei'),(562,49,'Xinjiang'),(563,49,'Xizang'),(564,49,'Gansu'),(565,49,'Guangxi'),(566,49,'Guizhou'),(567,49,'Liaoning'),(568,49,'Nei Mongol'),(569,49,'Ningxia'),(570,49,'Beijing'),(571,49,'Shanghai'),(572,49,'Shanxi'),(573,49,'Shandong'),(574,49,'Shaanxi'),(575,49,'Tianjin'),(576,49,'Yunnan'),(577,49,'Guangdong'),(578,49,'Hainan'),(579,49,'Sichuan'),(580,49,'Chongqing'),(581,53,'Amazonas'),(582,53,'Antioquia'),(583,53,'Arauca'),(584,53,'Atlantico'),(585,53,'Caqueta'),(586,53,'Cauca'),(587,53,'Cesar'),(588,53,'Choco'),(589,53,'Cordoba'),(590,53,'Guaviare'),(591,53,'Guainia'),(592,53,'Huila'),(593,53,'La Guajira'),(594,53,'Meta'),(595,53,'Narino'),(596,53,'Norte De Santander'),(597,53,'Putumayo'),(598,53,'Quindio'),(599,53,'Risaralda'),(600,53,'San Andres Y Providencia'),(601,53,'Santander'),
                (602,53,'Sucre'),(603,53,'Tolima'),(604,53,'Valle Del Cauca'),(605,53,'Vaupes'),(606,53,'Vichada'),(607,53,'Casanare'),(608,53,'Cundinamarca'),(609,53,'Distrito Especial'),(610,53,'Bolivar'),(611,53,'Boyaca'),(612,53,'Caldas'),(613,53,'Magdalena'),(614,58,'Alajuela'),(615,58,'Cartago'),(616,58,'Guanacaste'),(617,58,'Heredia'),(618,58,'Limon'),(619,58,'Puntarenas'),(620,58,'San Jose'),(621,61,'Pinar Del Rio'),(622,61,'Ciudad De La Habana'),(623,61,'Matanzas'),(624,61,'Isla De La Juventud'),(625,61,'Camaguey'),(626,61,'Ciego De Avila'),(627,61,'Cienfuegos'),(628,61,'Granma'),(629,61,'Guantanamo'),(630,61,'La Habana'),(631,61,'Holguin'),(632,61,'Las Tunas'),(633,61,'Sancti Spiritus'),(634,61,'Santiago De Cuba'),(635,61,'Villa Clara'),(636,61,'Artemisa'),(637,61,'Mayabeque'),(638,43,'Boa Vista'),(639,43,'Brava'),(640,43,'Maio'),(641,43,'Paul'),(642,43,'Ribeira Grande'),(643,43,'Sal'),(644,43,'Sao Vicente'),(645,43,'Mosteiros'),(646,43,'Praia'),(647,43,'Santa Catarina'),(648,43,'Santa Cruz'),(649,43,'Sao Domingos'),(650,43,'Sao Filipe'),(651,43,'Sao Miguel'),(652,43,'Tarrafal'),(653,43,'Porto Novo'),(654,43,'Ribeira Brava'),(655,43,'Ribeira Grande De Santiago'),(656,43,'Santa Catarina Do Fogo'),(657,43,'Sao Salvador Do Mundo'),(658,43,'Tarrafal De Sao Nicolau'),(659,50,'Christmas Island'),(660,62,'Famagusta'),(661,62,'Kyrenia'),(662,62,'Larnaca'),(663,62,'Nicosia'),(664,62,'Limassol'),(665,62,'Paphos'),(666,63,'Hlavni Mesto Praha'),(667,63,'Jihomoravsky Kraj'),(668,63,'Jihocesky Kraj'),(669,63,'Vysocina Kraj'),(670,63,'Karlovarsky Kraj'),(671,63,'Kralovehradecky Kraj'),(672,63,'Liberecky Kraj'),(673,63,'Olomoucky Kraj'),(674,63,'Moravskoslezsky Kraj'),(675,63,'Pardubicky Kraj'),(676,63,'Plzensky Kraj'),(677,63,'Stredocesky Kraj'),(678,63,'Ustecky Kraj'),(679,63,'Zlinsky Kraj'),(680,88,'Baden-wurttemberg'),(681,88,'Bayern'),(682,88,'Bremen'),(683,88,'Hamburg'),(684,88,'Hessen'),(685,88,'Niedersachsen'),(686,88,'Nordrhein-westfalen'),(687,88,'Rheinland-pfalz'),(688,88,'Saarland'),(689,88,'Schleswig-holstein'),(690,88,'Brandenburg'),(691,88,'Mecklenburg-vorpommern'),(692,88,'Sachsen'),(693,88,'Sachsen-anhalt'),(694,88,'Thuringen'),(695,88,'Berlin'),(696,66,'Ali Sabieh'),(697,66,'Obock'),(698,66,'Tadjoura'),(699,66,'Dikhil'),(700,66,'Djibouti'),(701,66,'Arta'),(702,64,'Hovedstaden'),(703,64,'Midtjylland'),(704,64,'Nordjylland'),(705,64,'Sjelland'),(706,64,'Syddanmark'),(707,67,'Saint Andrew'),(708,67,'Saint David'),(709,67,'Saint George'),(710,67,'Saint John'),(711,67,'Saint Joseph'),(712,67,'Saint Luke'),(713,67,'Saint Mark'),(714,67,'Saint Patrick'),(715,67,'Saint Paul'),(716,68,'Azua'),(717,68,'Baoruco'),(718,68,'Barahona'),(719,68,'Dajabon'),(720,68,'Duarte'),(721,68,'Espaillat'),(722,68,'Independencia'),(723,68,'La Altagracia'),(724,68,'Elias Pina'),(725,68,'La Romana'),(726,68,'Maria Trinidad Sanchez'),(727,68,'Monte Cristi'),(728,68,'Pedernales'),(729,68,'Puerto Plata'),(730,68,'Salcedo'),(731,68,'Samana'),(732,68,'Sanchez Ramirez'),(733,68,'San Juan'),(734,68,'San Pedro De Macoris'),(735,68,'Santiago'),(736,68,'Santiago Rodriguez'),(737,68,'Valverde'),(738,68,'El Seibo'),(739,68,'Hato Mayor'),(740,68,'La Vega'),(741,68,'Monsenor Nouel'),(742,68,'Monte Plata'),(743,68,'San Cristobal'),(744,68,'Distrito Nacional'),(745,68,'Peravia'),(746,4,'Alger'),(747,4,'Batna'),(748,4,'Constantine'),(749,4,'Medea'),(750,4,'Mostaganem'),(751,4,'Oran'),(752,4,'Saida'),(753,4,'Setif'),(754,4,'Tiaret'),(755,4,'Tizi Ouzou'),(756,4,'Tlemcen'),(757,4,'Bejaia'),(758,4,'Biskra'),(759,4,'Blida'),(760,4,'Bouira'),(761,4,'Djelfa'),(762,4,'Guelma'),(763,4,'Laghouat'),(764,4,'Mascara'),(765,4,'M\'sila'),(766,4,'Oum El Bouaghi'),(767,4,'Sidi Bel Abbes'),(768,4,'Skikda'),(769,4,'Tebessa'),(770,4,'Adrar'),(771,4,'Ain Defla'),(772,4,'Ain Temouchent'),(773,4,'Annaba'),(774,4,'Bechar'),(775,4,'Bordj Bou Arreridj'),(776,4,'Boumerdes'),(777,4,'Chlef'),(778,4,'El Bayadh'),(779,4,'El Oued'),(780,4,'El Tarf'),(781,4,'Ghardaia'),(782,4,'Illizi'),(783,4,'Khenchela'),(784,4,'Mila'),(785,4,'Naama'),(786,4,'Ouargla'),(787,4,'Relizane'),(788,4,'Souk Ahras'),(789,4,'Tamanghasset'),(790,4,'Tindouf'),(791,4,'Tipaza'),(792,4,'Tissemsilt'),(793,69,'Galapagos'),(794,69,'Azuay'),(795,69,'Bolivar'),(796,69,'Canar'),(797,69,'Carchi'),(798,69,'Chimborazo'),(799,69,'Cotopaxi'),(800,69,'El Oro'),(801,69,'Esmeraldas'),
                (802,69,'Guayas'),(803,69,'Imbabura'),(804,69,'Loja'),(805,69,'Los Rios'),(806,69,'Manabi'),(807,69,'Morona-santiago'),(808,69,'Pastaza'),(809,69,'Pichincha'),(810,69,'Tungurahua'),(811,69,'Zamora-chinchipe'),(812,69,'Sucumbios'),(813,69,'Napo'),(814,69,'Orellana'),(815,69,'Santa Elena'),(816,74,'Harjumaa'),(817,74,'Hiiumaa'),(818,74,'Ida-virumaa'),(819,74,'Jarvamaa'),(820,74,'Jogevamaa'),(821,74,'Laanemaa'),(822,74,'Laane-virumaa'),(823,74,'Parnumaa'),(824,74,'Polvamaa'),(825,74,'Raplamaa'),(826,74,'Saaremaa'),(827,74,'Tartumaa'),(828,74,'Valgamaa'),(829,74,'Viljandimaa'),(830,74,'Vorumaa'),(831,70,'Ad Daqahliyah'),(832,70,'Al Bahr Al Ahmar'),(833,70,'Al Buhayrah'),(834,70,'Al Fayyum'),(835,70,'Al Gharbiyah'),(836,70,'Al Iskandariyah'),(837,70,'Al Isma\'iliyah'),(838,70,'Al Jizah'),(839,70,'Al Minufiyah'),(840,70,'Al Minya'),(841,70,'Al Qahirah'),(842,70,'Al Qalyubiyah'),(843,70,'Al Wadi Al Jadid'),(844,70,'Ash Sharqiyah'),(845,70,'As Suways'),(846,70,'Aswan'),(847,70,'Asyut'),(848,70,'Bani Suwayf'),(849,70,'Bur Sa\'id'),(850,70,'Dumyat'),(851,70,'Kafr Ash Shaykh'),(852,70,'Matruh'),(853,70,'Qina'),(854,70,'Suhaj'),(855,70,'Janub Sina\''),(856,70,'Shamal Sina\''),(857,70,'Muhafazat Al Uqsur'),(858,252,'Western Sahara'),(859,252,'Oued Ed-dahab-lagouira'),(860,73,'Anseba'),(861,73,'Debub'),(862,73,'Debubawi K\'eyih Bahri'),(863,73,'Gash Barka'),(864,73,'Ma\'akel'),(865,73,'Semenawi K\'eyih Bahri'),(866,214,'Islas Baleares'),(867,214,'La Rioja'),(868,214,'Madrid'),(869,214,'Murcia'),(870,214,'Navarra'),(871,214,'Asturias'),(872,214,'Cantabria'),(873,214,'Andalucia'),(874,214,'Aragon'),(875,214,'Canarias'),(876,214,'Castilla-la Mancha'),(877,214,'Castilla Y Leon'),(878,214,'Catalonia'),(879,214,'Extremadura'),(880,214,'Galicia'),(881,214,'Pais Vasco'),(882,214,'Comunidad Valenciana'),(883,214,'Ceuta'),(884,214,'Melilla'),(885,75,'Adis Abeba'),(886,75,'Afar'),(887,75,'Amara'),(888,75,'Binshangul Gumuz'),(889,75,'Dire Dawa'),(890,75,'Gambela Hizboch'),(891,75,'Hareri Hizb'),(892,75,'Oromiya'),(893,75,'Sumale'),(894,75,'Tigray'),(895,75,'Yedebub Biheroch Bihereseboch Na Hizboch'),(896,80,'Lapland'),(897,80,'Oulu'),(898,80,'Southern Finland'),(899,80,'Eastern Finland'),(900,80,'Western Finland'),(901,79,'Central'),(902,79,'Northern'),(903,79,'Western'),(904,77,'Falkland Islands'),(905,147,'Kosrae'),(906,147,'Pohnpei'),(907,147,'Chuuk'),(908,147,'Yap'),(909,78,'Nordoyar'),(910,78,'Eysturoy'),(911,78,'Sandoy'),(912,78,'Streymoy'),(913,78,'Suduroy'),(914,78,'Vagar'),(915,81,'Aquitaine'),(916,81,'Auvergne'),(917,81,'Basse-normandie'),(918,81,'Bourgogne'),(919,81,'Bretagne'),(920,81,'Centre'),(921,81,'Champagne-ardenne'),(922,81,'Corse'),(923,81,'Franche-comte'),(924,81,'Haute-normandie'),(925,81,'Ile-de-france'),(926,81,'Languedoc-roussillon'),(927,81,'Limousin'),(928,81,'Lorraine'),(929,81,'Midi-pyrenees'),(930,81,'Nord-pas-de-calais'),(931,81,'Pays De La Loire'),(932,81,'Picardie'),(933,81,'Poitou-charentes'),(934,81,'Provence-alpes-cote D\'azur'),(935,81,'Rhone-alpes'),(936,81,'Alsace'),(937,85,'Estuaire'),(938,85,'Haut-ogooue'),(939,85,'Moyen-ogooue'),(940,85,'Ngounie'),(941,85,'Nyanga'),(942,85,'Ogooue-ivindo'),(943,85,'Ogooue-lolo'),(944,85,'Ogooue-maritime'),(945,85,'Woleu-ntem'),(946,243,'England'),(947,243,'Northern Ireland'),(948,243,'Scotland'),(949,243,'Wales'),(950,93,'Saint Andrew'),(951,93,'Saint David'),(952,93,'Saint George'),(953,93,'Saint John'),(954,93,'Saint Mark'),(955,93,'Saint Patrick'),(956,87,'Abkhazia'),(957,87,'Ajaria'),(958,87,'Akhalk\'alak\'is Raioni'),(959,87,'Baghdat\'is Raioni'),(960,87,'Borjomis Raioni'),(961,87,'Goris Raioni'),(962,87,'Javis Raioni'),(963,87,'K\'arelis Raioni'),(964,87,'Khashuris Raioni'),(965,87,'T\'bilisi'),(966,87,'Vanis Raioni'),(967,87,'Guria'),(968,87,'Imereti'),(969,87,'Kakheti'),(970,87,'Kvemo Kartli'),(971,87,'Mtskheta-mtianeti'),(972,87,'Racha-lechkhumi And Kvemo Svaneti'),(973,87,'Samegrelo And Zemo Svaneti'),(974,87,'Samtskhe-javakheti'),(975,87,'Shida Kartli'),(976,82,'Guyane'),(977,97,'Guernsey (general)'),(978,89,'Greater Accra'),(979,89,'Ashanti'),(980,89,'Brong-ahafo'),(981,89,'Central'),(982,89,'Eastern'),(983,89,'Northern'),(984,89,'Volta'),(985,89,'Western'),(986,89,'Upper East'),(987,89,'Upper West'),(988,90,'Gibraltar'),(989,92,'Vestgronland'),(990,92,'Kujalleq'),(991,92,'Qaasuitsup'),(992,92,'Qeqqata'),(993,92,'Sermersooq'),(994,86,'Banjul'),(995,86,'Lower River'),(996,86,'Central River'),(997,86,'Upper River'),(998,86,'Western'),(999,86,'North Bank'),(1000,98,'Beyla'),(1001,98,'Boffa'),
                (1002,98,'Boke'),(1003,98,'Conakry'),(1004,98,'Dabola'),(1005,98,'Dalaba'),(1006,98,'Dinguiraye'),(1007,98,'Faranah'),(1008,98,'Forecariah'),(1009,98,'Fria'),(1010,98,'Gaoual'),(1011,98,'Gueckedou'),(1012,98,'Kerouane'),(1013,98,'Kindia'),(1014,98,'Kissidougou'),(1015,98,'Koundara'),(1016,98,'Kouroussa'),(1017,98,'Macenta'),(1018,98,'Mali'),(1019,98,'Mamou'),(1020,98,'Pita'),(1021,98,'Telimele'),(1022,98,'Tougue'),(1023,98,'Yomou'),(1024,98,'Coyah'),(1025,98,'Dubreka'),(1026,98,'Kankan'),(1027,98,'Koubia'),(1028,98,'Labe'),(1029,98,'Lelouma'),(1030,98,'Lola'),(1031,98,'Mandiana'),(1032,98,'Nzerekore'),(1033,98,'Siguiri'),(1034,94,'Guadeloupe'),(1035,72,'Annobon'),(1036,72,'Bioko Norte'),(1037,72,'Bioko Sur'),(1038,72,'Centro Sur'),(1039,72,'Kie-ntem'),(1040,72,'Litoral'),(1041,72,'Wele-nzas'),(1042,91,'Evros'),(1043,91,'Rodhopi'),(1044,91,'Xanthi'),(1045,91,'Drama'),(1046,91,'Serrai'),(1047,91,'Kilkis'),(1048,91,'Pella'),(1049,91,'Florina'),(1050,91,'Kastoria'),(1051,91,'Grevena'),(1052,91,'Kozani'),(1053,91,'Imathia'),(1054,91,'Thessaloniki'),(1055,91,'Kavala'),(1056,91,'Khalkidhiki'),(1057,91,'Pieria'),(1058,91,'Ioannina'),(1059,91,'Thesprotia'),(1060,91,'Preveza'),(1061,91,'Arta'),(1062,91,'Larisa'),(1063,91,'Trikala'),(1064,91,'Kardhitsa'),(1065,91,'Magnisia'),(1066,91,'Kerkira'),(1067,91,'Levkas'),(1068,91,'Kefallinia'),(1069,91,'Zakinthos'),(1070,91,'Fthiotis'),(1071,91,'Evritania'),(1072,91,'Aitolia Kai Akarnania'),(1073,91,'Fokis'),(1074,91,'Voiotia'),(1075,91,'Evvoia'),(1076,91,'Attiki'),(1077,91,'Argolis'),(1078,91,'Korinthia'),(1079,91,'Akhaia'),(1080,91,'Ilia'),(1081,91,'Messinia'),(1082,91,'Arkadhia'),(1083,91,'Lakonia'),(1084,91,'Khania'),(1085,91,'Rethimni'),(1086,91,'Iraklion'),(1087,91,'Lasithi'),(1088,91,'Dhodhekanisos'),(1089,91,'Samos'),(1090,91,'Kikladhes'),(1091,91,'Khios'),(1092,91,'Lesvos'),(1093,212,'South Georgia And The South Sandwich Islands'),(1094,96,'Alta Verapaz'),(1095,96,'Baja Verapaz'),(1096,96,'Chimaltenango'),(1097,96,'Chiquimula'),(1098,96,'El Progreso'),(1099,96,'Escuintla'),(1100,96,'Guatemala'),(1101,96,'Huehuetenango'),(1102,96,'Izabal'),(1103,96,'Jalapa'),(1104,96,'Jutiapa'),(1105,96,'Peten'),(1106,96,'Quetzaltenango'),(1107,96,'Quiche'),(1108,96,'Retalhuleu'),(1109,96,'Sacatepequez'),(1110,96,'San Marcos'),(1111,96,'Santa Rosa'),(1112,96,'Solola'),(1113,96,'Suchitepequez'),(1114,96,'Totonicapan'),(1115,96,'Zacapa'),(1116,95,'Agana Heights Municipality'),(1117,95,'Hagatna Municipality'),(1118,95,'Asan-maina Municipality'),(1119,95,'Agat Municipality'),(1120,95,'Barrigada Municipality'),(1121,95,'Chalan Pago-ordot Municipality'),(1122,95,'Dededo Municipality'),(1123,95,'Inarajan Municipality'),(1124,95,'Mangilao Municipality'),(1125,95,'Merizo Municipality'),(1126,95,'Mongmong-toto-maite Municipality'),(1127,95,'Piti Municipality'),(1128,95,'Sinajana Municipality'),(1129,95,'Santa Rita Municipality'),(1130,95,'Talofofo Municipality'),(1131,95,'Tamuning-tumon-harmon Municipality'),(1132,95,'Umatac Municipality'),(1133,95,'Yigo Municipality'),(1134,95,'Yona Municipality'),(1135,99,'Bafata'),(1136,99,'Quinara'),(1137,99,'Oio'),(1138,99,'Bolama'),(1139,99,'Cacheu'),(1140,99,'Tombali'),(1141,99,'Gabu'),(1142,99,'Bissau'),(1143,99,'Biombo'),(1144,100,'Cuyuni-mazaruni'),(1145,100,'Demerara-mahaica'),(1146,100,'East Berbice-corentyne'),(1147,100,'Essequibo Islands-west Demerara'),(1148,100,'Mahaica-berbice'),(1149,100,'Pomeroon-supenaam'),(1150,100,'Upper Demerara-berbice'),(1151,104,'Hong Kong (sar)'),(1152,103,'Atlantida'),(1153,103,'Choluteca'),(1154,103,'Colon'),(1155,103,'Comayagua'),(1156,103,'Copan'),(1157,103,'Cortes'),(1158,103,'El Paraiso'),(1159,103,'Francisco Morazan'),(1160,103,'Gracias A Dios'),(1161,103,'Intibuca'),(1162,103,'Islas De La Bahia'),(1163,103,'La Paz'),(1164,103,'Lempira'),(1165,103,'Ocotepeque'),(1166,103,'Olancho'),(1167,103,'Santa Barbara'),(1168,103,'Valle'),(1169,103,'Yoro'),(1170,60,'Bjelovarsko-bilogorska'),(1171,60,'Brodsko-posavska'),(1172,60,'Dubrovacko-neretvanska'),(1173,60,'Istarska'),(1174,60,'Karlovacka'),(1175,60,'Koprivnicko-krizevacka'),(1176,60,'Krapinsko-zagorska'),(1177,60,'Licko-senjska'),(1178,60,'Medimurska'),(1179,60,'Osjecko-baranjska'),(1180,60,'Pozesko-slavonska'),(1181,60,'Primorsko-goranska'),(1182,60,'Sibensko-kninska'),(1183,60,'Sisacko-moslavacka'),(1184,60,'Splitsko-dalmatinska'),(1185,60,'Varazdinska'),(1186,60,'Viroviticko-podravska'),(1187,60,'Vukovarsko-srijemska'),(1188,60,'Zadarska'),(1189,60,'Zagrebacka'),(1190,60,'Grad Zagreb'),(1191,101,'Nord-ouest'),(1192,101,'Artibonite'),(1193,101,'Centre'),(1194,101,'Nord'),(1195,101,'Nord-est'),(1196,101,'Ouest'),(1197,101,'Sud'),(1198,101,'Sud-est'),(1199,101,'Grand\' Anse'),(1200,101,'Nippes'),(1201,105,'Bacs-kiskun'),
                (1202,105,'Baranya'),(1203,105,'Bekes'),(1204,105,'Borsod-abauj-zemplen'),(1205,105,'Budapest'),(1206,105,'Csongrad'),(1207,105,'Fejer'),(1208,105,'Gyor-moson-sopron'),(1209,105,'Hajdu-bihar'),(1210,105,'Heves'),(1211,105,'Komarom-esztergom'),(1212,105,'Nograd'),(1213,105,'Pest'),(1214,105,'Somogy'),(1215,105,'Szabolcs-szatmar-bereg'),(1216,105,'Jasz-nagykun-szolnok'),(1217,105,'Tolna'),(1218,105,'Vas'),(1219,105,'Veszprem'),(1220,105,'Zala'),(1221,108,'Aceh'),(1222,108,'Bali'),(1223,108,'Bengkulu'),(1224,108,'Jakarta Raya'),(1225,108,'Jambi'),(1226,108,'Jawa Tengah'),(1227,108,'Jawa Timur'),(1228,108,'Yogyakarta'),(1229,108,'Kalimantan Barat'),(1230,108,'Kalimantan Selatan'),(1231,108,'Kalimantan Tengah'),(1232,108,'Kalimantan Timur'),(1233,108,'Lampung'),(1234,108,'Nusa Tenggara Barat'),(1235,108,'Nusa Tenggara Timur'),(1236,108,'Sulawesi Tengah'),(1237,108,'Sulawesi Tenggara'),(1238,108,'Sumatera Barat'),(1239,108,'Sumatera Utara'),(1240,108,'Maluku'),(1241,108,'Maluku Utara'),(1242,108,'Jawa Barat'),(1243,108,'Sulawesi Utara'),(1244,108,'Sumatera Selatan'),(1245,108,'Banten'),(1246,108,'Gorontalo'),(1247,108,'Kepulauan Bangka Belitung'),(1248,108,'Papua'),(1249,108,'Riau'),(1250,108,'Sulawesi Selatan'),(1251,108,'Irian Jaya Barat'),(1252,108,'Kepulauan Riau'),(1253,108,'Sulawesi Barat'),(1254,111,'Carlow'),(1255,111,'Cavan'),(1256,111,'Clare'),(1257,111,'Cork'),(1258,111,'Donegal'),(1259,111,'Dublin'),(1260,111,'Galway'),(1261,111,'Kerry'),(1262,111,'Kildare'),(1263,111,'Kilkenny'),(1264,111,'Leitrim'),(1265,111,'Laois'),(1266,111,'Limerick'),(1267,111,'Longford'),(1268,111,'Louth'),(1269,111,'Mayo'),(1270,111,'Meath'),(1271,111,'Monaghan'),(1272,111,'Offaly'),(1273,111,'Roscommon'),(1274,111,'Sligo'),(1275,111,'Tipperary'),(1276,111,'Waterford'),(1277,111,'Westmeath'),(1278,111,'Wexford'),(1279,111,'Wicklow'),(1280,111,'Dublin City'),(1281,111,'Fingal'),(1282,111,'Tipperary North Riding'),(1283,111,'South Dublin'),(1284,113,'Hadarom'),(1285,113,'Hamerkaz'),(1286,113,'Hazafon'),(1287,113,'Hefa'),(1288,113,'Tel Aviv'),(1289,113,'Yerushalayim'),(1290,112,'Isle Of Man'),(1291,107,'Andaman And Nicobar Islands'),(1292,107,'Andhra Pradesh'),(1293,107,'Assam'),(1294,107,'Chandigarh'),(1295,107,'Dadra And Nagar Haveli'),(1296,107,'Delhi'),(1297,107,'Gujarat'),(1298,107,'Haryana'),(1299,107,'Himachal Pradesh'),(1300,107,'Jammu And Kashmir'),(1301,107,'Kerala'),(1302,107,'Lakshadweep'),(1303,107,'Maharashtra'),(1304,107,'Manipur'),(1305,107,'Meghalaya'),(1306,107,'Karnataka'),(1307,107,'Nagaland'),(1308,107,'Orissa'),(1309,107,'Puducherry'),(1310,107,'Punjab'),(1311,107,'Rajasthan'),(1312,107,'Tamil Nadu'),(1313,107,'Tripura'),(1314,107,'West Bengal'),(1315,107,'Sikkim'),(1316,107,'Arunachal Pradesh'),(1317,107,'Mizoram'),(1318,107,'Daman And Diu'),(1319,107,'Goa'),(1320,107,'Bihar'),(1321,107,'Madhya Pradesh'),(1322,107,'Uttar Pradesh'),(1323,107,'Chhattisgarh'),(1324,107,'Jharkhand'),(1325,107,'Uttarakhand'),(1326,33,'British Indian Ocean Territory'),(1327,110,'Al Anbar'),(1328,110,'Al Basrah'),(1329,110,'Al Muthanna'),(1330,110,'Al Qadisiyah'),(1331,110,'As Sulaymaniyah'),(1332,110,'Babil'),(1333,110,'Baghdad'),(1334,110,'Dahuk'),(1335,110,'Dhi Qar'),(1336,110,'Diyala'),(1337,110,'Arbil'),(1338,110,'Karbala\''),(1339,110,'At Ta\'mim'),(1340,110,'Maysan'),(1341,110,'Ninawa'),(1342,110,'Wasit'),(1343,110,'An Najaf'),(1344,110,'Salah Ad Din'),(1345,109,'Azarbayjan-e Bakhtari'),(1346,109,'Chahar Mahall Va Bakhtiari'),(1347,109,'Sistan Va Baluchestan'),(1348,109,'Kohkiluyeh Va Buyer Ahmadi'),(1349,109,'Fars'),(1350,109,'Gilan'),(1351,109,'Hamadan'),(1352,109,'Ilam'),(1353,109,'Hormozgan'),(1354,109,'Bakhtaran'),(1355,109,'Khuzestan'),(1356,109,'Kordestan'),(1357,109,'Bushehr'),(1358,109,'Lorestan'),(1359,109,'Semnan'),(1360,109,'Tehran'),(1361,109,'Esfahan'),(1362,109,'Kerman'),(1363,109,'Ardabil'),(1364,109,'East Azarbaijan'),(1365,109,'Markazi'),(1366,109,'Mazandaran'),(1367,109,'Zanjan'),(1368,109,'Golestan'),(1369,109,'Qazvin'),(1370,109,'Qom'),(1371,109,'Yazd'),(1372,109,'Khorasan-e Janubi'),(1373,109,'Khorasan-e Razavi'),(1374,109,'Khorasan-e Shemali'),(1375,109,'Alborz'),(1376,106,'Austurland'),(1377,106,'Hofuoborgarsvaoio'),(1378,106,'Norourland Eystra'),(1379,106,'Norourland Vestra'),(1380,106,'Suourland'),(1381,106,'Suournes'),(1382,106,'Vestfiroir'),(1383,106,'Vesturland'),(1384,114,'Abruzzi'),(1385,114,'Basilicata'),(1386,114,'Calabria'),(1387,114,'Campania'),(1388,114,'Emilia-romagna'),(1389,114,'Friuli-venezia Giulia'),(1390,114,'Lazio'),(1391,114,'Liguria'),(1392,114,'Lombardia'),(1393,114,'Marche'),(1394,114,'Molise'),(1395,114,'Piemonte'),(1396,114,'Puglia'),(1397,114,'Sardegna'),(1398,114,'Sicilia'),(1399,114,'Toscana'),(1400,114,'Trentino-alto Adige'),(1401,114,'Umbria'),(1402,114,'Valle D\'aosta'),
                (1403,114,'Veneto'),(1404,117,'Jersey'),(1405,115,'Clarendon'),(1406,115,'Hanover'),(1407,115,'Manchester'),(1408,115,'Portland'),(1409,115,'Saint Andrew'),(1410,115,'Saint Ann'),(1411,115,'Saint Catherine'),(1412,115,'Saint Elizabeth'),(1413,115,'Saint James'),(1414,115,'Saint Mary'),(1415,115,'Saint Thomas'),(1416,115,'Trelawny'),(1417,115,'Westmoreland'),(1418,115,'Kingston'),(1419,118,'Al Balqa\''),(1420,118,'Al Karak'),(1421,118,'At Tafilah'),(1422,118,'Al Mafraq'),(1423,118,'\'amman'),(1424,118,'Az Zarqa\''),(1425,118,'Irbid'),(1426,118,'Ma\'an'),(1427,118,'Al \'aqabah'),(1428,118,'Madaba'),(1429,116,'Aichi'),(1430,116,'Akita'),(1431,116,'Aomori'),(1432,116,'Chiba'),(1433,116,'Ehime'),(1434,116,'Fukui'),(1435,116,'Fukuoka'),(1436,116,'Fukushima'),(1437,116,'Gifu'),(1438,116,'Gumma'),(1439,116,'Hiroshima'),(1440,116,'Hokkaido'),(1441,116,'Hyogo'),(1442,116,'Ibaraki'),(1443,116,'Ishikawa'),(1444,116,'Iwate'),(1445,116,'Kagawa'),(1446,116,'Kagoshima'),(1447,116,'Kanagawa'),(1448,116,'Kochi'),(1449,116,'Kumamoto'),(1450,116,'Kyoto'),(1451,116,'Mie'),(1452,116,'Miyagi'),(1453,116,'Miyazaki'),(1454,116,'Nagano'),(1455,116,'Nagasaki'),(1456,116,'Nara'),(1457,116,'Niigata'),(1458,116,'Oita'),(1459,116,'Okayama'),(1460,116,'Osaka'),(1461,116,'Saga'),(1462,116,'Saitama'),(1463,116,'Shiga'),(1464,116,'Shimane'),(1465,116,'Shizuoka'),(1466,116,'Tochigi'),(1467,116,'Tokushima'),(1468,116,'Tokyo'),(1469,116,'Tottori'),(1470,116,'Toyama'),(1471,116,'Wakayama'),(1472,116,'Yamagata'),(1473,116,'Yamaguchi'),(1474,116,'Yamanashi'),(1475,116,'Okinawa'),(1476,120,'Central'),(1477,120,'Coast'),(1478,120,'Eastern'),(1479,120,'Nairobi Area'),(1480,120,'North-eastern'),(1481,120,'Nyanza'),(1482,120,'Rift Valley'),(1483,120,'Western'),(1484,123,'Bishkek'),(1485,123,'Chuy'),(1486,123,'Jalal-abad'),(1487,123,'Naryn'),(1488,123,'Talas'),(1489,123,'Ysyk-kol'),(1490,123,'Osh'),(1491,123,'Batken'),(1492,39,'Kampong Cham'),(1493,39,'Kampong Chhnang'),(1494,39,'Kampong Speu'),(1495,39,'Kampong Thom'),(1496,39,'Kandal'),(1497,39,'Koh Kong'),(1498,39,'Kratie'),(1499,39,'Mondulkiri'),(1500,39,'Pursat'),(1501,39,'Preah Vihear'),(1502,39,'Prey Veng'),(1503,39,'Stung Treng'),(1504,39,'Svay Rieng'),(1505,39,'Takeo'),(1506,39,'Kampot'),(1507,39,'Phnom Penh'),(1508,39,'Ratanakiri'),(1509,39,'Siem Reap'),(1510,39,'Banteay Meanchey'),(1511,39,'Kep'),(1512,39,'Oddar Meanchey'),(1513,39,'Preah Sihanouk'),(1514,39,'Battambang'),(1515,39,'Pailin'),(1516,121,'Gilbert Islands'),(1517,121,'Line Islands'),(1518,54,'Anjouan'),(1519,54,'Grande Comore'),(1520,54,'Moheli'),(1521,192,'Saint George Basseterre'),(1522,192,'Saint Paul Charlestown'),(1523,168,'Chagang-do'),(1524,168,'Hamgyong-namdo'),(1525,168,'Hwanghae-namdo'),(1526,168,'Hwanghae-bukto'),(1527,168,'Kangwon-do'),(1528,168,'P\'yongan-bukto'),(1529,168,'P\'yongyang-si'),(1530,168,'Yanggang-do'),(1531,168,'P\'yongan-namdo'),(1532,168,'Hamgyong-bukto'),(1533,168,'Najin Sonbong-si'),(1534,213,'Cheju-do'),(1535,213,'Cholla-bukto'),(1536,213,'Ch\'ungch\'ong-bukto'),(1537,213,'Kangwon-do'),(1538,213,'Pusan-jikhalsi'),(1539,213,'Seoul-t\'ukpyolsi'),(1540,213,'Inch\'on-jikhalsi'),(1541,213,'Kyonggi-do'),(1542,213,'Kyongsang-bukto'),(1543,213,'Taegu-jikhalsi'),(1544,213,'Cholla-namdo'),(1545,213,'Ch\'ungch\'ong-namdo'),(1546,213,'Kwangju-jikhalsi'),(1547,213,'Taejon-jikhalsi'),(1548,213,'Kyongsang-namdo'),(1549,213,'Ulsan-gwangyoksi'),(1550,122,'Al Asimah'),(1551,122,'Al Ahmadi'),(1552,122,'Al Jahra'),(1553,122,'Al Farwaniyah'),(1554,122,'Hawalli'),(1555,122,'Mubarak Al Kabir'),(1556,44,'Cayman Islands'),(1557,119,'Almaty'),(1558,119,'Almaty City'),(1559,119,'Aqmola'),(1560,119,'Aqtobe'),(1561,119,'Astana'),(1562,119,'Atyrau'),(1563,119,'West Kazakhstan'),(1564,119,'Bayqonyr'),(1565,119,'Mangghystau'),(1566,119,'South Kazakhstan'),(1567,119,'Pavlodar'),(1568,119,'Qaraghandy'),(1569,119,'Qostanay'),(1570,119,'Qyzylorda'),(1571,119,'East Kazakhstan'),(1572,119,'North Kazakhstan'),(1573,119,'Zhambyl'),(1574,124,'Attapu'),(1575,124,'Champasak'),(1576,124,'Houaphan'),(1577,124,'Oudomxai'),(1578,124,'Xaignabouri'),(1579,124,'Xiangkhoang'),(1580,124,'Khoueng Khammouan'),(1581,124,'Loungnamtha'),(1582,124,'Louangphrabang'),(1583,124,'Khoueng Phongsali'),(1584,124,'Khoueng Salavan'),(1585,124,'Khoueng Savannakhet'),(1586,124,'Bokeo'),(1587,124,'Bolikhamxai'),(1588,124,'Kampheng Nakhon Viangchan'),(1589,124,'Khoueng Xekong'),(1590,124,'Khoueng Viangchan'),(1591,126,'Beyrouth'),(1592,126,'Mont-liban'),(1593,126,'Liban-sud'),(1594,126,'Nabatiye'),(1595,126,'Beqaa'),(1596,126,'Liban-nord'),(1597,126,'Aakk'),(1598,126,'Baalbek-hermel'),(1599,193,'Anse-la-raye'),(1600,193,'Castries'),(1601,193,'Dennery'),(1602,193,'Gros-islet'),
                (1603,193,'Laborie'),(1604,193,'Micoud'),(1605,193,'Soufriere'),(1606,193,'Vieux-fort'),(1607,130,'Balzers'),(1608,130,'Eschen'),(1609,130,'Gamprin'),(1610,130,'Mauren'),(1611,130,'Planken'),(1612,130,'Ruggell'),(1613,130,'Schaan'),(1614,130,'Schellenberg'),(1615,130,'Triesen'),(1616,130,'Triesenberg'),(1617,130,'Vaduz'),(1618,215,'Central'),(1619,215,'North Central'),(1620,215,'Northern'),(1621,215,'North Western'),(1622,215,'Sabaragamuwa'),(1623,215,'Southern'),(1624,215,'Uva'),(1625,215,'Western'),(1626,128,'Bong'),(1627,128,'Nimba'),(1628,128,'Sino'),(1629,128,'Grand Bassa'),(1630,128,'Grand Cape Mount'),(1631,128,'Maryland'),(1632,128,'Montserrado'),(1633,128,'Bomi'),(1634,128,'Grand Kru'),(1635,128,'Margibi'),(1636,128,'River Cess'),(1637,128,'Grand Gedeh'),(1638,128,'Lofa'),(1639,128,'Gbarpolu'),(1640,128,'River Gee'),(1641,127,'Berea'),(1642,127,'Butha-buthe'),(1643,127,'Leribe'),(1644,127,'Mafeteng'),(1645,127,'Maseru'),(1646,127,'Mohales Hoek'),(1647,127,'Mokhotlong'),(1648,127,'Qachas Nek'),(1649,127,'Quthing'),(1650,127,'Thaba-tseka'),(1651,131,'Alytaus Apskritis'),(1652,131,'Kauno Apskritis'),(1653,131,'Klaipedos Apskritis'),(1654,131,'Marijampoles Apskritis'),(1655,131,'Panevezio Apskritis'),(1656,131,'Siauliu Apskritis'),(1657,131,'Taurages Apskritis'),(1658,131,'Telsiu Apskritis'),(1659,131,'Utenos Apskritis'),(1660,131,'Vilniaus Apskritis'),(1661,132,'Diekirch'),(1662,132,'Grevenmacher'),(1663,132,'Luxembourg'),(1664,125,'Aizkraukles'),(1665,125,'Aluksnes'),(1666,125,'Balvu'),(1667,125,'Bauskas'),(1668,125,'Cesu'),(1669,125,'Daugavpils'),(1670,125,'Dobeles'),(1671,125,'Gulbenes'),(1672,125,'Jekabpils'),(1673,125,'Jelgava'),(1674,125,'Jelgavas'),(1675,125,'Jurmala'),(1676,125,'Kraslavas'),(1677,125,'Kuldigas'),(1678,125,'Liepaja'),(1679,125,'Liepajas'),(1680,125,'Limbazu'),(1681,125,'Ludzas'),(1682,125,'Madonas'),(1683,125,'Ogres'),(1684,125,'Preilu'),(1685,125,'Rezeknes'),(1686,125,'Riga'),(1687,125,'Rigas'),(1688,125,'Saldus'),(1689,125,'Talsu'),(1690,125,'Tukuma'),(1691,125,'Valkas'),(1692,125,'Valmieras'),(1693,125,'Ventspils'),(1694,125,'Adazu'),(1695,125,'Aglonas'),(1696,125,'Aizputes'),(1697,125,'Alojas'),(1698,125,'Babites'),(1699,125,'Baltinavas'),(1700,125,'Beverinas'),(1701,125,'Brocenu'),(1702,125,'Carnikavas'),(1703,125,'Cesvaines'),(1704,125,'Ciblas'),(1705,125,'Dundagas'),(1706,125,'Iecavas'),(1707,125,'Incukalna'),(1708,125,'Jaunjelgavas'),(1709,125,'Jaunpiebalgas'),(1710,125,'Jaunpils'),(1711,125,'Kekavas'),(1712,125,'Kokneses'),(1713,125,'Lubanas'),(1714,125,'Malpils'),(1715,125,'Olaines'),(1716,125,'Ozolnieku'),(1717,125,'Rojas'),(1718,125,'Ropazu'),(1719,125,'Rugaju'),(1720,125,'Rundales'),(1721,125,'Salacgrivas'),(1722,125,'Sejas'),(1723,125,'Siguldas'),(1724,125,'Skrundas'),(1725,125,'Stopinu'),(1726,125,'Strencu'),(1727,125,'Vainodes'),(1728,125,'Varkavas'),(1729,125,'Vecumnieku'),(1730,129,'Al Jabal Al Akhdar'),(1731,129,'Al Jufrah'),(1732,129,'Al Kufrah'),(1733,129,'Al Marj'),(1734,129,'An Nuqat Al Khams'),(1735,129,'Az Zawiyah'),(1736,129,'Benghazi'),(1737,129,'Darnah'),(1738,129,'Ghat'),(1739,129,'Misratah'),(1740,129,'Murzuq'),(1741,129,'Nalut'),(1742,129,'Sabha'),(1743,129,'Surt'),(1744,129,'Tripoli'),(1745,129,'Wadi Ash Shati\''),(1746,129,'Al Butnan'),(1747,129,'Al Jabal Al Gharbi'),(1748,129,'Al Jifarah'),(1749,129,'Al Marqab'),(1750,129,'Al Wahat'),(1751,129,'Wadi Al Hayat'),(1752,153,'Grand Casablanca'),(1753,153,'Fes-boulemane'),(1754,153,'Marrakech-tensift-al Haouz'),(1755,153,'Meknes-tafilalet'),(1756,153,'Rabat-sale-zemmour-zaer'),(1757,153,'Chaouia-ouardigha'),(1758,153,'Doukkala-abda'),(1759,153,'Gharb-chrarda-beni Hssen'),(1760,153,'Guelmim-es Smara'),(1761,153,'Oriental'),(1762,153,'Souss-massa-dr'),(1763,153,'Tadla-azilal'),(1764,153,'Tanger-tetouan'),(1765,153,'Taza-al Hoceima-taounate'),(1766,149,'Monaco'),(1767,148,'Gagauzia'),(1768,148,'Chisinau'),(1769,148,'Stinga Nistrului'),(1770,148,'Anenii Noi'),(1771,148,'Balti'),(1772,148,'Basarabeasca'),(1773,148,'Bender'),(1774,148,'Briceni'),(1775,148,'Cahul'),(1776,148,'Cantemir'),(1777,148,'Calarasi'),(1778,148,'Causeni'),(1779,148,'Cimislia'),(1780,148,'Criuleni'),(1781,148,'Donduseni'),(1782,148,'Drochia'),(1783,148,'Dubasari'),(1784,148,'Edinet'),(1785,148,'Falesti'),(1786,148,'Floresti'),(1787,148,'Glodeni'),(1788,148,'Hincesti'),(1789,148,'Ialoveni'),(1790,148,'Leova'),(1791,148,'Nisporeni'),(1792,148,'Ocnita'),(1793,148,'Orhei'),(1794,148,'Rezina'),(1795,148,'Riscani'),(1796,148,'Singerei'),(1797,148,'Soldanesti'),(1798,148,'Soroca'),(1799,148,'Stefan-voda'),(1800,148,'Straseni'),(1801,148,'Taraclia'),
                (1802,148,'Telenesti'),(1803,148,'Ungheni'),(1804,151,'Opstina Bar'),(1805,151,'Opstina Budva'),(1806,151,'Opstina Cetinje'),(1807,151,'Opstina Danilovgrad'),(1808,151,'Opstina Herceg Novi'),(1809,151,'Opstina Kolasin'),(1810,151,'Opstina Kotor'),(1811,151,'Opstina Mojkovac'),(1812,151,'Opstina Niksic'),(1813,151,'Opstina Podgorica'),(1814,151,'Opstina Tivat'),(1815,151,'Opstina Ulcinj'),(1816,151,'Opstina Zabljak'),(1817,194,'Saint Martin'),(1818,135,'Antsiranana'),(1819,135,'Fianarantsoa'),(1820,135,'Mahajanga'),(1821,135,'Toamasina'),(1822,135,'Antananarivo'),(1823,135,'Toliara'),(1824,141,'Ailinglaplap Atoll'),(1825,141,'Ailuk Atoll'),(1826,141,'Arno Atoll'),(1827,141,'Aur Atoll'),(1828,141,'Ebon Atoll'),(1829,141,'Enewetak Atoll'),(1830,141,'Jabat Island'),(1831,141,'Jaluit Atoll'),(1832,141,'Kili Island'),(1833,141,'Kwajalein Atoll'),(1834,141,'Lae Atoll'),(1835,141,'Lib Island'),(1836,141,'Likiep Atoll'),(1837,141,'Majuro Atoll'),(1838,141,'Maloelap Atoll'),(1839,141,'Mejit Island'),(1840,141,'Mili Atoll'),(1841,141,'Namdrik Atoll'),(1842,141,'Namu Atoll'),(1843,141,'Rongelap Atoll'),(1844,141,'Ujae Atoll'),(1845,141,'Utrik Atoll'),(1846,141,'Wotho Atoll'),(1847,141,'Wotje Atoll'),(1848,134,'Aracinovo'),(1849,134,'Belcista'),(1850,134,'Berovo'),(1851,134,'Bistrica'),(1852,134,'Bitola'),(1853,134,'Blatec'),(1854,134,'Bogdanci'),(1855,134,'Bogomila'),(1856,134,'Bogovinje'),(1857,134,'Bosilovo'),(1858,134,'Brvenica'),(1859,134,'Capari'),(1860,134,'Caska'),(1861,134,'Cegrane'),(1862,134,'Centar'),(1863,134,'Centar Zupa'),(1864,134,'Cesinovo'),(1865,134,'Cucer-sandevo'),(1866,134,'Debar'),(1867,134,'Delcevo'),(1868,134,'Delogozdi'),(1869,134,'Demir Hisar'),(1870,134,'Demir Kapija'),(1871,134,'Dobrusevo'),(1872,134,'Dolna Banjica'),(1873,134,'Dolneni'),(1874,134,'Drugovo'),(1875,134,'Dzepciste'),(1876,134,'Gazi Baba'),(1877,134,'Gevgelija'),(1878,134,'Gostivar'),(1879,134,'Gradsko'),(1880,134,'Ilinden'),(1881,134,'Jegunovce'),(1882,134,'Kamenjane'),(1883,134,'Karbinci'),(1884,134,'Karpos'),(1885,134,'Kavadarci'),(1886,134,'Kicevo'),(1887,134,'Kisela Voda'),(1888,134,'Klecevce'),(1889,134,'Kocani'),(1890,134,'Konce'),(1891,134,'Kondovo'),(1892,134,'Kosel'),(1893,134,'Kratovo'),(1894,134,'Kriva Palanka'),(1895,134,'Krivogastani'),(1896,134,'Krusevo'),(1897,134,'Kuklis'),(1898,134,'Kukurecani'),(1899,134,'Kumanovo'),(1900,134,'Labunista'),(1901,134,'Lipkovo'),(1902,134,'Lozovo'),(1903,134,'Lukovo'),(1904,134,'Makedonska Kamenica'),(1905,134,'Makedonski Brod'),(1906,134,'Meseista'),(1907,134,'Miravci'),(1908,134,'Mogila'),(1909,134,'Murtino'),(1910,134,'Negotino'),(1911,134,'Negotino-polosko'),(1912,134,'Novaci'),(1913,134,'Novo Selo'),(1914,134,'Oblesevo'),(1915,134,'Ohrid'),(1916,134,'Orasac'),(1917,134,'Orizari'),(1918,134,'Oslomej'),(1919,134,'Pehcevo'),(1920,134,'Petrovec'),(1921,134,'Plasnica'),(1922,134,'Podares'),(1923,134,'Prilep'),(1924,134,'Probistip'),(1925,134,'Radovis'),(1926,134,'Rankovce'),(1927,134,'Resen'),(1928,134,'Rosoman'),(1929,134,'Rostusa'),(1930,134,'Samokov'),(1931,134,'Saraj'),(1932,134,'Sipkovica'),(1933,134,'Sopiste'),(1934,134,'Sopotnica'),(1935,134,'Srbinovo'),(1936,134,'Star Dojran'),(1937,134,'Staro Nagoricane'),(1938,134,'Stip'),(1939,134,'Struga'),(1940,134,'Strumica'),(1941,134,'Studenicani'),(1942,134,'Suto Orizari'),(1943,134,'Sveti Nikole'),(1944,134,'Tearce'),(1945,134,'Tetovo'),(1946,134,'Topolcani'),(1947,134,'Valandovo'),(1948,134,'Vasilevo'),(1949,134,'Veles'),(1950,134,'Velesta'),(1951,134,'Vevcani'),(1952,134,'Vinica'),(1953,134,'Vranestica'),(1954,134,'Vrapciste'),(1955,134,'Vratnica'),(1956,134,'Vrutok'),(1957,134,'Zajas'),(1958,134,'Zelenikovo'),(1959,134,'Zelino'),(1960,134,'Zitose'),(1961,134,'Zletovo'),(1962,134,'Zrnovci'),(1963,139,'Bamako'),(1964,139,'Kayes'),(1965,139,'Mopti'),(1966,139,'Segou'),(1967,139,'Sikasso'),(1968,139,'Koulikoro'),(1969,139,'Tombouctou'),(1970,139,'Gao'),(1971,139,'Kidal'),(1972,155,'Rakhine State'),(1973,155,'Chin State'),(1974,155,'Irrawaddy'),(1975,155,'Kachin State'),(1976,155,'Karan State'),(1977,155,'Kayah State'),(1978,155,'Magwe'),(1979,155,'Mandalay'),(1980,155,'Pegu'),(1981,155,'Sagaing'),(1982,155,'Shan State'),(1983,155,'Tenasserim'),(1984,155,'Mon State'),(1985,155,'Yangon'),(1986,150,'Arhangay'),(1987,150,'Bayanhongor'),(1988,150,'Bayan-olgiy'),(1989,150,'Dornod'),(1990,150,'Dornogovi'),(1991,150,'Dundgovi'),(1992,150,'Dzavhan'),(1993,150,'Govi-altay'),(1994,150,'Hentiy'),(1995,150,'Hovd'),(1996,150,'Hovsgol'),(1997,150,'Omnogovi'),(1998,150,'Ovorhangay'),(1999,150,'Selenge'),(2000,150,'Suhbaatar'),(2001,150,'Tov'),
                (2002,150,'Uvs'),(2003,150,'Ulaanbaatar'),(2004,150,'Bulgan'),(2005,150,'Darhan-uul'),(2006,150,'Govisumber'),(2007,150,'Orhon'),(2008,133,'Macau'),(2009,169,'Northern Mariana Islands'),(2010,142,'Martinique'),(2011,143,'Hodh Ech Chargui'),(2012,143,'Hodh El Gharbi'),(2013,143,'Assaba'),(2014,143,'Gorgol'),(2015,143,'Brakna'),(2016,143,'Trarza'),(2017,143,'Adrar'),(2018,143,'Dakhlet Nouadhibou'),(2019,143,'Tagant'),(2020,143,'Guidimaka'),(2021,143,'Tiris Zemmour'),(2022,143,'Inchiri'),(2023,143,'Nouakchott'),(2024,152,'Saint Anthony'),(2025,152,'Saint Peter'),(2026,140,'Malta'),(2027,144,'Mauritius'),(2028,144,'Black River'),(2029,144,'Flacq'),(2030,144,'Grand Port'),(2031,144,'Moka'),(2032,144,'Pamplemousses'),(2033,144,'Plaines Wilhems'),(2034,144,'Port Louis'),(2035,144,'Riviere Du Rempart'),(2036,144,'Savanne'),(2037,138,'Seenu'),(2038,138,'Laamu'),(2039,138,'Alifu'),(2040,138,'Baa'),(2041,138,'Dhaalu'),(2042,138,'Gaafu Dhaalu'),(2043,138,'Haa Alifu'),(2044,138,'Haa Dhaalu'),(2045,138,'Kaafu'),(2046,138,'Maale'),(2047,138,'Meemu'),(2048,138,'Noonu'),(2049,138,'Raa'),(2050,138,'Shaviyani'),(2051,138,'Thaa'),(2052,136,'Chikwawa'),(2053,136,'Chiradzulu'),(2054,136,'Chitipa'),(2055,136,'Thyolo'),(2056,136,'Dedza'),(2057,136,'Dowa'),(2058,136,'Karonga'),(2059,136,'Kasungu'),(2060,136,'Lilongwe'),(2061,136,'Mangochi'),(2062,136,'Mchinji'),(2063,136,'Mzimba'),(2064,136,'Ntcheu'),(2065,136,'Nkhata Bay'),(2066,136,'Nkhotakota'),(2067,136,'Nsanje'),(2068,136,'Ntchisi'),(2069,136,'Rumphi'),(2070,136,'Salima'),(2071,136,'Zomba'),(2072,136,'Blantyre'),(2073,136,'Mwanza'),(2074,136,'Balaka'),(2075,136,'Likoma'),(2076,136,'Machinga'),(2077,136,'Mulanje'),(2078,136,'Phalombe'),(2079,136,'Neno'),(2080,146,'Aguascalientes'),(2081,146,'Baja California'),(2082,146,'Baja California Sur'),(2083,146,'Campeche'),(2084,146,'Chiapas'),(2085,146,'Chihuahua'),(2086,146,'Coahuila De Zaragoza'),(2087,146,'Colima'),(2088,146,'Distrito Federal'),(2089,146,'Durango'),(2090,146,'Guanajuato'),(2091,146,'Guerrero'),(2092,146,'Hidalgo'),(2093,146,'Jalisco'),(2094,146,'Mexico'),(2095,146,'Michoacan De Ocampo'),(2096,146,'Morelos'),(2097,146,'Nayarit'),(2098,146,'Nuevo Leon'),(2099,146,'Oaxaca'),(2100,146,'Puebla'),(2101,146,'Queretaro De Arteaga'),(2102,146,'Quintana Roo'),(2103,146,'San Luis Potosi'),(2104,146,'Sinaloa'),(2105,146,'Sonora'),(2106,146,'Tabasco'),(2107,146,'Tamaulipas'),(2108,146,'Tlaxcala'),(2109,146,'Veracruz-llave'),(2110,146,'Yucatan'),(2111,146,'Zacatecas'),(2112,137,'Johor'),(2113,137,'Kedah'),(2114,137,'Kelantan'),(2115,137,'Melaka'),(2116,137,'Negeri Sembilan'),(2117,137,'Pahang'),(2118,137,'Perak'),(2119,137,'Perlis'),(2120,137,'Pulau Pinang'),(2121,137,'Sarawak'),(2122,137,'Selangor'),(2123,137,'Terengganu'),(2124,137,'Kuala Lumpur'),(2125,137,'Labuan'),(2126,137,'Sabah'),(2127,137,'Putrajaya'),(2128,154,'Cabo Delgado'),(2129,154,'Gaza'),(2130,154,'Inhambane'),(2131,154,'Maputo'),(2132,154,'Sofala'),(2133,154,'Nampula'),(2134,154,'Niassa'),(2135,154,'Tete'),(2136,154,'Zambezia'),(2137,154,'Manica'),(2138,156,'Kaokoland'),(2139,156,'Otjiwarongo'),(2140,156,'Windhoek'),(2141,156,'Caprivi'),(2142,156,'Erongo'),(2143,156,'Hardap'),(2144,156,'Karas'),(2145,156,'Kunene'),(2146,156,'Ohangwena'),(2147,156,'Okavango'),(2148,156,'Omaheke'),(2149,156,'Omusati'),(2150,156,'Oshana'),(2151,156,'Oshikoto'),(2152,156,'Otjozondjupa'),(2153,161,'Province Nord'),(2154,161,'Province Sud'),(2155,161,'Province Des Iles Loyaute'),(2156,164,'Agadez'),(2157,164,'Diffa'),(2158,164,'Dosso'),(2159,164,'Maradi'),(2160,164,'Tahoua'),(2161,164,'Zinder'),(2162,164,'Niamey'),(2163,164,'Tillaberi'),(2164,167,'Norfolk Island'),(2165,165,'Lagos'),(2166,165,'Federal Capital Territory'),(2167,165,'Ogun'),(2168,165,'Akwa Ibom'),(2169,165,'Cross River'),(2170,165,'Kaduna'),(2171,165,'Katsina'),(2172,165,'Anambra'),(2173,165,'Benue'),(2174,165,'Borno'),(2175,165,'Imo'),(2176,165,'Kano'),(2177,165,'Kwara'),(2178,165,'Niger'),(2179,165,'Oyo'),(2180,165,'Adamawa'),(2181,165,'Delta'),(2182,165,'Edo'),(2183,165,'Jigawa'),(2184,165,'Kebbi'),(2185,165,'Kogi'),(2186,165,'Osun'),(2187,165,'Taraba'),(2188,165,'Yobe'),(2189,165,'Abia'),(2190,165,'Bauchi'),(2191,165,'Enugu'),(2192,165,'Ondo'),(2193,165,'Plateau'),(2194,165,'Rivers'),(2195,165,'Sokoto'),(2196,165,'Bayelsa'),(2197,165,'Ebonyi'),(2198,165,'Ekiti'),(2199,165,'Gombe'),(2200,165,'Nassarawa'),(2201,165,'Zamfara'),
                (2202,163,'Boaco'),(2203,163,'Carazo'),(2204,163,'Chinandega'),(2205,163,'Chontales'),(2206,163,'Esteli'),(2207,163,'Granada'),(2208,163,'Jinotega'),(2209,163,'Leon'),(2210,163,'Madriz'),(2211,163,'Managua'),(2212,163,'Masaya'),(2213,163,'Matagalpa'),(2214,163,'Nueva Segovia'),(2215,163,'Rio San Juan'),(2216,163,'Rivas'),(2217,163,'Autonoma Atlantico Norte'),(2218,163,'Region Autonoma Atlantico Sur'),(2219,159,'Drenthe'),(2220,159,'Friesland'),(2221,159,'Gelderland'),(2222,159,'Groningen'),(2223,159,'Limburg'),(2224,159,'Noord-brabant'),(2225,159,'Noord-holland'),(2226,159,'Utrecht'),(2227,159,'Zeeland'),(2228,159,'Zuid-holland'),(2229,159,'Overijssel'),(2230,159,'Flevoland'),(2231,170,'Akershus'),(2232,170,'Aust-agder'),(2233,170,'Buskerud'),(2234,170,'Finnmark'),(2235,170,'Hedmark'),(2236,170,'Hordaland'),(2237,170,'More Og Romsdal'),(2238,170,'Nordland'),(2239,170,'Nord-trondelag'),(2240,170,'Oppland'),(2241,170,'Oslo'),(2242,170,'Ostfold'),(2243,170,'Rogaland'),(2244,170,'Sogn Og Fjordane'),(2245,170,'Sor-trondelag'),(2246,170,'Telemark'),(2247,170,'Troms'),(2248,170,'Vest-agder'),(2249,170,'Vestfold'),(2250,158,'Bagmati'),(2251,158,'Bheri'),(2252,158,'Dhawalagiri'),(2253,158,'Gandaki'),(2254,158,'Janakpur'),(2255,158,'Karnali'),(2256,158,'Kosi'),(2257,158,'Lumbini'),(2258,158,'Mahakali'),(2259,158,'Mechi'),(2260,158,'Narayani'),(2261,158,'Rapti'),(2262,158,'Sagarmatha'),(2263,158,'Seti'),(2264,157,'Yaren'),(2265,166,'Niue'),(2266,162,'Chatham Islands'),(2267,162,'Auckland'),(2268,162,'Bay Of Plenty'),(2269,162,'Canterbury'),(2270,162,'Gisborne'),(2271,162,'Hawke\'s Bay'),(2272,162,'Manawatu-wanganui'),(2273,162,'Marlborough'),(2274,162,'Nelson'),(2275,162,'Northland'),(2276,162,'Otago'),(2277,162,'Southland'),(2278,162,'Taranaki'),(2279,162,'Waikato'),(2280,162,'Wellington'),(2281,162,'West Coast'),(2282,162,'Tasman'),(2283,171,'Ad Dakhiliyah'),(2284,171,'Al Batinah'),(2285,171,'Al Wusta'),(2286,171,'Ash Sharqiyah'),(2287,171,'Az Zahirah'),(2288,171,'Masqat'),(2289,171,'Musandam'),(2290,171,'Zufar'),(2291,171,'Ad Dhahirah'),(2292,171,'Al Buraymi'),(2293,176,'Bocas Del Toro'),(2294,176,'Chiriqui'),(2295,176,'Cocle'),(2296,176,'Colon'),(2297,176,'Darien'),(2298,176,'Herrera'),(2299,176,'Los Santos'),(2300,176,'Panama'),(2301,176,'San Blas'),(2302,176,'Veraguas'),(2303,179,'Amazonas'),(2304,179,'Ancash'),(2305,179,'Apurimac'),(2306,179,'Arequipa'),(2307,179,'Ayacucho'),(2308,179,'Cajamarca'),(2309,179,'Callao'),(2310,179,'Cusco'),(2311,179,'Huancavelica'),(2312,179,'Huanuco'),(2313,179,'Ica'),(2314,179,'Junin'),(2315,179,'La Libertad'),(2316,179,'Lambayeque'),(2317,179,'Lima'),(2318,179,'Loreto'),(2319,179,'Madre De Dios'),(2320,179,'Moquegua'),(2321,179,'Pasco'),(2322,179,'Piura'),(2323,179,'Puno'),(2324,179,'San Martin'),(2325,179,'Tacna'),(2326,179,'Tumbes'),(2327,179,'Ucayali'),(2328,179,'Provincia De Lima'),(2329,83,'Iles Du Vent'),(2330,83,'Iles Sous-le-vent'),(2331,83,'Iles Tuamotu-gambier'),(2332,83,'Iles Marquises'),(2333,83,'Iles Australes'),(2334,177,'Gulf'),(2335,177,'Milne Bay'),(2336,177,'Northern'),(2337,177,'Southern Highlands'),(2338,177,'Western'),(2339,177,'North Solomons'),(2340,177,'Chimbu'),(2341,177,'Eastern Highlands'),(2342,177,'East New Britain'),(2343,177,'East Sepik'),(2344,177,'Madang'),(2345,177,'Manus'),(2346,177,'Morobe'),(2347,177,'New Ireland'),(2348,177,'Western Highlands'),(2349,177,'West New Britain'),(2350,177,'Sandaun'),(2351,177,'Enga'),(2352,177,'National Capital'),(2353,180,'Abra'),(2354,180,'Agusan Del Norte'),(2355,180,'Agusan Del Sur'),(2356,180,'Aklan'),(2357,180,'Albay'),(2358,180,'Antique'),(2359,180,'Bataan'),(2360,180,'Batanes'),(2361,180,'Batangas'),(2362,180,'Benguet'),(2363,180,'Bohol'),(2364,180,'Bukidnon'),(2365,180,'Bulacan'),(2366,180,'Cagayan'),(2367,180,'Camarines Norte'),(2368,180,'Camarines Sur'),(2369,180,'Camiguin'),(2370,180,'Capiz'),(2371,180,'Catanduanes'),(2372,180,'Cavite'),(2373,180,'Cebu'),(2374,180,'Basilan'),(2375,180,'Eastern Samar'),(2376,180,'Davao'),(2377,180,'Davao Del Sur'),(2378,180,'Davao Oriental'),(2379,180,'Ifugao'),(2380,180,'Ilocos Norte'),(2381,180,'Ilocos Sur'),(2382,180,'Iloilo'),(2383,180,'Isabela'),(2384,180,'Kalinga-apayao'),(2385,180,'Laguna'),(2386,180,'Lanao Del Norte'),(2387,180,'Lanao Del Sur'),(2388,180,'La Union'),(2389,180,'Leyte'),(2390,180,'Marinduque'),(2391,180,'Masbate'),(2392,180,'Mindoro Occidental'),(2393,180,'Mindoro Oriental'),(2394,180,'Misamis Occidental'),(2395,180,'Misamis Oriental'),(2396,180,'Mountain'),(2397,180,'Negros Oriental'),(2398,180,'Nueva Ecija'),(2399,180,'Nueva Vizcaya'),(2400,180,'Palawan'),(2401,180,'Pampanga'),
                (2402,180,'Pangasinan'),(2403,180,'Rizal'),(2404,180,'Romblon'),(2405,180,'Samar'),(2406,180,'Maguindanao'),(2407,180,'North Cotabato'),(2408,180,'Sorsogon'),(2409,180,'Southern Leyte'),(2410,180,'Sulu'),(2411,180,'Surigao Del Norte'),(2412,180,'Surigao Del Sur'),(2413,180,'Tarlac'),(2414,180,'Zambales'),(2415,180,'Zamboanga Del Norte'),(2416,180,'Zamboanga Del Sur'),(2417,180,'Northern Samar'),(2418,180,'Quirino'),(2419,180,'Siquijor'),(2420,180,'South Cotabato'),(2421,180,'Sultan Kudarat'),(2422,180,'Tawitawi'),(2423,180,'Angeles'),(2424,180,'Bacolod'),(2425,180,'Baguio'),(2426,180,'Batangas City'),(2427,180,'Butuan'),(2428,180,'Cabanatuan'),(2429,180,'Cadiz'),(2430,180,'Cagayan De Oro'),(2431,180,'Calbayog'),(2432,180,'Caloocan'),(2433,180,'Canlaon'),(2434,180,'Cavite City'),(2435,180,'Cotabato'),(2436,180,'Danao'),(2437,180,'Dapitan'),(2438,180,'Davao City'),(2439,180,'Dipolog'),(2440,180,'Dumaguete'),(2441,180,'General Santos'),(2442,180,'Gingoog'),(2443,180,'Iligan'),(2444,180,'Iloilo City'),(2445,180,'Iriga'),(2446,180,'La Carlota'),(2447,180,'Laoag'),(2448,180,'Lapu-lapu'),(2449,180,'Legaspi'),(2450,180,'Lipa'),(2451,180,'Lucena'),(2452,180,'Mandaue'),(2453,180,'Manila'),(2454,180,'Marawi'),(2455,180,'Naga'),(2456,180,'Olongapo'),(2457,180,'Ormoc'),(2458,180,'Oroquieta'),(2459,180,'Ozamis'),(2460,180,'Pagadian'),(2461,180,'Palayan'),(2462,180,'Puerto Princesa'),(2463,180,'Roxas'),(2464,180,'San Carlos'),(2465,180,'San Pablo'),(2466,180,'Silay'),(2467,180,'Surigao'),(2468,180,'Tacloban'),(2469,180,'Tagaytay'),(2470,180,'Tagbilaran'),(2471,180,'Tangub'),(2472,180,'Toledo'),(2473,180,'Trece Martires'),(2474,180,'Zamboanga'),(2475,180,'Aurora'),(2476,180,'Quezon'),(2477,180,'Negros Occidental'),(2478,173,'Federally Administered Tribal Areas'),(2479,173,'Balochistan'),(2480,173,'North-west Frontier'),(2481,173,'Punjab'),(2482,173,'Sindh'),(2483,173,'Azad Kashmir'),(2484,173,'Northern Areas'),(2485,173,'Islamabad'),(2486,182,'Dolnoslaskie'),(2487,182,'Kujawsko-pomorskie'),(2488,182,'Lodzkie'),(2489,182,'Lubelskie'),(2490,182,'Lubuskie'),(2491,182,'Malopolskie'),(2492,182,'Mazowieckie'),(2493,182,'Opolskie'),(2494,182,'Podkarpackie'),(2495,182,'Podlaskie'),(2496,182,'Pomorskie'),(2497,182,'Slaskie'),(2498,182,'Swietokrzyskie'),(2499,182,'Warminsko-mazurskie'),(2500,182,'Wielkopolskie'),(2501,182,'Zachodniopomorskie'),(2502,195,'Saint Pierre And Miquelon'),(2503,181,'Pitcairn Islands'),(2504,184,'Adjuntas'),(2505,184,'Aguada'),(2506,184,'Aguadilla'),(2507,184,'Aguas Buenas'),(2508,184,'Aibonito'),(2509,184,'Anasco'),(2510,184,'Arecibo'),(2511,184,'Arroyo'),(2512,184,'Barceloneta'),(2513,184,'Barranquitas'),(2514,184,'Bayamon'),(2515,184,'Cabo Rojo'),(2516,184,'Caguas'),(2517,184,'Camuy'),(2518,184,'Canovanas'),(2519,184,'Carolina'),(2520,184,'Catano'),(2521,184,'Cayey'),(2522,184,'Ceiba'),(2523,184,'Ciales'),(2524,184,'Cidra'),(2525,184,'Coamo'),(2526,184,'Comerio'),(2527,184,'Corozal'),(2528,184,'Culebra'),(2529,184,'Dorado'),(2530,184,'Fajardo'),(2531,184,'Florida'),(2532,184,'Guanica'),(2533,184,'Guayama'),(2534,184,'Guayanilla'),(2535,184,'Guaynabo'),(2536,184,'Gurabo'),(2537,184,'Hatillo'),(2538,184,'Hormigueros'),(2539,184,'Humacao'),(2540,184,'Isabela'),(2541,184,'Municipio De Jayuya'),(2542,184,'Juana Diaz'),(2543,184,'Municipio De Juncos'),(2544,184,'Lajas'),(2545,184,'Lares'),(2546,184,'Las Marias'),(2547,184,'Las Piedras'),(2548,184,'Loiza'),(2549,184,'Luquillo'),(2550,184,'Manati'),(2551,184,'Maricao'),(2552,184,'Maunabo'),(2553,184,'Mayaguez'),(2554,184,'Moca'),(2555,184,'Morovis'),(2556,184,'Naguabo'),(2557,184,'Naranjito'),(2558,184,'Patillas'),(2559,184,'Penuelas'),(2560,184,'Ponce'),(2561,184,'Quebradillas'),(2562,184,'Rincon'),(2563,184,'Rio Grande'),(2564,184,'Sabana Grande'),(2565,184,'Salinas'),(2566,184,'San German'),(2567,184,'San Juan'),(2568,184,'San Lorenzo'),(2569,184,'San Sebastian'),(2570,184,'Santa Isabel Municipio'),(2571,184,'Toa Alta'),(2572,184,'Toa Baja'),(2573,184,'Trujillo Alto'),(2574,184,'Utuado'),(2575,184,'Vega Alta'),(2576,184,'Vega Baja'),(2577,184,'Vieques'),(2578,184,'Villalba'),(2579,184,'Yabucoa'),(2580,184,'Yauco'),(2581,175,'Gaza'),(2582,175,'West Bank'),(2583,183,'Aveiro'),(2584,183,'Beja'),(2585,183,'Braga'),(2586,183,'Braganca'),(2587,183,'Castelo Branco'),(2588,183,'Coimbra'),(2589,183,'Evora'),(2590,183,'Faro'),(2591,183,'Madeira'),(2592,183,'Guarda'),(2593,183,'Leiria'),(2594,183,'Lisboa'),(2595,183,'Portalegre'),(2596,183,'Porto'),(2597,183,'Santarem'),(2598,183,'Setubal'),(2599,183,'Viana Do Castelo'),(2600,183,'Vila Real'),(2601,183,'Viseu'),
                (2602,183,'Azores'),(2603,174,'Aimeliik'),(2604,174,'Airai'),(2605,174,'Angaur'),(2606,174,'Kayangel'),(2607,174,'Koror'),(2608,174,'Melekeok'),(2609,174,'Ngaraard'),(2610,174,'Ngarchelong'),(2611,174,'Ngardmau'),(2612,174,'Ngatpang'),(2613,174,'Ngiwal'),(2614,174,'Peleliu'),(2615,178,'Alto Parana'),(2616,178,'Amambay'),(2617,178,'Caaguazu'),(2618,178,'Caazapa'),(2619,178,'Central'),(2620,178,'Concepcion'),(2621,178,'Cordillera'),(2622,178,'Guaira'),(2623,178,'Itapua'),(2624,178,'Misiones'),(2625,178,'Neembucu'),(2626,178,'Paraguari'),(2627,178,'Presidente Hayes'),(2628,178,'San Pedro'),(2629,178,'Canindeyu'),(2630,178,'Asuncion'),(2631,178,'Alto Paraguay'),(2632,178,'Boqueron'),(2633,185,'Ad Dawhah'),(2634,185,'Al Khawr'),(2635,185,'Ar Rayyan'),(2636,185,'Madinat Ach Shamal'),(2637,185,'Umm Salal'),(2638,185,'Al Wakrah'),(2639,185,'Az Za\'ayin'),(2640,186,'Reunion'),(2641,187,'Alba'),(2642,187,'Arad'),(2643,187,'Arges'),(2644,187,'Bacau'),(2645,187,'Bihor'),(2646,187,'Bistrita-nasaud'),(2647,187,'Botosani'),(2648,187,'Braila'),(2649,187,'Brasov'),(2650,187,'Bucuresti'),(2651,187,'Buzau'),(2652,187,'Caras-severin'),(2653,187,'Cluj'),(2654,187,'Constanta'),(2655,187,'Covasna'),(2656,187,'Dambovita'),(2657,187,'Dolj'),(2658,187,'Galati'),(2659,187,'Gorj'),(2660,187,'Harghita'),(2661,187,'Hunedoara'),(2662,187,'Ialomita'),(2663,187,'Iasi'),(2664,187,'Maramures'),(2665,187,'Mehedinti'),(2666,187,'Mures'),(2667,187,'Neamt'),(2668,187,'Olt'),(2669,187,'Prahova'),(2670,187,'Salaj'),(2671,187,'Satu Mare'),(2672,187,'Sibiu'),(2673,187,'Suceava'),(2674,187,'Teleorman'),(2675,187,'Timis'),(2676,187,'Tulcea'),(2677,187,'Vaslui'),(2678,187,'Valcea'),(2679,187,'Vrancea'),(2680,187,'Calarasi'),(2681,187,'Giurgiu'),(2682,187,'Ilfov'),(2683,202,'Central Serbia'),(2684,202,'Kosovo'),(2685,202,'Vojvodina'),(2686,188,'Adygeya'),(2687,188,'Gorno-altay'),(2688,188,'Altaisky Krai'),(2689,188,'Amur'),(2690,188,'Arkhangelsk'),(2691,188,'Astrakhan\''),(2692,188,'Bashkortostan'),(2693,188,'Belgorod'),(2694,188,'Bryansk'),(2695,188,'Buryat'),(2696,188,'Chechnya'),(2697,188,'Chelyabinsk'),(2698,188,'Chukot'),(2699,188,'Chuvashia'),(2700,188,'Dagestan'),(2701,188,'Ingush'),(2702,188,'Irkutsk'),(2703,188,'Ivanovo'),(2704,188,'Kabardin-balkar'),(2705,188,'Kaliningrad'),(2706,188,'Kalmyk'),(2707,188,'Kaluga'),(2708,188,'Karachay-cherkess'),(2709,188,'Karelia'),(2710,188,'Kemerovo'),(2711,188,'Khabarovsk'),(2712,188,'Khakass'),(2713,188,'Khanty-mansiy'),(2714,188,'Kirov'),(2715,188,'Komi'),(2716,188,'Kostroma'),(2717,188,'Krasnodar'),(2718,188,'Kurgan'),(2719,188,'Kursk'),(2720,188,'Leningrad'),(2721,188,'Lipetsk'),(2722,188,'Magadan'),(2723,188,'Mariy-el'),(2724,188,'Mordovia'),(2725,188,'Moskva'),(2726,188,'Moscow City'),(2727,188,'Murmansk'),(2728,188,'Nenets'),(2729,188,'Nizhegorod'),(2730,188,'Novgorod'),(2731,188,'Novosibirsk'),(2732,188,'Omsk'),(2733,188,'Orenburg'),(2734,188,'Orel'),(2735,188,'Penza'),(2736,188,'Perm\''),(2737,188,'Primor\'ye'),(2738,188,'Pskov'),(2739,188,'Rostov'),(2740,188,'Ryazan\''),(2741,188,'Sakha'),(2742,188,'Sakhalin'),(2743,188,'Samara'),(2744,188,'Saint Petersburg City'),(2745,188,'Saratov'),(2746,188,'North Ossetia'),(2747,188,'Smolensk'),(2748,188,'Stavropol\''),(2749,188,'Sverdlovsk'),(2750,188,'Tambovskaya Oblast'),(2751,188,'Tatarstan'),(2752,188,'Tomsk'),(2753,188,'Tula'),(2754,188,'Tver\''),(2755,188,'Tyumen\''),(2756,188,'Tuva'),(2757,188,'Udmurt'),(2758,188,'Ul\'yanovsk'),(2759,188,'Vladimir'),(2760,188,'Volgograd'),(2761,188,'Vologda'),(2762,188,'Voronezh'),(2763,188,'Yamal-nenets'),(2764,188,'Yaroslavl\''),(2765,188,'Yevrey'),(2766,188,'Permskiy Kray'),(2767,188,'Krasnoyarskiy Kray'),(2768,188,'Kamchatka'),(2769,188,'Zabaykalsky'),(2770,189,'Est'),(2771,189,'Kigali'),(2772,189,'Nord'),(2773,189,'Ouest'),(2774,189,'Sud'),(2775,200,'Al Bahah'),(2776,200,'Al Madinah'),(2777,200,'Ash Sharqiyah'),(2778,200,'Al Qasim'),(2779,200,'Ar Riyad'),(2780,200,'Asir Province'),(2781,200,'Ha\'il'),(2782,200,'Makkah'),(2783,200,'Al Hudud Ash Shamaliyah'),(2784,200,'Najran'),(2785,200,'Jizan'),(2786,200,'Tabuk'),(2787,200,'Al Jawf'),(2788,209,'Malaita'),(2789,209,'Guadalcanal'),(2790,209,'Isabel'),(2791,209,'Makira'),(2792,209,'Central'),(2793,209,'Western'),(2794,204,'English River'),(2795,216,'Khartoum'),(2796,216,'Red Sea'),(2797,216,'Gezira'),(2798,216,'Gedarif'),(2799,216,'White Nile'),(2800,216,'Blue Nile'),(2801,216,'Northern'),
                (2802,216,'West Darfur'),(2803,216,'South Darfur'),(2804,216,'South Kordufan'),(2805,216,'Kassala'),(2806,216,'River Nile'),(2807,216,'North Darfur'),(2808,216,'North Kordufan'),(2809,216,'Sennar'),(2810,220,'Blekinge Lan'),(2811,220,'Gavleborgs Lan'),(2812,220,'Gotlands Lan'),(2813,220,'Hallands Lan'),(2814,220,'Jamtlands Lan'),(2815,220,'Jonkopings Lan'),(2816,220,'Kalmar Lan'),(2817,220,'Dalarnas Lan'),(2818,220,'Kronobergs Lan'),(2819,220,'Norrbottens Lan'),(2820,220,'Orebro Lan'),(2821,220,'Ostergotlands Lan'),(2822,220,'Sodermanlands Lan'),(2823,220,'Uppsala Lan'),(2824,220,'Varmlands Lan'),(2825,220,'Vasterbottens Lan'),(2826,220,'Vasternorrlands Lan'),(2827,220,'Vastmanlands Lan'),(2828,220,'Stockholms Lan'),(2829,220,'Skane Lan'),(2830,220,'Vastra Gotaland'),(2831,206,'Singapore'),(2832,191,'Ascension'),(2833,191,'Saint Helena'),(2834,191,'Tristan Da Cunha'),(2835,208,'Ajdovscina'),(2836,208,'Bled'),(2837,208,'Bohinj'),(2838,208,'Borovnica'),(2839,208,'Bovec'),(2840,208,'Brezice'),(2841,208,'Brezovica'),(2842,208,'Celje'),(2843,208,'Cerknica'),(2844,208,'Cerkno'),(2845,208,'Crensovci'),(2846,208,'Crnomelj'),(2847,208,'Divaca'),(2848,208,'Dravograd'),(2849,208,'Gornja Radgona'),(2850,208,'Grosuplje'),(2851,208,'Hrastnik'),(2852,208,'Idrija'),(2853,208,'Ig'),(2854,208,'Ilirska Bistrica'),(2855,208,'Ivancna Gorica'),(2856,208,'Izola-isola'),(2857,208,'Kanal'),(2858,208,'Kidricevo'),(2859,208,'Kobarid'),(2860,208,'Koper-capodistria'),(2861,208,'Kranj'),(2862,208,'Kranjska Gora'),(2863,208,'Krsko'),(2864,208,'Lasko'),(2865,208,'Ljubljana'),(2866,208,'Logatec'),(2867,208,'Medvode'),(2868,208,'Menges'),(2869,208,'Metlika'),(2870,208,'Mezica'),(2871,208,'Mislinja'),(2872,208,'Mozirje'),(2873,208,'Murska Sobota'),(2874,208,'Muta'),(2875,208,'Nova Gorica'),(2876,208,'Odranci'),(2877,208,'Ormoz'),(2878,208,'Pivka'),(2879,208,'Postojna'),(2880,208,'Racam'),(2881,208,'Radece'),(2882,208,'Radenci'),(2883,208,'Radlje Ob Dravi'),(2884,208,'Radovljica'),(2885,208,'Rogaska Slatina'),(2886,208,'Sencur'),(2887,208,'Sentilj'),(2888,208,'Sevnica'),(2889,208,'Sezana'),(2890,208,'Skofja Loka'),(2891,208,'Skofljica'),(2892,208,'Slovenj Gradec'),(2893,208,'Slovenske Konjice'),(2894,208,'Sostanj'),(2895,208,'Store'),(2896,208,'Tolmin'),(2897,208,'Trbovlje'),(2898,208,'Trebnje'),(2899,208,'Trzic'),(2900,208,'Turnisce'),(2901,208,'Velenje'),(2902,208,'Vipava'),(2903,208,'Vodice'),(2904,208,'Vrhnika'),(2905,208,'Vuzenica'),(2906,208,'Zagorje Ob Savi'),(2907,208,'Zelezniki'),(2908,208,'Ziri'),(2909,208,'Zrece'),(2910,208,'Destrnik'),(2911,208,'Domzale'),(2912,208,'Hoce-slivnica'),(2913,208,'Horjul'),(2914,208,'Jesenice'),(2915,208,'Kamnik'),(2916,208,'Kocevje'),(2917,208,'Lenart'),(2918,208,'Lendava'),(2919,208,'Litija'),(2920,208,'Ljutomer'),(2921,208,'Lovrenc Na Pohorju'),(2922,208,'Maribor'),(2923,208,'Miklavz Na Dravskem Polju'),(2924,208,'Miren-kostanjevica'),(2925,208,'Novo Mesto'),(2926,208,'Oplotnica'),(2927,208,'Piran'),(2928,208,'Polzela'),(2929,208,'Prebold'),(2930,208,'Prevalje'),(2931,208,'Ptuj'),(2932,208,'Ravne Na Koroskem'),(2933,208,'Ribnica'),(2934,208,'Ruse'),(2935,208,'Sempeter-vrtojba'),(2936,208,'Sentjur Pri Celju'),(2937,208,'Slovenska Bistrica'),(2938,208,'Trzin'),(2939,208,'Vojnik'),(2940,208,'Zalec'),(2941,208,'Zuzemberk'),(2942,208,'Log-dragomer'),(2943,208,'Poljcane'),(2944,208,'Straza'),(2945,218,'Svalbard And Jan Mayen'),(2946,207,'Banska Bystrica'),(2947,207,'Bratislava'),(2948,207,'Kosice'),(2949,207,'Nitra'),(2950,207,'Presov'),(2951,207,'Trencin'),(2952,207,'Trnava'),(2953,207,'Zilina'),(2954,205,'Eastern'),(2955,205,'Northern'),(2956,205,'Southern'),(2957,205,'Western Area'),(2958,198,'Acquaviva'),(2959,198,'Chiesanuova'),(2960,198,'San Marino'),(2961,198,'Serravalle'),(2962,201,'Dakar'),(2963,201,'Diourbel'),(2964,201,'Tambacounda'),(2965,201,'Thies'),(2966,201,'Fatick'),(2967,201,'Kaolack'),(2968,201,'Kolda'),(2969,201,'Ziguinchor'),(2970,201,'Louga'),(2971,201,'Saint-louis'),(2972,201,'Matam'),(2973,201,'Kaffrine'),(2974,201,'Kedougou'),(2975,201,'Sedhiou'),(2976,210,'Bakool'),(2977,210,'Banaadir'),(2978,210,'Bari'),(2979,210,'Bay'),(2980,210,'Galguduud'),(2981,210,'Gedo'),(2982,210,'Hiiraan'),(2983,210,'Jubbada Dhexe'),(2984,210,'Jubbada Hoose'),(2985,210,'Mudug'),(2986,210,'Sanaag'),(2987,210,'Shabeellaha Dhexe'),(2988,210,'Shabeellaha Hoose'),(2989,210,'Nugaal'),(2990,210,'Togdheer'),(2991,210,'Woqooyi Galbeed'),(2992,210,'Awdal'),(2993,210,'Sool'),(2994,217,'Brokopondo'),(2995,217,'Commewijne'),(2996,217,'Coronie'),(2997,217,'Marowijne'),(2998,217,'Nickerie'),(2999,217,'Para'),(3000,217,'Paramaribo'),
                (3001,217,'Saramacca'),(3002,217,'Wanica'),(3003,199,'Principe'),(3004,199,'Sao Tome'),(3005,71,'Ahuachapan'),(3006,71,'Cabanas'),(3007,71,'Chalatenango'),(3008,71,'Cuscatlan'),(3009,71,'La Libertad'),(3010,71,'La Paz'),(3011,71,'La Union'),(3012,71,'Morazan'),(3013,71,'San Miguel'),(3014,71,'San Salvador'),(3015,71,'Santa Ana'),(3016,71,'San Vicente'),(3017,71,'Sonsonate'),(3018,71,'Usulutan'),(3019,222,'Al Hasakah'),(3020,222,'Al Ladhiqiyah'),(3021,222,'Al Qunaytirah'),(3022,222,'Ar Raqqah'),(3023,222,'As Suwayda\''),(3024,222,'Dar'),(3025,222,'Dayr Az Zawr'),(3026,222,'Rif Dimashq'),(3027,222,'Halab'),(3028,222,'Hamah'),(3029,222,'Hims'),(3030,222,'Idlib'),(3031,222,'Dimashq'),(3032,222,'Tartus'),(3033,219,'Hhohho'),(3034,219,'Lubombo'),(3035,219,'Manzini'),(3036,219,'Shiselweni'),(3037,236,'Turks And Caicos Islands'),(3038,47,'Batha'),(3039,47,'Wadi Fira'),(3040,47,'Guera'),(3041,47,'Kanem'),(3042,47,'Lac'),(3043,47,'Logone Occidental'),(3044,47,'Logone Oriental'),(3045,47,'Ouaddai'),(3046,47,'Salamat'),(3047,47,'Tandjile'),(3048,47,'Chari-baguirmi'),(3049,47,'Mayo-kebbi Est'),(3050,47,'Moyen-chari'),(3051,47,'Hadjer-lamis'),(3052,47,'Mandoul'),(3053,47,'Mayo-kebbi Ouest'),(3054,47,'Barh El Ghazel'),(3055,47,'Borkou'),(3056,47,'Tibesti'),(3057,84,'French Southern And Antarctic Lands'),(3058,228,'Centrale'),(3059,228,'Kara'),(3060,228,'Maritime'),(3061,228,'Plateaux'),(3062,228,'Savanes'),(3063,226,'Mae Hong Son'),(3064,226,'Chiang Mai'),(3065,226,'Chiang Rai'),(3066,226,'Nan'),(3067,226,'Lamphun'),(3068,226,'Lampang'),(3069,226,'Phrae'),(3070,226,'Tak'),(3071,226,'Sukhothai'),(3072,226,'Uttaradit'),(3073,226,'Kamphaeng Phet'),(3074,226,'Phitsanulok'),(3075,226,'Phichit'),(3076,226,'Phetchabun'),(3077,226,'Uthai Thani'),(3078,226,'Nakhon Sawan'),(3079,226,'Nong Khai'),(3080,226,'Loei'),(3081,226,'Sakon Nakhon'),(3082,226,'Khon Kaen'),(3083,226,'Kalasin'),(3084,226,'Maha Sarakham'),(3085,226,'Roi Et'),(3086,226,'Chaiyaphum'),(3087,226,'Nakhon Ratchasima'),(3088,226,'Buriram'),(3089,226,'Surin'),(3090,226,'Sisaket'),(3091,226,'Narathiwat'),(3092,226,'Chai Nat'),(3093,226,'Sing Buri'),(3094,226,'Lop Buri'),(3095,226,'Ang Thong'),(3096,226,'Phra Nakhon Si Ayutthaya'),(3097,226,'Saraburi'),(3098,226,'Nonthaburi'),(3099,226,'Pathum Thani'),(3100,226,'Krung Thep'),(3101,226,'Phayao'),(3102,226,'Samut Prakan'),(3103,226,'Nakhon Nayok'),(3104,226,'Chachoengsao'),(3105,226,'Chon Buri'),(3106,226,'Rayong'),(3107,226,'Chanthaburi'),(3108,226,'Trat'),(3109,226,'Kanchanaburi'),(3110,226,'Suphan Buri'),(3111,226,'Ratchaburi'),(3112,226,'Nakhon Pathom'),(3113,226,'Samut Songkhram'),(3114,226,'Samut Sakhon'),(3115,226,'Phetchaburi'),(3116,226,'Prachuap Khiri Khan'),(3117,226,'Chumphon'),(3118,226,'Ranong'),(3119,226,'Surat Thani'),(3120,226,'Phangnga'),(3121,226,'Phuket'),(3122,226,'Krabi'),(3123,226,'Nakhon Si Thammarat'),(3124,226,'Trang'),(3125,226,'Phatthalung'),(3126,226,'Satun'),(3127,226,'Songkhla'),(3128,226,'Pattani'),(3129,226,'Yala'),(3130,226,'Yasothon'),(3131,226,'Nakhon Phanom'),(3132,226,'Prachin Buri'),(3133,226,'Ubon Ratchathani'),(3134,226,'Udon Thani'),(3135,226,'Amnat Charoen'),(3136,226,'Mukdahan'),(3137,226,'Nong Bua Lamphu'),(3138,226,'Sa Kaeo'),(3139,224,'Regions Of Republican Subordination'),(3140,224,'Kuhistoni Badakhshon'),(3141,224,'Khatlon'),(3142,224,'Sughd'),(3143,224,'Tajikistan'),(3144,229,'Tokelau'),(3145,227,'Timor-leste'),(3146,235,'Ahal'),(3147,235,'Balkan'),(3148,235,'Dashoguz'),(3149,235,'Lebap'),(3150,235,'Mary'),(3151,233,'Kasserine'),(3152,233,'Kairouan'),(3153,233,'Jendouba'),(3154,233,'El Kef'),(3155,233,'Al Mahdia'),(3156,233,'Al Munastir'),(3157,233,'Bajah'),(3158,233,'Bizerte'),(3159,233,'Nabeul'),(3160,233,'Siliana'),(3161,233,'Sousse'),(3162,233,'Ben Arous'),(3163,233,'Madanin'),(3164,233,'Gabes'),(3165,233,'Gafsa'),(3166,233,'Kebili'),(3167,233,'Sfax'),(3168,233,'Sidi Bou Zid'),(3169,233,'Tataouine'),(3170,233,'Tozeur'),(3171,233,'Tunis'),(3172,233,'Zaghouan'),(3173,233,'Aiana'),(3174,233,'Manouba'),(3175,230,'Ha'),(3176,230,'Tongatapu'),(3177,230,'Vava'),(3178,234,'Adiyaman'),(3179,234,'Afyonkarahisar'),(3180,234,'Agri'),(3181,234,'Amasya'),(3182,234,'Antalya'),(3183,234,'Artvin'),(3184,234,'Aydin'),(3185,234,'Balikesir'),(3186,234,'Bilecik'),(3187,234,'Bingol'),(3188,234,'Bitlis'),(3189,234,'Bolu'),(3190,234,'Burdur'),(3191,234,'Bursa'),(3192,234,'Canakkale'),(3193,234,'Corum'),(3194,234,'Denizli'),(3195,234,'Diyarbakir'),(3196,234,'Edirne'),(3197,234,'Elazig'),(3198,234,'Erzincan'),(3199,234,'Erzurum'),(3200,234,'Eskisehir'),(3201,234,'Giresun'),(3202,234,'Hatay'),
                (3203,234,'Mersin'),(3204,234,'Isparta'),(3205,234,'Istanbul'),(3206,234,'Izmir'),(3207,234,'Kastamonu'),(3208,234,'Kayseri'),(3209,234,'Kirklareli'),(3210,234,'Kirsehir'),(3211,234,'Kocaeli'),(3212,234,'Kutahya'),(3213,234,'Malatya'),(3214,234,'Manisa'),(3215,234,'Kahramanmaras'),(3216,234,'Mugla'),(3217,234,'Mus'),(3218,234,'Nevsehir'),(3219,234,'Ordu'),(3220,234,'Rize'),(3221,234,'Sakarya'),(3222,234,'Samsun'),(3223,234,'Sinop'),(3224,234,'Sivas'),(3225,234,'Tekirdag'),(3226,234,'Tokat'),(3227,234,'Trabzon'),(3228,234,'Tunceli'),(3229,234,'Sanliurfa'),(3230,234,'Usak'),(3231,234,'Van'),(3232,234,'Yozgat'),(3233,234,'Ankara'),(3234,234,'Gumushane'),(3235,234,'Hakkari'),(3236,234,'Konya'),(3237,234,'Mardin'),(3238,234,'Nigde'),(3239,234,'Siirt'),(3240,234,'Aksaray'),(3241,234,'Batman'),(3242,234,'Bayburt'),(3243,234,'Karaman'),(3244,234,'Kirikkale'),(3245,234,'Sirnak'),(3246,234,'Adana'),(3247,234,'Cankiri'),(3248,234,'Gaziantep'),(3249,234,'Kars'),(3250,234,'Zonguldak'),(3251,234,'Ardahan'),(3252,234,'Bartin'),(3253,234,'Igdir'),(3254,234,'Karabuk'),(3255,234,'Kilis'),(3256,234,'Osmaniye'),(3257,234,'Yalova'),(3258,234,'Duzce'),(3259,231,'Arima'),(3260,231,'Caroni'),(3261,231,'Mayaro'),(3262,231,'Port-of-spain'),(3263,231,'Saint Andrew'),(3264,231,'Saint George'),(3265,231,'San Fernando'),(3266,231,'Tobago'),(3267,231,'Victoria'),(3268,231,'Trinidad And Tobago'),(3269,237,'Tuvalu'),(3270,223,'Fu-chien'),(3271,223,'Kao-hsiung'),(3272,223,'T\'ai-pei'),(3273,223,'T\'ai-wan'),(3274,225,'Pwani'),(3275,225,'Dodoma'),(3276,225,'Iringa'),(3277,225,'Kigoma'),(3278,225,'Kilimanjaro'),(3279,225,'Lindi'),(3280,225,'Mara'),(3281,225,'Mbeya'),(3282,225,'Morogoro'),(3283,225,'Mtwara'),(3284,225,'Mwanza'),(3285,225,'Pemba North'),(3286,225,'Ruvuma'),(3287,225,'Shinyanga'),(3288,225,'Singida'),(3289,225,'Tabora'),(3290,225,'Tanga'),(3291,225,'Kagera'),(3292,225,'Pemba South'),(3293,225,'Zanzibar Central'),(3294,225,'Zanzibar North'),(3295,225,'Dar Es Salaam'),(3296,225,'Rukwa'),(3297,225,'Zanzibar Urban'),(3298,225,'Arusha'),(3299,225,'Manyara'),(3300,241,'Cherkas\'ka Oblast\''),(3301,241,'Chernihivs\'ka Oblast\''),(3302,241,'Chernivets\'ka Oblast\''),(3303,241,'Dnipropetrovs\'ka Oblast\''),(3304,241,'Donets\'ka Oblast\''),(3305,241,'Ivano-frankivs\'ka Oblast\''),(3306,241,'Kharkivs\'ka Oblast\''),(3307,241,'Khersons\'ka Oblast\''),(3308,241,'Khmel\'nyts\'ka Oblast\''),(3309,241,'Kirovohrads\'ka Oblast\''),(3310,241,'Krym'),(3311,241,'Kyyiv'),(3312,241,'Kyyivs\'ka Oblast\''),(3313,241,'Luhans\'ka Oblast\''),(3314,241,'L\'vivs\'ka Oblast\''),(3315,241,'Mykolayivs\'ka Oblast\''),(3316,241,'Odes\'ka Oblast\''),(3317,241,'Poltavs\'ka Oblast\''),(3318,241,'Rivnens\'ka Oblast\''),(3319,241,'Sevastopol\''),(3320,241,'Sums\'ka Oblast\''),(3321,241,'Ternopil\'s\'ka Oblast\''),(3322,241,'Vinnyts\'ka Oblast\''),(3323,241,'Volyns\'ka Oblast\''),(3324,241,'Zakarpats\'ka Oblast\''),(3325,241,'Zaporiz\'ka Oblast\''),(3326,241,'Zhytomyrs\'ka Oblast\''),(3327,240,'Kampala District'),(3328,240,'Apac'),(3329,240,'Bundibugyo'),(3330,240,'Bushenyi'),(3331,240,'Gulu'),(3332,240,'Hoima'),(3333,240,'Jinja'),(3334,240,'Kabale'),(3335,240,'Kalangala'),(3336,240,'Kampala'),(3337,240,'Kamuli'),(3338,240,'Kapchorwa'),(3339,240,'Kasese'),(3340,240,'Kibale'),(3341,240,'Kiboga'),(3342,240,'Kisoro'),(3343,240,'Kotido'),(3344,240,'Kumi'),(3345,240,'Lira'),(3346,240,'Masindi'),(3347,240,'Mbarara'),(3348,240,'Mubende'),(3349,240,'Nebbi'),(3350,240,'Ntungamo'),(3351,240,'Pallisa'),(3352,240,'Rakai'),(3353,240,'Adjumani'),(3354,240,'Bugiri'),(3355,240,'Busia'),(3356,240,'Katakwi'),(3357,240,'Luwero'),(3358,240,'Masaka'),(3359,240,'Moyo'),(3360,240,'Nakasongola'),(3361,240,'Sembabule'),(3362,240,'Tororo'),(3363,240,'Arua'),(3364,240,'Iganga'),(3365,240,'Kabarole'),(3366,240,'Kaberamaido'),(3367,240,'Kamwenge'),(3368,240,'Kanungu'),(3369,240,'Kayunga'),(3370,240,'Kitgum'),(3371,240,'Kyenjojo'),(3372,240,'Mayuge'),(3373,240,'Mbale'),(3374,240,'Moroto'),(3375,240,'Mpigi'),(3376,240,'Mukono'),(3377,240,'Nakapiripirit'),(3378,240,'Pader'),(3379,240,'Rukungiri'),(3380,240,'Sironko'),(3381,240,'Soroti'),(3382,240,'Wakiso'),(3383,240,'Yumbe'),(3384,240,'Abim'),(3385,240,'Amolatar'),(3386,240,'Amuria'),(3387,240,'Amuru'),(3388,240,'Budaka'),(3389,240,'Bududa'),(3390,240,'Bukedea'),(3391,240,'Bukwa'),(3392,240,'Bulisa'),(3393,240,'Butaleja'),(3394,240,'Dokolo'),(3395,240,'Isingiro'),(3396,240,'Kaabong'),(3397,240,'Kaliro'),(3398,240,'Koboko'),(3399,240,'Lyantonde'),(3400,240,'Manafwa'),(3401,240,'Maracha'),
                (3402,240,'Mityana'),(3403,240,'Nakaseke'),(3404,240,'Namutumba'),(3405,240,'Oyam'),(3406,240,'Agago District'),(3407,240,'Alebtong District'),(3408,240,'Amudat District'),(3409,240,'Buikwe District'),(3410,240,'Bukomansimbi District'),(3411,240,'Bulambuli District'),(3412,240,'Buvuma District'),(3413,240,'Buyende District'),(3414,240,'Gomba District'),(3415,240,'Kibuku District'),(3416,240,'Kiryandongo District'),(3417,240,'Kole District'),(3418,240,'Kween District'),(3419,240,'Kyankwanzi District'),(3420,240,'Kyegegwa District'),(3421,240,'Luuka District'),(3422,240,'Lwengo District'),(3423,240,'Mitoma District'),(3424,240,'Namayingo District'),(3425,240,'Napak District'),(3426,240,'Ngora District'),(3427,240,'Ntoroko District'),(3428,240,'Otuke District'),(3429,240,'Rubirizi District'),(3430,240,'Serere District'),(3431,240,'Sheema District'),(3432,240,'Zombo District'),(3433,238,'Palmyra Atoll'),(3434,244,'Alabama'),(3435,244,'Alaska'),(3436,244,'Arizona'),(3437,244,'Arkansas'),(3438,244,'California'),(3439,244,'Colorado'),(3440,244,'Connecticut'),(3441,244,'Delaware'),(3442,244,'Florida'),(3443,244,'Georgia'),(3444,244,'Hawaii'),(3445,244,'Idaho'),(3446,244,'Illinois'),(3447,244,'Indiana'),(3448,244,'Iowa'),(3449,244,'Kansas'),(3450,244,'Kentucky'),(3451,244,'Louisiana'),(3452,244,'Maine'),(3453,244,'Maryland'),(3454,244,'Massachusetts'),(3455,244,'Michigan'),(3456,244,'Minnesota'),(3457,244,'Mississippi'),(3458,244,'Missouri'),(3459,244,'Montana'),(3460,244,'Nebraska'),(3461,244,'Nevada'),(3462,244,'New Hampshire'),(3463,244,'New Jersey'),(3464,244,'New Mexico'),(3465,244,'New York'),(3466,244,'North Carolina'),(3467,244,'North Dakota'),(3468,244,'Ohio'),(3469,244,'Oklahoma'),(3470,244,'Oregon'),(3471,244,'Pennsylvania'),(3472,244,'Rhode Island'),(3473,244,'South Carolina'),(3474,244,'South Dakota'),(3475,244,'Tennessee'),(3476,244,'Texas'),(3477,244,'Utah'),(3478,244,'Vermont'),(3479,244,'Virginia'),(3480,244,'Washington'),(3481,244,'West Virginia'),(3482,244,'Wisconsin'),(3483,244,'Wyoming'),(3484,244,'District Of Columbia'),(3485,245,'Artigas'),(3486,245,'Canelones'),(3487,245,'Cerro Largo'),(3488,245,'Colonia'),(3489,245,'Durazno'),(3490,245,'Flores'),(3491,245,'Florida'),(3492,245,'Lavalleja'),(3493,245,'Maldonado'),(3494,245,'Montevideo'),(3495,245,'Paysandu'),(3496,245,'Rio Negro'),(3497,245,'Rivera'),(3498,245,'Rocha'),(3499,245,'Salto'),(3500,245,'San Jose'),(3501,245,'Soriano'),(3502,245,'Tacuarembo'),(3503,245,'Treinta Y Tres'),(3504,246,'Andijon'),(3505,246,'Bukhoro'),(3506,246,'Farghona'),(3507,246,'Khorazm'),(3508,246,'Namangan'),(3509,246,'Nawoiy'),(3510,246,'Qashqadaryo'),(3511,246,'Qoraqalpoghiston'),(3512,246,'Samarqand'),(3513,246,'Surkhondaryo'),(3514,246,'Toshkent Shahri'),(3515,246,'Toshkent'),(3516,246,'Jizzax'),(3517,246,'Sirdaryo'),(3518,248,'Vatican City'),(3519,196,'Charlotte'),(3520,196,'Saint George'),(3521,249,'Amazonas'),(3522,249,'Anzoategui'),(3523,249,'Apure'),(3524,249,'Aragua'),(3525,249,'Barinas'),(3526,249,'Bolivar'),(3527,249,'Carabobo'),(3528,249,'Cojedes'),(3529,249,'Delta Amacuro'),(3530,249,'Falcon'),(3531,249,'Guarico'),(3532,249,'Lara'),(3533,249,'Merida'),(3534,249,'Miranda'),(3535,249,'Monagas'),(3536,249,'Nueva Esparta'),(3537,249,'Portuguesa'),(3538,249,'Sucre'),(3539,249,'Tachira'),(3540,249,'Trujillo'),(3541,249,'Yaracuy'),(3542,249,'Zulia'),(3543,249,'Distrito Federal'),(3544,249,'Vargas'),(3545,34,'British Virgin Islands'),(3546,239,'Virgin Islands'),(3547,250,'An Giang'),(3548,250,'Ben Tre'),(3549,250,'Cao Bang'),(3550,250,'Dong Thap'),(3551,250,'Hai Phong'),(3552,250,'Ho Chi Minh'),(3553,250,'Kien Giang'),(3554,250,'Lam Dong'),(3555,250,'Long An'),(3556,250,'Quang Ninh'),(3557,250,'Son La'),(3558,250,'Tay Ninh'),(3559,250,'Thanh Hoa'),(3560,250,'Thai Binh'),(3561,250,'Tien Giang'),(3562,250,'Lang Son'),(3563,250,'Dac Lac'),(3564,250,'Dong Nai'),(3565,250,'Song Be'),(3566,250,'Vinh Phu'),(3567,250,'Ha Noi'),(3568,250,'Ba Ria-vung Tau'),(3569,250,'Binh Dinh'),(3570,250,'Binh Thuan'),(3571,250,'Ha Giang'),(3572,250,'Ha Tay'),(3573,250,'Ha Tinh'),(3574,250,'Hoa Binh'),(3575,250,'Khanh Hoa'),(3576,250,'Kon Tum'),(3577,250,'Quang Tri'),(3578,250,'Nam Ha'),(3579,250,'Nghe An'),(3580,250,'Ninh Binh'),(3581,250,'Ninh Thuan'),(3582,250,'Phu Yen'),(3583,250,'Quang Binh'),(3584,250,'Quang Ngai'),(3585,250,'Soc Trang'),(3586,250,'Thua Thien'),(3587,250,'Tra Vinh'),(3588,250,'Tuyen Quang'),(3589,250,'Vinh Long'),(3590,250,'Da Nang'),(3591,250,'Hai Duong'),(3592,250,'Ha Nam'),(3593,250,'Hung Yen'),(3594,250,'Nam Dinh'),(3595,250,'Phu Tho'),(3596,250,'Quang Nam'),(3597,250,'Thai Nguyen'),(3598,250,'Vinh Puc Province'),(3599,250,'Can Tho'),(3600,250,'Dak Lak'),
                (3601,250,'Lai Chau'),(3602,250,'Lao Cai'),(3603,250,'Dien Bien'),(3604,247,'Torba'),(3605,247,'Sanma'),(3606,247,'Tafea'),(3607,247,'Malampa'),(3608,247,'Shefa'),(3609,251,'Wallis And Futuna Islands'),(3610,197,'A\'ana'),(3611,197,'Atua'),(3612,197,'Gagaifomauga'),(3613,197,'Palauli'),(3614,197,'Tuamasaga'),(3615,253,'Abyan'),(3616,253,'Adan'),(3617,253,'Al Mahrah'),(3618,253,'Hadramawt'),(3619,253,'Shabwah'),(3620,253,'Al Hudaydah'),(3621,253,'Al Mahwit'),(3622,253,'Dhamar'),(3623,253,'Ma\'rib'),(3624,253,'Sa\'dah'),(3625,253,'San\'a\''),(3626,253,'Ad Dali'),(3627,253,'Amran'),(3628,253,'Al Bayda\''),(3629,253,'Al Jawf'),(3630,253,'Hajjah'),(3631,253,'Ibb'),(3632,253,'Lahij'),(3633,253,'Taizz'),(3634,253,'Amanat Al Asimah'),(3635,253,'Muhafazat Raymah'),(3636,145,'Acoua'),(3637,145,'Bandraboua'),(3638,145,'Bandrele'),(3639,145,'Boueni'),(3640,145,'Chiconi'),(3641,145,'Chirongui'),(3642,145,'Dzaoudzi'),(3643,145,'Kani-keli'),(3644,145,'Koungou'),(3645,145,'Mamoudzou'),(3646,145,'Mtsamboro'),(3647,145,'Ouangani'),(3648,145,'Pamandzi'),(3649,145,'Sada'),(3650,145,'Tsingoni'),(3651,211,'Kwazulu-natal'),(3652,211,'Free State'),(3653,211,'Eastern Cape'),(3654,211,'Gauteng'),(3655,211,'Mpumalanga'),(3656,211,'Northern Cape'),(3657,211,'Limpopo'),(3658,211,'North-west'),(3659,211,'Western Cape'),(3660,254,'Western'),(3661,254,'Central'),(3662,254,'Eastern'),(3663,254,'Luapula'),(3664,254,'Northern'),(3665,254,'North-western'),(3666,254,'Southern'),(3667,254,'Copperbelt'),(3668,254,'Lusaka'),(3669,255,'Manicaland'),(3670,255,'Midlands'),(3671,255,'Mashonaland Central'),(3672,255,'Mashonaland East'),(3673,255,'Mashonaland West'),(3674,255,'Matabeleland North'),(3675,255,'Matabeleland South'),(3676,255,'Masvingo'),(3677,255,'Bulawayo'),(3678,255,'Harare');")

            ->execute();
    }

    /**
     * @Given /^I am located in "([^"]*)"$/
     */
    public function iAmLocatedIn($countryCode)
    {
        $file = $this->getContainer()->getParameter('maxmind_lookup_directory').'overrideCountry';
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
