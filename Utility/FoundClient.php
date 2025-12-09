<?php
if (!isset($_SESSION)) {
    session_start();
}
require_once 'PageClient.php';
require_once 'DAOS/OrderSearchDAO.php';
require_once 'Utility/SearchValidator.php';
require_once 'DAOS/AdminDAO.php';
require_once 'DAOS/ClientDAO.php';
require_once 'DAOS/DoctorDAO.php';
require_once 'IClient.php';
/**
 * The purpose of this class is to assist with providing data used directly on the found.php page
 *
 * @author Edd
 */
class FoundClient extends PageClient implements IClient {
    
    private $SearchData = array (
        "MaxRows" => 10,        
        "Offset" => 0,
        "OrderBy" => "orderDate",
        "Direction" => "desc",
        "CurrentPage" => 1,
        "TotalOrders" => 0,
        "TotalPages" => 1        
    );
    
    private $SearchFields = array();
    private $DefaultReportType;
    private $IsAdmin = false;
    //private $AdminUser = false;
    private $Orders = array();
    private $OrderByKeys = array (
        "accession", "doctorLastName", "number", 
        "clientName", "clientNo", "patientLastName", 
        "patientFirstName", "patientNo", "orderDate", "specimenDate",
        "stage"
    );

    private $OSDao;

    private $WebReports = array();
    
    public function __construct(array $data = null) {
        parent::__construct();
        if ($data != null) {
            foreach ($data as $key => $value) {
                if (array_key_exists($key, $this->SearchData)) {
                    if ($key != "OrderBy" || in_array($value, $this->OrderByKeys)) {
                        $this->SearchData[$key] = $value;
                    }
                }
            }
        }

        $this->setAdminUser();
        $validateSearch = $this->setSearchFields();
        $userIsSet = $this->setDAO();

        //$this->WebReports = $this->OSDao->getWebReports();

        if ($data != null && array_key_exists("InvalidateOrderId", $data) && !empty($data['InvalidateOrderId']) && array_key_exists("InvalidateOrderStatus", $data)) {
            $this->invalidateOrder($data['InvalidateOrderId'], $data['InvalidateOrderStatus']);
        }

        if ($userIsSet && $this->doValidate($validateSearch)) { // everything looks good so far, so get the main order data

            $this->setOffset();

            $this->resetViewSession();

            $arySearchData = $this->SearchData;
            $arySearchData['Ip'] = $this->getIpAddress();

            /*echo "<pre>"; print_r($arySearchData); echo "</pre>";*/

            $this->Orders = $this->OSDao->getResultSearch($arySearchData);
            $this->SearchData['TotalOrders'] = $this->OSDao->TotalOrders;

            if ($this->SearchData['TotalOrders'] == 0) {
                $_SESSION['ErrorMessages']["OrderCount"] = "No results found. Please try again.";
                header("Location: search.php");
            }

            $this->DefaultReportType = $this->OSDao->getDefaultReportType();
            $this->setTotalPages();

            if (isset($data['Direction'])) {
                $this->SearchData['Direction'] = $data['Direction'];
                /*if ($data['Direction'] == "asc") {
                    $this->SearchData['Direction'] = "desc";
                } else {
                    $this->SearchData['Direction'] = "asc";
                }*/
            }

            $this->setSession();
        }

        $this->addStylesheet("/outreach/css/found.css");
        $this->addStylesheet("/outreach/css/pagination.css");
        $this->addStylesheet("/outreach/css/dropdown.css");

        $this->addScript("/outreach/js/dropdown.js");
        //$this->addScript("js/plugins/jquery-responsiveText.js");
        $this->addScript("js/plugins/jquery-truncateLines.js");
        $this->addScript("js/components/equalizeColumns.js");
        $this->addScript("js/found.js");
        $this->addScript("js/paginate.js");

        //echo "<pre>"; print_r($this->AdminUser); echo "</pre>";
    }

    private function doValidate($validateSearch) {
        $isValid = true;
        if ($validateSearch) {
            $searchValidator = new SearchValidator($_POST, $this->User, 0);
            $isValid = $searchValidator->validate();
            if (!$isValid) {
                if (array_key_exists("User", $searchValidator->ErrorMessages)) {
                    error_log("FoundClient Error: ResultUser Not Found"); // this error should never reasonably happen, so log the error
                    parent::logout();
                    header("Location: /login.php");
                    exit();
                } else {
                    $errorMessages = $searchValidator->ErrorMessages;
                    foreach ($errorMessages as $field => $message) {
                        $_SESSION['ErrorMessages'][$field] = $message;
                    }
                    header("Location: search.php");
                    exit();
                }
            }
        }
        return $isValid;
    }

