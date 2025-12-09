<?php
require_once 'DataObject.php';
require_once 'DOS/UserNotification.php';
require_once 'UserDAO.php';
require_once 'ResultLogDAO.php';
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 6/21/2017
 * Time: 10:23 AM
 */
class UserNotificationDAO extends DataObject {

    protected $Data = array(
        "userId" => "",
        "ip" => null,
        "idNotifications" => "",
        "notificationTitle" => "",
        "notificationText" => "",
        "dateFrom" => "",
        "dateTo" => null,
        "isActive" => "",
        "clientIds" => null,
        "doctorIds" => null
    );

    private $Conn;

    public function __construct(array $data = null) {
        parent::__construct($data);

        if ($this->Data['isActive'] == 0) {
            $this->Data['isActive'] = false;
        }

        $dateFrom = new DateTime($this->Data['dateFrom']);
        $this->Data['dateFrom'] = $dateFrom->format('Y-m-d H:i:s');
        if (isset($this->Data['dateTo']) && !empty($this->Data['dateTo'])) {
            $dateTo = new DateTime($this->Data['dateTo']);
            $this->Data['dateTo'] = $dateTo->format('Y-m-d H:i:s');
        }

        if ($data != null && array_key_exists("Conn", $data) && $data['Conn'] instanceof mysqli) {
            $this->Conn = $data['Conn'];
        } else {
            $this->Conn = parent::connect();
        }

        if (isset($this->Data['notificationTitle']) && !empty($this->Data['notificationTitle'])) {
            $this->Data['notificationTitle'] = html_entity_decode($this->Data['notificationTitle']);
        }

        if (isset($this->Data['notificationText']) && !empty($this->Data['notificationText'])) {
            //$this->Data['notificationText'] = strip_tags($this->Data['notificationText'], '<h6><h5><h4><h3><h2><h1><p><strong><em><span><sup><blockquote><sub><code><pre><ul><li><ol><a><img><br>');
            //$this->Data['notificationText'] = str_replace("&nbsp;", " ", strip_tags($this->Data['notificationText'], '<h6><h5><h4><h3><h2><h1><p><strong><em><span><sup><blockquote><sub><code><pre><ul><li><ol><a><img><br>'));
            $this->Data['notificationText'] = html_entity_decode(strip_tags($this->Data['notificationText'], '<h6><h5><h4><h3><h2><h1><p><strong><em><span><sup><blockquote><sub><code><pre><ul><li><ol><a><img><br>'));
        }
    }

    // https://stackoverflow.com/a/20103331
    // https://stackoverflow.com/a/2109602
    public function RemoveBS($Str, $convertHtml = true) {
        $StrArr = str_split($Str); $NewStr = '';
        foreach ($StrArr as $Char) {
            $CharNo = ord($Char);
            if ($CharNo == 163) { $NewStr .= $Char; continue; } // keep £
            if ($CharNo > 31 && $CharNo < 127) {
                $NewStr .= $Char;
            }
        }
        if ($convertHtml) {
            $NewStr = htmlspecialchars($NewStr, ENT_QUOTES);
        }
        return $NewStr;
    }

