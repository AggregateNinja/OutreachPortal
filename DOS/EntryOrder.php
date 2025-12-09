<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'Order.php';
require_once 'Subscriber.php';
require_once 'Patient.php';
require_once 'DoctorUser.php';
require_once 'ClientUser.php';
require_once 'Test.php';
require_once 'Drug.php';
require_once 'POCTest.php';
require_once 'Result.php';
require_once 'Prescription.php';
require_once 'OrderComment.php';
require_once 'DiagnosisValidityCode.php';
require_once 'Insurance.php';
require_once 'Phlebotomy.php';
require_once 'Location.php';
require_once 'OrderEmail.php';

class EntryOrder extends Order {

    private $Subscriber;
    protected $Patient;
    //private $Tests = array();
    //private $POCTests = array();
    private $Insurance;
    private $SecondaryInsurance;
    private $Prescriptions = array();
    private $Results = array();
    private $DiagnosisCodes = array();
    private $OrderComment;
    private $CreatedDate;
    
    private $IsNewPatient = false;
    private $IsNewSubscriber = false;
    //private $IsAdvancedOrder = false;
    private $SubscriberChanged = false;
    
    private $Phlebotomy;

    private $IdUsers;
    public $AdminUserId;
    public $AdminTypeId;

    public $PrintESignature = false;


    public $OrderEmail;
    
    public function __construct(array $data, $includeAllFields = false) {
        parent::__construct($data);
        
        if (array_key_exists("idUsers", $data)) {
            $this->IdUsers = $data['idUsers'];
        }
        if (array_key_exists("printESignature", $data) && ($data['printESignature'] == 1 || $data['printESignature'] == true)) {
            $this->PrintESignature = true;
        }

        if (array_key_exists("adminUserId", $data) && isset($data['adminUserId'])) {
            $this->AdminUserId = $data['adminUserId'];
        }
        if (array_key_exists("adminUserId", $data) && isset($data['adminUserId'])) {
            $this->AdminTypeId = $data['adminTypeId'];
        }

        if ($includeAllFields) {
            $this->formatOrderFields($data);
            
            if (isset($data['isAdvancedOrder']) && $data['isAdvancedOrder'] == true) {
                //$this->IsAdvancedOrder = true;
                $this->Data['isAdvancedOrder'] = true;
            } else if (strtotime($data['specimenDate']) > strtotime(date("m/d/Y"))) {
                //$this->IsAdvancedOrder = true;
                $this->Data['isAdvancedOrder'] = true;
            }
            if (isset($data['startsOn']) && isset($data['frequency']) && isset($data['phlebotomist']) && (isset($data['timesToDraw']) || isset($data['continuous']))) {
                if (!empty($data['startsOn']) && !empty($data['frequency']) && !empty($data['phlebotomist'])) {
                    $this->setPhlebotomy($data);
                }                
            }
            $this->setSubscriber($data);        
            $this->setPatient($data);     
            $this->setOrderComment($data);
            // Build a result object for each POC test and insert into results or wait until the rest of the results are finished processing
            $this->setPOCResults($data); // Do processing for POC Tests
            $this->setOrderTests($data); // Do processing for regular tests that are being ordered
            $this->setDiagnosisCodes($data);
            $this->setPrescriptions($data); // Do processing for prescriptions


            $this->OrderEmail = new OrderEmail($data);
            
            if (!$this->IsNewPatient) {
                $this->Data['patientId'] = $this->Patient->idPatients;
            }
            
        }
    }

