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


require_once 'UnzerCw/AbstractController.php';

require_once 'Customweb/Core/Util/System.php';
require_once 'Customweb/Core/Util/Xml.php';

class UnzerCw_Controller_LogMessages extends UnzerCw_AbstractController {

	public function indexAction(){
		
		$this->appendViewData('grid', unzercw_translate("The logs can be found in the 'Toolbox > Show Logs' in the shop menu"));
		
		header('Content-Type: text/html; charset=UTF-8');
		echo $this->render('list');
		
	}

	
}