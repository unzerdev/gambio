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

if ((!isset($GLOBALS['unzercw_sofortueberweisung_loaded_class']) || $GLOBALS['unzercw_sofortueberweisung_loaded_class'] !== true ) && !class_exists('unzercw_sofortueberweisung')) {
	$GLOBALS['unzercw_sofortueberweisung_loaded_class'] = true;

	/**
	 * Include abstract class unzercw
	 */
	$baseAdminPath = defined('DIR_ADMIN') ? DIR_ADMIN : 'admin/';
	require_once dirname(dirname(dirname(dirname(__FILE__)))). '/' . $baseAdminPath . 'includes/UnzerCw/init.php';
	require_once 'UnzerCw/PaymentMethod.php';

	// Since Gambio loads some classes over autoloader, we need to check it twice.
	if (!class_exists('unzercw_sofortueberweisung') && !in_array('unzercw_sofortueberweisung', get_declared_classes())) {

		class unzercw_sofortueberweisung extends UnzerCw_PaymentMethod
		{
			public $machineName = 'sofortueberweisung';
			public $admin_title = 'SOFORT';
			public $title = 'SOFORT';

			private $currencies = array(
		'AED' => array(
			'code' => 'AED',
 			'name' => 'United Arab Emirates dirham',
 			'decimalPlaces' => '2',
 		),
 		'AFN' => array(
			'code' => 'AFN',
 			'name' => 'Afghan afghani',
 			'decimalPlaces' => '2',
 		),
 		'ALL' => array(
			'code' => 'ALL',
 			'name' => 'Albanian lek',
 			'decimalPlaces' => '2',
 		),
 		'AMD' => array(
			'code' => 'AMD',
 			'name' => 'Armenian dram',
 			'decimalPlaces' => '2',
 		),
 		'ANG' => array(
			'code' => 'ANG',
 			'name' => 'Netherlands Antillean guilder',
 			'decimalPlaces' => '2',
 		),
 		'AOA' => array(
			'code' => 'AOA',
 			'name' => 'Angolan kwanza',
 			'decimalPlaces' => '2',
 		),
 		'ARS' => array(
			'code' => 'ARS',
 			'name' => 'Argentine peso',
 			'decimalPlaces' => '2',
 		),
 		'AUD' => array(
			'code' => 'AUD',
 			'name' => 'Australian dollar',
 			'decimalPlaces' => '2',
 		),
 		'AWG' => array(
			'code' => 'AWG',
 			'name' => 'Aruban florin',
 			'decimalPlaces' => '2',
 		),
 		'AZN' => array(
			'code' => 'AZN',
 			'name' => 'Azerbaijani manat',
 			'decimalPlaces' => '2',
 		),
 		'BAM' => array(
			'code' => 'BAM',
 			'name' => 'Bosnia and Herzegovina convertible mark',
 			'decimalPlaces' => '2',
 		),
 		'BBD' => array(
			'code' => 'BBD',
 			'name' => 'Barbados dollar',
 			'decimalPlaces' => '2',
 		),
 		'BDT' => array(
			'code' => 'BDT',
 			'name' => 'Bangladeshi taka',
 			'decimalPlaces' => '2',
 		),
 		'BGN' => array(
			'code' => 'BGN',
 			'name' => 'Bulgarian lev',
 			'decimalPlaces' => '2',
 		),
 		'BHD' => array(
			'code' => 'BHD',
 			'name' => 'Bahraini dinar',
 			'decimalPlaces' => '3',
 		),
 		'BIF' => array(
			'code' => 'BIF',
 			'name' => 'Burundian franc',
 			'decimalPlaces' => '0',
 		),
 		'BMD' => array(
			'code' => 'BMD',
 			'name' => 'Bermudian dollar',
 			'decimalPlaces' => '2',
 		),
 		'BND' => array(
			'code' => 'BND',
 			'name' => 'Brunei dollar',
 			'decimalPlaces' => '2',
 		),
 		'BOB' => array(
			'code' => 'BOB',
 			'name' => 'Boliviano',
 			'decimalPlaces' => '2',
 		),
 		'BOV' => array(
			'code' => 'BOV',
 			'name' => 'Bolivian Mvdol',
 			'decimalPlaces' => '2',
 		),
 		'BRL' => array(
			'code' => 'BRL',
 			'name' => 'Brazilian real',
 			'decimalPlaces' => '2',
 		),
 		'BSD' => array(
			'code' => 'BSD',
 			'name' => 'Bahamian dollar',
 			'decimalPlaces' => '2',
 		),
 		'BTN' => array(
			'code' => 'BTN',
 			'name' => 'Bhutanese ngultrum',
 			'decimalPlaces' => '2',
 		),
 		'BWP' => array(
			'code' => 'BWP',
 			'name' => 'Botswana pula',
 			'decimalPlaces' => '2',
 		),
 		'BYN' => array(
			'code' => 'BYN',
 			'name' => 'Belarusian ruble',
 			'decimalPlaces' => '0',
 		),
 		'BZD' => array(
			'code' => 'BZD',
 			'name' => 'Belize dollar',
 			'decimalPlaces' => '2',
 		),
 		'CAD' => array(
			'code' => 'CAD',
 			'name' => 'Canadian dollar',
 			'decimalPlaces' => '2',
 		),
 		'CDF' => array(
			'code' => 'CDF',
 			'name' => 'Congolese franc',
 			'decimalPlaces' => '2',
 		),
 		'CHE' => array(
			'code' => 'CHE',
 			'name' => 'WIR Euro',
 			'decimalPlaces' => '2',
 		),
 		'CHF' => array(
			'code' => 'CHF',
 			'name' => 'Swiss franc',
 			'decimalPlaces' => '2',
 		),
 		'CHW' => array(
			'code' => 'CHW',
 			'name' => 'WIR Franc',
 			'decimalPlaces' => '2',
 		),
 		'CLF' => array(
			'code' => 'CLF',
 			'name' => 'Unidad de Fomento',
 			'decimalPlaces' => '0',
 		),
 		'CLP' => array(
			'code' => 'CLP',
 			'name' => 'Chilean peso',
 			'decimalPlaces' => '0',
 		),
 		'CNY' => array(
			'code' => 'CNY',
 			'name' => 'Chinese yuan',
 			'decimalPlaces' => '2',
 		),
 		'COP' => array(
			'code' => 'COP',
 			'name' => 'Colombian peso',
 			'decimalPlaces' => '2',
 		),
 		'COU' => array(
			'code' => 'COU',
 			'name' => 'Unidad de Valor Real',
 			'decimalPlaces' => '2',
 		),
 		'CRC' => array(
			'code' => 'CRC',
 			'name' => 'Costa Rican colon',
 			'decimalPlaces' => '2',
 		),
 		'CUC' => array(
			'code' => 'CUC',
 			'name' => 'Cuban convertible peso',
 			'decimalPlaces' => '2',
 		),
 		'CUP' => array(
			'code' => 'CUP',
 			'name' => 'Cuban peso',
 			'decimalPlaces' => '2',
 		),
 		'CVE' => array(
			'code' => 'CVE',
 			'name' => 'Cape Verde escudo',
 			'decimalPlaces' => '0',
 		),
 		'CZK' => array(
			'code' => 'CZK',
 			'name' => 'Czech koruna',
 			'decimalPlaces' => '2',
 		),
 		'DJF' => array(
			'code' => 'DJF',
 			'name' => 'Djiboutian franc',
 			'decimalPlaces' => '0',
 		),
 		'DKK' => array(
			'code' => 'DKK',
 			'name' => 'Danish krone',
 			'decimalPlaces' => '2',
 		),
 		'DOP' => array(
			'code' => 'DOP',
 			'name' => 'Dominican peso',
 			'decimalPlaces' => '2',
 		),
 		'DZD' => array(
			'code' => 'DZD',
 			'name' => 'Algerian dinar',
 			'decimalPlaces' => '2',
 		),
 		'EGP' => array(
			'code' => 'EGP',
 			'name' => 'Egyptian pound',
 			'decimalPlaces' => '2',
 		),
 		'ERN' => array(
			'code' => 'ERN',
 			'name' => 'Eritrean nakfa',
 			'decimalPlaces' => '2',
 		),
 		'ETB' => array(
			'code' => 'ETB',
 			'name' => 'Ethiopian birr',
 			'decimalPlaces' => '2',
 		),
 		'EUR' => array(
			'code' => 'EUR',
 			'name' => 'Euro',
 			'decimalPlaces' => '2',
 		),
 		'FJD' => array(
			'code' => 'FJD',
 			'name' => 'Fiji dollar',
 			'decimalPlaces' => '2',
 		),
 		'FKP' => array(
			'code' => 'FKP',
 			'name' => 'Falkland Islands pound',
 			'decimalPlaces' => '2',
 		),
 		'GBP' => array(
			'code' => 'GBP',
 			'name' => 'Pound sterling',
 			'decimalPlaces' => '2',
 		),
 		'GEL' => array(
			'code' => 'GEL',
 			'name' => 'Georgian lari',
 			'decimalPlaces' => '2',
 		),
 		'GHS' => array(
			'code' => 'GHS',
 			'name' => 'Ghanaian cedi',
 			'decimalPlaces' => '2',
 		),
 		'GIP' => array(
			'code' => 'GIP',
 			'name' => 'Gibraltar pound',
 			'decimalPlaces' => '2',
 		),
 		'GMD' => array(
			'code' => 'GMD',
 			'name' => 'Gambian dalasi',
 			'decimalPlaces' => '2',
 		),
 		'GNF' => array(
			'code' => 'GNF',
 			'name' => 'Guinean franc',
 			'decimalPlaces' => '0',
 		),
 		'GTQ' => array(
			'code' => 'GTQ',
 			'name' => 'Guatemalan quetzal',
 			'decimalPlaces' => '2',
 		),
 		'GYD' => array(
			'code' => 'GYD',
 			'name' => 'Guyanese dollar',
 			'decimalPlaces' => '2',
 		),
 		'HKD' => array(
			'code' => 'HKD',
 			'name' => 'Hong Kong dollar',
 			'decimalPlaces' => '2',
 		),
 		'HNL' => array(
			'code' => 'HNL',
 			'name' => 'Honduran lempira',
 			'decimalPlaces' => '2',
 		),
 		'HRK' => array(
			'code' => 'HRK',
 			'name' => 'Croatian kuna',
 			'decimalPlaces' => '2',
 		),
 		'HTG' => array(
			'code' => 'HTG',
 			'name' => 'Haitian gourde',
 			'decimalPlaces' => '2',
 		),
 		'HUF' => array(
			'code' => 'HUF',
 			'name' => 'Hungarian forint',
 			'decimalPlaces' => '2',
 		),
 		'IDR' => array(
			'code' => 'IDR',
 			'name' => 'Indonesian rupiah',
 			'decimalPlaces' => '2',
 		),
 		'ILS' => array(
			'code' => 'ILS',
 			'name' => 'Israeli new shekel',
 			'decimalPlaces' => '2',
 		),
 		'INR' => array(
			'code' => 'INR',
 			'name' => 'Indian rupee',
 			'decimalPlaces' => '2',
 		),
 		'IQD' => array(
			'code' => 'IQD',
 			'name' => 'Iraqi dinar',
 			'decimalPlaces' => '3',
 		),
 		'IRR' => array(
			'code' => 'IRR',
 			'name' => 'Iranian rial',
 			'decimalPlaces' => '0',
 		),
 		'ISK' => array(
			'code' => 'ISK',
 			'name' => 'Icelandic króna',
 			'decimalPlaces' => '0',
 		),
 		'JMD' => array(
			'code' => 'JMD',
 			'name' => 'Jamaican dollar',
 			'decimalPlaces' => '2',
 		),
 		'JOD' => array(
			'code' => 'JOD',
 			'name' => 'Jordanian dinar',
 			'decimalPlaces' => '3',
 		),
 		'JPY' => array(
			'code' => 'JPY',
 			'name' => 'Japanese yen',
 			'decimalPlaces' => '0',
 		),
 		'KES' => array(
			'code' => 'KES',
 			'name' => 'Kenyan shilling',
 			'decimalPlaces' => '2',
 		),
 		'KGS' => array(
			'code' => 'KGS',
 			'name' => 'Kyrgyzstani som',
 			'decimalPlaces' => '2',
 		),
 		'KHR' => array(
			'code' => 'KHR',
 			'name' => 'Cambodian riel',
 			'decimalPlaces' => '2',
 		),
 		'KMF' => array(
			'code' => 'KMF',
 			'name' => 'Comoro franc',
 			'decimalPlaces' => '0',
 		),
 		'KPW' => array(
			'code' => 'KPW',
 			'name' => 'North Korean won',
 			'decimalPlaces' => '0',
 		),
 		'KRW' => array(
			'code' => 'KRW',
 			'name' => 'South Korean won',
 			'decimalPlaces' => '0',
 		),
 		'KWD' => array(
			'code' => 'KWD',
 			'name' => 'Kuwaiti dinar',
 			'decimalPlaces' => '3',
 		),
 		'KYD' => array(
			'code' => 'KYD',
 			'name' => 'Cayman Islands dollar',
 			'decimalPlaces' => '2',
 		),
 		'KZT' => array(
			'code' => 'KZT',
 			'name' => 'Kazakhstani tenge',
 			'decimalPlaces' => '2',
 		),
 		'LAK' => array(
			'code' => 'LAK',
 			'name' => 'Lao kip',
 			'decimalPlaces' => '0',
 		),
 		'LBP' => array(
			'code' => 'LBP',
 			'name' => 'Lebanese pound',
 			'decimalPlaces' => '0',
 		),
 		'LKR' => array(
			'code' => 'LKR',
 			'name' => 'Sri Lankan rupee',
 			'decimalPlaces' => '2',
 		),
 		'LRD' => array(
			'code' => 'LRD',
 			'name' => 'Liberian dollar',
 			'decimalPlaces' => '2',
 		),
 		'LSL' => array(
			'code' => 'LSL',
 			'name' => 'Lesotho loti',
 			'decimalPlaces' => '2',
 		),
 		'LYD' => array(
			'code' => 'LYD',
 			'name' => 'Libyan dinar',
 			'decimalPlaces' => '3',
 		),
 		'MAD' => array(
			'code' => 'MAD',
 			'name' => 'Moroccan dirham',
 			'decimalPlaces' => '2',
 		),
 		'MDL' => array(
			'code' => 'MDL',
 			'name' => 'Moldovan leu',
 			'decimalPlaces' => '2',
 		),
 		'MGA' => array(
			'code' => 'MGA',
 			'name' => 'Malagasy ariary',
 			'decimalPlaces' => '0',
 		),
 		'MKD' => array(
			'code' => 'MKD',
 			'name' => 'Macedonian denar',
 			'decimalPlaces' => '0',
 		),
 		'MMK' => array(
			'code' => 'MMK',
 			'name' => 'Myanma kyat',
 			'decimalPlaces' => '0',
 		),
 		'MNT' => array(
			'code' => 'MNT',
 			'name' => 'Mongolian tugrik',
 			'decimalPlaces' => '2',
 		),
 		'MOP' => array(
			'code' => 'MOP',
 			'name' => 'Macanese pataca',
 			'decimalPlaces' => '2',
 		),
 		'MUR' => array(
			'code' => 'MUR',
 			'name' => 'Mauritian rupee',
 			'decimalPlaces' => '2',
 		),
 		'MVR' => array(
			'code' => 'MVR',
 			'name' => 'Maldivian rufiyaa',
 			'decimalPlaces' => '2',
 		),
 		'MWK' => array(
			'code' => 'MWK',
 			'name' => 'Malawian kwacha',
 			'decimalPlaces' => '2',
 		),
 		'MXN' => array(
			'code' => 'MXN',
 			'name' => 'Mexican peso',
 			'decimalPlaces' => '2',
 		),
 		'MXV' => array(
			'code' => 'MXV',
 			'name' => 'Mexican Unidad de Inversion',
 			'decimalPlaces' => '2',
 		),
 		'MYR' => array(
			'code' => 'MYR',
 			'name' => 'Malaysian ringgit',
 			'decimalPlaces' => '2',
 		),
 		'MZN' => array(
			'code' => 'MZN',
 			'name' => 'Mozambican metical',
 			'decimalPlaces' => '2',
 		),
 		'NAD' => array(
			'code' => 'NAD',
 			'name' => 'Namibian dollar',
 			'decimalPlaces' => '2',
 		),
 		'NGN' => array(
			'code' => 'NGN',
 			'name' => 'Nigerian naira',
 			'decimalPlaces' => '2',
 		),
 		'NIO' => array(
			'code' => 'NIO',
 			'name' => 'Nicaraguan córdoba',
 			'decimalPlaces' => '2',
 		),
 		'NOK' => array(
			'code' => 'NOK',
 			'name' => 'Norwegian krone',
 			'decimalPlaces' => '2',
 		),
 		'NPR' => array(
			'code' => 'NPR',
 			'name' => 'Nepalese rupee',
 			'decimalPlaces' => '2',
 		),
 		'NZD' => array(
			'code' => 'NZD',
 			'name' => 'New Zealand dollar',
 			'decimalPlaces' => '2',
 		),
 		'OMR' => array(
			'code' => 'OMR',
 			'name' => 'Omani rial',
 			'decimalPlaces' => '3',
 		),
 		'PAB' => array(
			'code' => 'PAB',
 			'name' => 'Panamanian balboa',
 			'decimalPlaces' => '2',
 		),
 		'PEN' => array(
			'code' => 'PEN',
 			'name' => 'Peruvian nuevo sol',
 			'decimalPlaces' => '2',
 		),
 		'PGK' => array(
			'code' => 'PGK',
 			'name' => 'Papua New Guinean kina',
 			'decimalPlaces' => '2',
 		),
 		'PHP' => array(
			'code' => 'PHP',
 			'name' => 'Philippine peso',
 			'decimalPlaces' => '2',
 		),
 		'PKR' => array(
			'code' => 'PKR',
 			'name' => 'Pakistani rupee',
 			'decimalPlaces' => '2',
 		),
 		'PLN' => array(
			'code' => 'PLN',
 			'name' => 'Polish złoty',
 			'decimalPlaces' => '2',
 		),
 		'PYG' => array(
			'code' => 'PYG',
 			'name' => 'Paraguayan guaraní',
 			'decimalPlaces' => '0',
 		),
 		'QAR' => array(
			'code' => 'QAR',
 			'name' => 'Qatari riyal',
 			'decimalPlaces' => '2',
 		),
 		'RON' => array(
			'code' => 'RON',
 			'name' => 'Romanian new leu',
 			'decimalPlaces' => '2',
 		),
 		'RSD' => array(
			'code' => 'RSD',
 			'name' => 'Serbian dinar',
 			'decimalPlaces' => '2',
 		),
 		'RUB' => array(
			'code' => 'RUB',
 			'name' => 'Russian rouble',
 			'decimalPlaces' => '2',
 		),
 		'RWF' => array(
			'code' => 'RWF',
 			'name' => 'Rwandan franc',
 			'decimalPlaces' => '0',
 		),
 		'SAR' => array(
			'code' => 'SAR',
 			'name' => 'Saudi riyal',
 			'decimalPlaces' => '2',
 		),
 		'SBD' => array(
			'code' => 'SBD',
 			'name' => 'Solomon Islands dollar',
 			'decimalPlaces' => '2',
 		),
 		'SCR' => array(
			'code' => 'SCR',
 			'name' => 'Seychelles rupee',
 			'decimalPlaces' => '2',
 		),
 		'SDG' => array(
			'code' => 'SDG',
 			'name' => 'Sudanese pound',
 			'decimalPlaces' => '2',
 		),
 		'SEK' => array(
			'code' => 'SEK',
 			'name' => 'Swedish krona',
 			'decimalPlaces' => '2',
 		),
 		'SGD' => array(
			'code' => 'SGD',
 			'name' => 'Singapore dollar',
 			'decimalPlaces' => '2',
 		),
 		'SHP' => array(
			'code' => 'SHP',
 			'name' => 'Saint Helena pound',
 			'decimalPlaces' => '2',
 		),
 		'SLL' => array(
			'code' => 'SLL',
 			'name' => 'Sierra Leonean leone',
 			'decimalPlaces' => '0',
 		),
 		'SOS' => array(
			'code' => 'SOS',
 			'name' => 'Somali shilling',
 			'decimalPlaces' => '2',
 		),
 		'SRD' => array(
			'code' => 'SRD',
 			'name' => 'Surinamese dollar',
 			'decimalPlaces' => '2',
 		),
 		'SSP' => array(
			'code' => 'SSP',
 			'name' => 'South Sudanese pound',
 			'decimalPlaces' => '2',
 		),
 		'SVC' => array(
			'code' => 'SVC',
 			'name' => 'El Salvador Colon',
 			'decimalPlaces' => '2',
 		),
 		'SYP' => array(
			'code' => 'SYP',
 			'name' => 'Syrian pound',
 			'decimalPlaces' => '2',
 		),
 		'SZL' => array(
			'code' => 'SZL',
 			'name' => 'Swazi lilangeni',
 			'decimalPlaces' => '2',
 		),
 		'THB' => array(
			'code' => 'THB',
 			'name' => 'Thai baht',
 			'decimalPlaces' => '2',
 		),
 		'TJS' => array(
			'code' => 'TJS',
 			'name' => 'Tajikistani somoni',
 			'decimalPlaces' => '2',
 		),
 		'TMT' => array(
			'code' => 'TMT',
 			'name' => 'Turkmenistani manat',
 			'decimalPlaces' => '2',
 		),
 		'TND' => array(
			'code' => 'TND',
 			'name' => 'Tunisian dinar',
 			'decimalPlaces' => '3',
 		),
 		'TOP' => array(
			'code' => 'TOP',
 			'name' => 'Tongan paʻanga',
 			'decimalPlaces' => '2',
 		),
 		'TRY' => array(
			'code' => 'TRY',
 			'name' => 'Turkish lira',
 			'decimalPlaces' => '2',
 		),
 		'TWD' => array(
			'code' => 'TWD',
 			'name' => 'New Taiwan dollar',
 			'decimalPlaces' => '2',
 		),
 		'TTD' => array(
			'code' => 'TTD',
 			'name' => 'Trinidad and Tobago dollar',
 			'decimalPlaces' => '2',
 		),
 		'TZS' => array(
			'code' => 'TZS',
 			'name' => 'Tanzanian shilling',
 			'decimalPlaces' => '2',
 		),
 		'UAH' => array(
			'code' => 'UAH',
 			'name' => 'Ukrainian hryvnia',
 			'decimalPlaces' => '2',
 		),
 		'UGX' => array(
			'code' => 'UGX',
 			'name' => 'Ugandan shilling',
 			'decimalPlaces' => '2',
 		),
 		'USD' => array(
			'code' => 'USD',
 			'name' => 'United States dollar',
 			'decimalPlaces' => '2',
 		),
 		'USN' => array(
			'code' => 'USN',
 			'name' => 'United States dollar',
 			'decimalPlaces' => '2',
 		),
 		'UYI' => array(
			'code' => 'UYI',
 			'name' => 'Uruguay Peso en Unidades Indexadas',
 			'decimalPlaces' => '0',
 		),
 		'UYU' => array(
			'code' => 'UYU',
 			'name' => 'Uruguayan peso',
 			'decimalPlaces' => '2',
 		),
 		'UZS' => array(
			'code' => 'UZS',
 			'name' => 'Uzbekistan som',
 			'decimalPlaces' => '2',
 		),
 		'VEF' => array(
			'code' => 'VEF',
 			'name' => 'Venezuelan bolívar fuerte',
 			'decimalPlaces' => '2',
 		),
 		'VND' => array(
			'code' => 'VND',
 			'name' => 'Vietnamese dong',
 			'decimalPlaces' => '0',
 		),
 		'VUV' => array(
			'code' => 'VUV',
 			'name' => 'Vanuatu vatu',
 			'decimalPlaces' => '0',
 		),
 		'WST' => array(
			'code' => 'WST',
 			'name' => 'Samoan tala',
 			'decimalPlaces' => '2',
 		),
 		'XAF' => array(
			'code' => 'XAF',
 			'name' => 'CFA franc BEAC',
 			'decimalPlaces' => '0',
 		),
 		'XCD' => array(
			'code' => 'XCD',
 			'name' => 'East Caribbean dollar',
 			'decimalPlaces' => '2',
 		),
 		'XOF' => array(
			'code' => 'XOF',
 			'name' => 'CFA franc BCEAO',
 			'decimalPlaces' => '0',
 		),
 		'XPF' => array(
			'code' => 'XPF',
 			'name' => 'CFP franc',
 			'decimalPlaces' => '0',
 		),
 		'YER' => array(
			'code' => 'YER',
 			'name' => 'Yemeni rial',
 			'decimalPlaces' => '2',
 		),
 		'ZAR' => array(
			'code' => 'ZAR',
 			'name' => 'South African rand',
 			'decimalPlaces' => '2',
 		),
 		'ZMW' => array(
			'code' => 'ZMW',
 			'name' => 'Zambian kwacha',
 			'decimalPlaces' => '2',
 		),
 		'ZWL' => array(
			'code' => 'ZWL',
 			'name' => 'Zimbabwe Dollar',
 			'decimalPlaces' => '2',
 		),
 	);

			public function getSettings() {
				$settings = parent::getSettings();

				$methodSettings = array(
			'status_authorized' => array(
				'title' => unzercw_translate("Authorized Status"),
 				'description' => unzercw_translate("This status is set, when the payment was successfull and it is authorized."),
 				'type' => 'ORDERSTATUSSELECT',
 				'default' => 'authorized',
 			),
 			'status_uncertain' => array(
				'title' => unzercw_translate("Uncertain Status"),
 				'description' => unzercw_translate("You can specify the order status for new orders that have an uncertain authorisation status."),
 				'type' => 'ORDERSTATUSSELECT',
 				'default' => 'uncertain',
 			),
 			'status_cancelled' => array(
				'title' => unzercw_translate("Cancelled Status"),
 				'description' => unzercw_translate("You can specify the order status when an order is cancelled."),
 				'type' => 'ORDERSTATUSSELECT',
 				'options' => array(
					'no_status_change' => unzercw_translate("Don't change order status"),
 				),
 				'default' => 'cancelled',
 			),
 			'status_captured' => array(
				'title' => unzercw_translate("Captured Status"),
 				'description' => unzercw_translate("You can specify the order status for orders that are captured either directly after the order or manually in the backend."),
 				'type' => 'ORDERSTATUSSELECT',
 				'options' => array(
					'no_status_change' => unzercw_translate("Don't change order status"),
 				),
 				'default' => 'no_status_change',
 			),
 			'send_basket' => array(
				'title' => unzercw_translate("Send Basket"),
 				'description' => unzercw_translate("Should the invoice items be transmitted to Unzer? This slightly increases the processing time due to an additional request, and may cause issues for certain quantity / price combinations."),
 				'type' => 'SELECT',
 				'options' => array(
					'no' => unzercw_translate("Do not send"),
 					'yes' => unzercw_translate("Send Basket"),
 				),
 				'default' => 'no',
 			),
 			'send_customer' => array(
				'title' => unzercw_translate("Send Customer"),
 				'description' => unzercw_translate("Should customer data be transmitted to Unzer? This slightly increases the processing time due to an additional request, but may allow e.g. saving the payment method to the customer."),
 				'type' => 'SELECT',
 				'options' => array(
					'no' => unzercw_translate("Do not send"),
 					'yes' => unzercw_translate("Send Customer"),
 				),
 				'default' => 'no',
 			),
 			'authorizationMethod' => array(
				'title' => unzercw_translate("Authorization Method"),
 				'description' => unzercw_translate("Select the authorization method to use for processing this payment method."),
 				'type' => 'SELECT',
 				'options' => array(
					'AjaxAuthorization' => unzercw_translate("Ajax Authorization"),
 				),
 				'default' => 'AjaxAuthorization',
 			),
 		);;

				return array_merge($settings, $methodSettings);
			}

			protected function getSupportedCurrencies() {
				return $this->currencies;
			}

			protected function getImageUrl() {
				return 'images/icons_unzercw/sofortueberweisung.png';
			}
		}
	}

}
