<?php
//require_once "config.php";
require_once "Utility/Date.php";
require_once "IDatabase.php";

abstract class DataObject implements IDatabase {
    protected $Data = array();
    
    private static $server = IDatabase::HOST;
    private static $port = IDatabase::SQL_PORT;
    private static $username = IDatabase::DB_USERNAME;
    private static $password = IDatabase::DB_PASSWORD;
   
    const TBL_CLIENTS = "clients";
    const TBL_DOCTORS = "doctors";
    const TBL_ORDERS = "orders";
    const TBL_RESULTS = "results";
    const TBL_TESTS = "tests";
    const TBL_PATIENTS = "patients";
    
    const TBL_LOGGEDINUSER = "WebLoggedInUser";
    const TBL_USERS = "WebUsers";
    const TBL_USERTYPES = "WebUserTypes";
    const TBL_MULTIUSER = "WebMultiUser";
    const TBL_LOG = "WebLog";
    const TBL_LOGTYPES = "WebLogTypes";
    const TBL_LOGVIEWS = "WebLogViews";
    const TBL_LOGCUMULATIVE = "WebLogCumulative";

    const TBL_CLIENTLOOKUP = "WebClientLookup";
    const TBL_DOCTORLOOKUP = "WebDoctorLookup";
    const TBL_ADMINSETTINGSLOOKUP = "WebAdminSettingsLookup";
    const TBL_USERSETTINGSLOOKUP = "WebUserSettingsLookup";
    const TBL_ADMINSETTINGS = "WebAdminSettings";
    const TBL_USERSETTINGS = "WebUserSettings";
    const TBL_COMMONTESTS = "WebCommonTests";
    const TBL_EXCLUDEDTESTS = "WebExcludedTests";

    const TBL_REPORTTYPE = "reportType";
    const TBL_SUBSTANCES = "substances";
    const TBL_DRUGS = "drugs";
    const TBL_PRESCRIPTIONS = "prescriptions";
    const TBL_PREFERENCES = "preferences";
    const TBL_PANELS = "panels";
    const TBL_MULTICHOICE = "multichoice";
    const TBL_DEPARTMENTS = "departments";
    const TBL_INSURANCES = "insurances";
    const TBL_LOCATIONS = "locations";
    const TBL_ORDERCOMMENT = "orderComment";
    const TBL_SUBSCRIBER = "subscriber";
    const TBL_DIAGNOSISCODES = "diagnosisCodes";
    const TBL_DIAGNOSISVALIDITY = "diagnosisValidity";
    const TBL_ORDERDIAGNOSISLOOKUP = "orderDiagnosisLookup";
    const TBL_COMMONDIAGNOSISCODES = "WebCommonDiagnosisCodes";
    const TBL_ADVANCEDORDERS = "advancedOrders";
    const TBL_ADVANCEDRESULTS = "advancedResults";
    const TBL_EMPLOYEES = "employees";
    const TBL_EMPLOYEEDEPARTMENTS = "employeeDepartments";
    const TBL_PHLEBOTOMY = "phlebotomy";
    const TBL_PATIENTSUBSCRIBERLOG = "WebPatientSubscriberLog";
    const TBL_LOGORDERENTRY = "WebLogOrderEntry";
    const TBL_ORDERENTRYLOGTYPES = "WebOrderEntryLogTypes";
    const TBL_ADMINLOGS = "WebAdminLog";
    const TBL_ORDERRECEIPTLOG = "WebOrderReceiptLog";
    const TBL_LOGGEDINPATIENT = "WebLoggedInPatient";
    const TBL_PATIENTLOG = "WebPatientLog";
    const TBL_PATIENTVIEWLOG = "WebPatientLogViews";
    const TBL_ORDERSEQUENCE = "orderSequence";
    const TBL_RECEIPTEDORDERS = "receiptedOrders";
    const TBL_SPECIMENTYPES = "specimenTypes";
    const TBL_REMARKS = "remarks";

