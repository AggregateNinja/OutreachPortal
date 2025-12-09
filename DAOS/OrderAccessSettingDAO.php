<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 11/6/15
 * Time: 12:31 PM
 */

require_once 'DataObject.php';
require_once 'DOS/OrderAccessSetting.php';

class OrderAccessSettingDAO extends DataObject {

    public static function getSettings(array $settings = null) {
        $sql = "SELECT * FROM " . self::DB_CSS . "." . self::TBL_ORDERACCESSSETTINGS . ";";

        $data = parent::select($sql, null, $settings);

        $arySettings = array();
        if (count($data) > 0) {
            foreach($data as $row) {
                $arySettings[] = new OrderAccessSetting($row);
            }
        }

        return $arySettings;
    }
}

?>
