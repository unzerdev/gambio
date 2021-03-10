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




final class UnzerCw_Compatibility
{

	private function __construct() {

	}

	public static function isGambio() {
		if (defined('SHOPSYSTEM') && strtolower(SHOPSYSTEM) == 'gambio') {
			return true;
		}
		else {
			return false;
		}
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


	public static function isMercari() {
		if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof mercari_db) {
			return true;
		}
		else {
			return false;
		}
	}

	public static function isModifiedShop() {
		if (defined('PROJECT_VERSION') && stristr(PROJECT_VERSION, 'modified')) {
			return true;
		}
		else {
			return false;
		}
	}

	public static function getBackButton($url) {
		return '<a href="' . $url . '" class="button_grey_big button_set_big action_page_back"><span class="button-outer"><span class="button-inner">' . unzercw_translate('Back') . '</span></span></a>';
		
	}

	public static function getOrderConfirmationButton() {
		return '<div class="checkout_button">
	<a href="#" class="button_green_big button_set_big action_submit unzercw-confirm-button"><span class="button-outer"><span class="button-inner">' .  unzercw_translate('Pay') . '</span></span></a>
</div>';

	}

	public static function writeBackendLink($orderId, $content) {
		
	}

	public static function getUpdateButton() {
		$altText = unzercw_translate("Update");
		return '<a href="#" class="button_blue button_set action_submit" id="unzercw-update-button"><span class="button-outer"><span class="button-inner">' . $altText . '</span></span></a>';
		
	}

	private static function includeImageButton() {
		if (!function_exists('xtc_image_submit')) {
			require_once(DIR_FS_INC . 'xtc_image_submit.inc.php');
		}
		if (!function_exists('xtc_image_button')) {
			require_once(DIR_FS_INC . 'xtc_image_button.inc.php');
		}
	}

}