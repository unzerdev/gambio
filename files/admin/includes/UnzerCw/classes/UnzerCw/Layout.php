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




class UnzerCw_Layout {
	
	private $layoutName;
	
	public function __construct($layoutName = 'default') {
		$this->layoutName = $layoutName;
	}
	
	public function getLayoutName() {
		return $this->layoutName;
	}
	
	public function getLayoutPath() {
		return dirname(dirname(dirname(__FILE__))) . '/layouts/' . $this->getLayoutName();
	}
	
	public function render(array $variables = array(), $layoutFileName = 'index.php') {
		$fileName = $this->getLayoutPath() . '/' . $layoutFileName;
		
		if (!file_exists($fileName)) {
			throw new Exception("Could not find layout file '" . $fileName . "'.");
		}
		
		extract($variables);
		ob_start();
		require $fileName;
		$content = ob_get_contents();
		ob_end_clean();
		
		return $content;
	}
	
}