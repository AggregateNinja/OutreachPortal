<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 5/31/2016
 * Time: 3:30 PM
 */
require_once 'PageClient.php';
require_once 'DAOS/EmailNotificationDAO.php';
require_once 'DAOS/ResultLogDAO.php';

class AdminEmailClient extends PageClient {

    private $Users;

    private $EmailDAO;

    private $InactiveUserIds;

    public function __construct(array $settings = null) {
        parent::__construct($settings);

        $this->addStylesheet("/outreach/css/bootstrap.css");
        $this->addStylesheet("css/emails.css");
        $this->addScript("/outreach/js/bootstrap.min.js");
        $this->addScript("js/emails.js");

        $this->Users = $this->UserDAO->getClientDoctorUsers();

        $this->EmailDAO = new EmailNotificationDAO(array("Conn" => $this->UserDAO->Conn));

        $this->InactiveUserIds = $this->EmailDAO->getInactiveUserIds();

        ResultLogDAO::addAdminLogEntry($this->User->idUsers, array(
            "adminUserId" => $this->User->idUsers,
            "Ip" => $this->Ip,
            "Conn" => $this->UserDAO->Conn,
            "userTypeId" => 1,
            "email" => '',
            "action" => 7
        ));

        //echo "<pre>"; print_r($this->InactiveUserIds); echo "</pre>";
    }

    public function printPage() {

        $tabOneActive = "active";
        $tabTwoActive = "";

        $clientsHtml = "
            <div class='row'>
                <div class='one mobile eighth'><a href='javascript:void(0)' data-id='clientNo' class='sort'>#<i class=\"icon-sort\"></i></a></div>
                <div class='three mobile eighths'><a href='javascript:void(0)' data-id='clientName' class='sort'>Client Name<i class=\"icon-sort\"></i></a></div>
                <div class='three mobile eighths'><a href='javascript:void(0)' data-id='clientEmail' class='sort'>Email<i class=\"icon-sort\"></i></a></div>
                <div class='one mobile eighth'><a href='javascript:void(0)' data-id='clientInactive' class='sort'>Inactive<i class=\"icon-sort\"></i></a></div>
            </div>";
        $doctorsHtml = "
            <div class='row'>
                <div class='one mobile eighth'><a href='javascript:void(0)' data-id='doctorNo' class='sort'>#<i class=\"icon-sort\"></i></a></div>
                <div class='three mobile eighths'><a href='javascript:void(0)' data-id='doctorName' class='sort'>Doctor Name<i class=\"icon-sort\"></i></a></div>
                <div class='three mobile eighths'><a href='javascript:void(0)' data-id='doctorEmail' class='sort'>Email<i class=\"icon-sort\"></i></a></div>
                <div class='one mobile eighth'><a href='javascript:void(0)' data-id='doctorInactive' class='sort'>Inactive<i class=\"icon-sort\"></i></a></div>
            </div>";

        foreach ($this->Users as $user) {

            $checked = "";

            if (in_array($user->idUsers, $this->InactiveUserIds)) {
                $checked = "checked='checked'";
            }

            if ($user->typeId == 2) {
                $clientsHtml .= "
                    <div class='row'>
                        <div class='one mobile eighth'>$user->clientNo</div>
                        <div class='three mobile eighths'>$user->clientName</div>
                        <div class='three mobile eighths'>$user->email</div>
                        <div class='one mobile eighth'><input type='checkbox' name='inactiveUserIds[]' value='$user->idUsers' $checked /></div>
                    </div>";
            } else if ($user->typeId == 3) {
                $doctorName = $user->firstName . " " . $user->lastName;

                $doctorsHtml .= "
                    <div class='row'>
                        <div class='one mobile eighth'>$user->number</div>
                        <div class='three mobile eighths'>$doctorName</div>
                        <div class='three mobile eighths'>$user->email</div>
                        <div class='one mobile eighth'><input type='checkbox' name='inactiveUserIds[]' value='$user->idUsers' $checked /></div>
                    </div>";
            }
        }

        $html = "
        <div class='container' style='margin-top: 10px; margin-bottom: 80px;'>
            <div class='row rounded box_shadow'>
                <div class='one mobile whole padded centered'>
                    <div class='row'>
                        <div class='one mobile third'>
                            <h3>Email Notification Settings</h5>
                        </div>
                        <div class='two mobile thirds' id='err'></div>
                    </div>
                    
                    <div class='row'>
                        <div class='one mobile whole'>
                            <ul class='nav nav-tabs' role='tablist'>
                                <li role='presentation' class='$tabOneActive' id='clientsTab'><a href='#clients' aria-controls='clients' role='tab' data-toggle='tab'>Clients</a></li>
                                <li role='presentation' class='$tabTwoActive' id='doctorsTab'><a href='#doctors' aria-controls='doctors' role='tab' data-toggle='tab'>Doctors</a></li>
                            </ul>
                        </div>
                        
                        <!-- Tab panes -->
                        <form name='frmEmailToggle' id='frmEmailToggle' action='process.php' method='post'>
                        <input type='hidden' name='action' value='7'/>
                        <div class='tab-content'>
                            <div role='tabpanel' class='tab-pane $tabOneActive' id='clients'>
                                <h5>Toggle email notifications for Client Users</h5>
                                $clientsHtml
                            </div>
                            <div role='tabpanel' class='tab-pane $tabTwoActive' id='doctors'>
                                <h5>Toggle email notifications for Doctor Users</h5>
                                $doctorsHtml
                            </div>
                        </div>
                        </form>
                    </div>
                    
                </div>
                
                <div class='one mobile whole padded'>
                    <a href=\"javascript:void(0)\" id=\"submit\" class=\"button\" style=\"padding: 2px 6px 3px 6px; margin-right: 2px;\">
                    Submit</a>
                </div>
            </div>
            
        </div>
        ";


        echo $html;
    }
}