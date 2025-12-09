<?php
require_once 'DataObject.php';
require_once 'DOS/ReportType.php';

class ReportTypeDAO extends DataObject {
    public static function getReportTypes (array $settings = null) {

        $sql = "SELECT idreportType, number, name, filePath FROM " . self::DB_CSS . "." . self::TBL_REPORTTYPE;

        $aryInput = null;
        if ($settings != null && is_array($settings) && array_key_exists("selectable", $settings)) {
            $sql .= " WHERE selectable = ?";
            $aryInput = array($settings['selectable']);
        }

        $data = parent::select($sql, $aryInput, $settings);
        $aryReportTypes = array();
        foreach ($data as $row) {
            $reportType = new ReportType($row);
            $aryReportTypes[$row['idreportType']] = $reportType;
        }
        return $aryReportTypes;
    }
    public static function getReportType($idreportType) {
        $sql = "SELECT idreportType, number, name, filePath FROM " . self::DB_CSS . "." . self::TBL_REPORTTYPE . " WHERE idreportType = ?";
        $data = parent::select($sql, array($idreportType));
        if (count($data) > 0) {
            return new ReportType($data[0]);
        }
        return false;
    }
}
?>
