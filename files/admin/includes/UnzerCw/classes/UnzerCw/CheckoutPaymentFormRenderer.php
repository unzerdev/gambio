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

require_once 'Customweb/Form/Renderer.php';

require_once 'UnzerCw/Util.php';


class UnzerCw_CheckoutPaymentFormRenderer extends Customweb_Form_Renderer {
	
	
	public function renderElements(array $elements, $jsFunctionPostfix = '') {
		
		$fields = array();
		
		foreach($elements as $element) {
			if ($this->getNamespacePrefix() !== NULL) {
				$element->applyNamespacePrefix($this->getNamespacePrefix());
			}
			
			if ($this->getControlCssClassResolver() !== NULL) {
				$element->applyControlCssResolver($this->getControlCssClassResolver());
			}
			
			$field = array();
			
			$field['title'] = $this->renderElementLabel($element);
			$field['field'] = $this->renderControl($element->getControl());
			
			$errorMessage = $element->getErrorMessage();
			if (!empty($errorMessage)) {
				$field['field'] .= ' ' . $this->renderElementErrorMessage($element);
			}
			
			$desc = $element->getDescription();
			if (!empty($desc)) {
				$field['field'] .= $this->renderElementDescription($element);
			}
			
			// Handle Encoding
			$field['title'] = UnzerCw_Util::handlePageOutput($field['title']);
			$field['field'] = UnzerCw_Util::handlePageOutput($field['field']);
			
			$fields[] = $field;
		}
		
		if (count($elements) > 0 && $this->isAddJs()) {
			$js = '<script type="text/javascript">' . "\n";
			$js .= $this->renderValidatorCallbacks($elements, $jsFunctionPostfix);
			$js .= $this->renderOnLoadJs(array() ,$jsFunctionPostfix);
			$js .= "\n</script>";
			
			$lastField = array_pop($fields);
			$lastField['field'] .= $js;
			$fields[] = $lastField;
		}
		
		return $fields;
	}
}