    private function setAdminUser() {


        if (isset($this->AdminUser) && $this->AdminUser instanceof AdminUser
            && isset($_SESSION['AdminId']) && !empty($_SESSION['AdminId']) && isset($_SESSION['AdminType'])) {
            $this->IsAdmin = true;
        }

        /*if (isset($_SESSION['AdminUser']) && $_SESSION['AdminUser'] == 1 && isset($_SESSION['AdminId']) && !empty($_SESSION['AdminId'])) {
            $this->IsAdmin = true;
            $this->AdminUser = AdminDAO::getAdmin(
                array("idUsers" => $_SESSION['AdminId']),
                array(
                    "IncludeAdminSettings" => true,
                    "Conn" => $this->UserDAO->Conn
                )
            );
        }*/
    }

    private function setDAO() {
        if (isset($this->User) && $this->User != null && $this->User instanceof User) {
            $this->OSDao = new OrderSearchDAO($this->SearchFields, $this->User, $this->UserDAO->Conn); // DAO for getting order search data
            return true;
        }
        return false;
    }

    // Set session ids for first/prev/next/last buttons on view page
    // Excludes pending web orders and pending in lab orders from buttons
    private function setSession() {
        $aryAllIdsGrouped = array();
        $aryAllIdsOrdered = array();
        $orders = $this->OSDao->AllOrders;
        foreach ($orders as $currOrder) {
            if ($currOrder[2] != 0 && $currOrder[2] != 2) {
                $currIdOrders = $currOrder[0];
                $currReportType = $currOrder[1];

                $aryAllIdsGrouped[$currReportType][] = $currIdOrders;
                $aryAllIdsOrdered[$currIdOrders] = $currReportType;
            }
        }

        $_SESSION['AllIdsGrouped'] = $aryAllIdsGrouped; // array of arrays - dependant on report type
        $_SESSION['AllIdsOrdered'] = $aryAllIdsOrdered; // single key/value array - idOrders => reportType
    }

    private function setOffset() {
        if ($this->SearchData['CurrentPage'] > 1) {
            $this->SearchData['Offset'] = $this->SearchData['MaxRows'] * ($this->SearchData['CurrentPage'] - 1);
        }
    }
    
    private function setTotalPages() {
        $tmpTotal = $this->SearchData['TotalOrders'] / $this->SearchData['MaxRows'];
        if (is_numeric($tmpTotal) && floor($tmpTotal) != $tmpTotal) {
                $tmpTotal = substr($tmpTotal, 0, strpos($tmpTotal, '.'));
                $tmpTotal += 1;
        }
        if (is_numeric($tmpTotal)) {
                $this->SearchData['TotalPages'] = $tmpTotal;
        }
    }
    public function __get($key) {
        if (array_key_exists($key, $this->SearchData)) {
            return $this->SearchData[$key];
        } else if ($key == "SearchFields") { // return the array of search fields
             return $this->SearchFields;             
        } else if ($key == "ResultUser" || $key == "User") {     
            return $this->User;
        } else if ($key == "Orders") {
        	return $this->Orders;        	 
        } else if ($key == "DefaultReportType") {
        	return $this->DefaultReportType;        	
        } else if ($key == "IsAdmin") {
        	return $this->IsAdmin;
        } else if ($key == "AdminUser") {
        	return $this->AdminUser;        	
        } else { // return a single field
            if (array_key_exists($key, $this->SearchFields)) {
                return $this->SearchFields[$key];
            } else if (array_key_exists($key, $this->User)) {
                return $this->User[$key];
            }
        }
    }
    
