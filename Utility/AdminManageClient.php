<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'PageClient.php';
require_once 'IClient.php';
require_once 'DAOS/UserDAO.php';
require_once 'DAOS/ResultUserTypeDAO.php';
require_once 'DAOS/AdminDAO.php';
require_once 'DAOS/TestDAO.php';
require_once 'DAOS/DiagnosisDAO.php';
require_once 'DAOS/ClientDAO.php';
require_once 'DAOS/DoctorDAO.php';
require_once 'DAOS/SalesDAO.php';
require_once 'DAOS/OrderAccessSettingDAO.php';


class AdminManageClient extends PageClient implements IClient {

    private $Action = 1; // 1 == add, 2 == edit

    private $PageTitle = "Add User";

    private $SignedInUserId = null;

    private $UserTypes;
    private $ClientUsers;
    private $UserSettings;
    private $AdminSettings;
    private $OrderEntrySettings;

    private $SignedInAdmin;
    private $Tests;

    private $IsMasterAdmin = false;
    private $PrintErrorMessages = false;
    private $ErrorMessages;

    private $UserId = 0;
    private $Email = "";
    private $TypeId = 0;
    private $ClientId = "";
    private $DoctorId = "";
    private $PatientId = "";
    private $SalesmanId = "";
    private $InsuranceId = "";
    private $Number = "";
    private $Name = "";
    private $Phone = "";
    private $Address = "";
    private $City = "";
    private $State = "";
    private $Zip = "";
    private $LocationName = "";
    private $LocationNumber = "";
    private $LocationId = "";
    private $AdminClientIds = array();

    private $HasMultiUser = false;
    private $MultiUsers = null;
    private $GroupName = "";
    private $GroupLeader = "";
    private $Territory = "";
    private $OrderAccess = "";

    private $Password = "";
    private $Password2 = "";
    private $SelectedUserSettings = array();
    private $SelectedAdminSettings = array();
    private $SelectedSalesSettings = array();
    private $SelectedOrderEntrySettings = array();
    private $SelectedMultiUsers = array();
    private $SelectedCommonTests = array();
    private $SelectedExcludedTests = array();
    private $SelectedCommonCodes = array();
    private $SelectedCommonDrugs = array();

    private $OrderAccessSettings = array();
    private $RestrictedUsers = array();

    private $Clients = array();
    private $Doctors = array();

