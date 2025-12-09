<?php
if (!isset($_SESSION)) {
    session_start();
}
/**
 * Created by PhpStorm.
 * User: Ed Bossmeyer
 * Date: 11/3/14
 * Time: 1:41 PM
 */


require_once 'PageClient.php';
require_once 'Utility/IClient.php';
require_once 'DAOS/SalesDAO.php';
require_once 'DOS/SpeedometerReportFactory.php';


class SalesPageClient extends PageClient implements IClient {
    private $SalesDAO;

    public function __construct() {
        parent::__construct(array("IncludeDetailedInfo" => true));
        $this->addStylesheet("/outreach/sales/css/main.css");
        $this->addStylesheet("/outreach/css/menu-slider.css");

        /*$this->addScript("/outreach/js/chart/ChartNew.js");*/

        /*$this->addScript("/outreach/pdf/src/shared/util.js");
        $this->addScript("/outreach/pdf/src/display/api.js");
        $this->addScript("/outreach/pdf/src/display/metadata.js");
        $this->addScript("/outreach/pdf/src/display/canvas.js");
        $this->addScript("/outreach/pdf/src/display/webgl.js");
        $this->addScript("/outreach/pdf/src/display/pattern_helper.js");
        $this->addScript("/outreach/pdf/src/display/font_loader.js");
        $this->addScript("/outreach/pdf/src/display/annotation_helper.js");

        $this->addScript("/outreach/pdf/web/ui_utils.js");
        $this->addScript("/outreach/pdf/web/default_preferences.js");
        $this->addScript("/outreach/pdf/web/preferences.js");
        $this->addScript("/outreach/pdf/web/download_manager.js");
        $this->addScript("/outreach/pdf/web/view_history.js");
        $this->addScript("/outreach/pdf/web/pdf_rendering_queue.js");
        $this->addScript("/outreach/pdf/web/pdf_page_view.js");
        $this->addScript("/outreach/pdf/web/text_layer_builder.js");
        $this->addScript("/outreach/pdf/web/annotations_layer_builder.js");
        $this->addScript("/outreach/pdf/web/pdf_viewer.js");
        $this->addScript("/outreach/pdf/web/thumbnail_view.js");
        $this->addScript("/outreach/pdf/web/document_outline_view.js");
        $this->addScript("/outreach/pdf/web/document_attachments_view.js");
        $this->addScript("/outreach/pdf/web/pdf_find_bar.js");
        $this->addScript("/outreach/pdf/web/pdf_find_controller.js");
        $this->addScript("/outreach/pdf/web/pdf_history.js");
        $this->addScript("/outreach/pdf/web/secondary_toolbar.js");
        $this->addScript("/outreach/pdf/web/presentation_mode.js");
        $this->addScript("/outreach/pdf/web/grab_to_pan.js");
        $this->addScript("/outreach/pdf/web/hand_tool.js");
        $this->addScript("/outreach/pdf/web/overlay_manager.js");
        $this->addScript("/outreach/pdf/web/password_prompt.js");
        $this->addScript("/outreach/pdf/web/document_properties.js");*/
        /*$this->addScript("/pdf/web/viewer.js");*/

        /*$this->addScript("/sales/js/test.js");*/

        $this->addScript("/outreach/js/jquery.tablesorter.min.js");

        $this->addScript("/outreach/sales/js/main.js");


        $this->addScript("https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js");

        $this->SalesDAO = new SalesDAO();

        //echo "<pre>"; print_r($this->User); echo "</pre>";

    }

