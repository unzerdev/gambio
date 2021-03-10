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

require_once 'Customweb/Payment/Authorization/IOrderContext.php';

require_once 'UnzerCw/BackendOrder.php';
require_once 'UnzerCw/Database.php';
require_once 'UnzerCw/OrderContext.php';


class UnzerCw_BackendOrderContext extends UnzerCw_OrderContext implements Customweb_Payment_Authorization_IOrderContext
{
	
	
	
	public function __construct($orderId, Customweb_Payment_Authorization_IPaymentMethod $paymentMethod) {
		
		$order = new UnzerCw_BackendOrder((int)$orderId);
		
		$order_totals = array();
		$rs = UnzerCw_Database::prepare('SELECT title, value, text, class FROM ' . TABLE_ORDERS_TOTAL  . ' WHERE orders_id = "%d"', array($orderId));
		while (($row = UnzerCw_Database::fetch($rs)) !== false) {
			$row['code'] = $row['class'];
			$order_totals[] = $row;
		}
		
		parent::__construct($order, $order_totals, $paymentMethod);
		
		
		$rs = UnzerCw_Database::prepare('SELECT code FROM ' . TABLE_LANGUAGES . ' AS l, ' . TABLE_ORDERS . ' AS o WHERE o.orders_id = "%d" AND l.directory = o.language', array($orderId));
		if (($row = UnzerCw_Database::fetch($rs)) !== false) {
			$this->language = $row['code'];
		}
		else {
			throw new Exception("Could not resolve the language of the given order.");
		}
	}
	
	
	
}