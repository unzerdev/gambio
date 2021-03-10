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

require_once 'Customweb/I18n/Translation.php';

require_once 'UnzerCw/SettingsRenderer.php';
require_once 'UnzerCw/AbstractController.php';
require_once 'UnzerCw/AbstractModule.php';


class UnzerCw_Controller_Settings extends UnzerCw_AbstractController {
	
	private $moduleInstance = null;
	
	public function indexAction() {
		$this->editAction();
	}
	
	public function editAction() {
		
		if (!is_writable(UNZERCW_CATALOG_PATH . '/templates_c/')) {
			die("Folder '" . UNZERCW_CATALOG_PATH . "/templates_c/' is not writable.");
		}
		
		$renderer = new UnzerCw_SettingsRenderer($this->getModuleInstance());
		$formFields = $renderer->render();
		$this->appendViewData('formFields', $formFields);
		$this->appendViewData('module', $this->getModuleInstance());
		echo $this->render("edit");
	}
	
	/**
	 * @return UnzerCw_AbstractModule
	 * @throws Exception
	 */
	protected function getModuleInstance() {
		if ($this->moduleInstance === null) {
			if (!isset($_GET['module_class'])) {
				throw new Exception("No module class given.");
			}
			$this->moduleInstance = UnzerCw_AbstractModule::getModulInstanceByClass($_GET['module_class']);
		}
		return $this->moduleInstance;
	}
	
	public function saveAction() {
		$this->setMessage(unzercw_translate("Settings saved successfully."), 'success');
		
		$module = $this->getModuleInstance();
		foreach($module->getSettings() as $key => $setting) {
			if (strtolower($setting['type']) == 'file') {
				if (isset($_POST['reset'][$key]) && $_POST['reset'][$key] == 'on') {
					$value = NULL;
				}
				else if (isset($_FILES[$key]) && !empty($_FILES[$key]['name'])) {
					$fileExtension = pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);
					$fileName = $module->getCode() . '____' . $key . '.' . $fileExtension;
					if (!is_writable(UNZERCW_UPLOAD_DIR)) {
						throw new Exception(Customweb_I18n_Translation::__("For uploading files the path '@path' must be writable.", array('@path' => UNZERCW_UPLOAD_DIR)));
					}
					$rs = move_uploaded_file($_FILES[$key]['tmp_name'], UNZERCW_UPLOAD_DIR . '/' . $fileName);
					if ($rs === false) {
						throw new Exception(Customweb_I18n_Translation::__("Failed to move file to upload direcotry."));
					}
					$value = $fileName;
				}
				else {
					continue;
				}
			}
			else if (strtolower($setting['type']) == 'multiselect' && empty($_POST[$key])) {
				$value = array();
			}
			else if (isset($_POST[$key])) {
				$value = $_POST[$key];
			}
			else {
				continue;
			}
			$module->setSettingValue($key, $value);
		}
		
		$this->redirectAction("edit", array('module_class' => $_GET['module_class']));
	}
	
	
	
}