    public function getNotifications(array $tmpAryInput = null) {

        $aryInput = null;
        if ($tmpAryInput != null) {

            $aryInput = array();
            $sql = "
            SELECT n.idNotifications, n.notificationTitle, n.notificationText, n.dateFrom, n.dateTo, n.isActive, ln.idNotificationLog, un.userId
            FROM " . self::DB_CSS . "." . self::TBL_USERNOTIFICATIONS . " n
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_USERNOTIFICATIONLOOKUP . " un ON n.idNotifications = un.notificationId
            LEFT JOIN (
                SELECT DISTINCT ln.idNotificationLog, ln.notificationId, l.userId
                FROM " . self::DB_CSS . "." . self::TBL_LOGNOTIFICATIONS . " ln
                INNER JOIN " . self::DB_CSS . "." . self::TBL_LOG . " l ON ln.logId = l.idLogs AND l.typeId = 19
            ) ln ON n.idNotifications = ln.notificationId AND ln.userId = ? ";
            $aryInput[] = $tmpAryInput['userId'];

            if (array_key_exists("isActive", $tmpAryInput)) {
                $sql .= "WHERE n.isActive = ? ";
                $aryInput[] = $tmpAryInput['isActive'];
            }
            if (array_key_exists("notificationTypeId", $tmpAryInput)) {
                if (empty($aryInput)) {
                    $sql .= "WHERE n.notificationTypeId = ? ";
                } else {
                    $sql .= "AND n.notificationTypeId = ? ";
                }
                $aryInput[] = $tmpAryInput['notificationTypeId'];
            }
            if (array_key_exists("dateFrom", $tmpAryInput)) {
                if (empty($aryInput)) {
                    $sql .= "WHERE n.dateFrom <= ? ";
                } else {
                    $sql .= "AND n.dateFrom <= ? ";
                }
                $aryInput[] = $tmpAryInput['dateFrom'];
            }
            if (array_key_exists("dateTo", $tmpAryInput)) {
                if (empty($aryInput)) {
                    $sql .= "WHERE (n.dateTo IS NULL OR n.dateTo >= ?) ";
                } else {
                    $sql .= "AND (n.dateTo IS NULL OR n.dateTo >= ?) ";
                }
                $aryInput[] = $tmpAryInput['dateTo'];
            }
            if (array_key_exists("userId", $tmpAryInput)) {
                if (empty($aryInput)) {
                    $sql .= "WHERE (un.idNotificationLookup IS NULL OR un.userId = ?) ";
                } else {
                    $sql .= "AND (un.idNotificationLookup IS NULL OR un.userId = ?) ";
                }
                $aryInput[] = $tmpAryInput['userId'];
            }
        } else {
            $sql = "SELECT n.idNotifications, n.notificationTitle, n.notificationText, n.dateFrom, n.dateTo, n.isActive, NULL AS `idNotificationLog`, NULL AS `userId`
            FROM " . self::DB_CSS . "." . self::TBL_USERNOTIFICATIONS . " n ";
        }

        $sql .= "GROUP BY n.idNotifications
        ORDER BY n.isActive DESC, n.idNotifications DESC";

        /*error_log($sql);
        error_log(implode($aryInput, ","));*/

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $aryNotifications = array();
        if (count($data) > 0) {
            foreach ($data as $row) {
                $aryNotifications[] = new UserNotification(
                    array(
                        "idNotifications" => $row['idNotifications'],
                        "notificationTitle" => $this->RemoveBS($row['notificationTitle']),
                        "notificationText" => $this->RemoveBS($row['notificationText']),
                        "dateFrom" => $row['dateFrom'],
                        "dateTo" => $row['dateTo'],
                        "isActive" => $row['isActive'],
                        "idNotificationLog" => $row['idNotificationLog'],
                        "userId" => $row['userId']
                    )
                );
            }
        }

        return $aryNotifications;
    }

    public function getNotification($idNotifications) {
        $sql = "
            SELECT  n.idNotifications, n.notificationTypeId, n.notificationTitle, n.notificationText, n.dateFrom, n.dateTo, n.isActive,
                    u.idUsers, u.typeId, cl.clientId, dl.doctorId
            FROM " . self::DB_CSS . "." . self::TBL_USERNOTIFICATIONS . " n
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_USERNOTIFICATIONLOOKUP . " nl ON n.idNotifications = nl.notificationId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_USERS . " u ON nl.userId = u.idUsers AND u.isActive = 1
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON u.idUsers = cl.userId AND u.typeId = 2
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON u.idUsers = dl.userId AND u.typeId = 3
            WHERE   n.idNotifications = ?";

        $data = parent::select($sql, array($idNotifications), array("Conn" => $this->Conn));

        if (count($data) > 0) {
            $notification = new UserNotification(array(
                "idNotifications" => $data[0]['idNotifications'],
                "notificationTypeId" => $data[0]['notificationTypeId'],
                "notificationTitle" => $data[0]['notificationTitle'],
                "notificationText" => $data[0]['notificationText'],
                "dateFrom" => $data[0]['dateFrom'],
                "dateTo" => $data[0]['dateTo'],
                "isActive" => $data[0]['isActive'],
                "idUsers" => $data[0]['idUsers'],
                "typeId" => $data[0]['typeId'],
                "clientId" => $data[0]['clientId'],
                "doctorId" => $data[0]['doctorId']
            ));

            foreach ($data as $row) {
                if ($row['clientId'] != null && !in_array($row['clientId'], $notification->ClientIds)) {
                    $notification->ClientIds[] = $row['clientId'];
                } else if ($row['doctorId'] != null && !in_array($row['doctorId'], $notification->DoctorIds)) {
                    $notification->DoctorIds[] = $row['doctorIds'];
                }
            }
            return $notification;
        }
        return null;
    }

    public function addNotification() {
        $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_USERNOTIFICATIONS . " (notificationTypeId, notificationTitle, notificationText, dateFrom, dateTo) VALUES (?, ?, ?, ?, ?)";
        $idNotifications = parent::manipulate($sql, array(
            4,
            $this->RemoveBS($this->Data['notificationTitle'], false),
            $this->RemoveBS($this->Data['notificationText'], false),
            $this->Data['dateFrom'], $this->Data['dateTo']), array("Conn" => $this->Conn, "LastInsertId" => true));
        $this->Data['idNotifications'] = $idNotifications;

        $this->updateNotificationLookups();

        ResultLogDAO::addAdminLogEntry($this->Data['userId'], array(
            "Conn" => $this->Conn,
            "Ip" => $this->Data['ip'],
            "adminUserId" => $this->Data['userId'],
            "userTypeId" => null,
            "email" => null,
            "action" => 8
        ));

        //$this->addXmlNotification();
    }

