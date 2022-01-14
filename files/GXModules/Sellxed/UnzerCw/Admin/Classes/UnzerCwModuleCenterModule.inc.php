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
require_once DIR_FS_CATALOG . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'export' . DIRECTORY_SEPARATOR . 'unzercw.php';




/**
 * Class UnzerCwModuleCenterModule
 */
class UnzerCwModuleCenterModule extends AbstractModuleCenterModule {

	/**
	 * @inheritDoc
	 */
	protected function _init()
	{
		$this->setTitle();
		$this->setDescription();
		$this->setSortOrder();
	}

	public function setTitle()
	{
		$this->title = unzercw_translate('Unzer Base Module');
	}

	public function setDescription()
	{
		$this->description = unzercw_translate('This module is used to install / deinstall the main configurations for the Unzer gateway.');
	}

	public function setSortOrder()
	{
		$this->sortOrder = 1;
	}

	/**
	 * @inheritDoc
	 */
	public function install()
	{
		parent::install();
		(new unzercw())->install();
	}

	/**
	 * @inheritDoc
	 */
	public function uninstall()
	{
		parent::uninstall();
	}
}
