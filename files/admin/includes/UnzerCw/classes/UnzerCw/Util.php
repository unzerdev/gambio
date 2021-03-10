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

require_once 'Customweb/Payment/Alias/Handler.php';
require_once 'Customweb/DependencyInjection/Container/Default.php';
require_once 'Customweb/Asset/Resolver/Composite.php';
require_once 'Customweb/Mvc/Template/Smarty/ContainerBean.php';
require_once 'Customweb/Core/Util/Rand.php';
require_once 'Customweb/Util/Html.php';
require_once 'Customweb/Core/Util/Serialization.php';
require_once 'Customweb/Core/Http/Response.php';
require_once 'Customweb/Database/Entity/Manager.php';
require_once 'Customweb/Core/Http/ContextRequest.php';
require_once 'Customweb/Payment/Endpoint/Dispatcher.php';
require_once 'Customweb/Cache/Backend/Memory.php';
require_once 'Customweb/Asset/Resolver/Simple.php';
require_once 'Customweb/Core/Http/Request.php';
require_once 'Customweb/Core/Logger/Factory.php';
require_once 'Customweb/Core/Url.php';
require_once 'Customweb/Core/Assert.php';
require_once 'Customweb/Core/Http/Client/Factory.php';
require_once 'Customweb/Core/Util/Error.php';
require_once 'Customweb/DependencyInjection/Bean/Provider/Annotation.php';
require_once 'Customweb/Date/DateTime.php';
require_once 'Customweb/Database/Migration/Manager.php';
require_once 'Customweb/DependencyInjection/Bean/Provider/Editable.php';
require_once 'Customweb/Core/Util/Class.php';
require_once 'Customweb/Payment/Authorization/IAdapterFactory.php';
require_once 'Customweb/Mvc/Layout/RenderContext.php';

require_once 'UnzerCw/Form/FrontendRenderer.php';
require_once 'UnzerCw/Database.php';
require_once 'UnzerCw/AbstractController.php';
require_once 'UnzerCw/Adapter/IAdapter.php';
require_once 'UnzerCw/EndpointAdapter.php';
require_once 'UnzerCw/AbstractModule.php';
require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/HttpRequest.php';
require_once 'UnzerCw/LayoutRenderer.php';
require_once 'UnzerCw/Form/BackendRenderer.php';
require_once 'UnzerCw/Entity/Util.php';


final class UnzerCw_Util {

	private static $baseModule = null;
	private static $encodingSetting = null;
	private static $countries = null;
	private static $currencies = null;
	private static $languages = null;
	private static $charset = null;
	private static $container = null;
	private static $entityManager = null;
	private static $driver = null;
	private static $schemaMigrated = false;
	private static $resolver = null;

	private static $allMethods = null;
	/**
	 * Map of order_id to payment_information, used to cache
	 * @var array
	 */
	private static $paymentInformation = array();
	
	private function __construct() {}

	public static function getCurrentCharSet() {
		if (self::$charset === null) {
			foreach (self::getLanguages() as $lang) {
				if ($lang['code'] == $_SESSION['language_code']) {
					self::$charset = strtolower($lang['language_charset']);
					break;
				}
			}
		}
		return self::$charset;
	}

	public static function getCurrentLanguageCode() {
		if (isset($_SESSION['language_code'])) {
			return $_SESSION['language_code'];
		}
		else if (isset($_SESSION['languages_id'])) {
			$statement = self::getDriver()->query('SELECT * FROM ' . TABLE_LANGUAGES . ' WHERE languages_id = ' . (int) $_SESSION['languages_id']);
			while (($row = $statement->fetch()) !== false) {
				return $row['code'];
			}
		}
		$language = self::getAcceptLanguageHeader(Customweb_Core_Http_ContextRequest::getInstance()->getParsedHeaders());
		if($language){
			return $language;
		}
		$language = self::getAcceptLanguageHeader($_SERVER);
		if($language){
			return $language;
		}
		return 'DE';
	}
	
	private static function getAcceptLanguageHeader(array $headers) {
		foreach($headers as $key => $value) {
			if(strtolower($key) === 'accept-language') {
				return $value;
			}
		}
		return null;
	}

