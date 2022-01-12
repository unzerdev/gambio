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

require_once 'Customweb/Core/Http/Response.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/HttpRequest.php';
require_once 'UnzerCw/Adapter/AbstractAdapter.php';
require_once 'UnzerCw/ConfigurationAdapter.php';
require_once 'UnzerCw/Entity/Util.php';


/**
 * @Bean
 */
class UnzerCw_Adapter_ServerAdapter extends UnzerCw_Adapter_AbstractAdapter
{
	
	public function getPaymentAdapterInterfaceName() {
		return 'Customweb_Payment_Authorization_Server_IAdapter';
	}
	
	/**
	 * @return Customweb_Payment_Authorization_Server_IAdapter
	 */
	public function getInterfaceAdapter() {
		return parent::getInterfaceAdapter();
	}
	
	
	public function getCheckoutFormActionUrl(UnzerCw_OrderContext $orderContext, UnzerCw_PaymentMethod $paymentMethod) {
		return null;
	}
	
	public function getCheckoutForm(UnzerCw_OrderContext $orderContext, UnzerCw_PaymentMethod $paymentMethod) {
		$formData = $paymentMethod->getFormData();
		if ($formData !== null) {
			return '';
		}
		else {
			$customerContext = UnzerCw_Entity_Util::getPaymentCustomerContext($_SESSION['customer_id']);
			$formElements = $this->getInterfaceAdapter()->getVisibleFormFields($orderContext, $paymentMethod->getAliasTransactionObject(), null, $customerContext);
			if (count($formElements) > 0) {
				return UnzerCw_Util::renderCheckoutFormElements($formElements);
			}
			else {
				return '';
			}
		}
	}
	
	public function processOrder(UnzerCw_OrderContext $orderContext, UnzerCw_PaymentMethod $paymentMethod) {
		if (!isset($GLOBALS['unzercwrun_authorization_in_isolation']) || $GLOBALS['unzercwrun_authorization_in_isolation'] !== true) {
			if (isset($_REQUEST['cw_transaction_id'])) {
				$this->processAuthorization($paymentMethod);
			}
			else if (!UnzerCw_ConfigurationAdapter::isPendingOrderModeActive()) {
				$this->createAndProcessOrder($orderContext, $paymentMethod);
			}
		}
	}
	
	public function processAuthorization($paymentMethod) {
		if (isset($_REQUEST['cw_transaction_id'])) {
			$transaction = UnzerCw_Entity_Util::findTransactionByTransactionId($_REQUEST['cw_transaction_id']);
				
			$formData = UnzerCw_HttpRequest::getInstance()->getParameters();
			if ($paymentMethod->getFormData() !== null) {
				$formData = array_merge($paymentMethod->getFormData(), $formData);
			}
				
			$response = $this->getInterfaceAdapter()->processAuthorization($transaction->getTransactionObject(), $formData);
			$response = new Customweb_Core_Http_Response($response);
			$response->send();
			die();
		}
	}
	
	private function createAndProcessOrder(UnzerCw_OrderContext $orderContext, UnzerCw_PaymentMethod $paymentMethod) {
		$dbTransaction = $paymentMethod->newDatabaseTransaction();
		$_REQUEST['cw_transaction_id'] = $dbTransaction->getTransactionId();
		$transaction = $this->getInterfaceAdapter()->createTransaction($paymentMethod->getTransactionContext($dbTransaction, null, $paymentMethod->getAliasTransactionId()), null);
		$dbTransaction->setTransactionObject($transaction);
		UnzerCw_Entity_Util::persist($dbTransaction);
	
		$formData = $paymentMethod->getFormData();
		if ($formData === null) {
			$formData = UnzerCw_Util::processPostData($_POST);;
		}
	
		$response = $this->getInterfaceAdapter()->processAuthorization($transaction, $formData);
		
		UnzerCw_Util::createContainer()->getBean('Customweb_Payment_ITransactionHandler')->persistTransactionObject($transaction);
	
		$response = new Customweb_Core_Http_Response($response);
		$response->send();
		die();
	}
	
	public function processPendingOrder(UnzerCw_OrderContext $orderContext, UnzerCw_PaymentMethod $paymentMethod) {
		$this->createAndProcessOrder($orderContext, $paymentMethod);
	}
	
	public function afterOrderCreation(UnzerCw_OrderContext $orderContext, UnzerCw_PaymentMethod $paymentMethod) {
		$this->finalizeSuccessfulOrder($paymentMethod);
	}
	
	
	public function getPaymentPageContent(UnzerCw_Entity_Transaction $dbTransaction, $aliasTransaction, $failedTransaction) {
		
		$failedTransactionObject = null;
		if ($failedTransaction !== null) {
			$failedTransactionObject = $failedTransaction->getTransactionObject();
		}
		$formElements = $this->getInterfaceAdapter()->getVisibleFormFields(
			$dbTransaction->getTransactionObject()->getTransactionContext()->getOrderContext(), 
			$aliasTransaction, 
			$failedTransactionObject,
			$dbTransaction->getTransactionObject()->getPaymentCustomerContext()
		);
		$formFieldHtml = '';
		if (count($formElements) > 0) {
			$formFieldHtml = UnzerCw_Util::renderCheckoutFormElements($formElements);
		}
		
		UnzerCw_Entity_Util::persist($dbTransaction);
		
		$data = array(
			'formContent' => $formFieldHtml,
			'formAction' => UnzerCw_Util::getFrontendUrl('checkout_process.php', array('cw_transaction_id' => $dbTransaction->getTransactionId())),
		);
		
		return $data;
	}
}