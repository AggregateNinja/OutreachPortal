<?php
require_once 'BaseObject.php';

class EmployeeDepartment extends BaseObject {
    protected $Data = array(
        "idemployeeDepartments" => "",
        "name" => "",
        "defaultUserGroup" => ""
    );
    
    public function __isset($field) {
        $isset = parent::__isset($field);        
        if (!$isset) {
            if ($field == "EmployeeDepartment" && isset($this->Data) && is_array($this->Data) && count($this->Data) > 0) {
                $isset = true;
            }            
        }        
        return $isset;
    }
}


?>
