<?php
/**
 * Created by PhpStorm.
 * User: Ed Bossmeyer
 * Date: 9/26/14
 * Time: 10:19 AM
 */

require_once 'DataObject.php';
require_once 'DOS/ResultOrder.php';
require_once 'ResultLogDAO.php';


class PendingOrdersDAO extends DataObject {

    private $UserId;
    protected $Data = array();

    public function __construct($userId, array $data = null) {
        $this->UserId = $userId;
        if ($data != null) {
            $this->Data = $data;
        }
    }

    public function getPendingOrders() {
        $orderBy = "orderDate";
        $direction = "desc";
        $searchFields = null;
        $orderAccess = self::DefaultRestrictAll;
        $logUserTableClause = "";
        if ($this->Data != null) {
            if (array_key_exists("OrderBy", $this->Data)) {
                if ($this->Data['OrderBy'] == "accession" || $this->Data['OrderBy'] == "avalonAccession" || $this->Data['OrderBy'] == "patientFirstName" ||
                    $this->Data['OrderBy'] == "doctorFirstName" || $this->Data['OrderBy'] == "clientName" || $this->Data['OrderBy'] == "insuranceName" ||
                    $this->Data['OrderBy'] == "orderDate" || $this->Data['OrderBy'] == "specimenDate" || $this->Data['OrderBy'] == "receiptedDate") {

                    if ($this->Data['OrderBy'] == "accession") {
                        $orderBy = "CAST(o.accession AS UNSIGNED)";
                    } else if ($this->Data['OrderBy'] == "avalonAccession") {
                        $orderBy = "CAST(o2.accession AS UNSIGNED)";
                    } else {
                        $orderBy = $this->Data['OrderBy'];
                    }
                }
            }
            if (array_key_exists("Direction", $this->Data) && ($this->Data['Direction'] == "asc" || $this->Data['Direction'] == "desc")) {
                $direction = $this->Data['Direction'];
            }
            if (array_key_exists("SearchFields", $this->Data)) {
                $searchFields = $this->Data['SearchFields'];
            }

            if (array_key_exists("OrderAccess", $this->Data) && $this->Data['OrderAccess'] != null) {
                $orderAccess = $this->Data['OrderAccess'];
            }

            if ($orderAccess == 2) {
                // Restrict all
                $logUserTableClause = "AND l.userId = ?";
            } else if ($orderAccess == 3 && !empty($this->Data['RestrictedUserIds'])) {
                // Restrict some
                $logUserTableClause = "AND l.userId NOT IN (";
                foreach($this->Data['RestrictedUserIds'] as $restrictedUserId) {
                    $logUserTableClause .= "?,";
                }
                $logUserTableClause = substr($logUserTableClause, 0, strlen($logUserTableClause) - 1) . ")";
            } else if ($orderAccess == 4) {
                // Access some
                $logUserTableClause = "AND l.userId IN(?,";
                if (!empty($this->Data['RestrictedUserIds'])) {
                    foreach($this->Data['RestrictedUserIds'] as $restrictedUserId) {
                        $logUserTableClause .= "?,";
                    }
                }
                $logUserTableClause = substr($logUserTableClause, 0, strlen($logUserTableClause) - 1) . ")";

            }

        }

        $aryWhere = $this->getWhereClause();
        $whereClause = $aryWhere[0];
        $aryInput = $aryWhere[1];

        if ($this->Data['TypeId'] == 7) {
            $whereClause .= " AND l.userId = ? ";
            $aryInput[] = $this->UserId;
        }

        if ($orderAccess == 2) {
            array_unshift($aryInput, $this->UserId);
        } else if ($orderAccess == 3 && !empty($this->Data['RestrictedUserIds'])) {
            $aryInput = array_merge($this->Data['RestrictedUserIds'], $aryInput);
        } else if ($orderAccess == 4) {
            array_unshift($aryInput, $this->UserId);
            if (!empty($this->Data['RestrictedUserIds'])) {
                $aryInput = array_merge($this->Data['RestrictedUserIds'], $aryInput);
            }
        }

        $orderBy = $orderBy . " " .  $direction;

        /* http://stackoverflow.com/questions/11277185/select-previous-date-mysql/11277811#11277811 */
        $sql = "
            SELECT 	DISTINCT CASE WHEN o2.idOrders IS NULL THEN o.idOrders ELSE o2.idOrders END AS `idOrders`,
                    o2.accession,
                    o.idOrders AS `webOrderId`,
                    o.accession AS `webAccession`,
                    u.idUsers, u.email,
                    CASE WHEN o2.idOrders IS NULL THEN o.doctorId ELSE o2.doctorId END AS `doctorId`,
                    CASE WHEN o2.idOrders IS NULL THEN o.clientId ELSE o2.clientId END AS `clientId`,
                    CASE WHEN o2.idOrders IS NULL THEN o.locationId ELSE o2.locationId END AS `locationId`,
                    CASE WHEN o2.idOrders IS NULL THEN o.orderDate ELSE o2.orderDate END AS `orderDate`,
                    CASE WHEN o2.idOrders IS NULL THEN o.specimenDate ELSE o2.specimenDate END AS `specimenDate`,
                    CASE WHEN o2.idOrders IS NULL THEN o.patientId ELSE o2.patientId END AS `patientId`,
                    CASE WHEN o2.idOrders IS NULL THEN o.insurance ELSE o2.insurance END AS `insurance`,
                    CAST(CASE WHEN o2.idOrders IS NULL THEN o.isAdvancedOrder ELSE o2.isAdvancedOrder END AS UNSIGNED) AS `isAdvancedOrder`,
                    lo.isNewPatient, l.logDate, l.idLogs,
                    CASE
                        WHEN lo.isNewPatient = true AND o2.idOrders IS NULL THEN p2.idPatients
                        WHEN lo.isNewPatient = false AND o2.idOrders IS NULL THEN p.idPatients
                        ELSE p3.idPatients
                    END AS `idPatients`,
                    CASE
                        WHEN lo.isNewPatient = true AND o2.idOrders IS NULL THEN p2.firstName
                        WHEN lo.isNewPatient = false AND o2.idOrders IS NULL THEN p.firstName
                        ELSE p3.firstName
                    END AS `patientFirstName`,
                    CASE
                        WHEN lo.isNewPatient = true AND o2.idOrders IS NULL THEN p2.lastName
                        WHEN lo.isNewPatient = false AND o2.idOrders IS NULL THEN p.lastName
                        ELSE p3.lastName
                    END AS `patientLastName`,

                    CASE WHEN o2.idOrders IS NULL THEN c.idClients ELSE c2.idClients END AS `idClients`,
                    CASE WHEN o2.idOrders IS NULL THEN c.clientNo ELSE c2.clientNo END AS `clientNo`,
                    CASE WHEN o2.idOrders IS NULL THEN c.clientName ELSE c2.clientName END AS `clientName`,

                    CASE WHEN o2.idOrders IS NULL THEN i.idinsurances ELSE i2.idinsurances END AS `idinsurances`,
                    CASE WHEN o2.idOrders IS NULL THEN i.name ELSE i2.name END AS `insuranceName`,

                    CASE WHEN o2.idOrders IS NULL THEN d.idDoctors ELSE d2.idDoctors END AS `idDoctors`,
                    CASE WHEN o2.idOrders IS NULL THEN d.number ELSE d2.number END AS `doctorNo`,
                    CASE WHEN o2.idOrders IS NULL THEN d.firstName ELSE d2.firstName END AS `doctorFirstName`,
                    CASE WHEN o2.idOrders IS NULL THEN d.lastName ELSE d2.lastName END AS `doctorLastName`,

                    rl.receiptedDate,
                    #CASE WHEN o2.idOrders IS NOT NULL THEN 1 ELSE 0 END AS `IsReceipted`,
                    CASE WHEN rl.receiptedDate IS NOT NULL THEN 1 ELSE 0 END AS `IsReceipted`,
                    CASE WHEN rl.idOrders IS NOT NULL AND o2.idOrders IS NULL THEN true ELSE false END AS `MissingOrder`,

                    ob.userId AS `userIdEditingOrder`, ob.sessionId, ob.token, ob.editDate,
                    #CASE WHEN el.idorderEntryLog IS NOT NULL THEN true ELSE false END AS `WebOrderCanceled`
                    CASE WHEN o.active = false THEN true ELSE false END AS `WebOrderCanceled`
            FROM " . self::DB_CSS . "." . self::TBL_LOGORDERENTRY . " lo
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_LOGORDERENTRY . " lo2 ON lo.orderId = lo2.orderId AND lo2.orderEntryLogType != 3 AND lo.idOrderEntryLog < lo2.idOrderEntryLog
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_LOGORDERENTRY . " lo3 ON lo.orderId = lo3.orderId AND lo3.orderEntryLogType != 3 AND lo.idOrderEntryLog < lo3.idOrderEntryLog AND lo3.idOrderEntryLog < lo2.idOrderEntryLog
            INNER JOIN " . self::DB_CSS . "." . self::TBL_LOG . " l ON lo.logId = l.idLogs AND l.typeId = 6 $logUserTableClause
            INNER JOIN " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o ON lo.orderId = o.idOrders AND o.accession IS NOT NULL
            INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERRECEIPTLOG . " rl ON o.accession = rl.webAccession
            INNER JOIN " . self::DB_CSS . "." . self::TBL_USERS . " u ON l.userId = u.idUsers AND u.typeId != 1
        ";

        if ($this->Data['TypeId'] == 2) {
            // Client
            $sql .= "
                INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON u.idUsers = cl.userId
                INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " uc ON cl.clientId = uc.idClients
            ";
        } else if ($this->Data['TypeId'] == 3) {
            // Doctor
            $sql .= "
                INNER JOIN " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON u.idUsers = dl.userId
                INNER JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " ud ON dl.doctorId = ud.iddoctors
            ";
        }

        $sql .= "
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_ORDERBEINGEDITED . " ob ON o.idOrders = ob.orderId

            LEFT JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o2 ON rl.idOrders = o2.idOrders

            LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON (o.clientId = c.idClients AND o2.idOrders IS NULL)
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c2 on (o2.clientid = c2.idclients AND o2.idorders IS NOT NULL)

            LEFT JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i ON (o.insurance = i.idinsurances AND o2.idOrders IS NULL)
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i2 ON (o2.insurance = i2.idinsurances AND o2.idOrders IS NOT NULL)

            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON (o.doctorId = d.idDoctors AND o2.idOrders IS NULL)
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d2 ON (o2.doctorId = d2.iddoctors AND o2.idOrders IS NOT NULL)

            LEFT JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON (o.patientId = p.idPatients) AND (lo.isNewPatient = false OR o2.idOrders IS NOT NULL)
            LEFT JOIN " . self::DB_CSS_WEB . "." . self::TBL_PATIENTS . " p2 ON o.patientId = p2.idPatients AND lo.isNewPatient = true AND o2.idOrders IS NULL
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p3 ON o2.patientId = p3.idPatients AND o2.idOrders IS NOT NULL

            $whereClause
            ORDER BY $orderBy ";

        /*error_log($sql);
        error_log(implode(",", $aryInput));*/

        //echo "<pre>$sql</pre><pre>"; print_r($aryInput); echo "</pre>";

        $data = parent::select($sql, $aryInput, $this->Data);
        //echo "<pre>"; print_r($data); echo "</pre>";
        if (count($data) > 0) {
            $aryOrders = array();
            foreach ($data as $row) {



                $currOrder = new ResultOrder($row);
                $currOrder->isAdvancedOrder = $row['isAdvancedOrder'];
                /*if ($row['isAdvancedOrder'] == 1) {
                    $currOrder->isAdvancedOrder = true; // advanced order only
                }*/

                if ($row['idinsurances'] != null) {
                    $currOrder->setInsurance(array("idinsurances" => $row['idinsurances'], "name" => $row['insuranceName']));
                }

                if ($row['idDoctors'] != null) {
                    $currOrder->Doctor = $row;
                }

                if ($row['IsReceipted'] == 1) {
                    $currOrder->IsReceipted = true;
                }

                /*if ($row['idPhlebotomy'] != null) {
                    $currOrder->Phlebotomy = $row;

                    if ($row['isAdvancedOrder'] != null) {
                        $currOrder->isAdvancedOrder = true; // advanced order with phlebotomy
                    }
                }*/

                $currOrder->setUser(array("idUsers" => $row['idUsers'], "email" => $row['email']));

                $aryOrders[] = $currOrder;
            }

            return $aryOrders;
        }

        return null;
    }

