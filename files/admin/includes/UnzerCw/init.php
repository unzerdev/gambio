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

/**
 * This file init the environment for any other operation.
 */
if (!isset($unzercwinit)) {
	
	require_once dirname(__FILE__) . '/init_includepath.php';
	define('UNZERCW_CATALOG_PATH', dirname(dirname(dirname(dirname(__FILE__)))));
	define('UNZERCW_TMP_SESSION_DIRECTORY_PATH', UNZERCW_CATALOG_PATH . '/templates_c/checkout_sessions/');
	define('UNZERCW_UPLOAD_DIR', UNZERCW_CATALOG_PATH . '/media/content/unzercw_uploads');
	
	$langDir = UNZERCW_CATALOG_PATH . '/lang/';
	$langFile = $langDir . $_SESSION['language'] . '/modules/unzercw.php';
	$fallbackLangFile = $langDir . 'english/modules/unzercw.php';
	if (file_exists($langFile)) {
		require_once $langFile;
	}
	else {
		require_once $fallbackLangFile;
	}
	
	require_once dirname(__FILE__) . '/functions.php';
	require_once 'UnzerCw/TranslationResolver.php';
	require_once 'UnzerCw/LoggingListener.php';
	require_once 'UnzerCw/Util.php';
	
	$root = dirname(dirname(dirname(dirname(__FILE__))));
	if (file_exists($root . '/gm/classes/GMCounter.php')) {
		require_once $root . '/gm/classes/GMCounter.php';
	}
	
	function UnzerCwClassLoader($name) {
		$root = dirname(dirname(dirname(dirname(__FILE__))));
		if (strpos($name, "GM") === 0) {
			if (file_exists($root . '/gm/classes/' . $name . '.php')) {
				require_once $root . '/gm/classes/' . $name . '.php';
			}
		}
		else if ($name == 'FilterManager') {
			if (file_exists($root . '/system/controls/FeatureFilter/' . $name . '.inc.php')) {
				require_once $root . '/system/controls/FeatureFilter/' . $name . '.inc.php';
			}
		}
		else if ($name == 'vat_validation') {
			require_once $root . '/includes/classes/' . $name . '.php';
		}
	}
	
	Customweb_Core_Util_Class::registerClassLoader('UnzerCwClassLoader');
	
	UnzerCw_Util::migrateDatabaseSchema();
	
	$unzercwinit = true;
}

