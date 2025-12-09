<?php
require_once "DataObject.php";
require_once "DOS/ResultUserType.php";
/**
 * Description of ResultUserTypeDAO
 *
 * @author Edd
 */
class ResultUserTypeDAO extends DataObject {
    
    public static function getResultUserTypes(array $settings = null) {
        $sql = "
            SELECT idTypes, typeName, dateCreated
            FROM " . self::TBL_USERTYPES . " ut
            WHERE ut.isActive = ?";
        $aryInput = array(1);
        $types = parent::select($sql, $aryInput, $settings);
        
        $aryTypes = array();
        
        foreach ($types as $type) {
            $ruTypeObject = new ResultUserType();
            $ruTypeObject->setResultUserTypes($type);
            $aryRUTypes[] = $ruTypeObject;
        }
        
        return $aryRUTypes;
    }
    
    
}

?>
