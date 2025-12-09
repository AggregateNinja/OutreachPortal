<?php
require_once 'User.php';
require_once 'UserSetting.php';
require_once 'DoctorUser.php';

/**
 * Description of ClientUser
 *
 * @author Edd
 */
class ClientUser extends User{

    /*
     * Parent Fields
     *  - private $Data = array();
     *  - private $LoggedInUser = array();
    */

    protected $Client = array (
        "idClients" => "",
        "clientNo" => "",
        "clientName" => "",
        "firstName" => "",
        "lastName" => "",
        "clientStreet" => "",
        "clientStreet2" => "",
        "clientCity" => "",
        "clientState" => "",
        "clientZip" => "",
        "phoneNo" => "",
        "faxNo" => "",
        "webEnabled" => "",
        "resultCopies" => "",
        "location" => "",
        "resReport1" => "",
        "resReport2" => "",
        "resReport3" => "",
        "resPrint" => "",
        "transType" => "",
        "statCode" => "",
        "contact1" => "",
        "contact2" => "",
        "hl7Enabled" => "",
        "defaultReportType" => "",
        "npi" => "",

        "idLocation" => "",
        "locationNo" => "",
        "locationName" => ""
    );

    private $MultiUsers = array(); // an array of userId's that this client has access too

    protected $UserSettings = array();

    protected $DoctorUsers = array();

    public $AutoTests = array();

    public $RequiredFieldNames = array();

    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);

            foreach ($data as $key => $value) {
                if (array_key_exists($key, $this->Client)) {
                    $this->Client[$key] = $value;
                } else if (array_key_exists("clientFirstName", $data)) {
                    $this->Client['firstName'] = $data['clientFirstName'];
                } else if (array_key_exists("clientLastName", $data)) {
                    $this->Client['lastName'] = $data['clientLastName'];
                }
            }

            if (array_key_exists("idUserSettings", $data) && !empty($data['idUserSettings'])) {
                $userSetting = new UserSetting($data);
                $this->UserSettings[$userSetting->idUserSettings] = $userSetting;
            }

            if (array_key_exists("iddoctors", $data) && !empty($data['iddoctors'])) {
                //echo "<pre>"; print_r($data); echo "</pre>";
                //            $doctorUser = new DoctorUser(array(
                //               "clientId" => $data['clientId'],
                //               "iddoctors" => $data['iddoctors'],
                //               "number" => $data['number'],
                //               "firstName" => $data['firstName'],
                //               "lastName" => $data['lastName'],
                //               "address1" => $data['address1'],
                //               "city" => $data['city'],
                //               "state" => $data['state'],
                //               "zip" => $data['zip']
                //            ));
                $doctorUser = new DoctorUser($data);
                $this->DoctorUsers[$data['iddoctors']] = $doctorUser;
            }

            if (array_key_exists("requiredFieldNames", $data) && is_array($data['requiredFieldNames']) && count($data['requiredFieldNames']) > 0) {
                $this->RequiredFieldNames = $data['requiredFieldNames'];
            }

        }
    }

    public function setAutoTests(array $tests) {
        $this->AutoTests = $tests;
    }

    public function getAutoTests() {
        return $this->AutoTests;
    }

    public function __isset($field) {
        $isset = parent::__isset($field);

        if (!$isset) {
            if (($field == "ClientUser" || $field == "Client") && isset($this->Client) && is_array($this->Client) && array_key_exists("idClients", $this->Client)) {
                $isset = true;
            } else if ($field == "MultiUsers" && isset($this->MultiUsers) && is_array($this->MultiUsers) && count($this->MultiUsers) > 0) {
                $isset = true;
            }
        }

        return $isset;
    }

    public function addDoctor(array $data) {
        //echo "<pre>"; print_r($data); echo "</pre>";
        if (!$this->hasDoctor($data['iddoctors'])) {
            $doctorUser = new DoctorUser($data);
            $this->DoctorUsers[$data['iddoctors']] = $doctorUser;
        }
    }

    private function hasDoctor($iddoctors) {
        if (!array_key_exists($iddoctors, $this->DoctorUsers)) {
            return false;
        }
        return true;
    }

    //    public function addUserSetting($data) {
    //        $userSetting = new UserSetting($data);
    //        $this->UserSettings[$userSetting['idUserSetting']] = $userSetting;
    //    }

    public function setMultiUsers(array $data) {
        foreach ($data as $value) {
            $this->MultiUsers[] = $value;
        }
    }


    public function setAll(array $data, $multiUsers = null) {
        parent::setAll($data);

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->Client))
                $this->Client[$key] = $value;
        }

        if ($multiUsers != null && is_array($multiUsers)) {
            $this->MultiUsers = $multiUsers;
        }
    }

    public function __get($key) {
        $field = parent::__get($key);

        if (empty($field) && $key != "LastLogin" && $key != "idLoggedIn"  && $key != "ip" && $key != "isActive" && $key != "loginDate"
            && $key != "IsRestrictedUser" && $key != "RestrictedUserIds" && $key != "OrderEntrySettings" && $key != "CommonDiagnosisCodes"
            && $key != "CommonDrugs" && $key != "CommonTests") {
            if (array_key_exists($key, $this->Client)) {
                $field = $this->Client[$key];
            } else if ($key == "MultiUsers") {
                $field = $this->MultiUsers;
            } else if ($key == "Client") {
                $field = $this->Client;
            } else if ($key == "UserSettings") {
                $field = $this->UserSettings;
            } else if ($key == "DoctorUsers") {
                $field = $this->DoctorUsers;
            } else {
                //die ("Client User Field Not Found: $key");
                error_log("Client User Field Not Found: $key");
                return null;
            }
        }

        return $field;
    }

    public function __set($field, $value) {
        $done = parent::__set($field, $value);
        if (!$done) {
            if (array_key_exists($field, $this->Client)) {
                $this->Client[$field] = $value;
                $done = true;
            }
        }
        return $done;
    }
}
?>
