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

require_once 'Customweb/Grid/Column.php';
require_once 'Customweb/Payment/BackendOperation/Adapter/Service/ICapture.php';
require_once 'Customweb/Payment/Authorization/EditableInvoiceItem.php';
require_once 'Customweb/Payment/Authorization/IInvoiceItem.php';
require_once 'Customweb/Payment/BackendOperation/Adapter/Service/IRefund.php';
require_once 'Customweb/Grid/Loader.php';

require_once 'UnzerCw/Grid/Adapter.php';
require_once 'UnzerCw/Grid/TransactionActionColumn.php';
require_once 'UnzerCw/Grid/AuthorizationAmountColumn.php';
require_once 'UnzerCw/Util.php';
require_once 'UnzerCw/Grid/Renderer.php';
require_once 'UnzerCw/AbstractController.php';
require_once 'UnzerCw/Grid/TransactionStatusColumn.php';
require_once 'UnzerCw/Entity/Util.php';

require_once 'Customweb/Core/Util/System.php';
require_once 'Customweb/Core/Util/Xml.php';

class UnzerCw_Controller_TransactionManagement extends UnzerCw_AbstractController {

	public function indexAction(){
		$this->listAction();
	}

	public function listAction(){
		$adapter = new UnzerCw_Grid_Adapter('SELECT * FROM unzercw_transactions WHERE ${WHERE} ${ORDER_BY} ${LIMIT}');
		
		$loader = new Customweb_Grid_Loader();
		$loader->setDataAdapter($adapter);
		$loader->setRequestData($_GET);
		$loader->
		// 			->addColumn(new Customweb_Grid_Column('transactionId', '#'))
		addColumn(new Customweb_Grid_Column('transactionExternalId', 'Transaction Number'))->addColumn(
				new Customweb_Grid_Column('orderId', 'Order ID'))->addColumn(new Customweb_Grid_Column('paymentMachineName', 'Payment Method'))->addColumn(
				new UnzerCw_Grid_AuthorizationAmountColumn('authorizationAmount', 'Amount'))->addColumn(
				new UnzerCw_Grid_TransactionStatusColumn('status', 'Authorization Status'))->addColumn(
				new Customweb_Grid_Column('createdOn', 'Created On', 'DESC'))->addColumn(
				new UnzerCw_Grid_TransactionActionColumn('actions'));
		
		$renderer = new UnzerCw_Grid_Renderer($loader, $this->getActionUrl('list'));
		$renderer->setGridId('transaction-grid');
		$this->appendViewData('grid', $renderer->render());
		
		header('Content-Type: text/html; charset=UTF-8');
		echo $this->render('list');
	}

	public function editAction(){
		if (!isset($_GET['transaction_id'])) {
			throw new Exception('No transaction id given.');
		}
		
		$transaction = UnzerCw_Entity_Util::findTransactionByTransactionId($_GET['transaction_id']);
		
		$relatedTransactions = array();
		if ($transaction->getOrderId() > 0) {
			$relatedTransactions = UnzerCw_Entity_Util::findTransactionsEntityByOrderId($transaction->getOrderId());
		}
		$this->appendViewData('transaction', $transaction);
		$this->appendViewData('relatedTransactions', $relatedTransactions);
		
		header('Content-Type: text/html; charset=UTF-8');
		echo $this->render('edit');
	}
	
	
	public function cancelAction(){
		if (!isset($_GET['transaction_id'])) {
			throw new Exception('No transaction id given.');
		}
		$transaction = UnzerCw_Entity_Util::findTransactionByTransactionId($_GET['transaction_id']);
		$transactionObject = $transaction->getTransactionObject();
		
		if (!is_object($transactionObject)) {
			throw new Exception("The transaction object could not be loaded.");
		}
		
		$adapter = UnzerCw_Util::createContainer()->getBean('Customweb_Payment_BackendOperation_Adapter_Service_ICancel');
		
		try {
			$adapter->cancel($transactionObject);
			UnzerCw_Entity_Util::persist($transaction);
			$paymentModule = UnzerCw_Util::getPaymentMehtodInstance($transaction->getPaymentClass());
			$cancelledState = $paymentModule->getPaymentMethodConfigurationValue('status_cancelled');
			if ($cancelledState != 'none') {
				UnzerCw_Util::setOrderStatus($cancelledState, $transaction->getOrderId());
			}
			$this->setMessage(unzercw_translate("Transaction successfully cancelled."), 'success');
		}
		catch (Exception $e) {
			$this->setMessage($e->getMessage(), 'error');
			UnzerCw_Entity_Util::persist($transaction);
		}
		$this->redirectAction('edit', array(
			'transaction_id' => $transaction->getTransactionId() 
		));
	}
	
	
	
