<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'IConfig.php';
require_once 'IPatientConfig.php';
require_once 'Auth.php';
require_once 'DAOS/PendingOrdersDAO.php';
require_once 'DOS/Proxy.php';

class PatientPageClient extends Auth implements IConfig  {
    private $CurrPage;
    private $LoggedIn;

    private $Stylesheets = array();
    private $Scripts = array();
    private $Overlays = array();

    private $TimeStart = 0;
    private $TimeEnd = 0;

    protected $IeJib = "";

    public $SiteUrl = self::SITE_URL;

    public $SearchLink = "";
    public $RequisitionLink = "";
    public $NewWebOrderLink = "";
    public $IdCardsLink = "";

    private $PendingOrders;
    private $HasPendingOrder = false;

    public $ErrorMessages = array();

    public $OrderId = "";

    public function __construct(array $data = null) {
        parent::__construct($data);

        $this->CurrPage = $_SERVER['PHP_SELF'];
        $this->checkSession();

        $this->addStylesheet(IPatientConfig::Directory . "/css/fontawesome/css/all.css");
        $this->addStylesheet(IPatientConfig::Directory . "/css/nav.css");
        $this->addStylesheet(IPatientConfig::Directory . "/css/styles.css");

        $this->addScript("/outreach/js/jquery-2.1.0.min.js");
        $this->addScript(IPatientConfig::Directory . "/js/bootstrap.js");
        $this->addScript(IPatientConfig::Directory . "/js/scripts.js");

        /*$key = $_SESSION['y'];
        $iv = $_SESSION['z'];
        if (array_key_exists("orderId", $_SESSION) && isset($_SESSION['orderId']) && !empty($_SESSION['orderId'])) {
            $proxy = new Proxy(array(
                "Key" => $key,
                "InitVector" => $iv,
                "EncData" => array(
                    "orderId" => $_SESSION['orderId'],
                )
            ));
            $proxy->decrypt();
            $rawData = $proxy->getRawData();
            $idOrders = $rawData['orderId'];

            $this->HasPendingOrder = true;
        }*/

        if (isset($_SESSION['ErrorMessages'])) {
            $this->ErrorMessages = $_SESSION['ErrorMessages'];
            unset($_SESSION['ErrorMessages']);
        }

        if ($this->CurrPage == IPatientConfig::Directory . "/search/index.php" || $this->CurrPage == IPatientConfig::Directory . "/search/indexb.php") {
            if (IPatientConfig::HasWebOrder) {

                /*if ($this->printRequestLabTestLink()) {
                    $this->NewWebOrderLink = "<li class=\"nav-item\"><a class=\"nav-link\" id=\"requestOrder\" href=\"#\">Request Lab Test</a></li>";
                }*/
                $this->NewWebOrderLink = "<li class=\"nav-item\"><a class=\"nav-link\" id=\"requestOrder\" href=\"../orderentry\">Request Lab Test</a></li>";

                $this->RequisitionLink = "<li class=\"nav-item\"><a class=\"nav-link\" id=\"requisition\" href=\"#\">Requisition</a></li>";

                if (IPatientConfig::HasCards) {
                    $this->IdCardsLink = "<li class=\"nav-item\"><a class=\"nav-link\" id=\"idcards\" href=\"" . IPatientConfig::Directory . "/cards\">ID Cards</a></li>";
                }


                $this->addOverlay("
                <div class=\"modal fade\" id=\"exampleModal\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"exampleModalLabel\" aria-hidden=\"true\">
                    <div class=\"modal-dialog\" role=\"document\">
                        <div class=\"modal-content\">
                            <div class=\"modal-header\">
                                <h5 class=\"modal-title\" id=\"exampleModalLabel\"></h5>
                                <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
                                    <span aria-hidden=\"true\">&times;</span>
                                </button>
                            </div>
                            <div class=\"modal-body\"></div>
                            <div class=\"modal-footer\">
                                <button type=\"button\" class=\"btn btn-secondary\" data-dismiss=\"modal\">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                ");

                $this->addOverlay("
                <div class=\"modal fade\" id=\"requestConfirmModal\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"requestConfirmModalLabel\" aria-hidden=\"true\">
                    <div class=\"modal-dialog\" role=\"document\">
                        <div class=\"modal-content\">
                            <div class=\"modal-header\">
                                <h5 class=\"modal-title\" id=\"requestConfirmModalLabel\">Lab Test Request</h5>
                                <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
                                    <span aria-hidden=\"true\">&times;</span>
                                </button>
                            </div>
                            <div class=\"modal-body\">
                            You are requesting additional testing from " . self::LabName . ". Please confirm that you would like to submit another lab test request.
                         
                                <div class=\"container text-center mt-2\">
                                    <input type=\"checkbox\" name=\"chkConfirmRequest\" id=\"chkConfirmRequest\" class=\"form-check-input\" value=\"1\" required autofocus>
                                    <label for=\"chkConfirmRequest\">Yes, request additional lab test</label>
                                </div>
                            </div>
                            <div class=\"modal-footer\">
                                <button type=\"button\" id=\"btnSubmitRequest\" class=\"btn btn-primary\">Submit</button>
                                <button type=\"button\" class=\"btn btn-secondary\" id=\"btnCancelRequest\" data-dismiss=\"modal\">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>");
            }
            //} else if ($this->CurrPage == IPatientConfig::Directory . "/cards/index.php" || $this->CurrPage == IPatientConfig::Directory . "/cards/indexb.php") {
        } else if ($this->CurrPage != IPatientConfig::Directory . "/search/index.php") {
            $this->SearchLink = "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . IPatientConfig::Directory . "/search\">Results</a></li>";

            $key = "";
            $iv = "";
            if (array_key_exists("y", $_SESSION)) {
                $key = $_SESSION['y'];
            }
            if (array_key_exists("z",$_SESSION)) {
                $iv = $_SESSION['z'];
            }

            $proxy = new Proxy(array(
                "Key" => $key,
                "InitVector" => $iv,
                "EncData" => array(
                    //"orderId" => $_SESSION['orderId'],
                    "id" => $_SESSION['id']
                )
            ));
            $proxy->decrypt();
            $rawData = $proxy->getRawData();

            //$this->OrderId = $rawData['orderId'];

        if ($this->CurrPage == IPatientConfig::Directory . "/orderentry/index.php") {
                $this->addOverlay("
                <div class=\"modal fade\" id=\"exampleModal\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"exampleModalLabel\" aria-hidden=\"true\">
                    <div class=\"modal-dialog\" role=\"document\">
                        <div class=\"modal-content\">
                            <div class=\"modal-header\">
                                <h5 class=\"modal-title\" id=\"exampleModalLabel\"></h5>
                                <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
                                    <span aria-hidden=\"true\">&times;</span>
                                </button>
                            </div>
                            <div class=\"modal-body\"></div>
                            <div class=\"modal-footer\">
                                <button type=\"button\" class=\"btn btn-secondary\" data-dismiss=\"modal\">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                ");
            }
        }


    }

