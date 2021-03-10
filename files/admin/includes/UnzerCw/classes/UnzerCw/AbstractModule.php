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

require_once 'Customweb/Core/String.php';
require_once 'Customweb/Core/Stream/Input/File.php';

require_once 'UnzerCw/ConfigurationTableAdapter.php';
require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/Database.php';
require_once 'UnzerCw/Log.php';
require_once 'UnzerCw/AbstractController.php';
require_once 'UnzerCw/OrderStatus.php';

abstract class UnzerCw_AbstractModule {
	public $code = null;
	public $title = null;
	public $description = null;
	public $enabled = null;
	private $installed = null;
	private $settingsCache = array();

	public function __construct(){
		$this->code = get_class($this);
		$statusConstant = $this->getStatusKey();
		if (defined($statusConstant)) {
			$this->enabled = constant($statusConstant) == 'True' ? true : false;
		}
		else {
			$this->enabled = false;
		}

		$this->setLanguageConstants();

		$this->title = $this->getTitle();
		$this->description = Customweb_Core_String::_($this->getDescription())->replaceNonAsciiCharsWithEntities()->toString();
		$this->check();
	}

	abstract public function getDescription();

	abstract public function getConstantPrefix();

	abstract public function getTitle();

	public function getCode(){
		return $this->code;
	}

	protected function setLanguageConstants(){
		$statusKey = $this->getStatusKey();
		if (!defined($statusKey . '_TITLE')) {
			define($statusKey . '_TITLE', unzercw_translate('Status'));
		}
		if (!defined($statusKey . '_DESC')) {
			define($statusKey . '_DESC',
					unzercw_translate('By activating the module the functionality of the module is active in the frontend.'));
		}
	}

	public function getSettings(){
		$statusKey = $this->getStatusKey();

		$settings = array();
		$settings['status'] = array(
			'title' => constant($statusKey . '_TITLE'),
			'description' => constant($statusKey . '_DESC'),
			'type' => 'select',
			'options' => array(
				'True' => unzercw_translate('Active'),
				'False' => unzercw_translate('Inactive')
			),
			'default' => 'True'
		);

		return $settings;
	}

	/**
	 *
	 * @param string $className
	 * @return UnzerCw_AbstractModule
	 * @throws Exception
	 */
	public static function getModulInstanceByClass($className){
		$catalogPath = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
		$className = str_replace("/", "_", $className);
		if ($className == 'unzercw') {
					require_once $catalogPath . '/admin/includes/modules/export/unzercw.php';
		}
		else {
			$path = $catalogPath . '/includes/modules/payment/' . strtolower($className) . '.php';
			if (file_exists($path)) {
				require_once $path;
			}
			else {
				throw new Exception("The module with class name '" . $className . "' could not be loaded.");
			}
		}
		return new $className();
	}

	public function isSettingPresent($key){
		$key = $this->cleanSettingsKey($key);
		if (!isset($this->settingsCache[$key])) {
			$settings = $this->getSettings();
			if (!isset($settings[$key])) {
				return false;
			}
			else {
				return true;
			}
		}
		else {
			return true;
		}
	}

	public function getSettingValue($key){
		$key = $this->cleanSettingsKey($key);
		if (!isset($this->settingsCache[$key])) {
			$settings = $this->getSettings();
			if (!isset($settings[$key])) {
				throw new Exception("No setting found for key '" . $key . "'");
			}

			if (strtolower($key) == 'status') {
				$value = constant($this->getStatusKey());
			}
			else {
				$row = NULL;
				try {
					$rs = UnzerCw_Database::query(
							"SELECT setting_value FROM `cw_module_settings` WHERE module_code = '" . $this->getCode() . "' AND setting_key = '" . UnzerCw_Database::escape($key) .
									 "'");
					$row = UnzerCw_Database::fetch($rs);
				}
				catch (Exception $e) {
					// ignore
				}

				$setting = $settings[$key];
				$type = strtolower($setting['type']);

				if ($type == 'file') {
					if (isset($row['setting_value']) && !empty($row['setting_value'])) {
						$filePath = UNZERCW_UPLOAD_DIR . '/' . $row['setting_value'];
						$value = new Customweb_Core_Stream_Input_File($filePath);
					}
					else {
						$resolver = UnzerCw_Util::getAssetResolver();
						if (!empty($setting['default'])) {
							$value = $resolver->resolveAssetStream($setting['default']);
						}
					}
				}

				else if ($row != NULL) {
					$value = $row['setting_value'];
					if ($type == 'multiselect' || $type == 'multilangfield') {
						$value = unserialize(base64_decode($value));
					}
				}
				else {
					if (isset($setting['default'])) {
						$value = $setting['default'];
					}
					else {
						$value = '';
					}
					if ($type == 'multiselect') {
						if ($value === '') {
							$value = array();
						}
						else {
							$value = explode(',', $value);
						}
					}
					else if ($type == 'multilangfield') {
						$defaultString = $value;
						$value = array();
						foreach (UnzerCw_Util::getLanguages() as $langId => $language) {
							$value[$langId] = $defaultString;
						}
					}
					else if ($type == 'orderstatusselect' || $type == 'orderstatusmultiselect') {
						if ($value == 'authorized' || $value == 'uncertain' || $value == 'cancelled') {
							$value = UnzerCw_OrderStatus::getStatusIdByKey($value);
						}
					}
				}
			}
			$this->settingsCache[$key] = $value;
		}

		return $this->settingsCache[$key];
	}

