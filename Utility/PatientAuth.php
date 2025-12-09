<?php

if (!isset($_SESSION)) {
    session_start();
}
require_once 'LoginValidator.php';
require_once 'DAOS/DataConnect.php';
require_once 'DAOS/PatientDAO.php';

class PatientAuth extends LoginValidator {
	protected $InputFields = array(
			"pLastName" => "",
			"pDob" => "",
			"bcNum" => "",
			"agreeToTerms" => ""
	);
	protected $PatientId;
	protected $TimeoutInterval = 120;
	protected $User;
    protected $Conn;
	
	
	public function __construct(array $data = null) {
            if ($data != null) {
                parent::__construct($data);
                foreach ($data as $name => $value) {
                    if (array_key_exists($name, $this->InputFields)) {
                        $this->InputFields[$name] = trim($value);
                    }
                }
            }
            $this->PatientId = null;

            $this->Conn = DataConnect::getConn();
	}
	
	public function validate() {
		//$this->IsValid = parent::validate();
		if (parent::isEmpty($this->InputFields['pLastName']) || parent::isEmpty($this->InputFields['pDob']) || parent::isEmpty($this->InputFields['bcNum'])) {
			$this->IsValid = false;
			$this->ErrMsg['err'] = "Invalid login, please try again";
		} else if (!parent::isValidDateFormat($this->InputFields['pDob'])) {
			$this->IsValid = false;
			$this->ErrMsg['pDob'] = "Please enter a valid date";
		} else if (empty($this->InputFields['agreeToTerms']) || $this->InputFields['agreeToTerms'] != 1) {
			$this->IsValid = false;
			$this->ErrMsg['agreeToTerms'] = "You must agree to the terms of service to login";
		}
		
		if (!$this->IsValid && count($this->ErrMsg) > 0) {
			$_SESSION['ERRMSG'] = $this->ErrMsg;
		}
		
		if ($this->IsValid) {
			$input = array (
				"p.dob" => date("Y-m-d", strtotime($this->InputFields['pDob'])),
				"p.lastName" => $this->InputFields['pLastName'],
				"o.accession" => $this->InputFields['bcNum']
			);
			$patientId = PatientDAO::getPatientId($input, array("ApprovedOrdersOnly" => true));
			
			if (!is_bool($patientId)) {
				$this->PatientId = $patientId;
				
				$this->login();	
				
			} else {
				$this->IsValid = false;
				$this->ErrMsg['err'] = "Invalid login, please try again."; 
				$_SESSION['ERRMSG'] = $this->ErrMsg;
			}
		
		}
		return $this->IsValid;
	}
	
	private function login() {
		
		$random = $this->getRandomString();
		$token = $_SERVER['HTTP_USER_AGENT'] . $random;
		$token = $this->hashData($token);
			
		$_SESSION['token'] = $token;
		$_SESSION['id'] = $this->PatientId;
		$_SESSION['type'] = 4;
		
		$sessionId = session_id();
		
		PatientDAO::setNewLogin($this->PatientId, $sessionId, $token, array("Conn" => $this->Conn));
		
		// add entry to the patient log
		PatientDAO::addPatientLogEntry($this->PatientId, 1, array("Conn" => $this->Conn));
	}
	
	public function logout($destroySession = true) {
		if (isset($_SESSION['id'])) {
			PatientDAO::logout($_SESSION['id']);
		}
	
		if ($destroySession && isset($_SESSION)) {
                        $_SESSION = array();
			if (ini_get("session.use_cookies")) {
				$params = session_get_cookie_params();
				setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
				);
			}
			session_destroy();
		}
	}
	
	public function checkSession() {
		
		if (isset($_SESSION['id']) && isset($_SESSION['type'])) {
			
			$patient = PatientDAO::getLoggedInPatient($_SESSION['id'], array("Conn" => $this->Conn));
			 
			if (!is_bool($patient)) {
                            
				$lastLogin = $patient->loginDate;
				$oneTwentySecondsAgo = date("Y-m-d H:i:s", mktime(date("H") - 7, date("i"), date("s") - $this->TimeoutInterval, date("m"), date("d"), date("Y")));
				//echo date("m/d/y h:i:s A", strtotime($lastLogin)) . "<br/>" . date("m/d/y h:i:s A", strtotime($oneTwentySecondsAgo));
				
				if ($lastLogin > $oneTwentySecondsAgo && session_id() === $patient->sessionId && $_SESSION['token'] === $patient->token) {
					
                    $this->refreshSession();
					$_SESSION['error'] = 0;
					$this->User = $patient;
					//echo $_SESSION['id'] . " of type " . $_SESSION['type'] . " is logged in";
					return true;					 
				}
			}
		}
		$_SESSION['error'] = 1;	
		return false;
	}
	
	private function refreshSession() {
		session_regenerate_id();
		$random = $this->getRandomString();
		$token = $_SERVER['HTTP_USER_AGENT'] . $random;
		$token = $this->hashData($token);
		$_SESSION['token'] = $token;
	
		$sessionId = session_id();
	
		PatientDAO::setNewLogin($_SESSION['id'], $sessionId, $token, array("Conn" => $this->Conn));
	}
	
	

	private function getRandomString($length = 50) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		$string = '';
	
		for ($p = 0; $p < $length; $p++) {
			$string .= $characters[mt_rand(0, strlen($characters) - 1)];
		}
	
		return $string;
	}
	
	private function hashData($data) {
		//return hash_hmac('sha512', $data, $this->_siteKey);
		return md5($data);
	}
	
}



?>