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




final class UnzerCw_Button
{
	private function __construct() {

	}

    public static function getBackButton($url) {
        return '<a href="' . $url . '" class="button_grey_big button_set_big action_page_back"><span class="button-outer"><span class="button-inner">' . unzercw_translate('Back') . '</span></span></a>';
    }

    public static function getOrderConfirmationButton() {
        return '<div class="checkout_button">
	<a href="#" class="button_green_big button_set_big action_submit unzercw-confirm-button"><span class="button-outer"><span class="button-inner">' .  unzercw_translate('Pay') . '</span></span></a>
</div>';
    }

    public static function getUpdateButton() {
        $altText = unzercw_translate("Update");
        return '<a href="#" class="button_blue button_set action_submit" id="unzercw-update-button"><span class="button-outer"><span class="button-inner">' . $altText . '</span></span></a>';
    }

    
}