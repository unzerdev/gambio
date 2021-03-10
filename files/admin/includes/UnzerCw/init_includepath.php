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


if (!isset($unzercwinit_includepath)) {
	$unzercwinit_includepath = true;
	require_once dirname(dirname(__FILE__)) . '/cw-libs/loader.php';
	
	// Add class path to include path
	$pathToLib = dirname(__FILE__) . '/classes';
	set_include_path(implode(PATH_SEPARATOR, array(
		get_include_path(),
		realpath($pathToLib),
	)));
	
	if (!class_exists('wishList')) {
		class wishList {
	
		}
	}
}
