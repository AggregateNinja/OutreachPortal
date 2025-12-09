<?php
if (!isset($_SESSION)) {
    session_start();
}
//require_once "jasper_bu/resources/jasper-rest/client/JasperClient.php";
require_once 'Report.php';
require_once 'jasper/src/Jaspersoft/Client/Client.php';
require_once 'IJasperServer.php';

/**
 * Description of Jasper
 *
 * @author Edd
 */
class JasperReport extends Report implements IJasperServer {
    private $JasperData = array (
        "ReportPath" => "",
        "ReportName" => "",
        "Parameters" => array(),
        "OutputType" => ""
    );

    private $Client;

    public function __construct(array $data, array $settings = null) {
        parent::__construct($data[0]);

        $outreachUsageSite = false;
        if ($settings != null) {
            if (array_key_exists("OutreachUsageSite", $settings) && $settings['OutreachUsageSite'] == true) {
                $outreachUsageSite = true;
            }
        }

        $fp = $data[0]['filePath'];
        $filePath = substr($this->Data['filePath'], 0, strpos($this->Data['filePath'], "."));
        $this->JasperData['ReportPath'] = "/reports/" . $filePath;
        $this->JasperData['ReportName'] = $this->Data['name'];
        $this->JasperData['OutputType'] = "pdf";

        if (array_key_exists("Parameters", $data[0])) {


            $this->JasperData['Parameters'] = $data[0]['Parameters'];

        } else {
            $aryIdOrders = array();
            foreach ($data as $reportData) {
                $aryIdOrders[] = $reportData['idOrders'];
            }
            $this->JasperData['Parameters']['idOrders'] = implode(",", $aryIdOrders);
        }


        if (!$outreachUsageSite) {
            $this->Client = new \Jaspersoft\Client\Client(
                self::JASPER_HOST, // Hostname
                self::JASPER_USERNAME, // Username
                self::JASPER_PASSWORD, // Password
                self::JASPER_BASEURL // Base URL
            );
        } else {
            // sometimes the connection values in IJasperServer could be different when developing or deploying to a outreach site
            // but the outreach usage site will always use these
            $this->Client = new \Jaspersoft\Client\Client(
                '10.0.0.66:8080/jasperserver', // Hostname
                'cssadmin', // Username
                'css2015outreach', // Password
                '/jasperserver' // Base URL
            );
        }

        //error_log(implode(", ", $this->JasperData));

        $reportService = $this->Client->reportService();
        $report = $reportService->runReport(
            $this->JasperData['ReportPath'],
            $this->JasperData['OutputType'],
            null, null,
            $this->JasperData['Parameters'],
            false, false, true, false, null
        );

        $this->EncodedPdf = $report;
    }

    public function runReport() {
        $reportService = $this->Client->reportService();
        $report = $reportService->runReport(
            $this->JasperData['ReportPath'],
            $this->JasperData['OutputType'],
            null, null,
            $this->JasperData['Parameters'],
            false, false, true, false, null
        );
        /*$this->EncodedPdf = array(
            "<b>Client:</b>" => $this->Client,
            "<b>Report Service:</b>" => $reportService,
            "<b>Report:</b>" => $report
        );*/
        $this->EncodedPdf = $report;
    }

    public function __get($field) {
        $value = parent::__get($field);
        if ($value == null) {
            if (array_key_exists($field, $this->JasperData)) {
                $value = $this->JasperData[$field];
            } else {
                $value = parent::__get($field);
            }
        }

        return $value;
    }
}
?>
