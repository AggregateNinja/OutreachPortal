<?php
require_once 'BaseObject.php';

class Report extends BaseObject {

    protected $Data = array (
        "idOrders" => "",
        "idreportType" => "",
        "number" => "",
        "name" => "",
        "filePath" => "",
        "selectable" => true,
        "format" => ""
    );

    private $EncodedPdf;

    public function __construct(array $data) {
        parent::__construct($data);

        $this->EncodedPdf = null;
        if (array_key_exists("EncodedPdf", $data)) {
            $this->EncodedPdf = $data['EncodedPdf'];
        }
    }

    public function __get($field) {
        $value = null;
        if (array_key_exists($field, $this->Data)) {
            $value = $this->Data[$field];
        } else if ($field == "EncodedPdf") {
            $value = $this->EncodedPdf;
        }
        return $value;
    }

    public function __set($field, $value) {
        $isset = parent::__set($field, $value);
        if (!$isset) {
            if ($field == "EncodedPdf") {
                $this->EncodedPdf = $value;
                $isset = true;
            }
        }
        return $isset;
    }
} 