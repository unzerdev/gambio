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


require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/Adapter/AbstractAdapter.php';
require_once 'UnzerCw/ConfigurationAdapter.php';
require_once 'UnzerCw/Entity/Util.php';


/**
 * @Bean
 */
class UnzerCw_Adapter_AjaxAdapter extends UnzerCw_Adapter_AbstractAdapter
{
	
	public function getPaymentAdapterInterfaceName() {
		return 'Customweb_Payment_Authorization_Ajax_IAdapter';
	}
	
	/**
	 * @return Customweb_Payment_Authorization_Ajax_IAdapter
	 */
	public function getInterfaceAdapter() {
		return parent::getInterfaceAdapter();
	}
	
	public function getCheckoutFormActionUrl(UnzerCw_OrderContext $orderContext, UnzerCw_PaymentMethod $paymentMethod) {
		return null;
	}
	
	public function getCheckoutForm(UnzerCw_OrderContext $orderContext, UnzerCw_PaymentMethod $paymentMethod) {
		
		if(defined('CHECKOUT_AJAX_STAT') && CHECKOUT_AJAX_STAT == 'true') {
			return '';
		}
		
		$customerContext = UnzerCw_Entity_Util::getPaymentCustomerContext($_SESSION['customer_id']);
		
		$elements = $this->getInterfaceAdapter()->getVisibleFormFields($orderContext, $paymentMethod->getAliasTransactionObject(), null, $customerContext);
		$formFieldsHtml = '';
		if (count($elements) > 0) {
			$formFieldsHtml = UnzerCw_Util::renderCheckoutFormElements($elements);
		}
		
		$html = '';
		$html .= UnzerCw_Util::getJavaScriptCode();
		
		$html .= '<script type="text/javascript">' . "\n";
		$html .= 'var unzercw_ajax_authorization_form_fields = "' . urlencode($formFieldsHtml) . '"; ' . "\n";
		$html .= "</script>\n";
		
		if (!UnzerCw_ConfigurationAdapter::isPendingOrderModeActive()) {
			$dbTransaction = $paymentMethod->newDatabaseTransaction();
			$transaction = $this->getInterfaceAdapter()->createTransaction($paymentMethod->getTransactionContext($dbTransaction, null, $paymentMethod->getAliasTransactionId()), null);
			$dbTransaction->setTransactionObject($transaction);
			UnzerCw_Entity_Util::persist($dbTransaction);
			
			$ajaxUrl = $this->getInterfaceAdapter()->getAjaxFileUrl($transaction);
			
			$html .= '<script type="text/javascript" src="' . $ajaxUrl . '"></script>';
			
			$html .= '<script type="text/javascript">' . "\n";
			$html .= ' var unzercw_ajax_submit_callback = ' . $this->getInterfaceAdapter()->getJavaScriptCallbackFunction($transaction) . ';';
			$html .= '</script>';
			
			UnzerCw_Entity_Util::persist($dbTransaction);
		}
		
		
		$html .= '<div id="unzercw-confirmation-ajax-form-container" style="display:none;" class="row"></div>';
			
		$html .= '<noscript> <div class="unzercw-no-script-message">' .
			 unzercw_translate('To process with this payment method you need to activate JavaScript in your browser.') . '</div></noscript>';
		
		return $html;
	}
	
	public function processOrder(UnzerCw_OrderContext $orderContext, UnzerCw_PaymentMethod $paymentMethod) {
		if (!UnzerCw_ConfigurationAdapter::isPendingOrderModeActive() && !isset($_REQUEST['cw_transaction_id']) && defined('CHECKOUT_AJAX_STAT') && CHECKOUT_AJAX_STAT == 'true') {
			$url = $this->doRedirection($paymentMethod);
			header('Location: ' . $url);
			die();
		}
		$this->processAuthorization($paymentMethod);
	}
	