    private function printRequestLabTestLink() {
        require_once 'DAOS/EntryOrderDAO.php';

        $user = $this->User;
        $idUsers = $user->idUsers;
        //$idUsers = $_SESSION['id'];

        $dteDateTime = new DateTime();
        $currDayOfYear = $dteDateTime->format('z');

        $dtePrevOrderDate = EntryOrderDAO::getPrevOrderDate($idUsers);

        if ($dtePrevOrderDate == null) {
            return false;
        } else {
            $prevDayOfYear = $dtePrevOrderDate->format('z');

            if ($currDayOfYear <= $prevDayOfYear) {
                return false;
            }
        }
        return true;
    }

    public function __get($field) {
        $value = "";
        if (array_key_exists($field, $this->Data)) {
            $value = $this->Data[$field];
        } else if ($field == "User") {
            $value = $this->User;
        } else if ($field == "Orders") {
            $value = $this->Orders;
        }
        return $value;
    }

    public function startPagePrint() { ?>
        <!doctype html>
        <html lang="en" class="h-100">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
            <meta name="description" content="">
            <meta name="author" content="Computer Service and Support">
            <title>Search Results</title>

            <!-- Bootstrap core CSS -->
            <link href="../css/bootstrap.css" rel="stylesheet" crossorigin="anonymous">

            <!-- Favicons -->
            <link rel="apple-touch-icon" href="../images/favicons/apple-touch-icon.png" sizes="180x180">
            <link rel="icon" href="../images/frame32.gif" sizes="32x32" type="image/gif">
            <link rel="icon" href="../images/frame16.gif" sizes="16x16" type="image/gif">
            <link rel="manifest" href="../images/favicons/manifest.json">
            <link rel="mask-icon" href="../images/favicons/safari-pinned-tab.svg" color="#563d7c">
            <meta name="msapplication-config" content="../images/favicons/browserconfig.xml">
            <meta name="theme-color" content="#563d7c">
            <meta name="robots" content="noindex, nofollow">

            <?php
            foreach ($this->Stylesheets as $stylesheet) {
                echo $stylesheet;
            }
            ?>
        </head>
        <body class="d-flex flex-column h-100">
        <?php
    }

