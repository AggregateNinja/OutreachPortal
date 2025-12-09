<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 1/19/16
 * Time: 1:08 PM
 */

require_once 'PageClient.php';
require_once 'IClient.php';


//require_once 'DOS/JasperReport.php';
require_once 'DAOS/ESigDAO.php';
require_once 'jasper/src/Jaspersoft/Client/Client.php';
require_once 'jasper/src/Jaspersoft/Dto/Resource/File.php';
require_once 'jasper/src/Jaspersoft/Dto/Resource/Folder.php';
require_once 'jasper/src/Jaspersoft/Dto/Resource/Resource.php';

class ESignatureClient extends PageClient implements IClient {

    private $ESigDAO;

    private $ErrorMessages = array();
    private $InputFields = array(
        "fullName" => "",
        "initials" => "",
        "assignTypeId" => "",
        "signatureType" => "",
        "idUtensilTypes" => "",
        "assignTypes" => "",
        "assignedDoctor" => ""
    );

    public $DoctorSignatures = array();

    public $ESignature;


    public function __construct(array $data = null) {

        if ($data == null) {
            $data = array("IncludeRelatedUsers" => true);
        } else {
            $data['IncludeRelatedUsers'] = true;
        }

        parent::__construct($data);

        //echo "<pre>"; print_r($this->User); echo "</pre>";


        $this->ESigDAO = new ESigDAO(array("Conn" => $this->Conn, "userId" => $_SESSION['id']));

        if (isset($_SESSION['ErrorMessages']) && isset($_SESSION['InputFields'])) {
            $this->ErrorMessages = $_SESSION['ErrorMessages'];


            $this->InputFields = $_SESSION['InputFields'];
            //echo "<pre>"; print_r($_SESSION['InputFields']); echo "</pre>";
        } else {

            $eSignature = $this->ESigDAO->getESig();
            $this->ESignature = $eSignature;
            $this->InputFields['assignTypes'] = $this->ESigDAO->getAssignTypes();

            $this->InputFields['assignTypeId'] = self::DefaultESigAssignType;
            if ($eSignature != null) {
                $this->InputFields['signatureType'] = $eSignature->signatureType;
                $this->InputFields['fullName'] = $eSignature->fullName;
                $this->InputFields['initials'] = $eSignature->initials;
                $this->InputFields['idUtensilTypes'] = $eSignature->idUtensilTypes;
                $this->InputFields['assignTypeId'] = $eSignature->assignTypeId;
            }


        }

        if ($this->User->typeId == 2 && count($this->User->DoctorUsers) > 0) {
            $aryDoctorIds = array();
            foreach ($this->User->DoctorUsers as $doctorUser) {
                $aryDoctorIds[] = $doctorUser->iddoctors;
            }

            $this->DoctorSignatures = $this->ESigDAO->getDoctorSignatures($aryDoctorIds);

            //echo "<pre>"; print_r($this->DoctorSignatures); echo "</pre>";
        } else if ($this->User->typeId == 3) {
            $this->DoctorSignatures = $this->ESigDAO->getDoctorSignatures(array($this->User->iddoctors));

        }



        $this->addStylesheet("/outreach/css/signature_pad.css");
        $this->addStylesheet("/outreach/orderentry/css/esignature.css");
        $this->addStylesheet("/outreach/css/bootstrap.css");

        $this->addScript("/outreach/js/signature_pad.js");
        /*$this->addScript("/outreach/js/app.js");*/
        //$this->addScript("/outreach/js/modernizr-2.6.2.min.js");
        //$this->addScript("/outreach/js/groundwork.all.js");
        //$this->addScript("/outreach/js/components/tabs.js");
        $this->addScript("/outreach/js/bootstrap.min.js");
        $this->addScript("js/esignature.js");

        $this->addOverlay("
            <div id='deleteSignatureOverlay' class='rounded'>
                <div class='row'>
                    <div class='one mobile whole' style='text-align: center'>
                        <h4>Are you sure you would like to delete this E-Signature?</h4>
                    </div>
                </div>
                <div class='row'>
                    <div class='one mobile whole' style='text-align: center'>
                        <button class='green' id='yesDelete'>Yes</button>
                        <button class='green' id='noDelete'>No</button>
                    </div>
                </div>
            </div>
        ");
    }

    public function printPage() {

        //echo "<pre>"; print_r($this->User->DoctorUsers); echo "</pre>";

        $fullName = $this->InputFields['fullName'];
        $initials = $this->InputFields['initials'];
        $currType = $this->InputFields['signatureType'];

        if ($this->InputFields['assignTypeId'] == 2) {
            $fullName = "";
            $initials = "";
            $currType = "";

            if ($this->User->typeId == 2) { // client user is logged in
                $doctorUsers = $this->User->DoctorUsers;
                reset($doctorUsers);
                $firstKey = key($doctorUsers);

                if (count($this->User->DoctorUsers) > 0 && array_key_exists($firstKey, $this->DoctorSignatures)) {
                    $fullName = $this->DoctorSignatures[$firstKey]['fullName'];
                    $initials = $this->DoctorSignatures[$firstKey]['initials'];
                    $currType = $this->DoctorSignatures[$firstKey]['signatureType'];
                }
            } else { // doctor user is logged in
                $fullName = $this->DoctorSignatures[$this->User->iddoctors]['fullName'];
                $initials = $this->DoctorSignatures[$this->User->iddoctors]['initials'];
                $currType = $this->DoctorSignatures[$this->User->iddoctors]['signatureType'];
            }


        }

        $fullNameError = "";
        $initialsError = "";
        $sigFileTypeError = "";
        $initFileTypeError = "";
        $sigFileSizeError = "";
        $initFileSizeError = "";

        $fullNameInputClass = "";
        $initialsInputClass = "";
        $sigFileInputClass = "";
        $initFileInputClass = "";

        $markerChecked = "";
        $penChecked = "";

        if (array_key_exists("fullName", $this->ErrorMessages)) {
            $fullNameError = "<span class=\"error_message\" id=\"fullNameError\">" . $this->ErrorMessages['fullName'] . "</span>";
            $fullNameInputClass = "error_input";
        }

        if (array_key_exists("initials", $this->ErrorMessages)) {
            $initialsError = "<span class=\"error_message\" id=\"initialsError\">" . $this->ErrorMessages['initials'] . "</span>";
            $initialsInputClass = "error_input";
        }

        if (array_key_exists("signatureFileType", $this->ErrorMessages)) {
            $sigFileTypeError = "<span class=\"error_message\" id=\"sigFileTypeError\" style=\"text-align: center; display: block;\">" . $this->ErrorMessages['signatureFileType'] . "</span>";
            $sigFileInputClass = "error_input";
        }

        if (array_key_exists("initialsFileType", $this->ErrorMessages)) {
            $initFileTypeError = "<span class=\"error_message\" id=\"initFileTypeError\" style=\"text-align: center; display: block;\">" . $this->ErrorMessages['initialsFileType'] . "</span>";
            $initFileInputClass = "error_input";
        }

        if (array_key_exists("signatureFileSize", $this->ErrorMessages)) {
            $sigFileSizeError = "<span class=\"error_message\" id=\"sigFileSizeError\" style=\"text-align: center; display: block;\">" . $this->ErrorMessages['signatureFileSize'] . "</span>";
            $sigFileInputClass = "error_input";
        }

        if (array_key_exists("initialsFileSize", $this->ErrorMessages)) {
            $initFileSizeError = "<span class=\"error_message\" id=\"initFileSizeError\" style=\"text-align: center; display: block;\">" . $this->ErrorMessages['initialsFileSize'] . "</span>";
            $initFileInputClass = "error_input";
        }

        if ($this->InputFields['idUtensilTypes'] == 2) {
            $penChecked = "checked";
        } else {
            $markerChecked = "checked";
        }

        /*$assignTypesHtml = "";
        if (count($this->InputFields['assignTypes']) > 0) {
            $assignTypesHtml = "<label>E-Signature Type: </label><ul class=\"unstyled zero\">";
            foreach ($this->InputFields['assignTypes'] as $aryAssignType) {
                $assignTypeId = $aryAssignType['idAssignTypes'];
                $assignTypeName = $aryAssignType['typeName'];
                $assignTypeDescription = $aryAssignType['typeDescription'];

                $assignTypeChecked = "";
                if ($assignTypeId == $this->InputFields['assignTypeId']) {
                    $assignTypeChecked = "checked='checked'";
                }

                $assignTypesHtml .= "
                    <li>
                        <input type=\"radio\" name=\"assignTypes\" value=\"$assignTypeId\" $assignTypeChecked />
                        <label for=\"\">$assignTypeName</label>
                    </li>
                ";
            }
            $assignTypesHtml .= "</ul>";
        }*/

        // Make the HTML for the assign type - Per user login or Per doctor
        $assignPerLoginChecked = "checked='checked'";
        $assignPerDoctorChecked = "";
        if ($this->InputFields['assignTypeId'] == 2) {
            $assignPerLoginChecked = "";
            $assignPerDoctorChecked = "checked='checked'";
        }

        $signatureType = 1;
        if ($this->InputFields['signatureType'] != null) {
            $signatureType = $this->InputFields['signatureType'];
        }

        $userDataFullName = "";
        $userDataInitials = "";
        if (isset($this->ESignature)) {
            $userDataFullName = $this->ESignature->fullName;
            $userDataInitials = $this->ESignature->initials;
        }

        if (self::DefaultESigAssignType == 1) {
            $assignTypesHtml = "
            <label>E-Signature Type: </label>
            <ul class=\"unstyled zero\">
                <li>
                    <input type=\"radio\" name=\"assignTypes\" id=\"perLogin\" data-type=\"$signatureType\" data-name=\"$userDataFullName\" data-initials=\"$userDataInitials\" value=\"1\" $assignPerLoginChecked />
                    <label for=\"perLogin\">Per user login</label>
                </li>
                <li>
                    <input type=\"radio\" name=\"assignTypes\" id=\"perDoctor\" value=\"2\" $assignPerDoctorChecked />
                    <label for=\"perDoctor\">Per doctor</label>
                </li>
            </ul>";
        } else {
            $assignTypesHtml = "<input type=\"hidden\" name=\"assignTypes\" id=\"assignTypes\" value=\"2\" />";
        }




        // Make the HTML for the select input for the doctors associated with this client
        $assignedDoctorsHtml = "";
        if ($this->User->typeId == 2 && count($this->User->DoctorUsers) > 0) {

            $assignedDoctorsDisabled = "";
            $assignedDoctorsStyle = "";
            if ($this->InputFields['assignTypeId'] != 2) {
                $assignedDoctorsDisabled = "disabled='disabled'";
                $assignedDoctorsStyle = "display: none;";
            }

            $assignedDoctorsHtml = "
                <label for=\"assignedDoctor\" style=\"$assignedDoctorsStyle\">Select the assigned doctor:</label>
                <select name=\"assignedDoctor\" id=\"assignedDoctor\" $assignedDoctorsDisabled style=\"$assignedDoctorsStyle\">";

            foreach ($this->User->DoctorUsers as $doctorUser) {
                if ($doctorUser->lastName != null && $doctorUser->firstName != null) {
                    $doctorName = $doctorUser->lastName . ", " . $doctorUser->firstName;
                } else{
                    $doctorName = $doctorUser->lastName;
                }

                $dataFullName = "";
                $dataInitials = "";
                $dataType = ""; // drawn or upload
                if (array_key_exists($doctorUser->iddoctors, $this->DoctorSignatures)) {
                    $dataFullName = $this->DoctorSignatures[$doctorUser->iddoctors]['fullName'];
                    $dataInitials = $this->DoctorSignatures[$doctorUser->iddoctors]['initials'];
                    $dataType = $this->DoctorSignatures[$doctorUser->iddoctors]['signatureType'];
                }

                $assignedDoctorsHtml .= "<option data-name=\"$dataFullName\" data-initials=\"$dataInitials\" data-type=\"$dataType\" value=\"$doctorUser->iddoctors\">$doctorName</value>";
            }

            $assignedDoctorsHtml .= "</select>";
        } elseif ($this->User->typeId == 2 && count($this->User->DoctorUsers) == 0) {

            $assignedDoctorsStyle = "";
            if ($this->InputFields['assignTypeId'] != 2) {
                $assignedDoctorsStyle = "display: none;";
            }

            $assignedDoctorsHtml = "<label for=\"assignedDoctor\" style=\"$assignedDoctorsStyle\">No doctors associated with this client</label>";
        } else if ($this->User->typeId == 3) {
            $assignedDoctorsHtml = "<input type=\"hidden\" name=\"assignedDoctor\" id=\"assignedDoctor\" data-name=\"$fullName\" data-initials=\"$initials\" data-type=\"$currType\" value=\"" . $this->User->iddoctors . "\" />";
        }

        $userTypeId = $_SESSION['type'];

        $drawDisabled = false;
        $uploadDisabled = false;

        foreach($this->User->OrderEntrySettings as $setting) {
            if ($setting->idOrderEntrySettings == 9) {
                $drawDisabled = true;
            } else if ($setting->idOrderEntrySettings == 10) {
                $uploadDisabled = true;
            }
        }

        if ($drawDisabled) {
            $tabOneActive = "";
            $tabTwoActive = "active";
        } else if ($uploadDisabled) {
            $tabOneActive = "active";
            $tabTwoActive = "";
        }

        if (self::ESignatureDrawDisabled == true || $this->User->hasOrderEntrySetting(9)) {
            $tabOneActive = "";
            $tabTwoActive = "active";
        } else {
            $tabOneActive = "active";
            $tabTwoActive = "";
        }

        $navTabsHtml = "<ul class=\"nav nav-tabs\" role=\"tablist\">";
        if (self::ESignatureDrawDisabled == false && $drawDisabled == false) {
            $navTabsHtml .= "<li role=\"presentation\" class=\"$tabOneActive\" id=\"drawTab\"><a href=\"#draw\" aria-controls=\"draw\" role=\"tab\" data-toggle=\"tab\">Draw</a></li>";
        }
        if (self::ESignatureUploadDisabled == false && $uploadDisabled == false) {
            $navTabsHtml .= "<li role=\"presentation\" class=\"$tabTwoActive\" id=\"uploadTab\"><a href=\"#upload\" aria-controls=\"upload\" role=\"tab\" data-toggle=\"tab\">Upload</a ></li>";
        }
        $navTabsHtml .= "</ul>";

        $inputDrawDisabled = "0";
        if (self::ESignatureDrawDisabled == true || $this->User->hasOrderEntrySetting(9)) {
            $inputDrawDisabled = "1";
        }

        $inputUploadDisabled = "0";
        if (self::ESignatureUploadDisabled == true || $this->User->hasOrderEntrySetting(10)) {
            $inputUploadDisabled = "1";
        }

        $html = "
        <input type=\"hidden\" name=\"userTypeId\" id=\"userTypeId\" value=\"$userTypeId\" />
        <input type=\"hidden\" name=\"currType\" id=\"currType\" value=\"$currType\" />
        <input type=\"hidden\" name=\"drawDisabled\" id=\"drawDisabled\" value=\"$inputDrawDisabled\" />
        <input type=\"hidden\" name=\"uploadDisabled\" id=\"uploadDisabled\" value=\"$inputUploadDisabled\" />
        <div class=\"container\" data-compression=\"80\" id=\"wrapper\" style=\"margin-top: 15px;\">
            <div class=\"row\">
                <div class=\"three mobile fifths centered\">
                    <div class=\"row\">
                        <div class=\"two mobile thirds pad-right\">
                            <label for=\"fullName\" class=\"bold\">Full Name: </label>
                            <input type=\"text\" name=\"fullName\" id=\"fullName\" class=\"$fullNameInputClass\" value=\"$fullName\" />
                            $fullNameError
                        </div>
                        <div class=\"one mobile third\">
                            <label for=\"initials\" class=\"bold\">Initials: </label>
                            <input type=\"text\" name=\"initials\" id=\"initials\" class=\"$initialsInputClass\" value=\"$initials\"/>
                            $initialsError
                        </div>
                    </div>
                </div>
            </div>
            <div class=\"row\">
                <div class=\"three mobile fifths centered\">
                    <div class=\"row\">
                        <div class=\"two mobile thirds\">
                            $assignTypesHtml
                        </div>
                        <div class=\"one mobile third\" style=\"padding-bottom: 5px;\">
                            $assignedDoctorsHtml
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nav tabs -->
            $navTabsHtml

            <!-- Tab panes -->
            <div class=\"tab-content\">
                <div role=\"tabpanel\" class=\"tab-pane $tabOneActive\" id=\"draw\">
                    <h4>Draw your electronic signature</h4>
                    <form action=\"settingsb.php\" method=\"post\" name=\"frmDrawESig\" id=\"frmDrawESig\" class=\"frmESig\">
                        <div class=\"row pad-top\" style=\"background: #f4f4f4;\">
                            <div class=\"one mobile half padded\" id=\"signaturePadContainer\">
                                <div class=\"inputDraw\" id=\"signature-pad-name\">
                                    <div class=\"m-signature-pad--body\">
                                        <canvas id=\"nameCanvas\" class=\"box_shadow\"></canvas>
                                        <div class=\"signatureLine\">X</div>
                                    </div>
                                    <div class=\"m-signature-pad--footer\">
                                        <div class=\"description\">Draw Signature</div>
                                        <button type=\"button\" class=\"button clear\" id=\"nameClear\" data-action=\"clear\">Clear</button>
                                    </div>                          
                                </div>

                            </div>
                            <div class=\"one mobile half padded\" id=\"initialsPadContainer\">
                                <div class=\"inputDraw\" id=\"signature-pad-initials\">
                                    <div class=\"m-signature-pad--body\">
                                        <canvas id=\"initialsCanvas\" class=\"box_shadow\"></canvas>
                                        <div class=\"signatureLine\">X</div>
                                    </div>
                                    <div class=\"m-signature-pad--footer\">
                                        <div class=\"description\">Draw Initials</div>
                                        <button type=\"button\" class=\"button clear\" id=\"initialsClear\" data-action=\"clear\">Clear</button>
                                    </div>
                                </div>                                    

                            </div>
                            <div class=\"one mobile whole pad-left pad-right pad-bottom\">
                                <input type=\"radio\" name=\"idUtensilTypes\" id=\"marker\" value=\"1\" $markerChecked> Marker
                                <input type=\"radio\" name=\"idUtensilTypes\" id=\"pen\" value=\"2\" $penChecked> Pen
                            </div>
                        </div>
                        <div class=\"row\">
                            <div class=\"one movile whole padded\">
                                <p>By clicking Create, I agree that the signature and initials will be the electronic representation of my signature and initials
                                for all purposes when I (or my agent) use them on documents, including legally binding contracts - just the same as a pen-and-paper
                                signature or initial.</p>
                            </div>
                        </div>
                        <div class=\"row\">
                            <div class=\"one mobile whole pad-top pad-left\">
                                <button class=\"green submit\" id=\"btnCreate\">Create</button>
                                <a href=\"javascript:void(0)\" id=\"btnDelete\" class=\"button btnDelete\">Delete</a>
                            </div>
                        </div>
                        <input type=\"hidden\" name=\"signatureType\" value=\"1\" />
                        <input type=\"hidden\" name=\"initialsType\" value=\"1\" />
                        <input type=\"hidden\" name=\"action\" value=\"2\" />
                    </form>
                </div>
                <div role=\"tabpanel\" class=\"tab-pane $tabTwoActive\" id=\"upload\">
                    <h4>Upload an image of your electronic signature</h4>
                    <form action=\"settingsb.php\" method=\"post\" name=\"frmImageESig\" id=\"frmImageESig\" enctype=\"multipart/form-data\" class=\"frmESig\">
                        <div class=\"row pad-top\" style=\"background: #f4f4f4;\">
                            <div class=\"one mobile half padded\">

                                <div class=\"inputUpload\" id=\"sigGroup\">
                                    <i class=\"icon icon-upload-alt\"></i>
                                    <a href=\"javascript:void(0)\" class=\"filebutton $sigFileInputClass\" name=\"signatureFile\" id=\"signatureFile\">Upload Signature</a>
                                    <input type=\"file\" name=\"signatureFileInput\" id=\"signatureFileInput\" />
                                    $sigFileTypeError
                                    $sigFileSizeError
                                </div>
                            </div>
                            <div class=\"one mobile half padded\">
                                <div class=\"inputUpload\" id=\"initGroup\">
                                    <i class=\"icon icon-upload-alt\"></i>
                                    <a href=\"javascript:void(0)\" class=\"filebutton $initFileInputClass\" name=\"initialsFile\" id=\"initialsFile\">Upload Initials</a>
                                    <input type=\"file\" name=\"initialsFileInput\" id=\"initialsFileInput\" />
                                    $initFileTypeError
                                    $initFileSizeError
                                </div>
                            </div>
                            <div class=\"one mobile whole pad-left pad-right pad-bottom\">
                                <p>Accepted File Formats: GIF, JPG, PNG. Max file size 500KB.</p>
                            </div>
                        </div>
                        <div class=\"row\">
                            <div class=\"one movile whole padded\">
                                <p>By clicking Create, I agree that the signature and initials will be the electronic representation of my signature and initials
                                for all purposes when I (or my agent) use them on documents, including legally binding contracts - just the same as a pen-and-paper
                                signature or initial.</p>
                            </div>
                        </div>
                        <div class=\"row\">
                            <div class=\"one mobile whole pad-top pad-left\">
                                <button class=\"green submit\" id=\"btnCreate\">Create</button>
                                <a href=\"javascript:void(0)\" id=\"btnDelete\" class=\"button btnDelete\">Delete</a>
                            </div>
                        </div>
                        <input type=\"hidden\" name=\"signatureType\" value=\"2\" />
                        <input type=\"hidden\" name=\"initialsType\" value=\"2\" />
                        <input type=\"hidden\" name=\"idUtensilTypes\" value=\"" . $this->InputFields['idUtensilTypes'] . "\" />
                        <input type=\"hidden\" name=\"action\" value=\"2\" />
                    </form>
                </div>
            </div>
          </div>
        ";
        echo $html;
    }
} 