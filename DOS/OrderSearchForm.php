<?php
require_once 'BaseObject.php';
/**
 * Description of OrderSearchForm
 *
 * @author Edd
 */
class OrderSearchForm extends BaseObject {
    private $DateFields = array(
        "patientDOB" => "",
        "dosFrom" => "",
        "dosTo" => "",

        "orderDateFrom" => "",
        "orderDateTo" => "",

        "specimenFrom" => "",
        "specimenTo" => "",
        "specimenDateFrom" => "",
        "specimenDateTo" => "",

        "reportedFrom" => "",
        "reportedTo" => "",
        "reportedDateFrom" => "",
        "reportedDateTo" => "",

        "createdFrom" => "",
        "createdTo" => "",

        "approvedDateFrom" => "",
        "approvedDateTo" => ""
    );    
    private $StringFields = array(
        "patientFirstName" => "",
        "patientLastName" => "",
        "patientId" => "",
        "doctorFirstName" => "",
        "doctorLastName" => "",
        "accession" => "",
        "clientName" => "",
        "clientNumber" => ""
    );
    private $CheckboxFields = array (
        "abnormalsOnly" => "",
        "unprintedReports" => "",
        "sinceLastLogin" => "",
        "invalidatedOnly" => "",
        "translationalOnly" => "",
        "pastTwentyFourHours" => false,

        "inconsistentOnly" => "",
        "consistentOnly" => "",
        "completeOnly" => "",
        "incompleteOnly" => "",
        "unprintedOnly" => "",

    );
    
        
    private $UsedFields = array();
        
    public function __construct($searchFields) {
        
        foreach ($searchFields as $key => $value) {
            if ($key == "patientDOB") {
                $this->DateFields[$key] = $value;
            }
            
            else if (array_key_exists($key, $this->DateFields)) {
                $this->DateFields[$key] = $value;
            }
            
            else if (array_key_exists($key, $this->StringFields)) {
                
                $this->StringFields[$key] = $value;
                
            } else if (array_key_exists($key, $this->CheckboxFields)) {
                $this->CheckboxFields[$key] = $value;
            } 
            
            if (!empty($value) || $key == "invalidatedOnly") {
                if ($key == "dosFrom" || $key == "reportedFrom" || $key == "specimenFrom" || $key == "createdFrom"
                    || $key == "orderDateFrom" || $key == "specimenDateFrom" || $key == "reportedDateFrom" || $key == "approvedDateFrom") {
                    $this->UsedFields[$key] = $this->formatDate($value . " 00:00:00", 'm/d/Y H:i:s', 'Y-m-d H:i:s');
                } else if ($key == "dosTo" || $key == "reportedTo" || $key == "specimenTo" || $key == "createdTo"
                    || $key == "orderDateTo" || $key == "specimenDateTo" || $key == "reportedDateTo" || $key == "approvedDateTo") {
                    $this->UsedFields[$key] = $this->formatDate($value . " 23:59:59", 'm/d/Y H:i:s', 'Y-m-d H:i:s');
                } else if ($key == "patientDOB") {
                    $this->UsedFields[$key] = $this->formatDate($value . " 00:00:00", 'm/d/Y H:i:s', 'Y-m-d H:i:s');
                } else {
                    $this->UsedFields[$key] = $value;
                }
            }
        }
        
    }

    public function getDateFields() {
        return $this->DateFields;
    }
    public function getStringFields() {
        return $this->StringFields;
    }
    public function getCheckboxFields() {
        return $this->CheckboxFields;
    }
    
    public function __get($field) {
        if (array_key_exists($field, $this->DateFields))
            return $this->DateFields[$field];
        else if (array_key_exists($field, $this->StringFields))
            return $this->StringFields[$field];
        else if (array_key_exists($field, $this->CheckboxFields))
            return $this->CheckboxFields[$field];
        else if ($field == "UsedFields")
            return $this->UsedFields;
        else if ($field == "DateFields")
            return $this->DateFields;
        else if ($field == "StringFields")
            return $this->StringFields;
        else if ($field == "CheckboxFields")
            return $this->CheckboxFields;
        else
            die("Field not found: $field");
    }
    
    public function __toString() {
        $strReturn = "";
        foreach ($this->DateFields AS $key => $value) {
            $strReturn .= $key . " - " . $value . "<br />";
        }
        foreach ($this->StringFields AS $key => $value) {
            $strReturn .= $key . " - " . $value . "<br />";
        }
        foreach ($this->CheckboxFields AS $key => $value) {
            $strReturn .= $key . " - " . $value . "<br />";
        }
        return $strReturn;
    }
    
}

?>
