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

require_once 'Customweb/Mvc/Template/RenderContext.php';
require_once 'Customweb/Core/Assert.php';
require_once 'Customweb/Core/Util/Rand.php';
require_once 'Customweb/Payment/Authorization/OrderContext/Address/Default.php';
require_once 'Customweb/Core/Http/Response.php';
require_once 'Customweb/Core/Exception/CastException.php';
require_once 'Customweb/Payment/ExternalCheckout/AbstractCheckoutService.php';
require_once 'Customweb/Util/String.php';
require_once 'Customweb/Mvc/Template/SecurityPolicy.php';
require_once 'Customweb/Database/Util.php';


/**
 * 
 * @author Thomas Hunziker
 * @Bean
 */
class UnzerCw_ExternalCheckoutService extends Customweb_Payment_ExternalCheckout_AbstractCheckoutService {
	
	private static $orderTotalsProcessed = false;
	
	public function loadContext($contextId, $cache = true) {
		return $this->getEntityManager()->fetch('UnzerCw_Entity_ExternalCheckoutContext', $contextId, $cache);
	}
	
	protected function refreshContext(Customweb_Payment_ExternalCheckout_AbstractContext $context) {
		if (!($context instanceof UnzerCw_Entity_ExternalCheckoutContext)) {
			throw new Customweb_Core_Exception_CastException('UnzerCw_Entity_ExternalCheckoutContext');
		}
		
		$cart = $_SESSION['cart'];
		Customweb_Core_Assert::notNull($cart);
		
		if (!empty($_SESSION['customer_id'])) {
			if ($context->getBillingAddress() !== null) {
				$billingAddressId = null;
				if (isset($_SESSION['billto'])) {
					$billingAddressId = $_SESSION['billto'];
				}
				$_SESSION['billto'] = $this->updateAddress($_SESSION['customer_id'], $context->getBillingAddress(), $billingAddressId);
			}
			
			if ($context->getShippingAddress() !== null) {
				$shippingAddressId = null;
				if (isset($_SESSION['sendto'])) {
					$shippingAddressId = $_SESSION['sendto'];
				}
				$_SESSION['sendto'] = $this->updateAddress($_SESSION['customer_id'], $context->getShippingAddress(), $shippingAddressId);
			}
			
			$context->setCustomerEmailAddress($this->getEMailAddressByCustomerId($_SESSION['customer_id']));
		}
		
		$context->updateFromCart($cart);
		
		$paymentMethod = $context->getPaymentMethod();
		if ($paymentMethod instanceof UnzerCw_PaymentMethod) {
			$_SESSION['payment'] = $paymentMethod->getCode();
		}
		$this->updateContextWithShippingMethod($context);
	}

