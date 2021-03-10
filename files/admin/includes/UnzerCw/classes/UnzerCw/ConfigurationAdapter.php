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

require_once 'Customweb/Core/Language.php';
require_once 'Customweb/Payment/IConfigurationAdapter.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/AbstractModule.php';
require_once 'UnzerCw/OrderStatus.php';


/**
 * @Bean
 */
class UnzerCw_ConfigurationAdapter implements Customweb_Payment_IConfigurationAdapter{
	
	/**
	 * @var UnzerCw_AbstractModule
	 */
	private static $baseModule = null;
	
	public function __construct() {
	}
	
	/**
	 * @return UnzerCw_AbstractModule
	 */
	public static function getBaseModule() {
		if (self::$baseModule === null) {
			self::$baseModule = UnzerCw_AbstractModule::getModulInstanceByClass('unzercw');
		}
		return self::$baseModule;
	}
	
	public function getConfigurationValue($key, $languageCode = null) {
		if ($languageCode === null) {
			return self::getBaseModule()->getSettingValue($key);
		}
		else {
			$languageCode = (string)$languageCode;
			$languageId = null;
			foreach (UnzerCw_Util::getLanguages() as $langId => $language) {
				if ($language['code'] == $languageCode) {
					$languageId = $langId;
					break;
				}
			}
			
			if ($languageId === null) {
				throw new Exception("Could not find language with language code '" . $languageCode . "'.");
			}
			
			$values = self::getBaseModule()->getSettingValue($key);
			if (isset($values[$languageId])) {
				return $values[$languageId];
			}
			else {
				return current($values);
			}
		}
	}
	
	public function existsConfiguration($key, $language = null) {
		try {
			$this->getConfigurationValue($key, $language);
			return true;
		}
		catch (Exception $e) {
			return false;
		}
	}
	
	
	public static function isPendingOrderModeActive() {
		return strtolower(self::getBaseModule()->getSettingValue('create_pending_orders')) == 'yes';
	}
	
	public static function getLoggingLevel(){
		return strtolower(self::getBaseModule()->getSettingValue('log_level'));
		
	}
	
	
	
	public function getLanguages($currentStore = false) {
		$languages = array();
		foreach (UnzerCw_Util::getLanguages() as $lang) {
			$languages[] = new Customweb_Core_Language($lang['code']);
		}

		return $languages;
	}

	public function getStoreHierarchy() {
		return null;
	}

	public function useDefaultValue(Customweb_Form_IElement $element, array $formData) {
		return false;
	}

	public function getOrderStatus() {
		return UnzerCw_OrderStatus::getOrderStatuses();
	}

}