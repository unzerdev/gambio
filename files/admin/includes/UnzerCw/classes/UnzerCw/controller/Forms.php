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

require_once 'Customweb/IForm.php';
require_once 'Customweb/Form.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/HttpRequest.php';
require_once 'UnzerCw/Form/BackendRenderer.php';
require_once 'UnzerCw/AbstractController.php';


class UnzerCw_Controller_Forms extends UnzerCw_AbstractController {
	
	public function indexAction() {
		$form = $this->getCurrentForm();
		
		if ($form->isProcessable()) {
			$form = new Customweb_Form($form);
			$form->setTargetUrl($this->getActionUrl('save', array('form' => $form->getMachineName())))->setRequestMethod(Customweb_IForm::REQUEST_METHOD_POST);
		}
		
		
		$renderer = new UnzerCw_Form_BackendRenderer();
		
		$this->appendViewData('form', $form);
		$this->appendViewData('formHtml', $renderer->renderForm($form));
		echo $this->render("edit");
	}
	

	public function saveAction() {
	
		$form = $this->getCurrentForm();
	
		$params = UnzerCw_HttpRequest::getInstance()->getParameters();
		if (!isset($params['button'])) {
			throw new Exception("No button returned.");
		}
		$pressedButton = null;
		foreach ($params['button'] as $buttonName => $value) {
			foreach ($form->getButtons() as $button) {
				if ($button->getMachineName() == $buttonName) {
					$pressedButton = $button;
				}
			}
		}
	
		if ($pressedButton === null) {
			throw new Exception("Could not find pressed button.");
		}
		UnzerCw_Util::getBackendFormAdapter()->processForm($form, $pressedButton, $params);
	
		$this->redirect($this->getActionUrl('index', array('form' => $form->getMachineName())));
	}
	
	/**
	 * @return Customweb_IForm
	 */
	protected function getCurrentForm() {
		$adapter = UnzerCw_Util::getBackendFormAdapter();
		
		if ($adapter !== null && isset($_GET['form'])) {
			$forms = $adapter->getForms();
			$formName = $_GET['form'];
			$currentForm = null;
			foreach ($forms as $form) {
				if ($form->getMachineName() == $formName) {
					return $form;
				}
			}
		}
		
		die('No form is set.');
	}
	
}