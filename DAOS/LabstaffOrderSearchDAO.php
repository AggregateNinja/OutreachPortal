<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 6/15/15
 * Time: 12:18 PM
 */

require_once "DataObject.php";
require_once "DOS/Order.php";
require_once "DAOS/ResultLogDAO.php";
require_once "PreferencesDAO.php";
require_once "DOS/OrderSearchForm.php";
require_once "Utility/ItemSorter.php";
require_once "DOS/DoctorUser.php";
require_once "DOS/Patient.php";

class LabstaffOrderSearchDAO extends DataObject {

    private $Conn;
    private $OrderSearchForm;
    private $LastLogin;
    private $TotalOrders;
    private $Orders;
    private $AllOrders;
    private $ReportedDateSearch = false;
    private $ApprovedDateSearch = false;
    private $Offset;
    private $MaxRows;
    private $OrderBy;
    private $Direction;


    public function __construct(array $searchFields, mysqli $conn = null) {
        if ($conn != null && $conn instanceof mysqli) {
            $this->Conn = $conn;
        } else {
            $this->Conn = parent::connect();
        }

        $this->OrderSearchForm = new OrderSearchForm($searchFields);
        if (array_key_exists("sinceLastLogin", $this->OrderSearchForm->UsedFields) && $this->OrderSearchForm->UsedFields['sinceLastLogin'] == 1) {
            $this->setLastLogin();
        }
        if (array_key_exists("reportedDateFrom", $this->OrderSearchForm->UsedFields)) {
            $this->ReportedDateSearch = true;
        }
        if (array_key_exists("approvedDateFrom", $this->OrderSearchForm->UsedFields)) {
            $this->ApprovedDateSearch = true;
        }

        $this->TotalOrders = 0;
        $this->AllOrders = array();

        $this->Offset = 0;
        $this->MaxRows = 10;
        $this->OrderBy = "orderDate";
        $this->Direction = "desc";
    }

    public function getResultSearch(array $pageData) {
        $this->Offset = $pageData['Offset'];
        $this->MaxRows = $pageData['MaxRows'];
        $this->OrderBy = $pageData['OrderBy'];
        $this->Direction = $pageData['Direction'];

        $withExtendedInfo = false;
        if (array_key_exists("WithExtendedInfo", $pageData)) {
            $withExtendedInfo = true;
        }

        $sql = "

            SELECT 	o.idOrders, o.accession, o.active, o.reportType, o.doctorId,
                    r.resultNo, r.resultRemark, r.isAbnormal, r.isCIDLow, r.isLow, r.isHigh, r.isCIDHigh,
                    c.transType, c.resPrint, c.idClients, c.clientName, c.clientNo,
                    r.isApproved, r.printAndTransmitted,
                    o.orderDate, o.specimenDate, r.dateReported, r.approvedDate, r.pAndTDate
            FROM        " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
            INNER JOIN 	" . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients ";

        $aryWhere = $this->getWhereClause(false, false); // add the where clause for the query

        $aryInput = null;
        if (count($aryWhere[1]) > 0) {
            $aryInput = $aryWhere[1];
            $sql .= $aryWhere[0];
        }


        $sql .= " ORDER BY o.idOrders DESC";

        $data = parent::select($sql, $aryInput);

        //echo "<pre>$sql</pre><pre>"; print_r($aryInput); echo "</pre>";


        $this->Orders = $this->getAllOrders($data, $pageData, $withExtendedInfo);


        //echo "<pre style=\"margin-left: 400px;\">"; print_r($this->Orders); echo "</pre>";
        //echo "<pre style=\"margin-left: 400px;\">"; print_r($sql); echo "</pre>";
        //echo "<pre style=\"margin-left: 400px;\">"; print_r($aryInput); echo "</pre>";
        //echo "<pre>"; print_r($pageData); echo "</pre>";

       return $this->Orders;
    }

