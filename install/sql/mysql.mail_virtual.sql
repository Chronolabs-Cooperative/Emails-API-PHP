
# Table structure for table `mail_virtual`
#

CREATE TABLE `mail_virtual` (
  `id` mediumint(128) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `callback` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `email_full` varchar(255) NOT NULL DEFAULT '',
  `destination` varchar(255) NOT NULL DEFAULT '',
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `pid` int(11) unsigned NOT NULL DEFAULT '0',
  `kid` mediumint(32) unsigned NOT NULL DEFAULT '0',
  `domainid` int(11) NOT NULL DEFAULT '0',
  `created` int(11) NOT NULL DEFAULT '0',
  `emailed` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `emails` (`email`,`email_full`,`destination`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;
