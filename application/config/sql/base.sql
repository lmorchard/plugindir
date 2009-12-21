/*!40101 SET NAMES utf8 */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET UNIQUE_CHECKS=0 */;
/*!40014 SET FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET SQL_NOTES=0 */;

DROP TABLE IF EXISTS `mimes`;
CREATE TABLE `mimes` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `suffixes` varchar(255) NOT NULL,
    UNIQUE INDEX `unique_mime` (`name`),
    PRIMARY KEY  (`id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `oses`;
CREATE TABLE `oses` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    UNIQUE INDEX `unique_os` (`name`),
    PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `platforms`;
CREATE TABLE `platforms` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `app_id` varchar(255) DEFAULT '*' NOT NULL,
    `app_release` varchar(255) DEFAULT '*' NOT NULL,
    `app_version` varchar(255) DEFAULT '*' NOT NULL,
    `locale` varchar(255) DEFAULT '*' NOT NULL,
    UNIQUE INDEX `unique_platform` (`app_id`, `app_release`, `app_version`, `locale`),
    PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `plugins`;
CREATE TABLE `plugins` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `pfs_id` char(64) NOT NULL UNIQUE,
    `name` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `latest_release_id` int(11) unsigned,
    `vendor` varchar(255) NOT NULL,
    `url` varchar(255) NOT NULL,
    `icon_url` varchar(255) NOT NULL,
    `license_url` varchar(255) NOT NULL,
    `modified` DATETIME,
    `created` DATETIME,
    PRIMARY KEY  (`id`),
    FOREIGN KEY (`latest_release_id`) REFERENCES plugin_releases(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `mimes_plugins`;
CREATE TABLE `mimes_plugins` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `plugin_id` int(11) unsigned NOT NULL,
    `mime_id` int(11) unsigned NOT NULL,
    UNIQUE INDEX (`mime_id`,`plugin_id`),
    PRIMARY KEY  (`id`),
    FOREIGN KEY (`plugin_id`) REFERENCES plugins(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `plugin_aliases`;
CREATE TABLE `plugin_aliases` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `plugin_id` int(11) unsigned NOT NULL,
    `alias` varchar(255) NOT NULL,
    `is_regex` tinyint(1) NOT NULL default 0,
    UNIQUE INDEX `unique_release` (`plugin_id`, `alias`, `is_regex`),
    PRIMARY KEY (`id`),
    FOREIGN KEY (`plugin_id`) REFERENCES plugins(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `plugin_releases`;
CREATE TABLE `plugin_releases` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `plugin_id` int(11) unsigned NOT NULL,
    `os_id` int(11) unsigned NOT NULL,
    `platform_id` int(11) unsigned NOT NULL,
    `status_code` int(11) default 10,
    `name` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `vendor` varchar(255) NOT NULL,
    `url` varchar(255) NOT NULL,
    `icon_url` varchar(255) NOT NULL,
    `vulnerability_description` TEXT NULL,
    `vulnerability_url` varchar(255) default NULL,
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
    `min` varchar(255) default NULL,
    `max` varchar(255) default NULL,
    `xpcomabi` varchar(255) default NULL,
    `modified` DATETIME,
    `created` DATETIME,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `unique_release` (`plugin_id`, `os_id`, `platform_id`, `version`, `detected_version`, `detection_type`),
    FOREIGN KEY (`plugin_id`) REFERENCES plugins(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `logins`
--

DROP TABLE IF EXISTS `logins`;
CREATE TABLE `logins` (
  `id` int(11) NOT NULL auto_increment,
  `login_name` varchar(64) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(32) NOT NULL,
  `created` datetime default NULL,
  `last_login` datetime default NULL,
  `active` tinyint(2) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `login_name` (`login_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `login_email_verification_tokens`
--

DROP TABLE IF EXISTS `login_email_verification_tokens`;
CREATE TABLE `login_email_verification_tokens` (
  `id` int(11) NOT NULL auto_increment,
  `login_id` int(11) default NULL,
  `token` varchar(32) default NULL,
  `value` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `login_email_verification_tokens`
    ADD CONSTRAINT `login_email_verification_tokens_ibfk_2` FOREIGN KEY (`login_id`) REFERENCES `logins` (`id`) ON DELETE CASCADE;

--
-- Table structure for table `login_password_reset_tokens`
--

DROP TABLE IF EXISTS `login_password_reset_tokens`;
CREATE TABLE `login_password_reset_tokens` (
  `id` int(11) NOT NULL auto_increment,
  `login_id` int(11) default NULL,
  `token` varchar(32) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `login_password_reset_tokens`
    ADD CONSTRAINT `login_password_reset_tokens_ibfk_2` FOREIGN KEY (`login_id`) REFERENCES `logins` (`id`) ON DELETE CASCADE;

--
-- Table structure for table `profiles`
--

DROP TABLE IF EXISTS `profiles`;
CREATE TABLE `profiles` (
  `id` int(11) NOT NULL auto_increment,
  `uuid` varchar(40) NOT NULL,
  `screen_name` varchar(64) NOT NULL,
  `full_name` varchar(128) NOT NULL,
  `bio` text,
  `created` datetime default NULL,
  `last_login` datetime default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `screen_name` (`screen_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `logins_profiles`
--

DROP TABLE IF EXISTS `logins_profiles`;
CREATE TABLE `logins_profiles` (
  `id` int(11) NOT NULL auto_increment,
  `login_id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `login_id_profile_id` (`login_id`,`profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `logins_profiles`
    ADD CONSTRAINT `logins_profiles_ibfk_1` FOREIGN KEY (`login_id`) REFERENCES `logins` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `logins_profiles_ibfk_2` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE;

--
-- Table structure for table `profile_attributes`
--

DROP TABLE IF EXISTS `profile_attributes`;
CREATE TABLE `profile_attributes` (
  `id` int(11) NOT NULL auto_increment,
  `profile_id` int(11) NOT NULL,
  `name` varchar(255) default NULL,
  `value` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `profile_id_name` (`profile_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `profile_attributes`
    ADD CONSTRAINT `profile_attributes_ibfk_1` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL auto_increment,
  `parent_role_id` int(11) default NULL,
  `name` varchar(32) default NULL,
  `description` text,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(32) default NULL,
  `description` text,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `permissions_roles`
--

DROP TABLE IF EXISTS `permissions_roles`;
CREATE TABLE `permissions_roles` (
  `id` int(11) NOT NULL auto_increment,
  `role_id` int(11) default NULL,
  `permission_id` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `permissions_roles`
    ADD CONSTRAINT `permissions_roles_ibfk_1` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `permissions_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Table structure for table `profiles_roles`
--

DROP TABLE IF EXISTS `profiles_roles`;
CREATE TABLE `profiles_roles` (
  `id` int(11) NOT NULL auto_increment,
  `profile_id` int(11) default NULL,
  `role_id` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `profiles_roles`
    ADD CONSTRAINT `profiles_roles_ibfk_1` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `profiles_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
