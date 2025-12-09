<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'PageClient.php';
require_once 'DAOS/UserDAO.php';
require_once 'DAOS/ClientDAO.php';
require_once 'DAOS/DoctorDAO.php';
require_once 'Utility/IClient.php';
//require_once 'DAOS/EmailNotificationDAO.php';
require_once 'DAOS/OrderSearchDAO.php';

class SearchClient extends PageClient implements IClient  {

    const PRINT_PENDING_CHECKBOX = true;
    const PRINT_OLD_SITE_LINK = false;
    const PRINT_UPS_SITE_LINK = false;
    const PRINT_TRANSLATIONAL_CHECKBOX = false;

    private $LastLogin;
    private $ErrorMessages = array();
    private $IsValidForm = true;
    private $OldSiteLink = "";
    private $UpsSiteLink = "";
    private $PendingCheckbox = "";
    private $Title = "";

    //private $UnprintedOrderIds = "";
    private $HasUnprintedOrders = false;

    private $TotalOrders = 0;
    private $MaxOrders = 25;
    
    public function __construct() {
        parent::__construct(array("IncludeDetailedInfo" => true));
        $this->addStylesheet("/outreach/css/search.css");
        $this->addScript("/outreach/js/velocity.min.js");
        $this->addScript("/outreach/js/tooltip.js");
        $this->addScript("/outreach/js/search.js");


        $this->Title = "Welcome to " . self::LabName . " Physician Outreach Portal";
        if (self::PRINT_OLD_SITE_LINK) {
            /*$this->OldSiteLink = "
                <a href=\"https://support.csslis.com/acs\" target=\"_blank\" id=\"oldSite\" class=\"button green\" style=\"font-size: 12px; margin-right: 10px; float: right;\">HERE</a>
                <p style=\"margin-right: 3px; font-size: 12px; float: right;\">To review results with dates of service prior to 9/15/2014, please login</p>";*/
        }
        if (self::PRINT_UPS_SITE_LINK) {
            /*$this->UpsSiteLink = "
                <a href=\"https://row.ups.com/Default.aspx?Company=PML&LoginId=PML&Password=pml\" target=\"_blank\" id=\"upsLogin\" class=\"button green\" style=\"font-size: 12px; margin-right: 10px; float: right;\">
                    HERE</a>
                <p style=\"margin-right: 3px; font-size: 12px; float: right;\">To print shipping labels, please login</p>";*/
        }
        if (self::PRINT_PENDING_CHECKBOX) {
            $this->PendingCheckbox = "
                <div class=\"row\" title=\"Include orders in search results that were submitted on the Web, but have not yet been processed by the lab.\">
                    <div class=\"three fourths\">
                        <label for=\"includePending\" class=\"inline bold\">Include orders not yet received by lab: </label>
                    </div>
                    <div class=\"one fourth\">
                        <input type=\"checkbox\" name=\"includePending\" id=\"includePending\" value=\"1\" />
                    </div>
                </div>";
        }

        $this->resetSearchSession();
        $this->setLastLogin(); // must be called after getUser function
        $this->setErrorMessages();

/*        $edao = new EmailNotificationDAO(array("Conn" => $this->Conn));
        $newOrders = $edao->getNewOrders();*/


        if (self::HasNewResultsButton) {
            $this->getUnprintedOrders();
        }
    }

    private function getUnprintedOrders() {

        $dosFrom = new DateTime();
        $dosFrom->modify('-7 day');
        $dosTo = new DateTime();

        $arySearchFields = array(
            "unprintedReports" => 1,
            "dosFrom" => $dosFrom->format('m/d/Y'),
            "dosTo" => $dosTo->format('m/d/Y')
        );
        $osDAO = new OrderSearchDAO($arySearchFields, $this->User, $this->Conn);

        $aryPageData = array(
            'MaxRows' => 9999,
            'Offset' => 0,
            'OrderBy' => 'orderDate',
            'Direction' => 'desc',
            'CurrentPage' => 1,
            'TotalOrders' => 0,
            'TotalPages' => 1,
            'Ip' => $this->Ip,
            'SkipAbnormalsCheck' => true
        );
        $orders = $osDAO->getResultSearch($aryPageData);

        $aryOrders = array();
        $currIdOrders = "";
        $currReportType = "";

        unset($_SESSION['ORDERS']);
        if (is_array($orders)) {
            $this->TotalOrders = count($orders);
        }
        $totalOrders = $this->TotalOrders;
        if ($this->TotalOrders > 0) {
            if ($this->TotalOrders > $this->MaxOrders) {
                $totalOrders = $this->MaxOrders;
            }

            $this->HasUnprintedOrders = true;
            //foreach ($orders as $order) {
            for ($i = 0; $i < $totalOrders; $i++) {
                $order = $orders[$i];

                $currIdOrders = $order->idOrders;
                $currReportType = $order->reportType;

                if (array_key_exists($currReportType, $aryOrders)) {
                    $aryOrders[$currReportType][] = $currIdOrders;
                } else {
                    $aryOrders[$currReportType] = array($currIdOrders);
                }
            }

            $_SESSION['ORDERS'] = $aryOrders;
        }
    }

