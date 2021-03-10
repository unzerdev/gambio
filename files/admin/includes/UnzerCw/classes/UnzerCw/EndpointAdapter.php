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

require_once 'Customweb/Payment/Endpoint/AbstractAdapter.php';
require_once 'Customweb/Form/Renderer.php';

require_once 'UnzerCw/Util.php';


/**
 * 
 * @author Thomas Hunziker
 * @Bean
 *
 */
class UnzerCw_EndpointAdapter extends Customweb_Payment_Endpoint_AbstractAdapter{
	
	protected function getBaseUrl() {
		$params = array();
		if (isset($_SESSION['language_code'])) {
			$params['language'] = $_SESSION['language_code'];
		}
		return UnzerCw_Util::getFrontendUrl('unzercw_endpoint.php', $params, true);
	}
	
	protected function getControllerQueryKey() {
		return 'pc';
	}
	
	protected function getActionQueryKey() {
		return 'pa';
	}
	
	public function getFormRenderer() {
		return new Customweb_Form_Renderer();
	}
}