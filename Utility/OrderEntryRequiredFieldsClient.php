<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 4/16/2020
 * Time: 11:06 AM
 */
require_once 'PageClient.php';
require_once 'DAOS/UserDAO.php';

class OrderEntryRequiredFieldsClient extends PageClient {

    private $Clients;

    private $RequiredFields = array(
        "roomNumber" => "Room Number",
        "bedNumber" => "Bed Number",
        "locationId" => "Location Id",
        "patientMiddleName" => "Patient Middle Name",
        "patientGender" => "Patient Gender",
        "patientEthnicity" => "Patient Ethnicity",
        "patientSsn" => "Patient SSN",
        "patientHeightFeet" => "Patient Height Feet",
        "patientHeightInches" => "Patient Height Inches",
        //"patientHeight" => "Patient Height",
        "patientWeight" => "Patient Weight",
        "patientAddress1" => "Patient Address",
        "patientAddress2" => "Patient Address 2",
        "patientCity" => "Patient City",
        "patientState" => "Patient State",
        "patientZip" => "Patient Zip",
        //"patientCityStateZip" => "Patient City, State, Zip",
        "patientPhone" => "Patient Phone",
        "patientWorkPhone" => "Patient Work Phone",
        "subscriberMiddleName" => "Subscriber Middle Name",
        "subscriberGender" => "Subscriber Gender",
        "subscriberSsn" => "Subscriber SSN",
        "subscriberAddress1" => "Subscriber Address",
        "subscriberAddress2" => "Subscriber Address 2",
        "subscriberCity" => "Subscriber City",
        "subscriberState" => "Subscriber State",
        "subscriberZip" => "Subscriber Zip",
        //"subscriberCityStateZip" => "Subscriber City, State, Zip",
        "subscriberPhone" => "Subscriber Phone",
        "subscriberWorkPhone" => "Subscriber Work Phone",
        "insurance" => "Insurance",
        "secondaryInsurance" => "Secondary Insurance",
        "policyNumber" => "Policy Number",
        "secondaryPolicyNumber" => "Secondary Policy Number",
        "groupNumber" => "Group Number",
        "secondaryGroupNumber" => "Secondary Group Number",
        "medicareNumber" => "Medicare Number",
        "medicaidNumber" => "Medicaid Number",
        "orderComment" => "Order Comment",
        "prescriptions" => "Prescriptions",
        "diagnosisCodes" => "Diagnosis Codes"
        //"eSignature" => "E-Signature"
    );

    private $GlobalRequiredFields;

    public function __construct() {
        parent::__construct();
        $this->addStylesheet("/outreach/css/bootstrap.css");
        $this->addStylesheet("css/required.css");

        $this->addScript("/outreach/js/tooltip.js");
        $this->addScript("/outreach/js/bootstrap.min.js");
        $this->addScript("js/required.js");

        $this->Clients = ClientDAO::getClients(array(
            "startRow" => 0,
            "numRows" => 999999,
            "orderBy" => "clientNo",
            "IncludeRequiredFields" => true,
            "HasMultiLocation" => self::HasMultiLocation
        ), $this->Conn);

        $this->GlobalRequiredFields = UserDAO::getGlobalRequiredFields();
        if (in_array("patientHeight", $this->GlobalRequiredFields)) {
            $this->GlobalRequiredFields[] = "patientHeightFeet";
            $this->GlobalRequiredFields[] = "patientHeightInches";
        }
        if (in_array("patientCityStateZip", $this->GlobalRequiredFields)) {
            $this->GlobalRequiredFields[] = "patientCity";
            $this->GlobalRequiredFields[] = "patientState";
            $this->GlobalRequiredFields[] = "patientZip";
        }
        if (in_array("subscriberCityStateZip", $this->GlobalRequiredFields)) {
            $this->GlobalRequiredFields[] = "subscriberCity";
            $this->GlobalRequiredFields[] = "subscriberState";
            $this->GlobalRequiredFields[] = "subscriberZip";
        }
    }

