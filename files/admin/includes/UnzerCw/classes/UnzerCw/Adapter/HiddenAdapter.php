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
class UnzerCw_Adapter_HiddenAdapter extends UnzerCw_Adapter_AbstractAdapter
{
	
	public function getPaymentAdapterInterfaceName() {
		return 'Customweb_Payment_Authorization_Hidden_IAdapter';
	}
	
	/**
	 * @return Customweb_Payment_Authorization_Hidden_IAdapter
	 */
	public function getInterfaceAdapter() {
		return parent::getInterfaceAdapter();
	}
	
	public function getCheckoutFormActionUrl(UnzerCw_OrderContext $orderContext, UnzerCw_PaymentMethod $paymentMethod) {
		if (UnzerCw_ConfigurationAdapter::isPendingOrderModeActive()) {
			return null;
		}
		else {
			$dbTransaction = $paymentMethod->newDatabaseTransaction();
			$transaction = $this->getInterfaceAdapter()->createTransaction($paymentMethod->getTransactionContext($dbTransaction), null);
			$dbTransaction->setTransactionObject($transaction);
			UnzerCw_Entity_Util::persist($dbTransaction);
			
			$url = $this->getInterfaceAdapter()->getFormActionUrl($dbTransaction->getTransactionObject());
			UnzerCw_Entity_Util::persist($dbTransaction);
			
			$GLOBALS['unzercw']['current_transaction'] = $dbTransaction;
			
			return $url;
		}
	}
	
	public function getCheckoutForm(UnzerCw_OrderContext $orderContext, UnzerCw_PaymentMethod $paymentMethod) {
	
		$customerContext = UnzerCw_Entity_Util::getPaymentCustomerContext($_SESSION['customer_id']);
		$elements = $this->getInterfaceAdapter()->getVisibleFormFields($orderContext, $paymentMethod->getAliasTransactionObject(), null, $customerContext);
		$formFieldsHtml = '';
		if (count($elements) > 0) {
			$formFieldsHtml = UnzerCw_Util::renderCheckoutFormElements($elements);
		}
		
		if (UnzerCw_ConfigurationAdapter::isPendingOrderModeActive()) {
			$html = '';
			$html .= UnzerCw_Util::getJavaScriptCode();
			
			$html .= '<script type="text/javascript">' . "\n";
			$html .= 'var unzercw_hidden_authorization_form_fields = "' . urlencode($formFieldsHtml) . '"; ' . "\n";
			$html .= "</script>\n";
			$html .= '<div id="unzercw-confirmation-hidden-form-container" style="display:hidden;"></div>';
			
			return $html;
		}
		else {
			if (!isset($GLOBALS['unzercw']['current_transaction'])) {
				throw new Exception("Before calling getCheckoutForm() the method getCheckoutFormActionUrl() must be called.");
			}
		
			$hiddenFields = $this->getInterfaceAdapter()->getHiddenFormFields($GLOBALS['unzercw']['current_transaction']->getTransactionObject());
			return $formFieldsHtml . UnzerCw_Util::renderHiddenFields($hiddenFields);
		}
	}

	public function processOrder(UnzerCw_OrderContext $orderContext, UnzerCw_PaymentMethod $paymentMethod) {
	}
	
	public function processPendingOrder(UnzerCw_OrderContext $orderContext, UnzerCw_PaymentMethod $paymentMethod) {
		$dbTransaction = $paymentMethod->newDatabaseTransaction();
		$transaction = $this->getInterfaceAdapter()->createTransaction($paymentMethod->getTransactionContext($dbTransaction, null, $paymentMethod->getAliasTransactionId()), null);
		$dbTransaction->setTransactionObject($transaction);
		if (UnzerCw_ConfigurationAdapter::isPendingOrderModeActive()) {
			$paymentMethod->writeTransactionLinkToOrder($dbTransaction);
		}
		UnzerCw_Entity_Util::persist($dbTransaction);
		
		
		if (isset($_GET['ajax'])) {
			$data = array(
				'formAction' => $this->getInterfaceAdapter()->getFormActionUrl($transaction),
				'hiddenFormFields' => UnzerCw_Util::renderHiddenFields($this->getInterfaceAdapter()->getHiddenFormFields($dbTransaction->getTransactionObject())),
			);
			echo json_encode($data);
		}
		else {
			header('Location: ' . UnzerCw_Util::getFrontendUrl('unzercw_payment.php', array('cw_transaction_id' => $dbTransaction->getTransactionId()), true));
		}
		unset($_SESSION['tmp_oID']);
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
		
		$formFields = $this->getInterfaceAdapter()->getVisibleFormFields(
			$dbTransaction->getTransactionObject()->getTransactionContext()->getOrderContext(), 
			$aliasTransaction, 
			$failedTransactionObject, 
			$dbTransaction->getTransactionObject()->getPaymentCustomerContext()
		);
		
		$formFieldHtml = '';
		if (count($formFields) > 0) {
			$formFieldHtml = UnzerCw_Util::renderCheckoutFormElements($formFields);
		}
		
		$hiddenFields = $this->getInterfaceAdapter()->getHiddenFormFields($dbTransaction->getTransactionObject());
		if (count($hiddenFields) > 0) {
			$formFieldHtml .= UnzerCw_Util::renderHiddenFields($hiddenFields);
		}
		
		$data = array(
			'formContent' => $formFieldHtml,
			'formAction' => $this->getInterfaceAdapter()->getFormActionUrl($dbTransaction->getTransactionObject()),
		);
		
		UnzerCw_Entity_Util::persist($dbTransaction);
		
		return $data;
	}
}

