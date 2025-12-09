<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 12/30/14
 * Time: 12:36 PM
 */

require_once 'BaseObject.php';
require_once 'SalesGoalInterval.php';
require_once 'SalesGoalType.php';
require_once 'Salesman.php';

class SalesGoal extends BaseObject {

    protected $Data = array(
        "idGoals" => "",
        "userId" => "",
        "typeId" => "",
        "intervalId" => "",
        "goal" => "",
        "salesgroupId" => "",
        "isActive" => true,
        "isDefault" => false,
        "dateCreated" => "",
        "dateUpdated" => ""
    );

    private $Salesmen = array();
    private $SalesGoalInterval = null;
    private $SalesGoalType = null;

    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);

            if (array_key_exists("idsalesmen", $data) && $data['idsalesmen'] != null) {
                $this->Salesmen[] = new Salesman($data);
            }


            $this->SalesGoalType = new SalesGoalType($data);
            $this->SalesGoalInterval = new SalesGoalInterval($data);
        }
    }

    public function __get($field) {
        $value = parent::__get($field);
        if ($value == "") {
            if ($field == "SalesGoalInterval" && $this->SalesGoalInterval != null && $this->SalesGoalInterval instanceof SalesGoalInterval) {
                $value = $this->SalesGoalInterval;
            } else if ($field == "SalesGoalType" && $this->SalesGoalType != null && $this->SalesGoalType instanceof SalesGoalType) {
                $value = $this->SalesGoalType;
            } else if ($this->SalesGoalInterval != null && $this->SalesGoalInterval instanceof SalesGoalInterval && array_key_exists($field, $this->SalesGoalInterval->Data)) {
                $value = $this->SalesGoalInterval->$field;
            } else if ($this->SalesGoalType != null && $this->SalesGoalType instanceof SalesGoalType && array_key_exists($field, $this->SalesGoalType->Data)) {
                $value = $this->SalesGoalType->$field;
            } else if ($field == "Salesmen") {
                $value = $this->Salesmen;
            }
        }
        return $value;
    }

    public function __set($field, $value) {
        $done = parent::__set($field, $value);


        return $done;
    }

    public function addSalesman(Salesman $salesman) {
        $this->Salesmen[] = $salesman;
    }

    public function getSalesmen() {
        return $this->Salesmen;
    }

    public function hasSalesmen() {
        if (count($this->Salesmen) == 0) {
            return false;
        }
        return true;
    }

    public function getSalesmenIds() {
        $aryIds = array();
        foreach($this->Salesmen as $currSalesman) {
            $aryIds[] = $currSalesman->idsalesmen;
        }
        return $aryIds;
    }


    public function __isset($field) {
        $isset = parent::__isset($field);
        return $isset;
    }
} 