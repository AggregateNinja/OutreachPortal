<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'Utility/Auth.php';
require_once 'UserValidator.php';

class PageClient extends Auth implements IConfig {

private $CurrPage;
private $LoggedIn;

/*private $RestrictedPages = array(
    "/statistics.php",
    "/abnormals.php",
    "/cumulative.php",
    "/orderentry/index.php",
    "/orderentry/add.php",
    "/sales",
    "/sales/settings",
    "/reports.php",
    "/manifest.php"
    //,"/admin/index.php"
    //,"/admin/add.php"
    //,"/admin/edit.php"
);*/

private $RestrictedPages = array(
    "/outreach/statistics.php",
    "/outreach/abnormals.php",
    "/outreach/cumulative.php",
    "/outreach/reports.php",
    "/outreach/consistency.php",
    "/outreach/cumulativereport.php"
);

private $OrderEntryPages = array(
    "/outreach/manifest.php",
    "/outreach/orderentry/index.php",
    "/outreach/orderentry/indexb.php",
    "/outreach/orderentry/add.php",
    "/outreach/orderentry/requisition.php",
    "/outreach/orderentry/requisitionb.php",
    "/outreach/orderentry/liveSearch.php",
    "/outreach/orderentry/addb.php",
    "/outreach/orderentry/settings.php",
    "/outreach/orderentry/settingsb.php"
);

private $AdminPages = array(
    "/outreach/admin/index.php",
    "/outreach/admin/add.php",
    "/outreach/admin/edit.php",
    "/outreach/admin/liveSearch.php",
    "/outreach/admin/process.php",
    "/outreach/admin/email.php",
    "/outreach/admin/settings.php",
    "/outreach/admin/notifications.php",
    "/outreach/admin/required/index.php"
);

private $SalesPages = array(
    "/outreach/sales/index.php",
    "/outreach/sales/settings/index.php",
    "/outreach/sales/settings/manage.php"
);

private $ResultsPages = array(
    "/outreach/search.php",
    "/outreach/found.php",
    "/outreach/foundb.php",
    "/outreach/view.php",
    "/outreach/viewb.php",
    "/outreach/settings.php",
);

private $Stylesheets = array();
private $Scripts = array();
private $Overlays = array();

private $TimeStart = 0;
private $TimeEnd = 0;

public $EditOrderLink = false;

protected $IeJib = "";

public function __construct(array $settings = null) {

    //$this->startPageLoadTimer();

    if (isset($_GET['z']) && is_numeric($_GET['z']) && $_GET['z'] == 1) {
        if ($settings != null) {
            $settings['ClearEditedOrder'] = true;
        } else {
            $settings = array("ClearEditedOrder" => true);
        }
    }

    parent::__construct($settings);
    $this->CurrPage = $_SERVER['PHP_SELF']; // /statistics.php
//    $this->checkSession();
//    $this->checkPageAccess();

    $this->Stylesheets[] = "<link rel='shortcut icon' href='" . $this->SiteUrl . "images/avalon.ico'>";
    $this->Stylesheets[] = "<!--[if IE]><link type='text/css' rel='stylesheet' href='/css/groundwork-ie.css'><![endif]-->";

    if ($this->isIe()) { // https://en.wikipedia.org/wiki/Jib
        // fixes issue in IE where sub-menu elements get displayed under iframe elements
        $this->IeJib = "<iframe class='ieJib' src='about:blank'></iframe>";

        $this->addStylesheet("/outreach/css/iejib.css");
    }

    $this->addStylesheet("/outreach/css/groundwork.css");
    $this->addStylesheet("/outreach/css/css3.css");
    $this->addStylesheet("/outreach/css/styles.css");
    $this->addStylesheet("/outreach/js/datepicker/public/css/default.css");
    /*$this->addStylesheet("/outreach/css/menu-slider.css");*/
    $this->addStylesheet("/outreach/css/popover.css");

    if ($this->LabName == "Alpha Medical Laboratory") {
        $this->addStylesheet("/outreach/css/aml.css");
    }

    $this->addScript("/outreach/js/jquery-2.1.0.min.js");
    $this->addScript("/outreach/js/components/navigation.js");
    $this->addScript("/outreach/js/popover.js");
    $this->addScript("/outreach/js/page.js");
    $this->addScript("/outreach/js/minidaemon.js");
    $this->addScript("/outreach/js/page_timer.js");
    $this->addScript("/outreach/js/velocity.min.js");

    if ($this->CurrPage != "/outreach/orderentry/add.php" && $this->CurrPage != "/outreach/admin/notifications.php") {
        $this->addScript("/outreach/js/datepicker/public/javascript/zebra_datepicker.src.js");
        $this->addScript("/outreach/js/page_datepicker.js");
    }

    $timeoutOverlay = "
            <div id='screen_lock' class='rounded'>
                <div class='row'>
                    <div class='one mobile twelfth' style='font-size: 30px;'><i class='icon-warning-sign green'></i></div>
                    <div class='ten mobile twelfths' style='text-align: center'>
                        <h4>Due to inactivity, you will be automatically logged out in
                            <br/><div id='timeRemaining' style='display: inline; font-weight: bold;'>30</div> seconds</h4>
                    </div>
                    <div class='one mobile twelfth' style='font-size: 30px;'><i class='icon-warning-sign green'></i></div>
                </div>
                <div class='row'>
                    <div class='one mobile whole' style='text-align: center'>
                        <h5>Please <button class='green' id='cancel_logout'>click here</button> to remain logged in.</h5>
                    </div>
                </div>
            </div>
        ";
    $this->Overlays[] = $timeoutOverlay;

    if ((array_key_exists("UpdatePassword", $_SESSION) && $_SESSION['UpdatePassword'] === true) || (array_key_exists("TempPassword", $_SESSION) && $_SESSION['TempPassword'] === true)) {

        $this->addStylesheet("/outreach/css/pwstrength.css");
        $this->addScript("/outreach/js/pwstrength.js");
        $this->addScript("/outreach/js/password_validator.js");

        $currentPasswordError = "";
        $passwordError = "";
        if (array_key_exists("Errors", $_SESSION) && !empty($_SESSION['Errors']) && is_array($_SESSION['Errors'])) {

            if (array_key_exists("currentPassword", $_SESSION['Errors'])) {
                //$currentPasswordError = "<p>" . $_SESSION['Errors']['currentPassword'] . "</p>";
                $currentPasswordError = "<div class='tooltip' id='currentPasswordError' style='top: 3px; display: block;'>" . $_SESSION['Errors']['currentPassword'] . "</div>";
            }
            if (array_key_exists("password", $_SESSION['Errors'])) {
                //$passwordError = "<p>" . $_SESSION['Errors']['password'] . "</p>";
                $passwordError = "<div class='tooltip' id='passwordError' style='top: 3px; display: block;'>" . $_SESSION['Errors']['password'] . "</div>";
            }

            unset($_SESSION['Errors']);
        }

        $password = "";
        $password2 = "";
        $currentPassword = "";
        if (array_key_exists("InputFields", $_SESSION) && !empty($_SESSION['InputFields']) && is_array($_SESSION['InputFields'])) {
            $password = $_SESSION['InputFields']['password'];
            $password2 = $_SESSION['InputFields']['password2'];
            $currentPassword = $_SESSION['InputFields']['currentPassword'];

            unset($_SESSION['InputFields']);
        }

        if (array_key_exists("TempPassword", $_SESSION) && $_SESSION['TempPassword'] === true) {
            $passwordTitle = "<h4>Temporary Password</h4><h5>For your security, we require that you choose a new, permanent password for your account.</h5>";
        } else {
            $passwordTitle = "<h4>Your password has expired. Please change your password.</h4>";
        }
        $updatePasswordOverlay = "
                <div id='update_password' class='rounded'>
                    <div class='row'>
                        <div class='one mobile whole'>
                            $passwordTitle
                        </div>
                    </div>
                    <form name='frmChangePassword' id='frmChangePassword' action='/outreach/settingsb.php' method='post'>
                    <input type='hidden' name='action' id='action' value='2' />
                    <input type='hidden' name='requestUri' id='requestUri' value='" . $_SERVER['REQUEST_URI'] . "' />
                    <div class='row'>
                        <div class='one mobile whole padded'>
                            <label for='password2'>Current Password</label>
                            <input type='password' name='currentPassword' id='currentPassword' value='$currentPassword' autocomplete='off' />
                            $currentPasswordError
                        </div>
                    </div>

                    <div class='row'>
                        <div class='one mobile half padded'>
                            <label for='password2'>New Password</label>
                            <input type='password' name='password' id='password' value='$password' autocomplete='off' />
                            $passwordError
                        </div>
                        <div class='one mobile half padded'>

                            <div class='pwstrength_viewport_progress'></div>
                        </div>
                    </div>

                    <div class='row'>
                        <div class='one mobile half padded'>
                            <label for='password2'>Verify Password</label>
                            <input type='password' name='password2' id='password2' value='$password2' autocomplete='off' />
                        </div>
                        <div class='one mobile half padded'></div>
                    </div>

                    <div class='row gap-top'>
                        <div class='one mobile whole'>
                            <button class='green submit' id='btnSubmit'>Submit</button>
                        </div>
                    </div>
                    </form>
                </div>
            ";

        $this->Overlays[] = $updatePasswordOverlay;
    } else if (array_key_exists("PasswordInterval", $_SESSION)) {
        $passwordInterval = "in " . $_SESSION['PasswordInterval'] . " days";
        if ($_SESSION['PasswordInterval'] == 0) {
            $passwordInterval = "today";
        }
        unset($_SESSION['PasswordInterval']);

        $passwordOverlay = "
                <div id='password_warning' class='rounded'>
                    <div class='row'>
                        <div class='one mobile whole' style='text-align: center;'>
                            <h4><i class='icon icon-warning-sign'></i> Your password will expire $passwordInterval. Do you want to change it now?</h4>
                        </div>
                    </div>
                    <div class='row gap-top'>
                        <div class='one mobile half pad-right' style='text-align: right;'>
                            <button class='green' id='lnkYes' data-type='" . $_SESSION['type'] . "' data-id='" . $_SESSION['id'] . "'>Yes</button>
                        </div>
                        <div class='one mobile half pad-left'>
                            <button class='green' id='lnkNo'>No</button>
                        </div>
                    </div>
                </div>
            ";

        $this->Overlays[] = $passwordOverlay;
    }


    if ($settings != null && array_key_exists("EditingWebOrder", $settings)) {
        $this->EditOrderLink = true;
    }
}


/* Simple function to replicate PHP 5 behaviour */
public function startPageLoadTimer() {
    list($usec, $sec) = explode(" ", microtime());
    $this->TimeStart = ((float)$usec + (float)$sec);
}

public function printPageLoadTime() {
    list($usec, $sec) = explode(" ", microtime());
    $this->TimeEnd = ((float)$usec + (float)$sec);
    $time = $this->TimeEnd - $this->TimeStart;
    echo "<b>Page loaded in: $time seconds</b>";
}

public function addScript($src) {
    if ($this->LabName == "Computer Service and Support") {
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"$src?timestamp=" . date('Ymdhis') . "\"></script>";
    } else {
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"$src?version=" . self::Version . "\"></script>";
    }
}

public function addStylesheet($href) {
    if ($this->LabName == "Computer Service and Support") {
        $this->Stylesheets[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$href?version=" . self::Version . "&timestamp=" . date('Ymdhis') . "\" >";
    } else {
        $this->Stylesheets[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$href?version=" . self::Version . "\" >";
    }
}

public function addOverlay($html) {
    $this->Overlays[] = $html;
}

public function checkSession() {
    if ($this->CurrPage != "/outreach/login.php") {
        $this->LoggedIn = parent::checkSession();

        if (!isset($_SESSION['id']) || empty($this->LoggedIn) || $this->LoggedIn == false) {
            $this->logout();
            $_SESSION['error'] = 1;
            header("Location: " . $this->SiteUrl);
            exit();
        }
    }

    if ($this->CurrPage != "/outreach/admin/index.php") {
        if (isset($_SESSION['users'])) {
            $_SESSION['users'] = "";
            unset($_SESSION['users']);
        }
    }

    if (isset($_SESSION) && isset($_SESSION['UserIds'])) { // clear the user ids saved from the cumulative page
        $_SESSION['UserIds'] = "";
        unset($_SESSION['UserIds']);
    }
}

private function checkPageAccess() {
    $hasAccess = false;

    if (in_array($this->CurrPage, $this->ResultsPages)) {
        // User is on a results page
        if (($this->User->typeId == 2 || $this->User->typeId == 3) && !$this->User->hasOrderEntrySetting(1)) {
            // Client/Doctor user and result search is not disabled
            $hasAccess = true;
        } else if ($this->User->typeId == 6) {
            // Insurance user
            $hasAccess = true;
        }
        if ($this->CurrPage == "/outreach/settings.php" && $this->User->typeId == 7) {
            $hasAccess = true;
        }

        if (isset($this->AdminUser) && $this->AdminUser instanceof AdminUser && $this->AdminUser->typeId == 7) {
            if ($this->AdminUser->hasOrderEntrySetting(1) && $this->CurrPage != "/outreach/settings.php") {
                $hasAccess = false;
            } else {
                $hasAccess = true;
            }
        }
    } else if (in_array($this->CurrPage, $this->OrderEntryPages) && ($this->User->typeId == 2 || $this->User->typeId == 3) && isset($this->User->UserSettings) && $this->User->hasUserSetting(3)) {
        // Client/Doctor user is on an order entry page and is assigned the order entry setting

        if ($this->CurrPage != "/outreach/orderentry/settings.php" || self::HasESignatureOnReq == true) {
            $hasAccess = true;
        }
    } else if (in_array($this->CurrPage, $this->OrderEntryPages) && isset($this->AdminUser) && $this->AdminUser instanceof AdminUser && $this->AdminUser->typeId == 7
        && isset($_SESSION['AdminId']) && !empty($_SESSION['AdminId']) && isset($_SESSION['AdminType']) && $_SESSION['AdminType'] == 7) {

        $hasAccess = true;

    } else if (in_array($this->CurrPage, $this->AdminPages) && ($this->User->typeId == 1 || $this->User->typeId == 7)) {
        // Admin user is on an admin page
        $hasAccess = true;
    } else if (in_array($this->CurrPage, $this->SalesPages) && ($this->User->typeId == 5 || ($this->User->typeId == 1 && $this->User->hasAdminSetting(13)))) {
        // Sales user or admin with owner/manager setting is on a sales page
        $hasAccess = true;
    } else if (in_array($this->CurrPage, $this->RestrictedPages) && isset($this->User->UserSettings)) {
        // User is on a Reports page
        foreach ($this->User->UserSettings as $setting) {
            if ($setting->pageName == $this->CurrPage) {
                $hasAccess = true;
            }
        }
    }

    if (!$hasAccess) {
        $this->logout(true);
        header("Location: " . $this->SiteUrl);
        exit();
    }
}

public function startPagePrint() { ?>
<!doctype html>
<!--[if lt IE 7]><html class="no-js ie ie6 lt-ie7 lt-ie8 lt-ie9 lt-ie10"><![endif]-->
<!--[if IE 7]>   <html class="no-js ie ie7 lt-ie8 lt-ie9 lt-ie10"><![endif]-->
<!--[if IE 8]>   <html class="no-js ie ie8 lt-ie9 lt-ie10"><![endif]-->
<!--[if IE 9]>   <html class="no-js ie ie9 lt-ie10"><![endif]-->
<!--[if gt IE 9]><html class="no-js ie ie10"><![endif]-->
<!--[if !IE]><!-->
<html class="no-js"><!--<![endif]-->
<head id="Head1">
    <meta charset="iso-8859-1">
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=-5, maximum-scale=5">
    <meta name="robots" content="noindex, nofollow">
    <title>Avalon Outreach Portal</title>
    <?php
    foreach ($this->Stylesheets as $stylesheet) {
        echo $stylesheet;
    }
    ?>
</head>
<body>

<?php
}

public function printPageHeader() {
    $dataCompression = 80;
    $userTypeId = null;
    $hasStatistics = false;
    $hasAbnormalsReport = false;
    $hasPositiveTestsReport = false;
    $hasConsistencyReport = false;
    $isMasterAdmin = false;
    $hasOrderEntry = false;
    $isSalesOwner = false;
    $class = "class=\"one half\"";
    $numIcons = 2;
    $masterAdminLink = "";
    $hasStandingOrdersReport = false;
    $resultSearchDisabled = false;
    $canManageEmailNotifications = false;

    $printResultSearchLi = true;

    $emailAdminLink = "";

    if ($_SERVER['PHP_SELF'] == "/outreach/cumulative.php" || $_SERVER['PHP_SELF'] == "/cumulative.php") {
        $dataCompression = 120;
    }
    $userTypeId = $this->User->typeId;
    if(isset($this->User) && $this->User != null && !$this->User instanceof AdminUser) {

        if ($this->User->hasUserSettingByName("Has Statistics")) {
            $hasStatistics = true;
        }
        if ($this->User->hasUserSettingByName("Has Abnormals Report")) {
            $hasAbnormalsReport = true;
        }
        if ($this->User->hasUserSettingByName("Has Order Entry")) {
            $hasOrderEntry = true;
        }
        if ($this->User->hasUserSettingByName("Has Inconsistent Report")) {
            $hasConsistencyReport = true;
        }
        if ($this->User->hasUserSetting(6)) {
            $hasPositiveTestsReport = true;
        }
        if ($this->User->hasOrderEntrySetting(1)) {
            $resultSearchDisabled = true;
        }

        if (isset($this->AdminUser) && $this->AdminUser instanceof AdminUser && $this->AdminUser->typeId == 7
            && isset($_SESSION['AdminId']) && !empty($_SESSION['AdminId']) && isset($_SESSION['AdminType']) && $_SESSION['AdminType'] == 7) {
            $hasOrderEntry = true;
        }

    } else if (isset($this->User) && $this->User != null && $this->User instanceof AdminUser) {
        if ($this->User->hasAdminSetting(8)) {
            $isMasterAdmin = true;
        }
        if ($this->User->hasAdminSetting(13)) {
            $isSalesOwner = true;
        }
        if ($this->User->hasAdminSetting(14)) {
            $canManageEmailNotifications = true;
        }
    }

    if (isset($this->AdminUser) && $this->AdminUser instanceof AdminUser) {
        if ($this->AdminUser->hasOrderEntrySetting(1)) {
            $resultSearchDisabled = true;
        }
    }

    if ($userTypeId != 1 && $userTypeId != null) {
        if (!self::ReportsDisabled && ($hasStatistics || $hasAbnormalsReport || $hasPositiveTestsReport || $hasConsistencyReport || ($hasOrderEntry && self::HasManifestReport == true))) {
            $numIcons += 1;
        }
        if ($hasOrderEntry) {
            $numIcons += 1;
        }

        if ($userTypeId == 5 && !$this->User->IsGroupLeader) {
            $numIcons -= 1;
        }

        if ($resultSearchDisabled) {
            $numIcons -= 1;
        }
    } else if ($userTypeId == 1) {
        $numIcons += 1;
        if ($isSalesOwner) {
            $numIcons += 1;
        }
        if ($canManageEmailNotifications) {
            $numIcons += 1;
        }
    }

    $printSalesReportMenu = false;
    if (strpos($_SERVER['PHP_SELF'], "/outreach/sales/settings") !== false) {
        $numIcons += 1;
        $printSalesReportMenu = true;
    }

    if ($numIcons == 1) {
        $class = "class=\"one mobile whole\"";
    } else if ($numIcons == 2) {
        $class = "class=\"one mobile half\"";
    } else if ($numIcons == 3) {
        $class = "class=\"one mobile third\"";
    } else if ($numIcons == 4) {
        $class="class=\"one mobile fourth\"";
    } else if ($numIcons == 5) {
        $class="class=\"one mobile fifth\"";
    } else if ($numIcons == 6) {
        $class="class=\"one mobile sixth\"";
    }

    $viewUsersLi = "";
    if ((in_array($this->CurrPage, $this->AdminPages) || $this->CurrPage == "/outreach/settings.php") && $this->User->typeId == 7) {
        $printResultSearchLi = false;


        if ($this->CurrPage == "/outreach/settings.php") {
            $viewUsersLi = "<li $class><a href=\"/outreach/admin/index.php\" ><i class=\"icon icon-group\"></i>View Users</a></a></li>";
        } else {
            $class = substr($class, 0, strlen($class) - 1) . " skip-one\"";
        }
    }

    $salesOwnerHtml = "";
    $salesReportHtml = "";

    $salesEditOrderLink = "";
    $editOrderLink = "";
    if ($this->EditOrderLink == true) {
        $salesEditOrderLink = "&z=1";
        $editOrderLink = "?z=1";
    }

    if ($printSalesReportMenu == true) {
        $salesReportHtml = "
                <li $class><a href=\"#\"><i class=\"icon icon-book\"></i>Reports</a>
                    <ul class=\"box_shadow sub-menu\">
                        $this->IeJib
                        <li>
                            <a href=\"/outreach/sales/index.php?chart=1&$salesEditOrderLink\"><i class=\"icon icon-dashboard\"></i>Sales Goals</a>
                        </li>
                        <li>
                            <a href=\"/outreach/sales/index.php?chart=2&$salesEditOrderLink\"><i class=\"icon icon-signal\"></i>Sales Report</a>
                        </li>
                    </ul>
                </li>
            ";
    }

    $notificationLink = "";
    if ($userTypeId == 1) { // print menu for admin section

        $adminSettingsSubmenu = "";
        if ($canManageEmailNotifications) {
            $adminSettingsSubmenu = "<li $class><a href=\"/outreach/admin/email.php\" ><i class=\"icon icon-flag\"></i>Email Notifications</a></li>";
        }

        if ($isSalesOwner) {
            $salesOwnerHtml = "
                    <li $class><a href=\"#\"><i class=\"icon icon-gear\"></i>Sales Settings</a>
                        <ul class=\"box_shadow sub-menu\">
                            $this->IeJib
                            <li><a href=\"/outreach/sales/settings/index.php\"><i class=\"icon icon-group\"></i>
                                View Sales Goals
                            </a></li>
                            <li><a href=\"/outreach/sales/settings/manage.php?action=1\"><i class=\"icon icon-plus\"></i>
                                Add Sales Goal
                            </a></li>
                        </ul>
                    </li>
                ";
        }
        $userNotificationsLink = "";
        if (self::HasUserNotifications) {
            $userNotificationsLink = "<li><a href=\"/outreach/admin/notifications.php\" ><i class=\"icon icon-edit-sign\"></i>User Notifications</a></li>";
        }
        $menuHtml = "
                <li $class >
                    <a href=\"/outreach/admin/index.php\" ><i class=\"icon icon-user-md\"></i>Site Admin</a>
                    <ul class=\"box_shadow sub-menu\">
                        $this->IeJib
                        <li><a href=\"/outreach/admin/index.php\" ><i class=\"icon icon-group\"></i>View Users</a></a></li>
                        <li><a href=\"/outreach/admin/add.php\" ><i class=\"icon icon-plus\"></i>Add User</a></li>
                        $userNotificationsLink
                        $masterAdminLink
                    </ul>
                </li>
                <li $class>
                    <a href=\"/outreach/admin/required/\" ><i class=\"icon icon-legal\"></i>Order Entry Required Fields</a>
                </li>
                $adminSettingsSubmenu
                $salesReportHtml
                $salesOwnerHtml
                <li $class ><a href=\"/outreach/logout.php\" ><i class=\"icon icon-off\"></i>Logout</a></li>
            ";


    } else {
        $userIcons = "";
        if (!self::ReportsDisabled && ($hasStatistics || $hasAbnormalsReport || $hasPositiveTestsReport || $hasStandingOrdersReport || $hasConsistencyReport ||($hasOrderEntry && self::HasManifestReport == true))) {
            $userIcons .= "
                    <li $class><a href=\"#\"><i class=\"icon icon-book\"></i>Reports</a>
                        <ul class=\"box_shadow sub-menu\">
                            $this->IeJib";
            if ($hasStatistics) {
                $userIcons .= "<li><a href=\"/outreach/statistics.php$editOrderLink\"><i class=\"icon icon-bar-chart\"></i>Result Statistics</a></li>";
            }
            if ($hasAbnormalsReport) {
                $userIcons .= "<li><a href=\"/outreach/abnormals.php$editOrderLink\"><i class=\"icon icon-exclamation-sign\"></i>Abnormals Report</a></li>";
            }
            if ($hasPositiveTestsReport) {
                $userIcons .= "<li><a href=\"/outreach/reports.php$editOrderLink\"><i class=\"icon icon-medkit\"></i>Test Specific Results Report</a></li>";
            }
            if ($hasStandingOrdersReport) {
                $userIcons .= "<li><a href=\"#\"><i class=\"icon icon-calendar\"></i>Standing Orders Report</a></li>";
            }
            if ($hasConsistencyReport) {
                $userIcons .= "<li><a href=\"/outreach/consistency.php$editOrderLink\"><i class=\"icon icon-exclamation-sign\"></i>Inconsistent Report</a></li>";
            }
            if ($hasOrderEntry && self::HasManifestReport == true) {
                $userIcons .= "<li><a href=\"/outreach/manifest.php$editOrderLink\"><i class=\"icon icon-book\"></i>Manifest Report</a></li>";
            }
            $userIcons .= "</ul></li>";
        }
        if ($hasOrderEntry) {
            $userIcons .= "
                    <li $class><a href=\"#\" ><i class=\"icon icon-user-md\"></i>Order Entry</a>
                        <ul class=\"box_shadow sub-menu\">
                            $this->IeJib
                            <li><a href=\"/outreach/orderentry/add.php$editOrderLink\"><i class=\"icon icon-plus\"></i>New Order</a></li>
                            <li><a href=\"/outreach/orderentry/index.php$editOrderLink\"><i class=\"icon icon-group\"></i>View Orders</a></li>";

            if (self::HasESignatureOnReq) {
                $userIcons .= "<li><a href=\"/outreach/orderentry/settings.php$editOrderLink\"><i class=\"icon icon-pencil\"></i>Manage E-Signature</a></li>";
            }

            $userIcons .= "
                        </ul>
                    </li>";
        }
        if ($userTypeId != null && $userTypeId == 5) { // salesman
            if ($this->User->IsGroupLeader) {
                $userIcons .= "
                        <li $class><a href=\"#\"><i class=\"icon icon-gear\"></i>Settings</a>
                            <ul class=\"box_shadow sub-menu\">
                                $this->IeJib
                                <li><a href=\"/outreach/sales/settings/index.php\"><i class=\"icon icon-group\"></i>
                                    View Sales Goals
                                </a></li>
                                <li><a href=\"/outreach/sales/settings/manage.php?action=1\"><i class=\"icon icon-plus\"></i>
                                    Add Sales Goal
                                </a></li>
                            </ul>
                        </li>";
            }
        } elseif (!$resultSearchDisabled && $printResultSearchLi && $userTypeId != 8) {
            $userIcons .= "<li $class><a href=\"/outreach/search.php$editOrderLink\"><i class=\"icon icon-search\"></i>Result Search</a></li>";
        }

        if ($userTypeId == 8) {

            if ($this->CurrPage == "/outreach/admin/kitnumbers.php") {
                $userIcons = "<li $class><a href=\"/outreach/admin/index.php\" ><i class=\"icon icon-group\"></i>View Users</a></a></li>";
            } else {
                $userIcons .= "<li $class><a href=\"/outreach/admin/kitnumbers.php\"><i class=\"icon icon-search\"></i>Manage Kit Numbers</a></li>";
            }


        }

        $viewUsersLi2 = "";
        if (array_key_exists("AdminId", $_SESSION) && isset($_SESSION['AdminId']) && array_key_exists("AdminType", $_SESSION) && $_SESSION['AdminType'] == 7) {
            $viewUsersLi2 = "<li><a href='javascript:void(0)' id='lnkViewUsers'><i class='icon icon-group'></i>View Users</a></a></li>";
        }


        $menuHtml = "
        $viewUsersLi
        $userIcons
        $salesReportHtml
        <li $class><a href=\"#\"><i class=\"icon icon-gears\"></i></a>
            <ul class=\"box_shadow sub-menu\">
                $this->IeJib
                $viewUsersLi2
                <li><a href='/outreach/settings.php' ><i class='icon icon-gear'></i>User Settings</a></li>
                <li><a href='/outreach/logout.php' class='link'><i class='icon icon-off'></i>Logout</a></li>
            </ul>
        </li>";

        if (self::HasUserNotifications == true) {
            require_once 'DAOS/UserNotificationDAO.php';
            $nDAO = new UserNotificationDAO(array("Conn" => $this->Conn));

            $dteDateTime = new DateTime();
            $currDateTime = $dteDateTime->format('Y-m-d H:i:s');

            $aryNotifications = $nDAO->getNotifications(array(
                "isActive" => true,
                "notificationTypeId" => 4,
                "dateFrom" => $currDateTime,
                "dateTo" => $currDateTime,
                "userId" => $_SESSION['id']
            ));

            $strNotifications = "<a href=\"javascript:void(0)\" id=\"btnCloseNotifications\" class=\"btn button\" title=\"Hide Notifications\">X</a>";
            $notificationLink = "";
            if (count($aryNotifications) > 0) {
                require_once 'DAOS/ResultLogDAO.php';
                $slideContainerClass = "slideOut";
                $bellIconClass = "icon-bell";
                $showNotification = "data-show='0'";
                foreach ($aryNotifications as $notification) {
                    if ($notification->idNotificationLog == null) { // log the initial view so it doesn't automatically get displayed the next time
                        ResultLogDAO::addNotificationLogEntry($_SESSION['id'], 19, $notification->idNotifications, array("Conn" => $this->Conn, "Ip" => $this->Ip));
                        $slideContainerClass = "slideIn";
                        $bellIconClass = "icon-bell-alt";
                        $showNotification = "data-show='1'";
                    }

                    if (self::AlwaysShowNotifications && $this->CurrPage == "/outreach/search.php") {
                        $showNotification = "data-show='1'";
                    }

                    $title = $notification->notificationTitle;
                    $text = $notification->notificationText;
                    $strNotifications .= "<strong>" . $title . "</strong><br/>" . $text;
                }

                /*$notificationLink = "
                    <div class='transition-container'>
                        <div class='slide-container $slideContainerClass' id='notifications'>
                            <button class='close-button' id='close-button'>X</button>
                            $strNotifications
                        </div>
                        <a href='javascript:void(0)' data-tooltip='View notifications' data-position='right' class='btn btn-default open-button' id='lnkNotification'><i class='icon $bellIconClass'></i></a>
                    </div>";*/
                $notificationLink = "<button type='button' class='rounded-20' id='lnkNotification'
                    data-container='body'
                    data-placement='bottom'
                    data-toggle='popover'
                    data-trigger='focus'
                    data-html='true'
                    data-content='$strNotifications'
                    $showNotification><i class='icon $bellIconClass'></i></button>";
            }
        }
    }

    $html = "
    <div id=\"page_wrapper\">
        <div id=\"menu-container\" class=\"box_shadow\">
            <div class=\"container\">
                <div class=\"row\" id=\"outer\">
                    <div class=\"one mobile whole\" data-compression=\"$dataCompression\">
                        <nav class=\"nav small-tablet\" role=\"navigation\" class=\"nav\" title=\"\">
                            <ul id=\"main-menu\" role=\"menubar\" class=\"menu\">
                                $menuHtml
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <div id=\"page_content\">
        $notificationLink
        ";

    echo $html;
}

public function endPagePrint() {

    $siteUrl = $this->SiteUrl;
    $logoFile = $this->Logo;

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

    $footerLogoImg = "";
    //if ($siteUrl == "https://acsoutreach.com/") {
    if ($this->LabName === 'American Clinical Solutions') {
        $footerLogoImg = "<img src=\"" . $siteUrl . "images/$logoFile\" alt=\"Avalon LIS\" id=\"headerLogo\" />";
    }

    $html = "
            </div>
            <footer>
                <p style=\"text-align: center; margin-bottom: 0; display: block !important;\">
                    $footerLogoImg
                    &copy; Powered by Computer Service &amp; Support " . date("Y") . "
                    <img src=\"/outreach/images/frame48.gif\" alt=\"Avalon LIS\" style=\"width: 28px; height: 28px; vertical-align: bottom;\" />
                </p>
            </footer>
        </div>
        <div id=\"overlay\"></div>
        <div id=\"overlay2\"></div>
        $overlays
        $scripts
        </body>
        </html>";

    echo $html;

    //$this->printPageLoadTime();
}

public function __get($field) {
    $value = parent::__get($field);
    if (empty($value)) {
        if ($field == "User") {
            $value = $this->User;
        } else if ($field == "UserDAO") {
            $value = $this->UserDAO;
        } else if ($field == "UseOldOrderInfoFormat") {
            $value = self::UseOldOrderInfoFormat;
        } else if ($field == "HasLabelPrint") {
            $value = self::HasLabelPrint;
        }

    }
    return $value;
}
}
?>
