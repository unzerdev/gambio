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

require_once 'Customweb/Licensing/UnzerCw/IShopAdapter.php';

require_once 'UnzerCw/Database.php';
require_once 'UnzerCw/AbstractModule.php';



final class UnzerCw_LicenseAdapter implements Customweb_Licensing_UnzerCw_IShopAdapter {

	public static function store($data) {
		try {
			$baseModule = UnzerCw_AbstractModule::getModulInstanceByClass('unzercw');

			$rs = UnzerCw_Database::query("SELECT setting_value FROM `cw_module_settings` WHERE module_code = '" . $baseModule->getCode() . "' AND setting_key = 'licensing_response'");
			$row = UnzerCw_Database::fetch($rs);
			if (!$row || empty($row)) {
				UnzerCw_Database::query("INSERT INTO cw_module_settings (module_code, setting_key, setting_value) VALUES('" . $baseModule->getCode() . "', 'licensing_response', '" . $data . "');");
			} else {
				UnzerCw_Database::query("UPDATE cw_module_settings SET setting_value = '" . $data . "' WHERE module_code = '" . $baseModule->getCode() . "' AND setting_key = 'licensing_response';");
			}
		} catch (Exception $e) {}
	}

	public static function load() {
		try {
			$baseModule = UnzerCw_AbstractModule::getModulInstanceByClass('unzercw');

			$rs = UnzerCw_Database::query("SELECT setting_value FROM `cw_module_settings` WHERE module_code = '" . $baseModule->getCode() . "' AND setting_key = 'licensing_response'");
			$row = UnzerCw_Database::fetch($rs);
			if ($row && !empty($row)) {
				return $row['setting_value'];
			}
		} catch(Exception $e) {}
	}

	public static function getDomain() {
		return HTTP_SERVER;
	}

}
