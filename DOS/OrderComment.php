<?php
require_once 'BaseObject.php';

/**
 * Description of OrderComment
 *
 * @author Edd
 */
class OrderComment extends BaseObject {
    protected $Data = array (
        "idorderComment" => "",
        "orderId" => "",
        "comment" => "",
        "advancedOrder" => 0
    );
    
    public function __construct($data) {
        parent::__construct($data);
    }
    
    public function __isset($field) {
        $isset = parent::__isset($field);
            
        if (!$isset) {
            if ($field == "OrderComment" && isset($this->Data) && is_array($this->Data) && array_key_exists("idorderComment", $this->Data)) {
                $isset = true;
            }
        }
        
        return $isset;
    }
    
}

?>
