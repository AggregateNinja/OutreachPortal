<?php
require_once 'BaseObject.php';
require_once 'Insurance.php';

/**
 * Description of Subscriber
 *
 * @author Edd
 */
class Subscriber extends BaseObject {
    protected $Data = array( 
        "idSubscriber" => "",
        "arNo" => "",
        "lastName" => "",
        "firstName" => "",
        "middleName"  => "",
        "sex" => "",
        "ssn"  => "",
        "dob" => "",
        "addressStreet" => "",
        "addressStreet2" => "",
        "addressCity" => "",
        "addressState" => "",
        "addressZip" => "",
        "phone" => "",
        "workPhone" => "",
        "insurance" => "",
        "secondaryInsurance" => "",
        "policyNumber" => "",
        "groupNumber" => "",
        "secondaryPolicyNumber" => "",
        "secondaryGroupNumber" => "",
        "medicareNumber" => "",
        "medicaidNumber" => "",
        "age" => "" // calculated
    );
    private $Insurance = null;
    private $SecondaryInsurance = null;
    
    // 0 => css schema, 1 => cssweb schema
    private $SubscriberSource; // used when processing entry orders to determine whether the subscriber came from the css or cssweb database
    //private $SqlFormat = "Y-m-d H:i:s";
    //private $UserFormat = "m/d/Y";
    public function __construct(array $data = null, array $settings = null) {
    	$inputDateFormat = 'm/d/Y';
    	$outputDateFormat = 'Y-m-d';
    	if ($settings != null) {
	    	if (array_key_exists("InputDateFormat", $settings) && !empty($settings['InputDateFormat'])) {
	    		$inputDateFormat = $settings['InputDateFormat'];
	    	}
	    	if (array_key_exists("OutputDateFormat", $settings) && !empty($settings['OutputDateFormat'])) {
	    		$outputDateFormat = $settings['OutputDateFormat'];
	    	}
    	}
    	
        if ($data != null) {
            parent::__construct($data);
            if (isset($this->Data)) {
                if (array_key_exists("dob", $this->Data) && !empty($this->Data['dob'])) {
                	//echo "<pre>"; print_r($data); echo "</pre>";
                    $this->Data['age'] = $this->calcAge();
                    $this->Data['dob'] = $this->formatDate($this->Data['dob'], $inputDateFormat, $outputDateFormat);
                }
            }
            if (array_key_exists("idinsurances", $data) && $data['idinsurances'] != null && !empty($data['idinsurances'])) {
                $this->Insurance = new Insurance($data);
            }
            if (array_key_exists("secondaryInsurance", $data) && $data['secondaryInsurance'] != null && !empty($data['secondaryInsurance'])) {
                $secondaryInsuranceName = "";
                if (array_key_exists("secondaryInsuranceName", $data) && isset($data['secondaryInsuranceName']) && !empty($data['secondaryInsuranceName'])) {
                    $secondaryInsuranceName = $data['secondaryInsuranceName'];
                }
                $this->SecondaryInsurance = new Insurance(array(
                    "idinsurances" => $data['secondaryInsurance'],
                    "name" => $secondaryInsuranceName
                ));
            }

            if (array_key_exists("subscriberNo", $data)) {
                $this->Data['arNo'] = $data['subscriberNo'];
            }

            $phone = "";
            $search = array(" ", "(", ")", "-");
            if (array_key_exists("phone", $this->Data) && !empty($this->Data['phone'])) {
                $phone = str_replace($search, "", $this->Data['phone']);
            } else if (array_key_exists("subscriberPhone", $this->Data) && !empty($this->Data['subscriberPhone'])) {
                $phone = str_replace($search, "", $this->Data['subscriberPhone']);
            }
            if (strlen($phone) == 10) {
                $this->Data['phone'] = "(" . substr($phone, 0, 3) . ")" . substr($phone, 3, 3) . "-" . substr($phone, 6, 4);
            }

            $workPhone = "";
            if (array_key_exists("workPhone", $this->Data) && !empty($this->Data['workPhone'])) {
                $workPhone = str_replace($search, "", $this->Data['workPhone']);
            } else if (array_key_exists("subscriberWorkPhone", $this->Data) && !empty($this->Data['subscriberWorkPhone'])) {
                $workPhone = str_replace($search, "", $this->Data['subscriberWorkPhone']);
            }
            if (strlen($workPhone) == 10) {
                $this->Data['workPhone'] = "(" . substr($workPhone, 0, 3) . ")" . substr($workPhone, 3, 3) . "-" . substr($workPhone, 6, 4);
            }

        }
    }
    
    public function __isset($field) {
        $isset = parent::__isset($field);
        
        if (!$isset) {
            if ($field == "Subscriber" && isset($this->Data) && is_array($this->Data) && count($this->Data) > 0) {
                $isset = true;
            } else if ($field == "SubscriberSource" && isset($this->SubscriberSource) && !empty($this->SubscriberSource)) {
                $isset = true;
            } else if ($field == "Insurance" && $this->Insurance != null && $this->Insurance instanceof Insurance) {
                $isset = true;
            } else if ($field == "SecondaryInsurance" && $this->SecondaryInsurance != null && $this->SecondaryInsurance instanceof Insurance) {
                $isset = true;
            }
        }
        return $isset;
    }
    
    public function __get($field) {
        $value = parent::__get($field);
        
        if (empty($value)) {
            if ($field == "Data") {
                $value = $this->Data;
            } else if ($field == "SubscriberSource") {
                $value = $this->SubscriberSource;
            } else if ($field == "Insurance") {
                $value = $this->Insurance;
            } else if ($field == "SecondaryInsurance") {
                $value = $this->SecondaryInsurance;
            }
        }
        
        return $value;
    }
    
    public function __set($field, $value) {
        $done = parent::__set($field, $value);
        if (!$done) {
            if ($field == "SubscriberSource") {
                $this->SubscriberSource = $value;
                $done = true;
            }
        }
        return $done;
    }
    
}



?>
