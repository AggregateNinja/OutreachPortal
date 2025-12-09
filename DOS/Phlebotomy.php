<?php
require_once 'BaseObject.php';
class Phlebotomy extends BaseObject {
    protected $Data = array(
        "idPhlebotomy" => "",
        "idAdvancedOrder" => "",
        "idOrders" => "",
        "startDate" => "",
        "drawCount" => "",
        "frequency" => "",
        "frequencyUnits" => "",
        "phlebotomist" => "",
        "zone" => "",
        "drawComment1" => "",
        "drawComment2" => ""
    );
    
    protected $Phlebotomist;
    
    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);
        }
    }
    
    public function __set($field, $value) {
        $done = parent::__set($field, $value);
        
        if (!$done) {
            if ($field == "Phlebotomist") {
                $this->Phlebotomist = $value;
            }
        }
    }
    
    public function __get($field) {
        $value = parent::__get($field);        
        if (empty($value)) {
            if (array_key_exists($field, $this->Data)) {
                $value = $this->Data[$field];
            } else if ($field == "Phlebotomist") {
                $value = $this->Phlebotomist;
            }
        }
        return $value;
    }
    public function __isset($field) {
        $isset = parent::__isset($field);        
        if (!$isset) {
            if ($field == "Phlebotomist" && isset($this->Phlebotomist)) {
                $isset = true;
            }
        } else if ($field == "Phlebotomy" && isset($this->Data) && is_array($this->Data) && array_key_exists("idPhlebotomy", $this->Data)) {
            $isset = true;
        }
        return $isset;
    }
        
    
}


?>
