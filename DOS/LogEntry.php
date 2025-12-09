<?php
require_once 'BaseObject.php';

class LogEntry extends BaseObject {
    protected $Data = array (
        "idLogs" => "",
        "userId" => "",
        //"typeId" => "",
        "logDate" => "",
        
        //LogEntryTypes fields
        "idTypes" => "",
        "logTypeName" => "",
        "description" => ""
    );
    
    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);
        }
    }
    
}

?>