    private function resetViewSession() {
        if (isset($_SESSION['ViewOrderIds'])) {
            $_SESSION['ViewOrderIds'] = "";
            unset ($_SESSION['ViewOrderIds']);
        }
        if (isset($_SESSION['ViewMultiple'])) {
            $_SESSION['ViewMultiple'] = "";
            unset($_SESSION['ViewMultiple']);
        }
        if (isset($_SESSION['ViewType'])) {
            $_SESSION['ViewType'] = "";
            unset($_SESSION['ViewType']);
        }
        if (isset($_SESSION['ViewSelectedOrderIds'])) {
            $_SESSION['ViewSelectedOrderIds'] = "";
            unset($_SESSION['ViewSelectedOrderIds']);
        }
        if (isset($_SESSION['reportType'])) {
            $_SESSION['reportType'] = "";
            unset($_SESSION['reportType']);
        }
    }

    
    public function printPage() {
        $html = "
        <div class=\"row\">
                <div class=\"one mobile whole\">";

        $totalOrders = $this->SearchData['TotalOrders'];
        $totalPages = $this->SearchData['TotalPages'];
        $currentPage = $this->SearchData['CurrentPage'];

        $orderBy = $this->SearchData['OrderBy'];
        $direction = $this->SearchData['Direction'];
        $currDirection = "desc";
        /*if ($this->SearchData['Direction'] == "desc") {
            $currDirection = "asc";
        }*/
        $currDirection = $this->SearchData['Direction'];

        $pageIdsGrouped = array (); // reportType => Array(idOrders1, 2, ..., n) - used for View Current Page button
        //$aryAllIdsGrouped = array();// array of arrays - dependant on report type - used for viewing multiple orders
        //$aryAllIdsOrdered = array();// single key/value array - idOrders => reportType - used for viewing single orders to populate the first/prev/next/last buttons
        $ordersHtml = "";


        if ($this->TotalOrders > 0) {

            //$attrData = "data-scrollable=\"true\" ";
            $attrData = "data-truncate=\"1\" data-scrollable=\"true\"";
            foreach ($this->Orders as $currOrder) {

                $reportType = $currOrder->reportType;
                $accession = $currOrder->accession;
                $idOrders = $currOrder->idOrders;
                $arNo = $currOrder->Patient->arNo;
                $iddoctors = $currOrder->Doctor->iddoctors;
                $idClients = $currOrder->Client->idClients;

                $invalidatedStyle = "";
                $invalidatedTitle = "";

                $isAbnormalClass = "";
                $isAbnormalTitle = "";

                if ($currOrder->stage != 0 && $currOrder->stage != 2) {
                    if (empty ($reportType )) { // the current order is missing a report type
                        if (empty($currOrder->Client->defaultReportType)) { // the current client does not have their default report type set
                            $currOrder->reportType = $this->DefaultReportType; // use the master default report type
                            $pageIdsGrouped [$this->DefaultReportType] [] = $currOrder->idOrders;
                        } else { // use the client's default report type
                            $currOrder->reportType = $this->Client->defaultReportType;
                            $pageIdsGrouped[$this->Client->defaultReportType][] = $currOrder->idOrders;
                        }
                    } else { // use the report type set on this order
                        $pageIdsGrouped [$reportType] [] = $currOrder->idOrders;
                    }

                    //$aryAllIdsGrouped[$currOrder->reportType][] = $currOrder->idOrders;
                    //$aryAllIdsOrdered[$currOrder->idOrders] = $currOrder->reportType;
                }

                $specimenDateEncoded = urlencode($currOrder->specimenDate);

                $viewURL = "view.php?reportType=$reportType&idOrders=$idOrders&idPatients=$arNo&specimenDate=$specimenDateEncoded";
                $viewWellnessURL = "view.php?reportType=0&idOrders=$idOrders&idPatients=$arNo&specimenDate=$specimenDateEncoded";
                $cumulativeURL = "cumulative.php?reportType=$reportType&specimenDate=$specimenDateEncoded&idPatients=$arNo&doctorId=$iddoctors&clientId=$idClients";

                $viewHtml = "";
                $cumulativeHtml = "";
                $cancelHtml = "";
                $invalidateHtml = "";

                $title = "";

                $doctor = $this->getDoctorName($currOrder);

                $client = $currOrder->Client->clientName . " (" . $currOrder->Client->clientNo . ")";
                $patient = $currOrder->Patient->firstName . " " . $currOrder->Patient->lastName;
                if (isset($currOrder->Patient->arNo) && !empty($currOrder->Patient->arNo)) {
                    $patient .= " (" . $currOrder->Patient->arNo . ")";
                }
                $orderDate = date("m/d/Y h:i:s A", strtotime($currOrder->orderDate));
                $specimenDate = date("m/d/Y h:i:s A", strtotime($currOrder->specimenDate));
                $orderStatus = $currOrder->OrderStatus;
                $stageHtml = "<input type=\"hidden\" name=\"statusNum\" id=\"statusNum\" value=\"" . $currOrder->stage . "\"/>";

                if ($currOrder->IsInvalidated == 1) {
                    $invalidatedStyle = "font-style:italic; color: #CCCCCC;";
                    if ($currOrder->stage == 0) {
                        $invalidatedTitle = "This result order was cancelled.";
                    } else {
                        $invalidatedTitle = "This result order was invalidated";
                    }
                }


                if ($currOrder->IsAbnormal == $currOrder->idOrders && $currOrder->IsInvalidated != 1) {
                    $isAbnormalClass = "abnormal";
                    $isAbnormalTitle = "This result order has abnormal or inconsistent results.";
                }

                $webOrderTitle = "";
                $checkboxDisabled = "";
                if ($currOrder->stage == 0 || $currOrder->stage == 2) {
                    $viewHtml = "<i class=\"icon-book\"></i>";
                    $checkboxDisabled = "disabled='disabled'";

                    if ($currOrder->stage == 0) {
                        $webOrderTitle = "The result report cannot be viewed for this order because it is still waiting to be received by the lab.";
                    } else if ($currOrder->stage == 2) {
                        $webOrderTitle = "The result report cannot be viewed for this order because it is pending in the lab.";
                    } else {
                        $webOrderTitle = "The result report cannot be viewed for this order";
                    }

                } else {

                    if (self::HasBloodWellnessReport == true) {
                        $viewHtml = "
                            <div class='dropdown'>
                                <button class='btn btn-default dropdown-toggle' type='button' data-toggle='dropdown'><i class='icon-book'></i></button>
                                <ul class='dropdown-menu' role='menu' aria-labelledby='menu1'>
                                    <li role='presentation'><a title='View Result Report' class='view' href='$viewURL'><i class='icon-book'></i> Result Report</a></li>
                                    <li role='separator' class='divider'></li>
                                    <li role='presentation'><a title='View Blood Wellness Report' class='view' href='$viewWellnessURL'><i class='icon-book'></i> Blood Wellness Report</a></li>
                                </ul>
                            </div>";

                    } else {
                        $viewHtml = "<a title='View Report " . $currOrder->stage . "' class='view' href='$viewURL'><i class='icon-book'></i></a>";
                    }
                }

                if ($this->IsAdmin) {
                    if ($this->User->hasUserSettingByName ( "Has Cumulative" )) {
                        if ($currOrder->stage == 0) {
                            $cumulativeHtml = "<i title=\"Cumulatives cannot be viewed for orders that have not yet been received and reported by the lab.\" class=\"icon-calendar\"></i>";
                        } else if ($currOrder->stage == 2) {
                            $cumulativeHtml = "<i title=\"Cumulatives cannot be viewed for orders that are pending in the lab.\" class=\"icon-calendar\"></i>";
                        } else {
                            $cumulativeHtml = "<a title=\"View Cumulative\" class=\"view\" href=\"$cumulativeURL\"><i class=\"icon-calendar\"></i></a>";
                        }
                    }
                    if ($this->AdminUser->hasSettingByName ( "Can Invalidate Reports" )) {
                        if ($currOrder->IsInvalidated == 0) {
                            if ($currOrder->stage == 0) {
                                $cancelHtml = "<a href=\"javascript:void(0)\" class=\"view invalidate\" title=\"Cancel this result order.\" id=\"$currOrder->idOrders\">"
                                    . "<i class=\"icon-ban-circle\"></i></a>";
                            } else {
                                $invalidateHtml = "<a href=\"javascript:void(0)\" class=\"view invalidate\" title=\"Invalidate this result order.\" id=\"$currOrder->idOrders\">"
                                    . "<i class=\"icon-ban-circle\"></i></a>";
                            }
                        } else {
                            if ($currOrder->stage == 0) {
                                $cancelHtml = "<i class=\"icon-ban-circle\" title=\"This result order was cancelled\"></i>";
                            } else {
                                $invalidateHtml = "<i class=\"icon-ban-circle\" title=\"This result order was invalidated\"></i>";
                            }
                        }
                    }
                } else { // regular result user

                    if ($this->User->hasUserSettingByName ( "Has Cumulative" )) {
                        $cumulativeHtml = "<a title=\"View Cumulative\" class=\"view\" href=\"$cumulativeURL\"><i class=\"icon-calendar\"></i></a>";
                    }
                }

                if ($invalidatedTitle != "") {
                    $title = "title='$invalidatedTitle'";
                } else if ($webOrderTitle != "") {
                    $title = "title='$webOrderTitle'";
                } else if ($isAbnormalTitle != "") {
                    $title = "title='$isAbnormalTitle'";
                }

                $webAccession = "";
                if (isset($currOrder->webAccession) && !empty($currOrder->webAccession)) {
                    $webAccession = "Web Acc: " . $currOrder->webAccession;
                }

                $ordersHtml .= "
                <div class=\"row resultOrder equalize $isAbnormalClass\" $title>
                    <div class=\"one mobile ninth action actionCol pad-left\">
                        $viewHtml
                        $cumulativeHtml
                        $cancelHtml
                        $invalidateHtml
                    </div>
                    <div class=\"one mobile ninth viewSelectedCol\">
                        <input type=\"checkbox\" name=\"view[$currOrder->reportType][]\" class=\"view\" $checkboxDisabled value=\"$idOrders\" />
                    </div>
                    <div class=\"one mobile ninth accessionCol \" style=\"$invalidatedStyle\">
                        <div data-truncate=\"1\">$accession</div>
                        $webAccession
                    </div>
                    <div class=\"one mobile ninth doctorCol hide-on-mobile\" style=\"min-height: 21px; $invalidatedStyle\">
                        <div data-truncate=\"1\">$doctor</div>
                    </div>
                    <div class=\"one mobile ninth desktop-only clientCol\" style=\"$invalidatedStyle\">
                        <div data-truncate=\"1\">$client</div>
                    </div>
                    <div class=\"one mobile ninth patientCol\" style=\"$invalidatedStyle\">
                        <div data-truncate=\"1\">$patient</div>
                    </div>
                    <div class=\"one mobile ninth orderDateCol\" style=\"$invalidatedStyle\">
                        <div data-truncate=\"1\">$orderDate</div>
                    </div>
                    <div class=\"one mobile ninth desktop-only\" style=\"$invalidatedStyle\">
                        <div data-truncate=\"1\">$specimenDate</div>
                    </div>
                    <div class=\"one mobile ninth desktop-only statusCol\" style=\"$invalidatedStyle\">
                        <div data-truncate=\"1\">$orderStatus</div>
                        $stageHtml
                    </div>
                </div>";
            }

            $orderDateColHeader = self::OrderDateColHeaderText;
            $specimenDateColHeader = self::SpecimenDateColHeader;

            $orderDateTitle = "date of service";
            if ($orderDateColHeader == "Received in Lab") {
                $orderDateTitle = "received in lab";
            }

            $specimenDateTitle = "specimen date";
            if ($specimenDateColHeader == "Collection Date") {
                $specimenDateTitle = "collection date";
            }

            $viewSelectedHtml = "<a href='javascript:void(0)' id='btnViewSelected' class='button green'>View Selected</a>";

            $viewAllHtml = "";
            $colWidth = "half";
            if (!self::ViewAllDisabled) {
                $viewAllHtml = "<div class='one mobile third padded' style='text-align: center;'><a href='javascript:void(0)' id='btnViewAll' class='green button'>View All</a></div>";
                $colWidth = "third";
            }

            $viewCurrPageHtml = "<a href='javascript:void(0)' id='btnViewCurrentPage' class='green button'>View Current Page</a>";
            if (self::HasBloodWellnessReport == true) {
                $viewSelectedHtml = "<div class='dropdown dropdown-view' style='float: right;'>
                        <button class='btn btn-default dropdown-toggle green button' type='button' data-toggle='dropdown'><i class='icon-book'></i> View Selected</button>
                        <ul class='dropdown-menu' role='menu' aria-labelledby='menu1'>
                            <li role='presentation'><a href='javascript:void(0)' id='btnViewSelected'><i class='icon-book'></i> Result Report</a></li>
                            <li role='separator' class='divider'></li>
                            <li role='presentation'><a href='javascript:void(0)' id='btnViewSelectedWellness'><i class='icon-book'></i> Blood Wellness Report</a></li>
                        </ul>
                    </div>";
                if (!self::ViewAllDisabled) {
                    $viewAllHtml = "<div class='one mobile third padded' style='text-align: center;'><div class='dropdown dropdown-view'>
                        <button class='btn btn-default dropdown-toggle green button' type='button' data-toggle='dropdown'><i class='icon-book'></i> View All</button>
                        <ul class='dropdown-menu' role='menu' aria-labelledby='menu1'>
                            <li role='presentation'><a href='javascript:void(0)' id='btnViewAll'><i class='icon-book'></i> Result Report</a></li>
                            <li role='separator' class='divider'></li>
                            <li role='presentation'><a href='javascript:void(0)' id='btnViewAllWellness'><i class='icon-book'></i> Blood Wellness Report</a></li>
                        </ul>
                    </div></div>";
                }
                $viewCurrPageHtml = "<div class='dropdown dropdown-view'>
                        <button class='btn btn-default dropdown-toggle green button' type='button' data-toggle='dropdown'><i class='icon-book'></i> View Current Page</button>
                        <ul class='dropdown-menu' role='menu' aria-labelledby='menu1'>
                            <li role='presentation'><li role='presentation'><a href='javascript:void(0)' id='btnViewCurrentPage'><i class='icon-book'></i> Result Report</a></li></li>
                            <li role='separator' class='divider'></li>
                            <li role='presentation'><a href='javascript:void(0)' id='btnViewCurrentPageWellness'><i class='icon-book'></i> Blood Wellness Report</a></li>
                        </ul>
                    </div>";
            }

            $html .= "
            <form action=\"view.php\" method=\"post\" name=\"frmFound\" id=\"frmFound\">
                <input type=\"hidden\" name=\"totalOrdersFound\" id=\"totalOrdersFound\" value=\"$totalOrders\" />
                <input type=\"hidden\" name=\"totalPagesFound\" id=\"totalPagesFound\" value=\"$totalPages\" />
                <input type=\"hidden\" name=\"currentPage\" id=\"currentPage\" value=\"$currentPage\" />
                <input type=\"hidden\" name=\"orderBy\" id=\"orderBy\" value=\"$orderBy\" />
                <input type=\"hidden\" name=\"direction\" id=\"direction\" value=\"$currDirection\" />
                <div class=\"row box_shadow\"  id=\"tblResults\">
                    <div class=\"one mobile whole\">
                        <div class=\"row\" id='tableHeader'>
                            <div class=\"one mobile ninth actionCol pad-left\">
                                <strong data-truncate=\"1\">Action</strong>
                            </div>
                            <div class=\"one mobile ninth viewSelectedCol\" id=\"thView\">
                                <strong data-truncate=\"1\">View</strong>
                            </div>
                            <div class=\"one mobile ninth accessionCol\">
                                <strong data-truncate=\"1\">Accession</strong>
                                <a href=\"javascript:void(0)\" class=\"sort\" id=\"accession\" d=\"$direction\" title=\"Sort by accession\"><i class=\"icon-sort\"></i></a>
                            </div>
                            <div class=\"one mobile ninth doctorCol hide-on-mobile\">
                                <strong data-truncate=\"1\">Doctor (#)</strong>
                                <a href=\"javascript:void(0)\" class=\"sort\" id=\"doctorLastName\" d=\"$direction\" title=\"Sort by doctor's last name\"><i class=\"icon-sort\"></i></a>
                                <a href=\"javascript:void(0)\" class=\"sort\" id=\"number\" d=\"$direction\" title=\"Sort by doctor's number\"><i class=\"icon-sort\"></i></a>
                            </div>
                            <div class=\"one mobile ninth desktop-only clientCol\">
                                <strong data-truncate=\"1\">Client (#)</strong>
                                <a href=\"javascript:void(0)\" class=\"sort\" id=\"clientName\" d=\"$direction\" title=\"Sort by client name\"><i class=\"icon-sort\"></i></a>
                                <a href=\"javascript:void(0)\" class=\"sort\" id=\"clientNo\" d=\"$direction\" title=\"Sort by client number\"><i class=\"icon-sort\"></i></a>
                            </div>
                            <div class=\"one mobile ninth patientCol\">
                                <strong data-truncate=\"1\">Patient Name (#)</strong>
                                <a href=\"javascript:void(0)\" class=\"sort\" id=\"patientNo\" d=\"$direction\" title=\"Sort by patient's ID number\"><i class=\"icon-sort\"></i></a>
                                <a href=\"javascript:void(0)\" class=\"sort\" id=\"patientLastName\" d=\"$direction\" title=\"Sort by patient's last name\"><i class=\"icon-sort\"></i></a>
                            </div>
                            <div class=\"one mobile ninth orderDateCol\">
                                <strong data-truncate=\"1\">$orderDateColHeader</strong>
                                <a href=\"javascript:void(0)\" class=\"sort\" id=\"orderDate\" d=\"$direction\" title=\"Sort by $orderDateTitle\"><i class=\"icon-sort\"></i></a>
                            </div>
                            <div class=\"one mobile ninth desktop-only\">
                                <strong data-truncate=\"1\">$specimenDateColHeader</strong>
                                <a href=\"javascript:void(0)\" class=\"sort\" id=\"specimenDate\" d=\"$direction\" title=\"Sort by $specimenDateTitle\"><i class=\"icon-sort\"></i></a>
                            </div>
                            <div class=\"one mobile ninth desktop-only statusCol\">
                                <strong data-truncate=\"1\">Status</strong>
                                <a href=\"javascript:void(0)\" class=\"sort\" id=\"stage\" d=\"$direction\" title=\"Sort by the order status\"><i class=\"icon-sort\"></i></a>
                            </div>
                        </div>

                        $ordersHtml

                        <div class=\"row\">
                            <div class=\"one whole\" colspan=\"9\" id=\"button-container\">
                                <div class=\"row\">
                                    <div class=\"three mobile fifths centered\">
                                        <div class=\"row\">
                                            <div class=\"one mobile $colWidth padded\" style=\"text-align: right;\">
                                                $viewSelectedHtml
                                            </div>
                                            $viewAllHtml
                                            <div class=\"one mobile $colWidth padded\">
                                                $viewCurrPageHtml
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            ";

            $_SESSION ['PageIdsGrouped'] = $pageIdsGrouped;
            //$_SESSION['AllIdsGrouped'] = $aryAllIdsGrouped;
            //$_SESSION['AllIdsOrdered'] = $aryAllIdsOrdered;
            $_SESSION ['TotalOrders'] = $this->SearchData['TotalOrders'];

        } else {
            $_SESSION ['ErrorMessages'] ['OrderCount'] = "No results found. Please try again.";
            $html .= "0";
        }

        $html .= "</div></div>";

        $html .= $this->setPagination($this->SearchData['CurrentPage'], $this->SearchData['TotalPages']);

        //echo "<pre>"; print_r($_SESSION['PageIdsGrouped']); echo "</pre>";
        //echo "<pre>"; print_r($_SESSION['AllIdsGrouped']); echo "</pre>";
        //echo "<pre>"; print_r($_SESSION['AllIdsOrdered']); echo "</pre>";

        echo $html;
    }

