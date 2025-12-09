<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 3/25/2022
 * Time: 11:25 AM
 */
require_once 'FormValidator.php';

class PatientCardsValidator extends FormValidator {
    private $Data = array(
        "licenseFrontUrl" => "",
        "licenseBackUrl" => "",
        "insuranceUrl" => "",

        "decodedLicenseFront" => "",
        "decodedLicenseBack" => "",
        "decodedInsurance" => "",

        "licenseFrontFileType" => "",
        "licenseBackFileType" => "",
        "insuranceFileType" => "",

        "licenseFrontFileSize" => 0, // In bytes - 500000 bytes = 500kB
        "licenseBackFileSize" => 0,
        "insuranceFileSize" => 0,

        "userId" => "",
        "orderId" => ""
    );
    public $IsValid = true;
    public $ErrorMessages = array();

    public $ValidFileTypes = array("image/png", "image/jpg", "image/jpeg", "image/gif");
    public $MaxFileSize = 500000;

    public function __construct(array $data) {
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->Data)) {
                $this->Data[$key] = $value;
            }
        }

        if (!empty($this->Data['licenseFrontUrl'])) {
            $dataPieces = explode(",", $this->Data['licenseFrontUrl']);
            $encodedImageTemp = $dataPieces[1];
            $this->Data['decodedLicenseFront'] = base64_decode($encodedImageTemp);
            $fileInfo = getimagesizefromstring($this->Data['decodedLicenseFront']);
            $this->Data['licenseFrontFileType'] = $fileInfo['mime'];
            $this->Data['licenseFrontFileSize'] = mb_strlen($this->Data['decodedLicenseFront'], '8bit');
        }

        if (!empty($this->Data['licenseBackUrl'])) {
            $dataPieces = explode(",", $this->Data['licenseBackUrl']);
            $encodedImageTemp = $dataPieces[1];
            $this->Data['decodedLicenseBack'] = base64_decode($encodedImageTemp);
            $fileInfo = getimagesizefromstring($this->Data['decodedLicenseBack']);
            $this->Data['licenseBackFileType'] = $fileInfo['mime'];
            $this->Data['licenseBackFileSize'] = mb_strlen($this->Data['decodedLicenseBack'], '8bit');
        }

        if (!empty($this->Data['insuranceUrl'])) {
            $dataPieces = explode(",", $this->Data['insuranceUrl']);
            $encodedImageTemp = $dataPieces[1];
            $this->Data['decodedInsurance'] = base64_decode($encodedImageTemp);
            $fileInfo = getimagesizefromstring($this->Data['decodedInsurance']);
            $this->Data['insuranceFileType'] = $fileInfo['mime'];
            $this->Data['insuranceFileSize'] = mb_strlen($this->Data['decodedInsurance'], '8bit');
        }
    }

    public function validate() {

        if (empty($this->Data['licenseFrontUrl'])) {
            $this->IsValid = false;
            $this->ErrorMessages['licenseFront'] = "Drivers license or identification is required";
        }
        if (empty($this->Data['licenseBackUrl'])) {
            $this->IsValid = false;
            $this->ErrorMessages['licenseBack'] = "Drivers license or identification is required";
        }

        if ($this->Data['licenseFrontFileSize'] > $this->MaxFileSize) {
            $this->IsValid = false;
            $this->ErrorMessages['licenseFrontFileSize'] = "File size must be 500KB or less";
        }
        if ($this->Data['licenseBackFileSize'] > $this->MaxFileSize) {
            $this->IsValid = false;
            $this->ErrorMessages['licenseBackFileSize'] = "File size must be 500KB or less";
        }
        if (!empty($this->Data['insuranceUrl'])) {
            if ($this->Data['insuranceFileSize'] > $this->MaxFileSize) {
                $this->IsValid = false;
                $this->ErrorMessages['insuranceFileSize'] = "File size must be 500KB or less";
            }
        }

        if (!in_array($this->Data['licenseFrontFileType'], $this->ValidFileTypes)) {
            $this->IsValid = false;
            $this->ErrorMessages['licenseFrontFileType'] = "Invalid file type";
        }
        if (!in_array($this->Data['licenseBackFileType'], $this->ValidFileTypes)) {
            $this->IsValid = false;
            $this->ErrorMessages['licenseBackFileType'] = "Invalid file type";
        }
        if (!empty($this->Data['insuranceUrl'])) {
            if (!in_array($this->Data['insuranceFileType'], $this->ValidFileTypes)) {
                $this->IsValid = false;
                $this->ErrorMessages['insuranceFileType'] = "Invalid file type";
            }
        }
        return $this->IsValid;
    }
}