    public function setPhlebotomy(array $data) {
        // optional fields
        $idOrders = null;
        $idPhlebotomy = null;
        $timesToDraw = 0; // 'continuous' must have been checked
        $drawComment1 = $data['phlebComment1'];
        $drawComment2 = $data['phlebComment2'];
        $idAdvancedOrder = null;
        if (array_key_exists("idOrders", $data) && !empty($data['idOrders'])) {
            $idOrders = $data['idOrders'];
        }        
        if (array_key_exists("advancedId", $data) && !empty($data['advancedId'])) {
            $idAdvancedOrder = $data['advancedId'];
        } else if (array_key_exists("idAdvancedOrder", $data) && !empty($data['idAdvancedOrder'])) {
            $idAdvancedOrder = $data['idAdvancedOrder'];
        }  
        if (array_key_exists("idPhlebotomy", $data) && !empty($data['idPhlebotomy'])) {
            $idPhlebotomy = $data['idPhlebotomy'];
        } else if (array_key_exists("phlebId", $data) && !empty($data['phlebId'])) {
            $idPhlebotomy = $data['phlebId'];
        }  
        if (array_key_exists("timesToDraw", $data) && !empty($data['timesToDraw'])) {
            $timesToDraw = $data['timesToDraw'];
        }
        if (array_key_exists("phlebComment1", $data) && !empty($data['phlebComment1'])) {
            $drawComment1 = $data['phlebComment1'];
        }
        if (array_key_exists("phlebComment2", $data) && !empty($data['phlebComment2'])) {
            $drawComment2 = $data['phlebComment2'];
        }
        
        $this->Phlebotomy = new Phlebotomy(array(
            "idPhlebotomy" => $idPhlebotomy,
            "idAdvancedOrder" => $idAdvancedOrder,
            "idOrders" => $idOrders,
            "startDate" => $data['startsOn'],
            "drawCount" => $timesToDraw,
            "frequency" => $data['frequency'],
            "frequencyUnits" => null,
            "phlebotomist" => $data['phlebotomist'],
            "zone" => null,
            "drawComment1" => $drawComment1,
            "drawComment2" => $drawComment2
        ));        
    }
    
    public function addResultFromData(array $data) {
        $result = new Result($data);
        $this->Results[] = $result;
    }
    
    public function addResultFromObject(Result $result) {
        foreach ($this->Results as $currResult) {
            if ($currResult->testId == $result->panelId && $currResult->noCharge == 1) {
                $result->noCharge = 1;
            }
        }
        $this->Results[] = $result;
    }
        
    public function setOrderId($orderId) {
        foreach ($this->Results as $result) {
            $result->orderId = $orderId;
        }
        foreach ($this->Prescriptions as $prescription) {
            $prescription->orderId = $orderId;
        }
        $this->Data['idOrders'] = $orderId;
    }
    
    public function getResultIdFromTestId($testId) {
        foreach ($this->Results as $result) {
            if ($result->testId == $testId) {
                return $result->idResults;
            }
        }
        return false;
    }
    
    private function formatOrderFields($data) {
        $this->CreatedDate = date("Y-m-d h:i:s");
        
        if (!isset($data['billOnly']) || empty($data['billOnly'])) {
            $this->Data['billOnly'] = 0;
        }
        if (!isset($data['hold']) || empty($data['billOnly'])) {
            $this->Data['hold'] = 0;
        }
        $this->Data['orderDate'] = date("Y-m-d H:i:s");
        $this->Data['specimenDate'] = date("Y-m-d H:i:s", strtotime($this->Data['specimenDate']));
        
        if (isset($data['roomNumber'])) {
            $this->Data['room'] = $data['roomNumber'];
        }
        if (isset($data['bedNumber'])) {
            $this->Data['bed'] = $data['bedNumber'];
        }
    }
    
    public function setOrderComment($data) {
        if (array_key_exists("orderComment", $data)) {
            $this->OrderComment = new OrderComment(array(
                "comment" => $data['orderComment'], 
                "idorderComment" => null, 
                "advancedOrder" => $this->Data['isAdvancedOrder']
            ));
        }        
    }
    
