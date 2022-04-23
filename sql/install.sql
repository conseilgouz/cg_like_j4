CREATE TABLE IF NOT EXISTS `#__cg_like` (  `id` int(11) NOT NULL AUTO_INCREMENT,  `cid` int(11) NOT NULL,  `lastdate` DATETIME NOT NULL,  PRIMARY KEY (`id`),  KEY `cg_like_idx` (`cid`));