    public function printPage() {
        $html = "";
        if (isset($_GET['chart'])) {
            $chartNum = $_GET['chart'];
            $groupId = 0;
            $salesmanId = 0;
            $isGroupLeader = 0;
            $isAdmin = 1;
            if ($this->User->typeId != 1) {
                $groupId = $this->User->id;
                $salesmanId = $this->User->idsalesmen;
                if ($this->User->IsGroupLeader) {
                    $isGroupLeader = 1;
                }
                $isAdmin = 0;
            }

            $salesEditOrderLink = "";
            $editOrderLink = "";
            if ($this->EditOrderLink == true) {
                $salesEditOrderLink = "&z=1";
                $editOrderLink = "?z=1";
            }

            /*$html = "
            <div class='transition-container'>
                <div class='slide-container' id='pending'>
                    <button class='close-button' id='close-button'>X</button>
                    <div class='menu-wrap box_shadow'>
                        <h3>Sales Reports</h3>
                        <nav class='menu'>
                            <fieldset>
                                <a href=\"/outreach/sales/index.php?chart=1&$salesEditOrderLink\"><i class=\"icon icon-signal\"></i>Sales Goal Chart</a>
                            </fieldset>
                            <fieldset>
                                <a href=\"/outreach/sales/index.php?chart=2&$salesEditOrderLink\"><i class=\"icon icon-signal\"></i>Sales Report</a>
                            </fieldset>
                            <fieldset>
                                <a href=\"/outreach/sales/index.php?chart=3&$salesEditOrderLink\"><i class=\"icon icon-signal\"></i>Orders Billed Chart</a>
                            </fieldset>
                            <fieldset>
                                <a href=\"/outreach/sales/index.php?chart=4&$salesEditOrderLink\"><i class=\"icon icon-signal\"></i>Billing Sales Summary</a>
                            </fieldset>
                        </nav>
                    </div>
                </div>
                <a href='javascript:void(0)' data-tooltip='Search &amp; filter your pending orders' data-position='right' class='tooltipped menu-button rounded box_shadow button open-button' id='open-button'><i class='icon icon-filter'></i></a>
            </div>
            ";*/
            $html = "";

            if ($chartNum == 1) {
                $html .= $this->getSalesGoalsHtml();

                $html .= "
                    <div class=\"row\" style=\"margin-top: 5px;\">
                        <div class=\"three mobile fifths right-one mobile\">
                            <div class=\"row pad-left pad-right\">
                                <div class=\"two mobile sixths\"></div>
                                <div class=\"two mobile sixths\" style=\"text-align: center;\">

                                    <form method=\"post\" action=\"process.php\" name=\"frmDownload\" id=\"frmDownload\">
                                        <button id=\"download\" class=\"button green\">Download</button>
                                        <input type=\"hidden\" name=\"action\" id=\"action\" class=\"speed\" value=\"5\" />
                                        <input type=\"hidden\" name=\"dateFrom\" id=\"dateFrom\" class=\"speed\" value=\"\" />
                                        <input type=\"hidden\" name=\"dateTo\" id=\"dateTo\" class=\"speed\" value=\"\" />
                                        <input type=\"hidden\" name=\"idsalesmen\" id=\"idsalesmen\" class=\"speed\" value=\"$salesmanId\" />
                                        <input type=\"hidden\" name=\"intervalId\" id=\"intervalId\" class=\"speed\" value=\"\" />
                                        <input type=\"hidden\" name=\"salesgroupId\" id=\"salesgroupId\" class=\"speed\" value=\"$groupId\" />
                                        <input type=\"hidden\" name=\"isOwner\" id=\"isOwner\" class=\"speed\" value=\"$isAdmin\" />
                                        <input type=\"hidden\" name=\"goalId\" id=\"goalId\" class=\"speed\" value=\"\" />
                                    </form>
                                </div>
                                <div class=\"two mobile sixths\">
                                    <button id=\"next\" class=\"button green\" style=\"float: right;\" title=\"View Sales Report\"><i class=\"icon icon-angle-right\"></i></button>
                                </div>

                            </div>
                        </div>
                    </div>
                ";

            } elseif ($chartNum == 2) {
                $html .= $this->getSalesReportHtml();
                $html .= "
                    <div class=\"row\" style=\"margin-top: 5px;\">
                        <div class=\"three mobile fifths right-one mobile\">
                            <div class=\"row pad-left pad-right\">
                                <div class=\"two mobile sixths\">
                                    <button id=\"prev\" class=\"button green\" title=\"Previous page\"><i class=\"icon icon-angle-left\"></i></button>
                                </div>
                                <div class=\"two mobile sixths\" style=\"text-align: center;\">
                                    <form method=\"post\" action=\"process.php\" name=\"frmDownload\" id=\"frmDownload\">
                                        <button id=\"download\" class=\"button green\">Download</button>
                                        <input type=\"hidden\" name=\"action\" id=\"action\" value=\"4\" />
                                        <input type=\"hidden\" name=\"dateFrom\" id=\"dateFrom\" class=\"graph\" value=\"\" />
                                        <input type=\"hidden\" name=\"dateTo\" id=\"dateTo\" class=\"graph\" value=\"\" />
                                        <input type=\"hidden\" name=\"groupId\" id=\"groupId\" class=\"graph\" value=\"$groupId\" />
                                        <input type=\"hidden\" name=\"salesmanId\" id=\"salesmanId\" class=\"graph\" value=\"$salesmanId\" />
                                        <input type=\"hidden\" name=\"isGroupLeader\" id=\"isGroupLeader\" class=\"graph\" value=\"$isGroupLeader\" />
                                        <input type=\"hidden\" name=\"isAdmin\" id=\"isAdmin\" class=\"graph\" value=\"$isAdmin\" />
                                    </form>

                                </div>
                                <div class=\"two mobile sixths\">
                                    <!--<button id=\"next\" class=\"button green\" style=\"float: right;\" title=\"View Sales Report\"><i class=\"icon icon-angle-right\"></i></button>-->
                                </div>

                            </div>
                        </div>
                    </div>
                ";
            } elseif ($chartNum == 3) {
                $dateFrom = date("m/d/Y", mktime(0, 0, 0, date("m")-1, date("d"),   date("Y")));
                $dateTo = date("m/d/Y");

                $html .= "
                    <div class=\"container\" id=\"wrapper\">
                        <div class=\"row pad-top pad-bottom\">
                            <div class=\"one mobile whole chart-row\">
                                <iframe src='charts/index.html' id='ifrChart'></iframe>
                            </div>
                        </div>
                    </div>
                    <div class=\"row\" style=\"margin-top: 5px;\">
                        <div class=\"three mobile fifths right-one mobile\">
                            <div class=\"row pad-left pad-right\">
                                <div class=\"two mobile sixths\">
                                    <button id=\"prev\" class=\"button green\" title=\"Previous page\"><i class=\"icon icon-angle-left\"></i></button>
                                </div>
                                <div class=\"two mobile sixths\" style=\"text-align: center;\">
                                    <form method=\"post\" action=\"process.php\" name=\"frmDownload\" id=\"frmDownload\">
                                        <button id=\"download\" class=\"button green\">Download</button>
                                        <input type=\"hidden\" name=\"action\" id=\"action\" value=\"7\" />
                                        <input type=\"hidden\" name=\"dateFrom\" id=\"dateFrom\" class=\"graph\" value=\"\" />
                                        <input type=\"hidden\" name=\"dateTo\" id=\"dateTo\" class=\"graph\" value=\"\" />
                                    </form>
                                </div>
                                <div class=\"two mobile sixths\"></div>

                            </div>
                        </div>
                    </div>
                ";
            } elseif ($chartNum == 4) {

                $html .= $this->getSalesPageHtml();

            }
        }

        echo $html;
    }