	protected function updateUserSessionWithCurrentUser(Customweb_Payment_ExternalCheckout_AbstractContext $context) {
		if (!($context instanceof UnzerCw_Entity_ExternalCheckoutContext)) {
			throw new Customweb_Core_Exception_CastException('UnzerCw_Entity_ExternalCheckoutContext');
		}
		$email = $context->getCustomerEmailAddress();
		if (!empty($email) && $context->getBillingAddress() !== null) {
			$customer = $this->getCustomerByEmailAddress($email);
			if ($customer === null || $customer['customers_status'] == DEFAULT_CUSTOMERS_STATUS_ID_GUEST) {
				$billingAddress = $context->getBillingAddress();
				$gender = '';
				if ($billingAddress->getGender() == 'male') {
					$gender = 'm';
				}
				else if ($billingAddress->getGender() == 'female') {
					$gender = 'f';
				}
				
				$data = array (
					'!customers_vat_id_status' => 0,
					'!customers_vat_id' => 0,
					'!customers_status' => DEFAULT_CUSTOMERS_STATUS_ID_GUEST,
					'>customers_firstname' => UnzerCw_Util::encode($billingAddress->getFirstName()),
					'>customers_lastname' => UnzerCw_Util::encode($billingAddress->getLastName()),
					'>customers_email_address' => $email,
					'>customers_telephone' => UnzerCw_Util::encode($billingAddress->getPhoneNumber()),
					'>customers_fax' => '',
					'>customers_newsletter' => 0,
					'!account_type' => '1',
					'>customers_gender' => $gender,
					'>customers_password' => md5(Customweb_Core_Util_Rand::getRandomString(8)),
					'!customers_default_address_id' => 0,
					'>password_request_key' => '',
					'>payment_unallowed' => '',
					'>shipping_unallowed' => '',
				);
				$customerId = UnzerCw_Util::getDriver()->insert(TABLE_CUSTOMERS, $data);
				
				$logonData = array(
					'!customers_info_id' => $customerId,
					'!customers_info_number_of_logons' => '0',
					'?customers_info_date_account_created' => time(),
				);
				UnzerCw_Util::getDriver()->insert(TABLE_CUSTOMERS_INFO, $logonData);
				
				$defaultAddressId = $this->updateAddress($customerId, $context->getBillingAddress());
				UnzerCw_Util::getDriver()->update(TABLE_CUSTOMERS, array('customers_default_address_id' => $defaultAddressId), array('!customers_id' => $customerId));
				
				$customer = $this->getCustomerByEmailAddress($email);
				if ($customer === null) {
					throw new Exception("Unable to insert new guest customer.");
				}
			}
			
			$rs = UnzerCw_Database::prepare("select entry_country_id, entry_zone_id from ".TABLE_ADDRESS_BOOK." where customers_id = '".(int) $customer['customers_id']."' and address_book_id = '".$customer['customers_default_address_id']."'");
			$check_country = UnzerCw_Database::fetch($rs);
			
			$_SESSION['customer_gender'] = UnzerCw_Util::encode($customer['customers_gender']);
			$_SESSION['customer_first_name'] = UnzerCw_Util::encode($customer['customers_firstname']);
			$_SESSION['customer_last_name'] = UnzerCw_Util::encode($customer['customers_lastname']);
			$_SESSION['customer_id'] = $customer['customers_id'];
			$_SESSION['customer_vat_id'] = $customer['customers_vat_id'];
			$_SESSION['customer_default_address_id'] = $customer['customers_default_address_id'];
			$_SESSION['customer_country_id'] = $check_country['entry_country_id'];
			$_SESSION['customer_zone_id'] = $check_country['entry_zone_id'];
			
			UnzerCw_Database::query("update ".TABLE_CUSTOMERS_INFO." SET customers_info_date_of_last_logon = now(), customers_info_number_of_logons = customers_info_number_of_logons+1 WHERE customers_info_id = '".(int) $_SESSION['customer_id']."'");
			require UNZERCW_CATALOG_PATH . '/includes/write_customers_status.php';
			
		}
	}
	


