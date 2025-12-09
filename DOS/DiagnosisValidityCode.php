<?php
require_once 'DiagnosisCode.php';
require_once 'DiagnosisValidity.php';


class DiagnosisValidityCode extends DiagnosisCode {
    public $Validity = array ();
    
    protected $Lookups = array(
        "orderId" => "",
        "resultId" => "",
        "advancedOrder" => false
    );
    
    public function __construct(array $data = null, $includeNames = false) {
        if ($data != null) {
            parent::__construct($data);

            /*$validity = new DiagnosisValidity($data, $includeNames);
            $this->Validity[] = $validity;*/

            if (array_key_exists("orderId", $data)) {
                $this->Lookups['orderId'] = $data['orderId'];
            }
            if (array_key_exists("resultId", $data)) {
                $this->Lookups['resultId'] = $data['resultId'];
            }
            if (array_key_exists("advancedOrder", $data)) {
                $this->Lookups['advancedOrder'] = $data['advancedOrder'];
            }
            if (array_key_exists("diagnosisCodeId", $data)) {
                $this->Data['idDiagnosisCodes'] = $data['diagnosisCodeId'];
            }        
        }
    }
    
    public function __set($field, $value) {
        $done = parent::__set($field, $value);
        if (!$done) {
            if ($field == "advancedOrder") {
                $this->advancedOrder = $value;
            }
        }
    }
    
    public function __get($field) {
        $value = parent::__get($field);

        if (empty($value)) {

            if ($value == "test") {
                $value = array("test" => "test");
            } else

            if ($value == "Validity") {
                $value = $this->Validity;
            } else if (array_key_exists($field, $this->Validity)) {
                $value = $this->Validity[$field];
            } else if (array_key_exists($field, $this->Lookups)) {
                $value = $this->Lookups[$field];
            }
        }
        
        return $value;
    }
    
    public function hasTestId($testId) {
        foreach ($this->Validity as $validity) {
            if ($testId == $validity->testId) {
                return true;
            }
        }
        return false;
    }
    
    public function getValidityByTestId($testId) {
        foreach ($this->Validity as $validity) {
            if ($validity->testId == $testId) {
                return $validity;
            }
        }
        return false;
    }
    
    public function addValidity($dataRow, $includeNames = false) {
        //if (!array_key_exists($dataRow['idDiagnosisValidity'], $this->Validity)) {
            $validity = new DiagnosisValidity($dataRow);
            if ($includeNames) {
                //$validity->setTest(array("name" => $dataRow['testName'], "number" => $dataRow['testNumber']));
                $validity->setTest($dataRow);
                $validity->setInsurance(array("name" => $dataRow['insuranceName']));
            }

            $this->Validity[] = $validity;
        //}
    }
    
    public function getValidity() {
        return $this->Validity;
    }
    
    public function setOrderId($orderId) {
        $this->Lookups['orderId'] = $orderId;
    }
    public function setResultId($resultId) {
        $this->Lookups['resultId'] = $resultId;
    }
    
    
}



?>
