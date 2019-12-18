
# Table structure for table `verify_address`
#

CREATE TABLE `verify_address` (
  `id` mediumint(128) unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(64) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(196) NOT NULL DEFAULT '',
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `pid` int(11) unsigned NOT NULL DEFAULT '0',
  `kid` mediumint(32) unsigned NOT NULL DEFAULT '0',
  `domainid` int(11) NOT NULL DEFAULT '0',
  `created` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `emails` (`domainid`,`email`,`name`,`created`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;
