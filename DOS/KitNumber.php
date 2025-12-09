<?php
/**
 * Created by PhpStorm.
 * User: eboss
 * Date: 8/5/2025
 * Time: 3:49 PM
 */
require_once 'BaseObject.php';

class KitNumber extends BaseObject {
    protected $Data = array (
        "idKitNumbers" => "",
        "productName" => "",
        "lotNumber" => "",
        "kitNumber" => "", // specimenId
        "description" => "",
        "dateCreated" => "",
        "dateUpdated" => "",
        "isActive" => true,
        "isPrepaid" => false
    );

    public function __construct(array $data = null, array $settings = null) {
        if ($data != null) {
            parent::__construct($data);
        }
    }

    public function __get($field)
    {
        $value = parent::__get($field);

        return $value;
    }
}