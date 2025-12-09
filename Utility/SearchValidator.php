<?php

if (!isset($_SESSION)) {
    session_start();
}
require_once 'FormValidator.php';
//require_once "DOS/OrderSearchForm.php";

/**
 * Description of SearchValidator
 * 
 * @author Edd
 */
class SearchValidator extends FormValidator {

    private $User; 
    private $TotalOrders;
    
    private $IsValid;
    private $ErrorMessages;
    private $DateFormat;    

    private $SearchFields;

    public function __construct(array $postArray, User $resultUser, $totalOrders) {

        $this->SearchFields = array();
        $this->setSearchFields($postArray);

        $this->User = $resultUser;
        $this->TotalOrders = $totalOrders;
        
        $this->IsValid = true;
        $this->ErrorMessages = array();
        $this->DateFormat = 'm/d/Y';
    }

    private function setSearchFields(array $postArray) {
        foreach ($postArray AS $fieldName => $fieldValue) {
            if (!empty($fieldValue)){
                $this->SearchFields[$fieldName] = $fieldValue;
            }
        }

    }
    
    public function validate() {
		$usedFields = $this->SearchFields;
        if (is_bool($this->User) || $this->User == null || $this->User->typeId == 1) {
            $this->IsValid = false;
            $this->ErrorMessages["User"] = "Invalid User";
        }

        if (count($usedFields) == 0) {
            $this->IsValid = false;
            $this->ErrorMessages["EmptyForm"] = "You must fill out at least one field";
        }

        if (array_key_exists("dosFrom", $usedFields) && array_key_exists("dosTo", $usedFields)) {
            if (!parent::isValidDate(array($usedFields['dosFrom'], $usedFields['dosTo']), $this->DateFormat)) {
                $this->IsValid = false;
                $this->ErrorMessages["dosFrom"] = "Invalid Date Range";
            }
        } else if (array_key_exists("dosFrom", $usedFields)) {
            $this->IsValid = false;
            $this->ErrorMessages["dosTo"] = "Ending date of service must be selected";

        } else if (array_key_exists("dosTo", $usedFields)) {
            $this->IsValid = false;
            $this->ErrorMessages["dosFrom"] = "Beginning date of service must be selected";
        }


        if (array_key_exists("reportedFrom", $usedFields) && array_key_exists("reportedTo", $usedFields)) {
            if (!parent::isValidDate(array($usedFields['reportedFrom'], $usedFields['reportedTo']), $this->DateFormat)) {
                $this->IsValid = false;
                $this->ErrorMessages["reportedFrom"] = "Invalid Date Range";
            }

        } else if (array_key_exists("reportedFrom", $usedFields)) {
            $this->IsValid = false;
            $this->ErrorMessages["reportedTo"] = "Ending reported date must be selected";

        } else if (array_key_exists("reportedTo", $usedFields)) {
            $this->IsValid = false;
            $this->ErrorMessages["reportedFrom"] = "Beginning reported date must be selected";
        }


        if (array_key_exists("specimenFrom", $usedFields) && array_key_exists("specimenTo", $usedFields)) {
            if (!parent::isValidDate(array($usedFields['specimenFrom'], $usedFields['specimenTo']), $this->DateFormat)) {
                $this->IsValid = false;
                $this->ErrorMessages["specimenFrom"] = "Invalid Date Range";
            }

        } else if (array_key_exists("specimenFrom", $usedFields)) {
            $this->IsValid = false;
            $this->ErrorMessages["specimenTo"] = "Ending specimen date must be selected";

        } else if (array_key_exists("specimenTo", $usedFields)) {
            $this->IsValid = false;
            $this->ErrorMessages["specimenFrom"] = "Beginning specimen date must be selected";
        }

        if (array_key_exists("createdFrom", $usedFields) && array_key_exists("createdTo", $usedFields)) {
            if (!parent::isValidDate(array($usedFields['createdFrom'], $usedFields['createdTo']), $this->DateFormat)) {
                $this->IsValid = false;
                $this->ErrorMessages["createdFrom"] = "Invalid Date Range";
            }

        } else if (array_key_exists("createdFrom", $usedFields)) {
            $this->IsValid = false;
            $this->ErrorMessages["createdTo"] = "Ending created date must be selected";

        } else if (array_key_exists("createdTo", $usedFields)) {
            $this->IsValid = false;
            $this->ErrorMessages["createdFrom"] = "Beginning created date must be selected";
        }





        if (array_key_exists("patientDOB", $usedFields) && !parent::isValidDate(array($usedFields['patientDOB']), $this->DateFormat)) {
            $this->IsValid = false;
            $this->ErrorMessages["patientDOB"] = "Invalid Date of Birth";
        }

        if (array_key_exists("patientId", $usedFields) && !is_numeric($usedFields['patientId'])) {
            $this->IsValid = false;
            $this->ErrorMessages["patientId"] = "A patient id must be a number";
        }
        	
        if ($this->TotalOrders == 0) {
            //$this->IsValid = false;
            //$this->ErrorMessages["OrderCount"] = "No results found. Please try again.";
        }
        return $this->IsValid;
    }

    public function __get($field) {
        $value = null;
        if ($field == "IsValid") {
            $value = $this->IsValid;
            
        } else if ($field == "ErrorMessages") {
            $value = $this->ErrorMessages;
        }
        return $value;
    }

}

?>
