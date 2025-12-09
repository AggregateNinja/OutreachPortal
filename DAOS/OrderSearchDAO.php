<?php
require_once "DataObject.php";
require_once "DOS/Order.php";
require_once "ResultLogDAO.php";
require_once "PreferencesDAO.php";
require_once "DOS/OrderSearchForm.php";
require_once "Utility/ItemSorter.php";
/**
 * Description of ResultSearchDAO
 * @author Edd
 */
class OrderSearchDAO extends DataObject
{

    private $Conn;
    private $User;
    private $OrderSearchForm;
    private $LastLogin;
    private $TotalOrders;
    private $Orders;
    private $AllOrders;

    public function __construct(array $searchFields, User $user = null, mysqli $conn = null)
    {
        if ($conn != null && $conn instanceof mysqli) {
            $this->Conn = $conn;
        } else {
            $this->Conn = parent::connect();
        }


        //echo "<pre>"; print_r($searchFields); echo "</pre>";

        if ($user != null) {
            $this->User = $user;
        }

        $this->OrderSearchForm = new OrderSearchForm($searchFields);
        if (array_key_exists("sinceLastLogin", $this->OrderSearchForm->UsedFields) && $this->OrderSearchForm->UsedFields['sinceLastLogin'] == 1) {
            $this->setLastLogin();
        }

        $this->TotalOrders = 0;

        $this->AllOrders = array();
    }

    /*public function getWebReports() {
        $sql = "
            SELECT  rt.name, SUBSTRING(rt.filePath, 21) AS 'filePath'
            FROM " . self::DB_CSS . "." . self::TBL_REPORTTYPE . " rt
            WHERE   rt.format = 'web'";

        $data = parent::select($sql, null, array("Conn" => $this->Conn));

        $aryReturn = array();
        if (count($data) > 0) {
            foreach($data as $row) {
                $aryReturn[] = $row;
            }
        }

        return $aryReturn;
    }*/

    public static function getPatientResultSearch(array $data)
    {
        if (array_key_exists("arNo", $data) && isset($data['arNo']) && !empty($data['arNo'])) {
            $aryInput = array($data['arNo']);
            $sql = "SELECT o.idOrders, o.accession, r.idResults,
                r.testId, r.panelId,
                t.number, t.name,
                r.resultText, r.resultNo, r.resultRemark, r.resultChoice,
                re.remarkNo, re.remarkAbbr, re.remarkName, re.remarkText, re.isAbnormal,
                r.isAbnormal,
                r.isApproved,
                o.orderDate, o.specimenDate
            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
            INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests 
            INNER JOIN " . self::DB_CSS . "." . self::TBL_PREFERENCES . " pre ON pre.`key` = 'CovidTestNumber' AND t.number = pre.value
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_REMARKS . " re ON r.resultRemark = re.idremarks
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t2 ON r.panelId = t2.idtests
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_PREFERENCES . " pre1 ON pre1.`key` = 'POCTest' AND (r.testId = pre1.value OR r.panelId = pre1.value)
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_PREFERENCES . " pre2 ON pre2.`key` = 'ValidityTests' AND (FIND_IN_SET(t.number, pre2.value) > 0 OR FIND_IN_SET(t2.number, pre2.value) > 0)
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_PREFERENCES . " pre3 ON pre3.`key` = 'ImmunoassayScreens' AND (FIND_IN_SET(t.number, pre3.value) > 0 OR FIND_IN_SET(t2.number, pre3.value) > 0)
            WHERE  pre1.`key` IS NULL
                AND pre2.`key` IS NULL
                AND pre3.`key` IS NULL
                AND r.isApproved = TRUE
                AND r.isInvalidated = FALSE
                AND o.active = TRUE
                AND p.arNo = ?
            ORDER BY o.idOrders DESC
            LIMIT 1";

            /*error_log($sql);
            error_log(implode(", ", $aryInput));*/

            $data = parent::select($sql, $aryInput);
            if (count($data) > 0) {
                $resultText = strtolower($data[0]['resultText']);
                $remarkAbbr = strtolower($data[0]['remarkAbbr']);
                $remarkName = strtolower($data[0]['remarkName']);

                if ((isset($resultText) && !empty($resultText) && ($resultText === 'pos' || $resultText === 'positive' || $resultText === 'detected'))
                    || (isset($remarkAbbr) && !empty($remarkAbbr) && ($remarkAbbr === 'pos' || $remarkAbbr === 'positive' || $remarkAbbr === 'detected'))
                    || (isset($remarkName) && !empty($remarkName) && ($remarkName === 'pos' || $remarkName === 'positive' || $remarkName === 'detected'))) {
                    return 1;
                }
                return 0;
            }
        }
        return 2;
    }

    public static function getPatientUserResultSearch(array $data)
    {
        $aryReturn = array();

        if (array_key_exists("idUsers", $data) && isset($data['idUsers']) && !empty($data['idUsers'])) {
            $aryInput = array($data['idUsers']);
            $sql = "SELECT o.idOrders, o.accession, o.reportType,
                COUNT(o.idOrders) AS `OrderCount`,
                SUM(r.isApproved) AS `ApprovedCount`,
                SUM(r.printAndTransmitted) AS `CompleteCount`,
                o.orderDate, o.specimenDate
            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
            INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTLOOKUP . " pl ON p.idPatients = pl.patientId
            WHERE pl.userId = ?
            GROUP BY o.idOrders
            HAVING OrderCount = ApprovedCount
            ORDER BY o.idOrders DESC
            LIMIT 1";

            $data = parent::select($sql, $aryInput);
            if (count($data) > 0) {
                $aryReturn['idOrders'] = $data[0]['idOrders'];
                $aryReturn['reportType'] = $data[0]['reportType'];
            }
        }

        return $aryReturn;
    }

