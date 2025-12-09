<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 12/17/14
 * Time: 3:01 PM
 */
require_once 'ReportCreator.php';
require_once 'JasperReport.php';
require_once 'DAOS/ResultLogDAO.php';

class OutreachUsageReportFactory extends ReportCreator {

    protected function factoryMethod(array $data, array $settings = null) {

        $jasperData = array(array(
            "name" => "OutreachUsageReport",
            "filePath" => "OutreachUsageReport.jrxml",
            "Parameters" => array (
                "dateFrom" => date("Y-m-d h:i:s", strtotime($data['dateFrom'])),
                "dateTo" => date("Y-m-d h:i:s", strtotime($data['dateTo'])),
                "orderBy" => $data['orderBy'],
                "direction" => $data['direction']
            )
        ));

        $this->Report = new JasperReport($jasperData);

        return $this->Report;
    }

    protected function addLogEntry(array $logData = null) {

    }

    private function getLabName($id) {
        $strLabName = "";
        $aryLabNames = array (
            "",
            "Advanced Laboratory Solutions",
            "Premier Medical Laboratory",
            "American Clinical Solutions",
            "Precision Diagnostics",
            "Vizion Specialty Labs",
            "Medical DNA Laboratories",
            "Balcones Pain Consultants",
            "Tomoka",
            "LifeBrite",
            "Assurance",
            "Clinical Laboratory Services",
            "PSO Laboratories"
        );
        if (array_key_exists($id, $aryLabNames)) {
            $strLabName = $aryLabNames[$id];
        }
        return $strLabName;
    }

} 