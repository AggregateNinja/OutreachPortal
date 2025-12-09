<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'FormValidator.php';

/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 9/18/2017
 * Time: 9:43 AM
 */
class NotificationValidator extends FormValidator {

    protected $InputFields = array(
        "userId" => "",
        "idNotifications" => "",
        "notificationTypeId" => "",
        "notificationTitle" => "",
        "notificationText" =>"",
        "dateFrom" => "",
        "dateTo" => "",
        "continuous" => false
    );

    public $ErrorMessages = array();
    private $IsValid = true;

    public function __construct(array $data) {

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->InputFields)) {
                $this->InputFields[$key] = $value;
            }
        }
    }

    public function validate() {

        if (empty($this->InputFields['notificationText'])) {
            $this->IsValid = false;
            $this->ErrorMessages['notificationText'] = "Notification text is empty";
        }

        if (empty($this->InputFields['dateFrom'])) {
            $this->IsValid = false;
            $this->ErrorMessages['dateFrom'] = "Start date must be selected";
        } else if (!parent::isValidDate(array($this->InputFields['dateFrom']), 'm/d/Y h:i A') && !parent::isValidDate(array($this->InputFields['dateFrom']), 'm/d/Y')) {
            $this->IsValid = false;
            $this->ErrorMessages['dateFrom'] = "Invalid date format";
        }

        if ($this->InputFields['continuous'] == false) {
            if (empty($this->InputFields['dateTo'])) {
                $this->IsValid = false;
                $this->ErrorMessages['dateTo'] = "End date must be selected";
            } else if (!parent::isValidDate(array($this->InputFields['dateTo']), 'm/d/Y h:i A') && !parent::isValidDate(array($this->InputFields['dateTo']), 'm/d/Y')) {
                $this->IsValid = false;
                $this->ErrorMessages['dateFrom'] = "Invalid date format";
            }
        }

        return $this->IsValid;
    }

}