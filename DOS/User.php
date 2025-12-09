<?php
require_once 'BaseObject.php';
require_once 'UserSetting.php';
require_once 'Preference.php';
require_once 'LogEntry.php';
require_once 'DOS/Test.php';
require_once 'DiagnosisCode.php';
require_once 'OrderAccessSetting.php';
require_once 'Drug.php';

/**
 * Description of User
 *
 * @author Edd
*/
class User extends BaseObject {
    protected $Data = array(
        "idUsers" => "",
        "typeId" => "",
        "email" => "",
        "password" => "",
        "userSalt" => "",
        "isVerified" => "",
        "verificationCode" => "",
        "dateCreated" => "",
        "dateUpdated" => "",
        "lastLogin" => "",
        "isActive" => true
    );
    
    protected $LoggedInUser = array (
        "idLoggedIn" => "",
        "userId" => "",
        "sessionId" => "",
        "token" => "",
        "ip" => null,
        "loginDate" => ""
    );
    
    protected $UserSettings = array();

    protected $OrderEntrySettings = array();

    protected $OrderAccessSetting;
    protected $RestrictedUserIds = array();
    
    protected $Preferences = array();
    
    protected $SearchLogEntries = array();
    
    protected $LastLogin;
    
    protected $CommonTests;
    
    protected $ExcludedTests;

    protected $CommonDrugs = array();
    
    protected $CommonDiagnosisCodes;

    public $IsRestrictedUser = false;
    
    public function __construct(array $data = null) {
        if ($data != null && !empty($data)) {
            parent::__construct($data);

            if (count($data) > 0) {
                foreach ($data as $key => $value) {
                    if (array_key_exists($key, $this->LoggedInUser))
                        $this->LoggedInUser[$key] = $value;
                }
            }
        }

        
        $this->LastLogin = new LogEntry();
        $this->CommonTests = array();
        $this->ExcludedTests = array();
        $this->CommonDiagnosisCodes = array();
        $this->CommonDrugs = array();

    }

    public function setAccessSetting(OrderAccessSetting $setting) {
        $this->OrderAccessSetting = $setting;
    }
    
    public function addSearchLogEntry(array $dataRow) {
        $this->SearchLogEntries[$dataRow['idLogs']] = new LogEntry($dataRow);               
    }
    
    public function addCommonDiagnosisCode(array $dataRow = null) {
        if ($dataRow != null) {
            $this->CommonDiagnosisCodes[$dataRow['idDiagnosisCodes']] = new DiagnosisCode($dataRow);
        } else {
            $this->CommonDiagnosisCodes = array(null);
        }
    }
    
    public function addPreferences(array $data) {
        
        foreach ($data as $row) {
            $preference = new Preference($row);
            $this->Preferences[$row['key']] = $preference;
        }
    }
    
    public function addCommonTest(array $dataRow = null) {
        if ($dataRow != null) {
            $this->CommonTests[$dataRow['idtests']] = new Test($dataRow);
        } else {
            $this->CommonTests = array(null);
        }
    }

    public function addCommonDrug(array $dataRow = null) {
        if ($dataRow != null) {
            $this->CommonDrugs[$dataRow['drugId']] = new Drug($dataRow, true);
        } else {
            $this->CommonDrugs = array(null);
        }
    }

    public function hasCommonTest($testId) {
        foreach ($this->CommonTests as $test) {
            if ($testId == $test->idtests) {
                return true;
            }
        }
        return false;
    }
    
    public function addExcludedTest(array $dataRow = null) {
        if ($dataRow != null) {
            $this->ExcludedTests[$dataRow['idtests']] = new Test($dataRow);
        } else {
            $this->ExcludedTests = array(null);
        }
           
    }
    
    public function hasExcludedTest($testId) {
        foreach ($this->ExcludedTests as $test) {
            if ($testId == $test->idtests) {
                return true;
            }
        }
        return false;
    }
    
    public function setLastLogin(array $dataRow) {
        $this->LastLogin = new LogEntry($dataRow);
    }
    
    public function hasPreference($key) {
        
        foreach($this->Preferences as $preference) {
            if ($preference->key == $key) {
                return true;
            }
        }
        
        return false;
    }
    
    public function hasPMByDepartment() {
        if ($this->hasPreference("PMByDepartment")) {
            if ($this->Preferences['PMByDepartment'] == true) {
                return true;
            }
        }
        return false;
    }
    
    public function addUserSetting(array $data) {
        $userSetting = new UserSetting($data);
        $this->UserSettings[$userSetting->idUserSettings] = $userSetting;
        
        //$this->UserSettings[$userSetting['idUserSetting']][] = $userSetting;
    }

    public function addOrderEntrySetting(array $data) {
        $orderEntrySetting = new OrderEntrySetting($data);
        $this->OrderEntrySettings[$orderEntrySetting->idOrderEntrySettings] = $orderEntrySetting;

        //$this->UserSettings[$userSetting['idUserSetting']][] = $userSetting;
    }
    
    public function hasUserSetting($idUserSettings) {
        foreach ($this->UserSettings as $userSettingId => $setting) {
            if ($userSettingId == $idUserSettings) {
                return true;
            }
        }
        return false;
    }

    public function hasOrderEntrySetting($idOrderEntrySettings) {
        foreach ($this->OrderEntrySettings as $orderEntrySettingId => $setting) {
            if ($orderEntrySettingId == $idOrderEntrySettings) {
                return true;
            }
        }
        return false;
    }


