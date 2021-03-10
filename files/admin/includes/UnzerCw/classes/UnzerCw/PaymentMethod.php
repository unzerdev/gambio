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

require_once 'Customweb/Payment/Authorization/PaymentPage/IAdapter.php';
require_once 'Customweb/Util/Html.php';
require_once 'Customweb/Core/Util/Serialization.php';
require_once 'Customweb/Core/Logger/Factory.php';
require_once 'Customweb/Payment/Authorization/Server/IAdapter.php';
require_once 'Customweb/Core/Assert.php';
require_once 'Customweb/Payment/Authorization/IPaymentMethod.php';
require_once 'Customweb/I18n/Translation.php';
require_once 'Customweb/Payment/Authorization/Moto/IAdapter.php';

require_once 'UnzerCw/BackendOrder.php';
require_once 'UnzerCw/Database.php';
require_once 'UnzerCw/Entity/Transaction.php';
require_once 'UnzerCw/TransactionContext.php';
require_once 'UnzerCw/Compatibility.php';
require_once 'UnzerCw/OrderContext.php';
require_once 'UnzerCw/AbstractModule.php';
require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/BackendOrderContext.php';
require_once 'UnzerCw/Log.php';
require_once 'UnzerCw/PaymentMethodWrapper.php';
require_once 'UnzerCw/ConfigurationTableAdapter.php';
require_once 'UnzerCw/CheckoutPaymentFormRenderer.php';
require_once 'UnzerCw/ConfigurationAdapter.php';
require_once 'UnzerCw/Entity/Util.php';



/** 
 * This class handlers the main payment interaction with the
 * Unzer server.
 */
abstract class UnzerCw_PaymentMethod extends UnzerCw_AbstractModule implements Customweb_Payment_Authorization_IPaymentMethod{


	/**
	 * @var Customweb_Payment_Authorization_IAdapterFactory
	 */
	private $adapterFactory = NULL;

	public $sort_order = NULL;

	private $configurationAdapter = NULL;

	public $form_action_url = NULL;

	public function __construct() {
		parent::__construct();
		if ($this->isEnabled()) {
			$this->sort_order = (int)$this->getSettingValue('sort_order');
		}
		define($this->getConstantPrefix() . strtoupper($this->getCode()) . '_TEXT_TITLE', $this->getPaymentMethodDisplayName());
		$this->updateMethodStatus();

		if (UnzerCw_ConfigurationAdapter::isPendingOrderModeActive()) {
			$this->tmpOrders = true;
			$this->tmpStatus = $this->getConfigurationAdapter()->getConfigurationValue('pending_order_status');
			$this->form_action_url = 'checkout_process.php';
		}
	}

	/**
	 * This method returns all currencies supported by this payment method.
	 */
	abstract protected function getSupportedCurrencies();

	/**
	 * This method returns all currencies supported by this payment method and activated
	 * in the store.
	 *
	 * @return array
	 */
	public function getPossibleCurrencies() {
		$currencies = $this->getSupportedCurrencies();
		$storeCurrencies = UnzerCw_Util::getCurrencies();
		$possibleCurrencies = array();
		foreach ($storeCurrencies as $currency) {
			if (isset($currencies[$currency['code']])) {
				$possibleCurrencies[$currency['code']] = $currency['title'];
			}
		}
		return $possibleCurrencies;
	}

	public function languageFileEvent() {
		define($this->getConstantPrefix() . strtoupper($this->getCode()) . 'TEXT_TITLE', $this->getPaymentMethodDisplayName());

		// Prevent warnings when those constants are expected.
		define($this->getConstantPrefix() . strtoupper($this->getCode()) . '_ALLOWED_TITLE', $this->getPaymentMethodDisplayName());
		define($this->getConstantPrefix() . strtoupper($this->getCode()) . '_ALLOWED_DESC', $this->getPaymentMethodDisplayName());


		if (isset($_GET['oID']) && isset($_GET['edit_action']) && $_GET['edit_action'] == 'other') {
			$this->updateMotoPaymentLink($_GET['oID']);
		}

	}

	protected function updateMotoPaymentLink($orderId) {
		
	}