    public function setSubscriber($data, array $settings = null) {

        if ($settings != null && array_key_exists("IsNewSubscriber", $settings)) {
                $this->IsNewSubscriber = $settings['IsNewSubscriber'];
        } else if (array_key_exists("IsNewSubscriber", $data)) {
                $this->IsNewSubscriber = $data['IsNewSubscriber'];
        } else if (!array_key_exists("idSubscriber", $data) || empty($data['idSubscriber'])) {
                $this->IsNewSubscriber = true;
        }

        $relationship = null;
        if (isset($data['relationship']) && !empty($data['relationship']) && $data['relationship'] != "self") {
            $relationship = $data['relationship'];
        } else if (isset($data['patientRelationship']) && !empty($data['patientRelationship']) && $data['patientRelationship'] != "self") {
            $relationship = $data['patientRelationship'];
        } else if (isset($this->Patient) && $this->Patient instanceof Patient && !empty($this->Patient->relationship) && $this->Patient->relationship != "self") {
            $relationship = $this->Patient->relationship;
        }

        //$sameSubscriber = true;
       // if (!array_key_exists("sameSubscriber", $data) || (array_key_exists("sameSubscriber", $data) && $data['sameSubscriber'] != 1)) { // different subscriber
        //if ((isset($data['relationship']) && $data['relationship'] != null && $data['relationship'] != "self") ||
        //        (isset($data['patientRelationship']) && $data['patientRelationship'] != null && $data['patientRelationship'] != "self") ||
        //        (isset($this->Patient) && $this->Patient->relationship != "self") || (array_key_exists("subscriberId", $data))) {
        if ($relationship != null && $relationship != "self") {
            $idSubscriber = "";
            if (isset($data['idSubscriber']) && !empty($data['idSubscriber'])) {
                $idSubscriber = $data['idSubscriber'];
            }

            $insurance = "";
            if (array_key_exists("insuranceId", $data) && isset($data['insuranceId']) && !empty($data['insuranceId'])) {
                $insurance = $data['insuranceId'];
            } else if (array_key_exists("insurance", $data) && isset($data['insurance']) && !empty($data['insurance']) && is_numeric($data['insuranceId'])) {
                $insurance = $data['insurance'];
            }

            $secondaryInsurance = "";
            if (array_key_exists("secondaryInsuranceId", $data) && isset($data['secondaryInsuranceId']) && !empty($data['secondaryInsuranceId'])) {
                $secondaryInsurance = $data['secondaryInsuranceId'];
            } else if (array_key_exists("secondaryInsurance", $data) && isset($data['secondaryInsurance']) && !empty($data['secondaryInsurance']) && is_numeric($data['secondaryInsurance'])) {
                $secondaryInsurance = $data['secondaryInsurance'];
            }

            $this->Subscriber = new Subscriber(array(
                "idSubscriber" => $idSubscriber,
                "arNo" => $data['subscriberId'],
                "lastName" => $data['subscriberLastName'],
                "firstName" => $data['subscriberFirstName'],
                "middleName" => $data['subscriberMiddleName'],
                "sex" => $data['subscriberGender'],
                "ssn" => $data['subscriberSsn'],
                "dob" => $data['subscriberDob'],
                "addressStreet" => $data['subscriberAddress1'],
                "addressStreet2" => $data['subscriberAddress2'],
                "addressCity" => $data['subscriberCity'],
                "addressState" => $data['subscriberState'],
                "addressZip" => $data['subscriberZip'],
                "phone" => $data['subscriberPhone'],
                "workPhone" => $data['subscriberWorkPhone'],
                "insurance" => $insurance,
                "secondaryInsurance" => $secondaryInsurance,
                "policyNumber" => $data['policyNumber'],
                "groupNumber" => $data['groupNumber'],
                "secondaryPolicyNumber" => $data['secondaryPolicyNumber'],
                "secondaryGroupNumber" => $data['secondaryGroupNumber'],
                "medicareNumber" => $data['medicareNumber'],
                "medicaidNumber" => $data['medicaidNumber']
            ), $settings);
            //$sameSubscriber = false;

            //$idSubscribers = 0;
            if (array_key_exists("subscriberSource", $data)) {
                if ($data['subscriberSource'] == 1) {
                    $this->Subscriber->SubscriberSource = 1; // patient selected from cssweb schema
                } else {
                    $this->Subscriber->SubscriberSource = 0; // patient selected from css schema
                }
            }
        }
        //return $sameSubscriber;
    }    
    public function setPatient(array $data, array $settings = null) {
        if ($settings != null && array_key_exists("IsNewPatient", $settings)) {
           $this->IsNewPatient = $settings['IsNewPatient'];
        } else {
            if (empty($data['idPatients'])) {
                $this->IsNewPatient = true;
            }
        }
        $subscriberId = null;
        if (array_key_exists("subscriber", $data)) {
            $subscriberId = $data['subscriber'];
        } else if (array_key_exists("patientSubscriber", $data)) {
            $subscriberId = $data['patientSubscriber'];
        }
//        if (!empty($this->Subscriber) && !$this->IsNewSubscriber) {
//            $subscriberId = $data['idSubscriber'];
//        } else if (array_key_exists("subscriber", $data)) {
//            $subscriberId = $data['subscriber'];
//        }
        $patientSmoker = 0;
        if (isset($data['patientSmoker']) && $data['patientSmoker'] == 1) {
            $patientSmoker = 1;
        }
        
        // calculate the patients height in inches
        $height = null;
        if (array_key_exists("patientHeightFeet", $data) && !empty($data['patientHeightFeet']) && is_numeric($data['patientHeightFeet'])) {
        	$height = 0;
        	$height += 12 * round($data['patientHeightFeet']);
        } 
        if (array_key_exists("patientHeightInches", $data) && !empty($data['patientHeightInches']) && is_numeric($data['patientHeightInches'])) {
        	if ($height == null || !is_numeric($height)) {
        		$height = 0;
        	}
        	$height += round($data['patientHeightInches']);
        }
        
        $weight = null;
        if (isset($data['patientWeight']) && !empty($data['patientWeight']) && is_numeric($data['patientWeight'])) {
        	$weight = round($data['patientWeight']);
        }

        $relationship = null;
        if (array_key_exists("relationship", $data) && !empty($data['relationship'])) {
            $relationship = $data['relationship'];
        } else if (array_key_exists("patientRelationship", $data) && !empty($data['patientRelationship'])) {
            $relationship = $data['patientRelationship'];
        }


        $this->Patient = new Patient(array(
            "idPatients" => $data['idPatients'],
            "arNo" => $data['patientId'],
            "lastName" => $data['patientLastName'],
            "firstName" => $data['patientFirstName'],
            "middleName" => $data['patientMiddleName'],
            "sex" => $data['patientGender'],
            "ssn" => $data['patientSsn'],
            "dob" => $data['patientDob'],
            "addressStreet" => $data['patientAddress1'],
            "addressStreet2" => $data['patientAddress2'],
            "addressCity" => $data['patientCity'],
            "addressState" => $data['patientState'],
            "addressZip" => $data['patientZip'],
            "phone" => $data['patientPhone'],
            "workPhone" => $data['patientWorkPhone'],
            "subscriber" => $subscriberId,
            "species" => $data['patientSpecies'],
            "height" => $height,
            "weight" => $weight,
            "ethnicity" => $data['patientEthnicity'],
            "smoker" => $patientSmoker,
            "relationship" => $relationship
        ), $settings);
        if (array_key_exists("patientSource", $data)) {
            if ($data['patientSource'] == 1) {
                $this->Patient->PatientSource = 1; // patient selected from cssweb schema
            } else {
                $this->Patient->PatientSource = 0; // patient selected from css schema
            }            
        }
        
    }    
    public function setPOCResults($data, array $settings = null) {
    	    	
        if ($settings != null && is_array($settings) && array_key_exists("EditOrder", $settings) && $settings['EditOrder'] == true) {
            
            foreach ($data as $row) {
                $this->Results[] = new Result($row);
            }

        } elseif (array_key_exists("choices", $data) && array_key_exists("pocPanelId", $data) && array_key_exists("pocResults", $data)) {
        	$choices = $data['choices'];
        	$panelId = $data['pocPanelId'];

            if (is_array($data['pocResults'])) {
                $userId = null;
                if (array_key_exists("id", $_SESSION) && isset($_SESSION['id']) && !empty($_SESSION['id'])) {
                    $userId = $_SESSION['id'];
                } else if (array_key_exists("idUsers", $data) && isset($data['idUsers']) && !empty($data['idUsers'])) {
                    $userId = $data['idUsers'];
                }

                foreach ($data['pocResults'] as $testId => $strIdMultiChoice) {
                    $aryIdMultiChoice = explode(":", $strIdMultiChoice);
                    $resultChoice = $aryIdMultiChoice[0];
                    $resultNo = $aryIdMultiChoice[1];

                    $resultText = $choices[$resultNo][0];
                    $isAbnormal = $choices[$resultNo][1];

                    $orderId = "";
                    if (isset($this->Data['idOrders']) && !empty($this->Data['idOrders'])) {
                        $orderId = $this->Data['idOrders'];
                    }

                    $result = new Result(array(
                        "orderId" => $orderId,
                        "testId" => $testId,
                        "panelId" => $panelId,
                        "resultNo" => $resultNo,
                        "resultText" => $resultText,
                        "resultRemark" => null,
                        "resultChoice" => $resultChoice,
                        "created" => $this->CreatedDate,
                        "reportedBy" => $userId,
                        "isAbnormal" => $isAbnormal
                    ));
                    $result->IsPOC = true;
                    $this->Results[] = $result;
                }
            }
        }
    }    
    public function setOrderTests($data, array $settings = null) {
        
        if ($settings != null && is_array($settings) && array_key_exists("EditOrder", $settings) && $settings['EditOrder'] == true) {
            
            foreach ($data as $row) {
                $result = new Result($row);
                $result->setTest(array(
                    "idtests" => $row['testId'],
                    "number" => $row['number'],
                    "testType" => $row['testType'],
                    "name" => $row['name'],
                    "idDepartment" => $row['idDepartment'],
                    "promptPOC" => $row['promptPOC'],
                    "deptName" => $row['deptName']
                ));
                $this->Results[] = $result;
            }
            
        } else if (isset($data['selectedTests'])) {

            $userId = null;
            if (array_key_exists("id", $_SESSION) && isset($_SESSION['id']) && !empty($_SESSION['id'])) {
                $userId = $_SESSION['id'];
            } else if (array_key_exists("idUsers", $data) && isset($data['idUsers']) && !empty($data['idUsers'])) {
                $userId = $data['idUsers'];
            }

            foreach ($data['selectedTests'] as $tmpTestId) {
                // 0 => testId
                // 1 => promptPOC
                // 2 => testType
                // 3 => number
                // 4 => name
                
                $aryTestId = explode("::", $tmpTestId);
                $testId = $aryTestId[0];
                $testType = $aryTestId[2];
                $number = $aryTestId[3];
                $name = $aryTestId[4];
                $orderId = "";
                if (isset($this->Data['idOrders']) && !empty($this->Data['idOrders'])) {
                    $orderId = $this->Data['idOrders'];
                }
                $result = new Result(array(
                    "orderId" => $orderId,
                    "testId" => $testId,
                    "panelId" => null,
                    "created" => $this->CreatedDate,
                    "reportedBy" => $userId,
                    "resultNo" => null,
                    "resultRemark" => null,
                    "resultChoice" => null,             
                    "resultText" => null,
                    "noCharge" => 0
                ));
                $result->setTest(array(
                    "idtests" => $testId,
                    "number" => $number,
                    "name" => $name,
                    "testType" => $testType
                ));

                $this->Results[] = $result;
            }
        }
    }
    
