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



final class UnzerCw_AuthorizationScript {

	public function generateFile(){
		$filePath = $this->getFilePath();
		
		if (!file_exists($filePath)) {
			$content = file_get_contents($this->getCheckoutFilePath());
			$content = $this->modifyScript($content);
			file_put_contents($filePath, $content);
		}
		
		return $filePath;
	}

	protected function getFilePath(){
		$time = filemtime($this->getCheckoutFilePath());
		return UNZERCW_CATALOG_PATH . '/templates_c/unzercw_checkout_process_' . $time . '_v2.php';
	}

	protected function getCheckoutFilePath(){
		return UNZERCW_CATALOG_PATH . '/checkout_process.php';
	}

	protected function modifyScript($content){
		
		// Make sure we do not run the bootstrap process again
		$content = str_replace("include ('includes/application_top.php');", '', $content);
		$content = str_replace("include('includes/application_top.php');", '', $content);
		
		// We have already checked the stock, we do not need to check it again:
		$content = str_replace('STOCK_ALLOW_CHECKOUT_DEACTIVATE', "'true'", $content);
		$content = str_replace('STOCK_ALLOW_CHECKOUT', "'true'", $content);
		
		// We do not use the original path, we need to make sure that script works anyway.
		$content = str_replace('__FILE__', '"' . $this->getCheckoutFilePath() . '"', $content);
		
		// We need a hook before sending the e-mail to modify the message.
		$sendOrderPosition = strpos($content, "send_order.php");
		
		// We may not find the send_order hence we need to skip. (e.g. Gambio)
		if ($sendOrderPosition !== false) {
			$includePosition = strrpos(substr($content, 0, $sendOrderPosition), 'include');
			$content = substr($content, 0, $includePosition) . "\n" .
					 'if (isset($GLOBALS[$payment_modules->selected_module]) && method_exists($GLOBALS[$payment_modules->selected_module], "beforeSendOrderConfirmation")){ $GLOBALS[$payment_modules->selected_module]->beforeSendOrderConfirmation(); }' . "\n" .
					 substr($content, $includePosition);
		}
		
		return $content;
	}

	public function loadSession(){
		$path = UNZERCW_TMP_SESSION_DIRECTORY_PATH . $_REQUEST['session_file_name'];
		if (!file_exists($path)) {
			throw new Exception("Invalid session file.");
		}
		$_SESSION = unserialize(file_get_contents($path));
	}
} 
