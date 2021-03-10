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


require_once 'UnzerCw/Entity/Util.php';
require_once 'UnzerCw/Adapter/IAdapter.php';


abstract class UnzerCw_Adapter_AbstractAdapter implements UnzerCw_Adapter_IAdapter{
	
	/**
	 * @var Customweb_Payment_Authorization_IAdapter
	 */
	private $interfaceAdapter;
	
	
	private $orderContext;
	private $paymentMethod;
	
	public function setInterfaceAdapter(Customweb_Payment_Authorization_IAdapter $interface) {
		$this->interfaceAdapter = $interface;
	}
	
	public function getInterfaceAdapter() {
		return $this->interfaceAdapter;
	}
	
	/**
	 * @return UnzerCw_Entity_Transaction
	 */
	protected function getTransaction() {
		if (!isset($_REQUEST['cw_transaction_id'])) {
			die("No transaction id given.");
		}
		
		return UnzerCw_Entity_Util::findTransactionEntityByTransactionExternalId($_REQUEST['cw_transaction_id']);
	}
	
	public function processAuthorization($paymentMethod) {
	
	}
	
}