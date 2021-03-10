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

interface UnzerCw_Adapter_IAdapter {
	
	
	/**
	 * @return string
	 */
	public function getPaymentAdapterInterfaceName();
	
	/**
	 * @return Customweb_Payment_Authorization_IAdapter
	*/
	public function getInterfaceAdapter();
	
	public function setInterfaceAdapter(Customweb_Payment_Authorization_IAdapter $adapter);
	
	
	/**
	 * This method returns the form action url for the form on the checkout confirmation page.
	 * If the method return NULL, then no change is done on the current form action URL.
	 * 
	 * @param UnzerCw_OrderContext $context
	 * @param UnzerCw_PaymentMethod $paymentMethod
	 * @return string URL
	 */
	public function getCheckoutFormActionUrl(UnzerCw_OrderContext $context, UnzerCw_PaymentMethod $paymentMethod);
	
	/**
	 * This method returns the form content for the checkout page.
	 * 
	 * @param UnzerCw_OrderContext $context
	 * @param UnzerCw_PaymentMethod $paymentMethod
	 * @return String Form content
	 */
	public function getCheckoutForm(UnzerCw_OrderContext $context, UnzerCw_PaymentMethod $paymentMethod);
	
	/**
	 * This method is called, when ever an order should be created. In case the order should not be created, this method must stop
	 * the process.
	 * 
	 * @param UnzerCw_OrderContext $context
	 * @param UnzerCw_PaymentMethod $paymentMethod
	 * @return void
	 */
	public function processOrder(UnzerCw_OrderContext $context, UnzerCw_PaymentMethod $paymentMethod);
	
	/**
	 * This method is called when a pending order should be processed. In case this method should be called 
	 * again, the temp order in the session must be set and the method must stop the process.
	 * 
	 * @param UnzerCw_OrderContext $context
	 * @param UnzerCw_PaymentMethod $paymentMethod
	 * @return void
	 */
	public function processPendingOrder(UnzerCw_OrderContext $context, UnzerCw_PaymentMethod $paymentMethod);
	
	/**
	 * This method is called when a order is successfully created. This method should not stop the process and it should never throw an 
	 * exception.
	 * 
	 * @param UnzerCw_OrderContext $context
	 * @param UnzerCw_PaymentMethod $paymentMethod
	 * @return void
	 */
	public function afterOrderCreation(UnzerCw_OrderContext $context, UnzerCw_PaymentMethod $paymentMethod);
	
	
	/**
	 * This method is called, when the user access the shop payment page. The method can return any content (forms etc.) or it can kill 
	 * the process and redirect the customer to a different page.
	 * 
	 * @param UnzerCw_Entity_Transaction $dbTransaction
	 * @return array The form target and the form fields (form content)
	 */
	public function getPaymentPageContent(UnzerCw_Entity_Transaction $dbTransaction, $aliasTransaction, $failedTransaction);
	
	
}