 <?php
require_once 'DOS/ResultReportFactory.php';
require_once 'DAOS/ReportDAO.php';
require_once 'Utility/IConfig.php';

class ResultReportClient implements IConfig {

    private $ResultReportFactory;

    public function __construct($userId, $viewMultiple, array $viewOrderIds, $downloadReport = false, $reportType = null, $orderBy = "orderDate", $direction = "DESC") {
        $arySettings = array();
        if ($downloadReport) {
            $arySettings['DownloadReport'] = true;
        }

        $rDAO = new ReportDAO($userId, null, $orderBy, $direction);
        $this->ResultReportFactory = new ResultReportFactory(array(
            'OrderEntryDocumentsFilePath' => self::OrderEntryDocumentsFilePath,
            'OrderEntryDocumentsEnabled' => self::OrderEntryDocumentsEnabled
        ));

        $reportData = array(
            "userId" => $userId,
            "Conn" => $rDAO->Conn,
            "logOrderIds" => null
        );

        if ($viewMultiple == true && count($viewOrderIds) > 1) { // viewing different report types in same PDF
            $logOrderIds = array();
            foreach ($viewOrderIds as $currReportType => $aryOrderIds) {
                foreach ($aryOrderIds as $id) {
                    $logOrderIds[] = $id;
                }

                $rDAO->ViewOrderIds = $aryOrderIds;
                //$reportData[] = $rDAO->getReport();
                $aryData = $rDAO->getReport();

                /*if (array_key_exists("reportType", $_SESSION)) {
                    $rowCount = count($aryData);
                    for ($i = 0; $i < $rowCount; $i++) {
                        $aryData[$i]['name'] = $_SESSION['reportType'];
                        $aryData[$i]['filePath'] = $_SESSION['reportType'] . ".jrxml";
                        $aryData[$i]['Parameters'] = array(
                            "idOrders" => $aryData[$i]['idOrders'],
                            "logoFileName" => "blankImage.png"
                        );
                    }
                }*/

                if ($reportType != null) {
                    $rowCount = count($aryData);
                    for ($i = 0; $i < $rowCount; $i++) {
                        $aryData[$i]['name'] = "BloodWellnessReport";
                        $aryData[$i]['filePath'] = "BloodWellnessReport.jrxml";
                        $aryData[$i]['Parameters'] = array(
                            "idOrders" => $aryData[$i]['idOrders'],
                            "logoFileName" => "blankImage.png"
                        );
                    }
                }
                $reportData[] = $aryData;
            }

            $reportData['logOrderIds'] = $logOrderIds;

            if (count($reportData) > 0) {

                $arySettings['HasMultipleReportTypes'] = true;
                if (!$downloadReport) {
                    $arySettings['ExcludeHeaders'] = true;
                    $arySettings['Base64Encode'] = true;
                }

                $this->ResultReportFactory->startFactory($reportData, $arySettings);
            }

        } else {

            if ($viewMultiple) { // view multiple all same report type
                $arrayKeys = array_keys($viewOrderIds);
                $viewOrderIds = $viewOrderIds[$arrayKeys[0]];
            }

            $rDAO->ViewOrderIds = $viewOrderIds;
            $reportData['logOrderIds'] = $viewOrderIds;
            $arySettings = $rDAO->getReport();

            if ($reportType != null) {
                if ($reportType == 2) {
                    $arySettings[0]['name'] = "DrugConfirmatory";
                    $arySettings[0]['filePath'] = "DrugConfirmatory.jrxml";
                    $arySettings[0]['Parameters'] = array(
                        "idOrders" => $arySettings[0]['idOrders']
                    );
                }

                /*if (!$viewMultiple) {
                    $arySettings[0]['name'] = "BloodWellnessReport";
                    $arySettings[0]['filePath'] = "BloodWellnessReport.jrxml";
                    $arySettings[0]['Parameters'] = array(
                        "idOrders" => $arySettings[0]['idOrders'],
                        "logoFileName" => "blankImage.png"
                    );
                } else {
                    $arySettingCount = count($arySettings);
                    for ($i = 0; $i < $arySettingCount; $i++) {
                        $arySettings[$i]['name'] = "BloodWellnessReport";
                        $arySettings[$i]['filePath'] = "BloodWellnessReport.jrxml";
                        $arySettings[$i]['Parameters'] = array(
                            "idOrders" => $arySettings[$i]['idOrders'],
                            "logoFileName" => "blankImage.png"
                        );
                    }
                }*/
            }

            $reportData = array_merge($reportData, $arySettings);

            //error_log(implode(", ", $arySettings));
            //echo "<pre>"; print_r($viewOrderIds); echo "</pre>";

            if (count($reportData) > 0) {
                if (!$downloadReport) {
                    $arySettings['ExcludeHeaders'] = true;
                    $arySettings['Base64Encode'] = true;
                }

                if ($downloadReport) {
                    $arySettings['DownloadReport'] = true;
                }

                $this->ResultReportFactory->startFactory($reportData, $arySettings);

            }
        }
    }
}