    private function getSalesPageHtml() {
        $salesmanName = $this->User->lastName . " " . $this->User->firstName;
        $groupId = $this->User->SalesGroup->id;
        $salesGroup = $this->User->groupName;

        $dateFrom = new DateTime('-1 month');
        $dateTo = new DateTime();

        if (isset($_POST['dateFrom'])) {
            $dateFrom = new DateTime($_POST['dateFrom']);
        }

        if (isset($_POST['dateTo'])) {
            $dateTo = new DateTime($_POST['dateTo']);
        }

        $salesAllData = $this->SalesDAO->getSalesSummaryData($dateFrom->format('Y-m-d H:i:s'), $dateTo->format('Y-m-d H:i:s'), $groupId);

        $aryOrderDates = $salesAllData[0];
        $arySalesmen = $salesAllData[1];
        $aryClients = $salesAllData[2];
        $arySalesDataIds = $salesAllData[3];

        $aryTotalSales = array();

        //echo "<pre>"; print_r($salesAllData); echo "</pre>";



        $salesTable = "
                    <table id='tblSales'><thead>
                        <tr>
                            <th class='fittext'>Sales Person</th>
                            <th class='fittext'>Territory</th>
                            <th class='fittext'>Client</th>";

        $orderDateCount = count($aryOrderDates);
        foreach ($aryOrderDates as $timestamp => $formattedDate) {
            $salesTable .= "<th class='fittext'>$formattedDate</th>";

            $aryTotalSales[$timestamp] = 0;
        }
        $salesTable .= "<th class='fittext'>Total</th></tr></thead><tbody>";


        $totalSales = 0;
        foreach ($arySalesDataIds as $idsalesmen => $aryClientId) {

            $salesmanName = $arySalesmen[$idsalesmen][0] . " " . $arySalesmen[$idsalesmen][1];
            $territory = $arySalesmen[$idsalesmen][2];
            foreach ($aryClientId as $idClients => $arySales) {

                $clientName = $aryClients[$idClients][1];

                $salesTable .= "<tr>
                            <td>$salesmanName</td>
                            <td>$territory</td>
                            <td>$clientName</td>";

                $currTotal = 0;
                foreach ($aryOrderDates as $timestamp => $formattedDate) {

                    $dailySales = 0;
                    if (array_key_exists($timestamp, $arySales)) {
                        $dailySales = $arySales[$timestamp];

                        $aryTotalSales[$timestamp] += $dailySales;

                        $currTotal += $dailySales;
                    }

                    $salesTable .= "<td>$dailySales</td>";
                }

                $salesTable .= "<td class='strong'>$currTotal</td></tr>";
            }
        }

        $salesTable .= "</tbody><tr class='strong'><td colspan='3'>Total Sales:</td>";
        foreach ($aryOrderDates as $timestamp => $formattedDate) {
            $salesTable .= "<td>" . $aryTotalSales[$timestamp] . "</td>";

            $totalSales += $aryTotalSales[$timestamp];
        }
        $salesTable .= "<td>$totalSales</td></tr>";

        $salesTable .= "</table>";

        return "
                <div class='container responsive' data-compression='130' id='wrapper'>
                    <div class='row'>
                        <div class='one mobile third'>
                            <p><strong>Sales Person: </strong>$salesmanName</p>
                            <p><strong>Sales Group: </strong>$salesGroup</p>
                        </div>
                        <div class='one mobile third'>
                            <div class='row'>
                                <form name='frmUpdateSalesPage' id='frmUpdateSalesPage' action='index.php?chart=4' method='post'>
                                <div class='one mobile third'>
                                    <label for='dateFrom'>Start Date:</label>
                                    <input type='text' name='dateFrom' id='dateFrom' class='datepicker' value='" . $dateFrom->format('m/d/Y') . "' />
                                </div>
                                <div class='one mobile third'>
                                    <label for='dateTo'>End Date:</label>
                                    <input type='text' name='dateTo' id='dateTo' class='datepicker' value='" . $dateTo->format('m/d/Y') . "' />
                                </div>
                                <div class='one mobile third'>
                                    <input type='submit' name='btnUpdateSalesPage' id='btnUpdateSalesPage' class='button green' value='Update' />
                                </div>
                                </form>
                            </div>

                        </div>
                        <div class='one mobile third'>
                            <h3 class='pull-right'>Sales Summary</h3>
                        </div>
                    </div>

                    <div class='row'>
                        <div class='one mobile whole pad-top' id='salesTableContainer'>
                            $salesTable
                        </div>
                    </div>
                </div>
                ";
    }

