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

require_once DIR_FS_CATALOG . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'UnzerCw' . DIRECTORY_SEPARATOR . 'init.php';


require_once 'UnzerCw/AbstractController.php';


/**
 * Class UnzerCwModuleCenterModuleController
 */
class UnzerCwModuleCenterModuleController extends AbstractModuleCenterModuleController {


	protected function _init()
	{
		$this->setPageTitle();
		$this->setRedirectUrl();
	}

	private function setButtonsForModule()
	{
		$this->buttons = [

			[
				'text' => unzercw_translate('Unzer Base Module'),
				'url'  => UnzerCw_AbstractController::getControllerUrl('settings', 'edit', [
					'module_class' => unzercw,
				]),
			],

		];
	}

	private function setPageTitle()
	{
		$this->pageTitle = unzercw_translate('Unzer Base Module');
	}

	private function setRedirectUrl()
	{
		$this->redirectUrl = UnzerCw_AbstractController::getControllerUrl('settings', 'edit', [
			'module_class' => unzercw,
		]);
	}

}
