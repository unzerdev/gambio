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

require_once 'Customweb/Payment/Authorization/DefaultInvoiceItem.php';
require_once 'Customweb/Payment/ExternalCheckout/IContext.php';
require_once 'Customweb/Payment/ExternalCheckout/AbstractContext.php';

require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/Entity/ExternalCheckoutContext.php';


/**
 * 
 * @Entity(tableName = 'unzercw_external_checkout_contexts')
 *
 */
class UnzerCw_Entity_ExternalCheckoutContext extends Customweb_Payment_ExternalCheckout_AbstractContext {
	
	private $shippingMethodCode;
	
	/**
	 * Updates this context object with the cart object.
	 * 
	 * <p>
	 * This method can be called when the context is already stored in the database.
	 * 
	 * @param Cart $cart
	 * @return UnzerCw_Entity_ExternalCheckoutContext
	 */
	public function updateFromCart(shoppingCart $cart) {
		
		$id = $this->getContextId();
		if (empty($id)) {
			throw new Exception("Before the context can be updated with cart, the context must be stored in the database.");
		}
		
		if (empty($_SESSION['currency'])) {
			throw new Exception("No currency code defined in the session.");
		}
		
		$this->setLanguageCode(UnzerCw_Util::getCurrentLanguageCode());
		$this->setCurrencyCode($_SESSION['currency']);
		$this->setCartUrl(UnzerCw_Util::getFrontendUrl('shopping_cart.php', array('unzercw-context-id' => $this->getContextId())));
		$this->setDefaultCheckoutUrl(UnzerCw_Util::getFrontendUrl('checkout_shipping.php'));
		
		// Make sure we have the most current data in the cart.
		$cart->calculate();
		
		$addTax = $_SESSION['customers_status']['customers_status_show_price_tax'] == '0' && $_SESSION['customers_status']['customers_status_add_tax_ot'] == '1';
		$hasNoTax = $_SESSION['customers_status']['customers_status_show_price_tax'] == '0' &&
		
		$items = array();
		foreach($cart->get_products() as $product) {
			$sku = $product['name'];
			if (!empty($product['model'])) {
				$sku = $product['model'];
			}
				
			$finalPrice = $product['final_price'];
			
			$product['tax'] = xtc_get_tax_rate($product['tax_class_id']);
			
			if ($addTax) {
				$finalPrice = $finalPrice * (1 + $product['tax'] / 100);
			}
			elseif ($hasNoTax) {
				$product['tax'] = 0;
			}
				
			$item = new Customweb_Payment_Authorization_DefaultInvoiceItem(
				UnzerCw_Util::decode($sku),
				UnzerCw_Util::decode($product['name']), 
				$product['tax'], 
				$finalPrice, 
				$product['quantity']
			);
			$items[] = $item;
		}
		if(isset($_SESSION['shipping']) && is_array($_SESSION['shipping'])){
			$item = new Customweb_Payment_Authorization_DefaultInvoiceItem(
					$_SESSION['shipping']['id'],
					$_SESSION['shipping']['title'],
					0,
					$_SESSION['shipping']['cost'],
					1
			);
			$items[]=$item;
		}
		
		if ($items !== null) {
			$this->setInvoiceItems($items);
		}
		
		return $this;
	}
	
	
	protected function loadPaymentMethodByMachineName($machineName) {
		return UnzerCw_Util::getPaymentMehtodInstance('unzercw_' . $machineName);
	}
	
	/**
	 * @param int $id
	 * @param string $loadFromCache
	 * @return UnzerCw_Entity_ExternalCheckoutContext
	 */
	public static function getContextById($id, $loadFromCache = true) {
		return UnzerCw_Util::getEntityManager()->fetch('UnzerCw_Entity_ExternalCheckoutContext', $id);
	}

	/**
	 *
	 * @param string $cartId
	 * @param boolean $loadFromCache
	 * @return UnzerCw_Entity_ExternalCheckoutContext
	 */
	public static function getReusableContextFromSession($loadFromCache = true) {
		if (isset($_SESSION['unzercw-context-id'])) {
			try {
				$context = UnzerCw_Util::getEntityManager()->fetch('UnzerCw_Entity_ExternalCheckoutContext', $_SESSION['unzercw-context-id']);
				if ($context instanceof UnzerCw_Entity_ExternalCheckoutContext && $context->getState() == Customweb_Payment_ExternalCheckout_IContext::STATE_PENDING) {
					return $context;
				}
			}
			catch(Customweb_Database_Entity_Exception_EntityNotFoundException $e) {
			}
		}
		return null;
	}

	/**
	 * @Column(type = 'varchar')
	 */
	public function getShippingMethodCode(){
		return $this->shippingMethodCode;
	}

	public function setShippingMethodCode($shippingMethodCode){
		$this->shippingMethodCode = $shippingMethodCode;
		return $this;
	}
	
	/**
	 * @return UnzerCw_PaymentMethod
	 */
	public function getPaymentMethod() {
		return parent::getPaymentMethod();
	}
	
}
