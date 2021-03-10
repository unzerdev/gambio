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

require_once 'Customweb/Payment/Authorization/IBackendTransactionContext.php';
require_once 'Customweb/Payment/Authorization/Moto/ITransactionContext.php';

require_once 'UnzerCw/AbstractController.php';
require_once 'UnzerCw/TransactionContext.php';


class UnzerCw_BackendTransactionContext extends UnzerCw_TransactionContext implements Customweb_Payment_Authorization_IBackendTransactionContext, Customweb_Payment_Authorization_Moto_ITransactionContext
{
	private $successUrl;
	private $failedUrl;
	
	public function __construct(UnzerCw_Entity_Transaction $transaction, $customerId, $orderContext, $aliasTransaction = null) {
		parent::__construct($transaction, $customerId, $orderContext, $aliasTransaction);
		
		$this->successUrl = UnzerCw_AbstractController::getControllerUrl('moto', 'success');
		$this->failedUrl = UnzerCw_AbstractController::getControllerUrl('moto', 'new');
	}
	
	public function __sleep() {
		return array('capturingMode', 'aliasTransactionId', 'paymentCustomerContext', 'orderContext', 'databaseTransactionId', 'customerId', 'successUrl', 'failedUrl');
	}
	
	public function getCustomParameters() {
		$params = parent::getCustomParameters();
		$params['moto'] = 'true';
		return $params;
	}
	
	public function getBackendSuccessUrl() {
		return $this->successUrl;
	}
	
	public function getBackendFailedUrl() {
		return $this->failedUrl;
	}
	
}