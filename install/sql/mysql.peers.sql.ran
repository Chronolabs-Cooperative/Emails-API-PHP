
# Table structure for table `peers`
#

CREATE TABLE `peers` (
  `pid` mediumint(32) unsigned NOT NULL auto_increment,
  `uid` mediumint(8) unsigned NOT NULL default 0,
  `callback` varchar(255) NOT NULL default '',
  `company` varchar(100) NOT NULL default '',
  `serial` varchar(60) NOT NULL default '',
  `email` varchar(60) NOT NULL default '',
  `protocol` varchar(10) NOT NULL default '',
  `host` varchar(100) NOT NULL default '',
  `port` varchar(6) NOT NULL default '80',
  `path` varchar(100) NOT NULL default '/',
  `version` varchar(60) NOT NULL default 'v1',
  `type` varchar(20) NOT NULL default '',
  `type-version` varchar(64) NOT NULL default '',
  `domains` blob,
  PRIMARY KEY  (`pid`),
  KEY companyserial (`company`,`serial`),
  KEY emailserialhostversiontype (`email`,`serial`,`host`,`version`,`type`),
  KEY protocolhostpathversion (`protocol`,`host`,`path`,`version`),
  KEY type (type)
) ENGINE=INNODB DEFAULT CHARSET=utf8;


