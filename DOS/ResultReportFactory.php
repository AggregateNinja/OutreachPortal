<?php
require_once 'ReportCreator.php';
require_once 'Report.php';
require_once 'PDFMerger/PDFMerger.php';
require_once 'DAOS/ResultLogDAO.php';
require_once 'GeneticReport.php';
require_once 'ReferenceLabReport.php';
require_once 'JasperReport.php';
require_once 'DAOS/PreferencesDAO.php';

class ResultReportFactory extends ReportCreator {
    public $OrderEntryDocumentsFilePath = "";
    public $OrderEntryDocumentsEnabled = false;
    public $AIEnabled = false;

    public function __construct(array $arySettings = null) {
        if ($arySettings != null) {
            if (array_key_exists("OrderEntryDocumentsFilePath", $arySettings)) {
                $this->OrderEntryDocumentsFilePath = $arySettings['OrderEntryDocumentsFilePath'];
            }
            if (array_key_exists("OrderEntryDocumentsEnabled", $arySettings)) {
                $this->OrderEntryDocumentsEnabled = $arySettings['OrderEntryDocumentsEnabled'];
            }
        }

        $aiEnabled = PreferencesDAO::getPreferenceByKey("AIEnabled");
        if ($aiEnabled != null && $aiEnabled instanceof Preference && $aiEnabled->value === 'true') {
            $this->AIEnabled = true;
        }


    }

    protected function factoryMethod(array $reportData, array $arySettings = null) {
        $userId = $reportData['userId'];
        $conn = $reportData['Conn'];
        $logOrderIds = $reportData['logOrderIds'];
        array_shift($reportData);
        array_shift($reportData);
        array_shift($reportData); // triple shift

        //echo "<pre>"; print_r($reportData); echo "</pre>";

        $hasMultipleReportTypes = false;
        $orderEntryDocumentsFilePath = "";
        if ($arySettings != null) {
            if (array_key_exists("HasMultipleReportTypes", $arySettings) && $arySettings['HasMultipleReportTypes'] == true) {
                $hasMultipleReportTypes = true;
            }
            if (array_key_exists("OrderEntryDocumentsFilePath", $arySettings)) {
                $orderEntryDocumentsFilePath = $arySettings['OrderEntryDocumentsFilePath'];
            }
        }

        if ($hasMultipleReportTypes) {
            $aryReportNames = array();
            $pdfMerger = new PDFMerger;
            foreach ($reportData as $row) {
                $report = $this->getReport($row);

                $currPdf = $report->EncodedPdf;
                $fileName = $this->saveReport($currPdf, "report" . $report->name);
                $aryReportNames[] = $fileName;
                $pdfMerger->addPDF($fileName, 'all');
            }

            $resultReport = "resultReport" . date("Ymdhis") . ".pdf";
            $encodedPdf = $pdfMerger->merge('string', $resultReport);

            $report = new Report(array("name" => $resultReport, "EncodedPdf" => $encodedPdf));

            $this->Report = $report;

            foreach ($aryReportNames as $fileName) {
                unlink($fileName);
            }

        } else {

            $this->Report = $this->getReport($reportData);
        }


        $logData = array(
            "userId" => $userId,
            "logOrderIds" => $logOrderIds,
            "Conn" => $conn
        );
        $this->addLogEntry($logData);

        //return $reportData;
        return $this->Report;
    }

