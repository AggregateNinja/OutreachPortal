<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 1/8/2021
 * Time: 10:43 AM
 */
require_once 'DataObject.php';
require_once 'DOS/PatientSMSQueue.php';

class SMSQueueDAO extends DataObject {

    private $Conn;

    public function __construct(array $data = null) {
        parent::__construct($data);

        $this->Conn = parent::connect();
    }

    public function getSMSQueue() {
        $sql = "SELECT s.idSMSQueue, s.orderId, s.patientId, s.sent, s.dateCreated, s.messageTypeId,
            p.arNo, p.firstName, p.middleName, p.lastName, p.phone
            FROM " . self::DB_CSS . "." . self::TBL_SMSQUEUE . " s
            INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON s.patientId = p.idPatients
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SMSUNSUBSCRIBED . " su ON p.idPatients = su.patientId
            WHERE s.sent = false
                AND su.idUnsubscribed IS NULL";
        $data = parent::select($sql, null, array("Conn" => $this->Conn));

        $arySMSQueue = array();
        if (count($data) > 0) {
            foreach ($data as $row) {
                $arySMSQueue[] = new PatientSMSQueue($row);
            }
        }

        return $arySMSQueue;
    }

    public function updateSMSQueue($idSMSQueue, $sent) {
        $sql = "UPDATE " . self::DB_CSS . "." . self::TBL_SMSQUEUE . " SET sent = ? WHERE idSMSQueue = ?";
        parent::manipulate($sql, array($sent, $idSMSQueue), array("Conn" => $this->Conn));
    }

    public function deleteByQueueId($idSMSQueue) {
        $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_SMSQUEUE . " WHERE idSMSQueue = ?";
        parent::manipulate($sql, array($idSMSQueue), array("Conn" => $this->Conn));
    }

    public function addLogEntry(array $logData) {
        /*$aryFields = array(
            "smsQueueId" => '',
            "logTypeId" => '',
            "orderId" => '',
            "userId" => '',
            "patientId" => '',
            "patientArNo" => '',
            "patientLastName" => '',
            "patientFirstName" => '',
            "patientMiddleName" => '',
            "patientPhone" => ''
        );*/

        $strFields = "";
        $strValues = "";
        $aryInput = array();
        foreach ($logData as $key => $val) {
            $strFields .= $key . ",";
            $strValues .= "?,";
            $aryInput[] = $val;
        }
        $strFields = substr($strFields, 0, strlen($strFields) - 1);
        $strValues = substr($strValues, 0, strlen($strValues) - 1);

        $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_SMSLOG . " (" . $strFields . ") VALUES (" . $strValues . ")";

        parent::manipulate($sql, $aryInput, array('Conn' => $this->Conn));
    }

    public function addUnsubscribed($phoneNumber) {
        // first try to get the patientId using the phone number that sent the incoming message
        if (strlen($phoneNumber) == 12) {
            $phone = substr($phoneNumber, 2, strlen($phoneNumber) - 2);

            //$phoneOne = substr($phone, 0, 3);
            //$phoneTwo = substr($phone, 3, 3);
            //$phoneThree = substr($phone, 5, 4);
            //$sqlPhone = "(" . $phoneOne . ")" . $phoneTwo . "-" . $phoneThree;

            $sql = "SELECT p.idPatients, p.arNo, p.firstName, p.lastName, p.middleName, p.phone 
            FROM " . self::DB_CSS . "." . self::TBL_PATIENTS . " p 
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SMSLOG . " sl ON p.idPatients = sl.patientId
            WHERE REPLACE(REPLACE(REPLACE(REPLACE(p.phone, '(', ''), ')', ''), '-', ''), ' ', '') = ?";

            $data = parent::select($sql, array($phone), array("Conn" => $this->Conn));

            if (count($data) > 0) {
                // just get the first row as there might be multiple entries. joining patientSMSLog ensures it selects a patient that has previously received a text
                $idPatients = $data[0]['idPatients'];
                $arNo = $data[0]['arNo'];
                $lastName = $data[0]['lastName'];
                $firstName = $data[0]['firstName'];
                $middleName = $data[0]['middleName'];
                $phone = $data[0]['phone'];

                $sql2 = "SELECT su.idUnsubscribed FROM " . self::DB_CSS . "." . self::TBL_SMSUNSUBSCRIBED . " su WHERE su.patientId = ?";
                $data2 = parent::select($sql2, array($idPatients), array("Conn" => $this->Conn));
                if (count($data2) == 0) {
                    // insert
                    $sql3 = "INSERT INTO " . self::DB_CSS . "." . self::TBL_SMSUNSUBSCRIBED . " (patientId, patientArNo, patientLastName, patientFirstName, patientMiddleName, patientPhone) VALUES (?,?,?,?,?,?)";

                    parent::manipulate($sql3, array($idPatients, $arNo, $lastName, $firstName, $middleName, $phone), array("Conn" => $this->Conn));

                    // add log entry
                } else {
                    // already unsubscribed - do nothing or maybe add a log entry
                }
            }

        }

    }

    public function removeUnsubscribed($phoneNumber) {
        if (strlen($phoneNumber) == 12) {
            $phone = substr($phoneNumber, 2, strlen($phoneNumber) - 2);

            //$phoneOne = substr($phone, 0, 3);
            //$phoneTwo = substr($phone, 3, 3);
            //$phoneThree = substr($phone, 5, 4);
            //$sqlPhone = "(" . $phoneOne . ")" . $phoneTwo . "-" . $phoneThree;

            $sql = "SELECT p.idPatients, p.arNo, p.firstName, p.lastName, p.middleName, p.phone 
            FROM " . self::DB_CSS . "." . self::TBL_PATIENTS . " p 
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SMSLOG . " sl ON p.idPatients = sl.patientId
            WHERE REPLACE(REPLACE(REPLACE(REPLACE(p.phone, '(', ''), ')', ''), '-', ''), ' ', '') = ?";

            $data = parent::select($sql, array($phone), array("Conn" => $this->Conn));

            if (count($data) > 0) {
                // just get the first row as there might be multiple entries. joining patientSMSLog ensures it selects a patient that has previously received a text
                $idPatients = $data[0]['idPatients'];
                $arNo = $data[0]['arNo'];
                $lastName = $data[0]['lastName'];
                $firstName = $data[0]['firstName'];
                $middleName = $data[0]['middleName'];
                $phone = $data[0]['phone'];

                $sql2 = "SELECT su.idUnsubscribed FROM " . self::DB_CSS . "." . self::TBL_SMSUNSUBSCRIBED . " su WHERE su.patientId = ?";
                $data2 = parent::select($sql2, array($idPatients), array("Conn" => $this->Conn));
                if (count($data2) > 0) {
                    // insert
                    $sql3 = "DELETE FROM " . self::DB_CSS . "." . self::TBL_SMSUNSUBSCRIBED . " WHERE patientId = ?";
                    parent::manipulate($sql3, array($idPatients));

                    // add log entry
                } else {
                    // already unsubscribed - do nothing or maybe add a log entry
                }
            }

        }
    }

}