--
-- Changes for bug 554368, longer password hashes for hash algo 
-- flexibility and salt.
--

ALTER TABLE `logins`
    MODIFY COLUMN `password` varchar(255) NOT NULL;

