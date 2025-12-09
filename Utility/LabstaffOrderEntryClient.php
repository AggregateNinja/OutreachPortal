<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 6/3/15
 * Time: 3:06 PM
 */

require_once 'LabstaffPageClient.php';
require_once 'DAOS/ReportTypeDAO.php';
require_once 'DAOS/LocationDAO.php';
require_once 'DAOS/PhlebotomyDAO.php';
require_once("JavaBridge/java/Java.inc");

class LabstaffOrderEntryClient extends LabstaffPageClient {

    private $AdvancedOrderBL;
    private $CarbonCopyBL;
    private $OrderEntryBL;
    private $AdvancedDiagnosisCodeDAO;
    private $AdvancedOrderDAO;
    private $AdvancedOrderLogDAO;
    private $AdvancedPrescriptionDAO;
    private $JUserDAO;

    private $AryStates;
    private $ReportTypes;
    private $Locations;
    private $Phlebotomists;



    public function __construct(array $data = null) {
        parent::__construct($data);

        $this->addStylesheet("css/palette.css");
        $this->addStylesheet("css/styles.css");
        $this->addScript("js/script.js");

        $this->AdvancedOrderBL = new Java("BL.AdvancedOrderBL");
        $this->CarbonCopyBL = new Java("BL.CarbonCopyBL");
        $this->OrderEntryBL = new Java("BL.OrderEntryBL");
        $this->AdvancedDiagnosisCodeDAO = new Java("DAOS.AdvancedDiagnosisCodeDAO");
        $this->AdvancedOrderDAO = new Java("DAOS.AdvancedOrderDAO");
        $this->AdvancedOrderLogDAO = new Java("DAOS.AdvancedOrderLogDAO");
        $this->AdvancedPrescriptionDAO = new Java("DAOS.AdvancedPrescriptionDAO");
        $this->JUserDAO = new Java("DAOS.UserDAO");

        $this->AryStates = $this->getStatesArray();
        $this->ReportTypes = ReportTypeDAO::getReportTypes(array("Conn" => $this->Conn));
        $this->Locations = LocationDAO::getLocations(array("Conn" => $this->Conn));
        $this->Phlebotomists = PhlebotomyDAO::getPhlebotomists(array("Conn" => $this->Conn));

        //echo "<pre>"; print_r($this->Conn); echo "</pre>";

        $user = $this->JUserDAO->GetUserByID($this->LabUser->idUser);

        if (java_values($user->getIsAdmin()) != 1) {
            // menuRemoveOrder.setEnabled(false);
        }
    }


    public function printPage(array $settings = null) {

        $orderInfoHtml = $this->getOrderInfoHtml();
        $subscriberHtml = $this->getSubscriberHtml();
        $testsHtml = $this->getTestHtml();
        $diagnosisHtml = $this->getDiagnosisHtml();
        $aoeHtml = $this->getAOEHtml();
        $phlebotomyHtml = $this->getPhlebotomyHtml();

        $html = "
        <main>
            <div class=\"container\">
                <div class=\"row\" style=\"padding-top: 10px;\">
                    <form class=\"col s12\" method=\"post\" action=\"indexb.php\" name=\"frmOrderEntry\" id=\"frmOrderEntry\">
                        <div class=\"row\">
                            <div class=\"col s12\">
                                <ul class=\"tabs\">
                                    <li class=\"tab col s2\"><a class=\"active flow-text\" href=\"#tab1\">Order Info</a></li>

                                    <li class=\"tab col s2\"><a class=\"flow-text\" href=\"#tab2\">Subscriber Info</a></li>
                                    <li class=\"tab col s2\"><a class=\"flow-text\" href=\"#tab3\">Tests</a></li>
                                    <li class=\"tab col s2\"><a class=\"flow-text\" href=\"#tab4\">Diagnosis Codes</a></li>
                                    <li class=\"tab col s2\"><a class=\"flow-text\" href=\"#tab5\">AOE</a></li>
                                    <li class=\"tab col s2\"><a class=\"flow-text\" href=\"#tab6\">Phlebotomy</a></li>
                                </ul>
                            </div>
                            <div id=\"tab1\" class=\"col s12\">
                                $orderInfoHtml
                            </div>
                            <div id=\"tab2\" class=\"col s12\">
                                $subscriberHtml
                            </div>
                            <div id=\"tab3\" class=\"col s12\">
                                $testsHtml
                            </div>
                            <div id=\"tab4\" class=\"col s12\">
                                $diagnosisHtml
                            </div>
                            <div id=\"tab5\" class=\"col s12\">
                                $aoeHtml
                            </div>
                            <div id=\"tab6\" class=\"col s12\">
                                $phlebotomyHtml
                            </div>
                        </div>

                        <div class=\"row\">
                            <div class=\"col s12\">
                                <a href=\"javascript:void(0)\" class=\"waves-effect waves-light btn-large\" id=\"btnSubmit\" name=\"btnSubmit\">Submit</a>
                            </div>
                        </div>
                    </form>
                </div> <!-- end outer row -->
            </div> <!-- end container -->
        </main>
        ";

        echo $html;
    }

