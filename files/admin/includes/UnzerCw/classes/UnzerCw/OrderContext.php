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

require_once 'Customweb/Payment/Authorization/OrderContext/AbstractDeprecated.php';
require_once 'Customweb/Util/Currency.php';
require_once 'Customweb/Payment/Authorization/DefaultInvoiceItem.php';
require_once 'Customweb/Payment/Authorization/IOrderContext.php';
require_once 'Customweb/Core/Language.php';
require_once 'Customweb/Core/Util/Rand.php';
require_once 'Customweb/Util/Invoice.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/Database.php';
require_once 'UnzerCw/OrderContext.php';

class UnzerCw_OrderContext extends Customweb_Payment_Authorization_OrderContext_AbstractDeprecated implements 
		Customweb_Payment_Authorization_IOrderContext {
	protected $order;
	protected $orderAmount;
	protected $currencyCode;
	protected $paymentMethod;
	protected $language;
	protected $customerId = null;
	protected $addTax = false;
	protected $hasNoTax = false;
	protected $orderTotals = array();
	protected $customerStatus = array();
	protected $checkoutId = null;
	protected $customerCid = null;

	public function __construct($order, $order_totals = null, Customweb_Payment_Authorization_IPaymentMethod $paymentMethod = null, $customerId = null){
		if ($order === null) {
			throw new Exception("Order object is null.");
		}
		if($order instanceof UnzerCw_OrderContext){
			//Copy constructor with new checkoutID
			$this->customerId = $order->customerId;
			$this->customerCid = $order->customerCid;
			$this->orderTotals = $order->orderTotals;
			$this->order = $order->order;
			$this->currencyCode = $order->currencyCode;
			$this->paymentMethod = $order->paymentMethod;
			$this->customerStatus = $order->customerStatus;
			$this->addTax = $order->addTax;
			$this->hasNoTax = $order->hasNoTax;
			$this->language = $order->language;
			$this->orderAmount = $order->orderAmount;
			$this->checkoutId = Customweb_Core_Util_Rand::getUuid();
			return;
		}
		
		if ($paymentMethod === null) {
			throw new Exception("Payment method is null.");
		}
		
		if ($order_totals === null) {
			throw new Exception("Order totals are null.");
		}
		
		if ($customerId === null) {
			$this->customerId = (int) $_SESSION['customer_id'];
		}
		else {
			$this->customerId = (int) $customerId;
		}
		
		$rs = UnzerCw_Database::prepare(
				'SELECT customers_cid FROM ' . TABLE_CUSTOMERS . ' WHERE customers_id = \'%d\' LIMIT 0,1', array(
							$this->customerId 
						));
		if ($row = UnzerCw_Database::fetch($rs)) {
			$this->customerCid = $row['customers_cid'];
		}
		
		$this->orderTotals = $order_totals;
		$this->updateOrder($order);
		$this->currencyCode = $this->order['info']['currency'];
		$this->paymentMethod = $paymentMethod;
		$this->updateCustomerStatus();
		
		$this->addTax = $this->customerStatus['customers_status_show_price_tax'] == '0' && $this->customerStatus['customers_status_add_tax_ot'] == '1';
		$this->hasNoTax = $this->customerStatus['customers_status_show_price_tax'] == '0' &&
				 $this->customerStatus['customers_status_add_tax_ot'] == '0';
		$this->updateOrderTotalAmount();
		
		$this->language = new Customweb_Core_Language(UnzerCw_Util::getCurrentLanguageCode());
		
		if (!isset($_SESSION['unzercw_checkout_id'])) {
			$_SESSION['unzercw_checkout_id'] = Customweb_Core_Util_Rand::getUuid();
		}
		
		if (!isset($this->order['billing']['country']['iso_code_2'])) {
			throw new Exception("No valid billing country code provided.");
		}
		
		$this->checkoutId = $_SESSION['unzercw_checkout_id'];
	}

	private function updateOrderTotalAmount(){
		$this->orderAmount = null;
		if (is_array($this->orderTotals)) {
			foreach ($this->orderTotals as $total) {
				if ($total['code'] == 'ot_total') {
					$this->orderAmount = (float) $total['value'];
					break;
				}
			}
		}
		else {
			throw new Exception("Invalid format of the order totals.");
		}
		
		// Sometimes the ot_total value is not set (in early checkout process), hence we try to use the
		// value from the order object.
		if ($this->orderAmount === null) {
			if ($this->addTax) {
				$this->orderAmount = $this->order['info']['total'] + $this->order['info']['tax'];
			}
			else {
				$this->orderAmount = $this->order['info']['total'];
			}
		}
		
		// There is a bug in the ot_total module, which may sum up not correctly (rounding error).
		// In this case we fix this by round up or round down.
		$sumLineItems = $this->getInvoiceItemSum();
		
		$decimalPlaces = Customweb_Util_Currency::getDecimalPlaces($this->currencyCode);
		$diff = round(abs($sumLineItems - $this->orderAmount), $decimalPlaces);
		$precision = 1 / (pow(10, $decimalPlaces));
		if ($diff <= $precision) {
			$this->orderAmount = $sumLineItems;
		}
	}

	private function getInvoiceItemSum(){
		return $this->getLineTotalsWithTax($this->getInvoiceItems());
	}

	private function updateOrder($order){
		$this->order = array();
		$this->order['info'] = $order->info;
		$this->order['delivery'] = $order->delivery;
		$this->order['billing'] = $order->billing;
		$this->order['customer'] = $order->customer;
		$this->order['products'] = $order->products;
	}

	private function updateCustomerStatus(){
		// Get customers info:
		$rs = UnzerCw_Database::prepare(
				'SELECT * FROM ' . TABLE_CUSTOMERS . ', ' . TABLE_CUSTOMERS_STATUS .
						 ' WHERE customers_id = \'%d\' AND customers_status = customers_status_id LIMIT 0,1', array(
							$this->customerId 
						));
		if ($row = UnzerCw_Database::fetch($rs)) {
			$this->customerStatus = $row;
			return $this->customerStatus;
		}
		else {
			throw new Exception("Could not load customer data.");
		}
	}

	public function getCheckoutId(){
		return $this->checkoutId;
	}

	public function getCustomerStatus(){
		return $this->customerStatus;
	}

	public function getCustomerId(){
		if (!empty($this->customerCid)) {
			return $this->customerCid;
		}
		else {
			return $this->customerId;
		}
	}

	public function getCustomerPrimaryKey(){
		return $this->customerId;
	}

	public function isNewCustomer(){
		return 'unknown';
	}

	public function getCustomerRegistrationDate(){
		return null;
	}

	public function getOrderObject(){
		return $this->order;
	}

	public function getOrderAmountInDecimals(){
		return $this->orderAmount;
	}

	public function getCurrencyCode(){
		return $this->currencyCode;
	}

	public function getInvoiceItems(){
		$items = array();
		
		foreach ($this->order['products'] as $product) {
			$sku = $product['name'];
			if (!empty($product['model'])) {
				$sku = $product['model'];
			}
			
			$finalPrice = $product['final_price'];
			
			if ($this->addTax) {
				$finalPrice = $finalPrice * (1 + $product['tax'] / 100);
			}
			elseif ($this->hasNoTax) {
				$product['tax'] = 0;
			}
			
			$item = new Customweb_Payment_Authorization_DefaultInvoiceItem(UnzerCw_Util::decode($sku), 
					UnzerCw_Util::decode($product['name']), $product['tax'], $finalPrice, $product['qty']);
			$items[] = $item;
		}
		
		if (is_array($this->orderTotals)) {
			
			// Apply all discounts, which are applied before tax
			foreach ($this->orderTotals as $total) {
				if ($total['code'] == 'ot_discount' || $total['code'] == 'ot_loyalty_discount') {
					$amount = (float) $total['value'];
					$amount = abs($amount);
					$items = $this->applyCartDiscounts($amount, $items);
				}
			}
			
			$excludedTotals = array(
				'ot_total_netto',
				'ot_subtotal',
				'ot_subtotal_no_tax',
				'ot_tax',
				'ot_total',
				'ot_discount',
				'ot_loyalty_discount' 
			);
			
			foreach ($this->orderTotals as $total) {
				if (!in_array($total['code'], $excludedTotals)) {
					$taxRate = 0;
					
					$amount = (float) $total['value'];
					if ($amount < 0) {
						$type = Customweb_Payment_Authorization_DefaultInvoiceItem::TYPE_DISCOUNT;
					}
					else {
						$type = Customweb_Payment_Authorization_DefaultInvoiceItem::TYPE_FEE;
					}

					// A total which is a coupon is a discount.
					if (isset($total['code']) && $total['code'] == 'ot_coupon') {
						$type = Customweb_Payment_Authorization_DefaultInvoiceItem::TYPE_DISCOUNT;
					}
						
					// We need always the positive value.
					$amount = abs($amount);
					
					if (isset($total['tax_rate'])) {
						$taxRate = $total['tax_rate'];
					}
					
					// Try to get tax code
					if ($total['code'] == 'ot_shipping') {
						$type = Customweb_Payment_Authorization_DefaultInvoiceItem::TYPE_SHIPPING;
					}
					
					if ($this->addTax) {
						$amount = $amount * (1 + $taxRate / 100);
					}
					elseif ($this->hasNoTax) {
						$taxRate = 0;
					}
					
					$items[] = $this->createTotalItem($total, $taxRate, $amount, $type);
				}
			}
		}
		
		return Customweb_Util_Invoice::cleanupLineItems($items, $this->getOrderAmountInDecimals(), $this->getCurrencyCode());
	}

	/**
	 *
	 * @return Customweb_Payment_Authorization_DefaultInvoiceItem
	 */
	protected function createTotalItem(array $total, $taxRate, $amount, $type){
		$sku = substr($total['code'], 3);
		$total['title'] = trim($total['title'], ':; ');
		
		return new Customweb_Payment_Authorization_DefaultInvoiceItem(UnzerCw_Util::decode($sku), 
				UnzerCw_Util::decode($total['title']), $taxRate, $amount, 1, $type);
	}

	protected function applyCartDiscounts($discount, $items){
		// Add discounts: We need to apply the discount direclty on the line items, because we can not
		// show a discount with a tax. The tax may be a mixure of multiple taxes, which leads to a strange tax
		// rate.
		if ($discount > 0) {
			$newItems = array();
			
			$total = $this->getLineTotalsWithTax($items);
			$reductionRate = $discount / $total;
			
			foreach ($items as $item) {
				/* @var $item Customweb_Payment_Authorization_DefaultInvoiceItem */
				$newTotalAmount = $item->getAmountExcludingTax() * (1 - $reductionRate) * (1 + $item->getTaxRate() / 100);
				
				$newItem = new Customweb_Payment_Authorization_DefaultInvoiceItem($item->getSku(), $item->getName(), $item->getTaxRate(), 
						$newTotalAmount, $item->getQuantity());
				$newItems[] = $newItem;
			}
			
			return $newItems;
		}
		else {
			return $items;
		}
	}

	protected function getLineTotalsWithoutTax(array $lines){
		$total = 0;
		
		foreach ($lines as $line) {
			/* @var $line Customweb_Payment_Authorization_DefaultInvoiceItem */
			if ($line->getType() == Customweb_Payment_Authorization_DefaultInvoiceItem::TYPE_DISCOUNT) {
				$total -= $line->getAmountExcludingTax();
			}
			else {
				$total += $line->getAmountExcludingTax();
			}
		}
		
		return $total;
	}

	protected function getLineTotalsWithTax(array $lines){
		$total = 0;
		
		foreach ($lines as $line) {
			/* @var $line Customweb_Payment_Authorization_DefaultInvoiceItem */
			if ($line->getType() == Customweb_Payment_Authorization_DefaultInvoiceItem::TYPE_DISCOUNT) {
				$total -= $line->getAmountIncludingTax();
			}
			else {
				$total += $line->getAmountIncludingTax();
			}
		}
		
		return $total;
	}

	public function getShippingMethod(){
		return UnzerCw_Util::decode($this->order['info']['shipping_method']);
	}

	public function getPaymentMethod(){
		return $this->paymentMethod;
	}

	public function getLanguage(){
		return $this->language;
	}

	public function getCustomerEMailAddress(){
		return UnzerCw_Util::decode($this->order['customer']['email_address']);
	}

	public function getBillingEMailAddress(){
		return $this->getCustomerEMailAddress();
	}

	public function getBillingGender(){
		if ($this->getBillingCompanyName() !== null) {
			return 'company';
		}
		else {
			if (isset($this->order['billing']['gender'])) {
				if ($this->order['billing']['gender'] == 'm') {
					return 'male';
				}
				else {
					return 'female';
				}
			}
			else if ($this->equalsCustomerNameBillingName()) {
				if ($this->order['customer']['gender'] == 'm') {
					return 'male';
				}
				else {
					return 'female';
				}
			}
		}
		return null;
	}

	public function getBillingSalutation(){
		return null;
	}

	public function getBillingFirstName(){
		return UnzerCw_Util::decode($this->order['billing']['firstname']);
	}

	public function getBillingLastName(){
		return UnzerCw_Util::decode($this->order['billing']['lastname']);
	}

	public function getBillingStreet(){
		return UnzerCw_Util::decode($this->order['billing']['street_address']);
	}

	public function getBillingCity(){
		return UnzerCw_Util::decode($this->order['billing']['city']);
	}

	public function getBillingPostCode(){
		return UnzerCw_Util::decode($this->order['billing']['postcode']);
	}

	public function getBillingState(){
		return null;
	}

	public function getBillingCountryIsoCode(){
		return $this->order['billing']['country']['iso_code_2'];
	}

	public function getBillingPhoneNumber(){
		return UnzerCw_Util::decode($this->order['customer']['telephone']);
	}

	public function getBillingMobilePhoneNumber(){
		return null;
	}

	public function getBillingDateOfBirth(){
		if ($this->equalsCustomerNameBillingName()) {
			return UnzerCw_Util::getDateOfBirth($this->customerId);
		}
		else {
			return null;
		}
	}

	public function getBillingCommercialRegisterNumber(){
		return null;
	}

	public function getBillingSalesTaxNumber(){
		return null;
	}

	public function getBillingSocialSecurityNumber(){
		return null;
	}

	public function getBillingCompanyName(){
		if (isset($this->order['billing']['company']) && !empty($this->order['billing']['company'])) {
			return UnzerCw_Util::decode($this->order['billing']['company']);
		}
		else {
			return null;
		}
	}

	public function getShippingEMailAddress(){
		return $this->getCustomerEMailAddress();
	}

	public function getShippingGender(){
		if ($this->getShippingCompanyName() !== null) {
			return 'company';
		}
		else {
			if ($this->equalsCustomerNameShippingName()) {
				if (isset($this->order['delivery']['gender'])) {
					if ($this->order['delivery']['gender'] == 'm') {
						return 'male';
					}
					else {
						return 'female';
					}
				}
				else if ($this->order['customer']['gender'] == 'm') {
					return 'male';
				}
				else {
					return 'female';
				}
			}
		}
		return null;
	}

	public function getShippingSalutation(){
		return null;
	}

	public function getShippingFirstName(){
		return UnzerCw_Util::decode($this->order['delivery']['firstname']);
	}

	public function getShippingLastName(){
		return UnzerCw_Util::decode($this->order['delivery']['lastname']);
	}

	public function getShippingStreet(){
		return UnzerCw_Util::decode($this->order['delivery']['street_address']);
	}

	public function getShippingCity(){
		return UnzerCw_Util::decode($this->order['delivery']['city']);
	}

	public function getShippingPostCode(){
		return UnzerCw_Util::decode($this->order['delivery']['postcode']);
	}

	public function getShippingState(){
		return null;
	}

	public function getShippingCountryIsoCode(){
		return UnzerCw_Util::decode($this->order['delivery']['country']['iso_code_2']);
	}

	public function getShippingPhoneNumber(){
		return UnzerCw_Util::decode($this->order['customer']['telephone']);
	}

	public function getShippingMobilePhoneNumber(){
		return null;
	}

	public function getShippingDateOfBirth(){
		if ($this->equalsCustomerNameShippingName()) {
			return UnzerCw_Util::getDateOfBirth($this->customerId);
		}
		else {
			return null;
		}
	}

	public function getShippingCompanyName(){
		if (isset($this->order['delivery']['company']) && !empty($this->order['delivery']['company'])) {
			return UnzerCw_Util::decode($this->order['delivery']['company']);
		}
		else {
			return null;
		}
	}

	public function getShippingCommercialRegisterNumber(){
		return null;
	}

	public function getShippingSalesTaxNumber(){
		return null;
	}

	public function getShippingSocialSecurityNumber(){
		return null;
	}

	public function getOrderParameters(){
		return array();
	}

	protected function equalsCustomerNameBillingName(){
		return $this->getBillingFirstName() == UnzerCw_Util::decode($this->order['customer']['firstname']) &&
				 $this->getBillingLastName() == UnzerCw_Util::decode($this->order['customer']['lastname']);
	}

	protected function equalsCustomerNameShippingName(){
		return $this->getShippingFirstName() == UnzerCw_Util::decode($this->order['customer']['firstname']) &&
				 $this->getShippingLastName() == UnzerCw_Util::decode($this->order['customer']['lastname']);
	}
}