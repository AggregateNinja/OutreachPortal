<?php
require_once 'PageClient.php';
require_once 'IClient.php';
require_once 'DAOS/CumulativeDAO.php';
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 4/25/2016
 * Time: 4:04 PM
 */
class CumulativeClient extends PageClient implements IClient
{
    private $idPatients;
    private $specimenDate;
    private $reportType;

    private $CumulativeDAO;

    private $Cumulative;

    private $MaxDisplay = 30; // Number of orders to display on the page
    private $Start; // Start position
    private $End; // End Position
    private $NumOrders; // Number of orders actually displaying on the page
    private $TotalOrders; // Total number of orders
    private $DistinctTests;
    private $NumDistinctTests;

    public $StartDate;
    
    public function __construct($idPatients, $specimenDate, $reportType) {
        parent::__construct();

        $this->idPatients = $idPatients;
        $this->specimenDate = $specimenDate;
        $this->reportType = $reportType;



        $this->StartDate = new DateTime($specimenDate);
        $this->StartDate->sub(new DateInterval('P2M')); // P2M = Period 2 Months

        $this->addStylesheet("css/cumulative.css");
        $this->addScript("https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js");
        $this->addScript("js/cumulative.js");
        $this->addScript("js/jquery.fittext.js");
        $this->addScript("/outreach/js/velocity.min.js");
        $this->addScript("/outreach/js/tooltip.js");

        $this->CumulativeDAO = new CumulativeDAO($this->Conn);
        
        $tmpCumulative = $this->CumulativeDAO->getCumulative($this->idPatients, $this->specimenDate, $this->Ip, $this->User);

        //echo "<pre>"; print_r($this->Cumulative); echo "</pre>";

        if (!is_bool($tmpCumulative)) { // orders and tests were found for this patient

            $this->TotalOrders = count($tmpCumulative->ResultOrders);
            $cumulative = new Cumulative();

            if (isset($_GET['start']) && !empty($_GET['start']) && is_numeric($_GET['start'])) {
                if ($_GET['start'] < $this->MaxDisplay) {
                    $this->Start = 0;
                } else {
                    $this->Start = $_GET['start'] - $this->MaxDisplay;
                }
                $this->End = $_GET['start'];

            } else if ($this->TotalOrders >= $this->MaxDisplay) {
                $this->Start = $this->TotalOrders - $this->MaxDisplay;
                $this->End = $this->Start + $this->MaxDisplay;
                if ($this->End > $this->TotalOrders) {
                    $this->End = $this->TotalOrders;
                }
            } else {
                $this->Start = 0;
                $this->End = $this->TotalOrders;
            }

            /*$k = 1;
            for ($j = count($tmpCumulative->ResultOrders) - 1; $j >= 0; $j--) {
                $order = $tmpCumulative->ResultOrders[$j];
                echo $order->accession . ", ";
                if ($k % $this->MaxDisplay == 0) {
                    echo "<br>";
                }
                $k++;
            }*/


            for($i = $this->Start; $i < $this->End; $i++) {
            //for($i = 0; $i < $numOrders; $i++) {
                $cumulative->addResultOrder($tmpCumulative->ResultOrders[$i]);
            }

            $this->Cumulative = $cumulative;
            $this->NumOrders = count($this->Cumulative->ResultOrders);
            $this->DistinctTests = $this->Cumulative->getDistinctTests();
            $this->NumDistinctTests = count($this->DistinctTests);

            $cumulativeDates = $this->Cumulative->getDates();
            $patientFirstName = trim(ucfirst(strtolower($cumulative->ResultOrders[0]->firstName)));
            $patientLastName = trim(ucfirst(strtolower($cumulative->ResultOrders[0]->lastName)));

            $aryTestNumbers = $cumulative->getDistinctTests();
            $aryGraphLines = array();
            foreach($aryTestNumbers as $testNumber => $testName) {
                $aryGraphLines[$testNumber] = $cumulative->getGraphLine($testNumber);
            }

            $_SESSION['CumulativeDates'] = $cumulativeDates;
            $_SESSION['PatientFirstName'] = $patientFirstName;
            $_SESSION['PatientLastName'] = $patientLastName;
            $_SESSION['GraphLines'] = $aryGraphLines;

            //echo "<pre>"; print_r($aryGraphLines); echo "</pre>";

            //$_SESSION['cumulative'] = serialize($this->Cumulative);

            if (isset($_SESSION['graphTests']) || (isset($_REQUEST['reset']) && $_REQUEST['reset'] == 1)) {
                $_SESSION['graphTests'] = "";
                unset($_SESSION['graphTests']);
            }
        }
    }

