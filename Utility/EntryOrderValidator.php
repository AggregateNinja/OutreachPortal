<?php
if (!isset($_SESSION)) {
    session_start();
}
require_once 'FormValidator.php';
require_once 'DAOS/EntryOrderDAO.php';
require_once 'DAOS/PreferencesDAO.php';
require_once 'DAOS/PatientDAO.php';
require_once 'DAOS/SubscriberDAO.php';
require_once 'DAOS/TestDAO.php';
require_once 'Utility/IConfig.php';

class EntryOrderValidator extends FormValidator implements IConfig  {
    
    protected $InputFields = array (
        // ------------------- order inputs
        "action" => "",
        "idOrders" => "",
        "isAdvancedOrder" => "",
        "typeId" => "",
        "hasPMByDepartment" => "",
        "accession" => "", 
        "clientId" => 0,
        "doctorId" => 0,
        "reportType" => "",
        "specimenDate" => "",
        "orderDate" => "", 
        "roomNumber" => "",
        "bedNumber" => "",
        "orderComment" => "",
        "locationId" => 0,
        // ------------------- patient inputs
        "relationship" => "self", // same as patientRelationship but it is only submitted when a new patient is entered
        "idPatients" => "", 
        "patientLastName" => "", 
        "patientFirstName" => "", 
        "patientMiddleName" => "", 
        "patientId" => "", 
        "patientDob" => "", 
        "patientGender" => "",
        "patientSpecies" => "",
        "patientEthnicity" => "",
        "patientSsn" => "", 
        "patientAge" => "", 
        "patientHeightFeet" => "", 
        "patientHeightInches" => "", 
        "patientWeight" => "", 
        "patientAddress1" => "", 
        "patientAddress2" => "", 
        "patientCity" => "", 
        "patientState" => "", 
        "patientZip" => "", 
        "patientPhone" => "", 
        "patientWorkPhone" => "", 
        "patientSmoker" => "",
        "patientSource" => "",
        "patientSubscriber" => "",
        // ------------------- subscriber inputs
        "idSubscriber" => "", 
        "subscriberLastName" => "", 
        "subscriberFirstName" => "", 
        "subscriberMiddleName" => "", 
        "subscriberId" => "", 
        "subscriberDob" => "", 
        "subscriberAge" => "", 
        "subscriberGender" => "",
        "subscriberSsn" => "", 
        "subscriberAddress1" => "", 
        "subscriberAddress2" => "", 
        "subscriberCity" => "", 
        "subscriberState" => "", 
        "subscriberZip" => "", 
        "subscriberSource" => "",
        // ------------------- insurance inputs
        "insuranceId" => "",
        "insurance" => "",
        "secondaryInsurance" => 0,
        "secondaryInsuranceId" => "",
        "policyNumber" => "", 
        "secondaryPolicyNumber" => "", 
        "groupNumber" => "", 
        "secondaryGroupNumber" => "", 
        "medicareNumber" => "", 
        "medicaidNumber" => "", 
        // ------------------- phlebotomy inputs
        "idPhlebotomy" => "",
        "idAdvancedOrder" => "",        
        "frequency" => "",
        "timesToDraw" => "",
        "continuous" => "",
        "startsOn" => "",
        "phlebotomist" => "",
        "phlebComment1" => "",
        "phlebComment2" => "",
        // ------------------- array variables
        "selectedTests" => "",
        "pocResults" => "",
        "prescribedDrugs" => "",
        "selectedCodes" => array(),
        "selectedCommonCodes" => array(),
        // ------------------- other
        "advancedOrderOnly" => false,
        "isNewPatient" => true,
        "isNewSubscriber" => true,
        "doctorRequired" => true,
        "UserLocationId" => 0,
        "OverlappingPanelsEnabled" => false
    );
    private $AdditionalRequiredFields = array();
    private $IsValid = true;
    private $InvalidFields = array();
    private $ErrorMessages = array();
    
