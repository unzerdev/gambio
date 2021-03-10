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

require_once 'Customweb/Form/Control/MultiControl.php';
require_once 'Customweb/Form/Control/Radio.php';
require_once 'Customweb/Form/Control/SingleCheckbox.php';
require_once 'Customweb/Form/Control/Html.php';
require_once 'Customweb/Form/Renderer.php';
require_once 'Customweb/Form/Control/MultiCheckbox.php';



class UnzerCw_Form_BackendRenderer extends Customweb_Form_Renderer {
	
	/**
	 * @var boolean
	 */
	private $isStaticControl = false;
	
	public function renderElementAdditional(Customweb_Form_IElement $element)
	{
		$output = '';
	
		$errorMessage = $element->getErrorMessage();
		if (!empty($errorMessage)) {
			$output .= $this->renderElementErrorMessage($element);
		}
	
		$description = $element->getDescription();
		if (!empty($description)) {
			$output .= $this->renderElementDescription($element);
		}
	
		return $output;
	}

	public function getFormCssClass()
	{
		return 'form-horizontal';
	}

	public function getElementCssClass()
	{
		return 'form-group clearfix';
	}

	public function getElementLabelCssClass()
	{
		return 'control-label col-sm-3';
	}

	public function getDescriptionCssClass()
	{
		return 'help-block col-sm-9 col-sm-offset-3';
	}

	public function getElementScopeCssClass()
	{
		return 'col-sm-2';
	}

	public function getControlCssClass()
	{
		return 'controls col-sm-7' . ($this->isStaticControl ? ' form-control-static' : '');
	}
	
	public function renderElementGroupPrefix(Customweb_Form_IElementGroup $elementGroup)
	{
		return '<div class="panel panel-default">';
	}
	
	public function renderElementGroupPostfix(Customweb_Form_IElementGroup $elementGroup)
	{
		return '</div></div>';
	}
	
	public function renderElementGroupTitle(Customweb_Form_IElementGroup $elementGroup)
	{
		$output = '';
		$title = $elementGroup->getTitle();
		if (! empty($title)) {
			$cssClass = $this->getCssClassPrefix() . $this->getElementGroupTitleCssClass();
			$output .= '<div class="panel-heading ' . $cssClass . '">' . $title . '</div>';
		}
		$output .= '<div class="panel-body">';
		return $output;
	}
	
	public function renderOptionPrefix(Customweb_Form_Control_IControl $control, $optionKey)
	{
		$optionCssClass = '';
		if ($control instanceof Customweb_Form_Control_Radio) {
			$optionCssClass = 'radio';
		} elseif ($control instanceof Customweb_Form_Control_MultiCheckbox
			|| $control instanceof Customweb_Form_Control_SingleCheckbox) {
			$optionCssClass = 'checkbox';
		}
		return '<div class="' . $this->getCssClassPrefix() . $this->getOptionCssClass() . ' ' . $optionCssClass . '" id="' . $control->getControlId() . '-' . $optionKey . '-key">';
	}

	public function renderControl(Customweb_Form_Control_IControl $control)
	{
		if (! ($control instanceof Customweb_Form_Control_MultiControl)) {
			$control->setCssClass($this->getControlCss($control));
		}
		$this->isStaticControl = ($control instanceof Customweb_Form_Control_Html);
		return $control->render($this);
	}
	
	protected function getControlCss(Customweb_Form_Control_IControl $control)
	{
		if ($control instanceof Customweb_Form_Control_Radio
			|| $control instanceof Customweb_Form_Control_MultiCheckbox
			|| $control instanceof Customweb_Form_Control_SingleCheckbox) {
			return $control->getCssClass();
		}
		return 'form-control ' . $control->getCssClass();
	}
	
	protected function renderButtons(array $buttons, $jsFunctionPostfix = '')
	{
		$output = '';
		$output .= '<div class="col-sm-9 col-sm-offset-3 text-right">';
		foreach ($buttons as $button) {
			$output .= $this->renderButton($button, $jsFunctionPostfix);
		}
		$output .= '</div>';
		
		if (isset($_SESSION['CSRFName'])) {
				$output .= '<input type="hidden" name="' . $_SESSION['CSRFName'] . '" value="' . $_SESSION['CSRFToken'] . '" />';
		}
	
		return $output;
	}
	
	
}