	public function writeTransactionLinkToOrder(UnzerCw_Entity_Transaction $dbTransaction) {

		$orderId = intval($dbTransaction->getOrderId());
		if ($orderId <= 0) {
			throw new Exception("The given database transaction has no order id, hence the transaction link could not be updated.");
		}

		$content = $this->getPaymentMethodDisplayName() . ' (';
		$content .= UnzerCw_Util::renderBackendPopupWindow(
			$dbTransaction->getTransactionId(),
			'TransactionManagement',
			'edit',
			array('transaction_id' => $dbTransaction->getTransactionId()),
			false
		);
		$content .= ')';

		try {
			UnzerCw_Compatibility::writeBackendLink($dbTransaction->getOrderId(), $content);
		}
		catch(Exception $e) {
			// we ignore this because it does not affect anything.
		}
	}

	protected function setFormActionUrl() {
		if(preg_match('/.*\/checkout_confirmation\.php.*/i', $_SERVER['REQUEST_URI'])) {

			$orderContext = $this->getOrderContext();
			$adapter = $this->getAdapterFactory()->getAuthorizationAdapterByContext($orderContext);
			$shopAdapter = UnzerCw_Util::getShopAdapterByPaymentAdapter($adapter);

			$url = $shopAdapter->getCheckoutFormActionUrl($orderContext, $this);
			if ($url !== null) {
				$this->form_action_url = $url;
			}
		}
	}

	public function getFormActionUrl() {
		return $this->form_action_url;
	}

	/**
	 * @return UnzerCw_ConfigurationAdapter
	 */
	public function getConfigurationAdapter() {
		if ($this->configurationAdapter === null) {
			$this->configurationAdapter = new UnzerCw_ConfigurationAdapter();
		}
		return $this->configurationAdapter;
	}

	/**
	 * This method updates the status (enabled/disabled) of the payment method depending on current context.
	 *
	 * @return void
	 */
	protected function updateMethodStatus() {
		global $order;

		// Change status only, when we are in the checkout process
		if (is_object($order) && $this->isEnabled() && !preg_match('/.*\/admin\/orders\.php.*/i', $_SERVER['REQUEST_URI'])) {
			$this->checkGatewayStatus();
			$this->checkCurrency();
			$this->checkCountries();
			$this->checkOrderTotal();

			$rs = $this->updateOrderTotals();
			if ($rs !== null) {
				try {
					$orderContext = $this->getOrderContext();
				}
				catch(Exception $e) {
					// We ignore this kind of exceptions, because we may not beeing in a context, where we can create the order context.
					return;
				}
				$paymentContext = UnzerCw_Entity_Util::getPaymentCustomerContext($_SESSION['customer_id']);

				$adapter = $this->getAdapterFactory()->getAuthorizationAdapterByContext($orderContext);

				// Validate transaction
				$errorMessage = null;
				try {
					$adapter->preValidate($orderContext, $paymentContext);
				}
				catch(Exception $e) {
					$errorMessage = $e->getMessage();
					Customweb_Core_Logger_Factory::getLogger(__CLASS__)->logDebug('Validation failed with error: ' . $errorMessage);
					$this->enabled = false;
				}
				UnzerCw_Entity_Util::persist($paymentContext);
			}
		}
	}

	/**
	 * Check if the whole gateway is deactivated
	 */
	protected function checkGatewayStatus() {
		$baseModule = UnzerCw_AbstractModule::getModulInstanceByClass('unzercw');
		if (!$baseModule->isEnabled()) {
			$this->enabled = false;
		}
	}

	/**
	 * @return UnzerCw_OrderContext
	 */
	public function getOrderContext() {
		global $order;

		if (!isset($_SESSION['unzercw']['order_totals'])) {
			throw new Exception("Before creating a order context, the order totals must be written to the session.");
		}

		// TODO: should we need a cache here?
		return new UnzerCw_OrderContext($order, $_SESSION['unzercw']['order_totals'], new UnzerCw_PaymentMethodWrapper($this));
	}