    public function __construct(array $data) {
        //echo "<pre>"; print_r($data); echo "</pre>";
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->InputFields) && !strpos($key, "--submit")) {    
                $this->InputFields[$key] = $value;
            } else if ($key == "patientRelationship" && $value != null && !empty($value)) {
                $this->InputFields['relationship'] = $value;
            }
        }

        if (array_key_exists("additionalRequiredFields", $data)) {
            $this->AdditionalRequiredFields = $data['additionalRequiredFields'];
        }
        if (array_key_exists("overlappingPanelsEnabled", $data) && ($data['overlappingPanelsEnabled'] == '1' || $data['overlappingPanelsEnabled'] == true)) {
            $this->InputFields['OverlappingPanelsEnabled'] = true;
        }
    }
    
    public function validate() {

        $receiptedOrderData = EntryOrderDAO::getReceiptedOrderInfo($this->InputFields['accession']);
        
        if (count($receiptedOrderData) > 0 && $receiptedOrderData[0]['idOrders'] != null && $receiptedOrderData[0]['receiptedDate'] != null && !empty($receiptedOrderData[0]['receiptedDate'])) {
            $this->IsValid = false;
            $dateReceipted = date("m/d/Y", strtotime($receiptedOrderData[0]['receiptedDate']));
            $timeReceipted = date("h:i:s A", strtotime($receiptedOrderData[0]['receiptedDate']));
            $this->ErrorMessages['IsReceipted'] = "
                Accession " . $this->InputFields['accession'] . " was receipted on $dateReceipted at $timeReceipted and can no longer be modified. <br/>
                Please contact the lab if further modifications must be made.
            ";
            
        } else {        
            $this->validateOrderInformation();
            $this->validatePatient();
            $this->validateSubscriber();
            $this->validateInsurance();
            $this->validateTests();
        }

        //echo "<pre>"; print_r($this->ErrorMessages); echo "</pre>";

        return $this->IsValid;
    }
    
    public function __get($key) {
        if ($key == "ErrorMessages") {
            return $this->ErrorMessages;
        }
        return "";
    }
    
    private function validateOrderInformation() {
        if (empty($this->InputFields['accession'])) {
            $this->IsValid = false;
            $this->ErrorMessages['accession'] = "Accession is required";
        } else if (!EntryOrderDAO::isUniqueAccession($this->InputFields['accession']) && $this->InputFields['action'] != "edit") {
            $this->IsValid = false;
            $this->ErrorMessages['accession'] = "Accession already exists";
        }

        if ($this->InputFields['clientId'] == 0) {
            $this->IsValid = false;
            $this->ErrorMessages['clientId'] = "Client must be selected";
        }
        if ($this->InputFields['doctorId'] == 0 && $this->InputFields['doctorRequired'] == true) {
            $this->IsValid = false;
            $this->ErrorMessages['doctorId'] = "Doctor must be selected";
        }
        if ($this->InputFields['locationId'] == null || empty($this->InputFields['locationId']) || $this->InputFields['locationId'] == 0) {
            $this->IsValid = false;
            $this->ErrorMessages['locationId'] = "Location must be selected";
        }

        if (empty($this->InputFields['specimenDate'])) {
            $this->IsValid = false;
            $this->ErrorMessages['specimenDate'] = "Collection date must be selected";
        } else if (!parent::isValidDate(array($this->InputFields['specimenDate']), 'm/d/Y h:i A') && !parent::isValidDate(array($this->InputFields['specimenDate']), 'm/d/Y')) {
            $this->IsValid = false;
            $this->ErrorMessages['specimenDate'] = "Invalid date format";
        }

        if (empty($this->InputFields['roomNumber']) && in_array("roomNumber", $this->AdditionalRequiredFields)) {
            $this->IsValid = false;
            $this->ErrorMessages['roomNumber'] = "Room number required";
        }
        if (empty($this->InputFields['bedNumber']) && in_array("bedNumber", $this->AdditionalRequiredFields)) {
            $this->IsValid = false;
            $this->ErrorMessages['bedNumber'] = "Bed number required";
        }

        if (in_array("orderComment", $this->AdditionalRequiredFields) && empty($this->InputFields['orderComment'])) {
            $this->IsValid = false;
            $this->ErrorMessages['orderComment'] = "Order comment required";
        }

        if (in_array("prescriptions", $this->AdditionalRequiredFields) && empty($this->InputFields['prescribedDrugs'])) {
            $this->IsValid = false;
            $this->ErrorMessages['prescriptions'] = "At least one prescription must be selected";
        }
    }
    
    private function validatePatient() {
        if (empty($this->InputFields['patientFirstName'])) {
            $this->IsValid = false;
            $this->ErrorMessages['patientFirstName'] = "First name is required";
        }
        if (empty($this->InputFields['patientLastName'])) {
            $this->IsValid = false;
            $this->ErrorMessages['patientLastName'] = "Last name is required";
        }

        if (empty($this->InputFields['patientId'])) {
            $this->IsValid = false;
            $this->ErrorMessages['patientId'] = "Id is required";
        } else if (($this->InputFields['idPatients'] == null || empty($this->InputFields['idPatients'])) && PatientDAO::patientArNoExists($this->InputFields['patientId']) == true) {
            $this->IsValid = false;
            $this->ErrorMessages['patientId'] = "Id already exists";
        }
        if($this->InputFields['patientDob'] == null || empty($this->InputFields['patientDob'])) {
            $this->IsValid = false;
            $this->ErrorMessages['patientDob'] = "Date of birth is required";
        } else if (!parent::isValidDate(array($this->InputFields['patientDob']), 'n/j/Y') && !parent::isValidDate(array($this->InputFields['patientDob']), 'm/d/Y')) {
            $this->IsValid = false;
            $this->ErrorMessages['patientDob'] = "Invalid date format";
        }

        if (empty($this->InputFields['idPatients'])) { // this is a new patient

            //if (!is_numeric($this->InputFields['patientId'])) {
            if (preg_match('/[^A-Za-z0-9]/', $this->InputFields['patientId'])) {
                $this->IsValid = false;
                $this->ErrorMessages['patientId'] = "Id must be alphanumeric";
            }

            if (in_array("patientMiddleName", $this->AdditionalRequiredFields) && empty($this->InputFields['patientMiddleName'])) {
                $this->IsValid = false;
                $this->ErrorMessages['patientMiddleName'] = "Middle name is required";
            }
            if (in_array("patientGender", $this->AdditionalRequiredFields) && $this->InputFields['patientGender'] === 'Unknown') {
                $this->IsValid = false;
                $this->ErrorMessages['patientGender'] = "Gender is required";
            }
            if (in_array("patientEthnicity", $this->AdditionalRequiredFields) && $this->InputFields['patientEthnicity'] === 'Other') {
                $this->IsValid = false;
                $this->ErrorMessages['patientEthnicity'] = "Ethnicity is required";
            }
            if (in_array("patientSsn", $this->AdditionalRequiredFields)) {
                if (empty($this->InputFields['patientSsn'])) {
                    $this->IsValid = false;
                    $this->ErrorMessages['patientSsn'] = "SSN is required";
                } else if (!parent::isValidSsn($this->InputFields['patientSsn'])) {
                    $this->IsValid = false;
                    $this->ErrorMessages['patientSsn'] = "SSN must be formatted ###-##-####";
                }
            }
            if (array_intersect(array("patientHeightFeet", "patientHeightInches", "patientHeight"), $this->AdditionalRequiredFields)
                && (
                    (empty($this->InputFields['patientHeightFeet']) && empty($this->InputFields['patientHeightInches']))
                    || ($this->InputFields['patientHeightFeet'] == 0 && $this->InputFields['patientHeightInches'] == 0)
                )) {
                $this->IsValid = false;
                $this->ErrorMessages['patientHeight'] = "Height is required";
            }
            if (in_array("patientWeight", $this->AdditionalRequiredFields) && (empty($this->InputFields['patientWeight']) || $this->InputFields['patientWeight'] == 0)) {
                $this->IsValid = false;
                $this->ErrorMessages['patientWeight'] = "Weight is required";
            }
            if (in_array("patientAddress1", $this->AdditionalRequiredFields) && empty($this->InputFields['patientAddress1'])) {
                $this->IsValid = false;
                $this->ErrorMessages['patientAddress1'] = "Address is required";
            }
            if (in_array("patientAddress2", $this->AdditionalRequiredFields) && empty($this->InputFields['patientAddress2'])) {
                $this->IsValid = false;
                $this->ErrorMessages['patientAddress2'] = "Second address is required";
            }
            if (in_array("patientCity", $this->AdditionalRequiredFields) && empty($this->InputFields['patientCity'])) {
                $this->IsValid = false;
                $this->ErrorMessages['patientCity'] = "City is required";
            }
            if (in_array("patientState", $this->AdditionalRequiredFields) && empty($this->InputFields['patientState'])) {
                $this->IsValid = false;
                $this->ErrorMessages['patientState'] = "State is required";
            }
            if (in_array("patientZip", $this->AdditionalRequiredFields) && empty($this->InputFields['patientZip'])) {
                $this->IsValid = false;
                $this->ErrorMessages['patientZip'] = "Zip is required";
            }
            if (in_array("patientCityStateZip", $this->AdditionalRequiredFields) &&
                (
                    empty($this->InputFields['patientCity'])
                    || empty($this->InputFields['patientState'])
                    || empty($this->InputFields['patientZip'])
                )) {
                $this->IsValid = false;
                $this->ErrorMessages['patientCityStateZip'] = "City, state, and zip are required";
            }
            if (in_array("patientPhone", $this->AdditionalRequiredFields) && empty($this->InputFields['patientPhone'])) {
                $this->IsValid = false;
                $this->ErrorMessages['patientPhone'] = "Phone number is required";
            }
            if (in_array("patientWorkPhone", $this->AdditionalRequiredFields) && empty($this->InputFields['patientWorkPhone'])) {
                $this->IsValid = false;
                $this->ErrorMessages['patientWorkPhone'] = "Work number is required";
            }
        }
    }

    private function validateSubscriber() {
        //if (($this->InputFields['patientRelationship'] != "self" && $this->InputFields['action'] == "edit") || ($this->InputFields['relationship'] != "self" && $this->InputFields['action'] == "add")) {
        if (strtolower($this->InputFields['relationship']) != "self") {
            //echo "<pre>"; print_r($this->InputFields); echo "</pre>";
            if (empty($this->InputFields['subscriberFirstName'])) {
                $this->IsValid = false;
                $this->ErrorMessages['subscriberFirstName'] = "First name is required";
            }
            if (empty($this->InputFields['subscriberLastName'])) {
                $this->IsValid = false;
                $this->ErrorMessages['subscriberLastName'] = "Last name is required";
            }
            if (empty($this->InputFields['subscriberId'])) {
                $this->IsValid = false;
                $this->ErrorMessages['subscriberId'] = "Id is required";
            } else if (($this->InputFields['idSubscriber'] == null || empty($this->InputFields['idSubscriber'])) && SubscriberDAO::subscriberArNoExists($this->InputFields['subscriberId']) == true) {
                $this->IsValid = false;
                $this->ErrorMessages['subscriberId'] = "Id already exists";
                //} else if (!is_numeric($this->InputFields['subscriberId'])) {
            } else if (preg_match('/[^A-Za-z0-9]/', $this->InputFields['subscriberId'])) {
                $this->IsValid = false;
                $this->ErrorMessages['subscriberId'] = "Id must be alphanumeric";
            }
            if($this->InputFields['subscriberDob'] == null || empty($this->InputFields['subscriberDob'])) {
                $this->IsValid = false;
                $this->ErrorMessages['subscriberDob'] = "Date of birth is required";
            } else if (!parent::isValidDate(array($this->InputFields['subscriberDob']))) {
                $this->IsValid = false;
                $this->ErrorMessages['subscriberDob'] = "Invalid date format";
            }

            if (empty($this->InputFields['idSubscriber'])) {
                if (in_array("subscriberMiddleName", $this->AdditionalRequiredFields) && empty($this->InputFields['subscriberMiddleName'])) {
                    $this->IsValid = false;
                    $this->ErrorMessages['subscriberMiddleName'] = "Middle name is required";
                }
                if (in_array("subscriberGender", $this->AdditionalRequiredFields) && $this->InputFields['subscriberGender'] == 'Unknown') {
                    $this->IsValid = false;
                    $this->ErrorMessages['subscriberGender'] = "Gender is required";
                }
                if (in_array("subscriberSsn", $this->AdditionalRequiredFields)) {
                    if (empty($this->InputFields['subscriberSsn'])) {
                        $this->IsValid = false;
                        $this->ErrorMessages['subscriberSsn'] = "SSN is required";
                    } else if (!parent::isValidSsn($this->InputFields['subscriberSsn'])) {
                        $this->IsValid = false;
                        $this->ErrorMessages['subscriberSsn'] = "SSN must be formatted ###-##-####";
                    }
                }
                if (in_array("subscriberAddress1", $this->AdditionalRequiredFields) && empty($this->InputFields['subscriberAddress1'])) {
                    $this->IsValid = false;
                    $this->ErrorMessages['subscriberAddress1'] = "Address is required";
                }
                if (in_array("subscriberAddress2", $this->AdditionalRequiredFields) && empty($this->InputFields['subscriberAddress2'])) {
                    $this->IsValid = false;
                    $this->ErrorMessages['subscriberAddress2'] = "Second address is required";
                }
                if (in_array("subscriberCity", $this->AdditionalRequiredFields) && empty($this->InputFields['subscriberCity'])) {
                    $this->IsValid = false;
                    $this->ErrorMessages['subscriberCity'] = "City is required";
                }
                if (in_array("subscriberState", $this->AdditionalRequiredFields) && empty($this->InputFields['subscriberState'])) {
                    $this->IsValid = false;
                    $this->ErrorMessages['subscriberState'] = "State is required";
                }
                if (in_array("subscriberZip", $this->AdditionalRequiredFields) && empty($this->InputFields['subscriberZip'])) {
                    $this->IsValid = false;
                    $this->ErrorMessages['subscriberZip'] = "Zip is required";
                }
                if (in_array("subscriberCityStateZip", $this->AdditionalRequiredFields)
                    &&(
                        empty($this->InputFields['subscriberCity'])
                        || empty($this->InputFields['subscriberState'])
                        || empty($this->InputFields['subscriberZip'])
                    )) {
                    $this->IsValid = false;
                    $this->ErrorMessages['subscriberCityStateZip'] = "City, state, and zip are required";
                }
                if (in_array("subscriberPhone", $this->AdditionalRequiredFields) && empty($this->InputFields['subscriberPhone'])) {
                    $this->IsValid = false;
                    $this->ErrorMessages['subscriberPhone'] = "Phone number is required";
                }
                if (in_array("subscriberWorkPhone", $this->AdditionalRequiredFields) && empty($this->InputFields['subscriberWorkPhone'])) {
                    $this->IsValid = false;
                    $this->ErrorMessages['subscriberWorkPhone'] = "Work number is required";
                }
            }
        }
    }

    private function validateInsurance() {
        if((strtolower($this->InputFields['relationship']) != "self" && PreferencesDAO::billingIsActive() == true) || in_array("insurance", $this->AdditionalRequiredFields)) {
            if($this->InputFields['insuranceId'] == 0 || empty($this->InputFields['insuranceId'])) {
                $this->IsValid = false;
                $this->ErrorMessages['insurance'] = "Primary insurance required";
            }
        }
        if (in_array("secondaryInsuranceId", $this->AdditionalRequiredFields) && empty($this->InputFields['secondaryInsuranceId'])) {
            $this->IsValid = false;
            $this->ErrorMessages['secondaryInsurance'] = "Secondary insurance required";
        }
        if (in_array("policyNumber", $this->AdditionalRequiredFields) && empty($this->InputFields['policyNumber'])) {
            $this->IsValid = false;
            $this->ErrorMessages['policyNumber'] = "Policy number required";
        }
        if (in_array("secondaryPolicyNumber", $this->AdditionalRequiredFields) && empty($this->InputFields['secondaryPolicyNumber'])) {
            $this->IsValid = false;
            $this->ErrorMessages['secondaryPolicyNumber'] = "Secondary policy number required";
        }
        if (in_array("groupNumber", $this->AdditionalRequiredFields) && empty($this->InputFields['groupNumber'])) {
            $this->IsValid = false;
            $this->ErrorMessages['groupNumber'] = "Group number required";
        }
        if (in_array("secondaryGroupNumber", $this->AdditionalRequiredFields) && empty($this->InputFields['secondaryGroupNumber'])) {
            $this->IsValid = false;
            $this->ErrorMessages['secondaryGroupNumber'] = "Secondary group number required";
        }
        if (in_array("medicareNumber", $this->AdditionalRequiredFields) && empty($this->InputFields['medicareNumber'])) {
            $this->IsValid = false;
            $this->ErrorMessages['medicareNumber'] = "Medicare number required";
        }
        if (in_array("medicaidNumber", $this->AdditionalRequiredFields) && empty($this->InputFields['medicaidNumber'])) {
            $this->IsValid = false;
            $this->ErrorMessages['medicaidNumber'] = "Medicaid number required";
        }
    }

    private function validateTests() {
        $selectedTests = $this->InputFields['selectedTests'];

        if ($selectedTests == null && !is_array($selectedTests) || count($selectedTests) == 0) {
            $this->IsValid = false;
            $this->ErrorMessages['selectedTests'] = "At least one test must be selected";
        } else if ($this->InputFields['OverlappingPanelsEnabled'] == false) {
            // make sure there are no dupes
            // build an array of selected test numbers
            $arySelectedTestNums = array();


            $arySettings = array();
            if (self::HasMultiLocation == true && isset($this->InputFields['UserLocationId']) && !empty($this->InputFields['UserLocationId']) && $this->InputFields['UserLocationId'] != 0) {
                $arySettings['UserLocationId'] = $this->InputFields['UserLocationId'];
            }

            foreach ($selectedTests as $currTest) {
                $aryTestInfo = explode("::", $currTest);
                $testId = $aryTestInfo[0];
                $testType = $aryTestInfo[2];
                $testNumber = $aryTestInfo[3];
                $arySelectedTestNums[] = $testNumber;

                if ($testType == 0) {
                    // get the tests within the panel
                    $panelTests = TestDAO::getPanelTestsByNumber($testNumber, $arySettings);
                    if ($panelTests != null) {
                        foreach ($panelTests as $currPanelTest) {
                            $arySelectedTestNums[] = $currPanelTest->number;
                            if ($currPanelTest->testType == 0) {
                                // its a panel within a battery, so get its panel tests
                                $panelTests2 = TestDAO::getPanelTestsByNumber($currPanelTest->number, $arySettings);
                                if ($panelTests2 != null) {
                                    foreach($panelTests2 as $currPanelTest2) {
                                        $arySelectedTestNums[] = $currPanelTest2->number;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            //http://stackoverflow.com/a/3297076
            if (count(array_unique($arySelectedTestNums)) < count($arySelectedTestNums)) {
                // there are dupelicate tests
                $this->IsValid = false;
                $this->ErrorMessages['selectedTests'] = "Duplicate tests are selected";
            }
        }

        if (in_array("diagnosisCodes", $this->AdditionalRequiredFields)
            && (!array_key_exists('selectedCodes', $this->InputFields) || count($this->InputFields['selectedCodes']) == 0)
            && (!array_key_exists('selectedCommonCodes', $this->InputFields) || count($this->InputFields['selectedCommonCodes']) == 0)) {
            $this->IsValid = false;
            $this->ErrorMessages['diagnosisCodes'] = "At least one diagnosis code must be selected";
        }
    }
}
?>
