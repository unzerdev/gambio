<?php 
$baseAdminPath = defined('DIR_ADMIN') ? DIR_ADMIN : 'admin/'; 
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/' . $baseAdminPath . 'includes/UnzerCw/init.php');
require_once('UnzerCw/PaymentMethod.php');
UnzerCw_PaymentMethod::getModulInstanceByClass('unzercw_unzerbanktransfer')->languageFileEvent();