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
require_once 'Customweb/Util/System.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/Entity/Util.php';


header_remove("Set-Cookie");

if (isset($_REQUEST['reset_cart'])) {
	// Reset Basket
	if (isset($_SESSION['cart']) && is_object($_SESSION['cart']) && method_exists($_SESSION['cart'], 'reset')) {
		$_SESSION['cart']->reset(true);
	}
	unset ($_SESSION['sendto']);
	unset ($_SESSION['billto']);
	unset ($_SESSION['shipping']);
	unset ($_SESSION['payment']);
	unset ($_SESSION['comments']);
	unset ($_SESSION['last_order']);
	unset ($_SESSION['tmp_oID']);
	unset ($_SESSION['cc']);
	unset ($_SESSION['unzercw_skip_payment_process']);
	unset ($_SESSION['unzercw_print_order_number']);
	header('Location: ' .  UnzerCw_Util::getFrontendUrl('checkout_success.php', array(), true));
	die();
}

if (!isset($_REQUEST['cw_transaction_id'])) {
	die("No transaction_id provided.");
}

if (!isset($_REQUEST['SameSiteFixRedirect'])) {
	header('Location: ' . UnzerCw_Util::getFrontendUrl('unzercw_success.php', array('cw_transaction_id' => $_REQUEST['cw_transaction_id'], 'SameSiteFixRedirect' => 1), true));
	die();
}


$dbTransaction = UnzerCw_Entity_Util::findTransactionByTransactionId(intval($_REQUEST['cw_transaction_id']));

if ($dbTransaction === null) {
	die("Invalid transaction id provided.");
}

// We have to close the session here otherwise the transaction may not be updated by the notification
// callback.
session_write_close();

$start = time();
$maxExecutionTime = Customweb_Util_System::getMaxExecutionTime() - 5;

if ($maxExecutionTime > 30) {
	$maxExecutionTime = 30;
}

// Wait as long as the notification is done in the background
while (true) {

	$dbTransaction = UnzerCw_Entity_Util::findTransactionByTransactionId(intval($_REQUEST['cw_transaction_id']), false);

	$transactionObject = $dbTransaction->getTransactionObject();

	$url = null;
	if ($transactionObject->isAuthorizationFailed()) {
		$url = UnzerCw_Util::getFrontendUrl('unzercw_failed.php', array('cw_transaction_id' => $_REQUEST['cw_transaction_id']), true);
	}
	else if ($transactionObject->isAuthorized()) {
		$url =  UnzerCw_Util::getFrontendUrl('unzercw_success.php', array('reset_cart' => 'true'), true);
	}

	if ($url !== null) {
		header('Location: ' . $url);
		die();
	}

	if (time() - $start > $maxExecutionTime) {
		die(unzercw_translate("The transaction takes too long for processing. It seems as something went wrong with your transaction. Please contact the shop owner."));
	}
	else {
		// Wait 2 seconds for the next try.
		sleep(2);
	}

}
