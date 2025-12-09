<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 1/24/2017
 * Time: 2:19 PM
 */
require_once 'PageClient.php';
require_once 'IClient.php';
require_once 'DAOS/AdminDAO.php';

class AdminSettingsClient extends PageClient implements IClient {
    private $PageTitle = "Edit Common Tests";
    private $action = 1;

    private $SelectedCommonTests = array();
    private $SelectedExcludedTests = array();
    private $Users = array();
    private $UserId = "";
    private $AdminDAO;

    public function __construct(array $data = null) {
        parent::__construct();

        $this->addStylesheet("css/manage.css");
        $this->addScript("js/orderentry.js");

        if (array_key_exists("id", $_SESSION)) {
            $this->UserId = $_SESSION['id'];
        }

        $this->AdminDAO = new AdminDAO(null, array("Conn" => $this->Conn));

        $this->Users = $this->AdminDAO->getUsers();

        //echo "<pre>"; print_r($this->Users); echo "</pre>";

        if (isset($_GET['action'])) {
            $this->action = $_GET['action'];
            if ($this->action == 2) {
                $this->PageTitle = "Edit Excluded Tests";
            }
        }
    }

    public function printPage () {
        $html = "
        <input type='hidden' name='idUsers' id='idUsers' value='" . $this->UserId . "' />
        <div class='container'>
            <div class='row'>
                <div class='one mobile whole padded centered'>
                    <h5>$this->PageTitle</h5>
                </div>
            </div>
        ";

        if ($this->action != 1) {
            $html .= $this->getExcludedTestsHtml();
        } else {
            $html .= $this->getCommonTestsHtml();
        }

        //$html .= $this->getUsersHtml();

        $html .= "</div>";

        echo $html;
    }

    private function getUsersHtml() {
        $usersHtml = "<div class='row'>
                <div class='one mobile whole padded'>";

        $aryClientUsers = $this->Users['Clients'];
        $aryDoctorUsers = $this->Users['Doctors'];

        if (count($aryClientUsers) > 0) {
            foreach ($aryClientUsers as $clientUser) {
                $email = $clientUser->email;
                $userType = $clientUser->typeName;
                $usersHtml .= "
                    <div class='row'>
                        <div class='one mobile fourth'>$email</div>
                        <div class='one mobile fourth'>$userType</div>
                    </div>";
            }
        }

        if (count($aryDoctorUsers) > 0) {
            foreach ($aryDoctorUsers as $doctorUser) {
                $email = $doctorUser->email;
                $userType = $doctorUser->typeName;
                $usersHtml .= "
                    <div class='row'>
                        <div class='one mobile fourth'>$email</div>
                        <div class='one mobile fourth'>$userType</div>
                    </div>";
            }
        }

        $usersHtml .= "</div></div>";

        return $usersHtml;
    }

    private function getCommonTestsHtml() {
        $testsHtml = "";
        if (count($this->SelectedCommonTests) > 0) {
            $testsHtml .= "
                <div class='row' id='selectedCommonTestsHeader'>
                    <div class='two mobile sevenths'>Test Name</div>
                    <div class='two mobile sevenths'>Department</div>
                    <div class='two mobile sevenths'>Specimen Type</div>
                    <div class='one mobile seventh'>Remove</div>
                </div>";

            foreach($this->SelectedCommonTests as $currTest) {
                if ($currTest instanceof Test) {
                    $testNumber = $currTest->number;
                    $testName = $currTest->name;
                    $department = $currTest->deptName;
                    $specimenType = $currTest->specimenTypeName;

                    $testsHtml .= "
                    <div class='row selectedCommonTests'>
                        <div class='two mobile sevenths'>$testName</div>
                        <div class='two mobile sevenths'>$department</div>
                        <div class='two mobile sevenths'>$specimenType</div>
                        <div class='one mobile seventh'>
                            <a href='javascript:void(0)' class='removeTests' id='$testNumber'><i class='icon icon-trash'></i></a>
                        </div>
                    </div>";
                }
            }
        }

        $commonTestsHtml = "
            <div class='row' id='commonTestsRow'>
                <div class='one mobile half'>
                    <h5 title='Search for common tests to be added for this user.'>
                        Common Tests</h5>
                    <div class='row pad-right'>
                        <div class='five mobile sevenths pad-right'>
                            <label for='commonTestName'>Test Name</label>
                            <input type='text' name='commonTestName' id='commonTestName' class='testInput' autocomplete='off' tabindex='50' />
                        </div>
                        <div class='two mobile sevenths'>
                            <label for='commonTestNumber'>Test Number</label>
                            <input type='number' name='commonTestNumber' id='commonTestNumber' class='testInput' tabindex='51' />
                        </div>
                        <div class='one mobile whole'>
                            <div id='commonTestsResults' class='box_shadow searchResults'></div>
                        </div>
                    </div>
                </div>
                <div class='one mobile half pad-left'>
                    <label for='selectedCommonTests'>Selected Common Tests: </label>
                    <div class='row'>
                        <div class='one mobile whole' id='selectedCommonTestsContainer'>
                            $testsHtml
                        </div>
                    </div>
                </div>
            </div>
        ";



        return $commonTestsHtml;
    }

    private function getExcludedTestsHtml() {
        $testsHtml = "";
        if (count($this->SelectedExcludedTests) > 0) {
            $testsHtml .= "
                <div class='row' id='selectedExcludedTestsHeader'>
                    <div class='two mobile sevenths'>Test Name</div>
                    <div class='two mobile sevenths'>Department</div>
                    <div class='two mobile sevenths'>Specimen Type</div>
                    <div class='one mobile seventh'>Remove</div>
                </div>";

            foreach($this->SelectedExcludedTests as $currTest) {
                $testNumber = $currTest->number;
                $testName = $currTest->name;
                $department = $currTest->deptName;
                $specimenType = $currTest->specimenTypeName;

                $testsHtml .= "
                    <div class='row selectedExcludedTests'>
                        <div class='two mobile sevenths'>$testName</div>
                        <div class='two mobile sevenths'>$department</div>
                        <div class='two mobile sevenths'>$specimenType</div>
                        <div class='one mobile seventh'>
                            <a href='javascript:void(0)' class='removeTests' id='$testNumber'><i class='icon icon-trash'></i></a>
                        </div>
                    </div>";
            }
        }

        $excludedTestsHtml = "
            <div class='row' id='excludedTestsRow'>
                <div class='one mobile half'>
                    <h5 title='Search for excluded tests to be added for this user.'>
                        Excluded Tests</h5>
                    <div class='row pad-right'>
                        <div class='five mobile sevenths pad-right'>
                            <label for='excludedTestName'>Test Name</label>
                            <input type='text' name='excludedTestName' id='excludedTestName' class='testInput' autocomplete='off' tabindex='52' />
                        </div>
                        <div class='two mobile sevenths'>
                            <label for='excludedTestNumber'>Test Number</label>
                            <input type='number' name='excludedTestNumber' id='excludedTestNumber' class='testInput' tabindex='53' />
                        </div>
                        <div class='one mobile whole'>
                            <div id='excludedTestsResults' class='box_shadow searchResults'></div>
                        </div>
                    </div>
                </div>
                <div class='one mobile half pad-left'>
                    <label for='selectedExcludedTests'>Selected Excluded Tests: </label>
                    <div class='row'>
                        <div class='one mobile whole' id='selectedExcludedTestsContainer'>
                            $testsHtml
                        </div>
                    </div>
                </div>
            </div>";


        return $excludedTestsHtml;
    }

}