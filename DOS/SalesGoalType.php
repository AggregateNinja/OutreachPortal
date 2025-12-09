<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 12/30/14
 * Time: 12:40 PM
 */

require_once 'BaseObject.php';

class SalesGoalType extends BaseObject {
    protected $Data = array(
        "idTypes" => "",
        "typeName" => "",
        "typeDescription" => "",
        "isActive" => true
    );

    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);
        }
    }

    public function __get($field) {
        $value = parent::__get($field);
        return $value;
    }

    public function __set($field, $value) {
        $done = parent::__set($field, $value);
        return $done;
    }

    public function __isset($field) {
        $isset = parent::__isset($field);
        return $isset;
    }
} 