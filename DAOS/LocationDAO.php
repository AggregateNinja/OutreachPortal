<?php
require_once 'DataObject.php';
require_once 'DOS/Location.php';
/**
 * Description of LocationDAO
 *
 * @author Edd
 */
class LocationDAO extends DataObject {
    public static function getLocations(array $settings = null) {
        $aryLocations = array();
        
        $sql = "SELECT idLocation, locationNo, locationName, address1, address2, city, state, zip
            FROM " . self::TBL_LOCATIONS;
        $data = parent::select($sql, null, $settings);
        foreach ($data as $row) {
            $location = new Location($row);
            $aryLocations[] = $location;
        }
        
        return $aryLocations;
        
    }
}

?>
