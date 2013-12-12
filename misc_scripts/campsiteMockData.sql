-- MySQL dump 10.13  Distrib 5.5.34, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: campsite
-- ------------------------------------------------------
-- Server version	5.5.34-0ubuntu0.13.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `pd_site`
--

DROP TABLE IF EXISTS `pd_site`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pd_site` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `defaultLocale` varchar(255) NOT NULL,
  `fullDomain` varchar(255) NOT NULL,
  `theme` varchar(255) NOT NULL,
  `entrySetRegistration_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_1025CCFBD0E9DF32` (`entrySetRegistration_id`),
  CONSTRAINT `FK_1025CCFBD0E9DF32` FOREIGN KEY (`entrySetRegistration_id`) REFERENCES `EntrySetRegistry` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pd_site`
--

LOCK TABLES `pd_site` WRITE;
/*!40000 ALTER TABLE `pd_site` DISABLE KEYS */;
INSERT INTO `pd_site` VALUES (1,'Campsite','en_dev','campsite.local','ideacontest',3);
/*!40000 ALTER TABLE `pd_site` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pd_site_config`
--

DROP TABLE IF EXISTS `pd_site_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pd_site_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT NULL,
  `facebook_app_id` varchar(50) DEFAULT NULL,
  `google_analytics_account` varchar(50) DEFAULT NULL,
  `supportEmailAddress` varchar(255) NOT NULL,
  `automatedEmailAddress` varchar(255) NOT NULL,
  `emailFromName` varchar(255) NOT NULL,
  `birthdateRequired` tinyint(1) NOT NULL,
  `forward_base_url` varchar(255) DEFAULT NULL,
  `forwarded_paths` longtext COMMENT '(DC2Type:array)',
  `min_age_requirement` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_74D28FF8F6BD1646` (`site_id`),
  CONSTRAINT `FK_74D28FF8F6BD1646` FOREIGN KEY (`site_id`) REFERENCES `pd_site` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pd_site_config`
--

LOCK TABLES `pd_site_config` WRITE;
/*!40000 ALTER TABLE `pd_site_config` DISABLE KEYS */;
INSERT INTO `pd_site_config` VALUES (1,1,NULL,NULL,'support@campsite.org','noreply@cloudcamp.org','Campsite',0,'www.campsite.org',NULL,0);
/*!40000 ALTER TABLE `pd_site_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pd_site_features`
--

DROP TABLE IF EXISTS `pd_site_features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pd_site_features` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT NULL,
  `has_video` tinyint(1) NOT NULL,
  `has_steam_xfire_communities` tinyint(1) NOT NULL,
  `has_sweepstakes` tinyint(1) NOT NULL,
  `has_forums` tinyint(1) NOT NULL,
  `has_arp` tinyint(1) NOT NULL,
  `has_news` tinyint(1) NOT NULL,
  `has_deals` tinyint(1) NOT NULL,
  `has_games` tinyint(1) NOT NULL,
  `has_games_nav_drop_down` tinyint(1) NOT NULL,
  `has_messages` tinyint(1) NOT NULL,
  `has_groups` tinyint(1) NOT NULL,
  `has_wallpapers` tinyint(1) NOT NULL,
  `has_microsoft` tinyint(1) NOT NULL,
  `has_photos` tinyint(1) NOT NULL,
  `has_contests` tinyint(1) NOT NULL,
  `has_comments` tinyint(1) NOT NULL,
  `has_events` tinyint(1) NOT NULL,
  `has_giveaways` tinyint(1) NOT NULL,
  `has_html_widgets` tinyint(1) NOT NULL,
  `has_facebook` tinyint(1) NOT NULL,
  `has_google_analytics` tinyint(1) NOT NULL,
  `has_profile` tinyint(1) NOT NULL,
  `has_tournaments` tinyint(1) NOT NULL,
  `has_match_client` tinyint(1) NOT NULL,
  `has_forward_on_404` tinyint(1) NOT NULL,
  `has_index` tinyint(1) NOT NULL,
  `has_about` tinyint(1) NOT NULL,
  `has_contact` tinyint(1) NOT NULL,
  `has_search` tinyint(1) NOT NULL,
  `has_polls` tinyint(1) NOT NULL,
  `has_static_photo_widget` tinyint(1) NOT NULL,
  `has_multi_site_groups` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_36F982ECF6BD1646` (`site_id`),
  CONSTRAINT `FK_36F982ECF6BD1646` FOREIGN KEY (`site_id`) REFERENCES `pd_site` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pd_site_features`
--

LOCK TABLES `pd_site_features` WRITE;
/*!40000 ALTER TABLE `pd_site_features` DISABLE KEYS */;
INSERT INTO `pd_site_features` VALUES (1,1,0,0,0,0,0,0,0,0,0,0,1,0,0,0,0,1,1,0,1,0,0,0,0,0,0,1,1,1,0,0,0,0);
/*!40000 ALTER TABLE `pd_site_features` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fos_user`
--

DROP TABLE IF EXISTS `fos_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fos_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT NULL,
  `username_canonical` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_canonical` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `salt` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `locked` tinyint(1) NOT NULL,
  `expired` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `confirmation_token` varchar(255) DEFAULT NULL,
  `password_requested_at` datetime DEFAULT NULL,
  `roles` longtext NOT NULL COMMENT '(DC2Type:array)',
  `credentials_expired` tinyint(1) NOT NULL,
  `credentials_expire_at` datetime DEFAULT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `has_alienware_system` tinyint(1) DEFAULT NULL,
  `latest_news_source` varchar(255) DEFAULT NULL,
  `subscribed_gaming_news` tinyint(1) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `operatingSystem` varchar(255) DEFAULT NULL,
  `cpu` varchar(255) DEFAULT NULL,
  `memory` varchar(255) DEFAULT NULL,
  `videoCard` varchar(255) DEFAULT NULL,
  `soundCard` varchar(255) DEFAULT NULL,
  `hardDrive` varchar(255) DEFAULT NULL,
  `headphones` varchar(255) DEFAULT NULL,
  `mouse` varchar(255) DEFAULT NULL,
  `mousePad` varchar(255) DEFAULT NULL,
  `keyboard` varchar(255) DEFAULT NULL,
  `monitor` varchar(255) DEFAULT NULL,
  `subscribedAlienwareEvents` tinyint(1) DEFAULT NULL,
  `locale` varchar(2) DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `cevoUserId` int(11) DEFAULT NULL,
  `cevoAvatarUrl` varchar(255) DEFAULT NULL,
  `ipAddress` varchar(50) DEFAULT NULL,
  `avatar_id` int(11) DEFAULT NULL,
  `uuid` varchar(36) DEFAULT NULL,
  `facebook_id` varchar(255) NOT NULL,
  `twitter_id` varchar(255) NOT NULL,
  `api_successful_login` datetime DEFAULT NULL,
  `about_me` longtext,
  `name` varchar(255) DEFAULT NULL,
  `affiliation` varchar(255) DEFAULT NULL,
  `organization` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `industry` varchar(255) DEFAULT NULL,
  `eventRole` varchar(255) DEFAULT NULL,
  `linkedIn` varchar(255) DEFAULT NULL,
  `professionalEmail` varchar(255) DEFAULT NULL,
  `twitterUsername` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `mailingAddress` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_957A6479A0D96FBF` (`email_canonical`),
  UNIQUE KEY `UNIQ_957A647992FC23A8` (`username_canonical`),
  UNIQUE KEY `UNIQ_957A647986383B10` (`avatar_id`),
  KEY `uuid_idx` (`uuid`),
  KEY `cevo_user_id_idx` (`cevoUserId`),
  CONSTRAINT `FK_957A647986383B10` FOREIGN KEY (`avatar_id`) REFERENCES `pd_avatar` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fos_user`
--

LOCK TABLES `fos_user` WRITE;
/*!40000 ALTER TABLE `fos_user` DISABLE KEYS */;
INSERT INTO `fos_user` VALUES (1,'admin','admin','admin@local.com','admin@local.com',1,'ab0q21z52kg0c4s4ckw8gskwsgwk8kw','eee89c4d94edca13461a014183226655ea62e7c0f99f356c0e846ea4db6cb1ae2e2e78dd822b12352c2833985a36036e82bcd8c48eb8d6280c9bb9a837a81b17','2013-12-12 12:53:56',0,0,NULL,'359m4t',NULL,'a:1:{i:0;s:16:\"ROLE_SUPER_ADMIN\";}',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'en','2013-12-05 19:12:10','2013-12-12 12:54:42',NULL,NULL,'',NULL,'e3cdef42-1010-402d-8a17-98798418d068','','',NULL,NULL,'Admin User',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(2,'bill','bill','bill@local.com','bill@local.com',1,'o35ssiltmj4css48gcg40kws8w0sgkw','dbaa5e1923750b6b80dae0c6e71fd09110ccd51ea18e93e7e98ff0126aaf611acfe5e3c308129c94912f09e1058d7590993f35d3ab923739eb3c9821437b6ad3','2013-12-05 19:33:17',0,0,NULL,'3bx3us',NULL,'a:0:{}',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'en','2013-12-05 19:31:42','2013-12-05 19:39:40',NULL,NULL,'',NULL,'047f299b-b929-4a0e-a329-cdf7bfdc444c','','',NULL,'I am an English poet and playwright, widely regarded as the greatest writer in the English language and the world\'s pre-eminent dramatist. I am often called England\'s national poet and the \"Bard of Avon\". My extant works, including some collaborations, consist of about 38 plays, 154 sonnets, two long narrative poems, and a few other verses, the authorship of some of which is uncertain. My plays have been translated into every major living language and are performed more often than those of any other playwright.','William Shakespeare',NULL,NULL,'Playwright','Entertainment',NULL,NULL,NULL,'shakenbake',NULL,NULL),(4,'john','john','john@local.com','john@local.com',1,'4cnrm9fekjcwk0kw40skwgc0gcsgkog','631b7aa5874ad500dcb6c722e897374e8c6049c155d7d65f61814059bda743d5c3d7f5f7b4530ca41523f3be838068e8509b311215c72a0aee2e3bcf240a3a3b',NULL,0,0,NULL,'22oz9b',NULL,'a:0:{}',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'en','2013-12-05 19:50:28','2013-12-05 20:08:26',NULL,NULL,'',NULL,'151100e5-3cf8-44de-99a9-38c2b82f9971','','',NULL,NULL,'John Johnson',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(5,'eric','eric','eric@platformd.com','eric@platformd.com',1,'65jb5occ5yg4cwgogkg00wk88oc0wwc','38d0c38a7cd39c0dc317605555c156e9fe7c4af56a005045821739e1cd0003c519f17357504d374e8ff9d5c4595d2cc628c6d381865e2c25cc17a6d0abd2a41d','2013-12-12 12:47:10',0,0,NULL,NULL,NULL,'a:0:{}',0,NULL,NULL,NULL,NULL,NULL,'US',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'en','2013-12-12 12:44:41','2013-12-12 12:52:23',NULL,NULL,'127.0.0.1',NULL,'627745e9-d0a7-4e80-93a2-aa6616ea27bb','','',NULL,NULL,'Eric Price',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `fos_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pd_groups`
--

DROP TABLE IF EXISTS `pd_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pd_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` longtext NOT NULL,
  `howToJoin` longtext,
  `isPublic` tinyint(1) NOT NULL,
  `backgroundImage_id` int(11) DEFAULT NULL,
  `groupAvatar_id` int(11) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `allLocales` tinyint(1) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL,
  `thumbNail_id` int(11) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `deletedReason` varchar(50) DEFAULT NULL,
  `featured` tinyint(1) NOT NULL,
  `featured_at` datetime DEFAULT NULL,
  `discussionsEnabled` tinyint(1) NOT NULL,
  `facebookLikesUpdatedAt` datetime DEFAULT NULL,
  `facebookLikes` bigint(20) NOT NULL,
  `entrySetRegistration_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_D63978B75E237E06` (`name`),
  UNIQUE KEY `UNIQ_D63978B764D218E` (`location_id`),
  UNIQUE KEY `UNIQ_D63978B7D0E9DF32` (`entrySetRegistration_id`),
  KEY `IDX_D63978B7745FB76C` (`backgroundImage_id`),
  KEY `IDX_D63978B72339B0AC` (`groupAvatar_id`),
  KEY `IDX_D63978B77E3C61F9` (`owner_id`),
  KEY `IDX_D63978B73242170E` (`thumbNail_id`),
  CONSTRAINT `FK_D63978B7D0E9DF32` FOREIGN KEY (`entrySetRegistration_id`) REFERENCES `EntrySetRegistry` (`id`),
  CONSTRAINT `FK_D63978B72339B0AC` FOREIGN KEY (`groupAvatar_id`) REFERENCES `pd_media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_D63978B73242170E` FOREIGN KEY (`thumbNail_id`) REFERENCES `pd_media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_D63978B764D218E` FOREIGN KEY (`location_id`) REFERENCES `pd_locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_D63978B7745FB76C` FOREIGN KEY (`backgroundImage_id`) REFERENCES `pd_media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_D63978B77E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `fos_user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pd_groups`
--

LOCK TABLES `pd_groups` WRITE;
/*!40000 ALTER TABLE `pd_groups` DISABLE KEYS */;
INSERT INTO `pd_groups` VALUES (1,'Ye Olde London Theatre Company','topic','<p>\n	Welcome to our fine company! This is where we will collaborate on ideas for new plays, put together wondrous shows and events, and have a great time!</p>',NULL,1,NULL,NULL,1,'2013-12-05 19:39:39','2013-12-12 12:54:42',0,2,0,NULL,'ye-olde-london-theatre-company',NULL,0,NULL,1,'2013-12-12 12:54:42',0,2);
/*!40000 ALTER TABLE `pd_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_event`
--

DROP TABLE IF EXISTS `group_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `game_id` int(11) DEFAULT NULL,
  `attendeeCount` int(11) DEFAULT NULL,
  `private` tinyint(1) NOT NULL,
  `deletedReason` varchar(50) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `content` longtext,
  `registration_option` varchar(255) NOT NULL,
  `published` tinyint(1) NOT NULL,
  `approved` tinyint(1) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `online` tinyint(1) NOT NULL,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `timezone` varchar(255) NOT NULL,
  `display_timezone` tinyint(1) NOT NULL,
  `external_url` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `address1` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `bannerImage_id` int(11) DEFAULT NULL,
  `address2` varchar(255) DEFAULT NULL,
  `currentRound` int(11) NOT NULL,
  `entrySetRegistration_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_6B8221C0D0E9DF32` (`entrySetRegistration_id`),
  KEY `IDX_6B8221C0FE54D947` (`group_id`),
  KEY `IDX_6B8221C0A76ED395` (`user_id`),
  KEY `IDX_6B8221C0E4C68F85` (`bannerImage_id`),
  KEY `IDX_6B8221C0E48FD905` (`game_id`),
  CONSTRAINT `FK_6B8221C0D0E9DF32` FOREIGN KEY (`entrySetRegistration_id`) REFERENCES `EntrySetRegistry` (`id`),
  CONSTRAINT `FK_6B8221C0A76ED395` FOREIGN KEY (`user_id`) REFERENCES `fos_user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_6B8221C0E48FD905` FOREIGN KEY (`game_id`) REFERENCES `pd_game` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_6B8221C0E4C68F85` FOREIGN KEY (`bannerImage_id`) REFERENCES `pd_media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_6B8221C0FE54D947` FOREIGN KEY (`group_id`) REFERENCES `pd_groups` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_event`
--

LOCK TABLES `group_event` WRITE;
/*!40000 ALTER TABLE `group_event` DISABLE KEYS */;
INSERT INTO `group_event` VALUES (1,1,2,NULL,3,0,NULL,0,'First Play','first-play','<p>\n	Hey guys,</p>\n<p>\n	 </p>\n<p>\n	Let\'s collaborate on ideas for a new play! Just propose your ideas and we\'ll put them to a vote!</p>\n<p>\n	 </p>\n<p>\n	The winning entries will be integrated into the next play I write and full credit will be given to the submitters. </p>','REGISTRATION_ENABLED',1,1,1,1,'2013-01-01 00:00:00','2014-01-01 23:59:00','UTC',1,NULL,NULL,NULL,0.0000000,0.0000000,'2013-12-05 19:42:45','2013-12-12 12:54:42',NULL,NULL,1,1);
/*!40000 ALTER TABLE `group_event` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EntrySetRegistry`
--

DROP TABLE IF EXISTS `EntrySetRegistry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `EntrySetRegistry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scope` varchar(255) NOT NULL,
  `containerId` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EntrySetRegistry`
--

LOCK TABLES `EntrySetRegistry` WRITE;
/*!40000 ALTER TABLE `EntrySetRegistry` DISABLE KEYS */;
INSERT INTO `EntrySetRegistry` VALUES (1,'EventBundle:GroupEvent',1),(2,'GroupBundle:Group',1),(3,'SpoutletBundle:Site',1);
/*!40000 ALTER TABLE `EntrySetRegistry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Document`
--

DROP TABLE IF EXISTS `Document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Document` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Document`
--

LOCK TABLES `Document` WRITE;
/*!40000 ALTER TABLE `Document` DISABLE KEYS */;
/*!40000 ALTER TABLE `Document` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `JudgeIdeaMap`
--

DROP TABLE IF EXISTS `JudgeIdeaMap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `JudgeIdeaMap` (
  `idea` int(11) NOT NULL,
  `judge` int(11) NOT NULL,
  PRIMARY KEY (`idea`,`judge`),
  KEY `IDX_92EEA103A8BCA45` (`idea`),
  KEY `IDX_92EEA10311FBC314` (`judge`),
  CONSTRAINT `FK_92EEA10311FBC314` FOREIGN KEY (`judge`) REFERENCES `fos_user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_92EEA103A8BCA45` FOREIGN KEY (`idea`) REFERENCES `idea` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `JudgeIdeaMap`
--

LOCK TABLES `JudgeIdeaMap` WRITE;
/*!40000 ALTER TABLE `JudgeIdeaMap` DISABLE KEYS */;
/*!40000 ALTER TABLE `JudgeIdeaMap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TagIdeaMap`
--

DROP TABLE IF EXISTS `TagIdeaMap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TagIdeaMap` (
  `idea` int(11) NOT NULL,
  `tag` varchar(100) NOT NULL,
  PRIMARY KEY (`idea`,`tag`),
  KEY `IDX_65C52F5A8BCA45` (`idea`),
  KEY `IDX_65C52F5389B783` (`tag`),
  CONSTRAINT `FK_65C52F5389B783` FOREIGN KEY (`tag`) REFERENCES `tags` (`tag`),
  CONSTRAINT `FK_65C52F5A8BCA45` FOREIGN KEY (`idea`) REFERENCES `idea` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TagIdeaMap`
--

LOCK TABLES `TagIdeaMap` WRITE;
/*!40000 ALTER TABLE `TagIdeaMap` DISABLE KEYS */;
INSERT INTO `TagIdeaMap` VALUES (1,'drama'),(1,'murder'),(1,'mystery'),(1,'tragedy'),(2,'death'),(2,'love'),(2,'romance'),(2,'tragedy'),(3,'comedy'),(3,'humor'),(3,'love'),(3,'romance'),(6,'tragedy'),(6,'war'),(7,'assassination'),(7,'betrayal'),(7,'tragedy');
/*!40000 ALTER TABLE `TagIdeaMap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Vote`
--

DROP TABLE IF EXISTS `Vote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Vote` (
  `idea_id` int(11) NOT NULL,
  `criteria_id` int(11) NOT NULL,
  `voter` varchar(100) NOT NULL,
  `value` int(11) NOT NULL,
  `round` int(11) NOT NULL,
  PRIMARY KEY (`idea_id`,`criteria_id`,`voter`,`round`),
  KEY `IDX_FA222A5A5B6FEF7D` (`idea_id`),
  KEY `IDX_FA222A5A990BEA15` (`criteria_id`),
  CONSTRAINT `FK_FA222A5A5B6FEF7D` FOREIGN KEY (`idea_id`) REFERENCES `idea` (`id`),
  CONSTRAINT `FK_FA222A5A990BEA15` FOREIGN KEY (`criteria_id`) REFERENCES `VoteCriteria` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Vote`
--

LOCK TABLES `Vote` WRITE;
/*!40000 ALTER TABLE `Vote` DISABLE KEYS */;
/*!40000 ALTER TABLE `Vote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `VoteCriteria`
--

DROP TABLE IF EXISTS `VoteCriteria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `VoteCriteria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) DEFAULT NULL,
  `displayName` varchar(100) NOT NULL,
  `description` varchar(1000) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A8A29CCA71F7E88B` (`event_id`),
  CONSTRAINT `FK_A8A29CCA71F7E88B` FOREIGN KEY (`event_id`) REFERENCES `group_event` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `VoteCriteria`
--

LOCK TABLES `VoteCriteria` WRITE;
/*!40000 ALTER TABLE `VoteCriteria` DISABLE KEYS */;
INSERT INTO `VoteCriteria` VALUES (1,1,'Excitement','How exciting this idea is'),(2,1,'Relatability','How relatable this idea is');
/*!40000 ALTER TABLE `VoteCriteria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `idea_id` int(11) DEFAULT NULL,
  `text` varchar(4096) NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5F9E962AA76ED395` (`user_id`),
  KEY `IDX_5F9E962A5B6FEF7D` (`idea_id`),
  CONSTRAINT `FK_5F9E962A5B6FEF7D` FOREIGN KEY (`idea_id`) REFERENCES `idea` (`id`),
  CONSTRAINT `FK_5F9E962AA76ED395` FOREIGN KEY (`user_id`) REFERENCES `fos_user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
INSERT INTO `comments` VALUES (1,2,3,'this is terrible','2013-12-05 20:15:30'),(2,2,3,'you should feel bad','2013-12-05 20:22:35');
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `entry_set`
--

DROP TABLE IF EXISTS `entry_set`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entry_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entrySetRegistration_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `isVotingActive` tinyint(1) NOT NULL,
  `isSubmissionActive` tinyint(1) NOT NULL,
  `allowedVoters` varchar(5000) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_15A85BBAD0E9DF32` (`entrySetRegistration_id`),
  CONSTRAINT `FK_15A85BBAD0E9DF32` FOREIGN KEY (`entrySetRegistration_id`) REFERENCES `EntrySetRegistry` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `entry_set`
--

LOCK TABLES `entry_set` WRITE;
/*!40000 ALTER TABLE `entry_set` DISABLE KEYS */;
INSERT INTO `entry_set` VALUES (1,1,'Plot Ideas','idea',0,1,''),(2,1,'Character Ideas','idea',0,1,''),(3,2,'Play Titles','thread',0,1,'');
/*!40000 ALTER TABLE `entry_set` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `followMappings`
--

DROP TABLE IF EXISTS `followMappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `followMappings` (
  `user` varchar(100) NOT NULL,
  `idea` int(11) NOT NULL,
  PRIMARY KEY (`user`,`idea`),
  KEY `IDX_C5E19882A8BCA45` (`idea`),
  CONSTRAINT `FK_C5E19882A8BCA45` FOREIGN KEY (`idea`) REFERENCES `idea` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `followMappings`
--

LOCK TABLES `followMappings` WRITE;
/*!40000 ALTER TABLE `followMappings` DISABLE KEYS */;
INSERT INTO `followMappings` VALUES ('bill',1),('bill',2),('john',2),('john',3),('bill',5);
/*!40000 ALTER TABLE `followMappings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_event_rsvp_actions`
--

DROP TABLE IF EXISTS `group_event_rsvp_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_event_rsvp_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rsvp_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `attendance` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_910265C71F7E88B` (`event_id`),
  KEY `IDX_910265CA76ED395` (`user_id`),
  CONSTRAINT `FK_910265CA76ED395` FOREIGN KEY (`user_id`) REFERENCES `fos_user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_910265C71F7E88B` FOREIGN KEY (`event_id`) REFERENCES `group_event` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_event_rsvp_actions`
--

LOCK TABLES `group_event_rsvp_actions` WRITE;
/*!40000 ALTER TABLE `group_event_rsvp_actions` DISABLE KEYS */;
INSERT INTO `group_event_rsvp_actions` VALUES (1,1,4,'2013-12-05 20:08:25','2013-12-05 20:08:25','2013-12-05 20:08:25','ATTENDING_YES'),(2,1,5,'2013-12-12 12:52:22','2013-12-12 12:52:22','2013-12-12 12:52:22','ATTENDING_YES');
/*!40000 ALTER TABLE `group_event_rsvp_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_events_attendees`
--

DROP TABLE IF EXISTS `group_events_attendees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_events_attendees` (
  `groupevent_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`groupevent_id`,`user_id`),
  KEY `IDX_AE8CF7EA83BF5DFA` (`groupevent_id`),
  KEY `IDX_AE8CF7EAA76ED395` (`user_id`),
  CONSTRAINT `FK_AE8CF7EAA76ED395` FOREIGN KEY (`user_id`) REFERENCES `fos_user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_AE8CF7EA83BF5DFA` FOREIGN KEY (`groupevent_id`) REFERENCES `group_event` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_events_attendees`
--

LOCK TABLES `group_events_attendees` WRITE;
/*!40000 ALTER TABLE `group_events_attendees` DISABLE KEYS */;
INSERT INTO `group_events_attendees` VALUES (1,4),(1,5);
/*!40000 ALTER TABLE `group_events_attendees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_events_sites`
--

DROP TABLE IF EXISTS `group_events_sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_events_sites` (
  `groupevent_id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  PRIMARY KEY (`groupevent_id`,`site_id`),
  KEY `IDX_E0FA287C83BF5DFA` (`groupevent_id`),
  KEY `IDX_E0FA287CF6BD1646` (`site_id`),
  CONSTRAINT `FK_E0FA287CF6BD1646` FOREIGN KEY (`site_id`) REFERENCES `pd_site` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_E0FA287C83BF5DFA` FOREIGN KEY (`groupevent_id`) REFERENCES `group_event` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_events_sites`
--

LOCK TABLES `group_events_sites` WRITE;
/*!40000 ALTER TABLE `group_events_sites` DISABLE KEYS */;
INSERT INTO `group_events_sites` VALUES (1,1);
/*!40000 ALTER TABLE `group_events_sites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `idea`
--

DROP TABLE IF EXISTS `idea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `idea` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entrySet_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `image_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `createdAt` datetime NOT NULL,
  `description` longtext NOT NULL,
  `stage` varchar(255) DEFAULT NULL,
  `forCourse` tinyint(1) DEFAULT NULL,
  `professors` varchar(255) DEFAULT NULL,
  `amount` varchar(255) DEFAULT NULL,
  `members` longtext,
  `highestRound` int(11) NOT NULL,
  `isPrivate` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_A8BCA453DA5256D` (`image_id`),
  KEY `IDX_A8BCA4561220EA6` (`creator_id`),
  KEY `IDX_A8BCA4519758714` (`entrySet_id`),
  CONSTRAINT `FK_A8BCA4519758714` FOREIGN KEY (`entrySet_id`) REFERENCES `entry_set` (`id`),
  CONSTRAINT `FK_A8BCA453DA5256D` FOREIGN KEY (`image_id`) REFERENCES `Document` (`id`),
  CONSTRAINT `FK_A8BCA4561220EA6` FOREIGN KEY (`creator_id`) REFERENCES `fos_user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `idea`
--

LOCK TABLES `idea` WRITE;
/*!40000 ALTER TABLE `idea` DISABLE KEYS */;
INSERT INTO `idea` VALUES (1,1,2,NULL,'Drama','2013-12-05 19:46:02','Let\'s have a prince\'s uncle kill the king, then have the son duke it out with him',NULL,0,NULL,NULL,'',1,0),(2,1,2,NULL,'Romance','2013-12-05 19:52:52','Let\'s have these two teenagers kill themselves after knowing each other for 3 days and making their families go to war',NULL,0,NULL,NULL,'John Johnson',1,0),(3,1,4,NULL,'Comedy','2013-12-05 20:11:51','The play features three interconnecting plots, connected by a celebration of the wedding of Theseus of Athens and the Amazon queen, Hippolyta, which is set simultaneously in the woodland and in the realm of Fairyland, under the light of the moon.[1]\r\n\r\nIn the opening scene, Hermia refuses to follow her father Egeus\' instructions to marry Demetrius, whom he has chosen for her, because she wishes to marry another man named Lysander. In response, Egeus invokes before Theseus an ancient Athenian law whereby a daughter must marry the suitor chosen by her father, or else face death. Theseus offers her another choice: lifelong chastity while worshipping the goddess Diana as a nun.\r\n\r\nAt that same time, Peter Quince and his fellow players gather to produce a stage play, \"the most lamentable comedy and most cruel death of Pyramus and Thisbe\", for the Duke and the Duchess.[2] Quince reads the names of characters and bestows them to the players. Nick Bottom, who is playing the main role of Pyramus, is over-enthusiastic and wants to dominate others by suggesting himself for the characters of Thisbe, the Lion, and Pyramus at the same time. He would also rather be a tyrant and recites some lines of Ercles. Quince ends the meeting with \"at the Duke\'s oak we meet\".\r\n\r\nMeanwhile, Oberon, king of the fairies, and his queen, Titania, have come to the forest outside Athens. Titania tells Oberon that she plans to stay there until she has attended Theseus and Hippolyta\'s wedding. Oberon and Titania are estranged because Titania refuses to give her Indian changeling to Oberon for use as his \"knight\" or \"henchman,\" since the child\'s mother was one of Titania\'s worshippers. Oberon seeks to punish Titania\'s disobedience, so he calls for his mischievous court jester Puck or \"Robin Goodfellow\" to help him concoct a magical juice derived from a flower called \"love-in-idleness\", which turns from white to purple when struck by Cupid\'s arrow. When the concoction is applied to the eyelids of a sleeping person, that person, upon waking, falls in love with the first living thing they perceive. He instructs Puck to retrieve the flower with the hope that he might make Titania fall in love with an animal of the forest and thereby shame her into giving up the little Indian boy. He says, \"And ere I take this charm from off her sight, / As I can take it with another herb, / I\'ll make her render up her page to me.\"[3]\r\n\r\nHermia and Lysander have escaped to the same forest in hopes of eloping. Helena, desperate to reclaim Demetrius\'s love, tells Demetrius about the plan and he follows them in hopes of killing Lysander. Helena continually makes advances towards Demetrius, promising to love him more than Hermia. However, he rebuffs her with cruel insults against her. Observing this, Oberon orders Puck to spread some of the magical juice from the flower on the eyelids of the young Athenian man. Instead, Puck mistakes Lysander for Demetrius, not having actually seen either before, and administers the juice to the sleeping Lysander. Helena, coming across him, wakes him while attempting to determine whether he is dead or asleep. Upon this happening, Lysander immediately falls in love with Helena. Oberon sees Demetrius still following Hermia and is enraged. When Demetrius decides to go to sleep, Oberon sends Puck to get Helena while he charms Demetrius\' eyes. Upon waking up, he sees Helena. Now, both men are in pursuit of Helena. However, she is convinced that her two suitors are mocking her, as neither loved her originally. Hermia is at a loss to see why her lover has abandoned her, and accuses Helena of stealing Lysander away from her. The four quarrel with each other until Lysander and Demetrius become so enraged that they seek a place to duel each other to prove whose love for Helena is the greatest. Oberon orders Puck to keep Lysander and Demetrius from catching up with one another and to remove the charm from Lysander. Lysander returns to loving Hermia, while Demetrius continues to love Helena.\r\n\r\n\r\n\r\nThe Quarrel of Oberon and Titania by Joseph Noel Paton\r\nMeanwhile, Quince and his band of six labourers (\"rude mechanicals\", as they are described by Puck) have arranged to perform their play about Pyramus and Thisbe for Theseus\' wedding and venture into the forest, near Titania\'s bower, for their rehearsal. Bottom is spotted by Puck, who (taking his name to be another word for a jackass) transforms his head into that of a donkey. When Bottom returns for his next lines, the other workmen run screaming in terror, much to Bottom\'s confusion, since he hasn\'t felt a thing during the transformation. Determined to wait for his friends, he begins to sing to himself. Titania is awakened by Bottom\'s singing and immediately falls in love with him. She lavishes him with attention and presumably makes love to him. While she is in this state of devotion, Oberon takes the changeling. Having achieved his goals, Oberon releases Titania, orders Puck to remove the donkey\'s head from Bottom, and arranges everything so that Hermia, Lysander, Demetrius, and Helena will believe that they have been dreaming when they awaken.\r\n\r\nThe fairies then disappear, and Theseus and Hippolyta arrive on the scene, during an early morning hunt. They wake the lovers and, since Demetrius does not love Hermia any more, Theseus overrules Egeus\'s demands and arranges a group wedding. The lovers decide that the night\'s events must have been a dream. After they all exit, Bottom awakes, and he too decides that he must have experienced a dream \"past the wit of man\". In Athens, Theseus, Hippolyta and the lovers watch the six workmen perform Pyramus and Thisbe. Given a lack of preparation, the performers are so terrible playing their roles to the point where the guests laugh as if it were meant to be a comedy, and afterward everyone retires to bed. Afterward, Oberon, Titania, Puck, and other fairies enter, and bless the house and its occupants with good fortune. After all other characters leave, Puck \"restores amends\" and suggests to the audience that what they just experienced might be nothing but a dream (hence the name of the play).',NULL,0,NULL,NULL,'',1,0),(4,3,4,NULL,'The Tragedy of Julius Caesar','2013-12-12 13:16:13','This should be a play about the assassination of Julius Caesar.',NULL,0,NULL,NULL,NULL,1,0),(5,3,2,NULL,'Macbeth','2013-12-12 13:18:18','This play\'s title is simple and to the point.',NULL,0,NULL,NULL,NULL,1,0),(6,2,2,NULL,'Macbeth','2013-12-12 13:23:02','The character is based on the historical king Macbeth of Scotland, and is derived largely from the account in Holinshed\'s Chronicles (1587), a history of Britain.\r\n\r\nHe is portrayed throughout the play as an antihero. Macbeth is a Scottish noble and a valiant military man. After a supernatural prophecy, and at the urging of his wife, Lady Macbeth, he commits regicide and becomes King of Scotland. He thereafter lives in anxiety and fear, unable to rest or to trust his nobles. He leads a reign of terror until defeated by Macduff. The throne is then restored to the rightful heir, the murdered King Duncan\'s son, Malcolm.',NULL,0,NULL,NULL,'',1,0),(7,2,2,NULL,'Brutus','2013-12-12 13:29:58','Brutus is the most complex of the characters in this play. He is proud of his reputation for honor and nobleness, but he is not always practical, and is often naive. He is the only major character in the play intensely committed to fashioning his behavior to fit a strict moral and ethical code, but he take actions that are unconsciously hypocritical. One of the significant themes that Shakespeare uses to enrich the complexity of Brutus involves his attempt to ritualize the assassination of Caesar. He cannot justify, to his own satisfaction, the murder of a man who is a friend and who has not excessively misused the powers of his office. Consequently, thinking of the assassination in terms of a quasi-religious ritual instead of cold-blooded murder makes it more acceptable to him. Unfortunately for him, he consistently misjudges the people and the citizens of Rome; he believes that they will be willing to consider the assassination in abstract terms.',NULL,0,NULL,NULL,'',1,0);
/*!40000 ALTER TABLE `idea` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `links`
--

DROP TABLE IF EXISTS `links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idea_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `linkDescription` varchar(255) NOT NULL,
  `url` varchar(2048) NOT NULL,
  `type` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D182A1185B6FEF7D` (`idea_id`),
  CONSTRAINT `FK_D182A1185B6FEF7D` FOREIGN KEY (`idea_id`) REFERENCES `idea` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `links`
--

LOCK TABLES `links` WRITE;
/*!40000 ALTER TABLE `links` DISABLE KEYS */;
/*!40000 ALTER TABLE `links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pd_group_membership_actions`
--

DROP TABLE IF EXISTS `pd_group_membership_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pd_group_membership_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_3A2B03ABFE54D947` (`group_id`),
  KEY `IDX_3A2B03ABA76ED395` (`user_id`),
  CONSTRAINT `FK_3A2B03ABA76ED395` FOREIGN KEY (`user_id`) REFERENCES `fos_user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_3A2B03ABFE54D947` FOREIGN KEY (`group_id`) REFERENCES `pd_groups` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pd_group_membership_actions`
--

LOCK TABLES `pd_group_membership_actions` WRITE;
/*!40000 ALTER TABLE `pd_group_membership_actions` DISABLE KEYS */;
INSERT INTO `pd_group_membership_actions` VALUES (1,1,4,'JOINED','2013-12-05 20:08:25'),(2,1,5,'JOINED','2013-12-12 12:52:23'),(3,1,1,'JOINED','2013-12-12 12:54:42');
/*!40000 ALTER TABLE `pd_group_membership_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pd_group_site`
--

DROP TABLE IF EXISTS `pd_group_site`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pd_group_site` (
  `group_id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  PRIMARY KEY (`group_id`,`site_id`),
  KEY `IDX_40195294FE54D947` (`group_id`),
  KEY `IDX_40195294F6BD1646` (`site_id`),
  CONSTRAINT `FK_40195294F6BD1646` FOREIGN KEY (`site_id`) REFERENCES `pd_site` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_40195294FE54D947` FOREIGN KEY (`group_id`) REFERENCES `pd_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pd_group_site`
--

LOCK TABLES `pd_group_site` WRITE;
/*!40000 ALTER TABLE `pd_group_site` DISABLE KEYS */;
INSERT INTO `pd_group_site` VALUES (1,1);
/*!40000 ALTER TABLE `pd_group_site` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pd_groups_members`
--

DROP TABLE IF EXISTS `pd_groups_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pd_groups_members` (
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`group_id`),
  KEY `IDX_179CAEECFE54D947` (`group_id`),
  KEY `IDX_179CAEECA76ED395` (`user_id`),
  CONSTRAINT `FK_179CAEECA76ED395` FOREIGN KEY (`user_id`) REFERENCES `fos_user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_179CAEECFE54D947` FOREIGN KEY (`group_id`) REFERENCES `pd_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pd_groups_members`
--

LOCK TABLES `pd_groups_members` WRITE;
/*!40000 ALTER TABLE `pd_groups_members` DISABLE KEYS */;
INSERT INTO `pd_groups_members` VALUES (1,2),(1,4),(1,5);
/*!40000 ALTER TABLE `pd_groups_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pd_locations`
--

DROP TABLE IF EXISTS `pd_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pd_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `address1` varchar(255) DEFAULT NULL,
  `address2` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state_province` varchar(255) DEFAULT NULL,
  `metro_area` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pd_locations`
--

LOCK TABLES `pd_locations` WRITE;
/*!40000 ALTER TABLE `pd_locations` DISABLE KEYS */;
INSERT INTO `pd_locations` VALUES (1,NULL,NULL,NULL,NULL,NULL,0.0000000,0.0000000);
/*!40000 ALTER TABLE `pd_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pd_tags`
--

DROP TABLE IF EXISTS `pd_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pd_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `status` varchar(255) NOT NULL,
  `times_used` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_16DA51395E237E06` (`name`),
  KEY `IDX_16DA5139F675F31B` (`author_id`),
  CONSTRAINT `FK_16DA5139F675F31B` FOREIGN KEY (`author_id`) REFERENCES `fos_user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pd_tags`
--

LOCK TABLES `pd_tags` WRITE;
/*!40000 ALTER TABLE `pd_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `pd_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tags` (
  `tag` varchar(100) NOT NULL,
  PRIMARY KEY (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` VALUES ('assassination'),('betrayal'),('comedy'),('death'),('drama'),('humor'),('love'),('murder'),('mystery'),('romance'),('tragedy'),('war');
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-12-12 13:41:40
