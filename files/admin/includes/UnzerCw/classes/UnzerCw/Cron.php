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

require_once 'Customweb/Cron/Processor.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/Database.php';
require_once 'UnzerCw/ConfigurationAdapter.php';


class UnzerCw_Cron {
	
	/**
	 * @var UnzerCw_ConfigurationAdapter
	 */
	private $configAdapter;
	
	public function __construct() {
		$this->configAdapter = new UnzerCw_ConfigurationAdapter();
	}
	
	public function run() {
		$this->removeCancelledOrders();
		$this->updateOrders();
	}
	
	protected function removeCancelledOrders() {
		$remove = strtolower($this->configAdapter->getConfigurationValue('remove_cancelled_orders'));
		if ($remove == 'yes') {
			$cancelledOrderStatus = (int)$this->configAdapter->getConfigurationValue('cancelled_order_status');
			$rs =UnzerCw_Database::prepare('SELECT orders_id FROM ' . TABLE_ORDERS . ' WHERE orders_status = %d', array($cancelledOrderStatus));
			while ($row = UnzerCw_Database::fetch($rs)) {
				$this->removeOrder($row['orders_id']);
			}
		}
	}
	
	protected function removeOrder($orderId) {
		
		// Fix stock
		$order_query = UnzerCw_Database::prepare("SELECT products_id, products_quantity FROM ".TABLE_ORDERS_PRODUCTS." WHERE orders_id = '%d'", array($orderId));
		while ($order = UnzerCw_Database::fetch($order_query)) {
			UnzerCw_Database::prepare(
				"UPDATE ".TABLE_PRODUCTS." SET products_quantity = products_quantity + %d, products_ordered = products_ordered - %d WHERE products_id = '%d'",
				array($order['products_quantity'], $order['products_quantity'], $order['products_id'])
			);
		}
		
		// Remove related entities
		UnzerCw_Database::prepare("DELETE FROM ".TABLE_ORDERS." WHERE orders_id = '%d'", array($orderId));
		UnzerCw_Database::prepare("DELETE FROM ".TABLE_ORDERS_PRODUCTS." WHERE orders_id = '%d'", array($orderId));
		UnzerCw_Database::prepare("DELETE FROM ".TABLE_ORDERS_PRODUCTS_ATTRIBUTES." WHERE orders_id = '%d'", array($orderId));
		UnzerCw_Database::prepare("DELETE FROM ".TABLE_ORDERS_STATUS_HISTORY." WHERE orders_id = '%d'", array($orderId));
		UnzerCw_Database::prepare("DELETE FROM ".TABLE_ORDERS_TOTAL." WHERE orders_id = '%d'", array($orderId));
	}
	
	protected function updateOrders() {
		$packages = array(
			0 => 'Customweb_Unzer',
 			1 => 'Customweb_Payment_Authorization',
 		);
		$packages[] = 'UnzerCw_';
		$cron = new Customweb_Cron_Processor(UnzerCw_Util::createContainer(), $packages);
		$cron->run();
	}
}

