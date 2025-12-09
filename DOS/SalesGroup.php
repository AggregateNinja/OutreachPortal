<?php
/**
 * Created by PhpStorm.
 * User: Ed Bossmeyer
 * Date: 10/15/14
 * Time: 11:40 AM
 */
require_once 'BaseObject.php';
require_once 'Employee.php';
require_once 'Salesman.php';

class SalesGroup extends BaseObject  {
    protected $Data = array (
        "id" => "",
        "groupName" => "",
        "groupLeader" => "",
        "created" => "",
        "createdBy" => ""
    );

    private $GroupLeader;

    private $Salesmen = array();

    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);

            if (array_key_exists("leaderLastName", $data) && array_key_exists("leaderFirstName", $data)) {
                $this->GroupLeader = new Employee(array(
                    "lastName" => $data['leaderLastName'],
                    "firstName" => $data['leaderFirstName']
                ));
                if (array_key_exists("leaderEmployeeId", $data)) {
                    $this->GroupLeader->idemployees = $data['leaderEmployeeId'];
                }
            }

            if (array_key_exists("salesgroupId", $data)) {
                $this->Data['id'] = $data['salesgroupId'];
            }
        }
    }

    public function __get($field) {
        $value = parent::__get($field);
        if (empty($value)) {
            if ($field == "GroupLeader") {
                $value = $this->GroupLeader;
            } else if ($this->GroupLeader != null && isset($this->GroupLeader) && $this->GroupLeader instanceof Employee && array_key_exists($field, $this->GroupLeader->Data)) {
                $value = $this->GroupLeader->$field;
            } else if ($field == "Salesmen") {
                $value = $this->Salesmen;
            }
        }
        return $value;
    }

    public function addSalesman(array $data) {
        $this->Salesmen[] = new Salesman($data);
    }

    public function __isset($field) {
        $isset = parent::__isset($field);
        if (!$isset) {
            if ($field == "GroupLeader" && isset($this->GroupLeader) && $this->GroupLeader instanceof Employee) {
                $isset = true;
            }
        }
        return $isset;
    }
} 