    private function getSalesGoalsHtml() {
        $groupMembersHtml = "";
        $goalOptions = "";
        $isOwner = false;
        $salesGroupsHtml = "";
        if ($this->User instanceof AdminUser && $this->User->hasAdminSetting(13)) {
            $isOwner = true;
        }
        $salesGoalSalesmenHiddenInputs = "";

        if ($isOwner) { // Sales Owner
            $salesGroups = $this->SalesDAO->getSalesGroupsWithEmployees();
            if($salesGroups != null) {
                $salesGroupsHtml = "
                    <div class=\"row pad-top pad-bottom\">
                        <div class=\"one mobile whole\">
                            <label for=\"salesGroups\">Sales Groups:</label>
                            <select name=\"salesGroups\" id=\"salesGroups\">
                                <option value=\"0\" disabled=\"disabled\" selected=\"selected\">Select a sales group</option>";
                foreach ($salesGroups as $currGroup) {
                    $salesGroupsHtml .= "<option value=\"" . $currGroup->id . "\">" . $currGroup->groupName . "</option>";
                }
                $salesGroupsHtml .= "
                            </select>
                        </div>
                    </div>";
            }

            $groupMembersHtml = "
                <div class=\"row pad-top pad-bottom\" style=\"display: none;\">
                    <div class=\"one mobile whole\">
                        <label for=\"groupMembers\">Group Members:</label>
                        <select name=\"groupMembers\" id=\"groupMembers\"></select>
                    </div>
                </div> ";

        } else if ($this->User->IsGroupLeader) { // Group Leader
            $memberOptions = "";
            $salesGroup = $this->User->SalesGroup;;
            $groupMembers = $this->SalesDAO->getSalesGroupMembers(array("groupId" => $salesGroup->id));
            if ($groupMembers != null) {
                $memberOptions = "<option value=\"0\">All Members</option>";
                foreach ($groupMembers as $member) {
                    $memberOptions .= "<option value=\"" . $member->idsalesmen . "\">" . $member->firstName . " " . $member->lastName . "</option>";

                }
            }

            $groupMembersHtml = "
                <div class=\"row pad-top pad-bottom\">
                    <div class=\"one mobile whole\">
                        <label for=\"groupMembers\">Group Members:</label>
                        <select name=\"groupMembers\" id=\"groupMembers\">
                            $memberOptions
                        </select>
                    </div>
                </div>
            ";

            $salesGroupsHtml = "<input type=\"hidden\" name=\"salesGroups\" id=\"salesGroups\" value=\"" . $this->User->salesGroup . "\" />";
        } else { // Regular Salesman
            $groupMembersHtml = "<input type=\"hidden\" name=\"groupMembers\" id=\"groupMembers\" value=\"" . $this->User->idsalesmen . "\" />";
            $salesGroupsHtml = "<input type=\"hidden\" name=\"salesGroups\" id=\"salesGroups\" value=\"" . $this->User->salesGroup . "\" />";
        }

        if ($this->User->typeId != 1) {
            $salesGoals = $this->SalesDAO->getSalesGoals(array(
                "salesgroupId" => $this->User->salesGroup,
                "sg.isActive" => 1
            ));
        } else {
            $salesGoals = $this->SalesDAO->getSalesGoals(array(
                "userId" => $this->User->idUsers,
                "sg.isActive" => 1
            ));
        }


        if ($salesGoals != null) {
            foreach($salesGoals as $currGoal) { // loop through sales goals

                if (count($currGoal->Salesmen) > 0) {
                    $individualGoal = true;
                } else {
                    $individualGoal = false;
                }

                if (!$individualGoal || $this->User->IsGroupLeader == true || $isOwner || in_array($this->User->idsalesmen, $currGoal->getSalesmenIds())) {
                    $goalText = $currGoal->goal . " " . $currGoal->intervalName . " sales";

                    $goalSelected = "";
                    if ($currGoal->isDefault == true) { // automatically select the default goal
                        $goalSelected = "selected=\"selected\"";
                    }

                    $goalDisabled = "";
                    if ($currGoal->hasSalesmen()) {
                        $goalDisabled = "disabled='disabled'";

                        $currGoalSalesmen = $currGoal->getSalesmen();
                        if (count($currGoalSalesmen) == 1) {
                            $currGoalSalesman = $currGoalSalesmen[0];

                            $goalText .= " assigned to " . $currGoalSalesman->firstName . " " . $currGoalSalesman->lastName;
                        } else {
                            $goalText .= " assigned to multiple salesmen";
                        }

                        $salesmenIds = array();
                        foreach ($currGoalSalesmen as $currGoalSalesman) {
                            $salesmenIds[] = $currGoalSalesman->idsalesmen;
                            if ($currGoalSalesman->idsalesmen == $this->User->idsalesmen) {
                                $goalDisabled = "";
                            }
                        }
                        $strSalesmenIds = implode(",", $salesmenIds);
                        $salesGoalSalesmenHiddenInputs .= "<input type=\"hidden\" name=\"salesGoalSalesmen[]\" class=\"salesGoalSalesmen\" id=\"" . $currGoal->idGoals . "\" value=\"$strSalesmenIds\" />";
                    }
                    $goalOptions .= "<option value=\"" . $currGoal->idGoals . "\" $goalSelected $goalDisabled>$goalText</option>";
                }

            }
        }



        $dateFrom = date("m/d/Y", mktime(0, 0, 0, date("m")-1, date("d"),   date("Y")));
        $dateTo = date("m/d/Y");

        $salesForm = "
            <form name=\"frmSales\" id=\"frmSales\">
                <input type=\"hidden\" name=\"isOwner\" id=\"isOwner\" value=\"$isOwner\" />
                $salesGoalSalesmenHiddenInputs
                $salesGroupsHtml
                $groupMembersHtml
                <div class=\"row pad-top pad-bottom\">
                    <div class=\"one mobile whole\">
                        <label for=\"goal\">Goal:</label>
                        <select name=\"goal\" id=\"goal\">
                            <option value=\"0\" disabled=\"disabled\">Select a goal</option>
                            $goalOptions
                        </select>
                    </div>
                </div>
                <div class=\"row pad-top pad-bottom\">
                    <div class=\"one mobile whole\">
                        <label for=\"dateFrom\">Sales date from </label>
                        <input type=\"text\" name=\"dateFrom\" id=\"dateFrom\" class=\"datepicker\" value=\"$dateFrom\" />
                        <label for=\"dateTo\"> to </label>
                        <input type=\"text\" name=\"dateTo\" id=\"dateTo\" class=\"datepicker\" value=\"$dateTo\" />
                    </div>
                </div>
                <div class=\"row\">
                    <div class=\"five mobile sixths\">
                        <input type=\"submit\" name=\"btnSubmit\" id=\"btnSubmit\" class=\"button green\" value=\"Submit\" />
                    </div>
                </div>
            </form>";



        $html = "
            <div class=\"container\" id=\"wrapper\">
                <div class=\"row pad-top pad-bottom\">
                    <div class=\"one mobile half chart-row\">
                        <div class=\"one mobile whole\" id=\"frameContainer\"></div>
                    </div>
                    <div class=\"one mobile half\">
                        $salesForm
                    </div>
                </div>
            </div>
            <span id=\"page_num\"></span><span id=\"page_count\"></span>
        ";
        return $html;
    }

