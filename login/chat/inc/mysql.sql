
CREATE TABLE IF NOT EXISTS `oom_openid_chatmessages` (
  `id` int(15) NOT NULL auto_increment,
  `sender` varchar(200) NOT NULL,
  `receiver` varchar(200) NOT NULL,
  `timestamp` int(15) NOT NULL,
  `message` mediumtext NOT NULL,
  `new` int(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

