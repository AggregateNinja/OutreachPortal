<?php

/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 5/18/2016
 * Time: 4:28 PM
 */

require_once 'EmailController.php';
require_once 'DAOS/EmailNotificationDAO.php';

class EmailNotificationController extends EmailController {

    public $NewOrders;
    
    private $EmailDAO;
 
    public function __construct(array $data = null) {
        parent::__construct();
        
        $this->EmailDAO = new EmailNotificationDAO();

        $this->NewOrders = array();

        $this->setNewOrdersAndSendEmails();
    }

    private function setNewOrdersAndSendEmails() {
        $data = $this->EmailDAO->getNewOrders();

        if (count($data) > 0) {
            $currIdUsers = $data[0]['idUsers'];
            $grandTotalOrders = 0;
            $totalEmailsSent = 0;
            foreach ($data as $row) {
                if (!array_key_exists($row['idUsers'], $this->NewOrders)) {

                    $doctorName = "";
                    if (isset($row['firstName']) && isset($row['lastName']) && !empty($row['firstName']) && !empty($row['lastName'])) {
                        $doctorName = $row['firstName'] . " " . $row['lastName'];
                    } else if (isset($row['lastName']) && !empty($row['lastName'])) {
                        $doctorName = $row['lastName'];
                    } else if (isset($row['firstName']) && !empty($row['firstName'])) {
                        $doctorName = $row['firstName'];
                    }

                    $to = $doctorName . " <" . $row['email'] . ">";
                    $subject = "Hello ";
                    if ($row['typeId'] == 2) {
                        $to = $row['clientName'] . "<" . $row['email'] . ">";
                        $subject .= $row['clientName'];
                    } else {
                        $subject .= $doctorName;
                    }

                    $body = "";

                    $aryUserOrders = array (
                        "To" => $to,
                        //"To" => "Edward Bossmeyer <ebossmeyer@csslis.com>",
                        "Subject" => $subject,
                        "Body" => $body,
                        "TotalOrders" => 1,
                        "email" => $row['email'],
                        "typeId" => $row['typeId'],
                        "clientNo" => $row['clientNo'],
                        "clientName" => $row['clientName'],
                        "doctorNo" => $row['doctorNo'],
                        "doctorName" => $doctorName
                    );

                    $this->NewOrders[$row['idUsers']] = $aryUserOrders;

                } else {
                    $this->NewOrders[$row['idUsers']]['TotalOrders'] += 1;
                }

                $grandTotalOrders += 1;

                // log totals for user and send email
                if ($row['idUsers'] != $currIdUsers) {
                    // add log entry for this user                    
                    $this->logUserTotals($currIdUsers, $this->NewOrders[$currIdUsers]['TotalOrders']);

                    $this->NewOrders[$currIdUsers]['Body'] = $this->getEmailContent($this->NewOrders[$currIdUsers]['TotalOrders']);

                    $this->setTo($this->NewOrders[$currIdUsers]['To']);
                    $this->setSubject($this->NewOrders[$currIdUsers]['Subject']);
                    $this->setBody($this->NewOrders[$currIdUsers]['Body']);
                    $this->setHeaders();
                    $this->send();

                    $this->setTo("Edward Bossmeyer <ebossmeyer@csslis.com>");
                    $this->setSubject($this->NewOrders[$currIdUsers]['Subject']);
                    $this->setBody($this->NewOrders[$currIdUsers]['Body']);
                    $this->setHeaders();
                    $this->send();

                    $totalEmailsSent += 1;
                }

                $currIdUsers = $row['idUsers'];
            }

            // log totals for user and send email for the final user
            $this->logUserTotals($currIdUsers, $this->NewOrders[$currIdUsers]['TotalOrders']);

            $this->NewOrders[$currIdUsers]['Body'] = $this->getEmailContent($this->NewOrders[$currIdUsers]['TotalOrders']);

            $this->setTo($this->NewOrders[$currIdUsers]['To']);
            $this->setSubject($this->NewOrders[$currIdUsers]['Subject']);
            $this->setBody($this->NewOrders[$currIdUsers]['Body']);
            $this->setHeaders();
            $this->send();

            $this->setTo("Edward Bossmeyer <ebossmeyer@csslis.com>");
            $this->setSubject($this->NewOrders[$currIdUsers]['Subject']);
            $this->setBody($this->NewOrders[$currIdUsers]['Body']);
            $this->setHeaders();
            $this->send();
            
            $this->EmailDAO->updateEmailLogEntry($totalEmailsSent + 1, $grandTotalOrders);
        }
    }
    