	public function getTransactionContext(UnzerCw_Entity_Transaction $transaction, $orderContext = null, $aliasTransactionId = NULL) {

		if ($orderContext === null) {
			$orderContext = $this->getOrderContext();
		}

		$customerId = $orderContext->getCustomerPrimaryKey();
		if (is_object($transaction->getTransactionObject())) {
			$customerId = $transaction->getTransactionObject()->getTransactionContext()->getOrderContext()->getCustomerPrimaryKey();
		}

		return new UnzerCw_TransactionContext($transaction, $customerId, $orderContext, $aliasTransactionId);
	}


	public function newDatabaseTransaction($orderId = null, $customerId = null) {
		$transaction = new UnzerCw_Entity_Transaction();

		if (!empty($orderId)) {
			$transaction->setOrderId($orderId);
		}
		else if (!empty($_SESSION['tmp_oID'])) {
			$transaction->setOrderId((int)$_SESSION['tmp_oID']);
		}

		if ($customerId === null) {
			$customerId = $_SESSION['customer_id'];
		}

		$transaction->setSessionData($_SESSION);

		$transaction->setCustomerId($customerId);
		$transaction->setPaymentClass(get_class($this));
		UnzerCw_Entity_Util::persist($transaction);
		if(UnzerCw_Compatibility::isModifiedShop()){
			$_SESSION['unzercw']['transaction_id'] = $transaction->getTransactionId();
		}

		return $transaction;
	}

	protected function checkCurrency() {
		$activeCurrencies = $this->getSettingValue('active_currencies');
		if (is_array($activeCurrencies) && count($activeCurrencies) > 0) {
			if (!in_array($_SESSION['currency'], $activeCurrencies)) {
				$this->enabled = false;
			}
		}
		else {
			$possibleCurrencies = $this->getPossibleCurrencies();
			if (!isset($possibleCurrencies[$_SESSION['currency']])) {
				$this->enabled = false;
			}
		}
	}

	protected function checkCountries() {
		global $order;
		$allowedCountries = $this->getSettingValue('allowed_countries');

		if (is_array($allowedCountries) && count($allowedCountries) > 0) {

			if (isset($order->billing['country']['id']) && !in_array($order->billing['country']['id'], $allowedCountries)) {
				$this->enabled = false;
			}
		}
	}

	protected function checkOrderTotal() {
		global $order;
		if ($_SESSION['customers_status']['customers_status_show_price_tax'] == 0 && $_SESSION['customers_status']['customers_status_add_tax_ot'] == 1) {
			$total = $order->info['total'] + $order->info['tax'];
		}
		else {
			$total = $order->info['total'];
		}

		$minTotal = (float)$this->getSettingValue('min_total');
		if ($minTotal > 0 && $minTotal > $total) {
			$this->enabled = false;
		}

		$maxTotal = (float)$this->getSettingValue('max_total');
		if ($maxTotal > 0 && $maxTotal < $total) {
			$this->enabled = false;
		}
	}


	public function getPaymentMethodName() {
		return $this->machineName;
	}

	public function getPaymentMethodDisplayName() {
		$title = $this->getSettingValue('title');
		if (!empty($title) && isset($title[$_SESSION['languages_id']]) && !empty($title[$_SESSION['languages_id']])) {
			return $title[$_SESSION['languages_id']];
		}
		else {
			return $this->title;
		}
	}

	public function getPaymentMethodConfigurationValue($key, $languageCode = null) {
		if ($languageCode === null) {
			return $this->getSettingValue($key);
		}
		else {
			$languageCode = (string)$languageCode;
			$languageId = null;
			foreach (UnzerCw_Util::getLanguages() as $langId => $language) {
				if ($language['code'] == $languageCode) {
					$languageId = $langId;
					break;
				}
			}

			if ($languageId === null) {
				throw new Exception("Could not find language with language code '" . $languageCode . "'.");
			}

			$values = $this->getSettingValue($key);
			if (isset($values[$languageId])) {
				return $values[$languageId];
			}
			else {
				return current($values);
			}
		}
	}

	public function existsPaymentMethodConfigurationValue($key, $languageCode = null) {
		try {
			$this->getPaymentMethodConfigurationValue($key, $languageCode);
			return true;
		}
		catch(Exception $e) {
			return false;
		}
	}

	public function getTitle() {
		if ($this->isBackend()) {
			return 'Unzer: ' . $this->admin_title;
		}
		else {
			return $this->getPaymentMethodDisplayName();
		}
	}

