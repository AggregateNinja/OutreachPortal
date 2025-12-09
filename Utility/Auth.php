<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'DAOS/UserDAO.php';
require_once 'DAOS/AdminDAO.php';
require_once 'Utility/ResultUserCreator.php';
require_once 'DAOS/ResultLogDAO.php';
require_once 'IConfig.php';
require_once 'DOS/BaseObject.php';

/**
 * Description: This class will help to login, logout, create, and check sessions of users
 * References:  http://www.sitepoint.com/password-hashing-in-php/
 *              http://www.dreamincode.net/forums/topic/247188-user-authentication-class/
 *              http://phpsec.org/projects/guide/4.html
 *              http://en.wikipedia.org/wiki/Rainbow_table
 *              http://en.wikipedia.org/wiki/Session_hijacking
 * @author      Edd
 */
class Auth extends BaseObject implements IConfig {
    protected $Data;

    public $User;
    protected $AdminUser;
    protected $UserDAO;

    private $Error = 0;

    public $ExtraPasswordSecurity = self::ExtraPasswordSecurity;

    public function __construct(array $settings = null) {
        parent::__construct();

        if (empty($this->SiteUrl)) {
            $this->SiteUrl = self::SITE_URL;
        }
        if (empty($this->Logo)) {
            $this->Logo = self::Logo;
        }
        if (empty($this->LabName)) {
            $this->LabName = self::LabName;
        }

        $this->Data = array (
            "Email" => "",
            "Password" => "",
            "Ip" => null,
            "Salt" => "",
            "VerificationCode" => "",
            "HashIsSet" => false,
            "SaltIsSet" => false,
            "MAX_LENGTH" => 6
        );
        $aryData = array();
        $arySettings = array("IncludeUserSettings" => true);

        $this->setIpAddress();
        if ($this->Data['Ip'] != null && !empty($this->Data['Ip'])) {
            $aryData['ip'] = $this->Data['Ip'];
        }

        if ($settings != null && array_key_exists("email", $settings) && array_key_exists("password", $settings)) {

            $this->Data['Email'] = $settings['email'];
            $this->Data['Password'] = $settings['password'];

            $aryData['email'] = $settings['email'];

            $this->UserDAO = new UserDAO($aryData, $arySettings);
            $this->UserDAO->getUserByEmail();

            if ($this->UserDAO->User != null) {
                $this->User = $this->UserDAO->User;

            }
            if (!isset($this->User)) {
                $this->logout(true);
            }
        } elseif (($settings == null || !array_key_exists("redirect", $settings) || $settings['redirect'] == true) && (!isset($_SESSION['id']) || empty($_SESSION['id'])) && (!isset($_SESSION['type']) || empty($_SESSION['type']))) {
            //header("Location: /login.php");
            //header("Location: http://" . $_SERVER['HTTP_HOST']);

            $currPage = $_SERVER['PHP_SELF'];
            $currPageDir = substr($currPage, 1, strpos($currPage, "/", 1) -1);

            if ($currPageDir == "patients") {
                header("Location: /patients/login/");
            } else {
                header("Location: " . self::SITE_URL);
            }
            exit();
        } else if (isset($_SESSION['id']) && isset($_SESSION['type']) && !empty($_SESSION['id']) && !empty($_SESSION['type'])) {

            if (isset($_SESSION['y']) && isset($_SESSION['z'])) {

                // decrypt session data where key = y and init vector = z
                require_once 'DOS/Proxy.php';

                $key = $_SESSION['y'];
                $iv = $_SESSION['z'];

                $aryEncData = array(
                    "id" => $_SESSION['id'],
                    "type" => $_SESSION['type']
                );
                if (array_key_exists("token", $_SESSION)) {
                    $aryEncData['token'] = $_SESSION['token'];
                }

                $proxy = new Proxy(array(
                    "Key" => $key,
                    "InitVector" => $iv,
                    "EncData" => $aryEncData
                ));
                $proxy->decrypt();
                $rawData = $proxy->getRawData();
                $aryData['userId'] = $rawData['id'];
                $aryData['typeId'] = $rawData['type'];

                $arySettings['IncludeDetailedInfo'] = true;

            } else {
                $aryData['userId'] = $_SESSION['id'];
                $aryData['typeId'] = $_SESSION['type'];
            }


            if ($settings != null) {
                foreach ($settings as $key => $value) {
                    $arySettings[$key] = $value;
                }
            }

            $this->UserDAO = new UserDAO($aryData, $arySettings);
            $this->UserDAO->getUser();

            if ($this->UserDAO->User != null) {
                $this->User = $this->UserDAO->User;


                if ($settings != null) {
                    if (array_key_exists("EditingWebOrder", $settings) && is_numeric($settings['EditingWebOrder'])) {

                        // update WebOrdersBeingEdited table
                        $this->UserDAO->setOrderBeingEdited($settings['EditingWebOrder']);
                    }

                    if (array_key_exists("ClearEditedOrder", $settings) && $settings['ClearEditedOrder'] == true) {
                        $this->UserDAO->clearOrderBeingEdited();
                    }
                }

            } else {
                $this->User = null;
            }

            if (isset($_SESSION['AdminId']) && isset($_SESSION['AdminType']) && !empty($_SESSION['AdminId'])) { // && $_SESSION['AdminType'] == 7
                $aryAdminSettings = array("IncludeUserSettings" => true);
                if ($_SERVER['PHP_SELF'] == "/outreach/orderentry/add.php") {
                    $aryAdminSettings['IncludeCommonCodes'] = true;
                    $aryAdminSettings['IncludeCommonTests'] = true;
                    $aryAdminSettings['IncludeExcludedTests'] = true;
                    $aryAdminSettings['IncludeCommonDrugs'] = true;
                }
                $userDAO = new UserDAO(array("userId" => $_SESSION['AdminId'], "typeId" => $_SESSION['AdminType']), $aryAdminSettings);
                $userDAO->getUser();
                $this->AdminUser = $userDAO->User;
            }

            if (!isset($this->User)) {
                $this->logout(true);
            }
        } else {
            $this->Error = 1;
        }
    }

