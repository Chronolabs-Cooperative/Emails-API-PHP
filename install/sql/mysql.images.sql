
# Table structure for table `images`
#

CREATE TABLE `images` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `file` varchar(255) NOT NULL DEFAULT 'avatars/blank.gif',
  `title` varchar(255) NOT NULL DEFAULT '',
  `location` varchar(255) NOT NULL DEFAULT '',
  `description` mediumtext,
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `domainid` int(11) NOT NULL DEFAULT '0',
  `directoryid` int(11) NOT NULL DEFAULT '0',
  `created` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `domain` (`domainid`),
  KEY `directory` (`directoryid`),
  KEY `location` (`location`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

