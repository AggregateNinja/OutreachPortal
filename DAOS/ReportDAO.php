<?php
require_once 'DataObject.php';
require_once 'DOS/GeneticReport.php';
require_once 'DOS/JasperReport.php';
require_once 'DataConnect.php';

class ReportDAO extends DataObject {

    private $ViewOrderIds = array(); //array of orderId's to be viewed in a single pdf report
    private $Conn;
    private $UserId;
    private $orderBy;
    private $direction;

    public function __construct($userId, array $viewOrderIds = null, $orderBy = "orderDate", $direction = "DESC") {
        $this->UserId = $userId;

        $this->Conn = DataConnect::getConn();

        if ($viewOrderIds != null) {
            for ($i = 1; $i <= count($viewOrderIds); $i++) {
                $this->ViewOrderIds[$i] = $viewOrderIds[$i - 1];
            }
        }

        $this->orderBy = $orderBy;
        $this->direction = $direction;
    }

    public function getReport() {
        $sql = $this->getSqlString();

//        error_log($sql);
//        error_log(implode(", ", $this->ViewOrderIds));

        $data = parent::select($sql, $this->ViewOrderIds, array("Conn" => $this->Conn));
        return $data;
    }

    private function getSqlString() {
        /*$sql = "
            SELECT  o.idOrders, r.number, r.name,
                    SUBSTRING(r.filePath, 21) AS 'filePath',
                    r.selectable, r.format, o.active,
                    gr2.idpatients, gr2.report, gr2.created,
                    rlr.report AS `ReferenceLabReport`
            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            INNER JOIN " . self::DB_CSS . "." . self::TBL_REPORTTYPE . " r ON o.reportType = r.number
            LEFT JOIN (
                SELECT MAX(gr.id) AS `MaxId`, gr.idorders
                FROM " . self::DB_CSS . "." . self::TBL_GENETICREPORT . " gr
                GROUP BY gr.idorders
                ORDER BY gr.id DESC
            ) gr ON o.idOrders = gr.idorders
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_GENETICREPORT . " gr2 ON gr.MaxId = gr2.id
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_REFERENCELABREPORT . " rlr ON o.idOrders = rlr.orderId
            WHERE o.idOrders IN (";*/

        $sql = "
            SELECT  o.idOrders, o.accession, r.number, r.name,
                    SUBSTRING(r.filePath, 21) AS 'filePath',
                    r.selectable, r.format, o.active,
                    gr.idpatients, gr.report, gr.created,
                    rlr.reportName AS `refReportName`, rlr.report AS `ReferenceLabReport`,
                    il.idAILog,
                    wd.idDocuments, wd.fileName AS `docFileName`, wd.typeName,
                    d.lastName AS `doctorLastName`, d.number,
                    c.clientName, c.clientNo,
                    p.lastName AS `patientLastName`, p.arNo AS `patientNo`,
                    o.orderDate, o.specimenDate,
                    o.stage
            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            INNER JOIN " . self::DB_CSS . "." . self::TBL_REPORTTYPE . " r ON o.reportType = r.number
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
            INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_GENETICREPORT . " gr ON o.idOrders = gr.idorders
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_REFERENCELABREPORT . " rlr ON o.idOrders = rlr.orderId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_WEBORDERDOCUMENTS . " wd ON o.idOrders = wd.avalonIdOrders AND wd.typeName = 'pdf' AND wd.isActive = true AND wd.isSent = true
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_AVALONINSIGHTLOG . " il ON o.idOrders = il.orderId AND il.active = true 
            WHERE o.idOrders IN (";

        foreach ($this->ViewOrderIds as $placeHolder => $orderId) {
            $sql .= "?, ";
        }
        $sql = substr($sql, 0, strlen($sql) - 2);
        $sql .= ") ORDER BY " . $this->orderBy . " " . $this->direction;

        return $sql;
    }

    public function __set($field, $value) {
        if ($field == "ViewOrderIds") {
            $this->ViewOrderIds = $value;
        }
    }

    public function __get($field) {
        if ($field == "Conn") {
            return $this->Conn;
        }
    }
}