    public function printPage() {
        if ($this->Cumulative instanceof Cumulative) {

            $patient = $this->Cumulative->ResultOrders[0]->Patient;
            $patientName = $patient->getName();
            $patientNumber = $patient->arNo;
            $patientDob = $patient->formatDate($patient->dob, 'Y-m-d H:i:s', 'm/d/Y');
            $patientAge = $patient->calcAge();

            //echo "<pre>"; print_r($patient); echo "</pre>";

            $numOrders = $this->NumOrders;
            $minSpecimenDate = date("M d Y", strtotime($this->Cumulative->ResultOrders[0]->specimenDate));
            $maxSpecimenDate = date("M d Y", strtotime($this->Cumulative->ResultOrders[$numOrders - 1]->specimenDate));

            $tableHeader = $this->getTableHeader();
            $tableBody = $this->getTableBody();

            $logoFileName = self::Logo;

            $labInfo = $this->UserDAO->getLabInfo();

            $labName = $labInfo->labName;
            $labAddress = $labInfo->getAddress();
            $labCityStateZip = $labInfo->getCityStateZip();
            $labPhone = $labInfo->getPhone();
            $labFax = $labInfo->getFax();
            $labDirector = $labInfo->labDirector;


            $_SESSION['UserIds'] = $this->CumulativeDAO->UserIds;

            $reportType = $this->reportType;
            $idPatients = $this->idPatients;
            $specimenDate = urlencode($this->specimenDate);


            $cumulativeMenuHtml = "
            <li class='one mobile fourth rounded_bottom_left' style='border-right: 1px solid #CCCCCC;'>
                <a href='found.php' class='rounded_bottom_left'>Return to search results</a>
            </li>
            <li class='one mobile fourth' style='border-right: 1px solid #CCCCCC;'>
                <a href='cumulative.php?reset=1&reportType=$reportType&idPatients=$idPatients&specimenDate=$specimenDate'>
                Reset Graph</a>
            </li>
            <li class='one mobile fourth' style='border-right: 1px solid #CCCCCC;'>
                <a href='cumulativeb.php?action=2&arNo=" . $this->idPatients . "&specimenDate=" . urlencode($this->specimenDate) . "&logoImageFile=" . self::Logo . "' id='lnkDownloadReport'>Download Report</a>
            </li>
            <li class='one mobile fourth rounded_bottom_right' style='border-right: none;'>
                <a href='cumulativeb.php?action=1&download=1' id='lnkDownload'>Download Graph</a>
            </li>";

            $prev =$this->Start;
            $next = $this->End + $this->MaxDisplay;
            $prevStyle = "";
            $nextStyle = "";
            if ($this->Start == 0) {
                $prevStyle = "display: none;";
            }
            if ($this->End == $this->TotalOrders) {
                $nextStyle = "display: none";
            }

            $html = "
            <div class='container responsive' data-compression='130' id='wrapper'>
                <div class='row'>
                    <div class='three mobile mobile fourths centered'>
                        <nav class='nav rounded_bottom box_shadow' title='' id='viewMenu' role='navigation'>
                            <ul class='row'>
                                $cumulativeMenuHtml
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
            <div class='container'>
                    <div class='row'>
                        <div class='one mobile whole padded'>
                            <div id='loading'><i class='icon-spinner icon-spin icon-4x green'></i></div>
                            <div id='cumulativeContainer'></div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='one mobile third pad-left'>
                            <img src='/outreach/images/$logoFileName' alt='' style='height: 88px;'/>
                        </div>
                        <div class='one mobile third' style='text-align: center;'>
                            <strong>$labName</strong><br/>
                            $labAddress, $labCityStateZip<br/>
                            <strong>Phone:</strong> $labPhone | <strong>Fax:</strong> $labFax<br/>
                            <strong>Lab Director:</strong> $labDirector


                        </div>
                        <div class='one mobile third pad-right'>
                            <p><strong>Patient: </strong>$patientName</p>
                            <p><strong>Patient #: </strong>$patientNumber</p>
                            <p><strong>Date of Birth: </strong>$patientDob</p>
                            <p><strong>Age: </strong>$patientAge</p>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='one mobile whole' id='numOrdersCol'>
                            <a href='cumulative.php?reset=1&reportType=$reportType&idPatients=$idPatients&specimenDate=$specimenDate&start=$prev' id='prevOrders' class='tooltipped' data-position='top' data-tooltip='Show previous orders' style='$prevStyle'><i class='icon icon-double-angle-left' style='margin-right: 0;'></i></a>
                            <h5>Displaying " . ($this->Start + 1) . " - " . $this->End . " of " . $this->TotalOrders . " orders from $minSpecimenDate to $maxSpecimenDate</h5>
                            <a href='cumulative.php?reset=1&reportType=$reportType&idPatients=$idPatients&specimenDate=$specimenDate&start=$next' id='nextOrders' class='tooltipped' data-position='top' data-tooltip='Show next orders' style='$nextStyle'><i class='icon icon-double-angle-right'></i></a>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='one mobile whole' style='overflow: hidden;'>
                            $tableHeader
                            $tableBody
                        </div>
                    </div>
            </div>";
        } else {
            $html = "
            <div class='container responsive' data-compression='130' id='wrapper'><div class='row'><div class='three fourths centered'>
                <nav class='nav rounded_bottom box_shadow' id='viewMenu' role='navigation'>
                    <ul class='row'>
                        <li class='one third right-one' style='border-right: 1px solid #CCCCCC; border-left: 1px solid #CCCCCC;'>
                            <a href='found.php' class='rounded_bottom_left'>Return to search results</a>
                        </li>
                    </ul>
                </nav>
            </div></div></div>
            <div class='container'><div class='row'><div class='one mobile whole padded' style='text-align: center;'>No orders found for this patient</div></div></div>";
        }


        echo $html;
    }

