--
-- Changes for bug 555027, longer password hashes for hash algo 
-- flexibility and salt.
--

ALTER TABLE `logins`
    MODIFY COLUMN `password` varchar(255) NOT NULL;

