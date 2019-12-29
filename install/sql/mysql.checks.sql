
# Table structure for table `checks`
#

CREATE TABLE checks (
  id mediumint(8) unsigned NOT NULL auto_increment,
  typal enum('header_checks','mime_header_checks','nested_header_checks','body_checks','milter_header_checks','smtp_header_checks','smtp_mime_header_checks','smtp_nested_header_checks','smtp_body_checks','unknown') NOT NULL default 'unknown',
  check varchar(450) NOT NULL default '',
  created int(11) unsigned NOT NULL default '0',
  updated int(11) unsigned NOT NULL default '0',
  deleted int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY typalvalue (typal,value(15)),
  KEY chronologistics (created,updated,deleted),
) ENGINE=INNODB;