    private function logUserTotals($currIdUsers, $totalOrders) {
        $aryUserData = array(
            "idUsers" => $currIdUsers,
            "TotalNewOrders" => $totalOrders
        );
        $this->EmailDAO->addEmailUserLogEntry($aryUserData);
    }

    private function getEmailContent($totalOrders) {
        if ($totalOrders > 1) {
            $htmlContent = "<h3><b>$totalOrders</b> new orders are available online.</h3>
                    <p>Please login to your outreach site to view the results.</p>";
        } else {
            $htmlContent = "<h3><b>$totalOrders</b> new order is available online.</h3>
                    <p>Please login to your outreach site to view the results.</p>";
        }

        $siteUrl = self::SITE_URL;
        $labName = self::LabName;

        $html = "
            <!doctype html>
            <html>
            <head>
                <meta name='viewport' content='width=device-width'>
                <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
                <title>$labName Notification</title>
                <link rel='shortcut icon' href='http://avalondemo.com/outreach/images/avalon.ico'>
                <style>
                    *{font-family:'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;font-size:100%;line-height:1.6em;margin:0;padding:0}img{max-width:600px;width:auto}body{-webkit-font-smoothing:antialiased;height:100%;-webkit-text-size-adjust:none;width:100% !important}a{color:#2ecc71}
                    .btn-primary{margin-bottom:10px;width:auto !important}
                    .btn-primary td{
                        -webkit-border-radius:25px !important;
                        -moz-border-radius:25px !important;
                        border-radius:25px;
                        font-family:'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif;
                        font-size:14px;text-align:center;vertical-align:top
                    }
                    .last{margin-bottom:0}.first{margin-top:0}.padding{padding:10px 0}table.body-wrap{padding:20px;width:100%}table.body-wrap .container{border:1px solid #f0f0f0}table.footer-wrap{clear:both !important;width:100%}.footer-wrap .container p{color:#666666;font-size:12px}table.footer-wrap a{color:#999999}h1,h2,h3{color:#111111;font-family:'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif;font-weight:200;line-height:1.2em;margin:40px 0 10px}h1{font-size:36px}h2{font-size:28px}h3{font-size:22px}p,ul,ol{font-size:14px;font-weight:normal;margin-bottom:10px}ul li,ol li{margin-left:5px;list-style-position:inside}.container{clear:both !important;display:block !important;margin:0 auto !important;max-width:600px !important}.body-wrap .container{padding:20px}.content{display:block;margin:0 auto;max-width:600px}.content table{width:100%}
                </style>
                <!--[if mso]>&nbsp;<![endif]-->
            </head>
            <body bgcolor='#f6f6f6'>
                <table class='body-wrap' bgcolor='#f6f6f6'>
                  <tr>
                    <td></td>
                    <td class='container' bgcolor='#FFFFFF'>
                      <div class='content'>
                      <table>
                        <tr>
                          <td>
                            $htmlContent
                          </td>
                        </tr>
                        <tr class='btn-primary'>
                            <td>
                                <a href='$siteUrl' style='background-color:#2ecc71;border:solid 1px #2ecc71;-webkit-border-radius:25px !important;-moz-border-radius:25px !important;border-radius:25px;border-width:10px 20px;display:inline-block;color:#FFFFFF;cursor:pointer;font-weight:bold;line-height:2;text-decoration:none;'>Click Here to Login to $labName Physician Outreach Portal</a>
                            </td>
                        </tr>
                      </table>
                      </div>
                    </td>
                    <td></td>
                  </tr>
                </table>
            </body>
            </html>
        ";

        return $html;
    }
    
}