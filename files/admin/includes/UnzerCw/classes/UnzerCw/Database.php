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


require_once 'UnzerCw/Database/Adapter.php';


final class UnzerCw_Database {
	
	private static $instance = null;

	private function __construct() {
	}
	
	/**
	 * @return UnzerCw_Database_IAdapter
	 */
	private static function getAdapter() {
		if (self::$instance === null) {
			require_once 'UnzerCw/Database/Adapter.php';
			self::$instance = new UnzerCw_Database_Adapter();
		}
	
		return self::$instance;
	}
	
	/**
	 * @return Customweb_Database_IDriver
	 */
	public static function getDriver() {
		return self::getAdapter()->getDriver();
	}
	
	/**
	 * @param string $sql
	 * @return object handler
	 */
	public static function query($query) {
		return self::getAdapter()->query($query);
	}

	public static function fetch($result) {
		return self::getAdapter()->fetch($result);
	}

	public static function getInsertId() {
		return self::getAdapter()->getInsertId();
	}

	public static function countRows($result) {
		return self::getAdapter()->countRows($result);
	}
	
	public static function strip($string) {
		return self::getAdapter()->escape(strip_tags($string));		
	}
	
	public static function escape($string) {
		return self::getAdapter()->escape($string);
	}
	
	public static function prepare($sql, $args = array()) {

		$cleanArgs = array();
		foreach($args as $arg) {
			$cleanArgs[] = self::strip($arg);
		}

		return self::getAdapter()->query(vsprintf($sql, $cleanArgs));
	}

	public static function insert($tableName, $data) {
		return self::getAdapter()->insert($tableName, $data);
	}

	public static function update($tableName, $data, $where) {
		return self::getAdapter()->update($tableName, $data, $where);
	}
}