	public static function handlePageOutput($output) {
		$charset = self::getCurrentCharSet();
		if ($charset == 'iso-8859-15') {
			return utf8_decode($output);
		}
		else {
			return $output;
		}
	}

	public static function getDateOfBirth($customerId) {
		$rs = UnzerCw_Database::query('SELECT customers_dob FROM ' . TABLE_CUSTOMERS . ' WHERE customers_id = ' . (int)$customerId);
		if ($row = UnzerCw_Database::fetch($rs)) {
			if (isset($row['customers_dob']) && !empty($row['customers_dob']) && $row['customers_dob'] != '0000-00-00 00:00:00' && $row['customers_dob'] != '1000-01-01 00:00:00') {
				return new Customweb_Date_DateTime($row['customers_dob']);
			}
		}

		return null;
	}

	/**
	 * This method decodes a string which is coming from the database.
	 *
	 * @param string $string
	 */
	public static function decode($string) {
		if (self::$encodingSetting === null) {
			self::$encodingSetting = self::getBaseModule()->getSettingValue('database_encoding');
		}

		if (self::$encodingSetting == 'encode') {
			return utf8_encode($string);
		}
		else {
			return $string;
		}
	}

	/**
	 * This method converts a UTF-8 string into the database encoding.
	 *
	 * @param string $string
	 * @return string
	 */
	public static function encode($string) {
		if (self::$encodingSetting === null) {
			self::$encodingSetting = self::getBaseModule()->getSettingValue('database_encoding');
		}

		if (self::$encodingSetting == 'encode') {
			return utf8_decode($string);
		}
		else {
			return $string;
		}
	}

	/**
	 * @return UnzerCw_AbstractModule
	 */
	private static function getBaseModule() {
		if (self::$baseModule === null) {
			self::$baseModule = UnzerCw_AbstractModule::getModulInstanceByClass('unzercw');
		}
		return self::$baseModule;
	}

	public static function includePaymentMethod($methodClass) {
		$dir = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/includes/modules/payment/';
		require_once $dir . $methodClass . '.php';
	}

	public static function getAllPaymentMethods() {
		if (self::$allMethods === null) {
			self::$allMethods = array();
			$dirName = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/includes/modules/payment/';
			if ($handle = opendir($dirName)) {
				while (false !== ($file = readdir($handle))) {
					$filePath = $dirName . $file;
					if ($file != "." && $file != ".." && !is_dir($filePath) && substr($file, -4) === '.php' && strpos($file, 'unzercw_') === 0) {
						$className = substr($file, 0, -4);
						self::$allMethods[] = UnzerCw_AbstractModule::getModulInstanceByClass($className);
					}
				}
				closedir($handle);
			}
			else {
				throw new Exception("Unable to read external checkout directory.");
			}
		}

		return self::$allMethods;
	}

	public static function getFrontendUrl($file, $params = array(), $ssl = true) {

		$server = HTTP_SERVER;

		$enabled = false;

		// We are in the frontend
		if (defined("ENABLE_SSL")) {
			if (is_bool(ENABLE_SSL)) {
				$enabled = ENABLE_SSL;
			}
			else if (is_string(ENABLE_SSL)) {
				$enabled = ENABLE_SSL == 'true';
			}
		}

		// We are in the backend:
		else if (defined("ENABLE_SSL_CATALOG")) {
			if (is_bool(ENABLE_SSL_CATALOG)) {
				$enabled = ENABLE_SSL_CATALOG;
			}
			else if (is_string(ENABLE_SSL_CATALOG)) {
				$enabled = ENABLE_SSL_CATALOG == 'true';
			}
		}

		if ($enabled && $ssl == true) {
			if (defined('HTTPS_SERVER')) {
				$server = HTTPS_SERVER;
			}
			else if (defined('HTTPS_CATALOG_SERVER')) {
				$server = HTTPS_CATALOG_SERVER;
			}
		}
		$url = new Customweb_Core_Url($server . DIR_WS_CATALOG . $file);
		$url->setQuery($params);

		return $url->toString();
	}


