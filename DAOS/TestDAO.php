<?php

require_once 'DataObject.php';
require_once 'DOS/Test.php';
require_once 'DOS/POCTest.php';
require_once 'DOS/PanelTest.php';

class TestDAO extends DataObject {

    //public static function getTests(array $inputFields, $hasPMByDepartment, $type, array $excludedTestIds = null) {
    public static function getTests(array $inputFields = null, array $settings = null) {

        $includeCommonTests = false;
        $includeDiagnosis = false;
        $excludedTests = false;
        $withoutPoc = false;
        $excludePanelHeaders = false;
        $excludeSelectedPanelTests = false;
        $includeTestComments = false;
        $excludeReferenceTests = false;
        $overlappingPanelsEnabled = false;

        $qrySettings = array("Conn" => parent::connect());
        if ($settings != null) {
            if (array_key_exists("OrderByCommonTests", $settings) && $settings['OrderByCommonTests'] == true) {
                $includeCommonTests = true;
            }
            if (array_key_exists("IncludeDiagnosis", $settings) && $settings['IncludeDiagnosis'] == true) {
                $includeDiagnosis = true;
            }
            if ((array_key_exists("ExcludedTests", $settings) && $settings['ExcludedTests'] = true)
                || (array_key_exists("HasExcludedTests", $settings) && $settings['HasExcludedTests'] = true)) {
                $excludedTests = true;
            }
            if (array_key_exists("WithoutPoc", $settings) && $settings['WithoutPoc'] == true) {
                $withoutPoc = true;
            }
            if (array_key_exists("ExcludePanelHeaders", $settings) && $settings['ExcludePanelHeaders'] == true) {
                $excludePanelHeaders = true;
            }
            if (array_key_exists("ExcludeSelectedPanelTests", $settings) && $settings['ExcludeSelectedPanelTests'] == true) {
                $excludeSelectedPanelTests = true;
            }
            if (array_key_exists("IncludeTestComments", $settings) && $settings['IncludeTestComments'] == true) {
                $includeTestComments = true;
            }
            if (array_key_exists("ExcludeReferenceTests", $settings) && $settings['ExcludeReferenceTests'] == true) {
                $excludeReferenceTests = true;
            }
            if (array_key_exists("OverlappingPanelsEnabled", $settings) && $settings['OverlappingPanelsEnabled'] == true) {
                $overlappingPanelsEnabled = true;
            }
        }
        
        $aryQueryInput = array();
        $sql = "
         SELECT t.idtests, t.number, t.name, idDepartment, deptName, promptPOC, t.testType, st.name AS 'specimenTypeName' ";
        if ($includeCommonTests) {
            $sql .= ", ct.userId ";
        }

        if ($includeTestComments) {
            $sql .= ", t.testComment ";

        }

        if ($excludedTests) {
            $sql .= ", et.userId ";
        }
        if ($includeDiagnosis) {
            $sql .= " , diag.idDiagnosisValidity, diag.code, diag.validity, diag.insuranceId, diag.testId, diag.diagnosisCodeId ";
        }
        $sql .= " FROM " . self::DB_CSS . "." . self::TBL_TESTS . " t
         INNER JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTS . " d ON d.idDepartment = t.department
         LEFT JOIN " . self::DB_CSS . "." . self::TBL_SPECIMENTYPES . " st ON t.specimenType = st.idspecimenTypes ";
        if ($includeCommonTests) { // -------------------------------------------------- 
            $sql .= " LEFT JOIN " . self::TBL_COMMONTESTS . " ct ON t.number = ct.testNumber AND ct.userId = ? AND t.active = true ";
            $aryQueryInput[] = $settings['UserId'];
        }
        if ($excludedTests) {
            $sql .= " LEFT JOIN " . self::TBL_EXCLUDEDTESTS . " et ON t.number = et.testNumber AND et.userId = ? AND t.active = true ";
            $aryQueryInput[] = $settings['UserId'];
        }
        if ($includeDiagnosis) {
            $sql .= "
            LEFT JOIN (
                SELECT idDiagnosisValidity, code, testId, insuranceId, validity, diagnosisCodeId
                FROM " . self::DB_CSS . "."  . self::TBL_DIAGNOSISCODES . " d
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_DIAGNOSISVALIDITY . " v ON d.idDiagnosisCodes = v.diagnosisCodeId ";
            if (isset($settings['insurance']) && isset($settings['secondaryInsurance'])) {
                $sql .= " WHERE insuranceId = ? OR insuranceId = ? ";
                $aryQueryInput[] = $settings['insurance'];
                $aryQueryInput[] = $settings['secondaryInsurance'];
            } else if (isset($settings['insurance'])) {
                $sql .= " WHERE insuranceId = ? ";
                $aryQueryInput[] = $settings['insurance'];
            } else if (isset($settings['secondaryInsurance'])) {
                $sql .= " WHERE insuranceId = ? ";
                $aryQueryInput[] = $settings['secondaryInsurance'];
            }
            $sql .= "
            ) diag ON t.idtests = diag.testId
            ";
        }


        
        if ($inputFields != null && ($settings == null || !array_key_exists("OrderByCommonTests", $settings))) {
            $sql .= "WHERE t.active = 1 AND ";
            foreach ($inputFields as $field => $value) {
                if ($field == "commonTests" && is_array($value) && count($value) > 0 && $value[0] != false) { // Do not select common tests this user has
                    /*$sql .= " t.idtests NOT IN ( ";
                    foreach ($value as $idtests) {
                        $sql .= "?, ";
                        $aryQueryInput[] = $idtests;
                    }
                    $sql = substr($sql, 0, strlen($sql) - 2);
                    $sql .= " ) AND ";*/
                } else if ((!empty($value) && !is_array($value) && trim($value) != "") || ($field == "number" && $value == 0 && $value != '')) {
                    if ($field == "name") {
                        $sql .= "t.name LIKE ? AND ";
                        $aryQueryInput[] = "%$value%";
                    } else if ($field == "number") {
                        $sql .= " t.number LIKE ? AND ";
                        $aryQueryInput[] = "%$value%";
                    } else if ($field == "testNameNumber") {
                        $sql .= " (t.name LIKE ? OR t.number LIKE ?) AND ";
                        $aryQueryInput[] = "$value%";
                        $aryQueryInput[] = "$value%";
                    } else if ($field == "numberExact") {
                        $sql .= " t.number = ? AND ";
                        $aryQueryInput[] = $value;
                    } else {
                        $sql .= "$field = ? AND ";
                        $aryQueryInput[] = "$value";
                    }
                }           
            }
            $sql = substr($sql, 0, -4);
            
            if ($settings != null && array_key_exists("HasExcludedTests", $settings) && $settings['HasExcludedTests'] == 1 && array_key_exists("UserId", $settings) && !empty($settings['UserId'])) {
                $sql .= " AND t.number NOT IN (
                    SELECT et.testNumber
                    FROM " . self::DB_CSS . "." . self::TBL_EXCLUDEDTESTS . " et
                    WHERE userId = ?
                ) ";
                $aryQueryInput[] = $settings['UserId'];
            }
            
            //if (isset($excludedTestIds) && !empty($excludedTestIds) && count($excludedTestIds) > 1) { // do not select the test ids in $excludedTestIds
            if ($settings != null && array_key_exists("SelectedTests", $settings) && !empty($settings['SelectedTests']) && count($settings['SelectedTests']) > 1 && $overlappingPanelsEnabled == false) {
                $list = "";
                for ($i = 0; $i < count($settings['SelectedTests']); $i++) {
                    $aryQueryInput[] = $settings['SelectedTests'][$i];

                    $list .= "?, ";
                }
                $list = substr($list, 0, strlen($list) - 2);
                $sql .= " AND t.idtests NOT IN ($list) ";
            }

            //echo "<pre>"; print_r($settings);echo "</pre>";

            //if ($settings != null && array_key_exists("HasPMByDepartment", $settings) && array_key_exists("type", $settings)) {
            if ($settings != null && array_key_exists("type", $settings) && array_key_exists("HasPMByDepartment", $settings)) {
                //if (!$settings['HasPMByDepartment'] || $settings['type'] == 3) { // the PMByDepartment preference is set to false or the first selected test has promptPOC = 0
                if ($settings['type'] == 3 && $settings['HasPMByDepartment'] == 1) {

                    // selected tests department has promptPOC = 0
                    $sql .= " AND d.promptPOC = ? ";
                    $aryQueryInput[] = 0;
                } else if ($settings['type'] == 2) { // the first selected test has promptPOC = 1
                    $sql .= " AND d.promptPOC = ? "; // only select tests with promptPOC = 1
                    $aryQueryInput[] = 1;
                }
            }
        }
        