	private function updateAddress($customerId, Customweb_Payment_Authorization_OrderContext_IAddress $source, $targetAddressId = null) {
		Customweb_Core_Assert::hasLength($customerId);
		
		$gender = '';
		if ($source->getGender() == 'male') {
			$gender = 'm';
		}
		else if ($source->getGender() == 'female') {
			$gender = 'f';
		}
		
		$countryId = 0;
		$countryCode = $source->getCountryIsoCode();
		if (!empty($countryCode)) {
			$country = UnzerCw_Util::getCountryByCode($countryCode);
			if (isset($country['status']) && $country['status'] == '1') {
				$countryId = $country['countries_id'];
			}
			else {
				throw new Exception(unzercw_translate("The country '!name' is not supported. Please choose a different country.", array('!name' => $country['countries_name'])));
			}
		}
		
		$addressData = array(
			'!customers_id' => $customerId, 
			'>entry_firstname' => UnzerCw_Util::encode(Customweb_Util_String::substrUtf8($source->getFirstName(), 0, 32)),
			'>entry_lastname' => UnzerCw_Util::encode(Customweb_Util_String::substrUtf8($source->getLastName(), 0, 32)),
			'>entry_street_address' => UnzerCw_Util::encode(Customweb_Util_String::substrUtf8($source->getStreet(), 0, 64)),
			'>entry_postcode' => UnzerCw_Util::encode(Customweb_Util_String::substrUtf8($source->getPostCode(), 0, 32)),
			'>entry_city' => UnzerCw_Util::encode(Customweb_Util_String::substrUtf8($source->getCity(), 0, 32)),
			'!entry_country_id' => $countryId,
			'>entry_gender' => $gender,
			'>entry_company' => UnzerCw_Util::encode(Customweb_Util_String::substrUtf8($source->getCompanyName(), 0, 32)),
			'>entry_suburb' => '',
			'!entry_zone_id' => 0,
			'>entry_state' => '',
		);
		
		if ($targetAddressId !== null) {
			$rs = UnzerCw_Util::getDriver()
				->query("SELECT * FROM " . TABLE_ADDRESS_BOOK . " WHERE address_book_id = !addressId AND customers_id = !customersId")
				->setParameter("!addressId", $targetAddressId)
				->setParameter("!customersId", $customerId)
				->getRowCount();
			if ($rs > 0) {
				UnzerCw_Util::getDriver()->update(TABLE_ADDRESS_BOOK, $addressData, array('!address_book_id' => $targetAddressId));
				return $targetAddressId;
			}
		}
		
		$rs = UnzerCw_Util::getDriver()
			->query("SELECT address_book_id FROM " . TABLE_ADDRESS_BOOK . Customweb_Database_Util::getWhereClause($addressData))
			->setParameters($addressData)
			->fetch();
		if (isset($rs['address_book_id'])) {
			return $rs['address_book_id'];
		}
		else {
			return UnzerCw_Util::getDriver()->insert(TABLE_ADDRESS_BOOK, $addressData);
		}
	}
	
	
	public function authenticate(Customweb_Payment_ExternalCheckout_IContext $context, $emailAddress, $successUrl) {
		if (!($context instanceof UnzerCw_Entity_ExternalCheckoutContext)) {
			throw new Customweb_Core_Exception_CastException('UnzerCw_Entity_ExternalCheckoutContext');
		}
		
		// When the user is already authenticated.
		if (isset($_SESSION['customer_id'])) {
			return Customweb_Core_Http_Response::redirect($successUrl);
		}
		
		if ($context->getBillingAddress() === null) {
			$billingAddress = new Customweb_Payment_Authorization_OrderContext_Address_Default();
			$billingAddress->setFirstName('First')->setLastName('Last')->setCity('unknown')->setStreet('unknown 1')->setCountryIsoCode('DE')->setPostCode(
					'10000');
			$context->setBillingAddress($billingAddress);
		}
	
		// Check if we can skip the account selection screen.
		if (UnzerCw_ConfigurationAdapter::isForceGuestAccountActiveForExternalCheckout() && !empty($emailAddress)) {
			$customer = $this->getCustomerByEmailAddress($emailAddress);
			if ($customer === null || $customer['customers_status'] == DEFAULT_CUSTOMERS_STATUS_ID_GUEST) {
				$this->updateCustomerEmailAddress($context, $emailAddress);
				return Customweb_Core_Http_Response::redirect($successUrl);
			}
		}
		$context->setAuthenticationEmailAddress($emailAddress);
		$context->setAuthenticationSuccessUrl($successUrl);
		$this->getEntityManager()->persist($context);
		
		$url = UnzerCw_Util::getFrontendUrl('unzercw_ec_login.php');
		
		$_SESSION['unzercw-context-id'] = $context->getContextId();
		
		return Customweb_Core_Http_Response::redirect($url);
	}
	
