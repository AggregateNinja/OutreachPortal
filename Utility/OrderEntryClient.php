<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//require_once 'DAOS/DataConnect.php';
require_once 'DAOS/DoctorDAO.php';
require_once 'DAOS/ClientDAO.php';
require_once 'DAOS/PreferencesDAO.php';
require_once 'DAOS/EntryOrderDAO.php';
require_once 'DAOS/DrugDAO.php';
require_once 'DAOS/InsuranceDAO.php';
require_once 'DAOS/LocationDAO.php';
require_once 'DAOS/ReportTypeDAO.php';
require_once 'DAOS/PhlebotomyDAO.php';
require_once 'DAOS/TestDAO.php';
require_once 'DAOS/OrderDAO.php';
require_once 'PageClient.php';
require_once 'DOS/EntryOrder.php';

require_once 'DAOS/ESigDAO.php';

class OrderEntryClient extends PageClient {
	
	private $Conn;
	
	private $Drugs;
	private $PocTests;
	//private $Insurances;

	private $Locations;
	private $ReportTypes;
	private $Phlebotomists;

    private $Prescriptions = array();

    private $MainTitle = "New Order";
    private $MainTooltip = "Enter and submit information for a new order to be reviewed and reported by the lab.";

    // https://gist.github.com/maxrice/2776900#gistcomment-869296
    public $States = array(
        'AL'=>'Alabama',
        'AK'=>'Alaska',
        'AS'=>'American Samoa',
        'AZ'=>'Arizona',
        'AR'=>'Arkansas',
        'CA'=>'California',
        'CO'=>'Colorado',
        'CT'=>'Connecticut',
        'DE'=>'Delaware',
        'DC'=>'District of Columbia',
        'FM'=>'Federated States of Micronesia',
        'FL'=>'Florida',
        'GA'=>'Georgia',
        'GU'=>'Guam GU',
        'HI'=>'Hawaii',
        'ID'=>'Idaho',
        'IL'=>'Illinois',
        'IN'=>'Indiana',
        'IA'=>'Iowa',
        'KS'=>'Kansas',
        'KY'=>'Kentucky',
        'LA'=>'Louisiana',
        'ME'=>'Maine',
        'MH'=>'Marshall Islands',
        'MD'=>'Maryland',
        'MA'=>'Massachusetts',
        'MI'=>'Michigan',
        'MN'=>'Minnesota',
        'MS'=>'Mississippi',
        'MO'=>'Missouri',
        'MT'=>'Montana',
        'NE'=>'Nebraska',
        'NV'=>'Nevada',
        'NH'=>'New Hampshire',
        'NJ'=>'New Jersey',
        'NM'=>'New Mexico',
        'NY'=>'New York',
        'NC'=>'North Carolina',
        'ND'=>'North Dakota',
        'MP'=>'Northern Marina Islands',
        'OH'=>'Ohio',
        'OK'=>'Oklahoma',
        'OR'=>'Oregon',
        'PW'=>'Palau',
        'PA'=>'Pennsylvania',
        'PR'=>'Puerto Rico',
        'RI'=>'Rhode Island',
        'SC'=>'South Carolina',
        'SD'=>'South Dakota',
        'TN'=>'Tennessee',
        'TX'=>'Texas',
        'UT'=>'Utah',
        'VT'=>'Vermont',
        'VI'=>'Virgin Islands',
        'VA'=>'Virginia',
        'WA'=>'Washington',
        'WV'=>'West Virginia',
        'WI'=>'Wisconsin',
        'WY'=>'Wyoming'
    );

    protected $InputFields = array (
        // ------------------- order inputs
        "orderType" => "",
        "idOrders" => "",
        "isAdvancedOrder" => "",
        "typeId" => "",
        "hasPMByDepartment" => 0,
        "defaultReportType" => "",
        "defaultReportTypeName" => "",
        "accession" => "",
        "clientId" => 0,
        "doctorId" => 0,
        "reportType" => "",
        "specimenDate" => "",
        "orderDate" => "",
        "roomNumber" => "",
        "bedNumber" => "",
        "orderComment" => "",
        "locationId" => 0,
        "isFasting" => false,
        // ------------------- patient inputs
        //"sameSubscriber" => true,
        "idPatients" => "",
        "patientLastName" => "",
        "patientFirstName" => "",
        "patientMiddleName" => "",
        "patientId" => "",
        "patientDob" => "",
        "patientEmail" => "",
        "patientGender" => "",
        "patientSpecies" => "",
        "patientEthnicity" => "",
        "patientSsn" => "",
        "patientAge" => "",
        "patientHeightFeet" => "",
        "patientHeightInches" => "",
        "patientWeight" => "",
        "patientAddress1" => "",
        "patientAddress2" => "",
        "patientCity" => "",
        "patientState" => "",
        "patientZip" => "",
        "patientPhone" => "",
        "patientWorkPhone" => "",
        "patientSmoker" => "",
        "relationship" => "",
        "patientRelationship" => "",
        "patientSource" => "",
        "patientSubscriber" => "",
        // ------------------- subscriber inputs
        "idSubscriber" => "",
        "subscriberLastName" => "",
        "subscriberFirstName" => "",
        "subscriberMiddleName" => "",
        "subscriberId" => "",
        "subscriberDob" => "",
        "subscriberAge" => "",
        "subscriberGender" => "",
        "subscriberSsn" => "",
        "subscriberAddress1" => "",
        "subscriberAddress2" => "",
        "subscriberCity" => "",
        "subscriberState" => "",
        "subscriberZip" => "",
        "subscriberSource" => "",
        "subscriberPhone" => "",
        "subscriberWorkPhone" => "",
        // ------------------- insurance inputs
        "insuranceId" => "",
        "secondaryInsuranceId" => "",
        "insurance" => "",
        "secondaryInsurance" => "",
        "policyNumber" => "",
        "secondaryPolicyNumber" => "",
        "groupNumber" => "",
        "secondaryGroupNumber" => "",
        "medicareNumber" => "",
        "medicaidNumber" => "",
        // ------------------- phlebotomy inputs
        "idPhlebotomy" => "",
        "idAdvancedOrder" => "",        
        "frequency" => "",
        "timesToDraw" => "",
        "continuous" => "",
        "startsOn" => "",
        "phlebotomist" => "",
        "phlebComment1" => "",
        "phlebComment2" => "",
        // ------------------- array variables
        "selectedTests" => "",
        "pocResults" => "",
        "prescribedDrugs" => array(),
        "selectedCodes" => array(),
        "selectedCommonCodes" => "",        
        // ------------------- other		
        "advancedOrderOnly" => false,
        "isNewPatient" => true,
        "isNewSubscriber" => true,
        "printESignature" => true,
        "ClientDoctorsOnly" => false,
        "eSignatureSet" => false
    );

    private $Insurance;
    private $SecondaryInsurance;

    private $ErrorMessages = array();
    private $IsRejectedForm = false;
    private $IsOrderEdit = false;
    private $clientName = "";

    //private $Preference;

    private $Action;

    private $IsNewPatient;
    private $IsNewSubscriber;
    private $SubscriberChanged;
    
    private $Styles = array(
        "reselectPatient" => "display: none;"
        
    );    
    private $PatientFieldsDisabled = "";
    
    private $NextAccession;

    private $UseOldOrderInfoFormat = true;

