<?php

/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 1/9/2018
 * Time: 3:43 PM
 */
require_once 'DAOS/SalesDAO.php';

class PieChartData {

    public $dateFrom;
    public $dateTo;
    public $groupId;
    public $dateField;
    public $idsalesmen;

    public $aryChartData;
    public $jsonChartData;

    public function __construct(array $params) {
        if (array_key_exists("dateFrom", $params)) {
            $this->dateFrom = $params['dateFrom'];
        }
        if (array_key_exists("dateTo", $params)) {
            $this->dateTo = $params['dateTo'];
        }
        if (array_key_exists("groupId", $params)) {
            $this->groupId = $params['groupId'];
        }
        if (array_key_exists("dateField", $params)) {
            $this->dateField = $params['dateField'];
        }
        if (array_key_exists("idsalesmen", $params)) {
            $this->idsalesmen = $params['idsalesmen'];
        }
    }

    public function getTotalOrders() {
        /*
        this.donutChartData = [
            { "key":"Billable","y":25 },
            { "key":"Unbillable","y":6 },
            { "key":"Rejections","y":4 }
        ];

        $aryChartData = array(
            array('key' => 'Billable', 'y' => 25),
            array('key' => 'Unbillable', 'y' => 6),
            array('key' => 'Rejections', 'y' => 4)
        );
        */
        $aryInput = array(
            "dateFrom" => $_GET['dateFrom'],
            "dateTo" => $_GET['dateTo']
        );

        $data = SalesDAO::getTotalBillableUnbillableRejected($aryInput);

        $totalBillable = $data[0]['TotalBillable'];
        $totalUnbillable = $data[0]['TotalUnbillable'];
        $totalRejected = $data[0]['TotalRejected'];

        $this->aryChartData = array(
            array('key' => $totalBillable . ' Billable', 'y' => $totalBillable),
            array('key' => $totalUnbillable . ' Unbillable', 'y' => $totalUnbillable),
            array('key' => $totalRejected . ' Rejected', 'y' => $totalRejected)
        );

        $this->jsonChartData = json_encode($this->aryChartData);
    }

    /*
    {
        "1": [
            "Blood Wellness", 4655, 47965.6
        ],
        "4": [
            "PGX", 181, 5002.6
        ],
        "5": [
            "Hereditary Cancer", 112, 5811.7
        ],
        "6": [
            "Pharmacogenomics", 40, 1526.75
        ]
    }
     */
}