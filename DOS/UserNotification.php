<?php
require_once 'BaseObject.php';
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 6/22/2017
 * Time: 1:59 PM
 */
class UserNotification extends BaseObject {
    protected $Data = array(
        "idNotifications" => "",
        "notificationTypeId" => "",
        "notificationTitle" => "",
        "notificationText" => "",
        "dateFrom" => "",
        "dateTo" => "",
        "isActive" => "",
        "idNotificationLog" => ""
    );

    public $ClientIds = array();
    public $DoctorIds = array();

    public function __construct(array $data) {
        parent::__construct($data);
    }

}