    public $AllowReportTypeSelection = self::AllowReportTypeSelection;
    public $RequireAdditionalOrderEntryFields = self::RequireAdditionalOrderEntryFields;
    public $CommonTestsFormat = self::CommonTestsFormat;
    public $SpecimenDateColHeader = self::SpecimenDateColHeader;
    public $HasESignatureOnReq = self::HasESignatureOnReq;
    public $HasCheckEligibility = self::HasCheckEligibility;
    public $HasDiagnosisValidity = self::HasDiagnosisValidity;
    public $OrderEntryPatientEmail = self::OrderEntryPatientEmail;
    public $HideOEInsurance = self::HideOEInsurance;
    public $HideOETests = self::HideOETests;
    public $HideOEComments = self::HideOEComments;
    public $HideOEPrescriptions = self::HideOEPrescriptions;
    public $OrderEntryAccessionSelectable = self::OrderEntryAccessionSelectable;
    public $OrderEntryDocumentsEnabled = self::OrderEntryDocumentsEnabled;
    public $OEPOCBGRowColor = self::OEPOCBGRowColor;

    private $PocDisabled = false;

    private $DefaultICD10 = false;
    private $ICD10Cutoff = "";
    private $DailyProcessingUseOrderDate = false;

    public $ExcludeReferenceTests = false;

    private $Icd9IsDisabled = false;
    private $Icd10IsDisabled = false;

    public $entryOrder = null;

    public $AdditionalRequiredFields = array();

    public $CommonTestsOnly = false;
    public $CommonCodesOnly = false;
    public $CommonDrugsOnly = false;
    public $UsePrevScripts = false;
    public $UsePrevIcdCodes = false;

    public $SpecimenTypes = array();

    public $OrderComments = array();

    public $RoomNumberDisabled = self::RoomNumberDisabled;
    public $BedNumberDisabled = self::BedNumberDisabled;
    public $PatientHeightDisabled = self::PatientHeightDisabled;
    public $PatientWeightDisabled = self::PatientWeightDisabled;
    public $PatientSmokerDisabled = self::PatientSmokerDisabled;

    public $DiagnosisValidity = array();

    public $UserLocationId = 0;
    public $HasMultiLocation = self::HasMultiLocation;

    public $OverlappingPanelsEnabled = false;
    public $PatientPrescriptionsEnabled = false;

    public $DocumentInputs = array();

