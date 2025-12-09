<?php
require_once 'DataObject.php';
require_once "DAOS/ResultLogDAO.php";
require_once 'DOS/Cumulative.php';
/**
 * Description of CumulativeDAO
 *
 * @author Edd
 */
class CumulativeDAO extends DataObject {

    private $Conn;
    public $RequireCompleted;
    public $UserIds;

    public function __construct(mysqli $Conn) {
        $this->Conn = $Conn;
        $this->RequireCompleted = self::RequireCompleted;
    }

    public function getCumulative($arNo, $specimenDate, $ip, User $user, $patientPortal = false) { // 17323
        $sql = "
            SELECT 		o.idOrders, o.accession,
                        t.idtests, t.printedName AS `name`, t.number,
                        r.resultText,
                        r.resultNo,
                        re.remarkAbbr,re.remarkName,
                        CAST(re.remarkText AS CHAR) AS `remarkText`,
                        TRIM(CAST(CASE
                            WHEN t.extraNormals = true AND e.idextraNormals IS NOT NULL THEN
                                CASE
                                    WHEN e.printNormals IS NOT NULL AND TRIM(e.printNormals) != '' THEN e.printNormals
                                    ELSE NULL
                                END
                            ELSE t.printNormals
                        END AS CHAR)) AS `printNormals`,
                        t.units,
                        d.number AS `doctorNumber`, d.firstName AS `doctorFirstName`, d.lastName AS `doctorLastName`,
                        p.idPatients, p.arNo, p.firstName, p.lastName, p.addressStreet, p.addressCity, p.addressState, p.addressZip, p.dob,
                        r.isApproved, r.printAndTransmitted,
                        o.orderDate, o.specimenDate
            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
            INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idTests
            INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.idDoctors ";

        if ($user->typeId == 2) { // Client user
            $sql .= "
                INNER JOIN 	" . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON c.idClients = cl.clientId
                INNER JOIN 	" . self::DB_CSS . "." . self::TBL_USERS . " u ON cl.userId = u.idUsers
            ";
        } else if ($user->typeId == 3) { // Doctor user
            $sql .= "
                INNER JOIN 	" . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON d.iddoctors = dl.doctorId
                INNER JOIN 	" . self::DB_CSS . "." . self::TBL_USERS . " u ON dl.userId = u.idUsers
            ";
        } else { // Insurance user
            $sql .= "
                INNER JOIN   " . self::DB_CSS . "." . self::TBL_INSURANCELOOKUP . " il ON o.insurance = il.insuranceId
                INNER JOIN 	" . self::DB_CSS . "." . self::TBL_USERS . " u ON il.userId = u.idUsers
            ";
        }

        $sql .= "
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_PREFERENCES . " p1 ON p1.`key` = 'POCTest' AND (p1.value = r.testId OR p1.value = r.panelId)
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_REMARKS . " re ON r.resultRemark = re.idremarks
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_EXTRANORMALS . " e ON t.extraNormals IS NOT NULL AND t.extraNormals = 1 AND t.idTests = e.test AND e.active = true AND (
                (e.type = 1 AND TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) BETWEEN e.ageLow AND e.ageHigh) OR
                (e.type = 2 AND ((e.sex IS NOT NULL AND p.sex IS NOT NULL AND e.sex = p.sex) OR (e.sex IS NULL AND p.sex != 'Male' AND p.sex != 'Female'))) OR
                (e.type = 3 AND TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) BETWEEN e.ageLow AND e.ageHigh AND ((e.sex IS NOT NULL AND p.sex IS NOT NULL AND e.sex = p.sex) OR e.sex IS NULL))
            )
            ";

        $where = "
            WHERE p.arNo = ?
                AND o.specimenDate <= ? ";

        $input = array ($arNo, $specimenDate, $user->idUsers);

        $strUserIds = $user->idUsers;
        if ($user->typeId == 2) {
            $multiUsers = $user->MultiUsers;
            if($user->hasUserSetting(4) && isset($multiUsers) && is_array($multiUsers) && count($multiUsers) > 0) {
                $strQuestionMarks = "?, ";
                foreach ($multiUsers as $userId) {
                    $input[] = $userId;
                    $strQuestionMarks .= "?, ";
                    $strUserIds .= "," . $userId;
                }
                $strQuestionMarks = substr($strQuestionMarks, 0, strlen($strQuestionMarks) - 2);
                $where .= "AND u.idUsers IN ($strQuestionMarks) ";
            } else {
                $where .= "AND u.idUsers = ? ";
            }
        } else {
            $where .= "AND u.idUsers = ? ";
        }

        $this->UserIds = $strUserIds;

        /*$where .= "AND c.idClients = ? ";
        $input[] = $clientId;

        if (!empty($doctorId)) {
            $where .= "AND d.iddoctors = ?";
            $input[] = $doctorId;
        }*/

        $where .= "
            AND (p1.`key` IS NULL OR p1.`key` != 'POCTest')
            AND t.testType != 0
            AND r.isInvalidated = 0
            AND t.resultType != 'Billing'
            AND (r.resultText IS NULL OR (r.resultText != 'DELETED' AND r.resultText != 'null'))
             ";

        $sql .= $where . "
        ORDER BY o.idOrders ASC, t.printedName ASC";

        //echo "<pre>$sql</pre><pre>"; print_r($input); echo "</pre>";
        $data = parent::select($sql, $input, array("Conn" => $this->Conn));

        if (count($data) > 0) {
            $cumulative = new Cumulative();
            $currResultOrder = new ResultOrder($data[0]); // set up the first ResultOrder
            $currResultOrder->Patient->dob = $data[0]['dob'];
            $idOrders = $data[0]['idOrders'];
            $settings = array("IncludeTest" => true);
            $idPatients = $data[0]['idPatients'];
            foreach ($data as $row) {

                $currOrderId = $row['idOrders'];
                if ($currOrderId != $idOrders) {
                    $cumulative->addResultOrder($currResultOrder); // add the previous result order to the list

                    $idOrders = $currOrderId; // set the new order id

                    $currResultOrder = new ResultOrder($row); // set up the new result order
                    $currResultOrder->Patient->dob = $row['dob'];
                }

                $currResultOrder->addResult($row, $settings); // add result data to the current result order
            }
            $cumulative->addResultOrder($currResultOrder); // add the final result order to the Cumulative object
            //echo "<pre>"; print_r($cumulative); echo "</pre>"; echo $sql;

            if ($patientPortal) {
                $sql = "INSERT INTO " . self::TBL_PATIENTLOG . " (patientId, typeId) VALUES (?, ?)";
                parent::manipulate($sql, array($_SESSION['id'], 4));
            } else {
                //ResultLogDAO::addLogEntry($_SESSION['id'], 4);
                ResultLogDAO::addCumulativeLogEntry($_SESSION['id'], $idPatients, $ip, array("Conn" => $this->Conn));
            }


            return $cumulative;
        }

        return false;
    }
}
?>
