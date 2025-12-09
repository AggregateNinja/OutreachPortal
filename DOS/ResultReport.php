<?php
if (!isset($_SESSION)) {
    session_start();
}
require_once 'BaseObject.php';
/**
 * - set up the input array
 * - handle the descrepencies for when multiple reports are being viewed of different types and 
 * when view all is selected and there are too many (more than 100) reports
 *
 * @author Edd
 */
class ResultReport extends BaseObject {
    
    protected $Data = array (
        "idreportType" => "",
        "number" => "",
        "name" => "",
        "filePath" => "",
        "selectable" => true,
        "format" => ""
    );       
    
    public function __construct(array $data) {
        foreach ($data as $key => $value) { // enables the class to be conveniently set up from an sql data row
            //echo $key . ": " . $value . "<br/>";
            if (array_key_exists($key, $this->Data)) {
                $this->Data[$key] = $value;
            }
        }
    }
    
    public function __get ($field) {
        if (array_key_exists($field, $this->Data)) {
            return $this->Data[$field];
        }
    }
    
}

?>