    public function setDiagnosisCodes(array $data) {
        if (isset($data['selectedCodes']) && count($data['selectedCodes']) > 0) {
            foreach ($data['selectedCodes'] as $value) {
                if (!array_key_exists($value, $this->DiagnosisCodes)) {
                    $code = new DiagnosisValidityCode(array(
                        "orderId" => null,
                        "diagnosisCodeId" => $value,
                        "advancedOrder" => $this->Data['isAdvancedOrder']
                    ));
                    $this->DiagnosisCodes[$value] = $code;
                }
            }
        }
        if (isset($data['selectedCommonCodes']) && count($data['selectedCommonCodes']) > 0) {
            foreach ($data['selectedCommonCodes'] as $value) {
                if (!array_key_exists("value", $this->DiagnosisCodes)) {
                    $code = new DiagnosisValidityCode(array(
                        "orderId" => null,
                        "diagnosisCodeId" => $value
                    ));
                    $this->DiagnosisCodes[$value] = $code;
                }
            }
        }
    }

    public function addDiagnosisCode(DiagnosisCode $code) {
        $validityCode = new DiagnosisValidityCode(array(
            "orderId" => null,
            "diagnosisCodeId" => $code->idDiagnosisCodes,
            "idDiagnosisCodes" => $code->idDiagnosisCodes,
            "code" => $code->code,
            "description" => $code->description,
            "version" => $code->version,
            "advancedOrder" => $this->Data['isAdvancedOrder']
        ));

        $this->DiagnosisCodes[$code->idDiagnosCodes] = $validityCode;
    }
    
