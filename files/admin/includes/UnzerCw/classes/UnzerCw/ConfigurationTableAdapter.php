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


require_once 'UnzerCw/Compatibility.php';
require_once 'UnzerCw/Database.php';


/**
 * This class adapts to the database table 'configuration' as required.
 *
 * @author Thomas Hunziker
 *
 */
class UnzerCw_ConfigurationTableAdapter {

	/**
	 * Inserts a new configuration entry.
	 *
	 * @param string $key the key of the configuration.
	 * @param string $value the value of the configuration.
	 * @param int $groupId the group ID to which it should be assigned to.
	 * @param string $type the type of the configuration (text or boolean)
	 */
	public static function insert($key, $value, $groupId = 0, $type = 'text', $sortOrder = 1) {

		if (UnzerCw_Compatibility::compareGambioVersion("4.1") >= 0) {
			$typeColumn = 'NULL';
			if ($type === 'boolean') {
				$typeColumn= "switcher";
			}

			UnzerCw_Database::query(
					"insert into `gx_configurations` (`key`, `value`, `default`, legacy_group_id, sort_order, `type`, last_modified) values ('configuration/" . UnzerCw_Database::escape($key) . "', '" .
					UnzerCw_Database::escape($value) . "', '" .
					UnzerCw_Database::escape($value) . "', '$groupId', '$sortOrder', '$typeColumn', now())");
		}
		else {
			$function = '';
			if ($type === 'boolean') {
				$function = "xtc_cfg_select_option(array(\'True\', \'False\'), ";
			}

			UnzerCw_Database::query(
					"insert into " . TABLE_CONFIGURATION .
					" ( configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) values ('" . UnzerCw_Database::escape($key) . "', '" .
					UnzerCw_Database::escape($value) . "', '$groupId', '$sortOrder', '$function', now())");
		}
	}

	/**
	 * Updates the given configuration by the key with the value.
	 *
	 * @param string $key the configuration key that should be updated.
	 * @param string $value the configuration value that should be set.
	 */
	public static function update($key, $value) {
		if (UnzerCw_Compatibility::compareGambioVersion("4.1") >= 0) {
			UnzerCw_Database::query("UPDATE `gx_configurations` SET `value` = '" . UnzerCw_Database::escape($value) . "' WHERE `key` = 'configuration/" . UnzerCw_Database::escape($key) . "';");
		}
		else {
			UnzerCw_Database::query("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '" . UnzerCw_Database::escape($value) . "' WHERE configuration_key = '" . UnzerCw_Database::escape($key) . "';");
		}
	}

	/**
	 * Reads the configuration value given by the key.
	 *
	 * @param string $key the configuration key for which the value should be read.
	 * @return string|NULL the configuration value.
	 */
	public static function read($key) {
		if (UnzerCw_Compatibility::compareGambioVersion("4.1") >= 0) {
			$result = UnzerCw_Database::query("SELECT `value` FROM `gx_configurations` WHERE `key` = 'configuration/" . UnzerCw_Database::escape($key) . "'");
			if ($result != null) {
				$keys = UnzerCw_Database::fetch($result);
				return $keys['value'];
			}
			else {
				return null;
			}
		}
		else {
			$result = UnzerCw_Database::query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = '" . UnzerCw_Database::escape($key) . "'");
			if ($result != null) {
				$keys = UnzerCw_Database::fetch($result);
				return $keys['configuration_value'];
			}
			else {
				return null;
			}
		}
	}

	/**
	 * Removes the configuration value with the given key.
	 *
	 * @param string $key the configuration key
	 */
	public static function remove($key) {
		if (UnzerCw_Compatibility::compareGambioVersion("4.1") >= 0) {
			UnzerCw_Database::query("DELETE FROM `gx_configurations` WHERE `key` = 'configuration/" . UnzerCw_Database::escape($key) . "'");
		}
		else {
			UnzerCw_Database::query("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = '" . UnzerCw_Database::escape($key) . "'");
		}
	}


}