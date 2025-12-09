<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 12/1/14
 * Time: 1:38 PM
 */

require_once 'User.php';
require_once 'Insurance.php';

class InsuranceUser extends User {
    private $Insurance;

    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);

            $this->Insurance = new Insurance($data);
            if (array_key_exists("insuranceName", $data)) {
                $this->Insurance->name = $data['insuranceName'];
            }
            if (array_key_exists("insurancePhone", $data)) {
                $this->Insurance->phone = $data['insurancePhone'];
            }
            if (array_key_exists("insuranceAddress", $data)) {
                $this->Insurance->address = $data['insuranceAddress'];
            }
            if (array_key_exists("insuranceCity", $data)) {
                $this->Insurance->city = $data['insuranceCity'];
            }
            if (array_key_exists("insuranceState", $data)) {
                $this->Insurance->state = $data['insuranceState'];
            }
            if (array_key_exists("insuranceZip", $data)) {
                $this->Insurance->zip = $data['insuranceZip'];
            }
        }
    }

    public function __set($field, $value) {
        $done = parent::__set($field, $value);
        if (!$done) {
            if (isset($this->Insurance) && $this->Insurance instanceof Insurance && array_key_exists($field, $this->Insurance->Data)) {
                $this->Insurance->Data[$field] = $value;
                $done = true;
            }
        }
        return $done;
    }

    public function __isset($field) {
        $isset = parent::__isset($field);
        if (!$isset) {
            if ($field == "Insurance" && isset($this->Insurance) && $this->Insurance != null && $this->Insurance instanceof Insurance) {
                $isset = true;
            }
        }
        return $isset;
    }

    public function __get($field) {
        $value = parent::__get($field);
        if (empty($value)) {
            if ($field == "Insurance" && isset($this->Insurance) && $this->Insurance instanceof Insurance) {
                $value = $this->Insurance;
            } else if (isset($this->Insurance) && $this->Insurance instanceof Insurance && array_key_exists($field, $this->Insurance->Data)) {
                $value = $this->Insurance->$field;
            }
        }
        return $value;
    }

} 