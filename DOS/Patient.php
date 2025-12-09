<?php
require_once 'BaseObject.php';
require_once 'Insurance.php';
require_once 'Subscriber.php';

/**
 * Description of Patient
 *
 * @author Edd
 */
class Patient extends BaseObject {
    protected $Data = array (
        "idPatients" => "",
        "arNo" => "",
        "lastName" => "",
        "firstName" => "",
        "middleName" => "",
        "sex" => "",
        "ssn" => "   -  -   ",
        "dob" => "",
        "addressStreet" => "",
        "addressStreet2" => "",
        "addressCity" => "",
        "addressState" => "",
        "addressZip" => "",
        "phone" => "(   )   -",
        "workPhone" => "(   )   -",
        "subscriber" => "",
        "relationship" => "",
        "counselor" => "",
        "species" => "",
        "height" => "",
        "weight" => "",
        "ethnicity" => "",
        "smoker" => "",

        // calcualted fields
        "heightFeet" => "",
        "heightInches" => "",
        "age" => ""
    );

    protected $LoggedInPatient = array (
        "idLoggedIn" => "",
        "patientId" => "",
        "sessionId" => "",
        "token" => "",
        "loginDate" => ""
    );

    // 0 => css schema, 1 => cssweb schema
    private $PatientSource; // used when processing entry orders to determine whether the patient came from the css or cssweb database

    private $Insurance;
    private $SecondaryInsurance;

    private $Subscriber;

    public function __construct(array $data = null, array $settings = null) {
        //$inputDateFormat = 'm/d/Y';
        $inputDateFormat = 'n/j/Y';
        $outputDateFormat = 'Y-m-d';
        if ($settings != null) {
            if (array_key_exists("InputDateFormat", $settings) && !empty($settings['InputDateFormat'])) {
                $inputDateFormat = $settings['InputDateFormat'];
            }
            if (array_key_exists("OutputDateFormat", $settings) && !empty($settings['OutputDateFormat'])) {
                $outputDateFormat = $settings['OutputDateFormat'];
            }
        }

        if ($data != null) {
            parent::__construct($data);

            if (isset($this->Data)) {

                if (array_key_exists("patientFirstName", $data)) {
                    $this->Data['firstName'] = $data['patientFirstName'];
                }
                if (array_key_exists("patientLastName", $data)) {
                    $this->Data['lastName'] = $data['patientLastName'];
                }

                if (array_key_exists("patientNo", $data)) {
                    $this->Data['arNo'] = $data['patientNo'];
                }

                if (array_key_exists("dob", $this->Data) && !empty($this->Data['dob'])) {
                    $this->Data['age'] = $this->calcAge();



                    $dob = $this->formatDate($this->Data['dob'], $inputDateFormat, $outputDateFormat);
                    if ($dob == null || empty($dob)) {
                        $dob = $this->formatDate($this->Data['dob']);
                    }

                    $this->Data['dob'] = $dob;

                }

                $phone = "";
                $search = array(" ", "(", ")", "-");
                if (array_key_exists("phone", $this->Data) && !empty($this->Data['phone'])) {
                    $phone = str_replace($search, "", $this->Data['phone']);
                } else if (array_key_exists("patientPhone", $this->Data) && !empty($this->Data['patientPhone'])) {
                    $phone = str_replace($search, "", $this->Data['patientPhone']);
                }
                if (strlen($phone) == 10) {
                    $this->Data['phone'] = "(" . substr($phone, 0, 3) . ")" . substr($phone, 3, 3) . "-" . substr($phone, 6, 4);
                }

                $workPhone = "";
                if (array_key_exists("workPhone", $this->Data) && !empty($this->Data['workPhone'])) {
                    $workPhone = str_replace($search, "", $this->Data['workPhone']);
                } else if (array_key_exists("patientWorkPhone", $this->Data) && !empty($this->Data['patientWorkPhone'])) {
                    $workPhone = str_replace($search, "", $this->Data['patientWorkPhone']);
                }
                if (strlen($workPhone) == 10) {
                    $this->Data['workPhone'] = "(" . substr($workPhone, 0, 3) . ")" . substr($workPhone, 3, 3) . "-" . substr($workPhone, 6, 4);
                }

                if (array_key_exists("relationship", $this->Data) && !empty($this->Data['relationship'])) {
                    $this->Data['relationship'] = strtolower($this->Data['relationship']);
                }
                if (array_key_exists("height", $this->Data) && !empty($this->Data['height']) && is_numeric($this->Data['height']) && $this->Data['height'] > 0) {
                    $aryHeight = $this->calcHeight();
                    $this->Data['heightFeet'] = $aryHeight[0];
                    $this->Data['heightInches'] = $aryHeight[1];
                }
            }
        }
    }

