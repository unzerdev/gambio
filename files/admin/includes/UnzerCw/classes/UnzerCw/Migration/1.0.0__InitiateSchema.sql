
CREATE TABLE IF NOT EXISTS `unzercw_transactions` (
  `transaction_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `transaction_number` varchar(255) DEFAULT NULL,
  `order_id` bigint(20) DEFAULT NULL,
  `alias_for_display` varchar(255) DEFAULT NULL,
  `alias_active` char(1) DEFAULT 'y',
  `payment_method` varchar(255) NOT NULL,
  `payment_class` varchar(255) NOT NULL,
  `transaction_object` text,
  `authorization_type` varchar(255) NOT NULL,
  `customer_id` varchar(255) DEFAULT NULL,
  `updated_on` datetime NOT NULL,
  `created_on` datetime NOT NULL,
  `payment_id` varchar(255) NOT NULL,
  `updatable` char(1) DEFAULT 'n',
  `session_data` longtext NOT NULL,
  PRIMARY KEY (`transaction_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `unzercw_customer_contexts` (
  `context_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) NOT NULL,
  `context_object` text,
  `updated_on` datetime NOT NULL,
  `created_on` datetime NOT NULL,
  PRIMARY KEY (`context_id`),
  UNIQUE KEY `customer_id` (`customer_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;