	public function renderShippingMethodSelectionPane(Customweb_Payment_ExternalCheckout_IContext $context, $errorMessages) {
		if (!($context instanceof UnzerCw_Entity_ExternalCheckoutContext)) {
			throw new Customweb_Core_Exception_CastException('UnzerCw_Entity_ExternalCheckoutContext');
		}
		
		$this->refreshContext($context);
		
		if (!isset($_SESSION['cart'])) {
			throw new Exception("No shopping cart object in session.");
		}
		
		$cart = $_SESSION['cart'];
		Customweb_Core_Assert::notNull($cart);
		
		if (!isset($_SESSION['sendto'])) {
			throw new Exception("The shipping address has to be set on the context before calling the shipping method render function.");
		}
		
		if (!isset($_SESSION['customer_id'])) {
			throw new Exception("The customer must be authenticated before calling the shipping method render function.");
		}

		// We store the cart id. In case the cart is now altered, we can detected this later during
		// confirmation.
		$_SESSION['cartID'] = $_SESSION['cart']->cartID;
		
		$order = $this->getOrder();
		
		
		$templateContext = new Customweb_Mvc_Template_RenderContext();
		$templateContext->setSecurityPolicy(new Customweb_Mvc_Template_SecurityPolicy());
		$templateContext->setTemplate('snippets/shipping-method');
		$templateContext->addVariable('NO_SHIPPING_METHOD_REQUIRED', unzercw_translate("No shipping method required."));
		$templateContext->addVariable('SHIPPING_METHOD_SELECTION', unzercw_translate("Shipping Method Selection"));
		$templateContext->addVariable('UPDATE_BUTTON', UnzerCw_Compatibility::getUpdateButton());
		$templateContext->addVariable('ERROR_MESSAGE', $errorMessages);
		
		if ($order->content_type == 'virtual' || ($order->content_type == 'virtual_weight') || ($cart->count_contents_virtual() == 0)) {
			$templateContext->addVariable('SHIPPING_SELECTION_BOCK', ''); 
		}
		else {
			$vars = $this->getShippingPaneVariables($order, $cart);
			$smarty = new Smarty();
			$smarty->assign($vars);
			$smarty->caching = 0;
			
			$shippingSelectionBlock = $smarty->fetch(CURRENT_TEMPLATE.'/module/checkout_shipping_block.html');
			if (isset($_SESSION['language_charset']) && strtolower($_SESSION['language_charset']) != 'utf-8') {
				$shippingSelectionBlock = utf8_encode($shippingSelectionBlock);
			}
			$templateContext->addVariable('SHIPPING_SELECTION_BOCK', $shippingSelectionBlock);
		}
		
		// Since the default shipping method may be set, we need to update the context to set the 
		// shipping method on the context.
		$this->refreshContext($context);
		$this->getEntityManager()->persist($context);
		
		return UnzerCw_Util::getTemplateRenderer()->render($templateContext);
	}
	
	private function getShippingPaneVariables($order, $cart) {
		if ($order->delivery['country']['iso_code_2'] != '') {
			$_SESSION['delivery_zone'] = $order->delivery['country']['iso_code_2'];
		}
		
		$GLOBALS['order'] = $order;
		
		$shipping_modules = $this->getShippingModulesObject();
		
		$isFreeShipping = $this->isFreeShippingAllowed($order);
		if ($isFreeShipping) {
			require_once DIR_WS_LANGUAGES.$_SESSION['language'].'/modules/order_total/ot_shipping.php';
		}

		// get all available shipping quotes
		$quotes = $shipping_modules->quote();
		
		if (!isset($_SESSION['shipping']) || (isset ($_SESSION['shipping']) && $_SESSION['shipping'] == false && strpos(MODULE_SHIPPING_INSTALLED, ';') > 0)) {
			$_SESSION['shipping'] = $shipping_modules->cheapest();
		}
		
		
		$vars = array();
		if (strlen(MODULE_SHIPPING_INSTALLED) > 0) {
			$vars['FREE_SHIPPING'] = $isFreeShipping;
		
			if ($isFreeShipping) {
				$vars['FREE_SHIPPING_TITLE'] = FREE_SHIPPING_TITLE;
				$vars['FREE_SHIPPING_DESCRIPTION'] = sprintf(FREE_SHIPPING_DESCRIPTION, $GLOBALS['xtPrice']->xtcFormat(MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER, true, 0, true)).
				xtc_draw_hidden_field('shipping', 'free_free');
				$vars['FREE_SHIPPING_ICON'] = $quotes[0]['icon'];
			} 
			else {
				$radio_buttons = 0;
				for ($i = 0, $n = sizeof($quotes); $i < $n; $i ++) {
					for ($j = 0, $n2 = sizeof($quotes[$i]['methods']); $j < $n2; $j ++) {
						$checked = (($quotes[$i]['id'].'_'.$quotes[$i]['methods'][$j]['id'] == $_SESSION['shipping']['id']) ? true : false);
						// The current selected shipping method has an error. So we unselect it.
						if ($checked && isset ($quotes[$i]['error'])) {
							unset($_SESSION['shipping']);
						}
						elseif ($checked){
							$_SESSION['shipping']['cost'] = $quotes[$i]['methods'][$j]['cost'];
						}
						if (!isset ($quotes[$i]['error'])) {
							$quotes[$i]['methods'][$j]['radio_buttons'] = $radio_buttons;
							if (($checked == true) || ($n == 1 && $n2 == 1)) {
								$quotes[$i]['methods'][$j]['checked'] = 1;
							}
		
							if (($n > 1) || ($n2 > 1)) {
								if ($_SESSION['customers_status']['customers_status_show_price_tax'] == 0) {
									$quotes[$i]['tax'] = 0;
								}
								$quotes[$i]['methods'][$j]['price'] = $GLOBALS['xtPrice']->xtcFormat(xtc_add_tax($quotes[$i]['methods'][$j]['cost'], $quotes[$i]['tax']), true, 0, true);
								$quotes[$i]['methods'][$j]['radio_field'] = xtc_draw_radio_field('shipping', $quotes[$i]['id'].'_'.$quotes[$i]['methods'][$j]['id'], $checked);
							} 
							else {
								if ($_SESSION['customers_status']['customers_status_show_price_tax'] == 0) {
									$quotes[$i]['tax'] = 0;
								}
		
								$quotes[$i]['methods'][$j]['price'] = $GLOBALS['xtPrice']->xtcFormat(xtc_add_tax($quotes[$i]['methods'][$j]['cost'], $quotes[$i]['tax']), true, 0, true).xtc_draw_hidden_field('shipping', $quotes[$i]['id'].'_'.$quotes[$i]['methods'][$j]['id']);
		
							}
							$radio_buttons ++;
						}
					}
				}
		
				$vars['module_content'] = $quotes;
		
			}
		}
		
		return $vars;
	}
	
	
	private function isFreeShippingAllowed($order) {
		if (defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING') && (MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING == 'true')) {
			switch (MODULE_ORDER_TOTAL_SHIPPING_DESTINATION) {
				case 'national' :
					if ($order->delivery['country_id'] == STORE_COUNTRY)
						$pass = true;
						break;
				case 'international' :
					if ($order->delivery['country_id'] != STORE_COUNTRY)
						$pass = true;
						break;
				case 'both' :
					$pass = true;
					break;
				default :
					$pass = false;
					break;
			}
		
			$free_shipping = false;
			if (($pass == true) && ($order->info['total'] - $order->info['shipping_cost'] >= $GLOBALS['xtPrice']->xtcFormat(MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER, false, 0, true))) {
				$free_shipping = true;
		
				
			}
		} else {
			$free_shipping = false;
		}
		
		return $free_shipping;
	}
	