    public function __isset($field) {
        $isset = parent::__isset($field);

        if (!$isset) {
            if ($field == "Patient" && isset($this->Data) && is_array($this->Data) && array_key_exists("idPatients", $this->Data)) {
                $isset = true;
            } else if ($field == "SubscriberSource" && isset($this->PatientSource) && !empty($this->PatientSource)) {
                $isset = true;
            } else if ($field == "LoggedInPatient" && is_array($this->LoggedInPatient) && count($this->LoggedInPatient) == 4) {
                if (!empty($this->LoggedInPatient['loginDate']) && !empty($this->LoggedInPatient['sessionId']) && !empty($this->LoggedInPatient['token'])) {
                    $isset = true;
                }
            } else if ($field == "PatientSource" && isset($this->PatientSource) && !empty($this->PatientSource) && ($this->PatientSource == 1 || $this->PatientSource == 0)) {
                $isset = true;
            } else if ($field == "Insurance" && isset($this->Insurance) && $this->Insurance instanceof Insurance) {
                $isset = true;
            } else if ($field == "SecondaryInsurance" && isset($this->SecondaryInsurance) && $this->SecondaryInsurance instanceof Insurance) {
                $isset = true;
            } else if ($field == "Subscriber" && isset($this->Subscriber) && $this->Subscriber instanceof Subscriber) {
                $isset = true;
            }
        }

        return $isset;
    }

    public function getName() {
        if (!empty($this->Data['lastName']) && !empty($this->Data['firstName']) && !empty($this->Data['middleName'])) {
            return $this->Data['lastName'] . ", " . $this->Data['firstName'] . " " . $this->Data['middleName'];
        } else if (!empty($this->Data['lastName']) && !empty($this->Data['firstName'])) {
            return $this->Data['lastName'] . ", " . $this->Data['firstName'];
        } else if (!empty($this->Data['lastName'])) {
            return $this->Data['lastName'];
        } else if (!empty($this->Data['firstName'])) {
            return $this->Data['firstName'];
        }
        return null;
    }

    public function __get($field) {
        $value = parent::__get($field);
        if (empty($value)) {
            if ($field == "PatientSource") {
                $value = $this->PatientSource;
            } else if (array_key_exists($field, $this->LoggedInPatient)) {
                $value = $this->LoggedInPatient[$field];
            } else if ($field == "LoggedInPatient") {
                $value = $this->LoggedInPatient;
            } else if ($field == "Insurance" && isset($this->Insurance) && $this->Insurance instanceof Insurance) {
                $value = $this->Insurance;
            } else if ($field == "SecondaryInsurance" && isset($this->SecondaryInsurance) && $this->SecondaryInsurance instanceof Insurance) {
                $value = $this->SecondaryInsurance;
            } else if ($field == "Subscriber" && isset($this->Subscriber) && $this->Subscriber instanceof Subscriber) {
                $value = $this->Subscriber;
            }
        }
        return $value;
    }

    public function setInsurance(array $data) {
        $this->Insurance = new Insurance($data);
    }
    public function setSecondaryInsurance(array $data) {
        $this->SecondaryInsurance = new Insurance($data);
    }
    public function setSubscriber(array $data) {
        $this->Subscriber = new Subscriber($data);
    }

    public function __set($field, $value) {
        $done = parent::__set($field, $value);
        if (!$done) {
            if ($field == "PatientSource") {
                $this->PatientSource = $value;
                $done = true;
            } else if ($field == "LoggedInPatient" && is_array($value)) {
                foreach ($value as $fieldKey => $fieldValue) {
                    if (array_key_exists($fieldKey, $this->LoggedInPatient)) {
                        $this->LoggedInPatient[$fieldKey] = $fieldValue;
                    }
                }
                $done = true;
            }
        }
        return $done;
    }

    public function calcAge() {
        return parent::calcAge();
    }

}

?>
