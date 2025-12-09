<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once 'BaseObject.php';
require_once 'DiagnosisValidity.php';

class Test extends BaseObject {
    protected $Data = array (
        "idtests" => "",
        "number" => "",
        "subtest" => "",
        "name" => "",
        "printedName" => "",
        "abbr" => "",
        "testType" => "",
        "resultType" => "",
        "lowNormal" => "",
        "highNormal" => "",
        "alertLow" => "",
        "alertHigh" => "",
        "criticalLow" => "",
        "criticalHigh" => "",
        "printNormals" => "",
        "units" => "",
        "remark" => "",
        "department" => "",
        "instrument" => "",
        "onlineCode1" => "",
        "onlineCode2" => "",
        "created" => "",
        "relatedDrug" => "",
        "decimalPositions" => "",
        "printOrder" => "",
        "specimenType" => "",
        "loinc" => "",
        "billingOnly" => "",
        "labelPrint" => "",
        "tubeType" => "",
        "headerPrint" => "",
        "active" => "",
        "deactivatedDate" => "",
        "testComment" => "",
        "extraNormals" => "",
        "AOE" => "",
        "cycles" => "",
        "stat" => "",

        "idDepartment" => "",
        "deptNo" => "",
        "deptName" => "",
        "promptPOC" => "",

        "idspecimenTypes" => "",
        "specimenTypeName" => ""
    );

    protected $Validities = array();

    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);

            if (array_key_exists("testName", $data)) {
                $this->Data['name'] = $data['testName'];
            }
            if (array_key_exists("testNumber", $data)) {
                $this->Data['number'] = $data['testNumber'];
            }
            if (array_key_exists("testId", $data)) {
                $this->Data['idtests'] = $data['testId'];
            }
        }
    }

    public function __get($field) {
        $value = parent::__get($field);

        if (empty($value)) {
            if ($field == "Validities") {
                $value = $this->Validities;
            }
        }

        return $value;
    }
    public function addValidity(array $dataRow) {
        $validity = new DiagnosisValidity($dataRow);
        $this->Validities[$dataRow['idDiagnosisValidity']] = $validity;
    }
}
?>