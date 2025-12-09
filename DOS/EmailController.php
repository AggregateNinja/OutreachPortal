<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 5/16/2016
 * Time: 12:01 PM
 */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
// Load Composer's autoloader
require 'vendor/autoload.php';

require_once 'BaseObject.php';
require_once 'Utility/IConfig.php';
require_once 'Utility/FormValidator.php';

class EmailController extends BaseObject implements IConfig {
    protected $Data = array(
        "Sender" => "",
        "SenderName" => "",
        "Recipient" => "",
        "Subject" => "",
        "HtmlBody" => "",
        "TextBody" => "",
        "Host" => "",
        "Port" => 587,
        "Username" => "",
        "Password" => "",
        "UserConfigSet" => true,
        "ConfigSet" => ""
    );

    private $Headers;
    private $MimeParams;
    private $SmtpParams;

    private $Mailer;

    // https://pear.php.net/manual/en/package.mail.mail-mime.example.php
    public function __construct(array $data = null) {
        if (empty($this->SiteUrl)) {
            $this->SiteUrl = self::SITE_URL;
        }
        if (empty($this->Logo)) {
            $this->Logo = self::Logo;
        }
        if (empty($this->LabName)) {
            $this->LabName = self::LabName;
        }

        /*
        $aryData = array(
            "From" => "Computer Service and Support <info@avalondemo.com>",
            "To" => "Edward Bossmeyer <ebossmeyer@csslis.com>",
            "Subject" => "Hi!",
            "Body" => "Hi,\n\nHow are you?",
            "Host" => "mail.avalondemo.com",
            "Username" => "info@avalondemo.com",
            "Password" => "C$$2015EBoss"
        );
        $emailController = new EmailController($aryData);
        $emailController->send();
        */
        parent::__construct($data);


        $this->Mailer = new PHPMailer(true);

        try {
            //$this->Mailer->SMTPDebug = SMTP::DEBUG_LOWLEVEL;
            // Specify the SMTP settings.
            $this->Mailer->isSMTP();
            $this->Mailer->setFrom($this->Data['Sender'], $this->Data['SenderName']);
            $this->Mailer->Username   = $this->Data['Username'];
            $this->Mailer->Password   = $this->Data['Password'];
            $this->Mailer->Host       = $this->Data['Host'];
            $this->Mailer->Port       = $this->Data['Port'];
            $this->Mailer->SMTPAuth   = true;
            $this->Mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged, ENCRYPTION_STARTTLS

            if ($this->Data['UserConfigSet'] == true) {
                $this->Mailer->addCustomHeader('X-SES-CONFIGURATION-SET', $this->Data['ConfigSet']);
            }

            /*$url = str_replace(array("https://", "http://", "www.", "/outreach/"), "", self::SITE_URL);
            $this->Mailer->DKIM_domain = $url;
            $this->Mailer->DKIM_private = '/etc/ssl/outreach/email/privateKey.txt';
            $this->Mailer->DKIM_selector = 'default';
            $this->Mailer->DKIM_identity = $this->Data['Sender'];*/

            /*$mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );*/

            // Specify the message recipients.
            $this->Mailer->addAddress($this->Data['Recipient']);
            // You can also add CC, BCC, and additional To recipients here.

            // Specify the content of the message.
            $this->Mailer->isHTML(true);
            $this->Mailer->Subject    = $this->Data['Subject'];
            $this->Mailer->Body       = $this->Data['HtmlBody'];
            $this->Mailer->AltBody    = $this->Data['TextBody'];

        } catch (phpmailerException $e) {
            error_log("An error occurred: " . $e->errorMessage()); //Catch errors from PHPMailer.
        } catch (Exception $e) {
            error_log("Email not sent: " . $this->Mailer->ErrorInfo); //Catch errors from Amazon SES.
        }
    }

    public function setTo($to) {
        $this->Data['Recipient'] = $to;
    }
    public function setSubject($subject) {
        $this->Data['Subject'] = $subject;
    }
    public function setBody($body) {
        $this->Data['HtmlBody'] = $body;
    }

    public function send() {
        if (isset($this->Data['Recipient']) && isset($this->Data['Sender'])) {
            $recipient = trim($this->Data['Recipient']);
            $sender = trim($this->Data['Sender']);

            if (!empty($recipient) && !empty($sender) && !empty($this->Data['Subject']) && !empty($this->Data['HtmlBody'])
                && FormValidator::isValidEmail($this->Data['Recipient']) && FormValidator::isValidEmail($this->Data['Sender'])) {
                try {
                    $this->Mailer->Send();
                } catch (phpmailerException $e) {
                    error_log("An error occurred Sending Email: " . $e->errorMessage()); //Catch errors from PHPMailer.
                } catch (Exception $e) {
                    error_log("Email not sent: " . $this->Mailer->ErrorInfo . ", Recipient: " . $this->Data['Recipient'] . ", Sender: " . $this->Data['Sender']); //Catch errors from Amazon SES.
                }
            } else {
                error_log("2 Required email parameters were not set, Recipient: " . $this->Data['Recipient'] . ", Sender: " . $this->Data['Sender']);
            }
        } else {
            error_log("1 Required email parameters were not set, Recipient: " . $this->Data['Recipient'] . ", Sender: " . $this->Data['Sender']);
        }
    }

}