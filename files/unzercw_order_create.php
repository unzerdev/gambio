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

// This script is used to create orders in isolations.

require_once 'includes/application_top.php';
$baseAdminPath = defined('DIR_ADMIN') ? DIR_ADMIN : 'admin/';
require_once dirname(__FILE__) . '/' . $baseAdminPath . 'includes/UnzerCw/init.php';

require_once 'UnzerCw/ScriptHandler.php';


// TODO: Load session (similar as in unzercw_authorization.php)

// Prevent redirecting after login
unset($_SESSION['REFERER']);

$scriptHandler = new UnzerCw_ScriptHandler(UNZERCW_CATALOG_PATH . '/login.php');

// @formatter:off
$scriptHandler
	->replace("include ('includes/application_top.php');", '')
	->replace("include('includes/application_top.php');", '')
	->replace("require_once('includes/application_top.php');", '')
	->replace('$payment_modules->before_process();', '')
	->replace('if ($tmp)', '')
	->replace('$payment_modules->payment_action();', '');

// @formatter:on

$file = $scriptHandler->write();
require_once $file;
