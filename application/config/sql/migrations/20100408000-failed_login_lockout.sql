--
-- Changes for bug 554368, tracking failed logins for account lockout.
--

ALTER TABLE `logins`
    ADD COLUMN `failed_login_count` int(11) NOT NULL DEFAULT '0',
    ADD COLUMN `last_failed_login` datetime DEFAULT NULL;
