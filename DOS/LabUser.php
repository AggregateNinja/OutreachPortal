<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 4/13/15
 * Time: 1:18 PM
 */

require_once 'BaseObject.php';

class LabUser extends BaseObject {

    protected $Data = array (
        "idUser" => "",
        "logon" => "",
        "password" => "",
        "lastLogon" => "",
        "isAdmin" => null,
        "ugroup" => 1,
        "position" => "",
        "createdBy" => "",
        "created" => "",
        "employeeId" => "",
        "active" => 1,
        "changePassword" => 1
    );

    protected $LoggedInUser = array (
        "idLoggedIn" => "",
        "userId" => "",
        "sessionId" => "",
        "token" => "",
        "ip" => null,
        "loginDate" => ""
    );

    public function __construct(array $data = null) {
        if ($data != null && !empty($data)) {
            parent::__construct($data);

            foreach ($data as $key => $value) {
                if (array_key_exists($key, $this->LoggedInUser))
                    $this->LoggedInUser[$key] = $value;
            }
        }
    }

    public function __get($key) {
        $field = parent::__get($key);

        if (empty($field)) {
            if (array_key_exists($key, $this->LoggedInUser)) {
                $field = $this->LoggedInUser[$key];
            } else if ($key == "LoggedInUser") {
                $field = $this->LoggedInUser;
            }
        }

        return $field;
    }

    public function __isset($name) {
        $isset = parent::__isset($name);

        if (!$isset) {
            if ($name == "LoggedInUser" && isset($this->LoggedInUser) && is_array($this->LoggedInUser) && array_key_exists("loginDate", $this->LoggedInUser) && $this->LoggedInUser['loginDate'] != null && !empty($this->LoggedInUser['loginDate'])) {
                $isset = true;
            }
        }

        return $isset;
    }
} 