/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table login_email_verification_tokens
# ------------------------------------------------------------

DROP TABLE IF EXISTS `login_email_verification_tokens`;

CREATE TABLE `login_email_verification_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login_id` int(11) DEFAULT NULL,
  `token` varchar(32) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login_email_verification_tokens_ibfk_2` (`login_id`),
  CONSTRAINT `login_email_verification_tokens_ibfk_2` FOREIGN KEY (`login_id`) REFERENCES `logins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table login_password_reset_tokens
# ------------------------------------------------------------

DROP TABLE IF EXISTS `login_password_reset_tokens`;

CREATE TABLE `login_password_reset_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login_id` int(11) DEFAULT NULL,
  `token` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login_password_reset_tokens_ibfk_2` (`login_id`),
  CONSTRAINT `login_password_reset_tokens_ibfk_2` FOREIGN KEY (`login_id`) REFERENCES `logins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table logins
# ------------------------------------------------------------

DROP TABLE IF EXISTS `logins`;

CREATE TABLE `logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login_name` varchar(64) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(32) NOT NULL,
  `created` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `active` tinyint(2) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `login_name` (`login_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table logins_profiles
# ------------------------------------------------------------

DROP TABLE IF EXISTS `logins_profiles`;

CREATE TABLE `logins_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login_id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login_id_profile_id` (`login_id`,`profile_id`),
  KEY `logins_profiles_ibfk_2` (`profile_id`),
  CONSTRAINT `logins_profiles_ibfk_1` FOREIGN KEY (`login_id`) REFERENCES `logins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `logins_profiles_ibfk_2` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table mimes
# ------------------------------------------------------------

DROP TABLE IF EXISTS `mimes`;

CREATE TABLE `mimes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `suffixes` varchar(255) NOT NULL,
  PRIMARY KEY (`id`,`name`),
  UNIQUE KEY `unique_mime` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table mimes_plugins
# ------------------------------------------------------------

DROP TABLE IF EXISTS `mimes_plugins`;

CREATE TABLE `mimes_plugins` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `plugin_id` int(11) unsigned NOT NULL,
  `mime_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mime_id` (`mime_id`,`plugin_id`),
  KEY `plugin_id` (`plugin_id`),
  CONSTRAINT `mimes_plugins_ibfk_1` FOREIGN KEY (`plugin_id`) REFERENCES `plugins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table oses
# ------------------------------------------------------------

DROP TABLE IF EXISTS `oses`;

CREATE TABLE `oses` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_os` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table permissions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `permissions`;

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table permissions_roles
# ------------------------------------------------------------

DROP TABLE IF EXISTS `permissions_roles`;

CREATE TABLE `permissions_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) DEFAULT NULL,
  `permission_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `permissions_roles_ibfk_1` (`permission_id`),
  KEY `permissions_roles_ibfk_2` (`role_id`),
  CONSTRAINT `permissions_roles_ibfk_1` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permissions_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table platforms
# ------------------------------------------------------------

DROP TABLE IF EXISTS `platforms`;

CREATE TABLE `platforms` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `app_id` varchar(255) NOT NULL DEFAULT '*',
  `app_release` varchar(255) NOT NULL DEFAULT '*',
  `app_version` varchar(255) NOT NULL DEFAULT '*',
  `locale` varchar(255) NOT NULL DEFAULT '*',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_platform` (`app_id`,`app_release`,`app_version`,`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table plugin_aliases
# ------------------------------------------------------------

DROP TABLE IF EXISTS `plugin_aliases`;

CREATE TABLE `plugin_aliases` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `plugin_id` int(11) unsigned NOT NULL,
  `alias` varchar(255) NOT NULL,
  `is_regex` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_release` (`plugin_id`,`alias`,`is_regex`),
  CONSTRAINT `plugin_aliases_ibfk_1` FOREIGN KEY (`plugin_id`) REFERENCES `plugins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table plugin_releases
# ------------------------------------------------------------

DROP TABLE IF EXISTS `plugin_releases`;

CREATE TABLE `plugin_releases` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `plugin_id` int(11) unsigned NOT NULL,
  `os_id` int(11) unsigned NOT NULL,
  `platform_id` int(11) unsigned NOT NULL,
  `status_code` int(11) DEFAULT '10',
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `vendor` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `icon_url` varchar(255) NOT NULL,
  `vulnerability_description` text,
  `vulnerability_url` varchar(255) DEFAULT NULL,
  `guid` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `version` varchar(255) NOT NULL,
  `detected_version` varchar(255) NOT NULL,
  `detection_type` varchar(255) NOT NULL,
  `xpi_location` varchar(255) NOT NULL,
  `installer_location` varchar(255) NOT NULL,
  `installer_hash` varchar(255) NOT NULL,
  `installer_shows_ui` tinyint(1) NOT NULL,
  `manual_installation_url` varchar(255) NOT NULL,
  `license_url` varchar(255) NOT NULL,
  `needs_restart` tinyint(1) NOT NULL,
  `min` varchar(255) DEFAULT NULL,
  `max` varchar(255) DEFAULT NULL,
  `xpcomabi` varchar(255) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_release` (`plugin_id`,`os_id`,`platform_id`,`version`,`detected_version`,`detection_type`),
  CONSTRAINT `plugin_releases_ibfk_1` FOREIGN KEY (`plugin_id`) REFERENCES `plugins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table plugins
# ------------------------------------------------------------

DROP TABLE IF EXISTS `plugins`;

CREATE TABLE `plugins` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pfs_id` char(64) NOT NULL,
  `sandbox_profile_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `guid` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `vendor` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `icon_url` varchar(255) NOT NULL,
  `license_url` varchar(255) NOT NULL,
  `modified` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pfs_id_and_sandbox` (`pfs_id`,`sandbox_profile_id`),
  KEY `sandbox_profile_id` (`sandbox_profile_id`),
  CONSTRAINT `plugins_ibfk_1` FOREIGN KEY (`sandbox_profile_id`) REFERENCES `profile` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table profile_attributes
# ------------------------------------------------------------

DROP TABLE IF EXISTS `profile_attributes`;

CREATE TABLE `profile_attributes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `profile_id_name` (`profile_id`,`name`),
  CONSTRAINT `profile_attributes_ibfk_1` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table profiles
# ------------------------------------------------------------

DROP TABLE IF EXISTS `profiles`;

CREATE TABLE `profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(40) NOT NULL,
  `screen_name` varchar(64) NOT NULL,
  `full_name` varchar(128) NOT NULL,
  `bio` text,
  `created` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `screen_name` (`screen_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table profiles_roles
# ------------------------------------------------------------

DROP TABLE IF EXISTS `profiles_roles`;

CREATE TABLE `profiles_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `profiles_roles_ibfk_1` (`profile_id`),
  KEY `profiles_roles_ibfk_2` (`role_id`),
  CONSTRAINT `profiles_roles_ibfk_1` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `profiles_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table roles
# ------------------------------------------------------------

DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_role_id` int(11) DEFAULT NULL,
  `name` varchar(32) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table submissions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `submissions`;

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `screen_name` varchar(255) DEFAULT NULL,
  `pfs_id` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `vendor` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `detected_version` varchar(255) DEFAULT NULL,
  `version` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `vulnerability_description` text,
  `vulnerability_url` varchar(255) DEFAULT NULL,
  `mimetypes` text,
  `appID` varchar(255) DEFAULT NULL,
  `appRelease` varchar(255) DEFAULT NULL,
  `appVersion` varchar(255) DEFAULT NULL,
  `clientOS` varchar(255) DEFAULT NULL,
  `chromeLocale` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pfs_id` (`pfs_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;






/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
