<?php 

/* @var $transaction UnzerCw_Entity_Transaction */
/* @var $relatedTransactionsrelatedTransactions UnzerCw_Entity_Transaction[] */
/* @var $this UnzerCw_Controller_TransactionManagement */

$transactionObject = $transaction->getTransactionObject();

?>
<?php if ($this->isRefundPossible($transaction) || $this->isCapturePossible($transaction) || $this->isCancelable($transaction)): ?>
	<br />
	<div class="text-left">
	<?php if ($this->isCapturePossible($transaction)): ?>
		<a href="<?php echo $this->getActionUrl('capture', array('transaction_id' => $transaction->getTransactionId())); ?>" class="btn btn-default"><?php echo unzercw_translate('Capture'); ?></a>
	<?php endif;?>
	<?php if ($this->isRefundPossible($transaction)): ?>
		<a href="<?php echo $this->getActionUrl('refund', array('transaction_id' => $transaction->getTransactionId())); ?>" class="btn btn-default"><?php echo unzercw_translate('Refund'); ?></a>
	<?php endif;?>
	<?php if ($this->isCancelable($transaction)): ?>
		<a href="<?php echo $this->getActionUrl('cancel', array('transaction_id' => $transaction->getTransactionId())); ?>" class="btn btn-default"><?php echo unzercw_translate('Cancel'); ?></a>
	<?php endif;?>
	</div>
<?php endif; ?>


<h2><?php echo unzercw_translate('Transaction Information of Transaction !number', array('!number' => $transaction->getTransactionExternalId())); ?></h2>



<table class="table table-striped table-condensed table-hover table-bordered">

	<tr>
		<th class="col-lg-3"><?php echo unzercw_translate('Transaction ID') ?></th>
		<td><?php echo $transaction->getTransactionId(); ?></td>
	</tr>
	<tr>
		<th class="col-lg-3"><?php echo unzercw_translate('Transaction Number') ?></th>
		<td><?php echo $transaction->getTransactionExternalId(); ?></td>
	</tr>
	<tr>
		<th class="col-lg-3"><?php echo unzercw_translate('Authorization Status') ?></th>
		<td><?php echo $transaction->getAuthorizationStatusName(); ?></td>
	</tr>
		<tr>
		<th class="col-lg-3"><?php echo unzercw_translate('Order ID') ?></th>
		<td><?php echo $transaction->getOrderId(); ?></td>
	</tr>
	<tr>
		<th class="col-lg-3"><?php echo unzercw_translate('Created On') ?></th>
		<td><?php echo $transaction->getCreatedOn()->format(Customweb_Core_Util_System::getDefaultDateTimeFormat()); ?></td>
	</tr>
	<tr>
		<th class="col-lg-3"><?php echo unzercw_translate('Updated On') ?></th>
		<td><?php echo $transaction->getUpdatedOn()->format(Customweb_Core_Util_System::getDefaultDateTimeFormat()); ?></td>
	</tr>
	<tr>
		<th class="col-lg-3"><?php echo unzercw_translate('Customer ID') ?></th>
		<td><?php echo Customweb_Core_Util_Xml::escape($transaction->getCustomerId()); ?></td>
	</tr>
	<tr>
		<th class="col-lg-3"><?php echo unzercw_translate('Payment ID') ?></th>
		<td><?php echo $transaction->getPaymentId(); ?></td>
	</tr>
	<tr>
		<th class="col-lg-3"><?php echo unzercw_translate('Payment Class') ?></th>
		<td><?php echo $transaction->getPaymentClass(); ?></td>
	</tr>
	<?php if (is_object($transaction->getTransactionObject())):?>
		<?php foreach ($transactionObject->getTransactionLabels() as $label): ?>	
			<tr>
				<th><?php echo $label['label'];?> 
					<?php if (isset($label['description'])): ?>
					<i data-toggle="popover" data-trigger="hover" data-placement="bottom"
					title="" data-content="<?php echo $label['description']; ?>"
					class="glyphicon glyphicon-question-sign" data-original-title="<?php echo $label['label']; ?>"></i>
					<?php endif; ?>
				</th>
				<td>
					<?php echo Customweb_Core_Util_Xml::escape($label['value']);?>
				</td>
			</tr>
		<?php endforeach;?>
		
	<?php endif;?>
	<?php if (is_object($transactionObject) && $transactionObject->isAuthorized() && $transactionObject->getPaymentInformation() != null):?>
		<tr>
			<th class="col-lg-3"><?php echo unzercw_translate('Payment Information') ?></th>
			<td><?php echo $transactionObject->getPaymentInformation(); ?></td>
		</tr>
	<?php endif;?>
</table>
<br />

