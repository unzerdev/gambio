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


require_once 'UnzerCw/ConfigurationTableAdapter.php';
require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/Database.php';


final class UnzerCw_OrderStatus {

	private static $orderStatuses = null;

	private function __construct() {

	}

	public static function installOrderStatuses() {
		$statuses = array();
		$statuses['uncertain'] = array(
			'title' => array(
				'de' => 'Zahlung unsicher (Unzer)',
				'en' => 'Payment uncertain (Unzer)',
			),
		);
		$statuses['cancelled'] = array(
			'title' => array(
				'de' => 'Zahlung abgebrochen (Unzer)',
				'en' => 'Payment cancelled (Unzer)',
			),
		);
		$statuses['pending'] = array(
			'title' => array(
				'de' => 'Bevorstehende Zahlung (Unzer)',
				'en' => 'Pending Payment (Unzer)',
			),
		);


		foreach ($statuses as $statusKey => $status) {

			$configKey = self::getStatusConfigKey($statusKey);
			$id = self::getStatusIdByKey($statusKey);
			if ($id === null) {
				$row = UnzerCw_Database::fetch(UnzerCw_Database::query("SELECT max(orders_status_id) as orders_status_id FROM " . TABLE_ORDERS_STATUS . ""));
				$statusId = $row['orders_status_id'] + 1;
				foreach (UnzerCw_Util::getLanguages() as $langId => $lang) {

					if (isset($status['title'][$lang['code']])) {
						$title = $status['title'][$lang['code']];
					}
					else {
						$title = $status['title']['en'];
					}
					UnzerCw_Database::query("INSERT INTO " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) VALUES ('" . $statusId . "', '" . $langId . "', '" . $title . "');");
				}

				UnzerCw_ConfigurationTableAdapter::insert($configKey, $statusId);
			}
		}


	}

	private static function getStatusConfigKey($statusKey) {
		return 'MODULE_UNZERCW_STATUS_ID_' . strtoupper($statusKey);
	}

	public static function getStatusIdByKey($key) {
		if ($key == 'authorized') {
			return 2;
		}
		$configKey = self::getStatusConfigKey($key);

		return UnzerCw_ConfigurationTableAdapter::read($configKey);
	}

	public static function getOrderStatuses() {

		if (self::$orderStatuses === null) {
			self::$orderStatuses = array ();
			$orders_status_query = UnzerCw_Database::query("SELECT orders_status_id, orders_status_name FROM ".TABLE_ORDERS_STATUS." WHERE language_id = '".$_SESSION['languages_id']."' order by orders_status_id");
			while ($orders_status = UnzerCw_Database::fetch($orders_status_query)) {
				self::$orderStatuses[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
			}
		}

		return self::$orderStatuses;
	}



}