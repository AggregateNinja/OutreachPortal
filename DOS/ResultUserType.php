<?php
require_once 'BaseObject.php';
/**
 * Description of ResultUserType
 *
 * @author Edd
 */
class ResultUserType extends BaseObject {
    
    protected $ResultUserTypes = array (
        "idTypes" => "",
        "typeName" => "",
        "dateCreated" => ""
    );
    
    public function __construct() {
        
    }
    
    public function setResultUserTypes($data) {
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->ResultUserTypes)) {
                $this->ResultUserTypes[$key] = $value;
            }
        }
    }
    
    public function __get($field) {
        if (array_key_exists($field, $this->ResultUserTypes)) {
            return $this->ResultUserTypes[$field];
        }
    }
}

?>
