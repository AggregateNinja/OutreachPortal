<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 9/22/14
 * Time: 4:39 PM
 */

require_once 'DOS/RequisitionReportFactory.php';

class RequisitionReportClient implements IConfig {

    public function __construct($idOrders, $userId, $type, $isReceipted) {

        $factory = new RequisitionReportFactory();

        $reportData = array(
            "idOrders" => $idOrders,
            "userId" => $userId,
            "type" => $type,
            "isReceipted" => $isReceipted,
            "SpecimenDateColHeader" => self::SpecimenDateColHeader,
            "HasESignatureOnReq" => self::HasESignatureOnReq,
            "PrintAllTestsOnReq" => self::PrintAllTestsOnReq
        );

        $arySettings = array("ExcludeHeaders" => true, "Base64Encode" => true);
        $factory->startFactory($reportData, $arySettings);
    }
}