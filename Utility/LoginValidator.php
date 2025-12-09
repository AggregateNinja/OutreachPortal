<?php
if (!isset($_SESSION)) {
	session_start();
}

require_once 'FormValidator.php';
require_once 'DAOS/UserDAO.php';
require_once 'DAOS/LabUserDAO.php';
require_once 'Auth.php';
require_once 'IConfig.php';

/**
 * Description of LoginValidator
 *
 * @author Edd
 */
class LoginValidator extends FormValidator implements IConfig {
//     protected $InputFields = array(
//         "pLastName" => "",
//         "pDob" => "",
//         "bcNum" => "",
//     	"agreeToTerms" => ""
//     );
	protected $InputFields = array(
            "Username" => "",
			"Email" => "",
			"Password" => "",
            "Ip" => null,
            "Force" => false
	);

    protected $ErrorCode;
    protected $IsValid;
    private $UserDAO;

    public function __construct($postArray) {
        foreach ($postArray as $name => $value) {
            if (array_key_exists($name, $this->InputFields)) {
                $this->InputFields[$name] = trim($value);
            }
        }

        if ($this->InputFields['Ip'] != null && isset($this->InputFields['Ip']) && !empty($this->InputFields['Ip'])) {
            $this->InputFields['Ip'] = ip2long($this->InputFields['Ip']);
        }

        $this->IsValid = true;
        $this->ErrorCode = 0;

        if (array_key_exists("Email", $postArray)) {
            $this->UserDAO = new UserDAO(array("email" => $this->InputFields['Email']));
        } else {
            $this->UserDAO = new LabUserDAO(array("username" => $this->InputFields['Username']));
        }
    }

    public function validateLabUser() {

        if (parent::isEmpty($this->InputFields['Username']) || parent::isEmpty($this->InputFields['Password'])) {
            $this->ErrorCode = 1;
            $this->IsValid = false;
            return false;

        }

        if ($this->IsValid == true) {

            $this->UserDAO->getUserByUsername();

            if (isset($this->UserDAO->User) && !empty($this->UserDAO->User)) {

                $user = $this->UserDAO->User;
                $isValidPassword = $this->isValidLabUserPassword($user);

                if (!$isValidPassword) {

                    $this->ErrorCode = 4; // invalid password
                    $this->IsValid = false;
                    return false;
                } else if ($user->ip != $this->InputFields['Ip'] && $this->userAlreadyLoggedIn($user)) {


                    $this->ErrorCode = 3; // User from different ip address already logged in
                    return false;
                }

            }

        }


        return true;
    }

    public function validate() {
    	if (parent::isEmpty($this->InputFields['Email']) || parent::isEmpty($this->InputFields['Password'])) {
            $this->ErrorCode = 1;
            $this->IsValid = false;
    		return false;
    	} else if (!parent::isValidEmail($this->InputFields['Email'])) {
            $this->ErrorCode = 1;
            $this->IsValid = false;
    		return false;
    	} else if (!parent::isValidLength($this->InputFields['Email'], 3)) {
            $this->ErrorCode = 1;
            $this->IsValid = false;
    		return false;
    	}

        if ($this->IsValid == true) {
            $this->UserDAO->getUserByEmail();

            if (isset($this->UserDAO->User) && !empty($this->UserDAO->User)) {
                $user = $this->UserDAO->User;
                $isValidPassword = $this->isValidUserPassword($user);

                if (!$isValidPassword) {
                    $this->ErrorCode = 4;
                    $this->IsValid = false;
                    return false;
                } else if (self::UserLoginLimit > 0 && $user->ip != $this->InputFields['Ip'] && $this->userAlreadyLoggedIn($user)) { // Someone with this userId is already logged in
                    $this->ErrorCode = 3; // User from different ip address already logged in
                    return false;
                }
            }
        }
    	return true;
    }

    private function isValidUserPassword(User $user) {
        $password = $this->InputFields['Password'];
        $salt = $user->userSalt;
        $passwordHash = Auth::getPasswordHash($password, $salt);
        if ($passwordHash != $user->password) {
            return false;
        }
        return true;
    }

    private function isValidLabUserPassword(LabUser $user) {
        $password = $this->InputFields['Password'];
        $passwordHash = LabstaffAuth::getPasswordHash($password);
        if ($passwordHash != $user->password) {
            return false;
        }
        return true;
    }

    private function userAlreadyLoggedIn(User $user) {
        if (isset($user->LoggedInUser) && is_array($user->LoggedInUser) && array_key_exists("loginDate", $user->LoggedInUser) && $user->LoggedInUser['loginDate'] != null && trim($user->LoggedInUser['loginDate']) != "") {

            try {

                $dteLastPageLoad = new DateTime($user->LoggedInUser['loginDate']);
                $dteTimeout = new DateTime(date("Y-m-d H:i:s", mktime(date("H"), date("i"), date("s") - self::TimeoutInterval, date("m"), date("d"), date("Y"))));
                $diff = $dteTimeout->diff($dteLastPageLoad);

                if ($diff->invert == 0) { // 1 if the interval represents a negative time period and 0 otherwise.
                    // the users last page load date/time was prior to the timeout date/time, so they must be still using their portal account
                    // the user's session hasn't reached the timeout yet, so they are still logged in
                    return true;
                }
            } catch (Exception $e) {
                error_log("Create DateTime Error in LoginValidator: " . $e->getMessage(), 0);
            }
        }

        return false;
    }

    public function __get($field) {
        $value = "";
        if ($field == "ErrorCode") {
            $value = $this->ErrorCode;
        }
        return $value;
    }


}

?>
