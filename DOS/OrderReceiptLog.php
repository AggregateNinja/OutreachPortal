<?php
require_once 'BaseObject.php';

class OrderReceiptLog extends BaseObject {
    protected $Data = array (
        "idWebOrderReceiptLog" => "",
        "webAccession" => "",
        "webUser" => "",
        "webCreated" => "",
        "idOrders" => "",
        "user" => "",
        "receiptedDate" => ""
    );
    
    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);
        }
    }
}


?>
