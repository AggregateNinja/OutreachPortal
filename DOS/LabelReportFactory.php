<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 2/17/15
 * Time: 4:30 PM
 */

require_once 'ReportCreator.php';
require_once 'Report.php';
require_once 'PDFMerger/PDFMerger.php';
require_once 'DAOS/ResultLogDAO.php';
require_once 'JasperReport.php';

class LabelReportFactory extends ReportCreator {

    protected function factoryMethod(array $data, array $settings = null) {
        $jasperData = array(array(
            "name" => "WebOrderBarcode",
            "filePath" => "WebOrderBarcode.jrxml",
            "Parameters" => array (
                "idOrders" => $data['idOrders'],
                "numLabels" => $data['numLabels']
            )
        ));

        $this->Report = new JasperReport($jasperData);

        //$this->addLogEntry(array("userId" => $data['userId']));

        //$fileName = $this->saveReport($this->Report->EncodedPdf, "labelbarcode_" . $data['idOrders'] . "_" . date("Ymdhis"));

        $pdfMerger = new PDFMerger;
        for ($i = 0; $i < $data['numLabels']; $i++) {
            $fileName = $this->saveReport($this->Report->EncodedPdf, "labelbarcode_" . $data['idOrders'] . "_" . date("Ymdhis") . "_" . $i);
            $aryReportNames[] = $fileName;
            $pdfMerger->addPDF($fileName, 'all');
        }
        $mergedLabelsFileName = "mergedLabels_" . date("Ymdhis");
        $encodedPdf = $pdfMerger->merge('string', $mergedLabelsFileName);
        $this->Report->EncodedPdf = $encodedPdf;

        //$this->saveReport($this->Report->EncodedPdf, "mergedLabels_" . date("Ymdhis"));

        foreach ($aryReportNames as $fileName) {
            unlink($fileName);
        }

        //return $fileName;
        return $this->Report;
    }

    protected function addLogEntry(array $logData = null) {
        //ResultLogDAO::addResultViewLogEntry($logData['userId'], $this->getIpAddress(), $logData['logOrderIds'], $logData['Conn']);
    }

} 