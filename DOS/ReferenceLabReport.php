<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 2/16/15
 * Time: 12:45 PM
 */

require_once 'Report.php';

class ReferenceLabReport extends Report {
    protected $ReferenceData = array(
        "id" => 0,
        "orderId" => 0,
        "departmentId" => 0,
        "report" => "",
        "md5" => "",
        "created" => "",
        "userId" => "",
        "ReferenceLabReport" => ""
    );

    public function __construct(array $data) {
        parent::__construct($data[0]);
        foreach($data[0] as $key => $value) {
            if (array_key_exists($key, $this->ReferenceData)) {
                $this->ReferenceData[$key] = $value;
            }

            if ($key == "ReferenceLabReport") {
                $this->EncodedPdf = $value;
            }
        }
//
//        if (array_key_exists("ReferenceLabReport", $data)) {
//            $this->ReferenceData['report'] = $data['ReferenceLabReport'];
//            $this->EncodedPdf = $data['ReferenceLabReport'];
//        } else {
//            $this->EncodedPdf = $this->ReferenceData['report'];
//        }



        //echo "<pre>"; print_r($data); echo "</pre>";
    }


    public function __get($field) {
        $value = parent::__get($field);

        if ($value == null) {
            if (array_key_exists($field, $this->Data)) {
                $value = $this->Data[$field];
            } else if (array_key_exists($field, $this->ReferenceData)) {
                $value = $this->ReferenceData[$field];
            }
        }

        return $value;
    }
} 