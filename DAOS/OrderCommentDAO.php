<?php
require_once 'DataObject.php';
require_once 'DOS/OrderComment.php';
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of OrderCommentDAO
 *
 * @author Edd
 */
class OrderCommentDAO extends DataObject {
    public static function insertOrderComment(OrderComment $orderComment, mysqli $conn) {
        $sql = "INSERT INTO " . self::DB_CSS_WEB . "." . self::TBL_ORDERCOMMENT . " (orderId, comment) VALUES (?, ?)";
        return parent::manipulate($sql, array($orderComment->orderId, $orderComment->comment), array("Conn" => $conn));
    }
    public static function deleteOrderComment($idOrders) {
        $sql = "DELETE FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDERCOMMENT . " WHERE orderId = ?";
        parent::manipulate($sql, array($idOrders), array("ConnectToWeb" => true));
        return true;
    }
    public static function getOrderComment($idOrders, array $settings = null) {
    	$sqlSettings = array("ConnectToWeb" => true);
    	if ($settings != null) {
    		if (array_key_exists("Conn", $settings) && $settings['Conn'] instanceof mysqli) {
    			$sqlSettings['Conn'] = $settings['Conn'];
    			unset($sqlSettings['ConnectToWeb']);
    		}
    	}
    	
        $sql = "
            SELECT idOrderComment, orderId, comment
            FROM " . self::TBL_ORDERCOMMENT . " 
            WHERE orderId = ?";
        $data = parent::select($sql, array($idOrders), $sqlSettings);
        if (count($data) > 0) {
            $oc = new OrderComment($data[0]);
            return $oc;
        }
        return false;
    }
}

?>
