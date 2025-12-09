<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 12/30/14
 * Time: 3:47 PM
 */
if (!isset($_SESSION)) {
    session_start();
}





require_once 'PageClient.php';
require_once 'Utility/IClient.php';
require_once 'DAOS/SalesDAO.php';

class SalesSettingsClient extends PageClient implements IClient {

    private $SalesDAO;

    private $SalesGoals = null;
    private $SalesGoalTypes = null;
    private $SalesGoalIntervals = null;

    private $ViewSettings = false;
    private $AddSetting = false;
    private $EditSetting = false;

    private $Message = "";

    private $InputFields;
    private $ErrorMessages;
    private $FormIsInvalid;

    public function __construct() {
        parent::__construct(array("IncludeDetailedInfo" => true));
        $this->addStylesheet("/outreach/sales/settings/css/settings.css");

        $this->addScript("/outreach/sales/settings/js/settings.js");
        $this->addScript("/outreach/sales/settings/js/validate.js");

        $this->addOverlay("
            <div id=\"deleteConfirm\" class=\"rounded\">
                <div style=\"margin: 0 auto; width: 100%; text-align:center;\">
                <h5 style=\"margin: 0 auto 10px auto;\">Are you sure you would like to delete this sales goal?</h5>
                <a href=\"javascript:void(0)\" id=\"deleteYes\" class=\"button\">Yes</a>
                <a href=\"javascript:void(0)\" id=\"deleteNo\" class=\"button\">No</a>
                </div>
            </div>
            <i class=\"icon-spinner icon-spin icon-4x\" id=\"loading-spinner\"></i>
        ");

        $this->SalesDAO = new SalesDAO();

        $this->FormIsInvalid = false;
        $this->InputFields = null;
        $this->ErrorMessages = null;
        $action = 0;
        if (array_key_exists("InputFields", $_SESSION) && array_key_exists("ErrorMessages", $_SESSION) && !empty($_SESSION['InputFields']) && !empty($_SESSION['ErrorMessages'])) {
            $this->FormIsInvalid = true;
            $this->InputFields = $_SESSION['InputFields'];
            $this->ErrorMessages = $_SESSION['ErrorMessages'];
            $_SESSION['InputFields'] = "";
            $_SESSION['ErrorMessages'] = "";
            unset($_SESSION['InputFields']);
            unset($_SESSION['ErrorMessages']);
            $action = $this->InputFields['action'];
        } else if (isset($_GET['action'])) {
            $action = $_GET['action'];
        }

        if ($_SERVER['PHP_SELF'] == "/sales/settings/index.php" || $_SERVER['PHP_SELF'] == "/outreach/sales/settings/index.php") {
            $this->ViewSettings = true;
            if ($this->User->typeId != 1) {
                $this->SalesGoals = $this->SalesDAO->getSalesGoals(array(
                    "salesgroupId" => $this->User->salesGroup,
                    "sg.isActive" => 1
                ));
            } else {
                $this->SalesGoals = $this->SalesDAO->getSalesGoals(array(
                    "userId" => $this->User->idUsers,
                    "sg.isActive" => 1
                ));
            }

        } else if ($action == 2) {
            $this->EditSetting = true;
            $this->SalesGoalTypes = $this->SalesDAO->getSalesGoalTypes(array("ActiveOnly" => true));
            $this->SalesGoalIntervals = $this->SalesDAO->getSalesGoalIntervals(array("ActiveOnly" => true));
        } else {
            $this->AddSetting = true;
            $this->SalesGoalTypes = $this->SalesDAO->getSalesGoalTypes(array("ActiveOnly" => true));
            $this->SalesGoalIntervals = $this->SalesDAO->getSalesGoalIntervals(array("ActiveOnly" => true));
        }

        $this->setMessage();



    }

    public function printPage() {
        if ($this->ViewSettings == true) {
            $this->printViewPage();
        } else {
            $this->printManagePage();
        }
    }

    private function printManagePage() {
        $title = "Add Sales Group Goal";
        $idGoals = "";
        $goal = "";
        $type = "";
        $interval = "";
        $isDefault = "";
        $action = 1;
        $isAdmin = 0;
        $salesman = 0;
        $errorHtml = "";
        $typesHtml = "";
        $intervalsHtml = "";


        $hiddenIdsHtml = "";
        $displayedHtml = "";

        if ($this->FormIsInvalid) { // Form was rejected
            $action = $this->InputFields['action'];
            if ($action == 2) {
                $title = "Edit Sales Group Goal";
            }
            $idGoals = $this->InputFields['idGoals'];
            $goal = $this->InputFields['goal'];
            $type = $this->InputFields['goalType'];
            $interval = $this->InputFields['goalInterval'];
            $isDefault = $this->InputFields['isDefault'];

            if ($this->ErrorMessages != null && is_array($this->ErrorMessages) && count($this->ErrorMessages) > 0) {
                $errorHtml = "<div class=\"row\"><div class=\"seven mobile ninths right-one pad-top\"><ul id=\"errul\">";
                foreach ($this->ErrorMessages as $msg) {
                    $errorHtml .= "<li>" . $msg . "</msg>";
                }
                $errorHtml .= "</ul></div></div>";
            }
        } else if ($this->EditSetting == true) { // editing sales goal
            $action = 2;
            $title = "Edit Sales Group Goal";
            if (isset($_GET['id']) && !empty($_GET['id'])) {
                $salesGoal = $this->SalesDAO->getSalesGoals(array("idGoals" => $_GET['id']));
                $salesGoal = $salesGoal[$_GET['id']];
                if ($salesGoal != null) {
                    $idGoals = $salesGoal->idGoals;
                    $goal = $salesGoal->goal;
                    $type = $salesGoal->typeId;
                    $interval = $salesGoal->intervalId;
                    if ($salesGoal->isDefault == 1) {
                        $isDefault = "checked=\"checked\"";
                    }


                    $goalSalesmen = $salesGoal->Salesmen;
                    //echo "<pre>"; print_r($goalSalesmen); echo "</pre>";


                    $i = 0;
                    foreach ($goalSalesmen as $androgynous) {
                        $hiddenIdsHtml .= "<input type=\"hidden\" name=\"selectedSalesmen[]\" id=\"" . $androgynous->idsalesmen . "\" class=\"selectedSalesmen\" value=\"" . $androgynous->idsalesmen . "\" />";

                        if ($i == 0) {
                            $displayedHtml .= "
                                <div class=\"row pad-left\" id=\"selectedSalesRowHeader\">
                                    <div class=\"ten mobile twelfths\" style=\"font-weight: bold;\">Name</div>
                                    <div class=\"two mobile twelfths\" style=\"font-weight: bold;\">Remove</div>
                                </div>";
                        }

                        $displayedHtml .= "
                            <div class=\"row pad-left selectedSalesRow\" id=\"" . $androgynous->idsalesmen . "\">
                                <div class=\"ten mobile twelfths\" style=\"font-weight: bold; line-height: 2.3;\">" . $androgynous->lastName . ", " . $androgynous->firstName . "</div>
                                <div class=\"two mobile twelfths\" style=\"font-weight: bold;\">
                                    <a href=\"javascript:void(0)\" class=\"removeSalesman\" id=\"" . $androgynous->idsalesmen . "\" title=\"Remove salesman\"><i class=\"icon icon-trash\"></i></a>
                                </div>
                            </div>";

                        $i++;
                    }


//                    if (count($goalSalesmen) > 0) {
//                        $salesman = $goalSalesmen[0]->idsalesmen;
//                    }
                }
            }
        }

//        $selectedSalesmenHtml = "";
//        if ($this->User->typeId != 1 && $this->User->IsGroupLeader) {
//            $arySalesmen = $this->SalesDAO->getSalesGroupMembers($this->User->salesGroup);
//            echo "<pre>"; print_r($arySalesmen); echo "</pre>";
//
//            foreach ($arySalesmen as $salesman) {
//                $selectedSalesmenHtml .= "<option value=\"" . $salesman->idsalesmen . "\">" . $salesman->firstName . " " . $salesman->lastName . "</option>";
//            }
//        } else {
//            $arySalesmen = $this->SalesDAO->getSalesmen();
//        }

        if ($this->SalesGoalTypes != null) {
            foreach ($this->SalesGoalTypes as $currType) {
                $selected = "";
                if ($currType->idTypes == $type) {
                    $selected = "selected=\"selected\"";
                }
                $typesHtml .= "<option value=\"" . $currType->idTypes . "\" $selected>" . $currType->typeName . "</option>";
            }
        }
        if ($this->SalesGoalIntervals != null) {
            foreach ($this->SalesGoalIntervals as $currInterval) {
                $selected = "";
                if ($currInterval->idIntervals == $interval) {
                    $selected = "selected=\"selected\"";
                }
                $intervalsHtml .= "<option value=\"" . $currInterval->idIntervals . "\" $selected>" . $currInterval->intervalName . "</option>";
            }
        }

        if ($this->User->typeId == 1) {
            $isAdmin = 1;
        }

        $html = "
            <div class=\"container\" id=\"wrapper\">
                <div class=\"row\">
                    <div class=\"seven mobile ninths right-one pad-top\" style=\"text-align: center;\">
                        <h3>$title</h3>
                    </div>
                </div>
                $errorHtml
                <div class=\"row\">
                    <div class=\"seven mobile ninths right-one\">
                        <form id=\"frmSalesGoal\" name=\"frmSalesGoal\" method=\"post\" action=\"indexb.php\">
                            <input type=\"hidden\" id=\"action\" name=\"action\" value=\"$action\" />
                            <input type=\"hidden\" id=\"idGoals\" name=\"idGoals\" value=\"$idGoals\" />
                            <input type=\"hidden\" id=\"isAdmin\" name=\"isAdmin\" value=\"$isAdmin\" />
                            $hiddenIdsHtml
                            <div class=\"row\">
                                <div class=\"one mobile third\">
                                    <label for=\"goalType\">Type</label>
                                    <select name=\"goalType\" id=\"goalType\">
                                        <option value=\"0\" disabled=\"disabled\" selected=\"selected\">Select a type</option>
                                        $typesHtml
                                    </select>
                                </div>
                                <div class=\"one mobile third pad-left pad-right\">
                                    <label for=\"goalInterval\">Interval</label>
                                    <select name=\"goalInterval\" id=\"goalInterval\">
                                        <option value=\"0\" disabled=\"disabled\" selected=\"selected\">Select an interval</option>
                                        $intervalsHtml
                                    </select>
                                </div>
                                <div class=\"one mobile third\">
                                    <label for=\"goal\">Goal</label>
                                    <input type=\"number\" name=\"goal\" id=\"goal\" value=\"$goal\" />
                                </div>
                            </div>
                            <div class=\"row\">
                                <div class=\"one mobile seventh padded\">
                                    <label for=\"isDefault\">Is Default Goal: </label>
                                    <input type=\"checkbox\" name=\"isDefault\" id=\"isDefault\" value=\"1\" $isDefault />
                                </div>";

        //                                <div class=\"one mobile third right-one padded\">
//                                    <label for=\"salesman\">Assign goal to salesman:</label>
//                                    <select name=\"salesman\" id=\"salesman\">
//                                        <option value=\"0\" selected=\"selected\">All group members</option>
//                                        $salesmanOptionsHtml
//                                    </select>
//                                </div>

        $html .= "
            <div class=\"three mobile sevenths padded\">
                <div class=\"row pad-right\">
                    <div class=\"one mobile whole pad-right\">
                        <label for=\"salesName\">Salesman name:</label>
                        <input type=\"text\" name=\"salesName\" id=\"salesName\" class=\"salesInput\" autocomplete=\"off\"/>
                    </div>
                    <div class=\"one mobile whole\">
                        <div id=\"results\" class=\"box_shadow searchResults\"></div>
                    </div>
                </div>
            </div>
            <div class=\"three mobile sevenths\">
                <label for=\"selectedSalesmen\">Selected Salesmen: </label>
                <div id=\"selected_salesmen_container\" class=\"rounded\">
                    $displayedHtml
                </div>
            </div>
        ";



        $html .= "
                            </div>
                            <div class=\"row\">
                                <div class=\"one mobile third right-two padded\">
                                    <input type=\"submit\" name=\"btnSubmit\" id=\"btnSubmit\" class=\"green button\" value=\"Submit\" />
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        ";

        echo $html;
    }

    private function printViewPage() {

        $subtitle = "";
        if ($this->User->typeId == 1) {
            $subtitle = "<h5>These goals can be applied to all sales groups.</h5>";
        }

        if ($this->SalesGoals == null) {
            $goalsHtml = "<p>There are no sales goals set.</p>";
        } else {
            $goalsHtml = "
                <div class=\"row col_header\">
                    <div class=\"one mobile fifth\">
                        Goal
                    </div>
                    <div class=\"one mobile fifth\">
                        Type
                    </div>
                    <div class=\"one mobile fifth\">
                        Interval
                    </div>
                    <div class=\"one mobile fifth\">
                        Created
                    </div>
                    <div class=\"one mobile fifth\">
                        Action
                    </div>
                </div>
            ";
            foreach ($this->SalesGoals as $salesGoal) {
                $idGoals = $salesGoal->idGoals;
                $goal = $salesGoal->goal;
                $type = $salesGoal->typeName;
                $interval = $salesGoal->intervalName;
                $created = date("m/d/Y", strtotime($salesGoal->dateCreated));
                $action = "
                    <a href=\"manage.php?action=2&id=$idGoals\" id=\"$idGoals\" class=\"edit green button\">Edit</a>
                    <a href=\"javascript:void(0)\" id=\"$idGoals\" class=\"delete green button\">Delete</a>
                ";

                $goalsHtml .= "
                    <div class=\"row pad-bottom\">
                        <div class=\"one mobile fifth\">
                            $goal
                        </div>
                        <div class=\"one mobile fifth\">
                            $type
                        </div>
                        <div class=\"one mobile fifth\">
                            $interval
                        </div>
                        <div class=\"one mobile fifth\">
                            $created
                        </div>
                        <div class=\"one mobile fifth\">
                            $action
                        </div>
                    </div>
                ";
            }
        }

        $message = $this->Message;

        $html = "
            <div class=\"container\" id=\"wrapper\">
                <div class=\"row\">
                    <div class=\"seven mobile ninths right-one pad-top\" style=\"text-align: center;\">
                        <h3 style=\"display: inline;\">Salesgroup Goals</h3>
                        $message
                        $subtitle
                    </div>
                </div>
                <div class=\"row\">
                    <div class=\"seven mobile ninths right-one\">
                        $goalsHtml
                    </div>
                </div>
            </div>

        ";

        echo $html;
    }

    private function setMessage() {
        if (array_key_exists("msg", $_SESSION) && !empty($_SESSION['msg'])) {
            $this->Message = "<h4 id=\"msg\">" . $_SESSION['msg'] . "</h4>";
            $_SESSION['msg'] = "";
            unset($_SESSION['msg']);
        }
    }
} 