    private function getAllOrders(array $data, array $pageData, $withExtendedInfo = false) {
        $rowCount = count($data);
        $aryIdOrders = array();
        $aryOrders = array();
        $orderCount = 1;
        $reportedCount = 0;
        $approvedCount = 0;
        $printAndTransmittedCount = 0;

        if ($rowCount > 0) {

            $currOrder = new Order($data[0]);
            $currResPrint = $data[0]['resPrint'];
            $prevIdOrders = $data[0]['idOrders'];
            //echo "<div style=\"margin-left: 400px;\">$rowCount</div><br/>";

            //$j = 1;
            for ($i = 0; $i < $rowCount; $i++) {



                $row = $data[$i];

                $idOrders = $row['idOrders'];

                if ($idOrders != $prevIdOrders) {



                    //echo "<div style=\"margin-left: 400px;\">" . $currOrder->accession . " - " . $orderCount . "</div><br/>";

                    //$currOrder->setOrderCount($orderCount);

                    $currOrder->OrderCount = $orderCount;
                    $currOrder->ReportedCount = $reportedCount;
                    $currOrder->ApprovedCount = $approvedCount;
                    $currOrder->PrintAndTransmittedCount = $printAndTransmittedCount;

                    if ($currResPrint == 2) {
                        // completed orders only

                        if (self::RequireCompleted) {
                            if ($printAndTransmittedCount == $orderCount) {
                                $aryOrders[] = $currOrder;

                                //$aryIdOrders[] = $data[$i]['accession'];
                                //echo $j . ") " . $data[$i]['accession'] . "<br/>";
                                //$j++;
                            } else {
                                $aryIdOrders[] = $data[$i]['idOrders'];
                            }
                        } else {
                            if ($approvedCount == $orderCount) {
                                $aryOrders[] = $currOrder;

                                //$aryIdOrders[] = $data[$i]['accession'];
                                //echo $j . ") " . $data[$i]['accession'] . "<br/>";
                                //$j++;
                            } else {
                                $aryIdOrders[] = $data[$i]['idOrders'];
                            }
                        }

                    } else {
                        if (self::RequireCompleted) {
                            if ($printAndTransmittedCount > 0) {
                                $aryOrders[] = $currOrder;

                                //$aryIdOrders[] = $data[$i]['accession'];
                                //echo $j . ") " . $data[$i]['accession'] . "<br/>";
                                //$j++;
                            } else {
                                $aryIdOrders[] = $data[$i]['idOrders'];
                            }
                        } else {
                            if ($approvedCount > 0) {
                                $aryOrders[] = $currOrder;

                                //$aryIdOrders[] = $data[$i]['accession'];
                                //echo $j . ") " . $data[$i]['accession'] . "<br/>";
                                //$j++;
                            } else {
                                $aryIdOrders[] = $data[$i]['idOrders'];
                            }
                        }
                    }

                    //$aryOrders[] = $currOrder;

                    $orderCount = 1;
                    $reportedCount = 0;
                    $approvedCount = 0;
                    $printAndTransmittedCount = 0;
                    if ($row['dateReported'] != null) {
                        $reportedCount = 1;
                    }
                    if ($row['isApproved'] == true) {
                        $approvedCount = 1;
                    }
                    if ($row['printAndTransmitted'] == true) {
                        $printAndTransmittedCount = 1;
                    }

                    $currResPrint = $row['resPrint'];
                    $currOrder = new Order($row);

                } else {
                    $orderCount++;

                    if ($row['dateReported'] != null) {
                        if ($this->ReportedDateSearch == true && $row['dateReported'] >= $this->OrderSearchForm->UsedFields['dateReportedFrom'] && $row['dateReported'] <= $this->OrderSearchForm->UsedFields['dateReportedTo']) {
                            // orders were searched by dateReported range, so only increment the count if this result was reported within the date range
                            $reportedCount++;
                        } else if ($this->ReportedDateSearch == false) {
                            $reportedCount++;
                        }
                    }

                    if ($row['approvedDate'] != null) {
                        if ($this->ApprovedDateSearch == true && $row['approvedDate'] >= $this->OrderSearchForm->UsedFields['approvedDateFrom'] && $row['approvedDate'] <= $this->OrderSearchForm->UsedFields['approvedDateTo']) {
                            // orders were searched by approvedDate range, so only increment the count if this result was reported within the date range
                            $approvedCount++;
                        } else if ($this->ApprovedDateSearch == false) {
                            $approvedCount++;
                        }
                    }

                    if ($row['pAndTDate'] != null) {
                        $printAndTransmittedCount++;
                    }
                }

                $prevIdOrders = $row['idOrders'];
            }

            $currOrder->OrderCount = $orderCount;
            $currOrder->ReportedCount = $reportedCount;
            $currOrder->ApprovedCount = $approvedCount;
            $currOrder->PrintAndTransmittedCount = $printAndTransmittedCount;
            $aryOrders[] = $currOrder;
            $this->TotalOrders = count($aryOrders);

            $aryOrders = $this->sortOrders($pageData, $aryOrders);


            $aryOrders = $this->filterOrders($aryOrders, $withExtendedInfo);

        }

        //$aryIdOrders = array_unique($aryIdOrders);
        //echo "<div style=\"margin-left: 400px;\">" . count($aryIdOrders) . "</div><br/>";
        //echo implode(',', $aryIdOrders);

        return $aryOrders;
    }

