<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 8/7/2018
 * Time: 2:55 PM
 */
require_once 'BaseObject.php';

class PokitdokEligibilityRequest  extends BaseObject {
    protected $Data = array(
        "member" => array(
            "birth_date" => "",
            "last_name" => "",
            "id" => ""
        ),
        "provider" => array(
            "first_name" => "",
            "last_name" => "",
            "npi" => ""
        ),
        "trading_partner_id" => ""
    );

    protected $member;
    protected $provider;
    protected $trading_partner_id;

    public function __construct(array $data = null) {

    }
}