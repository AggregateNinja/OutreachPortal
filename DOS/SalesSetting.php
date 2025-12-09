<?php
require_once 'BaseObject.php';

/**
 * Description of UserSettings
 *
 * @author Edd
 */
class SalesSetting extends BaseObject {

    protected $Data = array (
        "idSalesSettings" => "",
        "settingName" => "",
        "settingDescription" => "",
        "pageName" => "",
        "isActive" => true
    );

    public function __construct(array $data) {
        parent::__construct($data);
        if (array_key_exists("salesSettingName", $data)) {
            $this->Data['settingName'] = $data['salesSettingName'];
        }
        if (array_key_exists("salesSettingDescription", $data)) {
            $this->Data['settingDescription'] = $data['salesSettingDescription'];
        }
        if (array_key_exists("salesSettingPageName", $data)) {
            $this->Data['pageName'] = $data['salesSettingPageName'];
        }
        if (array_key_exists("salesSettingIsActive", $data)) {
            $this->Data['isActive'] = $data['salesSettingIsActive'];
        }
    }

}

?>
