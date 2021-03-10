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

require_once 'Customweb/Core/Stream/Input/File.php';
require_once 'Customweb/Util/Html.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/OrderStatus.php';


class UnzerCw_SettingsRenderer {

	/**
	 * @var UnzerCw_AbstractModule
	 */
	private $module;
	
	public function __construct(UnzerCw_AbstractModule $module) {
		$this->module = $module;
	}

	public function render() {
		
		$output = '';
		$settingsData = $this->module->getSettings();
		foreach ($settingsData as $key => $item) {
			$item['key'] = $key;
			$value = $this->module->getSettingValue($key);
			$item['value'] = $value;
			switch(strtolower($item['type'])) {
				case 'select':
					$controlOutput = $this->renderSelect($item);
					break;
				case 'textfield':
					$controlOutput = $this->renderTextfield($item);
					break;
				case 'multiselect':
					$controlOutput = $this->renderMultiselect($item);
					break;
				case 'password':
					$controlOutput = $this->renderPassword($item);
					break;
				case 'multilangfield':
					$controlOutput = $this->renderMultiLangfield($item);
					break;
				case 'textarea':
					$controlOutput = $this->renderTextarea($item);
					break;
				case 'orderstatusselect':
					$controlOutput = $this->renderOrderStatusSelect($item);
					break;
				case 'orderstatusmultiselect':
					$controlOutput = $this->renderOrderStatusMultiselect($item);
					break;
				case 'file':
					$controlOutput = $this->renderFileInput($item);
			}
			$output .= $this->renderElement($item, $controlOutput);
		}
		
		return $output;
	}
	
	protected function renderElement($item, $controlHtml) {
		$output = '<div class="form-group">';
  		$output .= '<label for="setting-' . $item['key'] . '" class="col-lg-3 control-label">' . $item['title'];
  		if (isset($item['description'])) {
  			$item['description'] = Customweb_Util_Html::escapeXml($item['description']);
 			$output .= ' <i data-toggle="popover" data-trigger="hover" data-placement="bottom" title="' . $item['title'] . '"
						data-content="' . $item['description'] . '" class="glyphicon glyphicon-question-sign"></i>';	
  		}
  		$output .= '</label>';
		$output .= '<div class="col-lg-9">';
		$output .= $controlHtml;
		$output .= '</div>';
		$output .= '</div>';
		return $output;
	}

	protected function renderSelect(array $item) {
		$output = '<select class="form-control" name="' . $item['key'] . '" id="setting-' . $item['key'] . '">';

		foreach ($item['options'] as $key => $value) {
			
			$key = (string)$key;
			$item['value'] = (string)$item['value'];
			
			$output .= '<option value="' . Customweb_Util_Html::escapeXml($key) . '"';
			if ($item['value'] === $key) {
				$output .= ' selected="selected" ';
			}
			$output .= '>' . $value . '</option>';
		}
		
		$output .= '</select>';
		return $output;
	}

	protected function renderTextfield(array $item) {
		return '<input class="form-control" type="text" name="' . $item['key'] . '" id="setting-' . $item['key'] . '" value="' . Customweb_Util_Html::escapeXml($item['value']) . '" />';
	}

	protected function renderMultiselect(array $item) {
		$output = '<select class="form-control" name="' . $item['key'] . '[]" id="setting-' . $item['key'] . '" multiple="multiple">';
		
		foreach ($item['options'] as $key => $value) {
			$output .= '<option value="' . Customweb_Util_Html::escapeXml($key) . '"';
			if ( in_array($key, $item['value'])) {
				$output .= ' selected="selected" ';
			}
			$output .= '>' . $value . '</option>';
		}
		
		$output .= '</select>';
		return $output;
	}

	protected function renderPassword(array $item) {
		return $this->renderTextfield($item);
	}

	protected function renderMultiLangfield(array $item) {
		$output = '';
		if (!is_array($item['value'])) {
			$item['value'] = array($item['value']);
		}
		foreach (UnzerCw_Util::getLanguages() as $langId => $language) {
			if (isset($item['value'][$langId])) {
				$value = $item['value'][$langId];
			}
			else {
				$value = current($item['value']);
			}
			$output .= strtoupper($language['code']) . ': ' . 
				'<input class="form-control" type="text" id="setting-' . $item['key'] . '-' . $langId . '" name="' . $item['key'] .'[' . $langId . ']" value="' . Customweb_Util_Html::escapeXml($value) . '" /> <br />';
		}
		return $output;
	}

	protected function renderTextarea(array $item) {
		return '<textarea class="form-control" id="setting-' . $item['key'] . '" name="' . $item['key'] . '">' . Customweb_Util_Html::escapeXml($item['value']) . '</textarea>';
	}
	
	protected function renderOrderStatusSelect(array $item) {
		if (!isset($item['options'])) {
			$item['options'] = array();
		}
		
		foreach (UnzerCw_OrderStatus::getOrderStatuses() as $key => $value) {
			$item['options'][$key] = $value;
		}
		
		return $this->renderSelect($item);
	}
	
	protected function renderOrderStatusMultiselect(array $item) {
		if (!isset($item['options'])) {
			$item['options'] = array();
		}
		
		foreach (UnzerCw_OrderStatus::getOrderStatuses() as $key => $value) {
			$item['options'][$key] = $value;
		}

		return $this->renderMultiselect($item);
	}
	
	protected function renderFileInput(array $item) {
		
		$path = '';
		if ($item['value'] instanceof Customweb_Core_Stream_Input_File) {
			$path = '<div>' . $item['value']->getFilePath() . '</div>';
		}
		
		
		return '<div class="checkbox"><input type="file" id="setting-' . $item['key'] . '" name="' . $item['key'] . '" /> 
				<label ><input type="checkbox" name="reset[' . $item['key'] . ']" value="on" /> ' . unzercw_translate("Reset") . '</label>'
				. $path . '</div>';		
	}


}