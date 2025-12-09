<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 8/27/15
 * Time: 2:03 PM
 */

require_once 'PageClient.php';

class ConsistencyReportPageClient extends PageClient {

    public function __construct() {
        parent::__construct();
        parent::addStylesheet("css/reports.css");
        parent::addScript("js/consistency.js");
    }

    public function printPage() {

        $clientId = 0;
        $doctorId = 0;
        if ($this->User->typeId == 2) {
            $clientId = $this->User->idClients;
        } else if ($this->User->typeId == 3) {
            $doctorId = $this->User->iddoctors;
        }


        $today = date("m/d/Y");
        $lastMonth = date("m/d/Y", mktime(0, 0, 0, date("m")-1, date("d"),   date("Y")));
        $html = "
            <div class=\"container\">
                <div class=\"row narrow\">
                    <div class=\"one mobile whole padded\">
                        <h3 style=\"margin-top: 20px; text-align: center;\">Inconsistent Report</h3>
                    </div>
                </div>
                <div class=\"row narrow\">
                    <div class=\"one mobile whole padded box_shadow rounded\" style=\"border: 1px solid #5a5a5a; padding-bottom: 0;\">
                        <fieldset class=\"rounded box_shadow\">
                            <form name=\"frmConsistency\" id=\"frmConsistency\">
                                <input type='hidden' name='clientId' id='clientId' value='$clientId' />
                                <input type='hidden' name='doctorId' id='doctorId' value='$doctorId' />
                                <div class=\"row pad-left pad-right\">
                                    <div class=\"three mobile eighths skip-one\">
                                        <label for=\"dateFrom\">Start Date: </label>
                                        <input type=\"text\" name=\"dateFrom\" id=\"dateFrom\" class=\"datepicker\" value=\"$lastMonth\" />
                                    </div>
                                    <div class=\"three mobile eighths\">
                                        <label for=\"dateTo\">End Date: </label>
                                        <input type=\"text\" name=\"dateTo\" id=\"dateTo\" class=\"datepicker\" value=\"$today\" />
                                    </div>
                                </div>
                                <div class=\"row pad-left pad-right\">
                                    <div class=\"three mobile eighths skip-one\">
                                        <label for=\"orderBy\">Order By: </label>
                                        <select name=\"orderBy\" id=\"orderBy\">
                                            <option value=\"PatientFirstName\">Patient First Name</option>
                                            <option value=\"PatientLastName\">Patient Last Name</option>
                                            <option value=\"accession\" selected=\"selected\">Accession</option>
                                            <option value=\"TestName\">Test Name</option>
                                            <option value=\"Prescription\">Prescription</option>
                                            <option value=\"Inconsistent\">Inconsistent Remark</option>
                                            <option value=\"DateReported\">Date Reported</option>
                                        </select>
                                    </div>
                                    <div class=\"three mobile eighths\">
                                        <label for=\"direction\">Direction: </label>
                                        <select name=\"direction\" id=\"direction\">
                                            <option value=\"ASC\">Ascending</option>
                                            <option value=\"DESC\">Descending</option>
                                        </select>
                                    </div>
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