<?php
require_once 'DataObject.php';
require_once 'DOS/GeneticReport.php';

class GeneticReportDAO extends DataObject {
    private $Conn;
    private $GeneticReport;

    public function __construct() {
        $this->Conn = parent::connect();
    }

    public function getReport($idOrders, array $settings = null) {
        $sql = "
            SELECT g.id, g.idorders, g.idpatients, g.report, g.created
            FROM " . self::DB_CSS . "." . self::TBL_GENETICREPORT . " g
            WHERE g.idorders = ?";
        $data = parent::select($sql, array($idOrders));
        if (count($data) > 0) {
            $this->GeneticReport = new GeneticReport($data[0]);
        }
    }

    public function __get($field) {
        $value = "";
        if ($field == "GeneticReport") {
            $value = $this->GeneticReport;
        }
        return $value;
    }
}