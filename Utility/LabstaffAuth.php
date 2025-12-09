<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 4/13/15
 * Time: 5:00 PM
 */

if (!isset($_SESSION)) {
    session_start();
}

require_once 'IConfig.php';
require_once 'DOS/BaseObject.php';
require_once 'DAOS/LabUserDAO.php';
require_once 'DAOS/ResultLogDAO.php';

class LabstaffAuth extends BaseObject implements IConfig {
    protected $Data = array (
        "Username" => "",
        "Password" => "",
        "Ip" => null,
        "HashIsSet" => false,
        "SaltIsSet" => false,
        "MAX_LENGTH" => 6
    );

    private $LabUserDAO;
    private $LabUser = null;

    private $Error = 0;

    public function __construct(array $data = null) {
        if ($data != null) {
            foreach ($data as $key => $value) { // set other input fields
                if ($key == "Ip" && !empty($value)) { // convert ip address to numeric
                    $this->Data['Ip'] = ip2long($value);
                } else if (array_key_exists($key, $this->Data)) {
                    $this->Data[$key] = $value;
                }
            }

            if (array_key_exists("Action", $data)) {
                $action = $data['Action'];

                if ($action == 1) { // user attempting to log in

                    $this->setLabUser(1);

                } else if ($action == 2) { // page is loading
                    $this->setLabUser(2);
                    $this->checkSession();
                } else if ($action == 3) { // user is logging out
                    $this->setLabUser(2);
                    $this->logout();
                } else if ($action == 4) { // refresh the session after clicking the page timer link
                    $this->refreshSession();
                }
            } else {
                $this->Error = 1;
            }
        }
    }

    /** Sets the LabUser object. Logs out if failed to set
     * @param $action
     * @return bool
     */
    private function setLabUser($action) {
        if ($action == 1) { // set user from login form

            $this->LabUserDAO = new LabUserDAO($this->Data);
            $this->LabUserDAO->getUserByUsername(); // get the lab user

            if ($this->LabUserDAO->User != null) {
                $this->LabUser = $this->LabUserDAO->User; // set the lab user

            }

            if (!isset($this->LabUser)) { // user was not found, so logout
                $this->logout(true);
            } else {
                return true;
            }
        } else if ($action == 2) { // set user from session id

            if (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
                $this->LabUserDAO = new LabUserDAO(array("UserId" => $_SESSION['id']));
                $this->LabUserDAO->getLabUser();
                if (isset($this->LabUserDAO->User)) {
                    $this->LabUser = $this->LabUserDAO->User;
                    return true;
                }
            }
            $this->logout();

        } else {
            $this->logout();
        }

        return false;
    }

    private function checkSession() {
        if (isset($_SESSION['id']) && isset($this->LabUser) && $this->LabUser instanceof LabUser) {
            $dteLastLogin = new DateTime($this->LabUser->loginDate);
            $dteTimeout = new DateTime(date("Y-m-d H:i:s", mktime(date("H"), date("i"), date("s") - self::TimeoutInterval, date("m"), date("d"), date("Y"))));
            $diff = $dteTimeout->diff($dteLastLogin);

            if (!is_bool($this->LabUser)) {
                //if ($lastLogin > $timeout && session_id() === $this->User->sessionId && $_SESSION['token'] === $this->User->token) {
                if ($diff->invert == 0) { // 1 if the interval represents a negative time period and 0 otherwise.
                    $this->refreshSession();
                    $_SESSION['error'] = 0;
                    return true;
                }
            }
        }
        $this->logout(2); // either the user id is not set or the timeout interval has passed
    }

    public function refreshSession() {
        session_regenerate_id();
        $random = $this->getRandomString();
        $token = $_SERVER['HTTP_USER_AGENT'] . $random;
        $token = $this->hashData($token);
        $_SESSION['token'] = $token;
        //$_SESSION['pageview_time'] = date("Y-m-d H:i:s");

        $sessionId = session_id();

        $this->LabUserDAO->setNewLogin($token, $sessionId);
    }

    /**
     * @param bool $hashPasswordInput - says whether or not to has the password. if it was selected from the data then it doesn't need to be hashed
     * @return int
     */
    public function login($hashPasswordInput = true) {

        if (isset($this->LabUser) && $this->LabUser != null && $this->LabUser instanceof LabUser && $this->LabUser->active == true) {
           //echo "<pre>"; print_r($this->LabUser); echo "</pre>";
            $password = $this->LabUser->password;
            if ($hashPasswordInput) {
                $this->setHash(); // hash the password
            }
            $passwordHash = $this->Data['Password'];

            if ($password === $passwordHash) {
                // the passwords match
                $random = $this->getRandomString(); // first, generate a random string
                $token = $_SERVER['HTTP_USER_AGENT'] . $random; // build the token
                $token = $this->hashData($token);
                // setup sessions vars
                $_SESSION['token'] = $token;
                $_SESSION['id'] = $this->LabUser->idUser;
                $sessionId = session_id();

                $this->LabUserDAO->setNewLogin($token, $sessionId); // set the login in the database

                $aryOtherFields = array(
                    "Conn" => $this->LabUserDAO->Conn,
                );
                if ($this->Data['Ip'] != null) { // add a log entry
                    $aryOtherFields["Ip"] = $this->Data['Ip'];
                }
                ResultLogDAO::addLogEntry($this->LabUser->idUsers, 12, $aryOtherFields);

                return true; // good login
            }
        }
        return false; // user not found
    }

    public function logout($destroySession = true) {
        if (isset($_SESSION) && isset($_SESSION['id']) && !empty($_SESSION['id'])) { // clear login session from database
            $id = $_SESSION['id'];
            $this->LabUserDAO->logout($id);
            ResultLogDAO::addLogEntry($id, 13, array("Conn" => $this->LabUserDAO->Conn, "Ip" => $this->Data['Ip']));
        }

        try { // destroy all session variables
            if ($destroySession && isset($_SESSION)) {
                $_SESSION = array();
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(
                        session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }
                if (isset($_SESSION)) {
                    session_destroy();
                }
            }
        } catch (exception $e) {
            error_log("Caught exception on PageClient->logout(): " . $e->getMessage());
        }

        header("Location: /login.php");
        exit();
    }

    private function setHash() {
        $passwordHash = base64_encode(hash("sha1", $this->Data['Password'], true));
        $this->Data['Password'] = $passwordHash;
    }

    /**
     * This is used mostly for encrypying a token used for detecting if a login session is valid
     * @param $data
     * @return string
     */
    private function hashData($data) {
        //return hash_hmac('sha512', $data, $this->_siteKey);
        return md5($data);
    }

    private function getRandomString($length = 50) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $string = '';

        for ($p = 0; $p < $length; $p++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        return $string;
    }

    public static function getPasswordHash($password) {
        return base64_encode(hash("sha1", $password, true));
    }

    public function __get($field) {
        $value = parent::__get($field);

        if ($value == "") {
            if ($field == "Conn") {
                $value = $this->LabUserDAO->Conn;
            } if ($field == "LabUser") {
                $value = $this->LabUser;
            }
        }

        return $value;
    }

    /**
     * Reference: http://daipratt.co.uk/mysql-store-ip-address/
     */
    private function setIpAddress() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];

        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { // It is a proxy address
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if (isset($ip) && $ip != null && !empty($ip)) {
            $this->Data['Ip'] = ip2long($ip);
        }
    }

} 