	public static function getZones() {
		$zones = array(); //array('id' => TEXT_NONE);
		$zone_class_query = UnzerCw_Database::query("SELECT geo_zone_id, geo_zone_name FROM ".TABLE_GEO_ZONES." ORDER BY geo_zone_name");
		while ($zone_class = UnzerCw_Database::fetch($zone_class_query)) {
			$zones[$zone_class['geo_zone_id']] = $zone_class['geo_zone_name'];
		}
		return $zones;
	}


	public static function getZoneIdByStateCode($stateCode, $countryCode) {
		$zone = self::getZoneByStateCode($stateCode, $countryCode);
		return $zone['zone_id'];
	}

	public static function getZoneByStateCode($stateCode, $countryCode) {
		Customweb_Core_Assert::hasLength($stateCode);
		$countryId = self::getCountryIdByCode($countryCode);
		$rs = self::getDriver()->query("SELECT * FROM " . TABLE_ZONES . " WHERE zone_country_id = !countryId AND zone_code = >zoneCode")
		->setParameter(">zoneCode", $stateCode)
		->setParameter('!countryId', $countryId)
		->fetch();
		if ($rs !== false) {
			return $rs;
		}
		else {
			throw new Exception("Unable to find state.");
		}
	}


	public static function getCountries() {
		if (self::$countries === null) {
			$rs = UnzerCw_Database::query("SELECT * FROM " . TABLE_COUNTRIES . " ORDER BY countries_name");
			self::$countries = array();
			while ($row = UnzerCw_Database::fetch($rs)) {
				self::$countries[$row['countries_id']] = $row;
			}
		}

		return self::$countries;
	}

	public static function getCountryIdByCode($countryCode) {
		$data = self::getCountryByCode($countryCode);
		return $data['countries_id'];
	}

	public static function getCountryByCode($countryCode) {
		Customweb_Core_Assert::hasLength($countryCode);
		$rs = self::getDriver()->query("SELECT * FROM " . TABLE_COUNTRIES . " WHERE countries_iso_code_2 = >code")->setParameter(">code", $countryCode)->fetch();
		if (is_array($rs)) {
			return $rs;
		}
		else {
			throw new Exception("Unable to find country.");
		}
	}

	public static function getCurrencies() {
		if (self::$currencies === null) {
			self::$currencies = array ();
			$query = UnzerCw_Database::query("SELECT code, title, currencies_id FROM " . TABLE_CURRENCIES . " ORDER BY code");
			while ($row = UnzerCw_Database::fetch($query)) {
				self::$currencies[$row['currencies_id']] = $row;
			}
		}

		return self::$currencies;
	}

	public static function getLanguages() {

		if (self::$languages === null) {
			$rs = UnzerCw_Database::query("SELECT * FROM " . TABLE_LANGUAGES . " ORDER BY code");
			self::$languages = array();
			while ($row = UnzerCw_Database::fetch($rs)) {
				self::$languages[$row['languages_id']] = $row;
			}
		}

		return self::$languages;
	}


	public static function renderContentInFrontend($contentToRender, $title = null) {
		$renderer = self::getLayoutRenderer();
		$context = new Customweb_Mvc_Layout_RenderContext();
		$context->setMainContent($contentToRender);
		if ($title !== null) {
			$context->setTitle($title);
		}
		echo $renderer->render($context);
	}

	/**
	 * @return Customweb_Mvc_Layout_IRenderer
	 */
	public static function getLayoutRenderer() {
		// TODO: Add per implementation a renderer
		return new UnzerCw_LayoutRenderer();
	}

	public static function renderCheckoutFormElements($elements) {
		if ($elements !== null && count($elements) > 0) {
			$renderer = new UnzerCw_Form_FrontendRenderer();
			$renderer->setCssClassPrefix('unzercw-');
			return '<div class="unzercw-confirmation-form" id="unzercw-confirmation-form">' . $renderer->renderElements($elements) . '</div>';
		}
		else {
			return '';
		}
	}

	public static function renderBackendFormElements($elements) {
		if ($elements !== null && count($elements) > 0) {
			$renderer = new UnzerCw_Form_BackendRenderer();
			return '<div class="unzercw-confirmation-form" id="unzercw-confirmation-form">' . $renderer->renderElements($elements) . '</div>';
		}
		else {
			return '';
		}
	}

