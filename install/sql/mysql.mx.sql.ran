
# Table structure for table `domains`
#

CREATE TABLE `mxs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `domainid` int(11) NOT NULL DEFAULT '0',
  `mx` varchar(255) NOT NULL DEFAULT '',
  `mxcheck` int(11) unsigned NOT NULL DEFAULT '0',
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `pid` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `domain` (`domainid`),
  KEY `mxdomain` (`mx`,`domainid`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