    private function filterOrders(array $aryOrders, $withExtendedInfo = false) {
        $aryFilteredOrders = array();
        $start = $this->Offset; // inclusive
        $end = $start + $this->MaxRows; // exclusive
        if ($end > $this->TotalOrders) {
            $end = $this->TotalOrders;
        }

        for ($i = 0; $i < $this->TotalOrders; $i++) {
            if ($i >= $start && $i < $end) { // add only orders to be viewed on the current page

                $currOrder = $aryOrders[$i];

                $sql = "
                    SELECT 	p.idPatients, p.arNo AS `patientNo`, p.lastName AS `patientLastName`, p.firstName AS `patientFirstName`,
                            d.iddoctors, d.number, d.lastName AS `doctorLastName`, d.firstName AS `doctorFirstName`
                    FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                    INNER JOIN  " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
                    LEFT JOIN  " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors
                    WHERE o.idOrders = ?;";
                $data = parent::select($sql, array($currOrder->idOrders));

                if (count($data) > 0) {
                    $currOrder->Patient = new Patient($data[0]);

                   // echo "<pre>"; print_r($currOrder); echo "</pre>";

                    if ($data[0]['iddoctors'] != null) {
                        $currOrder->Doctor = new DoctorUser($data[0]);
                    }

                    if ($withExtendedInfo) {
                        $extendedInfo = $this->getExtendedInfo($currOrder->idOrders);

                        $hasExtendedInfo = false;
                        if (count($extendedInfo['PrescribedDrugs']) > 0) {
                            $currOrder->PrescribedDrugs = $extendedInfo['PrescribedDrugs'];

                            $aryPrescribedNotDetected = array();
                            foreach ($extendedInfo['PrescribedDrugs'] as  $substanceId => $prescribedDrug) {
                                if (!array_key_exists($substanceId, $extendedInfo['PrescribedDetected'])) {
                                    $aryPrescribedNotDetected[$substanceId] = $prescribedDrug;
                                }
                            }

                            $currOrder->PrescribedNotDetected = $aryPrescribedNotDetected;
                        }

                        if (count($extendedInfo['PrescribedDetected']) > 0) {
                            $currOrder->PrescribedDetected = $extendedInfo['PrescribedDetected'];
                        }

                        if (count($extendedInfo['NotPrescribedDetected']) > 0) {

                            $currOrder->NotPrescribedDetected = $extendedInfo['NotPrescribedDetected'];
                            //echo "<pre style=\"margin-left: 400px;\">"; print_r($currOrder->NotPrescribedDetected); echo "</pre>";
                        }
                    }

                }



                $aryFilteredOrders[] = $currOrder;


            }
        }



        return $aryFilteredOrders;
    }