    private function getDoctorName(Order $currOrder) {
        $doctor = "";
        if (isset($currOrder->Doctor) && $currOrder->Doctor != null && $currOrder->Doctor instanceof DoctorUser) {
            $lastName = $currOrder->Doctor->lastName;
            $firstName = $currOrder->Doctor->firstName;

            if (!empty($lastName) && !empty($firstName)) {
                $doctor = $firstName . " " . $lastName . " (" . $currOrder->Doctor->number . ")";
            } else if (!empty($lastName)) {
                $doctor = $lastName . " (" . $currOrder->Doctor->number . ")";
            } else if (!empty($firstName)) {
                $doctor = $firstName . " (" . $currOrder->Doctor->number . ")";
            } else {
                $doctor = $currOrder->Doctor->number;
            }
        }
        return $doctor;
    }

    private function setPagination($currentPage, $totalPages) {

        $rowWidth = $this->getRowWidth();

        $liHtml = "";
        for($i = 1; $i <= $totalPages; $i ++) {
            $liStyle = "";
            if ($i == $currentPage) {
                $liStyle = "style=\"background: #29b765;\"";
            }
            $liHtml .= "<li class=\"rounded\" id=\"page\"><a href=\"javascript:void(0)\" class=\"pages button\" id=\"$i\" $liStyle>$i</a></li>";
        }

        $pageHtml = "
        <div class=\"row\">
            <div class=\"one whole centered\">
                <p id=\"total\" style=\"font-weight: bold;\">
                    <strong>Page $currentPage of $totalPages</strong>
                </p>
             </div>
        </div>
        <input type=\"hidden\" name=\"page-clicks\" id=\"page-clicks\" value=\"0\" />
        <div class=\"pagination-row\" style=\"$rowWidth\">
            <div class=\"one mobile third\" id=\"first-page\">
                <li class=\"rounded\" id=\"scroll-left\"><a href=\"javascript:void(0)\" class=\"button\" id=\"1\"><i class=\"icon-double-angle-left\"></i></a></li>
                <li class=\"rounded\" id=\"page\"><a href=\"javascript:void(0)\" class=\"pages button\" id=\"1\">First</a></li>
            </div>
            <div class=\"one mobile third\" style=\"overflow: hidden;\">
                <ul id=\"pagination\">
                    $liHtml
                </ul>
            </div>
            <div class=\"one mobile third\" id=\"last-page\">
                <li class=\"rounded\" id=\"scroll-right\"><a href=\"javascript:void(0)\" class=\"button\" id=\"$totalPages\"><i class=\"icon-double-angle-right\"></i></a></li>
                <li class=\"rounded\" id=\"page\"><a href=\"javascript:void(0)\" class=\"pages button\" id=\"$totalPages\">Last</a></li>
            </div>
        </div>
        ";
        return $pageHtml;
    }

