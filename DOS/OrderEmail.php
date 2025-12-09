<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 3/17/2020
 * Time: 12:09 PM
 */
require_once 'BaseObject.php';

class OrderEmail extends BaseObject {

    protected $Data = array(
        "idEmails" => "",
        "orderId" => "",
        "patientNumber" => "",
        "email" => "",
        "isWebPatient" => null,
        "dateCreated" => "",
        "isActive" => true
    );

    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);

        }
    }

}