	private function getLineItemAmount(array $parameters, $index) {
		if(isset($parameters['price_including'][$index])) {
			$amount = $parameters['price_including'][$index];
			$amount = str_replace(',', '.', $amount);
			return floatval($amount);
		}
		return 0;
	}
	
	
	public function refundAction(){
		$transaction = $this->loadCurrentTransaction();
		if (isset($_POST['quantity'])) {
			$this->processRefund($transaction, $_POST);
		}
		
		$this->appendViewData('transaction', $transaction);
		echo $this->render('refund');
	}

	private function processRefund(UnzerCw_Entity_Transaction $transaction, $parameters = array()){
		if (isset($parameters['quantity'])) {
			$refundLineItems = array();
			$lineItems = $transaction->getTransactionObject()->getNonRefundedLineItems();
			foreach ($parameters['quantity'] as $index => $quantity) {
				$amount = $this->getLineItemAmount($parameters, $index);
				
				if ($amount) {
					$originalItem = $lineItems[$index];
					if ($originalItem->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT) {
						$priceModifier = -1;
					}
					else {
						$priceModifier = 1;
					}
					$refundLineItems[$index] = new Customweb_Payment_Authorization_EditableInvoiceItem($originalItem);
					$refundLineItems[$index]->setAmountIncludingTax($priceModifier * $amount);
					$refundLineItems[$index]->setQuantity($quantity);
				}
			}
		}
		else {
			$refundLineItems = $transaction->getTransactionObject()->getNonRefundedLineItems();
		}
		
		if (count($refundLineItems) > 0) {
			$adapter = UnzerCw_Util::createContainer()->getBean('Customweb_Payment_BackendOperation_Adapter_Service_IRefund');
			if (!($adapter instanceof Customweb_Payment_BackendOperation_Adapter_Service_IRefund)) {
				throw new Exception("No adapter with interface 'Customweb_Payment_BackendOperation_Adapter_Service_IRefund' provided.");
			}
			
			$close = false;
			if (isset($parameters['close']) && $parameters['close'] == 'on') {
				$close = true;
			}
			try {
				$adapter->partialRefund($transaction->getTransactionObject(), $refundLineItems, $close);
				UnzerCw_Util::getEntityManager()->persist($transaction);
				$this->setMessage(unzercw_translate("Refund successfully created."), 'success');
			}
			catch (Exception $e) {
				UnzerCw_Util::getEntityManager()->persist($transaction);
				$this->setMessage($e->getMessage(), 'error');
			}
		}
		else {
			$this->setMessage(unzercw_translate("No item was refunded."), 'error');
		}
	}
	
	

	
	public function captureAction($target = null){
		$transaction = $this->loadCurrentTransaction();
		if (isset($_POST['quantity'])) {
			$this->processCapture($transaction, $_POST);
		}
		
		$this->appendViewData('transaction', $transaction);
		echo $this->render('capture');
	}