        if ($withoutPoc) {
            $sql2 = "SELECT p.value FROM " . self::TBL_PREFERENCES . " p WHERE p.key = ?";
            $data = parent::select($sql2, array("POCTest"), $settings);
            $pocId = $data[0]['value'];
            if (strpos($sql, "WHERE") === false) {
                $sql .= " WHERE ";
            } else {
                $sql .= " AND ";
            }
            $sql .= " t.active = 1 AND t.idtests NOT IN (
                        SELECT subtestId 
                        FROM " . self::DB_CSS . "." . self::TBL_PANELS . " p
                        WHERE idpanels = ?
                ) AND t.idtests != ? ";
            $aryQueryInput[] = $pocId;
            $aryQueryInput[] = $pocId;
        }

        if ($excludeSelectedPanelTests == true && array_key_exists("SelectedTests", $settings) && !empty($settings['SelectedTests']) && count($settings['SelectedTests']) > 1 && $overlappingPanelsEnabled == false) {
            $sql3 = "
                SELECT t1.number AS `TestNumber1`, t2.number AS `TestNumber2`, t3.number AS `TestNumber3`
                FROM " . self::DB_CSS . "." . self::TBL_TESTS . " t1
                LEFT JOIN ".  self::DB_CSS . "." . self::TBL_PANELS . " p ON t1.idtests = p.idpanels
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t2 ON p.subtestId = t2.idtests
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_PANELS . " p2 ON p.subtestId = p2.idpanels
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t3 ON p2.subtestId = t3.idtests
                WHERE   t1.active = true
                        AND (t2.idtests IS NULL OR t2.active = true)
                        AND (t3.idtests IS NULL OR t3.active = true)
                        AND t1.idtests IN (
            ";
            $list = "";
            $aryQueryInput2 = array();
            for ($i = 0; $i < count($settings['SelectedTests']); $i++) {
                $aryQueryInput2[] = $settings['SelectedTests'][$i];

                $list .= "?, ";
            }
            $list = substr($list, 0, strlen($list) - 2);
            $sql3 .= "$list) ";

            /*error_log($sql3);
            error_log(implode($aryQueryInput2, ","));*/

            //$selectedTestsData = parent::select($sql3, $aryQueryInput2, $settings);
            $selectedTestsData = parent::select($sql3, $aryQueryInput2, $qrySettings);

            if (count($selectedTestsData) > 0) {
                $aryExcludedTestNumbers = array();
                $strParamPlaceholders = "";
                foreach ($selectedTestsData as $row) {
                    if (!empty($row['TestNumber1']) && !in_array($row['TestNumber1'], $aryExcludedTestNumbers)) {
                        $aryExcludedTestNumbers[] = $row['TestNumber1'];
                        $strParamPlaceholders .= "?,";
                    }
                    if (!empty($row['TestNumber2']) && !in_array($row['TestNumber2'], $aryExcludedTestNumbers)) {
                        $aryExcludedTestNumbers[] = $row['TestNumber2'];
                        $strParamPlaceholders .= "?,";
                    }
                    if (!empty($row['TestNumber3']) && !in_array($row['TestNumber3'], $aryExcludedTestNumbers)) {
                        $aryExcludedTestNumbers[] = $row['TestNumber3'];
                        $strParamPlaceholders .= "?,";
                    }
                }
                if (strlen($strParamPlaceholders) > 0) {
                    $strParamPlaceholders = substr($strParamPlaceholders, 0, strlen($strParamPlaceholders) - 1);
                    $sql .= " AND t.number NOT IN ($strParamPlaceholders) ";
                    foreach($aryExcludedTestNumbers as $testNumber) {
                        $aryQueryInput[] = $testNumber;
                    }
                }
            }
        }
        
         if ($settings != null && array_key_exists("OrderEntrySearch", $settings) && $settings['OrderEntrySearch'] == true) {
                $sql .= "
                    AND t.idtests NOT IN (
                        SELECT subtestId
                        FROM " . self::DB_CSS . "." . self::TBL_TESTS . " t
                        INNER JOIN " . self::DB_CSS . "." . self::TBL_PANELS . " p ON t.idtests = p.idpanels
                        WHERE t.idtests = (
                            SELECT pr.value FROM css.preferences pr WHERE pr.key = ?
                        )
                    )
                ";
                $aryQueryInput[] = "POCTest";
         }
        
        if ($excludePanelHeaders) {
            if (strpos($sql, "WHERE") === false) {
                $sql .= " WHERE ";
            } else {
                $sql .= " AND ";
            }
            $sql .= "t.testType != ? ";
            $aryQueryInput[] = 0;
        }

        if (strpos($sql, "WHERE") === false) {
            $sql .= " WHERE t.active = true ";
        } else {
            $sql .= " AND t.active = true ";
        }

        if (strpos($sql, "WHERE") === false) {
            $sql .= " WHERE t.isOrderable = true ";
        } else {
            $sql .= " AND t.isOrderable = true ";
        }

        if (strpos($sql, "WHERE") === false) {
            $sql .= " WHERE t.billingOnly = 0 AND t.resultType != 'Billing' ";
        } else {
            $sql .= " AND t.billingOnly = 0 AND t.resultType != 'Billing' ";
        }

        if (strpos($sql, "WHERE") === false && $excludeReferenceTests == true) {
            $sql .= " WHERE d.ReferenceLab = false ";
        } else if ($excludeReferenceTests == true) {
            $sql .= " AND d.ReferenceLab = false ";
        }

        if (strpos($sql, "WHERE") === false && self::HasMultiLocation == true
            && array_key_exists("locationId", $inputFields) && !empty($inputFields['locationId']) && $inputFields['locationId'] != 0) {

            $sql .= " WHERE t.locationId = ? ";
            $aryQueryInput[] = $inputFields['locationId'];
        } else if (self::HasMultiLocation == true && array_key_exists("locationId", $inputFields) && !empty($inputFields['locationId']) && $inputFields['locationId'] != 0) {
            $sql .= " AND t.locationId = ? ";
            $aryQueryInput[] = $inputFields['locationId'];
        }

        if ($includeCommonTests && $excludedTests) {
            $sql .= " ORDER BY ct.userId DESC, et.userId DESC, t.name";
        } else if ($includeCommonTests) {
            $sql .= " ORDER BY ct.userId DESC, t.name";
        } else {
            $sql .= " ORDER BY t.name";
        }

//        error_log($sql);
//        error_log(implode(",", $aryQueryInput));
//        error_log("Overlapping: " . $overlappingPanelsEnabled);

        $data = parent::select($sql, $aryQueryInput, $qrySettings);

        $aryTests = array();
        foreach ($data as $row) { // loop through search results
            $currTest = new Test($row);
            if (!array_key_exists($row['idtests'], $aryTests)) {

                if ($row['testType'] != 0) {
                    $aryTests[$row['idtests']] = $currTest;
                    if ($includeDiagnosis && !empty($row['idDiagnosisValidity'])) {
                        $aryTests[$row['idtests']]->addValidity($row);
                    }

                } else {
    
                    if ($settings != null && is_array($settings) && (!array_key_exists("SelectedTests", $settings) || count($settings['SelectedTests']) > 1 || (count($settings['SelectedTests']) == 1 && $settings['SelectedTests'][0] != 0))
                        && $overlappingPanelsEnabled == false) {


                    //if (1 == 0) {
                   // if ($settings != null && !array_key_exists($settings, "SelectedTests")) {
                        // there are selected tests on this order
                        // make sure single tests within this panel are not already selected on the order
                        $sql4 = "
                            SELECT GROUP_CONCAT(DISTINCT t.idtests) AS `PanelTestIds`
                            FROM " . self::DB_CSS . "." . self::TBL_PANELS . " p
                            LEFT JOIN " . self::DB_CSS . "." . self::TBL_PANELS . " p2 ON p.subtestId = p2.idpanels
                            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON (p.subtestId = t.idtests AND t.testType != 0) OR p2.subtestId = t.idtests
                            WHERE p.idpanels = ? AND t.active = true;
                        ";
                        $data4 = parent::select($sql4, array($row['idtests']), $qrySettings); // select all panel tests of the current search result

                        if (count($data4) > 0) { // the selected panel has panel tests....
                            $aryPanelTestIds = explode(",", $data4[0]['PanelTestIds']);

                            //echo "<pre>Test: "; print_r($aryPanelTestIds); echo "</pre>";

                            $panelContainsSelectedTest = false;
                            if (array_key_exists("SelectedTests", $settings)) {



                                foreach ($settings['SelectedTests'] as $currIdTests) { // loop through the selected test ids and check if they are in the array of test ids in the panel
                                    if ($currIdTests != 0) {
                                        $data5 = parent::select($sql4, array($currIdTests), $qrySettings);

                                        //echo "$currIdTests<pre>$sql</pre>";



                                        if (count($data5) > 0 && $data5[0]['PanelTestIds'] != null) { // the current selected test is an array or panel, so check the tests within it
                                            $arySelectedPanelTestIds = explode(",", $data5[0]['PanelTestIds']);
                                            //echo count($data5) . "<br/>";
                                            //echo $data5[0]['PanelTestIds'] . "<br/>";

                                            if (count(array_intersect($arySelectedPanelTestIds, $aryPanelTestIds)) > 0) {
                                                $panelContainsSelectedTest = true;
                                            }

                                            //echo $row['idtests'] . " - " . $row['name'] . " - " . $row['number'] . "<br/>";
                                        } else { // the current selected test is a single test



                                            if (in_array($currIdTests, $aryPanelTestIds)) {
                                                $panelContainsSelectedTest = true;
                                            }
                                        }

                                    }
                                }
                            }


                            if ($panelContainsSelectedTest == false) {
                                // this panel does not contain any tests already selected on this order
                                $aryTests[$row['idtests']] = $currTest;
                                if ($includeDiagnosis && !empty($row['idDiagnosisValidity'])) {
                                    $aryTests[$row['idtests']]->addValidity($row);
                                }
                            }



                        }
                    } else {
                        $aryTests[$row['idtests']] = $currTest;
                        if ($includeDiagnosis && !empty($row['idDiagnosisValidity'])) {
                            $aryTests[$row['idtests']]->addValidity($row);
                        }
                    }


                }


            } else if ($includeDiagnosis) {
                $aryTests[$row['idtests']]->addValidity($row);
            }
        }

        return $aryTests;
    }

    /*
     * Check whether a test exists in a given array of test ids.
     * - Considers if any of the selected test ids
     * are panels or batteries and checks wether the test ids exists with the panel or battery
     * - Considers if the test id is a pannel or battery and check each test within it to make sure it
     * is not in any selected single tests, panels, or batteries.
     * @param $testId
     * @param array $selectedTestIds
     */
    public static function testIdIsSelected($testId, array $selectedTestIds, $commonTestType = null) {
        $conn = parent::connect();
        $isSelected = false;
        $aryAllSelectedTestIds = array();
        foreach ($selectedTestIds as $currTestId) {
            $currTestIds = self::getAllTestIds($currTestId, $conn);
            $aryAllSelectedTestIds = array_merge($currTestIds, $aryAllSelectedTestIds);
        }

        if ($commonTestType != null) {
            if ($commonTestType != 0) {
                $aryTest = self::getTests(array("idtests" => $testId));
                $test = array_shift($aryTest);
                if (in_array($test->idtests, $aryAllSelectedTestIds)) {
                    $isSelected = true;
                }

            } else { // the common test is a battery/panel
                $commonTestIds = self::getAllTestIds($testId, $conn);

                $arrayIntersect = array_intersect($aryAllSelectedTestIds, $commonTestIds);

                if (count($arrayIntersect) > 0) {
                    $isSelected = true;
                }
            }

        }

        return $isSelected;
    }

    // return array of all test ids including battery headers, battery panel headers, single panel headers, battery tests, panel tests, and single tests
    public static function getAllTestIds($testId, mysqli $conn) {
        $sql = "
            SELECT 	t1.idtests AS `testId1`, # t1.number, t1.name, t1.testType, t1.header, t1.active,
                    t2.idtests AS `testId2`, # t2.number, t2.name, t2.testType, t2.header, t2.active,
                    t3.idtests AS `testId3` #, t3.number, t3.name, t3.testType, t3.header, t3.active
            FROM " . self::DB_CSS . "." . self::TBL_TESTS . " t1
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_PANELS . " p1 ON t1.idtests = p1.idpanels
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t2 ON p1.subtestId = t2.idtests AND (t2.idtests IS NULL OR t2.active = true)
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_PANELS . " p2 ON t2.idtests = p2.idpanels
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t3 ON p2.subtestId = t3.idtests AND (t3.idtests IS NULL OR t3.active = true)
            WHERE 	t1.active = true
                    AND t1.idtests = ?
            ORDER BY t1.idtests, t2.idtests, t3.idtests";

        $data = parent::select($sql, array($testId), array("Conn" => $conn));

        if (count($data) != 0) {
            $aryTestIds = array();
            foreach ($data as $row) {
                $testId1 = $row['testId1'];
                $testId2 = $row['testId2'];
                $testId3 = $row['testId3'];

                if ($testId1 != null && !in_array($testId1, $aryTestIds)) {
                    $aryTestIds[] = $testId1;
                }
                if ($testId2 != null && !in_array($testId2, $aryTestIds)) {
                    $aryTestIds[] = $testId2;
                }
                if ($testId3 != null && !in_array($testId3, $aryTestIds)) {
                    $aryTestIds[] = $testId3;
                }
            }
            return $aryTestIds;
        }

        return null;
    }

    public static function getPOCTests(array $settings = null) {
        $sql = "
             SELECT pa.idpanels AS 'panelId', pa.subtestId AS 'idtests', t.name, m.idMultiChoice, m.choice, m.isAbnormal, m.choiceOrder
             FROM " . self::DB_CSS . "."  . self::TBL_PREFERENCES . " p
             INNER JOIN " . self::DB_CSS . "."  . self::TBL_PANELS . " pa ON pa.idpanels = p.value
             INNER JOIN " . self::DB_CSS . "."  . self::TBL_TESTS . " t ON t.idTests = pa.subtestId
             INNER JOIN " . self::DB_CSS . "."  . self::TBL_MULTICHOICE . " m ON m.testId = pa.subtestId
             WHERE t.active = 1 AND p.key = ?
             ORDER BY pa.subtestOrder ASC";
        // ORDER BY t.name ASC, m.choiceOrder

        $results = parent::select($sql, array("POCTest"), $settings);
		if (count($results) > 0) {
			$aryPOCTests = array();
			$currTestId = $results[0]['idtests'];
			$pocTest = new POCTest($results[0]);
			$i = 0;
			foreach ($results as $row) {

				if ($row['idtests'] != $currTestId) { // new test
					$aryPOCTests[] = $pocTest;

					$pocTest = new POCTest($row);
				} elseif ($i != 0) {
					$pocTest->addChoice($row);
				}

				$currTestId = $row['idtests'];
				$i++;
			}

            $aryPOCTests[] = $pocTest; // add the last test to the return array

			return $aryPOCTests;
		}
		return false;
    }

    public static function getPanelTests($idpanels, mysqli $conn = null) {
        $aryTestPanel = array();
        /*$sql = "
            SELECT idpanels, subtestId, subtestOrder, t.idtests, t.name, t.testType, t.number
            FROM " . self::DB_CSS . "." . self::TBL_PANELS . " p
            INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON t.idtests = p.subtestId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t2 ON p.idpanels = t2.idtests
            WHERE p.idpanels = ?
                AND t.idtests != ?
                AND t2.active = 1";*/
        $sql = "
            SELECT idpanels, subtestId, subtestOrder, t.idtests, t.name, t.testType, t.number
            FROM " . self::DB_CSS . "." . self::TBL_PANELS . " p
            INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON t.idtests = p.subtestId
            WHERE t.active = 1 AND p.idpanels = ? AND t.idtests != ?";


        $arySettings = null;
        if ($conn != null) {
            $arySettings = array("Conn" => $conn);
        }

        $data = parent::select($sql, array ($idpanels, $idpanels), $arySettings);

        if (count($data) > 0) {
            foreach ($data as $row) {
                $panelTest = new PanelTest($row);

                $aryTestPanel[] = $panelTest;
            }
            return $aryTestPanel;
        }

        return $aryTestPanel;
    }

    public static function getPanelTestsByNumber($testNumber, array $settings = null) {
        $aryTestPanel = array();
        $aryInput = array($testNumber);

        $whereLocationId = "";
        if ($settings != null && array_key_exists("UserLocationId", $settings) && !empty($settings['UserLocationId']) && $settings['UserLocationId'] != 0) {
            $whereLocationId = " AND t.locationId = ?";
            $aryInput[] = $settings['UserLocationId'];
        }

        $sql = "
            SELECT p.idpanels, p.subtestId, p.subtestOrder, t2.idtests, t2.number, t2.name, t2.testType
            FROM " . self::DB_CSS . "." . self::TBL_TESTS . " t
            INNER JOIN " . self::DB_CSS . "." . self::TBL_PANELS . " p ON t.idtests = p.idpanels
            INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t2 ON p.subtestId = t2.idtests
            WHERE t.active = true AND t2.active = true AND t.number = ? $whereLocationId";

        $data = parent::select($sql, $aryInput, $settings);

        if (count($data) > 0) {
            foreach ($data as $row) {
                $panelTest = new PanelTest($row);
                $aryTestPanel[] = $panelTest;
            }
            return $aryTestPanel;
        }
        return null;
    }
    
    public static function checkDiagnosisCode($testId, $insurance, $secondaryInsurance, $code) {
        $sql = "
            SELECT c.idDiagnosisCodes, code, description, testId, insuranceId, validity
            FROM " . self::DB_CSS . "."  . self::TBL_DIAGNOSISCODES . " c
            INNER JOIN " . self::DB_CSS . "."  . self::TBL_DIAGNOSISVALIDITY . " v ON v.diagnosisCodeId = c.idDiagnosisCodes
            WHERE testId = ? AND code = ?
        ";
        $input = array ($testId, $code);
        
        //echo $sql . "<br/>";
        //echo "<pre>"; print_r($input); echo "</pre>";
        
        $data = parent::select($sql, $input);
        
        if (count($data) == 0) {
            echo "YELLOW"; // no match found for testid and code
        } else if (count($data) == 1) {
            if (($insurance != 0 && $insurance == $data[0]['idinsurances']) || ($secondaryInsurance != 0 && $secondaryInsurance == $data[0]['secondaryInsurance'])) {
                // match found, now check the validity field to see if the insurance covers this test
                if ($data[0]['validity'] == "ALL" || $data[0]['validity'] == "OK") {
                    echo "GREEN"; 
                } else {
                    // the validity is either "NOT" or "NONE", so the insurance does not cover this test
                    echo "RED"; 
                }
            } else if (empty($data[0]['idinsurances'])) {
                if ($data[0]['validity'] == "ALL") {
                    echo "GREEN";
                } else if ($data[0]['validity'] == "NONE") {
                    echo "RED";
                }                
            } else if ($insurance == 0 || $secondaryInsurance == 0) {
                // a testid/code match was found, but the user didn't select an insurance yet
                echo "YELLOW"; 
            }
        } else {
            $responce = "YELLOW"; // default to match found but insurance not yet selected
            $found = false;
            foreach ($data as $row) { // loop through each test & code
                
                if (!$found) {
                    if ((($insurance != 0 && $insurance == $row['idinsurances']) || ($secondaryInsurance != 0 && $secondaryInsurance == $row['secondaryInsurance'])) && $row['validity'] == "OK") {
                        $responce = "GREEN";
                        $found = true;
                    } else if (empty($row['idinsurances'])) {
                        if ($row['validity'] == "ALL") { // all insurances are accepted for this test
                            $responce = "GREEN";
                            $found = true;
                        } else if ($row['validity'] == "NONE") { // no insurances are accepted for this test
                            $responce = "RED";
                            $found = true;
                        }
                    }                  
                    
                    
                }
                
            }
            echo $responce;
        }
    }

    public static function getTestsBySearch(array $data) {
        $aryTests = array();
        $aryInput = array();

        $sql = "
          SELECT 	DISTINCT t.idtests, t.number, t.name AS `testName`, t.testType, d.idDepartment, d.deptNo, d.deptName, s.idspecimenTypes, s.name AS `specimenTypeName`
            FROM " . self::DB_CSS . "." . self::TBL_TESTS . " t
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMONTESTS . " ct ON t.number = ct.testNumber
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_EXCLUDEDTESTS . " et ON t.number = et.testNumber
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTS . " d ON t.department = d.idDepartment
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SPECIMENTYPES . " s ON t.specimenType = s.idspecimenTypes
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_PREFERENCES . " pre ON pre.`key` = 'POCTest' AND t.idtests = pre.value
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_PANELS . " p ON t.idtests = p.idpanels
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_USERS . " u ON (ct.userId = u.idUsers OR et.userId = u.idUsers) AND u.idUsers = ?
            WHERE   t.active = true
                    AND pre.`key` IS NULL
                    AND (
                        p.idpanels IS NULL # Single test
                        OR (t.testType != 0 AND t.idtests = p.subtestId) # Panel test
                        OR (t.testType = 0 AND t.idtests = p.idpanels) # Panel header
                    ) ";
        foreach ($data as $aryParam) {
            /*
             array (
                "field",
                "condition",
                "value"
            )
            */
            $aryInput[] = $aryParam[2];
            if ($aryParam[0] != "u.idUsers") {
                $sql .= "AND " . $aryParam[0] . " " . $aryParam[1] . " ? ";

            }
        }
        $sql .= " ORDER BY t.name ASC; ";

        /*error_log($sql);
        error_log(implode($aryInput, ","));*/

        $testData = parent::select($sql, $aryInput);
        if (count($testData) > 0) {
            foreach ($testData as $row) {
                $aryTests[] = new Test($row);
            }
        }

        return $aryTests;
    }

    public static function getSpecimenTypes(mysqli $conn = null) {
        $sql = "
            SELECT st.idspecimenTypes, st.name, st.code
            FROM " . self::DB_CSS . "." . self::TBL_SPECIMENTYPES . " st";
        if ($conn != null && $conn instanceof mysqli) {
            $data = parent::select($sql, null, array("Conn" => $conn));
        } else {
            $data = parent::select($sql);
        }
        return $data;
    }
}

?>