	private function getShippingModulesObject() {
		if (!isset($GLOBALS['shipping_modules'])) {

			// We need to set the weight otherwise the shipping cost calculation may
			// fail.
			$GLOBALS['total_weight'] = $_SESSION['cart']->show_weight();
			$GLOBALS['total_count'] = $_SESSION['cart']->count_contents();
			$GLOBALS['order'] = $this->getOrder();
			
			// load all enabled shipping modules
			require_once (DIR_WS_CLASSES.'shipping.php');
			$GLOBALS['shipping_modules'] = new shipping();
		}
		return $GLOBALS['shipping_modules'];
	}
	
	protected function updateShippingMethodOnContext(Customweb_Payment_ExternalCheckout_IContext $context, Customweb_Core_Http_IRequest $request) {
		if (!($context instanceof UnzerCw_Entity_ExternalCheckoutContext)) {
			throw new Customweb_Core_Exception_CastException('UnzerCw_Entity_ExternalCheckoutContext');
		}
		
		$shipping_modules = $this->getShippingModulesObject();
		
		$parameters = $request->getParameters();
		
		$order = $this->getOrder();
		$isFreeShipping = $this->isFreeShippingAllowed($order);
		
		if (strlen(MODULE_SHIPPING_INSTALLED) > 0 || $isFreeShipping) {
			if ((isset ($parameters['shipping'])) && (strpos($parameters['shipping'], '_'))) {
	
				list ($module, $method) = explode('_', $parameters['shipping']);
				if (is_object($GLOBALS[$module]) || ($parameters['shipping'] == 'free_free')) {
					$quote = array();
					if ($parameters['shipping'] == 'free_free') {
						$quote[0]['methods'][0]['title'] = FREE_SHIPPING_TITLE;
						$quote[0]['methods'][0]['cost'] = '0';
					} else {
						$quote = $shipping_modules->quote($method, $module);
					}
					if (isset ($quote['error'])) {
						unset ($_SESSION['shipping']);
					} else {
						if ((isset ($quote[0]['methods'][0]['title'])) && (isset ($quote[0]['methods'][0]['cost']))) {
							$_SESSION['shipping'] = array (
								'id' => $parameters['shipping'], 
								'title' => (($isFreeShipping == true) ? $quote[0]['methods'][0]['title'] : $quote[0]['module'].' ('.$quote[0]['methods'][0]['title'].')'), 
								'cost' => $quote[0]['methods'][0]['cost'],
							);
						}
					}
				} else {
					throw new Exception("Unable to determine shipping module.");
				}
			}
		}
		else {
			$_SESSION['shipping'] = false;
		}
		$this->refreshContext($context);
		$this->getEntityManager()->persist($context);
	}
	
