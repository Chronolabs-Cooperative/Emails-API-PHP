
# Table structure for table `smtp`
#

CREATE TABLE smtp (
  id mediumint(8) unsigned NOT NULL auto_increment,
  typal enum('postfix','other') NOT NULL default 'postfix',
  username varchar(128) NOT NULL default '',
  password varchar(128) NOT NULL default '',
  hostname varchar(255) NOT NULL default '',
  port int(6) unsigned NOT NULL default '25',
  notifyemail varchar(196) NOT NULL default '',
  cclastfour varchar(4) unsigned NOT NULL default '',
  ccexpirymonth varchar(2) unsigned NOT NULL default '',
  ccexpiryyear varchar(4) unsigned NOT NULL default '',
  renewal int(11) NOT NULL default '0',
  renewaltypal enum('none','weekly','fortnightly','monthly','quarterly','yearly') NOT NULL default 'yearly',
  supportemail varchar(128) NOT NULL default '',
  supportphone varchar(32) NOT NULL default '',
  supportaccount varchar(32) NOT NULL default '',
  comment tinytext,
  online int(11) unsigned NOT NULL default '0',
  offline int(11) unsigned NOT NULL default '0',
  checked int(11) unsigned NOT NULL default '0',
  created int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY username (username),
  KEY hostname (hostname),
  KEY idtypalhostname (id,typal,hostname)
) ENGINE=INNODB;