	public static function renderBackendPopupWindow($title, $controller, $action, $params = array(), $absolute = true) {
		$url = UnzerCw_AbstractController::getControllerUrl($controller, $action, $params, $absolute);
		$windowTitle = str_replace('"', '', $title);
		$windowTitle = str_replace("'", '', $windowTitle);

		$js = 'popup = window.open(this.href, \'' . $windowTitle . '\', \'width=800,height=800,status=yes,scrollbars=yes,resizable=yes\'); popup.focus(); return false;';
		$js = str_replace('"', '\\"', $js);

		$html = '<a href="' . $url . '" target="_blank" onclick="' . $js . '">' . $title . '</a>';

		return $html;
	}

	/**
	 *
	 * @param string $className
	 * @return UnzerCw_PaymentMethod
	 * @throws Exception
	 */
	public static function getPaymentMehtodInstance($methodClassName) {
		return UnzerCw_AbstractModule::getModulInstanceByClass($methodClassName);
	}


	public static function setOrderStatus($statusId, $orderId) {
		if (empty($orderId)) {
			throw new Exception("The order status can not be updated when no order id is provided.");
		}
		UnzerCw_Database::update(TABLE_ORDERS, array('orders_status' => $statusId), array('orders_id' => $orderId));
		UnzerCw_Database::insert(TABLE_ORDERS_STATUS_HISTORY, array(
			'orders_status_id' => $statusId,
			'orders_id' => $orderId,
			'customer_notified' => '0',
			'date_added' => date('Y-m-d H:i:s'),
		));
	}

