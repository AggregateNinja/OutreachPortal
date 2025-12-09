<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 11/6/15
 * Time: 12:27 PM
 */

require_once 'BaseObject.php';

/**
 * Description of OrderComment
 *
 * @author Edd
 */
class OrderAccessSetting extends BaseObject {
    protected $Data = array (
        "idAccessSettings" => "",
        "settingName" => "",
        "settingDescription" => "",
        "isActive" => true,
        "selectedByDefault" => false

    );

    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);
        }

    }

    public function __isset($field) {
        $isset = parent::__isset($field);

        if (!$isset) {
            if ($field == "OrderAccessSetting" && isset($this->Data) && is_array($this->Data) && array_key_exists("idAccessSettings", $this->Data)) {
                $isset = true;
            }
        }

        return $isset;
    }

}

?>
