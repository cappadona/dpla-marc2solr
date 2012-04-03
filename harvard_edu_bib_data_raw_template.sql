-- MySQL dump 10.13  Distrib 5.1.61, for redhat-linux-gnu (i386)
--
-- Host: localhost    Database: lc_template
-- ------------------------------------------------------
-- Server version	5.1.61

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
-- Table structure for table `harvard_edu_bib_data_raw_template`
--

DROP TABLE IF EXISTS `harvard_edu_bib_data_raw_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `harvard_edu_bib_data_raw_template` (
  `RecordID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `MarcLDR` text,
  `MarcMaterialFormat` text,
  `Marc001` varchar(255) DEFAULT NULL,
  `Marc008Year` varchar(255) DEFAULT NULL,
  `Marc008Lang` varchar(20) DEFAULT NULL,
  `LangFull` varchar(40) DEFAULT NULL,
  `Marc010` varchar(255) DEFAULT NULL,
  `Marc020` text,
  `Marc035A` varchar(20) DEFAULT NULL,
  `Marc050` varchar(255) DEFAULT NULL,
  `Marc090` varchar(255) DEFAULT NULL,
  `Marc100` varchar(255) DEFAULT NULL,
  `Marc110` text,
  `Marc111` text,
  `Marc130` text,
  `Marc240A` text,
  `Marc245A` text,
  `Marc245AUTCandidate` text,
  `Marc245AUTCandidateMD5` varchar(40) DEFAULT NULL,
  `Marc245NumNonFilingChars` varchar(10) DEFAULT NULL,
  `Marc245ASort` text,
  `Marc245B` text,
  `Marc245C` text,
  `Marc246A` text,
  `Marc250` text,
  `Marc260` text,
  `Marc260A` text,
  `Marc260B` text,
  `Marc260C` text,
  `Marc300Pages` text,
  `Marc300Other` text,
  `Marc300Dim` text,
  `Marc440` text,
  `Marc500` text,
  `Marc504` text,
  `Marc505` text,
  `Marc600` text,
  `Marc610` text,
  `Marc611` text,
  `Marc630` text,
  `Marc648` text,
  `Marc650` text,
  `Marc651` text,
  `Marc653` text,
  `Marc654` text,
  `Marc655` text,
  `Marc656` text,
  `Marc657` text,
  `Marc658` text,
  `Marc662` text,
  `Marc690` text,
  `Marc691` text,
  `Marc692` text,
  `Marc693` text,
  `Marc695` text,
  `Marc700` text,
  `Marc710` text,
  `Marc711` text,
  `Marc730` text,
  `Marc856` text,
  `DataID` varchar(255) DEFAULT NULL,
  `DataSource` varchar(255) DEFAULT NULL,
  `RecordCreated` datetime DEFAULT NULL,
  PRIMARY KEY (`RecordID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `harvard_edu_bib_data_raw_template`
--

LOCK TABLES `harvard_edu_bib_data_raw_template` WRITE;
/*!40000 ALTER TABLE `harvard_edu_bib_data_raw_template` DISABLE KEYS */;
/*!40000 ALTER TABLE `harvard_edu_bib_data_raw_template` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2012-03-07 17:13:26
