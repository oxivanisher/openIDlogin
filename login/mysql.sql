

CREATE TABLE IF NOT EXISTS `oom_openid_session` (
  `openid` varchar(200) NOT NULL,
  `hash` varchar(40) NOT NULL,
  UNIQUE KEY `openid` (`openid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



CREATE TABLE IF NOT EXISTS `oom_openid_lastonline` (
  `openid` varchar(200) NOT NULL,
  `timestamp` int(15) NOT NULL,
  `name` varchar(50) NOT NULL,
  `status` varchar(200) NOT NULL,
  UNIQUE KEY `openid` (`openid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