    const TBL_CLIENTDOCTORRELATIONSHIP = "clientDoctorRelationship";
    const TBL_LOGINVALIDATEDORDERS = "WebLogInvalidatedOrders";
    const TBL_LOGCANCELLEDORDERS = "WebLogCanceledOrders";
    const TBL_GENETICREPORT = "GeneticReport";

    const TBL_SALESMEN = "salesmen";
    const TBL_SALESGROUP = "salesGroup";
    const TBL_SALESMENLOOKUP = "WebSalesmenLookup";
    const TBL_TERRITORY = "territory";

    const TBL_SALESSETTINGS = "WebSalesSettings";
    const TBL_SALESSETTINGSLOOKUP = "WebSalesSettingsLookup";

    const TBL_INSURANCELOOKUP = "WebInsuranceLookup";

    const TBL_SALESGOALTYPES = "WebSalesGoalTypes";
    const TBL_SALESGOALINTERVALS = "WebSalesGoalIntervals";
    const TBL_SALESGOALS = "WebSalesGoals";

    const TBL_SALESLOGTYPES = "WebSalesLogTypes";
    const TBL_SALESLOG = "WebSalesLog";
    const TBL_SALESGOALLOG = "WebSalesGoalLog";

    const TBL_REFERENCELABREPORT = "ReferenceLabReport";

    const TBL_SALESGOALLOOKUP = "WebSalesGoalLookup";

    const TBL_LABUSERS = "users";
    const TBL_LOGGEDINLABUSER = "WebLoggedInLabUser";

    const VIEW_PRESCRIBEDDRUGS = "PrescribedDrugs";

    const TBL_ORDERENTRYSETTINGS = "WebOrderEntrySettings";
    const TBL_ORDERENTRYSETTINGSLOOKUP = "WebOrderEntrySettingsLookup";

    const TBL_ORDERACCESSSETTINGS = "WebOrderAccessSettings";
    const TBL_RESTRICTEDUSERS = "WebRestrictedUsers";
    const TBL_ORDERACCESSSETTINGSLOOKUP = "WebOrderAccessSettingsLookup";

    const TBL_ORDERBEINGEDITED = "WebOrdersBeingEdited";

    const SP_VIEWPENDINGORDERS = "ViewPendingOrders"; // stored proc that was supposed to be used to speed up "View Orders" page -- currently not being used

    const TBL_ESIGNATURES = "WebESignatures";
    const TBL_ESIGNATURETYPES = "WebESignatureTypes";
    const TBL_ESIGUTENSILTYPES = "WebESigUtensilTypes";
    const TBL_ESIGUTENSILLOOKUP = "WebESigUtensilLookup";
    const TBL_LOGESIGNATURES = "WebLogESignatures";

    const TBL_EXTRANORMALS = "extranormals";

    const TBL_EMAILLOG = "WebEmailLog";
    const TBL_EMAILUSERLOG = "WebEmailUserLog";

    const TBL_LABMASTER = "labMaster";

    const TBL_DEACTIVATEDEMAILUSERS = "WebDeactivatedEmailUsers";

    const TBL_ORDERENTRYLOG = "orderEntryLog";

    const TBL_CLIENTPROPERTIES = "clientProperties";
    const TBL_CLIENTPROPERTYLOOKUP = "clientPropertyLookup";
    const TBL_CLIENTPROPERTYREQUIREDFIELDS = "clientPropertyRequiredFields";
    const TBL_CLIENTPROPERTYREQUIREDFIELDLOOKUP = "clientPropertyRequiredFieldLookup";

    const TBL_APILOG = "WebApiLog";

    const TBL_ESIGASSIGNTYPES = "WebESigAssignTypes";
    const TBL_DOCTORESIGNATURES = "doctorESignatures";

    const TBL_PRINTESIGONREQ = "PrintESigOnReq";
    const TBL_LOGDOCTORESIGS = "WebLogDoctorESigs";

