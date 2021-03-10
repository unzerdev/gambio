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

require_once dirname(dirname(dirname(__FILE__))) . '/UnzerCw/init.php';


require_once 'UnzerCw/AbstractModule.php';
require_once 'UnzerCw/OrderStatus.php';


class unzercw extends UnzerCw_AbstractModule
{

	public function __construct() {
		parent::__construct();
	}
	
	public function unzercw() {
		self::__construct();
	}

	public function getSettings() {
		$settings = parent::getSettings();

		$settings['create_pending_orders'] = array (
			'title' => unzercw_translate('Create Pending Orders'),
			'description' => unzercw_translate('By creating pending (temporary) orders the order id is transmitted to Unzer. Otherwise only a transaction id is transmitted. However also failed transactions creates orders.'),
			'type' => 'select',
			'options' => array(
				'yes' => unzercw_translate('Yes'),
				'no' => unzercw_translate('No'),
			),
			'default' => 'yes',
		);

		$settings['pending_order_status'] = array (
			'title' => unzercw_translate('Pending Order Status'),
			'description' => unzercw_translate("Which order status should be set for pending orders?"),
			'type' => 'orderstatusselect',
			'default' => UnzerCw_OrderStatus::getStatusIdByKey('pending'),
		);

		$settings['remove_cancelled_orders'] = array (
			'title' => unzercw_translate('Remove Cancelled Orders'),
			'description' => unzercw_translate("Should cancelled orders be removed from the database?"),
			'type' => 'select',
			'options' => array(
				'yes' => unzercw_translate('Yes'),
				'no' => unzercw_translate('No'),
			),
			'default' => 'yes',
		);

		$settings['cancelled_order_status'] = array (
			'title' => unzercw_translate('Cancelled Order Status'),
			'description' => unzercw_translate("Which order status should be set for orders, which are cancelled? If you select the option to remove cancelled orders, then all orders with this status get removed."),
			'type' => 'orderstatusselect',
			'default' => UnzerCw_OrderStatus::getStatusIdByKey('cancelled'),
		);

		$settings['database_encoding'] = array (
			'title' => unzercw_translate('Database Encoding'),
			'description' => unzercw_translate("Depending on your installation, you may have a database encoding different to UTF-8. In this case you may want to correct the encoding, before sent to Unzer."),
			'type' => 'select',
			'options' => array(
				'none' => unzercw_translate('Do not change the encoding.'),
				'encode' => unzercw_translate("Encode the data to UTF-8"),
			),
			'default' => 'encode',
		);

		$settings['include_css'] = array (
			'title' => unzercw_translate('Include CSS'),
			'description' => unzercw_translate('During the checkout some input fields may be rendered. To improve the style of these fields a CSS file can be included. Do you want to add the additional CSS?'),
			'type' => 'select',
			'options' => array(
				'yes' => unzercw_translate('Yes, add the additional CSS file.'),
				'no' => unzercw_translate('No, no not add any CSS file'),
			),
			'default' => 'yes',
		);

		$primarySettings = array(
			'operating_mode' => array(
				'title' => unzercw_translate("Operation Mode"),
 				'description' => unzercw_translate("Operation mode of the shop."),
 				'type' => 'SELECT',
 				'options' => array(
					'test' => unzercw_translate("Test"),
 					'live' => unzercw_translate("Live"),
 				),
 				'default' => 'test',
 			),
 			'public_key_live' => array(
				'title' => unzercw_translate("Public Key (Live)"),
 				'description' => unzercw_translate("Public Key for live requests, provided by Unzer."),
 				'type' => 'TEXTFIELD',
 				'default' => '',
 			),
 			'private_key_live' => array(
				'title' => unzercw_translate("Private Key (Live)"),
 				'description' => unzercw_translate("Private Key for live requests, provided by Unzer."),
 				'type' => 'TEXTFIELD',
 				'default' => '',
 			),
 			'public_key_test' => array(
				'title' => unzercw_translate("Public Key (Test)"),
 				'description' => unzercw_translate("Public Key for test requests, provided by Unzer."),
 				'type' => 'TEXTFIELD',
 				'default' => '',
 			),
 			'private_key_test' => array(
				'title' => unzercw_translate("Private Key (Test)"),
 				'description' => unzercw_translate("Private Key for test requests, provided by Unzer."),
 				'type' => 'TEXTFIELD',
 				'default' => '',
 			),
 			'order_id_schema' => array(
				'title' => unzercw_translate("OrderId Schema"),
 				'description' => unzercw_translate("Here you can set a schema for the orderId parameter transmitted to identify the payment. If left empty it is not transmitted. The following placeholders can be used: {oid} for the order id, which may not be unique or set; {tid} for the sellxed transaction id which is a unique number, or {id} which contains the order id and is guaranteed to be unique."),
 				'type' => 'TEXTFIELD',
 				'default' => '{id}',
 			),
 			'payment_reference_schema' => array(
				'title' => unzercw_translate("PaymentReference Schema"),
 				'description' => unzercw_translate("Here you can set a schema for the paymentReference parameter transmitted to identify the payment. If left empty it is not transmitted. The following placeholders can be used: {oid} for the order id, which may not be unique or set; {tid} for the sellxed transaction id which is a unique number, or {id} which contains the order id and is guaranteed to be unique."),
 				'type' => 'TEXTFIELD',
 				'default' => '{id}',
 			),
 			'invoice_id_schema' => array(
				'title' => unzercw_translate("InvoiceID Schema"),
 				'description' => unzercw_translate("Here you can set a schema for the invoiceId parameter transmitted to identify the payment. If left empty it is not transmitted. The following placeholders can be used: {oid} for the order id, which may not be unique or set; {tid} for the sellxed transaction id which is a unique number, or {id} which contains the order id and is guaranteed to be unique."),
 				'type' => 'TEXTFIELD',
 				'default' => '{id}',
 			),
 			'log_level' => array(
				'title' => unzercw_translate("Log Level"),
 				'description' => unzercw_translate("Messages of this or a higher level will be logged."),
 				'type' => 'SELECT',
 				'options' => array(
					'error' => unzercw_translate("Error"),
 					'info' => unzercw_translate("Info"),
 					'debug' => unzercw_translate("Debug"),
 				),
 				'default' => 'error',
 			),
 		);

		return array_merge($settings, $primarySettings);
	}

	public function getTitle() {
		return unzercw_translate('Unzer Base Module');
	}

	public function getDescription() {
		$description = unzercw_translate('This module is used to install / deinstall the main configurations for the Unzer gateway.');
		if ($this->enabled) {
			$description .= '<br />';
			$description .= '<a href="' . $this->getSettingsUrl() . '" target="_blank" class="button" style="white-space: nowrap; width: auto;">' . unzercw_translate('Open Main Configuration') . '</a>';
		}
		return $description;
	}

	public function getConstantPrefix() {
		return 'MODULE_';
	}

	public function display() {
		return array(
			'text' =>
			'<br>' . xtc_button(BUTTON_UPDATE) .
				xtc_button_link(BUTTON_CANCEL, xtc_href_link(FILENAME_MODULE_EXPORT, 'set=' . $_GET['set'] . '&module=unzercw'))
		);
	}

	public function install() {
		parent::install();
	}

	public function process() {

	}

}