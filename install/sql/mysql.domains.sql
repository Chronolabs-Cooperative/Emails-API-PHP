
# Table structure for table `domains`
#

CREATE TABLE `domains` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) NOT NULL DEFAULT '',
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `isdirectory` tinyint(1) NOT NULL DEFAULT '0',
  `isemaildomain` tinyint(1) NOT NULL DEFAULT '0',
  `email_only` tinyint(1) NOT NULL DEFAULT '0',
  `parentdomainid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `parentdomain` (`parentdomainid`),
  KEY `domain` (`domain`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

