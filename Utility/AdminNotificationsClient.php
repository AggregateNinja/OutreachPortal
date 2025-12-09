<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'PageClient.php';
require_once 'IClient.php';
require_once 'DAOS/UserNotificationDAO.php';
require_once 'DAOS/ClientDAO.php';
require_once 'DAOS/DoctorDAO.php';

/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 6/14/2017
 * Time: 3:38 PM
 */
class AdminNotificationsClient extends PageClient implements IClient {

    private $NotificationDAO;

    private $Notifications;

    private $Action;

    private $InputFields = array(
        "idNotifications" => "",
        "notificationTypeId" => "",
        "notificationTitle" => "",
        "notificationText" => "",
        "continuous" => false,
        "dateFrom" => "",
        "dateTo" => ""
    );

    private $ErrorMessages = array();

    public function __construct(array $data = null) {
        parent::__construct($data);

        $this->addStylesheet("/outreach/css/datepicker.css");
        /*$this->addStylesheet("/outreach/css/dropdown.css");*/
        $this->addStylesheet("/outreach/admin/css/notifications.css");

        $this->addScript("/outreach/js/tinymce/tinymce.min.js");
        $this->addScript("/outreach/js/datepicker.js");
        $this->addScript("/outreach/js/datepicker.en.js");
        $this->addScript("/outreach/js/velocity.min.js");
        $this->addScript("/outreach/js/tooltip.js");
        /*$this->addScript("/outreach/js/dropdown.min.js");*/
        $this->addScript("/outreach/admin/js/notifications.js");
        $this->addScript("/outreach/admin/js/notifications.validate.js");
        $this->addScript("/outreach/js/components/tabs.js");

        $this->NotificationDAO = new UserNotificationDAO(array("Conn" => $this->Conn));


        $this->Action = 0;
        if (isset($_GET['action'])) {
            $this->Action = $_GET['action'];
        }

        if (isset($_SESSION['ErrorMessages'])) {
            $this->ErrorMessages = $_SESSION['ErrorMessages'];
            $_SESSION['ErrorMessages'] = "";
            unset($_SESSION['ErrorMessages']);
        }
        if (isset($_SESSION['InputFields'])) {
            $this->InputFields = $_SESSION['InputFields'];
            $_SESSION['InputFields'] = "";
            unset($_SESSION['InputFields']);
        }
    }

    public function printPage() {
        $lnkNewNotification = "";
        if ($this->Action == 2 && isset($_GET['id']) && !empty($_GET['id'])) { // edit
            $notificationHtml = $this->getNotificationForm($_GET['id']);
        } else if ($this->Action == 1) { // add
            $notificationHtml = $this->getNotificationForm();
        } else { // view
            $notificationHtml = $this->getNotificationsTable();
            $lnkNewNotification = "<a href='notifications.php?action=1' id='lnkNew' class='button'>New Notification</a>";
        }

        $html = "
            <div class='container'>
                <div class='row pad-top pad-bottom'>
                    <div class='one mobile whole'>
                        <h3 style='display: inline;'>User Notifications</h3>
                        $lnkNewNotification
                    </div>
                </div>
                $notificationHtml
            </div>";

        echo $html;
    }

