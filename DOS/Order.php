<?php
require_once 'BaseObject.php';
require_once 'Patient.php';
require_once 'DoctorUser.php';
require_once 'ClientUser.php';

/**
 * Description of ResultOrder
 *
 * @author Edd
 */
class Order extends BaseObject{
//    protected $Data = array(
//        "idOrders" => "",
//        "accession" => "",
//        "number" => "",
//        "clientNo" => "",
//        "firstName" => "",
//        "lastName" => "",
//        "orderDate" => "",
//        "idPatients" => "",
//        "isInvalidated" => "",
//        "invalidatedDate" => "",
//        "invalidatedBy" => "",
//        "active" => "",
//        "reportType" => ""
//    );
    protected $Data = array (
        "idOrders" => "",
        "doctorId" => "",
        "clientId" => "",
        "accession" => "",
        "locationId" => "",
        "orderDate" => "",
        "specimenDate" => "",
        "patientId" => "",
        "subscriberId" => "",
        "isAdvancedOrder" => false,
        "phlebotomyId" => "",
        "insurance" => null,
        "secondaryInsurance" => null,
        "policyNumber" => "",
        "groupNumber" => "",
        "secondaryPolicyNumber" => "",
        "secondaryGroupNumber" => "",
        "medicareNumber" => "",
        "medicaidNumber" => "",
        "reportType" => "",
        "requisition" => "",
        "billOnly" => "",
        "active" => "",
        "hold" => "",
        "stage" => "",
        "holdComment" => "",
        "resultComment" => "",
        "internalComment" => "",
        "room" => "",
        "bed" => "",

        // used for sorting
        "firstName" => "",
        "lastName" => "",
        "clientNo" => "",
        "number" => "",

        // used for printing invalidated orders on the found page
        "IsInvalidated" => "",
        "IsAbnormal" => "",
        "OrderStatus" => null,

        // used to display the temp web accession on receipted web orders
        "webAccession" => "",
        "webOrderId" => "",

        "isFasting" => false
    );

    protected $Patient;
    protected $Doctor;
    protected $Client;

    private $IsReceipted;
    private $DateReceipted;
    private $TimeReceipted;

    private $OrderCount = 0;
    private $ReportedCount = 0;
    private $ApprovedCount = 0;
    private $PrintAndTransmittedCount = 0;
    private $InvalidatedCount = 0;
    private $InconsistentCount = 0; // Prescribed not detected/Not prescribed detected
    private $AbnormalCount = 0; // Result is outside the test's normal range

    private $PrescribedDrugs = array();
    private $PrescribedDetected = array();
    private $PrescribedNotDetected = array();
    private $NotPrescribedDetected = array();