	public function getDescription() {
		$description = unzercw_translate('This module activates the payment method !methodName for the Unzer gateway.', array('!methodName' => $this->getPaymentMethodDisplayName()));
		if ($this->enabled) {
			$description .= '<br />';
			$description .= '<a href="' . $this->getSettingsUrl() . '" class="button"  style="white-space: nowrap; width: auto;" target="_blank">' . unzercw_translate('Open Method Configuration') . '</a>';
		}
		return $description;
	}

	public function getConstantPrefix() {
		return 'MODULE_PAYMENT_';
	}

	public function getSettings() {
		$settings = parent::getSettings();

		$settings['sort_order'] = array(
			'title' => unzercw_translate('Sort Order'),
			'description' => unzercw_translate("The sort order of the payment method."),
			'type' => 'textfield',
			'default' => '0',
		);

		$settings['show_logos'] = array(
			'title' => unzercw_translate('Payment Logos'),
			'type' => 'select',
			'description' => unzercw_translate("Should the payment logos be shown on the checkout page?"),
			'options' => array(
				'yes' => unzercw_translate('Show payment logos on the checkout page'),
				'no' => unzercw_translate('No'),
			),
			'default' => 'no',
		);

		$countries = array();
		foreach (UnzerCw_Util::getCountries() as $countryId => $country) {
			$countries[$countryId] = $country['countries_name'];
		}
		$settings['allowed_countries'] = array (
			'title' => unzercw_translate('Allowed Countries'),
			'description' => unzercw_translate('The payment method is available for the selected countries. If none is selected, the payment method is available for all countries.'),
			'type' => 'multiselect',
			'options' => $countries,
			'default' => '',
		);

		$settings['active_currencies'] = array(
			'title' => unzercw_translate('Allowed Currencies'),
			'type' => 'multiselect',
			'description' => unzercw_translate('This payment method is only active for the selected currencies. If none is selected the method is active for all currencies.'),
			'options' => $this->getPossibleCurrencies(),
			'default' => '',
		);

		$settings['title'] = array(
			'title' => unzercw_translate('Method Title'),
			'type' => 'multilangfield',
			'description' => unzercw_translate("This controls the title which the user sees during checkout. If not set the default title ('!methodTitle') is used.", array('!methodTitle' => $this->title)),
			'default' => '',
		);

		$settings['description'] = array(
			'title' => unzercw_translate('Description'),
			'type' => 'multilangfield',
			'description' => unzercw_translate('This controls the description which the user sees during checkout.'),
			'default' => '',
		);

		$settings['min_total'] = array(
			'title' => unzercw_translate('Minimal Order Total'),
			'type' => 'textfield',
			'description' => unzercw_translate('Set here the minimal order total for which this payment method is available. If it is set to zero, it is always available.'),
			'default' => 0,
		);

		$settings['max_total'] = array(
			'title' => unzercw_translate('Maximal Order Total'),
			'type' => 'textfield',
			'description' => unzercw_translate('Set here the maximal order total for which this payment method is available. If it is set to zero, it is always available.'),
			'default' => 0,
		);

		$settings['error_handling'] = array(
			'title' => unzercw_translate('Error Handling'),
			'type' => 'select',
			'description' => unzercw_translate('During the authorization errors may occur. When an error occurs should the user be sent to the payment selection page or should the user be sent to a separate page?'),
			'default' => 'separate_page',
			'options' => array(
				'payment_selection' => unzercw_translate('Payment Selection Page'),
				'separate_page' => unzercw_translate('Separate Error Page'),
			),
		);

		return $settings;
	}

	public function install() {
		parent::install();

		// We insert the allowed config to prevent warnings on checkout page, but it is not used at all.
		$allowedKey = $this->getConstantPrefix() . strtoupper($this->getCode()) . '_ALLOWED';
		UnzerCw_ConfigurationTableAdapter::insert($allowedKey, '');

	}

	public function remove(){
		parent::remove();
		//Remove the allowed config, to fix issue with reinstalling
		$allowedKey = $this->getConstantPrefix() . strtoupper($this->getCode()) . '_ALLOWED';
		UnzerCw_ConfigurationTableAdapter::remove($allowedKey);
	}

