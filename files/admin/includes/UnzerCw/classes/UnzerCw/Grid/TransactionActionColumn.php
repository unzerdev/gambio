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

require_once 'Customweb/Grid/Column.php';

require_once 'UnzerCw/AbstractController.php';
require_once 'UnzerCw/Entity/Util.php';


class UnzerCw_Grid_TransactionActionColumn extends Customweb_Grid_Column {

	public function isFilterable() {
		return false;
	}
	
	public function isSortable() {
		return false;
	}
	
	public function getContent($rowData) {
		
		$content = '<a href="' . 
		UnzerCw_AbstractController::getControllerUrl('transactionmanagement', 'edit', array('transaction_id' => $rowData['transactionId'])) .
		'" title="' . unzercw_translate('Edit') . '" class="btn btn-success btn-xs">' . unzercw_translate('Edit') . '</i></a>';
		
		
		
		$transaction = UnzerCw_Entity_Util::findTransactionByTransactionId($rowData['transactionId'], true);
		if (is_object($transaction->getTransactionObject()) && $transaction->getTransactionObject()->isCapturePossible()) {
			$params = $_GET;
			$params['transaction_id'] = $rowData['transactionId'];
			$content .= '<a href="' .
				UnzerCw_AbstractController::getControllerUrl('transactionmanagement', 'listCapture', $params) . '"
				title="' . unzercw_translate('Capture') . '" class="btn btn-success btn-xs">' . unzercw_translate('Capture') . '</i></a>';
		}
		
		
		return $content;
	}
	
}