<?php
require_once 'ReportCreator.php';
require_once 'JasperReport.php';
require_once 'DAOS/ResultLogDAO.php';

class ManifestReportFactory extends ReportCreator {

    protected function factoryMethod(array $data, array $settings = null) {

        $jasperData = array(array(
            "name" => "WebManifest",
            "filePath" => "WebManifest.jrxml",
            "Parameters" => array (
                "dateFrom" => date("Y-m-d", strtotime($data['orderDate'])) . " 00:00:00",
                "dateTo" => date("Y-m-d", strtotime($data['orderDate'])) . " 23:59:59",
                "userId" => $data['userId'],
                "orderBy" => $data['orderBy'],
                "direction" => $data['direction'],
                "clientNo" => $data['clientNo'],
                "doctorNo" => $data['doctorNo'],
                "orderAccess" => $data['orderAccess'],
                "userIdList" => $data['userIdList']
            )
        ));

        $this->Report = new JasperReport($jasperData);

        $this->addLogEntry(array("userId" => $data['userId']));

        return $this->Report;
    }

    protected function addLogEntry(array $logData = null) {
        ResultLogDAO::addLogEntry($logData['userId'], 12, array("Ip" => $this->getIpAddress()));
    }
} 