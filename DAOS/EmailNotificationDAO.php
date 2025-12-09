<?php

/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 5/19/2016
 * Time: 10:42 AM
 */
require_once 'DataObject.php';
require_once 'ResultLogDAO.php';

class EmailNotificationDAO extends DataObject {

    private $Conn;

    private $LogId;

    public function __construct(array $data = null) {

        if ($data != null && array_key_exists("Conn", $data) && $data['Conn'] instanceof mysqli) {
            $this->Conn = $data['Conn'];
        } else {
            $this->Conn = parent::connect();
        }
    }

    private function getStartDate($lastDateWithNewOrders = false) {

        $where = "";
        if ($lastDateWithNewOrders == true) {
            $where = "WHERE el.TotalNewOrders > 0";
        }

        $sql = "
            SELECT el.idEmailLogs, el.TotalEmailsSent, el.TotalNewOrders, el.logDate
            FROM " . self::DB_CSS . "." . self::TBL_EMAILLOG . " el
            $where
            ORDER BY el.logDate DESC
            LIMIT 1
        ";
        $data = parent::select($sql, null, array("Conn" => $this->Conn));

        if (count($data) == 0) { // this is the first time the script has run, so get the date of yesterday
            $dateData = parent::select("SELECT DATE_SUB(NOW(), INTERVAL 1 DAY) AS `StartDate`");
            //$dateData = parent::select("SELECT DATE_SUB(NOW(), INTERVAL 2 YEAR) AS `StartDate`"); // dev only
            if (count($dateData) == 0) {
                return date("Y-m-d H:i:s", strtotime("-1 day")); // for whatever reason, the query returned zero rows, so just get PHP to generate the date
            } else {
                return $dateData[0]['StartDate'];
            }
        }

        return $data[0]['logDate'];

        //return '2015-02-28 00:00:00';
    }

    public function addEmailUserLogEntry(array $data) {

        $aryData = array(
            $data['idUsers'],
            $this->LogId,
            $data['TotalNewOrders']
        );

        ResultLogDAO::addEmailUserLogEntry($aryData, $this->Conn);
    }

    public function updateEmailLogEntry($totalEmailsSent, $totalNewOrders) {

        $aryData = array($totalEmailsSent,$totalNewOrders,$this->LogId);

        ResultLogDAO::updateEmailLogEntry($aryData, $this->Conn);
    }

    public function getNewOrders() {
        // select the last insert row to get the date to search for orders
        $dateFrom = self::getStartDate(true);

        // insert row into log table for current round of email notifications
        $this->LogId = ResultLogDAO::addEmailLogEntry($this->Conn);

        $countField = "approvedCount";
        $completedField = "approvedDate";
        $maxDate = "MaxApprovedDate";
        if (self::RequireCompleted == true) {
            $countField = "completedCount";
            $completedField = "pAndTDate";
            $maxDate = "MaxTransmitDate";
        }

        $sql = "
            SELECT 	o.idOrders, o.accession, 
                    u.idUsers, u.email, u.typeId,
                    cl.userId, dl.userId,                    
                    c.idClients, c.clientNo, c.clientName, 
                    d.iddoctors, d.number AS `doctorNo`, d.lastName, d.firstName,                    
                    COUNT(CASE WHEN r.isInvalidated = false THEN o.idOrders ELSE NULL END) AS `orderCount`,
                    SUM(CASE WHEN r.isInvalidated = false THEN r.isApproved ELSE 0 END) AS `approvedCount`,
                    SUM(CASE WHEN r.isInvalidated = false THEN r.printAndTransmitted ELSE 0 END) AS `completedCount`,                    
                    MAX(r.approvedDate) AS `MaxApprovedDate`,
                    MAX(r.pAndTDate) AS `MaxTransmitDate`,                    
                    c.transType, c.resPrint,
                    o.active,
                    o.orderDate, o.specimenDate                    
            FROM " . self::DB_CSS . "." . self::TBL_USERS . " u
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON u.idUsers = cl.userId AND u.typeId = 2
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON u.idUsers = dl.userId AND u.typeId = 3
            INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON cl.clientId = o.clientId OR dl.doctorId = o.doctorId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors
            INNER JOIN  " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
            INNER JOIN  " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_PREFERENCES . " p ON p.key = 'POCTest' AND (r.testId = p.value OR r.panelId = p.value)
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DEACTIVATEDEMAILUSERS . " eu ON u.idUsers = eu.userId
            WHERE   u.isActive = true
                    AND eu.idDeactivated IS NULL
                    AND p.key IS NULL
                    AND r.isInvalidated = false
                    AND o.active = true
                    AND (r.$completedField IS NULL OR r.$completedField >= ?)
            GROUP BY o.idOrders, u.idUsers
            HAVING $maxDate >= ? AND (
                (orderCount = $countField AND c.resPrint IS NOT NULL AND c.resPrint = 2)
                OR ($countField > 0 AND (c.resPrint IS NULL OR c.resPrint != 2))
            )
            ORDER BY u.idUsers ASC
        ";

        $data = parent::select($sql, array($dateFrom, $dateFrom), array("Conn" => $this->Conn));

        return $data;
    }

    public function getInactiveUserIds() {
        $sql = "
            SELECT eu.userId
            FROM " . self::DB_CSS . "." . self::TBL_DEACTIVATEDEMAILUSERS . " eu";
        $data = parent::select($sql, null, array("Conn" => $this->Conn));
        $aryUserIds = array();
        foreach($data as $row) {
            $aryUserIds[] = $row['userId'];
        }
        return $aryUserIds;
    }

    public function removeInactiveUserIds(array $userIds = null) {
        if ($userIds != null) {
            $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_DEACTIVATEDEMAILUSERS . " WHERE ";

            foreach($userIds as $userId) {
                $sql .= "userId = ? OR ";
            }
            $sql = substr($sql, 0, strlen($sql) - 3);

            parent::manipulate($sql, $userIds, array("Conn" => $this->Conn));
        } else {
            $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_DEACTIVATEDEMAILUSERS;
            parent::manipulate($sql, null, array("Conn" => $this->Conn));
        }
    }

    public function addInactiveUserIds(array $userIds) {
        $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_DEACTIVATEDEMAILUSERS . " (userId) VALUES ";
        foreach ($userIds as $userId) {
            $sql .= "(?), ";
        }
        $sql = substr($sql, 0, strlen($sql) - 2);

        parent::manipulate($sql, $userIds, array("Conn" => $this->Conn));
    }

}