	private function processCapture(UnzerCw_Entity_Transaction $transaction, $parameters = array()){
		if (isset($parameters['quantity'])) {
			$captureLineItems = array();
			$lineItems = $transaction->getTransactionObject()->getUncapturedLineItems();
			foreach ($parameters['quantity'] as $index => $quantity) {
				$amount = $this->getLineItemAmount($parameters, $index);
				if ($amount) {
					$originalItem = $lineItems[$index];
					if ($originalItem->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT) {
						$priceModifier = -1;
					}
					else {
						$priceModifier = 1;
					}
					$captureLineItems[$index] = new Customweb_Payment_Authorization_EditableInvoiceItem($originalItem);
					$captureLineItems[$index]->setAmountIncludingTax($priceModifier * $amount);
					$captureLineItems[$index]->setQuantity($quantity);
				}
			}
		}
		else {
			$captureLineItems = $transaction->getTransactionObject()->getUncapturedLineItems();
		}
		
		if (count($captureLineItems) > 0) {
			$adapter = UnzerCw_Util::createContainer()->getBean('Customweb_Payment_BackendOperation_Adapter_Service_ICapture');
			if (!($adapter instanceof Customweb_Payment_BackendOperation_Adapter_Service_ICapture)) {
				throw new Exception("No adapter with interface 'Customweb_Payment_BackendOperation_Adapter_Service_ICapture' provided.");
			}
			
			$close = false;
			if (isset($parameters['close']) && $parameters['close'] == 'on') {
				$close = true;
			}
			try {
				$adapter->partialCapture($transaction->getTransactionObject(), $captureLineItems, $close);
				UnzerCw_Util::getEntityManager()->persist($transaction);
				$this->setMessage(unzercw_translate("Capture successfully created."), 'success');
			}
			catch (Exception $e) {
				UnzerCw_Util::getEntityManager()->persist($transaction);
				$this->setMessage($e->getMessage(), 'error');
			}
		}
		else {
			$this->setMessage(unzercw_translate("No item was captured."), 'error');
		}
	}

	public function listCaptureAction(){
		$transaction = $this->loadCurrentTransaction();
		$this->processCapture($transaction);
		$this->redirectAction('list');
	}
	
	public function isCapturePossible(UnzerCw_Entity_Transaction $transaction){
		
		if (is_object($transaction->getTransactionObject())) {
			return $transaction->getTransactionObject()->isCapturePossible();
		}
		
		

		return false;
	}

	public function isPartialCapturePossible(UnzerCw_Entity_Transaction $transaction){
		
		if (is_object($transaction->getTransactionObject())) {
			return $transaction->getTransactionObject()->isPartialCapturePossible();
		}
		
		

		return false;
	}

	public function isCaptureClosable(UnzerCw_Entity_Transaction $transaction){
		
		if (is_object($transaction->getTransactionObject())) {
			return $transaction->getTransactionObject()->isCaptureClosable();
		}
		
		

		return false;
	}

	public function isRefundPossible(UnzerCw_Entity_Transaction $transaction){
		
		if (is_object($transaction->getTransactionObject())) {
			return $transaction->getTransactionObject()->isRefundPossible();
		}
		
		

		return false;
	}

	public function isPartialRefundPossible(UnzerCw_Entity_Transaction $transaction){
		
		if (is_object($transaction->getTransactionObject())) {
			return $transaction->getTransactionObject()->isPartialRefundPossible();
		}
		
		

		return false;
	}

	public function isRefundClosable(UnzerCw_Entity_Transaction $transaction){
		
		if (is_object($transaction->getTransactionObject())) {
			return $transaction->getTransactionObject()->isRefundClosable();
		}
		
		

		return false;
	}

	public function isCancelable(UnzerCw_Entity_Transaction $transaction){
		
		if (is_object($transaction->getTransactionObject())) {
			return $transaction->getTransactionObject()->isCancelPossible();
		}
		
		

		return false;
	}

	public function formatLabels($labels){
		$html .= '<table class="table table-striped table-condensed table-hover table-bordered">';
		
		foreach ($labels as $label) {
			$html .= '<tr><th>' . $label['label'];
			
			if (isset($label['description'])) {
				$html .= ' <i data-toggle="popover" data-trigger="hover" data-placement="bottom"
					title="' . $label['label'] . '" data-content="' . $label['description'] . '"
					class="glyphicon glyphicon-question-sign" data-original-title="' . $label['label'] . '"></i>';
			}
			$html .= '</th><td>' . $label['value'] . '</td></tr>';
		}
		
		$html .= '</table>';
		return $html;
	}

	/**
	 *
	 * @throws Exception
	 * @return UnzerCw_Entity_Transaction
	 */
	protected function loadCurrentTransaction(){
		if (!isset($_REQUEST['transaction_id'])) {
			throw new Exception('No transaction id given.');
		}
		
		$transaction = UnzerCw_Entity_Util::findTransactionByTransactionId($_REQUEST['transaction_id']);
		$transactionObject = $transaction->getTransactionObject();
		
		if (!is_object($transactionObject)) {
			throw new Exception("The transaction object could not be loaded.");
		}
		
		return $transaction;
	}
}