    private function getOrderInfoHtml() {

        $reportTypesHtml = "<option value=\"0\" disabled selected>Select a report type</option>";
        if (isset($this->ReportTypes) && is_array($this->ReportTypes)) {
            foreach($this->ReportTypes as $reportType) {
                $reportTypesHtml .= "<option value=\"" . $reportType->idreportType . "\">" . $reportType->name . "</option>";
            }
        }

        $locationsHtml = "<option value=\"0\" disabled selected>Select a location</option>";
        if (isset($this->Locations) && is_array($this->Locations)) {
            foreach ($this->Locations as $location) {
                $locationsHtml .= "<option value=\"" . $location->idLocation . "\">" . $location->locationName . "</option>";
            }
        }

        //$patientStateHtml = $this->getStateSelect("patientState", "patientState");
        $patientStateHtml = "<select name=\"patientState\" id=\"patientState\"><option value=\"0\" disabled selected>Select a state</option>";

        foreach($this->AryStates as $abbr => $state) {
            $patientStateHtml .= "<option value=\"$abbr\">$state</option>";
        }
        $patientStateHtml .= "</select>";


        $html = "
            <div class=\"row\">
                <div class=\"input-field col s6 m3 l2\">
                    <a href=\"javascript:void(0)\" class=\"waves-effect waves-light prefix tooltipped\" id=\"generateAccession\" data-tooltip=\"Generate Next Available Accession\">
                        <i class=\"mdi-action-settings\"></i></a>
                    <!--<a href=\"javascript:void(0)\" class=\"waves-effect waves-light prefix tooltipped\" id=\"orderLookUp\" data-tooltip=\"Order Look-up\">
                        <i class=\"mdi-action-search\"></i></a>-->
                    <input id=\"accession\" name=\"accession\" type=\"text\" />
                    <label for=\"accession\">Accession</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"clientNumber\" name=\"clientNumber\" type=\"number\" />
                    <label for=\"clientNumber\">Client Number</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"clientName\" name=\"clientName\" type=\"text\" />
                    <label for=\"clientName\">Client Name</label>
                </div>

                <div class=\"input-field col s6 m3 l2 \">
                    <select name=\"reportType\" id=\"reportType\">
                        $reportTypesHtml
                    </select>
                    <label for=\"reportType\">Report Type</label>
                </div>

                <div class=\"input-field no-top-margin col s6 m3 l2\">
                    <input type=\"checkbox\" id=\"billingOnly\" name=\"billingOnly\" />
                    <label for=\"billingOnly\">Billing Only</label>
                    <br/>
                    <input type=\"checkbox\" id=\"hold\" name=\"hold\" />
                    <label for=\"hold\">Hold</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"doctorNumber\" name=\"doctorNumber\" type=\"number\" />
                    <label for=\"doctorNumber\">Doctor Number</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"doctorName\" name=\"doctorName\" type=\"text\" />
                    <label for=\"doctorName\">Doctor Name</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"reqId\" name=\"reqId\" type=\"text\" />
                    <label for=\"reqId\">Req. ID</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"orderStatus\" name=\"orderStatus\" type=\"text\" value=\"\" disabled />
                    <label for=\"orderStatus\">Order Status</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <select name=\"location\" id=\"location\">
                        $locationsHtml
                    </select>
                    <label for=\"location\">Location</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"roomNumber\" name=\"roomNumber\" type=\"text\" />
                    <label for=\"roomNumber\">Room #</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"bedNumber\" name=\"bedNumber\" type=\"text\" />
                    <label for=\"bedNumber\">Bed #</label>
                </div>

                <div class=\"input-field col s12 m9 l4\">
                    <input name=\"orderFlag\" type=\"radio\" id=\"normal\" checked />
                    <label for=\"normal\">Normal</label>

                    <input name=\"orderFlag\" type=\"radio\" id=\"stat\" />
                    <label for=\"stat\">STAT</label>

                    <input name=\"orderFlag\" type=\"radio\" id=\"fax\" />
                    <label for=\"fax\">FAX</label>

                    <input name=\"orderFlag\" type=\"radio\" id=\"call\" />
                    <label for=\"call\">CALL</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"emrOrderId\" name=\"emrOrderId\" type=\"text\" />
                    <label for=\"emrOrderId\">EMR Order ID</label>
                </div>

                <div class=\"input-field col s12 m9 l4\">
                    <div class=\"row no-bottom-margin\">
                        <div class=\"input-field no-top-margin col s12 m6 l6\">
                            <input id=\"specimenDate\" name=\"specimenDate\" type=\"text\" class=\"datepicker\" \>
                            <label for=\"specimenDate\" class=\"active\">Specimen Date</label>
                        </div>
                        <div class=\"input-field no-top-margin col s12 m6 l6\">
                            <input id=\"specimenTime\" name=\"specimenTime\" type=\"time\" step=\"900\"/>

                        </div>
                    </div>
                </div>

                <div class=\"input-field col s12 m9 l4\">
                    <div class=\"row no-bottom-margin\">
                        <div class=\"input-field col no-top-margin s12 m6 l6\">
                            <input id=\"orderDate\" name=\"orderDate\" type=\"text\" class=\"datepicker\" \>
                            <label for=\"orderDate\" class=\"active\">Order Date</label>
                        </div>
                        <div class=\"input-field no-top-margin col s12 m6 l6\">
                            <input id=\"orderTime\" name=\"orderTime\" type=\"time\" step=\"900\"/>

                        </div>
                    </div>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"payment\" name=\"payment\" type=\"number\" min=\"0.00\" />
                    <label for=\"payment\">Payment</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"doi\" name=\"doi\" type=\"text\" class=\"datepicker\" \>
                    <label for=\"doi\" class=\"active\">DOI</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <select name=\"eoa\" id=\"eoa\">
                        <option value=\"0\" disabled selected>Select an EOA</option>
                        <option value=\"1\">One</option>
                        <option value=\"2\">Two</option>
                        <option value=\"3\">Three</option>
                    </select>
                    <label for=\"eoa\">EOA</label>
                </div>
            </div> <!-- end row four -->



            <div class=\"row\">
                <blockquote class=\"no-bottom-margin title\">Patient Information</blockquote>
                <div class=\"divider\"></div>


                <div class=\"input-field col s12 m3 l2\">
                    <input id=\"patientLastName\" name=\"patientLastName\" type=\"text\" />
                    <label for=\"patientLastName\">Last Name</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"patientFirstName\" name=\"patientFirstName\" type=\"text\" />
                    <label for=\"patientFirstName\">First Name</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"patientMiddleName\" name=\"patientMiddleName\" type=\"text\" />
                    <label for=\"patientMiddleName\">Middle Name</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <a href=\"javascript:void(0)\" class=\"waves-effect waves-light prefix tooltipped\" id=\"lnkPatientSearch\" data-tooltip=\"Search for a patient\">
                        <i class=\"mdi-action-settings\"></i></a>
                    <input id=\"patientId\" name=\"patientId\" type=\"text\" />
                    <label for=\"patientId\">Patient ID</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"patientDob\" name=\"patientDob\" type=\"text\" class=\"datepicker\" \>
                    <label for=\"patientDob\" class=\"active\">DOB</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"patientAge\" name=\"patientAge\" type=\"number\" min=\"0.00\" max=\"150\" />
                    <label for=\"patientAge\">Age</label>
                </div>

                <div class=\"col s12 m9 l4\">
                    <div class=\"row no-top-margin no-bottom-margin\">
                        <div class=\"input-field col s12 m12 l12 no-bottom-margin\">
                            <input id=\"patientAddressOne\" name=\"patientAddressOne\" type=\"text\" />
                            <label for=\"patientAddressOne\">Street Address 1</label>
                        </div>
                        <div class=\"input-field col s12 m12 l12 no-bottom-margin\">
                            <input id=\"patientAddressTwo\" name=\"patientAddressTwo\" type=\"text\" />
                            <label for=\"patientAddressTwo\">Street Address 2</label>
                        </div>
                    </div>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"patientCity\" name=\"patientCity\" type=\"text\" />
                    <label for=\"patientCity\">City</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"patientZip\" name=\"patientZip\" type=\"text\" />
                    <label for=\"patientZip\">Zip Code</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <!--<div class=\"row no-top-margin\">
                        <div class=\"input-field col s12 m4 l4 no-bottom-margin left-right-padding-2\">
                            <input id=\"patientPhone1\" name=\"patientPhone1\" type=\"number\" min=\"0\" max=\"999\" maxlength=\"3\"/>
                        </div>
                        <div class=\"input-field col s12 m4 l4 no-bottom-margin left-right-padding-2\">
                            <input id=\"patientPhone2\" name=\"patientPhone2\" type=\"number\" min=\"0\" max=\"999\" maxlength=\"3\" />
                        </div>
                        <div class=\"input-field col s12 m4 l4 no-bottom-margin left-right-padding-2\">
                            <input id=\"patientPhone3\" name=\"patientPhone3\" type=\"number\" min=\"0\" max=\"9999\" maxlength=\"4\" />
                        </div>
                        <label for=\"patientPhone\">Phone #</label>
                    </div>-->
                    <input id=\"patientPhone\" name=\"patientPhone\" type=\"number\" min=\"0\" max=\"9999999999\" maxlength=\"10\" />
                    <label for=\"patientPhone\">Phone #</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    $patientStateHtml
                    <label for=\"patientState\">State</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <select name=\"patientGender\" id=\"patientGender\">
                        <option value=\"0\" disabled selected>Select a gender</option>
                        <option value=\"N/A\">N/A</option>
                        <option value=\"Male\">Male</option>
                        <option value=\"Female\">Female</option>
                    </select>
                    <label for=\"patientGender\">Gender</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <select name=\"patientEthnicity\" id=\"patientEthnicity\">
                        <option value=\"Other\" selected>Other</option>
                        <option value=\"Caucasian\">Caucasian</option>
                        <option value=\"African American/Black\">African American/Black</option>
                        <option value=\"Hispanic/Latino\">Hispanic/Latino</option>
                        <option value=\"Pacific Islander\">Pacific Islander</option>
                        <option value=\"Asian\">Asian</option>
                        <option value=\"Native American\">Native American</option>
                    </select>
                    <label for=\"patientEthnicity\">Ethnicity</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <!--<div class=\"row no-top-margin\">
                        <div class=\"input-field col s12 m4 l4 no-bottom-margin left-right-padding-2\">
                            <input id=\"patientWorkPhone1\" name=\"patientWorkPhone1\" type=\"number\" min=\"0\" max=\"999\" maxlength=\"3\"/>
                        </div>
                        <div class=\"input-field col s12 m4 l4 no-bottom-margin left-right-padding-2\">
                            <input id=\"patientWorkPhone2\" name=\"patientWorkPhone2\" type=\"number\" min=\"0\" max=\"999\" maxlength=\"3\" />
                        </div>
                        <div class=\"input-field col s12 m4 l4 no-bottom-margin left-right-padding-2\">
                            <input id=\"patientWorkPhone3\" name=\"patientWorkPhone3\" type=\"number\" min=\"0\" max=\"9999\" maxlength=\"4\" />
                        </div>
                        <label for=\"patientWorkPhone\">Work Phone #</label>
                    </div>-->
                    <input id=\"patientWorkPhone\" name=\"patientWorkPhone\" type=\"number\" min=\"0\" max=\"9999999999\" maxlength=\"10\" />
                    <label for=\"patientWorkPhone\">Work Phone #</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"patientMrn\" name=\"patientMrn\" type=\"text\" />
                    <label for=\"patientMrn\">MRN</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"patientSsn\" name=\"patientSsn\" type=\"number\" min=\"0\" max=\"999999999\" maxlength=\"9\" />
                    <label for=\"patientSsn\">SSN</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <select name=\"patientRelationship\" id=\"patientRelationship\">
                        <option value=\"Self\" selected>Self</option>
                        <option value=\"Spouse\">Spouse</option>
                        <option value=\"Child\">Child</option>
                        <option value=\"Other\">Other</option>
                    </select>
                    <label for=\"patientRelationship\">Subscriber Relationship</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"patientHeightFeet\" name=\"patientHeightFeet\" type=\"number\" min=\"0\" max=\"10\" maxlength=\"2\" />
                    <label for=\"patientHeightFeet\">Height Feet</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"patientHeightInches\" name=\"patientHeightInches\" type=\"number\" min=\"0\" max=\"12\" maxlength=\"2\" />
                    <label for=\"patientHeightInches\">Height Inches</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"patientWeight\" name=\"patientWeight\" type=\"number\" min=\"0\" max=\"3000\" maxlength=\"10\" />
                    <label for=\"patientWeight\">Weight</label>
                </div>

                <div class=\"input-field no-top-margin col s6 m3 l2\">
                    <input type=\"checkbox\" id=\"patientSmoker\" name=\"patientSmoker\" />
                    <label for=\"patientSmoker\">Smoker</label>
                    <br/>
                    <input type=\"checkbox\" id=\"patientIsFasting\" name=\"patientIsFasting\" />
                    <label for=\"patientIsFasting\">Is Fasting</label>
                </div>
            </div> <!-- end patient information section -->

            <div class=\"row\">
                <p class=\"no-bottom-margin title\">Comments</p>
                <div class=\"divider\" style=\"margin-bottom: 15px;\"></div>
                <div class=\"input-field no-top-margin col s12 m4 l4\">
                    <input id=\"holdComment\" name=\"holdComment\" type=\"text\" length=\"80\"/>
                    <label for=\"holdComment\">Hold Comment</label>
                </div>
                <div class=\"input-field no-top-margin col s12 m4 l4\">
                    <input id=\"resultComment\" name=\"resultComment\" type=\"text\" length=\"80\"/>
                    <label for=\"resultComment\">Result Comment</label>
                </div>
                <div class=\"input-field no-top-margin col s12 m4 l4\">
                    <input id=\"internalComment\" name=\"internalComment\" type=\"text\" length=\"80\"/>
                    <label for=\"internalComment\">Internal Comment</label>
                </div>
                <div class=\"input-field no-top-margin col s12 m12 l12\">
                    <textarea id=\"orderComment\" name=\"orderComment\" class=\"materialize-textarea\"></textarea>
                    <label for=\"orderComment\">Order Comment</label>
                </div>
            </div>";

        return $html;
    }