<?php if (is_object($transactionObject) && count($transactionObject->getCaptures()) > 0): ?>
<h3><?php echo unzercw_translate('Captures for this transaction'); ?></h3>
<table class="table table-striped table-condensed table-hover table-bordered">
	<thead>
		<tr>
			<th><?php echo unzercw_translate('Date'); ?></th>
			<th><?php echo unzercw_translate('Amount'); ?></th>
			<th><?php echo unzercw_translate('Status'); ?></th>
			<th> </th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($transactionObject->getCaptures() as $capture):?>
		<tr>
			<td><?php echo $capture->getCaptureDate()->format(Customweb_Core_Util_System::getDefaultDateTimeFormat()); ?></td>
			<td><?php echo $capture->getAmount(); ?></td>
			<td><?php echo $capture->getStatus(); ?></td>
			<td><a data-toggle="modal" href="#captureModal<?php echo $capture->getCaptureId(); ?>" class="btn btn-default btn-xs" ><?php echo unzercw_translate('Info'); ?></a>

				<div class="modal fade" id="captureModal<?php echo $capture->getCaptureId(); ?>" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
					<div class="modal-dialog">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
								<h4 class="modal-title">
									<?php echo unzercw_translate('Capture Info'); ?>
								</h4>
							</div>
							<div class="modal-body">
								<?php echo $this->formatLabels($capture->getCaptureLabels()); ?>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							</div>
						</div>
					</div>
				</div>
			</td>
		</tr>
		<?php endforeach;?>
	</tbody>
</table>
<br />
<?php endif;?>


<?php if (is_object($transactionObject) && count($transactionObject->getRefunds()) > 0): ?>
<h3><?php echo unzercw_translate('Refunds for this transaction'); ?></h3>
<table class="table table-striped table-condensed table-hover table-bordered">
	<thead>
		<tr>
			<th><?php echo unzercw_translate('Date'); ?></th>
			<th><?php echo unzercw_translate('Amount'); ?></th>
			<th><?php echo unzercw_translate('Status'); ?></th>
			<th> </th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($transactionObject->getRefunds() as $refund):?>
		<tr>
			<td><?php echo $refund->getRefundedDate()->format(Customweb_Core_Util_System::getDefaultDateTimeFormat()); ?></td>
			<td><?php echo $refund->getAmount(); ?></td>
			<td><?php echo $refund->getStatus(); ?></td>
			<td><a data-toggle="modal" href="#captureModal<?php echo $refund->getRefundId(); ?>" class="btn btn-default btn-xs" ><?php echo unzercw_translate('Info'); ?></a>

				<div class="modal fade" id="captureModal<?php echo $refund->getRefundId(); ?>" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
					<div class="modal-dialog">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
								<h4 class="modal-title">
									<?php echo unzercw_translate('Refund Info'); ?>
								</h4>
							</div>
							<div class="modal-body">
								<?php echo $this->formatLabels($refund->getRefundLabels()); ?>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							</div>
						</div>
					</div>
				</div>
			</td>
		</tr>
		<?php endforeach;?>
	</tbody>
</table>
<br />
<?php endif;?>


