<?php
if (!isset($_SESSION)) {
    session_start();
}
require_once "DataObject.php";
require_once "DOS/ResultLog.php";

/**
 * Description of ResultLogDAO
 *
 * @author Edd
 */
class ResultLogDAO extends DataObject {

    // $other is an array
    // $other is an array
    public static function addLogEntry ($userId, $typeId, array $otherFields = null) {
        $conn = null;
        if ($otherFields != null && array_key_exists("Conn", $otherFields) && $otherFields['Conn'] instanceof mysqli) {
            $conn = $otherFields['Conn'];
        }

        if ($otherFields != null && array_key_exists("Ip", $otherFields) && $otherFields['Ip'] != null) {

            $ip = $otherFields['Ip'];
            if (!is_numeric($ip)) {
                $ip = ip2long($ip);
            }

            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_LOG . " (userId, typeId, ip) VALUES (?, ?, ?)";
            $logId = parent::manipulate($sql, array ($userId, $typeId, $ip), array("LastInsertId" => true, "Conn" => $conn));
        } else {
            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_LOG . " (userId, typeId) VALUES (?, ?)";
            $logId = parent::manipulate($sql, array ($userId, $typeId), array("LastInsertId" => true, "Conn" => $conn));
        }

        return $logId;
    }

    public static function addNotificationLogEntry($userId, $typeId, $notificationId, array $otherFields = null) {
        $conn = null;
        if ($otherFields != null && array_key_exists("Conn", $otherFields) && $otherFields['Conn'] instanceof mysqli) {
            $conn = $otherFields['Conn'];
        }

        $logId = self::addLogEntry($userId, $typeId, $otherFields);

        $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_LOGNOTIFICATIONS . " (logId, notificationId) VALUES (?, ?)";
        parent::manipulate($sql, array($logId, $notificationId), array("Conn" => $conn));
    }

    public static function addApiLogEntry($orderId) {
        $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_APILOG . " (orderId) VALUES (?)";
        parent::manipulate($sql, array($orderId));
    }

    public static function addEmailLogEntry(mysqli $conn) {
        $sql = "
            INSERT INTO " . self::DB_CSS . "." . self::TBL_EMAILLOG . " (TotalEmailsSent, TotalNewOrders)
            VALUES (?, ?)";
        return parent::manipulate($sql, array(0, 0), array("LastInsertId" => true, "Conn" => $conn));
    }
    
    public static function addEmailUserLogEntry(array $data, mysqli $conn) {
        $sql = "
            INSERT INTO " . self::DB_CSS . "." . self::TBL_EMAILUSERLOG . " (userId, emailLogId, TotalNewOrders)
            VALUES (?, ?, ?)";
        parent::manipulate($sql, $data, array("Conn" => $conn));
    }

    public static function updateEmailLogEntry(array $data, mysqli $conn) {
        $sql = "
            UPDATE " . self::DB_CSS . "." . self::TBL_EMAILLOG . "
            SET TotalEmailsSent = ?, TotalNewOrders = ?
            WHERE idEmailLogs = ?";
        parent::manipulate($sql, $data, array("Conn" => $conn));
    }


    public static function addESignatureLogEntry(array $data, array $settings = null) {
        $conn = null;
        if ($settings != null && array_key_exists("Conn", $settings) && $settings['Conn'] instanceof mysqli) {
            $conn = $settings['Conn'];
        }

        $logId = self::addLogEntry(
            $data['userId'],
            16,
            array("Ip" => $data['ip'], "Conn" => $conn)
        );
        
        $prevSignatureTypeId = null;
        $prevDecodedSignature = null;
        $prevDecodedInitials = null;
        $doctorId = null;
        $prevAssignTypeId = null;
        $assignTypeId = null;
        $prevDoctorSignatureTypeId = null;
        if (isset($data['prevSignatureTypeId'])) {
            $prevSignatureTypeId = $data['prevSignatureTypeId'];
        }
        if (isset($data['prevDecodedSignature'])) {
            $prevDecodedSignature = $data['prevDecodedSignature'];
        }
        if (isset($data['prevDecodedInitials'])) {
            $prevDecodedInitials = $data['prevDecodedInitials'];
        }
        if (isset($data['doctorId'])) {
            $doctorId = $data['doctorId'];
        }
        if (isset($data['prevAssignTypeId'])) {
            $prevAssignTypeId = $data['prevAssignTypeId'];
        }
        if (isset($data['assignTypeId'])) {
            $assignTypeId = $data['assignTypeId'];
        }
        if (isset($data['prevDoctorSignatureTypeId'])) {
            $prevDoctorSignatureTypeId = $data['prevDoctorSignatureTypeId'];
        }


        $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_LOGESIGNATURES . " (logId, prevSignatureTypeId, prevAssignTypeId, prevDecodedSignature, prevDecodedInitials) VALUES (?, ?, ?, ?, ?);";
        if ($assignTypeId == 1) {
            parent::manipulate($sql, array($logId, $prevSignatureTypeId, $prevAssignTypeId, $prevDecodedSignature, $prevDecodedInitials), array("Conn" => $conn));
        } else {
            $idESignatureLogs = parent::manipulate($sql, array($logId, $prevSignatureTypeId, $prevAssignTypeId, null, null), array("Conn" => $conn, "LastInsertId" => true));

            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_LOGDOCTORESIGS . " (eSigLogId, doctorId, prevSignatureTypeId, prevDecodedSignature, prevDecodedInitials) VALUES (?, ?, ?, ?, ?)";
            parent::manipulate($sql, array($idESignatureLogs, $doctorId, $prevDoctorSignatureTypeId, $prevDecodedSignature, $prevDecodedInitials), array("Conn" => $conn));

        }


    }

