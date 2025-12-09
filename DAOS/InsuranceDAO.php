<?php
require_once 'DataObject.php';
require_once 'DOS/Insurance.php';
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of InsuranceDAO
 *
 * @author Edd
 */
class InsuranceDAO extends DataObject {
    public static function getInsurances(array $settings = null) {
        $aryInsurances = array();
        $input = null;
        $where = "";

        if ($settings != null) {
            if (array_key_exists("name", $settings) && $settings['name'] != null && !empty($settings['name'])) {
                if ($input == null) {
                    $input = array("%" . $settings['name'] . "%");
                    $where = "WHERE i.name LIKE ?";
                } else {
                    $input[] = "%" . $settings['name'] . "%";
                    $where .= " AND i.name LIKE ?";
                }
            }
            if (array_key_exists("phone", $settings) && $settings['phone'] != null && !empty($settings['phone'])) {
                if ($input == null) {
                    $input = array("%" . $settings['phone'] . "%");
                    $where = "WHERE i.phone LIKE ?";
                } else {
                    $input[] = "%" . $settings['phone'] . "%";
                    $where .= " AND i.phone LIKE ?";
                }
            }
            if (array_key_exists("address", $settings) && $settings['address'] != null && !empty($settings['address'])) {
                if ($input == null) {
                    $input = array("%" . $settings['address'] . "%");
                    $where = "WHERE i.address LIKE ?";
                } else {
                    $input[] = "%" . $settings['address'] . "%";
                    $where .= " AND i.address LIKE ?";
                }
            }
            if (array_key_exists("city", $settings) && $settings['city'] != null && !empty($settings['city'])) {
                if ($input == null) {
                    $input = array("%" . $settings['city'] . "%");
                    $where = "WHERE i.city LIKE ?";
                } else {
                    $input[] = "%" . $settings['city'] . "%";
                    $where .= " AND i.city LIKE ?";
                }
            }
            if (array_key_exists("state", $settings) && $settings['state'] != null && !empty($settings['state'])) {
                if ($input == null) {
                    $input = array("%" . $settings['state'] . "%");
                    $where = "WHERE i.state LIKE ?";
                } else {
                    $input[] = "%" . $settings['state'] . "%";
                    $where .= " AND i.state LIKE ?";
                }
            }
            if (array_key_exists("zip", $settings) && $settings['zip'] != null && !empty($settings['zip'])) {
                if ($input == null) {
                    $input = array("%" . $settings['zip'] . "%");
                    $where = "WHERE i.zip LIKE ?";
                } else {
                    $input[] = "%" . $settings['zip'] . "%";
                    $where .= " AND i.zip LIKE ?";
                }
            }
        }

        $sql = "
            SELECT i.idinsurances, i.name, i.phone, i.address, i.city, i.state, i.zip
            FROM " . self::TBL_INSURANCES . " i
            $where
            ORDER BY name ";

        $data = parent::select($sql, $input, $settings);
        
        foreach ($data as $row) {
            $insurance = new Insurance($row);
            $aryInsurances[] = $insurance;
        }
        return $aryInsurances;
    }
    
    public static function getInsurance(array $input, array $settings = null) {
    	$sqlSettings = null;
    	if ($settings != null) {
    		if (array_key_exists("Conn", $settings) && $settings['Conn'] instanceof mysqli) {
    			$sqlSettings['Conn'] = $settings['Conn'];
    		}
    	}
    	
        $sql = "
            SELECT idinsurances, name, address, city, state, zip, phone
            FROM " . self::DB_CSS . "." . self::TBL_INSURANCES . " i ";

            
        $qryInput = array();
        if (array_key_exists("idinsurances", $input) && count($input) == 1) {
            $sql .= " WHERE idinsurances = ? ";
            $qryInput[] = $input['idinsurances'];
        } else {
            $sql .= " WHERE ";
            foreach ($input as $key => $value) {
                $sql .= "$key = ? AND ";
                $qryInput[] = $value;
            }
            $sql = substr($sql, 0, strlen($sql) - 4);
        }        
        $sql .= " ORDER BY name ";

        //echo "<pre>$sql</pre><pre>"; print_r($qryInput); echo "</pre>";
        
        $data = parent::select($sql, $qryInput, $sqlSettings);
        
        if (count($data) == 1) {
            return new Insurance($data[0]);
        }
        return false;
    }
}

?>
