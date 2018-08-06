
# Table structure for table `pgpkeys`
#

CREATE TABLE `pgpkeys` (
  `kid` mediumint(32) unsigned NOT NULL auto_increment,
  `typal` enum('internal','external') NOT NULL default 'internal',
  `domainid` int(11) unsigned NOT NULL default 0,
  `zonekey` varchar(32) NOT NULL default '',
  `name` varchar(255) NOT NULL default '',
  `address` varchar(255) NOT NULL default '',
  `public` tinytext,
  `created` int(12) unsigned NOT NULL default '',
  `imported` int(12) unsigned NOT NULL default '',
  `zoned` int(12) unsigned NOT NULL default '',
  PRIMARY KEY  (kid),
  KEY domainidaddress (domainid, address),
) ENGINE=INNODB;