    public function getReport(array $reportData) {

        $singleOrderId = true;
        /*if (count($reportData) > 1) {
            $singleOrderId = false;
        }*/

        $hasTranslational = false;
        $hasReference = false;
        $hasMultipleReference = false;
        $hasDocument = false;
        $currOrderId = $reportData[0]['idOrders'];
        $hasAIReport = false;
        for ($i = 0; $i < count($reportData); $i++) {
            if ($reportData[$i]['report'] != null && !empty($reportData[$i]['report']) && $reportData[$i]['active'] == true) {
                $hasTranslational = true;
            }
            if ($reportData[$i]['ReferenceLabReport'] != null && !empty($reportData[$i]['ReferenceLabReport']) && $reportData[$i]['active'] == true) {
                $hasReference = true;
            }

            if ($i != 0 && $reportData[$i]['idOrders'] == $currOrderId && $hasReference == true) {
                $hasMultipleReference = true;
            }

            if ($reportData[$i]['idOrders'] != $currOrderId) {
                $singleOrderId = false;
            }

            if ($reportData[$i]['docFileName'] != null && !empty($reportData[$i]['docFileName'])) {
                $hasDocument = true;
            }

            if ($this->AIEnabled && $reportData[$i]['idAILog'] != null && !empty($reportData[$i]['idAILog'])) {
                $hasAIReport = true;
            }

            $currOrderId  = $reportData[$i]['idOrders'];
        }

        $filePath = $this->OrderEntryDocumentsFilePath;
        if ($singleOrderId == true) { // single order
            //echo $reportData[0]['report'];

            $docFileName = $reportData[0]['docFileName'];
            $refReportName = $reportData[0]['refReportName'];





            $aryReportNames = array();
            $pdfMerger = new PDFMerger;


            if ($reportData[0]['format'] != "referenceLabReport") {
                $jasperReport = new JasperReport(array($reportData[0]));
                $jasperFileName = $this->saveReport($jasperReport->EncodedPdf, "jasper_" . $reportData[0]['idOrders']);
                $pdfMerger->addPDF($jasperFileName, 'all');
                $aryReportNames[] = $jasperFileName;
            }

            for ($i = 0; $i < count($reportData); $i++) {
                if ($hasTranslational) {
                    $geneticReport = new GeneticReport(array($reportData[$i]));
                    $geneticFileName = $this->saveReport($geneticReport->EncodedPdf, "translational_" . $reportData[$i]['idOrders'] . "_" . $i);
                    $pdfMerger->addPDF($geneticFileName, 'all');
                    $aryReportNames[] = $geneticFileName;
                }

                if ($hasReference && !$hasMultipleReference && $i == 0) {
                    $refReport = new ReferenceLabReport($reportData);
                    $refFileName = $this->saveReport($refReport->EncodedPdf, "reference_" . $reportData[0]['idOrders']);
                    $pdfMerger->addPDF($refFileName, 'all');
                    $aryReportNames[] = $refFileName;
                } else if ($hasMultipleReference) {
                    $refReport = new ReferenceLabReport(array($reportData[$i]));
                    $refFileName = $this->saveReport($refReport->EncodedPdf, "reference_" . $reportData[$i]['idOrders'] . "_" . $i);
                    $pdfMerger->addPDF($refFileName, 'all');
                    $aryReportNames[] = $refFileName;
                }

                if ($this->OrderEntryDocumentsEnabled && $i == 0) {
                    if (!empty(trim($docFileName)) && strpos(str_replace('.pdf', '', $docFileName), str_replace('.pdf', '', $refReportName)) === false) {
                        if (file_exists($filePath . $docFileName)) {
                            $docData = array(
                                "idOrders" => $reportData[0]['idOrders'],
                                "name" => str_replace(".pdf", "", $reportData[0]['docFileName']),
                                "EncodedPdf" => file_get_contents($filePath . $docFileName)
                            );
                            $webDoc = new Report($docData);
                            $docFileName = $this->saveReport($webDoc->EncodedPdf, "doc_" . $docData['idOrders']);
                            $aryReportNames[] = $docFileName;
                            $pdfMerger->addPDF($docFileName, 'all');
                        } else {
                            error_log("file not found: " . $filePath . $docFileName);
                        }
                    }
                }

                if ($this->AIEnabled && $hasAIReport) {
                    $aiReport = new JasperReport(array(array(
                        "name" => "AIInsightReport",
                        "filePath" => "AIInsightReport.jrxml",
                        "Parameters" => array("idOrders" => $reportData[0]['idOrders'])
                    )));
                    $aiFileName = $this->saveReport($aiReport->EncodedPdf, "ai_" . $reportData[0]['idOrders']);
                    $pdfMerger->addPDF($aiFileName, 'all');
                    $aryReportNames[] = $aiFileName;
                }
            }

            $mergedFileName = "resultReport" . date("Ymdhis") . ".pdf";
            $encodedPdf = $pdfMerger->merge('string', $mergedFileName);

            foreach ($aryReportNames as $rpt) {
                unlink($rpt);
            }

            $report = new Report(array("name" => $mergedFileName, "EncodedPdf" => $encodedPdf));

//            if ($hasTranslational == true && $hasReference == false) { // has translational report
//
//
//                /*$jasperReport = new JasperReport($reportData);
//                $translationalReport = new GeneticReport($reportData);
//
//                $timestamp = date("Ymdhis");
//                $jasperFileName = $this->saveReport($jasperReport->EncodedPdf, "jasper_" . "_" . $reportData[0]['idOrders']);
//                $translationalFileName = $this->saveReport($translationalReport->EncodedPdf, "translational_" . "_" . $reportData[0]['idOrders']);
//                $mergedFileName = "merged_translational_" . "_" . $reportData[0]['idOrders'] . "_" . $timestamp . "_" . $i;
//
//                $pdfMerger = new PDFMerger;
//                $pdfMerger->addPDF($jasperFileName, 'all');
//                $pdfMerger->addPDF($translationalFileName, 'all');
//
//                $mergedPdf = $pdfMerger->merge('string', $mergedFileName);
//
//                $report = new Report(array(
//                    "name" => $mergedFileName,
//                    "EncodedPdf" => $mergedPdf
//                ));
//                //echo "<pre>"; print_r($translationalReport); echo "</pre>";
//
//                unlink($jasperFileName);
//                unlink($translationalFileName);*/
//
//
//
//
//                $aryReportNames = array();
//                $pdfMerger = new PDFMerger;
//
//                $jasperReport = new JasperReport(array($reportData[0]));
//                $jasperFileName = $this->saveReport($jasperReport->EncodedPdf, "jasper_" . $reportData[0]['idOrders']);
//                $pdfMerger->addPDF($jasperFileName, 'all');
//                $aryReportNames[] = $jasperFileName;
//                for ($i = 0; $i < count($reportData); $i++) {
//                    $geneticReport = new GeneticReport(array($reportData[$i]));
//                    $geneticFileName = $this->saveReport($geneticReport->EncodedPdf, "translational_" . $reportData[$i]['idOrders'] . "_" . $i);
//
//                    $pdfMerger->addPDF($geneticFileName, 'all');
//                    $aryReportNames[] = $geneticFileName;
//                }
//
//                if ($this->OrderEntryDocumentsEnabled) {
//                    if (file_exists($filePath . $docFileName)) {
//                        $docData = array(
//                            "idOrders" => $reportData[0]['idOrders'],
//                            "name" => str_replace(".pdf", "", $reportData[0]['docFileName']),
//                            "EncodedPdf" => file_get_contents($filePath . $docFileName)
//                        );
//                        $webDoc = new Report($docData);
//                        $docFileName = $this->saveReport($webDoc->EncodedPdf, "doc_" . $docData['idOrders']);
//                        $aryReportNames[] = $docFileName;
//                        $pdfMerger->addPDF($docFileName, 'all');
//                    } else {
//                        error_log("file not found: " . $filePath . $docFileName);
//                    }
//                }
//
//
//
//                $mergedFileName = "resultReport" . date("Ymdhis") . ".pdf";
//                $encodedPdf = $pdfMerger->merge('string', $mergedFileName);
//
//                foreach ($aryReportNames as $rpt) {
//                    unlink($rpt);
//                }
//
//                $report = new Report(array("name" => $mergedFileName, "EncodedPdf" => $encodedPdf));
//
//
//            } else if ($hasReference == true && $hasMultipleReference == false && $hasTranslational == false) { // has single reference report
//                $pdfMerger = new PDFMerger;
//
//                if ($reportData[0]['format'] != "referenceLabReport") {
//                    $jasperReport = new JasperReport(array($reportData[0]));
//                    $timestamp = date("Ymdhis");
//                    $jasperFileName = $this->saveReport($jasperReport->EncodedPdf, "jasper_" . $reportData[0]['idOrders']);
//                    $pdfMerger->addPDF($jasperFileName, 'all');
//                }
//
//
//                $refReport = new ReferenceLabReport($reportData);
//                $refFileName = $this->saveReport($refReport->EncodedPdf, "reference_" . $reportData[0]['idOrders']);
//                $pdfMerger->addPDF($refFileName, 'all');
//
//
//
//                $docFileExists = false;
//                if ($this->OrderEntryDocumentsEnabled) {
//                    if (strpos(str_replace('.pdf', '', $docFileName), str_replace('.pdf', '', $refReportName)) === false) {
//                        if (file_exists($filePath . $docFileName)) {
//                            $docData = array(
//                                "idOrders" => $reportData[0]['idOrders'],
//                                "name" => str_replace(".pdf", "", $reportData[0]['docFileName']),
//                                "EncodedPdf" => file_get_contents($filePath . $docFileName)
//                            );
//                            $webDoc = new Report($docData);
//                            $docFileName = $this->saveReport($webDoc->EncodedPdf, "doc_" . $docData['idOrders']);
//                            $pdfMerger->addPDF($docFileName, 'all');
//                            $docFileExists = true;
//                        } else {
//                            error_log("file not found: " . $filePath . $docFileName);
//                        }
//                    }
//                }
//
//                $mergedFileName = "merged_reference_" . $jasperReport->name . "_" . $timestamp . ".pdf";
//
//                $mergedPdf = $pdfMerger->merge('string', $mergedFileName);
//
//                if ($docFileExists) {
//                    unlink($docFileName);
//                }
//                unlink($jasperFileName);
//                unlink($refFileName);
//
//                $report = new Report(array(
//                    "name" => $mergedFileName,
//                    "EncodedPdf" => $mergedPdf
//                ));
//            } else if ($hasReference == true && $hasMultipleReference == true && $hasTranslational == false) { // has multiple reference reports
//                $aryReportNames = array();
//                $pdfMerger = new PDFMerger;
//
//                if ($reportData[0]['format'] != "referenceLabReport") {
//                    $jasperReport = new JasperReport(array($reportData[0]));
//                    $jasperFileName = $this->saveReport($jasperReport->EncodedPdf, "jasper_" . $reportData[0]['idOrders']);
//                    $pdfMerger->addPDF($jasperFileName, 'all');
//                    $aryReportNames[] = $jasperFileName;
//                }
//
//
//                for ($i = 0; $i < count($reportData); $i++) {
//                    $refReport = new ReferenceLabReport(array($reportData[$i]));
//                    $refFileName = $this->saveReport($refReport->EncodedPdf, "reference_" . $reportData[$i]['idOrders'] . "_" . $i);
//
//                    $pdfMerger->addPDF($refFileName, 'all');
//                    $aryReportNames[] = $refFileName;
//                }
//
//                if ($this->OrderEntryDocumentsEnabled) {
//                    if (strpos(str_replace('.pdf', '', $docFileName), str_replace('.pdf', '', $refReportName)) === false) {
//                        if (file_exists($filePath . $docFileName)) {
//                            $docData = array(
//                                "idOrders" => $reportData[0]['idOrders'],
//                                "name" => str_replace(".pdf", "", $reportData[0]['docFileName']),
//                                "EncodedPdf" => file_get_contents($filePath . $docFileName)
//                            );
//                            $webDoc = new Report($docData);
//                            $docFileName = $this->saveReport($webDoc->EncodedPdf, "doc_" . $docData['idOrders']);
//                            $aryReportNames[] = $docFileName;
//                            $pdfMerger->addPDF($docFileName, 'all');
//                        } else {
//                            error_log("file not found: " . $filePath . $docFileName);
//                        }
//                    }
//                }
//
//
//                $mergedFileName = "resultReport" . date("Ymdhis") . ".pdf";
//                $encodedPdf = $pdfMerger->merge('string', $mergedFileName);
//
//                foreach ($aryReportNames as $rpt) {
//                    unlink($rpt);
//                }
//
//                $report = new Report(array("name" => $mergedFileName, "EncodedPdf" => $encodedPdf));
//            } else if ($hasReference == true &&  $hasTranslational == true) { // has translational & reference reports
//
//                $aryReportNames = array();
//                $pdfMerger = new PDFMerger;
//
//                if ($reportData[0]['format'] != "referenceLabReport") {
//                    // create the jasper report and add it to the PDFMerger
//                    $jasperReport = new JasperReport(array($reportData[0]));
//                    $jasperFileName = $this->saveReport($jasperReport->EncodedPdf, "jasper_" . $reportData[0]['idOrders']);
//                    $pdfMerger->addPDF($jasperFileName, 'all');
//                    $aryReportNames[] = $jasperFileName;
//                }
//
//
//
//                // create the translational report and add it to the PDFMerger
//                $translationalReport = new GeneticReport(array($reportData[0]));
//                $translationalFileName = $this->saveReport($translationalReport->EncodedPdf, "translational_" . "_" . $reportData[0]['idOrders']);
//                $pdfMerger->addPDF($translationalFileName, 'all');
//                $aryReportNames[] = $translationalFileName;
//                // create each reference report and add to PDFMerger
//                for ($i = 0; $i < count($reportData); $i++) {
//                    $refReport = new ReferenceLabReport(array($reportData[$i]));
//                    $refFileName = $this->saveReport($refReport->EncodedPdf, "reference_" . $reportData[$i]['idOrders']);
//
//                    $pdfMerger->addPDF($refFileName, 'all');
//                    $aryReportNames[] = $refFileName;
//                }
//
//                $docFileExists = false;
//                if ($this->OrderEntryDocumentsEnabled) {
//                    if (strpos(str_replace('.pdf', '', $docFileName), str_replace('.pdf', '', $refReportName)) === false) {
//                        if (file_exists($filePath . $docFileName)) {
//                            $docData = array(
//                                "idOrders" => $reportData[0]['idOrders'],
//                                "name" => str_replace(".pdf", "", $reportData[0]['docFileName']),
//                                "EncodedPdf" => file_get_contents($filePath . $docFileName)
//                            );
//                            $webDoc = new Report($docData);
//                            $docFileName = $this->saveReport($webDoc->EncodedPdf, "doc_" . $docData['idOrders']);
//                            $aryReportNames[] = $docFileName;
//                            $pdfMerger->addPDF($docFileName, 'all');
//                            $docFileExists = true;
//                        } else {
//                            error_log("file not found: " . $filePath . $docFileName);
//                        }
//                    }
//                }
//
//
//                // create the final merged report
//                $mergedFileName = "resultReport" . date("Ymdhis") . ".pdf";
//                $encodedPdf = $pdfMerger->merge('string', $mergedFileName);
//                // delete unneeded pdf files
//                foreach ($aryReportNames as $rpt) {
//                    unlink($rpt);
//                }
//                // create final Report object
//                $report = new Report(array("name" => $mergedFileName, "EncodedPdf" => $encodedPdf));
//
//            } else { // single report
//                $report = new JasperReport($reportData);
//
//                if ($this->OrderEntryDocumentsEnabled) {
//                    if (strpos(str_replace('.pdf', '', $docFileName), str_replace('.pdf', '', $refReportName)) === false) {
//                        if (file_exists($filePath . $docFileName)) {
//                            $pdfMerger = new PDFMerger;
//
//                            $jasperFileName = $this->saveReport($report->EncodedPdf, "jasper_" . $reportData[0]['idOrders']);
//                            $pdfMerger->addPDF($jasperFileName, 'all');
//
//                            $docData = array(
//                                "idOrders" => $reportData[0]['idOrders'],
//                                "name" => str_replace(".pdf", "", $reportData[0]['docFileName']),
//                                "EncodedPdf" => file_get_contents($filePath . $docFileName)
//                            );
//                            $webDoc = new Report($docData);
//                            $docFileName = $this->saveReport($webDoc->EncodedPdf, "doc_" . $docData['idOrders']);
//                            $pdfMerger->addPDF($docFileName, 'all');
//
//
//                            $mergedFileName = "resultReport" . date("Ymdhis") . ".pdf";
//                            $encodedPdf = $pdfMerger->merge('string', $mergedFileName);
//                            $report = new Report(array("name" => $mergedFileName, "EncodedPdf" => $encodedPdf));
//
//                            unlink($jasperFileName);
//                            unlink($docFileName);
//
//                        } else {
//                            error_log("file not found: " . $filePath . $docFileName);
//                        }
//                    }
//                }
//            }

        } else { // multiple orders

            $aryReportNames = array();
            $pdfMerger = new PDFMerger;
            $aryAddedOrderIds = array();

            for ($i = 0; $i < count($reportData); $i++) {
                $row = $reportData[$i];
                $docFileName = $row['docFileName'];
                $refReportName = $row['refReportName'];

                $jasperReport = new JasperReport(array($row));
                $jasperFileName = $this->saveReport($jasperReport->EncodedPdf, "jasper_" . $row['idOrders']);
                $aryReportNames[] = $jasperFileName;
                $pdfMerger->addPDF($jasperFileName, 'all');

                // GeneticReport
                if (array_key_exists("report", $row) && $row['report'] != null && !empty($row['report']) && !in_array($row['idOrders'], $aryAddedOrderIds)) {
                    $translationalReport = new GeneticReport(array($row));
                    $translationalFileName = $this->saveReport($translationalReport->EncodedPdf, "translational_" . $row['idOrders']);
                    $pdfMerger->addPDF($translationalFileName, 'all');
                    $aryReportNames[] = $translationalFileName;
                }

                // ReferenceLabReport
                if (array_key_exists("ReferenceLabReport", $row) && $row['ReferenceLabReport'] != null && !empty($row['ReferenceLabReport'])) {
                    $refReport = new ReferenceLabReport(array($row));
                    $refFileName = $this->saveReport($refReport->EncodedPdf, "reference_" . $row['idOrders']);
                    $pdfMerger->addPDF($refFileName, 'all');
                    $aryReportNames[] = $refFileName;
                }

                // WebOrderDocument
                if ($this->OrderEntryDocumentsEnabled && !in_array($row['idOrders'], $aryAddedOrderIds)) {
                    if (!empty(trim($docFileName)) && strpos(str_replace('.pdf', '', $docFileName), str_replace('.pdf', '', $refReportName)) === false) {
                        if (file_exists($filePath . $docFileName)) {
                            $docData = array(
                                "idOrders" => $row['idOrders'],
                                "name" => str_replace(".pdf", "", $row['docFileName']),
                                "EncodedPdf" => file_get_contents($filePath . $docFileName)
                            );
                            $webDoc = new Report($docData);
                            $docFileName = $this->saveReport($webDoc->EncodedPdf, "doc_" . $docData['idOrders']);
                            $aryReportNames[] = $docFileName;
                            $pdfMerger->addPDF($docFileName, 'all');
                        } else {
                            error_log("file not found: " . $filePath . $docFileName);
                        }
                    }
                }

                // AIInsightReport
                if ($this->AIEnabled && array_key_exists("idAILog", $row) && $row['idAILog'] != null && !empty($row['idAILog'])) {
                    $aiReport = new JasperReport(array(array(
                        "name" => "AIInsightReport",
                        "filePath" => "AIInsightReport.jrxml",
                        "Parameters" => array("idOrders" => $row['idOrders'])
                    )));
                    $aiFileName = $this->saveReport($aiReport->EncodedPdf, "ai_" . $row['idOrders']);
                    $pdfMerger->addPDF($aiFileName, 'all');
                    $aryReportNames[] = $aiFileName;
                }

                $aryAddedOrderIds[] = $row['idOrders'];
            }

            $resultReport = "resultReport" . date("Ymdhis") . ".pdf";
            $encodedPdf = $pdfMerger->merge('string', $resultReport);

            $report = new Report(array("name" => $resultReport, "EncodedPdf" => $encodedPdf));

            foreach ($aryReportNames as $cfileName) {
                unlink($cfileName);
            }

//            if ($hasTranslational == true && $hasReference == false) { // has translational reports
//                $aryReportNames = array();
//                $pdfMerger = new PDFMerger;
//
//                foreach ($reportData as $row) {
//                    $timestamp = date("Ymdhis");
//                    if (array_key_exists("report", $row) && $row['report'] != null && !empty($row['report'])) {
//                        $jasperReport = new JasperReport(array($row));
//                        $translationalReport = new GeneticReport(array($row));
//
//                        $jasperFileName = $this->saveReport($jasperReport->EncodedPdf, "jasper_" . $row['idOrders']);
//                        $translationalFileName = $this->saveReport($translationalReport->EncodedPdf, "translational_" . $row['idOrders']);
//
//                        $currPdfMerger = new PDFMerger;
//                        $currPdfMerger->addPDF($jasperFileName, 'all');
//                        $currPdfMerger->addPDF($translationalFileName, 'all');
//
//
//                        $mergedFileName = "merged_translational_" . $row['idOrders'];
//                        $mergedPdf = $currPdfMerger->merge('string', $mergedFileName);
//
//                        $report = new Report(array(
//                            "name" => $mergedFileName,
//                            "EncodedPdf" => $mergedPdf
//                        ));
//
//                        $mergedFileName = $this->saveReport($mergedPdf, $mergedFileName);
//
//                        unlink($jasperFileName);
//                        unlink($translationalFileName);
//
//                    } else {
//                        $jasperReport = new JasperReport(array($row));
//                        $mergedFileName = $this->saveReport($jasperReport->EncodedPdf, "jasper_" . $row['idOrders'] . "_" . $jasperReport->name);
//                    }
//
//                    $aryReportNames[] = $mergedFileName;
//                    $pdfMerger->addPDF($mergedFileName, 'all');
//                }
//
//                $resultReport = "resultReport" . date("Ymdhis") . ".pdf";
//                $encodedPdf = $pdfMerger->merge('string', $resultReport);
//
//                $report = new Report(array("name" => $resultReport, "EncodedPdf" => $encodedPdf));
//
//                foreach ($aryReportNames as $cfileName) {
//                    unlink($cfileName);
//                }
//
//            } else if ($hasTranslational == false && $hasMultipleReference == false && $hasReference == true) { // has single reference report
//                $aryReportNames = array();
//                $pdfMerger = new PDFMerger;
//
//                foreach ($reportData as $row) {
//
//                    if (array_key_exists("ReferenceLabReport", $row) && $row['ReferenceLabReport'] != null && !empty($row['ReferenceLabReport'])) {
//                        // curr order has ref report
//                        $currPdfMerger = new PDFMerger;
//
//                        if ($row['format'] != "referenceLabReport") {
//                            $jasperReport = new JasperReport(array($row));
//                            $jasperFileName = $this->saveReport($jasperReport->EncodedPdf, "jasper_" . $row['idOrders']);
//                            $currPdfMerger->addPDF($jasperFileName, 'all');
//                        }
//
//
//                        $refReport = new ReferenceLabReport(array($row));
//                        $refFileName = $this->saveReport($refReport->EncodedPdf, "reference_" . $row['idOrders']);
//                        $currPdfMerger->addPDF($refFileName, 'all');
//
//                        $mergedFileName = "merged_reference_" . $row['idOrders'];
//                        $mergedPdf = $currPdfMerger->merge('string', $mergedFileName);
//
//                        $report = new Report(array(
//                            "name" => $mergedFileName,
//                            "EncodedPdf" => $mergedPdf
//                        ));
//
//                        $mergedFileName = $this->saveReport($mergedPdf, $mergedFileName);
//
//                        if ($row['format'] != "referenceLabReport") {
//                            unlink($jasperFileName);
//                        }
//
//                        unlink($refFileName);
//
//
//                    } else { // no reference report for curr order
//                        $jasperReport = new JasperReport(array($row));
//                        $mergedFileName = $this->saveReport($jasperReport->EncodedPdf, "jasper_" . $row['idOrders'] . "_" . $jasperReport->name);
//                    }
//
//                    $aryReportNames[] = $mergedFileName;
//                    $pdfMerger->addPDF($mergedFileName, 'all');
//                }
//
//                $resultReport = "resultReport" . date("Ymdhis") . ".pdf";
//                $encodedPdf = $pdfMerger->merge('string', $resultReport);
//
//                $report = new Report(array("name" => $resultReport, "EncodedPdf" => $encodedPdf));
//
//                foreach ($aryReportNames as $cfileName) {
//                    unlink($cfileName);
//                }
//            } else if ($hasTranslational || $hasReference) { // this should handle all the situations
//
//                $aryReportNames = array();
//                $aryOrderReportNames = array();
//                $pdfMerger = new PDFMerger;
//
//                $currOrderId = $reportData[0]['idOrders'];
//                $mergeMultiRef = false;
//                $currPdfMerger = new PDFMerger;
//                $jasperFileName = "";
//                $refFileName = "";
//                $i = 0;
//                foreach ($reportData as $row) {
//                    $i++;
//                    $timestamp = date("Ymdhis");
//
//                    if (($currOrderId != $row['idOrders'] || $i == 1)) {
//                        if ($mergeMultiRef == true) { // the prev order had ref reports
//                            $mergedFileName = "merged_reference_" . $currOrderId;
//                            $mergedPdf = $currPdfMerger->merge('string', $mergedFileName);
//
////                            foreach ($aryOrderReportNames as $cOrderFName) {
////                                unlink($cOrderFName);
////                            }
//
//                            $report = new Report(array(
//                                "name" => $mergedFileName,
//                                "EncodedPdf" => $mergedPdf
//                            ));
//
//                            $mergedFileName = $this->saveReport($mergedPdf, $mergedFileName);
//                            $mergeMultiRef = false;
//                            $pdfMerger->addPDF($mergedFileName, 'all');
//                            $aryReportNames[] = $mergedFileName;
//                        }
//
//                        // curr order has ref report
//                        if ($row['format'] != "referenceLabReport") {
//                            $jasperReport = new JasperReport(array($row));
//                            $jasperFileName = $this->saveReport($jasperReport->EncodedPdf, "jasper_" . $row['idOrders']);
//                            $aryOrderReportNames[] = $jasperFileName;
//                        }
//
//
//                        $currPdfMerger = new PDFMerger;
//                        $currPdfMerger->addPDF($jasperFileName, 'all');
//
//                        if ($row['report'] != null && !empty($row['report'])) {
//                            $transReport = new GeneticReport(array($row));
//                            $transFileName = $this->saveReport($transReport->EncodedPdf, "trans_" . $row['idOrders']);
//                            $aryOrderReportNames[] = $transFileName;
//                            $currPdfMerger->addPDF($transFileName, 'all');
//
//                            //echo "genetic - ";
//                        }
//
//                        if (array_key_exists("ReferenceLabReport", $row) && $row['ReferenceLabReport'] != null && !empty($row['ReferenceLabReport'])) {
//                            $refReport = new ReferenceLabReport(array($row));
//                            $refFileName = $this->saveReport($refReport->EncodedPdf, "reference_" . $row['idOrders']);
//                            $aryOrderReportNames[] = $refFileName;
//                            $currPdfMerger->addPDF($refFileName, 'all');
//
//                            //echo "reference - ";
//                        }
//
//
//                        //echo "1 " . $row['idOrders'] . " - " . $currOrderId . "<br/>";
//
//                        $mergeMultiRef = true;
//
//
//
//
//                        if ($i == count($reportData)) { // this is the last row, so finish the job
//                            $mergedFileName = "merged_reference_" . $row['idOrders'];
//                            $mergedPdf = $currPdfMerger->merge('string', $mergedFileName);
//
//                            foreach ($aryOrderReportNames as $cOrderFName) {
//                                unlink($cOrderFName);
//                            }
//
//                            $report = new Report(array(
//                                "name" => $mergedFileName,
//                                "EncodedPdf" => $mergedPdf
//                            ));
//
//                            $mergedFileName = $this->saveReport($mergedPdf, $mergedFileName);
//
//                            $mergeMultiRef = false;
//
//                            $aryReportNames[] = $mergedFileName;
//                            $pdfMerger->addPDF($mergedFileName, 'all');
//                        }
//
//                    } else if ($currOrderId == $row['idOrders'] && array_key_exists("ReferenceLabReport", $row) && $row['ReferenceLabReport'] != null && !empty($row['ReferenceLabReport'])) {
//                        //echo "2 " . $row['idOrders'] . " - " . $currOrderId . "<br/>";
//
//                        // add ref report to curr order
//                        $refReport = new ReferenceLabReport(array($row));
//                        $refFileName = $this->saveReport($refReport->EncodedPdf, "reference_" . $row['idOrders']);
//                        $currPdfMerger->addPDF($refFileName, 'all');
//
//                        $aryOrderReportNames[] = $refFileName;
//
//                        if ($i == count($reportData)) { // this is the last row, so finish the job
//                            $mergedFileName = "merged_reference_" . $row['idOrders'];
//                            $mergedPdf = $currPdfMerger->merge('string', $mergedFileName);
//
//                            foreach ($aryOrderReportNames as $cOrderFName) {
//                                unlink($cOrderFName);
//                            }
//
//                            $report = new Report(array(
//                                "name" => $mergedFileName,
//                                "EncodedPdf" => $mergedPdf
//                            ));
//
//                            $mergedFileName = $this->saveReport($mergedPdf, $mergedFileName);
//
//                            $mergeMultiRef = false;
//
//                            $aryReportNames[] = $mergedFileName;
//                            $pdfMerger->addPDF($mergedFileName, 'all');
//                        }
//
//                    } else { // no reference report for curr order
//                        //echo "3 " . $row['idOrders'] . " - " . $currOrderId . "<br/>";
//
//                        if ($mergeMultiRef == true) {
//                            $mergedFileName = "merged_reference_" . $row['idOrders'];
//                            $mergedPdf = $currPdfMerger->merge('string', $mergedFileName);
//
//                            foreach ($aryOrderReportNames as $cOrderFName) {
//                                unlink($cOrderFName);
//                            }
//
//                            $report = new Report(array(
//                                "name" => $mergedFileName,
//                                "EncodedPdf" => $mergedPdf
//                            ));
//
//                            $mergedFileName = $this->saveReport($mergedPdf, $mergedFileName);
//
//                            $mergeMultiRef = false;
//
//                            $aryReportNames[] = $mergedFileName;
//                            $pdfMerger->addPDF($mergedFileName, 'all');
//                        } else {
//                            $jasperReport = new JasperReport(array($row));
//                            $mergedFileName = $this->saveReport($jasperReport->EncodedPdf, "jasper_" . $jasperReport->name);
//
//                            $aryReportNames[] = $mergedFileName;
//                            $pdfMerger->addPDF($mergedFileName, 'all');
//                        }
//                    }
//
//                    $currOrderId = $row['idOrders'];
//                }
//
//                $resultReport = "resultReport" . date("Ymdhis") . ".pdf";
//                $encodedPdf = $pdfMerger->merge('string', $resultReport);
//
//                $report = new Report(array("name" => $resultReport, "EncodedPdf" => $encodedPdf));
//
//                foreach ($aryReportNames as $cfileName) {
//                    unlink($cfileName);
//                }
//
//            } else {
//
//                if (array_key_exists("Parameters", $reportData[0])) {
//                    $strIdOrders = "";
//                    foreach($reportData as $row) {
//                        $strIdOrders .= $row['idOrders'] . ",";
//                    }
//                    $strIdOrders = substr($strIdOrders, 0, strlen($strIdOrders) - 1);
//                    $reportData[0]['Parameters']['idOrders'] = $strIdOrders;
//                }
//
//                $report = new JasperReport($reportData);
//            }
        }
        return $report;
    }

    protected function addLogEntry(array $logData = null) {
        ResultLogDAO::addResultViewLogEntry($logData['userId'], $this->getIpAddress(), $logData['logOrderIds'], $logData['Conn']);
    }

    public function __set($field, $value) {
        if ($field == "Report" && $value instanceof Report) {
            $this->Report = $value;
        }
    }
}