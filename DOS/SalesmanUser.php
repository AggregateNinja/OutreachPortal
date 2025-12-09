<?php
/**
 * Created by PhpStorm.
 * User: Ed Bossmeyer
 * Date: 10/20/14
 * Time: 11:17 AM
 */

require_once 'User.php';
require_once 'Salesman.php';
require_once 'SalesSetting.php';

class SalesmanUser extends User {
    protected $SalesSettings = array();
    private $Salesman;

    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);

            if (array_key_exists("idSalesSettings", $data) && !empty($data['idSalesSettings'])) {
                $salesSetting = new SalesSetting($data);
                $this->SalesSettings[$salesSetting->idSalesSettings] = $salesSetting;
            }

            $this->Salesman = new Salesman($data);

            $employeeId = $this->Salesman->idemployees;
            $salesGroup = $this->Salesman->SalesGroup;
            $groupLeader = $salesGroup->GroupLeader;
            if (isset($groupLeader) && $employeeId == $groupLeader->idemployees) {
                $this->Salesman->IsGroupLeader = true;
            }
        }
    }

    public function __get($field) {
        $value = parent::__get($field);
        if (!is_bool($value) && empty($value) && $field != "LastLogin" && $field != "idLoggedIn"  && $field != "ip" && $field != "isActive" && $field != "loginDate") {
            $value = $this->Salesman->$field;
            if (empty($value) && ($field == "SalesSettings" || $field == "UserSettings")) {
                $value = $this->SalesSettings;
            } else if ($field == "SalesGroup") {
                $value = $this->Salesman->SalesGroup;
            } else if ($field == "IsGroupLeader") {
                $value = $this->Salesman->IsGroupLeader;
            }
        }
        return $value;
    }

    public function __set($field, $value) {
        $done = parent::__set($field, $value);
        if (!$done) {
            if (array_key_exists($field, $this->Salesman->SalesmanData)) {
                $this->Salesman->$field = $value;
            }
        }
        return $done;
    }

    public function __isset($field) {
        $isset = parent::__isset($field);
        if (!$isset) {
            if (($field == "SalesmanUser") && isset($this->Salesman) && $this->Salesman instanceof Salesman) {
                $isset = true;
            } else if ($field == "SalesSettings" && count($this->SalesSettings) > 0) {
                $isset = true;
            }
        }
        return $isset;
    }

    public function __toString() {
        $strUser = parent::__toString();
        return $strUser;
    }

    public function addSalesSetting(array $data) {
        $salesSetting = new SalesSetting($data);
        $this->SalesSettings[$salesSetting->idSalesSettings] = $salesSetting;
    }

} 