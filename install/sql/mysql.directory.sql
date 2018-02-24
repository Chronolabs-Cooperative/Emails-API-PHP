
# Table structure for table `directory`
#

CREATE TABLE `directory` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `avatar` varchar(255) NOT NULL DEFAULT 'avatars/blank.gif',
  `fullname` varchar(255) NOT NULL DEFAULT '',
  `nickname` varchar(255) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '',
  `location` varchar(255) NOT NULL DEFAULT '',
  `biography` mediumtext,
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `images` int(11) unsigned NOT NULL DEFAULT '0',
  `domainid` int(11) NOT NULL DEFAULT '0',
  `created` int(11) unsigned NOT NULL DEFAULT '0',
  `updated` int(11) unsigned NOT NULL DEFAULT '0',
  `emailed` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `domain` (`domainid`),
  KEY `location` (`location`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

