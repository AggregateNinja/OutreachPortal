<?php
//ini_set('memory_limit', '-1');
/**
 *  This class is the root parent to all DOS classes.
 * 
 *  Its primary function is to provide magic methods for its child classes.
 *  http://www.php.net/manual/en/language.oop5.magic.php
 * 
 *  All objects have a $Data array for the main class
 * variables, which is inherited from this class.
 * 
 *  The object class also provides a constructor for 
 * initializing the $Data array, a getter method, a setter method,
 * and a toString method.
 *
 * @author Edd
 */
abstract class BaseObject {
    protected $Data = array();
    protected $GenericErrorMessage = "There was a problem processing this request. Please contact support.";
    private $DateDelimiter = "/";

    public $SiteUrl = "";
    public $Logo = "";
    public $LabName = "";

    public function __construct(array $data = null) {
        if ($data != null && count($data) > 0) {
            foreach ($data as $key => $value) {
                if (array_key_exists($key, $this->Data))
                    $this->Data[$key] = $value;
            }
        }

        if ((isset($_SERVER['SSL_TLS_SNI']) && !empty($_SERVER['SSL_TLS_SNI']) && $_SERVER['SSL_TLS_SNI'] === 'cardiopathoutreach.com')
            || (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'cardiopathoutreach.com')) {
            $this->SiteUrl = "https://cardiopathoutreach.com/outreach/";
            $this->Logo = "cardioPathLogo.png";
            $this->LabName = "CardioPath LLC";
        } else if ((isset($_SERVER['SSL_TLS_SNI']) && !empty($_SERVER['SSL_TLS_SNI']) && $_SERVER['SSL_TLS_SNI'] === 'cardiotropicoutreach.com')
            || (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'cardiotropicoutreach.com')) {
            $this->SiteUrl = "https://cardiotropicoutreach.com/outreach/";
            $this->Logo = "cardioLogo.png";
            $this->LabName = "Cardio Tropic Labs";
        }
    }
    
    public function __get($key) {
        if (array_key_exists($key, $this->Data)) {
            return $this->Data[$key];
        } else if ($key == "Data") {
            return $this->Data;
        } else if ($key == "GenericErrorMessage") {
            return $this->GenericErrorMessage;

        } else {
            return "";
        }
//        else {
//            die("Get Parent Field not found - $key");
//        }
    }
    
    public function __set($field, $value) {
        if (array_key_exists($field, $this->Data)) {
            $this->Data[$field] = $value;
            return true;
        } else if ($field == "Data") {
            foreach ($value as $currField => $currValue) {
                if (array_key_exists($currField, $this->Data)) {
                    $this->Data[$currField] = $currValue;
                }
            }
            return true;
        } else {            
            //die("Set Parent Field not found");
            return false;
        }
    }
    
    public function __isset($name) {
        if (array_key_exists($name, $this->Data)) {
            return true;
        } 
        return false;
    }
    
    public function setAll(array $data) {
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->Data))
                $this->Data[$key] = $value;
        }      
    }
    
//    public function setData($data) {
//        foreach ($data as $key => $value) {
//            if (array_key_exists($key, $this->Data))
//                $this->Data[$key] = $value;
//        }        
//    }
    
    public function __toString() {
        $strObject = "<strong>Data:</strong> <br />";
        foreach ($this->Data as $key => $value) {
            $strObject .= $key . ": " . $value . "<br />";
        }
        $strObject .= "<br />";
        return $strObject;
    }
    
    protected function calcAge() {
        $bday = new DateTime($this->Data['dob']);
        // $today = new DateTime('00:00:00'); - use this for the current date
        $today = new DateTime(date("Y-m-d h:i:s")); // for testing purposes

        $diff = $today->diff($bday);
        if (is_numeric($diff->y)) {
            return $diff->y;
        }
        return "";
    }
    
    protected function isValidDate($date, $format = 'Y-m-d') {
    	
    	$d = DateTime::createFromFormat($format, $date);
    	
    	if ($d && $d->format($format) == $date) {
    		return true;
    	}
    	return false;
    }
    
    // converts dates in the format of 'mm/dd/yyyy' or 'mm/dd/yyyy hh:mm:ss' to a MySql friendly date string
    // returns null if it is an invalid date. 
    protected function formatDate($date, $inputFormat = 'm/d/Y', $outputFormat = 'Y-m-d') {
    	
    	if ($this->isValidDate($date, $inputFormat)) {
    			
    		$objDate = DateTime::createFromFormat($inputFormat, $date);
    		return $objDate->format($outputFormat);
    	}    		
    	
    	return null; 	
    }
    
    protected function calcHeight() {
        $aryHeight = array();
        if ($this->Data['height'] < 12) {
            $aryHeight[0] = 0;
            $aryHeight[1] = $this->Data['height'];
        } else {
            $decFeet = $this->Data['height'] / 12;
            $decPos = strpos($decFeet, ".");
            if (!$decPos) {
                $feet = $decFeet;
            } else {
                $feet = substr($decFeet, 0, $decPos);
            }
            $aryHeight[0] = $feet;
            $aryHeight[1] = $this->Data['height'] - ($aryHeight[0] * 12);
        }
        
        return $aryHeight;
    }
    
    public function isDecimal($val) {
    	if (is_numeric($val) && floor($val) != $val) {
    		return true;
    	}
    	return false;    	
    }
}

?>
