<?php

class UnzerCw_BackendOrder {
	var $info, $totals, $products, $customer, $delivery, $content_type;

	public function __construct($order_id) {
		global $xtPrice;
		$this->info = array();
		$this->totals = array();
		$this->products = array();
		$this->customer = array();
		$this->delivery = array();


		$this->query($order_id);
	}

	function query($order_id) {

		$order_id = xtc_db_prepare_input($order_id);

		$order_query = xtc_db_query("SELECT
			*
			FROM " . TABLE_ORDERS . " WHERE
			orders_id = '" . xtc_db_input($order_id) . "'");

		$order = xtc_db_fetch_array($order_query);

		$totals_query = xtc_db_query("SELECT * FROM " . TABLE_ORDERS_TOTAL . " where orders_id = '" . xtc_db_input($order_id) . "' order by sort_order");
		while ($totals = xtc_db_fetch_array($totals_query)) {
			$this->totals[] = array('title' => $totals['title'],
				'text' =>$totals['text'],
				'value'=>$totals['value']);
		}

		$order_total_query = xtc_db_query("select text, value from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . $order_id . "' and class = 'ot_total'");
		$order_total = xtc_db_fetch_array($order_total_query);

		$shipping_method_query = xtc_db_query("select title from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . $order_id . "' and class = 'ot_shipping'");
		$shipping_method = xtc_db_fetch_array($shipping_method_query);

		$order_status_query = xtc_db_query("select orders_status_name from " . TABLE_ORDERS_STATUS . " where orders_status_id = '" . $order['orders_status'] . "' and language_id = '" . $_SESSION['languages_id'] . "'");
		$order_status = xtc_db_fetch_array($order_status_query);

		$this->info = array('currency' => $order['currency'],
			'currency_value' => $order['currency_value'],
			'payment_method' => $order['payment_method'],
			'cc_type' => $order['cc_type'],
			'cc_owner' => $order['cc_owner'],
			'cc_number' => $order['cc_number'],
			'cc_expires' => $order['cc_expires'],
			// BMC CC Mod Start
			'cc_start' => $order['cc_start'],
			'cc_issue' => $order['cc_issue'],
			'cc_cvv' => $order['cc_cvv'],
			// BMC CC Mod End
			'date_purchased' => $order['date_purchased'],
			'orders_status' => $order_status['orders_status_name'],
			'last_modified' => $order['last_modified'],
			'total' => $order_total['value'],
			'shipping_method' => ((substr($shipping_method['title'], -1) == ':') ? substr(strip_tags($shipping_method['title']), 0, -1) : strip_tags($shipping_method['title'])),
			'comments' => $order['comments']
		);
		$houseNr = isset($order['customers_house_number']) ? ' ' . $order['customers_house_number'] : '';
		$this->customer = array('id' => $order['customers_id'],
			'name' => $order['customers_name'],
			'firstname' => $order['customers_firstname'],
			'lastname' => $order['customers_lastname'],
			'csID' => $order['customers_cid'],
			'company' => $order['customers_company'],
			'street_address' => $order['customers_street_address'] . $houseNr,
			'suburb' => $order['customers_suburb'],
			'city' => $order['customers_city'],
			'postcode' => $order['customers_postcode'],
			'state' => $order['customers_state'],
			'country' => $order['customers_country'],
			'format_id' => $order['customers_address_format_id'],
			'telephone' => $order['customers_telephone'],
			'email_address' => $order['customers_email_address']);
		$houseNr = isset($order['delivery_house_number']) ? ' ' . $order['delivery_house_number'] : '';
		$this->delivery = array('name' => $order['delivery_name'],
			'firstname' => $order['delivery_firstname'],
			'lastname' => $order['delivery_lastname'],
			'company' => $order['delivery_company'],
			'street_address' => $order['delivery_street_address'] . $houseNr,
			'suburb' => $order['delivery_suburb'],
			'city' => $order['delivery_city'],
			'postcode' => $order['delivery_postcode'],
			'state' => $order['delivery_state'],
			'country' => array('iso_code_2' => $order['delivery_country_iso_code_2']),
			'format_id' => $order['delivery_address_format_id']);

		if (empty($this->delivery['name']) && empty($this->delivery['street_address'])) {
			$this->delivery = false;
		}

		$houseNr = isset($order['billing_house_number']) ? ' ' . $order['billing_house_number'] : '';
		$this->billing = array('name' => $order['billing_name'],
			'firstname' => $order['billing_firstname'],
			'lastname' => $order['billing_lastname'],
			'company' => $order['billing_company'],
			'street_address' => $order['billing_street_address'] . $houseNr,
			'suburb' => $order['billing_suburb'],
			'city' => $order['billing_city'],
			'postcode' => $order['billing_postcode'],
			'state' => $order['billing_state'],
			'country' => array('iso_code_2' => $order['billing_country_iso_code_2']),
			'format_id' => $order['billing_address_format_id']);

		$index = 0;
		$orders_products_query = xtc_db_query("SELECT * FROM " . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . xtc_db_input($order_id) . "'");
		while ($orders_products = xtc_db_fetch_array($orders_products_query)) {
			$this->products[$index] = array('qty' => $orders_products['products_quantity'],
				'id' => $orders_products['products_id'],
				'name' => $orders_products['products_name'],
				'model' => $orders_products['products_model'],
				'tax' => $orders_products['products_tax'],
				'price'=>$orders_products['products_price'],
				'shipping_time'=>$orders_products['products_shipping_time'],
				'final_price' => $orders_products['final_price']);

			$subindex = 0;
			$attributes_query = xtc_db_query("SELECT * FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " where orders_id = '" . xtc_db_input($order_id) . "' and orders_products_id = '" . $orders_products['orders_products_id'] . "'");
			if (xtc_db_num_rows($attributes_query)) {
				while ($attributes = xtc_db_fetch_array($attributes_query)) {
					$this->products[$index]['attributes'][$subindex] = array('option' => $attributes['products_options'],
						'value' => $attributes['products_options_values'],
						'prefix' => $attributes['price_prefix'],
						'price' => $attributes['options_values_price']);

					$subindex++;
				}
			}

			$this->info['tax_groups']["{$this->products[$index]['tax']}"] = '1';

			$index++;
		}
	}


}