    private function getRowWidth() {
        switch ($this->SearchData['TotalPages']) {
            case 1:
                $rowWidth = "width: 22%;";
                break;
            case 2:
                $rowWidth = "width: 22%;";
                break;
            case 3:
                $rowWidth = "width: 22.5%;";
                break;
            case 4:
                $rowWidth = "width: 30.5%;";
                break;
            case 5:
                $rowWidth = "width: 38.5%;";
                break;
            case 6:
                $rowWidth = "width: 46.5%;";
                break;
            case 7:
                $rowWidth = "width: 54%;";
                break;
            case 8:
                $rowWidth = "width: 61.5%;";
                break;
            case 9:
                $rowWidth = "width: 69.5%;";
                break;
            case 10:
                $rowWidth = "width: 79.5%;";
                break;
            case 11:
                $rowWidth = "width: 88.5%;";
                break;
            case 12:
                $rowWidth = "width: 98.5%;";
                break;
            default:
                $rowWidth = "width: 100%;";
        }
        return $rowWidth;
    }

   private function setSearchFields() {
       if (!isset($_SESSION['searchFields'])) { // the search form was just submitted and must be validated
           foreach ($_POST AS $key => $value) {
               $this->SearchFields[$key] = $value;
           }   // set the class search fields
           $_SESSION['searchFields'] = $this->SearchFields; // save searchFields in session for future ajax requests from the foundb.php page
           return true;
       } else {
           $this->SearchFields = $_SESSION['searchFields']; // this is an ajax request
       }

        return false;
   }

