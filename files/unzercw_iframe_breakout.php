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
// It can cause issues when the GZIP output is activated. The LayoutRenderer may cause issues
// since the output in the ob stream is gzip.
$gzip_off = true;

$GLOBALS['UnzerCwBackupRequestData'] = array(
	'request' => $_REQUEST,
	'post' => $_POST,
	'get' => $_GET,
);
require_once 'includes/application_top.php';
$baseAdminPath = defined('DIR_ADMIN') ? DIR_ADMIN : 'admin/';
require_once dirname(__FILE__) . '/' . $baseAdminPath . 'includes/UnzerCw/init.php';
require_once 'Customweb/Util/Url.php';

require_once 'UnzerCw/Entity/Util.php';


if (!isset($_REQUEST['cw_transaction_id'])) {
	die("No transaction_id provided.");
}

$dbTransaction = UnzerCw_Entity_Util::findTransactionByTransactionId(intval($_REQUEST['cw_transaction_id']));

$redirectionUrl = '';
if ($dbTransaction->getTransactionObject()->isAuthorizationFailed()) {
	$redirectionUrl = Customweb_Util_Url::appendParameters(
		$dbTransaction->getTransactionObject()->getTransactionContext()->getFailedUrl(),
		$dbTransaction->getTransactionObject()->getTransactionContext()->getCustomParameters()
	);
}
else {
	$redirectionUrl = Customweb_Util_Url::appendParameters(
		$dbTransaction->getTransactionObject()->getTransactionContext()->getSuccessUrl(),
		$dbTransaction->getTransactionObject()->getTransactionContext()->getCustomParameters()
	);
}

?>
<script type="text/javascript">
top.location.href = '<?php echo $redirectionUrl; ?>';
</script>

<noscript>
	<a class="button unzercw-continue-button" href="<?php echo $redirectionUrl; ?>" target="_top"><?php echo unzercw_translate('Continue'); ?></a>
</noscript>