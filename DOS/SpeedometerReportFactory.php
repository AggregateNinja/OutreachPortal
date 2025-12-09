<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 1/2/15
 * Time: 10:00 AM
 */

require_once 'ReportCreator.php';
require_once 'JasperReport.php';
require_once 'DAOS/ResultLogDAO.php';

class SpeedometerReportFactory extends ReportCreator {

    protected function factoryMethod(array $data, array $settings = null) {
        $downloadReport = false;
        if ($settings != null) {
            if (array_key_exists("DownloadReport", $settings)) {
                $downloadReport = true;
            }
        }

        $dateFrom = date("Y-m-d", strtotime($data['dateFrom'])) . " 00:00:00";
        //if ($data['intervalId'] != 1) {
            $dateTo = date("Y-m-d", strtotime($data['dateTo'])) . " 23:59:59";
        //} else {
        //    $dateTo = date("Y-m-d", strtotime($data['dateFrom'])) . " 23:59:59";
        //}

        // "dateFrom" => date("Y-m-d h:i:s", strtotime($data['dateFrom'])),
        // "dateTo" => date("Y-m-d h:i:s", strtotime($data['dateTo'])),

        $aryParams = array (
            "dateFrom" => $dateFrom,
            "dateTo" => $dateTo,
            "idsalesmen" => $data['idsalesmen'],
            "idGoals" => $data['goalId'],
            "salesgroupId" => $data['salesgroupId']
        );

        $name = "SalesSpeedometer";
        $filePath = "SalesSpeedometer.jrxml";
        if ($data['isOwner'] == true) {
            $aryParams['userId'] = $data['userId'];
            $name = "SalesOwnerSpeedometer";
            $filePath = "SalesOwnerSpeedometer.jrxml";
        }
        $jasperData = array (
            array(
                "name" => $name,
                "filePath" => $filePath,
                "Parameters" => $aryParams
            )
        );

        /*$jasperData = array(array(
            "name" => "SalesSpeedometer",
            "filePath" => "SalesSpeedometer.jrxml",
            "Parameters" => array (
                "dateFrom" => '2016-08-28 00:00:00',
                "dateTo" => '2016-09-28 23:59:59',
                "idsalesmen" => 6,
                "idGoals" => 6,
                "salesgroupId" => 12
            )
        ));*/



        //echo "<pre>"; print_r($jasperData); echo "</pre>";

        $this->Report = new JasperReport($jasperData);

        /*if (!$downloadReport) {
            //echo "<pre>"; print_r($this->Report); echo "</pre>";
            $fileName = $this->saveReport($this->Report->EncodedPdf, "report" . $this->Report->name);
            return $fileName;
        }*/

        $this->addLogEntry(array("userId" => $data['userId']));

        return $this->Report;
    }

    protected function addLogEntry(array $logData = null) {
        $logData = array(
            "userId" => $logData['userId'],
            "typeId" => 10,
            "ip" => $this->getIpAddress(),
            "action" => 1
        );
        ResultLogDAO::addSalesLogEntry($logData);
    }

} 