<?php
/**
 * Created by PhpStorm.
 * User: Ed Bossmeyer
 * Date: 10/15/14
 * Time: 11:41 AM
 */
require_once 'BaseObject.php';
require_once 'Employee.php';
require_once 'SalesGroup.php';
require_once 'Territory.php';

class Salesman extends Employee {
    protected $SalesmanData = array (
        "idsalesmen" => "",
        "employeeID" => "",
        "commisionRate" => "",
        "territory" => "",
        "classification" => "",
        "salesGroup" => "",
        "byOrders" => "",
        "byTests" => "",
        "byBilled" => "",
        "byReceived" => "",
        "byGroup" => "",
        "byPercentage" => "",
        "byAmount" => "",
        "created" => "",
        "createdBy" => ""
    );
    private $SalesGroup;
    private $Territory;
    private $IsGroupLeader = false;

    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);

            foreach($data as $key => $value) {
                if (array_key_exists($key, $this->SalesmanData)) {
                    $this->SalesmanData[$key] = $value;
                }
            }

            $this->SalesGroup = new SalesGroup($data);
            $this->Territory = new Territory($data);

            if (array_key_exists("salesmanFirstName", $data)) {
                $this->Data['firstName'] = $data['salesmanFirstName'];
            }
            if (array_key_exists("salesmanLastName", $data)) {
                $this->Data['lastName'] = $data['salesmanLastName'];
            }

            if (array_key_exists("idsalesmen", $data) && array_key_exists("groupLeader", $data) && $data['idsalesmen'] == $data['groupLeader']) {
                $this->IsGroupLeader = true;
            }
        }
    }

    public function __get($field) {
        $value = parent::__get($field);
        if (empty($value)) {
            if ($field == "id") {
                $value = $this->SalesGroup->id;
            } elseif ($field == "SalesmanData") {
                $value = $this->SalesmanData;
            } else if (array_key_exists($field, $this->SalesmanData)) {
                $value = $this->SalesmanData[$field];
            } else if (array_key_exists($field, $this->SalesGroup->Data)) {
                $value = $this->SalesGroup->$field;
            } else if (array_key_exists($field, $this->Territory->Data)) {
                $value = $this->Territory->$field;
            } else if ($field == "IsGroupLeader") {
                $value = $this->IsGroupLeader;
            } else if ($field == "GroupLeader") {
                $value = $this->SalesGroup->GroupLeader;
            } else if ($field == "SalesGroup") {
                $value = $this->SalesGroup;
            }
        }
        return $value;
    }

    public function __set($field, $value) {
        $done = parent::__set($field, $value);
        if (!$done) {
            if ($field == "IsGroupLeader") {
                $this->IsGroupLeader = $value;
            } else if (array_key_exists($field, $this->SalesmanData)) {
                $this->SalesmanData[$field] = $value;
                $done = true;
            }
        }
        return $done;
    }

    public function __isset($field) {
        $isset = parent::__isset($field);
        if (!$isset) {
            if ($field == "SalesGroup" && isset($this->SalesGroup) && $this->SalesGroup instanceof SalesGroup) {
                $isset = true;
            }
        }
        return $isset;
    }
} 