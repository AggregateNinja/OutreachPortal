<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 1/21/15
 * Time: 11:34 AM
 */

require_once 'PageClient.php';

class PatientPositiveReportClient extends PageClient {

    public function __construct() {
        parent::__construct();
        parent::addStylesheet("css/reports.css");
        parent::addScript("js/reports.js");
    }

    public function printPage() {
        $pageTitle = self::PositiveTestsPageTitle;
        $today = date("m/d/Y");
        $lastMonth = date("m/d/Y", mktime(0, 0, 0, date("m")-1, date("d"),   date("Y")));

        $html = "
            <div class=\"container\">
                <div class=\"row narrow\">
                    <div class=\"one mobile whole padded\">
                        <h3 style=\"margin-top: 20px; text-align: center;\">$pageTitle</h3>
                    </div>
                </div>
                <div class=\"row narrow\">
                    <div class=\"one mobile whole padded box_shadow rounded\" style=\"border: 1px solid #5a5a5a; padding-bottom: 0;\">
                        <fieldset class=\"rounded box_shadow\">
                            <form name=\"frmPositive\" id=\"frmPositive\">
                                <div class=\"row pad-left pad-right\">
                                    <div class=\"one mobile eighth\">&nbsp;</div>
                                    <div class=\"three mobile eighths\">
                                        <label for=\"dateFrom\">Start Date: </label>
                                        <input type=\"text\" name=\"dateFrom\" id=\"dateFrom\" class=\"datepicker\" value=\"$lastMonth\" />
                                    </div>
                                    <div class=\"three mobile eighths\">
                                        <label for=\"dateTo\">End Date: </label>
                                        <input type=\"text\" name=\"dateTo\" id=\"dateTo\" class=\"datepicker\" value=\"$today\" />
                                    </div>
                                    <div class=\"one mobile eighth\">&nbsp;</div>
                                </div>
                                <div class=\"row pad-left pad-right\">
                                    <div class=\"one mobile eighth\">&nbsp;</div>
                                    <div class=\"three mobile eighths\">
                                        <label for=\"orderBy\">Order By: </label>
                                        <select name=\"orderBy\" id=\"orderBy\">
                                            <option value=\"PatientFirstName\">Patient First Name</option>
                                            <option value=\"PatientLastName\" selected=\"selected\">Patient Last Name</option>
                                            <option value=\"accession\">Accession</option>
                                            <option value=\"orderDate\">Order Date</option>
                                            <option value=\"dateReported\">Reported Date</option>
                                        </select>
                                    </div>
                                    <div class=\"three mobile eighths\">
                                        <label for=\"direction\">Direction: </label>
                                        <select name=\"direction\" id=\"direction\">
                                            <option value=\"ASC\">Ascending</option>
                                            <option value=\"DESC\">Descending</option>
                                        </select>
                                    </div>
                                    <div class=\"one mobile eighth\">&nbsp;</div>
                                </div>
                                <div class=\"row pad-left pad-right\">
                                    <div class=\"one mobile eighth\">&nbsp;</div>
                                    <div class=\"three mobile eighths\">
                                        <div class=\"row pad-right\">
                                            <div class=\"five mobile sevenths pad-right\">
                                                <label for=\"testInput\">Test Name</label>
                                                <input type=\"text\" name=\"testInput\" id=\"testNameInput\" class=\"testInput\" autocomplete=\"off\" placeholder=\"begin typing a test name\" tabindex=\"50\" />
                                            </div>
                                            <div class=\"two mobile sevenths\">
                                                <label for=\"testInput\">Test Number</label>
                                                <input type=\"number\" name=\"testNumberInput\" id=\"testNumberInput\" class=\"testInput\"  placeholder=\"type a test #\" tabindex=\"50\" />
                                            </div>
                                            <div class=\"one mobile whole\">
                                                <div id=\"results\" class=\"box_shadow searchResults\"></div>
                                            </div>
                                        </div>
                                        <div class=\"row pad-right\">
                                            <div class=\"one mobile whole\">
                                                <label for=\"allTests\">All Tests</label>
                                                <input type=\"checkbox\" name=\"allTests\" id=\"allTests\" value='1' tabindex=\"51\" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class=\"three mobile eighths\">
                                        <label for=\"selectedTests\">Selected Tests: </label>
                                        <div id=\"selected_tests_container\" class=\"rounded\"></div>
                                    </div>
                                    <div class=\"one mobile eighth\">&nbsp;</div>
                                </div>
                                <div class=\"row pad-left pad-right pad-top\">
                                    <div class=\"six mobile eighths right-one\">
                                        <a id=\"btnSubmit\" class=\"button pull-right\" href=\"javascript:void(0)\">Submit</a>
                                    </div>
                                </div>
                            </form>
                        </fieldset>
                        <div id=\"frameContainer\"></div>
                    </div>
                </div>
            </div>
        ";

        echo $html;
    }

} 