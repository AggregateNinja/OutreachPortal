<?php

require_once 'Test.php';

/**
 * Description of PanelTest
 *
 * @author Edd
 */
class PanelTest extends Test {
    protected $Panel = array (
        "idpanels" => "",
        "subtestId" => "",
        "subtestOrder" => ""
    );
   
    public function __construct($data) {
        parent::__construct($data);
        
        foreach ($data as $field => $value) {
            if (array_key_exists($field, $this->Panel)) {
                $this->Panel[$field] = $value;
            }
        }        
    }
    
    public function __get($key) {
        $field = parent::__get($key);
        
        if(empty($field)) {
            if (array_key_exists($key, $this->Panel)) {
                $field = $this->Panel[$key];
            } else if ($key == "Panel") {
                $field = $this->Panel;
            }
        }
        
        return $field;
    }
}

?>
