<?php
/**
 * Created by PhpStorm.
 * User: eboss
 * Date: 8/5/2025
 * Time: 3:52 PM
 */
require_once 'DataObject.php';
require_once 'DOS/KitNumber.php';

class KitNumberDAO extends DataObject {
    private $Conn;

    protected $Data = array(
        "OrderBy" => "",
        "Direction" => ""
    );

    public function __construct(array $data = null) {
        parent::__construct($data);



        $this->Conn = parent::connect();
    }

    public function getKitNumbers($activeOnly = true) {
        $sql = "SELECT k.idKitNumbers, k.productName, k.lotNumber, k.kitNumber, k.description, k.dateCreated, k.dateUpdated, k.isActive, k.isPrepaid
            FROM " . self::DB_CSS . "." . self::TBL_KITNUMBERS . " k ";

        if ($activeOnly) {
            $sql .= " WHERE k.isActive = true";
        }

        $orderBy = "";
        if ($this->Data != null) {
            if (array_key_exists("OrderBy", $this->Data) && !empty($this->Data['OrderBy'])) {
                if ($this->Data['OrderBy'] == "productName") {
                    $orderBy = "ORDER BY productName";
                } else if ($this->Data['OrderBy'] == "lotNumber") {
                    $orderBy = "ORDER BY lotNumber";
                } else if ($this->Data['OrderBy'] == "kitNumber") {
                    $orderBy = "ORDER BY kitNumber";
                } else if ($this->Data['OrderBy'] == "description") {
                    $orderBy = "ORDER BY description";
                } else if ($this->Data['OrderBy'] == "dateCreated") {
                    $orderBy = "ORDER BY dateCreated";
                } else if ($this->Data['OrderBy'] == "dateUpdated") {
                    $orderBy = "ORDER BY dateUpdated";
                } else if ($this->Data['OrderBy'] == "prePaid") {
                    $orderBy = "ORDER BY prePaid";
                }

                if (array_key_exists("Direction", $this->Data) && ($this->Data['Direction'] == "asc" || $this->Data['Direction'] == "desc")) {
                    $orderBy .= " " . $this->Data['Direction'];
                }

                $sql .= " " . $orderBy;
            }
        }



        //error_log($sql);
        if (isset(self::$Conn) && self::$Conn != null) {
            $data = parent::select($sql, null, array("Conn" => self::Conn));
        } else {
            $data = parent::select($sql, null);
        }

        $aryKitNumbers = array();
        if (count($data) > 0) {
            foreach ($data as $row) {
                $aryKitNumbers[] = new KitNumber($row);
            }
        }
        return $aryKitNumbers;
    }

    public function insertKitNumber(KitNumber $kit) {
        $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_KITNUMBERS . " (productName, lotNumber, kitNumber) VALUES (?, ?, ?)";
        $qryInput = array($kit->productName, $kit->lotNumber, $kit->kitNumber);
        $idKitNumbers = parent::manipulate($sql, $qryInput, array("LastInsertId" => true));
        return $idKitNumbers;
    }

    public function insertKitNumbers(array $kitNumbers) {
        $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_KITNUMBERS . " (productName, lotNumber, kitNumber) VALUES ";

        $qryInput = array();
        foreach ($kitNumbers as $kit) {
            $sql .= "(?, ?, ?),";
            $qryInput[] = $kit->productName;
            $qryInput[] = $kit->lotNumber;
            $qryInput[] = $kit->kitNumber;

        }
        $sql = substr($sql, 0, strlen($sql) - 1);
        parent::manipulate($sql, $qryInput);

        return true;
    }

    public function updateKitNumber(KitNumber $kit) {
        $sql = "UPDATE " . self::DB_CSS . "." . self::TBL_KITNUMBERS . " " .
            "SET kitNumber = ?, description = ?, isPrepaid = ?, dateUpdated = ? " .
            "WHERE idKitNumbers = ?";
        $qryInput = array($kit->kitNumber, $kit->description, $kit->isPrepaid, date("Y-m-d H:i:s"), $kit->idKitNumbers);
        $idKitNumbers = parent::manipulate($sql, $qryInput, array("LastInsertId" => true));

        if ($idKitNumbers == $kit->idKitNumbers) {
            return true;
        }
        return false;
    }

    public function deleteKitNumber($idKitNumbers) {
        $sql = "UPDATE " . self::DB_CSS . "." . self::TBL_KITNUMBERS . " " .
            "SET isActive = ?, dateUpdated = ? " .
            "WHERE idKitNumbers = ?";

        $qryInput = array(false, date("Y-m-d H:i:s"), $idKitNumbers);
        $affectedRows = parent::manipulate($sql, $qryInput, array("AffectedRows" => true));
        return $affectedRows;
    }
}