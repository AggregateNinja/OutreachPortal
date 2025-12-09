<?php
require_once 'AdminClient.php';
require_once 'PageClient.php';
require_once 'IClient.php';

class AdminViewClient extends PageClient implements IClient {

    private $AdminClient;
    private $UserId = null;
    private $IsMasterAdmin = false;
    private $UserPageInfo = array(
        "Administrators" => array(
            "MaxRows" => 100,
            "Offset" => 0,
            "CurrentPage" => 1,
            "TotalUsers" => 0,
            "TotalPages" => 1
        ),
        "Clients" => array(
            "MaxRows" => 100,
            "Offset" => 0,
            "CurrentPage" => 1,
            "TotalUsers" => 0,
            "TotalPages" => 1
        ),
        "Doctors" => array(
            "MaxRows" => 100,
            "Offset" => 0,
            "CurrentPage" => 1,
            "TotalUsers" => 0,
            "TotalPages" => 1
        ),
        "Salesmen" => array(
            "MaxRows" => 100,
            "Offset" => 0,
            "CurrentPage" => 1,
            "TotalUsers" => 0,
            "TotalPages" => 1
        ),
        "Insurances" => array(
            "MaxRows" => 100,
            "Offset" => 0,
            "CurrentPage" => 1,
            "TotalUsers" => 0,
            "TotalPages" => 1
        ),
        "OrderEntryAdmins" => array(
            "MaxRows" => 100,
            "Offset" => 0,
            "CurrentPage" => 1,
            "TotalUsers" => 0,
            "TotalPages" => 1
        ),
        "Patients" => array(
            "MaxRows" => 100,
            "Offset" => 0,
            "CurrentPage" => 1,
            "TotalUsers" => 0,
            "TotalPages" => 1
        ),
        "PatientAdmins" => array(
            "MaxRows" => 100,
            "Offset" => 0,
            "CurrentPage" => 1,
            "TotalUsers" => 0,
            "TotalPages" => 1
        )
    );

    public $Search = "";
    public $SortBy = "";
    public $Direction = "";

