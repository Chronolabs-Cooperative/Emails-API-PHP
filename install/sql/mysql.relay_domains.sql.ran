
# Table structure for table `relay_domains`
#

CREATE TABLE `relay_domains` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `zonekey` varchar(32) NOT NULL DEFAULT '',
  `domain` varchar(255) NOT NULL DEFAULT '',
  `parentdomainid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `parentdomain` (`parentdomainid`),
  KEY `domain` (`domain`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;