	/**
	 * Check if we are in the backend
	 *
	 * @return Boolean
	 */
	protected function isBackend() {
		global $order;
		if(preg_match('/.*\/modules.*/i', $_SERVER['REQUEST_URI']))
			return true;
		else
			return false;
	}

	public function javascript_validation() {
	}

	public function getCssHtmlCode() {
		$url = UnzerCw_Util::getAssetResolver()->resolveAssetUrl('css/unzercw.css');
		return ' <link rel="stylesheet" type="text/css" href="' . $url . '" />';
	}

	
	public function selection() {
		try {
			$selection = array ('id' => $this->getCode(), 'module' => Customweb_Util_Html::convertSpecialCharacterToEntities($this->getPaymentMethodDisplayName()));

			if ($this->getConfigurationAdapter()->getConfigurationValue('include_css') == 'yes') {
				$selection['module'] .= $this->getCssHtmlCode();
			}
			$selection['logo_alt'] = htmlentities($this->getPaymentMethodDisplayName());

			$descriptions = $this->getSettingValue('description');

			$description = '';
			if (!empty($descriptions) && isset($descriptions[$_SESSION['languages_id']])) {
				$description = $descriptions[$_SESSION['languages_id']];
			}

			if (strtolower($this->getSettingValue('show_logos')) == 'yes') {
				$path = $this->getImageUrl();
				$description = '<img src="' . $path . '" class="unzercw-payment-image" title="' .$this->getPaymentMethodDisplayName() . '" /> ' . $description;
			}

			// For Gambio Version > 3.12.0.4 we can explicit set the icon, but only for some themes, so we cannot remove the inline icon. The merchant has to deactivate this manually.
			$selection['logo_url'] = $this->getImageUrl();

			if (!empty($description)) {
				$selection['description'] = Customweb_Util_Html::convertSpecialCharacterToEntities($description);
			}

			// Try to add fields in case an order object exists, (in Gambio do not try to get order for account checkout page)
			if (isset($GLOBALS['order']) && basename($_SERVER['PHP_SELF']) != 'account_checkout_express.php') {
				$orderContext = $this->getOrderContext();
				$selection['fields'] = array();
				if ($this->isAliasManagerActive()) {
					$handler = UnzerCw_Util::getAliasHandler($orderContext);
					$aliasTransactions = $handler->getAliasTransactions($orderContext);
					if (count($aliasTransactions) > 0) {

						$options = '<option value="none">' . unzercw_translate("Store new card") . '</option>';
						foreach ($aliasTransactions as $aliasTransaction) {
							$options .= '<option value="' . $aliasTransaction->getTransactionId() . '">' . $aliasTransaction->getAliasForDisplay() . '</option>';
						}
						$selection['fields'][] = array(
							'title' => unzercw_translate("Use previous stored credit card:"),
							'field' => '<select name="' . $this->getPaymentMethodName() . '[alias]">' . $options . '</select>'
						);
					}
				}

				if (count($selection['fields']) <= 0) {
					$this->updateOrderTotals();
					$adapter = $this->getAdapterFactory()->getAuthorizationAdapterByContext($orderContext);

					if ($adapter instanceof Customweb_Payment_Authorization_PaymentPage_IAdapter || $adapter instanceof Customweb_Payment_Authorization_Server_IAdapter) {
						$aliasTransaction = null;
						$paymentContext = UnzerCw_Entity_Util::getPaymentCustomerContext($_SESSION['customer_id']);
						$elements = $adapter->getVisibleFormFields($orderContext, $aliasTransaction, $failedTransaction, $paymentContext);
						$renderer = new UnzerCw_CheckoutPaymentFormRenderer();
						$renderer->setNamespacePrefix($this->getPaymentMethodName());
						$renderer->setCssClassPrefix('unzercw-');
						$renderer->setAddJs(false);
						$selection['fields'] = $renderer->renderElements($elements);
					}
				}
			}

			// for ot_payment Module
			if(defined("MODULE_ORDER_TOTAL_PAYMENT_STATUS") and MODULE_ORDER_TOTAL_PAYMENT_STATUS == 'True' && class_exists('ot_payment'))
			{
				$arrCosts = ot_payment::getPaymentCosts($this->getCode());
				$selection['module_cost'] = $arrCosts['text'];
			}
			if(defined("MODULE_ORDER_TOTAL_PAYFEE_STATUS") and MODULE_ORDER_TOTAL_PAYFEE_STATUS == 'True' && class_exists('ot_payfee'))
			{
				$arrCosts = ot_payfee::getPaymentCosts($this->getCode());
				$selection['module_cost'] = $arrCosts['text'];
			}
			// end ot_payment Module

			if (false) {
				return array(
					'id' => $this->getCode(),
					'module' => $this->getPaymentMethodDisplayName(),
					'description' => '<div style="border: 1px solid #ff0000; background: #ffcccc; font-weight: bold;">' . unzercw_translate('We experienced a problem with your sellxed payment extension. For more information, please visit the configuration page of the plugin.') . '</div>'
				);
			}

			return $selection;
		}
		catch(Exception $e) {
			// we catch here the exceptions to avoid not showing the exceptions at all.
			echo $e->getMessage();
			echo "<br>\n";
			echo $e->getTraceAsString();
			die();
		}
	}
	

