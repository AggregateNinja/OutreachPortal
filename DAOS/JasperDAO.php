<?php
if (!isset($_SESSION)) {
    session_start();
}
require_once 'DataObject.php';
require_once 'DOS/JasperReport.php';
require_once 'DataConnect.php';

/**
 * Description of JasperDAO
 *
 * @author Edd
 */
class JasperDAO extends DataObject {
    
    private $ViewOrderIds = array(); //array of orderId's to be viewed in a single pdf report
    
    private $DefaultReportType;

    private $Conn;

    private $UserId;

    private $Ip;
    
    public function __construct($userId, $ip, array $viewOrderIds = null) {
        $this->UserId = $userId;
        $this->Ip = $ip;

        $this->Conn = DataConnect::getConn();

        if ($viewOrderIds != null) {
            for ($i = 1; $i <= count($viewOrderIds); $i++) {
                $this->ViewOrderIds[$i] = $viewOrderIds[$i - 1];
            }
        }
        
        $sql = "
            SELECT value
            FROM " . self::TBL_PREFERENCES . " p
            WHERE p.key = ?";
        $data = parent::select($sql, array('DefaultResultReport'));
        $this->DefaultReportType = $data[0]['value'];
    }
    
    public static function getDefaultReportType(array $input = null) {
        $sql = "
            SELECT value
            FROM " . self::TBL_PREFERENCES . " p
            WHERE p.key = ?";

        if ($input != null && array_key_exists("Conn", $input) && $input['Conn'] instanceof mysqli) {
            $data = parent::select($sql, array('DefaultResultReport'), array("Conn" => $input['Conn']));
        } else {
            $data = parent::select($sql, array('DefaultResultReport'));
        }

        return $data[0]['value'];
    }
    
    public function getJasperReport(array $settings = null) {
        if (isset($settings) && !empty($settings) && array_key_exists("UseDefaultReportType", $settings) && $settings['UseDefaultReportType'] == true) {
            $sql = "
                SELECT  o.idOrders, r.number, r.name,
                        SUBSTRING(r.filePath, 21) AS 'filePath',
                        r.selectable, r.format,
                        gr.idpatients, gr.report, gr.created
                FROM " . self::TBL_ORDERS . " o 
                INNER JOIN " . self::TBL_REPORTTYPE . " r ON $this->DefaultReportType = r.number
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_GENETICREPORT . " gr ON o.idOrders = gr.idorders
                WHERE idOrders IN (";
        } else {
            $sql = "
                SELECT  o.idOrders, r.number, r.name,
                        SUBSTRING(r.filePath, 21) AS 'filePath',
                        r.selectable, r.format,
                        gr.idpatients, gr.report, gr.created
                FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                INNER JOIN " . self::DB_CSS . "." . self::TBL_REPORTTYPE . " r ON o.reportType = r.number  OR (o.reportType IS NULL AND number = $this->DefaultReportType)
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_GENETICREPORT . " gr ON o.idOrders = gr.idorders
                WHERE idOrders IN (";
        }
        
        foreach ($this->ViewOrderIds as $placeHolder => $orderId) {
            $sql .= "?, ";
        }
        $sql = substr($sql, 0, strlen($sql) - 2);
        $sql .= ")";
        $data = parent::select($sql, $this->ViewOrderIds, array("Conn" => $this->Conn));
        
        $fp = $data[0]['filePath'];
        $fpName = substr($fp, 0, strpos($fp, "."));
        
        $reportData = array(
        	"idOrders" => $data[0]['idOrders'],
        	"number" => $data[0]['number'],
        	"name" => $fpName,
        	"filePath" => $data[0]['filePath']
        );

        //$resultReport = new ResultReport($reportData);

        $jasperReport = new JasperReport($reportData, implode(",", $this->ViewOrderIds));
        return $jasperReport;        
    }    
    
    public function logResultView() {

        if ($this->Ip != null && !empty($this->Ip)) {
            $sql = "INSERT INTO " . self::TBL_LOG . " (userId, typeId, ip) VALUES (?, ?, ?)";
            $idLogs = parent::manipulate($sql, array($this->UserId, 3, $this->Ip), array("LastInsertId" => true));
        } else {
            $sql = "INSERT INTO " . self::TBL_LOG . " (userId, typeId) VALUES (?, ?)";
            $idLogs = parent::manipulate($sql, array($this->UserId, 3), array("LastInsertId" => true));
        }



    	$sql = "INSERT INTO " . self::TBL_LOGVIEWS . " (logId, orderId) VALUES ";
    	$qryInput = array();
    	
    	foreach ($this->ViewOrderIds as $orderId) {
    		$sql .= "(?, ?), ";
    		$qryInput[] = $idLogs;
    		$qryInput[] = $orderId;
    	}
    	$sql = substr($sql, 0, strlen($sql) - 2);
    	
    	parent::manipulate($sql, $qryInput, array("Conn" => $this->Conn));
    	return true;
    }

    public function __set($field, $value) {
        if ($field == "ViewOrderIds") {
            $this->ViewOrderIds = $value;
        }
    }
}

?>