	private function updateContextWithShippingMethod(UnzerCw_Entity_ExternalCheckoutContext $context) {
		if (isset($_SESSION['shipping']['title]'])) {
			$context->setShippingMethodName($_SESSION['shipping']['title]']);
		}
		else {
			$context->setShippingMethodName(unzercw_translate("No shipping method needed."));
		}
	}
	
	protected function extractShippingName(Customweb_Payment_ExternalCheckout_IContext $context, Customweb_Core_Http_IRequest $request) {
		if (!($context instanceof UnzerCw_Entity_ExternalCheckoutContext)) {
			throw new Customweb_Core_Exception_CastException('UnzerCw_Entity_ExternalCheckoutContext');
		}
		
		// We store the shipping method during processing of the user selection.
		return $context->getShippingMethodName();
	}
	
	public function getPossiblePaymentMethods(Customweb_Payment_ExternalCheckout_IContext $context) {
		if (!($context instanceof UnzerCw_Entity_ExternalCheckoutContext)) {
			throw new Customweb_Core_Exception_CastException('UnzerCw_Entity_ExternalCheckoutContext');
		}
		return UnzerCw_Util::getAllPaymentMethods();
	}
	

	public function renderAdditionalFormElements(Customweb_Payment_ExternalCheckout_IContext $context, $errorMessage) {
		$templateContext = new Customweb_Mvc_Template_RenderContext();
		$templateContext->setSecurityPolicy(new Customweb_Mvc_Template_SecurityPolicy());
		$templateContext->setTemplate('snippets/additional-form-fields');
		$templateContext->addVariable('COMMENT_TITLE', unzercw_translate("Add a note to your order"));
		$templateContext->addVariable('commentContent', strip_tags($_SESSION['comments']));
		$templateContext->addVariable('ERROR_MESSAGE', $errorMessage);
		
		return UnzerCw_Util::getTemplateRenderer()->render($templateContext);
	}
	
	public function processAdditionalFormElements(Customweb_Payment_ExternalCheckout_IContext $context, Customweb_Core_Http_IRequest $request) {
		$parameters = $request->getParameters();
		if (isset($parameters['comments'])) {
			$_SESSION['comments'] = xtc_db_prepare_input(strip_tags($parameters['comments']));
		}
	}
	
	
	
