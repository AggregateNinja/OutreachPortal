<?php
require_once 'BaseObject.php';

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ResultLog
 *
 * @author Edd
 */
class ResultLog extends BaseObject {
    protected $Data = array (
        "idLogs" => "",
        "typeId" => "",
        "authId" => "",
        "logDate" => ""        
    );
    
    protected $ResultLogTypes = array (
        "idTypes" => "",
        "name" => "",
        "description" => ""
    );
    
    public function __get($field) {
        if (array_key_exists($field, $this->Data)) {
            return $this->Data[$field];
        } else if (array_key_exists($field, $this->ResultLogTypes)) {
            return $this->ResultLogTypes[$field];
        } else {
            die("Field not found: $field");
        }
    }
    
    public function setResultLogTypes($types) {
        foreach ($types as $key => $value) {            
            if (array_key_exists($key, $this->ResultLogTypes))
                $this->ResultLogTypes[$key] = $value;
        }
    }
}

?>
