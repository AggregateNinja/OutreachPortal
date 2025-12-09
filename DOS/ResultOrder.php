<?php
if (!isset($_SESSION)) {
    session_start();
}
require_once 'Order.php';
require_once 'Result.php';
require_once 'Patient.php';
require_once 'Location.php';
require_once 'Insurance.php';
require_once 'Phlebotomy.php';
require_once 'User.php';

/**
 * Description of ResultOrder
 *
 * @author Edd
 */
class ResultOrder extends Order {
    protected $Results = array(); // array of Results
    protected $Patient;
    protected $Location;
    protected $Insurance;
    protected $Phlebotomy;
    protected $IsAdvancedOrder = false;

    protected $User; // The user who added the initial order or last updated the order

    private $IsReceipted = false;

    private $UserIdEditingOrder = "";
    private $OrderEditDate = "";

    public $WebOrderCanceled = false;

    public function __construct($data) {
        parent::__construct($data); // set the Order fields

        if (array_key_exists("userIdEditingOrder", $data) && $data['userIdEditingOrder'] != null) {
            $this->UserIdEditingOrder = $data['userIdEditingOrder'];
        }
        if (array_key_exists("editDate", $data) && $data['editDate'] != null) {
            $this->OrderEditDate = $data['editDate'];
        }

        if (array_key_exists("WebOrderCanceled", $data) && ($data['WebOrderCanceled'] == 1 || $data['WebOrderCanceled'] == true)) {
            $this->WebOrderCanceled = true;
        }

        $this->Patient = new Patient($data); // set the Patient fields
    }
    
    public function addResult($data, array $settings = null) {
        $result = new Result($data);
        if ($settings != null && array_key_exists("IncludeTest", $settings) && $settings['IncludeTest'] == true) {
            $result->setTest($data);
        }
        
        $this->Results[] = $result;
    }
    
    public function setPatient(array $dataRow) {
        $this->Patient = new Patient($dataRow);
    }
    
    public function setLocation(array $dataRow) {
        $this->Location = new Location($dataRow);
    }
    
    public function setInsurance(array $dataRow) {
        $this->Insurance = new Insurance($dataRow);
    }
    
    public function getResultTextByTestId($idTests) {

        foreach ($this->Results as $result) {
            if ($result->Test->idtests == $idTests) {
                return $result->resultText;
            }
        }

        return "";
    }

    public function getResultTextByTestNumber($number) {

        foreach ($this->Results as $result) {
            if ($result->Test->number == $number) {
                return $result->resultText;
            }
        }

        return "";
    }

    public function getResultByTestNumber($number) {
        foreach ($this->Results as $result) {
            if ($result->Test->number == $number) {
                return $result;
            }
        }

        return null;
    }

    public function setUser(array $data) {
        $this->User = new User($data);
    }

    public function getUser($field = null) {
        if (isset($this->User) && $this->User instanceof User) {
            if ($field == null) {
                return $this->User;
            } elseif (isset($this->User->$field)) {
                return $this->User->$field;
            }
        }
        return null;
    }

    public function __get($key) {
        $value = parent::__get($key);
        
        if (empty($value)) {
            if ($key == "Results") {
                $value = $this->Results;
            } elseif ($key == "Patient") {
                $value = $this->Patient;
            } elseif($key == "Location") {
                $value = $this->Location;
            } elseif ($key == "Insurance") {
                $value = $this->Insurance;
            } else if ($key == "insuranceName" && isset($this->Insurance)) {
                $value = $this->Insurance->name;
            } else if ($key == "Phlebotomy" && isset($this->Phlebotomy)) {                
                $value = $this->Phlebotomy;
            } else if ($key == "IsAdvancedOrder") {
                $value = $this->IsAdvancedOrder;
            } else if ($key == "UserIdEditingOrder") {
                $value = $this->UserIdEditingOrder;
            } else if ($key == "OrderEditDate") {
                $value = $this->OrderEditDate;
            }
            /*else if ($key == "IsReceipted") {
                $value = $this->IsReceipted;
            } else if ($key == "DateReceipted") {
                $value = $this->DateReceipted;
            } else if ($key == "TimeReceipted") {
                $value = $this->TimeReceipted;
            }*/
        }
        
        return $value;
    }
    
    public function __set($field, $value) {
        $done = parent::__set($field, $value);
        if (!$done) {
            if ($field == "Phlebotomy") {
            	if ($value instanceof Phlebotomy) {
                    $this->Phlebotomy = $value;
                    $done = true;
            	} else if (is_array($value)) {
                    $this->Phlebotomy = new Phlebotomy($value);
                    $done = true;
            	}                
            } else if ($field == "IsAdvancedOrder") {
                $this->IsAdvancedOrder = $value;
                $done = true;
            }
        }
        return $done;
    }
    
    public function __isset($field) {
        $isset = parent::__isset($field);
        if (!$isset) {
            if ($field == "Phlebotomy" && isset($this->Phlebotomy)) {
                $isset = true;
            } else if ($field == "Patient" && isset($this->Patient) && $this->Patient instanceof Patient) {
            	$isset = true;
            } else if ($field == "Location" && isset($this->Location) && $this->Location instanceof Location) {
            	$isset = true;
            } else if ($field == "Insurance" && isset($this->Insurance) && $this->Insurance instanceof Insurance) {
            	$isset = true;
            } else if ($field == "UserIdEditingOrder" && $this->UserIdEditingOrder != null && !empty($this->UserIdEditingOrder) && is_numeric($this->UserIdEditingOrder)) {
                $isset = true;
            } else if ($field == "OrderEditDate" && $this->OrderEditDate != null && !empty($this->OrderEditDate) && $this->isValidDate($this->OrderEditDate, "Y-m-d H:i:s")) {
                $isset = true;
            }
        }
        return $isset;
    }
    
    
}

?>
