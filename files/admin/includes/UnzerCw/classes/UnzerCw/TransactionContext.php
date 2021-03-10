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

require_once 'Customweb/Payment/Authorization/Hidden/ITransactionContext.php';
require_once 'Customweb/Payment/Authorization/Server/ITransactionContext.php';
require_once 'Customweb/Payment/Authorization/Ajax/ITransactionContext.php';
require_once 'Customweb/Payment/Authorization/PaymentPage/ITransactionContext.php';
require_once 'Customweb/Payment/Authorization/Iframe/ITransactionContext.php';
require_once 'Customweb/Payment/Authorization/Widget/ITransactionContext.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/ConfigurationAdapter.php';
require_once 'UnzerCw/Entity/Util.php';


class UnzerCw_TransactionContext implements Customweb_Payment_Authorization_PaymentPage_ITransactionContext,
Customweb_Payment_Authorization_Hidden_ITransactionContext, Customweb_Payment_Authorization_Server_ITransactionContext,
Customweb_Payment_Authorization_Iframe_ITransactionContext, Customweb_Payment_Authorization_Ajax_ITransactionContext,
Customweb_Payment_Authorization_Widget_ITransactionContext
{
	protected $capturingMode;
	protected $aliasTransactionId = NULL;
	protected $paymentCustomerContext = null;
	protected $orderContext;
	protected $databaseTransactionId = NULL;
	protected $customerId = NULL;

	private $databaseTransaction = NULL;

	public function __construct(UnzerCw_Entity_Transaction $transaction, $customerId, UnzerCw_OrderContext $orderContext, $aliasTransactionId = NULL) {

		$aliasTransactionIdCleaned = NULL;
		$active = 'no';
		if($orderContext->getPaymentMethod()->existsPaymentMethodConfigurationValue('alias_manager')){
			$active = $orderContext->getPaymentMethod()->getPaymentMethodConfigurationValue('alias_manager');
		}
		if ($active == 'active') {
			if ($aliasTransactionId === NULL || $aliasTransactionId === 'new') {
				$aliasTransactionIdCleaned = 'new';
			}
			else {
				$aliasTransactionIdCleaned = intval($aliasTransactionId);
			}
		}
		$this->customerId = $customerId;
		$this->aliasTransactionId = $aliasTransactionIdCleaned;
		$this->paymentCustomerContext = UnzerCw_Entity_Util::getPaymentCustomerContext($_SESSION['customer_id']);
		$this->orderContext = $orderContext;
		$this->databaseTransaction = $transaction;
		$this->databaseTransactionId = $transaction->getTransactionId();
		unset($_SESSION['unzercw_checkout_id']);
	}

	/**
	 * @return UnzerCw_Entity_Transaction
	 */
	public function getDatabaseTransaction() {
		if ($this->databaseTransaction === NULL) {
			$this->databaseTransaction = UnzerCw_Entity_Util::findTransactionByTransactionId($this->databaseTransactionId);
		}

		return $this->databaseTransaction;
	}

	public function getOrderId() {
		return $this->getDatabaseTransaction()->getOrderId();
	}

	public function isOrderIdUnique() {
		// The error page, which would allow a retry with the same order, is never shown in case a pending order
		// is created. This is because the creation of a pending order has to be done always with the checkout_process.php.
		return UnzerCw_ConfigurationAdapter::isPendingOrderModeActive();
	}

	/**
	 * @deprecated Use instead getTransaction().
	 */
	public function getDatabaseTransactionId() {
		return $this->getTransactionId();
	}

	public function getCapturingMode() {
		return null;
	}

	public function getJavaScriptSuccessCallbackFunction() {
		return '
		function (redirectUrl) {
			window.location = redirectUrl
		}';
	}

	public function getJavaScriptFailedCallbackFunction() {
		return '
		function (redirectUrl) {
			window.location = redirectUrl
		}';
	}

	public function __sleep() {
		return array('capturingMode', 'aliasTransactionId', 'paymentCustomerContext', 'orderContext', 'databaseTransactionId', 'customerId');
	}

	public function getOrderContext() {
		return $this->orderContext;
	}

	public function getTransactionId() {
		return $this->getDatabaseTransaction()->getTransactionId();
	}

	public function createRecurringAlias() {
		return false;
	}

	public function getAlias() {
		if ($this->aliasTransactionId === 'new') {
			return 'new';
		}

		if ($this->aliasTransactionId !== null) {
			$transcation = UnzerCw_Entity_Util::findTransactionByTransactionId($this->aliasTransactionId);
			$customerId = $transcation->getTransactionObject()->getTransactionContext()->getOrderContext()->getCustomerPrimaryKey();
			if ($transcation !== null && $transcation->getTransactionObject() !== null && $customerId == $this->customerId) {
				return $transcation->getTransactionObject();
			}
		}

		return null;
	}

	public function getCustomParameters() {
		return array(
			'cw_transaction_id' => $this->getDatabaseTransactionId(),
// 			session_name() => session_id(),
		);
	}

	public function getSuccessUrl() {
		return UnzerCw_Util::getFrontendUrl('unzercw_success.php', array(), true);
	}

	public function getFailedUrl() {
		return UnzerCw_Util::getFrontendUrl('unzercw_failed.php', array(), true);
	}

	public function getPaymentCustomerContext() {
		return $this->paymentCustomerContext;
	}

	public function getNotificationUrl() {
		return UnzerCw_Util::getFrontendUrl('unzercw_notification.php', array(), true);
	}

	public function getIframeBreakOutUrl() {
		return UnzerCw_Util::getFrontendUrl('unzercw_iframe_breakout.php', array(), true);
	}

}