<?php
require_once 'DataObject.php';
require_once 'DOS/Phlebotomy.php';
require_once 'DOS/Employee.php';

class PhlebotomyDAO extends DataObject {
    public static function insertPhlebotomy(Phlebotomy $phlebotomy) {
        //echo "<pre>"; print_r($phlebotomy); echo "</pre>";
        $sql = "
            INSERT INTO " . self::TBL_PHLEBOTOMY . " (idAdvancedOrder, idOrders, startDate, drawCount, frequency, phlebotomist, drawComment1, drawComment2)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $qryInput = array(
            $phlebotomy->idAdvancedOrder,
            $phlebotomy->idOrders,
            date("Y-m-d h:i:s", strtotime($phlebotomy->startDate)),
            $phlebotomy->drawCount,
            $phlebotomy->frequency,
            $phlebotomy->phlebotomist,
            $phlebotomy->drawComment1,
            $phlebotomy->drawComment1
        );
        //echo $sql;
        //echo "<pre>"; print_r($qryInput); echo "</pre>";
        return parent::manipulate($sql, $qryInput, array("ConnectToWeb" => true, "LastInsertId" => true));
    }
    public static function updatePhlebotomy(Phlebotomy $phlebotomy) {
        //echo "<pre>"; print_r($phlebotomy); echo "</pre>";
        
        $sql = "
            UPDATE " . self::TBL_PHLEBOTOMY . "
            SET startDate = ?, drawCount = ?, frequency = ?, phlebotomist = ?, drawComment1 = ?, drawComment2 = ?
            WHERE idAdvancedOrder = ?";
        
        $qryInput = array(            
            date("Y-m-d h:i:s", strtotime($phlebotomy->startDate)),
            $phlebotomy->drawCount,
            $phlebotomy->frequency,
            $phlebotomy->phlebotomist,
            $phlebotomy->drawComment1,
            $phlebotomy->drawComment1,
            $phlebotomy->idAdvancedOrder
        );
        //echo $sql;
        //echo "<pre>"; print_r($qryInput); echo "</pre>";
        parent::manipulate($sql, $qryInput, array("ConnectToWeb" => true));
        
        return true;
    }
    
    public static function deletePhlebotomy(array $input, array $settings = null) {
        if (count($input) == 1) {
            $aryKey = array_keys($input); // get keys of $input as an array
            $key = $aryKey[0]; // get the first key of the $input array
            $value = $input[$key]; // get the first value of the $input array
            
            $sql = "DELETE FROM " . self::TBL_PHLEBOTOMY . " WHERE $key = ?";
            parent::manipulate($sql, array($value), array("ConnectToWeb" => true));
            return true;
        }
        return false;        
    }
    
    public static function getPhlebotomists(array $settings = null) {

        $sql = "
            SELECT      e.idemployees, e.firstName, e.lastName, e.department, e.position, e.homePhone, e.mobilePhone, e.address, e.address2, e.city, e.state, e.zip,
                        ed.name
            FROM        " . self::TBL_EMPLOYEES . " e
            INNER JOIN  " . self::TBL_EMPLOYEEDEPARTMENTS . " ed ON e.department = ed.idemployeeDepartments
            WHERE       ed.name = ?
            ORDER BY    lastName";
        $data = parent::select($sql, array('Phlebotomy'), $settings);
        
        if (count($data) > 0) {
            $aryPhlebotomists = array();
            foreach ($data as $row) {
                $aryPhlebotomists[$row['idemployees']] = new Employee($row);
            }
            //echo "<pre>"; print_r($aryPhlebotomists); echo "</pre>";
            return $aryPhlebotomists;
        }
        return false;
    }
    
    public static function getPhlebotomy(array $input, array $settings = null) {
    	$sqlSettings = array("ConnectToWeb" => true);
    	if ($settings != null) {
    		if (array_key_exists("Conn", $settings) && $settings['Conn'] instanceof mysqli) {
    			$sqlSettings['Conn'] = $settings['Conn'];
    			unset($sqlSettings['ConnectToWeb']);
    		}
    	}
    	
        if (count($input) == 1) {
            $aryKeys = array_keys($input);
            $key = $aryKeys[0];
            $value = reset($input);
            $sql = "
                SELECT idPhlebotomy, idAdvancedOrder, idOrders, startDate, drawCount, frequency, phlebotomist, drawComment1, drawComment2
                FROM " . self::TBL_PHLEBOTOMY . " p
                WHERE $key = ?";
            $data = parent::select($sql, array($value), $sqlSettings);
            
            if (count($data) == 1) {
                //echo "<pre>"; print_r($data); echo "</pre>";
                return new Phlebotomy($data[0]);
            }
        }
        
        return false;        
    }
}

?>