    private function getNotificationForm($notificationId = null) {

        $dteNow = new DateTime();
        $action = $this->Action;
        $idNotifications = "";
        $notificationTitle = "";
        $notificationText = "";
        //$dateFrom = $dteNow->format('m/d/Y h:i A');
        $dateFrom = "";
        $dateTo = "";
        $continuous = "";

        $dateToDisabled = "";
        $notificationIdInput = "";
        $arySelectedClientIds = array();
        $arySelectedDoctorIds = array();
        if (isset($notificationId)) {
            $notification = $this->NotificationDAO->getNotification($notificationId);
            $arySelectedClientIds = $notification->ClientIds;
            $arySelectedDoctorIds = $notification->DoctorIds;

            if (isset($notification) && !empty($notification)) {
                $this->InputFields['idNotifications'] = $notification->idNotifications;
                $this->InputFields['notificationTitle'] = $notification->notificationTitle;
                $this->InputFields['notificationText'] = $notification->notificationText;

                $dateFrom = new DateTime($notification->dateFrom);
                $this->InputFields['dateFrom'] = $dateFrom->format('m/d/Y h:i A');

                if (isset($notification->dateTo) && !empty($notification->dateTo)) {
                    $dateTo = new DateTime($notification->dateTo);
                    $this->InputFields['dateTo'] = $dateTo->format('m/d/Y h:i A');
                } else {
                    $continuous = "checked='checked'";
                    $dateToDisabled = "disabled";
                }

                $notificationIdInput = "<input type='hidden' name='idNotifications' id='idNotifications' value='" . $this->InputFields['idNotifications'] . "' />";
            }
        }

        $notificationTextTooltip = "";
        $dateFromTooltip = "";
        $dateToTooltip = "";
        if (array_key_exists("notificationText", $this->ErrorMessages)) {
            $notificationTextTooltip = "<div id='notificationText' class='tooltip' style='display: block;'>" . $this->ErrorMessages['notificationText'] . "</div>";
        }
        if (array_key_exists("dateFrom", $this->ErrorMessages)) {
            $dateFromTooltip = "<div id='dateFrom' class='tooltip' style='display: block; left: 10%;'>" . $this->ErrorMessages['dateFrom'] . "</div>";
        }
        if (array_key_exists("dateTo", $this->ErrorMessages)) {
            $dateToTooltip = "<div id='dateTo' class='tooltip' style='display: block; left: 0;'>" . $this->ErrorMessages['dateTo'] . "</div>";
        }

        $clientsHtml = $this->getClientsHtml($arySelectedClientIds);
        $doctorsHtml = $this->getDoctorsHtml($arySelectedDoctorIds);

        $assignToHtml = "
        <div class='tabs ipad'>
            <ul role='tablist'>
                <li role='tab' aria-controls='#tab1'>Clients</li>
                <li role='tab' aria-controls='#tab2'>Doctors</li>
            </ul>
            <div id='tab1' role='tabpanel'>$clientsHtml</div>
            <div id='tab2' role='tabpanel' style='display: none;'>$doctorsHtml</div>
        </div>";

        $html = "
        <form action='notificationsb.php' method='post' name='frmNotifications' id='frmNotifications'>
        <input type='hidden' name='action' id='action' value='$action' />
        $notificationIdInput
        <div class='row'>
            <div class='one mobile whole'>
                <label for='notificationTitle'>Title: </label>
                <input type='text' name='notificationTitle' id='notificationText' value='" . $this->InputFields['notificationTitle'] . "' />
            </div>

            <div class='one mobile whole'>
                <label for='notificationText'>Notification: </label>
                <textarea name='notificationText' id='notificationText'>" . $this->InputFields['notificationText'] . "</textarea>
                $notificationTextTooltip
            </div>

            <div class='one mobile whole pad-top'>
                <label for='continuous'>Continuous</label>
                <input type='checkbox' name='continuous' id='continuous' class='tooltipped' value='1' $continuous data-position='top' data-tooltip='This notification will never expire' />
            </div>

            <div class='one mobile half'>
                <div class='row'>
                    <div class='one mobile half'>
                        $dateFromTooltip
                        <label for='dateFrom'>Display from</label>
                        <input type='text' name='dateFrom' id='dateFrom' value='" . $this->InputFields['dateFrom'] . "' data-time-format='hh:ii AA' class='datepicker-here' data-timepicker='true' data-language='en' />
                        
                    </div>
                    <div class='one mobile half'>
                        <label for='dateTo'>to</label>
                        <input type='text' name='dateTo' id='dateTo' value='" . $this->InputFields['dateTo'] . "' data-time-format='hh:ii AA' class='datepicker-here $dateToDisabled' data-timepicker='true' data-language='en' $dateToDisabled />
                        $dateToTooltip
                    </div>
                </div>
            </div>

            <div class='one mobile half' id='clientGroup'>
                <label>Assign to:</label>
                $assignToHtml
            </div>

            <div class='one mobile whole pad-top'>
                <a href='notifications.php' class='button' style='float: left;'>Cancel</a>
                <button class='green submit pull-right' id='btnAddSubmit'>Submit</button>
            </div>
        </div>
        </form>";

        return $html;
    }