    public function updateNotification() {
        $isActive = true;

        $sql = "
            UPDATE " . self::DB_CSS . "." . self::TBL_USERNOTIFICATIONS . "
            SET notificationTitle = ?, notificationText = ?, dateFrom = ?, dateTo = ?, isActive = ?
            WHERE idNotifications = ?";
        parent::manipulate($sql, array(
            $this->RemoveBS($this->Data['notificationTitle'], false),
            $this->RemoveBS($this->Data['notificationText'], false),
            $this->Data['dateFrom'], $this->Data['dateTo'], $isActive, $this->Data['idNotifications']), array("Conn" => $this->Conn));

        $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_USERNOTIFICATIONLOOKUP . " WHERE notificationId = ?";
        parent::manipulate($sql, array($this->Data['idNotifications']), array("Conn" => $this->Conn));

        $this->updateNotificationLookups();

        /*$updated = $this->updateXmlNotification();

        if (!$updated) {
            $this->addXmlNotification();
        }*/
    }

    private function updateNotificationLookups() {
        $aryInput = array();
        $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_USERNOTIFICATIONLOOKUP . " (userId, notificationId) VALUES ";
        if (is_array($this->Data['clientIds']) && count($this->Data['clientIds']) > 0) {
            foreach ($this->Data['clientIds'] as $clientId) {
                $aryCurrUserIds = UserDAO::getUserIdsByClientId($clientId, $this->Conn);

                if (count($aryCurrUserIds) > 0) {
                    foreach ($aryCurrUserIds as $userId) {
                        $aryInput[] = $userId;
                        $aryInput[] = $this->Data['idNotifications'];
                        $sql .= "(?, ?), ";
                    }
                }
            }
        }

        if (is_array($this->Data['doctorIds']) && count($this->Data['doctorIds']) > 0) {
            foreach ($this->Data['doctorIds'] as $doctorId) {
                $aryCurrUserIds = UserDAO::getUserIdsByDoctorId($doctorId, $this->Conn);

                if (count($aryCurrUserIds) > 0) {
                    foreach ($aryCurrUserIds as $userId) {
                        $aryInput[] = $userId;
                        $aryInput[] = $this->Data['idNotifications'];
                        $sql .= "(?, ?), ";
                    }
                }
            }
        }

        if (count($aryInput) > 0) {
            $sql = substr($sql, 0, strlen($sql) - 2);
            parent::manipulate($sql, $aryInput, array("Conn" => $this->Conn));
        }
    }

    public function toggleNotification() {
        $sql = "
            UPDATE " . self::DB_CSS . "." . self::TBL_USERNOTIFICATIONS . "
            SET isActive = ?
            WHERE idNotifications = ?";
        parent::manipulate($sql, array($this->Data['isActive'], $this->Data['idNotifications']), array("Conn" => $this->Conn));

        /*if ($this->Data['isActive'] == false) {
            $this->deleteXmlNotification();
        } else {
            $notification = $this->getNotification($this->Data['idNotifications']);
            $this->Data['notificationTitle'] = $notification->notificationTitle;
            $this->Data['notificationText'] = $notification->notificationText;
            $this->Data['dateFrom'] = $notification->dateFrom;
            $this->Data['dateTo'] = $notification->dateTo;
            $this->addXmlNotification();
        }*/
    }

    private function addXmlNotification() {
        if (file_exists('../../notifications.xml')) {
            $doc = simplexml_load_file('../../notifications.xml');

            $newNotification = $doc->addChild('notification');
            $newNotification->addAttribute('id', $this->Data['idNotifications']);
            $newNotification->addChild('title', $this->Data['notificationTitle']);
            $newNotification->addChild('text', $this->Data['notificationText']);
            $newNotification->addChild('dateFrom', $this->Data['dateFrom']);
            $newNotification->addChild('dateTo', $this->Data['dateTo']);

            $doc->asXML('../../notifications.xml');
        }
    }

    private function updateXmlNotification() {
        if (file_exists('../../notifications.xml')) {
            $doc = simplexml_load_file('../../notifications.xml');

            foreach($doc as $notification) {
                if ($notification['id'] == $this->Data['idNotifications']) {
                    $notification->title = $this->Data['notificationTitle'];
                    $notification->text = $this->Data['notificationText'];
                    $notification->dateFrom = $this->Data['dateFrom'];
                    $notification->dateTo = $this->Data['dateTo'];
                    $doc->asXML('../../notifications.xml');
                    return true;
                }
            }
        }
        return false;
    }

    private function deleteXmlNotification() {
        if (file_exists('../../notifications.xml')) {
            $doc = simplexml_load_file('../../notifications.xml');

            foreach ($doc->notification as $notification) {
                if ($notification['id'] == $this->Data['idNotifications']) {
                    $dom = dom_import_simplexml($notification);
                    $dom->parentNode->removeChild($dom);
                    $doc->asXml('../../notifications.xml');
                    return true;
                }
            }
        }
        return false;
    }
}