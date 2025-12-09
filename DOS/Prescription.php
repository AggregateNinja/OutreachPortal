<?php
require_once 'BaseObject.php';
require_once 'Order.php';
require_once 'Drug.php';

class Prescription extends BaseObject {
    protected $Data = array (
        "idPrescriptions" => "",
        "orderId" => "",
        "drugId" => "",
        "advancedOrder" => 0
    );
    protected $Drug;
    protected $Order;

    public function __construct(array $data, $withDrugAndOrder = false) {
        parent::__construct($data);
        if ($withDrugAndOrder) {
            $this->Drug = new Drug($data);
            $this->Order = new Order($data);
        } else if (array_key_exists("iddrugs", $data) || array_key_exists("genericName", $data)) {
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


?>