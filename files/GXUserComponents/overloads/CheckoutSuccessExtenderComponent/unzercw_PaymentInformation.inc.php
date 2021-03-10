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
if (!class_exists('unzercw_PaymentInformation', false)) {
	$baseAdminPath = defined('DIR_ADMIN') ? DIR_ADMIN : 'admin/';
	require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/' . $baseAdminPath . 'includes/UnzerCw/init.php';
	require_once 'UnzerCw/Util.php';

	/**
	 * Set payment information on success page.
	 *
	 */
	class unzercw_PaymentInformation extends unzercw_PaymentInformation_parent {

		public function proceed(){
			parent::proceed();
			$this->setPaymentInformation();
		}

		private function setPaymentInformation(){
			$paymentInformation = UnzerCw_Util::getPaymentInformation($this->v_data_array['orders_id']);
			if ($paymentInformation) {
				$this->v_output_buffer[] = (string) $paymentInformation;
				$this->html_output_array[] = (string) $paymentInformation;
			}
		}
	}
}