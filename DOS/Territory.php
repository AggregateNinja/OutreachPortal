<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 10/16/14
 * Time: 11:14 AM
 */
require_once 'BaseObject.php';

class Territory extends BaseObject {

    protected $Data = array(
        "idterritory" => "",
        "territoryName" => "",
        "description" => "",
        "created" => "",
        "createdBy" => ""
    );

    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);
        }
    }

} 