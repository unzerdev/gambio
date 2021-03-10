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

require_once 'Customweb/Mvc/Layout/IRenderer.php';



class UnzerCw_LayoutRenderer implements Customweb_Mvc_Layout_IRenderer{
	
	public function render(Customweb_Mvc_Layout_IRenderContext $context) {
		if (class_exists('MainFactory') && file_exists(UNZERCW_CATALOG_PATH . '/system/classes/layout/LayoutContentControl.inc.php')) {
			$content = $this->renderWithLayoutContentController($context);
		}
		else {
			$content = $this->renderLegacy($context);
		}
		
		if (isset($_SESSION['language_charset']) && strtolower($_SESSION['language_charset']) != 'utf-8') {
			$content = str_replace('text/html; charset=' . $_SESSION['language_charset'], 'text/html; charset=UTF-8', $content);
			$content = utf8_encode($content);
		}
		
		// Place the content into the layout. We need to do this, because the content is UTF-8 and the layout may be
		// in ISO encoding. Hence we need to clean up the encoding first.
		$content = str_replace('_______$CONENT$_______', $this->getMainContent($context), $content);
		
		return $content;
	}
	
	private function renderWithLayoutContentController(Customweb_Mvc_Layout_IRenderContext $context) {
		$coo_layout_control = MainFactory::create_object('LayoutContentControl');
		$coo_layout_control->set_data('GET', $_GET);
		$coo_layout_control->set_data('POST', $_POST);
		$coo_layout_control->set_('coo_breadcrumb', $GLOBALS['breadcrumb']);
		$coo_layout_control->set_('coo_product', $GLOBALS['product']);
		$coo_layout_control->set_('coo_xtc_price', $GLOBALS['xtPrice']);
		$coo_layout_control->set_('c_path', $GLOBALS['cPath']);
		$coo_layout_control->set_('main_content', '_______$CONENT$_______');
		$coo_layout_control->set_('request_type', $GLOBALS['request_type']);
		$coo_layout_control->proceed();
		
		return $coo_layout_control->get_response();
	}
	
	private function renderLegacy(Customweb_Mvc_Layout_IRenderContext $context) {
		ob_start();
		extract($GLOBALS);
		
		$smarty = new Smarty;
		
		// include boxes 
		require (DIR_FS_CATALOG.'templates/'.CURRENT_TEMPLATE.'/source/boxes.php');
		require (DIR_WS_INCLUDES . 'header.php');
		
		$smarty->assign('language', $_SESSION['language']);
		$smarty->assign('main_content', '_______$CONENT$_______');
		$smarty->caching = 0;
		
		$smarty->display(CURRENT_TEMPLATE.'/index.html');
		
		include ('includes/application_bottom.php');
		
		$content = ob_get_contents();
		ob_end_clean();
		
		return $content;
	}
	
	private function getMainContent(Customweb_Mvc_Layout_IRenderContext $context) {
	
		$smarty = new Smarty();
		$smarty->assign('content', $context->getMainContent());
		$smarty->assign('title', $context->getTitle());
		$smarty->assign('css', $context->getCssFiles());
		$smarty->assign('js', $context->getJavaScriptFiles());
	
		$currentTemplatePath = UNZERCW_CATALOG_PATH . '/templates/' . CURRENT_TEMPLATE . '/unzercw/';
		$defaultTemplatePath = UNZERCW_CATALOG_PATH . '/templates/default/unzercw/';
	
		if (file_exists($currentTemplatePath . 'base.tpl')) {
			return $smarty->fetch($currentTemplatePath . 'base.tpl');
		}
		else {
			return $smarty->fetch($defaultTemplatePath . 'base.tpl');
		}
	}
	
	

}