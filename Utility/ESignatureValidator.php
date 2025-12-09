<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 2/9/16
 * Time: 10:28 AM
 */

require_once 'FormValidator.php';

//use Symfony\Component\Form\Forms;
//use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
//use Symfony\Component\Validator\Validation;

//use Symfony\Component\Validator\Mapping\ClassMetadata;
//use Symfony\Component\Validator\Constraints\NotBlank;

//use Symfony\Component\Validator\Constraints as Assert;

class ESignatureValidator extends FormValidator {

    private $Data = array(
        "signatureType" => "",
        "initialsType" => "",
        "signatureUrl" => "",
        "initialsUrl" => "",
        "fullName" => "",
        "initials" => "",
        "idUtensilTypes" => "",

        "decodedSignature" => "",
        "decodedInitials" => "",

        "signatureFileType" => "",
        "initialsFileType" => "",

        "signatureWidth" => "",
        "initialsWidth" => "",

        "signatureHeight" => "",
        "initialsHeight" => "",

        "signatureFileSize" => "", // In bytes - 500000 bytes = 500kB
        "initialsFileSize" => "",


        "assignedDoctor" => "",
        "assignTypeId" => ""
    );
    public $IsValid = true;
    public $ErrorMessages = array();

    public $ValidFileTypes = array("image/png", "image/jpg", "image/jpeg", "image/gif");
    public $MaxFileSize = 500000;

    public function __construct(array $data) {

        //if ($_SERVER['CONTENT_LENGTH'] < 8388608) {
            foreach ($data as $key => $value) {
                if (array_key_exists($key, $this->Data)) {
                    $this->Data[$key] = $value;
                } else if ($key == "utensil") {
                    $this->Data['idUtensilTypes'] = $value;
                }
            }
            // Set up signature variables
            $signatureData = $this->Data['signatureUrl'];

            $signatureDataPieces = explode(",", $signatureData);
            $encodedSignature = $signatureDataPieces[1];
            $this->Data['decodedSignature'] = base64_decode($encodedSignature);

            $signatureFileInfo = getimagesizefromstring($this->Data['decodedSignature']);

            $this->Data['signatureFileType'] = $signatureFileInfo['mime'];
            $this->Data['signatureWidth'] = $signatureFileInfo[0];
            $this->Data['signatureHeight'] = $signatureFileInfo[1];
            $this->Data['signatureFileSize'] = mb_strlen($this->Data['decodedSignature'], '8bit');

            if (!empty($data['initialsUrl'])) { // initials is optional
                // Set up initials variables
                $initialsData = $this->Data['initialsUrl'];

                $initialsDataPieces = explode(",", $initialsData);
                $encodedInitials = $initialsDataPieces[1];
                $this->Data['decodedInitials'] = base64_decode($encodedInitials);

                $initialsFileInfo = getimagesizefromstring($this->Data['decodedInitials']);

                $this->Data['initialsFileType'] = $initialsFileInfo['mime'];
                $this->Data['initialsWidth'] = $initialsFileInfo[0];
                $this->Data['initialsHeight'] = $initialsFileInfo[1];
                $this->Data['initialsFileSize'] = mb_strlen($this->Data['decodedInitials'], '8bit');
            }



            // Display the image - testing
            //$base64   = base64_encode($signatureData);
            //$dataUri = 'data:png;base64,' . $encodedSignature;
            //echo "<img src=\"$dataUri\" alt=\"ESignature\" />";


            //echo "<pre>"; print_r($this->Data); echo "</pre>";

        //} else {
        //    // the uploaded file size is larger than the limit set in PHP
        //    $this->IsValid = false;
        //    $this->ErrorMessages['imageFileSize'] = "Image file size must be 500KB or less";
        //}
    }

    public function validate() {

        if (empty($this->Data['fullName'])) {
            $this->IsValid = false;
            $this->ErrorMessages['fullName'] = "Full name must be entered";
        }

        if (empty($this->Data['initials'])) {
            $this->IsValid = false;
            $this->ErrorMessages['initials'] = "Initials must be entered";
        }

        if ($this->Data['signatureType'] == 2) {
            if (!in_array($this->Data['signatureFileType'], $this->ValidFileTypes)) {
                $this->IsValid = false;
                $this->ErrorMessages['signatureFileType'] = "Signature file type invalid";
            }

            if ($this->Data['signatureFileSize'] > $this->MaxFileSize) {
                $this->IsValid = false;
                $this->ErrorMessages['signatureFileSize'] = "Signature file size must be 500KB or less";
            }

        }

        if (!empty($this->Data['decodedInitials'])) {
            if ($this->Data['initialsType'] == 2) {
                if (!in_array($this->Data['initialsFileType'], $this->ValidFileTypes)) {
                    $this->IsValid = false;
                    $this->ErrorMessages['initialsFileType'] = "Initials file type invalid";
                }

                if ($this->Data['initialsFileSize'] > $this->MaxFileSize) {
                    $this->IsValid = false;
                    $this->ErrorMessages['initialsFileSize'] = "initials file size must be 500KB or less";
                }
            }
        }

        return $this->IsValid;
    }

} 