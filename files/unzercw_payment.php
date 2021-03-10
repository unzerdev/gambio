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
require_once 'Customweb/Util/Url.php';
require_once 'Customweb/Util/Html.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/Compatibility.php';
require_once 'UnzerCw/Entity/Util.php';


$failedTransaction = null;
if (isset($_GET['failedTransactionId'])) {
	$failedTransaction = UnzerCw_Entity_Util::findTransactionByTransactionId(intval($_GET['failedTransactionId']));

	$customerId = (int)$_SESSION['customer_id'];
	if ($failedTransaction->getTransactionObject() == null) {
		die("No transaction object set on the transaction.");
	}

	if ($failedTransaction->getTransactionObject()->getTransactionContext()->getOrderContext()->getCustomerPrimaryKey() != $customerId) {
		die("Invalid customer id.");
	}
}

if (isset($_REQUEST['cw_transaction_id'])) {
	$dbTransaction = UnzerCw_Entity_Util::findTransactionByTransactionId($_REQUEST['cw_transaction_id']);

	if ($dbTransaction === null) {
		die("Invalid transaction id provided.");
	}

	$paymentModule = UnzerCw_Util::getPaymentMehtodInstance($dbTransaction->getPaymentClass());
	$adapter = UnzerCw_Util::getAuthorizationAdapter($dbTransaction->getAuthorizationType());
	$shopAdapter = UnzerCw_Util::getShopAdapterByPaymentAdapter($adapter);

}
else {
	die("Invalid state (no transaction given).");
}

$aliasTransactionId = $paymentModule->getAliasTransactionId();
$aliasTransaction = null;
if ($aliasTransactionId !== null) {
	$aliasTransaction = UnzerCw_Entity_Util::findTransactionByTransactionId($aliasTransactionId)->getTransactionObject();
}

$formData = $shopAdapter->getPaymentPageContent($dbTransaction, $aliasTransaction, $failedTransaction);

$contentUnzerCw = '';
$contentUnzerCw .= $paymentModule->getCssHtmlCode();
$contentUnzerCw .= ' <h2>' . $paymentModule->getPaymentMethodDisplayName() . '</h2>';

if ($failedTransaction !== null) {
	$errorMessage = current($failedTransaction->getTransactionObject()->getErrorMessages());

	$message = unzercw_translate('The payment was unsuccessful. Please try it again or go back and choose another payment method.');
	if (!empty($errorMessage)) {
		$message .= ' (Error: ' . Customweb_Util_Html::convertSpecialCharacterToEntities($errorMessage) . ')';
	}

	$contentUnzerCw .= '<div class="unzercw-error-message">' . $message . '</div>';
}

$contentUnzerCw .= '<form action="' . $formData['formAction'] . '" method="POST">';
$contentUnzerCw .= $formData['formContent'];
$contentUnzerCw .= UnzerCw_Compatibility::getBackButton(UnzerCw_Util::getFrontendUrl('checkout_payment.php', array(), true));

if (!isset($formData['showConfirmButton']) || $formData['showConfirmButton'] == true) {
	$contentUnzerCw .= UnzerCw_Compatibility::getOrderConfirmationButton();
}

$contentUnzerCw .= '</form>';
UnzerCw_Entity_Util::persist($dbTransaction);

header('Content-Type: text/html; charset=UTF-8');
UnzerCw_Util::renderContentInFrontend($contentUnzerCw);

