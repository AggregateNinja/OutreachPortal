<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 5/24/16
 * Time: 1:11 PM
 */
require_once 'BaseObject.php';

class LabInfo extends BaseObject {
    protected $Data = array(
        "idlabMaster" => "",
        "labName" => "",
        "facilityName" => "",
        "address1" => "",
        "address2" => "",
        "city" => "",
        "state" => "",
        "zip" => "",
        "physicalAddress1" => "",
        "physicalAddress2" => "",
        "physicalCity" => "",
        "physicalState" => "",
        "physicalZip" => "",
        "country" => "",
        "locality" => "",
        "phone" => "",
        "fax" => "",
        "CLIANumber" => "",
        "COLANumber" => "",
        "NPINumber" => "",
        "taxID" => "",
        "providerID" => "",
        "labDirector" => "",
        "contact" => "",
        "website" => "",
        "email" => "",
        "logoPath" => "",
        "logoPath2" => ""
    );

    public function __construct($data) {
        parent::__construct($data);
    }

    public function getAddress() {
        if (isset($this->Data['address2']) && !empty($this->Data['address2'])) {
            return $this->Data['address1'] . ", " . $this->Data['address2'];
        }
        return $this->Data['address1'];
    }

    public function getCityStateZip() {
        if (isset($this->Data['city']) && !empty($this->Data['city']) && isset($this->Data['state']) && !empty($this->Data['state']) && isset($this->Data['zip']) && !empty($this->Data['zip'])) {
            return $this->Data['city'] . ", " . $this->Data['state'] . " " . $this->Data['zip'];
        } else if (isset($this->Data['city']) && !empty($this->Data['city']) && isset($this->Data['state']) && !empty($this->Data['state'])) {
            return $this->Data['city'] . ", " . $this->Data['state'];
        } else if (isset($this->Data['city']) && !empty($this->Data['city']) && isset($this->Data['zip']) && !empty($this->Data['zip'])) {
            return $this->Data['city'] . " " . $this->Data['zip'];
        } else if (isset($this->Data['state']) && !empty($this->Data['state']) && isset($this->Data['zip']) && !empty($this->Data['zip'])) {
            return $this->Data['state'] . " " . $this->Data['zip'];
        } else if (isset($this->Data['city']) && !empty($this->Data['city'])) {
            return $this->Data['city'];
        } else if (isset($this->Data['state']) && !empty($this->Data['state'])) {
            return $this->Data['state'];
        } else if (isset($this->Data['zip']) && !empty($this->Data['zip'])) {
            return $this->Data['zip'];
        }
        return null;
    }

    public function getPhone() {
        $phone = $this->Data['phone'];
        if (isset($phone) && !empty($phone)) {
            if (strlen($phone) == 12 && substr_count($phone, "-") == 2) {
                return "(" . substr($phone, 0, 3) . ") " . substr($phone, 4, 12);
            } else {
                return $phone;
            }
        }
        return null;
    }

    public function getFax() {
        $fax = $this->Data['fax'];
        if (isset($fax) && !empty($fax)) {
            if (strlen($fax) == 12 && substr_count($fax, "-") == 2) {
                return "(" . substr($fax, 0, 3) . ") " . substr($fax, 4, 12);
            } else {
                return $fax;
            }
        }
        return null;
    }

} 