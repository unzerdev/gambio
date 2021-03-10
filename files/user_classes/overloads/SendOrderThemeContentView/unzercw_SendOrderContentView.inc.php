<?php
/**
 *  * You are allowed to use this API in your web application.
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
if (!class_exists('unzercw_SendOrderContentView', false)) {

	$baseAdminPath = defined('DIR_ADMIN') ? DIR_ADMIN : 'admin/';
	require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/' . $baseAdminPath . 'includes/UnzerCw/init.php';
	require_once 'UnzerCw/Util.php';

	/**
	 * Set payment information in order confirmation email
	 */
	class unzercw_SendOrderContentView extends unzercw_SendOrderContentView_parent {

		public function set_payment_info_html($html){
			parent::set_payment_info_html($html);
			$paymentInformation = UnzerCw_Util::getPaymentInformation($this->order_id);
			if ($paymentInformation) {
				$this->payment_info_html = (string) $paymentInformation;
			}
		}

		public function set_payment_info_text($text){
			parent::set_payment_info_text($text);
			$paymentInformation = UnzerCw_Util::getPaymentInformation($this->order_id);
			if ($paymentInformation) {
				$this->payment_info_text = $this->prepareTextPaymentInformation($paymentInformation);
			}
		}

		protected function prepareTextPaymentInformation($paymentInformation){
			return strip_tags(str_replace(array(
				"<br />",
				"<br>",
				"<br/>"
			), "\n", (string) $paymentInformation));
		}
	}
}