    public function getResultSearch(array $pageData)
    {

        $arySql = $this->getSql();
        $sql = $arySql[0];
        $aryInput = $arySql[1];

        $orderBy = $pageData['OrderBy'];
        if ($pageData['OrderBy'] == 'accession') {
            $orderBy = "CAST(accession AS UNSIGNED)";
        }

        $sql .= "
        ORDER BY " . $orderBy . " " . $pageData['Direction'] . " "; // add the group by clause to the query

//        error_log($sql);
//        error_log(implode(", ", $aryInput));


        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn)); // execute the query

        $skipAbnormalsCheck = false;
        if (array_key_exists("SkipAbnormalsCheck", $pageData)) {
            $skipAbnormalsCheck = $pageData['SkipAbnormalsCheck'];
        }

        if (count($data) > 0) {
            if (array_key_exists("WithExtendedInfo", $pageData)) {
                $this->Orders = $this->makeOrderArray($data, $pageData['Offset'], $pageData['MaxRows'], true, $skipAbnormalsCheck);
            } else {
                $this->Orders = $this->makeOrderArray($data, $pageData['Offset'], $pageData['MaxRows'], false, $skipAbnormalsCheck);
            }
        } else {
            $this->Orders = null;
        }

        $aryLogFields = array("Conn" => $this->Conn);
        if (array_key_exists("Ip", $pageData) && $pageData['Ip'] != null && !empty($pageData['Ip'])) {
            $aryLogFields['Ip'] = $pageData['Ip'];
        }

        if (isset($this->User) && $this->User instanceof User) {
            ResultLogDAO::addLogEntry($this->User->idUsers, 2, $aryLogFields);
        } else {
            // add log entry for a labstaff result search
        }

        //echo "<pre>"; print_r($this->Orders); echo "</pre>";

        return $this->Orders;
    }

    private function makeOrderArray(array $data, $offset, $maxRows, $withExtendedInfo, $skipAbnormalsCheck = false)
    {
        $currIdOrders = $data[0]['idOrders'];
        $finalData = array($data[0]);
        foreach ($data as $row) {
            if ($row['idOrders'] != $currIdOrders) {
                $finalData[] = $row;
                $currIdOrders = $row['idOrders'];
            }
        }
        $this->TotalOrders = count($finalData);

        //private function makeOrderArray(array $data) {
        $start = $offset; // inclusive
        $end = $start + $maxRows; // exclusive
        if ($end > $this->TotalOrders) {
            $end = $this->TotalOrders;
        }
        $orders = array();

        $i = 0;

        $abnormalsOnly = false;
        if (array_key_exists("abnormalsOnly", $this->OrderSearchForm->UsedFields)) {
            $abnormalsOnly = true;
        }

        foreach ($finalData as $row) {

            $idOrders = $row['idOrders'];
            $reportType = $row['reportType'];
            $stage = $row['stage'];

            // Initially we were using stage = 77 to mean an order is complete, and that is how this was originally build to determine an order's status
            // Now, it has come to be that that is not always the case. So, instead of reworking everything I just added a patch to check if
            // everything is approved/transmitted, and if so then set the $stage to be 77
            if (self::RequireCompleted) {
                if ($row['orderCount'] == $row['completedCount']) {
                    $stage = 77;
                }
            } else {
                if ($row['orderCount'] == $row['approvedCount']) {
                    $stage = 77;
                }
            }

            if ($row['IsWeb'] == 1) {
                $stage = 0;
            }

            if ($row['IsWeb'] == 0 && $stage != 77 && $row['key3'] != null && $row['value3'] === 'true') { // WebVisiblePendingStatus
                if ($row['key2'] != null && $row['value2'] === 'true') { // WebVisibleOnApproved

                    if ($row['resPrint'] == 2 && $row['orderCount'] < $row['approvedCount']) { // Require Completed
                        $stage = 2;
                    } else if ($row['approvedCount'] == 0) {
                        $stage = 2;
                    }

                } else {
                    // OnTransmitted
                    if ($row['resPrint'] == 2 && $row['orderCount'] < $row['completedCount']) { // Require Completed
                        $stage = 2;
                    } else if ($row['completedCount'] == 0) {
                        $stage = 2;
                    }
                }
            }

            $this->AllOrders[] = array($idOrders, $reportType, $stage);

            if ($i >= $start && $i < $end) {
                $currOrder = new Order($row);

                if ($stage == 2) {
                    $currOrder->OrderStatus = "Pending in Lab";
                    $currOrder->stage = 2;
                } else if (self::RequireCompleted) {
                    if ($row['orderCount'] == $row['completedCount']) {
                        $currOrder->OrderStatus = "Complete";
                    }
                } else {
                    if ($row['orderCount'] == $row['approvedCount']) {
                        $currOrder->OrderStatus = "Complete";
                    }
                }

                #if ($row['abnormalCount'] > 0) {
                #    $currOrder->IsAbnormal = true;
                #}
                if ($abnormalsOnly) {
                    if ($row['abnormalCount'] > 0 && $row['IsWeb'] == 0) {
                        $currOrder->IsAbnormal = true;
                    }
                } elseif ($row['IsWeb'] == 0 && $skipAbnormalsCheck == false) {
                    $currOrder->IsAbnormal = $this->orderIsAbnormal($currOrder->idOrders);
                }

                if ($row['active'] != 1) {
                    $currOrder->IsInvalidated = true;
                }

                if ($withExtendedInfo) {
                    $extendedInfo = $this->getExtendedInfo($idOrders);

                    $hasExtendedInfo = false;
                    if (count($extendedInfo['PrescribedDrugs']) > 0) {
                        $currOrder->PrescribedDrugs = $extendedInfo['PrescribedDrugs'];

                        $aryPrescribedNotDetected = array();
                        foreach ($extendedInfo['PrescribedDrugs'] as $substanceId => $prescribedDrug) {
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

                $orders[] = $currOrder;
            }

            $i++;
        }

        return $orders;
    }

    private function getSql()
    {
        $aryWhere = $this->getWhereClause(false, false); // add the where clause for the query
        $aryInput = $aryWhere[1];
        $strWhere = $aryWhere[0];

        $abnormalsOnly = false;
        if (array_key_exists("abnormalsOnly", $this->OrderSearchForm->UsedFields)) {
            $abnormalsOnly = true;
        }

        $typeId = $this->User->typeId;

        $aryAggregateClause = $this->getAggregateClauses();
        $sqlAggregateClause = $aryAggregateClause[0];
        $aryAggregateClauseInput = $aryAggregateClause[1];


        if (array_key_exists("includePending", $this->OrderSearchForm->UsedFields) && $this->OrderSearchForm->UsedFields['includePending'] == 1) {
            // include orders not yet recieved by lab
            $sql = "
                (
                    SELECT 0 AS `IsWeb`, o.idOrders, o.accession, rl.webAccession, o.reportType, r.resultNo, r.isAbnormal,
                            pr.`key`,
                            o.active,
                            COUNT(CASE WHEN r.isInvalidated = false THEN o.idOrders ELSE NULL END) AS `orderCount`,
                            SUM(CASE WHEN r.dateReported IS NOT NULL AND r.isInvalidated = false THEN 1 ELSE 0 END) AS `reportedCount`,
                            SUM(CASE WHEN r.isInvalidated = false THEN r.isApproved ELSE 0 END) AS `approvedCount`,
                            SUM(CASE WHEN r.isInvalidated = false THEN r.printAndTransmitted ELSE 0 END) AS `completedCount`, ";

            if ($abnormalsOnly) {
                $sql .= "
                    SUM(
                        CASE
                            WHEN pr.key = 'Positive' AND pd.PrescriptionId IS NULL THEN 1
                            WHEN pr.key = 'Negative' AND pd.PrescriptionId IS NOT NULL THEN 1
                            WHEN r.isAbnormal AND t.resultType = 'Displacement' THEN 1
                            ELSE 0
                        END
                    ) AS `abnormalCount`,";
            }

            $sql .= "       SUM(r.isInvalidated) AS `invalidatedCount`,
                            c.transType, c.resPrint, c.idClients, c.clientName, c.clientNo,
                            p.idPatients, p.arNo AS `patientNo`, p.lastName AS `patientLastName`, p.firstName AS `patientFirstName`,
                            d.iddoctors, d.number, d.lastName AS `doctorLastName`, d.firstName AS `doctorFirstName`,
                            #gr.report,
                            o.orderDate, o.specimenDate, r.dateReported, r.approvedDate,
                            CASE WHEN o.stage IS NULL THEN 1 ELSE o.stage END AS `stage`,
                            null AS `logDate`, 
                            pr2.`key` AS `key2`, 
                            pr2.value AS `value2`,
                            pr3.`key` AS `key3`,
                            pr3.value AS `value3` ";
            $sql .= $this->getTables(); // add the tables being used in the query
            $sql .= $strWhere;
            //$sql .= $this->getAggregateClauses(); // get GROUP BY and HAVING clauses
            $sql .= $sqlAggregateClause;
            $sql .= "
                ) UNION (
                    SELECT 1 AS `IsWeb`, o.idOrders, o.accession, NULL AS `webAccession`, o.reportType, r.resultNo, r.isAbnormal,
                            pr.`key`,
                            o.active,
                            COUNT(CASE WHEN r.isInvalidated = false THEN o.idOrders ELSE NULL END) AS `orderCount`,
                            SUM(CASE WHEN r.dateReported IS NOT NULL AND r.isInvalidated = false THEN 1 ELSE 0 END) AS `reportedCount`,
                            SUM(CASE WHEN r.isInvalidated = false THEN r.isApproved ELSE 0 END) AS `approvedCount`,
                            SUM(CASE WHEN r.isInvalidated = false THEN r.printAndTransmitted ELSE 0 END) AS `completedCount`, ";

            if ($abnormalsOnly) {
                $sql .= "
                    SUM(
                        CASE
                            WHEN pr.key = 'Positive' AND pd.PrescriptionId IS NULL THEN 1
                            WHEN pr.key = 'Negative' AND pd.PrescriptionId IS NOT NULL THEN 1
                            WHEN r.isAbnormal AND t.resultType = 'Displacement' THEN 1
                            ELSE 0
                        END
                    ) AS `abnormalCount`,";
            }

            $sql .= "       SUM(r.isInvalidated) AS `invalidatedCount`,
                            c.transType, c.resPrint, c.idClients, c.clientName, c.clientNo,
                            if (lo.isNewPatient = 0, p.idPatients, wp.idPatients) AS `idPatients`,
                            if (lo.isNewPatient = 0, p.arNo, wp.arNo) AS `patientNo`,
                            if (lo.isNewPatient = 0, p.lastName, wp.lastName) AS `patientLastName`,
                            if (lo.isNewPatient = 0, p.firstName, wp.firstName) AS `patientFirstName`,
                            d.iddoctors, d.number, d.lastName AS `doctorLastName`, d.firstName AS `doctorFirstName`,
                            #gr.report,
                            o.orderDate, o.specimenDate, r.dateReported, r.approvedDate,
                            CASE WHEN o.stage IS NULL THEN 1 ELSE o.stage END AS `stage`,
                            logDate, pr2.`key` AS `key2`, pr2.value AS `value2`, NULL AS `key3`, NULL AS `value3`
                    FROM " . self::DB_CSS . "." . self::TBL_LOG . " l
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_LOGORDERENTRY . " lo ON l.idLogs = lo.logId
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERRECEIPTLOG . " rl ON l.userId = rl.webUser AND lo.accession = rl.webAccession AND rl.receiptedDate IS NULL
                    
                    LEFT JOIN " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o ON lo.orderId = o.idOrders
                    LEFT JOIN " . self::DB_CSS_WEB . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.idDoctors ";

            if (isset($this->User) && $this->User instanceof User) {

                if ($typeId == 2) { // Client user
                    $sql .= "
                            INNER JOIN 	" . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON c.idClients = cl.clientId
                            INNER JOIN 	" . self::DB_CSS . "." . self::TBL_USERS . " u ON cl.userId = u.idUsers ";
                } else if ($typeId == 3) { // Doctor user
                    $sql .= "
                            INNER JOIN 	" . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON d.iddoctors = dl.doctorId
                            INNER JOIN 	" . self::DB_CSS . "." . self::TBL_USERS . " u ON dl.userId = u.idUsers ";
                } else if ($typeId == 4) { // Patient user
                    $sql .= "
                            INNER JOIN 	" . self::DB_CSS . "." . self::TBL_PATIENTLOOKUP . " pl ON p.idPatients = pl.patientId
                            INNER JOIN 	" . self::DB_CSS . "." . self::TBL_USERS . " u ON pl.userId = u.idUsers ";
                } else { // Insurance user
                    $sql .= "
                            INNER JOIN   " . self::DB_CSS . "." . self::TBL_INSURANCELOOKUP . " il ON o.insurance = il.insuranceId
                            INNER JOIN 	" . self::DB_CSS . "." . self::TBL_USERS . " u ON il.userId = u.idUsers ";
                }
            }

            $sql .= "
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients AND lo.isNewPatient = 0
                    LEFT JOIN " . self::DB_CSS_WEB . "." . self::TBL_PATIENTS . " wp ON  o.patientId = wp.idPatients AND lo.isNewPatient = 1
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_PREFERENCES . " pr ON
                        ((r.testId = pr.value OR r.panelId = pr.value) AND pr.`key` = 'POCTest')
                        OR ((pr.`key` = 'Positive' OR pr.`key` = 'Negative') AND r.resultRemark = pr.value) 
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_PREFERENCES . " pr2 ON pr2.`key` = 'WebVisibleOnApproved' ";

            if ($abnormalsOnly) {
                $sql .= " LEFT JOIN " . self::DB_CSS . "." . self::VIEW_PRESCRIBEDDRUGS . " pd ON o.idOrders = pd.OrderId AND (t.relatedDrug = pd.SubstanceId OR t.relatedDrug = pd.MetaboliteId) ";
            }

            //$sql .= " LEFT JOIN " . self::DB_CSS . "." . self::TBL_GENETICREPORT . " gr ON o.idOrders = gr.idorders ";
            $aryWhere = $this->getWhereClause(true, false);
            $sql .= $aryWhere[0];
            $sql .= " GROUP BY o.idOrders, logId
                    ORDER BY o.idOrders DESC 
                ) ";

            $aryInput = array_merge($aryInput, $aryAggregateClauseInput, $aryWhere[1]);

        } else {

            $sql = "SELECT 0 AS `IsWeb`, o.idOrders, o.accession, rl.webAccession, o.reportType, r.resultNo, r.isAbnormal, pr.`key`, o.active,
                    COUNT(CASE WHEN r.isInvalidated = false THEN o.idOrders ELSE NULL END) AS `orderCount`,
                    SUM(CASE WHEN r.dateReported IS NOT NULL AND r.isInvalidated = false THEN 1 ELSE 0 END) AS `reportedCount`,
                    SUM(CASE WHEN r.isInvalidated = false THEN r.isApproved ELSE 0 END) AS `approvedCount`,
                    SUM(CASE WHEN r.isInvalidated = false THEN r.printAndTransmitted ELSE 0 END) AS `completedCount`, ";

            if ($abnormalsOnly) {
                $sql .= "
                    SUM(
                        CASE
                            WHEN pr.key = 'Positive' AND pd.PrescriptionId IS NULL THEN 1
                            WHEN pr.key = 'Negative' AND pd.PrescriptionId IS NOT NULL THEN 1
                            WHEN r.isAbnormal AND t.resultType = 'Displacement' THEN 1
                            ELSE 0
                        END
                    ) AS `abnormalCount`,";
            }

            $sql .= "       SUM(r.isInvalidated) AS `invalidatedCount`,
                            c.transType, c.resPrint, c.idClients, c.clientName, c.clientNo, p.idPatients, p.arNo AS `patientNo`, p.lastName AS `patientLastName`,
                            p.firstName AS `patientFirstName`, d.iddoctors, d.number, d.lastName AS `doctorLastName`, d.firstName AS `doctorFirstName`,
                            #gr.report,
                            o.orderDate, o.specimenDate, r.dateReported, r.approvedDate,
                            CASE WHEN o.stage IS NULL THEN 1 ELSE o.stage END AS `stage`,
                            null AS `logDate`, pr2.`key` AS `key2`, pr2.value AS `value2`, 
                            pr3.`key` AS `key3`,
                            pr3.value AS `value3` ";
            $sql .= $this->getTables(); // add the tables being used in the query
            $sql .= $strWhere;
            //$sql .= $this->getAggregateClauses(); // get GROUP BY and HAVING clauses
            $sql .= $sqlAggregateClause;

            $aryInput = array_merge($aryInput, $aryAggregateClauseInput);
        }
        return array($sql, $aryInput);
    }

    private function getTables()
    {
        $abnormalsOnly = false;
        if (array_key_exists("abnormalsOnly", $this->OrderSearchForm->UsedFields)) {
            $abnormalsOnly = true;
        }

        $typeId = $this->User->typeId;

        $sqlTables = "";
        if (isset($this->User) && $this->User instanceof User) {

            if (self::HasMultiLocation) {
                if ($typeId == 2) { // Client user
                    $sqlTables .= " FROM " . self::DB_CSS . "." . self::TBL_USERS . " u 
                    INNER JOIN 	" . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON u.idUsers = cl.userId
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c2 ON cl.clientId = c2.idClients
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON c2.npi = c.npi
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON c.idClients = o.clientId 
                    INNER JOIN 	" . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients 
                    LEFT JOIN 	" . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors ";
                } else if ($typeId == 3) { // Doctor user
                    $sqlTables .= " FROM " . self::DB_CSS . "." . self::TBL_USERS . " u 
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON u.idUsers = dl.userId
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d2 ON dl.doctorId = d2.iddoctors
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON d2.NPI = d.NPI
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON d.iddoctors = o.doctorId 
                    INNER JOIN 	" . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients 
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients  ";
                } else if ($typeId == 4) {
                    $sqlTables = " FROM        " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                    INNER JOIN 	" . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
                    INNER JOIN 	" . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
                    LEFT JOIN 	" . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors 
                    INNER JOIN 	" . self::DB_CSS . "." . self::TBL_PATIENTLOOKUP . " pl ON p.idPatients = pl.patientId
                    INNER JOIN 	" . self::DB_CSS . "." . self::TBL_USERS . " u ON pl.userId = u.idUsers ";
                } else { // Insurance user
                    $sqlTables = " FROM        " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                    INNER JOIN 	" . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
                    INNER JOIN 	" . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
                    LEFT JOIN 	" . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors 
                    INNER JOIN   " . self::DB_CSS . "." . self::TBL_INSURANCELOOKUP . " il ON o.insurance = il.insuranceId
                    INNER JOIN 	" . self::DB_CSS . "." . self::TBL_USERS . " u ON il.userId = u.idUsers ";
                }
            } else {
                $sqlTables = "
                FROM        " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                INNER JOIN 	" . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
                INNER JOIN 	" . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
                LEFT JOIN 	" . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors ";
                if ($typeId == 2) { // Client user
                    $sqlTables .= " INNER JOIN 	" . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON c.idClients = cl.clientId
                    INNER JOIN 	" . self::DB_CSS . "." . self::TBL_USERS . " u ON cl.userId = u.idUsers ";
                } else if ($typeId == 3) { // Doctor user
                    $sqlTables .= " INNER JOIN 	" . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON d.iddoctors = dl.doctorId
                    INNER JOIN 	" . self::DB_CSS . "." . self::TBL_USERS . " u ON dl.userId = u.idUsers ";
                } else if ($typeId == 4) {
                    $sqlTables .= " INNER JOIN 	" . self::DB_CSS . "." . self::TBL_PATIENTLOOKUP . " pl ON p.idPatients = pl.patientId
                    INNER JOIN 	" . self::DB_CSS . "." . self::TBL_USERS . " u ON pl.userId = u.idUsers ";
                } else { // Insurance user
                    $sqlTables .= " INNER JOIN   " . self::DB_CSS . "." . self::TBL_INSURANCELOOKUP . " il ON o.insurance = il.insuranceId
                    INNER JOIN 	" . self::DB_CSS . "." . self::TBL_USERS . " u ON il.userId = u.idUsers ";
                }
            }
        }

        $sqlTables .= " INNER JOIN  " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
            INNER JOIN  " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests
            LEFT JOIN 	" . self::DB_CSS . "." . self::TBL_PREFERENCES . " pr ON
                ((r.testId = pr.value OR r.panelId = pr.value) AND pr.`key` = 'POCTest')
                OR ((pr.`key` = 'Positive' OR pr.`key` = 'Negative') AND r.resultRemark = pr.value) 
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_PREFERENCES . " pr2 ON pr2.`key` = 'WebVisibleOnApproved'
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_PREFERENCES . " pr3 ON pr3.`key` = 'WebVisiblePendingStatus' ";

        if ($abnormalsOnly) {
            $sqlTables .= " LEFT JOIN " . self::DB_CSS . "." . self::VIEW_PRESCRIBEDDRUGS . " pd ON o.idOrders = pd.OrderId AND (t.relatedDrug = pd.SubstanceId OR t.relatedDrug = pd.MetaboliteId) ";
        }

        //$sqlTables .= " LEFT JOIN " . self::DB_CSS . "." . self::TBL_GENETICREPORT . " gr ON o.idOrders = gr.idorders ";
        $sqlTables .= " LEFT JOIN " . self::DB_CSS . "." . self::TBL_ORDERRECEIPTLOG . " rl ON o.idOrders = rl.idOrders AND u.idUsers = rl.webUser ";

        return $sqlTables;
    }

    /*private function getTables() {
        $abnormalsOnly = false;
        if (array_key_exists("abnormalsOnly", $this->OrderSearchForm->UsedFields)) {
            $abnormalsOnly = true;
        }

        $typeId = $this->User->typeId;

        $sqlTables = " FROM        " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            INNER JOIN 	" . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
            INNER JOIN 	" . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
            LEFT JOIN 	" . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors ";

        if (isset($this->User) && $this->User instanceof User) {

            if ($typeId == 2) { // Client user
                $sqlTables .= " INNER JOIN 	" . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON c.idClients = cl.clientId
                            INNER JOIN 	" . self::DB_CSS . "." . self::TBL_USERS . " u ON cl.userId = u.idUsers ";
            } else if ($typeId == 3) { // Doctor user
                $sqlTables .= " INNER JOIN 	" . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON d.iddoctors = dl.doctorId
                            INNER JOIN 	" . self::DB_CSS . "." . self::TBL_USERS . " u ON dl.userId = u.idUsers ";
            } else { // Insurance user
                $sqlTables .= " INNER JOIN   " . self::DB_CSS . "." . self::TBL_INSURANCELOOKUP . " il ON o.insurance = il.insuranceId
                            INNER JOIN 	" . self::DB_CSS . "." . self::TBL_USERS . " u ON il.userId = u.idUsers ";
            }
        }

        $sqlTables .= " INNER JOIN  " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
            INNER JOIN  " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests
            LEFT JOIN 	" . self::DB_CSS . "." . self::TBL_PREFERENCES . " pr ON
                ((r.testId = pr.value OR r.panelId = pr.value) AND pr.`key` = 'POCTest')
                OR ((pr.`key` = 'Positive' OR pr.`key` = 'Negative') AND r.resultRemark = pr.value)
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_PREFERENCES . " pr2 ON pr2.`key` = 'WebVisibleOnApproved' ";

        if ($abnormalsOnly) {
            $sqlTables .= " LEFT JOIN " . self::DB_CSS . "." . self::VIEW_PRESCRIBEDDRUGS . " pd ON o.idOrders = pd.OrderId AND (t.relatedDrug = pd.SubstanceId OR t.relatedDrug = pd.MetaboliteId) ";
        }

        $sqlTables .= " LEFT JOIN " . self::DB_CSS . "." . self::TBL_GENETICREPORT . " gr ON o.idOrders = gr.idorders ";
        $sqlTables .= " LEFT JOIN " . self::DB_CSS . "." . self::TBL_ORDERRECEIPTLOG . " rl ON o.idOrders = rl.idOrders AND u.idUsers = rl.webUser ";

        return $sqlTables;
    }*/

    private function getWhereClause($isWebQuery, $isLabstaffSearch = false)
    {

        $input = array();

        if ($isLabstaffSearch == true) {
            $where = " WHERE 1 = 1 ";
        } else if (!$isWebQuery) {
            $where = "
                WHERE   (pr.`key` IS NULL OR pr.`key` != 'POCTest')
                        AND (t.header = 0 OR t.headerPrint = 1)
                        AND !(r.panelId IS NULL AND t.testType = 0)
            ";
        } else {
            $where = "WHERE l.typeId = 6 ";
        }

        if (isset($this->User) && $this->User instanceof User) {

            if ($this->User->typeId == 2) {
                $input[] = $this->User->idUsers;
                $multiUsers = $this->User->MultiUsers;
                if ($this->User->hasUserSetting(4) && isset($multiUsers) && is_array($multiUsers) && count($multiUsers) > 0) {
                    $strQuestionMarks = "?, ";
                    foreach ($multiUsers as $userId) {
                        $input[] = $userId;
                        $strQuestionMarks .= "?, ";
                    }
                    $strQuestionMarks = substr($strQuestionMarks, 0, strlen($strQuestionMarks) - 2);
                    $where .= "AND u.idUsers IN ($strQuestionMarks) ";
                } else {
                    $where .= "AND u.idUsers = ? ";
                }
            } else if ($this->User->typeId == 3 || $this->User->typeId == 6) {
                $input[] = $this->User->idUsers;
                $where .= "AND u.idUsers = ? ";
            }
        }

        foreach ($this->OrderSearchForm->UsedFields as $key => $value) {
            if (array_key_exists($key, $this->OrderSearchForm->getDateFields())) {

                //if (!$isWebQuery || ($key != "reportedDateFrom" && $key != "reportedDateTo" && $key != "reportedFrom" && $key != "reportedTo")) {
                $input[] = $value;
                //}

                switch ($key) {
                    case "patientDOB":
                        if ($isWebQuery) {
                            $where .= " AND (p.dob = ? OR wp.dob = ?) ";
                            $input[] = $value;
                        } else {
                            $where .= " AND p.dob = ? ";
                        }

                        break;

                    case "dosFrom":
                        $where .= " AND o.orderDate BETWEEN ? ";
                        break;
                    case "dosTo":
                        $where .= " AND ? ";
                        break;
                    case "reportedFrom":

                        /*if (!array_key_exists("dosFrom", $this->OrderSearchForm->UsedFields) && !array_key_exists("orderDateFrom", $this->OrderSearchForm->UsedFields)
                                && !array_key_exists("specimenFrom", $this->OrderSearchForm->UsedFields) && !array_key_exists("specimenDateFrom", $this->OrderSearchForm->UsedFields)) {
                            // reported date will always be between the order date - improved speed
                            $where .= " AND o.orderDate >= ? ";
                            $input[] = $value;
                        }*/

                        //if (!$isWebQuery) {
                        $where .= " AND (r.dateReported IS NULL OR r.dateReported BETWEEN ? ";
                        //}
                        break;
                    case "reportedTo":
                        //if (!$isWebQuery) {
                        $where .= " AND ?) ";
                        //}

                        /*if (!array_key_exists("dosTo", $this->OrderSearchForm->UsedFields) && !array_key_exists("orderDateTo", $this->OrderSearchForm->UsedFields)
                            && !array_key_exists("specimenTo", $this->OrderSearchForm->UsedFields) && !array_key_exists("specimenDateTo", $this->OrderSearchForm->UsedFields)) {
                            // reported date will always be between the order date - improved speed
                            $where .= " AND o.orderDate <= ? ";
                            $input[] = $value;
                        }*/

                        break;
                    case "specimenFrom":
                        $where .= " AND o.specimenDate BETWEEN ? ";
                        break;
                    case "specimenTo":
                        $where .= " AND ? ";
                        break;
                    case "orderDateFrom":
                        $where .= " AND o.orderDate BETWEEN ? ";
                        break;
                    case "orderDateTo":
                        $where .= " AND ? ";
                        break;
                    case "reportedDateFrom":

                        /*if (!array_key_exists("dosFrom", $this->OrderSearchForm->UsedFields) && !array_key_exists("orderDateFrom", $this->OrderSearchForm->UsedFields)
                            && !array_key_exists("specimenFrom", $this->OrderSearchForm->UsedFields) && !array_key_exists("specimenDateFrom", $this->OrderSearchForm->UsedFields)) {
                            // reported date will always be between the order date - improved speed
                            $where .= " AND o.orderDate >= ? ";
                            $input[] = $value;
                        }*/

                        //if (!$isWebQuery) {
                        $where .= " AND (r.dateReported IS NULL OR r.dateReported BETWEEN ? ";
                        //}
                        break;
                    case "reportedDateTo":
                        //if (!$isWebQuery) {
                        $where .= " AND ?) ";
                        //}

                        /*if (!array_key_exists("dosTo", $this->OrderSearchForm->UsedFields) && !array_key_exists("orderDateTo", $this->OrderSearchForm->UsedFields)
                            && !array_key_exists("specimenTo", $this->OrderSearchForm->UsedFields) && !array_key_exists("specimenDateTo", $this->OrderSearchForm->UsedFields)) {
                            // reported date will always be between the order date - improved speed
                            $where .= " AND o.orderDate <= ? ";
                            $input[] = $value;
                        }*/

                        break;
                    case "specimenDateFrom":
                        $where .= " AND o.specimenDate BETWEEN ? ";
                        break;
                    case "specimenDateTo":
                        $where .= " AND ? ";
                        break;
                    case "createdFrom":
                        //$where .= " AND r.created BETWEEN ? ";
                        //$where .= " AND o.orderDate BETWEEN ? ";
                        $where .= " AND o.created BETWEEN ? ";
                        break;
                    case "createdTo":
                        //$where .= " AND ? ";
                        $where .= " AND ? ";
                        break;
                    case "approvedDateFrom":
                        $where .= " AND (r.approvedDate IS NULL OR r.approvedDate BETWEEN ? ";
                        break;
                    case "approvedDateTo":
                        $where .= " AND ?) ";
                }

            } else if (array_key_exists($key, $this->OrderSearchForm->getStringFields())) {
                switch ($key) {
                    case "patientFirstName":
                        if ($isWebQuery) {
                            $where .= " AND (p.firstName LIKE ? OR wp.firstName LIKE ?) ";
                            $input[] = "$value%";
                        } else {
                            $where .= " AND p.firstName LIKE ? ";
                        }
                        $input[] = "$value%";
                        break;
                    case "patientLastName":
                        if ($isWebQuery) {
                            $where .= " AND (p.lastName LIKE ? OR wp.lastName LIKE ?) ";
                            $input[] = "$value%";
                        } else {
                            $where .= " AND p.lastName LIKE ? ";
                        }

                        $input[] = "$value%";
                        break;
                    case "doctorFirstName":
                        $where .= " AND d.firstName LIKE ? ";
                        $input[] = "$value%";
                        break;
                    case "doctorLastName":
                        $where .= " AND d.lastName LIKE ? ";
                        $input[] = "$value%";
                        break;
                    case "patientId":
                        if ($isWebQuery) {
                            $where .= " AND (p.arNo = ? OR wp.arNo = ?) ";
                            $input[] = $value;
                        } else {
                            $where .= " AND p.arNo = ? ";
                        }

                        $input[] = $value;
                        break;
                    case "accession":
                        $where .= " AND o.accession = ? ";
                        $input[] = $value;
                        break;
                    case "clientName":
                        $where .= " AND c.clientName LIKE ? ";
                        $input[] = $value . "%";
                        break;
                    case "clientNo":
                        $where .= " AND c.clientNo LIKE ? ";
                        $input[] = $value . "%";
                        break;

                }

            } else if (array_key_exists($key, $this->OrderSearchForm->getCheckboxFields())) {
                switch ($key) {
                    case "invalidatedOnly":
                        $where .= " AND o.active != 1 ";
                        break;
                    case "sinceLastLogin":
                        if ($this->LastLogin != null && !empty($this->LastLogin)) {
                            $where .= " AND o.orderDate >= ? ";
                            $input[] = $this->LastLogin;
                        }
                        break;
                    case "unprintedReports":
                        // select all orderIds that this user has already viewed
                        $sqlUnprinted = "
                            SELECT DISTINCT lv.orderId
                            FROM " . self::DB_CSS . "." . self::TBL_LOGVIEWS . " lv
                            INNER JOIN " . self::DB_CSS . "." . self::TBL_LOG . " l ON lv.logId = l.idLogs
                            WHERE l.userId = ? ";
                        $dataUnprinted = parent::select($sqlUnprinted, array($this->User->idUsers), array("Conn" => $this->Conn));

                        $strUnprintedSql = "";
                        if (count($dataUnprinted) > 0) {
                            // add them to the query so that only orderIds are selected if they are not in this list
                            foreach ($dataUnprinted as $unprintedRow) {
                                $input[] = $unprintedRow['orderId'];
                                $strUnprintedSql .= "?, ";
                            }
                            $strUnprintedSql = substr($strUnprintedSql, 0, strlen($strUnprintedSql) - 2);
                            $where .= " AND o.idOrders NOT IN ($strUnprintedSql) ";
                        }
                        break;
                    case "translationalOnly":
                        $where .= " AND gr2.report IS NOT NULL ";
                        break;
                    case "pastTwentyFourHours":
                        $currDateTime = date("Y-m-d H:i:s");
                        $twentyFourHoursAgo = date("Y-m-d H:i:s", strtotime('-24 hour'));
                        //$twentyFourHoursAgo = date("Y-m-d H:i:s", strtotime('-6 month')); // for development only

                        if (self::RequireCompleted) {
                            $where .= " AND r.printAndTransmitted = true AND r.pAndTDate BETWEEN ? AND ? ";
                        } else {
                            $where .= " AND r.isApproved = true AND r.approvedDate BETWEEN ? AND ? ";
                        }
                        $where .= " AND o.clientId = 1 "; // development only to speed up searches
                        $input[] = $twentyFourHoursAgo;
                        $input[] = $currDateTime;
                        break;

                }
            }
        }

        if ($this->SiteUrl == "https://cardiopathoutreach.com/outreach/") {
            $where .= " AND o.orderDate >= ?";
            $input[] = "2020-09-22 00:00:00";
        } else if ($this->SiteUrl == "https://cardiotropicoutreach.com/outreach/") {
            $where .= " AND o.orderDate < ?";
            $input[] = "2020-09-22 00:00:00";
        }

        return array($where, $input);
    }

    private function getAggregateClauses()
    {

        if (self::RequireCompleted && self::HoldUntilCompleteOnly) {
            $aggregateClause = "
            GROUP BY idOrders
            HAVING (
                (orderCount = completedCount AND c.resPrint IS NOT NULL AND c.resPrint = 2)
	            OR (approvedCount > 0 AND (c.resPrint IS NULL OR c.resPrint != 2))
            ) ";
        } else {
            $aggregateClause = "
            GROUP BY idOrders
            HAVING (
                (
                    key2 IS NOT NULL 
                    AND value2 = 'true'
                    AND (
                        (orderCount = approvedCount AND c.resPrint IS NOT NULL AND c.resPrint = 2)
                        OR (approvedCount > 0 AND (c.resPrint IS NULL OR c.resPrint != 2))
                    )
                ) OR (
                    (orderCount = completedCount AND c.resPrint IS NOT NULL AND c.resPrint = 2)
                    OR (completedCount > 0 AND (c.resPrint IS NULL OR c.resPrint != 2))
                ) OR (pr3.`key` IS NOT NULL AND pr3.value = 'true')
            ) ";
        }
        /*} else if (self::RequireCompleted) {
            $aggregateClause = "
            GROUP BY idOrders
            HAVING (
                (orderCount = completedCount AND c.resPrint IS NOT NULL AND c.resPrint = 2)
	            OR (completedCount > 0 AND (c.resPrint IS NULL OR c.resPrint != 2))
            ) ";
        } else {
            $aggregateClause = "
            GROUP BY idOrders
            HAVING (
                (orderCount = approvedCount AND c.resPrint IS NOT NULL AND c.resPrint = 2)
	            OR (approvedCount > 0 AND (c.resPrint IS NULL OR c.resPrint != 2))
            ) ";
        }*/

        foreach ($this->OrderSearchForm->UsedFields as $key => $value) {
            if (array_key_exists($key, $this->OrderSearchForm->getCheckboxFields())) {
                switch ($key) {
                    /*case ("invalidatedOnly") :
                        $aggregateClause .= " AND invalidatedCount > 0 ";
                        break;*/
                    case "abnormalsOnly":
                        $aggregateClause .= " AND abnormalCount > 0 ";
                        break;
                    case "completeOnly":
                        if (self::RequireCompleted) {
                            // use printAndTransmitted flag to determine if a result is complete
                            $aggregateClause .= " AND ((key2 IS NULL OR value2 = 'false') AND (orderCount - invalidatedCount) <= completedCount) ";
                        } else {
                            // use isApproved flag
                            $aggregateClause .= " AND (orderCount - invalidatedCount) <= approvedCount ";
                        }
                        break;
                    case "incompleteOnly":
                        if (self::RequireCompleted) {
                            // use printAndTransmitted flag to determine if a result is complete
                            $aggregateClause .= " AND ((key2 IS NULL OR value2 = 'false') AND (orderCount - invalidatedCount) > completedCount) ";
                        } else {
                            // use isApproved flag
                            $aggregateClause .= " AND (orderCount - invalidatedCount) > approvedCount ";
                        }
                        break;
                    case "consistentOnly":

                        break;
                    case "inconsistentOnly":

                        break;
                }
            }
        }


        $aryInput = array();
        /*if (array_key_exists("createdFrom", $this->OrderSearchForm->UsedFields) && array_key_exists("createdTo", $this->OrderSearchForm->UsedFields)) {
            $createdFrom = $this->OrderSearchForm->UsedFields['createdFrom'];
            $createdTo = $this->OrderSearchForm->UsedFields['createdTo'];

            $aggregateClause .= " AND (
                MAX(r.created) BETWEEN ? AND ?
                OR MIN(r.created) BETWEEN ? AND ?
            ) ";

            $aryInput = array($createdFrom, $createdTo, $createdFrom, $createdTo);
        }*/

        //return $aggregateClause;
        return array($aggregateClause, $aryInput);
    }

    public function getExtendedInfo($idOrders)
    {
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

    private function orderIsAbnormal($orderId)
    {
        $isAbnormal = false;
        $sql = "
            SELECT 	r.orderId,
                    r.testId, r.panelId, t.number, t.name,
                    r.resultText,
                    p.`key`,
                    t.relatedDrug,
                    CAST(r.isAbnormal AS UNSIGNED) AS `isAbnormal`,
                    d.promptPOC,
                    pd.PrescriptionId, pd.SubstanceId, pd.MetaboliteId, pd.GenericName, pd.SubstanceName, pd.MetaboliteName
            FROM " . self::DB_CSS . "." . self::TBL_RESULTS . " r
            INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests AND (t.header = 0 OR t.headerPrint = 1)
            INNER JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTS . " d ON t.department = d.idDepartment
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_PREFERENCES . " p ON ((p.key = 'Positive' OR p.key = 'Negative') AND r.resultRemark = p.value)
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_PREFERENCES . " p2 ON p2.key = 'POCTest' AND (r.testId = p2.value OR r.panelId = p2.value)
            LEFT JOIN " . self::DB_CSS . "." . self::VIEW_PRESCRIBEDDRUGS . " pd ON r.orderId = pd.OrderId AND (t.relatedDrug = pd.SubstanceId OR t.relatedDrug = pd.MetaboliteId)
            WHERE 	r.orderId = ?
                    AND p2.key IS NULL
                    AND t.testType != 0";
        if (self::RequireCompleted) {
            $sql .= " AND r.printAndTransmitted = true ";
        } else {
            $sql .= " AND r.isApproved = true ";
        }
        $data = parent::select($sql, array($orderId), array("Conn" => $this->Conn));
        if (count($data) > 0) {
            foreach ($data as $row) {
                if ($row['promptPOC'] == 0) { // Chemistry or other non-LCMS test
                    if ($row['isAbnormal'] == 1) {
                        $isAbnormal = true;
                    }
                } else { // Check for consistency
                    if ($row['key'] == "Positive" && $row['PrescriptionId'] == null) {
                        $isAbnormal = true; // Not Prescribed Detected
                    } else if ($row['key'] == "Negative" && $row['SubstanceId'] != null && $row['relatedDrug'] == $row['SubstanceId']) {
                        $isAbnormal = true; // Prescribed Not Detected
                    }
                }

            }
        }

        return $isAbnormal;
    }

    //public static function invalidateOrder($orderId, $invalidatedBy, $orderStatus) {
    public static function invalidateOrder(array $input)
    {
        $orderStatus = null;
        $orderId = null;
        $adminUserId = null; // the userId of the administrator
        $userId = null; // the userId of the client/doctor
        $conn = null;
        if (array_key_exists("orderStatus", $input)) {
            $orderStatus = $input['orderStatus'];
        }
        if (array_key_exists("orderId", $input)) {
            $orderId = $input['orderId'];
        }
        if (array_key_exists("adminUserId", $input)) {
            $adminUserId = $input['adminUserId'];
        }
        if (array_key_exists("userId", $input)) {
            $userId = $input['userId'];
        }
        if (array_key_exists("Conn", $input) && $input['Conn'] != null && $input['Conn'] instanceof mysqli) {
            $conn = $input['Conn'];
        }

        /*$sql = "
            UPDATE " . self::TBL_ORDERS . "
            SET active = 0
            WHERE idOrders = ?
        ";*/
        if ($orderStatus == 0) {

            $sql = "
                UPDATE " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o
                SET o.active = 0
                WHERE o.idOrders = ?
            ";

            //echo "<pre>$sql</pre>$orderId";

            parent::manipulate($sql, array($orderId), array("Conn" => $conn)); // cancel web entered order

            $sql = "
                UPDATE " . self::DB_CSS_WEB . "." . self::TBL_RESULTS . " r
                SET r.isInvalidated = ?, r.invalidatedDate = ?, r.invalidatedBy = ?
                WHERE r.orderId = ?
            ";
            $aryResultsInput = array(1, date("Y-m-d H:i.s"), $adminUserId, $orderId);
            parent::manipulate($sql, $aryResultsInput, array("Conn" => $conn));  // cancel web entered order
        } else {
            $sql = "
                UPDATE " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                SET o.active = 0
                WHERE o.idOrders = ?
            ";
            parent::manipulate($sql, array($orderId), array("Conn" => $conn)); // invalidate order

            $sql = "
                UPDATE " . self::DB_CSS . "." . self::TBL_RESULTS . " r
                SET r.isInvalidated = ?, r.invalidatedDate = ?, r.invalidatedBy = ?
                WHERE r.orderId = ?
            ";
            $aryResultsInput = array(1, date("Y-m-d H:i.s"), $adminUserId, $orderId);
            parent::manipulate($sql, $aryResultsInput, array("Conn" => $conn)); // invalidate order
        }

        ResultLogDAO::orderInvalidatedLogEntry($input);
    }

    public static function getIdOrdersList($searchFields, $clientId)
    {
        $frmOrderSearch = new OrderSearchForm($searchFields);
        $sql = "
            SELECT  idOrders
            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
            INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
            INNER JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.idDoctors
        ";
        $where = "WHERE c.idClients = :idClients ";

        $where .= self::getWhereClause($frmOrderSearch);
        $sql .= $where;
        $input = $frmOrderSearch->UsedFields;
        $input['idClients'] = $clientId;

        $idOrdersList = self::select($sql, $input, 1);

        $return = array();
        foreach ($idOrdersList as $row) {
            $return[] = $row[0];
        }
        return $return;
    }

    public function getDefaultReportType()
    {
        $sql = "
            SELECT value
            FROM " . self::DB_CSS . "." . self::TBL_PREFERENCES . " p
            WHERE p.key = ?";
        $data = parent::select($sql, array('DefaultResultReport'), array("Conn" => $this->Conn));
        return $data[0]['value'];
    }

    private function setLastLogin()
    {
        $sqlLastLogin = "
            SELECT l.logDate
            FROM " . self::DB_CSS . "." . self::TBL_LOG . " l
            WHERE l.userId = ? AND l.typeId = 1
            ORDER BY l.logDate DESC
            LIMIT 2
        ";
        $dataLastLogin = parent::select($sqlLastLogin, array($this->User->idUsers), array("Conn" => $this->Conn));

        // get the second row, because the first row reflects when the user logged in for this session
        if (count($dataLastLogin) > 1) {
            $this->LastLogin = $dataLastLogin[1]['logDate']; // referenced from UserDAO->getLastLogin();
        } else {
            $this->LastLogin = null;
        }
    }


    public function __get($field)
    {
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

    /*public function setRequireCompleted($value) {
        $this->RequireCompleted = $value;
    }*/

    /*public function __destruct() {
        if (isset($this->Conn) && $this->Conn instanceof mysqli) {
            $this->Conn->close();
        }
    }  */

    private function sortOrders(array $pageData, array $orders)
    {
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

        $start = $pageData['Offset']; // inclusive
        $end = $start + $pageData['MaxRows']; // exclusive
        if ($end > $this->TotalOrders) {
            $end = $this->TotalOrders;
        }

        $aryPageOrders = array();
        for ($i = $start; $i < $end; $i++) {
            $currOrder = $orders[$i];
            $aryPageOrders[] = $currOrder;
        }
        $orders = $aryPageOrders;

        return $orders;
    }

    /*public function getOrderCount() {
        $sql = "SELECT o.idOrders, r.dateReported, c.transType,
		            COUNT(idOrders) AS `orderCount`,
                    SUM(if( r.dateReported IS NOT NULL, 1, 0)) AS `reportedCount`,
                    SUM(r.printAndTransmitted) AS `completedCount`,
                    SUM(r.isAbnormal) AS `abnormalCount`,
                    SUM(r.isInvalidated) AS `invalidatedCount` ";
        $sql .= $this->getTables();
        $aryWhere = $this->getWhereClause();
        $input = $aryWhere[1]; // get the query input from the return array
        $sql .= $aryWhere[0]; // get the where clause string from the return array

        $sql .= $this->getAggregateClauses(); // add the group by clause to the query

        //echo $sql . "<pre>"; print_r($input); echo "</pre>";
        //echo "<pre>"; print_r($this->OrderSearchForm); echo "</pre>";
        $data = parent::select($sql, $input, array("Conn" => $this->Conn)); // execute the query
        return parent::numRows($sql, $input, array("Conn" => $this->Conn));
    }*/

    /*private function getWebOrderData(array $pageData) {
        $sql = "
            SELECT o.idOrders, o.accession, o.active, o.reportType, r.resultNo, r.isAbnormal, pr.key, o.active,
                            COUNT(idOrders) AS `orderCount`,
                            SUM(if( r.dateReported IS NOT NULL, 1, 0)) AS `reportedCount`,
                            SUM(r.printAndTransmitted) AS `completedCount`,
                            SUM(r.isAbnormal) AS `abnormalCount`,
                            SUM(r.isInvalidated) AS `invalidatedCount`,
                            c.transType, c.idClients, c.clientName, c.clientNo, p.idPatients, p.arNo AS `patientNo`, p.lastName AS `patientLastName`,
                            p.firstName AS `patientFirstName`, d.iddoctors, d.number, d.lastName AS `doctorLastName`, d.firstName AS `doctorFirstName`,
                            o.orderDate, o.specimenDate, r.dateReported, r.approvedDate,
                            if (
                                o.stage IS NULL,
                                0,
                                o.stage
                            ) AS `stage`, logDate
            FROM css." . self::TBL_LOG . " l
            INNER JOIN css." . self::TBL_LOGORDERENTRY . " lo ON l.idLogs = lo.logId
            LEFT JOIN cssweb." . self::TBL_ORDERS . " o ON lo.orderId = o.idOrders
            LEFT JOIN cssweb." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
            INNER JOIN css." . self::TBL_TESTS . " t ON r.testId = t.idtests
            INNER JOIN css." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
            LEFT JOIN css." . self::TBL_DOCTORS . " d ON o.doctorId = d.idDoctors
            LEFT JOIN css." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients AND lo.isNewPatient = 0
            LEFT JOIN css." . self::TBL_PREFERENCES . " pr ON (r.testId = pr.value OR r.panelId = pr.value) AND pr.key = 'POCTest'
            WHERE l.userId = ? AND l.typeId = 6
            GROUP BY o.idOrders, logId DESC
            ORDER BY o.idOrders, l.logDate DESC ";

        $input = array($this->User->idUsers);

        echo $sql;
        echo "<pre>"; print_r($input); echo "</pre>";

        $data = parent::select($sql, $input, array("Conn" => $this->Conn));

        $aryPendingOrders = array();

        if (count($data) > 0) {
            $currOrderId = $data[0]['idOrders'];
            $aryPendingOrders[] = $data[0];
            foreach ($data as $row) {
                if ($row['idOrders'] != $currOrderId) {
                    $currOrderId = $row['idOrders'];
                    $aryPendingOrders[] = $row;
                }
            }
            return $aryPendingOrders;
        }

        return null;
    }*/
}