    public function setPrescriptions($data) {
        if (isset($data['prescribedDrugs']) && !empty($data['prescribedDrugs'])) {
            foreach ($data['prescribedDrugs'] as $drugId) {
                $prescription = new Prescription(array(
                    "drugId" => $drugId,
                    "advancedOrder" => $this->Data['isAdvancedOrder']
                ));
                $this->Prescriptions[] = $prescription;
            }
        }
    }

    public function __isset($field) {
        
        $isset = parent::__isset($field);
        
        if (!$isset) {
            if ($field == "Results" && isset($this->Results) && is_array($this->Results) && count($this->Results) > 0) {
                $isset = true;
            }  else if ($field == "OrderComment" && isset($this->OrderComment)) {
                $isset = true;
            } else if ($field == "Prescriptions" && isset($this->Prescriptions) && is_array($this->Prescriptions) && count($this->Prescriptions) > 0) {
                $isset = true;
            } else if ($field == "DiagnosisCodes" && isset($this->DiagnosisCodes) && is_array($this->DiagnosisCodes) && count($this->DiagnosisCodes) > 0) {
                $isset = true;
            } else if ($field == "Subscriber" && isset($this->Subscriber)) {
                $isset = true;
            } else if ($field == "Patient" && isset($this->Patient)) {
                $isset = true;
            } else if ($field == "IsNewPatient" && isset($this->IsNewPatient)) {
                $isset = true;
            } else if ($field == "IsNewSubscriber" && isset($this->IsNewSubscriber)) {
                $isset = true;
            } else if ($field == "Insurance" && isset($this->Insurance)) {
                $isset = true;
            } else if ($field == "Phlebotomy" && isset($this->Phlebotomy) && $this->Phlebotomy instanceof Phlebotomy) {
                $isset = true;
            } else if ($field == "SecondaryInsurance" && isset($this->SecondaryInsurance) && $this->SecondaryInsurance instanceof Insurance) {
                $isset = true;
            }
        }
        return $isset;
    }
    public function __get($field) {
        $value = parent::__get($field);
        
        if (empty($value)) {
            if (array_key_exists($field, $this->Data)) {
                return $this->Data[$field];
            } else if ($field == "Patient") {
                return $this->Patient;
            } else if ($field == "Subscriber") {
                return $this->Subscriber;
            } else if ($field == "Results") {
                return $this->Results;
            } else if ($field == "Prescriptions") {
                return $this->Prescriptions;
            } else if ($field == "OrderComment") {
                return $this->OrderComment;
            } else if ($field == "IsNewPatient") {
                return $this->IsNewPatient;
            } else if ($field == "IsNewSubscriber") {
                return $this->IsNewSubscriber;
            } else if ($field == "DiagnosisCodes") {
                return $this->DiagnosisCodes;
            } else if ($field == "Insurance") {
                return $this->Insurance;
            //} else if ($field == "IsAdvancedOrder") {
            //    return $this->IsAdvancedOrder;
            } else if ($field == "Phlebotomy") {
                return $this->Phlebotomy;
            } else if ($field == "IdUsers") {
                return $this->IdUsers;
            } else if ($field == "SubscriberChanged") {
                return $this->SubscriberChanged;
            } else if ($field == "SecondaryInsurance") {
                return $this->SecondaryInsurance;
            }
        }
        
        return $value;
    }
    
