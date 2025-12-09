<?php
require_once 'DataObject.php';
require_once 'DOS/Result.php';
require_once 'TestDAO.php';
require_once 'DOS/Test.php';

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ResultDAO
 *
 * @author Edd
 */
class ResultDAO extends DataObject {
    public static function insertResult(Result $result, array $settings = null) {
        
        $isAdvancedOrder = false;
        $qrySettings = array("LastInsertId" => true);
        if ($settings != null) {
            if (array_key_exists("IsAdvancedOrder", $settings) && $settings['IsAdvancedOrder'] == true) {
                $isAdvancedOrder = true;
            }
            if (array_key_exists("Conn", $settings) && $settings['Conn'] instanceof mysqli) {
                $qrySettings['Conn'] = $settings['Conn'];
            } else {
                $qrySettings['ConnectToWeb'] = true;
            }
        } else {
            $qrySettings['ConnectToWeb'] = true;
        }
        
        if ($isAdvancedOrder) {
            $sql = "INSERT INTO " . self::DB_CSS_WEB . "." . self::TBL_ADVANCEDRESULTS . " (idAdvancedOrder, testId, panelId, resultNo, resultText, resultRemark, resultChoice, created, reportedBy, isAbnormal, isApproved, approvedBy, dateReported, approvedDate)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $input = array(
                $result->orderId, $result->testId, $result->panelId, $result->resultNo, $result->resultText, $result->resultRemark,
                $result->resultChoice, $result->created, $result->reportedBy, $result->isAbnormal, $result->isApproved, $result->approvedBy, $result->dateReported, $result->approvedDate
            );
        } else {
            $sql = "INSERT INTO " . self::DB_CSS_WEB . "." . self::TBL_RESULTS . " (orderId, testId, panelId, resultNo, resultText, resultRemark, resultChoice, created, reportedBy, isAbnormal, isApproved, approvedBy, dateReported, approvedDate)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $input = array(
                $result->orderId, $result->testId, $result->panelId, $result->resultNo, $result->resultText, $result->resultRemark, 
                $result->resultChoice, $result->created, $result->reportedBy, $result->isAbnormal, $result->isApproved, $result->approvedBy, $result->dateReported, $result->approvedDate
            );
        }
        
        $idResults = parent::manipulate($sql, $input, $qrySettings);
        return $idResults;
    }
    
    public static function insertResults(EntryOrder $entryOrder, mysqli $conn, array $settingsParam = null) {
        $pocHeaderInserted = false;
        $overlappingPanelsEnabled = false;
        
        $settings = array("IsAdvancedOrder" => false);
        if ($settingsParam != null && array_key_exists("IsAdvancedOrder", $settingsParam) && $settingsParam['IsAdvancedOrder'] == true) {
            $settings['IsAdvancedOrder'] = true;
        }
        if ($settingsParam != null && array_key_exists("OverlappingPanelsEnabled", $settingsParam) && $settingsParam['OverlappingPanelsEnabled'] == true) {
            $overlappingPanelsEnabled = true;
        }
        $settings['Conn'] = $conn;
        $aryPanelIds = self::getDistinctPanelIds($settings['Conn']);
        
        $aryTestPanels = array();
        $aryOtherResults = array();
        $aryResults = array();

        //echo "<pre>"; print_r($entryOrder->Results); echo "</pre>";
        foreach ($entryOrder->Results as $result) {
            if (!$result->IsPOC && isset($result->Test) && $result->Test->testType == 0 && in_array($result->testId, $aryPanelIds)) { // panel header
                $result->orderId = $entryOrder->idOrders;
                $result->reportedBy = null;
                $result->approvedBy = null;
                $result->dateReported = null;
                $result->approvedDate = null;


                $aryTestPanel = TestDAO::getPanelTests($result->testId, $settings['Conn']); // get all panel tests for this panel
                foreach ($aryTestPanel as $test) {
                    $aryTestPanels[] = $test->subtestId;
                }

                $result->panelId = null;

                $idResults = self::insertResult($result, $settings); // insert result for panel header
                
                $result->idResults = $idResults;
                
                // make sure the panel does not just consist of the header and that the first testid is the same as the test id for the current result
                if (!(count($aryTestPanel) == 1 && $aryTestPanel[0]->idTests == $result->testId)) {
                    
                    foreach ($aryTestPanel as $panelTest) { // loop through each panel test
                        if ($panelTest->idtests != $result->testId) { // make sure the panel header is not inserted twice

                            $newResult = new Result(array(
                                "idResults" => null,
                                "orderId" => $entryOrder->idOrders,
                                "testId" => $panelTest->subtestId,
                                "panelId" => $panelTest->idpanels,
                                "created" => $result->created,
                                "reportedBy" => null,
                                "approvedBy" => null,
                                "dateReported" => null,
                                "approvedDate" => null,
                                "testType" => $panelTest->testType,
                                "resultNo" => null,
                                "resultRemark" => null,
                                "resultChoice" => null
                            ));
                            
                            $idResults = self::insertResult($newResult, $settings);
                            $newResult->idResults = $idResults;
                            //$entryOrder->addResultFromObject($newResult);
                            $aryResults[] = $newResult;
                            
                            //if ($newResult->testType == 0 && in_array($newResult->testId, $aryPanelIds)) {
                            if ($panelTest->testType == 0 && in_array($newResult->testId, $aryPanelIds)) {
                                $aryTestSubPanel = TestDAO::getPanelTests($newResult->testId, $settings['Conn']);
                                
                                if (!(count($aryTestSubPanel) == 1 && $aryTestSubPanel[0]->idtests == $newResult->testId)) { 
                                    foreach ($aryTestSubPanel as $panelSubTest) {
                                        if ($panelSubTest->idtests != $newResult->testId) {
                                            $newSubResult = new Result(array(
                                                "idResults" => null,
                                                "orderId" => $entryOrder->idOrders,
                                                "testId" => $panelSubTest->subtestId,
                                                "panelId" => $panelSubTest->idpanels,
                                                "created" => $newResult->created,
                                                "reportedBy" => null,
                                                "approvedBy" => null,
                                                "dateReported" => null,
                                                "approvedDate" => null,
                                                "testType" => $panelSubTest->testType,
                                                "resultNo" => null,
                                                "resultRemark" => null,
                                                "resultChoice" => null
                                            ));
                                            
                                            $idResults = self::insertResult($newSubResult, $settings);
                                            $newSubResult->idResults = $idResults;
                                            //$entryOrder->addResultFromObject($newSubResult);
                                            $aryResults[] = $newSubResult;
                                        }
                                    }
                                }
                            }
                            
                        }
                    }
                }
                
            } else if ($result->IsPOC) { // $result->Test->testType == 1) { // insert POC or non-panel result
                $result->orderId = $entryOrder->idOrders;
                $result->dateReported = $result->created;
                $result->approvedDate = $result->created;
                $result->approvedBy = $result->reportedBy;
                $result->isApproved = 1;

                if (!$pocHeaderInserted) { // insert the POC Header row just before the first POC test is added
                    $pocHeader = new Result(array(
                        "idResults" => null,
                        "orderId" => $result->orderId,
                        "testId" => $result->panelId,
                        "panelId" => null,
                        "resultNo" => null,
                        "resultRemark" => null,
                        "resultChoice" => null,
                        "created" => $result->created,
                        "reportedBy" => $result->reportedBy,
                        "dateReported" => $result->created,
                        "approvedDate" => $result->created,
                        "isApproved" => true,
                        "approvedBy" => $result->reportedBy
                    ));

                    $idResults = self::insertResult($pocHeader, $settings);
                    $pocHeader->idResults = $idResults;
                    $aryResults[] = $pocHeader;

                    $pocHeaderInserted = true;
                }

                $newPocResult = new Result(array(
                    "idResults" => null,
                    "orderId" => $entryOrder->idOrders,
                    "testId" => $result->testId,
                    "panelId" => $result->panelId,
                    "created" => $result->created,
                    "reportedBy" => $result->reportedBy,
                    "approvedBy" => $result->reportedBy,
                    "dateReported" => $result->created,
                    "approvedDate" => $result->created,
                    "isApproved" => true,
                    "testType" => 1,
                    "resultNo" => $result->resultNo,
                    "resultText" => $result->resultText,
                    "resultRemark" => null,
                    "resultChoice" => $result->resultChoice
                ));

                if ($result->isAbnormal == 1) {
                    $newPocResult->isAbnormal = true;
                } else {
                    $newPocResult->isAbnormal = false;
                }

                $idResults = self::insertResult($newPocResult, $settings); // always connects to web
                $result->idResults = $idResults;

            } else if (isset($result->Test) &&$result->Test->testType == 1) {
                $aryOtherResults[] = $result;
            }
        }

        foreach ($aryOtherResults as $res) {
            if (!in_array($res->testId, $aryTestPanels) || $overlappingPanelsEnabled == true) {
                $res->orderId = $entryOrder->idOrders;
                $res->reportedBy = null;
                $res->approvedBy = null;
                $res->dateReported = null;
                $res->approvedDate = null;
                $idResults = self::insertResult($res, $settings); // always connects to web
                $res->idResults = $idResults;
            }
        }

        return $entryOrder;
    }

    public static function deleteResults($entryOrder, array $settings) {
        
        //if ($entryOrder->IsAdvancedOrder && isset($entryOrder->Phlebotomy)) { // advanced order with phlebotomy
        if ($settings['orderType'] == 4) {
            $qryInput = array();
            if (array_key_exists("idAdvancedOrders", $settings) && !empty($settings['idAdvancedOrders'])) {
                // settings['idAdvancedOrders'] should always have the idAdvancedOrders, but check and use the Phlebotomy as a fallback just in case.
                $qryInput[] = $settings['idAdvancedOrders'];
            } else {
                $qryInput[] = $entryOrder->Phlebotomy->idAdvancedOrder;
            }            
            $sql = "DELETE FROM " . self::TBL_ADVANCEDRESULTS . " WHERE idAdvancedOrder = ?";
            parent::manipulate($sql, $qryInput, array("ConnectToWeb" => true));
            
            $sql2 = "DELETE FROM " . self::TBL_RESULTS . " WHERE orderId = ?";
            parent::manipulate($sql2, array($entryOrder->idOrders), array("ConnectToWeb" => true));
            
        //} else if ($entryOrder->IsAdvancedOrder) { // advanced order only
        } else if ($settings['orderType'] == 3) {    
            $sql = "DELETE FROM " . self::TBL_ADVANCEDRESULTS . " WHERE idAdvancedOrder = ?";
            parent::manipulate($sql, array($entryOrder->idOrders), array("ConnectToWeb" => true));
            
        } else { // standard regular order
        //} else if ($settings['orderType'] == 1) {
            $sql = "DELETE FROM " . self::TBL_RESULTS . " WHERE orderId = ?";
            parent::manipulate($sql, array($entryOrder->idOrders), array("ConnectToWeb" => true));
            
        }
        /*else {
            return false;
        }*/
                
        return true;
    }
    
    private static function getDistinctPanelIds(mysqli $conn = null) {

        $arySettings = null;
        if ($conn != null) {
            $arySettings = array("Conn" => $conn);
        }
        $panelData = parent::select("SELECT DISTINCT idpanels FROM " . self::DB_CSS . "." . self::TBL_PANELS, null, $arySettings);
        $panelIds = array();
        foreach ($panelData as $row) {
            $panelIds[] = $row['idpanels'];
        }
        return $panelIds;
    }
    
    public static function getResults(array $input, array $settings = null) {
        $advancedOrderOnly = false;
        $sqlSettings = array("ConnectToWeb" => true);
        if ($settings != null) {
            if (array_key_exists("AdvancedOrderOnly", $settings) && $settings['AdvancedOrderOnly'] == true) {
                $advancedOrderOnly = true;
            }
            if (array_key_exists("Conn", $settings) && $settings['Conn'] instanceof mysqli) {
            	$sqlSettings['Conn'] = $settings['Conn'];
            	unset($sqlSettings['ConnectToWeb']);
            }
            
        }

        if ($advancedOrderOnly) {
            $sql = "
            SELECT  r.idAdvancedOrder AS 'orderId', r.testId, r.panelId, r.resultNo, r.resultText, r.resultRemark, r.resultChoice, r.created, r.reportedBy,
                    t.name, t.printedName, t.abbr, t.testType, t.header, t.number, t.idtests,
                    d.deptName, d.promptPOC, d.idDepartment
            FROM " . self::TBL_ADVANCEDRESULTS . " r
            INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idTests
            INNER JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTS . " d ON t.department = d.idDepartment
            WHERE   r.idAdvancedOrder = ?
            ORDER BY panelId, testType, testId";
        } else {
            $sql = "
            SELECT  r.orderId, r.testId, r.panelId, r.resultNo, r.resultText, r.resultRemark, r.resultChoice,  r.created, r.reportedBy, r.isAbnormal,
                    t.name, t.printedName, t.abbr, t.testType, t.header, t.number, t.idtests,
                    d.deptName, d.promptPOC, d.idDepartment
            FROM " . self::TBL_RESULTS . " r
            INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idTests
            INNER JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTS . " d ON t.department = d.idDepartment
            WHERE   r.orderId = ?
            #ORDER BY panelId, testType, testId
            ORDER BY r.idResults
            ";
        }

        //echo "<pre>$sql</pre>";

        $data = parent::select($sql, array($input['orderId']), $sqlSettings);

        $pocId = self::getPOCId();
        $aryResults = array();
        foreach ($data as $row) {
            $result = new Result($row);
            if ($row['panelId'] == $pocId || $row['testId'] == $pocId) {
                $result->IsPOC = true;
            }
            $test = new Test($row);
            $result->Test = $test;
            $aryResults[] = $result;
        }
        
        return $aryResults;
    }
    
    public static function getPOCId() {
        $sql = "SELECT p.value FROM " . self::TBL_PREFERENCES . " p WHERE p.key = ?";
        $data = parent::select($sql, array("POCTest"));
        return $data[0]['value'];
    }
    
    
}

?>