    public function __construct(array $data) {
        parent::__construct($data);

        if (array_key_exists("IsReceipted", $data)) {
            $this->IsReceipted = $data['IsReceipted'];

            if (array_key_exists("receiptedDate", $data) && $data['receiptedDate'] != null && parent::isValidDate($data['receiptedDate'], 'Y-m-d H:i:s')) {
                $this->DateReceipted = date("m/d/Y", strtotime($data['receiptedDate']));
                $this->TimeReceipted = date("h:i A", strtotime($data['receiptedDate']));
            } else {
                $this->DateReceipted = null;
                $this->TimeReceipted = null;
            }
        } else if (array_key_exists("IsWeb", $data)) {

            if ($data['IsWeb'] == 1) {
                $this->Data['stage'] = 0;
                $this->Data['OrderStatus'] = "Web Entered";
            } else {
                if (array_key_exists("stage", $data)) {
                    if ($this->Data['stage'] == 1) {
                        $this->Data['OrderStatus'] = "Avalon Entered";
                    } else if ($this->Data['stage'] == 77) {
                        $this->Data['OrderStatus'] = "Complete";
                    }

                }
            }
        } else if (array_key_exists("stage", $data)) {
            if ($this->Data['stage'] == 0) {
                $this->Data['OrderStatus'] = "Web Entered";
            } else if ($this->Data['stage'] == 1) {
                $this->Data['OrderStatus'] = "Avalon Entered";
            } else if ($this->Data['stage'] == 77) {
                $this->Data['OrderStatus'] = "Complete";
            }
        } else {
            $this->IsReceipted = false;
            $this->DateReceipted = null;
            $this->TimeReceipted = null;
        }

        if (array_key_exists("insuranceId", $data) && isset($data['insuranceId']) && !empty($data['insuranceId']) && $data['insuranceId'] != null && $data['insuranceId'] != "" && $data['insuranceId'] != 0) {
            $this->Data['insurance'] = $data['insuranceId'];
        } else if (array_key_exists("insurance", $data) && isset($data['insurance']) && !empty($data['insurance']) && $data['insurance'] != null && $data['insurance'] != "" && $data['insurance'] != 0) {
            $this->Data['insurance'] = $data['insurance'];
        } else {
            $this->Data['insurance'] = null;
        }

        if (array_key_exists("secondaryInsuranceId", $data) && isset($data['secondaryInsuranceId']) && !empty($data['secondaryInsuranceId'])
            && $data['secondaryInsuranceId'] != null && $data['secondaryInsuranceId'] != "" && $data['secondaryInsuranceId'] != 0) {
            $this->Data['secondaryInsurance'] = $data['secondaryInsuranceId'];
        } else if (array_key_exists("secondaryInsurance", $data) && isset($data['secondaryInsurance']) && !empty($data['secondaryInsurance'])
            && $data['secondaryInsurance'] != null && $data['secondaryInsurance'] != "" && $data['secondaryInsurance'] != 0) {
            $this->Data['secondaryInsurance'] = $data['secondaryInsurance'];
        } else {
            $this->Data['secondaryInsurance'] = null;
        }

        if (array_key_exists("doctorNumber", $data) || array_key_exists("number", $data)) {
            $doctorId = "";
            if (array_key_exists("iddoctors", $data)) {
                $doctorId = $data['iddoctors'];
            } else if (array_key_exists("doctorId", $data)) {
                $doctorId = $data['doctorId'];
            }

            $doctorNumber = "";
            if (array_key_exists("doctorNumber", $data)) {
                $doctorNumber = $data['doctorNumber'];
            } else if (array_key_exists("number", $data)) {
                $doctorNumber = $data['number'];
            }

            $this->Doctor = new DoctorUser(array("iddoctors" => $doctorId, "number" => $doctorNumber));

            if (array_key_exists("doctorFirstName", $data)) {
                $this->Doctor->firstName = $data['doctorFirstName'];
            }
            if (array_key_exists("doctorLastName", $data)) {
                $this->Doctor->lastName = $data['doctorLastName'];
            }
        }

        if (array_key_exists("doctorId", $data) && $data['doctorId'] == 0) {
            $this->Data['doctorId'] = null;
        }

        if (array_key_exists("clientNo", $data)) {
            $this->Client = new ClientUser(array("clientNo" => $data['clientNo']));
            if (array_key_exists("clientName", $data)) {
                $this->Client->clientName = $data['clientName'];
            }
            if (array_key_exists("idClients", $data)) {
                $this->Client->idClients = $data['idClients'];
            }
        }
        if ((array_key_exists("firstName", $data) || array_key_exists("patientFirstName", $data)) &&
            (array_key_exists("lastName", $data) || array_key_exists("patientLastName", $data)) && array_key_exists("idPatients", $data)) {
            $this->Patient = new Patient($data);

        }
        if (array_key_exists("idSubscriber", $data)) {
        	$this->Data['subscriberId'] = $data['idSubscriber'];
        } else if (array_key_exists("subscriber", $data)) {
            $this->Data['subscriberId'] = $data['subscriber'];
        }
        if (array_key_exists("idPatients", $data)) {
        	$this->Data['patientId'] = $data['idPatients'];
        }

        if (array_key_exists("orderCount", $data)) {
            $this->OrderCount = $data['orderCount'];
        }
        if (array_key_exists("reportedCount", $data)) {
            $this->ReportedCount = $data['reportedCount'];
        }
        if (array_key_exists("approvedCount", $data)) {
            $this->ApprovedCount = $data['approvedCount'];
        }
        if (array_key_exists("printAndTransmittedCount", $data)) {
            $this->PrintAndTransmittedCount = $data['printAndTransmittedCount'];
        }
        if (array_key_exists("invalidatedCount", $data)) {
            $this->InvalidatedCount = $data['invalidatedCount'];
        }
        if (array_key_exists("inconsistentCount", $data)) {
            $this->InconsistentCount = $data['inconsistentCount'];
        }
        if (array_key_exists("abnormalCount", $data)) {
            $this->AbnormalCount = $data['abnormalCount'];
        }

        if (array_key_exists("PrescribedDrugs", $data)) {
            $this->PrescribedDrugs = $data['PrescribedDrugs'];
        }
        if (array_key_exists("PrescribedDetected", $data)) {
            $this->PrescribedDetected = $data['PrescribedDetected'];
        }
        if (array_key_exists("PrescribedNotDetected", $data)) {
            $this->PrescribedNotDetected = $data['PrescribedNotDetected'];
        }
        if (array_key_exists("NotPrescribedDetected", $data)) {
            $this->NotPrescribedDetected = $data['NotPrescribedDetected'];
        }

        if (array_key_exists("secondaryInsurance", $data) && (
                empty($data['secondaryInsurance'])
                || $data['secondaryInsurance'] == ""
                || $data['secondaryInsurance'] == 0
            )) {
            $this->Data['secondaryInsurance'] = null;
        }

        if (array_key_exists("isFasting", $data) && $data['isFasting'] == 1) {
            $this->Data['isFasting'] = true;
        }
    }