	public function get_error() {

		if (isset($_GET['failedTransactionId'])) {
			$failedTransaction = UnzerCw_Entity_Util::findTransactionByTransactionId((int)$_GET['failedTransactionId']);

			$errorMessage = current($failedTransaction->getTransactionObject()->getErrorMessages());
			$message = unzercw_translate('The payment was unsuccessful. Please try it again or go back and choose another payment method.');
			if (!empty($errorMessage)) {
				$message .= ' (Error: ' . $errorMessage . ')';
			}
		}
		else if (isset($_GET['error'])) {
			$message = stripslashes(urldecode($_GET['error']));
		}

		if (isset($_SESSION['language_charset']) && stristr($_SESSION['language_charset'], 'utf') !== false) {
			$message = $message;
		}
		else {
			$message = utf8_decode($message);
		}

		$error = array (
			'title' => unzercw_translate('Error'),
			'error' => $message,
		);
		return $error;
	}

	
	public function pre_confirmation_check() {
		if (false) {
			UnzerCw_Log::add('There is a problem with your license. Please contact us (www.sellxed.com/support).');
			$this->redirectToPaymentSelection(unzercw_translate('We experienced a problem with your sellxed payment extension. For more information, please visit the configuration page of the plugin.'));
		}

		$this->updateOrderTotals();
		$orderContext = $this->getOrderContext();
		$paymentContext = UnzerCw_Entity_Util::getPaymentCustomerContext($_SESSION['customer_id']);
		$adapter = $this->getAdapterFactory()->getAuthorizationAdapterByContext($orderContext);

		$formData = array();
		if (isset($_POST[$this->getPaymentMethodName()])) {
			$formData = UnzerCw_Util::processPostData($_POST[$this->getPaymentMethodName()]);
		}

		// Validate transaction
		$errorMessage = null;
		try {
			$adapter->validate($orderContext, $paymentContext, $formData);
			UnzerCw_Entity_Util::persist($paymentContext);
		}
		catch(Exception $e) {
			$errorMessage = $e->getMessage();
			UnzerCw_Log::add('Validation failed with error: ' . $errorMessage);
			UnzerCw_Entity_Util::persist($paymentContext);
			$this->redirectToPaymentSelection($errorMessage);
		}

		if (!isset($_SESSION['formData'])) {
			$_SESSION['formData'] = array();
		}

		// Store the user input
		$_SESSION['formData'][$this->getPaymentMethodName()] = array();
		$_SESSION['unzercw']['aliasTransactionId'][$this->getPaymentMethodName()] = null;
		if (isset($_POST[$this->getPaymentMethodName()]['alias'])) {
			if ($_POST[$this->getPaymentMethodName()]['alias'] !== 'none') {
				$aliasTransactionId = $_POST[$this->getPaymentMethodName()]['alias'];
				$aliasTransaction = UnzerCw_Entity_Util::findTransactionByTransactionId($aliasTransactionId);

				$customerId = $aliasTransaction->getTransactionObject()->getTransactionContext()->getOrderContext()->getCustomerPrimaryKey();
				if ($_SESSION['customer_id'] == $customerId) {
					$_SESSION['unzercw']['aliasTransactionId'][$this->getPaymentMethodName()] = $_POST[$this->getPaymentMethodName()]['alias'];
				}
			}
		}
		else if (isset($_POST[$this->getPaymentMethodName()])) {
			$_SESSION['formData'][$this->getPaymentMethodName()] = $formData;
		}
	}
	