    public function getSubscriberHtml() {

        $subscriberStateHtml = "<select name=\"subscriberState\" id=\"subscriberState\"><option value=\"0\" disabled selected>Select a state</option>";
        foreach($this->AryStates as $abbr => $state) {
            $subscriberStateHtml .= "<option value=\"$abbr\">$state</option>";
        }
        $subscriberStateHtml .= "</select>";

        $html = "
            <div class=\"row\">
                <div class=\"input-field col s12 m3 l2\">
                    <input id=\"subscriberLastName\" name=\"subscriberLastName\" type=\"text\" />
                    <label for=\"subscriberLastName\">Last Name</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"subscriberFirstName\" name=\"subscriberFirstName\" type=\"text\" />
                    <label for=\"subscriberFirstName\">First Name</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"subscriberMiddleName\" name=\"subscriberMiddleName\" type=\"text\" />
                    <label for=\"subscriberMiddleName\">Middle Name</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <a href=\"javascript:void(0)\" class=\"waves-effect waves-light prefix tooltipped\" id=\"lnkSubscriberSearch\" data-tooltip=\"Search for a subscriber\">
                        <i class=\"mdi-action-settings\"></i></a>
                    <input id=\"subscriberId\" name=\"subscriberId\" type=\"text\" />
                    <label for=\"subscriberId\">Subscriber ID</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"subscriberDob\" name=\"subscriberDob\" type=\"text\" class=\"datepicker\" \>
                    <label for=\"subscriberDob\" class=\"active\">DOB</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"subscriberAge\" name=\"subscriberAge\" type=\"number\" min=\"0.00\" max=\"150\" />
                    <label for=\"subscriberAge\">Age</label>
                </div>

                <div class=\"col s12 m9 l4\">
                    <div class=\"row no-top-margin no-bottom-margin\">
                        <div class=\"input-field col s12 m12 l12 no-bottom-margin\">
                            <input id=\"subscriberAddressOne\" name=\"subscriberAddressOne\" type=\"text\" />
                            <label for=\"subscriberAddressOne\">Street Address 1</label>
                        </div>
                        <div class=\"input-field col s12 m12 l12 no-bottom-margin\">
                            <input id=\"subscriberAddressTwo\" name=\"subscriberAddressTwo\" type=\"text\" />
                            <label for=\"subscriberAddressTwo\">Street Address 2</label>
                        </div>
                    </div>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"subscriberCity\" name=\"subscriberCity\" type=\"text\" />
                    <label for=\"subscriberCity\">City</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"subscriberZip\" name=\"subscriberZip\" type=\"text\" />
                    <label for=\"subscriberZip\">Zip Code</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <select name=\"subscriberGender\" id=\"subscriberGender\">
                        <option value=\"0\" disabled selected>Select a gender</option>
                        <option value=\"N/A\">N/A</option>
                        <option value=\"Male\">Male</option>
                        <option value=\"Female\">Female</option>
                    </select>
                    <label for=\"subscriberGender\">Gender</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"subscriberSsn\" name=\"subscriberSsn\" type=\"number\" min=\"0\" max=\"999999999\" maxlength=\"9\" />
                    <label for=\"subscriberSsn\">SSN</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    $subscriberStateHtml
                    <label for=\"subscriberState\">State</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"billStatus\" name=\"billStatus\" type=\"text\" value=\"\" disabled />
                    <label for=\"billStatus\">Bill Status</label>
                </div>


                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"subscriberPhone\" name=\"subscriberPhone\" type=\"number\" min=\"0\" max=\"9999999999\" maxlength=\"10\" />
                    <label for=\"subscriberPhone\">Phone #</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"subscriberWorkPhone\" name=\"subscriberWorkPhone\" type=\"number\" min=\"0\" max=\"9999999999\" maxlength=\"10\" />
                    <label for=\"subscriberWorkPhone\">Work Phone #</label>
                </div>
            </div>
            <div class=\"row\">
                <blockquote class=\"no-bottom-margin title\">Insurance Information</blockquote>
                <div class=\"divider\"></div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"insuranceNumber\" name=\"insuranceNumber\" type=\"number\" min=\"0\"/>
                    <label for=\"insuranceNumber\">Insurance #</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"insuranceName\" name=\"insuranceName\" type=\"text\" />
                    <label for=\"insuranceName\">Insurance Name</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"secondaryInsuranceNumber\" name=\"secondaryInsuranceNumber\" type=\"number\" min=\"0\"/>
                    <label for=\"secondaryInsuranceNumber\">Secondary Ins #</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"secondaryInsuranceName\" name=\"secondaryInsuranceName\" type=\"text\" />
                    <label for=\"secondaryInsuranceName\">Secondary Ins Name</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"policyNumber\" name=\"policyNumber\" type=\"text\" length=\"45\" />
                    <label for=\"policyNumber\">Policy Number</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"secondaryPolicyNumber\" name=\"secondaryPolicyNumber\" type=\"text\" length=\"45\" />
                    <label for=\"secondaryPolicyNumber\">Secondary Policy #</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"groupNumber\" name=\"groupNumber\" type=\"text\" length=\"45\" />
                    <label for=\"groupNumber\">Group Number</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"secondaryGroupNumber\" name=\"secondaryGroupNumber\" type=\"text\" length=\"45\" />
                    <label for=\"secondaryGroupNumber\">Secondary Group #</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"medicareNumber\" name=\"medicareNumber\" type=\"text\" length=\"45\" />
                    <label for=\"medicareNumber\">Medicare Number</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"medicaidNumber\" name=\"medicaidNumber\" type=\"text\" length=\"45\" />
                    <label for=\"medicaidNumber\">Medicaid Number</label>
                </div>

            </div>
        ";
        return $html;
    }

