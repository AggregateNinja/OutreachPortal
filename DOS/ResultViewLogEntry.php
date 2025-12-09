<?php
require_once 'LogEntry.php';

class ResultViewLogEntry extends LogEntry {
    protected $ViewData = array (
        "idViews" => "",
        "logId" => "",
        "orderId" => ""
    );
    
    public function __construct(array $data) {
        parent::__construct($data);
        
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->ViewData)) {
                $this->ViewData[$key] = $value;
            }
        }
    }
    
    public function __get($key) {
        $value = parent::__get($key);
        
        if (empty($value)) {
            if ($key == "ViewData") {
                $value = $this->ViewData;
            } else if (array_key_exists($key, $this->ViewData)) {
                $value = $this->ViewData[$key];
            }
        }
        
        return $value;
    }
    
}

?>

