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
require_once 'Customweb/Payment/Authorization/PaymentPage/IAdapter.php';
require_once 'Customweb/Util/Html.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/HttpRequest.php';
require_once 'UnzerCw/Entity/Util.php';


if (!isset($_REQUEST['cw_transaction_id'])) {
	die("No transaction_id provided.");
}

$dbTransaction = UnzerCw_Entity_Util::findTransactionByTransactionId(intval($_REQUEST['cw_transaction_id']));

if ($dbTransaction === null) {
	die("Invalid transaction id provided.");
}

$authorizationAdapter = UnzerCw_Util::getAuthorizationAdapter($dbTransaction->getAuthorizationType());

if (!($authorizationAdapter instanceof Customweb_Payment_Authorization_PaymentPage_IAdapter)) {
	throw new Exception("Only supported for payment page authorization.");
}

$paymentMethod = UnzerCw_Util::getPaymentMehtodInstance($dbTransaction->getPaymentClassName());

$formData = UnzerCw_HttpRequest::getInstance()->getParameters();

if ($paymentMethod->getFormData() !== null) {
	$formData = array_merge($paymentMethod->getFormData(), $formData);
}

$headerRedirection = $authorizationAdapter->isHeaderRedirectionSupported($dbTransaction->getTransactionObject(), $formData);

if ($headerRedirection) {
	$url = $authorizationAdapter->getRedirectionUrl($dbTransaction->getTransactionObject(), $formData);
	UnzerCw_Entity_Util::persist($dbTransaction);
	header('Location: ' . $url);
	die();
}
else {

	$paymentMethodName = $dbTransaction->getTransactionObject()->getPaymentMethod()->getPaymentMethodDisplayName();
	$form_target_url = $authorizationAdapter->getFormActionUrl($dbTransaction->getTransactionObject(), $formData);
	$hidden_fields = $authorizationAdapter->getParameters($dbTransaction->getTransactionObject(), $formData);
	UnzerCw_Entity_Util::persist($dbTransaction);

	$html = '';
	$html .= '<h2>'.  unzercw_translate('Redirection') . ': ' .  Customweb_Util_Html::convertSpecialCharacterToEntities($paymentMethodName) . '</h2>';
	$html .= '<form action="' . $form_target_url . '" method="POST" name="process_form">';
		$html .= Customweb_Util_Html::buildHiddenInputFields($hidden_fields);
		$html .= '<input class="button" type="submit" name="continue_button" value="' . unzercw_translate('Continue') . '" />';
	$html .= '</form>';

	$html .= '<script type="text/javascript">' . "\n";
		$html .= "document.process_form.submit(); \n";
	$html .= '</script>';
	header('Content-Type: text/html; charset=UTF-8');
	UnzerCw_Util::renderContentInFrontend($html);
}






