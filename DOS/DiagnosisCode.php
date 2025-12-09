<?php
require_once 'BaseObject.php';

class DiagnosisCode extends BaseObject {

    protected $Data = array (
        "idDiagnosisCodes" => "",
        "code" => "",
        "description" => "",
        "longDescription" => "",
        "FullDescription" => "",
        "loinc" => "",
        "cptCode" => "",
        "dateCreated" => "",
        "dateUpdated" => "",
        "version" => ""
    );

    public function __get($field) {
        if (array_key_exists($field, $this->Data)) {
            return $this->Data[$field];
        }
        return "";
    }
    
    public function __set($field, $value) {
        $done = parent::__set($field, $value);
        
        if (!$done) {
            if ($field = "Data" && is_array($value)) { 
                foreach ($value as $dataField => $dataValue) {
                    if (array_key_exists($dataField, $this->Data)) {
                        $this->Data[$dataField] = $dataValue;
                    }
                }
                return true;
            } else if (array_key_exists($field, $this->Data)) {
                $this->Data[$field] = $value;
                return true;
            }
        }
        return false;
    }
    
//    public function __isset($field) {
//        $isset = parent::__isset($field);
//        
//        if (!isset($isset)) {
//            if ($field == "DiagnosisCode") {
//                if ()
//            }            
//        }
//        
//        return $isset;
//    }
}
?>