    private function getClientsHtml(array $clientIds) {
        $clients = ClientDAO::getClients(array("startRow" => 0, "numRows" => 999999, "orderBy" => "clientNo", "WebClientsOnly" => true), $this->Conn);
        $allClientsChecked = "";
        if (count($clientIds) == 0) {
            $allClientsChecked = "checked='checked'";
        }
        $clientsHtml = "
        <div class='row'>
            <div class='four mobile fifths'><b>Client Name (#)</b></div>
            <div class='one mobile fifth'></div>
        </div>
        <div class='row clientRow'>
            <div class='four mobile fifths pad-left'>All clients</div>
            <div class='one mobile fifth'><input type='checkbox' name='clientIds[]' data-id='0' value='0' $allClientsChecked /></div>
        </div>";

        $aryDistinctClientIds = array();
        foreach ($clients as $client) {
            $idClients = $client->idClients;
            $clientNo = $client->clientNo;
            $clientName = $client->clientName;
            $checked = "";

            if (!in_array($idClients, $aryDistinctClientIds)) {
                $aryDistinctClientIds[] = $idClients;

                if (in_array($idClients, $clientIds)) {
                    $checked = "checked='checked'";
                }

                $clientsHtml .= "<div class='row clientRow'>
                    <div class='four mobile fifths pad-left'>$clientName ($clientNo)</div>
                    <div class='one mobile fifth'><input type='checkbox' name='clientIds[]' data-id='$idClients' value='$idClients' $checked /></div>
                </div>";
            }


        }
        return $clientsHtml;
    }

    private function getDoctorsHtml(array $doctorIds) {
        $doctors = DoctorDAO::getDoctors(array("startRow" => 0, "numRows" => 999999, "orderBy" => "number", "WebDoctorsOnly" => true), $this->Conn);
        $allDoctorsChecked = "";
        if (count($doctorIds) == 0) {
            $allDoctorsChecked = "checked='checked'";
        }
        $doctorsHtml = "
        <div class='row'>
            <div class='four mobile fifths'><b>Doctor Name (#)</b></div>
            <div class='one mobile fifth'></div>
        </div>
        <div class='row doctorRow'>
            <div class='four mobile fifths pad-left'>All doctors</div>
            <div class='one mobile fifth'><input type='checkbox' name='doctorIds[]' data-id='0' value='0' $allDoctorsChecked /></div>
        </div>";

        $aryDistinctDoctorIds = array();
        foreach ($doctors as $doctor) {
            $iddoctors = $doctor->iddoctors;
            $number = $doctor->number;
            $firstName = $doctor->firstName;
            $lastName = $doctor->lastName;
            $checked = "";

            if (!in_array($iddoctors, $aryDistinctDoctorIds)) {
                $aryDistinctDoctorIds[] = $iddoctors;

                if (in_array($iddoctors, $doctorIds)) {
                    $checked = "checked='checked'";
                }

                $doctorsHtml .= "<div class='row doctorRow'>
                    <div class='four mobile fifths pad-left'>$firstName $lastName ($number)</div>
                    <div class='one mobile fifth'><input type='checkbox' name='doctorIds[]' data-id='$iddoctors' value='$iddoctors' $checked /></div>
                </div>";
            }
        }
        return $doctorsHtml;
    }

    private function getNotificationsTable() {
        $this->Notifications = $this->NotificationDAO->getNotifications();

        $html = "";

        if (isset($this->Notifications) && !empty($this->Notifications)) {
            $html = "
            <div class='row' id='headerRow'>
                <div class='one mobile twelfth'></div>
                <div class='four mobile twelfths'>Title<a href='javascript:void(0)' id='title' class='sort'><i class='icon-sort'></i></a></div>
                <div class='three mobile twelfths'>Date From<a href='javascript:void(0)' id='dateFrom' class='sort'><i class='icon-sort'></i></a></div>
                <div class='three mobile twelfths'>Date To<a href='javascript:void(0)' id='dateTo' class='sort'><i class='icon-sort'></i></a></div>
                <div class='one mobile twelfth'>Status<a href='javascript:void(0)' id='status' class='sort'><i class='icon-sort'></i></a></div>
            </div>
            ";

            foreach ($this->Notifications as $notification) {
                $idNotifications = $notification->idNotifications;
                $notificationTitle = $notification->notificationTitle;
                $notificationText = $notification->notificationText;
                $notificationIsActive = $notification->isActive;
                $dateFrom = new DateTime($notification->dateFrom);
                $dateFrom = $dateFrom->format('m/d/Y h:i A');
                $dateTo = "";
                $status = "Active";
                $statusLink = "Deactivate";
                $notificationToggle = 0;
                if (isset($notification->dateTo) && !empty($notification->dateTo)) {
                    $dateTo = new DateTime($notification->dateTo);
                    $dateTo = $dateTo->format('m/d/Y h:i A');
                }


                if ($notificationIsActive == 0) {
                    $status = "Inactive";
                    $statusLink = "Activate";
                    $notificationToggle = 1;
                }

                $lnkIconsHtml = "
                <div class='dropdown'>
                    <button class='btn btn-default dropdown-toggle button' type='button' id='menu' data-toggle='dropdown' title='Modify this notification'>
                        <i class='icon-ellipsis-vertical'></i>
                    </button>
                    <ul class='dropdown-menu' role='menu' aria-labelledby='menu'>
                        <li role='presentation'><a href='/outreach/admin/notifications.php?action=2&id=$idNotifications' id='$idNotifications'>Edit</a></li>
                        <li role='separator' class='divider'></li>
                        <li role='presentation'><a href='javascript:void(0)' data-id='$idNotifications' data-active='$notificationToggle' class='toggle'>$statusLink</a></li>
                    </ul>
                </div>";

                $html .= "
                <div class='row notification'>
                    <div class='one mobile twelfth' style='padding-left: 5px;'>$lnkIconsHtml</div>
                    <div class='four mobile twelfths'>$notificationTitle</div>
                    <div class='three mobile twelfths'>$dateFrom</div>
                    <div class='three mobile twelfths'>$dateTo</div>
                    <div class='one mobile twelfth'>$status</div>
                </div>
                ";
            }
        }

        return $html;
    }
}