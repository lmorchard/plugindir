--
-- Table structure for table `plugins_trustedprofiles`
--

DROP TABLE IF EXISTS `plugins_trustedprofiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugins_trustedprofiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pfs_id` char(64) NOT NULL,
  `profile_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pfs_id_profile_id` (`pfs_id`,`profile_id`),
  KEY `plugins_trustedprofiles_ibfk_2` (`profile_id`),
  CONSTRAINT `plugins_trustedprofiles_ibfk_1` FOREIGN KEY (`pfs_id`) REFERENCES `plugins` (`pfs_id`) ON DELETE CASCADE,
  CONSTRAINT `plugins_trustedprofiles_ibfk_2` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