    public function printPage() {
        $errorMessagesHtml = $this->printErrorMessages();
        $lastLogin = $this->LastLogin;
        $dobErrorHtml = "";
        $doctorFieldsetHtml = "";
        $dosErrorHtml = "";
        $dateReportedErrorHtml = "";
        $specimenDateErrorHtml = "";
        $createdDateErrorHtml = "";
        $translationalCheckboxHtml = "";

        if (isset($this->ErrorMessages['patientDOB'])) {
            $dobErrorMessage = $this->ErrorMessages['patientDOB'];
            $dobErrorHtml = "<div class=\"tooltip\" id=\"patientDOB\" style=\"top: -7px; display: block;\">$dobErrorMessage</div>";
        }
        if (isset($this->User) && $this->User instanceof User && $this->User->typeId != 3) {
            $doctorFieldsetHtml .= "
                <fieldset class=\"rounded pad-bottom box_shadow gap-top\">
                    <h5>Doctor Fields</h5>
                    <a class=\"toggle\" href=\"javascript:void(0)\" title=\"hide field group\">-</a>
                    <div class=\"fieldGroup\">
                        <div class=\"row\">
                            <div class=\"one half pad-left pad-right\">
                                <label for=\"doctorFirstName\" class=\"bold\">First Name: </label>
                                <input type=\"text\" name=\"doctorFirstName\" id=\"doctorFirstName\" />
                            </div>
                            <div class=\"one half pad-left pad-right\">
                                <label for=\"doctorLastName\" class=\"bold\">Last Name: </label>
                                <input type=\"text\" name=\"doctorLastName\" id=\"doctorLastName\" />
                            </div>
                        </div>
                    </div>
                </fieldset>";
        }
        if (isset($this->ErrorMessages['dosFrom'])) {
            $dosErrorMessage = $this->ErrorMessages['dosFrom'];
            $dosErrorHtml = "<div class=\"tooltip\" id=\"dosFrom\" style=\"top: -40px; display: block;\">$dosErrorMessage</div>";
        }
        if (isset($this->ErrorMessages['reportedFrom'])) {
            $dateReportedErrorMessage = $this->ErrorMessages['reportedFrom'];
            $dateReportedErrorHtml = "<div class='tooltip' id='reportedFrom' style='top: -40px; display: block;'>$dateReportedErrorMessage</div>";
        }
        if (isset($this->ErrorMessages['specimenFrom'])) {
            $specimenDateErrorMessage = $this->ErrorMessages['specimenFrom'];
            $specimenDateErrorHtml = "<div class='tooltip' id='specimenFrom' style='top: -40px; display: block;'>$specimenDateErrorMessage</div>";
        }
        if (isset($this->ErrorMessages['createdFrom'])) {
            $createdDateErrorMessage = $this->ErrorMessages['createdFrom'];
            $createdDateErrorHtml = "<div class='tooltip' id='createdFrom' style='top: -40px; display: block;'>$createdDateErrorMessage</div>";
        }

        if ($this->User->typeId == 6) {
            $welcomeText = "Welcome, " . $this->User->name;
        } else {

            if ($this->User->typeId == 2) {
                $welcomeText = $this->User->clientName . " Physician Outreach Portal";
            } else if ($this->User->typeId == 3) {
                $doctorName = $this->User->firstName . " " . $this->User->lastName;
                $welcomeText = $doctorName . " Physician Outreach Portal";
            } else {
                $welcomeText = $this->Title;
            }
        }

        if (self::PRINT_TRANSLATIONAL_CHECKBOX == true) {
            $translationalCheckboxHtml = "
                <div class=\"row\" title=\"Only show orders that have a translational report attached.\">
                    <div class=\"one half pad-left pad-right\">
                        <label for=\"translationalOnly\" class=\"inline bold\">Translational Only: </label>
                    </div>
                    <div class=\"once half pad-left pad-right\">
                        <input type=\"checkbox\" name=\"translationalOnly\" id=\"translationalOnly\" value=\"1\" />
                    </div>
                </div>";
        }

        $patientColWidth = "half";
        $patientCol = "
            <div class=\"one $patientColWidth pad-left pad-right\">
                <label for=\"patientId\" class=\"bold\">Id/EMR/Chart #: </label>
                <input type=\"text\" name=\"patientId\" id=\"patientId\" />
            </div>
        ";
        if (defined('static::PrintPatientIdSearch') && self::PrintPatientIdSearch == false) {
            $patientColWidth = "third";
            $patientCol = "";
        }

        $specimenDateColHeader = self::SpecimenDateColHeader;
        $specimenDatePlaceholder = "collection date";
        if ($specimenDateColHeader == "Specimen Date") {
            $specimenDatePlaceholder = "specimen date";
        }

        $newResultsButton = "";
        if ($this->HasUnprintedOrders) {
            $newResultsTooltip = "There are " . $this->TotalOrders . " new orders. Click here to print reports.";
            if ($this->TotalOrders == 1) {
                $newResultsTooltip = "There is 1 new order. Click here to print report.";
            } else if ($this->TotalOrders > $this->MaxOrders) {
                $newResultsTooltip = "There are " . $this->TotalOrders . " new orders. Click here to print the first " . $this->MaxOrders . " reports.";
            }
            $newResultsButton = "<button class='red tooltipped' id='btnNewResults' data-tooltip='$newResultsTooltip' data-position='top'>New Results</button>";
        }

        $html = "
            <div class=\"container\" id=\"wrapper\">
                <div class=\"row\">
                    <div class=\"two mobile thirds centered\">
                        <h3 style=\"margin-top: 20px; text-align: center;\">$welcomeText</h3>
                    </div>
                </div>
                <div class=\"row\">
                    <div class=\"two mobile thirds padded centered box_shadow callout success\" id=\"searchForm\" style=\"border: 1px solid #5a5a5a;\">
                        <div class=\"row\" style=\"margin-bottom: 8px;\">
                            <div class=\"one mobile sixth\">
                                <h4 style=\"text-align: left; margin: 0;\">Result Search</h4>
                            </div>
                            <div class=\"three mobile sixths\" id=\"err\" style=\"text-align:center;\">
                                $newResultsButton
                                $errorMessagesHtml
                            </div>
                            <div class=\"two mobile sixths\">
                                <p style=\"text-align: right; margin: 0;\"><strong>Last Login: </strong>
                                    $lastLogin
                                </p>
                            </div>
                        </div>
                        <form action=\"found.php\" method=\"post\" name=\"frmSearch\" id=\"frmSearch\" style=\"width: 100%\">
                            <input type=\"hidden\" name=\"totalOrders\" id=\"totalOrders\" value=\"" . $this->TotalOrders . "\" />
                            <input type=\"hidden\" name=\"maxOrders\" id=\"maxOrders\" value=\"" . $this->MaxOrders . "\" />
                            <fieldset class=\"rounded pad-bottom box_shadow\">
                                <h5>Patient Fields</h5>
                                <a class=\"toggle\" href=\"javascript:void(0)\" title=\"hide field group\">-</a>
                                <div class=\"fieldGroup\">
                                    <div class=\"row\">
                                        <div class=\"one mobile $patientColWidth pad-left pad-right\">
                                            <label for=\"patientFirstName\" class=\"bold\">First Name: </label>
                                            <input type=\"text\" name=\"patientFirstName\" id=\"patientFirstName\" />
                                        </div>
                                        <div class=\"one mobile $patientColWidth pad-left pad-right\">
                                            <label for=\"patientLastName\" class=\"bold\">Last Name: </label>
                                            <input type=\"text\" name=\"patientLastName\" id=\"patientLastName\" />
                                        </div>
                                        <div class=\"one mobile $patientColWidth pad-left pad-right\">
                                            <label for=\"patientDOB\" class=\"bold\">Date of Birth: (MM/DD/YYYY) </label>
                                            <input type=\"text\" name=\"patientDOB\" id=\"patientDOB\" class=\"datepicker\" placeholder=\"ex: 01/01/1980\" />
                                            $dobErrorHtml
                                        </div>
                                        $patientCol
                                    </div>
                                </div>
                            </fieldset>
                            $doctorFieldsetHtml
                            <fieldset class=\"rounded box_shadow gap-top\">
                                <h5>Result Fields</h5>
                                <a class=\"toggle\" href=\"javascript:void(0)\" title=\"hide field group\" style=\"float: right;\">-</a>
                                $this->OldSiteLink

                                <div class=\"fieldGroup\">
                                    <div class=\"row\">
                                        <div class=\"one mobile half pad-left pad-top\">
                                            <label class=\"bold inline\" style=\"padding-bottom: 0;\">Date of Service</label>
                                        </div>
                                        <div class=\"one mobile half pad-left pad-top\">
                                            <div class=\"error\" id=\"dos\"></div>
                                        </div>
                                    </div>
                                    <div class=\"row\" id=\"dos\">
                                        <div class=\"one mobile half pad-left pad-right\" >
                                            <label for=\"dosFrom\" class=\"inline\">From: </label>
                                            <input class=\"datepicker\" type=\"text\" name=\"dosFrom\" id=\"dosFrom\" placeholder=\"enter beginning date of service\" autocomplete=\"off\" />
                                            $dosErrorHtml
                                        </div>
                                        <div class=\"one mobile half pad-left pad-right\">
                                            <label for=\"dosTo\" class=\"inline\">To: </label>
                                            <input class=\"datepicker\" type=\"text\" name=\"dosTo\" id=\"dosTo\" placeholder=\"enter the ending date of service\" autocomplete=\"off\" />
                                        </div>
                                    </div>
                                    <div class=\"row\">
                                        <div class=\"one mobile half pad-left pad-top\">
                                            <label class=\"bold inline\" style=\"padding-bottom: 0;\">Date Reported</label>
                                        </div>
                                        <div class=\"one mobile half pad-left pad-top\">
                                            <div class=\"error\" id=\"reported\"></div>
                                        </div>
                                    </div>
                                    <div class=\"row\">
                                        <div class=\"one mobile half pad-left pad-right\">
                                            <label for=\"reportedFrom\" class=\"inline\">From: </label>
                                            <input class=\"datepicker\" type=\"text\" name=\"reportedFrom\" id=\"reportedFrom\" placeholder=\"enter beginning date reported\" autocomplete=\"off\" />
                                            $dateReportedErrorHtml
                                        </div>
                                        <div class=\"one mobile half pad-left pad-right\">
                                            <label for=\"reportedTo\" class=\"inline\">To: </label>
                                            <input class=\"datepicker\" type=\"text\" name=\"reportedTo\" id=\"reportedTo\" placeholder=\"enter the ending date reported\" autocomplete=\"off\" />

                                        </div>
                                    </div>
                                    <div class=\"row\">
                                        <div class=\"one mobile half pad-left pad-top\">
                                            <label class=\"bold inline\" style=\"padding-bottom: 0;\">$specimenDateColHeader</label>
                                        </div>
                                        <div class=\"one mobile half pad-left pad-top\">
                                            <div class=\"error\" id=\"specimen\"></div>
                                        </div>
                                    </div>
                                    <div class=\"row\">
                                        <div class=\"one mobile half pad-left pad-right\">
                                            <label for=\"specimenFrom\" class=\"inline\">From: </label>
                                            <input class=\"datepicker\" type=\"text\" name=\"specimenFrom\" id=\"specimenFrom\" placeholder=\"enter beginning $specimenDatePlaceholder\" autocomplete=\"off\" />
                                            $specimenDateErrorHtml
                                        </div>
                                        <div class=\"one mobile half pad-left pad-right\">
                                            <label for=\"specimenTo\" class=\"inline\">To: </label>
                                            <input class=\"datepicker\" type=\"text\" name=\"specimenTo\" id=\"specimenTo\" placeholder=\"enter the ending $specimenDatePlaceholder\" autocomplete=\"off\" />
                                        </div>
                                    </div>
                                    <div class=\"row\">
                                        <div class=\"one mobile half pad-left pad-top\">
                                            <label class=\"bold inline\" style=\"padding-bottom: 0;\">Imported/Created</label>
                                        </div>
                                        <div class=\"one mobile half pad-left pad-top\">
                                            <div class=\"error\" id=\"created\"></div>
                                        </div>
                                    </div>
                                    <div class=\"row\">
                                        <div class=\"one mobile half pad-left pad-right\">
                                            <label for=\"createdFrom\" class=\"inline\" style='display: inline;'>From: </label>
                                            <input class=\"datepicker\" type=\"text\" name=\"createdFrom\" id=\"createdFrom\" placeholder=\"enter beginning date created\" autocomplete=\"off\" />
                                            $createdDateErrorHtml
                                        </div>
                                        <div class=\"one mobile half pad-left pad-right\">
                                            <label for=\"createdTo\" class=\"inline\">To: </label>
                                            <input class=\"datepicker\" type=\"text\" name=\"createdTo\" id=\"createdTo\" placeholder=\"enter the ending date created\" autocomplete=\"off\" />
                                        </div>
                                    </div>
                                    <div class=\"row pad-top\">
                                        <div class=\"one mobile half pad-left pad-right\">
                                            <div class=\"row pad-bottom\">
                                                <div class=\"one mobile whole\">
                                                    <label for=\"accession\" class=\"inline\">Accession: </label>
                                                    <input style=\"width: 85%;\" class=\"inline\" type=\"text\" name=\"accession\" id=\"accession\" />
                                                </div>
                                            </div>
                                            $this->PendingCheckbox
                                        </div>
                                        <div class=\"one mobile half pad-left pad-right\">
                                            <div class=\"row\" title=\"Only show orders that have inconsistent results.\">
                                                <div class=\"once mobile half pad-left pad-right\">
                                                    <label for=\"abnormalsOnly\" class=\"inline bold\">Abnormals Only: </label>
                                                </div>
                                                <div class=\"once mobile half pad-left pad-right\">
                                                    <input type=\"checkbox\" name=\"abnormalsOnly\" id=\"abnormalsOnly\" value=\"1\" />
                                                </div>
                                            </div>
                                            <div class=\"row\" title=\"Only show orders that have not yet been viewed by this user.\">
                                                <div class=\"once mobile half pad-left pad-right\">
                                                    <label for=\"unprintedReports\" class=\"inline bold\">Unprinted Reports: </label>
                                                </div>
                                                <div class=\"once mobile half pad-left pad-right\">
                                                    <input type=\"checkbox\" name=\"unprintedReports\" id=\"unprintedReports\" value=\"1\" />
                                                </div>
                                            </div>
                                            <div class=\"row\" title=\"Only show orders that have a date of service after your previous login.\">
                                                <div class=\"one mobile half pad-left pad-right\">
                                                    <label for=\"sinceLastLogin\" class=\"inline bold\">Since Last Login: </label>
                                                </div>
                                                <div class=\"once mobile half pad-left pad-right\">
                                                    <input type=\"checkbox\" name=\"sinceLastLogin\" id=\"sinceLastLogin\" value=\"1\" />
                                                </div>
                                            </div>
                                            <div class=\"row\" title=\"Only show orders that have been invalidated.\">
                                                <div class=\"one mobile half pad-left pad-right\">
                                                    <label for=\"invalidatedOnly\" class=\"inline bold\">Invalidated Only: </label>
                                                </div>
                                                <div class=\"once mobile half pad-left pad-right\">
                                                    <input type=\"checkbox\" name=\"invalidatedOnly\" id=\"invalidatedOnly\" value=\"1\" />
                                                </div>
                                            </div>
                                            $translationalCheckboxHtml
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                            <div class=\"row\">
                                <div class=\"one mobile half pad-left pad-right pad-top\">
                                    <button class=\"green submit\" id=\"btnSubmit\">Submit</button>
                                </div>
                                <div class=\"one mobile half pad-left pad-right\">
                                    $this->UpsSiteLink
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div id='printLoading'>&nbsp;<i class='icon-spinner icon-spin icon-4x'></i></div>
            <div id='frameContainer'>
                <div id='box'></box>
            </div>
            
        ";

        echo $html;
    }
   