    // check that a users session is legitimate upon each page request
    public function checkSession() {

        if (isset($_SESSION['id']) && isset($_SESSION['type']) && isset($this->User) && $this->User instanceof User) {

            // session id is set
            $dteLastLogin = new DateTime($this->User->loginDate);

            //echo "<pre>"; print_r($dteLastLogin); echo "</pre>";

            if ($this->User->loginDate != null && $this->User->loginDate != '') {

                $dteTimeout = new DateTime(date("Y-m-d H:i:s", mktime(date("H"), date("i"), date("s") - self::TimeoutInterval, date("m"), date("d"), date("Y"))));
                $diff = $dteTimeout->diff($dteLastLogin);

                //error_log("Login Date: " . $this->User->loginDate . ", Curr Time: " . $dteTimeout->format("Y-m-d H:i:s"));

                if (!is_bool($this->User)) {
                    //if ($lastLogin > $timeout && session_id() === $this->User->sessionId && $_SESSION['token'] === $this->User->token) {
                    if ($diff->invert == 0) { // 1 if the interval represents a negative time period and 0 otherwise.
                        $this->refreshSession();
                        $_SESSION['error'] = 0;
                        return true;
                    }
                }
            }
        }
        $_SESSION['error'] = 1;

        return false;
    }

    public function refreshSession() {
        if (session_status() !== PHP_SESSION_NONE) {
            session_regenerate_id();
        }
        $random = $this->getRandomString();
        $token = $_SERVER['HTTP_USER_AGENT'] . $random;
        $token = $this->hashData($token);
        $_SESSION['token'] = $token;
        //$_SESSION['pageview_time'] = date("Y-m-d H:i:s");

        $sessionId = session_id();

        $adminUserId = null;
        if (isset($_SESSION['AdminId'])) {
            $adminUserId = $_SESSION['AdminId'];
        }

        $this->UserDAO->setNewLogin($token, $sessionId, $adminUserId); // update the LoggedInUser table with the new session token and id
    }

    public function setLoginCredentials($email, $password) {
        $this->Data['Email'] = $email;
        $this->Data['Password'] = $password;
    }

