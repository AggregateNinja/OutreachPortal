<?php
require_once 'Utility/PageClient.php';
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 9/25/2017
 * Time: 3:03 PM
 */
class AdminOrderEntryClient extends PageClient {

    private $InputFields = array(
        "Room Number" => "roomNumber",
        "Bed Number" => "bedNumber",
        "Location" => "locationId",
        "Patient Middle Name" => "patientMiddleName",
        "Patient Gender" => "patientGender",
        "Patient Ethnicity" => "patientEthnicity",
        "Patient Ssn" => "patientSsn",
        "Patient Height" => "patientHeight",
        "Patient Weight" => "patientWeight",
        "Patient Address 1" => "patientAddress1",
        "Patient Address 2" => "patientAddress2",
        "Patient City" => "patientCity",
        "Patient State" => "patientState",
        "Patient Zip" => "patientZip",
        "Patient City, State and Zip" => "patientCityStateZip",
        "Patient Phone" => "patientPhone",
        "Patient Work Phone" => "patientWorkPhone",
        "Subscriber Middle Name" => "subscriberMiddleName",
        "Subscriber Gender" => "subscriberGender",
        "Subscriber Ssn" => "subscriberSsn",
        "Subscriber Address 1" => "subscriberAddress1",
        "Subscriber Address 2" => "subscriberAddress2",
        "Subscriber City" => "subscriberCity",
        "Subscriber State" => "subscriberState",
        "Subscriber Zip" => "subscriberZip",
        "Subscriber City, State and Zip" => "subscriberCityStateZip",
        "Subscriber Phone" => "subscriberPhone",
        "Subscriber Work Phone" => "subscriberWorkPhone",
        "Insurance" => "insurance",
        "Secondary Insurance" => "secondaryInsurance",
        "Policy Number" => "policyNumber",
        "Secondary Policy Number" => "secondaryPolicyNumber",
        "Group Number" => "groupNumber",
        "Secondary Group Number" => "secondaryGroupNumber",
        "Medicare Number" => "medicareNumber",
        "Medicaid Number" => "medicaidNumber",
        "Diagnosis Codes" => "diagnosisCodes",
        "Order Comment" => "orderComment",
        "Prescriptions" => "prescriptions"
    );
    private $RequiredFields = array();

    public function __construct(array $data = null) {
        parent::__construct($data);

        $this->addStylesheet("/outreach/admin/css/orderentry.css");

        $data = $this->UserDAO->getClientProperties();
        if (count($data) > 0) {
            foreach($data as $row) {
                $this->RequiredFields[] = $row['fieldName'];
            }
        }
    }

    public function printPage() {

        $inputFieldsHtml = "";
        foreach ($this->InputFields as $fieldName => $val) {
            $checked = "";
            if (in_array($val, $this->RequiredFields)) {
                $checked = "checked='checked'";
            }
            $inputFieldsHtml .= "<div class='row inputFieldRow'>
                <div class='three mobile fourths pad-left'>$fieldName</div>
                <div class='one mobile fourth'><input type='checkbox' name='inputFields[]' value='$val' $checked /></div>
            </div>";
        }

        $html = "
            <div class='container'>
                <div class='row pad-top pad-bottom'>
                    <div class='one mobile whole'>
                        <h3 style='display: inline;'>Order Entry Required Fields</h3>
                    </div>
                </div>
                <form action='orderentryb.php' method='post' name='frmRequiredFields'>
                <div class='row'>
                    <div class='one mobile half'>
                        <p><b>Select fields to be required:</b></p>
                        $inputFieldsHtml
                    </div>
                    <div class='one mobile half'>

                    </div>
                </div>
                </form>
            </div>";

        echo $html;
    }

}