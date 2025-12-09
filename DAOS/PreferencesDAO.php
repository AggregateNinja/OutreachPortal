<?php
require_once 'DataObject.php';
require_once 'DOS/Preference.php';

class PreferencesDAO extends DataObject {
    public static function getPreferences(array $settings = null) {
        $sql = "SELECT p.idpreferences, p.key, p.value, p.type FROM " . self::TBL_PREFERENCES . " p ";
        $data = parent::select($sql, null, $settings);
        
        if (count($data) > 0) {
            $aryPreferences = array();
            foreach ($data as $row) {
                $currPref = new Preference($row);
                $aryPreferences[] = $currPref;
            }
            return $aryPreferences;
        }
        
        return false;
    }

    public static function getPreferenceByKey($key, array $settings = null) {
        $sql = "SELECT p.idpreferences, p.key, p.value, p.type FROM " . self::TBL_PREFERENCES . " p WHERE p.key = ?";
        $data = parent::select($sql, array($key), $settings);

        if (count($data) > 0) {
            return new Preference($data[0]);
        }
        return null;
    }
    
    
    public static function getPositivePreferenceId() {
        $sql = "SELECT p.value FROM " . self::TBL_PREFERENCES . " p WHERE p.key = ?";
        $data = parent::select($sql, array("Positive"));
        if (count($data) > 0) {
            return $data[0]['value'];
        }
        return null;
    }
    
    public static function getNegativePreferenceId() {
        $sql = "SELECT p.value FROM " . self::TBL_PREFERENCES . " p WHERE p.key = ?";
        $data = parent::select($sql, array("Negative"));
        if (count($data) > 0) {
            return $data[0]['value'];            
        }
        return null;
    }

    public static function getPOCTest(array $settings = null) {
        $sql = "SELECT p.value FROM " . self::TBL_PREFERENCES . " p WHERE p.key = 'POCTest'";
        $data = parent::select($sql, null, $settings);
        if (count($data) > 0) {
            return $data[0]['value'];
        }
        return null;
    }

    public static function billingIsActive(array $settings = null) {
        $sql = "SELECT p.value FROM " . self::TBL_PREFERENCES . " p WHERE p.key = 'CSSBillingEnabled'";

        $data = parent::select($sql, null, $settings);

        if (count($data) > 0 && $data[0]['value'] == true) {
            return true;
        }

        return false;

    }


}




?>