    public function printErrorMessages() {
        $html = "";
        $display = "display: none;";
        $errorMessages = "";
        if (isset($this->ErrorMessages['OrderCount']) || isset($this->ErrorMessages['EmptyForm'])) {
            $display = "display: inline-block !important;";
        }
        if (isset($this->ErrorMessages['EmptyForm'])) {
            $errorMessages .= $this->ErrorMessages['EmptyForm'];
        } elseif (isset($this->ErrorMessages['OrderCount'])) {
            $errorMessages .= $this->ErrorMessages['OrderCount'];
        }

        $html .= "
            <h5 style=\"$display\" id=\"error\">
                $errorMessages
            </h5>
        ";
        return $html;
    }
    
    private function setErrorMessages() {
        if (isset($_SESSION['ErrorMessages'])) {
            $this->ErrorMessages = $_SESSION['ErrorMessages'];
            $this->IsValidForm = false;
            $_SESSION['ErrorMessages'] = "";
            unset($_SESSION['ErrorMessages']);
        }
    }
    
    private function setLastLogin() {
        if (isset($this->UserDAO) && $this->UserDAO instanceof UserDAO) {
            $lastLogin = $this->UserDAO->getLastLogin();
            if ($lastLogin != null) {
                $this->LastLogin = $lastLogin;
            } else {
                $this->LastLogin = "<span style='color: #CCCCCC; font-style: italic;'>This is your first login</span>";
            }
        } else {
            $this->LastLogin = "";
        }

    }
    