    public function getExtendedInfo($idOrders) {
        $sql = "
            (
                SELECT DISTINCT 1 AS `id`, NULL AS `idOrders`, NULL AS `accession`, NULL AS `testId`, NULL AS `panelId`, NULL AS `number`, NULL AS `name`, NULL AS `testType`, NULL AS `relatedDrug`,
                        NULL AS `resultText`, NULL AS `resultRemark`, NULL AS `key`,
                        pd.OrderId, pd.DrugId, pd.GenericName, pd.SubstanceId, pd.SubstanceName, NULL AS `MetaboliteId`, NULL AS `MetaboliteName`,
                        NULL AS `isInvalidated`, NULL AS `isApproved`, NULL AS `printAndTransmitted`
                FROM " . self::DB_CSS . "." . self::VIEW_PRESCRIBEDDRUGS . " pd
                WHERE pd.OrderId = ?
            ) UNION (
                SELECT 	2 AS `id`, o.idOrders, o.accession, r.testId, r.panelId, t.number, t.name, t.testType, t.relatedDrug,
                        r.resultText, r.resultRemark, pre.key,
                        pd.OrderId, pd.DrugId, pd.GenericName, pd.SubstanceId, pd.SubstanceName, pd.MetaboliteId, pd.MetaboliteName,
                        r.isInvalidated, r.isApproved, r.printAndTransmitted
                FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
                INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests
                LEFT JOIN " . self::DB_CSS . "." . self::VIEW_PRESCRIBEDDRUGS . " pd ON o.idOrders = pd.OrderId AND (t.relatedDrug = pd.SubstanceId OR t.relatedDrug = pd.MetaboliteId)
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_PREFERENCES . " pre ON
                    ((pre.key = 'Positive' OR pre.key = 'Negative') AND r.resultRemark = pre.value)
                    OR (pre.key = 'POCTest' AND (r.testId = pre.value OR r.panelId = pre.value))
                WHERE 	o.idOrders = ?
                        AND (pre.key IS NULL OR pre.key != 'POCTest')
                        AND t.header = false
                        AND t.testType != 0
                        AND r.dateReported IS NOT NULL
            );";

        $orderInfo = array(
            "PrescribedDrugs" => array(),
            "PrescribedDetected" => array(),
            "PrescribedNotDetected" => array(),
            "NotPrescribedDetected" => array()
        );

        $data = parent::select($sql, array($idOrders, $idOrders), array("Conn" => $this->Conn));

        if (count($data) > 0) {
            foreach ($data as $row) {

                if ($row['id'] != null && $row['id'] == 1) { // Prescribed Drug
                    $orderInfo['PrescribedDrugs'][$row['SubstanceId']] = "<b>" . $row['GenericName'] . "</b> (" . $row['SubstanceName'] . ")";

                } else if (($row['SubstanceId'] != null || $row['MetaboliteId'] != null) && $row['key'] == "Positive") { // Prescribed Detected
                    if ($row['SubstanceId'] != null) { // Substance detected
                        $orderInfo['PrescribedDetected'][$row['SubstanceId']] = "<b>" . $row['GenericName'] . "</b> (" . $row['SubstanceName'] . ")";
                    } else { // Metabolite detected
                        $orderInfo['PrescribedDetected'][$row['MetaboliteId']] = $row['MetaboliteName'];
                    }
                } else if ($row['SubstanceId'] == null && $row['MetaboliteId'] == null && $row['key'] == "Positive") { // Not Prescribed Detected
                    $orderInfo['NotPrescribedDetected'][$row['testId']] = $row['name'];
                }


            }
        }

        //echo "<pre>"; print_r($orderInfo); echo "</pre>";

        return $orderInfo;
    }