    public function login($hashPasswordInput = true, $encryptSessionData = false) {
        if (isset($this->User) && $this->User != null && $this->User instanceof User && $this->User->isActive == true) {

            $password = $this->User->password;
            if ($hashPasswordInput) { // passwords don't need to be hashed when admin's login as a user
                $salt = $this->User->userSalt;
                $this->setHash($salt);
            }
            if ($this->Data['Password'] === $password) { // the passwords match
                //First, generate a random string.
                $random = $this->getRandomString();
                //Build the token
                $token = $_SERVER['HTTP_USER_AGENT'] . $random;
                $token = $this->hashData($token);

                //Setup sessions vars
                if ($encryptSessionData == true) {
                    require_once 'DOS/Proxy.php';

                    $proxy = new Proxy(array(
                        "RawData" => array(
                            "token" => $token,
                            "id" => $this->User->idUsers,
                            "type" => $this->User->typeId
                        )
                    ));
                    $proxy->encrypt();
                    $encData = $proxy->getEncData();
                    $key = $proxy->getKey();
                    $iv = $proxy->getInitVector();

                    $_SESSION['token'] = $encData['token'];
                    $_SESSION['id'] = $encData['id'];
                    $_SESSION['type'] = $encData['type'];
                    $_SESSION['y'] = $key;
                    $_SESSION['z'] = $iv;

                } else {
                    $_SESSION['token'] = $token;
                    $_SESSION['id'] = $this->User->idUsers;
                    $_SESSION['type'] = $this->User->typeId;
                }

                //$_SESSION['pageview_time'] = date("Y-m-d H:i:s");
                $sessionId = session_id();

                $adminUserId = null;
                if (isset($_SESSION['AdminId'])) {
                    $adminUserId = $_SESSION['AdminId'];
                }

                $this->UserDAO->setNewLogin($token, $sessionId, $adminUserId);
                $aryOtherFields = array(
                    "Conn" => $this->UserDAO->Conn,
                );
                if ($this->Data['Ip'] != null) {
                    $aryOtherFields["Ip"] = $this->Data['Ip'];
                }
                ResultLogDAO::addLogEntry($this->User->idUsers, 1, $aryOtherFields);
                if ($this->User->typeId == 5) { // Salesman Logged In
                    return 2;
                } else if ($this->User->typeId == 8) { // Patient Admin Logged In
                    return 6;
                } else if ($this->User->typeId != 1) {
                    if ($this->User->typeId == 7) {
                        return 5;
                    }

                    if (!$this->User->hasOrderEntrySetting(1)) {
                        return 1; // Client/Doctor Logged In with result search
                    } else {
                        return 3; // client/doctor with result search disabled
                    }


                } else {
                    return 4; // Administrator Logged In
                }
            }
        }
        return 0; // result user not found
    }

    public function orderEntryAdminLogin() {
        if (isset($_SESSION['AdminId'])) {
            $userId = $_SESSION['AdminId'];


            return true;
        } else {
            $this->logout(true);
        }

    }

    public function logout($destroySession = true) {
        if (isset($_SESSION) && isset($_SESSION['id']) && !empty($_SESSION['id'])) {
            $id = $_SESSION['id'];
            if (isset($_SESSION['y']) && isset($_SESSION['z'])) {
                require_once 'DOS/Proxy.php';

                $key = $_SESSION['y'];
                $iv = $_SESSION['z'];

                $aryEncData = array(
                    "id" => $_SESSION['id'],
                    "type" => $_SESSION['type']
                );
                if (array_key_exists("token", $_SESSION)) {
                    $aryEncData['token'] = $_SESSION['token'];
                }

                $proxy = new Proxy(array(
                    "Key" => $key,
                    "InitVector" => $iv,
                    "EncData" => $aryEncData
                ));
                $proxy->decrypt();
                $rawData = $proxy->getRawData();
                $id = $rawData['id'];

            }



            if (isset($this->UserDAO->Conn) && $this->UserDAO->Conn != null && $this->UserDAO->Conn instanceof mysqli) {
                AdminDAO::logout($id, $this->Data['Ip'], array("Conn" => $this->UserDAO->Conn));
            } else {
                AdminDAO::logout($id, $this->Data['Ip']);
            }

            ResultLogDAO::addLogEntry($id, 9, array("Conn" => $this->UserDAO->Conn, "Ip" => $this->Data['Ip']));
        }

        try {
            if ($destroySession && isset($_SESSION)) {
                $_SESSION = array();
                /*if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(
                        session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }*/

                if (session_status() == PHP_SESSION_ACTIVE) {
                    session_destroy();
                }
            }
        } catch (exception $e) {
            error_log("Caught exception on PageClient->logout(): " . $e->getMessage());
        }
    }