    const TBL_COMMONDRUGS = "WebCommonDrugs";

    const TBL_AUTOORDERTESTS = "AutoOrderTests";

    const TBL_REMARKTYPES = "remarkTypes";

    const TBL_USERNOTIFICATIONS = "WebUserNotifications";
    const TBL_USERNOTIFICATIONLOOKUP = "WebUserNotificationLookup";

    const TBL_LOGNOTIFICATIONS = "WebLogNotifications";

    const TBL_DETAILORDERS = "detailOrders";
    const TBL_DETAILCPTCODES = "detailCptCodes";
    const TBL_DETAILORDEREVENTS = "detailOrderEvents";
    const TBL_EVENTS = "events";
    const TBL_EVENTTYPES = "eventTypes";

    const TBL_COMMISSIONRATES = "commissionRates";
    //const TBL_COMMISSIONRATETYPES = "commissionRateTypes";

    const TBL_COMMISSIONPAYMENTLOG = "commissionPaymentLog";

    const TBL_FEESCHEDULES = "feeSchedules";
    const TBL_FEESCHEDULETESTLOOKUP = "feeScheduleTestLookup";
    const TBL_FEESCHEDULECPTLOOKUP = "feeScheduleCptLookup";
    const TBL_CPTCODES = "cptCodes";
    const TBL_DIAGNOSISVALIDITYLOOKUP = "diagnosisValidityLookup";
    const TBL_BILLINGPAYORS = "billingPayors";

    const TBL_INSURANCERULES = "insuranceRules";

    const TBL_SUBGROUPLOOKUP = "subGroupLookup";
    const TBL_SALESGROUPLOOKUP = "salesGroupLookup";

    const TBL_CLAIMSTATUSES = "claimStatuses";

    const TBL_DEPARTMENTCOSTS = "departmentCosts";
    const TBL_COSTTYPES = "costTypes";

    const TBL_AVALON_USERS = "users";
    const TBL_CHARTUSERLOOKUP = "chartUserLookup";
    const TBL_CHARTS = "charts";
    const TBL_CHARTTYPES = "chartTypes";

    const TBL_PATIENTLOOKUP = "WebPatientLookup";

    const TBL_ORDEREMAIL = "orderEmail";

    const TBL_DOCTORESIGNATUREIMAGES = "doctorESignatureImages";

    const TBL_PATIENTSMSQUEUE = "patientSMSQueue";
    const TBL_PATIENTSMSLOGTYPES = "patientSMSLogTypes";
    const TBL_PATIENTSMSLOG = "patientSMSLog";
    const TBL_SMSUNSUBSCRIBED = "patientSMSUnsubscribed";

    const TBL_REQUISITIONS = "requisitions";
    const TBL_ORDERIDCARDS = "orderIdCards";

    const TBL_WEBORDERDOCUMENTS = "WebOrderDocuments";

    const TBL_PATIENTPRESCRIPTIONS = "patientPrescriptions";

    const TBL_AVALONINSIGHTLOG = "avalonInsightLog";

    const TBL_KITNUMBERS = "kitNumbers";

    public $SiteUrl = "";
    public $Logo = "";
    public $LabName = "";

    public function __construct(array $data = null) {
    	if ($data != null) {
    		foreach ($data as $key => $value) {
    			if (array_key_exists($key, $this->Data)) {
    				$this->Data[$key] = $value;
    			}
    		}
    	}

        if ((isset($_SERVER['SSL_TLS_SNI']) && !empty($_SERVER['SSL_TLS_SNI']) && $_SERVER['SSL_TLS_SNI'] === 'cardiopathoutreach.com')
            || (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'cardiopathoutreach.com')) {
            $this->SiteUrl = "https://cardiopathoutreach.com/outreach/";
            $this->Logo = "cardioPathLogo.png";
            $this->LabName = "CardioPath LLC";
        } else if ((isset($_SERVER['SSL_TLS_SNI']) && !empty($_SERVER['SSL_TLS_SNI']) && $_SERVER['SSL_TLS_SNI'] === 'cardiotropicoutreach.com')
            || (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'cardiotropicoutreach.com')) {
            $this->SiteUrl = "https://cardiotropicoutreach.com/outreach/";
            $this->Logo = "cardioLogo.png";
            $this->LabName = "Cardio Tropic Labs";
        } else {
            $this->SiteUrl = self::SITE_URL;
            $this->Logo = self::Logo;
            $this->LabName = self::LabName;
        }
    }   
    
