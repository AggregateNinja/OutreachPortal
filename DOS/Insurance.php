<?php
require_once 'BaseObject.php';

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Insurance
 *
 * @author Edd
 */
class Insurance extends BaseObject {
    protected $Data = array (
        "idinsurances" => "", 
        "name" => "", 
        "addreviation" => "", 
        "address" => "", 
        "address2" => "", 
        "city" => "", 
        "state" => "", 
        "zip" => "", 
        "phone" => "", 
        "received" => "", 
        "sourceid1" => "", 
        "sourceid2" => "", 
        "payorid" => "", 
        "fee" => "", 
        "procedureset" => "", 
        "servicetype" => "", 
        "hcfa" => "", 
        "capario" => "", 
        "ins_type" => "", 
        "accept_assignment" => "", 
        "internal_only" => "", 
        "sendoutBillable" => "", 
        "rules" => "", 
        "billingId" => ""
    );

    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);

            if (array_key_exists("insuranceName", $data)) {
                $this->Data['name'] = $data['insuranceName'];
            }
        }
    }
    
    public function __isset($field) {
        $isset = parent::__isset($field);
        if (!$isset) {
            if ($field == "Insurance" && isset($this->Data) && is_array($this->Data) && count($this->Data) > 0) {
                $isset = true;
            }
        }
        return $isset;
    }
    
}

?>
