<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 5/2/2016
 * Time: 3:31 PM
 */
require_once 'ReportCreator.php';
require_once 'JasperReport.php';
require_once 'DAOS/ResultLogDAO.php';

class CumulativeReportFactory extends ReportCreator {

    protected function factoryMethod(array $data, array $settings = null) {
        $jasperData = array(array(
            "name" => "CumulativeReport",
            "filePath" => "CumulativeReport.jrxml",
            "Parameters" => array (
                "arNo" => $data['arNo'],
                "specimenDate" => urldecode($data['specimenDate']),
                "idUsers" => $data['idUsers'],
                "logoImageFile" => $data['logoImageFile']

                //"dateFrom" => date("Y-m-d h:i:s", strtotime($data['dateFrom'])),
                //"dateTo" => date("Y-m-d h:i:s", strtotime($data['dateTo'])),
                //"userId" => $data['userId'],
                //"orderBy" => $data['orderBy'],
                //"direction" => $data['direction']
            )
        ));

        $this->Report = new JasperReport($jasperData);

        $this->addLogEntry(array("userId" => $data['idUsers']));

        return $this->Report;
    }

    protected function addLogEntry(array $logData = null) {
        //ResultLogDAO::addLogEntry($logData['userId'], 5, array("Ip" => $this->getIpAddress()));
    }

}