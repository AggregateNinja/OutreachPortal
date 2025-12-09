<?php
require_once "DataObject.php";
require_once "DOS/Order.php";

class PatientOrderSearchDAO extends DataObject {

    private $Conn;

    private $Orders;
    private $TotalOrders;

    private $PatientId;
    private $OrderBy;
    private $Direction;
    private $Offset;
    private $MaxRows;

    public function __construct($patientId, array $pageData, array $settings = null) {
        $this->PatientId = $patientId;
        $this->TotalOrders = 0;
        $this->Orders = null;
        $this->OrderBy = $pageData['OrderBy'];
        $this->Direction = $pageData['Direction'];
        $this->Offset = $pageData['Offset'];
        $this->MaxRows = $pageData['MaxRows'];

        if($settings != null && array_key_exists("Conn", $settings) && $settings['Conn'] instanceof mysqli) {
            $this->Conn = $settings['Conn'];
        }

    }

	public function getPageResults () {
        $sql = "
            SELECT 		o.idOrders, o.accession, o.active, o.reportType, r.resultNo, r.isAbnormal, pr.key, o.active,
                        COUNT(idOrders) AS `orderCount`,
                        SUM(if( r.dateReported IS NOT NULL, 1, 0)) AS `reportedCount`,
                        SUM(r.printAndTransmitted) AS `completedCount`,
                        SUM(r.isAbnormal) AS `abnormalCount`,
                        SUM(r.isInvalidated) AS `invalidatedCount`,
                        c.transType, c.idClients, c.clientName, c.clientNo, p.idPatients, p.arNo AS `patientNo`, p.lastName AS `patientLastName`,
                        p.firstName AS `patientFirstName`, d.iddoctors, d.number, d.lastName AS `doctorLastName`, d.firstName AS `doctorFirstName`,
                        o.orderDate, o.specimenDate, r.dateReported, r.approvedDate,
                        if (
                            o.stage IS NULL,
                            1,
                            o.stage
                        ) AS `stage`, null AS `logDate`
            FROM " . self::TBL_ORDERS . " o
            INNER JOIN " . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
            INNER JOIN " . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
            LEFT JOIN " . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors
            INNER JOIN " . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
            INNER JOIN " . self::TBL_TESTS . " t ON r.testId = t.idtests
            LEFT JOIN " . self::TBL_PREFERENCES . " pr ON (r.testId = pr.value OR r.panelId = pr.value) AND pr.key = 'POCTest'
            WHERE p.idPatients = ?
            GROUP BY idOrders
            HAVING ((orderCount = completedCount AND c.transType = 1) OR (reportedCount > 0 AND c.transType != 1))
            ORDER BY " . $this->OrderBy . " " . $this->Direction;

        $input = array($this->PatientId);

        $data = parent::select($sql, $input, array("Conn" => $this->Conn));

        $this->TotalOrders = count($data);
        $this->makeOrderArray($data);
    }

    private function makeOrderArray(array $data) {
        if (count($data) > 0) {
            $start = $this->Offset; // inclusive
            $end = $start + $this->MaxRows; // exclusive
            if ($end > $this->TotalOrders) {
                $end = $this->TotalOrders;
            }

            $orders = array();
            //foreach ($data as $row) {
            for ($i = $start; $i < $end; $i++) {
                $row = $data[$i];
                $currOrder = new Order($row);

                if ($row['abnormalCount'] > 0) {
                    $currOrder->IsAbnormal = true;
                }
                if ($row['active'] != 1) {
                    $currOrder->IsInvalidated = true;
                }
                $orders[] = $currOrder;
            }

            $this->Orders = $orders;
            //echo "<pre>"; print_r($this->Orders); echo "</pre>";
        }
    }

    public function __get($field) {
        $value = "";
        if ($field == "Orders") {
            $value = $this->Orders;
        } else if ($field == "TotalOrders") {
            $value = $this->TotalOrders;
        }
        return $value;
    }


}
