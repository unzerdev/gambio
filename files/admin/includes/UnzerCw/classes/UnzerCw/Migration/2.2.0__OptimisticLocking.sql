ALTER TABLE  `unzercw_transactions` ADD  `versionNumber` int NOT NULL;
ALTER TABLE  `unzercw_transactions` ADD  `liveTransaction` CHAR( 1 );
ALTER TABLE  `unzercw_customer_contexts` ADD  `versionNumber` int NOT NULL;
-- TODO remove: ALTER TABLE  `unzercw_external_checkout_contexts` ADD  `versionNumber` int NOT NULL;