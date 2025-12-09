<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 1/8/2021
 * Time: 10:50 AM
 */
require_once 'BaseObject.php';
require_once 'DOS/Patient.php';
require_once 'DOS/Order.php';

class PatientSMSQueue extends BaseObject {
    protected $Data = array(
        "idSMSQueue" => "",
        "orderId" => "",
        "patientId" => "",
        "messageTypeId" => "",
        "sent" => false,
        "dateCreated" => ""
    );

    public $Patient;
    public $Order;

    public function __construct(array $data = null)
    {
        if ($data != null) {
            parent::__construct($data);
            $this->Patient = new Patient($data);
            $this->Order = new Order($data);
        }
    }

}