	public function getFormData() {
		if (isset($_SESSION['formData'][$this->getPaymentMethodName()]) && count($_SESSION['formData'][$this->getPaymentMethodName()]) > 0) {
			return $_SESSION['formData'][$this->getPaymentMethodName()];
		}
		else {
			return null;
		}
	}

	public function getAliasTransactionId() {
		if (isset($_SESSION['unzercw']['aliasTransactionId'][$this->getPaymentMethodName()])) {
			return $_SESSION['unzercw']['aliasTransactionId'][$this->getPaymentMethodName()];
		}
		else {
			return null;
		}
	}

	/**
	 * @return UnzerCw_Entity_Transaction
	 */
	public function getAliasTransaction() {
		$aliasTransactionId = $this->getAliasTransactionId();
		if ($aliasTransactionId !== null) {
			return UnzerCw_Entity_Util::findTransactionByTransactionId($aliasTransactionId);
		}
		else {
			return null;
		}
	}

	public function getAliasTransactionObject() {
		$aliasTransaction = $this->getAliasTransaction();
		if ($aliasTransaction !== null) {
			return $aliasTransaction->getTransactionObject();
		}
		else if ($this->isAliasManagerActive()) {
			return 'new';
		}
		else {
			return null;
		}
	}

	public function redirectToPaymentSelection($errorMessage = '', $failedTransactionId = null) {
		$payment_error_return = 'payment_error='.$this->code.'&error='.urlencode($errorMessage) ;
		if ($failedTransactionId !== null) {
			$payment_error_return .= '&failedTransactionId=' . $failedTransactionId;
		}

		xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
		die();
	}

	public function confirmation() {

		$this->updateOrderTotals();
		$this->setFormActionUrl();

		$title = $this->getPaymentMethodDisplayName();
		if ($this->getConfigurationAdapter()->getConfigurationValue('include_css') == 'yes') {
			$title .= $this->getCssHtmlCode();
		}
		$confirmation = array('title' => $title);

		return $confirmation;
	}

	public function process_button() {
		$this->updateOrderTotals();
		$orderContext = $this->getOrderContext();
		$adapter = $this->getAdapterFactory()->getAuthorizationAdapterByContext($orderContext);
		$shopAdapter = UnzerCw_Util::getShopAdapterByPaymentAdapter($adapter);
		if (UnzerCw_Compatibility::isModifiedShop()) {
			$css = '';
			if ($this->getConfigurationAdapter()->getConfigurationValue('include_css') == 'yes') {
				$css .= $this->getCssHtmlCode();
			}
			return $css . $shopAdapter->getCheckoutForm($orderContext, $this);
		}
		//Removed html entities encoding. Fixes as metntion in ticket: 2017072515472003521 (Gambio3)
		return	$shopAdapter->getCheckoutForm($orderContext, $this);

	}

	/**
	 * @return Customweb_Payment_Authorization_IAdapterFactory
	 */
	public function getAdapterFactory() {
		if ($this->adapterFactory === NULL) {
			$this->adapterFactory = UnzerCw_Util::getAuthorizationAdapterFactory();
		}

		return $this->adapterFactory;
	}


	public function payment_action() {
		if (isset($_SESSION['unzercw_print_order_number'])) {
			echo $GLOBALS['insert_id'];
			die();
		}

		if (isset($_SESSION['unzercw_skip_payment_process']) || isset($GLOBALS['unzercwrun_authorization_in_isolation'])) {
			return;
		}

		$this->updateOrderTotals();
		$orderContext = $this->getOrderContext();
		$adapter = $this->getAdapterFactory()->getAuthorizationAdapterByContext($orderContext);
		$shopAdapter = UnzerCw_Util::getShopAdapterByPaymentAdapter($adapter);
		return $shopAdapter->processPendingOrder($orderContext, $this);
	}