	private function doRedirection(UnzerCw_PaymentMethod $paymentMethod) {
		$dbTransaction = $paymentMethod->newDatabaseTransaction();
		$transaction = $this->getInterfaceAdapter()->createTransaction($paymentMethod->getTransactionContext($dbTransaction, null, $paymentMethod->getAliasTransactionId()), null);
		$dbTransaction->setTransactionObject($transaction);
		UnzerCw_Entity_Util::persist($dbTransaction);
		$params = array_merge(array('cw_transaction_id' => $dbTransaction->getTransactionId()));
		return UnzerCw_Util::getFrontendUrl('unzercw_payment.php', $params, true);
	}
	
	public function processPendingOrder(UnzerCw_OrderContext $orderContext, UnzerCw_PaymentMethod $paymentMethod) {
		if(defined('CHECKOUT_AJAX_STAT') && CHECKOUT_AJAX_STAT == 'true') {
			$url = $this->doRedirection($paymentMethod);
			header('Location: ' . $url);
			die();
		}
		$dbTransaction = $paymentMethod->newDatabaseTransaction();
		$transaction = $this->getInterfaceAdapter()->createTransaction($paymentMethod->getTransactionContext($dbTransaction, $orderContext, $paymentMethod->getAliasTransactionId()), null);
		$dbTransaction->setTransactionObject($transaction);
		UnzerCw_Entity_Util::persist($dbTransaction);

		$ajaxUrl = $this->getInterfaceAdapter()->getAjaxFileUrl($transaction);
		$callbackFunction = $this->getInterfaceAdapter()->getJavaScriptCallbackFunction($transaction);
		UnzerCw_Entity_Util::persist($dbTransaction);
		
		echo json_encode(array(
			'ajaxScriptUrl' => $ajaxUrl,
			'submitCallbackFunction' => $callbackFunction,	
		));
		die();
	}
	
	public function afterOrderCreation(UnzerCw_OrderContext $orderContext, UnzerCw_PaymentMethod $paymentMethod) {
		$this->finalizeSuccessfulOrder($paymentMethod);
	}
	
	
	public function getPaymentPageContent(UnzerCw_Entity_Transaction $dbTransaction, $aliasTransaction, $failedTransaction) {
		$failedTransactionObject = null;
		if ($failedTransaction !== null) {
			$failedTransactionObject = $failedTransaction->getTransactionObject();
		}
		
		$elements = $this->getInterfaceAdapter()->getVisibleFormFields(
			$dbTransaction->getTransactionObject()->getTransactionContext()->getOrderContext(), 
			$aliasTransaction, 
			$failedTransactionObject, 
			$dbTransaction->getTransactionObject()->getPaymentCustomerContext()
		);
		$formFieldsHtml = '';
		if (count($elements) > 0) {
			$formFieldsHtml = UnzerCw_Util::renderCheckoutFormElements($elements);
		}
		
		$html = '';
		$html .= UnzerCw_Util::getJavaScriptCode();
		
		$html .= '<script type="text/javascript">' . "\n";
		$html .= 'var unzercw_ajax_authorization_form_fields = "' . urlencode($formFieldsHtml) . '"; ' . "\n";
		$html .= "</script>\n";
				
		$ajaxUrl = $this->getInterfaceAdapter()->getAjaxFileUrl($dbTransaction->getTransactionObject());
			
		$html .= '<script type="text/javascript" src="' . $ajaxUrl . '"></script>';
			
		$html .= '<script type="text/javascript">' . "\n";
		$html .= ' var unzercw_ajax_submit_callback = ' . $this->getInterfaceAdapter()->getJavaScriptCallbackFunction($dbTransaction->getTransactionObject()) . ';';
		$html .= '</script>';
			
		UnzerCw_Entity_Util::persist($dbTransaction);
		
		$html .= '<div id="unzercw-confirmation-ajax-form-container" style="display:none;" class="row"></div>';
			
		$html .= '<noscript> <div class="unzercw-no-script-message">' .
			unzercw_translate('To process with this payment method you need to activate JavaScript in your browser.') . '</div></noscript>';
		
		
		return array(
			'formContent' => $html,
			'formAction' => "",
		);
		
	}

}