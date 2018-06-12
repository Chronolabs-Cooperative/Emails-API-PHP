
# Table structure for table `domains`
#

CREATE TABLE `domains` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) NOT NULL DEFAULT '',
  `mxcheck` int(11) unsigned NOT NULL DEFAULT '0',
  `mxemail` int(11) unsigned NOT NULL DEFAULT '0',
  `mxcover` int(11) unsigned NOT NULL DEFAULT '0',
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `ispeer` tinyint(1) NOT NULL DEFAULT '0',
  `isdirectory` tinyint(1) NOT NULL DEFAULT '0',
  `isemaildomain` tinyint(1) NOT NULL DEFAULT '0',
  `parentdomainid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `parentdomain` (`parentdomainid`),
  KEY `domain` (`domain`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

