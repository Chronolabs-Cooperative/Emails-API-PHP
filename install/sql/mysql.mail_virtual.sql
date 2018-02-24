
# Table structure for table `mail_virtual`
#

CREATE TABLE `mail_virtual` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL DEFAULT '',
  `email_full` varchar(255) NOT NULL DEFAULT '',
  `destination` varchar(255) NOT NULL DEFAULT '',
  `uid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `emails` (`email`,`email_full`,`destination`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;
