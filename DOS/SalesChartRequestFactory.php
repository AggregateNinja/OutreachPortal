<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 11/17/14
 * Time: 9:47 AM
 */

require_once 'ReportCreator.php';
require_once 'JasperReport.php';
require_once 'DAOS/ResultLogDAO.php';

class SalesChartRequestFactory extends ReportCreator {

    protected function factoryMethod(array $data, array $settings = null) {
        $downloadReport = false;
        if ($settings != null) {
            if (array_key_exists("DownloadReport", $settings)) {
                $downloadReport = true;
            }
        }

        $name = "";
        $filePath = "";
        $groupId = 0;
        $dateFrom = date("Y-m-d", strtotime($data['dateFrom'])) . " 00:00:00";
        $dateTo = date("Y-m-d", strtotime($data['dateTo'])) . " 23:59:59";

        $aryParams = array(
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        );

        if ($data['isAdmin'] == true) {
            $name = "SalesmenOwnerReport";
            $filePath = "SalesmenOwnerReport.jrxml";
            $aryParams['groupId'] = 0;
        } else if ($data['isGroupLeader'] == true) {
            $name = "SalesmenGroupLeaderReport";
            $filePath = "SalesmenGroupLeaderReport.jrxml";
            $aryParams['groupId'] = $data['groupId'];
        } else {
            $name = "SalesmanReport";
            $filePath = "SalesmanReport.jrxml";
            $aryParams['groupId'] = $data['groupId'];
            $aryParams['idsalesmen'] = $data['salesmanId'];
        }

        $jasperData = array(
            array(
                "name" => $name,
                "filePath" => $filePath,
                "Parameters" => $aryParams
            )
        );

        //echo "<pre>"; print_r($data); echo "</pre>";

        $this->Report = new JasperReport($jasperData);

        $this->addLogEntry(array("userId" => $data['userId']));

        /*if (!$downloadReport) {
            $fileName = $this->saveReport($this->Report->EncodedPdf, "report" . $this->Report->name);
            return $fileName;
        }*/

        return $this->Report;
    }

    protected function addLogEntry(array $logData = null) {
        $aryInput = array(
            "userId" => $logData['userId'],
            "typeId" => 10,
            "ip" => $this->getIpAddress(),
            "action" => 1
        );
        ResultLogDAO::addSalesLogEntry($aryInput);
    }
} 