    private function getTableHeader() {

        $tableWidth = 96 * ($this->NumOrders + 2);
        $tableStyle = "max-width: " . $tableWidth . "px; width: " . $tableWidth . "px;";

        $tableHeader = "<table class='responsive' id='tblCumulative' data-max='15' style='$tableStyle'>";
        $headerRow1 = "<tr><th class='fittext'>Accession No:</th>";
        $headerRow2 = "<tr><th class='fittext'>Physician:</th>";
        $headerRow3 = "<tr><th class='fittext'>Received Date:</th>";
        $headerRow4 = "<tr><th class='fittext'>Collection Date:</th>";
        $headerRow5 = "<tr><th class='fittext'>Order Status:</th>";


        //for ($i = 0, $specimenDate =  new DateTime($this->Cumulative->ResultOrders[$i]->specimenDate); $i < $this->NumOrders && $this->StartDate > $specimenDate; $i++, $specimenDate =  new DateTime($this->Cumulative->ResultOrders[$i]->specimenDate)) {
        for ($i = 0; $i < $this->NumOrders; $i++) {

            $currOrder = $this->Cumulative->ResultOrders[$i];

            $orderDate = new DateTime($currOrder->orderDate);
            $specimenDate = new DateTime($currOrder->specimenDate);


            $doctorName = "";
            if (isset($currOrder->Doctor)) {
                $doctorName = $currOrder->Doctor->lastName . ", " . $currOrder->Doctor->firstName;
            }
            $viewUrl = "view.php?reportType=" . $this->reportType . "&idOrders=" . $currOrder->idOrders . "&idPatients=" . $this->idPatients . "&specimenDate=" . urlencode($this->specimenDate);

            $orderStatus = "Incomplete";
            $orderCount = count($currOrder->Results);
            $completeCount = 0;
            foreach ($currOrder->Results as $result) {
                if ($this->CumulativeDAO->RequireCompleted == true && $result->printAndTransmitted == 1) {
                    $completeCount++;
                } else if ($this->CumulativeDAO->RequireCompleted == false && $result->isApproved == 1) {
                    $completeCount++;
                }
            }
            if ($orderCount == $completeCount) {
                $orderStatus = "Final";
            }

            $headerRow1 .= "<th class='fittext'><a title='View Report' class='viewReport' href='$viewUrl'>" . $currOrder->accession . "</a></th>";
            $headerRow2 .= "<th class='fittext'>$doctorName</th>";
            $headerRow3 .= "<th class='fittext'>" . $orderDate->format('m/d/Y h:i A') . "</th>";
            $headerRow4 .= "<th class='fittext'>" . $specimenDate->format('m/d/Y h:i A') . "</th>";
            $headerRow5 .= "<th class='fittext'>$orderStatus</th>";
        }

        $headerRow1 .= "<th></th></tr>";
        $headerRow2 .= "<th></th></tr>";
        $headerRow3 .= "<th></th></tr>";
        $headerRow4 .= "<th></th></tr>";
        $headerRow5 .= "<th class='fittext'>Ref. Range Units</th></tr>";

        $tableHeader .= $headerRow1 . $headerRow2 . $headerRow3 . $headerRow4 . $headerRow5;

        return $tableHeader;
    }

