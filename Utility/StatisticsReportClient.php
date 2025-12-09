<?php
require_once 'DOS/StatisticsReportFactory.php';

class StatisticsReportClient {

    public function __construct(array $data, $userId) {

        $factory = new StatisticsReportFactory();

        $reportData = $data;
        $reportData['userId'] = $userId;

        $arySettings = array("ExcludeHeaders" => true, "Base64Encode" => true);
        $factory->startFactory($reportData, $arySettings);
    }
} 