    private function getWhereClause($isWebQuery, $isLabstaffSearch = false) {

        $input = array();

        $where = " WHERE ";


        $usedFields = $this->OrderSearchForm->UsedFields;

        foreach ($usedFields as $key => $value) {
            if (array_key_exists($key, $this->OrderSearchForm->getDateFields())) {
                $input[] = $value;
                switch ($key) {
                    case "patientDOB":
                        if ($isWebQuery) {
                            $where .= "(p.dob = ? OR wp.dob = ?) AND ";
                            $input[] = $value;
                        } else {
                            $where .= "p.dob = ? AND ";
                        }

                        break;
                    case "orderDateFrom":
                        $where .= "o.orderDate BETWEEN ? AND ";
                        break;
                    case "orderDateTo":
                        $where .= "? AND ";
                        break;
                    case "specimenDateFrom":
                        $where .= "o.specimenDate BETWEEN ? AND ";
                        break;
                    case "specimenDateTo":
                        $where .= "? AND ";
                        break;
                    /*
                    case "reportedDateFrom":
                        if (!$isWebQuery) {
                            $where .= " AND r.dateReported BETWEEN ? ";
                        }
                        break;
                    case "reportedDateTo":
                        if (!$isWebQuery) {
                            $where .= " AND ? ";
                        }
                        break;
                    case "approvedDateFrom":
                        $where .= " AND r.approvedDate BETWEEN ? ";
                        break;
                    case "approvedDateTo":
                        $where .= " AND ? ";
                    */
                }

            } else if (array_key_exists($key, $this->OrderSearchForm->getStringFields())) {
                switch ($key) {
                    case "patientFirstName":
                        if ($isWebQuery) {
                            $where .= "(p.firstName LIKE ? OR wp.firstName LIKE ?) AND ";
                            $input[] = "%$value%";
                        } else {
                            $where .= "p.firstName LIKE ? AND ";
                        }
                        $input[] = "%$value%";
                        break;
                    case "patientLastName":
                        if ($isWebQuery) {
                            $where .= "(p.lastName LIKE ? OR wp.lastName LIKE ?) AND ";
                            $input[] = "%$value%";
                        } else {
                            $where .= "p.lastName LIKE ? AND ";
                        }

                        $input[] = "%$value%";
                        break;
                    case "doctorFirstName":
                        $where .= "d.firstName LIKE ? AND ";
                        $input[] = "%$value%";
                        break;
                    case "doctorLastName":
                        $where .= "d.lastName LIKE ? AND ";
                        $input[] = "%$value%";
                        break;
                    case "patientId":
                        if ($isWebQuery) {
                            $where .= "(p.arNo = ? OR wp.arNo = ?) AND ";
                            $input[] = $value;
                        } else {
                            $where .= "p.arNo = ? AND ";
                        }
                        $input[] = $value;
                        break;
                    case "accession":
                        $where .= "o.accession = ? AND ";
                        $input[] = $value;
                        break;
                    case "clientName":
                        $where .= "c.clientName LIKE ? AND ";
                        $input[] = $value . "%";
                        break;
                    case "clientNo":
                        $where .= "c.clientNo LIKE ? AND ";
                        $input[] = $value . "%";
                        break;

                }

            } else if (array_key_exists($key, $this->OrderSearchForm->getCheckboxFields())) {
                switch ($key) {
                    case "invalidatedOnly":
                        $where .= "o.active != 1 AND ";
                        break;
                    case "sinceLastLogin":
                        if ($this->LastLogin != null && !empty($this->LastLogin)) {
                            $where .= "o.orderDate >= ? AND ";
                            $input[] = $this->LastLogin;
                        }
                        break;
                    case "unprintedReports":
                        // select all orderIds that this user has already viewed
                        $sqlUnprinted = "
                            SELECT DISTINCT lv.orderId
                            FROM " . self::TBL_LOGVIEWS . " lv
                            INNER JOIN " . self::TBL_LOG . " l ON lv.logId = l.idLogs
                            WHERE l.userId = ? ";
                        $dataUnprinted = parent::select($sqlUnprinted, array($this->User->idUsers), array("Conn" => $this->Conn));

                        $strUnprintedSql = "";
                        if ($dataUnprinted > 0) {
                            // add them to the query so that only orderIds are selected if they are not in this list
                            foreach ($dataUnprinted as $unprintedRow) {
                                $input[] = $unprintedRow['orderId'];
                                $strUnprintedSql .= "?, ";
                            }
                            $strUnprintedSql = substr($strUnprintedSql, 0, strlen($strUnprintedSql) - 2);
                            $where .= "o.idOrders NOT IN ($strUnprintedSql) AND ";
                        }
                        break;
                    case "translationalOnly":
                        $where .= "gr.report IS NOT NULL AND ";
                        break;
                    /*
                    case "pastTwentyFourHours":
                        $currDateTime = date("Y-m-d H:i:s");
                        //$twentyFourHoursAgo = date("Y-m-d H:i:s", strtotime('-24 hour'));
                        $twentyFourHoursAgo = date("Y-m-d H:i:s", strtotime('-6 month')); // for development only

                        if (self::RequireCompleted) {
                            $where .= " AND r.printAndTransmitted = true AND r.pAndTDate BETWEEN ? AND ? ";
                        } else {
                            $where .= " AND r.isApproved = true AND r.approvedDate BETWEEN ? AND ? ";
                        }
                        //$where .= " AND o.clientId = 1 "; // development only to speed up searches
                        $input[] = $twentyFourHoursAgo;
                        $input[] = $currDateTime;
                        break;
                    */
                }
            }



        }

        // reported date and approved date searches must be handled when looping through the search results, so just use the order date for now
        if (!array_key_exists("orderDateFrom", $usedFields) && (array_key_exists("reportedDateFrom", $usedFields) || array_key_exists("approvedDateFrom", $usedFields))) {
            $where .= "o.orderDate BETWEEN ? AND ? AND ";

            if (array_key_exists("reportedDateFrom", $usedFields)) {
                $input[] = $usedFields['reportedDateFrom'];
                $input[] = $usedFields['reportedDateTo'];
            } else {
                $input[] = $usedFields['approvedDateFrom'];
                $input[] = $usedFields['approvedDateTo'];
            }

        }
        if (!array_key_exists("orderDateFrom", $usedFields) && array_key_exists("pastTwentyFourHours", $usedFields)) {
            $currDateTime = date("Y-m-d H:i:s");
            $twentyFourHoursAgo = date("Y-m-d H:i:s", strtotime('-24 hour'));
            //$twentyFourHoursAgo = date("Y-m-d H:i:s", strtotime('-7 day')); // for development only

            $where .= "o.orderDate BETWEEN ? AND ? AND ";

            //$where .= " AND o.clientId = 1 "; // development only to speed up searches
            $input[] = $twentyFourHoursAgo;
            $input[] = $currDateTime;
        }



        if (count($input) > 0) {
            $where = substr($where, 0, strlen($where) - 4);
        }

        return array($where, $input);
    }