    private function invalidateOrder($orderId, $orderStatus) {
        if (!is_bool($this->AdminUser) && $this->AdminUser instanceof AdminUser) {
            if ($this->AdminUser->hasAdminSetting(1)) {

                if ($orderStatus != 0) {
                    $input = array(
                        "orderStatus" => $orderStatus,
                        "orderId" => $orderId,
                        "adminUserId" => $this->AdminUser->idUsers,
                        "userId" => $this->User->idUsers,
                        "Ip" => $this->getIpAddress(),
                        "Conn" => $this->Conn,
                        "email" => $this->User->email,
                        "userTypeId" => $this->User->typeId
                    );

                    OrderSearchDAO::invalidateOrder($input);
                }  else {
                    // cancel a web order
                    require_once 'DAOS/EntryOrderDAO.php';

                    EntryOrderDAO::cancelWebOrder($orderId, $this->User->idUsers, $this->getIpAddress(), $this->User->typeId);
                }
            } else {
                error_log(
                    "Web admin id " . $this->AdminUser->idUsers .
                    " attempted to invalidate order id " . $orderId .
                    " without having the authority to invalidate orders."
                );
            }
        } else {
            error_log(
                "Could not find admin id while attempting to invalidate an order on . " . date("m/d/Y H:i:s") . ".
                Order id: " . $orderId . ",
                Web user id: " . $this->User->idUsers
            );
        }
    }
}
?>