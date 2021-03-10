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

require_once 'Customweb/Grid/DataAdapter/MySqlAdapter.php';

require_once 'UnzerCw/Database.php';


class UnzerCw_Grid_Adapter extends Customweb_Grid_DataAdapter_MySqlAdapter {
	
	protected function executeQuery($query) {
		return UnzerCw_Database::query($query);
	}
	
	protected function fetchRow($result) {
		return UnzerCw_Database::fetch($result);
	}
	
	protected function fetchNumberOfRows($result) {
		return UnzerCw_Database::countRows($result);
	}
	
	protected function escape($string) {
		return UnzerCw_Database::escape($string);
	}
}