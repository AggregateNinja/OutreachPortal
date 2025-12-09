<?php
require_once 'BaseObject.php';
require_once 'EmployeeDepartment.php';

class Employee extends BaseObject {
    protected $Data = array (
        "idemployees" => "",
        "firstName" => "",
        "lastName" => "",
        "department" => "",
        "position" => "",
        "homePhone" => "",
        "mobilePhone" => "",
        "address" => "",
        "address2" => "",
        "city" => "",
        "state" => "",
        "zip" => ""
    );
    protected $EmployeeDepartment;
    
    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);
            $this->EmployeeDepartment = new EmployeeDepartment($data);
        }        
    }
    
    public function __get($field) {
        $value = parent::__get($field);
        
        if (empty($value)) {
            if ($field == "EmployeeDepartment" && isset($this->EmployeeDepartment)) {
                $value = $this->EmployeeDepartment;
            }
        }
        return $value;
    }
    
    public function __isset($field) {
        $isset = parent::__isset($field);
        
        if (!$isset) {
            if (($field == "Employee" || $field == "Phlebotomist") && isset($this->Data) && is_array($this->Data) && count($this->Data) > 0) {
                $isset = true;
            } else if ($field == "EmployeeDepartment" && isset($this->EmployeeDepartment)) {
                $isset = true;
            }    
        }
        
        return $isset;
    }
    
    
}


?>

