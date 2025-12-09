<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 1/14/15
 * Time: 3:01 PM
 */

require_once 'FormValidator.php';
require_once 'DAOS/UserDAO.php';
require_once 'DAOS/SalesDAO.php';

class SalesSettingsValidator extends FormValidator {

    private $InputFields = array(
        "action" => "",
        "idGoals" => "",
        "isAdmin" => "",
        "goalType" => "",
        "goalInterval" => "",
        "goal" => "",
        "isDefault" => "",
        "userId" => "",
        "ip" => "",
        "selectedSalesmen" => array()
    );

    private $ErrorMessages;
    private $IsValid;
    private $SalesDAO;

    public function __construct(array $inputFields) {
        foreach ($inputFields as $name => $value) {
            if (array_key_exists($name, $this->InputFields)) {

                $this->InputFields[$name] = $value;
            }
        }
        $this->ErrorMessages = array();
        $this->IsValid = true;
        $this->SalesDAO = new SalesDAO();
    }

    public function __get($field) {
        if ($field == "InputFields") {
            return $this->InputFields;
        } else if ($field == "ErrorMessages") {
            return $this->ErrorMessages;
        } else if ($field == "IsValid") {
            return $this->IsValid;
        } else if (array_key_exists($field, $this->InputFields)) {
            return $this->InputFields[$field];
        }
    }

    public function validate() {
        if ($this->InputFields['action'] == 1) {
            $this->isValidAddSetting();
        } else if ($this->InputFields['action'] == 2) {
            $this->isValidEditSetting();
        } else {
            $this->IsValid = false;
        }

        return $this->IsValid;
    }

    private function isValidEditSetting() {

    }

    private function isValidAddSetting() {
        $goals = $this->SalesDAO->getSalesGoals(array("userId" => $this->InputFields['userId'], "sg.isActive" => 1));
        //echo "<pre>"; print_r($this->InputFields); echo "</pre>";
        if (count($goals) > 0) {
            foreach ($goals as $goal) {
                $hasSalesmen = false;
                if (count($goal->Salesmen) > 0) {
                    $hasSalesmen = true;
                }

                if ($goal->intervalId == $this->InputFields['goalInterval'] && $hasSalesmen == false && count($this->InputFields['selectedSalesmen']) == 0) {
                    // sales group goal already exists
                    $this->IsValid = false;
                    $this->ErrorMessages['goalInterval'] = "A sales goal already exists for this interval. You might consider editing or deleting the existing goal.";
                }
                if ($goal->intervalId == $this->InputFields['goalInterval'] && $hasSalesmen == true && count($this->InputFields['selectedSalesmen']) > 0) {
                    // goal assigned to salesmen ... check to see if this goal matches one
                    $currGoalSalesmen = $goal->Salesmen;
                    foreach ($currGoalSalesmen as $salesman) {
                        //if ($salesman->idsalesmen == $this->InputFields['salesman']) {
                        if (in_array($salesman->idsalesmen, $this->InputFields['selectedSalesmen'])) {
                            // the selected salesman already has a goal for this interval
                            $this->IsValid = false;
                            $this->ErrorMessages['goalInterval'] = "The selected salesman is already assigned a goal for this interval.";
                        }
                    }

                }


                /*if ($goal->intervalId == $this->InputFields['goalInterval']) {
                    $this->IsValid = false;
                    $this->ErrorMessages['goalInterval'] = "A sales goal already exists for this interval. You might consider editing or deleting the existing goal.";
                }*/
            }


        }
    }

} 