    public function __construct(array $settings = null) {

        $arySettings = array(
            "IncludeDetailedInfo" => true,
            "IncludeCommonCodes" => true,
            "IncludePreferences" => true,
            "IncludeCommonTests" => true,
            "IncludeExcludedTests" => true,
            "IncludeRelatedUsers" => true,
            "IncludeCommonDrugs" => true
        );

        if (isset($_GET['action']) && $_GET['action'] == "edit" && isset($_GET['id']) && !empty($_GET['id'])) {
            $arySettings['EditingWebOrder'] = $_GET['id'];
        }

        parent::__construct($arySettings);

        $this->addScript("/outreach/js/plugins/jquery-responsiveText.js");
        $this->addScript("/outreach/js/plugins/jquery-truncateLines.js");

        $this->addScript("/outreach/js/velocity.min.js");
        $this->addScript("/outreach/js/tooltip.js");
        $this->addScript("/outreach/orderentry/js/documents.js");

        $data = $this->UserDAO->getClientProperties();

        if (count($data) > 0) {
            foreach($data as $row) {
                $this->AdditionalRequiredFields[] = $row['fieldName'];
            }
        }

        $eSigDAO = new ESigDAO(array("Conn" => $this->Conn, "userId" => $_SESSION['id']));
        $eSignature = $eSigDAO->getESig();

        if (isset($eSignature)) {
            $this->InputFields['eSignatureSet'] == true;
        }

        $this->resetSession();

        $this->Conn = $this->UserDAO->Conn;

        $this->setDataObjects();

        $aryFileNames = array();
        if (isset($_SESSION['ErrorMessages']) && is_array($_SESSION['ErrorMessages']) && count($_SESSION['ErrorMessages']) > 0 &&
            isset($_SESSION['InputFields']) && is_array($_SESSION['InputFields']) && count($_SESSION['InputFields']) > 0) { // the form failed validation and redirected the user back to the add page

            $this->setUpForPostBack();

        } else if (isset($_GET['action']) && $_GET['action'] == "edit" && isset($_GET['id']) && !empty($_GET['id'])) {
            $this->setUpForEdit();

            $isValid = true;
            if ($this->User->typeId == 2 && $this->User->idClients != $this->InputFields['clientId']) {
                // this accession belongs to a different client
                $isValid = false;
            } elseif ($this->User->typeId == 3 && $this->User->iddoctors != $this->InputFields['doctorId']) {
                // this accession belongs to a difference doctor
                $isValid = false;
            }

            if (isset($this->AdminUser) && $this->AdminUser instanceof AdminUser && $this->AdminUser->typeId == 7
                && isset($_SESSION['AdminId']) && !empty($_SESSION['AdminId']) && isset($_SESSION['AdminType']) && $_SESSION['AdminType'] == 7
                && in_array($this->InputFields['clientId'], $this->AdminUser->adminClientIds)) {
                $isValid = true;
            }

            if (!$isValid) {
                $this->logout(true);
                header("Location: " . $this->SiteUrl);
                exit();
            }

            $aryFileNames = EntryOrderDAO::getDocuments($_GET['id'], "image");

        } else {
            if (!$this->OrderEntryAccessionSelectable) {
                $this->InputFields['accession'] = EntryOrderDAO::getNewAccession();
            }


            $this->Action = "add";

            if ($this->User->hasOrderEntrySetting(8)) {
                if ($this->User->typeId == 2) {
                    $this->InputFields["patientAddress1"] = $this->User->clientStreet;
                    $this->InputFields["patientAddress2"] = $this->User->clientStreet2;
                    $this->InputFields["patientCity"] = $this->User->clientCity;
                    $this->InputFields["patientState"] = $this->User->clientState;
                    $this->InputFields["patientZip"] = $this->User->clientZip;
                } else if ($this->User->typeId == 3) {
                    $this->InputFields["patientAddress1"] = $this->User->address1;
                    $this->InputFields["patientAddress2"] = $this->User->address2;
                    $this->InputFields["patientCity"] = $this->User->city;
                    $this->InputFields["patientState"] = $this->User->state;
                    $this->InputFields["patientZip"] = $this->User->zip;
                }

            }
        }

        $dteICD10Cutoff = new DateTime($this->ICD10Cutoff);
        if ($this->DailyProcessingUseOrderDate == true || empty($this->InputFields['specimenDate']) || array_key_exists("specimenDate", $this->ErrorMessages)) {
            $dteDate = new DateTime(); // the orderDate is always set to the current date time for Web orders
        } else {
            $dteDate = new DateTime($this->InputFields['specimenDate']);
        }

        if ($dteICD10Cutoff <= $dteDate) {// ICD 9 is disabled
            if (!empty($this->InputFields['selectedCodes'])) {
                if (isset($this->entryOrder->DiagnosisCodes)) {
                    foreach ($this->entryOrder->DiagnosisCodes as $code) {
                        if ($code->version == 9) {
                            $this->Icd10IsDisabled = true;
                        } else {
                            $this->Icd9IsDisabled = true;
                        }
                    }
                }

                if ($this->Icd9IsDisabled == true && $this->Icd10IsDisabled == true) {
                    $this->Icd10IsDisabled = false;
                }
            }

            $this->Icd9IsDisabled = true;

        } else {
            if (!empty($this->InputFields['selectedCodes'])) {
                foreach ($this->entryOrder->DiagnosisCodes as $code) {
                    if ($code->version == 9) {
                        $this->Icd10IsDisabled = true;
                    }
                }
            }
        }

        $this->InputFields['typeId'] = $_SESSION['type'];

        $this->UseOldOrderInfoFormat = self::UseOldOrderInfoFormat;

        if (isset($this->AdminUser) && $this->AdminUser instanceof AdminUser && $this->AdminUser->typeId == 7
            && isset($_SESSION['AdminId']) && !empty($_SESSION['AdminId']) && isset($_SESSION['AdminType']) && $_SESSION['AdminType'] == 7) {
            if ($this->AdminUser->hasOrderEntrySetting(2)) {
                $this->CommonTestsOnly = true;
            }
            if ($this->AdminUser->hasOrderEntrySetting(3)) {
                $this->CommonCodesOnly = true;
            }
            if ($this->AdminUser->hasOrderEntrySetting(4)) {
                $this->CommonDrugsOnly = true;
            }
            if ($this->AdminUser->hasOrderEntrySetting(5)) {
                $this->UsePrevScripts = true;
            }
            if ($this->AdminUser->hasOrderEntrySetting(7)) {
                $this->UsePrevIcdCodes = true;
            }
        } else {
            if ($this->User->hasOrderEntrySetting(2)) {
                $this->CommonTestsOnly = true;
            }
            if ($this->User->hasOrderEntrySetting(3)) {
                $this->CommonCodesOnly = true;
            }
            if ($this->User->hasOrderEntrySetting(4)) {
                $this->CommonDrugsOnly = true;
            }
            if ($this->User->hasOrderEntrySetting(5)) {
                $this->UsePrevScripts = true;
            }
            if ($this->User->hasOrderEntrySetting(7)) {
                $this->UsePrevIcdCodes = true;
            }
        }



        $this->SpecimenTypes = TestDAO::getSpecimenTypes($this->Conn);

        $this->OrderComments = OrderDAO::getOrderCommentRemarks($this->Conn);

        if (self::HasMultiLocation == true) {
            $this->UserLocationId = $this->User->idLocation;
        }

        $this->addStylesheet("css/styles.css");
        $this->addStylesheet("/outreach/css/datepicker.css");
        $this->addStylesheet("/outreach/css/dropdown.css");
        //$this->addStylesheet("/outreach/css/bootstrap-grid.css");

        $this->addScript("/outreach/js/common.js");

        $this->addScript("js/validate.js");

        $this->addScript("js/scripts.drugs.js");
        $this->addScript("js/scripts.subscriber.js");
        $this->addScript("js/scripts.test.js");
        $this->addScript("js/scripts.patient.js");

        $this->addScript("js/scripts.insurance.js");

        $this->addScript("/outreach/js/datepicker.js");
        $this->addScript("/outreach/js/datepicker.en.js");

        $this->addScript("/outreach/js/velocity.min.js");
        $this->addScript("/outreach/js/tooltip.js");

        $this->addScript("js/main.js");

        $this->addOverlay("
            <div id='subscriberInsuranceOverlay' class='rounded'>
                <div class='row'>
                    <div class='one mobile whole' style='text-align: center'>
                        <div id='ins'></div>
                    </div>
                </div>
                <div class='row'>
                    <div class='one mobile whole' style='text-align: center'>
                        <h5>Would you like to use this subscriber's insurance on this order?</h5>
                    </div>
                </div>
                <div class='row'>
                    <div class='one mobile whole' style='text-align: center'>
                        <a href='javascript:void(0)' id='insYes' class='button'>Yes</a>
                        <a href='javascript:void(0)' id='insNo' class='button'>No</a>
                    </div>
                </div>
            </div>
        ");
        $this->addOverlay("
            <div id='patientInsuranceOverlay' class='rounded'>
                <div class='row'>
                    <div class='one mobile whole' style='text-align: center'>
                        <div id='pIns'></div>
                    </div>
                </div>
                <div class='row'>
                    <div class='one mobile whole' style='text-align: center'>
                        <h5>Would you like to use this patient's insurance on this order?</h5>
                    </div>
                </div>
                <div class='row'>
                    <div class='one mobile whole' style='text-align: center'>
                        <a href='javascript:void(0)' id='pInsYes' class='button'>Yes</a>
                        <a href='javascript:void(0)' id='pInsNo' class='button'>No</a>
                    </div>
                </div>
            </div>
        ");

        $this->addOverlay("
        <div id='patientPrescriptionsOverlay' class='rounded'>
            <div class='row'>
                <div class='one mobile whole' style='text-align: center'>
                    <h5>The following prescriptions were selected on the previous order for this patient:</h5>
                </div>
            </div>
            <div class='row'>
                <div class='one mobile whole' id='prevScriptsContainer'>
                    <div class='row'>
                        <div class='three mobile fifths'><label style='font-weight: bold'>Generic Name</label></div>
                        <div class='two mobile fifths'><label style='font-weight: bold'>Substance</label></div>
                    </div>
                </div>
            </div>
            <div class='row'>
                <div class='one mobile whole' style='text-align: center'>
                    <h5>Would you like to add these prescriptions to this order?</h5>
                </div>
            </div>
            <div class='row'>
                <div class='one mobile whole' style='text-align: center'>
                    <a href='javascript:void(0)' id='pScriptsYes' class='button'>Yes</a>
                    <a href='javascript:void(0)' id='pScriptsNo' class='button'>No</a>
                </div>
            </div>
        </div>
        ");

        $this->addOverlay("
        <div id='patientIcdOverlay' class='rounded'>
            <div class='row'>
                <div class='one mobile whole' style='text-align: center'>
                    <h5>The following ICD codes were selected on the previous order for this patient:</h5>
                </div>
            </div>
            <div class='row'>
                <div class='one mobile whole' id='prevCodesContainer'>
                    <div class='one mobile fifth'><label style='font-weight: bold'>ICD Code</label></div>
                    <div class='four mobile fifths'><label style='font-weight: bold'>Description</label></div>
                </div>
            </div>
            <div class='row'>
                <div class='one mobile whole' style='text-align: center'>
                    <h5>Would you like to add these ICD codes to this order?</h5>
                </div>
            </div>
            <div class='row'>
                <div class='one mobile whole' style='text-align: center'>
                    <a href='javascript:void(0)' id='pCodesYes' class='button'>Yes</a>
                    <a href='javascript:void(0)' id='pCodesNo' class='button'>No</a>
                </div>
            </div>
        </div>
        ");

        $this->addOverlay("
            <div id='icdOverlay' class='rounded'>
                <div class='row'>
                    <div class='one mobile whole' style='text-align: center'>
                        <h5>Warning</h5>
                    </div>
                </div>
                <div class='row'>
                    <div class='one mobile whole' style='text-align: center'>
                        <p>The specimen date is after the ICD 10 cutoff date. All ICD 9 codes on this order will be cleared.</p>
                        <h5>Would you like to continue?</h5>
                    </div>
                </div>
                <div class='row'>
                    <div class='one mobile whole' style='text-align: center'>
                        <a href='javascript:void(0)' id='icdYes' class='button'>Yes</a>
                        <a href='javascript:void(0)' id='icdNo' class='button'>No</a>
                    </div>
                </div>
            </div>
        ");
        $this->addOverlay("
            <div id='eligibilityOverlay' class='rounded'>
                <div class='row'>
                    <div class='one mobile half'><b>Patient Eligibility</b></div>
                    <div class='one mobile half'><a href='javascript:void(0)' class='button eligibilityClose' style='float: right;'>X</a></div>
                </div>                
                <div class='row'>
                    <div class='one mobile whole' id='eligibilityBody'></div>
                </div>
                <div class='row'>
                    <div class='one mobile whole' style='text-align: center'>
                        <a href='javascript:void(0)' class='button eligibilityClose'>Close</a>
                    </div>
                </div>
            </div>
        ");

        $documentPreviews = "";
        if (count($aryFileNames) > 0) {
            //$filePath = $_SERVER['DOCUMENT_ROOT'] . "/outreach/orderentry/documents/";
            $filePath = self::OrderEntryDocumentsFilePath;
            $i = 1;
            foreach ($aryFileNames as $fileName) {
                $elemId = substr($fileName, 0, strrpos($fileName, "."));
                if (file_exists($filePath . $fileName)) {
                    $imageFile = "data:" . mime_content_type($filePath . $fileName) . ";base64," . base64_encode(file_get_contents($filePath . $fileName));
                    $this->DocumentInputs[] = "<input type='hidden' id='$elemId' class='docs' name='docUrl[]' value='$imageFile' />";
                    $previewElem = "<img id='$elemId' src='$imageFile' alt='Document Preview' class='docPreview' />";
                    if (substr($fileName, strpos($fileName, ".") + 1) == "pdf") {
                        $previewElem = "<p id='$elemId' src='$imageFile' class='docPreview'>$fileName</p>";
                    }

                    $documentPreviews  .= "<div class='row docPreviewRow'>
                        <div class='three mobile fourths'>$previewElem</div>
                        <div class='one mobile fourth'><a href='javascript:void(0)' class='button removeDocument'>X</a></div>
                    </div>";
                }
                $i++;
            }
        }

        $this->addOverlay("
            <div id='saveImagesOverlay' class='rounded'>
                <form action='indexb.php' method='post' name='frmDocuments' id='frmDocuments' enctype='multipart/form-data'>
                <div class='row' style='margin-bottom: 15px'>
                    <div class='one mobile whole'>
                        <h4 style='margin-bottom: 0;'>Attach Documents to Order</h4>
                        <p>Note: This order must be submitted for these documents to be saved</p>
                    </div>
                </div>       
                <div class='row'>
                    <div class='one mobile whole' id='documentsPreview'>
                        $documentPreviews
                    </div>
                </div>         
                <div class='row' style='margin-top: 15px'>
                    <div class='one mobile half'>
                        <a href='javascript:void(0)' class='filebutton button' name='uploadDocument' id='uploadDocument'>Upload Document</a>
                        <input type='file' name='documentFileInput' id='documentFileInput' />
                    </div>
                    <div class='one mobile half'>
                        <a href='javascript:void(0)' class='button saveImagesClose' style='float: right;'>Close</a>
                    </div>
                </div>
                
                </form>
            </div>
        ");


        if ($this->User instanceof ClientUser && (!isset($_GET['action']) || $_GET['action'] == "add") && $this->User->hasOrderEntrySetting(6)) {
            // get auto tests for this client
            $aryAutoTests = ClientDAO::getAutoTests($this->User->idClients, $this->Conn);
            if (count($aryAutoTests) > 0) {
                $this->User->setAutoTests($aryAutoTests);

                $clientName = $this->User->clientName;
                $autoTestHtml = "<ul>
                <div class='row' style='border-bottom: 1px solid #CCCCCC;'>
                  <div class='two mobile elevenths'><p style='font-weight: bold;'>#</p></div>
                  <div class='three mobile elevenths pad-left pad-right testNameCol'><p style='font-weight: bold;'>Test Name</p></div>
                  <div class='three mobile elevenths pad-left hide-on-mobile hide-on-small-tablet' style='text-align:center;'><p style='font-weight: bold;'>Department</p></div>
                  <div class='three mobile elevenths pad-left'style='text-align:center;'><p style='font-weight: bold;'>Specimen Type</p></div>
               </div>";

                foreach ($this->User->getAutoTests() as $test) {

                    $style = "style='color: #1D7F46;'";
                    $title = "";
                    if ($test->testType == 0) {
                        $style = "style='color: #1D7F46;font-weight: bold;'";
                        $title = "title='This is a panel test.'";
                    }

                    $autoTestHtml .= "
                    <div class='row selectTest' id='$test->idtests' $style $title>
                        <div class='two mobile elevenths' id='$test->idtests'>$test->number</div>
                        <div class='three mobile elevenths pad-left pad-right testNameCol' id='$test->testType'>$test->name</div>
                        <div class='three mobile elevenths pad-left pad-right hide-on-mobile hide-on-small-tablet' id='$test->promptPOC' style='text-align: center;'>$test->deptName</div>
                        <div class='three mobile elevenths pad-left pad-right' style='text-align: center;'>$test->specimenTypeName</div>
                     </div>
                    ";
                }
                $this->addOverlay("
                    <div id='autoTestOverlay' class='rounded'>
                        <div class='row'>
                            <div class='one mobile whole' style='text-align: center'>
                                <h5>$clientName has the following auto-tests:</h5>
                            </div>
                            <div class='one mobile whole'>
                                $autoTestHtml
                            </div>
                            <div class='one mobile whole' style='text-align: center'>
                                <h5>Would you like to add them to this order?</h5>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='one mobile whole' style='text-align: center'>
                                <a href='javascript:void(0)' id='autoTestYes' class='button'>Yes</a>
                                <a href='javascript:void(0)' id='autoTestNo' class='button'>No</a>
                            </div>
                        </div>
                    </div>
                ");
            }

        } else {
            // get auto tests for list of clients
        }

        if ($this->PatientPrescriptionsEnabled && $this->IsOrderEdit == false) {
            $this->addOverlay("
                <div id='patientPrescriptionsOverlay2' class='rounded'>
                    <div class='row'>
                        <div class='one mobile whole' style='text-align: center'>
                            <h5>This patient has the following saved prescriptions:</h5>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='one mobile whole' id='patientScriptsContainer'>
                            <div class='row'>
                                <div class='three mobile fifths'><label style='font-weight: bold'>Generic Name</label></div>
                                <div class='two mobile fifths'><label style='font-weight: bold'>Substance</label></div>
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='one mobile whole' style='text-align: center'>
                            <h5>Would you like to add these prescriptions to this order?</h5>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='one mobile whole' style='text-align: center'>
                            <a href='javascript:void(0)' id='pScriptsYes2' class='button'>Yes</a>
                            <a href='javascript:void(0)' id='pScriptsNo2' class='button'>No</a>
                        </div>
                    </div>
                </div>
            ");
            $this->addOverlay("
                <div id='patientPrescriptionsOverlay3' class='rounded'>
                    <div class='row'>
                        <div class='one mobile whole' style='text-align: center'>
                            <h5>The prescriptions on this order do not match the prescriptions on the patient record.</h5>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='one mobile whole' style='text-align: center'>
                            <h5>Would you like to update the saved prescriptions for this patient?</h5>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='one mobile whole' style='text-align: center'>
                            <a href='javascript:void(0)' id='pScriptsYes3' class='button'>Yes</a>
                            <a href='javascript:void(0)' id='pScriptsNo3' class='button'>No</a>
                        </div>
                    </div>
                </div>
            ");
        }


    }
    
    private function setDataObjects() {
    	//$this->Preferences = PreferencesDAO::getPreferences(array("Conn" => $this->Conn));
        $pref1 = PreferencesDAO::getPreferenceByKey('PMByDepartment', array("Conn" => $this->Conn));
        // http://stackoverflow.com/a/7336873/3099782
        if ($pref1 != null && $pref1->value === 'true') { // evaluate the string value of 'true'
            $this->InputFields['hasPMByDepartment'] = 1;
        }
        if ($this->User->typeId == 2 && $this->User->defaultReportType != null && trim($this->User->defaultReportType) != "") {
            $this->InputFields['defaultReportType'] = $this->User->defaultReportType;
            $rt = ReportTypeDAO::getReportType($this->User->defaultReportType);
            if (!is_bool($rt) && $rt instanceof ReportType) {
                $this->InputFields['defaultReportTypeName'] = $rt->name;
            }
        } else {
            $pref2 = PreferencesDAO::getPreferenceByKey('DefaultResultReport', array("Conn" => $this->Conn));
            if ($pref2 != null && is_numeric($pref2->value)) {
                $this->InputFields['defaultReportType'] = $pref2->value;
                $rt = ReportTypeDAO::getReportType($pref2->value);
                if (!is_bool($rt) && $rt instanceof ReportType) {
                    $this->InputFields['defaultReportTypeName'] = $rt->name;
                }
            }
        }

        $clientDoctorsOnly = PreferencesDAO::getPreferenceByKey('ClientDoctorsOnly', array("Conn" => $this->Conn));
        if ($clientDoctorsOnly != null && $clientDoctorsOnly->value === 'true') {
            $this->InputFields['ClientDoctorsOnly'] = true;
        }

        $pref3 = PreferencesDAO::getPreferenceByKey('POCDisabled', array("Conn" => $this->Conn));
        if ($pref3 != null) {
            $this->PocDisabled = $pref3->value === 'true' ? true : false;
        }
        if (self::PocSectionDisabled == true) {
            $this->PocDisabled = true;
        }

        $pref4 = PreferencesDAO::getPreferenceByKey('DefaultICD10', array("Conn" => $this->Conn));
        if ($pref4 != null) {
            $this->DefaultICD10 = $pref4->value === 'true' ? true : false;
        }
        $pref5 = PreferencesDAO::getPreferenceByKey('ICD10Cutoff', array("Conn" => $this->Conn));
        if ($pref5 != null) {
            $this->ICD10Cutoff = $pref5->value;
        }
        $pref6 = PreferencesDAO::getPreferenceByKey('DailyProcessingUseOrderDate', array("Conn" => $this->Conn));
        if ($pref6 != null) {
            $this->DailyProcessingUseOrderDate = $pref6->value === 'true' ? true : false;
        }

        $pref7 = PreferencesDAO::getPreferenceByKey('ExcludeReferenceTests', array("Conn" => $this->Conn));
        if ($pref7 != null) {
            $this->ExcludeReferenceTests = $pref7->value === 'true' ? true : false;
        }

    	$this->Drugs = DrugDAO::getDrugs(array("Conn" => $this->Conn));
    	$this->PocTests = TestDAO::getPOCTests(array("Conn" => $this->Conn));
    	//$this->Insurances = InsuranceDAO::getInsurances(array("Conn" => $this->Conn));
    	$this->Locations = LocationDAO::getLocations(array("Conn" => $this->Conn));
        $this->ReportTypes = ReportTypeDAO::getReportTypes(array("selectable" => true, "Conn" => $this->Conn));

    	$this->Phlebotomists = PhlebotomyDAO::getPhlebotomists(array("Conn" => $this->Conn));

    	$hasOverlapping = PreferencesDAO::getPreferenceByKey('OverlappingPanelsEnabled', array("Conn" => $this->Conn));
    	if ($hasOverlapping != null) {
            $this->OverlappingPanelsEnabled = $hasOverlapping->value === 'true' ? true : false;
        }

        $hasPatientPrescriptions = PreferencesDAO::getPreferenceByKey('PatientPrescriptionsEnabled', array("Conn" => $this->Conn));
        if ($hasPatientPrescriptions != null) {
            $this->PatientPrescriptionsEnabled = $hasPatientPrescriptions->value === 'true' ? true : false;
        }
    }
    
    private function setUpForEdit() {
        $this->MainTitle = "Edit Order";
        $this->MainTooltip = "Enter and submit information to edit an existing order";
    	$entryOrder = EntryOrderDAO::getEntryOrder(
            array(
                    "userId" => $_SESSION['id'],
                    "idOrders" => $_GET['id']
            ),
            array(
                "type" => $_GET['type'],
                "Conn" => $this->Conn,
                "OrderEntryPatientEmail" => self::OrderEntryPatientEmail
            )
    	);
        $this->entryOrder = $entryOrder;

        //echo "<pre>"; print_r($entryOrder); echo "</pre>";

        $this->InputFields['printESignature'] = $entryOrder->PrintESignature;

        // check to make sure the user didnt enter a different orderId in the url parameter that belongs to a different client or doctor
        $isValid = true;
        if ($this->User instanceof ClientUser && $entryOrder->clientId != $this->User->idClients) {
                $isValid = false;
        } elseif ($this->User instanceof DoctorUser && $entryOrder->doctorId != $this->User->iddoctors) {
            $isValid = false;
        }

        if (isset($this->AdminUser) && $this->AdminUser instanceof AdminUser && $this->AdminUser->typeId == 7
            && isset($_SESSION['AdminId']) && !empty($_SESSION['AdminId']) && isset($_SESSION['AdminType']) && $_SESSION['AdminType'] == 7
            && in_array($this->User->idClients, $this->AdminUser->adminClientIds)) {
            $isValid = true;
        }

        if ($isValid == false) {
            parent::logout();
            header("Location: " . self::SITE_URL);
            exit();
        }

        if ($entryOrder->IsReceipted) {

    		$_SESSION['MSG'] = "Accession " . $entryOrder->accession . " was receipted on " . $entryOrder->DateReceipted . " at " . $entryOrder->TimeReceipted . " "
    				. "and can no longer be modified. <br/>Please contact the lab if further modifications must be made.";
    		header("Location: index.php");
    	}

    	foreach ($entryOrder->Data as $field => $value) {
    		if (array_key_exists($field, $this->InputFields)) {
    			$this->InputFields[$field] = $value;
    		} else if ($field == "room") {
    			$this->InputFields["roomNumber"] = $value;
    		} else if ($field == "bed") {
    			$this->InputFields["bedNumber"] = $value;
    		} else if ($field == "reportType") {
                $this->InputFields['reportType'] = $value;
            } else if ($field == "isFasting") {
    		    $this->InputFields['isFasting'] = $value;
            }
    	}

        $this->Prescriptions = $entryOrder->Prescriptions;

    	$this->setPatientInputs($entryOrder);
    	$this->setSubscriberInputs($entryOrder);
    	$this->setInsuranceInputs($entryOrder);
    	$this->setOrderCommentInput($entryOrder);
    	$this->setTestInputs($entryOrder);
    	$this->setPrescribedDrugsInputs($entryOrder);
    	$this->setCodeInputs($entryOrder);
    	if (isset($entryOrder->Phlebotomy) && isset($entryOrder->Phlebotomy->idPhlebotomy)) {
    		$this->setPhlebotomyInputs($entryOrder);
    	}
    	
    	$this->IsOrderEdit = true;
    	$this->Action = "edit";
    	$this->InputFields['idOrders'] = $entryOrder->idOrders;
    	$this->InputFields['orderType'] = $_GET['type'];
    	if ($entryOrder->isAdvancedOrder && !isset($entryOrder->Phlebotomy)) {
    		$this->InputFields['advancedOrderOnly'] = true;
    	}

    	$this->setPageStyles();
    }

    // Set the input fields from $_POST/$_SESSION array
    // Does not create EntryOrder Object
    private function setUpForPostBack() {
        $entryOrder = new EntryOrder(array("idUsers" => $_SESSION['id']));

        $this->IsRejectedForm = true;
        // retreive the previously submitted form fields from session storage
        foreach ($_SESSION['InputFields'] as $key => $value) {
            if ($key == "pocResults") {
                $aryPocResults = array();
                foreach ($value as $currKey => $currValue) {
                    $aryCurrValue = explode(":", $currValue);
                    $aryPocResults[$currKey] = array("idMultiChoice" => $aryCurrValue[0], "choiceOrder" => $aryCurrValue[1]);
                }
                $this->InputFields['pocResults'] = $aryPocResults;

    		} else if ($key == "specimenDate--submit") {
    			$this->InputFields['specimenDate'] = date("Y-m-d h:i:s", strtotime($value));

    		} else if ($key == "orderDate--submit") {
    			$this->InputFields['orderDate'] = date("Y-m-d h:i:s", strtotime($value));

            } else if ($key == "selectedTests") {
                $arySelectedTests = array();
                foreach ($value as $aryString) {
                    $aryCurr = explode("::", $aryString);
                    $arySelectedTests[$aryCurr[0]] = $aryString;
                }
                $this->InputFields["selectedTests"] = $arySelectedTests;
            } else if ($key == "relationship" && $value != null && !empty($value)) {
                $this->InputFields['relationship'] = $value;
                $this->InputFields['patientRelationship'] = $value;
            } else if ($key == "patientRelationship" && $value != null && !empty($value)) {
                $this->InputFields['relationship'] = $value;
                $this->InputFields['patientRelationship'] = $value;
            } else if ($key == "selectedCodes" && is_array($value) && count($value) > 0) {
                foreach ($value as $codeInput) {
                    $currCode = DiagnosisDAO::getDiagnosisCode($codeInput, array("Conn" => $this->Conn));
                    $entryOrder->addDiagnosisCode($currCode);
                    $this->InputFields['selectedCodes'][] = $currCode;
                }
            } else if ($key == "insuranceId") {

                $this->Insurance = InsuranceDAO::getInsurance(array("idinsurances" => $value), array("Conn", $this->Conn));
                $this->InputFields['insuranceId'] = $value;
            } else if ($key == "secondaryInsuranceId") {
                $this->SecondaryInsurance = InsuranceDAO::getInsurance(array("idinsurances" => $value), array("Conn" => $this->Conn));
                $this->InputFields['secondaryInsuranceId'] = $value;

            } else if ($key == "prescribedDrugs" && is_array($value) && count($value) > 0) {
                $aryDrugs = array();
                foreach ($value as $drugId) {
                    $currDrug = DrugDAO::getDrugs(array(array("iddrugs", "=", $drugId)), array("IncludeSubstances" => true, "Conn" => $this->Conn));

                    //$substance = $currDrug[0]->Substances;
                    //$idSubstances = $substance[0]->idSubstances;
                    //$substance = $substance[0]->substance;

                    $aryDrugs[] = new Prescription(array(
                        "iddrugs" => $currDrug[0]->iddrugs,
                        "genericName" => $currDrug[0]->genericName
                        //,
                        //"idSubstances" => $idSubstances,
                        //"substance" => $substance
                    ));
                }
                $this->Prescriptions = $aryDrugs;

                $this->InputFields[$key] = $value;

            } else {

                $this->InputFields[$key] = $value;

            }
    	}
    	
    	if ($_SESSION['InputFields']['action'] == "edit") {
    		$this->Action = "edit";
    	} else {
            $this->Action = "add";
        }
    	
    	
    	$_SESSION['InputFields'] = "";
    	unset($_SESSION['InputFields']);
    	
    	// retreive the error messages from session storage
    	$this->ErrorMessages = $_SESSION['ErrorMessages'];
    	$_SESSION['ErrorMessages'] = "";
    	unset($_SESSION['ErrorMessages']);
    }
    
    private function setPageStyles() {
        // patient styles
        if ($this->IsOrderEdit || $this->IsRejectedForm) {
            $this->Styles['reselectPatient'] = "";
            $this->PatientFieldsDisabled = "disabled='disabled' style=''";
        }
    }
    private function setPhlebotomyInputs(EntryOrder $entryOrder) {
        $this->InputFields['isAdvancedOrder'] = 1;

        if (isset($entryOrder->Phlebotomy)) {
            $this->InputFields['idPhlebotomy'] = $entryOrder->Phlebotomy->idPhlebotomy;
            $this->InputFields['idAdvancedOrder'] = $entryOrder->Phlebotomy->idAdvancedOrder;
            $this->InputFields['frequency'] = $entryOrder->Phlebotomy->frequency;
            $this->InputFields['timesToDraw'] = $entryOrder->Phlebotomy->drawCount;
            if ($entryOrder->Phlebotomy->drawCount == 0) {
                $this->InputFields['continuous'] = 1;
            }
            $this->InputFields['startsOn'] = $entryOrder->Phlebotomy->startDate;
            $this->InputFields['phlebotomist'] = $entryOrder->Phlebotomy->phlebotomist;
            $this->InputFields['phlebComment1'] = $entryOrder->Phlebotomy->drawComment1;
            $this->InputFields['phlebComment2'] = $entryOrder->Phlebotomy->drawComment2;
        }
    }
    private function setPatientInputs($entryOrder) {
        $patient = $entryOrder->Patient;
        if (isset($patient->idPatients)) {
            $this->InputFields["idPatients"] = $patient->idPatients;
            $this->InputFields["patientLastName"] = $patient->lastName;
            $this->InputFields["patientFirstName"] = $patient->firstName;
            $this->InputFields["patientMiddleName"] = $patient->middleName;
            $this->InputFields["patientId"] = $patient->arNo;
            $this->InputFields["patientGender"] = $patient->sex;
            $this->InputFields["patientSpecies"] = $patient->species;
            $this->InputFields["patientEthnicity"] = $patient->ethnicity;
            $this->InputFields["patientSsn"] = $patient->ssn;
            $this->InputFields["patientAge"] = $patient->age;
            $this->InputFields["patientHeightFeet"] = $patient->heightFeet;
            $this->InputFields["patientHeightInches"] = $patient-> heightInches;
            $this->InputFields["patientWeight"] = $patient->weight;
            $this->InputFields["patientAddress1"] = $patient->addressStreet;
            $this->InputFields["patientAddress2"] = $patient->addressStreet2;
            $this->InputFields["patientCity"] = $patient->addressCity;
            $this->InputFields["patientState"] = $patient->addressState;
            $this->InputFields["patientZip"] = $patient->addressZip;
            $this->InputFields["patientPhone"] = $patient->phone;
            $this->InputFields["patientWorkPhone"] = $patient->workPhone;
            $this->InputFields["patientSmoker"] = $patient->smoker;
            $this->InputFields["relationship"] = $patient->relationship;
            $this->InputFields["patientRelationship"] = $patient->relationship;
            $this->InputFields["patientSubscriber"] = $patient->subscriber;
            if (isset($patient->dob) && !empty($patient->dob) && $patient->dob != "0000-00-00 00:00:00") {
            	$this->InputFields["patientDob"] = $patient->dob;
            } else {
                $this->InputFields["patientDob"] = "";
            }
            if (isset($entryOrder->OrderEmail) && !empty($entryOrder->OrderEmail->email)) {
                $this->InputFields["patientEmail"] = $entryOrder->OrderEmail->email;
            }
        }
        
        if (isset($entryOrder->Patient->PatientSource) && $entryOrder->Patient->PatientSource == 1) {
            $this->IsNewPatient = 1;
            $this->InputFields['patientSource'] = 1; // patient was selected from cssweb schema
        } else {
            $this->IsNewPatient = 0;
            $this->InputFields['patientSource'] = 0; // patient was selected from css schema
        }
        
    }
    private function setSubscriberInputs($entryOrder) {
        $subscriber = $entryOrder->Subscriber;
        if (isset($subscriber->idSubscriber) && $entryOrder->Patient->relationship != "self") { // There will only be a subscriber if "Same Subscriber" was unchecked on the form
            $this->InputFields["idSubscriber"] = $subscriber->idSubscriber;
            $this->InputFields["subscriberLastName"] = $subscriber->lastName;
            $this->InputFields["subscriberFirstName"] = $subscriber->firstName;
            $this->InputFields["subscriberMiddleName"] = $subscriber->middleName;
            $this->InputFields["subscriberId"] = $subscriber->arNo;
            $this->InputFields["subscriberAge"] = $subscriber->age;
            $this->InputFields["subscriberGender"] = $subscriber->sex;
            $this->InputFields["subscriberSsn"] = $subscriber->ssn;
            $this->InputFields["subscriberAddress1"] = $subscriber->addressStreet;
            $this->InputFields["subscriberAddress2"] = $subscriber->addressStreet2;
            $this->InputFields["subscriberCity"] = $subscriber->addressCity;
            $this->InputFields["subscriberState"] = $subscriber->addressState;
            $this->InputFields["subscriberZip"] = $subscriber->addressZip;
            $this->InputFields["subscriberPhone"] = $subscriber->phone;
            $this->InputFields["subscriberWorkPhone"] = $subscriber->workPhone;

            if (isset($subscriber->dob) && !empty($subscriber->dob)) {
                //$this->InputFields["subscriberDob"] = date("m/d/Y", strtotime($subscriber->dob));
            	$this->InputFields["subscriberDob"] = $subscriber->dob;
            } else {
                $this->InputFields["subscriberDob"] = "";
            }
            //$this->InputFields["insurance"] = $subscriber->insurance;
            //$this->InputFields["secondaryInsurance"] = $subscriber->secondardInsurance;
            //$this->InputFields["policyNumber"] = $subscriber->policyNumber;
            //$this->InputFields["secondaryPolicyNumber"] = $subscriber->secondaryPolicyNumber;
            //$this->InputFields["groupNumber"] = $subscriber->groupNumber;
            //$this->InputFields["secondaryGroupNumber"] = $subscriber->secondaryGroupNumber;
            //$this->InputFields["medicareNumber"] = $subscriber->medicareNumber;
            //$this->InputFields["medicaidNumber"] = $subscriber->medicaidNumber;
        } 
        //else {
        //    $this->InputFields['sameSubscriber'] = true;
        //}


        if ((isset($entryOrder->Subscriber->SubscriberSource) && $entryOrder->Subscriber->SubscriberSource == 1) || $entryOrder->IsNewSubscriber == 1) {
            $this->IsNewSubscriber = 1;
            $this->InputFields['subscriberSource'] = 1; // subscriber was selected from cssweb schema
        } else {
            $this->IsNewSubscriber = 0;
            $this->InputFields['subscriberSource'] = 0; // subscriber was selected from css schema
        }
    }
    private function setInsuranceInputs($entryOrder) {
        $this->Insurance = InsuranceDAO::getInsurance(array("idinsurances" => $entryOrder->insurance));
        $this->SecondaryInsurance = InsuranceDAO::getInsurance(array("idinsurances" => $entryOrder->secondaryInsurance));
        //echo "<pre>"; print_r($entryOrder); echo "</pre>";


        //if (!isset($entryOrder->Subscriber)) {
            $this->InputFields["insuranceId"] = $entryOrder->insurance;
            $this->InputFields["secondaryInsuranceId"] = $entryOrder->secondaryInsurance;
            $this->InputFields["insurance"] = $entryOrder->insurance;
            $this->InputFields["secondaryInsurance"] = $entryOrder->secondaryInsurance;
            $this->InputFields["policyNumber"] = $entryOrder->policyNumber;
            $this->InputFields["secondaryPolicyNumber"] = $entryOrder->secondaryPolicyNumber;
            $this->InputFields["groupNumber"] = $entryOrder->groupNumber;
            $this->InputFields["secondaryGroupNumber"] = $entryOrder->secondaryGroupNumber;
            $this->InputFields["medicareNumber"] = $entryOrder->medicareNumber;
            $this->InputFields["medicaidNumber"] = $entryOrder->medicaidNumber;
        //}
    }
    private function setOrderCommentInput($entryOrder) {
        if (isset($entryOrder->OrderComment)) {
            $this->InputFields['orderComment'] = $entryOrder->OrderComment->comment;
        }
    }
    
    /*
     * Populate an Array: pocResults[testId] => Array('idMultiChoice','choiceOrder') 
     * to be used for retreiving POC result info for editing orders
     */
    private function setTestInputs($entryOrder) {

        if (isset($entryOrder->Results)) {

            $pocTestId = PreferencesDAO::getPOCTest(array("Conn" => $this->Conn));

            $this->InputFields['pocResults'] = array();
            $this->InputFields['selectedTests'] = array();
            foreach ($entryOrder->Results as $result) { // populate the selectedTests, pocResults, and choices InputFields
                if ($result->panelId == $pocTestId) { // this is a poc test

                    $this->InputFields['pocResults'][$result->testId] = array(
                    	"idMultiChoice" => $result->resultChoice, 
                    	"choiceOrder" => $result->resultNo
                    );
                    
                } else { // this is a selectedTest
                    /*
                    [selectedTests] => Array (
                        [21] => 21:0:1:4017:ABS BASOPHILS
                        [19] => 19:0:1:4015:ABS MONOCYTES
                        [191] => 191:0:1:110:2-OH ETHYL FLUR.
                        [624] => 624:0:1:4356:2C9
                    )
                    [selectedTests] => Array (

                    )
                    */

                    $resultTestInfo = $result->testId . "::";
                    $resultTestInfo .= $result->Test->promptPOC . "::";
                    $resultTestInfo .= $result->Test->testType . "::";
                    $resultTestInfo .= $result->Test->number . "::";
                    $resultTestInfo .= $result->Test->name . "::";
                    $resultTestInfo .= $result->panelId;
                    $this->InputFields['selectedTests'][$result->testId] = $resultTestInfo;
                }
            }
        }
    }
    private function setPrescribedDrugsInputs($entryOrder) {
        if (isset($entryOrder->Prescriptions)) {
            /*
            [prescribedDrugs] => Array (
                [0] => 84
                [1] => 75
            )
            */
            $this->InputFields['prescribedDrugs'] = array();
            foreach ($entryOrder->Prescriptions as $prescription) {
                $this->InputFields['prescribedDrugs'][] = $prescription->drugId;
            }
        }
    }
    private function setCodeInputs($entryOrder) {

        $aryTestIds = array();
        if (isset($entryOrder->Results)) {
            foreach ($entryOrder->Results as $result) {
                if (($result->Test->testType == 1 && empty($result->panelId))
                    //|| ($result->Test->testType == 0 && ($result->testId == $result->panelId || $result->panelId == null))) {
                    || ($result->Test->testType == 0 && ($result->panelId == null || empty($result->panelId)))) {

                    $aryTestIds[] = $result->testId;

                }
            }
        }

        $aryDiagnosisCodeIds = array();
        if (isset($entryOrder->DiagnosisCodes)) {
            $hasCommonDiagnosisCodes = false;
            if (isset($this->User->CommonDiagnosisCodes)) {
                $hasCommonDiagnosisCodes = true;
            }

            $this->InputFields['selectedCodes'] = array();
            $this->InputFields['selectedCommonCodes'] = array();
            foreach ($entryOrder->DiagnosisCodes as $code) {

            	//$currCode = DiagnosisDAO::getDiagnosisCode($code->idDiagnosisCodes, array("Conn" => $this->Conn));

                if ($hasCommonDiagnosisCodes && array_key_exists($code->idDiagnosisCodes, $this->User->CommonDiagnosisCodes)) {
                    // add to selectedCommonCodes
                    $this->InputFields['selectedCommonCodes'][] = $code->idDiagnosisCodes;

                } else {
                    // add to selectedCodes
                    //$this->InputFields['selectedCodes'][] = $currCode;
                    $this->InputFields['selectedCodes'][] = $code;
                }

                $aryDiagnosisCodeIds[] = $code->idDiagnosisCodes;
            }
        }

        if (self::HasDiagnosisValidity && !empty($aryDiagnosisCodeIds)) {
            $diagnosisValidity = DiagnosisDAO::checkDiagnosisCode(implode(",", $aryTestIds), implode(",", $aryDiagnosisCodeIds), 0, 0);
            $this->DiagnosisValidity = $diagnosisValidity;

            //echo "<pre>"; print_r($diagnosisValidity); echo "</pre>";
        }
    }


    public function getPreferenceByKey($key) {
        $found = false;
        $preference = false;
        for ($i = 0; $i < count($this->Preferences) && !$found; $i++) {
            $currPref = $this->Preferences[$i];
            if ($currPref->key == $key) {
                $preference = $currPref;
                $found = true;
            }
        }
        return $preference;
    }

    public function getUserName() {
        return $this->clientName;
    }

    public function __get($key) {
        $value = parent::__get($key);
        if (empty($value)) {
            if ($key == "Conn") {
                $value = $this->Conn;
            } else if ($key == "User") {
                $value =  $this->User;
            } else if ($key == "IsRejectedForm") {
                $value =  $this->IsRejectedForm;
            } else if ($key == "InputFields") {
                $value =  $this->InputFields;
            } else if ($key == "ErrorMessages") {
                $value =  $this->ErrorMessages;
            } else if ($key == "IsOrderEdit") {
                $value =  $this->IsOrderEdit;
            } else if ($key == "Action") {
                $value =  $this->Action;
            } else if ($key == "IsNewPatient") {
                $value =  $this->IsNewPatient;
            } else if ($key == "IsNewSubscriber") {
                $value =  $this->IsNewSubscriber;
            } else if (array_key_exists($key, $this->Styles)) {
                $value =  $this->Styles[$key];
            } else if ($key == "SubscriberChanged") {
                $value =  $this->SubscriberChanged;
            } else if ($key == "NextAccession") {
                $value =  $this->NextAccession;
            } else if ($key == "Drugs") {
                $value =  $this->Drugs;
            } else if ($key == "PocTests") {
                $value =  $this->PocTests;
            //} else if ($key == "Insurances") {
            //    $value =  $this->Insurances;
            } else if ($key == "Insurance") {
                $value = $this->Insurance;
            } else if ($key == "SecondaryInsurance") {
                $value = $this->SecondaryInsurance;
            } else if ($key == "Locations") {
                $value =  $this->Locations;
            } else if ($key == "ReportTypes") {
                $value =  $this->ReportTypes;
            } else if ($key == "Phlebotomists") {
                $value =  $this->Phlebotomists;
            } else if ($key == "MainTitle") {
                $value = $this->MainTitle;
            } else if ($key == "MainTooltip") {
                $value = $this->MainTooltip;
            } else if ($key == "Prescriptions") {
                $value = $this->Prescriptions;
            } else if ($key == "UseOldOrderInfoFormat") {
                $value = $this->UseOldOrderInfoFormat;
            } else if ($key == "PocDisabled") {
                $value = $this->PocDisabled;
            } else if ($key == "DefaultICD10") {
                $value = $this->DefaultICD10;
            } else if ($key == "ICD10Cutoff") {
                $value = $this->ICD10Cutoff;
            } else if ($key == "DailyProcessingUseOrderDate") {
                $value = $this->DailyProcessingUseOrderDate;
            } else if ($key == "Icd9IsDisabled") {
                $value = $this->Icd9IsDisabled;
            } else if ($key == "Icd10IsDisabled") {
                $value = $this->Icd10IsDisabled;
            } else if ($key == "AdminUser") {
                $value = $this->AdminUser;
            }
        }
        return $value;
    }

    public function __isset($field) {
        $isset = false;

        if ($field == "IsNewPatient" && isset($this->IsNewPatient)) {
            $isset = true;
        } else if ($field == "IsNewSubscriber" && isset($this->IsNewSubscriber)) {
            $isset = true;
        } else if ($field == "SubscriberChanged" && isset($this->SubscriberChanged)) {
            $isset = true;
        } else if ($field == "Insurance" && isset($this->Insurance) && $this->Insurance instanceof Insurance) {
            $isset = true;
        } else if ($field == "SecondaryInsurance" && isset($this->SecondaryInsurance) && $this->SecondaryInsurance instanceof Insurance) {
            $isset = true;
        } else if ($field == "AdminUser" && isset($this->AdminUser) && $this->AdminUser instanceof AdminUser) {
            $isset = true;
        }

        return $isset;
    }

    private function resetSession() {
        if (isset($_SESSION['searchFields'])) {
            $_SESSION['searchFields'] = "";
            unset($_SESSION['searchFields']);
        }
        if (isset($_SESSION['idOrdersList'])) {
            $_SESSION['idOrdersList'] = "";
            unset($_SESSION['idOrdersList']);
        }
        if (isset($_SESSION['idOrdersList2'])) {
            $_SESSION['idOrdersList2'] = "";
            unset($_SESSION['idOrdersList2']);
        }
    }
}

?>
