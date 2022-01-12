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




final class UnzerCw_Compatibility
{
	private function __construct() {

	}

	public static function getGambioVersion() {
		$gx_version = null;
		$file = UNZERCW_CATALOG_PATH . '/release_info.php';
		if (file_exists($file)) {
			require $file;
		}

		return $gx_version;
	}

	public static function compareGambioVersion($expectedVersion) {
		$version = self::getGambioVersion();
		if ($version !== null) {
			$version = str_replace('v', '', $version);
			return version_compare($version, $expectedVersion);
		}
		else {
			return -1;
		}
	}
}