    public function getTestHtml() {
        $html = "
            <div class=\"row\">
                <div class=\"input-field col s12 m6 l6\">
                    <input id=\"testNameNumber\" name=\"testNameNumber\" type=\"text\" />
                    <label for=\"testNameNumber\">Test Name/Number Search</label>
                </div>
                <div class=\"input-field col s12 m6 l6\">
                    <ul class=\"collection with-header\">
                        <li class=\"collection-header\"><p style=\"margin-left: 10px;\">Selected Tests</p></li>
                        <li class=\"collection-item\"><a href=\"javascript:void(0)\"><i class=\"mdi-action-description\"></i>Single Test 1</a></li>
                        <li class=\"collection-item\"><a href=\"javascript:void(0)\"><i class=\"mdi-content-add\"></i>Panel Test 2</a></li>
                        <li class=\"collection-item\"><a href=\"javascript:void(0)\"><i class=\"mdi-action-description\"></i>Single Test 3</a></li>
                        <li class=\"collection-item\"><a href=\"javascript:void(0)\"><i class=\"mdi-action-description\"></i>Single Test 4</a></li>
                    </ul>
                </div>
            </div>
            <div class=\"row\">
                <blockquote class=\"no-bottom-margin title\">Point of Care Tests</blockquote>
                <div class=\"divider\"></div>

                <div class=\"input-field col s12 m12 l12\">


                </div>
            </div>
        ";
        return $html;
    }