    public function __set($field, $value) {
        $done = parent::__set($field, $value);
        if (!$done) {
            if ($field == "Insurance") {
                $this->Insurance = $value;
                $done = true;
            } else if ($field == "Phlebotomy" && isset($value)) { // $value must be of type Phlebotomy
                $this->Phlebotomy = $value;
            } else if ($field == "Patient" && $value instanceof Patient) {
                $this->Patient = $value;
            } else if ($field == "IsNewPatient" && is_bool($value)) {
                $this->IsNewPatient = $value;
            } else if ($field == "IsNewSubscriber" && is_bool($value)) {
                $this->IsNewSubscriber = $value;
            } else if ($field == "Subscriber" && $value instanceof Subscriber) {                
                $this->Subscriber = $value;
            } else if ($field == "Results" && is_array($value) && count($value) > 0) {
                $this->Results = $value;
            } else if ($field == "Prescriptions" && is_array($value) && count($value) > 0) {
                $this->Prescriptions = $value;
            } else if ($field == "DiagnosisCodes" && is_array($value) && count($value) > 0) {
                $this->DiagnosisCodes = $value;
            } else if ($field == "OrderComment" && $value instanceof OrderComment) {
                $this->OrderComment = $value;
            } else if ($field == "idUsers") {
                $this->IdUsers = $value;
            } else if ($field == "SubscriberChanged") {
                $this->SubscriberChanged = $value;
            } else if ($field == "SecondaryInsurance" && $value instanceof Insurance) {
                $this->SecondaryInsurance = $value;
            }
        }
        return $done;        
    }
}

?>