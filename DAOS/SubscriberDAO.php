<?php
require_once 'DataObject.php';
require_once 'DOS/Subscriber.php';
/**
 * Description of SubscriberDAO
 *
 * @author Edd
 */
class SubscriberDAO extends DataObject {
    
    public static function insertSubscriber(Subscriber $subscriber, $connectToWeb = true) {

        $schema = self::DB_CSS_WEB;
        if ($connectToWeb == false) {
            $schema = self::DB_CSS;
        }

        $sql = "
            INSERT INTO " . $schema . "." . self::TBL_SUBSCRIBER . "(
                arNo, lastName, firstName, middleName, sex, ssn, dob, addressStreet, addressStreet2, addressCity, 
                addressState, addressZip, phone, workPhone, insurance, secondaryInsurance, policyNumber, 
                groupNumber, secondaryPolicyNumber, secondaryGroupNumber, medicareNumber, medicaidNumber
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ";

        $dob = $subscriber->dob;
        if (empty($subscriber->dob)) {
            $dob = null;
        }
        $insurance = $subscriber->insurance;
        if (empty($subscriber->insurance)) {
            $insurance = 0;
        }
        $secondaryInsurance = $subscriber->secondaryInsurance;
        if (empty($subscriber->secondaryInsurance)){
            $secondaryInsurance = 0;
        }

        $qryInput = array(
            $subscriber->arNo, $subscriber->lastName, $subscriber->firstName, $subscriber->middleName, $subscriber->sex, $subscriber->ssn, $dob, $subscriber->addressStreet, $subscriber->addressStreet2, $subscriber->addressCity,
            $subscriber->addressState, $subscriber->addressZip, $subscriber->phone, $subscriber->workPhone, $insurance, $secondaryInsurance, $subscriber->policyNumber,
            $subscriber->groupNumber, $subscriber->secondaryPolicyNumber, $subscriber->secondaryGroupNumber, $subscriber->medicareNumber, $subscriber->medicaidNumber
        );

        //echo $sql . "<pre>"; print_r($qryInput); echo "</pre>";

        $idSubscriber = parent::manipulate($sql, $qryInput, array("ConnectToWeb" => $connectToWeb, "LastInsertId" => true));

        if (!isset($idSubscriber) || empty($idSubscriber) || $idSubscriber == 0) {
            error_log($sql);
            error_log(implode(",", $qryInput));
        }

        return $idSubscriber;
    }
    
//     public static function deleteSubscriber($idSubscriber) {
//         $sql = "DELETE FROM " . self::TBL_SUBSCRIBER . " WHERE idSubscriber = ?";
//         parent::manipulate($sql, array($idSubscriber), array("ConnectToWeb" => true));
//         return true;
//     }
    
    public static function getSubscribers(array $inputFields, array $settings = null) {
        $includeInsurance = false;
        if ($settings != null) {
            if (array_key_exists("IncludeInsurance", $settings) && $settings['IncludeInsurance'] == true) {
                $includeInsurance = true;
            }
        }
        $sql = "
            SELECT  idSubscriber, s.arNo, s.lastName, s.firstName, s.middleName, s.sex, s.ssn, s.dob, s.addressStreet, s.addressStreet2, 
                    s.addressCity, s.addressState, s.addressZip, s.phone, s.workPhone, s.insurance, s.secondaryInsurance, s.policyNumber,
                    s.groupNumber, s.secondaryPolicyNumber, s.secondaryGroupNumber, s.medicareNumber, s.medicaidNumber ";
        if ($includeInsurance) {
            $sql .= "
                , i.idinsurances, i.name, i.address, i.city, i.state, i.zip, i.phone,
                i2.name AS `secondaryInsuranceName` ";
        }
        $sql .= " FROM " . self::TBL_SUBSCRIBER . " s ";
        if ($includeInsurance) {
            $sql .= "
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i ON s.insurance = i.idinsurances
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i2 ON s.secondaryInsurance = i2.idinsurances ";
        }
        $hasOrdersTable = false;
        if (array_key_exists("doctorId", $inputFields) || array_key_exists("clientId", $inputFields)) {
            //$sql .= "
            //    INNER JOIN " . self::TBL_PATIENTS . " p ON p.subscriber = s.idSubscriber
            //    INNER JOIN " . self::TBL_ORDERS . " o ON o.patientId = p.idPatients ";
            $sql .= " INNER JOIN " . self::TBL_ORDERS . " o ON o.subscriberId = s.idSubscriber ";
            $hasOrdersTable = true;
        }
        $where = " WHERE ";
        $aryQueryInput = array();
        foreach ($inputFields as $field => $value) {            
            if ($field == "lastName" || $field == "arNo") {
                $where .= "s.$field LIKE ? AND ";
                $aryQueryInput[] = "%$value%";
            } else {
                $where .= "$field = ? AND ";
                $aryQueryInput[] = $value;
            }
        }
        $where = substr($where, 0, strlen($where) - 4);
        $sql .= $where;        
        if ($hasOrdersTable) {
            $sql .= " GROUP BY idSubscriber ";
        }        
        $sql .= "   ORDER   BY s.lastName";
        //echo "<pre>$sql</pre><pre>"; print_r($aryQueryInput); echo "</pre>";
        $data = parent::select($sql, $aryQueryInput);
        //$webData = parent::select($sql, $aryQueryInput, array("ConnectToWeb" => true));
        
        $arySubscribers = array();
        if (count($data) > 0) {
            foreach($data as $row) {
                $subscriber = new Subscriber($row, $settings);
                $subscriber->SubscriberSource = 0;
                $arySubscribers[] = $subscriber;            
            }
        }

        //echo "<pre>"; print_r($arySubscribers); echo "</pre>";

        //if (count($webData) > 0) {
        //    foreach($webData as $row) {
      //          $subscriber = new Subscriber($row);
       //         $subscriber->SubscriberSource = 1;
       //         $arySubscribers[] = $subscriber;     
       //     }
       // }
        return $arySubscribers;
    }
    
    public static function getSubscriber(array $input, array $settings = null) {
    	$useConn = false;
       $connectToWeb = false;
       if ($settings != null) {
            if (array_key_exists("ConnectToWeb", $settings) && $settings['ConnectToWeb'] == true) {
               $connectToWeb = true;
            } else if (array_key_exists("Conn", $settings) && $settings['Conn'] instanceof mysqli) {
                $useConn = true;
            }
       }
        
        $sql = "
            SELECT  s.idSubscriber, s.arNo, s.lastName, s.firstName, s.middleName, s.sex, s.ssn, s.dob, s.addressStreet, s.addressStreet2, 
                    s.addressCity, s.addressState, s.addressZip, s.phone, s.workPhone, s.insurance, s.secondaryInsurance, s.policyNumber, 
                    s.groupNumber, s.secondaryPolicyNumber, s.secondaryGroupNumber, s.medicareNumber, s.medicaidNumber 
            FROM " . self::TBL_SUBSCRIBER . " s
            WHERE ";
        
        $qryInput = array();
        foreach ($input as $field => $value) {
            $sql .= "$field = ? AND ";
            $qryInput[] = $value;
        }
        $sql = substr ($sql, 0, strlen($sql) - 4);
        
        if ($useConn) {
        	$data = parent::select($sql, $qryInput, array("Conn" => $settings['Conn']));
        	$subscriberSource = 1;
        } else if ($connectToWeb) {
        	$data = parent::select($sql, $qryInput, array("ConnectToWeb" => true));
        	$subscriberSource = 1;
        } else {
        	$data = parent::select($sql, $qryInput);
        	$subscriberSource = 0;
        }
       
        //if (count($data) == 0) {
        //    $data = parent::select($sql, $qryInput);
        //    $subscriberSource = 0;    
        //} else {
        //	$subscriberSource = 1;
        //}
        
        if (count($data) > 0) {
        	$subscriber = new Subscriber($data[0], $settings);
        	$subscriber->SubscriberSource = $subscriberSource;
        	
        	return $subscriber;
        }
        
        return false;
    }
    
    public static function subscriberExists($idSubscriber, array $settings = null) {
        $connectToWeb = false;
        if ($settings != null) {
            if (array_key_exists("ConnectToWeb", $settings) && $settings['ConnectToWeb'] == true) {
                $connectToWeb = true;
            }
        }
        
        $sql = "
            SELECT COUNT(*) as 'cnt'
            FROM " . self::TBL_SUBSCRIBER . "
            WHERE idSubscriber = ?";
        
        if ($connectToWeb) {
            $data = parent::select($sql, array($idSubscriber), array("ConnectToWeb" => true));
        } else {
            $data = parent::select($sql, array($idSubscriber));
        }
        
        if (array_key_exists("cnt", $data[0]) && $data[0]['cnt'] > 0) {
            return true;
        }        
        
        return false;
    }
    
    public static function updateSubscriber(Subscriber $subscriber, array $settings = null) {
        $connectToWeb = false;
        if ($settings != null) {
            if (array_key_exists("ConnectToWeb", $settings) && $settings['ConnectToWeb'] == true) {
                $connectToWeb = true;
            }
        }
        
        $sql = "UPDATE " . self::TBL_SUBSCRIBER . " SET ";
            
        foreach ($subscriber->Data as $field => $value) {
            if ($field == "dob") {
                if (!is_bool(strtotime($value))) {
                    $sql .= "dob = ?, ";
                    $qryInput[] = date("Y-m-d", strtotime($value));
                }
            } else if ($field == "arNo" && empty($value)) {
                $sql .= "arNo = ?, ";
                $qryInput[] = 1;
            } else if ($field != "idSubscriber" && $field != "age" && $value != 0) {
                $sql .= "$field = ?, ";
                $qryInput[] = $value;
            }
            
        }
        $sql = substr($sql, 0, strlen($sql) - 2);
        $sql .= " WHERE idSubscriber = ?";
        $qryInput[] = $subscriber->idSubscriber;

        parent::manipulate($sql, $qryInput, array("ConnectToWeb" => true));

        
        return true;
    }
    
    public static function getNewArNo($length = 8) {
        $arNo = parent::generateRandomString($length); // get randome string
        $done = false;
        while ($done == false) { // loop until it is a unique arNo on both cssweb and css schemas
            $sql = "SELECT idSubscriber FROM " . self::TBL_SUBSCRIBER . " WHERE arNo = ?"; // query will return 0 rows if it is a unique arNo
            $data = parent::select($sql, array($arNo));
            if (count($data) == 0) { // arNo is unique on css
                $webData = parent::select($sql, array($arNo), array("ConnectToWeb" => true)); // now check cssweb
                if (count($webData) == 0) { // arNo is unique on cssweb
                    $done = true; // exit loop
                } else { // not unique on cssweb, so generate new string
                    $arNo = parent::generateRandomString($length);
                }
            } else { // not unique on css, so generate new string
                $arNo = parent::generateRandomString($length);
            }
        }
        return $arNo;
    }
    
    public static function deleteSubscriber($idSubscriber) {
    	$sql = "DELETE FROM " . self::TBL_SUBSCRIBER . " WHERE idSubscriber = ?";
    	$affectedRows = parent::manipulate($sql, array($idSubscriber), array("ConnectToWeb" => true, "AffectedRows" => true));
    	return $affectedRows;
    }

    public static function subscriberArNoExists($arNo, $includeWebSubscribers = false) {
        $arNoExists = true;

        $sql = "
            SELECT COUNT(*) AS `cnt`
            FROM " . self::DB_CSS . "." . self::TBL_SUBSCRIBER . " s
            WHERE s.arNo = ?";
        $data = parent::select($sql, array($arNo));
        if (count($data) > 0 && $data[0]['cnt'] == 0) {
            $arNoExists = false;
        }

        if ($includeWebSubscribers == true) {
            $sql = "
                SELECT COUNT(*) AS `cnt`
                FROM " . self::DB_CSS_WEB . "." . self::TBL_SUBSCRIBER . " s
            WHERE s.arNo = ?";
            $data = parent::select($sql, array($arNo));
            if (count($data) > 0 && $data[0]['cnt'] == 0) {
                $arNoExists = false;
            }
        }

        return $arNoExists;
    }
}

?>
