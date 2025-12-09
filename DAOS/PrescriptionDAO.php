<?php
require_once 'DataObject.php';
require_once 'DOS/Prescription.php';

/**
 * Description of PrescriptionDAO
 *
 * @author Edd
 */
class PrescriptionDAO extends DataObject {
    public static function insertPrescriptions(array $aryPrescriptions) {
        $sql = "INSERT INTO " . self::DB_CSS_WEB . "." . self::TBL_PRESCRIPTIONS . " (orderId, drugId, advancedOrder) VALUES ";
        
        $input = array();
        foreach ($aryPrescriptions as $prescription) {
            $sql .= "(?, ?, ?), ";
            $input[] = $prescription->orderId;
            $input[] = $prescription->drugId;
            $input[] = $prescription->advancedOrder;
        }
        $sql = substr($sql, 0, strlen($sql) - 2);
        parent::manipulate($sql, $input, array("ConnectToWeb" => true));         
    }
    
    public static function insertPrescription(Prescription $prescription, mysqli $conn) {
        $sql = "INSERT INTO " . self::DB_CSS_WEB . "." . self::TBL_PRESCRIPTIONS . " (orderId, drugId, advancedOrder) VALUES (?, ?, ?)";
        $input = array(
            $prescription->orderId, 
            $prescription->drugId,
            $prescription->advancedOrder
        );
        return parent::manipulate($sql, $input, array("Conn" => $conn));
    }
    
    public static function deletePrescriptions($idOrders) {
        $sql = "DELETE FROM " . self::DB_CSS_WEB . "." . self::TBL_PRESCRIPTIONS . " WHERE orderId = ?";
        parent::manipulate($sql, array($idOrders), array("ConnectToWeb" => true));
        return true;
    }
    
    public static function getPrescriptions(array $input, array $settings = null) {
        $isAdvancedOrder = 0;
        $sqlSettings = array("ConnectToWeb" => true);
        if ($settings != null) {
            if (array_key_exists("IsAdvancedOrder", $settings) && $settings['IsAdvancedOrder'] == true) {
                $isAdvancedOrder = 1;
            }
            if (array_key_exists("Conn", $settings) && $settings['Conn'] instanceof mysqli) {
            	$sqlSettings['Conn'] = $settings['Conn'];
            	unset($sqlSettings['ConnectToWeb']);
            }
        }
        $sql = "
            SELECT p.orderId, p.drugId, d.iddrugs, d.genericName, s.idsubstances AS `idsubstances1`, s.substance AS `substance1`
            FROM " . self::TBL_PRESCRIPTIONS . " p
            INNER JOIN " . self::DB_CSS . "." . self::TBL_DRUGS . " d ON p.drugId = d.iddrugs
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SUBSTANCES . " s ON d.substance1 = s.idsubstances
            WHERE p.orderId = ?
            ORDER BY d.genericName ";

        $data = parent::select($sql, array($input['idOrders']), $sqlSettings);

        $aryPrescriptions = array();
        if (count($data) > 0) {
            foreach ($data as $row) {
                $script = new Prescription($row);
                $script->advancedOrder = $isAdvancedOrder;
                $aryPrescriptions[] = $script;
            }
            return $aryPrescriptions;
        }
        
        return false;
        
    }
}

?>
