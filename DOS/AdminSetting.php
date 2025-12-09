<?php
require_once 'BaseObject.php';

/**
 * Description of AdminSettings
 *
 * @author Edd
 */
class AdminSetting extends BaseObject {
    
    protected $Data = array (
        "idAdminSettings" => "",
        "settingName" => "",
        "settingDescription" => "",
        "isMasterSetting" => false,
        "isActive" => true
    );
    
    public function __construct(array $data) {
        parent::__construct($data);
        if (array_key_exists("adminSettingName", $data)) {
            $this->Data['settingName'] = $data['adminSettingName'];
        }
        if (array_key_exists("adminSettingDescription", $data)) {
            $this->Data['settingDescription'] = $data['adminSettingDescription'];
        }
        if (array_key_exists("adminSettingIsActive", $data)) {
            $this->Data['isActive'] = $data['adminSettingIsActive'];
        }
    }
    
}

?>
