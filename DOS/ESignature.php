<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 2/10/16
 * Time: 4:26 PM
 */

require_once 'BaseObject.php';

class ESignature extends BaseObject {
    public $Data = array(
        "idESig" => "",
        "userId" => "",
        "fullName" => "",
        "initials" => "",
        "signatureFileName" => "",
        "initialsFileName" => "",
        "assignTypeId" => "",
        "signatureType" => "",
        "initialsType" => "",
        "isActive" => "",

        "idESigTypes" => "",
        "typeName" => "",
        "typeDescription" => "",

        "idUtensilTypes" => "",
        "utensilTypeName" => "",
        "lineWidth" => ""
    );

    public $EncodedSignature = "";
    public $EncodedInitials = "";

    public function __construct(array $data) {
        parent::__construct($data);
    }
} 