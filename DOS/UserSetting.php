<?php
require_once 'BaseObject.php';

/**
 * Description of UserSettings
 *
 * @author Edd
 */
class UserSetting extends BaseObject {
    
    protected $Data = array (
        "idUserSettings" => "",
        "settingName" => "",
        "settingDescription" => "",
        "pageName" => ""
    );
    
    public function __construct(array $data) {
        parent::__construct($data);
        if (array_key_exists("userSettingName", $data)) {
            $this->Data['settingName'] = $data['userSettingName'];
        }
        if (array_key_exists("userSettingDescription", $data)) {
            $this->Data['settingDescription'] = $data['userSettingDescription'];
        }
        if (array_key_exists("userSettingPageName", $data)) {
            $this->Data['pageName'] = $data['userSettingPageName'];
        }
    }
    
}

?>