    private function getSalesReportHtml() {
        $groupId = 0;
        $salesmanId = 0;
        $isGroupLeader = 0;
        $isAdmin = 1;
        if ($this->User->typeId != 1) {
            $groupId = $this->User->id;
            $salesmanId = $this->User->idsalesmen;
            if ($this->User->IsGroupLeader) {
                $isGroupLeader = 1;
            }
            $isAdmin = 0;
        }

        $dateFrom = date("m/d/Y", mktime(0, 0, 0, date("m")-1, date("d"),   date("Y")));
        $dateTo = date("m/d/Y");
        $dateHtml = "
            <div class=\"one mobile third\">
                <label for=\"dateFrom\">Start Date:</label>
                <input type=\"text\" name=\"dateFrom\" id=\"dateFrom\" class=\"datepicker\" value=\"$dateFrom\" />
            </div>
            <div class=\"one mobile third\">
                <label for=\"dateTo\">End Date:</label>
                <input type=\"text\" name=\"dateTo\" id=\"dateTo\" class=\"datepicker\" value=\"$dateTo\" />
            </div>
        ";

        $html = "
            <div class=\"container\" id=\"wrapper\">
                <div class=\"row pad-top\" style=\"padding-bottom: 5px;\">
                    $dateHtml
                    <div class=\"one mobile third\" style=\"text-align: center;\">
                        <input type=\"submit\" name=\"btnUpdate\" id=\"btnUpdate\" class=\"button green\" value=\"Update\" />
                    </div>
                </div>

                <div class=\"row rounded box_shadow\">
                    <div class=\"one mobile whole chart-row\">
                        <div class=\"one mobile whole\" id=\"frameContainer\"></div>
                    </div>
                </div>
        ";

//        $html .= "
//                <div class=\"row\" style=\"margin-top: 5px;\">
//                    <div class=\"three fifths right-one mobile\">
//                        <div class=\"row pad-left pad-right\">
//                            <div class=\"one sixth pad-left mobile\">
//                                <button id=\"first\" class=\"button green\" title=\"First page\"><i class=\"icon icon-double-angle-left\"></i></button>
//                            </div>
//                            <div class=\"one sixth mobile\">
//                                <button id=\"prev\" class=\"button green\" title=\"Previous page\"><i class=\"icon icon-angle-left\"></i></button>
//                            </div>
//                            <div class=\"two sixths mobile\" style=\"text-align: center;\">
//                                <form method=\"post\" action=\"process.php\" name=\"frmDownload\" id=\"frmDownload\">
//                                    <button id=\"download\" class=\"button green\">Download</button>
//                                    <input type=\"hidden\" name=\"action\" id=\"action\" value=\"4\" />
//                                    <input type=\"hidden\" name=\"groupId\" id=\"groupId\" value=\"$groupId\" />
//                                    <input type=\"hidden\" name=\"salesmanId\" id=\"salesmanId\" value=\"$salesmanId\" />
//                                    <input type=\"hidden\" name=\"isGroupLeader\" id=\"isGroupLeader\" value=\"$isGroupLeader\" />
//                                    <input type=\"hidden\" name=\"isAdmin\" id=\"isAdmin\" value=\"$isAdmin\" />
//                                </form>
//                            </div>
//                            <div class=\"one sixth mobile\">
//                                <button id=\"next\" class=\"button green\" style=\"float: right;\" title=\"Next page\"><i class=\"icon icon-angle-right\"></i></button>
//                            </div>
//                            <div class=\"one sixth mobile\">
//                                <button id=\"last\" class=\"button green\" style=\"float: right;\" title=\"Last page\"><i class=\"icon icon-double-angle-right\"></i></button>
//                            </div>
//
//                        </div>
//                    </div>
//                </div>
//
//            </div>
//        ";

        $html .= "<span id=\"page_num\"></span><span id=\"page_count\"></span>";

        return $html;
    }


    public function endPagePrint() {
        parent::endPagePrint();
    }
}