<?php if (is_object($transactionObject) && count($transactionObject->getHistoryItems()) > 0): ?>
<h3><?php echo unzercw_translate('Transactions History'); ?></h3>
<table class="table table-striped table-condensed table-hover table-bordered">
	<thead>
		<tr>
			<th><?php echo unzercw_translate('Date'); ?></th>
			<th><?php echo unzercw_translate('Action'); ?></th>
			<th><?php echo unzercw_translate('Message'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($transactionObject->getHistoryItems() as $item):?>
		<tr>
			<td><?php echo $item->getCreationDate()->format(Customweb_Core_Util_System::getDefaultDateTimeFormat()); ?></td>
			<td><?php echo $item->getActionPerformed(); ?></td>
			<td><?php echo $item->getMessage(); ?></td>
		</tr>
		<?php endforeach;?>
	</tbody>
</table>
<br />
<?php endif;?>


<?php if (is_object($transactionObject)): ?>
<h3><?php echo unzercw_translate('Customer Data'); ?></h3>
<table class="table table-striped table-condensed table-hover table-bordered">
	<?php $context = $transactionObject->getTransactionContext()->getOrderContext(); ?>
	<tr>
		<th class="col-lg-3"><?php echo unzercw_translate('Customer ID') ?></th>
		<td><?php echo Customweb_Core_Util_Xml::escape($context->getCustomerId()); ?></td>
	</tr>
	<tr>
		<th class="col-lg-3"><?php echo unzercw_translate('Billing Address') ?></th>
		<td>
			<?php echo Customweb_Core_Util_Xml::escape($context->getBillingFirstName() . ' ' . $context->getBillingLastName()); ?><br />
			<?php if ($context->getBillingCompanyName() !== null): ?>
				<?php echo Customweb_Core_Util_Xml::escape($context->getBillingCompanyName()); ?><br />
			<?php endif;?>
			<?php echo Customweb_Core_Util_Xml::escape($context->getBillingStreet()); ?><br />
			<?php echo Customweb_Core_Util_Xml::escape(strtoupper($context->getBillingCountryIsoCode()) . '-' . $context->getBillingPostCode() . ' ' . $context->getBillingCity()); ?><br />
			<?php if ($context->getBillingDateOfBirth() !== null) :?>
				<?php echo unzercw_translate('Birthday') . ': ' . $context->getBillingDateOfBirth()->format("Y-m-d"); ?><br />
			<?php endif;?>
			<?php if ($context->getBillingPhoneNumber() !== null) :?>
				<?php echo unzercw_translate('Phone') . ': ' . Customweb_Core_Util_Xml::escape($context->getBillingPhoneNumber()); ?><br />
			<?php endif;?>
		</td>
	</tr>
	<tr>
		<th class="col-lg-3"><?php echo unzercw_translate('Shipping Address') ?></th>
		<td>
			<?php echo Customweb_Core_Util_Xml::escape($context->getShippingFirstName() . ' ' . $context->getShippingLastName()); ?><br />
			<?php if ($context->getShippingCompanyName() !== null): ?>
				<?php echo Customweb_Core_Util_Xml::escape($context->getShippingCompanyName()); ?><br />
			<?php endif;?>
			<?php echo Customweb_Core_Util_Xml::escape($context->getShippingStreet()); ?><br />
			<?php echo Customweb_Core_Util_Xml::escape(strtoupper($context->getShippingCountryIsoCode()) . '-' . $context->getShippingPostCode() . ' ' . $context->getShippingCity()); ?><br />
			<?php if ($context->getShippingDateOfBirth() !== null) :?>
				<?php echo unzercw_translate('Birthday') . ': ' . $context->getShippingDateOfBirth()->format("Y-m-d"); ?><br />
			<?php endif;?>
			<?php if ($context->getShippingPhoneNumber() !== null) :?>
				<?php echo unzercw_translate('Phone') . ': ' . Customweb_Core_Util_Xml::escape($context->getShippingPhoneNumber()); ?><br />
			<?php endif;?>
		</td>
	</tr>
</table>
<br />
<h3><?php echo unzercw_translate('Products'); ?></h3>
<table class="table table-striped table-condensed table-hover table-bordered">
	<thead>
		<tr>
			<th><?php echo unzercw_translate('Name'); ?></th>
			<th><?php echo unzercw_translate('SKU'); ?></th>
			<th><?php echo unzercw_translate('Quantity'); ?></th>
			<th><?php echo unzercw_translate('Type'); ?></th>
			<th><?php echo unzercw_translate('Tax Rate'); ?></th>
			<th><?php echo unzercw_translate('Amount (excl. VAT)'); ?></th>
			<th><?php echo unzercw_translate('Amount (inkl. VAT)'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($transactionObject->getTransactionContext()->getOrderContext()->getInvoiceItems() as $invoiceItem):?>
		<tr>
			<td><?php echo $invoiceItem->getName() ?></td>
			<td><?php echo $invoiceItem->getSku(); ?></td>
			<td><?php echo $invoiceItem->getQuantity(); ?></td>
			<td><?php echo $invoiceItem->getType(); ?></td>
			<td><?php echo $invoiceItem->getTaxRate(); ?>%</td>
			<td><?php echo Customweb_Util_Currency::roundAmount($invoiceItem->getAmountExcludingTax(), $context->getCurrencyCode()) . ' ' . $context->getCurrencyCode(); ?></td>
			<td><?php echo Customweb_Util_Currency::roundAmount($invoiceItem->getAmountIncludingTax(), $context->getCurrencyCode()) . ' ' . $context->getCurrencyCode(); ?></td>
		</tr>
		<?php endforeach;?>
	</tbody>
</table>
<br />
<?php endif;?>


<?php if (count($relatedTransactions) > 0): ?>
<h3><?php echo unzercw_translate('Transactions related to the same order'); ?></h3>
<table class="table table-striped table-condensed table-hover table-bordered">
	
	<tr>
		<th><?php echo unzercw_translate('Transaction Number'); ?></th>
		<th><?php echo unzercw_translate('Is Authorized'); ?></th>
		<th><?php echo unzercw_translate('Authorization Amount'); ?></th>
		<th></th>
	</tr>
	
	<?php foreach ($relatedTransactions as $transaction): ?>
		<?php if (is_object($transaction->getTransactionObject())) : ?>
		<tr>
			<td><?php echo $transaction->getTransactionExternalId(); ?></td>
			<td><?php echo $transaction->getTransactionObject()->isAuthorized() ? unzercw_translate('yes') : unzercw_translate('no'); ?></td>
			<td><?php echo $transaction->getTransactionObject()->getAuthorizationAmount(); ?></td>
			<td><a class="btn btn-success btn-xs" href="<?php echo $this->getActionUrl('edit', array('transaction_id' => $transaction->getTransactionId())); ?>"><?php echo unzercw_translate('Edit'); ?></a>
		</tr>
		<?php endif; ?>
	<?php endforeach;?>
	
	
</table>
<br />
<?php endif; ?>