	public function renderReviewPane(Customweb_Payment_ExternalCheckout_IContext $context, $renderConfirmationFormElements, $errorMessage) {
		if (!($context instanceof UnzerCw_Entity_ExternalCheckoutContext)) {
			throw new Customweb_Core_Exception_CastException('UnzerCw_Entity_ExternalCheckoutContext');
		}
		require_once (DIR_WS_CLASSES.'order.php');
		$order = new order();
		$GLOBALS['order'] = $order;
		
		$templateContext = new Customweb_Mvc_Template_RenderContext();
		$templateContext->setSecurityPolicy(new Customweb_Mvc_Template_SecurityPolicy());
		$templateContext->setTemplate('snippets/confirmation');
		$templateContext->addVariable('CONFIRMATION_TITLE', unzercw_translate("Order Overview"));
		$templateContext->addVariable('ERROR_MESSAGE', $errorMessage);
		
		$products = $order->products;
		foreach ($products as $index => $product) {
			if (!isset($product['price_formated'])) {
				$products[$index]['price_formated'] = $GLOBALS['xtPrice']->xtcFormat($product['price'], true);
			}
			if (!isset($product['final_price_formated'])) {
				$products[$index]['final_price_formated'] = $GLOBALS['xtPrice']->xtcFormat($product['final_price'], true);
			}
			if (isset($product['name'])) {
				$products[$index]['name'] = UnzerCw_Util::decode($product['name']);
			}
			if (isset($product['attributes'])) {
				foreach ($product['attributes'] as $attributeIndex => $attribute) {
					if (isset($attribute['value'])) {
						$products[$index]['attributes'][$attributeIndex]['value'] = UnzerCw_Util::decode($attribute['value']);
					}
					if (isset($attribute['option'])) {
						$products[$index]['attributes'][$attributeIndex]['option'] = UnzerCw_Util::decode($attribute['option']);
					}
				}
			}
		}
		
		$templateContext->addVariable("PRODUCTS", $products);
		
		// GV Code Start
		$this->getOrderTotals();
		$GLOBALS['order_total_modules']->collect_posts();
		$GLOBALS['order_total_modules']->pre_confirmation_check();
		// GV Code End
		
		$templateContext->addVariable('HEADER_QTY', unzercw_translate("Quantity"));
		$templateContext->addVariable('HEADER_ARTICLE', unzercw_translate('Products'));
		$templateContext->addVariable('HEADER_SINGLE', unzercw_translate("Unit Price"));
		$templateContext->addVariable('HEADER_TOTAL', unzercw_translate("Total"));
		$templateContext->addVariable('CHECKOUT_SHOW_PRODUCTS_IMAGES', CHECKOUT_SHOW_PRODUCTS_IMAGES);
		
		$templateContext->addVariable('TEXT_SHIPPING_ADDRESS', unzercw_translate('Shipping Address'));
		$templateContext->addVariable('TEXT_BILLING_ADDRESS', unzercw_translate('Billing Address'));
		$templateContext->addVariable('SHIPPING_ADDRESS', UnzerCw_Util::decode(xtc_address_format($order->delivery['format_id'], $order->delivery, 1, ' ', '<br />')));
		$templateContext->addVariable('BILLING_ADDRESS', UnzerCw_Util::decode(xtc_address_format($order->billing['format_id'], $order->billing, 1, ' ', '<br />')));
		
		if (MODULE_ORDER_TOTAL_INSTALLED) {
			$this->processOrderTotals();
			$total_block = $GLOBALS['order_total_modules']->output();
			$templateContext->addVariable('TOTAL_BLOCK', UnzerCw_Util::decode($total_block));
		}
		
		if ($renderConfirmationFormElements && (DISPLAY_CONDITIONS_ON_CHECKOUT == 'true' || (function_exists('gm_get_conf') && gm_get_conf('GM_SHOW_CONDITIONS') == 1))) {
			if (GROUP_CHECK == 'true') {
				$group_check = "and group_ids LIKE '%c_" . $_SESSION['customers_status']['customers_status_id'] . "_group%'";
			}
		
			$shop_content_data = UnzerCw_Util::getDriver()->query(
				"SELECT content_title, content_heading, content_text, content_file FROM " . TABLE_CONTENT_MANAGER . " WHERE content_group='3' " . $group_check . " AND languages_id='" . (int)$_SESSION['languages_id'] . "'"
			)->fetch();
		
			if ($shop_content_data['content_file'] != '') {
				$conditions = '<iframe SRC="' . DIR_WS_CATALOG . 'media/content/' . $shop_content_data['content_file'] . '" width="100%" height="300">';
				$conditions .= '</iframe>';
			} else {
				$conditions = '<textarea name="blabla" cols="60" rows="10" readonly="readonly">' . strip_tags(str_replace('<br />', "\n", $shop_content_data['content_text'])) . '</textarea>';
			}
		
			$templateContext->addVariable('GTC_TITLE', unzercw_translate('General Terms and Conditions'));
			$link = xtc_href_link(FILENAME_POPUP_CONTENT, 'coID=3');
			$templateContext->addVariable('GTC', $conditions);
			$templateContext->addVariable('GTC_TEXT',  unzercw_translate("I accept the <a href='!link' target='_blank'>general terms and conditions</a>.", array('!link' => $link)));
		}
		
		$templateContext->addVariable('CONFIRMATION_BUTTON', UnzerCw_Compatibility::getOrderConfirmationButton());
		
		return UnzerCw_Util::getTemplateRenderer()->render($templateContext);
	}