	public function setSettingValue($key, $value){
		$key = $this->cleanSettingsKey($key);
		$settings = $this->getSettings();
		if (!isset($settings[$key])) {
			throw new Exception("No setting found for key '" . $key . "'");
		}
		$setting = $settings[$key];

		// Update value in cache
		$this->settingsCache[$key] = $value;

		if (strtolower($key) == 'status') {

			UnzerCw_ConfigurationTableAdapter::update($this->getStatusKey(), $value);
		}
		else {
			// Prepare value for database
			$type = strtolower($setting['type']);
			if ($type == 'multiselect' || $type == 'multilangfield') {
				$value = base64_encode(serialize($value));
			}

			$rs = UnzerCw_Database::query(
					"SELECT setting_value FROM `cw_module_settings` WHERE module_code = '" . $this->getCode() . "' AND setting_key = '" . UnzerCw_Database::escape($key) . "'");
			$row = UnzerCw_Database::fetch($rs);

			// Insert
			if ($row == NULL) {
				UnzerCw_Database::query(
						"INSERT INTO cw_module_settings (module_code, setting_key, setting_value) VALUES('" . $this->getCode() . "', '" . UnzerCw_Database::escape($key) . "', '" .
						UnzerCw_Database::escape($value) . "');");
			}

			// Update
			else {
				UnzerCw_Database::query(
						"UPDATE cw_module_settings SET setting_value = '" . UnzerCw_Database::escape($value) . "' WHERE module_code = '" . $this->getCode() .
						"' AND setting_key = '" . UnzerCw_Database::escape($key) . "';");
			}
		}

		return $this;
	}

	protected function cleanSettingsKey($key){
		$key = strip_tags($key);
		return preg_replace("/[[:space:]]{1,}/", "_", $key);
	}

	public function getSettingsUrl(){
		return UnzerCw_AbstractController::getControllerUrl('settings', 'edit', array(
			'module_class' => get_class($this)
		));
	}

	public function install(){
		$statusKey = $this->getStatusKey();

		UnzerCw_ConfigurationTableAdapter::insert($statusKey, "True", 6, 'boolean', 1);

		UnzerCw_Database::query(
				"CREATE TABLE IF NOT EXISTS `cw_module_settings` (
			`settting_id` int(11) unsigned NOT NULL auto_increment,
			`module_code` varchar(80) NOT NULL,
			`setting_key` varchar(250) NOT NULL,
			`setting_value` text default NULL,
			PRIMARY KEY  (`settting_id`),
			UNIQUE KEY `module_setting_key` (`module_code`,`setting_key`)
		) DEFAULT CHARSET=utf8 ");

		// Install the additional tables
		UnzerCw_OrderStatus::installOrderStatuses();

		require_once 'UnzerCw/Log.php';
		UnzerCw_Log::installTable();

		// Increase field sizes:
		$this->executeQuery("ALTER TABLE " . TABLE_ORDERS . " CHANGE `payment_class`  `payment_class` VARCHAR( 128 )");
		$this->executeQuery("ALTER TABLE " . TABLE_ORDERS . " CHANGE `cc_number`  `cc_number` TEXT");
		$this->executeQuery("ALTER TABLE " . TABLE_ORDERS . " CHANGE `cc_type`  `cc_type` TEXT");
		$this->executeQuery("ALTER TABLE " . TABLE_ORDERS . " CHANGE `payment_method`  `payment_method` VARCHAR( 128 )");

				// Modify cc type field, to allow more content in it
		UnzerCw_Database::query("ALTER TABLE  " . TABLE_ORDERS . " CHANGE  `cc_type`  `cc_type` TEXT NULL DEFAULT NULL");
		UnzerCw_Database::query("ALTER TABLE  " . TABLE_ORDERS . " CHANGE  `cc_expires`  `cc_expires` TEXT NULL DEFAULT NULL");
		
		// Insert Access Informations:
		$rs = UnzerCw_Database::query("SELECT * FROM " . TABLE_ADMIN_ACCESS . " LIMIT 0,1");
		$row = UnzerCw_Database::fetch($rs);
		if (!isset($row['unzercw']))
		{
			UnzerCw_Database::query("ALTER TABLE " . TABLE_ADMIN_ACCESS . " ADD `unzercw` INT( 1 ) NOT NULL DEFAULT '0'");
		}
		UnzerCw_Database::query("UPDATE " . TABLE_ADMIN_ACCESS . " SET `unzercw` = '1' WHERE `customers_id` = '" . $_SESSION['customer_id'] . "'");
		
		UnzerCw_Database::query("UPDATE " . TABLE_ADMIN_ACCESS . " SET `unzercw` = '1' WHERE `customers_id` = '1'");
		
	}

	private function executeQuery($query) {
		try {
			UnzerCw_Database::query($query);
		}
		catch(Exception $e) {
			// We ignore those execptions.
		}
	}

	public function getStatusKey(){
		return strtoupper($this->getConstantPrefix()) . strtoupper($this->getCode()) . '_STATUS';
	}

	public function remove(){
		UnzerCw_ConfigurationTableAdapter::remove($this->getStatusKey());
		UnzerCw_Database::query("DELETE FROM cw_module_settings WHERE module_code = '" . $this->getCode() . "'");
	}

	public function isInstalled(){
		return $this->check();
	}

	public function isEnabled(){
		return $this->enabled;
	}

	public function check(){
		if ($this->installed == NULL) {
			$rs = UnzerCw_ConfigurationTableAdapter::read($this->getStatusKey());
			$this->installed = $rs !== null;
		}
		return $this->installed;
	}

	public function keys(){
		return array(
			$this->getStatusKey()
		);
	}
}