    private function sortOrders(array $pageData, array $orders) {
        //echo "<pre>"; print_r($pageData); echo "</pre>";
        $orderBy = $pageData['OrderBy'];
        $direction = $pageData['Direction'];

        if ($direction == "DESC") {
            switch ($orderBy) {
                case "orderDate":
                    usort($orders, array("ItemSorter", "byOrderDateDescFixed"));
                    break;
                case "accession":
                    usort($orders, array("ItemSorter", "byAccessionDescFixed"));
                    break;
                case "doctorLastName":
                    usort($orders, array("ItemSorter", "byDoctorLastNameDescFixed"));
                    break;
                case "number":
                    usort($orders, array("ItemSorter", "byDoctorNumDescFixed"));
                    break;
                case "clientName":
                    usort($orders, array("ItemSorter", "byClientNameDescFixed"));
                    break;
                case "clientNo":
                    usort($orders, array("ItemSorter", "byClientNumDescFixed"));
                    break;
                case "patientLastName":
                    usort($orders, array("ItemSorter", "byPatientLastNameDescFixed"));
                    break;
                case "patientFirstName":
                    usort($orders, array("ItemSorter", "byPatientFirstNameDescFixed"));
                    break;
                case "specimenDate":
                    usort($orders, array("ItemSorter", "bySpecimenDateDescFixed"));
                    break;
                case "stage":
                    usort($orders, array("ItemSorter", "byOrderStatusDescFixed"));
                    break;
                default:
                    usort($orders, array("ItemSorter", "byOrderDateDescFixed"));
                    break;
            }
        } else { // $direction == "ASC"
            switch ($orderBy) {
                case "orderDate":
                    usort($orders, array("ItemSorter", "byOrderDateAscFixed"));
                    break;
                case "accession":
                    usort($orders, array("ItemSorter", "byAccessionAscFixed"));
                    break;
                case "doctorLastName":
                    usort($orders, array("ItemSorter", "byDoctorLastNameAscFixed"));
                    break;
                case "number":
                    usort($orders, array("ItemSorter", "byDoctorNumAscFixed"));
                    break;
                case "clientName":
                    usort($orders, array("ItemSorter", "byClientNameAscFixed"));
                    break;
                case "clientNo":
                    usort($orders, array("ItemSorter", "byClientNumAscFixed"));
                    break;
                case "patientLastName":
                    usort($orders, array("ItemSorter", "byPatientLastNameAscFixed"));
                    break;
                case "patientFirstName":
                    usort($orders, array("ItemSorter", "byPatientFirstNameAscFixed"));
                    break;
                case "specimenDate":
                    usort($orders, array("ItemSorter", "bySpecimenDateAscFixed"));
                    break;
                case "stage":
                    usort($orders, array("ItemSorter", "byOrderStatusAscFixed"));
                    break;
                default:
                    usort($orders, array("ItemSorter", "byOrderDateAscFixed"));
                    break;
            }
        }

        /*$start = $pageData['Offset']; // inclusive
        $end = $start + $pageData['MaxRows']; // exclusive
        if ($end > $this->TotalOrders) {
            $end = $this->TotalOrders;
        }

        $aryPageOrders = array();
        for ($i = $start; $i < $end; $i++) {
            $currOrder = $orders[$i];
            $aryPageOrders[] = $currOrder;
        }
        $orders = $aryPageOrders;*/

        return $orders;
    }

    public function __get($field) {
        $value = "";
        if ($field == "TotalOrders") {
            $value = $this->TotalOrders;
        } else if ($field == "AllOrders") {
            $value = $this->AllOrders;
        } else if ($field == "Orders") {
            $value = $this->Orders;
        }
        return $value;
    }


} 