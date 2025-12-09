<?php
require_once 'FormValidator.php';
require_once 'DAOS/UserDAO.php';

/**
 * Description of AddUserValidator
 *
 * @author Edd
 */
class UserValidator extends FormValidator {

    private $InputFields = array (
        "idUsers" => "",
        "clientId" => "",
        "doctorId" => "",
        "salesmanId" => "",
        "insuranceId" => "",
        "typeId" => "",
        "email" => "",
        "password" => "",
        "password2" => "",
        "userSalt" => "",
        "verificationCode" => "",
        "resetPasswordInput" => "",
        "action" => "",
        "adminClientId" => ""
    );
    
    private $InvalidFields; 
    private $IsValid;
    
    public function __construct(array $inputFields) {
        foreach ($inputFields as $name => $value) {
            if (array_key_exists($name, $this->InputFields)) {
                $this->InputFields[$name] = $value;
            }
        }

        $this->InvalidFields = array();
        $this->IsValid = true;
    }
    
    public function __get($field) {
        if (array_key_exists($field, $this->InputFields)) {
            return $this->InputFields[$field];
        } else if ($field == "InvalidFields") {
            return $this->InvalidFields;
        } else if ($field == "IsValid") {
            return $this->IsValid;
        }
    }

    public function setIsValid($isValid) {
        $this->IsValid = $isValid;
    }

	public function userIsValid($skipUserTypeCheck = false) {
		if (!parent::isValidEmail($this->InputFields['email'])) {
			$this->InvalidFields['email'] = "Invalid Email Format";
		} 

        if (!$skipUserTypeCheck) {
            if (empty($this->InputFields['typeId'])) {
                $this->InvalidFields['typeId'] = "A user type must be selected";

            } else if (parent::isEmpty($this->InputFields['clientId']) && $this->InputFields['typeId'] == 2) {
                $this->InvalidFields['typeId'] = "A client must be selected";

            } else if (parent::isEmpty($this->InputFields['doctorId']) && $this->InputFields['typeId'] == 3) {
                $this->InvalidFields['typeId'] = "A doctor must be selected";
            } else if (parent::isEmpty($this->InputFields['salesmanId']) && $this->InputFields['typeId'] == 5) {
                $this->InvalidFields['typeId'] = "A salesman must be selected";
            } else if (parent::isEmpty($this->InputFields['insuranceId']) && $this->InputFields['typeId'] == 6) {
                $this->InvalidFields['typeId'] = "An insurance must be selected";
            }
            /*else if ($this->InputFields['typeId'] == 1 && (!parent::isEmpty($this->InputFields['clientId']) || !parent::isEmpty($this->InputFields['doctorId']))) {
                $this->InvalidFields['typeId'] = "An administrator cannot be a client or doctor";
            }*/
        }

		if ($this->InputFields['resetPasswordInput'] == 1 || $this->InputFields['action'] == 1) {

            if (!parent::passwordsMatch($this->InputFields['password'], $this->InputFields['password2'])) {
                $this->InvalidFields['password'] = "Passwords do not match";
            }

            if (!parent::isValidPasswordLength($this->InputFields['password'])) {
                $this->InvalidFields['password'] = "Password must be at least 6 characters";
            }

            if (!parent::isValidPasswordComplexity($this->InputFields['password'])) {
                $this->InvalidFields['password'] = "Password must contain at least one letter and one number";
            }
		}

        if (UserDAO::userEmailExists($this->InputFields['email'], $this->InputFields['idUsers'], true)) {
            $this->InvalidFields['email'] = "Email address already exists";
        } else if (UserDAO::userEmailExists($this->InputFields['email'], $this->InputFields['idUsers'], false)) {
            $this->InvalidFields['email2'] = true;
        }
		
		if (count($this->InvalidFields) > 0) {
			$this->IsValid = false;
		}
	}
}
?>
