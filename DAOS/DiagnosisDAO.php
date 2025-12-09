<?php
require_once 'DataObject.php';
require_once 'DOS/DiagnosisCode.php';
require_once 'DOS/DiagnosisValidityCode.php';

class DiagnosisDAO extends DataObject {
    
    public static function insertDiagnosisLookups(EntryOrder $entryOrder, mysqli $conn) {
        $advancedOrder = $entryOrder->isAdvancedOrder;
        foreach ($entryOrder->DiagnosisCodes as $code) {
            $code->setOrderId($entryOrder->idOrders);
            self::insertDiagnosisLookup($code, $advancedOrder, $conn); // always connects to web
        }
    }
    
    public static function insertDiagnosisLookup(DiagnosisValidityCode $code, $advancedOrder = 0, mysqli $conn = null) {
        //$validity = $code->getValidity();
        //$idDiagnosisValidity = $validity[0]->idDiagnosisValidity;
        
        $sql = "INSERT INTO " . self::DB_CSS_WEB . "." . self::TBL_ORDERDIAGNOSISLOOKUP . "(idOrders, idDiagnosisCodes, advancedOrder) VALUES (?, ?, ?)";
        $input = array(            
            $code->orderId,
            $code->idDiagnosisCodes,
            $advancedOrder
        );
        parent::manipulate($sql, $input, array("Conn" => $conn));
    }


    /*public static function checkDiagnosisCode($testId, $insurance, $secondaryInsurance, $code) {
        $codeExists = false;
        $testValidityExists = false;
        $testAndCodeMatch = false;
    }*/


    public static function checkDiagnosisCode($testIds, $diagnosisCodeIds, $insurance, $secondaryInsurance) {


        $aryDiagnosisCodeIds = explode(",", $diagnosisCodeIds);
        $aryTestIds = explode(",", $testIds);

        $aryCodes = self::getValidity(array("testIds" => $aryTestIds, "diagnosisCodeIds" => $aryDiagnosisCodeIds, "IncludeNames" => true));

        //echo "<pre>"; print_r($aryTestIds); echo "</pre>";

        $aryReturn = array();

        foreach($aryTestIds as $testId) {
            $diagnosisCodeId = "";
            $codeExists = false;
            $testValidityExists = false;
            $testAndCodeMatch = false;
            $idDiagnosisValidity = null;


            for ($i = 0; $i < count($aryCodes); $i++) {
                $diagnosisValidity = $aryCodes[$i];

                //if (strtolower($code) == strtolower($diagnosisValidity->code)) {
                if (in_array($diagnosisValidity->idDiagnosisCodes, $aryDiagnosisCodeIds)) {

                    if ($diagnosisValidity->hasTestId($testId)) {
                        $codeExists = true;
                        $testAndCodeMatch = true;
                    }

                    //$aryValidity = $diagnosisValidity->Validity;
                    //$testId = $aryValidity[0]->testId;

                    $diagnosisCodeId = $diagnosisValidity->idDiagnosisCodes;

                }

                if ($diagnosisValidity->hasTestId($testId)) {
                    $testValidityExists = true;
                }
            }

            if (($codeExists && !$testValidityExists) || $testAndCodeMatch) {
                $aryReturn[] = array(
                    "testId" => $testId,
                    "diagnosisCodeId" => $diagnosisCodeId,
                    "response" => 0
                );

            } else if ($codeExists && $testValidityExists && !$testAndCodeMatch) {
                $aryReturn[] = array(
                    "testId" => $testId,
                    "diagnosisCodeId" => $diagnosisCodeId,
                    "response" => 1
                );
            } else {
                $aryReturn[] = array(
                    "testId" => $testId,
                    "diagnosisCodeId" => $diagnosisCodeId,
                    "response" => 2
                );
            }
        }



        return $aryReturn;
    }

