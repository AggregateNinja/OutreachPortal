<?php
require_once 'BaseObject.php';
require_once 'Test.php';
require_once 'Insurance.php';

class DiagnosisValidity extends BaseObject {
    protected $Data = array (
        "idDiagnosisValidity" => "",
        "diagnosisCodeId" => "",
        "testId" => "",
        "insuranceId" => "",
        "validity" => ""
        
    );
    protected $Test;
    protected $Insurance;
    
    public function __construct($data, $includeNames = false) {
        parent::__construct($data);

        $testNumber = "";
        if (array_key_exists("number", $data)) {
            $testNumber = $data['number'];
        } else if (array_key_exists("testNumber", $data)) {
            $testNumber = $data['testNumber'];
        }
        
        if ($includeNames) {
            //$this->setTest(array("name" => $data['testName'], "number" => $testNumber));
            $this->setTest($data);
            $this->setInsurance(array("name" => $data['insuranceName']));            
        }
    }

    public function setTest($data){
        $this->Test = new Test($data);
    }
    public function setInsurance($data) {
        $this->Insurance = new Insurance($data);
    }
        
    public function getTest() {
        return $this->Test;
    }
    public function getInsurance() {
        return $this->Insurance;
    }
    
    
    
    
}






?>
