<?php
require_once 'BaseObject.php';
require_once 'DOS/Test.php';

/**
 * Description of Result
 *
 * @author Edd
 */
class Result extends BaseObject {
    
    protected $Data = array(
        /*
        "idResults" => "",
        "orderId" => "",
        "idTests" => "",
        "name" => "",
        "resultText" => ""
        */
        "idResults" => "",
        "orderId" => "",
        "testId" => "",
        "panelId" => "",
        "resultNo" => "",
        "resultText" => "",
        "resultRemark" => "",
        "resultChoice" => "",
        "created" => "",
        "reportedBy" => "",
        "dateReported" => "",
        "isApproved" => "",
        "approvedDate" => "",
        "approvedBy" => "",
        "isInvalidated" => "",
        "invalidatedDate" => "",
        "invalidatedBy" => "",
        "isUpdated" => "",
        "updatedBy" => "",
        "updatedDate" => "",
        "isAbnormal" => "",
        "isHigh" => "",
        "isLow" => "",
        "isCIDHigh" => "",
        "isCIDLow" => "",
        "noCharge" => "",
        "textAnswer" => "",
        "printAndTransmitted" => "",
        "pAndTDate" => "",

        "remarkAbbr" => "",
        "remarkName" => "",
        "remarkText" => ""
    );
    
    protected $IsPOC;
    
    public $Test2;
    
    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);
            $this->IsPOC = false;

            /*foreach($data as $key => $value) {
                if (in_array($key, $this->booleanFields) && is_numeric($value)) {
                    if ($value == 0) {
                        $this->Data[$key] = false;
                    } else if ($value == 1) {
                        $this->Data[$key] = true;
                    }
                }
            }*/
        }
    }
    
    public function __set($key, $value) {
        if ($key == "IsPOC") {
            $this->IsPOC = $value;
        } else if (array_key_exists($key, $this->Data)) {
            $this->Data[$key] = $value;
        } else if ($key == "Test" && $value instanceof Test) {
            $this->Test = $value;
        }
    }
    
    public function __get($key) {
        $field = parent::__get($key);
        
        if (empty($field)) {
            if ($key == "IsPOC") {
                $field = $this->IsPOC;
            } else if ($key == "Test") {
                return $this->Test;
            }       
        }
        return $field;
    }
    
    public function setTest(array $data) {
        $this->Test = new Test($data);
    }
   
    public function __isset($field) {
        $isset = parent::__isset($field);
        if (!$isset) {
            if ($field == "Test" && isset($this->Test)) {
                $isset = true;
            }
        }
        return $isset;
    }
    
//    public function __construct() {
//        
//    }
}

?>
