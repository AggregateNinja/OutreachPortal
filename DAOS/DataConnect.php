<?php
require_once 'DataObject.php';


class DataConnect extends DataObject {
	
	public static function getConn(array $settings = null) {
		return parent::connect($settings);
	}
	
	
}





?>