    public function printPage() {
        $today = date("m/d/Y");
        $lastMonth = date("m/d/Y", mktime(0, 0, 0, date("m")-1, date("d"),   date("Y")));

        $clientsSingleHtml = "";
        $clientsMultipleHtml = "";
        $clientRequiredFieldInputs = "";
        foreach ($this->Clients as $client) {

            if (isset($client->RequiredFieldNames) && is_array($client->RequiredFieldNames) && count($client->RequiredFieldNames) > 0) {
                foreach ($client->RequiredFieldNames as $requiredFieldName) {
                    $clientRequiredFieldInputs .= "<input type='hidden' name='clientRequiredFieldNames[]' data-clientid='" . $client->idClients . "' value='$requiredFieldName' />";
                }
            }

            if (self::HasMultiLocation) {
                $clientsSingleHtml .= "
                <div class='row pad-left pad-right clientRow'>
                    <div class='three mobile ninths'>
                        $client->clientName
                    </div>
                    <div class='two mobile ninths pad-left'>
                        $client->locationNo
                    </div>
                    <div class='three mobile ninths pad-left'>
                        $client->locationName
                    </div>
                    <div class='one mobile ninth pad-left'>
                        <input type='radio' name='clients' value='" . $client->idClients . "' />
                    </div>
                </div>";

                    $clientsMultipleHtml .= "
                <div class='row pad-left pad-right clientRow'>
                    <div class='three mobile ninths'>
                        $client->clientName
                    </div>
                    <div class='two mobile ninths pad-left'>
                        $client->locationNo
                    </div>
                    <div class='three mobile ninths pad-left'>
                        $client->locationName
                    </div>
                    <div class='one mobile ninth pad-left'>
                        <input type='checkbox' name='clients[]' value='" . $client->idClients . "' />
                    </div>
                </div>";
            } else {
                $clientsSingleHtml .= "
                <div class='row pad-left pad-right clientRow'>
                    <div class='five mobile sixths pad-left'>
                        $client->clientName
                    </div>
                    <div class='one mobile sixth pad-left'>
                        <input type='radio' name='clients' value='" . $client->idClients . "' />
                    </div>
                </div>";

                    $clientsMultipleHtml .= "
                <div class='row pad-left pad-right clientRow'>
                    <div class='five mobile sixths pad-left'>
                        $client->clientName
                    </div>
                    <div class='one mobile sixth pad-left'>
                        <input type='checkbox' name='clients[]' value='" . $client->idClients . "' />
                    </div>
                </div>";
            }


        }

        $clientFieldsHtml = "";
        foreach ($this->RequiredFields as $key => $value) {
            $clientFieldsHtml .= "
            <div class='row pad-left pad-right clientFieldRow'>
                <div class='five mobile sixths'>
                    $value
                </div>
                <div class='one mobile sixth'>
                    <input type='checkbox' name='clientFields[]' value='$key' />
                </div>
            </div>
            ";
        }

        $globalFieldsHtml = "";
        foreach ($this->RequiredFields as $key => $value) {
            $checked = "";
            if (in_array($key, $this->GlobalRequiredFields)) {
                $checked = "checked='checked'";
            }
            $globalFieldsHtml .= "
            <div class='row pad-left pad-right globalFieldRow'>
                <div class='five mobile sixths'>
                    $value
                </div>
                <div class='one mobile sixth'>
                    <input type='checkbox' name='globalFields[]' value='$key' $checked />
                </div>
            </div>
            ";
        }

        $headerHtml = "";
        if (self::HasMultiLocation) {
            $headerHtml = "<div class='three mobile ninths'>
                <label for='clients'>Clients</label>
            </div>
            <div class='two mobile ninths'>
                <label for='clients'>Location #</label>
            </div>
            <div class='three mobile ninths'>
                <label for='clients'>Location</label>
            </div>
            ";
        } else {
            $headerHtml = "<div class='five mobile sixths' style='width: 82.15%'>
                <label for='clients'>Clients</label>
            </div>";
        }

        $html = "
            <div class='container'>
                <div class='row narrow'>
                    <div class='one mobile whole'>
                        <h3 style='margin-top: 20px; text-align: center;'>Order Entry Required Fields</h3>
                    </div>
                </div>
                <div class='row narrow'>
                    <div class='one mobile whole padded box_shadow rounded' style='border: 1px solid #5a5a5a;'>
                        <form name='frmRequiredFields' id='frmRequiredFields' action='indexb.php' method='post'>
                            $clientRequiredFieldInputs
                            
                            <ul class='nav nav-tabs' role='tablist'>
                                <li role='presentation' class='active' id='singleTab'><a href='#single' aria-controls='single' role='tab' data-toggle='tab'>Single</a></li>
                                <li role='presentation' class='' id='multipleTab'><a href='#multiple' aria-controls='multiple' role='tab' data-toggle='tab'>Multiple</a></li>                                
                            </ul>
                            <div class='tab-content'>
                                <div role='tabpanel' class='tab-pane active' id='single'>
                                    <fieldset class=''>
                                        <div class='row pad-left pad-right'>
                                            $headerHtml
                                        </div>
                                        <div class='clientsContainer'>
                                            $clientsSingleHtml
                                        </div>
                                    </fieldset>
                                </div>
                                <div role='tabpanel' class='tab-pane' id='multiple'>
                                    <fieldset class=''>
                                        <div class='row pad-left pad-right'>
                                            $headerHtml
                                            <div class='one mobile ninth'>
                                                <input type='checkbox' name='selectAllClients' class='tooltipped' data-tooltip='Select All Clients' data-position='top' value='0' />
                                            </div>
                                        </div>
                                        <div class='clientsContainer'>
                                            $clientsMultipleHtml
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                            
                            <div class='row pad-top'>
                                <div class='one mobile half pad-right'>
                                    <fieldset class='rounded box_shadow gap-top'>
                                    <div class='row pad-left pad-right'>
                                        <div class='five mobile sixths tooltipped' data-tooltip='Fields required for selected clients' data-position='top' style='width: 80.615%'>
                                            <label for='clients'>Client Required Fields</label>
                                        </div>
                                        <div class='one mobile sixth'>
                                            <input type='checkbox' name='selectAllClientFields' class='tooltipped' data-tooltip='Select All Required Fields' data-position='top' value='0' />
                                        </div>
                                    </div>
                                    <div id='clientFieldsContainer'>
                                        $clientFieldsHtml
                                    </div>
                                    </fieldset>
                                </div>
                                <div class='one mobile half pad-left'>
                                    <fieldset class='rounded box_shadow gap-top'>
                                    <div class='row pad-left pad-right'>
                                        <div class='five mobile sixths tooltipped' data-tooltip='Fields required for all users' data-position='top' style='width: 80.615%'>
                                            <label for='clients'>Global Required Fields</label>
                                        </div>
                                        <div class='one mobile sixth'>
                                            <input type='checkbox' name='selectAllGlobalFields' class='tooltipped' data-tooltip='Select All Required Fields' data-position='top' value='0' />
                                        </div>
                                    </div>
                                    <div id='globalFieldsContainer'>
                                        $globalFieldsHtml
                                    </div>
                                    </fieldset>
                                </div>
                            </div>
                               
                            <div class='row pad-left pad-right pad-top'>
                                <div class='five mobile sixths'>
                                    <label for='ignoreGlobalFields' class='tooltipped' data-tooltip='When checked, only the fields assigned to the selected clients will be required in order entry' data-position='top'>Ignore Global Required Fields:</label>
                                    <input type='checkbox' name='ignoreGlobalFields' value='1' />
                                </div>
                                <div class='one mobile sixth'>
                                    <button class='green submit pull-right' id='btnSubmit'>Submit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        ";

        echo $html;
    }

}