    public function printPageHeader() {
        $dataCompression = 80;
        if ($_SERVER['PHP_SELF'] == "/cumulative.php") {
                $dataCompression = 120;
        }
        ?>
        <header class="navbar navbar-expand navbar-dark flex-column flex-md-row bd-navbar">
            <nav class="navbar navbar-expand-md navbar-dark bg-danger mb-4">
                <!--<a class="navbar-brand" href="#">Top navbar</a>-->
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item">
                            <a class="nav-link" id="logout" href="#">Logout</a>
                        </li>
                        <?php
                        echo $this->SearchLink;
                        echo $this->RequisitionLink;
                        echo $this->NewWebOrderLink;
                        echo $this->IdCardsLink;
                        ?>
                    </ul>
                </div>
            </nav>


        </header>
        <?php
    }

    public function checkSession() {
        if (!array_key_exists("token", $_SESSION) || !array_key_exists("id", $_SESSION) || !array_key_exists("type", $_SESSION)
            || !isset($_SESSION['token']) || !isset($_SESSION['id']) || !isset($_SESSION['type'])
            || empty($_SESSION['token']) || empty($_SESSION['id']) || empty($_SESSION['type'])) {

            $_SESSION = array();
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            if (session_status() == PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            $this->LoggedIn = false;
        }

        $this->LoggedIn = parent::checkSession();

        if (empty($this->LoggedIn) || $this->LoggedIn == false) {
            $this->logout();
            header("Location: " . IPatientConfig::Directory . "/login/");
        }
    }

    public function addScript($src) {
        if (self::LabName == "Computer Service and Support") {
            $this->Scripts[] = "<script type=\"text/javascript\" src=\"$src?timestamp=" . date('Ymdhis') . "\"></script>";
        } else {
            $this->Scripts[] = "<script type=\"text/javascript\" src=\"$src?version=" . self::Version . "\"></script>";
        }
    }

    public function addStylesheet($href) {
        if (self::LabName == "Computer Service and Support") {
            $this->Stylesheets[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$href?version=" . self::Version . "&timestamp=" . date('Ymdhis') . "\" >";
        } else {
            $this->Stylesheets[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$href?version=" . self::Version . "\" >";
        }
    }

    public function addOverlay($html) {
        $this->Overlays[] = $html;
    }

    
    
    public function endPagePrint() {
        $currYear = date("Y");
        $overlays = "";
        $scripts = "";
        if (count($this->Overlays) > 0) {
            foreach ($this->Overlays as $overlay) {
                $overlays .= $overlay;
            }
        }
        foreach ($this->Scripts as $script) {
            $scripts .= $script;
        }
        ?>
        <footer class="footer mt-auto text-center">
            <div class="container">
                <span class="text-muted">Powered by Computer Service &amp; Support Laboratory Systems, Inc. <img class="mb-4" id="csslisLogo" src="<?php echo IPatientConfig::Directory ?>/images/frame32.gif" alt="" width="32" height="32"> &copy; <?php echo $currYear ?></span>
            </div>
        </footer>

        <?php
        echo $overlays;
        echo $scripts;
        ?>
        <div id="overlay"></div>
<!--        <i class="icon-spinner icon-spin icon-4x" id="loading-spinner"></i>-->

        <div class="d-flex justify-content-center" id="loading-spinner">
            <div class="spinner-border text-danger" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        </body>
        </html>
        <?php
    } 	
}