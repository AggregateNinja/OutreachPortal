<?php
require_once 'BaseObject.php';
require_once 'Drug.php';

/**
 * Created by PhpStorm.
 * User: eboss
 * Date: 4/1/2024
 * Time: 3:13 PM
 */



class PatientPrescription extends BaseObject
{
    protected $Data = array (
        "idPatientPrescriptions" => "",
        "patientId" => "",
        "drugId" => "",
        "isActive" => true,
        "dateCreated" => ""
    );
    protected $Drug;

    public function __construct(array $data = null)
    {
        parent::__construct($data);
        if (array_key_exists("iddrugs", $data) || array_key_exists("genericName", $data)) {
            $this->Drug = new Drug($data, true);
        }
    }

    public function __get($field) {
        $value = parent::__get($field);

        if (empty($value)) {
            if ($field == "Drug") {
                $value = $this->Drug;
            } else if (isset($this->Drug) && $this->Drug instanceof Drug && array_key_exists($field, $this->Drug->Data)) {
                $value = $this->Drug->$field;
            }
        }

        return $value;
    }

}