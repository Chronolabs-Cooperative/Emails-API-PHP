
# Table structure for table `mail_users`
#

CREATE TABLE `mail_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL DEFAULT '',
  `username` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(128) NOT NULL DEFAULT '',
  `password_enc` varchar(128) NOT NULL DEFAULT '',
  `uid` int(11) NOT NULL DEFAULT '0',
  `gid` int(11) NOT NULL DEFAULT '0',
  `homedir` varchar(255) NOT NULL DEFAULT '',
  `maildir` varchar(255) NOT NULL DEFAULT '',
  `postfix` enum('Y','N') NOT NULL DEFAULT 'Y',
  `domainid` int(11) NOT NULL DEFAULT '0',
  `customerid` int(11) NOT NULL DEFAULT '0',
  `quota` bigint(13) NOT NULL DEFAULT '0',
  `pop3` tinyint(1) NOT NULL DEFAULT '1',
  `imap` tinyint(1) NOT NULL DEFAULT '1',
  `mboxsize` bigint(30) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;