    private function resetSearchSession() {
        if (isset($_SESSION['accessionList'])) {
            $_SESSION['accessionList'] = "";
            unset($_SESSION['accessionList']);
        }
        if (isset($_SESSION['OrderSearch'])) {
            $_SESSION['OrderSearch'] = "";
            unset($_SESSION['OrderSearch']);
        }
        if (isset($_SESSION['invalidatedOrderIds'])) {
            $_SESSION['invalidatedOrderIds'] = "";
            unset($_SESSION['invalidatedOrderIds']);
        }
        if (isset($_SESSION['idOrdersList'])) {
            $_SESSION['idOrdersList'] = "";
            unset($_SESSION['idOrdersList']);
        }
        if (isset($_SESSION['idOrdersList2'])) {
            $_SESSION['idOrdersList2'] = "";
            unset($_SESSION['idOrdersList2']);
        }
        if (isset($_SESSION['searchFields'])) {
            $_SESSION['searchFields'] = "";
            unset($_SESSION['searchFields']);
        }
        if (isset($_SESSION['TotalOrders'])) {
            $_SESSION['TotalOrders'] = "";
            unset($_SESSION['TotalOrders']);
        }
        if (isset($_SESSION['OrdersPerPage'])) {
            $_SESSION['OrdersPerPage'] = "";
            unset($_SESSION['OrdersPerPage']);
        }
        if (isset($_SESSION['TotalPages'])) {
            $_SESSION['TotalPages'] = "";
            unset($_SESSION['TotalPages']);
        }
        if (isset($_SESSION['CurrentPage'])) {
            $_SESSION['CurrentPage'] = "";
            unset($_SESSION['CurrentPage']);
        }
        if (isset($_SESSION['Start'])) {
            $_SESSION['Start'] = "";
            unset($_SESSION['Start']);
        }
        if (isset($_SESSION['End'])) {
            $_SESSION['End'] = "";
            unset($_SESSION['End']);
        }
        if (isset($_SESSION['AllIdsOrdered'])) {
            $_SESSION['AllIdsOrdered'] = "";
            unset($_SESSION['AllIdsOrdered']);
        }
        if (isset($_SESSION['AllIdsGrouped'])) {
            $_SESSION['AllIdsGrouped'] = "";
            unset($_SESSION['AllIdsGrouped']);
        }
        if (isset($_SESSION['PageIdsGrouped'])) {
            $_SESSION['PageIdsGrouped'] = "";
            unset($_SESSION['PageIdsGrouped']);
        }
        if (isset($_SESSION['OrderBy'])) {
            $_SESSION['OrderBy'] = "";
            unset($_SESSION['OrderBy']);
        }
        if (isset($_SESSION['Direction'])) {
            $_SESSION['Direction'] = "";
            unset($_SESSION['Direction']);
        }
        if (isset($_SESSION['CurrentPage'])) {
            $_SESSION['CurrentPage'] = "";
            unset($_SESSION['CurrentPage']);
        }
        if (isset($_SESSION['MaxRows'])) {
            $_SESSION['MaxRows'] = "";
            unset($_SESSION['MaxRows']);
        }
        if (isset($_SESSION['Offset'])) {
            $_SESSION['Offset'] = "";
            unset($_SESSION['Offset']);
        }
    }
    
    public function __get($field) {
        $value = parent::__get($field);
        if (empty($value)) {            
            if ($field == "User") {
                $value =  $this->User;
            } else if ($field == "LastLogin") {
                $value = $this->LastLogin;
            } else if ($field == "ErrorMessages") {
                $value = $this->ErrorMessages;
            } else if ($field == "IsValidForm") {
                $value = $this->IsValidForm;
            }
        }
        return $value;        
    }

    public function __isset($field) {
        $isset = false;
        if ($field == "User" && isset($this->User) && $this->User instanceof User) {
            $isset = true;
        }
        return $isset;
    }
}
?>
