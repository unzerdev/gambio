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

// Since we need a way to process authorization without any dependency on a current user session, we
// need to emulate the user session in separate PHP process.


require_once 'includes/application_top.php';
$baseAdminPath = defined('DIR_ADMIN') ? DIR_ADMIN : 'admin/';
require_once dirname(__FILE__) . '/' . $baseAdminPath . 'includes/UnzerCw/init.php';

require_once 'UnzerCw/AuthorizationScript.php';


$scriptHandler = new UnzerCw_AuthorizationScript();

$scriptHandler->loadSession();
$file = $scriptHandler->generateFile();

// Make sure we set the right currency and the right customer status.
$xtPrice = new xtcPrice($_SESSION['currency'], $_SESSION['customers_status']['customers_status_id']);

// Set some environment variables
$_SERVER['SCRIPT_NAME'] = str_replace('unzercw_authorization.php', 'checkout_process.php', $_SERVER['SCRIPT_NAME']);
$_SERVER['PHP_SELF'] = str_replace('unzercw_authorization.php', 'checkout_process.php', $_SERVER['PHP_SELF']);

$GLOBALS['unzercwrun_authorization_in_isolation'] = true;

require_once $file;
