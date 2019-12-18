
# Table structure for table `callbacks`
#

CREATE TABLE `callbacks` (
  `cid` mediumint(200) unsigned NOT NULL auto_increment,
  `url` varchar(255) NOT NULL default '',
  `post` longtext,
  `created` int(12) unsigned NOT NULL default '0',
  `called` int(12) unsigned NOT NULL default '0',
  PRIMARY KEY  (cid),
  KEY createdcalled (created, called)
) ENGINE=INNODB DEFAULT CHARSET=utf8;


