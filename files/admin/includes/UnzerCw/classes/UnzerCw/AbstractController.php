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

require_once 'Customweb/Core/Url.php';
require_once 'Customweb/Licensing/UnzerCw/License.php';

require_once 'UnzerCw/HttpRequest.php';
require_once 'UnzerCw/Layout.php';


abstract class UnzerCw_AbstractController {

	private $viewsPath = null;
	private $viewData = array();

	/**
	 *
	 * @var UnzerCw_Layout
	 */
	private $layout = null;

	public function __construct() {
		 $this->setViewsPath($this->getUnzerCwBasePath() . '/views/' . strtolower($this->getControllerName()));
		 $this->layout = new UnzerCw_Layout();
	}

	/**
	 * @param UnzerCw_Layout $layout
	 */
	public function setLayout(UnzerCw_Layout $layout) {
		$this->layout = $layout;
		return $this;
	}

	/**
	 *
	 * @param string $message
	 * @param string $type Either 'error', 'success' or 'info'
	 */
	public function setMessage($message, $type) {
		if (!isset($_SESSION['unzercw_messages'])) {
			$_SESSION['unzercw_messages'] = array();
		}

		if (!isset($_SESSION['unzercw_messages'][$type])) {
			$_SESSION['unzercw_messages'][$type] = array();
		}
		$_SESSION['unzercw_messages'][$type][] = (string)$message;
	}

	public function getMessages() {
		return $_SESSION['unzercw_messages'];
	}

	public function resetMessages() {
		$_SESSION['unzercw_messages'] = array();
	}

	/**
	 * @return UnzerCw_Layout
	 */
	public function getLayout() {
		return $this->layout;
	}

	public function getViewData() {
		return $this->viewData;
	}

	public function setViewData(array $data) {
		$this->viewData = $data;
		return $this;
	}

	public function appendViewData($key, $value) {
		$this->viewData[$key] = $value;
		return $this;
	}

	public function getViewsPath() {
		return $this->viewsPath;
	}

	public function setViewsPath($viewsPath) {
		$this->viewsPath = $viewsPath;
		return $this;
	}

	public function getUnzerCwBasePath() {
		return dirname(dirname(dirname(__FILE__)));
	}

	
	public function render($viewName) {
		$variables = array();
		$variables['mainContent'] = $this->fetchView($viewName);

		if (false) {
			$reason = Customweb_Licensing_UnzerCw_License::getValidationErrorMessage();
			if ($reason === null) {
				$reason = 'Unknown error.';
			}
			$token = Customweb_Licensing_UnzerCw_License::getCurrentToken();
			$variables['mainContent'] = '<div class="alert alert-danger">' . unzercw_translate('There is a problem with your license. Please contact us (www.sellxed.com/support). Reason: !reason Current Token: !token', array('!reason' => $reason, '!token' => $token)) . '</div>' . $variables['mainContent'];
		}

		$variables['messages'] = $this->getMessages();
		$this->resetMessages();
 		return $this->getLayout()->render($variables);
	}
	

	public function fetchView($viewName) {
		$fileName = $this->getViewsPath() . '/' . $viewName . '.php';
		if (!file_exists($fileName)) {
			throw new Exception("Could not fetch view '" . $viewName . "'. Tried with file '" . $fileName . "'.");
		}

		extract($this->getViewData());
		ob_start();
		require $fileName;
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	public function redirectAction($targetAction, array $params = array()) {
		$this->redirect(str_replace('&amp;', '&', self::getControllerUrl($this->getControllerName(), $targetAction, $params)));
	}

	public function getActionUrl($actionName, array $params = array()) {
		return self::getControllerUrl($this->getControllerName(), $actionName, $params);
	}

	public static function getControllerUrl($controllerName, $action, array $params = array(), $absolute = true) {
		$params['controller'] = $controllerName;
		$params['action'] = $action;
		$prefix = '';
		if ($absolute) {
			$prefix = dirname(UnzerCw_HttpRequest::getInstance()->getUrl()) . '/';
		}

 		return $prefix . 'unzercw.php?' . Customweb_Core_Url::parseArrayToString($params);
	}

	public function getControllerName() {
		$className = get_class($this);
		return str_ireplace('UnzerCw_Controller_', '', $className);
	}

	public function redirect($url) {
		header('Location: ' . $url);
		die();
	}


}