	public function before_process() {
		if (isset($_SESSION['unzercw_skip_payment_process'])) {
			return;
		}

		if (isset($GLOBALS['unzercw_injected_session_id']) && $GLOBALS['unzercw_injected_session_id'] === true) {
			unset($_POST['XTCsid']);
		}

		$orderContext = $this->getOrderContext();
		$adapter = $this->getAdapterFactory()->getAuthorizationAdapterByContext($orderContext);
		$shopAdapter = UnzerCw_Util::getShopAdapterByPaymentAdapter($adapter);
		return $shopAdapter->processOrder($orderContext, $this);
	}

	public function beforeSendOrderConfirmation() {
		if (isset($_SESSION['unzercw_transaction_object'])) {
			$transactionObject = Customweb_Core_Util_Serialization::unserialize($_SESSION['unzercw_transaction_object']);
			if ($transactionObject->getPaymentInformation() !== null) {
				$paymentInformation = $transactionObject->getPaymentInformation();
				$GLOBALS['smarty']->assign('PAYMENT_INFO_HTML', $paymentInformation);
				$GLOBALS['smarty']->assign('PAYMENT_INFO_TXT', strip_tags(str_replace("<br />", "\n", $paymentInformation)));
			}
		}
	}

	public function after_process() {
		echo $GLOBALS['insert_id'];
		if (isset($GLOBALS['unzercwrun_authorization_in_isolation'])) {
			die();
		}
	}

	public function updateSuccesfulOrderStatus(UnzerCw_Entity_Transaction $dbTransaction) {

		if ($dbTransaction->getTransactionObject() === NULL) {
			throw new Exception("The transaction object is not set.");
		}

		if (!$dbTransaction->getTransactionObject()->isAuthorized()) {
			throw new Exception("Invalid transaction state. To update the order status the transaction must be authorized.");
		}

		// Check if the order is not changed in the mean time. (--> Check currency and amount with the authorized one!)
		$order = new UnzerCw_BackendOrder($dbTransaction->getOrderId());
		$orderAmount = round((float)$order->info['total']);
		$currency = $order->info['currency'];
		if (strtolower($dbTransaction->getTransactionObject()->getCurrencyCode()) != strtolower($currency)) {
			throw new Exception("The currency code does not match, with the order currency code.");
		}
		$authorizationAmount = round($dbTransaction->getTransactionObject()->getAuthorizationAmount());

		// We do not check the amount here again. This is done in the api.
		if ($authorizationAmount < $orderAmount) {
// 			throw new Exception("The order total is bigger as the authorized amount.");
		}

		// Update the state
		$status = $dbTransaction->getTransactionObject()->getOrderStatus();

		$orderId = $dbTransaction->getOrderId();
		Customweb_Core_Assert::notNull($orderId, "Order id cannot be empty.");
		UnzerCw_Util::setOrderStatus($status, $orderId);
	}

	protected function updateOrderTotals() {
		return UnzerCw_Util::updateOrderTotalsInSession();
	}


	public function isAliasManagerActive() {

		
		return false;

	}
	
	protected function loadModifiedSuccessTransaction() {
		if(isset($_SESSION['unzercw']) && isset($_SESSION['unzercw']['transaction_id'])) {
			$transactionId = (int) $_SESSION['unzercw']['transaction_id'];
			$th = UnzerCw_Util::getTransactionHandler();
			try {
				$transaction = $th->findTransactionByTransactionId($transactionId);
				if(isset($_SESSION['customer_id']) && $transaction->getTransactionContext()->getOrderContext()->getCustomerId() == $_SESSION['customer_id']) {
					return $transaction;
				}
			}
			catch(Exception $e) {
			}
		}
		return null;
	}
	
	public function success(){
		if (UnzerCw_Compatibility::isModifiedShop()) {
			$transaction = $this->loadModifiedSuccessTransaction();
			if ($transaction && $transaction->getPaymentInformation()) {
				return array(
					array(
						"title" => "Unzer",
						"fields" => array(
							array(
								"title" => Customweb_I18n_Translation::__("Payment Information")->toString(),
								"field" => $transaction->getPaymentInformation()
							)
						)
					)
				);
			}
		}
		if (is_callable('parent::success')) {
			return parent::success();
		}
	}
}