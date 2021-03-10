
ALTER TABLE unzercw_transactions ENGINE = INNODB;
ALTER TABLE unzercw_customer_contexts ENGINE = INNODB;

ALTER TABLE  `unzercw_transactions` CHANGE  `transaction_id`  `transactionId` BIGINT( 20 ) NOT NULL AUTO_INCREMENT;
ALTER TABLE  `unzercw_transactions` CHANGE  transaction_number `transactionExternalId` VARCHAR( 255 );
ALTER TABLE  `unzercw_transactions` CHANGE  `order_id`  `orderId` VARCHAR( 255 );
ALTER TABLE  `unzercw_transactions` CHANGE  `alias_for_display`  `aliasForDisplay` VARCHAR( 255 );
ALTER TABLE  `unzercw_transactions` CHANGE  `alias_active`  `aliasActive` CHAR( 1 );
ALTER TABLE  `unzercw_transactions` CHANGE  `payment_method`  `paymentMachineName` VARCHAR( 255 );
ALTER TABLE  `unzercw_transactions` CHANGE  `payment_class`  `paymentClass` VARCHAR( 255 );
ALTER TABLE  `unzercw_transactions` CHANGE  `transaction_object`  `transactionObject` LONGTEXT;
ALTER TABLE  `unzercw_transactions` CHANGE  `authorization_type`  `authorizationType` VARCHAR( 255 );
ALTER TABLE  `unzercw_transactions` CHANGE  `customer_id`  `customerId` VARCHAR( 255 );
ALTER TABLE  `unzercw_transactions` CHANGE  `updated_on`  `updatedOn` DATETIME;
ALTER TABLE  `unzercw_transactions` CHANGE  `created_on`  `createdOn` DATETIME;
ALTER TABLE  `unzercw_transactions` CHANGE  `payment_id`  `paymentId` VARCHAR( 255 );
ALTER TABLE  `unzercw_transactions` CHANGE  `updatable`  `updatable` CHAR( 1 );
ALTER TABLE  `unzercw_transactions` CHANGE  `session_data`  `sessionData` LONGTEXT;
ALTER TABLE  `unzercw_transactions` ADD  `executeUpdateOn`  DATETIME NULL DEFAULT NULL;
ALTER TABLE  `unzercw_transactions` ADD  `authorizationAmount`  DECIMAL( 20, 5 ) NULL DEFAULT NULL;
ALTER TABLE  `unzercw_transactions` ADD  `authorizationStatus` VARCHAR( 255 );
ALTER TABLE  `unzercw_transactions` ADD  `paid` CHAR( 1 );
ALTER TABLE  `unzercw_transactions` ADD  `currency` VARCHAR( 255 );
ALTER TABLE  `unzercw_transactions` ADD  `securityToken` VARCHAR( 255 );
ALTER TABLE  `unzercw_transactions` ADD  `lastSetOrderStatusSettingKey` VARCHAR( 255 );


ALTER TABLE  `unzercw_customer_contexts` CHANGE  `customer_id`  `customerId` VARCHAR( 255 );
ALTER TABLE  `unzercw_customer_contexts` CHANGE  `context_id` `contextId` BIGINT( 20 ) NOT NULL AUTO_INCREMENT;
ALTER TABLE  `unzercw_customer_contexts` CHANGE  `context_object`  `context_values` LONGTEXT;
ALTER TABLE  `unzercw_customer_contexts` DROP  `created_on`;
ALTER TABLE  `unzercw_customer_contexts` DROP  `updated_on`;