    public static function getValidity(array $settings = null) {
        $includeNames = false;
        if (!empty($settings) && array_key_exists("IncludeNames", $settings) && $settings['IncludeNames'] == true) {
            $includeNames = true;
        }
        
        /*$sql = "SELECT  d.idDiagnosisCodes, d.code, d.description,
            v.validity, v.idDiagnosisValidity, v.diagnosisCodeId, v.testId, v.insuranceId,
            d.dateCreated, d.dateUpdated, ";
        if ($includeNames) {
            $sql .= ", t.name as 'testName',  t.number, i.name as 'insuranceName' ";
        }
        $sql .= " FROM " . self::DB_CSS . "." . self::TBL_DIAGNOSISCODES . " d
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DIAGNOSISVALIDITY . " v ON d.idDiagnosisCodes = v.diagnosisCodeId ";
        if ($includeNames) {
            $sql .= " LEFT JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON v.testId = t.idTests
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i ON v.insuranceId = i.idInsurances";
        }
        $sql .= " ORDER BY idDiagnosisCodes, testId ";*/

        $where = "";
        $aryInput = array();
        if ($settings != null) {
            if (array_key_exists("testIds", $settings) && array_key_exists("diagnosisCodeIds", $settings)) {
                $where = "WHERE t.idtests IN (";
                foreach ($settings['testIds'] as $testId) {
                    $where .= "?,";
                    $aryInput[] = $testId;
                }
                $where = substr($where, 0, strlen($where) - 1) . ")";

                $where .= " AND dc.idDiagnosisCodes IN(";
                foreach ($settings['diagnosisCodeIds'] as $diagnosisCodeId) {
                    $where .= "?,";
                    $aryInput[] = $diagnosisCodeId;
                }
                $where = substr($where, 0, strlen($where) - 1) . ") ";
            }
        }

        $sql = "
        SELECT	DISTINCT
                t.idtests AS `testId`, t.number AS `testNumber`, t.name AS `testName`,                
                dv.idDiagnosisValidity,
                dc.idDiagnosisCodes, dc.code, dc.description,                
                bp.idbillingPayors,
                i.idinsurances, i.name AS `insuranceName`,
                bp.clientId, bp.patientId                
        FROM " . self::DB_CSS . "." . self::TBL_FEESCHEDULES . " f        
        INNER JOIN " . self::DB_CSS . "." . self::TBL_FEESCHEDULETESTLOOKUP . " ftl ON f.idFeeSchedules = ftl.feeScheduleId
        INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON (ftl.testNumber = t.number OR ftl.panelNumber = t.number OR ftl.batteryNumber = t.number) AND t.active = true        
        INNER JOIN " . self::DB_CSS . "." . self::TBL_FEESCHEDULECPTLOOKUP . " fcl ON ftl.idFeeScheduleTestLookup = fcl.feeScheduleTestLookupId
        INNER JOIN " . self::DB_CSS . "." . self::TBL_CPTCODES . " cc ON fcl.cptCodeId = cc.idCptCodes AND cc.active = true
        INNER JOIN " . self::DB_CSS . "." . self::TBL_DIAGNOSISVALIDITYLOOKUP . " dvl ON cc.idCptCodes = dvl.cptCodeId
        INNER JOIN " . self::DB_CSS . "." . self::TBL_DIAGNOSISVALIDITY . " dv ON dvl.diagnosisValidityId = dv.idDiagnosisValidity
        INNER JOIN " . self::DB_CSS . "." . self::TBL_DIAGNOSISCODES . " dc ON dvl.diagnosisCodeId = dc.idDiagnosisCodes
        INNER JOIN " . self::DB_CSS . "." . self::TBL_BILLINGPAYORS . " bp ON dv.billingPayorId = bp.idbillingPayors
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i ON bp.insuranceId = i.idinsurances
        $where
        ";

        /*error_log($sql);
        error_log(implode($aryInput, ","));*/

        $data = parent::select($sql, $aryInput);

        /*echo "<pre>"; print_r($data); echo "</pre>";*/
        
        $aryCodes = array();
        if (count($data) > 0) {
            $code = new DiagnosisValidityCode($data[0], $includeNames);
            $idDiagnosisCodes = $data[0]['idDiagnosisCodes'];

            for ($i = 0; $i < count($data); $i++) {
                $row = $data[$i];

                if ($row['idDiagnosisCodes'] != $idDiagnosisCodes) {
                    $aryCodes[] = $code;

                    $code = new DiagnosisValidityCode($row, $includeNames);

                    $idDiagnosisCodes = $row['idDiagnosisCodes'];
                    if ($includeNames) {
                        $code->AddValidity($row, true);
                    } else {
                        $code->AddValidity($row);
                    }
                    if ($i == count($data) - 1) {
                        $aryCodes[] = $code;
                    }
                } else {

                    if ($includeNames) {
                        $code->AddValidity($row, true);
                    } else {
                        $code->AddValidity($row);
                    }

                    if ($i == count($data) - 1) {
                        $aryCodes[] = $code;
                    }
                }
            }
        }

        return $aryCodes;
    }
 
