<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 1/21/15
 * Time: 12:06 PM
 */
require_once 'ReportCreator.php';
require_once 'JasperReport.php';
require_once 'DAOS/ResultLogDAO.php';

class PatientPosReportFactory extends ReportCreator {

    protected function factoryMethod(array $data, array $settings = null) {

        $orderBy = $data['orderBy'];
        if ($orderBy == "PatientLastName") {
            $orderBy = "p.lastName";
        } else if ($orderBy == "PatientFirstName") {
            $orderBy = "p.firstName";
        }

        $aryParams = array (
            "dateFrom" => date("Y-m-d", strtotime($data['dateFrom'])) . " 00:00:00",
            "dateTo" => date("Y-m-d", strtotime($data['dateTo'])) . " 23:59:59",
            "orderBy" => $orderBy,
            "direction" => $data['direction'],
            "userId" => $data['userId'],
            "TestIds" => $data['TestIds'],
            "testNameDirection" => $data['testNameDirection'],
            "allTests" => $data['allTests']
        );

        $strParams = implode(", ", $aryParams);

        $jasperData = array(array(
            "name" => "PatientPositiveReport",
            "filePath" => "PatientPositiveReport.jrxml",
            "Parameters" => $aryParams
        ));

        $this->Report = new JasperReport($jasperData);

        $this->addLogEntry(array("userId" => $data['userId']));

        return $this->Report;
    }

    protected function addLogEntry(array $logData = null) {
        ResultLogDAO::addLogEntry($logData['userId'], 11, array("Ip" => $this->getIpAddress()));
    }
} 