    /*private function getTableBody() {
        $tableBody = "";
        foreach($this->DistinctTests as $number => $name) {
            $tableBody .= "
                <tr><td class='fittext'>
                    <a href='javascript:void(0)' class='view' id='$number'>$name ($number)</a>
                </td>";

            $cutoff = "";
            for ($j = 0; $j < $this->NumOrders; $j++) {
                $currOrder = $this->Cumulative->ResultOrders[$j];

                //$resultText = $currOrder->getResultTextByTestNumber($number);

                $result = $currOrder->getResultByTestNumber($number);
                $resultText = "";
                $remarkText = "";
                if (isset($result)) {
                    $test = $result->Test;
                    $resultText = $result->resultText;
                    $printNormals = $test->printNormals;
                    $units = $test->units;

                    $remarkText = $result->remarkText;

                    if (isset($printNormals) || $printNormals === 0) {
                        $cutoff = $printNormals . " " . $units;
                    }
                }

                $tooltipAttributes = "";
                if (!empty($remarkText)) {
                    $tooltipAttributes = "data-tooltip='$remarkText' class='tooltipped' data-position='top'";
                }

                $tableBody .= "<td $tooltipAttributes>" . $resultText . "</td>";
            }
            $tableBody .= "<td>$cutoff</td></tr>";
        }
        $tableBody .= "</table>";

        return $tableBody;
    }*/

    private function getTableBody() {
        $tableBody = "";
        foreach($this->DistinctTests as $number => $name) {
            $cutoff = "";
            $currTest = "<tr><td class='fittext'>
                    <a href='javascript:void(0)' class='view' id='$number'>$name ($number)</a>
                </td>";
            $printTest = false;
            for ($j = 0; $j < $this->NumOrders; $j++) {
                $currOrder = $this->Cumulative->ResultOrders[$j];

                //$resultText = $currOrder->getResultTextByTestNumber($number);

                $result = $currOrder->getResultByTestNumber($number);
                $resultText = "";
                $remarkText = "";
                if (isset($result)) {
                    $test = $result->Test;
                    $resultText = $result->resultText;
                    $printNormals = $test->printNormals;
                    $units = $test->units;

                    $remarkText = $result->remarkText;

                    if (isset($printNormals) || $printNormals === 0) {
                        $cutoff = $printNormals . " " . $units;
                    }
                }

                $tooltipAttributes = "";
                if (!empty($remarkText)) {
                    $tooltipAttributes = "data-tooltip='$remarkText' class='tooltipped' data-position='top'";
                }
                $currTest .= "<td $tooltipAttributes>" . $resultText . "</td>";

                if (isset($resultText) && !empty($resultText)) {
                    $printTest = true;
                }
            }

            if ($printTest) {
                $tableBody .= "$currTest<td>$cutoff</td></tr>";
            }
        }
        $tableBody .= "</table>";

        return $tableBody;
    }
}