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

require_once 'Customweb/Payment/Entity/AbstractPaymentCustomerContext.php';
require_once 'Customweb/Core/Util/Rand.php';
require_once 'Customweb/Payment/Authorization/ITransaction.php';
require_once 'Customweb/Payment/Entity/AbstractTransaction.php';
require_once 'Customweb/Core/Util/Serialization.php';


/**
 *
 * @Entity(tableName = 'unzercw_transactions')
 *
 */
class UnzerCw_Entity_Transaction extends Customweb_Payment_Entity_AbstractTransaction
{
	private $sessionData;

	private $sessionDataDeprecated;

	private $paymentClass;

	private $securityToken;


	/**
	 * We need to override, because we need a different internal customer id as the external id.
	 */
	public function onAfterLoad(Customweb_Database_Entity_IManager $entityManager){
		if ($this->getTransactionObject() !== null && $this->getTransactionObject() instanceof Customweb_Payment_Authorization_ITransaction) {
			$context = $this->getTransactionObject()->getTransactionContext()->getPaymentCustomerContext();
			if ($context instanceof Customweb_Payment_Entity_AbstractPaymentCustomerContext) {
				$customerId = $this->getTransactionObject()->getTransactionContext()->getOrderContext()->getCustomerPrimaryKey();
				$contexts = $entityManager->searchByFilterName(get_class($context), 'loadByCustomerId',
						array(
							'>customerId' => $customerId
						));
				if (is_array($contexts) && count($contexts) > 0) {
					$currentContext = current($contexts);
					$context->setCustomerId($customerId);
					$context->setContextId($currentContext->getContextId());
					$context->setStoreMap($currentContext->getStoreMap());
					$context->setContext($currentContext->getContext());
					$context->setVersionNumber($currentContext->getVersionNumber());
				}
			}
		}
	}

	/**
	 * We need to override, because we need a different internal customer id as the external id.
	 */
	public function onAfterSave(Customweb_Database_Entity_IManager $entityManager){
		if($this->isSkipOnSafeMethods()){
			$this->setSkipOnSaveMethods(false);
			return;
		}
		if ($this->getTransactionObject() !== null && $this->getTransactionObject() instanceof Customweb_Payment_Authorization_ITransaction) {
			$paymentCustomerContext = $this->getTransactionObject()->getTransactionContext()->getPaymentCustomerContext();

			if ($paymentCustomerContext instanceof Customweb_Payment_Entity_AbstractPaymentCustomerContext) {
				$customerId = $this->getTransactionObject()->getTransactionContext()->getOrderContext()->getCustomerPrimaryKey();
				$paymentCustomerContext->setCustomerId($customerId);
				$entityManager->persist($paymentCustomerContext);
			}
		}
	}


	/**
	 * @Column(type = 'binaryObject', name='sessionDataBinary')
	 */
	public function getSessionData()
	{
		return $this->sessionData;
	}

	public function setSessionData($sessionData)
	{
		if(is_array($sessionData)){
			unset($sessionData['paypal_checkout_data']);

			// The Xycons adds some classes to the session which causes issues when the data should be deserialized. As such we remove the data.
			foreach ($sessionData as $key => $value) {
				if (strpos($key, 'swgx2') === 0) {
					unset($sessionData[$key]);
				}
			}
		}
		$this->sessionData = $sessionData;
		return $this;
	}


	/**
	 * @Column(type = 'object', name='sessionData')
	 *
	 * @return array
	 */
	public function getSessionDataDeprecated(){

		return $this->sessionDataDeprecated;
	}

	public function setSessionDataDeprecated($data){
		if(!empty($data)){
			$this->sessionData = $data;
		}
		$this->sessionDataDeprecated = $data;
		return $this;
	}

	/**
	 * @Column(type = 'varchar')
	 */
	public function getPaymentClass(){
		return $this->paymentClass;
	}

	public function setPaymentClass($paymentClass){
		$this->paymentClass = $paymentClass;
		return $this;
	}

	/**
	 * @Column(type = 'varchar')
	 */
	public function getSecurityToken(){
		return $this->securityToken;
	}

	public function setSecurityToken($securityToken){
		$this->securityToken = $securityToken;
		return $this;
	}

	/**
	 * @deprecated
	 */
	public function getPaymentClassName() {
		return $this->getPaymentClass();
	}

	public function onBeforeSave(Customweb_Database_Entity_IManager $entityManager) {
		if($this->isSkipOnSafeMethods()){
			return;
		}

		parent::onBeforeSave($entityManager);
		if ($this->securityToken === NULL) {
			$this->securityToken = Customweb_Core_Util_Rand::getRandomString(128);
		}
		$this->sessionDataDeprecated = array();
		if ($this->getTransactionObject() instanceof Customweb_Payment_Authorization_ITransaction) {
			$orderId = $this->getOrderId();
			if ($this->getTransactionObject()->isAuthorizationFailed() && !empty($orderId)) {
				$lastStatus = $this->getLastSetOrderStatusSettingKey();
				$currentStatus = 'failed_transaction';
				if ($lastStatus === null || $lastStatus != $currentStatus) {
					$configAdapter = new UnzerCw_ConfigurationAdapter();
					$orderStatusId = $configAdapter->getConfigurationValue('cancelled_order_status');
					if (!empty($orderStatusId)) {
						$this->updateOrderStatus($entityManager, $orderStatusId, $currentStatus);
					}
				}
				$this->setLastSetOrderStatusSettingKey($currentStatus);
			}
		}
	}

	/**
	 * @deprecated
	 */
	public function getAuthorizationStatusName() {
		return $this->getAuthorizationStatus();
	}

	protected function updateOrderStatus(Customweb_Database_Entity_IManager $entityManager, $orderStatus, $orderStatusSettingKey) {
		$orderId = $this->getOrderId();
		if (!empty($orderId)) {
			UnzerCw_Util::setOrderStatus($orderStatus, $orderId);
		}

	}

	protected function authorize(Customweb_Database_Entity_IManager $entityManager) {
		$this->runAuthorizationProcessInIsolation($entityManager);
	}

	protected function generateExternalTransactionId(Customweb_Database_Entity_IManager $entityManager) {
		return $this->generateExternalTransactionIdAlwaysAppend($entityManager);
	}

	/**
	 * This method emulates a user request on the checkout_process.php.
	 *
	 * @param UnzerCw_Entity_Transaction $transaction
	 */
	private function runAuthorizationProcessInIsolation(Customweb_Database_Entity_IManager $entityManager) {

		$sessionData = $this->getSessionData();
		$sessionData['unzercw_transaction_object'] = Customweb_Core_Util_Serialization::serialize($this->getTransactionObject());

		$parameters = array(
			'cw_transaction_id' => $this->getTransactionId(),
		);
		$result = UnzerCw_Util::sendLocalRequest('unzercw_authorization.php', $parameters, $sessionData);

		$body = $result['body'];

		$body = trim($body);

		$rs = array();
		if (!preg_match('/^([0-9]+)$/', $body, $rs) || empty($rs[1])) {
			if (!empty($body)) {
				throw new Exception("Failed to create transaction. Error: " . $body);
			}
			else {
				throw new Exception("Failed to create transaction. The authorization script returned an empty body.");
			}
		}
		$this->setOrderId($rs[1]);
		$this->setSessionData(array());
	}
}
