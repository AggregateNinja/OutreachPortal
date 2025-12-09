<?php
require_once 'DataObject.php';

class OrderDAO extends DataObject {
    public static function getOrderDateById($idOrders) {
        $sql = "SELECT orderDate FROM " . self::TBL_ORDERS . " WHERE idOrders = ?";
        $orderDate = parent::select($sql, array($idOrders));
        return $orderDate[0]['orderDate'];        
    }
    public static function getSpecimenDateById($idOrders, mysqli $conn = null) {
        $sql = "SELECT specimenDate FROM " . self::TBL_ORDERS . " WHERE idOrders = ?";

        if ($conn != null) {
            $specimenDate = parent::select($sql, array($idOrders), array("Conn" => $conn));
        } else {
            $specimenDate = parent::select($sql, array($idOrders));
        }

        return $specimenDate[0]['specimenDate'];
    }

    public static function getUserIdByOrderId(array $userIds, mysqli $conn, $idOrders) {
        $aryInput = array();

        $where = "WHERE ";
        for ($i = 0; $i < count($userIds); $i++) {

            if ($i == 0) {
                $where .= "(u.idUsers = ?";
            } else {
                $where .= " OR u.idUsers = ?";
            }
            $aryInput[] = $userIds[$i];
        }
        $where .= ") AND o.idOrders = ?";
        $aryInput[] = $idOrders;

        /*$sql = "
        SELECT 	u.idUsers, u.email, u.typeId,
                c.idClients, c.clientNo, c.clientName,
                d.iddoctors, d.number, d.lastName, d.firstName
        FROM " . self::DB_CSS . "." . self::TBL_USERS . " u
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON u.idUsers = cl.userId AND u.typeId = 2
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON u.idUsers = dl.userId AND u.typeId = 3
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON cl.clientId = c.idClients
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON dl.doctorId = d.iddoctors
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON c.idClients = o.clientId OR d.iddoctors = o.doctorId
        " . $where;*/

        $sql = "
        SELECT 	u.idUsers, u.email, u.typeId,
                c.idClients, c.clientNo, c.clientName,
                d.iddoctors, d.number, d.lastName, d.firstName
        FROM " . self::DB_CSS . "." . self::TBL_USERS . " u
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON u.idUsers = cl.userId AND u.typeId = 2
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON u.idUsers = dl.userId AND u.typeId = 3
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c2 ON cl.clientId = c2.idClients
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON c2.npi = c.npi
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d2 ON dl.doctorId = d2.iddoctors
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON d2.NPI = d.NPI
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON c.idClients = o.clientId OR d.iddoctors = o.doctorId
        " . $where;

        return $data = parent::select($sql, $aryInput, array("Conn" => $conn));
    }

    public static function getOrderCommentRemarks(mysqli $conn = null) {
        $sql = "
            SELECT re.idremarks, re.remarkNo, re.remarkName, re.remarkText, rt.id AS `idRemarkTypes`, rt.name AS `remarkTypeName`
            FROM " . self::DB_CSS . "." . self::TBL_REMARKS . " re
            INNER JOIN " . self::DB_CSS . "." . self::TBL_REMARKTYPES . " rt ON re.remarkType = rt.id
            WHERE rt.name = 'Order Comment'";

        if ($conn != null) {
            $data = parent::select($sql, null, array("Conn" => $conn));
        } else {
            $data = parent::select($sql);
        }

        return $data;
    }

    public static function getTotalBillableUnbillableRejected(array $input) {
        $sql = "
            SELECT      COUNT(DISTINCT CASE WHEN rej.orderId IS NOT NULL THEN o.idOrders ELSE NULL END) AS `TotalRejected`,
                    COUNT(DISTINCT CASE WHEN rej.orderId IS NULL AND o.billable = 1 THEN o.idOrders ELSE NULL END) AS `TotalBillable`,
                    COUNT(DISTINCT CASE WHEN rej.orderId IS NULL AND o.billable = 0 THEN o.idOrders ELSE NULL END) AS `TotalUnbillable`
            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            LEFT JOIN (
                SELECT DISTINCT r.orderId
                FROM " . self::DB_CSS . "." . self::TBL_RESULTS . " r
                INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests AND t.number = 490
            ) rej ON o.idOrders = rej.orderId
            WHERE o.orderDate BETWEEN ? AND ?";

        $dateFrom = $input['dateFrom'];
        $dateTo = $input['dateTo'];

        $aryInput = array($dateFrom, $dateTo);

        return parent::select($sql, $aryInput);
    }


}
?>