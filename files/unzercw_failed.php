<?php
/**
  * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 */
// It can cause issues when the GZIP output is activated. The LayoutRenderer may cause issues
// since the output in the ob stream is gzip.
$gzip_off = true;

$GLOBALS['UnzerCwBackupRequestData'] = array(
	'request' => $_REQUEST,
	'post' => $_POST,
	'get' => $_GET,
);
require_once 'includes/application_top.php';
$baseAdminPath = defined('DIR_ADMIN') ? DIR_ADMIN : 'admin/';
require_once dirname(__FILE__) . '/' . $baseAdminPath . 'includes/UnzerCw/init.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/OrderContext.php';
require_once 'UnzerCw/ConfigurationAdapter.php';
require_once 'UnzerCw/Entity/Util.php';


if (!isset($_REQUEST['cw_transaction_id'])) {
	die("No transaction_id provided.");
}

$failedDbTransaction = UnzerCw_Entity_Util::findTransactionByTransactionId(intval($_REQUEST['cw_transaction_id']));

if ($failedDbTransaction === null) {
	die("Invalid transaction id provided.");
}

$customerId = (int)$_SESSION['customer_id'];
if ($failedDbTransaction->getTransactionObject() == null) {
	die("No transaction object set on the transaction.");
}

if ($failedDbTransaction->getTransactionObject()->getTransactionContext()->getOrderContext()->getCustomerPrimaryKey() != $customerId) {
	die("Invalid customer id.");
}


$paymentModule = UnzerCw_Util::getPaymentMehtodInstance($failedDbTransaction->getPaymentClassName());

if ($paymentModule === NULL) {
	die("Could not load payment module. May be the module class is not set.");
}

$orderId = null;
if (UnzerCw_ConfigurationAdapter::isPendingOrderModeActive()) {
	$orderId = $failedDbTransaction->getOrderId();
}

$errorHandling = $paymentModule->getPaymentMethodConfigurationValue('error_handling');

// When we are using pending orders we cannot use a dedicated error page, because we cannot
// create a new order in this case manually. We would have to go through the checkout_process.php which
// we cannot trigger and as such we go always to the payment method selection page.
if ($errorHandling == 'payment_selection' || UnzerCw_ConfigurationAdapter::isPendingOrderModeActive()) {
	$url = UnzerCw_Util::getFrontendUrl('checkout_payment.php', array(
		'failedTransactionId' => $failedDbTransaction->getTransactionId(),
		'payment_error' => $paymentModule->getCode(),
	));
	header('Location: ' . $url);
	die();
}


// Create new transaction from the failed one
$dbTransaction = $paymentModule->newDatabaseTransaction($orderId);

$adapter = UnzerCw_Util::getAuthorizationAdapter($failedDbTransaction->getAuthorizationType());
$transactionContext = $paymentModule->getTransactionContext(
	$dbTransaction,
	new UnzerCw_OrderContext($failedDbTransaction->getTransactionObject()->getTransactionContext()->getOrderContext()),
	$paymentModule->getAliasTransactionId()
);
$transaction = $adapter->createTransaction($transactionContext, $failedDbTransaction->getTransactionObject());

$dbTransaction->setTransactionObject($transaction);

if (UnzerCw_ConfigurationAdapter::isPendingOrderModeActive()) {
	$paymentModule->writeTransactionLinkToOrder($dbTransaction);
}
UnzerCw_Entity_Util::persist($dbTransaction);


$url = UnzerCw_Util::getFrontendUrl('unzercw_payment.php', array('failedTransactionId' => $failedDbTransaction->getTransactionId(), 'cw_transaction_id' => $dbTransaction->getTransactionId()), true);
header('Location: ' . $url);
