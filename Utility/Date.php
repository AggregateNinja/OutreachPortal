<?php
/**
 * Description of Date
 *
 * @author Edd
 */
class Date {
    private $DateString;
    private $Month;
    private $Day;
    private $Year;
    
    public function __construct($dateString) {
        $this->DateString = $dateString;
        $this->Month = date("m", strtotime($dateString));
        $this->Day = date("d", strtotime($dateString));
        $this->Year =  date("Y", strtotime($dateString));
    }
    
    public function __get($field) {
        return $this->$field;    
    }    
    
    function isDate() {
        if ($this->Month == 01 && $this->Day == 01 && $this->Year == 1970) {
            return false;
        } else {
            return checkdate($this->Month, $this->Day, $this->Year);
        }
    }
}

?>
