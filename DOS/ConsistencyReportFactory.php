<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 8/27/15
 * Time: 4:29 PM
 */

require_once 'ReportCreator.php';
require_once 'JasperReport.php';
require_once 'DAOS/ResultLogDAO.php';

class ConsistencyReportFactory extends ReportCreator {

    protected function factoryMethod(array $data, array $settings = null) {

        $orderBy = $data['orderBy'];
        if ($orderBy == "PatientLastName") {
            $orderBy = "lastName";
        } else if ($orderBy == "PatientFirstName") {
            $orderBy = "firstName";
        } else if ($orderBy == "accession") {
            $orderBy = "CAST(accession AS UNSIGNED)";
        }


        $jasperData = array(array(
            "name" => "InconsistentReport",
            "filePath" => "InconsistentReport.jrxml",
            "Parameters" => array (
                "dateFrom" => date("Y-m-d", strtotime($data['dateFrom'])) . " 00:00:00",
                "dateTo" => date("Y-m-d", strtotime($data['dateTo'])) . " 23:59:59",
                "orderBy" => $orderBy,
                "direction" => $data['direction'],
                "userId" => $data['userId'],
                "patientOrderBy" => "p.lastName",
                "patientDirection" => "ASC",
                "clientId" => $data['clientId'],
                "doctorId" => $data['doctorId']
            )
        ));

        $this->Report = new JasperReport($jasperData);

        $this->addLogEntry(array("userId" => $data['userId']));

        return $this->Report;
    }

    protected function addLogEntry(array $logData = null) {
        ResultLogDAO::addLogEntry($logData['userId'], 15, array("Ip" => $this->getIpAddress()));
    }

} 