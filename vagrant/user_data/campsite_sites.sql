-- MySQL dump 10.13  Distrib 5.5.34, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: platformd
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pd_site`
--

LOCK TABLES `pd_site` WRITE;
/*!40000 ALTER TABLE `pd_site` DISABLE KEYS */;
INSERT INTO `pd_site` VALUES (1,'Campsite','en_dev','192.168.56.3','ideacontest'),(2,'Toyota','en_toyota','toyota.campsite.local','toyota_ideathon'),(3,'PlaceHolder','en_campsite','','ideacontest');
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
INSERT INTO `pd_site_config` VALUES (1,1,NULL,NULL,'support@campsite.org','noreply@cloudcamp.org','Campsite',0,'www.campsite.org',NULL,0),(2,2,NULL,NULL,'support@campsite.org','noreply@cloudcamp.org','Campsite',0,'www.campsite.org',NULL,0),(3,3,NULL,NULL,'support@campsite.org','noreply@cloudcamp.org','Campsite',0,'www.campsite.org',NULL,0);
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
INSERT INTO `pd_site_features` VALUES (1,1,0,0,0,0,0,0,0,0,0,0,1,0,0,0,0,1,1,0,1,0,0,0,0,0,0,1,1,1,0,0,0,0),(2,2,0,0,0,0,0,0,0,0,0,0,1,0,0,0,0,1,1,0,1,0,0,0,0,0,0,1,1,1,0,0,0,0),(3,3,0,0,0,0,0,0,0,0,0,0,1,0,0,0,0,1,1,0,1,0,0,0,0,0,0,1,1,1,0,0,0,0);
/*!40000 ALTER TABLE `pd_site_features` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-11-11 19:20:17
