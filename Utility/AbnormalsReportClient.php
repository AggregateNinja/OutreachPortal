<?php
require_once 'DOS/AbnormalsReportFactory.php';

class AbnormalsReportClient {

    public function __construct(array $data, $userId) {

        $factory = new AbnormalsReportFactory();

        $reportData = $data;
        $reportData['userId'] = $userId;

        $arySettings = array("ExcludeHeaders" => true, "Base64Encode" => true);
        $factory->startFactory($reportData, $arySettings);
    }
}