    public static function clearOrdersBeingEdited($userId, $ip, mysqli $conn) {
        $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_ORDERBEINGEDITED . " WHERE userId = ? AND ip = ?";
        parent::manipulate($sql, array($userId, $ip), array("Conn" => $conn));
    }
    
    private function getWhereClause() {
        $where = "
            WHERE   lo2.idOrderEntryLog IS NULL
                    AND lo.orderEntryLogType != 3
                    AND u.typeId = ? ";
        $aryInput = array($this->Data['TypeId']);
        if (array_key_exists("ClientNo", $this->Data)) {
            $where .= "AND uc.clientNo = ?";
            $aryInput[] = $this->Data['ClientNo'];
        } else if (array_key_exists("DoctorNo", $this->Data)) {
            $where .= "AND ud.number = ?";
            $aryInput[] = $this->Data['DoctorNo'];
        } else if ($this->Data['TypeId'] == 4) {
            $where .= "AND u.idUsers = ?";
            $aryInput[] = $this->UserId;
        }

        if ($this->Data != null && array_key_exists("SearchFields", $this->Data)) {
            $searchFields = $this->Data['SearchFields'];
            foreach ($searchFields as $key => $value) {
                if (!empty($value)) {
                    switch ($key) {
                        case "accession":
                            $where .= " AND o.accession = ?";
                            $aryInput[] = $value;
                            break;
                        case "patientName":
                            $where .= " AND (p.firstName LIKE ? OR p.lastName LIKE ? OR p2.firstName LIKE ? OR p2.lastName LIKE ?)";
                            $aryInput[] = "%$value%";
                            $aryInput[] = "%$value%";
                            $aryInput[] = "%$value%";
                            $aryInput[] = "%$value%";
                            break;
                        case "doctorName":
                            $where .= " AND (d.firstName LIKE ? OR d.lastName LIKE ?)";
                            $aryInput[] = "%$value%";
                            $aryInput[] = "%$value%";
                            break;
                        case "clientName":
                            $where .= " AND c.clientName LIKE ?";
                            $aryInput[] = "%$value%";;
                            break;
                        case "insuranceName":
                            $where .= " AND i.name LIKE ?";
                            $aryInput[] = "%$value%";
                            break;
                    }
                }
            }

            if (!empty($searchFields['orderDateFrom']) && !empty($searchFields['orderDateTo'])) {
                $where .= " AND o.orderDate BETWEEN ? AND ?";
                $aryInput[] = date("Y-m-d", strtotime($searchFields['orderDateFrom'])) . " 00:00:00";
                $aryInput[] = date("Y-m-d", strtotime($searchFields['orderDateTo'])) . " 23:59:59";
            } else if (!empty($searchFields['orderDateFrom'])) {
                $where .= " AND o.orderDate >= ?";
                $aryInput[] = date("Y-m-d", strtotime($searchFields['orderDateFrom'])) . "00:00:00";
            } else if (!empty($searchFields['orderDateTo'])) {
                $where .= " AND o.orderDate <= ?";
                $aryInput[] = date("Y-m-d", strtotime($searchFields['orderDateTo'])) . " 23:59:59";
            }

            if (array_key_exists("displayReceived", $this->Data['SearchFields']) && $this->Data['SearchFields']['displayReceived'] == 0) {
                $where .= " AND rl.receiptedDate IS NULL";
            }
            if (array_key_exists("displayCanceled", $this->Data['SearchFields']) && $this->Data['SearchFields']['displayCanceled'] == 0) {
                $where .= " AND o.active = 1";
            }
        }
        return array($where, $aryInput);
    }
} 