    public function hasUserSettingByName($settingName) {
        $settingName = strtolower(trim($settingName));
        foreach ($this->UserSettings as $setting) {
            if (strtolower(trim($setting->settingName)) == $settingName) {
                return true;                
            }
        }
        return false;
    }

    public function setRestrictedUserIds(array $ids) {
        $this->RestrictedUserIds = $ids;
    }
    
    //public function getUserSetting($idUserSetting)

    public function addRestrictedUserId($id) {
        $this->RestrictedUserIds[] = $id;
    }
    
    public function __get($key) {
        $field = parent::__get($key);
        
        if (empty($field)) {
            if (array_key_exists($key, $this->LoggedInUser)) {
                $field = $this->LoggedInUser[$key];
            } else if ($key == "LoggedInUser") {
                $field = $this->LoggedInUser;
            } else if ($key == "SearchLogEntries") {
                $field =  $this->SearchLogEntries;
            } else if ($key == "LastLogin") {
                $field = $this->LastLogin;
            } else if ($key == "CommonTests") {
                $field = $this->CommonTests;
            } else if ($key == "ExcludedTests") {
                $field = $this->ExcludedTests;
            } else if ($key == "CommonDiagnosisCodes") {
                $field = $this->CommonDiagnosisCodes;
            } else if ($key == "UserSettings") {
                $field = $this->UserSettings;
            } else if ($key == "OrderEntrySettings") {
                $field = $this->OrderEntrySettings;
            } else if ($key == "OrderAccessSetting") {
                $field = $this->OrderAccessSetting;
            } else if ($key == "RestrictedUserIds") {
                $field = $this->RestrictedUserIds;
            } else if ($key == "CommonDrugs") {
                $field = $this->CommonDrugs;
            }
        }
        
        return $field;
    }
    
    public function __set($key, $value) {
        $done = false;
        if (array_key_exists($key, $this->Data)) {
            $this->Data[$key] = $value;
            $done = true;
        } else if (array_key_exists($key, $this->LoggedInUser)) {
            $this->LoggedInUser[$key] = $value;
            $done = true;
        }
        return $done;
    }
    
    public function __isset($name) {
        $isset = parent::__isset($name);
        
        if (!$isset) {
            if ($name == "CommonDiagnosisCodes") {
                if (isset($this->CommonDiagnosisCodes) && is_array($this->CommonDiagnosisCodes) && count($this->CommonDiagnosisCodes) > 0) {
                    $isset = true;
                }
            } else if ($name == "CommonTests") {
                if (isset($this->CommonTests) && is_array($this->CommonTests) && count($this->CommonTests) > 0) {
                    $isset = true;
                }
            } else if ($name == "ExcludedTests") {
                if (isset($this->ExcludedTests) && is_array($this->ExcludedTests) && count($this->ExcludedTests) > 0) {
                    $isset = true;
                }
            } else if ($name == "UserSettings") {
                if (isset($this->UserSettings) && is_array($this->UserSettings) && count($this->UserSettings) > 0) {
                    $isset = true;
                }
            } else if ($name == "LoggedInUser" && isset($this->LoggedInUser) && is_array($this->LoggedInUser) && array_key_exists("loginDate", $this->LoggedInUser) && $this->LoggedInUser['loginDate'] != null && !empty($this->LoggedInUser['loginDate'])) {
                $isset = true;
            } else if ($name == "OrderEntrySettings" && isset($this->OrderEntrySettings) && is_array($this->OrderEntrySettings) && count($this->OrderEntrySettings) > 0) {
                $isset = true;
            } else if ($name == "OrderAccessSetting" && isset($this->OrderAccessSetting) && $this->OrderAccessSetting instanceof OrderAccessSetting) {
                $isset = true;
            } else if ($name == "RestrictedUserIds" && isset($this->RestrictedUserIds) && is_array($this->RestrictedUserIds) && count($this->RestrictedUserIds) > 0) {
                $isset = true;
            } else if ($name == "CommonDrugs" && isset($this->CommonDrugs) && is_array($this->CommonDrugs) && count($this->CommonDrugs) > 0) {
                $isset = true;
            }
        }
        
        return $isset;
    }
    
    public function setAll(array $data) {
        parent::setAll($data);
        
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->LoggedInUser))
                $this->LoggedInUser[$key] = $value;
        }  
    }
    
    public function __toString() {
        $strUser = parent::__toString();
        
        $strUser .= "<strong>LoggedInUser Fields:</strong> <br/>";
        foreach ($this->LoggedInUser as $key => $value) {
            $strUser .= $key . ": " . $value . "<br />";
        }
        $strUser .= "<br />";
        
        if (count($this->UserSettings) > 0) {
            $strUser .= "<strong>User Settings:</strong> <br/>";
            foreach ($this->UserSettings as $setting) {
                $strUser .= $setting->__toString();
            }
            $strUser .= "<br />";
        }
        
        if (count($this->Preferences) > 0) {
            $strUser .= "<strong>Preferences:</strong> <br/>";
            foreach ($this->Preferences as $preference) {
                $strUser .= $preference->__toString();
            }
            $strUser .= "<br />";
        }
        
        if (count($this->SearchLogEntries) > 0) {
            $strUser .= "<strong>SearchLogEntries:</strong> <br/>";
            foreach ($this->SearchLogEntries as $logEntry) {
                $strUser .= $logEntry->__toString();
            }
            $strUser .= "<br />";
        }
        
        if (!empty($this->LastLogin)) {
            $strUser .= "<strong>LastLogin:</strong> <br/>";
            $strUser .= $this->LastLogin->__toString();
            $strUser .= "<br />";
        }
        
        return $strUser;
    }
    
    
}

?>
