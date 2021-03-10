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

require_once 'UnzerCw/Database/IAdapter.php';

class UnzerCw_Database_Adapter implements UnzerCw_Database_IAdapter{

	public function query($query) {
		$link = $this->getDbLink();
		if ($link instanceof mysqli) {
			$result = mysqli_query($link, $query);
			if ($result === false) {
				throw new Exception(sprintf("Could not execute database query '%s'. Error: '%s'", $query, mysqli_error($link)));
			}
		}
		else {
			$result = mysql_query($query, $link);
			if ($result === false) {
				throw new Exception(sprintf("Could not execute database query '%s'. Error: '%s'", $query, mysql_error()));
			}
		}
		return $result;
	}


	/**
	 * @return Customweb_Database_IDriver
	 */
	public function getDriver() {
		$connection = $this->getDbLink();
		if ($connection instanceof mysqli) {
			require_once 'Customweb/Database/Driver/MySQLi/Driver.php';
			return new Customweb_Database_Driver_MySQLi_Driver($connection);
		}
		else {
			require_once 'Customweb/Database/Driver/MySQL/Driver.php';
			return new Customweb_Database_Driver_MySQL_Driver($connection);
		}
	}

	public function fetch($result) {
		$connection = $this->getDbLink();
		if ($connection instanceof mysqli) {
			$rs = mysqli_fetch_array($result);
			if ($rs === null) {
				return false;
			}
			return $rs;
		}
		else {
			return mysql_fetch_array($result);
		}
	}

	public function getInsertId() {
		return xtc_db_insert_id();
	}

	public function countRows($result) {
		$connection = $this->getDbLink();
		if ($connection instanceof mysqli) {
			return mysqli_num_rows($result);
		}
		else {
			return mysql_num_rows($result);
		}
	}

	public function escape($string) {
		$connection = $this->getDbLink();
		if ($connection instanceof mysqli) {
			if (function_exists('mysqli_real_escape_string')) {
				$string = mysqli_real_escape_string($this->getDbLink(), $string);
			}
		}
		else {
			if (function_exists('mysql_real_escape_string')) {
				$string = mysql_real_escape_string($string, $this->getDbLink());
			} elseif (function_exists('mysql_escape_string')) {
				$string = mysql_escape_string($string);
			}
		}

		return str_replace("\\r", "\r", str_replace("\\n", "\n", addslashes($string)));
	}

	public function insert($tableName, $data) {
		return xtc_db_perform($tableName, $data, 'insert');
	}

	public function update($tableName, $data, $where) {

		if (is_array($where)) {
			$whereSql = '';
			foreach ($where as $key => $value) {
				$whereSql .= $key . ' = "' . $this->escape($value) . '" AND ';
			}

			$whereSql = substr($whereSql, 0, strlen($whereSql) - 4);
			$where = $whereSql;
		}

		return xtc_db_perform($tableName, $data, 'update', $where);
	}

	private function getDbLink() {
		if (isset($GLOBALS['db']->_connectionID)) {
			return $GLOBALS['db']->_connectionID;
		}
		else {
			return $GLOBALS['db_link'];
		}
	}
}