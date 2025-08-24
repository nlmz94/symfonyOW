/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.0.2-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: onlyweebs
-- ------------------------------------------------------
-- Server version	12.0.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `anime`
--

DROP TABLE IF EXISTS `anime`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `anime` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(1024) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `title_english` varchar(1024) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `synopsis` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `img_url` varchar(1024) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `episodes` int(11) DEFAULT NULL,
  `pegi_id` int(11) DEFAULT NULL,
  `airing` varchar(1024) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `aired` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pegi_id_idx` (`pegi_id`),
  CONSTRAINT `pegi_id` FOREIGN KEY (`pegi_id`) REFERENCES `pegi` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22348 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `anime_genre`
--

DROP TABLE IF EXISTS `anime_genre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `anime_genre` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_anime` int(11) DEFAULT NULL,
  `id_genre` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_anime_idx` (`id_anime`),
  KEY `id_genre_idx` (`id_genre`),
  CONSTRAINT `id` FOREIGN KEY (`id_anime`) REFERENCES `anime` (`id`),
  CONSTRAINT `id_genre` FOREIGN KEY (`id_genre`) REFERENCES `genre` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=63372 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `anime_producer`
--

DROP TABLE IF EXISTS `anime_producer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `anime_producer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_anime` int(11) DEFAULT NULL,
  `id_producer` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_idx` (`id_anime`),
  KEY `id_idx1` (`id_producer`),
  CONSTRAINT `id_anime_has_producer` FOREIGN KEY (`id_anime`) REFERENCES `anime` (`id`),
  CONSTRAINT `id_producer_has_producer` FOREIGN KEY (`id_producer`) REFERENCES `producer` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35400 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `anime_studio`
--

DROP TABLE IF EXISTS `anime_studio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `anime_studio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_anime` int(11) DEFAULT NULL,
  `id_studio` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `has_anime_key_idx` (`id_anime`),
  KEY `has_studio_key_idx` (`id_studio`),
  CONSTRAINT `has_anime_key` FOREIGN KEY (`id_anime`) REFERENCES `anime` (`id`),
  CONSTRAINT `has_studio_key` FOREIGN KEY (`id_studio`) REFERENCES `studio` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25116 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `genre`
--

DROP TABLE IF EXISTS `genre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `genre` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=63297 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pegi`
--

DROP TABLE IF EXISTS `pegi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pegi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pegi` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pegi_UNIQUE` (`pegi`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `producer`
--

DROP TABLE IF EXISTS `producer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `producer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1333 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `studio`
--

DROP TABLE IF EXISTS `studio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `studio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=748 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping events for database 'onlyweebs'
--

--
-- Dumping routines for database 'onlyweebs'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2025-08-24 15:17:12