    public static function getDiagnosisCodes(array $input = null, array $settings = null) {
        $limitRows = false;
        if ($settings != null){
            if (array_key_exists("Limit", $settings) && !empty($settings['Limit']) && is_numeric($settings['Limit'])) {
                $limitRows = true;
            }            
        }
        $sql = "
            SELECT idDiagnosisCodes, code, description, FullDescription, version, dateCreated, dateUpdated
            FROM " . self::DB_CSS . "." . self::TBL_DIAGNOSISCODES;
        
        if ($input != null) {
            $sql .= " WHERE ";
            $aryInput = array();
            foreach ($input as $field => $value) {
                if (!empty($value)) {
                    if ($field == "selectedCodes") {
                        $sql .= " idDiagnosisCodes NOT IN ( ";
                        foreach ($value as $idDiagnosisCodes) {
                            if ($idDiagnosisCodes != 0) {
                                $sql .= "?, ";
                                $aryInput[] = $idDiagnosisCodes;
                            }
                        }
                        $sql = substr($sql, 0, strlen($sql) - 2);
                        $sql .= " ) AND ";
                    } else if ($field == "version") {
                        $sql .= " version = ? AND ";
                        $aryInput[] = $value;
                    } else {
                        $sql .= " $field LIKE ? AND ";
                        $aryInput[] = "%$value%";
                    }
                    
                }
            }
            $sql = substr($sql, 0, strlen($sql) - 4);
            $sql .= " ORDER BY code "; // LIMIT 10";
            if ($limitRows) {
                $sql .= " LIMIT " . $settings['Limit'];
            }
            $data = parent::select($sql, $aryInput);
            //echo $sql; echo "<pre>"; print_r($aryInput); echo "</pre>";
        } else {            
            $data = parent::select($sql);
        }
        
        
        if (count($data) > 0) {
            $aryCodes = array();
            foreach ($data as $row) {
                $code = new DiagnosisCode($row);
                $aryCodes[] = $code;
            }

            return $aryCodes;
        }
        return false;
    }
    
    public static function getDiagnosisCode($idDiagnosisCodes, $settings) {
    	
        $sql = "
            SELECT idDiagnosisCodes, code, description, FullDescription, dateCreated, dateUpdated, version
            FROM " . self::DB_CSS . "." . self::TBL_DIAGNOSISCODES . "
            WHERE idDiagnosisCodes = ?
        ";
        $data = parent::select($sql, array($idDiagnosisCodes), $settings);
        
        if (count($data) == 1) {
            return new DiagnosisCode($data[0]);
        }
        return false;
    }
    
    public static function getDiagnosisValidity() {
        $sql = "
            SELECT v.idDiagnosisValidity, v.diagnosisCodeId, v.testId, v.insuranceId, v.dateCreated, v.dateUpdated
            FROM " . self::DB_CSS . "." . self::TBL_DIAGNOSISVALIDITY;
        $data = parent::select($sql);
        
        return $data;
    }
    
    public static function deleteOrderCodes($idOrders) {
        $sql = "DELETE FROM " . self::TBL_ORDERDIAGNOSISLOOKUP . " WHERE idOrders = ?";
        parent::manipulate($sql, array($idOrders), array("ConnectToWeb" => true));
        return true;
    }
    
    public static function getOrderDiagnosisCodes($orderId, array $settings = null) {
    	$sqlSettings = array("ConnectToWeb" => true);
    	if ($settings != null) {
    		if (array_key_exists("Conn", $settings) && $settings['Conn'] instanceof mysqli) {
    			$sqlSettings['Conn'] = $settings['Conn'];
    			unset($sqlSettings['ConnectToWeb']);
    		}
    	}
    	
        $sql = "
            SELECT  dl.idDiagnosisLookup, dl.idOrders AS 'orderId', dl.idDiagnosisCodes, dl.idDiagnosisCodes as 'diagnosisCodeId',
                    dc.code, dc.loinc, dc.description, dc.cptCode, dc.dateCreated, dc.dateUpdated, dc.version
            FROM " . self::TBL_ORDERDIAGNOSISLOOKUP . " dl
            INNER JOIN " . self::DB_CSS . "." . self::TBL_DIAGNOSISCODES . " dc ON dl.idDiagnosisCodes = dc.idDiagnosisCodes
            WHERE idOrders = ?";
        $data = parent::select($sql, array($orderId), $sqlSettings);
        
        $aryCodes = array();
        if (count($data) > 0) {
            foreach ($data as $row) {
                $code = new DiagnosisValidityCode($row);
                $aryCodes[] = $code;
            }
        }
        
        return $aryCodes;
    }
   
}

?>
