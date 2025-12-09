<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 3/17/2020
 * Time: 12:09 PM
 */
require_once 'User.php';
require_once 'Patient.php';

class PatientUser extends User {
    public $Patient;

    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);

            $this->Patient = new Patient($data);
        }
    }

    public function __get($key) {
        $field = parent::__get($key);
        if (empty($field)) {
            if (array_key_exists($key, $this->Patient->Data)) {
                $field = $this->Patient->Data[$key];
            }
        }
        return $field;
    }

}