    public static function addSalesLogEntry(array $data, array $settings = null) {
        $conn = null;
        if ($settings != null && array_key_exists("Conn", $settings) && $settings['Conn'] instanceof mysqli) {
            $conn = $settings['Conn'];
        }
        $logId = self::addLogEntry(
            $data['userId'],
            $data['typeId'],
            array("Ip" => $data['ip'], "Conn" => $conn)
        );

        $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_SALESLOG . " (logId, action) VALUES (?, ?);";
        $aryInput = array ($logId, $data['action']);

        $salesLogId = parent::manipulate($sql, $aryInput, array("LastInsertId" => true, "Conn" => $settings['Conn']));

        if ($data['action'] != 1) { // additional logging for sales goal setting add/edit/delete

            if (array_key_exists("goalId", $data)) {

                $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_SALESGOALLOG . " (salesLogId, salesGoalId, goal, typeId, intervalId) VALUES (?, ?, ?, ?, ?);";
                $aryInput = array($salesLogId, $data['goalId'], $data['goal'], $data['goalTypeId'], $data['intervalId']);
                parent::manipulate($sql, $aryInput, array("Conn" => $settings['Conn']));
            } else {
                $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_SALESGOALLOG . " (salesLogId, goal, typeId, intervalId) VALUES (?, ?, ?, ?);";
                $aryInput = array($salesLogId, $data['goal'], $data['goalTypeId'], $data['intervalId']);
                parent::manipulate($sql, $aryInput, array("Conn" => $settings['Conn']));
            }

        }
    }


    public static function addResultViewLogEntry($userId, $ip, array $orderIds, mysqli $conn) {
        $logId = self::addLogEntry($userId, 3, array("Conn" => $conn, "Ip" => $ip));

        $sql = "INSERT INTO " . self::TBL_LOGVIEWS . " (logId, orderId) VALUES ";
        $qryInput = array();

        foreach ($orderIds as $orderId) {
            $sql .= "(?, ?), ";
            $qryInput[] = $logId;
            $qryInput[] = $orderId;
        }
        $sql = substr($sql, 0, strlen($sql) - 2);

        parent::manipulate($sql, $qryInput, array("Conn" => $conn));


    }

    public static function addCumulativeLogEntry($userId, $patientId, $ip, array $settings = null) {
        $conn = null;
        if ($settings != null && array_key_exists("Conn", $settings) && $settings['Conn'] instanceof mysqli) {
            $conn = $settings['Conn'];
        }

        if ($ip != null && !empty($ip)) {
            $logId = self::addLogEntry($userId, 4, array("Conn" => $conn, "Ip" => $ip));
        } else {
            $logId = self::addLogEntry($userId, 4, array("Conn" => $conn));
        }

        $sql = "INSERT INTO " . self::TBL_LOGCUMULATIVE . " (logId, patientId) VALUES (?, ?)";
        parent::manipulate($sql, array($logId, $patientId), array("Conn" => $conn));
    }

    // ---------------------------------------- Admin Log Entry
    public static function addAdminLogEntry($userId, array $otherFields) {
        $conn = null;
        $ip = null;
        if ($otherFields != null) {
            if (array_key_exists("Conn", $otherFields) && $otherFields['Conn'] instanceof mysqli) {
                $conn = $otherFields['Conn'];
            }
            if (array_key_exists("Ip", $otherFields)) {
                $ip = $otherFields['Ip'];
            }
        }

        $logId = self::addLogEntry($userId, 7, array("Conn" => $conn, "Ip" => $ip));
        if (array_key_exists("adminUserId", $otherFields) && array_key_exists("userTypeId", $otherFields) && array_key_exists("email", $otherFields) && array_key_exists("action", $otherFields)) {
            $sql = "
                INSERT INTO " . self::TBL_ADMINLOGS . " (logId, adminUserId, userTypeId, email, action)
                VALUES (?, ?, ?, ?, ?)";
            $qryInput = array (
                $logId,
                $otherFields['adminUserId'],
                $otherFields['userTypeId'],
                $otherFields['email'],
                $otherFields['action']
            );
            $idAdminLogs = parent::manipulate($sql, $qryInput, array("LastInsertId" => true, "Conn" => $conn));
            return true;
        }
        return false;
    }

