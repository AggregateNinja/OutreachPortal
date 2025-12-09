<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 6/1/15
 * Time: 3:27 PM
 */
if (!isset($_SESSION)) {
    session_start();
}

require_once 'LabstaffPageClient.php';
require_once 'DAOS/OrderSearchDAO.php';
require_once 'DAOS/LabstaffOrderSearchDAO.php';
class LabstaffEnquiryClient extends LabstaffPageClient {

    private $OSDao;
    private $Orders;
    private $LSDAO;
    #private $TotalOrders;

    private $SearchData = array (
        "MaxRows" => 10,
        "Offset" => 0,
        "OrderBy" => "orderDate",
        "Direction" => "desc",
        "CurrentPage" => 1,
        "TotalOrders" => 0,
        "TotalPages" => 1
    );

    public function __construct(array $data = null) {
        parent::__construct($data);

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->SearchData)) {
                $this->SearchData[$key] = $value;
            }
        }

        $this->addScript("js/script.js");
        $this->addScript("js/validate.js");
        $this->addScript("/js/pagination.js");
        $this->addStylesheet("css/styles.css");

        /*
        $this->OSDao = new OrderSearchDAO($data, null, $this->Conn);
        $this->Orders = $this->OSDao->getResultSearch(array(
            "OrderBy" => $this->SearchData['OrderBy'],
            "Direction" => $this->SearchData['Direction'],
            "Offset" => $this->SearchData['Offset'],
            "MaxRows" => $this->SearchData['MaxRows'],
            "Ip" => $this->Ip,
            "WithExtendedInfo" => true
        ));*/

        $this->OSDao = new LabstaffOrderSearchDAO($data, $this->Conn);
        $this->Orders = $this->OSDao->getResultSearch(array(
            "OrderBy" => $this->SearchData['OrderBy'],
            "Direction" => $this->SearchData['Direction'],
            "Offset" => $this->SearchData['Offset'],
            "MaxRows" => $this->SearchData['MaxRows'],
            "Ip" => $this->Ip,
            "WithExtendedInfo" => true
        ));

        $this->SearchData['TotalOrders'] = $this->OSDao->TotalOrders;
    }


    public function printPage(array $settings = null) {

        //$this->SearchData['TotalOrders'] = $this->OSDao->TotalOrders;

        $this->setTotalPages();
        $this->setOffset();

        $ordersHtml = $this->getOrdersHtml();

        $html = "
        <div class=\"modal bottom-sheet\" id=\"modal\">
            <div class=\"modal-content\">
                <main>
                    <div class=\"container\" >
                        <div class=\"row\">
                            <form class=\"col s12\" method=\"post\" action=\"indexb.php\" name=\"frmSearch\" id=\"frmSearch\">

                                <fieldset>
                                    <div class=\"row\">
                                        <div class=\"col s12\">
                                            <div class=\"section\">
                                                <p class=\"caption\">Patient Fields</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class=\"row\">
                                        <div class=\"input-field col s6\">
                                            <input id=\"patientFirstName\" name=\"patientFirstName\" type=\"text\" />
                                            <label for=\"patientFirstName\">First Name</label>
                                        </div>
                                        <div class=\"input-field col s6\">
                                            <input id=\"patientLastName\" name=\"patientLastName\" type=\"text\">
                                            <label for=\"patientLastName\">Last Name</label>
                                        </div>
                                    </div>
                                    <div class=\"row\">
                                        <div class=\"input-field col s6\">
                                            <input id=\"patientId\" name=\"patientId\" type=\"text\">
                                            <label for=\"patientId\">Id #</label>
                                        </div>
                                        <div class=\"input-field col s6\">
                                            <input id=\"patientDob\" name=\"patientDob\" type=\"text\" class=\"datepicker\">
                                            <label for=\"patientDob\" class=\"active\">Date of Birth</label>
                                        </div>
                                    </div>
                                </fieldset>

                                <fieldset>
                                    <div class=\"row\">
                                        <div class=\"col s12\">
                                            <div class=\"section\">
                                                <p class=\"caption\">Doctor Fields</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class=\"row\">
                                        <div class=\"input-field col s6\">
                                            <input id=\"doctorFirstName\" name=\"doctorFirstName\" type=\"text\">
                                            <label for=\"doctorFirstName\">First Name</label>
                                        </div>
                                        <div class=\"input-field col s6\">
                                            <input id=\"doctorLastName\" name=\"doctorLastName\" type=\"text\">
                                            <label for=\"doctorLastName\">Last Name</label>
                                        </div>
                                    </div>
                                </fieldset>

                                <fieldset>
                                    <div class=\"row\">
                                        <div class=\"col s12\">
                                            <div class=\"section\">
                                                <p class=\"caption\">Client Fields</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class=\"row\">
                                        <div class=\"input-field col s6\">
                                            <input id=\"clientName\" name=\"clientName\" type=\"text\">
                                            <label for=\"clientName\">Client Name</label>
                                        </div>
                                        <div class=\"input-field col s6\">
                                            <input id=\"clientNo\" name=\"clientNo\" type=\"text\">
                                            <label for=\"clientNo\">Client Number</label>
                                        </div>
                                    </div>
                                </fieldset>

                                <fieldset>
                                    <div class=\"row\">
                                        <div class=\"col s12\">
                                            <div class=\"section\">
                                                <p class=\"caption\">Result Fields</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class=\"row\">
                                        <div class=\"input-field col s6\">
                                            <input id=\"orderDateFrom\" name=\"orderDateFrom\" type=\"text\" class=\"datepicker\">
                                            <label for=\"orderDateFrom\" class=\"active\">Order Date From</label>
                                        </div>
                                        <div class=\"input-field col s6\">
                                            <input id=\"orderDateTo\" name=\"orderDateTo\" type=\"text\" class=\"datepicker\">
                                            <label for=\"orderDateTo\" class=\"active\">Order Date To</label>
                                        </div>
                                    </div>
                                    <div class=\"row\">
                                        <div class=\"input-field col s6\">
                                            <input id=\"specimenDateFrom\" name=\"specimenDateFrom\" type=\"text\" class=\"datepicker\">
                                            <label for=\"specimenDateFrom\" class=\"active\">Specimen Date From</label>
                                        </div>
                                        <div class=\"input-field col s6\">
                                            <input id=\"specimenDateTo\" name=\"specimenDateTo\" type=\"text\" class=\"datepicker\">
                                            <label for=\"specimenDateTo\" class=\"active\">Specimen Date To</label>
                                        </div>
                                    </div>
                                    <div class=\"row\">
                                        <div class=\"input-field col s6\">
                                            <input id=\"reportedDateFrom\" name=\"reportedDateFrom\" type=\"text\" class=\"datepicker\">
                                            <label for=\"reportedDateFrom\" class=\"active\">Reported Date From</label>
                                        </div>
                                        <div class=\"input-field col s6\">
                                            <input id=\"reportedDateTo\" name=\"reportedDateTo\" type=\"text\" class=\"datepicker\">
                                            <label for=\"reportedDateTo\" class=\"active\">Reported Date To</label>
                                        </div>
                                    </div>
                                    <div class=\"row\">
                                        <div class=\"input-field col s6\">
                                            <input id=\"approvedDateFrom\" name=\"approvedDateFrom\" type=\"text\" class=\"datepicker\">
                                            <label for=\"approvedDateFrom\" class=\"active\">Approved Date From</label>
                                        </div>
                                        <div class=\"input-field col s6\">
                                            <input id=\"approvedDateTo\" name=\"approvedDateTo\" type=\"text\" class=\"datepicker\">
                                            <label for=\"approvedDateTo\" class=\"active\">Approved Date To</label>
                                        </div>
                                    </div>
                                    <div class=\"row\">
                                        <div class=\"input-field col s6\">
                                            <input id=\"accession\" name=\"accession\" type=\"text\">
                                            <label for=\"accession\" >Accession</label>
                                        </div>
                                        <div class=\"input-field col s6\">
                                            <div class=\"row\">
                                                <div class=\"col s6\">
                                                    <input type=\"checkbox\" class=\"filled-in\" id=\"inconsistentOnly\" name=\"inconsistentOnly\" value=\"1\" />
                                                    <label for=\"inconsistentOnly\">Inconsistent Only</label>
                                                </div>
                                                <div class=\"col s6\">
                                                    <input type=\"checkbox\" class=\"filled-in\" id=\"consistentOnly\" name=\"consistentOnly\" value=\"1\" />
                                                    <label for=\"consistentOnly\">Consistent Only</label>
                                                </div>
                                            </div>

                                            <div class=\"row\">
                                                <div class=\"col s6\">
                                                    <input type=\"checkbox\" class=\"filled-in\" id=\"completeOnly\" name=\"completeOnly\" value=\"1\" />
                                                    <label for=\"completeOnly\">Complete Only</label>
                                                </div>
                                                <div class=\"col s6\">
                                                    <input type=\"checkbox\" class=\"filled-in\" id=\"incompleteOnly\" name=\"incompleteOnly\" value=\"1\" />
                                                    <label for=\"incompleteOnly\">Incomplete Only</label>
                                                </div>
                                            </div>

                                            <div class=\"row\">
                                                <div class=\"col s6\">
                                                    <input type=\"checkbox\" class=\"filled-in\" id=\"translationalOnly\" name=\"translationalOnly\" value=\"1\" />
                                                    <label for=\"translationalOnly\">Translational Only</label>
                                                </div>
                                                <div class=\"col s6\">
                                                    <input type=\"checkbox\" class=\"filled-in\" id=\"sinceLastLogin\" name=\"sinceLastLogin\" value=\"1\" />
                                                    <label for=\"sinceLastLogin\">Since Last Login</label>
                                                </div>
                                            </div>

                                            <div class=\"row\">
                                                <div class=\"col s6\">
                                                    <input type=\"checkbox\" class=\"filled-in\" id=\"invalidatedOnly\" name=\"invalidatedOnly\" value=\"1\" />
                                                    <label for=\"invalidatedOnly\">Invalidated Only</label>
                                                </div>
                                                <div class=\"col s6\">
                                                    <input type=\"checkbox\" class=\"filled-in\" id=\"pastTwentyFourHours\" name=\"pastTwentyFourHours\" value=\"1\" checked/>
                                                    <label for=\"pastTwentyFourHours\">Completed Past 24 Hours</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>

                                <div class=\"row\">
                                    <div class=\"input-field col s6\">
                                       <a href=\"javascript:void(0)\" class=\"waves-effect waves-light btn-large\" id=\"btnSubmit\" name=\"btnSubmit\">Submit</a>
                                       <div id=\"error\"></div>
                                    </div>
                                    <div class=\"col s6\">

                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <main>
        <div class=\"container\">


            $ordersHtml

            <div class=\"row\" style=\"padding-left: 50px;\">
                <div class=\"col s6 m3 l2\" style=\"padding-bottom: 3px;\">
                    <a class=\"waves-effect waves-light modal-trigger btn-large\" href=\"#modal\">Result Search</a>
                </div>
                <div class=\"col s6 m3 l2\" style=\"padding-bottom: 3px;\">
                    <a class=\"waves-effect waves-light btn-large\" href=\"javascript:void(0)\">Archive Orders</a>
                </div>
                <div class=\"col s6 m3 l2\" style=\"padding-bottom: 3px;\">
                    <a class=\"waves-effect waves-light btn-large\" href=\"javascript:void(0)\">Invalidate</a>
                </div>
                <div class=\"col s6 m3 l2\" style=\"padding-bottom: 3px;\">
                    <a class=\"waves-effect waves-light btn-large\" href=\"javascript:void(0)\">View Selected</a>
                </div>
                <div class=\"col s6 m3 l2\" style=\"padding-bottom: 3px;\">
                    <a class=\"waves-effect waves-light btn-large\" href=\"javascript:void(0)\">View Page</a>
                </div>
                <div class=\"col s6 m3 l2\" style=\"padding-bottom: 3px;\">
                    <a class=\"waves-effect waves-light btn-large\" href=\"javascript:void(0)\">View All</a>
                </div>
            </div>
        </div>
        </main>";

        echo $html;
    }

    public function getOrdersHtml() {

        //echo "<pre>"; print_r($this->Orders); echo "</pre>";

        $this->SearchData['TotalOrders'] = $this->OSDao->TotalOrders;
        $totalOrders = $this->SearchData['TotalOrders'];

        $this->setTotalPages();
        $this->setOffset();

        $direction = "desc";
        $tooltipDirection = "Descending";
        if ($this->SearchData['Direction'] == "desc") {
            $direction = "asc";
            $tooltipDirection = "Ascending";
        }
        $maxRows = $this->SearchData['MaxRows'];
        $offset = $this->SearchData['Offset'];
        $orderBy = $this->SearchData['OrderBy'];
        $currentPage = $this->SearchData['CurrentPage'];
        $totalPages = $this->SearchData['TotalPages'];

        $ordersHtml = "
            <div id=\"orders\">
                <div class=\"row\" style=\"margin-top: 10px; margin-bottom: 0; padding-left: 45px;\" >
                    <div class=\"col s12 m9 l9\">
                        <p style=\"font-weight: bold;\" id=\"totalOrders\">$totalOrders result orders found</p>
                    </div>
                    <div class=\"input-field col s12 m3 l3\" style=\"padding-right: 60px;\">
                        <select name=\"amountPerPage\" id=\"amountPerPage\">
                            <option value=\"10\" selected>10</option>
                            <option value=\"20\">20</option>
                            <option value=\"50\">50</option>
                            <option value=\"100\">100</option>
                            <option value=\"200\">200</option>
                            <option value=\"500\">500</option>
                        </select>
                        <label for=\"amountPerPage\">Amount Per Page</label>
                    </div>
                </div>


                <div class=\"row\" style=\"margin-bottom: 5px;\">
                    <div class=\"col s12\" style=\"padding: 0;\">
                        <input type=\"hidden\" name=\"MaxRows\" id=\"MaxRows\" value=\"$maxRows\" />
                        <input type=\"hidden\" name=\"Offset\" id=\"Offset\" value=\"$offset\" />
                        <input type=\"hidden\" name=\"OrderBy\" id=\"OrderBy\" value=\"$orderBy\" />
                        <input type=\"hidden\" name=\"Direction\" id=\"Direction\" value=\"$direction\" />
                        <input type=\"hidden\" name=\"CurrentPage\" id=\"CurrentPage\" value=\"$currentPage\" />
                        <input type=\"hidden\" name=\"TotalPages\" id=\"TotalPages\" value=\"$totalPages\" />
                        <input type=\"hidden\" name=\"TotalOrders\" id=\"TotalOrders\" value=\"$totalOrders\" />
        ";

        if ($this->SearchData['TotalOrders'] > 0) {
            $ordersHtml .= "
                <ul class=\"collapsible popout\" id=\"ul-results\" data-collapsible=\"accordion\">
                    <li style=\"border-bottom: 1px solid #DDDDDD;\">
                        <div class=\"row\" id=\"table-header\">
                            <div class=\"col s1\" style=\"text-align: center;\">
                                <input type=\"checkbox\" name=\"checkAll\" id=\"checkAll\" class=\"filled-in\" />
                                <label for=\"checkAll\" data-delay=\"100\" data-tooltip=\"Check All\" class=\"tooltipped\"></label>
                            </div>
                            <div class=\"col s2 m2 l1 truncate\" style=\"padding: 10px 0 0 0;\">Accession
                                <a href=\"javascript:void(0)\" id=\"accession\" class=\"sort tooltipped\" data-direction=\"$direction\" data-delay=\"100\" data-tooltip=\"Accession $tooltipDirection\">
                                    <i class=\"mdi-navigation-unfold-more\"></i></a></div>
                            <div class=\"col s3 m3 l2  truncate\">Doctor (#)
                                <a href=\"javascript:void(0)\" id=\"doctorLastName\" class=\"sort tooltipped\" data-direction=\"$direction\" data-delay=\"100\" data-tooltip=\"Doctor Last Name $tooltipDirection\">
                                    <i class=\"mdi-navigation-unfold-more\"></i></a></div>
                            <div class=\"col s2 truncate hide-on-med-and-down\">Client (#)
                                <a href=\"javascript:void(0)\" id=\"clientName\" class=\"sort tooltipped\" data-direction=\"$direction\" data-delay=\"100\" data-tooltip=\"Client Name $tooltipDirection\">
                                    <i class=\"mdi-navigation-unfold-more\"></i></a></div>
                            <div class=\"col s3 m3 l2  truncate\">Patient (#)
                                <a href=\"javascript:void(0)\" id=\"patientLastName\" class=\"sort tooltipped\" data-direction=\"$direction\" data-delay=\"100\" data-tooltip=\"Patient Last Name $tooltipDirection\">
                                    <i class=\"mdi-navigation-unfold-more\"></i></a></div>
                            <div class=\"col s3 m3 l2 truncate\">Order Date
                                <a href=\"javascript:void(0)\" id=\"orderDate\" class=\"sort tooltipped\" data-direction=\"$direction\" data-delay=\"100\" data-tooltip=\"Order Date $tooltipDirection\">
                                    <i class=\"mdi-navigation-unfold-more\"></i></a></div>
                            <div class=\"col s2 truncate hide-on-med-and-down\">Specimen Date
                                <a href=\"javascript:void(0)\" id=\"specimenDate\" class=\"sort tooltipped\" data-direction=\"$direction\" data-delay=\"100\" data-tooltip=\"Specimen Date $tooltipDirection\">
                                    <i class=\"mdi-navigation-unfold-more\"></i></a></div>
                        </div>
                    </li>
            ";

            foreach ($this->Orders as $currOrder) {

                $hasExtendedInfo = false;

                $prescribedDrugsHtml = "";
                $prescribedDetectedHtml  = "";
                $prescribedNotDetectedHtml  = "";
                $notPrescribedDetectedHtml = "";

                $prescribedDrugs = $currOrder->PrescribedDrugs;
                $prescribedDetectedDrugs = $currOrder->PrescribedDetected;
                $prescribedNotDetectedDrugs = $currOrder->PrescribedNotDetected;
                $notPrescribedDetectedDrugs = $currOrder->NotPrescribedDetected;

                if (count($prescribedDrugs) > 0) {
                    $hasExtendedInfo = true;
                    $prescribedDrugsHtml = "<ul class=\"collection with-header extendedInfo\"><li class=\"collection-item\"><b>Prescribed Drugs</b></li>";
                    foreach ($prescribedDrugs as $prescribedDrug) {
                        $prescribedDrugsHtml .= "<li class=\"collection-item\">" . $prescribedDrug . "</li>";
                    }
                    $prescribedDrugsHtml .= "</ul>";
                }

                if (count($prescribedDetectedDrugs) > 0) {
                    $hasExtendedInfo = true;
                    $prescribedDetectedHtml = "<ul class=\"collection with-header extendedInfo\"><li class=\"collection-item\"><b>Prescribed Detected</b></li>";
                    foreach ($prescribedDetectedDrugs as $prescribedDetected) {
                        $prescribedDetectedHtml .= "<li class=\"collection-item\">" . $prescribedDetected . "</li>";
                    }
                    $prescribedDetectedHtml .= "</ul>";
                }

                if (count($prescribedNotDetectedDrugs) > 0) {
                    $hasExtendedInfo = true;
                    $prescribedNotDetectedHtml = "<ul class=\"collection with-header extendedInfo\"><li class=\"collection-item\"><b>Prescribed Not Detected</b></li>";
                    foreach ($prescribedNotDetectedDrugs as $prescribedNotDetected) {
                        $prescribedNotDetectedHtml .= "<li class=\"collection-item\">" . $prescribedNotDetected . "</li>";
                    }
                    $prescribedNotDetectedHtml .= "</ul>";
                }


                if (count($notPrescribedDetectedDrugs) > 0) {
                    $hasExtendedInfo = true;
                    $notPrescribedDetectedHtml = "<ul class=\"collection with-header extendedInfo\"><li class=\"collection-item\"><b>Not Prescribed Detected</b></li>";
                    foreach ($notPrescribedDetectedDrugs as $notPrescribedDetected) {
                        $notPrescribedDetectedHtml .= "<li class=\"collection-item\">" . $notPrescribedDetected . "</li>";
                    }
                    $notPrescribedDetectedHtml .= "</ul>";
                }

                $orderCount = $currOrder->OrderCount;
                $reportedCount = $currOrder->ReportedCount;
                $approvedCount = $currOrder->ApprovedCount;
                $printAndTransmittedCount = $currOrder->PrintAndTransmittedCount;
                $inconsistentCount = $currOrder->InconsistentCount;
                $abnormalCount = $currOrder->AbnormalCount;
                $invalidatedCount = $currOrder->InvalidatedCount;

                $extendedInfoHtml = "";
                if ($hasExtendedInfo) {
                    $extendedInfoHtml = $prescribedDrugsHtml . $prescribedDetectedHtml . $prescribedNotDetectedHtml . $notPrescribedDetectedHtml;
                }

                $extendedInfoHtml .= "
                    <ul class=\"collection with-header extendedInfo orderRow\">
                        <li class=\"collection-item\"><b>Order Totals</b></li>
                        <li class=\"collection-item\">Order Count: $orderCount</li>
                        <li class=\"collection-item\">Reported Count: $reportedCount</li>
                        <li class=\"collection-item\">Approved Count: $approvedCount</li>
                        <li class=\"collection-item\">Transmitted Count: $printAndTransmittedCount</li>
                        <li class=\"collection-item\">Inconsistent Count: $inconsistentCount</li>
                        <li class=\"collection-item\">Abnormal Count: $abnormalCount</li>
                        <li class=\"collection-item\">Invalidated Count: $invalidatedCount</li>
                    </ul>
                ";

                $client = $currOrder->Client->clientName . " (" . $currOrder->Client->clientNo . ")";
                //$patient = $currOrder->Patient->firstName . " " . $currOrder->Patient->lastName;
                $patient = $currOrder->Patient->firstName . " " . $currOrder->Patient->lastName . " (" . $currOrder->Patient->arNo . ")";
                $orderDate = date("m/d/Y h:i:s A", strtotime($currOrder->orderDate));
                $specimenDate = date("m/d/Y h:i:s A", strtotime($currOrder->specimenDate));
                $orderStatus = $currOrder->OrderStatus;
                $doctor = "";
                if (isset($currOrder->Doctor) && $currOrder->Doctor != null && $currOrder->Doctor instanceof DoctorUser) {
                    $lastName = $currOrder->Doctor->lastName;
                    $firstName = $currOrder->Doctor->firstName;

                    if (!empty($lastName) || !empty($firstName)) {
                        $doctor = $firstName . " " . $lastName . " (" . $currOrder->Doctor->number . ")";
                    }
                }

                $ordersHtml .= "
                    <li>
                        <div class=\"collapsible-header\">
                            <!--<i class=\"mdi-image-filter-drama\"></i>-->
                            <div class=\"row hoverable\" style=\"overflow: hidden; padding: 0;\">
                                <div class=\"col s1 truncate\" style=\"text-align: center\">
                                    <input type=\"checkbox\" name=\"action[]\" id=\"$currOrder->idOrders\" class=\"action filled-in\" value=\"$currOrder->idOrders\" />
                                    <label for=\"action\"></label>
                                </div>
                                <div class=\"col s2 m2 l1 truncate\">$currOrder->accession</div>
                                <div class=\"col s3 m3 l2 truncate\">$doctor</div>
                                <div class=\"col s2 truncate hide-on-med-and-down\">$client</div>
                                <div class=\"col s3 m3 l2 truncate\">$patient</div>
                                <div class=\"col s3 m3 l2 truncate\">$orderDate</div>
                                <div class=\"col s2 truncate hide-on-med-and-down\">$specimenDate</div>
                            </div>
                        </div>
                        <div class=\"collapsible-body\"><p>
                            $extendedInfoHtml
                        </p></div>
                    </li>
                ";
            }

            $ordersHtml .= "</ul>";
            $ordersHtml .= "<input type=\"hidden\" name=\"currentPage\" id=\"currentPage\" value=\"" . $this->SearchData['CurrentPage'] . "\" />";
        } else {
            $ordersHtml .= "No orders found";
        }

        $ordersHtml .= "
                </div>
            </div>";

        $ordersHtml .= $this->setPagination();

        return $ordersHtml;
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
        if ($this->SearchData['TotalOrders'] == 0) {
            $this->SearchData['TotalPages'] = 1;
        }
    }

    private function setPagination() {
        //echo "<pre style=\"margin-left: 300px;\">"; print_r($this->SearchData); echo "</pre>";
        $currentPage = $this->SearchData['CurrentPage'];
        $totalPages = $this->SearchData['TotalPages'];

        $liHtml = "";

        $start = 1;
        $end = 5;
        if ($currentPage > 3) {
            $start = $currentPage - 2;

            if ($currentPage < $totalPages - 2) {
                $end = $currentPage + 2;
            }
        } else if ($totalPages == 1) {
            $end = 1;
        }


        for($i = $start; $i <= $end; $i ++) {
            $liClass = "";
            if ($i == $currentPage) {
                $liClass = "active";
            } else {
                $liClass = "waves-effect";
            }

            $liHtml .= "<li class=\"$liClass\" data-page=\"$i\"><a href=\"#!\">$i</a></li>";
        }

        $prevPage = $currentPage - 1;
        $nextPage = $currentPage + 1;
        //if ($currentPage == 1) { $prevPage = 1; }
        //if ($currentPage == $totalPages) { $nextPage = $totalPages; }

        $pageHtml = "
            <div class=\"row\" style=\"margin-bottom: 5px;\">
                <div class=\"one whole centered\">
                    <p id=\"total\" style=\"font-weight: bold; text-align: center;\">
                        Page $currentPage of $totalPages
                    </p>
                 </div>
            </div>

            <div class=\"row\" style=\"margin-bottom: 5px;\">
                <div class=\"col s12\" style=\"text-align: center;\">
                    <ul class=\"pagination center-align\">
                        <li class=\"waves-effect tooltipped\" data-delay=\"100\" data-tooltip=\"Previous Page\" data-page=\"$prevPage\" id=\"prevPage\"><a href=\"#!\"><i class=\"mdi-navigation-chevron-left\"></i></a></li>
                        $liHtml
                        <li class=\"waves-effect tooltipped\" data-delay=\"100\" data-tooltip=\"Next Page\" data-page=\"$nextPage\" id=\"nextPage\"><a href=\"#!\"><i class=\"mdi-navigation-chevron-right\"></i></a></li>
                    </ul>
                </div>
            </div>
        </div>
        ";

        return $pageHtml;
    }

    public function __get($field) {
        $value = parent::__get($field);

        if ($value == "") {
            if ($field == "Orders") {
                $value = $this->Orders;
            } else if (array_key_exists($field, $this->SearchData)) {
                $value = $this->SearchData[$field];
            }
        }
        return $value;
    }

}