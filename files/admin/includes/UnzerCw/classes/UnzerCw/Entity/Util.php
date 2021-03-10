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

require_once 'Customweb/Payment/Authorization/DefaultPaymentCustomerContext.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/Entity/PaymentCustomerContext.php';
require_once 'UnzerCw/Entity/Transaction.php';


final class UnzerCw_Entity_Util {
	
	private static $paymentCustomerContexts = array();
	
	private function __construct() {
		
	}

	/**
	 * @param int $transactionId
	 * @return UnzerCw_Entity_Transaction
	 */
	public static function findTransactionByTransactionId($transactionId, $cache = true) {
		return UnzerCw_Util::getEntityManager()->fetch('UnzerCw_Entity_Transaction', $transactionId, $cache);
	}

	/**
	 * @param int $transactionId
	 * @return UnzerCw_Entity_Transaction
	 */
	public static function findTransactionEntityByTransactionExternalId($transactionId) {
		$transactions = UnzerCw_Util::getEntityManager()->searchByFilterName('UnzerCw_Entity_Transaction', 'loadByExternalId', array('>transactionExternalId' => $transactionId));
		if (count($transactions) !== 1) {
			throw new Exception("Transaction could not be loaded by the external transaction id.");
		}
		$transaction = end($transactions);
		if (!($transaction instanceof UnzerCw_Entity_Transaction)) {
			throw new Exception("Transaction must be of type UnzerCw_Entity_Transaction.");
		}
		return $transaction;
	}

	/**
	 * @param int $transactionId
	 * @return UnzerCw_Entity_Transaction[]
	 */
	public static function findTransactionsEntityByOrderId($orderId) {
		return UnzerCw_Util::getEntityManager()->searchByFilterName('UnzerCw_Entity_Transaction', 'loadByOrderId', array('>orderId' => $orderId));
	}
	
	public static function persist($entity) {
		if ($entity instanceof Customweb_Payment_Authorization_DefaultPaymentCustomerContext) {
			return;
		}
		$storedContext = UnzerCw_Util::getEntityManager()->persist($entity);
		if ($entity instanceof UnzerCw_Entity_PaymentCustomerContext) {
			self::$paymentCustomerContexts[$storedContext->getCustomerId()] = $storedContext;
		}
	}
		
	/**
	 * @param int $customerId
	 * @return Customweb_Payment_Authorization_IPaymentCustomerContext
	 */
	public static function getPaymentCustomerContext($customerId) {
		// Handle guest context. This context is not stored.
		if (empty($customerId)) {
			if (!isset(self::$paymentCustomerContexts['guestContext'])) {
				self::$paymentCustomerContexts['guestContext'] = new Customweb_Payment_Authorization_DefaultPaymentCustomerContext(array());
			}
				
			return self::$paymentCustomerContexts['guestContext'];
		}
	
		if (!isset(self::$paymentCustomerContexts[$customerId])) {
			$entities = UnzerCw_Util::getEntityManager()->searchByFilterName('UnzerCw_Entity_PaymentCustomerContext', 'loadByCustomerId', array(
				'>customerId' => $customerId,
			));
			if (count($entities) > 0) {
				self::$paymentCustomerContexts[$customerId] = current($entities);
			}
			else {
				$context = new UnzerCw_Entity_PaymentCustomerContext();
				$context->setCustomerId($customerId);
				self::$paymentCustomerContexts[$customerId] = $context;
			}
		}
		return self::$paymentCustomerContexts[$customerId];
	}
	
}