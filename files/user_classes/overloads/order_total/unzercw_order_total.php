<?php
if(!class_exists('unzercw_order_total', false)) {
	class unzercw_order_total extends unzercw_order_total_parent {
		public function __construct() {
			parent::__construct();
			$GLOBALS['order_total_modules'] = $this;
		}
	
		function unzercw_order_total() {
			self::__construct();
		}
	}
}