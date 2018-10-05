
# Table structure for table `pgpkeys`
#

CREATE TABLE `pgpkeys` (
  `kid` mediumint(32) unsigned NOT NULL auto_increment,
  `typal` enum('internal','external') NOT NULL default 'internal',
  `domainid` int(11) unsigned NOT NULL default 0,
  `zonekey` varchar(32) NOT NULL default '',
  `name` varchar(255) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `key` tinytext,
  `created` int(12) unsigned NOT NULL default '0',
  `imported` int(12) unsigned NOT NULL default '0',
  `zoned` int(12) unsigned NOT NULL default '0',
  PRIMARY KEY  (kid),
  KEY domainidaddress (domainid, address)
) ENGINE=INNODB DEFAULT CHARSET=utf8;