    public function getDiagnosisHtml() {
        $html = "
            <div class=\"row\">
                <div class=\"input-field col s4 m2 l2\">
                    <input id=\"diagnosisCode\" name=\"diagnosisCode\" type=\"text\" length=\"20\"/>
                    <label for=\"diagnosisCode\">Diagnosis Code</label>
                </div>
                <div class=\"input-field col s4 m2 l2\">
                    <input id=\"loincCode\" name=\"loincCode\" type=\"text\" length=\"20\"/>
                    <label for=\"loincCode\">LOINC</label>
                </div>
                <div class=\"input-field col s4 m2 l2\">
                    <input id=\"cptCode\" name=\"cptCode\" type=\"text\" length=\"10\"/>
                    <label for=\"cptCode\">CPT Code</label>
                </div>
                <div class=\"input-field col s12 m6 l6\">
                    <input id=\"codeDescription\" name=\"codeDescription\" type=\"text\" length=\"40\"/>
                    <label for=\"codeDescription\">Description</label>
                </div>
                <div class=\"input-field col s12 m6 l6 offset-l3 offset-m3\">
                    <ul class=\"collection with-header\">
                        <li class=\"collection-header\"><p style=\"margin-left: 10px;\">Selected Diagnosis Codes</p></li>
                        <li class=\"collection-item\"><a href=\"javascript:void(0)\"><i class=\"mdi-action-description\"></i>Code 1</a></li>
                        <li class=\"collection-item\"><a href=\"javascript:void(0)\"><i class=\"mdi-action-description\"></i>Code 2</a></li>
                        <li class=\"collection-item\"><a href=\"javascript:void(0)\"><i class=\"mdi-action-description\"></i>Code 3</a></li>
                        <li class=\"collection-item\"><a href=\"javascript:void(0)\"><i class=\"mdi-action-description\"></i>Code 4</a></li>
                    </ul>
                </div>
            </div>
        ";
        return $html;
    }

