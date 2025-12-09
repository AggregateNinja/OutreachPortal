<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 6/26/15
 * Time: 5:02 PM
 */

require_once 'BaseObject.php';

class OrderEntrySetting extends BaseObject {

    protected $Data = array (
        "idOrderEntrySettings" => "",
        "settingName" => "",
        "settingDescription" => "",
        "isActive" => "",
        "checkedByDefault" => ""
    );

    public function __construct(array $data) {
        parent::__construct($data);

    }

} 