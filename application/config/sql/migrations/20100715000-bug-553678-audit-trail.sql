--
-- bug 553678: create audit trail
--
DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_id` int(11),
  `action` varchar(255) NOT NULL,
  `pfs_id` char(64),
  `plugin_id` int(11) unsigned,
  `pluginrelease_id` int(11) unsigned,
  `details` text,
  `old_state` text,
  `new_state` text,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_log_ibfk_2` (`profile_id`),
  KEY `activity_log_ibfk_3` (`plugin_id`),
  KEY `activity_log_ibfk_4` (`pluginrelease_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
