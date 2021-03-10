CREATE TABLE IF NOT EXISTS `unzercw_storage` (
  `keyId` bigint(20) NOT NULL AUTO_INCREMENT,
  `keyName` varchar(165) DEFAULT NULL,
  `keySpace` varchar(165) DEFAULT NULL,
  `keyValue` longtext,
  PRIMARY KEY (`keyId`),
  UNIQUE KEY `keyName_keySpace` (`keyName`,`keySpace`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;