    public function withData($data) {
        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                if (array_key_exists($key, $this->Data))
                    $this->Data[$key] = $value;
            }
        }
    }
    
    public function getValue($field) {
        if (array_key_exists($field, $this->Data)) {
            return $this->Data[$field];
        } else {
            die("Field not found");
        }
    }
    
    public function getValueEncoded($field) {
        return htmlspecialchars($this->getValue($field));
    }

    protected static function connect(array $settings = null) {

        //shell_exec("ssh -f -L 3110:127.0.0.1:5288 ubuntu@192.168.104.97 sleep 60 >> logfile");
        //shell_exec("ssh -fNg -L 3110:127.0.0.1:5288 ubuntu@192.168.104.97");

        if (!empty($settings) && array_key_exists("ConnectToWeb", $settings) && $settings["ConnectToWeb"] == true) {
            $conn = new mysqli(self::$server, self::$username, self::$password, IDatabase::DB_CSS_WEB, self::$port);
        } else {
            $conn = new mysqli(self::$server, self::$username, self::$password, IDatabase::DB_CSS, self::$port);
        }

        if ($conn->connect_errno) {
            //die("Failed to connect to MySQL: " . $conn->connect_error);
            die("There was an error processing this request");
        }

        return $conn;
    }
      
    /*
     * ma·nip·u·late verb
     * 1. handle or control (a tool, mechanism, etc.), typically in a skillful manner.
     * 2. alter, edit, or move (text or data) on a computer.
     */
    protected static function manipulate($sql, array $inputs = null, array $settings = null) {
    	$lastInsertId = false;
    	$affectedRows = false;
        $closeConn = true;
    	if ($settings != null) {
            if (array_key_exists("LastInsertId", $settings) && $settings["LastInsertId"] == true) {
                    $lastInsertId = true;
            } else if (array_key_exists("AffectedRows", $settings) && $settings["AffectedRows"] == true) {
                    $affectedRows = true;
            }

            if (array_key_exists("Conn", $settings) && $settings['Conn'] != null && $settings['Conn'] instanceof mysqli) {
                $closeConn = false;
            }
    	}  	
    	
        if ($closeConn) {
            $conn = self::connect($settings);
        } else {
            $conn = $settings['Conn'];
        }
        
        if ($stmt = $conn->prepare($sql)) {
            if ($inputs != null) {
                $stmt = self::bind($stmt, $inputs);
            }

            $stmt->execute();
                        
            if ($settings != null && array_key_exists("LastInsertId", $settings) && $settings["LastInsertId"] == true) {
                $lastInsertId = $stmt->insert_id;
                if ($closeConn) {
                    self::disconnect($conn);
                }
                return $lastInsertId;
            }
                        
            if ($affectedRows) {
            	$affected = $stmt->affected_rows;
            	if ($closeConn) {
                    self::disconnect($conn);
                }
            	return $affected;
            }
            
            if ($closeConn) {
                self::disconnect($conn);
            }
            return true;
            
        } else {
            self::disconnect($conn);
            $inp = "";
            if ($inputs != null) {
                foreach ($inputs as $input) {
                    $inp .= "$input, ";
                }
            }

            $inp = substr($inp, 0, strlen($inp) - 2);
            error_log("Insert/Update/Delete Error: $sql - $inp", 0);
            if (self::DEV_MODE == true) {
                echo "Insert/Update/Delete Error: $sql - $inp . <br/><br/>";
            }
            die("An error occurred while processing this page. We are sorry for the inconvenience.");
        }
    }

    protected static function numRows($sql, array $inputs = null, array $settings = null) {
        $closeConn = true;
        if ($settings != null && count($settings) > 0 && array_key_exists("Conn", $settings) && $settings['Conn'] != null && $settings['Conn'] instanceof mysqli) {
            $conn = $settings['Conn'];
            $closeConn = false;
        } else {
            $conn = self::connect($settings);
        }

        if ($stmt = $conn->prepare($sql)) {
            if (!empty($inputs)) {
                $stmt = self::bind($stmt, $inputs);
            }
            // http://stackoverflow.com/a/15693175
            if (!$meta = $stmt->result_metadata()) {
                throw new Exception($stmt->error);
            }
            $refs = array();
            $data = array();
            // Iterate over the fields and set a reference
            while ($name = $meta->fetch_field()) {
                $refs[] =& $data[$name->name];
            }
            // Free the metadata result
            $meta->free_result();
            // Throw an exception if the result cannot be bound
            if (!call_user_func_array(array($stmt, 'bind_result'), $refs)) {
                throw new Exception($stmt->error);
            }

            $stmt->execute();
            $stmt->store_result();

            return $stmt->num_rows;

            /*if ($closeConn) {
                self::disconnect($conn);
            }*/
        } else {
            self::disconnect($conn);
            $inp = "";
            foreach ($inputs as $input) {
                $inp .= "$input, ";
            }
            $inp = substr($inp, 0, strlen($inp) - 2);
            error_log("Select Error: $sql - $inp", 0);
            if (self::DEV_MODE == true) {
                echo "Select Error: $sql - $inp<br/><br/>";
            }
            die("An error occurred while processing this page. We are sorry for the inconvenience.");
        }
    }

    protected static function call($sql, array $inputs = null, array $settings = null) {
        if ($settings != null && count($settings) > 0 && array_key_exists("Conn", $settings) && $settings['Conn'] != null && $settings['Conn'] instanceof mysqli) {
            $mysqli = $settings['Conn'];
        } else {
            $mysqli = self::connect($settings);
        }

        if (!($stmt = $mysqli->prepare($sql))) {
            echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
        }

        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        }

        do {
            if ($res = $stmt->get_result()) {
                printf("---\n");
                var_dump(mysqli_fetch_all($res));
                mysqli_free_result($res);
            } else {
                if ($stmt->errno) {
                    echo "Store failed: (" . $stmt->errno . ") " . $stmt->error;
                }
            }
        } while ($stmt->more_results() && $stmt->next_result());


    }
    
    protected static function select($sql, array $inputs = null, array $settings = null) {
        $closeConn = true;
        if ($settings != null && count($settings) > 0 && array_key_exists("Conn", $settings) && $settings['Conn'] != null && $settings['Conn'] instanceof mysqli) {
            $conn = $settings['Conn'];
            $closeConn = false;
        } else {
            $conn = self::connect($settings);
        }
        
        if ($stmt = $conn->prepare($sql)) {
            
            if (!empty($inputs)) {
                $stmt = self::bind($stmt, $inputs);
            }

            // http://stackoverflow.com/a/15693175
            if (!$meta = $stmt->result_metadata()) {
                throw new Exception($stmt->error);
            }

            $refs = array();
            $data = array();
            // Iterate over the fields and set a reference
            while ($name = $meta->fetch_field()) {
                $refs[] =& $data[$name->name];
            }

            // Free the metadata result
            $meta->free_result();

            // Throw an exception if the result cannot be bound
            if (!call_user_func_array(array($stmt, 'bind_result'), $refs)) {
                error_log("Bind Result Error: " . $stmt->error . " - " . implode(", ", $refs), 0);
                throw new Exception($stmt->error);
            }

            $stmt->execute();

            $aryReturn = array();  

            while ($stmt->fetch()) {
                
                $aryCurr = array();
                foreach ($data as $field => $value) {
                    $aryCurr[$field] = $value;
                }

                $aryReturn[] = $aryCurr;
            }
            
            if ($closeConn) {
                self::disconnect($conn);
            }
            
            return $aryReturn;            
            
        } else {
            self::disconnect($conn);
            $inp = "";
            if ($inputs != null) {
                foreach ($inputs as $input) {
                    $inp .= "$input, ";
                }
            }
            $inp = substr($inp, 0, strlen($inp) - 2);
            error_log("Select Error: $sql - $inp", 0);
            if (self::DEV_MODE == true) {
                echo "Select Error: $sql - $inp<br/><br/>";
            }
            die("An error occurred while processing this page. We are sorry for the inconvenience.");
        }
    }
    
    protected static function disconnect(mysqli $conn) {
        $conn->close();
    }
    
    // http://stackoverflow.com/a/5108167
    /*protected static function bind($stmt, array $inputs) {

        $params = self::formatInputs($inputs);
        $return = call_user_func_array(array ($stmt,'bind_param'), self::refValues($params));

        return $stmt;
    }*/

    /*protected static function bind($stmt, array $inputs) {

        $params = self::formatInputs($inputs);
        $return = call_user_func_array(array ($stmt,'bind_param'), self::refValues($params));

        $stmt->bind_param(self::getParamTypes($inputs), count($inputs), ...$inputs);

        $result = $stmt->execute_query();



        return $stmt;
    }*/

    protected static function bind($stmt, array $inputs) {
        if (empty($inputs)) return $stmt;

        // First element is the types string; the rest are the values
        $params = self::formatInputs($inputs); // e.g. ['sii', $a, $b, $c]

        // IMPORTANT: pass references to the array elements (not a foreach $p)
        call_user_func_array([$stmt, 'bind_param'], self::refValues($params));

        return $stmt;
    }