    public function __construct(array $data = null) {

        parent::__construct();

        $conn = parent::__get("Conn");

        if ($data != null) { // Used primarily for Editing a user
            if (array_key_exists("id", $data)) {
                $this->UserId = $data['id'];
            }
            if (array_key_exists("type", $data)) {
                $this->TypeId = $data['type'];
            }
            if (array_key_exists("action", $data)) {
                $this->Action = $data['action'];
                if ($data['action'] == 2) {
                    $this->PageTitle = "Edit User";
                }
            }
        }

        $this->setErrorMessages();
        $this->setInputFields();

        $this->SignedInUserId = $this->User->idUsers;



        $this->UserTypes = ResultUserTypeDAO::getResultUserTypes(array("Conn" => $this->Conn));

        $clientUserOptions = array(
            "Conn" => $this->Conn,
            "OrderBy" => "clientNo",
            "Direction" => "ASC"
        );
        if (isset($this->User->clientId) && !empty($this->User->clientId) && is_numeric($this->User->clientId)) {
            $clientUserOptions['clientId'] = $this->User->clientId;
        }
        $this->ClientUsers = AdminDAO::getUserList($clientUserOptions);

        $this->Clients = ClientDAO::getClients(array("startRow" => 0, "numRows" => 999999, "orderBy" => "clientNo"), $this->Conn);

        $this->UserSettings = AdminDAO::getUserSettings(null, array("Conn" => $this->Conn));

        $this->AdminSettings = AdminDAO::getAdminSettings(array("Conn" => $this->Conn));

        //$this->SalesSettings = SalesDAO::getSalesSettings(array("Conn" => $conn));
        $this->OrderEntrySettings = AdminDAO::getOrderEntrySettings(array("Conn" => $conn));

        $this->SignedInAdmin = AdminDAO::getAdmin(array("idUsers" => $this->SignedInUserId), array("IncludeAdminSettings" => true, "Conn" => $this->Conn));

        //$this->Tests = TestDAO::getTests(array("userId" => $this->SignedInUserId), array("WithoutPOC" => true, "OrderByCommonTests" => true, "Conn" => $this->Conn));

        if (isset($this->SignedInAdmin) && $this->SignedInAdmin instanceof AdminUser && $this->SignedInAdmin->hasSettingByName("Is Master Admin")) {
            $this->IsMasterAdmin = true;
        }

        $this->OrderAccessSettings = OrderAccessSettingDAO::getSettings(array("Conn" => $this->Conn));

        $this->addStylesheet("css/manage.css");
        $this->addScript("js/admin.js");
        $this->addScript("js/orderentry.js");
        $this->addScript("js/admin.validate.js");
        $this->addScript("/outreach/js/velocity.min.js");
        $this->addScript("/outreach/js/tooltip.js");

        $this->addOverlay("
            <div id=\"noOtherUsersOverlay\" class=\"rounded\">
                <div class=\"row\">
                    <div class=\"one mobile whole\" style=\"text-align: center\">
                        <p style=\"display: inline; font-weight: bold;\">There are no other users to select for this <div id=\"userTypeText\"></div></p>
                    </div>
                </div>
                <div class=\"row\">
                    <div class=\"one mobile whole\" style=\"text-align: center\">
                        <a href=\"javascript:void(0)\" id=\"noOtherUsersOkay\" class=\"button\">Okay</a>
                    </div>
                </div>
            </div>
        ");

        //echo "<pre>"; print_r($this->User); echo "</pre>";
        $this->Doctors = $this->UserDAO->getRelatedDoctors();
        //echo "<pre>"; print_r($this->Doctors); echo "</pre>";
    }

    private function setErrorMessages() {
        if (isset($_SESSION['errMsg']) && !empty($_SESSION['errMsg'])) {
            $this->ErrorMessages = $_SESSION['errMsg'];
            $this->PrintErrorMessages = true;
            $_SESSION['errMsg'] = "";
            unset($_SESSION['errMsg']);
        }
    }

    public function setInputFields() {
        if (array_key_exists("inputFields", $_SESSION) && $this->PrintErrorMessages == true) { // the processing page kicked back an error message & the previously submitted input fields
            $inputFields = $_SESSION['inputFields'];
            $_SESSION['inputFields'] = "";
            unset($_SESSION['inputFields']);

            //echo "<pre>"; print_r($inputFields); echo "</pre>";

            if (array_key_exists("email", $inputFields) && !empty($inputFields['email'])) {
                $this->Email = $inputFields['email'];
            }
            if (array_key_exists("typeId", $inputFields) && !empty($inputFields['typeId'])) {
                $this->TypeId = $inputFields['typeId'];
                switch ($this->TypeId) {
                    case 2:
                        $client = ClientDAO::getClient(array("idClients" => $inputFields['clientId']), array("Conn" => $this->Conn));



                        if (!is_bool($client)) {
                            $this->Name = $client->clientName;
                            $this->ClientId = $client->idClients;
                            $this->Number = $client->clientNo;
                            $this->Address = $client->clientStreet;
                            $this->City = $client->clientCity;
                            $this->State = $client->clientState;
                            $this->Zip = $client->clientZip;
                            $this->LocationName = $client->locationName;
                            $this->LocationNumber = $client->locationNo;
                        }
                        break;
                    case 3:
                        $doctor = DoctorDAO::getDoctor(array("iddoctors" => $inputFields['doctorId']), array("Conn" => $this->Conn));
                        if (!is_bool($doctor)) {
                            $this->Name = $doctor->lastName . ", " . $doctor->firstName;
                            $this->DoctorId = $doctor->iddoctors;
                            $this->Number = $doctor->number;
                            $this->Address = $doctor->address1;
                            $this->City = $doctor->city;
                            $this->State = $doctor->state;
                            $this->Zip = $doctor->zip;
                            $this->LocationName = $doctor->locationName;
                            $this->LocationNumber = $doctor->locationNo;
                        }
                        break;
                    case 4:
                        $userDAO = new UserDAO(
                            array("userId" => $this->UserId, "typeId" => $this->TypeId),
                            array(
                                "IncludeCommonCodes" => true,
                                "IncludeUserSettings" => true,
                                "IncludeCommonTests" => true,
                                "IncludeExcludedTests" => true,
                                "IncludeDetailedInfo" => true,
                                "IncludeCommonDrugs" => true
                            )
                        );
                        $userDAO->getUser();

                        $this->PatientId = $userDAO->User->idPatients;
                        $this->Name = $userDAO->User->firstName . " " . $userDAO->User->lastName;
                        break;
                    default: break;
                }

            }
            if (array_key_exists("password", $inputFields) && !empty($inputFields['password'])) {
                $this->Password = $inputFields['password'];
            }
            if (array_key_exists("password2", $inputFields) && !empty($inputFields['password2'])) {
                $this->Password2 = $inputFields['password2'];
            }
            if (array_key_exists("userSettings", $inputFields) && !empty($inputFields['userSettings']) && is_array($inputFields['userSettings']) && count($inputFields['userSettings']) > 0) {
                $this->SelectedUserSettings = $inputFields['userSettings'];
            }
            if (array_key_exists("orderEntrySettings", $inputFields) && !empty($inputFields['orderEntrySettings']) && is_array($inputFields['orderEntrySettings']) && count($inputFields['orderEntrySettings']) > 0) {
                $this->SelectedOrderEntrySettings = $inputFields['orderEntrySettings'];
            }
            if (array_key_exists("adminSettings", $inputFields) && !empty($inputFields['adminSettings']) && is_array($inputFields['adminSettings']) && count($inputFields['adminSettings']) > 0) {
                $this->SelectedAdminSettings = $inputFields['adminSettings'];
            }
            if (array_key_exists("salesSettings", $inputFields) && !empty($inputFields['salesSettings']) && is_array($inputFields['salesSettings']) && count($inputFields['salesSettings']) > 0) {
                $this->SelectedSalesSettings = $inputFields['salesSettings'];
            }
            if (array_key_exists("multiUsers", $inputFields) && !empty($inputFields['multiUsers']) && is_array($inputFields['multiUsers']) && count($inputFields['multiUsers']) > 0) {
                $this->SelectedMultiUsers = $inputFields['multiUsers'];
            }
            if (array_key_exists("commonTests", $inputFields) && !empty($inputFields['commonTests']) && is_array($inputFields['commonTests']) && count($inputFields['commonTests']) > 0) {
                $this->SelectedCommonTests = $inputFields['commonTests'];
            }
            if (array_key_exists("excludedTests", $inputFields) && !empty($inputFields['excludedTests']) && is_array($inputFields['excludedTests']) && count($inputFields['excludedTests']) > 0) {
                $this->SelectedExcludedTests = $inputFields['excludedTests'];
            }
            if (array_key_exists("commonDiagnosisCodes", $inputFields) && !empty($inputFields['commonDiagnosisCodes']) && is_array($inputFields['commonDiagnosisCodes']) && count($inputFields['commonDiagnosisCodes']) > 0) {
                foreach ($inputFields['commonDiagnosisCodes'] as $idDiagnosisCode) {
                    $this->SelectedCommonCodes[] = DiagnosisDAO::getDiagnosisCode($idDiagnosisCode, array("Conn" => $this->Conn));
                }
            }
            if (array_key_exists("commonDrugs", $inputFields) && !empty($inputFields['commonDrugs']) && is_array($inputFields['commonDrugs']) && count($inputFields['commonDrugs']) > 0) {
                $this->SelectedCommonDrugs = $inputFields['commonDrugs'];
            }

            // add code for repopulating selected common prescriptions on a error redirect

        } else if ($this->Action == 2) { // the edit page just loaded

            $userDAO = new UserDAO(
                array("userId" => $this->UserId, "typeId" => $this->TypeId),
                array(
                    "IncludeCommonCodes" => true,
                    "IncludeUserSettings" => true,
                    "IncludeCommonTests" => true,
                    "IncludeExcludedTests" => true,
                    "IncludeDetailedInfo" => true,
                    "IncludeCommonDrugs" => true
                )
            );
            $userDAO->getUser();
            if (isset($userDAO->User)) {

                $editUser = $userDAO->User;
                $this->Email = $editUser->email;
                $this->TypeId = $editUser->typeId;
                $this->UserId = $editUser->idUsers;

                //echo "<pre>"; print_r($editUser); echo "</pre>";

                switch ($this->TypeId) {
                    case 1:
                        $this->ClientId = $editUser->clientId;
                        break;

                    case 2:
                        $this->ClientId = $editUser->idClients;
                        $this->Name = $editUser->clientName;
                        $this->Number = $editUser->clientNo;
                        $this->Address = $editUser->clientStreet;
                        $this->City = $editUser->clientCity;
                        $this->State = $editUser->clientState;
                        $this->Zip = $editUser->clientZip;
                        $this->LocationNumber = $editUser->locationNo;
                        $this->LocationName = $editUser->locationName;
                        $this->LocationId = $editUser->idLocation;

                        $this->HasMultiUser = $editUser->hasUserSetting(4);
                        $this->MultiUsers = $editUser->MultiUsers;
                        break;
                    case 3:
                        $this->DoctorId = $editUser->iddoctors;
                        $this->Name = $editUser->firstName . ", " . $editUser->lastName;
                        $this->Number = $editUser->number;
                        $this->Address = $editUser->address1;
                        $this->City = $editUser->city;
                        $this->State = $editUser->state;
                        $this->Zip = $editUser->zip;
                        $this->LocationNumber = $editUser->locationNo;
                        $this->LocationName = $editUser->locationName;
                        $this->LocationId = $editUser->idLocation;

                        break;
                    case 4:
                        $this->PatientId = $editUser->idPatients;
                        $this->Name = $editUser->firstName . " " . $editUser->lastName;
                        break;
                    case 5:
                        //echo $this->Name;
                        $this->SalesmanId = $editUser->idsalesmen;
                        $this->GroupName = $editUser->groupName;
                        $this->Name = $editUser->firstName . ", " . $editUser->lastName;
                        if (isset($editUser->SalesGroup->GroupLeader)) {
                            $this->GroupLeader = $editUser->SalesGroup->GroupLeader->lastName . ", " . $editUser->SalesGroup->GroupLeader->firstName;
                        }
                        $this->Territory = $editUser->territoryName;
                        $this->Address = $editUser->address;
                        $this->City = $editUser->city;
                        $this->State = $editUser->state;
                        $this->Zip = $editUser->zip;
                        break;
                    case 6:

                        $this->InsuranceId = $editUser->idinsurances;
                        $this->Name = $editUser->name;
                        $this->Phone = $editUser->phone;
                        $this->Address = $editUser->address;
                        $this->City = $editUser->city;
                        $this->State = $editUser->state;
                        $this->Zip = $editUser->zip;
                        break;
                    case 7:
                        $this->AdminClientIds = $editUser->adminClientIds;
                        break;
                    default: break;
                }


                if ($this->TypeId == 2 && count($editUser->MultiUsers) > 0) {
                    $this->SelectedMultiUsers = $editUser->MultiUsers;
                }
                if (isset($editUser->CommonDiagnosisCodes)) {
                    $this->SelectedCommonCodes = $editUser->CommonDiagnosisCodes;
                }
                if (isset($editUser->CommonTests)) {
                    /*foreach ($editUser->CommonTests as $test) {
                        $this->SelectedCommonTests[] = $test->number;
                    }*/
                    $this->SelectedCommonTests = $editUser->CommonTests;
                }
                if (isset($editUser->ExcludedTests)) {
                    /*foreach ($editUser->ExcludedTests as $test) {
                        $this->SelectedExcludedTests[] = $test->number;
                    }*/
                    $this->SelectedExcludedTests = $editUser->ExcludedTests;
                }
                if (isset($editUser->CommonDrugs)) {
                    $this->SelectedCommonDrugs = $editUser->CommonDrugs;
                }
                $hasOrderEntry = false;
                if (isset($editUser->UserSettings)) {
                    foreach ($editUser->UserSettings as $setting) {
                        if ($setting->idUserSettings == 3) {
                            $hasOrderEntry = true;
                        }
                        $this->SelectedUserSettings[] = $setting->idUserSettings;
                    }
                }
                if (isset($editUser->OrderEntrySettings) && is_array($editUser->OrderEntrySettings)) {
                    foreach ($editUser->OrderEntrySettings as $setting) {
                        $this->SelectedOrderEntrySettings[] = $setting->idOrderEntrySettings;
                    }
                }
                /*if ($editUser->typeId == 5 && isset($editUser->SalesSettings)) {
                    foreach ($editUser->SalesSettings as $setting) {
                        $this->SelectedSalesSettings[] = $setting->idSalesSettings;
                    }
                }*/
                if ($editUser->typeId == 1 && isset($editUser->AdminSettings)) {
                    $adminSettings = $editUser->getAdminSettings();
                    foreach ($adminSettings as $setting) {
                        $this->SelectedAdminSettings[] = $setting->idAdminSettings;
                    }
                }

                if (($editUser->typeId == 2 || $editUser->typeId == 3) && isset($editUser->OrderAccessSetting)) {
                    $this->OrderAccess = $editUser->OrderAccessSetting->idAccessSettings;
                }

                /*if (($editUser->typeId == 2 || $editUser->typeId == 3) && count($editUser->RestrictedUserIds) > 0) {
                    $this->RestrictedUserIds = $editUser->RestrictedUserIds;
                }*/

                if ($hasOrderEntry == true) {
                    if ($editUser->typeId == 2) {
                        $this->RestrictedUsers = UserDAO::getRestrictedUsersByClientId($editUser->idClients, $editUser->idUsers, $this->Conn);
                    } else if ($editUser->typeId == 3) {
                        $this->RestrictedUsers = UserDAO::getRestrictedUsersByDoctorId($editUser->iddoctors, $editUser->idUsers, $this->Conn);
                    }
                }
            }
        } else if ($this->Action == 1) { // add a new user
            if (isset($this->User->clientId)) {
                // this admin is assigned to a particular client

                $client = ClientDAO::getClient(array("idClients" => $this->User->clientId), array("Conn" => $this->Conn));
                if (!is_bool($client)) {
                    $this->Name = $client->clientName;
                    $this->ClientId = $client->idClients;
                    $this->Number = $client->clientNo;
                    $this->Address = $client->clientStreet;
                    $this->City = $client->clientCity;
                    $this->State = $client->clientState;
                    $this->Zip = $client->clientZip;
                }
            }

        }
    }

    public function printPage() {
        $displayOrderEntryStyle = "display: none;";
        if ((count($this->SelectedUserSettings) > 0 && in_array(3, $this->SelectedUserSettings)) || $this->TypeId == 7) {
            $displayOrderEntryStyle = "display: inline;";
        }

        $displayAdminSettingsStyle = "display: none;";
        if($this->TypeId == 1) {
            $displayAdminSettingsStyle = "display: block;";
        }

        $displayPatientAdminStyle = "display: none;";
        if($this->TypeId == 8) {
            $displayPatientAdminStyle = "display: block;";
        }

        $basicFieldsHtml = $this->printBasicFields();
        $adminSettingsHtml = $this->printAdminSettingsCheckboxes();
        $userSettingsHtml = $this->printUserSettingsCheckboxes();
        $salesSettingsHtml = $this->printSalesSettingsCheckboxes();
        $clientDoctorSearchFieldsHtml = $this->printUserSearchFields();

        $orderEntrySettingsHtml = $this->printOrderEntryCheckboxes();
        $commonCodesHtml = $this->printCommonCodesFields();
        $commonTestsHtml = $this->printCommonTestsFields();
        $excludedTestsHtml = $this->printExcludedTestsFields();
        $commonDrugsHtml = $this->printCommonDrugsFields();

        $patientAdminHtml = $this->printPatientAdminFields();

        $action = $this->Action;
        $userId = $this->UserId;
        $clientId = $this->ClientId;
        $doctorId = $this->DoctorId;
        $salesmanId = $this->SalesmanId;
        $insuranceId = $this->InsuranceId;
        $patientId = $this->PatientId;

        $commonTestsInputs = "";
        if (count($this->SelectedCommonTests) > 0) {
            foreach($this->SelectedCommonTests as $currTest) {
                if ($currTest instanceof Test) {
                    $currNum = $currTest->number;
                    $commonTestsInputs .= "<input type='hidden' name='commonTests[]' class='commonTests' id='$currNum' value='$currNum'>";
                }
            }
        }
        $excludedTestsInputs = "";
        if (count($this->SelectedExcludedTests) > 0) {
            foreach($this->SelectedExcludedTests as $currTest) {
                if ($currTest instanceof Test) {
                    $currNum = $currTest->number;
                    $excludedTestsInputs .= "<input type='hidden' name='excludedTests[]' class='excludedTests' id='$currNum' value='$currNum'>";
                }
            }
        }
        $commonDrugsInputs = "";
        if (count($this->SelectedCommonDrugs) > 0) {
            foreach($this->SelectedCommonDrugs as $currDrug) {
                $currDrugId = $currDrug->iddrugs;
                $commonDrugsInputs .= "<input type='hidden' name='commonDrugs[]' class='commonDrugs' id='$currDrugId' value='$currDrugId' />";
            }
        }

        $clientOptions = "";
        foreach($this->Clients as $client) {
            $selected = "";
            if (isset($this->ClientId) && $this->ClientId != 0 && $client->idClients == $this->ClientId) {
                $selected = "selected='selected'";
            }

            //echo $client->clientNo . " - " . $client->clientName . "<br/>";
            $clientOptions .= "<option value='" . $client->idClients . "' $selected>" . $client->clientNo . ": " . $client->clientName . "</option>";
        }

        $clientOptionsInput = "";
        if (!isset($this->User->clientId)) {
            $clientOptionsInput = "
            <label for='adminClientId'>Assign to client:</label>
            <select name='adminClientId' id='adminClientId' class='tooltipped' data-position='top' data-delay='50' data-tooltip='Admin can only manage users for the selected client'>
                <option value='0'>All Clients</option>
                $clientOptions
            </select>
            ";
        } else {
            $clientOptionsInput = "<input type='hidden' name='adminClientId' id='adminClientId' value='" . $this->User->clientId . "' />";
        }

        $locationId = 0;
        $hasMultiLocation = 0;
        if (self::HasMultiLocation == true) {
            if ($this->TypeId == 2 || $this->TypeId == 3) {
                $locationId = $this->LocationId;
            }

            $hasMultiLocation = 1;
        }

        if ($this->Action == 2 && $this->TypeId == 4) {
            $this->PageTitle = "Editing Patient User: " . $this->Name;
        }

        $html = "
        <div class='container' id='content'>
            <div class='row rounded box_shadow'>
                <div class='one mobile whole padded centered'>
                    <div class='row'>
                        <div class='one mobile third'>
                            <h5>$this->PageTitle</h5>
                        </div>
                        <div class='two mobile thirds' id='err'></div>
                    </div>
                </div>
                <div class='one mobile whole padded centered'>
                    <form action='process.php' method='post' name='frmAdmin' id='frmAdmin'>
                        <input type='hidden' name='idUsers' id='idUsers' value='$userId' />
                        <input type='hidden' name='action' id='action' value='$action' />
                        <input type='hidden' name='clientId' id='clientId' value='$clientId' />
                        <input type='hidden' name='doctorId' id='doctorId' value='$doctorId' />
                        <input type='hidden' name='salesmanId' id='salesmanId' value='$salesmanId' />
                        <input type='hidden' name='insuranceId' id='insuranceId' value='$insuranceId' />
                        <input type='hidden' name='resetPasswordInput' id='resetPasswordInput' value='0' />
                        <input type='hidden' name='locationId' id='locationId' value='$locationId' />
                        <input type='hidden' name='hasMultiLocation' id='hasMultiLocation' value='$hasMultiLocation' />
                        <input type='hidden' name='patientId' id='patientId' value='$patientId' />
                        
                        $commonTestsInputs
                        $excludedTestsInputs
                        $commonDrugsInputs
                        $basicFieldsHtml
                        <div class='row' id='adminSettings' style='$displayAdminSettingsStyle'>
                            <div class='one mobile half'>
                                $adminSettingsHtml
                            </div>
                            <div class='one mobile half pad-left'>$clientOptionsInput</div>
                        </div>

                        <div class='row' id='row3'>
                            <div class='one mobile half'>
                                $userSettingsHtml
                                $salesSettingsHtml
                            </div>
                            <div class='one mobile half'>
                                $clientDoctorSearchFieldsHtml
                            </div>
                        </div>
                        <div class='row' id='orderEntryFieldgroup' style='$displayOrderEntryStyle'>
                            <div class='one mobile whole'>
                                $orderEntrySettingsHtml
                                $commonCodesHtml
                                $commonTestsHtml
                                $excludedTestsHtml
                                $commonDrugsHtml
                            </div>
                        </div>
                        <div class='row' id='patientAdminFieldgroup' style='$displayPatientAdminStyle'>
                            <div class='one mobile whole'>
                                $patientAdminHtml
                            </div>
                        </div>
                        <div class='row'>
                            <div class='one mobile whole padded'>
                                <a href='index.php' class='button' style='float: left;'>Cancel</a>
                                <button class='green submit pull-right' id='btnAddSubmit'>Submit</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        ";

        echo $html;
    }

    private function printPatientAdminFields() {
        //$html = "<h5>Kit Numbers";
        $html = "";

        return $html;
    }

    private function printBasicFields() {
        $displayResetPasswordStyle = "display: none;";
        $passwordStyle = "background: #FAFFBD;";
        $attrDisableInput = "";
        $emailErrorHtml = "";
        $userTypeOptionHtml = "";
        $passwordErrorHtml = "";
        $email = $this->Email;
        $password = $this->Password;
        $password2 = $this->Password2;
        if ($this->Action == 2) {
            $attrDisableInput = "disabled=\"disabled\"";
            $displayResetPasswordStyle = "display: inline;";
            $passwordStyle = "background: #DDDDDD;";
        }

        if ($this->PrintErrorMessages && array_key_exists("email", $this->ErrorMessages)) {
            $emailErrorHtml = "<span class=\"error\">" . $this->ErrorMessages['email'] . "</span>";
        }
        if ($this->PrintErrorMessages && array_key_exists("typeId", $this->ErrorMessages)) {
            $userTypeHtml = "<span class=\"error\">" . $this->ErrorMessages['typeId'] . "</span>";
        }

        foreach ($this->UserTypes as $type) {
            $userTypeSelected = "";
            if ($type->idTypes != 4 && ($type->idTypes != 5 || ($this->User->typeId == 1 && $this->User->hasAdminSetting(11))) && ($type->idTypes != 6 || ($this->User->typeId == 1 && $this->User->hasAdminSetting(12)))) {
            //if (($type->idTypes != 5 || ($this->User->typeId == 1 && $this->User->hasAdminSetting(11))) && ($type->idTypes != 6 || ($this->User->typeId == 1 && $this->User->hasAdminSetting(12)))) {
                if ($type->idTypes == $this->TypeId) {
                    $userTypeSelected = "selected=\"selected\"";
                }
                $userTypeOptionHtml .= "<option value=\"" . $type->idTypes . "\" $userTypeSelected>" . $type->typeName . "</option>";
            }
        }
        if ($this->PrintErrorMessages && array_key_exists("password", $this->ErrorMessages)) {
            $passwordErrorHtml = "<span class=\"error\">" . $this->ErrorMessages['password'] . "</span>";
        }


        $userTypeHtml = "";
        $patientNameLabel = "";
        if ($this->Action == 2 && $this->TypeId == 4) {
            $userTypeHtml = "<input type='hidden' name='userType' id='userType' value='4' />
                <input type='text' name='userTypeName' id='userTypeName' value='Patient' disabled='disabled' style='background: #DDDDDD;' />";

        } else {
            $userTypeHtml = "<select name='userType' id='userType'>
                    <option value='0' disabled='disabled' selected='selected'>Please select the type of user</option>
                    $userTypeOptionHtml
                </select>";
        }


        $basicHtml = "
        <div class=\"row\" id=\"row1\">
            <div class=\"one mobile half pad-right\">
                <label for=\"newEmail\" style=\"display: inline\">Email: </label>
                $emailErrorHtml
                <input type=\"email\" name=\"email\" id=\"email\" value=\"$email\" placeholder=\"this will be used to log into your web portal \" autocomplete=\"off\" />
            </div>
            <div class=\"one mobile half pad-left\">
                <label for=\"userType\" style=\"display: inline;\">User Type: </label>
                $userTypeHtml
            </div>
        </div>
        <div class=\"row\" id=\"row2\">
            <div class=\"one mobile half pad-right\">
                <label for=\"newPassword\" style=\"display: inline\">Password: </label>
                <a href=\"javascript:void(0)\" id=\"resetPassword\" style=\"$displayResetPasswordStyle\">Click here to reset password</a>
                $passwordErrorHtml
                <input type=\"password\" name=\"password\" id=\"password\" value=\"$password\" $attrDisableInput placeholder=\"enter user's password here\" autocomplete=\"off\" style=\"$passwordStyle\" />
            </div>
            <div class=\"one mobile half pad-left\">
                <label for=\"newPassword2\" style=\"display: inline\">Re-Type Password: </label>
                <input type=\"password\" name=\"password2\" id=\"password2\" value=\"$password2\" $attrDisableInput placeholder=\"please re-type the user's password\" autocomplete=\"off\" style=\"$passwordStyle\" />
            </div>
        </div>
        ";

        return $basicHtml;
    }
    private function printAdminSettingsCheckboxes() {
        $checkBoxesHtml = "";
        foreach ($this->AdminSettings as $setting) {
            // make sure setting is active and the logged in admin either is a master admin, the current setting is not a master setting, or the logged in admin has the current setting
            // does not print the checkbox if it is a master setting and the logged in admin does not have the setting
            if ($setting->isActive && (
                    $this->IsMasterAdmin
                    || (!$setting->isMasterSetting && $setting->idAdminSettings != 13)
                    || (($setting->idAdminSettings == 11 || $setting->idAdminSettings == 13) && $this->User->hasAdminSetting(13))
                    || ($setting->idAdminSettings == 9 && $this->User->hasAdminSetting(9))
                    || ($setting->idAdminSettings == 12 && $this->User->hasAdminSetting(12))
                    || ($setting->idAdminSettings == 15 && $this->User->hasAdminSetting(15))
                )
            ) {

                $settingName = $setting->settingName . ": ";
                $adminSettingChecked = "";
                if ($this->TypeId == 1 && in_array($setting->idAdminSettings, $this->SelectedAdminSettings)) {
                    $adminSettingChecked = "checked='checked'";
                }
                $checkBoxesHtml .= "
                <div class='row'>
                    <div class='one mobile whole'>
                        <label for='adminSettings' class='lblAdminSettings'>$settingName</label>
                        <input type='checkbox' name='adminSettings[$setting->idAdminSettings]' class='adminSettings' data-id='$setting->idAdminSettings' value='$setting->idAdminSettings' $adminSettingChecked />
                    </div>
                </div>
                ";
            }
        }
        return $checkBoxesHtml;
    }

    private function printUserSettingsCheckboxes() {
        $displayUserSettingsStyle = "display: none;";
        $hasMultiUser = false;
        $displayMultiUserStyle = "display: none;";
        $checkBoxesHtml = "";
        $multiUserHtml = "";
        if ($this->TypeId == 2) {
            $displayUserSettingsStyle = "display: block;";
            if (in_array(4, $this->SelectedUserSettings)) {
                $hasMultiUser = true;
                $displayMultiUserStyle = "display: block;";
            }
        } else if ($this->TypeId == 3) {
            $displayUserSettingsStyle = "display: block;";
        }
        foreach ($this->UserSettings as $setting) {  // ---- User Settings checkboxes
            if ($setting->settingName != "Has Order Entry" || ($setting->settingName == "Has Order Entry" && ($this->IsMasterAdmin || $this->SignedInAdmin->hasSettingByName("Can Manage Order Entry"))) || ($this->IsMasterAdmin && $setting->settingName == "Can View User Statistics")) {
                $settingName = $setting->settingName . ": ";
                $userSettingChecked = "";
                if ($this->TypeId != 1 && in_array($setting->idUserSettings, $this->SelectedUserSettings)) {
                    $userSettingChecked = "checked=\"checked\"";
                }

                $displaySettingStyle = "display: block;";
                //if ($this->Action == 2 && $this->TypeId != 2 && $setting->settingName == "Has Multi User") {
                if ($this->TypeId != 2 && $setting->settingName == "Has Multi User") {
                    $displaySettingStyle = "display: none;";
                }
                $checkBoxesHtml .= "
                <div class=\"row\" style=\"$displaySettingStyle\">
                    <div class=\"one mobile whole\">
                        <label for=\"userSettings\" class=\"lblUserSettings\">$settingName</label>
                        <input type=\"checkbox\" name=\"userSettings[$setting->idUserSettings]\" class=\"userSettings\" data-id=\"$setting->idUserSettings\" value=\"$setting->idUserSettings\" $userSettingChecked />
                    </div>
                </div>
                ";
            }
        }

        $numClients = 0;
        $selectedClients = 0;
        foreach ($this->ClientUsers as $user) {

            if ($user['idUsers'] != $this->UserId) {
                $numClients++;
                $multiUserChecked = "";
                $currMultiUserId = $user['idUsers'];
                $currUserEmail = $user['email'];
                $currClientNo = $user['clientNo'];
                $currClientName = $user['clientName'];

                $currIdLocation = $user['idLocation'];
                $currLocationNo = $user['locationNo'];
                $currLocationName = $user['locationName'];

                if ($hasMultiUser && is_array($this->SelectedMultiUsers) && in_array($currMultiUserId, $this->SelectedMultiUsers)) {
                    $multiUserChecked = "checked=\"checked\"";
                    $selectedClients++;
                }
                $multiUserHtml .= "
                <div class='row multiUserRow'>
                    <div class='three mobile twelfths pad-left pad-right' style='overflow: hidden'>$currUserEmail</div>
                    <div class='one mobile twelfth pad-left pad-right'>$currClientNo</div>
                    <div class='three mobile twelfths pad-left pad-right' style='overflow: hidden'>$currClientName</div>
                    <div class='one mobile twelfth pad-left pad-right'>$currLocationNo</div>
                    <div class='three mobile twelfths pad-left pad-right' style='overflow: hidden'>$currLocationName</div>
                    <div class='one mobile twelfth pad-left pad-right' style='padding-top: 8px;'>
                        <input type='checkbox' name='multiUsers[]' value='$currMultiUserId' $multiUserChecked />
                    </div>
                </div>";
            }
        }

        $checkAllChecked = "";
        $checkAllTitle = "Select All Clients";
        if ($numClients == $selectedClients) {
            $checkAllChecked = "checked='checked'";
            $checkAllTitle = "Deselect All Clients";
        }

        $userSettingsHtml = "
            <div id='userSettings' style='$displayUserSettingsStyle'>
                $checkBoxesHtml
                <div id='multiUserSection' style='$displayMultiUserStyle'>
                    <label for='multiUserAccess' class='inline' style='vertical-align: 3px;'>Select which users this user has access to: </label>
                    <a href='javascript:void(0)' class='toggle pull-right'>+</a>
                    <div id='multiUserGroup'>
                        <div class='row' style='border-bottom: 1px solid #CCCCCC; margin-bottom: 5px;'>
                            <div class='three mobile twelfths pad-left pad-right'><p class='label'>User Email</p></div>
                            <div class='one mobile twelfth pad-left pad-right'><p class='label' title='Client Number'>#</p></div>
                            <div class='three mobile twelfths pad-left pad-right'><p class='label'>Client Name</p></div>
                            <div class='one mobile twelfth pad-left pad-right'><p class='label' title='Location Number'>#</p></div>
                            <div class='three mobile twelfths pad-left pad-right'><p class='label'>Location Name</p></div>
                            <div class='one mobile twelfth pad-left pad-right'>
                                <input type='checkbox' name='selectAllUsers' id='selectAllUsers' title='Select/Deselect All' value='1' title='$checkAllTitle' $checkAllChecked style='vertical-align: text-bottom;'/>
                            </div>
                        </div>
                        $multiUserHtml
                    </div>
                </div>
            </div>
        ";

        return $userSettingsHtml;
    }
    private function printSalesSettingsCheckboxes() {
        $displayStyle = "display: none;";
        if($this->TypeId == 5) {
            $displayStyle = "display: block;";
        }

        $checkboxesHtml = "";
//        foreach ($this->SalesSettings as $setting) {
//            if ($setting->isActive) {
//                $settingName = $setting->settingName . ": ";
//                $settingChecked = "";
//                if ($this->TypeId == 5 && in_array($setting->idSalesSettings, $this->SelectedSalesSettings)) {
//                    $settingChecked = "checked=\"checked\"";
//                }
//                $checkboxesHtml .= "
//                <div class=\"row\">
//                    <div class=\"one whole\">
//                        <label for=\"salesSettings\" class=\"lblSalesSettings\">$settingName</label>
//                        <input type=\"checkbox\" name=\"salesSettings[$setting->idSalesSettings]\" class=\"salesSettings\" id=\"$setting->idSalesSettings\" value=\"$setting->idSalesSettings\" $settingChecked />
//                    </div>
//                </div>
//                ";
//            }
//        }

        return "
        <div id=\"salesSettings\" style=\"$displayStyle\">
            $checkboxesHtml
        </div>";
    }
    private function printClientSearchFields() {
        $displaySearchStyle = "display: none;";
        $displaySelectNewClientStyle = "display: none;";
        $disableInputs = "";
        // Input field values
        $clientNo = "";
        $clientName = "";
        $clientAddress = "";
        $clientCity = "";
        $clientState = "";
        $clientZip = "";
        $clientLocationName = "";
        $clientLocationNumber = "";
        if ($this->TypeId == 2 || ($this->Action == 1 && isset($this->User->clientId))) {

            if($this->TypeId == 2) {
                if (!isset($this->User->clientId)) {
                    $displaySelectNewClientStyle = "display: block;";
                }

                $disableInputs = "disabled=\"disabled\" style=\"background: #DDDDDD;\"";
                $displaySearchStyle = "display: block;";
            }

            $clientNo = $this->Number;
            $clientName = $this->Name;
            $clientAddress = $this->Address;
            $clientCity = $this->City;
            $clientState = $this->State;

            //echo $clientName;
            $clientZip = $this->Zip;

            $clientLocationName = $this->LocationName;
            $clientLocationNumber = $this->LocationNumber;
        }
        return "
            <div id='clientSearch' style='$displaySearchStyle'>
                <p id='clientTitle'>Please begin typing in the form fields below to search for the appropriate client. The results will begin to appear as you type: </p>
                <div class='row'>
                    <div class='one mobile half'>
                        <label for='userClient'>Client Name: </label>
                        <input type='text' name='userClient' class='clientInput' id='userClient' value=\"$clientName\" autocomplete='off' placeholder='client&#39;s name' $disableInputs />
                    </div>
                    <div class='one mobile half pad-left'>
                        <label for='clientNumber'>Client Number: </label>
                        <input type='number' name='clientNumber' class='clientInput' id='clientNumber' value='$clientNo' autocomplete='off' placeholder='client number' $disableInputs />
                    </div>
                </div>
                <div class='row'>
                    <div class='one mobile half'>
                        <label for='clientAddress'>Address: </label>
                        <input type='text' name='clientAddress' class='clientInput' id='clientAddress' value=\"$clientAddress\" autocomplete='off' placeholder='client&#39;s address' $disableInputs />
                    </div>
                    <div class='one mobile half pad-left'>
                        <label for='clientCity'>City: </label>
                        <input type='text' name='clientCity' class='clientInput' id='clientCity' value=\"$clientCity\" autocomplete='off' placeholder='client&#39;s city' $disableInputs />
                    </div>
                </div>
                <div class='row'>
                    <div class='one mobile half'>
                        <label for='clientState'>State: </label>
                        <input type='text' name='clientState' class='clientInput' id='clientState' value='$clientState' autocomplete='off' placeholder='client&#39;s state' $disableInputs />
                    </div>
                    <div class='one mobile half pad-left'>
                        <label for='clientZip'>Zip: </label>
                        <input type='number' name='clientZip' class='clientInput' id='clientZip' value='$clientZip' autocomplete='off' placeholder='client&#39;s zip' $disableInputs />
                    </div>
                </div>
                <div class='row'>
                    <div class='one mobile half'>
                        <label for='clientLocationName'>Location Name: </label>
                        <input type='text' name='clientLocationName' class='clientInput' id='clientLocationName' value=\"$clientLocationName\" autocomplete='off' placeholder='location name' $disableInputs />
                    </div>
                    <div class='one mobile half pad-left'>
                        <label for='clientLocationNumber'>Location Number: </label>
                        <input type='number' name='clientLocationNumber' class='clientInput' id='clientLocationNumber' value='$clientLocationNumber' autocomplete='off' placeholder='location number' $disableInputs />
                    </div>
                </div>
                <div class='row pad-top' id='resetClientContainer' style='$displaySelectNewClientStyle'>
                    <div class='one mobile whole'>
                        <a href='javascript:void(0)' id='resetClientLink'>Click here to select a different client.</a>
                    </div>
                </div>
            </div>";
    }
    private function printDoctorSearchFields() {
        $html = "";

        $displaySearchStyle = "display: none;";
        if ($this->TypeId == 3) {
            $displaySearchStyle = "display: block;";
        }

        if (isset($this->User->clientId)) {

            $doctorHtmlOptions = "";
            if (count($this->Doctors) > 0) {
                foreach ($this->Doctors as $doctor) {

                    $selected = "";
                    if ($this->DoctorId == $doctor->iddoctors) {
                        $selected = "selected='selected'";
                    }

                    $lastName = $doctor->lastName;
                    $firstName = $doctor->firstName;
                    $doctorName = "";
                    if (!empty($firstName) && !empty($lastName)) {
                        $doctorName = $firstName . " " . $lastName;
                    } else if (!empty($lastName)) {
                        $doctorName = $lastName;
                    } else if (!empty($firstName)) {
                        $doctorName = $firstName;
                    }
                    $doctorHtmlOptions .= "<option value='" . $doctor->iddoctors . "' $selected>$doctorName</option>";
                }
            }

            $html = "
                <div id='doctorSearch' style='$displaySearchStyle'>
                    <div class='row'>
                        <div class='one mobile whole'>
                            <label for=''>Select Doctor</label>
                            <select name='selectDoctor' id='selectDoctor'>
                                <option value='0'>Select a doctor to assign this user to</option>
                                $doctorHtmlOptions
                            </select
                        </div>
                    </div>
                </div>
            ";

        } else {

            $displaySelectNewDoctorStyle = "display: none;";
            $disableInputs = "";
            // Input field values
            $doctorNo = "";
            $doctorName = "";
            $doctorAddress = "";
            $doctorCity = "";
            $doctorState = "";
            $doctorZip = "";
            $doctorLocationName = "";
            $doctorLocationNumber = "";
            if ($this->TypeId == 3) {
                if (!isset($this->User->clientId)) {
                    $displaySelectNewDoctorStyle = "display: block;";
                }

                $disableInputs = "disabled='disabled' style='background: #DDDDDD;'";
                $doctorNo = $this->Number;
                $doctorName = $this->Name;
                $doctorAddress = $this->Address;
                $doctorCity = $this->City;
                $doctorState = $this->State;
                $doctorZip = $this->Zip;

                $doctorLocationName = $this->LocationName;
                $doctorLocationNumber = $this->LocationNumber;
            }
            $html = "
            <div id='doctorSearch' style='$displaySearchStyle'>
                <p id='doctorTitle'>Please begin typing in the form fields below to search for the appropriate doctor. The results will begin to appear as you type: </p>
                <div class='row'>
                    <div class='one mobile half'>
                        <label for='userDoctor'>Doctor Name: </label>
                        <input type='text' name='userDoctor' class='doctorInput' id='userDoctor' value=\"$doctorName\" autocomplete='off' placeholder='doctor&#39;s name' $disableInputs />
                    </div>
                    <div class='one mobile half pad-left'>
                        <label for='doctorNumber'>Doctor Number: </label>
                        <input type='number' name='doctorNumber' class='doctorInput' id='doctorNumber' value='$doctorNo' autocomplete='off' placeholder='doctor number' $disableInputs />
                    </div>
                </div>
                <div class='row'>
                    <div class='one mobile half'>
                        <label for='doctorAddress'>Address: </label>
                        <input type='text' name='doctorAddress' class='doctorInput' id='doctorAddress' value=\"$doctorAddress\" autocomplete='off' placeholder='doctor&#39;s address' $disableInputs />
                    </div>
                    <div class='one mobile half pad-left'>
                        <label for='doctorCity'>City: </label>
                        <input type='text' name='doctorCity' class='doctorInput' id='doctorCity' value=\"$doctorCity\" autocomplete='off' placeholder='doctor&#39;s city' $disableInputs />
                    </div>
                </div>
                <div class='row'>
                    <div class='one mobile half'>
                        <label for='doctorState'>State: </label>
                        <input type='text' name='doctorState' class='doctorInput' id='doctorState' value='$doctorState' autocomplete='off' placeholder='doctor&#39;s state' $disableInputs />
                    </div>
                    <div class='one mobile half pad-left'>
                        <label for='doctorZip'>Zip: </label>
                        <input type='number' name='doctorZip' class='doctorInput' id='doctorZip' value='$doctorZip' autocomplete='off' placeholder='doctor&#39;s zip' $disableInputs />
                    </div>
                </div>
                <div class='row'>
                    <div class='one mobile half'>
                        <label for='doctorLocationName'>Location Name: </label>
                        <input type='text' name='doctorLocationName' class='doctorInput' id='doctorLocationName' value=\"$doctorLocationName\" autocomplete='off' placeholder='location name' $disableInputs />
                    </div>
                    <div class='one mobile half pad-left'>
                        <label for='doctorLocationNumber'>Location Number: </label>
                        <input type='number' name='doctorLocationNumber' class='doctorInput' id='doctorLocationNumber' value='$doctorLocationNumber' autocomplete='off' placeholder='location number' $disableInputs />
                    </div>
                </div>
                <div class='row pad-top' id='resetDoctorContainer' style='$displaySelectNewDoctorStyle'>
                    <div class='one mobile whole'>
                        <a href='javascript:void(0)' id='resetDoctorLink'>Click here to select a different doctor.</a>
                    </div>
                </div>
            </div>";
        }

        return $html;
    }

    private function printSalesSearchFields() {
        $displaySearchStyle = "display: none;";
        $disableInputs = "";
        // Input field values
        $salesGroup = "";
        $salesName = "";
        $salesAddress = "";
        $salesCity = "";
        $salesState = "";
        $salesZip = "";
        $groupLeader = "";
        $salesTerritory = "";
        if ($this->TypeId == 5) {
            $disableInputs = "disabled=\"disabled\" style=\"background: #DDDDDD;\"";
            $displaySearchStyle = "display: block;";
            $salesGroup = $this->GroupName;
            $salesName = $this->Name;
            $salesAddress = $this->Address;
            $salesCity = $this->City;
            $salesState = $this->State;
            $salesZip = $this->Zip;
            $groupLeader = $this->GroupLeader;
            $salesTerritory = $this->Territory;
        }
        return "
            <div id=\"salesSearch\" style=\"$displaySearchStyle\">
                <p id=\"salesTitle\">Please begin typing in the form fields below to search for the appropriate sales person. The results will begin to appear as you type: </p>
                <div class=\"row\">
                    <div class=\"one mobile half\">
                        <label for=\"salesGroup\">Sales Group: </label>
                        <input type=\"text\" name=\"salesGroup\" class=\"salesInput\" id=\"salesGroup\" value=\"$salesGroup\" autocomplete=\"off\" placeholder=\"sales person's group\" $disableInputs/>
                    </div>
                    <div class=\"one mobile half pad-left\">
                        <label for=\"salesName\">Sales Person Name: </label>
                        <input type=\"text\" name=\"salesName\" class=\"salesInput\" id=\"salesName\" value=\"$salesName\" autocomplete=\"off\" placeholder=\"sales person's name\" $disableInputs/>
                    </div>
                </div>
                <div class=\"row\">
                    <div class=\"one mobile half\">
                        <label for=\"groupLeader\">Group Leader: </label>
                        <input type=\"text\" name=\"groupLeader\" class=\"salesInput\" id=\"groupLeader\" value=\"$groupLeader\" autocomplete=\"off\" placeholder=\"the sales group's leader\" $disableInputs/>
                    </div>
                    <div class=\"one mobile half pad-left\">
                        <label for=\"salesTerritory\">Territory: </label>
                        <input type=\"text\" name=\"salesTerritory\" class=\"salesInput\" id=\"salesTerritory\" value=\"$salesTerritory\" autocomplete=\"off\" placeholder=\"the sales person's territory\" $disableInputs/>
                    </div>
                </div>
                <div class=\"row\">
                    <div class=\"one mobile half\">
                        <label for=\"salesAddress\">Address: </label>
                        <input type=\"text\" name=\"salesAddress\" class=\"salesInput\" id=\"salesAddress\" value=\"$salesAddress\" autocomplete=\"off\" placeholder=\"sales person's address\" $disableInputs/>
                    </div>
                    <div class=\"one mobile half pad-left\">
                        <label for=\"salesCity\">City: </label>
                        <input type=\"text\" name=\"salesCity\" class=\"salesInput\" id=\"salesCity\" value=\"$salesCity\" autocomplete=\"off\" placeholder=\"sales person's city\" $disableInputs/>
                    </div>
                </div>
                <div class=\"row\">
                    <div class=\"one mobile half\">
                        <label for=\"salesState\">State: </label>
                        <input type=\"text\" name=\"salesState\" class=\"salesInput\" id=\"salesState\" value=\"$salesState\" autocomplete=\"off\" placeholder=\"sales person's state\" $disableInputs/>
                    </div>
                    <div class=\"one mobile half pad-left\">
                        <label for=\"salesZip\">Zip: </label>
                        <input type=\"text\" name=\"salesZip\" class=\"salesInput\" id=\"salesZip\" value=\"$salesZip\" autocomplete=\"off\" placeholder=\"sales person's zip\" $disableInputs/>
                    </div>
                </div>
                <div class=\"row pad-top\" id=\"resetSalesContainer\" style=\"$displaySearchStyle\">
                    <div class=\"one mobile whole\">
                        <a href=\"javascript:void(0)\" id=\"resetSalesLink\">Click here to select a different sales person.</a>
                    </div>
                </div>
            </div>";
    }
    private function printInsuranceSearchFields() {
        $displaySearchStyle = "display: none;";
        $disableInputs = "";
        // Input field values
        $insuranceName = "";
        $insurancePhone = "";
        $insuranceAddress = "";
        $insuranceCity = "";
        $insuranceState = "";
        $insuranceZip = "";
        if ($this->TypeId == 6) {
            $disableInputs = "disabled=\"disabled\" style=\"background: #DDDDDD;\"";
            $displaySearchStyle = "display: block;";
            $insuranceName = $this->Name;
            $insurancePhone = $this->Phone;
            $insuranceAddress = $this->Address;
            $insuranceCity = $this->City;
            $insuranceState = $this->State;
            $insuranceZip = $this->Zip;
        }
        return "
            <div id=\"insuranceSearch\" style=\"$displaySearchStyle\">
                <p id=\"insuranceTitle\">Please begin typing in the form fields below to search for the appropriate insurance company. The results will begin to appear as you type: </p>
                <div class=\"row\">
                    <div class=\"one mobile half\">
                        <label for=\"insuranceName\">Insurance Name: </label>
                        <input type=\"text\" name=\"insuranceName\" class=\"insuranceInput\" id=\"insuranceName\" value=\"$insuranceName\" autocomplete=\"off\" placeholder=\"The company's name\" $disableInputs/>
                    </div>
                    <div class=\"one mobile half pad-left\">
                        <label for=\"insurancePhone\">Phone Number: </label>
                        <input type=\"text\" name=\"insurancePhone\" class=\"insuranceInput\" id=\"insurancePhone\" value=\"$insurancePhone\" autocomplete=\"off\" placeholder=\"The company's phone number\" $disableInputs/>
                    </div>
                </div>
                <div class=\"row\">
                    <div class=\"one mobile half\">
                        <label for=\"insuranceAddress\">Address: </label>
                        <input type=\"text\" name=\"insuranceAddress\" class=\"insuranceInput\" id=\"insuranceAddress\" value=\"$insuranceAddress\" autocomplete=\"off\" placeholder=\"The company's address\" $disableInputs/>
                    </div>
                    <div class=\"one mobile half pad-left\">
                        <label for=\"insuranceCity\">City: </label>
                        <input type=\"text\" name=\"insuranceCity\" class=\"insuranceInput\" id=\"insuranceCity\" value=\"$insuranceCity\" autocomplete=\"off\" placeholder=\"The company's city\" $disableInputs/>
                    </div>
                </div>
                <div class=\"row\">
                    <div class=\"one mobile half\">
                        <label for=\"insuranceState\">State: </label>
                        <input type=\"text\" name=\"insuranceState\" class=\"insuranceInput\" id=\"insuranceState\" value=\"$insuranceState\" autocomplete=\"off\" placeholder=\"The company's state\" $disableInputs/>
                    </div>
                    <div class=\"one mobile half pad-left\">
                        <label for=\"insuranceZip\">Zip: </label>
                        <input type=\"text\" name=\"insuranceZip\" class=\"insuranceInput\" id=\"insuranceZip\" value=\"$insuranceZip\" autocomplete=\"off\" placeholder=\"The company's zip\" $disableInputs/>
                    </div>
                </div>
                <div class=\"row pad-top\" id=\"resetInsuranceContainer\" style=\"$displaySearchStyle\">
                    <div class=\"one mobile whole\">
                        <a href=\"javascript:void(0)\" id=\"resetInsuranceLink\">Click here to select a different insurance.</a>
                    </div>
                </div>
            </div>";
    }
    private function printUserSearchFields() {
        $clientSearchHtml = $this->printClientSearchFields();
        $doctorSearchHtml = $this->printDoctorSearchFields();
        $salesSearchHtml = $this->printSalesSearchFields();
        $insuranceSearchHtml = $this->printInsuranceSearchFields();

        $searchHtml = "
            $clientSearchHtml
            $doctorSearchHtml
            $salesSearchHtml
            $insuranceSearchHtml
            <div class=\"one mobile whole\" id=\"results\"></div>";
        return $searchHtml;
    }

    private function printOrderEntryCheckboxes() {

        $html = "<div class=\"row pad-top\" style=\"margin-bottom: 10px;\"><div class=\"one mobile whole\"><h5 title=\"Order entry settings\">Order Entry Settings</h5>";
        $displaySettingsStyle = "display: none;";
        $displayAdminClientsStyle = "display: none;";
        if ($this->TypeId == 7) {
            $displayAdminClientsStyle = "display: block;";
        }

        $html .= "<div class=\"row\">";

        $aryClientIds = array();
        $checkAllChecked = "";
        $checkAllTitle = "Select All Clients";
        $html .= "
        <div class='one mobile half' id='adminClientSection' style='$displayAdminClientsStyle'>
            <label class='inline' style='vertical-align: 3px;'>Select which clients this admin has access to: </label>
            <a href='javascript:void(0)' class='toggle pull-right'>+</a>
            <div id='adminClientGroup'>
                <div class='row' style='border-bottom: 1px solid #CCCCCC; margin-bottom: 5px;'>
                    <div class='one mobile sixth pad-left pad-right'><p class='label' title='Client Number'>#</p></div>
                    <div class='four mobile sixths pad-left pad-right'><p class='label'>Client Name</p></div>
                    <div class='one mobile sixth pad-left pad-right'>
                        <input type='checkbox' name='selectAllAdminClients' id='selectAllAdminClients' title='Select/Deselect All' value='1' title='$checkAllTitle' $checkAllChecked style='vertical-align: text-bottom;'/>
                    </div>
                </div>";
        foreach ($this->ClientUsers as $user) {
            $idUsers = $user['idUsers'];
            $idClients = $user['idClients'];
            $clientNo = $user['clientNo'];
            $clientName = $user['clientName'];
            $clientChecked = "";
            if (!in_array($idClients, $aryClientIds)) {
                $aryClientIds[] = $idClients;

                if (in_array($idClients, $this->AdminClientIds)) {
                    $clientChecked = "checked='checked'";
                }

                $html .= "
                <div class='row adminClientRow'>
                    <div class='one mobile sixth pad-left pad-right'>$clientNo</div>
                    <div class='four mobile sixths pad-left pad-right' style='overflow: hidden'>$clientName</div>
                    <div class='one mobile sixth pad-left pad-right' style='padding-top: 8px;'>
                        <input type='checkbox' name='adminClients[]' class='adminClients' data-id='$idUsers' value='$idClients' $clientChecked />
                    </div>
                </div>";
            }
        }

        $html .= "</div></div>";


        $html .= "<div class=\"one mobile half\">";

        foreach ($this->OrderEntrySettings as $setting) {
            $settingName = $setting->settingName;
            $idOrderEntrySettings = $setting->idOrderEntrySettings;
            $settingChecked = "";
            $tooltip = "";
            $tooltipped = "";

            if ($this->TypeId == 2 || $idOrderEntrySettings != 6) {
                if ($this->TypeId != 1 && in_array($idOrderEntrySettings, $this->SelectedOrderEntrySettings)) {
                    $settingChecked = "checked=\"checked\"";
                }
                if (isset($setting->settingDescription) && !empty($setting->settingDescription)) {
                    $tooltip = "data-position=\"top\" data-delay=\"50\" data-tooltip=\"" . $setting->settingDescription . "\"";
                    $tooltipped = "tooltipped";
                }
                $html .= "<div class=\"row\">
                    <div class=\"one mobile whole\">
                        <label for=\"orderEntrySettings\" class=\"lblOrderEntrySettings $tooltipped\" data-position=\"right\" $tooltip>$settingName</label>
                        <input type=\"checkbox\" name=\"orderEntrySettings[$idOrderEntrySettings]\" class=\"orderEntrySettings $tooltipped\" data-position=\"right\" id=\"$idOrderEntrySettings\" value=\"$idOrderEntrySettings\" $settingChecked $tooltip />
                    </div>
                </div>";
            }
        }

        $html .= "</div>";
        $html .= "
            <div class=\"one mobile half pad-left\">
                <label for=\"orderAccess\" class=\"tooltipped\" data-position=\"top\" data-delay=\"50\" data-tooltip=\"Web order access privilege for this user\" style=\"font-weight: bold;\">
                    Order Access: </label>
                <select name=\"orderAccess\" id=\"orderAccess\" style=\"margin-bottom: 10px;\">";

        foreach ($this->OrderAccessSettings as $setting) {
            $settingSelected = "";
            if (empty($this->OrderAccess)) {
                if (self::DefaultRestrictAll == false && $setting->idAccessSettings == 1) {
                    $settingSelected = "selected='selected'";
                } else if (self::DefaultRestrictAll == true && $setting->idAccessSettings == 2) {
                    $settingSelected = "selected='selected'";
                }
            } else if ($setting->idAccessSettings == $this->OrderAccess) {
                $settingSelected = "selected='selected'";
            }
            $html .= "<option value=\"" . $setting->idAccessSettings . "\" $settingSelected>" . $setting->settingName . "</option>";
        }

        $html .= "</select>";

        $drawSelected = "";
        $uploadSelected = "";
        if (in_array(9, $this->SelectedOrderEntrySettings)) { // draw disabled
            $uploadSelected = "selected='selected'";
        }
        if (in_array(10, $this->SelectedOrderEntrySettings)) { // upload disabled
            $drawSelected = "selected='selected'";
        }

        $html .= "
                <label for=\"orderEntrySettings\" class=\"lblOrderEntrySettings\" data-position=\"right\">E-Signature Type:</label>
                <select name=\"eSignatureType\" id=\"eSignatureType\" style=\"margin-top: 10px;\">
                    <option value=\"1\" selected=\"selected\">Draw and Upload</option>
                    <option value=\"2\" $drawSelected>Draw</option>
                    <option value=\"3\" $uploadSelected>Upload</option>
                </select>";

        $restrictedUsersHtml = "";
        $restrictedUsersStyle = "";
        if (count($this->RestrictedUsers) > 0 && ($this->OrderAccess == 3 || $this->OrderAccess == 4)) {
            $restrictedUsersHtml = "<div class=\"one mobile whole pad-top\"><label style=\"font-weight: bold;\">Select Users:</label></div>";
            $restrictedUsersStyle = "style=\"display: block;\"";
            foreach ($this->RestrictedUsers as $user) {

                $checked = "";
                if ($user->IsRestrictedUser == true) {
                    $checked = "checked=\"checked\"";
                }

                if ($user->idUsers != $this->UserId) {
                    $restrictedUsersHtml .= "
                        <div class=\"one mobile half pad-top\">
                            <label for=\"\" style=\"display: inline;\">" . $user->email . "</label>
                            <input type=\"checkbox\" name=\"restrictedUsers[]\" class=\"restrictedUsers\" value=\"" . $user->idUsers . "\" $checked/>
                        </div>
                    ";
                }
            }
        }

        $html .= "
            <div class=\"row\" id=\"restrictedUsersContainer\" $restrictedUsersStyle>
                $restrictedUsersHtml
            </div>
        </div>
        ";

        //echo "<pre>"; print_r($this->RestrictedUserIds); echo "</pre>";

        $html .= "</div></div></div>";

        return $html;
    }

    private function printCommonCodesFields() {
        $hasCommonCodes = false;
        if ($this->TypeId != 1 && (in_array(3, $this->SelectedUserSettings) || $this->TypeId == 7) && count($this->SelectedCommonCodes) > 0) {
            $hasCommonCodes = true;
        }

        $codesContainerHtml = "";
        if($hasCommonCodes) {
            $codesContainerHtml = "
                <div class=\"row\" id=\"selectedDiagnosisCodesHeader\">
                    <div class=\"two mobile twelfths pad-left pad-right\">Code</div>
                    <div class=\"six mobile twelfths pad-right\">Description</div>
                    <div class=\"two mobile twelfths pad-right\">ICD v.</div>
                    <div class=\"two mobile twelfths pad-right\">Remove</div>
                </div>";
            foreach ($this->SelectedCommonCodes as $code) {
                $codesContainerHtml .= "
                    <div class=\"row selectedDiagnosisCodes\">
                        <input type=\"hidden\" name=\"commonDiagnosisCodes[]\" class=\"commonDiagnosisCodes\" id=\"$code->idDiagnosisCodes\" value=\"$code->idDiagnosisCodes\" />
                        <div class=\"two mobile twelfths pad-left pad-right\">$code->code</div>
                        <div class=\"six mobile twelfths pad-right\">$code->FullDescription</div>
                        <div class=\"two mobile twelfths pad-right\">$code->version</div>
                        <div class=\"two mobile twelfths pad-right\">
                            <a href=\"javascript:void(0)\" class=\"removeCodes\" id=\"$code->idDiagnosisCodes\"><i class=\"icon icon-trash\"></i></a>
                        </div>
                    </div>";
            }
        }

        $commonCodesHtml = "
            <div class=\"row\" id=\"commonDiagnosisRow\"> <!--// ---- Common Diagnosis Codes --- //-->
                <div class=\"one mobile half pad-right\">
                    <h5 title=\"Search for diagnosis codes to be added for this user.\">Common Diagnosis Codes</h5>
                    <div class=\"row\">
                        <div class=\"two mobile twelfths pad-right\">
                            <label for=\"codeSearch\">Code</label>
                            <input type=\"text\" name=\"codeSearch\" id=\"codeSearch\" class=\"diagnosisSearch\" />
                        </div>
                        <div class=\"eight mobile twelfths pad-right\">
                            <label for=\"descriptionSearch\">Description</label>
                            <input type=\"text\" name=\"descriptionSearch\" id=\"descriptionSearch\" class=\"diagnosisSearch\" />
                        </div>
                        <div class=\"two mobile twelfths pad-right\">
                            <label for=\"versionSearch\" title=\"ICD Version\">ICD v.</label>
                            <select name=\"versionSearch\" id=\"versionSearch\" class=\"diagnosisSearch\">
                                <option value=\"1\">All</option>
                                <option value=\"2\">9</option>
                                <option value=\"3\" selected=\"selected\">10</option>
                            </select>
                        </div>
                    </div>
                    <div class=\"row\" style=\"margin-bottom: 50px;\">
                        <div class=\"one mobile whole\" id=\"diagnosisSearchResults\"></div>
                    </div>
                </div>
                <div class=\"one mobile half pad-left\">
                    <label for=\"commonDiagnosisCodes\">Selected Common Diagnosis Codes</label>
                    <div class=\"row\">
                        <div class=\"one mobile whole\" id=\"selectedDiagnosisCodesContainer\">
                            $codesContainerHtml
                        </div>
                    </div>
                </div>
            </div>
        ";
        return $commonCodesHtml;
    }

    private function printCommonTestsFields() {
        $hasCommonTests = false;
        $hasExcludedTests = false;
        $testsHtml = "";
        if ($this->TypeId != 1 && in_array(3, $this->SelectedUserSettings)) {
            if (count($this->SelectedCommonTests) > 0) {
                $hasCommonTests = true;
            }
            if (count($this->SelectedExcludedTests) > 0) {
                $hasExcludedTests = true;
            }
        }

        $testsHtml = "";
        if (count($this->SelectedCommonTests) > 0) {
            $testsHtml .= "
                <div class=\"row\" id=\"selectedCommonTestsHeader\">
                    <div class=\"two mobile eighths\">Test Name</div>
                    <div class=\"one mobile eighth\">Test #</div>
                    <div class=\"two mobile eighths\">Department</div>
                    <div class=\"two mobile eighths\">Specimen Type</div>
                    <div class=\"one mobile eighth\">Remove</div>
                </div>";

            foreach($this->SelectedCommonTests as $currTest) {
                if ($currTest instanceof Test) {
                    $testNumber = $currTest->number;
                    $testName = $currTest->name;
                    $department = $currTest->deptName;
                    $specimenType = $currTest->specimenTypeName;

                    $testsHtml .= "
                    <div class=\"row selectedCommonTests\">
                        <div class=\"two mobile eighths\">$testName</div>
                        <div class=\"one mobile eighth\">$testNumber</div>
                        <div class=\"two mobile eighths\">$department</div>
                        <div class=\"two mobile eighths\">$specimenType</div>
                        <div class=\"one mobile eighth\">
                            <a href=\"javascript:void(0)\" class=\"removeTests\" id=\"$testNumber\"><i class=\"icon icon-trash\"></i></a>
                        </div>
                    </div>";
                }
            }
        }

        $commonTestsHtml = "
            <div class=\"row\" id=\"commonTestsRow\">
                <div class=\"one mobile half\">
                    <h5 title=\"Search for common tests to be added for this user.\">
                        Common Tests</h5>
                    <div class=\"row pad-right\">
                        <div class=\"five mobile sevenths pad-right\">
                            <label for=\"commonTestName\">Test Name</label>
                            <input type=\"text\" name=\"commonTestName\" id=\"commonTestName\" class=\"testInput\" autocomplete=\"off\" tabindex=\"50\" />
                        </div>
                        <div class=\"two mobile sevenths\">
                            <label for=\"commonTestNumber\">Test Number</label>
                            <input type=\"number\" name=\"commonTestNumber\" id=\"commonTestNumber\" class=\"testInput\" tabindex=\"51\" />
                        </div>
                        <div class=\"one mobile whole\">
                            <div id=\"commonTestsResults\" class=\"box_shadow searchResults\"></div>
                        </div>
                    </div>
                </div>
                <div class=\"one mobile half pad-left\">
                    <label for=\"selectedCommonTests\">Selected Common Tests: </label>
                    <div class=\"row\">
                        <div class=\"one mobile whole\" id=\"selectedCommonTestsContainer\">
                            $testsHtml
                        </div>
                    </div>
                </div>
            </div>
        ";



        return $commonTestsHtml;
    }

    private function printExcludedTestsFields() {
        $hasExcludedTests = false;
        $hasCommonTests = false;
        $testsHtml = "";
        if ($this->TypeId != 1 && in_array(3, $this->SelectedUserSettings)) {
            if (count($this->SelectedCommonTests) > 0) {
                $hasCommonTests = true;
            }
            if (count($this->SelectedExcludedTests) > 0) {
                $hasExcludedTests = true;
            }
        }

        $testsHtml = "";
        if (count($this->SelectedExcludedTests) > 0) {
            $testsHtml .= "
                <div class=\"row\" id=\"selectedExcludedTestsHeader\">
                    <div class=\"two mobile eighths\">Test Name</div>
                    <div class=\"one mobile eighth\">Test #</div>
                    <div class=\"two mobile eighths\">Department</div>
                    <div class=\"two mobile eighths\">Specimen Type</div>
                    <div class=\"one mobile eighth\">Remove</div>
                </div>";

            foreach($this->SelectedExcludedTests as $currTest) {
                if ($currTest instanceof Test) {
                    $testNumber = $currTest->number;
                    $testName = $currTest->name;
                    $department = $currTest->deptName;
                    $specimenType = $currTest->specimenTypeName;

                    $testsHtml .= "
                    <div class=\"row selectedExcludedTests\">
                        <div class=\"two mobile eighths\">$testName</div>
                        <div class=\"one mobile eighth\">$testNumber</div>
                        <div class=\"two mobile eighths\">$department</div>
                        <div class=\"two mobile eighths\">$specimenType</div>
                        <div class=\"one mobile eighth\">
                            <a href=\"javascript:void(0)\" class=\"removeTests\" id=\"$testNumber\"><i class=\"icon icon-trash\"></i></a>
                        </div>
                    </div>";
                }

            }
        }

        $excludedTestsHtml = "
            <div class=\"row\" id=\"excludedTestsRow\">
                <div class=\"one mobile half\">
                    <h5 title=\"Search for excluded tests to be added for this user.\">
                        Excluded Tests</h5>
                    <div class=\"row pad-right\">
                        <div class=\"five mobile sevenths pad-right\">
                            <label for=\"excludedTestName\">Test Name</label>
                            <input type=\"text\" name=\"excludedTestName\" id=\"excludedTestName\" class=\"testInput\" autocomplete=\"off\" tabindex=\"52\" />
                        </div>
                        <div class=\"two mobile sevenths\">
                            <label for=\"excludedTestNumber\">Test Number</label>
                            <input type=\"number\" name=\"excludedTestNumber\" id=\"excludedTestNumber\" class=\"testInput\" tabindex=\"53\" />
                        </div>
                        <div class=\"one mobile whole\">
                            <div id=\"excludedTestsResults\" class=\"box_shadow searchResults\"></div>
                        </div>
                    </div>
                </div>
                <div class=\"one mobile half pad-left\">
                    <label for=\"selectedExcludedTests\">Selected Excluded Tests: </label>
                    <div class=\"row\">
                        <div class=\"one mobile whole\" id=\"selectedExcludedTestsContainer\">
                            $testsHtml
                        </div>
                    </div>
                </div>
            </div>";


        return $excludedTestsHtml;
    }

    private function printCommonDrugsFields() {
        $drugsHtml = "";
        if (count($this->SelectedCommonDrugs) > 0) {
            $drugsHtml .= "
                <div class='row' id='selectedCommonDrugsHeader'>
                    <div class='three mobile sixths'>Generic Name</div>
                    <div class='two mobile sixths'>Substance</div>
                    <div class='one mobile sixth'>Remove</div>
                </div>";

            foreach($this->SelectedCommonDrugs as $currDrug) {
                if ($currDrug instanceof Drug) {
                    $iddrugs = $currDrug->iddrugs;
                    $genericName = $currDrug->genericName;
                    $substanceName = "";
                    if (count($currDrug->Substances) > 0) {
                        $substances = $currDrug->Substances;
                        $substance1 = $substances[0];
                        $substanceName = $substance1->substance;
                    }

                    $drugsHtml .= "
                    <div class='row selectedCommonDrugs'>
                        <div class='three mobile sixths'>$genericName</div>
                        <div class='two mobile sixths'>$substanceName</div>
                        <div class='one mobile sixth'>
                            <a href='javascript:void(0)' class='removeDrugs' id='$iddrugs'><i class='icon icon-trash'></i></a>
                        </div>
                    </div>";
                }
            }
        }

        $html = "
            <div class='row' id='commonDrugsRow'>
                <div class='one mobile half'>
                    <h5 title='Search for common prescriptions to be added for this user.'>Common Prescriptions</h5>
                    <div class='row pad-right'>
                        <div class='three mobile fifths pad-right'>
                            <label for='commonDrugName'>Generic Name</label>
                            <input type='text' name='commonDrugName' id='commonDrugName' class='drugInput' autocomplete='off' tabindex='53' />
                        </div>
                        <div class='two mobile fifths'>
                            <label for='commonSubstanceName'>Substance Name</label>
                            <input type='text' name='commonSubstanceName' id='commonSubstanceName' class='drugInput' autocomplete='off' tabindex='54' />
                        </div>
                        <div class='one mobile whole'>
                            <div id='commonDrugsResults' class='box_shadow searchResults'></div>
                        </div>
                    </div>
                </div>
                <div class='one mobile half pad-left'>
                    <label for='selectedCommonDrugs'>Selected Common Prescriptions: </label>
                    <div class='row'>
                        <div class='one mobile whole' id='selectedCommonDrugsContainer'>
                            $drugsHtml
                        </div>
                    </div>
                </div>
            </div>";

        return $html;
    }
}
?>