    public function __construct(array $data = null) {
        parent::__construct();

        if (array_key_exists("search", $_GET)) {
            $this->Search = $_GET['search'];
        }
        if (array_key_exists("sortby", $_GET)) {
            $this->SortBy = $_GET['sortby'];
        }
        if (array_key_exists("direction", $_GET)) {
            $this->Direction = $_GET['direction'];
        }

        if (isset($this->User->clientId) && !empty($this->User->clientId)) {

            $this->AdminClient = new AdminClient(array("clientId" => $this->User->clientId, "userId" => $this->User->idUsers));
        } else {
            $this->AdminClient = new AdminClient();
        }

        /*if (array_key_exists("clientName", $_GET) && !empty($_GET['clientName'])) {
            $clientSearch = array();
            foreach ($this->AdminClient->Users['Clients'] as $user) {
                if (strpos($user->clientName, $_GET['clientName']) !== false) {
                    $clientSearch[] = $user;
                }
            }
        }*/

        $typeName = "";
        if (isset($_GET['type'])) {
            $userType = $_GET['type'];
            if ($userType == 1) {
                $typeName = "Administrators";
            } else if ($userType == 2) {
                $typeName = "Clients";
            } else if ($userType == 3) {
                $typeName = "Doctors";
            } else if ($userType == 4) {
                $typeName = "Patients";
            } else if ($userType == 5) {
                $typeName = "Salesmen";
            } else if ($userType == 6) {
                $typeName = "Insurances";
            } else if ($userType == 7) {
                $typeName = "OrderEntryAdmins";
            } else if ($userType == 8) {
                $typeName = "PatientAdmins";
            }
        }

        if (isset($_GET['page']) && isset($_GET['type'])) {
            $currPage = $_GET['page'];
            if (array_key_exists($typeName, $this->UserPageInfo)) {
                $this->UserPageInfo[$typeName]['CurrentPage'] = $currPage;
            }
        }

        $this->UserPageInfo['Administrators']['TotalUsers'] = count($this->AdminClient->Users['Administrators']);
        $this->UserPageInfo['Administrators']['TotalPages'] = ceil(count($this->AdminClient->Users['Administrators']) / $this->UserPageInfo['Administrators']['MaxRows']);

        $this->UserPageInfo['OrderEntryAdmins']['TotalUsers'] = count($this->AdminClient->Users['OrderEntryAdmins']);
        $this->UserPageInfo['OrderEntryAdmins']['TotalPages'] = ceil(count($this->AdminClient->Users['OrderEntryAdmins']) / $this->UserPageInfo['OrderEntryAdmins']['MaxRows']);


        if (array_key_exists("search", $_GET) && !empty($typeName)) {
            $search = $_GET['search'];

            /*if ($typeName == "Clients") {
                $this->setUserPageInfo("Clients", "clientName", $search);
            } else {
                $this->setUserPageInfo($typeName, "name", $search);
            }*/

            $this->setUserPageInfo($typeName, $search);

        } else {
            $this->UserPageInfo['Clients']['TotalUsers'] = count($this->AdminClient->Users['Clients']);
            $this->UserPageInfo['Doctors']['TotalUsers'] = count($this->AdminClient->Users['Doctors']);
            $this->UserPageInfo['Patients']['TotalUsers'] = count($this->AdminClient->Users['Patients']);
            $this->UserPageInfo['Salesmen']['TotalUsers'] = count($this->AdminClient->Users['Salesmen']);
            $this->UserPageInfo['Insurances']['TotalUsers'] = count($this->AdminClient->Users['Insurances']);
            $this->UserPageInfo['PatientAdmins']['TotalUsers'] = count($this->AdminClient->Users['PatientAdmins']);
            $this->UserPageInfo['Clients']['TotalPages'] = ceil(count($this->AdminClient->Users['Clients']) / $this->UserPageInfo['Clients']['MaxRows']);
            $this->UserPageInfo['Doctors']['TotalPages'] = ceil(count($this->AdminClient->Users['Doctors']) / $this->UserPageInfo['Doctors']['MaxRows']);
            $this->UserPageInfo['Patients']['TotalPages'] = ceil(count($this->AdminClient->Users['Patients']) / $this->UserPageInfo['Patients']['MaxRows']);
            $this->UserPageInfo['Salesmen']['TotalPages'] = ceil(count($this->AdminClient->Users['Salesmen']) / $this->UserPageInfo['Salesmen']['MaxRows']);
            $this->UserPageInfo['Insurances']['TotalPages'] = ceil(count($this->AdminClient->Users['Insurances']) / $this->UserPageInfo['Insurances']['MaxRows']);
            $this->UserPageInfo['PatientAdmins']['TotalPages'] = ceil(count($this->AdminClient->Users['PatientAdmins']) / $this->UserPageInfo['PatientAdmins']['MaxRows']);

        }

        //echo "<pre>"; print_r($this->UserPageInfo); echo "</pre>";

        $this->UserId = $this->User->idUsers;
        if ($this->User->hasAdminSetting(8)) {
            $this->IsMasterAdmin = true;
        }
        $this->addStylesheet("/outreach/css/_pagination.css");
        $this->addStylesheet("/outreach/admin/css/main.css");
        $this->addScript("/outreach/admin/js/main.js");
        $this->addOverlay("
            <div id='patientEmailOverlay' class='rounded'>
                <div class='row'>
                    <div class='ten mobile twelfths' style='text-align: center'>
                        <h4>Verification Email Sent</h4>
                    </div>
                </div>
                <div class='row'>
                    <div class='one mobile whole' style='text-align: center'>
                        <button class='green' id='btnEmailOk'>Ok</button>
                    </div>
                </div>
            </div>
        ");
    }

    private function setUserPageInfo($typeName, $searchValue) {
        foreach ($this->AdminClient->Users as $userTypeName => $users) {
            if (!empty($typeName) && $userTypeName == $typeName) {
                $userSearch = array();
                foreach ($users as $user) {
                    if ($userTypeName == "Clients") {
                        $currUserSearchField = strtolower($user->clientName);
                    } else if ($userTypeName == "Insurances") {
                        $currUserSearchField = strtolower($user->name);
                    } else {
                        //$currUserSearchField = strtolower($user->firstName . ' ' . $user->lastName);
                        $currUserSearchField = strtolower($user->firstName);
                    }

                    if (empty($searchValue) || strpos($currUserSearchField, strtolower($searchValue)) !== false) {
                        $userSearch[] = $user;
                    }
                }
                $this->AdminClient->Users[$typeName] = $userSearch;
                $this->UserPageInfo[$typeName]['TotalUsers'] = count($userSearch);
                $this->UserPageInfo[$typeName]['TotalPages'] = ceil(count($userSearch) / $this->UserPageInfo[$typeName]['MaxRows']);
            } else {
                $this->UserPageInfo[$userTypeName]['TotalUsers'] = count($this->AdminClient->Users[$userTypeName]);
                $this->UserPageInfo[$userTypeName]['TotalPages'] = ceil(count($this->AdminClient->Users[$userTypeName]) / $this->UserPageInfo[$userTypeName]['MaxRows']);
            }
        }
    }

    public function printPage() {

        $direction = $this->AdminClient->Direction;
        $message = $this->AdminClient->Message;

        $htmlAdmins = $this->printAdminUsers();
        $htmlClients = $this->printClientUsers();
        $htmlDoctors = $this->printDoctorUsers();
        $htmlPatients = $this->printPatientUsers();
        $htmlPatientAdmins = $this->printPatientAdmins();

        $clientSearchUrlParam = "";
        $clientName = "";
        if (array_key_exists("search", $_GET) && array_key_exists("type", $_GET) && $_GET['type'] == 2) {
            $clientSearchUrlParam = "&search=" . $_GET['search'];
            $clientName = $_GET['search'];
        }
        $doctorSearchUrlParam = "";
        $doctorName = "";
        if (array_key_exists("search", $_GET) && array_key_exists("type", $_GET) && $_GET['type'] == 3) {
            $doctorSearchUrlParam = "&search=" . $_GET['search'];
            $doctorName = $_GET['search'];
        }
        $salesmenSearchUrlParam = "";
        $salesmenName = "";
        if (array_key_exists("search", $_GET) && array_key_exists("type", $_GET) && $_GET['type'] == 5) {
            $salesmenSearchUrlParam = "&search=" . $_GET['search'];
            $salesmenName = $_GET['search'];
        }
        $insuranceSearchUrlParam = "";
        $insuranceName = "";
        if (array_key_exists("search", $_GET) && array_key_exists("type", $_GET) && $_GET['type'] == 6) {
            $insuranceSearchUrlParam = "&search=" . $_GET['search'];
            $insuranceName = $_GET['search'];
        }

        $htmlSalesmen = "";
        if ($this->User->hasAdminSetting(11)) {
            $htmlSalesmenList = $this->printSalesmenUsers();
            $htmlSalesmen = "
                <div class=\"one whole mobile pad-top pad-bottom centered rounded box_shadow\" style=\"margin-top: 10px;\">
                    <div class=\"row\" id=\"salesmenHeader\" title=\"salesmenHeader\">
                        <div class=\"four mobile twelfths\">
                            <h5>Salesmen</h5>
                        </div>
                        <div class=\"seven mobile twelfths\">
                            <div class=\"row\">
                                <div class=\" mobile third\">
                                    <label for=\"salesmenSearch\">Filter Salesmen:</label>
                                </div>
                                <div class=\"two mobile thirds\">
                                     <input type=\"text\" name=\"salesmenSearch\" id=\"salesmenSearch\" class=\"userSearch\" value=\"$salesmenName\" placeholder=\"Begin typing a salesman name to search\" />
                                </div>
                            </div>
                        </div>
                        <div class=\"one mobile twelfth pad-right\">
                            <a class=\"toggle\" href=\"javascript:void(0)\" title=\"hide field group\">-</a>
                        </div>
                    </div>
                    <div class=\"user-container\">
                        <div class=\"row columnHeader\">
                            <div class=\"three mobile elevenths\"><p>Email <a id=\"salesmenSortEmail\" href=\"index.php?type=5&sortby=email&direction=$direction" . $salesmenSearchUrlParam . "#salesmenHeader\"><i class=\"icon-sort\"></i></a></p></div>
                            <div class=\"two mobile elevenths\"><p>Name <a id=\"salesmenSortName\" href=\"index.php?type=5&sortby=name&direction=$direction" . $salesmenSearchUrlParam . "#salesmenHeader\"><i class=\"icon-sort\"></i></a></p></div>
                            <div class=\"two mobile elevenths\"><p>Territory <a id=\"salesmenSortTerritory\" href=\"index.php?type=5&sortby=territory&direction=$direction" . $salesmenSearchUrlParam . "#salesmenHeader\"><i class=\"icon-sort\"></i></a></p></div>
                            <div class=\"two mobile elevenths\"><p>Group Name <a id=\"salesmenSortGroupName\" href=\"index.php?type=5&sortby=groupName&direction=$direction" . $salesmenSearchUrlParam . "#salesmenHeader\"><i class=\"icon-sort\"></i></a></p></div>
                            <div class=\"two mobile elevenths\"><p>Action</p></div>
                        </div>
                        <div style=\"margin: 0 20px; border-bottom: 1px solid #CCCCCC;\"></div>

                        $htmlSalesmenList
                    </div>
                </div>";
        }

        $htmlInsurances = "";
        if ($this->User->hasAdminSetting(12)) {
            $htmlInsurancesList = $this->printInsuranceUsers();
            $htmlInsurances = "
                <div class=\"one mobile whole pad-top pad-bottom centered rounded box_shadow\" style=\"margin-top: 10px;\">
                    <div class=\"row\" id=\"insurancesHeader\" title=\"insurancesHeader\">
                        <div class=\"three mobile twelfths\">
                            <h5>Insurances</h5>
                        </div>
                        <div class=\"eight mobile twelfths\">
                            <div class=\"row\">
                                <div class=\"one mobile third\">
                                    <label for=\"salesmenSearch\">Filter Insurances:</label>
                                </div>
                                <div class=\"two mobile thirds\">
                                     <input type=\"text\" name=\"insuranceSearch\" id=\"insuranceSearch\" class=\"userSearch\" value=\"$insuranceName\" placeholder=\"Begin typing an insurance name to search\" />
                                </div>
                            </div>
                        </div>
                        <div class=\"one mobile twelfth pad-right\">
                            <a class=\"toggle\" href=\"javascript:void(0)\" title=\"hide field group\">-</a>
                        </div>
                    </div>
                    <div class=\"user-container\">
                        <div class=\"row columnHeader\">
                            <div class=\"three mobile elevenths\"><p>Email <a id=\"insuranceSortNumber\" href=\"index.php?type=6&sortby=email&direction=$direction" . $insuranceSearchUrlParam . "#insurancesHeader\"><i class=\"icon-sort\"></i></a></p></div>
                            <div class=\"three mobile elevenths\"><p>Name <a id=\"insuranceSortName\" href=\"index.php?type=6&sortby=name&direction=$direction" . $insuranceSearchUrlParam . "#insurancesHeader\"><i class=\"icon-sort\"></i></a></p></div>
                            <div class=\"three mobile elevenths\"><p>Address <a id=\"insuranceSortAddress\" href=\"index.php?type=6&sortby=territory&direction=$direction" . $insuranceSearchUrlParam . "#insurancesHeader\"><i class=\"icon-sort\"></i></a></p></div>
                            <div class=\"two mobile elevenths\"><p>Action</p></div>
                        </div>
                        <div style=\"margin: 0 20px; border-bottom: 1px solid #CCCCCC;\"></div>

                        $htmlInsurancesList
                    </div>
                </div>";
        }

        $htmlOrderEntryAdmins = "";
        if (self::OrderEntryAdminDisabled == false && ($this->User->hasAdminSetting(8) || $this->User->hasAdminSetting(9))) {
            $htmlOrderEntryAdminsList = $this->printOrderEntryAdmins();
            $htmlOrderEntryAdmins = "<div class=\"one mobile whole pad-top pad-bottom centered rounded box_shadow\" style=\"margin-bottom: 10px;\">
                <div class=\"row\">
                    <div class=\"one mobile half\">
                        <h5>Order Entry Administrators</h5>
                    </div>
                    <div class=\"one mobile half pad-right\">
                        <a class=\"toggle\" href=\"javascript:void(0)\" title=\"hide field group\">-</a>
                    </div>
                </div>
                <div class=\"user-container\">
                    <div class=\"row columnHeader desktop-only\">
                        <div class=\"one mobile fourth\"><p>Id <a href=\"index.php?type=7&sortby=id&direction=$direction\"><i class=\"icon-sort\"></i></a></p></div>
                        <div class=\"one mobile fourth\"><p>Email <a href=\"index.php?type=7&sortby=email&direction=$direction\"><i class=\"icon-sort\"></i></a></p></div>
                        <div class=\"one mobile fourth desktop-only\"><p>Date Created <a href=\"index.php?type=7&sortby=dateCreated&direction=$direction\"><i class=\"icon-sort\"></i></a></p></div>
                        <div class=\"one mobile fourth\"><p>Action</p></div>
                    </div>
                    <div class=\"row columnHeader hide-on-desktop\">
                        <div class=\"one mobile third id\"><p>Id <a href=\"index.php?type=7&sortby=id&direction=$direction\"><i class=\"icon-sort\"></i></a></p></div>
                        <div class=\"one mobile third name\"><p>Email <a href=\"index.php?type=7&sortby=email&direction=$direction\"><i class=\"icon-sort\"></i></a></p></div>
                        <div class=\"one mobile third action\"><p>Action</p></div>
                    </div>
                    <div style=\"margin: 0 20px; border-bottom: 1px solid #CCCCCC;\"></div>
    
                    $htmlOrderEntryAdminsList
                </div>
            </div>";
        }

        $htmlAdministrators = "";
        $htmlDoctorsSection = "";
        $colWidth = "third";
        $clientEmailCol = "";
        if ($this->User->typeId == 1) { // || $this->User->typeId != 7
            $colWidth = "fifth";
            $clientEmailCol = "<div class='one mobile $colWidth email hide-on-mobile'><p style='text-align: left'>Email <a id='clientSortEmail' href='index.php?type=2&sortby=email&direction=$direction" . $clientSearchUrlParam . "#clientsHeader'><i class='icon-sort'></i></a></p></div>";
            $htmlAdministrators = "<div class=\"one mobile whole pad-top pad-bottom centered rounded box_shadow\" style=\"margin-bottom: 10px;\">
                <div class=\"row\">
                    <div class=\"one mobile half\">
                        <h5>Administrators</h5>
                    </div>
                    <div class=\"one mobile half pad-right\">
                        <a class=\"toggle\" href=\"javascript:void(0)\" title=\"hide field group\">-</a>
                    </div>
                </div>
                <div class=\"user-container\">
                    <div class=\"row columnHeader desktop-only\">
                        <div class=\"one mobile fourth\"><p>Id <a href=\"index.php?type=1&sortby=id&direction=$direction\"><i class=\"icon-sort\"></i></a></p></div>
                        <div class=\"one mobile fourth\"><p>Email <a href=\"index.php?type=1&sortby=email&direction=$direction\"><i class=\"icon-sort\"></i></a></p></div>
                        <div class=\"one mobile fourth desktop-only\"><p>Date Created <a href=\"index.php?type=1&sortby=dateCreated&direction=$direction\"><i class=\"icon-sort\"></i></a></p></div>
                        <div class=\"one mobile fourth\"><p>Action</p></div>
                    </div>
                    <div class=\"row columnHeader hide-on-desktop\">
                        <div class=\"one mobile third id\"><p>Id <a href=\"index.php?type=1&sortby=id&direction=$direction\"><i class=\"icon-sort\"></i></a></p></div>
                        <div class=\"one mobile third name\"><p>Email <a href=\"index.php?type=1&sortby=email&direction=$direction\"><i class=\"icon-sort\"></i></a></p></div>
                        <div class=\"one mobile third action\"><p>Action</p></div>
                    </div>
                    <div style=\"margin: 0 20px; border-bottom: 1px solid #CCCCCC;\"></div>

                    $htmlAdmins
                </div>
            </div>";

            $htmlDoctorsSection = "<div class=\"one mobile whole pad-top pad-bottom centered rounded box_shadow\">
                <div class=\"row\" id=\"doctorsHeader\" title=\"doctorsHeader\">
                    <div class=\"three mobile twelfths\">
                        <h5>Doctors</h5>
                    </div>
                    <div class=\"eight mobile twelfths\">
                        <div class=\"row\">
                            <div class=\"one mobile third\">
                                <label for=\"doctorSearch\">Filter Doctors:</label>
                            </div>
                            <div class=\"two mobile thirds\">
                                <input type=\"text\" name=\"doctorSearch\" id=\"doctorSearch\" class=\"userSearch\" value=\"$doctorName\" placeholder=\"Begin typing a doctor name to search\" />
                            </div>
                        </div>
                    </div>
                    <div class=\"one mobile twelfth pad-right\">
                        <a class=\"toggle\" href=\"javascript:void(0)\" title=\"hide field group\">-</a>
                    </div>
                </div>
                <div class=\"user-container\">
                    <div class=\"row columnHeader\">
                        <div class=\"one mobile fifth\"><p>Doctor # <a id=\"doctorSortNumber\" href=\"index.php?type=3&sortby=number&direction=$direction" . $doctorSearchUrlParam . "#doctorsHeader\"><i class=\"icon-sort\"></i></a></p></div>
                        <div class=\"one mobile fifth email hide-on-mobile\"><p style=\"text-align: left;\">Email <a id=\"doctorSortEmail\" href=\"index.php?type=3&sortby=email&direction=$direction" . $doctorSearchUrlParam . "#doctorsHeader\"><i class=\"icon-sort\"></i></a></p></div>
                        <div class=\"one mobile fifth name\"><p style=\"text-align: left;\">Name <a id=\"doctorSortName\" href=\"index.php?type=3&sortby=name&direction=$direction" . $doctorSearchUrlParam . "#doctorsHeader\"><i class=\"icon-sort\"></i></a></p></div>
                        <div class=\"one mobile fifth desktop-only\"><p style=\"text-align: left !imporant;\">Address <a id=\"doctorSortAddress\" href=\"index.php?type=3&sortby=address&direction=$direction" . $doctorSearchUrlParam . "#doctorsHeader\"><i class=\"icon-sort\"></i></a></p></div>
                        <div class=\"one mobile fifth action\"><p>Action</p></div>
                    </div>
                    <div style=\"margin: 0 20px; border-bottom: 1px solid #CCCCCC;\"></div>

                    $htmlDoctors
                </div>
            </div>";
        }


        /*<div class='three mobile twelfths'>
            <label for='amountPerPage'>Amount Per Page: </label>
            <select name='amountPerPage' id='amountPerPage'>
                <option value='50'>50</option>
                <option value='100' selected='selected'>100</option>
                <option value='250'>250</option>
                <option value='500'>500</option>
                <option value='1000'>1000</option>
            </select>
        </div>*/

        $isOrderEntryAdmin = 0;
        if ($this->User->typeId == 7) {
            $isOrderEntryAdmin = 1;
        }
        $isMasterAdmin = 0;
        if ($this->IsMasterAdmin) {
            $isMasterAdmin = 1;
        }


        $inputSortBy = "";
        if (array_key_exists("sortby", $_GET)) {
            $inputSortBy = "<input type='hidden' name='sortBy' id='sortBy' value='" . $_GET['sortby'] . "' />";
        }
        $inputDirection = "";
        if (array_key_exists("direction", $_GET)) {
            $inputDirection = "<input type='hidden' name='sortBy' id='sortBy' value='" . $_GET['direction'] . "' />";
        }

        $htmlClientsSection = "";
        if ($this->User->typeId != 8) {
            $htmlClientsSection = "<div class=\"one mobile whole pad-top pad-bottom centered rounded box_shadow\" style=\"margin-bottom: 10px;\">
                            <div class=\"row\" id=\"clientsHeader\" title=\"clientsHeader\">
                                <div class=\"three mobile twelfths\">
                                    <h5>Clients</h5>
                                </div>
                                <div class=\"eight mobile twelfths\">
                                    <div class=\"row\">
                                        <div class=\"one mobile third\">
                                            <label for=\"clientSearch\">Filter Clients:</label>
                                        </div>
                                        <div class=\"two mobile thirds pad-right\">
                                            <input type=\"text\" name=\"clientSearch\" id=\"clientSearch\" class=\"userSearch\" value=\"$clientName\" placeholder=\"Begin typing a client name to search\" />
                                        </div>
                                    </div>
                                </div>
                                
                                <div class=\"one mobile twelfth pad-right\">
                                    <a class=\"toggle\" href=\"javascript:void(0)\" title=\"hide field group\">-</a>
                                </div>
                            </div>
                            <div class=\"user-container\">
                                <div class=\"row columnHeader\">
                                    <div class=\"one mobile $colWidth\"><p>Client # <a id='clientSortNumber' href=\"index.php?type=2&sortby=clientNo&direction=$direction" . $clientSearchUrlParam . "#clientsHeader\"><i class=\"icon-sort\"></i></a></p></div>
                                    $clientEmailCol
                                    <div class=\"one mobile $colWidth name\"><p style=\"text-align: left;\">Name <a id='clientSortName' href=\"index.php?type=2&sortby=clientName&direction=$direction" . $clientSearchUrlParam . "#clientsHeader\"><i class=\"icon-sort\"></i></a></p></div>
                                    <div class=\"one mobile $colWidth desktop-only\"><p style=\"text-align: left !important;\">Address <a id='clientSortAddress' href=\"index.php?type=2&sortby=clientStreet&direction=$direction" . $clientSearchUrlParam . "#clientsHeader\"><i class=\"icon-sort\"></i>
                                    </a></p></div>";

            if ($colWidth == "fifth") {
                $htmlClientsSection .= "<div class=\"one mobile $colWidth action\"><p>Action</p></div>";
            }

            $htmlClientsSection .= "
                                </div>
                                <div style=\"margin: 0 20px; border-bottom: 1px solid #CCCCCC;\"></div>

                                $htmlClients
                            </div>
                        </div>";
        }


        echo "
        <input type='hidden' name='isOrderEntryAdmin' id='isOrderEntryAdmin' value='$isOrderEntryAdmin' />
        <input type='hidden' name='isMasterAdmin' id='isMasterAdmin' value='$isMasterAdmin' />
        <input type='hidden' name='clientsCurrPage' id='clientsCurrPage' value='" . $this->UserPageInfo['Clients']['CurrentPage'] . "' />
        <input type='hidden' name='doctorsCurrPage' id='doctorsCurrPage' value='" . $this->UserPageInfo['Doctors']['CurrentPage'] . "' />
        <input type='hidden' name='patientsCurrPage' id='patientsCurrPage' value='" . $this->UserPageInfo['Patients']['CurrentPage'] . "' />
        <input type='hidden' name='salesmenCurrPage' id='salesmenCurrPage' value='" . $this->UserPageInfo['Salesmen']['CurrentPage'] . "' />
        <input type='hidden' name='insurancesCurrPage' id='insurancesCurrPage' value='" . $this->UserPageInfo['Insurances']['CurrentPage'] . "' />
        <input type='hidden' name='orderEntryAdminsCurrPage' id='orderEntryAdminsCurrPage' value='" . $this->UserPageInfo['OrderEntryAdmins']['CurrentPage'] . "' />
        $inputSortBy
        $inputDirection
        <i class=\"icon-spinner icon-spin icon-4x\" id=\"loading-spinner\"></i>
        <div class=\"container\" style=\"margin-top: 20px;\">
            <div class=\"row\">
                <div class=\"one mobile whole padded centered rounded box_shadow\">
                    <div class=\"row responsive\" data-compression=\"80\">
                        <div class=\"one third\">
                            <h4>View Users</h4>
                        </div>
                        <div class=\"one mobile third\">
                            $message
                        </div>
                        <div class=\"one mobile third\"></div>
                    </div>

                    <div class=\"row responsive\" data-compression=\"80\">
                        $htmlAdministrators
                        
                        $htmlOrderEntryAdmins

                        $htmlClientsSection

                        $htmlDoctorsSection

                        $htmlSalesmen

                        $htmlInsurances
                        
                        $htmlPatients
                        
                        $htmlPatientAdmins
                    </div>
                </div>
            </div>
        </div>

        <div id=\"deleteConfirm\" class=\"rounded\">
            <div style=\"margin: 0 auto; width: 100%; text-align:center;\">
            <h5 style=\"margin: 0 auto 10px auto;\">Are you sure you would like to delete this user?</h5>
            <a href=\"javascript:void(0)\" id=\"deleteYes\" class=\"button\">Yes</a>
            <a href=\"javascript:void(0)\" id=\"deleteNo\" class=\"button\">No</a>
            </div>
        </div>
        ";
    }

    private function printAdminUsers() {
        $htmlAdminUsers = "";
        if (count($this->AdminClient->Users['Administrators']) > 0) {
            //foreach ($this->AdminClient->Users['Administrators'] as $admin) {
            for ($i = ($this->UserPageInfo['Administrators']['CurrentPage'] * $this->UserPageInfo['Administrators']['MaxRows']) - $this->UserPageInfo['Administrators']['MaxRows'];
                 $i < $this->UserPageInfo['Administrators']['CurrentPage'] * $this->UserPageInfo['Administrators']['MaxRows'] && $i < $this->UserPageInfo['Administrators']['TotalUsers'];
                 $i++) {
                $admin = $this->AdminClient->Users['Administrators'][$i];

                if (!$admin->hasAdminSetting(8) || (isset($this->UserId) && !empty($this->UserId) && $admin->idUsers == $this->UserId)) {
                    $disabled = "";
                    $idLoggedIn = $admin->idLoggedIn;
                    $title = "title=\"Log-out this user\"";
                    if (!empty($idLoggedIn) && $admin->idUsers == $this->UserId) {
                        $disabled = "disabled=\"disabled\"";
                        $title = "title=\"You are currently logged in as this user\"";
                    } else if (empty($idLoggedIn)) {
                        $disabled = "disabled=\"disabled\"";
                        $title = "title=\"This user is not currently logged in\"";
                    }

                    $currAdminId = $admin->idUsers;
                    $currAdminEmail = $admin->email;
                    $currDateCreated = date("m/d/Y h:i:s A", strtotime($admin->dateCreated));
                    $currDeleteAdminId = $admin->idUsers . ":" . $admin->typeId;

                    $lnkSignInAsUser = "";
                    if ($admin->hasAdminSetting(8) || $admin->hasAdminSetting(13)) {
                        $lnkSignInAsUser = "<a href=\"javascript:void(0)\" class=\"signinasuser\" id=\"$currAdminId\" title=\"Sign in as sales owner\"><i class='icon-signin'></i></a>";
                    } else if ($admin->hasAdminSetting(16) && $_SESSION['id'] == $admin->idUsers) {
                        $lnkSignInAsUser = "<a href=\"javascript:void(0)\" class=\"signinasuser\" id=\"$currAdminId\" title=\"Sign in as sales owner\"><i class='icon-signin'></i></a>";
                    }


                    $col1Html = "<p>$currAdminId</p>";
                    if (($admin->hasAdminSetting(13) || $admin->hasAdminSetting(16)) && ($this->User->idUsers == $admin->idUsers || $this->User->hasAdminSetting(8))) {
                        $col1Html = "
                            <div class=\"row\">
                                <div class=\"two mobile sixths right-one\">
                                    <a href='javascript:void(0)' class='signinasuser' id='$currAdminId' title='Sign in as sales owner'><i class='icon-signin'></i></a>
                                </div>
                                <div class=\"two mobile sixths\">
                                    <p>$currAdminId</p>
                                </div>
                            </div>
                        ";
                    }

                    $htmlAdminUsers .= "
                    <div class='row user pad-top desktop-only'>
                        <div class=\"one mobile fourth\">$col1Html</div>
                        <div class=\"one mobile fourth\"><p>$currAdminEmail</p></div>
                        <div class=\"one mobile fourth\"><p>$currDateCreated</p></div>
                        <div class=\"one mobile fourth\"><p style=\"text-align: center;\">
                            <a href=\"javascript:void(0)\" id=\"$currDeleteAdminId\" class=\"button delete\">Delete</a>
                            <a href=\"edit.php?id=$admin->idUsers&type=$admin->typeId\" class=\"button\">Edit</a> ";
                            if ($this->IsMasterAdmin) {
                                $htmlAdminUsers .= "<a href=\"javascript:void(0)\" id=\"$currAdminId\" class=\"sign-out button\" $title $disabled><i class=\"icon icon-off\"></i></a>";
                            }
                    $htmlAdminUsers .= "</p></div>
                    </div>
                    <div class='row user pad-top hide-on-desktop'>
                        <div class=\"one mobile third id\">$col1Html</div>
                        <div class=\"one mobile third name\"><p>$currAdminEmail</p></div>
                        <div class=\"one mobile third action\"><p style=\"text-align: center;\">
                            <a href=\"javascript:void(0)\" id=\"$currDeleteAdminId\" class=\"button delete\">Delete</a>
                            <a href=\"edit.php?id=$admin->idUsers&type=$admin->typeId\" class=\"button\">Edit</a> ";
                    if ($this->IsMasterAdmin) {
                        $htmlAdminUsers .= "<a href=\"javascript:void(0)\" id=\"$currAdminId\" class=\"sign-out button\" $title $disabled><i class=\"icon icon-off\"></i></a>";
                    }
                    $htmlAdminUsers .= "</p></div>
                    </div>
                    ";
                }
            }

            if ($this->UserPageInfo['Administrators']['TotalUsers'] > $this->UserPageInfo['Administrators']['MaxRows']) {
                $htmlAdminUsers .= $this->getPaginationHtml($this->UserPageInfo['Administrators']['CurrentPage'], $this->UserPageInfo['Administrators']['TotalPages'], 1, $this->Search, $this->SortBy, $this->Direction);
            }
        } else {
            $htmlAdminUsers .= "
            <div class=\"row user pad-top\">
                <div class=\"one mobile whole\"><p>This user has no order entry administrators</p></div>
            </div>
            ";
        }

        return $htmlAdminUsers;
    }

    private function printOrderEntryAdmins() {
        $htmlAdminUsers = "";
        if (count($this->AdminClient->Users['OrderEntryAdmins']) > 0) {
            //foreach ($this->AdminClient->Users['OrderEntryAdmins'] as $admin) {
            for ($i = ($this->UserPageInfo['OrderEntryAdmins']['CurrentPage'] * $this->UserPageInfo['OrderEntryAdmins']['MaxRows']) - $this->UserPageInfo['OrderEntryAdmins']['MaxRows'];
                 $i < $this->UserPageInfo['OrderEntryAdmins']['CurrentPage'] * $this->UserPageInfo['OrderEntryAdmins']['MaxRows'] && $i < $this->UserPageInfo['OrderEntryAdmins']['TotalUsers'];
                 $i++) {
                $admin = $this->AdminClient->Users['OrderEntryAdmins'][$i];

                if (!$admin->hasAdminSetting(8) || (isset($this->UserId) && !empty($this->UserId) && $admin->idUsers == $this->UserId)) {
                    $disabled = "";
                    $idLoggedIn = $admin->idLoggedIn;
                    $title = "title=\"Log-out this user\"";
                    if (!empty($idLoggedIn) && $admin->idUsers == $this->UserId) {
                        $disabled = "disabled=\"disabled\"";
                        $title = "title=\"You are currently logged in as this user\"";
                    } else if (empty($idLoggedIn)) {
                        $disabled = "disabled=\"disabled\"";
                        $title = "title=\"This user is not currently logged in\"";
                    }

                    $currAdminId = $admin->idUsers;
                    $currAdminEmail = $admin->email;
                    $currDateCreated = date("m/d/Y h:i:s A", strtotime($admin->dateCreated));
                    $currDeleteAdminId = $admin->idUsers . ":" . $admin->typeId;

                    $lnkSignInAsUser = "";
                    if ($admin->hasAdminSetting(8) || $admin->hasAdminSetting(13)) {
                        $lnkSignInAsUser = "<a href=\"javascript:void(0)\" class=\"signinasuser\" id=\"$currAdminId\" title=\"Sign in as sales owner\"><i class='icon-signin'></i></a>";
                    } else if ($admin->hasAdminSetting(16) && $_SESSION['id'] == $admin->idUsers) {
                        $lnkSignInAsUser = "<a href=\"javascript:void(0)\" class=\"signinasuser\" id=\"$currAdminId\" title=\"Sign in as sales owner\"><i class='icon-signin'></i></a>";
                    }


                    $col1Html = "<p>$currAdminId</p>";
                    if (($admin->hasAdminSetting(13) || $admin->hasAdminSetting(16)) && ($this->User->idUsers == $admin->idUsers || $this->User->hasAdminSetting(8))) {
                        $col1Html = "
                            <div class=\"row\">
                                <div class=\"two mobile sixths right-one\">
                                    <a href='javascript:void(0)' class='signinasuser' id='$currAdminId' title='Sign in as sales owner'><i class='icon-signin'></i></a>
                                </div>
                                <div class=\"two mobile sixths\">
                                    <p>$currAdminId</p>
                                </div>
                            </div>
                        ";
                    }

                    $htmlAdminUsers .= "
                    <div class='row user pad-top desktop-only'>
                        <div class=\"one mobile fourth\">$col1Html</div>
                        <div class=\"one mobile fourth\"><p>$currAdminEmail</p></div>
                        <div class=\"one mobile fourth\"><p>$currDateCreated</p></div>
                        <div class=\"one mobile fourth\"><p style=\"text-align: center;\">
                            <a href=\"javascript:void(0)\" id=\"$currDeleteAdminId\" class=\"button delete\">Delete</a>
                            <a href=\"edit.php?id=$admin->idUsers&type=$admin->typeId\" class=\"button\">Edit</a> ";
                    if ($this->IsMasterAdmin) {
                        $htmlAdminUsers .= "<a href=\"javascript:void(0)\" id=\"$currAdminId\" class=\"sign-out button\" $title $disabled><i class=\"icon icon-off\"></i></a>";
                    }
                    $htmlAdminUsers .= "</p></div>
                    </div>
                    <div class='row user pad-top hide-on-desktop'>
                        <div class=\"one mobile third id\">$col1Html</div>
                        <div class=\"one mobile third name\"><p>$currAdminEmail</p></div>
                        <div class=\"one mobile third action\"><p style=\"text-align: center;\">
                            <a href=\"javascript:void(0)\" id=\"$currDeleteAdminId\" class=\"button delete\">Delete</a>
                            <a href=\"edit.php?id=$admin->idUsers&type=$admin->typeId\" class=\"button\">Edit</a> ";
                    if ($this->IsMasterAdmin) {
                        $htmlAdminUsers .= "<a href=\"javascript:void(0)\" id=\"$currAdminId\" class=\"sign-out button\" $title $disabled><i class=\"icon icon-off\"></i></a>";
                    }
                    $htmlAdminUsers .= "</p></div>
                    </div>
                    ";
                }
            }

            if ($this->UserPageInfo['OrderEntryAdmins']['TotalUsers'] > $this->UserPageInfo['OrderEntryAdmins']['MaxRows']) {
                $htmlAdminUsers .= $this->getPaginationHtml($this->UserPageInfo['OrderEntryAdmins']['CurrentPage'], $this->UserPageInfo['OrderEntryAdmins']['TotalPages'], 7, $this->Search, $this->SortBy, $this->Direction);
            }
        } else {
            $htmlAdminUsers .= "
            <div class=\"row user pad-top\">
                <div class=\"one mobile whole\"><p>This user has no administrators</p></div>
            </div>
            ";
        }

        return $htmlAdminUsers;
    }

    private function printPatientAdmins() {
        $htmlPatientUsers = "";
        $direction = $this->AdminClient->Direction;
        $message = $this->AdminClient->Message;
        if (count($this->AdminClient->Users['PatientAdmins']) > 0) {

            $searchUrlParam = "";
            $patientSearch = "";
            if (array_key_exists("search", $_GET) && array_key_exists("type", $_GET) && $_GET['type'] == 8) {
                $searchUrlParam = "&search=" . $_GET['search'];
                $patientSearch = $_GET['search'];
            }

            $htmlPatientUsers = "<div class='one mobile whole pad-top pad-bottom centered rounded box_shadow'>
                <div class='row' id='patientsHeader' title='patientsHeader'>
                    <div class='three mobile twelfths'>
                        <h5>Patient Administrators</h5>
                    </div>
                    <div class='eight mobile twelfths'>
                        <div class='row'>
                            <div class='one mobile third'>
                                <label for='patientSearch'>Filter Patient Administrators:</label>
                            </div>
                            <div class='two mobile thirds'>
                                <input type=\"text\" name=\"patientAdminSearch\" id=\"patientAdminSearch\" class=\"userSearch\" value=\"$patientSearch\" placeholder=\"Begin typing an email to search\" autocomplete=\"nope\" />
                            </div>
                        </div>
                    </div>
                    <div class='one mobile twelfth pad-right'>
                        <a class='toggle' href='javascript:void(0)' title='hide field group'>-</a>
                    </div>
                </div>
                <div class='user-container'>
                    <div class='row columnHeader' style='padding-left: 20px'>
                        <div class='seven mobile tenths email'><p style='text-align: left;'>Email <a id='patientSortEmail' href='index.php?type=4&sortby=email&direction=$direction" . $searchUrlParam . "#patientsHeader'><i class='icon-sort'></i></a></p></div>
                        <div class='three mobile tenths action'><p>Action</p></div>
                    </div>
                    <div style='margin: 0 20px; border-bottom: 1px solid #CCCCCC;'></div>
               ";

            //foreach ($this->AdminClient->Users['Patients'] as $patient) {
            for ($i = ($this->UserPageInfo['PatientAdmins']['CurrentPage'] * $this->UserPageInfo['PatientAdmins']['MaxRows']) - $this->UserPageInfo['PatientAdmins']['MaxRows'];
                 $i < $this->UserPageInfo['PatientAdmins']['CurrentPage'] * $this->UserPageInfo['PatientAdmins']['MaxRows'] && $i < $this->UserPageInfo['PatientAdmins']['TotalUsers'];
                 $i++) {
                $patientAdmin = $this->AdminClient->Users['PatientAdmins'][$i];

                $disabled = "";
                $idLoggedIn = $patientAdmin->idLoggedIn;
                $title = "title=\"Log-out this user\"";
                if (empty($idLoggedIn)) {
                    $disabled = "disabled=\"disabled\"";
                    $title = "title=\"This user is not currently logged in\"";
                }

                $signOutLink = "";
                if ($this->IsMasterAdmin) {
                    $signOutLink = "<a href=\"javascript:void(0)\" id=\"$patientAdmin->idUsers\" class=\"sign-out button\" $title $disabled><i class=\"icon icon-off\"></i></a>";
                }

                $htmlPatientUsers .= "
                <div class=\"row patientUsers pad-top\" id=\"$patientAdmin->idUsers\" style='padding-left: 20px'>
                    <div class=\"seven mobile tenths email\">$patientAdmin->email</div>
                    <div class=\"three mobile tenths action\"><p style=\"text-align: center;\">
                        <a href=\"javascript:void(0)\" id=\"$patientAdmin->idUsers:$patientAdmin->typeId\" class=\"button delete\">Delete</a>
                        <a href=\"edit.php?id=$patientAdmin->idUsers&type=$patientAdmin->typeId\" class=\"button\">Edit</a> 
                        <a href=\"javascript:void(0)\" data-id=\"$patientAdmin->idUsers\" class=\"button resendEmail\" style='margin-right: 4px'>Email</a>
                        $signOutLink
                    </p></div>
                </div>";
            }

            $search = "";
            if (array_key_exists("type", $_GET) && $_GET['type'] == 4 && isset($this->Search) && !empty($this->Search)) {
                $search = $this->Search;
            }
            if ($this->UserPageInfo['PatientAdmins']['TotalUsers'] > $this->UserPageInfo['PatientAdmins']['MaxRows']) {
                $htmlPatientUsers .= $this->getPaginationHtml($this->UserPageInfo['PatientAdmins']['CurrentPage'], $this->UserPageInfo['PatientAdmins']['TotalPages'], 8, $search, $this->SortBy, $this->Direction, "patientAdminsHeader");
            }
            $htmlPatientUsers .= "</div>";
        }

        return $htmlPatientUsers;
    }

    private function printPatientUsers() {
        $htmlPatientUsers = "";
        $direction = $this->AdminClient->Direction;
        $message = $this->AdminClient->Message;
        if (count($this->AdminClient->Users['Patients']) > 0) {

            $searchUrlParam = "";
            $patientSearch = "";
            if (array_key_exists("search", $_GET) && array_key_exists("type", $_GET) && $_GET['type'] == 4) {
                $searchUrlParam = "&search=" . $_GET['search'];
                $patientSearch = $_GET['search'];
            }

            $htmlPatientUsers = "<div class='one mobile whole pad-top pad-bottom centered rounded box_shadow'>
                <div class='row' id='patientsHeader' title='patientsHeader'>
                    <div class='three mobile twelfths'>
                        <h5>Patients</h5>
                    </div>
                    <div class='eight mobile twelfths'>
                        <div class='row'>
                            <div class='one mobile third'>
                                <label for='patientSearch'>Filter Patients:</label>
                            </div>
                            <div class='two mobile thirds'>
                                <input type=\"text\" name=\"patientSearch\" id=\"patientSearch\" class=\"userSearch\" value=\"$patientSearch\" placeholder=\"Begin typing a patient name or email to search\" autocomplete=\"nope\" />
                            </div>
                        </div>
                    </div>
                    <div class='one mobile twelfth pad-right'>
                        <a class='toggle' href='javascript:void(0)' title='hide field group'>-</a>
                    </div>
                </div>
                <div class='user-container'>
                    <div class='row columnHeader'>
                        <div class='one mobile tenth'><p>Patient # <a id='patientSortNumber' href='index.php?type=4&sortby=arNo&direction=$direction" . $searchUrlParam . "#patientsHeader'><i class='icon-sort'></i></a></p></div>
                        <div class='two mobile tenths email hide-on-mobile'><p style='text-align: left;'>Email <a id='patientSortEmail' href='index.php?type=4&sortby=email&direction=$direction" . $searchUrlParam . "#patientsHeader'><i class='icon-sort'></i></a></p></div>
                        <div class='two mobile tenths name'><p style='text-align: left;'>Name <a id='patientSortName' href='index.php?type=4&sortby=name&direction=$direction" . $searchUrlParam . "#patientsHeader'><i class='icon-sort'></i></a></p></div>
                        <div class='two mobile tenths desktop-only'><p style='text-align: left'>Date of Birth <a id='patientSortDob' href='index.php?type=4&sortby=dob&direction=$direction" . $searchUrlParam . "#patientsHeader'><i class='icon-sort'></i></a></p></div>
                        <div class='three mobile tenths action'><p>Action</p></div>
                    </div>
                    <div style='margin: 0 20px; border-bottom: 1px solid #CCCCCC;'></div>
               ";

            //foreach ($this->AdminClient->Users['Patients'] as $patient) {
            for ($i = ($this->UserPageInfo['Patients']['CurrentPage'] * $this->UserPageInfo['Patients']['MaxRows']) - $this->UserPageInfo['Patients']['MaxRows'];
                 $i < $this->UserPageInfo['Patients']['CurrentPage'] * $this->UserPageInfo['Patients']['MaxRows'] && $i < $this->UserPageInfo['Patients']['TotalUsers'];
                 $i++) {
                $patient = $this->AdminClient->Users['Patients'][$i];

                $disabled = "";
                $idLoggedIn = $patient->idLoggedIn;
                $title = "title=\"Log-out this user\"";
                if (empty($idLoggedIn)) {
                    $disabled = "disabled=\"disabled\"";
                    $title = "title=\"This user is not currently logged in\"";
                }

                $signOutLink = "";
                if ($this->IsMasterAdmin) {
                    $signOutLink = "<a href=\"javascript:void(0)\" id=\"$patient->idUsers\" class=\"sign-out button\" $title $disabled><i class=\"icon icon-off\"></i></a>";
                }

                $htmlPatientUsers .= "
                <div class=\"row patientUsers pad-top\" id=\"$patient->idUsers\">
                    <div class=\"one mobile tenth\" style=\"text-align: center\">$patient->arNo</div>
                    <div class=\"two mobile tenths email hide-on-mobile patientEmail\">$patient->email</div>
                    <div class=\"two mobile tenths name patientName\">$patient->firstName $patient->lastName</div>
                    <div class=\"two mobile tenths desktop-only\">$patient->dob</div>
                    <div class=\"three mobile tenths action\"><p style=\"text-align: center;\">
                        <a href=\"javascript:void(0)\" id=\"$patient->idUsers:$patient->typeId\" class=\"button delete\">Delete</a>
                        <a href=\"edit.php?id=$patient->idUsers&type=$patient->typeId\" class=\"button\">Edit</a> 
                        <a href=\"javascript:void(0)\" data-id=\"$patient->idUsers\" class=\"button resendEmail\" style='margin-right: 4px'>Email</a>
                        $signOutLink
                    </p></div>
                </div>";
            }

            $search = "";
            if (array_key_exists("type", $_GET) && $_GET['type'] == 4 && isset($this->Search) && !empty($this->Search)) {
                $search = $this->Search;
            }
            if ($this->UserPageInfo['Patients']['TotalUsers'] > $this->UserPageInfo['Patients']['MaxRows']) {
                $htmlPatientUsers .= $this->getPaginationHtml($this->UserPageInfo['Patients']['CurrentPage'], $this->UserPageInfo['Patients']['TotalPages'], 4, $search, $this->SortBy, $this->Direction, "patientsHeader");
            }
            $htmlPatientUsers .= "</div>";
        }

        return $htmlPatientUsers;
    }

    private function printClientUsers() {
        $htmlClientUsers = "";
        if (count($this->AdminClient->Users['Clients']) > 0) {
            $isOrderEntryAdmin = false;
            $colWidth = "fifth";
            if ($this->User->typeId == 7) {
                $isOrderEntryAdmin = true;
                $colWidth = "third";
            }
            $aryClientIds = array();
            //foreach ($this->AdminClient->Users['Clients'] as $client) {
            for ($i = ($this->UserPageInfo['Clients']['CurrentPage'] * $this->UserPageInfo['Clients']['MaxRows']) - $this->UserPageInfo['Clients']['MaxRows'];
                 $i < $this->UserPageInfo['Clients']['CurrentPage'] * $this->UserPageInfo['Clients']['MaxRows'] && $i < $this->UserPageInfo['Clients']['TotalUsers'];
                 $i++) {
                $client = $this->AdminClient->Users['Clients'][$i];

                $disabled = "";
                $idLoggedIn = $client->idLoggedIn;
                $title = "title=\"Log-out this user\"";
                if (empty($idLoggedIn)) {
                    $disabled = "disabled=\"disabled\"";
                    $title = "title=\"This user is not currently logged in\"";
                }

                $emailCol = "";
                if ($isOrderEntryAdmin == false) {
                    $emailCol = "<div class='one mobile $colWidth email hide-on-mobile'><p>$client->email</p></div>";
                }

                if ($isOrderEntryAdmin == false || (in_array($client->idClients, $this->User->adminClientIds) && !in_array($client->idClients, $aryClientIds))) {
                    $htmlClientUsers .= "
                    <div class='row clientUsers pad-top' id=\"$client->idUsers\">
                        <div class='one mobile $colWidth'>
                            <div class='row'>
                                <div class='two mobile fifths skip-one'>
                                    <a href='javascript:void(0)' class='signinasuser' id='$client->idUsers' title='Sign in as client'><i class='icon-signin'></i></a>
                                </div>
                                <div class='two mobile fifths'>
                                    $client->clientNo
                                </div>
                            </div>
                        </div>
                        $emailCol
                        <div class='one mobile $colWidth name'><p>$client->clientName</p></div>
                        <div class='one mobile $colWidth desktop-only'>$client->clientStreet <br /> $client->clientCity, $client->clientState, $client->clientZip</div>";
                    if ($isOrderEntryAdmin == false) {
                        $htmlClientUsers .= "<div class='one mobile $colWidth action'><p style='text-align: center;'>
                            <a href='javascript:void(0)' id=\"$client->idUsers:$client->typeId\" class='button delete'>Delete</a>
                            <a href=\"edit.php?id=$client->idUsers&type=$client->typeId\" class='button'>Edit</a>";
                        if ($this->IsMasterAdmin) {
                            $htmlClientUsers .= "<a href=\"javascript:void(0)\" id=\"$client->idUsers\" class=\"sign-out button\" $title $disabled style='margin-left: 4px;'><i class=\"icon icon-off\"></i></a>";
                        }
                        $htmlClientUsers .= "</p></div>";
                    }

                    $htmlClientUsers .= "</div>";
                }

                $aryClientIds[] = $client->idClients;
            }

            $search = "";
            if (array_key_exists("type", $_GET) && $_GET['type'] == 2 && isset($this->Search) && !empty($this->Search)) {
                $search = $this->Search;
            }
            if ($this->UserPageInfo['Clients']['TotalUsers'] > $this->UserPageInfo['Clients']['MaxRows']) {
                $htmlClientUsers .= self::getPaginationHtml($this->UserPageInfo['Clients']['CurrentPage'], $this->UserPageInfo['Clients']['TotalPages'], 2, $search, $this->SortBy, $this->Direction, "clientsHeader");
            }


        } else {
            $htmlClientUsers .= "
            <div class='row user pad-top'>
                <div class='one mobile whole'><p>This user has no clients.</p></div>
            </div>";
        }

        return $htmlClientUsers;
    }

    private function printDoctorUsers() {
        $htmlDoctorUsers = "";
        if (count($this->AdminClient->Users['Doctors']) > 0) {
            //foreach ($this->AdminClient->Users['Doctors'] as $doctor) {
            for ($i = ($this->UserPageInfo['Doctors']['CurrentPage'] * $this->UserPageInfo['Doctors']['MaxRows']) - $this->UserPageInfo['Doctors']['MaxRows'];
                 $i < $this->UserPageInfo['Doctors']['CurrentPage'] * $this->UserPageInfo['Doctors']['MaxRows'] && $i < $this->UserPageInfo['Doctors']['TotalUsers'];
                 $i++) {
                $doctor = $this->AdminClient->Users['Doctors'][$i];

                $disabled = "";
                $idLoggedIn = $doctor->idLoggedIn;
                $title = "title=\"Log-out this user\"";
                if (empty($idLoggedIn)) {
                    $disabled = "disabled=\"disabled\"";
                    $title = "title=\"This user is not currently logged in\"";
                }

                $htmlDoctorUsers .= "
                <div class=\"row doctorUsers pad-top\" id=\"$doctor->idUsers\">
                    <div class=\"one mobile fifth\">
                        <div class=\"row\">
                            <div class=\"two mobile fifths skip-one\">
                                <a href=\"javascript:void(0)\" class=\"signinasuser\" id=\"$doctor->idUsers\" title=\"Sign in as doctor\"><i class=\"icon-signin\"></i></a>
                            </div>
                            <div class=\"two mobile fifths\">
                                $doctor->number
                            </div>
                        </div>
                    </div>
                    <div class=\"one mobile fifth email hide-on-mobile\"><p>$doctor->email</p></div>
                    <div class=\"one mobile fifth name\"><p>$doctor->firstName $doctor->lastName</p></div>
                    <div class=\"one mobile fifth desktop-only\">$doctor->address1 <br /> $doctor->city, $doctor->state, $doctor->zip</div>
                    <div class=\"one mobile fifth action\"><p style=\"text-align: center;\">
                        <a href=\"javascript:void(0)\" id=\"$doctor->idUsers:$doctor->typeId\" class=\"button delete\">Delete</a>
                        <a href=\"edit.php?id=$doctor->idUsers&type=$doctor->typeId\" class=\"button\">Edit</a> ";
                        if ($this->IsMasterAdmin) {
                            $htmlDoctorUsers .= "<a href=\"javascript:void(0)\" id=\"$doctor->idUsers\" class=\"sign-out button\" $title $disabled><i class=\"icon icon-off\"></i></a>";
                        }
                $htmlDoctorUsers .= "</p></div>
                </div>
                ";
            }

            $search = "";
            if (array_key_exists("type", $_GET) && $_GET['type'] == 3 && isset($this->Search) && !empty($this->Search)) {
                $search = $this->Search;
            }
            if ($this->UserPageInfo['Doctors']['TotalUsers'] > $this->UserPageInfo['Doctors']['MaxRows']) {
                $htmlDoctorUsers .= $this->getPaginationHtml($this->UserPageInfo['Doctors']['CurrentPage'], $this->UserPageInfo['Doctors']['TotalPages'], 3, $search, $this->SortBy, $this->Direction, "doctorsHeader");
            }
        } else {
            $htmlDoctorUsers .= "
            <div class=\"row user pad-top\">
                <div class=\"one mobile whole\"><p>This user has no doctors.</p></div>
            </div>";
        }

        return $htmlDoctorUsers;
    }

    private function printSalesmenUsers() {
        $htmlSalesUsers = "";

        if (count($this->AdminClient->Users['Salesmen']) > 0) {
            //foreach ($this->AdminClient->Users['Salesmen'] as $sales) {
            for ($i = ($this->UserPageInfo['Salesmen']['CurrentPage'] * $this->UserPageInfo['Salesmen']['MaxRows']) - $this->UserPageInfo['Salesmen']['MaxRows'];
                 $i < $this->UserPageInfo['Salesmen']['CurrentPage'] * $this->UserPageInfo['Salesmen']['MaxRows'] && $i < $this->UserPageInfo['Salesmen']['TotalUsers'];
                 $i++) {
                $sales = $this->AdminClient->Users['Salesmen'][$i];

                $disabled = "";
                $idLoggedIn = $sales->idLoggedIn;
                $title = "title=\"Log-out this user\"";
                if (empty($idLoggedIn)) {
                    $disabled = "disabled=\"disabled\"";
                    $title = "title=\"This user is not currently logged in\"";
                }

                $signInAsUser = "";
                if (!self::SalesPortalDisabled) {
                    $signInAsUser = "<a href='javascript:void(0)' class='signinasuser' id='$sales->idUsers' title='Sign in as salesman'><i class='icon-signin'></i></a>";
                }

                $htmlSalesUsers .= "
                <div class=\"row salesUsers pad-top\" id=\"$sales->idUsers\">
                    <div class=\"three mobile elevenths\">
                        <div class=\"row\">
                            <div class=\"two mobile elevenths skip-one\">
                                 $signInAsUser
                            </div>
                            <div class=\"eight mobile elevenths\">
                               <p>$sales->email</p>
                            </div>
                        </div>
                    </div>
                    <div class=\"two mobile elevenths\">$sales->firstName $sales->lastName</div>
                    <div class=\"two mobile elevenths\">$sales->territoryName</div>
                    <div class=\"two mobile elevenths\">$sales->groupName</div>
                    <div class=\"two mobile elevenths\"><p style=\"text-align: center;\">
                        <a href=\"javascript:void(0)\" id=\"$sales->idUsers:$sales->typeId\" class=\"button delete\">Delete</a>
                        <a href=\"edit.php?id=$sales->idUsers&type=$sales->typeId\" class=\"button\">Edit</a> ";
                if ($this->IsMasterAdmin) {
                    $htmlSalesUsers .= "<a href=\"javascript:void(0)\" id=\"$sales->idUsers\" class=\"sign-out button\" $title $disabled><i class=\"icon icon-off\"></i></a>";
                }
                $htmlSalesUsers .= "</p></div>
                </div>
                ";
            }

            $search = "";
            if (array_key_exists("type", $_GET) && $_GET['type'] == 5 && isset($this->Search) && !empty($this->Search)) {
                $search = $this->Search;
            }
            if ($this->UserPageInfo['Salesmen']['TotalUsers'] > $this->UserPageInfo['Salesmen']['MaxRows']) {
                $htmlSalesUsers .= $this->getPaginationHtml($this->UserPageInfo['Salesmen']['CurrentPage'], $this->UserPageInfo['Salesmen']['TotalPages'], 5, $search, $this->SortBy, $this->Direction, "salesmenHeader");
            }
        } else {
            $htmlSalesUsers .= "
            <div class=\"row user pad-top\">
                <div class=\"one mobile whole\"><p>This user has no salesmen.</p></div>
            </div>";
        }

        return $htmlSalesUsers;
    }

    private function printInsuranceUsers() {
        $htmlInsuranceUsers = "";

        if (count($this->AdminClient->Users['Insurances']) > 0) {
            //foreach ($this->AdminClient->Users['Insurances'] as $insurance) {
            for ($i = ($this->UserPageInfo['Insurances']['CurrentPage'] * $this->UserPageInfo['Insurances']['MaxRows']) - $this->UserPageInfo['Insurances']['MaxRows'];
                 $i < $this->UserPageInfo['Insurances']['CurrentPage'] * $this->UserPageInfo['Insurances']['MaxRows'] && $i < $this->UserPageInfo['Insurances']['TotalUsers'];
                 $i++) {
                $insurance = $this->AdminClient->Users['Insurances'][$i];

                $disabled = "";
                $idLoggedIn = $insurance->idLoggedIn;
                $title = "title=\"Log-out this user\"";
                if (empty($idLoggedIn)) {
                    $disabled = "disabled=\"disabled\"";
                    $title = "title=\"This user is not currently logged in\"";
                }

                $address = $insurance->address;
                $city = $insurance->city;
                $state = $insurance->state;
                $zip = $insurance->zip;

                $location = "";
                if (!empty($address) && !empty($city) && !empty($state) && !empty($zip)) {
                    $location = $address . ", " . $city . ", " . $state . " " . $zip;
                } else if (!empty($address) && !empty($city) && !empty($state)) {
                    $location = $address . ", " . $city . ", " . $state;
                } else if (!empty($address) && !empty($city) && !empty($zip)) {
                    $location = $address . ", " . $city . " " . $zip;
                } else if (!empty($address) && !empty($state) && !empty($zip)) {
                    $location = $address . ", " . $state . " " . $zip;
                } else if (!empty($address) && !empty($city)) {
                    $location = $address . ", " . $city;
                } else if (!empty($address) && !empty($state)) {
                    $location = $address . ", " . $state;
                } else if (!empty($address) && !empty($zip)) {
                    $location = $address . " " . $zip;
                } else if (!empty($city) && !empty($state) && !empty($zip)) {
                    $location = $city . ", " . $state . " " . $zip;
                } else if (!empty($city) && !empty($state)) {
                    $location = $city . ", " . $state;
                } else if (!empty($city) && !empty($zip)) {
                    $location = $city .  " " .$zip;
                } else if (!empty($state) && !empty($zip)) {
                    $location = $state . " " . $zip;
                } else if (!empty($address)) {
                    $location = $address;
                } else if (!empty($city)) {
                    $location = $city;
                } else if (!empty($state)) {
                    $location = $state;
                } else if (!empty($zip)) {
                    $location = $zip;
                } else {
                    $location = "";
                }

                $htmlInsuranceUsers .= "
                <div class=\"row insuranceUsers pad-top\" id=\"$insurance->idUsers\">
                    <div class=\"three mobile elevenths\">
                        <div class=\"row\">
                            <div class=\"two mobile elevenths skip-one\">
                                 <a href=\"javascript:void(0)\" class=\"signinasuser\" id=\"$insurance->idUsers\" title=\"Sign in as insurance\"><i class=\"icon-signin\"></i></a>
                            </div>
                            <div class=\"eight mobile elevenths\">
                               <p>$insurance->email</p>
                            </div>
                        </div>
                    </div>
                    <div class=\"three mobile elevenths\"><p>$insurance->name</p></div>
                    <div class=\"three mobile elevenths\">$location</div>

                    <div class=\"two mobile elevenths\"><p style=\"text-align: center;\">
                        <a href=\"javascript:void(0)\" id=\"$insurance->idUsers:$insurance->typeId\" class=\"button delete\">Delete</a>
                        <a href=\"edit.php?id=$insurance->idUsers&type=$insurance->typeId\" class=\"button\">Edit</a> ";
                if ($this->IsMasterAdmin) {
                    $htmlInsuranceUsers .= "<a href=\"javascript:void(0)\" id=\"$insurance->idUsers\" class=\"sign-out button\" $title $disabled><i class=\"icon icon-off\"></i></a>";
                }
                $htmlInsuranceUsers .= "</p></div>
                </div>
                ";
            }

            $search = "";
            if (array_key_exists("type", $_GET) && $_GET['type'] == 6 && isset($this->Search) && !empty($this->Search)) {
                $search = $this->Search;
            }
            if ($this->UserPageInfo['Insurances']['TotalUsers'] > $this->UserPageInfo['Insurances']['MaxRows']) {
                $htmlInsuranceUsers .= $this->getPaginationHtml($this->UserPageInfo['Insurances']['CurrentPage'], $this->UserPageInfo['Insurances']['TotalPages'], 6, $search, $this->SortBy, $this->Direction, "insurancesHeader");
            }
        } else {
            $htmlInsuranceUsers .= "
            <div class=\"row user pad-top\">
                <div class=\"one mobile whole\"><p>This user has no insurances.</p></div>
            </div>";
        }

        return $htmlInsuranceUsers;
    }

    public static function getPaginationHtml($currPage, $totalPages, $userType, $searchValue, $sortBy, $direction, $title = null) {

        $searchUrlParam = "";
        if (isset($searchValue) && !empty($searchValue)) {
            $searchUrlParam = "&search=" . $searchValue;
        }

        $sortByUrlParam = "";
        $directionUrlParam = "";
        if (isset($sortBy) && !empty($sortBy)) {
            $sortByUrlParam = "&sortby=" . $sortBy;
            if (isset($direction) && !empty($direction)) {
                $directionUrlParam = "&direction=" . $direction;
            }
        }

        $titleUrlParam = "";
        if ($title != null) {
            $titleUrlParam = "#" . $title;
        }

        $prevLink = "";
        $nextLink = "";
        $firstLink = "";
        $lastLink = "";
        if ($currPage > 1) {
            $prevLink = "<li class='page-item'><a class='page-link' href='/outreach/admin/index.php?page=" . ($currPage - 1) . "&type=$userType" . $searchUrlParam . $sortByUrlParam . $directionUrlParam . $titleUrlParam . "'>Previous</a></li>";
        }
        if ($currPage < $totalPages) {
            $nextLink = "<li class='page-item'><a class='page-link' href='/outreach/admin/index.php?page=" . ($currPage + 1) . "&type=$userType" . $searchUrlParam . $sortByUrlParam . $directionUrlParam . $titleUrlParam . "'>Next</a></li>";
        }
        if ($currPage > 5) {
            $firstLink = "<li class='page-item'><a class='page-link' href='/outreach/admin/index.php?page=1&type=$userType" . $searchUrlParam . $sortByUrlParam . $directionUrlParam . $titleUrlParam . "'>First</a></li>";
        }
        if ($totalPages - $currPage > 5) {
            $lastLink = "<li class='page-item'><a class='page-link' href='/outreach/admin/index.php?page=$totalPages&type=$userType" . $searchUrlParam . $sortByUrlParam . $directionUrlParam . $titleUrlParam . "'>Last</a></li>";
        }


        $iStart = 1;
        $iEnd = $totalPages;
        if ($currPage > 4) {
            $iStart = $currPage - 4;
        }
        if ($totalPages - $currPage > 4) {
            $iEnd = $currPage + 4;
        }



        $paginationHtml = "<div class='row paginationRow'><div class='twelve mobile twelfths'>
                <nav><ul class='pagination justify-content-center'>" . $firstLink . $prevLink;
        for ($i = $iStart; $i <= $iEnd; $i++) {
            $active = "";
            if ($i == $currPage) {
                $active = "active";
            }
            $paginationHtml .= "<li class='page-item $active'><a class='page-link' href='/outreach/admin/index.php?page=$i&type=$userType" . $searchUrlParam . $sortByUrlParam . $directionUrlParam . $titleUrlParam . "'>$i</a></li>";

        }
        $paginationHtml .= $nextLink . $lastLink . "</ul></nav></div></div>";

        return $paginationHtml;
    }
}
?>