/*    protected static function bind($stmt, array $inputs) {
        if (empty($inputs)) {
            return $stmt;
        }

        $params = self::formatInputs($inputs); // ['s', 610037524]

        $typeString = $params[0];
        $bindVars = [];

        foreach (array_slice($params, 1) as $p) {
            $bindVars[] = &$p;
        }

        array_unshift($bindVars, $typeString); // ['s', &$var1, &$var2, ...]

        call_user_func_array([$stmt, 'bind_param'], $bindVars);
        return $stmt;
    }*/


    private static function getParamTypes(array $inputs) {
        $types = "";
        foreach ($inputs as $input) {
            if (is_float($input)) {
                $types .= "d";
            } else if (is_int($input) || is_bool($input)) {
                $types .= "i";
                //} else if (is_string($input)) {
            } else { // default for strings and null values
                $types .= "s";
            }
            //} else {
            //die("Input error: cannot find data type: $input");
            //}
        }
        return $types;
    }

    private static function formatInputs(array $inputs) {
        $types = "";
        $aryInputs = array();
        foreach ($inputs as $input) {
            if (is_float($input)) {
                $types .= "d";
            } else if (is_int($input) || is_bool($input)) {
                $types .= "i";
            //} else if (is_string($input)) {
            } else { // default for strings and null values
                $types .= "s";
            }
            //} else {
                //die("Input error: cannot find data type: $input");                
            //}
        }
        $params = array_merge(array($types), $inputs);
        return $params;
    }
    
    
    // http://stackoverflow.com/questions/3681262/php5-3-mysqli-stmtbind-params-with-call-user-func-array-warnings
    private static function refValues($arr) {
        $refs = array();

        foreach ($arr as $key => $value) {
            //$refs[$key] = &$arr[$key];
            $refs[] = &$arr[$key];
        }

        return $refs; 
    }
    
    protected static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
}
?>