    public function getAOEHtml() {
        $html = "
            <div class=\"row\">
                <blockquote class=\"no-bottom-margin\">AOE</blockquote>
                <div class=\"divider\"></div>
                <div class=\"input-field col s6 m3 l2\">

                </div>
            </div>
        ";
        return $html;
    }

    public function getPhlebotomyHtml() {

        $phlebotomistsHtml = "<option value=\"0\" selected disabled>Select a phlebotomist</option>";
        if (isset($this->Phlebotomists) && is_array($this->Phlebotomists)) {
            foreach($this->Phlebotomists as $phlebotomist) {
                $phlebotomistsHtml .= "<option value=\"" . $phlebotomist->idemployees . "\">" . $phlebotomist->firstName . " " . $phlebotomist->lastName . "</option>";
            }
        }

        $html = "
            <div class=\"row no-bottom-margin\">
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"startDate\" name=\"startDate\" type=\"text\" class=\"datepicker\" \>
                    <label for=\"startDate\" class=\"active\">Start Date</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <select name=\"frequency\" id=\"frequency\">
                        <option value=\"0\" selected disabled>Select a frequency</option>
                        <option value=\"1\">Daily</option>
                        <option value=\"2\">Weekly</option>
                        <option value=\"3\">Monthly</option>
                        <option value=\"4\">Yearly</option>
                    </select>
                    <label for=\"frequency\">Frequency</label>
                </div>

                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"occurrences\" name=\"occurrences\" type=\"number\" min=\"0\"/>
                    <label for=\"occurrences\">Occurrences</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <select name=\"phlebotomist\" id=\"phlebotomist\">
                        $phlebotomistsHtml
                    </select>
                    <label for=\"phlebotomist\">Phlebotomist</label>
                </div>
                <div class=\"input-field col s6 m3 l2\">
                    <input id=\"zone\" name=\"zone\" type=\"text\" length=\"25\" />
                    <label for=\"zone\">Zone</label>
                </div>
                <div class=\"input-field col s6 m3 l2 no-top-margin\">
                    <input name=\"fixedOrUntilCanceled\" type=\"radio\" id=\"untilCanceled\" />
                    <label for=\"untilCanceled\">Until Canceled</label>

                    <input name=\"fixedOrUntilCanceled\" type=\"radio\" id=\"fixedNumber\" checked />
                    <label for=\"fixedNumber\">Fixed Number</label>
                </div>
            </div>
        ";
        return $html;
    }

} 