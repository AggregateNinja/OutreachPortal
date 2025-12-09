<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 2/17/2020
 * Time: 1:43 PM
 */
require_once 'DataObject.php';

class ChartDAO extends DataObject {
    private $WhereClause = array();
    private $Conn;
    protected $Data = array();

    public function __construct(array $data = null)
    {
        $this->Conn = parent::connect();
    }

    public function getUser(array $input) {
        $aryInput = array();
        $aryInput[] = $input['id'];

        $sql = "
        SELECT * 
        FROM " . self::DB_CSS . "." . self::TBL_AVALON_USERS . " u
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_CHARTUSERLOOKUP . " ul ON u.idUser = ul.userId
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_CHARTS . " c ON ul.chartId = c.idCharts
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_CHARTTYPES . " ct ON c.chartTypeId = ct.idChartTypes
        WHERE u.idUser = ?
        ORDER BY c.displayOrder ASC
        LIMIT 2";

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        return $data;

    }

    private function getDateSearchField(array $input) {
        $dateField = "o.specimenDate";
        if (array_key_exists("dateField", $input)) {
            $n = $input['dateField'];
            switch ($n) {
                case "DOI":
                    $dateField = "o.DOI";
                    break;
                case "orderDate":
                    $dateField = "o.orderDate";
                    break;
                case "paymentDate":
                    $dateField = "do.lastPaymentDate";
                    break;
            }
        }
        return $dateField;
    }

    private function getWhere(array $input)
    {

        $dateField = $this->getDateSearchField($input);

        $dateFrom = $input['dateFrom'];
        $dateTo = $input['dateTo'];

        $where = "WHERE o.active = true
            AND $dateField BETWEEN ? AND ? ";
        $aryInput = array($dateFrom, $dateTo);

        return array(
            "where" => $where,
            "input" => $aryInput
        );
    }

    public function getCompleteIncompleteOrders(array $input) {

        $aryWhere = $this->getWhere($input);
        $where = $aryWhere['where'];
        $aryInput = $aryWhere['input'];

        $where .= "AND (r.isInvalidated IS NULL OR r.isInvalidated = false)";

        $sql = "
        SELECT o.idOrders, o.accession,
            COUNT(o.idOrders) AS `OrderCount`,
            SUM(r.printAndTransmitted) AS `CompleteCount`
        FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
        INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
        $where
        GROUP BY o.idOrders";

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $aryReturn = array(
            array(
                "name" => "Complete",
                "y" => 0,
                "type" => "undefined"
            ),
            array(
                "name" => "Incomplete",
                "y" => 0,
                "type" => "undefined"
            ),
            array(
                "name" => "Partial",
                "y" => 0,
                "type" => "undefined"
            )
        );

        if (count($data) > 0) {
            foreach ($data as $row) {
                if ($row['OrderCount'] == $row['CompleteCount']) {
                    // Complete
                    $aryReturn[0]['y'] += 1;
                } else if ($row['CompleteCount'] == 0) {
                    $aryReturn[1]['y'] += 1;
                } else if ($row['CompleteCount'] > 0 && $row['OrderCount'] > $row['CompleteCount']) {
                    $aryReturn[2]['y'] += 1;
                }

            }
        }

        return $aryReturn;
    }

    public function getTotalBilledPaidByInsuranceSubmissionType(array $input) {
        $aryWhere = $this->getWhere($input);
        $where = $aryWhere['where'];
        $aryInput = $aryWhere['input'];

        $sql = "
        SELECT ist.idInsuranceSubmissionTypes, ist.name,
            COUNT(*) AS `SubmissionTypeCount`,
            SUM(dcc.billAmount) AS `TotalBillAmount`,
            SUM(dcc.paid) AS `TotalPaid`
        FROM orders o
        INNER JOIN cssbilling.detailOrders do ON o.idOrders = do.orderId AND do.active = true
        INNER JOIN cssbilling.detailCptCodes dcc ON do.iddetailOrders = dcc.detailOrderId
        INNER JOIN cssbilling.detailInsurances di ON do.iddetailOrders = di.detailOrderId AND di.rank = 1
        INNER JOIN insuranceSubmissionTypes ist ON di.insuranceSubmissionTypeId = ist.idInsuranceSubmissionTypes
        $where
        GROUP BY ist.idInsuranceSubmissionTypes";

        return parent::select($sql, $aryInput, array("Conn" => $this->Conn));
    }

    public function getCompleteIncompleteResults(array $input) {
        $aryWhere = $this->getWhere($input);
        $where = $aryWhere['where'];
        $aryInput = $aryWhere['input'];

        $sql = "
        SELECT SUM(CASE WHEN (r.isInvalidated IS NULL OR r.isInvalidated = false) AND r.printAndTransmitted = true THEN 1 ELSE 0 END) AS `CompleteCount`,
            SUM(CASE WHEN (r.isInvalidated IS NULL OR r.isInvalidated = false) AND r.printAndTransmitted = false THEN 1 ELSE 0 END) AS `IncompleteCount`,
            SUM(r.isInvalidated) AS `InvalidatedCount`
        FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
        INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
        $where";

        /*echo $sql;
        echo "<pre>"; print_r($aryInput); echo "</pre>";*/

        return parent::select($sql, $aryInput, array("Conn" => $this->Conn));
    }

    public function getBillableUnbillableOrders(array $input) {
        $aryWhere = $this->getWhere($input);
        $where = $aryWhere['where'];
        $aryInput = $aryWhere['input'];

        $sql = "
        SELECT SUM(o.billable) AS `BillableCount`,
            SUM(CASE WHEN o.billable = false THEN 1 ELSE 0 END) AS `UnbillableCount`
        FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
        $where";

        return parent::select($sql, $aryInput, array("Conn" => $this->Conn));
    }

    public function getTotalOrdersTimeSeries(array $input) {
        $aryWhere = $this->getWhere($input);
        $where = $aryWhere['where'];
        $aryInput = $aryWhere['input'];

        $dateField = $this->getDateSearchField($input);

        $sql = "
            SELECT COUNT(o.idOrders) AS `OrderCount`,
                DATE_FORMAT($dateField, '%Y-%m-%d') AS `FormattedDate`
            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            $where
            GROUP BY FormattedDate
            ORDER BY FormattedDate";

        return parent::select($sql, $aryInput, array("Conn" => $this->Conn));
    }



}