    public function generateNewHash($verificationCodeLength = 32) {
        $this->generateSalt();
        $this->generateHash();
        $this->generateVerificationCode($verificationCodeLength, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');
    }

    private function generateHash() {
        if ($this->Data['SaltIsSet'] && !$this->Data['HashIsSet']) {
            $this->Data['Password'] = $this->hashData($this->Data['Password'] . $this->Data['Salt']);
            $this->Data['HashIsSet'] = true;
        } else {
            die("Error 0");
        }
    }

    private function generateSalt() {
        if (!$this->Data['SaltIsSet']) {
            $intermediateSalt = md5(uniqid(rand(), true));
            $this->Data['Salt'] = substr($intermediateSalt, 0, $this->Data['MAX_LENGTH']);
            $this->Data['SaltIsSet'] = true;
        } else {
            die("Error 1");
        }
    }

    private function hashData($data) {
        //return hash_hmac('sha512', $data, $this->_siteKey);
        return md5($data);
    }

    public static function getPasswordHash($password, $salt) {
        $passwordWithSalt = $password . $salt;
        return md5($passwordWithSalt);
    }

    public function setHash($salt) {
        $this->Data['Salt'] = $salt;
        $this->Data['SaltIsSet'] = true;
        $this->generateHash();
    }

    public function getNewUserInfo($password, $verificationCodeLength = 32) {
        $aryReturn = array();

        $intermediateSalt = md5(uniqid(rand(), true));
        $salt = substr($intermediateSalt, 0, 6);

        $aryReturn[] = $salt;
        $aryReturn[] = $this->hashData($password . $salt);

        $aryReturn[] = $this->generateVerificationCode2($verificationCodeLength, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');

        return $aryReturn;
    }

    // Generate a random character string
    private function generateVerificationCode($length = 32, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890') {
        // Length of character list
        $chars_length = (strlen($chars) - 1);

        // Start our string
        $code = $chars[rand(0, $chars_length)];

        // Generate random string
        for ($i = 1; $i < $length; $i = strlen($code)) {
            // Grab a random character from our list
            $r = $chars[rand(0, $chars_length)];

            // Make sure the same two characters don't appear next to each other
            if ($r != $code[$i - 1])
                $code .= $r;
        }
        $this->Data['VerificationCode'] = $code;
    }

    public static function generateVerificationCode2($length = 32, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890') {
        // Length of character list
        $chars_length = (strlen($chars) - 1);

        // Start our string
        $code = $chars[rand(0, $chars_length)];

        // Generate random string
        for ($i = 1; $i < $length; $i = strlen($code)) {
            // Grab a random character from our list
            $r = $chars[rand(0, $chars_length)];

            // Make sure the same two characters don't appear next to each other
            if ($r != $code[$i - 1])
                $code .= $r;
        }
        return $code;
    }

    private function getRandomString($length = 50, $characters = null) {
        if ($characters == null) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        }

        $string = '';

        for ($p = 0; $p < $length; $p++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        return $string;
    }

    public static function getRandomString2($length = 50, $characters = null) {
        if ($characters == null) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        }

        $string = '';

        for ($p = 0; $p < $length; $p++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        return $string;
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

        if ($ip === '::1') {
            $ip = "127.0.0.1";
        }

        if (isset($ip) && $ip != null && !empty($ip)) {
            $this->Data['Ip'] = ip2long($ip);
        }
    }


    protected function getIpAddress() {
        return $this->Data['Ip'];
    }


    public function __get($field) {
        $value = "";
        if (array_key_exists($field, $this->Data)) {
            $value = $this->Data[$field];
        } else if ($field == "User") {
            $value = $this->User;
        } else if ($field == "UserDAO") {
            $value = $this->UserDAO;
        } else if ($field == "Error") {
            $value = $this->Error;
        } else if ($field == "Conn") {
            $value = $this->UserDAO->Conn;
        }
        return $value;
    }

    public function encrypt($field, $salt = "@dd3dS@1t") {
        $var = $field . $salt;
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', 'ecb', '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $salt, $iv);
        $encrypted_data = mcrypt_generic($td, $var);

        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        $encoded_64 = base64_encode($encrypted_data);

        return $encoded_64;
    }

    public function decrypt($field, $salt = "@dd3dS@1t") {
        $decoded_64 = base64_decode($field);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', 'ecb', '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $salt, $iv);

        $decrypted_data = mdecrypt_generic($td, $decoded_64);

        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        return $decrypted_data;
    }

    // http://stackoverflow.com/a/11741586
    protected function isIe() {
        //preg_match('/(?i)msie [5-8]/',$_SERVER['HTTP_USER_AGENT']);
        preg_match('/(?i)MSIE (.*?);/', $_SERVER['HTTP_USER_AGENT'], $matches);
        if(count($matches) < 2){
            // preg_match('/Trident\/\d{1,2}.\d{1,2}; rv:([0-9]*)/', $_SERVER['HTTP_USER_AGENT'], $matches);
            preg_match('/Trident\/\d{1,2}.\d{1,2};/', $_SERVER['HTTP_USER_AGENT'], $matches);
        }
        if (count($matches) >= 1){
            return true;
        }
        return false;
    }

    public static function destroySession() {
        $_SESSION = array();
        unset($_SESSION);
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}

?>