	public function validateReviewForm(Customweb_Payment_ExternalCheckout_IContext $context, Customweb_Core_Http_IRequest $request) {
		if (!($context instanceof UnzerCw_Entity_ExternalCheckoutContext)) {
			throw new Customweb_Core_Exception_CastException('UnzerCw_Entity_ExternalCheckoutContext');
		}
		
		if (empty($_SESSION['shipping'])) {
			throw new Exception(unzercw_translate('You have to select a shipping method.'));
		}
		
		if ($_SESSION['cart']->cartID != $_SESSION['cartID']) {
			throw new Exception("Cart content was altered.");
		}
		
		$parameters = $request->getParameters();
		if (function_exists('gm_get_conf')) {
			if (gm_get_conf('GM_CHECK_CONDITIONS') == 1 && (!isset($parameters['conditions']) || $parameters['conditions'] != 'conditions')) {
				throw new Exception(unzercw_translate('You have to accept the general terms and conditions.'));
			}
		}
		else {
			if (!isset($parameters['conditions']) || $parameters['conditions'] != 'conditions') {
				throw new Exception(unzercw_translate('You have to accept the general terms and conditions.'));
			}
		}
		
	}
	


	protected function createTransactionContextFromContext(Customweb_Payment_ExternalCheckout_IContext $context) {
		if (!($context instanceof UnzerCw_Entity_ExternalCheckoutContext)) {
			throw new Customweb_Core_Exception_CastException('UnzerCw_Entity_ExternalCheckoutContext');
		}
	
		$this->getOrder();
		$this->processOrderTotals();
		UnzerCw_Util::updateOrderTotalsInSession();
			
		$orderId = null;
		if (UnzerCw_ConfigurationAdapter::isPendingOrderModeActive()) {
			$_SESSION['unzercw_skip_payment_process'] = 'true';
			$result = UnzerCw_Util::sendLocalRequest('unzercw_ec_authorization.php', array(), $_SESSION);
			
			if (!isset($result['body'])) {
				throw new Exception("Invalid result format received from authorization execution.");
			}
			
			$rs = array();
			if (!preg_match('/^([0-9]+)$/', $result['body'], $rs) || !isset($rs[1])) {
				if (!empty($result['body'])) {
					throw new Exception("Failed to create order. Error: " . $result['body']);
				}
				else {
					throw new Exception("Failed to create order (no error message provided by checkout_process.php).");
				}
			}
			$_SESSION = $result['sessionData'];
			$orderId = $result['body'];
		}
		
		$paymentMethod = $context->getPaymentMethod();
		if (!($paymentMethod instanceof UnzerCw_PaymentMethod)) {
			throw new Customweb_Core_Exception_CastException('UnzerCw_PaymentMethod');
		}
		$transaction = $paymentMethod->newDatabaseTransaction($orderId);
		return $paymentMethod->getTransactionContext($transaction);
	}
	
	private function getOrderTotals() {
		if (!isset($GLOBALS['order_total_modules'])) { 
			require_once DIR_WS_CLASSES.'order_total.php';
			$GLOBALS['order_total_modules'] = new order_total();
		}
		return $GLOBALS['order_total_modules'];
	}
	
	private function processOrderTotals() {
		if (self::$orderTotalsProcessed === false) {
			self::$orderTotalsProcessed = true;
			$this->getOrderTotals()->process();
		}
	}
	
	/**
	 * @return order
	 */
	private function getOrder() {
		require_once (DIR_WS_CLASSES.'order.php');
		$GLOBALS['order'] = new order();
		return $GLOBALS['order'];
	}
	
	
	private function getCustomerByEmailAddress($emailAddress) {
		$rs = UnzerCw_Util::getDriver()->query("SELECT * FROM " . TABLE_CUSTOMERS . " WHERE customers_email_address = >mail")->setParameter(">mail", $emailAddress)->fetch();
		
		if ($rs !== false) {
			return $rs;
		}
		else {
			return null;
		}
	}
	
	private function getEMailAddressByCustomerId($id) {
		$rs = UnzerCw_Util::getDriver()->query("SELECT customers_email_address FROM " . TABLE_CUSTOMERS . " WHERE customers_id = >id")->setParameter(">id", (int)$id)->fetch();
		if ($rs !== false) {
			return $rs['customers_email_address'];
		}
		else {
			throw new Exception("Unable to find customer by id.");
		}
	}
	
	
}