<?php
require_once 'LogEntry.php';

class AdminLogEntry extends LogEntry {
    
    protected $AdminLogData = array(
        "idAdminLogs" => "",
        "logId" => "",
        "adminUserId" => "",
        "userTypeId" => "",
        "email" => "",
        "action" => "",
        
        // WebAdminLogTypes
        "idAdminLogTypes",
        "name" => "",
        "description" => ""
    );
    
    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);
            
            foreach ($data as $field => $value) {
                if (array_key_exists($field, $this->AdminLogData)) {
                    $this->Data[$field] = $value;
                }
            }
        }        
    }
    
    public function __get($field) {
        $value = parent::__get($field);
        if (empty($value)) {
            if (array_key_exists($field, $this->AdminLogData)) {
                $value = $this->AdminLogData[$field];
            } else if ($field == "AdminLogData") {
                $field = $this->AdminLogData;
            }
        }        
        return $value;
    }
    
    public function __set($field, $value) {
        $done = parent::__set($field, $value);        
        if (!$done) {
            if (is_array($field)) {
                foreach ($field as $fieldName => $fieldValue) {
                    if (array_key_exists($fieldName, $this->AdminLogData)) {
                        $this->AdminLogData[$fieldName] = $fieldValue;
                        $done = true;
                    }
                }
            } else if (array_key_exists($field, $this->AdminLogData)) {
                $this->AdminLogData[$field] = $value;
                $done = true;
            }
        }
        return $done;
    }
    
}

?>
