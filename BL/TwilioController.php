<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 1/12/2021
 * Time: 11:39 AM
 */
require_once 'DAOS/SMSQueueDAO.php';
require_once 'Utility/IConfig.php';
require 'vendor/autoload.php';
use Twilio\Rest\Client;

class TwilioController {
    // Your Account SID and Auth Token from twilio.com/console
    // In production, these should be environment variables. E.g.:
    // $auth_token = $_ENV["TWILIO_AUTH_TOKEN"]

    // Avalon Demo
    private $AccountSID = 'ACc1242e79e7ff02da9ca2f3161387d5c4';
    private $AuthToken = '8322d542272ae7257c7179538dcb46b6';
    private $SenderPhone = "+16592075580";

    // Genesis
//    private $AccountSID = 'ACe58f54fc3dd7ffcdab991ebe4a7425e4';
//    private $AuthToken = '84fcd4dc55f01ea5d3c45a4c933203c8';
//    private $SenderPhone = "+16097578209";

    private $SMSBody = '';
    private $TwilioClient;
    private $QueueDAO;

    public function __construct() {
        $this->TwilioClient = new Client($this->AccountSID, $this->AuthToken);
        $this->QueueDAO = new SMSQueueDAO();
        $this->SMSBody = 'your lab test has been received and is being processed by the lab. Reply STOP to opt out';
        // jim - +16095171518
        // ed - +16097055700
        // rick - +16097055704
        // austin - +16092876062
    }

    /*
     * Called from crontab
     * - Check the patientSMSQueue table for any text messages that need to be sent out
     * - Send text messages
     * - Add log entry
     * - Update sent flag in patientSMSQueue table
     */
    public function updateQueue() {
        $arySMSQueue = $this->QueueDAO->getSMSQueue();

        foreach ($arySMSQueue as $smsQueueRow) {
            $sendSms = false;
            $idSMSQueue = $smsQueueRow->idSMSQueue;
            $orderId = $smsQueueRow->orderId;
            $patientId = $smsQueueRow->patientId;
            $messageTypeId = $smsQueueRow->messageTypeId;
            $dateQueued = $smsQueueRow->dateCreated;
            $patientArNo = $smsQueueRow->Patient->arNo;
            $patientFirstName = $smsQueueRow->Patient->firstName;
            $patientMiddleName = $smsQueueRow->Patient->middleName;
            $patientLastName = $smsQueueRow->Patient->lastName;

            if (empty($patientMiddleName)) {
                $patientMiddleName = null;
            }

            $userId = 1;

            $patientPhone = $smsQueueRow->Patient->phone;

            if ($patientPhone != null && !empty($patientPhone)) {
                $patientPhone = str_replace(array("(", ")", "-", "+", " "), "", $patientPhone);
                if (strlen($patientPhone) == 10) {

                    try {
                        $patientPhone = "+1" . $patientPhone;

                        $phone_number = $this->TwilioClient->lookups->v1->phoneNumbers($patientPhone)->fetch();
                        //print($phone_number->nationalFormat);

                        if ($patientMiddleName != null && !empty($patientMiddleName)) {
                            $patientName = $patientFirstName . " " . $patientMiddleName . " " . $patientLastName;
                        } else {
                            $patientName = $patientFirstName . " " . $patientLastName;
                        }

                        $sendSms = true;

                    } catch (Exception $e) {
                        // invalid phone number
                    }
                }
            }

            $aryLogData = array(
                "smsQueueId" => $idSMSQueue,
                "logTypeId" => 2,
                "orderId" => $orderId,
                "userId" => $userId,
                "patientId" => $patientId,
                "patientArNo" => $patientArNo,
                "patientLastName" => $patientLastName,
                "patientFirstName" => $patientFirstName,
                "patientMiddleName" => $patientMiddleName,
                "patientPhone" => $patientPhone,
                "messageTypeId" => $messageTypeId
            );

            if ($sendSms) {
                $this->sendTwilioSMS($patientPhone, $patientFirstName, $patientArNo, $messageTypeId);
                // update queue - either remove row altogether, or update the sent flag
                $this->addLogEntry($aryLogData);
                $this->QueueDAO->updateSMSQueue($idSMSQueue, true);
            } else {
                $aryLogData['logTypeId'] = 4;
                $this->addLogEntry($aryLogData);
                $this->QueueDAO->deleteByQueueId($idSMSQueue);
            }

            //echo $patientName . ' - ' . $patientPhone;
            //echo "<pre>"; print_r($smsQueueRow); echo "</pre><br/><br/>";
        }
    }

    public function clearQueue() {

    }

    private function sendTwilioSMS($recipientPhone, $patientName, $patientArNo, $messageTypeId) {
        $message = "";
        if ($messageTypeId == 1) {
            $message = $patientName . ", " . "your lab test has been received and is being processed by " . IConfig::LabName . ". " .
                "Your patient record locator number is $patientArNo. Reply STOP to opt out";
        } else if ($messageTypeId == 2) {
            $message = $patientName . ", " . "your test results from " . IConfig::LabName . " are complete. " .
                "Your patient record locator number is $patientArNo. " .
                "Register for an account at " . str_replace("/outreach/", "/patients/", IConfig::SITE_URL) . " to view your results. " .
                "Reply STOP to opt out";
        }

        if (!empty($message)) {
            $this->TwilioClient->messages->create(
                $recipientPhone,
                array(
                    'from' => $this->SenderPhone,
                    'body' => $message
                )
            );
        }
    }

    private function addLogEntry(array $logData) {
        $this->QueueDAO->addLogEntry($logData);
    }
}