	public static function cloneArray(array $array) {
		$newArray = array();

		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$newArray[$key] = self::cloneArray($value);
			}
			else if (is_object($value)) {
				$newArray[$key] = clone $value;
			}
			else {
				$newArray[$key] = $value;
			}
		}

		return $newArray;
	}

	public static function renderHiddenFields($hiddenFields) {
		require_once 'Customweb/Util/Html.php';
		return Customweb_Util_Html::buildHiddenInputFields($hiddenFields);

	}

	public static function getJavaScriptCode() {

		$jqueryUrl = UnzerCw_Util::getAssetResolver()->resolveAssetUrl('js/jquery-1.10.2.min.js');
		$js = UnzerCw_Util::getAssetResolver()->resolveAssetUrl('js/unzercw.js');

		$html = '';
		$html .= '<script type="text/javascript" src="' . $jqueryUrl . '"></script>';
		$html .= '<script type="text/javascript">' . "\n";
		$html .= "var unzercw_jquery = jQuery.noConflict(true); \n";
		$html .= "</script>\n";
		$html .= '<script type="text/javascript" src="' . $js . '"></script>';

		return $html;
	}


	/**
	 * @return Customweb_DependencyInjection_Container_Default
	 */
	public static function createContainer() {
		require_once 'Customweb/DependencyInjection/Bean/Provider/Editable.php';
		require_once 'Customweb/DependencyInjection/Bean/Provider/Annotation.php';
		require_once 'Customweb/DependencyInjection/Container/Default.php';

		if (self::$container === null) {
			$packages = array(
			0 => 'Customweb_Unzer',
 			1 => 'Customweb_Payment_Authorization',
 		);
			$packages[] = 'UnzerCw_';
			$packages[] = 'Customweb_Payment_TransactionHandler';
			$packages[] = 'Customweb_Payment_Update_ContainerHandler';
			$packages[] = 'UnzerCw_TemplateRenderer';
			$packages[] = 'Customweb_Payment_SettingHandler';
			$packages[] = 'Customweb_Storage_Backend_Database';

			$provider = new Customweb_DependencyInjection_Bean_Provider_Editable(new Customweb_DependencyInjection_Bean_Provider_Annotation(
					$packages
			));
			$provider->addObject(self::getEntityManager())->addObject(UnzerCw_HttpRequest::getInstance());
			$smarty = new Smarty;
			$templateRenderer = new Customweb_Mvc_Template_Smarty_ContainerBean($smarty);
			$provider->addObject($templateRenderer);
			$provider->add('databaseTransactionClassName', 'UnzerCw_Entity_Transaction');
			$provider->add('storageDatabaseEntityClassName', 'UnzerCw_Entity_Storage');
			$provider->addObject(self::getDriver());
			$provider->addObject(self::getLayoutRenderer());
			$provider->addObject(self::getAssetResolver());

			self::$container = new Customweb_DependencyInjection_Container_Default($provider);
		}

		return self::$container;
	}

	/**
	 * @return Customweb_Database_Entity_Manager
	 */
	public static function getEntityManager() {
		if (self::$entityManager === null) {
			$cache = new Customweb_Cache_Backend_Memory();
			self::$entityManager = new Customweb_Database_Entity_Manager(self::getDriver(), $cache);
		}
		return self::$entityManager;
	}

	/**
	 * @return Customweb_Database_Driver_PDO_Driver
	 */
	public static function getDriver() {
		if (self::$driver === null) {
			self::$driver = UnzerCw_Database::getDriver();
		}
		return self::$driver;
	}

	public static function migrateDatabaseSchema() {
		if (self::$schemaMigrated === false) {
			require_once 'Customweb/Database/Migration/Manager.php';
			$manager = new Customweb_Database_Migration_Manager(self::getDriver(), dirname(__FILE__) . '/Migration/', 'unzercw_schema_version');
			$manager->migrate();
			self::$schemaMigrated = true;
		}
	}

	/**
	 *
	 * @throws Exception
	 * @return Customweb_Payment_Authorization_IAdapterFactory
	 */
	public static function getAuthorizationAdapterFactory() {
		require_once 'Customweb/Payment/Authorization/IAdapterFactory.php';
		$container = self::createContainer();
		$factory = $container->getBean('Customweb_Payment_Authorization_IAdapterFactory');

		if (!($factory instanceof Customweb_Payment_Authorization_IAdapterFactory)) {
			throw new Exception("The payment api has to provide a class which implements 'Customweb_Payment_Authorization_IAdapterFactory' as a bean.");
		}

		return $factory;
	}

	/**
	 *
	 * @param String $authorizationMethodName
	 * @return Customweb_Payment_Authorization_IAdapter
	 */
	public static function getAuthorizationAdapter($authorizationMethodName) {
		return self::getAuthorizationAdapterFactory()->getAuthorizationAdapterByName($authorizationMethodName);
	}

	/**
	 *
	 * @param Customweb_Payment_Authorization_IOrderContext $orderContext
	 * @return Customweb_Payment_Authorization_IAdapter
	 */
	public static function getAuthorizationAdapterByContext(Customweb_Payment_Authorization_IOrderContext $orderContext) {
		return self::getAuthorizationAdapterFactory()->getAuthorizationAdapterByContext($orderContext);
	}


	/**
	 * @param Customweb_Payment_Authorization_IAdapter $paymentAdapter
	 * @throws Exception
	 * @return UnzerCw_Adapter_IAdapter
	 */
	public static function getShopAdapterByPaymentAdapter(Customweb_Payment_Authorization_IAdapter $paymentAdapter) {
		$reflection = new ReflectionClass($paymentAdapter);
		$adapters = self::createContainer()->getBeansByType('UnzerCw_Adapter_IAdapter');
		foreach ($adapters as $adapter) {
			if ($adapter instanceof UnzerCw_Adapter_IAdapter) {
				Customweb_Core_Util_Class::loadLibraryClassByName($adapter->getPaymentAdapterInterfaceName());
				if ($reflection->implementsInterface($adapter->getPaymentAdapterInterfaceName())) {
					$adapter->setInterfaceAdapter($paymentAdapter);
					return $adapter;
				}
			}
		}

		throw new Exception("Could not resolve to Shop adapter.");
	}

	/**
	 *
	 * @return Customweb_Payment_TransactionHandler
	 */
	public static function getTransactionHandler() {
		return self::createContainer()->getBean('Customweb_Payment_TransactionHandler');
	}

	/**
	 * @return Customweb_Payment_Endpoint_Dispatcher
	 */
	public static function getEndpointDispatcher() {
		$packages = array(
			0 => 'Customweb_Unzer',
 			1 => 'Customweb_Payment_Authorization',
 		);
		$dispatcher = new Customweb_Payment_Endpoint_Dispatcher(new UnzerCw_EndpointAdapter(), self::createContainer(), $packages);

		return $dispatcher;
	}

	/**
	 * @return Customweb_Payment_Alias_Handler
	 */
	public static function getAliasHandler() {
		return new Customweb_Payment_Alias_Handler(UnzerCw_Util::getEntityManager(), UnzerCw_Util::createContainer(), 'UnzerCw_Entity_Transaction');
	}

	/**
	 * @return Customweb_Asset_IResolver
	 */
	public static function getAssetResolver() {
		if (self::$resolver === null) {
			self::$resolver = new Customweb_Asset_Resolver_Composite(array(
				new Customweb_Asset_Resolver_Simple(UNZERCW_CATALOG_PATH . '/templates/' . CURRENT_TEMPLATE . '/unzercw/', self::getFrontendUrl('/templates/' . CURRENT_TEMPLATE . '/unzercw/')),
				new Customweb_Asset_Resolver_Simple(UNZERCW_CATALOG_PATH . '/templates/default/unzercw/', self::getFrontendUrl('/templates/default/unzercw/')),
			));
		}

		return self::$resolver;
	}

	/**
	 * @return Customweb_Payment_BackendOperation_Form_IAdapter
	 */
	public static function getBackendFormAdapter() {
		$container = self::createContainer();
		if ($container->hasBean('Customweb_Payment_BackendOperation_Form_IAdapter')) {
			return $container->getBean('Customweb_Payment_BackendOperation_Form_IAdapter');
		}
		else {
			return null;
		}
	}

	public static function processPostData($data) {
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				$data[$key] = self::processPostData($value);
			}
			else {
				$data[$key] = stripslashes(utf8_encode($value));
			}
		}
		return $data;
	}

	/**
	 * @return Customweb_Mvc_Template_IRenderer
	 */
	public static function getTemplateRenderer() {
		return self::createContainer()->getBean('Customweb_Mvc_Template_IRenderer');
	}

	/**
	 * Sends a HTTP request to given file to run tasks in isolation.
	 *
	 * @param string $fileName
	 * @param array $parameters
	 * @param array $sessionData
	 * @return multitype:unknown string
	 */
	public static function sendLocalRequest($fileName, array $parameters, array $sessionData) {

		// Handle session
		$sessionFileName = Customweb_Core_Util_Rand::getRandomString(40);
		$parameters['session_file_name'] = $sessionFileName;
		if (!file_exists(UNZERCW_TMP_SESSION_DIRECTORY_PATH)) {
			mkdir(UNZERCW_TMP_SESSION_DIRECTORY_PATH, 0777, true);
		}
		$sessionFilePath = UNZERCW_TMP_SESSION_DIRECTORY_PATH . $sessionFileName;
		file_put_contents($sessionFilePath, serialize($sessionData));

		// Execute call
		$url = UnzerCw_Util::getFrontendUrl(
				$fileName,
				$parameters
		);

		$context = stream_context_create(
				array (
					'http' => array (
						'follow_location' => false
					)
				)
		);

		Customweb_Core_Util_Error::deactivateErrorMessages();
		$body = file_get_contents($url, false, $context);
		Customweb_Core_Util_Error::activateErrorMessages();

		// Fallback in case file_get_contents does not work.
		if ($body === false) {
			try {
				$request = new Customweb_Core_Http_Request($url);
				$request->appendHeader('Accept-Language: ' . self::getCurrentLanguageCode());
				$client = Customweb_Core_Http_Client_Factory::createClient();
				$client->disableCertificateAuthorityCheck();
				$response = new Customweb_Core_Http_Response($client->send($request));
				$body = $response->getBody();
			}
			catch(Exception $e) {
				$logger = Customweb_Core_Logger_Factory::getLogger(__CLASS__);
				$logger->logException($e);

				// In case the regular socket does not work either, we try to use CURL, when
				// it is installed.
				if (function_exists('curl_version')) {
					Customweb_Core_Util_Error::startErrorHandling();
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

					// We do disable the certificate checks since we send here a request to itself and as such an attacker cannot
					// inject the request. Additionally injecting the request will not reveal any useful information.
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);


					$body = curl_exec($ch);
					$errorMessage = curl_error($ch);
					curl_close($ch);
					Customweb_Core_Util_Error::endErrorHandling();

					// The CURL does not work either. As such we need to throw this error.
					if (!empty($errorMessage)) {
						throw new Exception("The processing of the authorization failed because of: " . $errorMessage);
					}
				}
				else {
					// When curl is not installed we rethow the exception.
					throw $e;
				}
			}
		}

		// Get session data
		$sessionData = unserialize(file_get_contents($sessionFilePath));

		// Clean Up session
		unlink($sessionFilePath);

		return array(
			'body' => $body,
			'sessionData' => $sessionData,
		);
	}
	
	public static function getPaymentInformation($order_id) {
		$paymentInformation = self::getPaymentInformationFromSession();
		if(!$paymentInformation) {
			$paymentInformation = self::getPaymentInformationForOrder($order_id);
		}
		return $paymentInformation;
	}
	
	public static function getPaymentInformationFromSession() {
		if (isset($_SESSION['unzercw_transaction_object'])) {
			$transactionObject = Customweb_Core_Util_Serialization::unserialize($_SESSION['unzercw_transaction_object']);
			return $transactionObject->getPaymentInformation();	
		}
	}
	
	public static function getPaymentInformationForOrder($order_id) {
		try {
			if (!isset(self::$paymentInformation[$order_id])) {
				$transactions = UnzerCw_Entity_Util::findTransactionsEntityByOrderId($order_id);
				if($transactions) {
					$transactionObject= end($transactions)->getTransactionObject();
					if ($transactionObject && $transactionObject->getPaymentInformation() !== null) {
						self::$paymentInformation[$order_id] = $transactionObject->getPaymentInformation();
					}
				}
			}
			if(isset(self::$paymentInformation[$order_id])) {
				return self::$paymentInformation[$order_id];
			}
		}
		catch (Exception $e) {
		}
		return null;
	}

	public static function updateOrderTotalsInSession() {
		if (isset($GLOBALS['order_total_modules'])) {
			$_SESSION['unzercw']['order_totals'] = array();
			foreach ($GLOBALS['order_total_modules']->modules as $module) {
				$class = substr($module, 0, strrpos($module, '.'));
				if ($GLOBALS[$class]->enabled) {
					foreach($GLOBALS[$class]->output as $output) {
						$_SESSION['unzercw']['order_totals'][$class] = $output;
						$_SESSION['unzercw']['order_totals'][$class]['code'] = $class;


						if ($class == 'ot_shipping') {
							$shippingClass = $_SESSION['shipping']['id'];
							$shippingClass = substr($shippingClass, 0, strpos($shippingClass, '_'));

							if (isset($GLOBALS[$shippingClass])) {
								$countryId = '';
								if (isset($GLOBALS['order']->delivery['country']['id'])) {
									$countryId = $GLOBALS['order']->delivery['country']['id'];
									$zoneId = $GLOBALS['order']->delivery['zone_id'];
								}
								else if (isset($GLOBALS['order']->delivery['country_iso_2'])) {
									$rs = xtc_db_query("select countries_id from " . TABLE_COUNTRIES . " where countries_iso_code_2 = '" . $GLOBALS['order']->delivery['country_iso_2'] . "'");
									$v = xtc_db_fetch_array($rs);
									if (isset($v['countries_id'])) {
										$countryId = $v['countries_id'];
									}
									$zoneId = 0;
								}

								$_SESSION['unzercw']['order_totals'][$class]['tax_rate'] = xtc_get_tax_rate($GLOBALS[$shippingClass]->tax_class, $countryId, $zoneId);
							}
						}
					}
				}
			}

			return $_SESSION['unzercw']['order_totals'];
		}
		else {
			return null;
		}
	}

}
