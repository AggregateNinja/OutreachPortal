<?php
/**
 * Created by PhpStorm.
 * User: Ed Bossmeyer
 * Date: 9/22/14
 * Time: 3:27 PM
 */

require_once 'ReportCreator.php';
require_once 'JasperReport.php';
require_once 'DAOS/ResultLogDAO.php';

class AbnormalsReportFactory extends ReportCreator {

    protected function factoryMethod(array $data, array $settings = null) {
        $jasperData = array(array(
            "name" => "AbnormalsReport",
            "filePath" => "AbnormalsReport.jrxml",
            "Parameters" => array (
                "dateFrom" => date("Y-m-d", strtotime($data['dateFrom'])) . " 00:00:00",
                "dateTo" => date("Y-m-d", strtotime($data['dateTo'])) . " 23:59:59",
                "orderBy" => $data['orderBy'],
                "direction" => $data['direction'],
                "userId" => $data['userId']
            )
        ));

        $this->Report = new JasperReport($jasperData);

        $this->addLogEntry(array("userId" => $data['userId']));

        return $this->Report;
    }

    protected function addLogEntry(array $logData = null) {
        ResultLogDAO::addLogEntry($logData['userId'], 8, array("Ip" => $this->getIpAddress()));
    }
} 