    public function __isset($field) {
        $isset = parent::__isset($field);

        if (!$isset) {
            if ($field == "Patient" && isset($this->Patient) && $this->Patient instanceof Patient) {
                $isset = true;
            } else if ($field == "Doctor" && isset($this->Doctor) && $this->Doctor instanceof DoctorUser) {// && !empty($this->Doctor->firstName)) {
                $isset = true;
            } else if ($field == "Client" && isset($this->Client) && $this->Client instanceof ClientUser) {
                $isset = true;
            }
        }

        return $isset;

    }

    public function __set($field, $value) {
        if (array_key_exists($field, $this->Data)) {
            $this->Data[$field] = $value;
            return true;
        } else if ($field == "Client") {
            $this->Client = $value;
            return true;
        } else if ($field == "Doctor") {
        	if ($value instanceof DoctorUser) {
            	$this->Doctor = $value;
            	return true;
        	} else if (is_array($value)) {
        		$this->Doctor = new DoctorUser($value);
        	}
        } else if ($field == "Patient") {
        	if ($value instanceof Patient) {
        		$this->Patient = $value;
        		return true;
        	} else if (is_array($value)) {
        		$this->Patient = new Patient($value);
        		return true;
        	}

        } else if ($field == "OrderCount") {
            $this->OrderCount = $value;
            return true;
        } else if ($field == "ReportedCount") {
            $this->ReportedCount = $value;
            return true;
        } else if ($field == "ApprovedCount") {
            $this->ApprovedCount = $value;
            return true;
        } else if ($field == "PrintAndTransmittedCount") {
            $this->PrintAndTransmittedCount = $value;
            return true;
        } else if ($field == "InvalidatedCount") {
            $this->InvalidatedCount = $value;
            return true;
        } else if ($field == "InconsistentCount") {
            $this->InconsistentCount = $value;
            return true;
        } else if ($field == "AbnormalCount") {
            $this->AbnormalCount = $value;
            return true;
        } else if ($field == "PrescribedDrugs") {
            $this->PrescribedDrugs = $value;
            return true;
        } else if ($field == "PrescribedDetected") {
            $this->PrescribedDetected = $value;
            return true;
        } else if ($field == "PrescribedNotDetected") {
            $this->PrescribedNotDetected = $value;
            return true;
        } else if ($field == "NotPrescribedDetected") {
            $this->NotPrescribedDetected = $value;
            return true;
        }

        return false;
    }

    public function setDoctor(array $dataRow) {
        $this->Doctor = new DoctorUser($dataRow);
    }

    public function setClient(array $dataRow) {
        $this->Client = new ClientUser($dataRow);
    }

    public function setOrderCount($value) {
        $this->OrderCount = $value;
    }

    public function __get($field) {
        $value = parent::__get($field);

        if (empty($value)) {
            if (array_key_exists($field, $this->Data)) {
                $value = $this->Data[$field];
            } else if ($field == "Patient") {
                $value = $this->Patient;
            } else if ($field == "Doctor") {
                $value = $this->Doctor;
            } else if ($field == "Client") {
                $value = $this->Client;
            } else if ($field == "clientName") {
                $value = $this->Client->clientName;
            } else if ($field == "doctorFirstName") {
                $value = $this->Doctor->firstName;
            } else if ($field == "doctorLastName") {
                $value = $this->Doctor->lastName;
            } else if ($field == "patientFirstName") {
                $value = $this->Patient->firstName;
            } else if ($field == "patientLastName") {
                $value = $this->Patient->lastName;
            } else if ($field == "IsReceipted") {
                $value = $this->IsReceipted;
            } else if ($field == "DateReceipted") {
                $value = $this->DateReceipted;
            } else if ($field == "TimeReceipted") {
                $value = $this->TimeReceipted;
            } else if ($field == "OrderCount") {
                $value = $this->OrderCount;
            } else if ($field == "ReportedCount") {
                $value = $this->ReportedCount;
            } else if ($field == "ApprovedCount") {
                $value = $this->ApprovedCount;
            } else if ($field == "PrintAndTransmittedCount") {
                $value = $this->PrintAndTransmittedCount;
            } else if ($field == "InvalidatedCount") {
                $value = $this->InvalidatedCount;
            } else if ($field == "InconsistentCount") {
                $value = $this->InconsistentCount;
            } else if ($field == "AbnormalCount") {
                $value = $this->AbnormalCount;
            } else if ($field == "PrescribedDrugs") {
                $value = $this->PrescribedDrugs;
            } else if ($field == "PrescribedDetected") {
                $value = $this->PrescribedDetected;
            } else if ($field == "PrescribedNotDetected") {
                $value = $this->PrescribedNotDetected;
            } else if ($field == "NotPrescribedDetected") {
                $value = $this->NotPrescribedDetected;
            }
        }

        return $value;
    }


    protected function isValidDate($date, $format = 'Y-m-d') {
        return $dateIsValid = parent::isValidDate($date, $format);
    }



//    public function getOrder() {
//        return $this->Order;
//    }

//    public function __get($field) {
//        if (array_key_exists($field, $this->Order)) {
//            return $this->Order[$field];
//        } else {
//            die ("ResultOrder: Field does not exist - $field");
//        }
//    }
}

?>