    public static function addReqViewLogEntry(array $input, array $settings = null) {
        $conn = null;
        if ($settings != null) {
            if (array_key_exists("Conn", $settings) && $settings['Conn'] instanceof mysqli) {
                $conn = $settings['Conn'];
            }
        }

        $logId = self::addLogEntry($input['idUsers'], 6, array("Conn" => $conn, "Ip" => $input['Ip']));

        $sql = "
            INSERT INTO " . self::TBL_LOGORDERENTRY . " (
                logId, orderEntryLogType, orderId, advancedOrderId,
                accession, advancedOrderOnly, isNewPatient, isNewSubscriber,
                subscriberChanged
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $aryInput = array();
        $aryInput[] = $logId;
        $aryInput[] = $input['orderEntryLogType'];
        $aryInput[] = $input['orderId'];
        $aryInput[] = $input['advancedOrderId'];
        $aryInput[] = $input['accession'];
        $aryInput[] = $input['advancedOrderOnly'];
        $aryInput[] = $input['isNewPatient'];
        $aryInput[] = $input['isNewSubscriber'];
        $aryInput[] = $input['subscriberChanged'];
        $idOrderEntryLogs = parent::manipulate($sql, $aryInput, array("LastInsertId" => true));
    }

    public static function orderInvalidatedLogEntry(array $input, array $settings = null) {
        $orderStatus = 2;
        $orderId = null;
        $adminUserId = null; // the userId of the administrator
        $userId = null; // the userId of the client/doctor
        $conn = null;
        $ip = null;
        $userTypeId = "";
        $email = "";
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
        if (array_key_exists("Ip", $input) && $input['Ip'] != null) {
            $ip = $input['Ip'];
        }
        if (array_key_exists("userTypeId", $input)) {
            $userTypeId = $input['userTypeId'];
        }
        if (array_key_exists("email", $input)) {
            $email = $input['email'];
        }

        if ($orderStatus == 1 || $orderStatus == 0) {
            $logId = self::addLogEntry($userId, 7, array("Conn" => $conn, "Ip" => $ip));

            $sql = "
                INSERT INTO " . self::DB_CSS . "." . self::TBL_ADMINLOGS . " (logId, adminUserId, userTypeId, email, action)
                VALUES (?, ?, ?, ?, ?)";

            $aryLogInput = array($logId, $adminUserId, $userTypeId, $email);

            if ($orderStatus == 1) { // Order invalidated
                $aryLogInput[] = 4;
            } else { // Entry order canceled
                $aryLogInput[] = 5;
            }

            $idAdminLogs = parent::manipulate($sql, $aryLogInput, array("LastInsertId" => true, "Conn" => $conn));

            if ($orderStatus == 1) {
                $sql = "
                INSERT INTO " . self::DB_CSS . "." . self::TBL_LOGINVALIDATEDORDERS . "(adminLogId, orderId)
                VALUES (?, ?)";
            } else {
                $sql = "
                INSERT INTO " . self::DB_CSS . "." . self::TBL_LOGCANCELLEDORDERS . "(adminLogId, orderId)
                VALUES (?, ?)";
            }
            parent::manipulate($sql, array($idAdminLogs, $orderId), array("Conn" => $conn));
        }
    }

    public static function getOrderLogData($idOrders, $orderType = null) {
        $selectFrom = " SELECT l.orderEntryLogType, l.orderId, l.advancedOrderId, l.advancedOrderOnly, l.isNewPatient, l.isNewSubscriber, l.subscriberChanged
                        FROM " . self::TBL_LOGORDERENTRY . " l ";


        if ($orderType == null || $orderType != 3) { // advanced & phlebotomy
            $where = " WHERE l.orderId = ? AND advancedOrderOnly = 0 ";
        } else { // advanced only
            $where = " WHERE l.advancedOrderId = ? AND advancedOrderOnly = 1 ";
        }

        $input = array($idOrders);

        $orderByLimit = " ORDER BY l.idOrderEntryLog DESC LIMIT 1";

        $sql = $selectFrom . $where . $orderByLimit;

        $data = parent::select($sql, $input);

//        if (count($data) == 0 && !$advancedOnly) {
//            // nothing was found, so the order must be advanced only (is stored by advancedOrderId in advancedOrders table only)
//            $where = " WHERE l.advancedOrderId = ? AND l.advancedOrderOnly = ? ";
//            $data = parent::select($selectFrom . $where . $orderByLimit, array($idOrders, true));
//            echo "<pre>"; print_r($data); echo "</pre>";
//            if (count($data) == 0) {
//                return null;
//            }
//        }

        if (count($data) > 0) {
            return $data;
        }
        return null;
    }




}

?>
