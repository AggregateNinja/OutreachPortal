<?php
require_once 'User.php';
require_once 'AdminSetting.php';
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AdminUser
 *
 * @author Edd
 */
class AdminUser extends User {
    protected $AdminSettings = array ();

    public $clientId = null;

    public $adminClientIds = array();
    
    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);
            if (array_key_exists("idAdminSettings", $data) && !empty($data['idAdminSettings'])) {
                $adminSetting = new AdminSetting($data);
                $this->AdminSettings[$adminSetting->idAdminSettings] = $adminSetting;
            }
            if (array_key_exists("adminClientIds", $data) && count($data['adminClientIds']) > 0) {
                $this->adminClientIds = $data['adminClientIds'];
            }
        }
    }
    
    public function addAdminSetting(array $data) {
        $adminSetting = new AdminSetting($data);
        $this->AdminSettings[$adminSetting->idAdminSettings] = $adminSetting;
    }
    
    public function hasAdminSetting($idAdminSettings) {
        foreach ($this->AdminSettings as $adminSettingId => $setting) {
            if ($adminSettingId == $idAdminSettings) {
                return true;
            }
        }
        return false;
    }
    
    public function hasSettingByName($settingName) {
        $settingName = strtolower(trim($settingName));
        foreach ($this->AdminSettings as $setting) {
            if (strtolower(trim($setting->settingName)) == $settingName) {
                return true;                
            }
        }
        return false;
    }

    public function getAdminSettings() {
        return $this->AdminSettings;
    }

    public function __isset($field) {
        $isset = parent::__isset($field);

        if (!$isset) {
            if ($field == "AdminSettings" && is_array($this->AdminSettings) && count($this->AdminSettings) > 0) {
                $isset = true;
            }
        }
        return $isset;
    }


}

?>
