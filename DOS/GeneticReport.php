<?php
require_once 'Report.php';

class GeneticReport extends Report {

    protected $GeneticData = array(
        "id" => 0,
        "idorders" => 0,
        "idpatients" => 0,
        "report" => "",
        "created" => ""
    );

    public function __construct(array $data) {
        parent::__construct($data[0]);
        foreach($data[0] as $key => $value) {
            if (array_key_exists($key, $this->GeneticData)) {
                $this->GeneticData[$key] = $value;
            }
        }

        //echo $data[0]['report'];

        $this->EncodedPdf = $this->GeneticData['report'];

        //echo "<pre>"; print_r($data); echo "</pre>";


    }


    public function __get($field) {
        $value = parent::__get($field);

        if ($value == null) {
            if (array_key_exists($field, $this->Data)) {
                $value = $this->Data[$field];
            } else if (array_key_exists($field, $this->GeneticData)) {
                $value = $this->GeneticData[$field];
            }
        }

        return $value;
    }
}