<?php
require_once 'DOS/ManifestReportFactory.php';

class ManifestReportClient {

    public function __construct(array $data, $userId) {

        $factory = new ManifestReportFactory();

        $reportData = $data;
        $reportData['userId'] = $userId;

        $arySettings = array("ExcludeHeaders" => true, "Base64Encode" => true);
        $factory->startFactory($reportData, $arySettings);
    }

} 