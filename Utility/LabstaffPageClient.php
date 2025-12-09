<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 4/15/15
 * Time: 11:44 AM
 */

if (!isset($_SESSION)) {
    session_start();
}

require_once 'IConfig.php';
require_once 'LabstaffAuth.php';


class LabstaffPageClient extends LabstaffAuth implements IConfig {

    private $Stylesheets = array();
    private $Scripts = array();
    private $Overlays = array();

    private $CurrPage;
    private $PageTitle = "";


    public function __construct(array $data = null) {
        parent::__construct(array("Action" => 2));
        if ($data != null) {
            if (array_key_exists("PageTitle", $data)) {
                $this->PageTitle = $data['PageTitle'];
            }
        }

        $this->checkPageAccess();


        $this->CurrPage = $_SERVER['PHP_SELF'];

        $this->Stylesheets[] = "<link rel=\"shortcut icon\" href=\"/images/avalon.ico\">";
        $this->Stylesheets[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/materialize.min.css\" media=\"screen,projection\" />";
        $this->Stylesheets[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/styles.css\" />";
        $this->Stylesheets[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/palette-main.css\" />";
        $this->Stylesheets[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/zebra_datepicker.css\" />";


        $this->Scripts[] = "<script type=\"text/javascript\" src=\"https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js\"></script>";
        /*$this->Scripts[] = "<script type=\"text/javascript\" src=\"https://cdnjs.cloudflare.com/ajax/libs/materialize/0.96.1/js/materialize.js\"></script>";*/
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/materialize.min.js\"></script>";

        /*$this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/global.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/init.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/animation.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/buttons.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/collapsible.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/colors.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/dropdown.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/forms.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/jquery.easing.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/leanModal.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/materialbox.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/pushpin.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/sideNav.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/slider.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/tabs.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/toasts.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/tooltip.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/transitions.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/waves.js\"></script>";*/



        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/script.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/page.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/minidaemon.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/page_timer.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/zebra_datepicker.js\"></script>";
        $this->Scripts[] = "<script type=\"text/javascript\" src=\"/js/page_datepicker.js\"></script>";

        $timeoutOverlay = "
            <div id=\"screen_lock\" class=\"rounded\">
                <div class=\"row\">
                    <div class=\"one twelfth\" style=\"font-size: 30px;\"><i class=\"icon-warning-sign green\"></i></div>
                    <div class=\"ten twelfths\" style=\"text-align: center\">
                        <p>
                            Due to inactivity, you will be automatically logged out in

                            <div id=\"timeRemaining\" style=\"display: inline; font-weight: bold; font-size: 16px;\">30</div>
                            seconds
                        </p>
                    </div>
                    <div class=\"one twelfth\" style=\"font-size: 30px;\"><i class=\"icon-warning-sign green\"></i></div>
                </div>
                <div class=\"row\">
                    <div class=\"one whole\" style=\"text-align: center\">
                        <p>Please <a href=\"javascript:void(0)\" class=\"waves-effect waves-light btn\" id=\"cancel_logout\">click here</a> to remain logged in.</p>
                    </div>
                </div>
            </div>
        ";
        $this->Overlays[] = $timeoutOverlay;
    }

    private function checkPageAccess() {

    }

    public function startPagePrint() {
        $stylesheets = "";
        foreach ($this->Stylesheets as $stylesheet) {
            $stylesheets .= $stylesheet;
        }

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Labstaff Portal</title>
            <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
            <!--<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">-->
            <meta name=\"robots\" content=\"noindex, nofollow\">
            <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge,chrome=1\">
            <!--Let browser know website is optimized for mobile-->
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, maximum-scale=3.0, maximum-scale=3.0, user-scalable=yes\"/>

            $stylesheets

        </head>
        <body>
        <div id=\"container\">
        ";

        echo $html;

    }

    public function printPageHeader(array $palette = null) {
        $pageTitle = $this->PageTitle;

        $liLogoStyle = "";

        $liHomeStyle = "";
        $liOrderEntryStyle = "";
        $liEnquiryStyle = "";
        $liReportsStyle = "";
        $liPrefsStyle = "";
        $liAddColorStyle = "";
        $liEditColorStyle = "";

        $aHomeStyle = "";
        $aOrderEntryStyle = "";
        $aEnquiryStyle = "";
        $aReportsStyle = "";
        $aPrefsStyle = "";
        $aAddColorStyle = "";
        $aEditColorStyle = "";

        $orderEntryActive = "";
        $prefsActive = "";

        /*if  ($this->PageTitle == "Home") {
            $liLogoStyle = "style=\"border-bottom: none;\"";
            $liHomeStyle = "style=\"background: #396A92; border-top: 1px solid #091D42; border-bottom: 1px solid #091D42;\"";
            $aHomeStyle = "style=\"color: #FDFDFD !important;\"";
        } else if  ($this->PageTitle == "Order Entry") {
            $liHomeStyle = "style=\"border-bottom: none;\"";
            $liOrderEntryStyle = "style=\"background: #396A92; border-top: 1px solid #091D42; border-bottom: 1px solid #091D42;\"";
            $aOrderEntryStyle = "style=\"color: #FDFDFD !important;\"";
        } else if  ($this->PageTitle == "Result Enquiry") {
            $liOrderEntryStyle = "style=\"border-bottom: none;\"";
            $liEnquiryStyle = "style=\"background: #396A92; border-top: 1px solid #091D42; border-bottom: 1px solid #091D42;\"";
            $aEnquiryStyle = "style=\"color: #FDFDFD !important;\"";
        } else if  ($this->PageTitle == "Reports") {
            $liEnquiryStyle = "style=\"border-bottom: none;\"";
            $liReportsStyle = "style=\"background: #396A92; border-top: 1px solid #091D42; border-bottom: 1px solid #091D42;\"";
            $aReportsStyle = "style=\"color: #FDFDFD !important;\"";
        } else if  ($this->PageTitle == "Preferences") {
            $liReportsStyle = "style=\"border-bottom: none;\"";
            $liPrefsStyle = "style=\"background: #396A92; border-top: 1px solid #091D42; border-bottom: 1px solid #091D42;\"";
            $aPrefsStyle = "style=\"color: #FDFDFD !important;\"";
        }*/

        if  ($this->PageTitle == "Home") {
            $liLogoStyle = "id=\"liLogoBorder\"";
            $liHomeStyle = "id=\"liHomeStyle\"";
            $aHomeStyle = "id=\"aHomeStyle\"";
        } else if  ($this->PageTitle == "Order Entry") {
            $liHomeStyle = "id=\"liHomeBorder\"";
            $liOrderEntryStyle = "id=\"liOrderEntryStyle\"";
            $aOrderEntryStyle = "id=\"aOrderEntryStyle\"";
            $orderEntryActive = "active";
        } else if  ($this->PageTitle == "Result Enquiry") {
            $liOrderEntryStyle = "id=\"liOrderEntryBorder\"";
            $liEnquiryStyle = "id=\"liEnquiryStyle\"";
            $aEnquiryStyle = "id=\"aEnquiryStyle\"";
        } else if  ($this->PageTitle == "Reports") {
            $liEnquiryStyle = "id=\"liEnquiryBorder\"";
            $liReportsStyle = "id=\"liReportsStyle\"";
            $aReportsStyle = "id=\"aReportsStyle\"";
        } else if  ($this->PageTitle == "Preferences") {
            $liReportsStyle = "id=\"liReportsBorder\"";
            $liPrefsStyle = "id=\"liPrefsStyle\"";
            $aPrefsStyle = "id=\"aPrefsStyle\"";
            $prefsActive = "active";
        } else if ($this->PageTitle == "Add Color Theme") {
            $prefsActive = "active";
        } else if ($this->PageTitle == "Edit Color Themes") {
            $prefsActive = "active";
        } else if ($this->PageTitle == "Scheduling Calendar") {
            $orderEntryActive = "active";
        }


        $html = "
        <header>
            <nav class=\"top-nav\">
                <div class=\"container\" id=\"top-nav-container\">
                    <div class=\"nav-wrapper\" style=\"margin-left: 20px;\"><a class=\"page-title\">$pageTitle</a></div>
                </div>
            </nav>
            <div class=\"container\" id=\"toggle-container\">
                <a href=\"#\" data-activates=\"slide-out\" class=\"button-collapse\" id=\"toggle\"><i class=\"mdi-navigation-menu\"></i></a>
            </div>
            <ul class=\"right hide-on-med-and-down side-nav fixed z-depth-2\" style=\"left: 0;\">
                <li class=\"logo\" $liLogoStyle>
                    <div class=\"logo-container\">
                        <img src=\"/images/AvalonSplashNoBorders.png\" alt=\"Avalon LIS\" id=\"logo\"/>
                    </div>
                </li>
                <li $liHomeStyle><a href=\"/main/index.php\" class=\"waves-effect waves-palette\" $aHomeStyle><i class=\"mdi-action-home\"></i>Home</a></li>
                <li $liOrderEntryStyle>
                    <ul class=\"collapsible collapsible-accordion\">
                        <li style=\"border-bottom: none;\">
                            <a href=\"#\" id=\"menu-collapsible\" class=\"collapsible-header waves-effect waves-palette $orderEntryActive\" $aOrderEntryStyle>
                                <i class=\"mdi-maps-local-hospital\" style=\"float:left;\"></i><div style=\"float: left;\">Order Entry</div><i class=\"mdi-navigation-expand-more\"></i>
                            </a>

                            <div class=\"collapsible-body\" id=\"menu-body\">
                                <ul style=\"border-bottom: none;\">
                                    <li><a href=\"/orderentry/index.php\">New Order</a></li>
                                    <li><a href=\"/orderentry/calendar.php\">Scheduling Calendar</a></li>

                                </ul>
                            </div>
                        </li>
                    </ul>


                </li>
                <li $liEnquiryStyle><a href=\"/enquiry/index.php\" class=\"waves-effect waves-palette\" $aEnquiryStyle><i class=\"mdi-action-search\"></i>Result Enquiry</a></li>
                <li $liReportsStyle><a href=\"/reports/index.php\" class=\"waves-effect waves-palette\"  $aReportsStyle><i class=\"mdi-editor-insert-chart\"></i>Reports</a></li>
                <li $liPrefsStyle>
                    <ul class=\"collapsible collapsible-accordion\">
                        <li style=\"border-bottom: none;\">
                            <a href=\"#!\" id=\"menu-collapsible\" class=\"collapsible-header waves-effect waves-palette $prefsActive\" $aPrefsStyle>
                                <i class=\"mdi-action-settings \" style=\"float:left;\"></i><div style=\"float: left;\">Preferences</div><i class=\"mdi-navigation-expand-more\"></i>
                            </a>
                            <div class=\"collapsible-body\" id=\"menu-body\">
                                <ul style=\"border-bottom: none;\">
                                    <li><a href=\"/theme/add.php\">Add Color Theme</a></li>
                                    <li><a href=\"/theme/edit.php\" style=\"border-bottom: none;\">Edit Color Themes</a></li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </li>
                <li><a href=\"/logout.php\" class=\"waves-effect waves-palette\"><i class=\"mdi-action-settings-power\"></i>Logout</a></li>
                <li><a href=\"javascript:void(0)\" class=\"waves-effect waves-palette\"><i class=\"mdi-action-help\"></i>Help</a></li>
            </ul>

            <ul id=\"slide-out\" class=\"side-nav z-depth-2\">
                <li class=\"logo\">
                    <div class=\"logo-container\">
                        <img src=\"/images/AvalonSplashNoBorders.png\" alt=\"Avalon LIS\" id=\"logo\"/>
                    </div>
                </li>
                <li $liHomeStyle><a href=\"/main/index.php\" $aHomeStyle><i class=\"mdi-action-home\"></i>Home</a></li>
                <li $liOrderEntryStyle><a href=\"/orderentry/index.php\" $aOrderEntryStyle><i class=\"mdi-maps-local-hospital\"></i>Order Entry</a></li>
                <li $liEnquiryStyle><a href=\"/enquiry/index.php\" $aEnquiryStyle><i class=\"mdi-action-search\"></i>Result Enquiry</a></li>
                <li $liReportsStyle><a href=\"/reports/index.php\" $aReportsStyle><i class=\"mdi-editor-insert-chart\"></i>Reports</a></li>
                <li $liPrefsStyle>
                    <ul class=\"collapsible collapsible-accordion\">
                        <li style=\"border-bottom: none;\">
                            <a href=\"#!\" class=\"collapsible-header waves-effect waves-palette $prefsActive\" $aPrefsStyle>
                                <i class=\"mdi-action-settings\" style=\"float:left;\"></i><div style=\"float: left;\">Preferences</div><i class=\"mdi-navigation-expand-more\"></i>
                            </a>
                            <div class=\"collapsible-body\" >
                                <ul style=\"border-bottom: none;\">
                                    <li><a href=\"/theme/add.php\">Add Color Theme</a></li>
                                    <li><a href=\"/theme/edit.php\" style=\"border-bottom: none;\">Edit Color Themes</a></li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </li>
                <li><a href=\"/logout.php\"><i class=\"mdi-action-settings-power\"></i>Logout</a></li>
                <li><a href=\"javascript:void(0)\"><i class=\"mdi-action-help\"></i>Help</a></li>
            </ul>
        </header>
        ";



        echo $html;

    }

    protected function printPage(array $settings = null) {

    }

    public function endPagePrint() {
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



        $html = "
            $overlays
            <div id=\"overlay\"></div>



            <footer class=\"page-footer\">
                <p style=\"text-align: center; margin-bottom: 0; display: block !important;\">
                    &copy; Powered by Computer Service &amp; Support " . date("Y") . "
                    <img src=\"/images/AvalonSquares.png\" alt=\"Avalon LIS\" style=\"width: 28px; height: 28px; vertical-align: bottom;\" />
                </p>
            </footer>

            </div>
            $scripts


            <div class=\"preloader-wrapper big active\">
                <div class=\"spinner-layer spinner-blue-only\">
                    <div class=\"circle-clipper left\">
                        <div class=\"circle\"></div>
                    </div>
                    <div class=\"gap-patch\">
                        <div class=\"circle\"></div>
                    </div>
                    <div class=\"circle-clipper right\">
                        <div class=\"circle\"></div>
                    </div>
                </div>
            </div>
        </body>

        </html>
        ";

        echo $html;
    }


    public function addScript($src, $includeVersion = true) {

        if ($includeVersion) {
            $src .= "?version=" . time();
        }

        $this->Scripts[] = "<script type=\"text/javascript\" src=\"$src\"></script>";
    }

    public function addStylesheet($href, array $attributes = null) {
        $link = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$href\"";
        if ($attributes != null) {
            foreach ($attributes as $name => $value) {
                $link .= " $name=\"$value\"";
            }
        }
        $link .= ">";

        $this->Stylesheets[] = $link;
    }

    public function addOverlay($html) {
        $this->Overlays[] = $html;
    }

    public function getStateSelect($name = "state", $id = "state", $class = "", $style = "") {
        if (!empty($class)) {
            $class = "class=\"$class\"";
        }
        if (!empty($style)) {
            $style = "style=\"$style\"";
        }

        return "
            <select name=\"$name\" id=\"$id\" $class $style>
                <option value=\"0\" disabled selected>Select a state</option>
                <option value=\"AL\">Alabama</option>
                <option value=\"AK\">Alaska</option>
                <option value=\"AZ\">Arizona</option>
                <option value=\"AR\">Arkansas</option>
                <option value=\"CA\">California</option>
                <option value=\"CO\">Colorado</option>
                <option value=\"CT\">Connecticut</option>
                <option value=\"DE\">Delaware</option>
                <option value=\"DC\">District Of Columbia</option>
                <option value=\"FL\">Florida</option>
                <option value=\"GA\">Georgia</option>
                <option value=\"HI\">Hawaii</option>
                <option value=\"ID\">Idaho</option>
                <option value=\"IL\">Illinois</option>
                <option value=\"IN\">Indiana</option>
                <option value=\"IA\">Iowa</option>
                <option value=\"KS\">Kansas</option>
                <option value=\"KY\">Kentucky</option>
                <option value=\"LA\">Louisiana</option>
                <option value=\"ME\">Maine</option>
                <option value=\"MD\">Maryland</option>
                <option value=\"MA\">Massachusetts</option>
                <option value=\"MI\">Michigan</option>
                <option value=\"MN\">Minnesota</option>
                <option value=\"MS\">Mississippi</option>
                <option value=\"MO\">Missouri</option>
                <option value=\"MT\">Montana</option>
                <option value=\"NE\">Nebraska</option>
                <option value=\"NV\">Nevada</option>
                <option value=\"NH\">New Hampshire</option>
                <option value=\"NJ\">New Jersey</option>
                <option value=\"NM\">New Mexico</option>
                <option value=\"NY\">New York</option>
                <option value=\"NC\">North Carolina</option>
                <option value=\"ND\">North Dakota</option>
                <option value=\"OH\">Ohio</option>
                <option value=\"OK\">Oklahoma</option>
                <option value=\"OR\">Oregon</option>
                <option value=\"PA\">Pennsylvania</option>
                <option value=\"RI\">Rhode Island</option>
                <option value=\"SC\">South Carolina</option>
                <option value=\"SD\">South Dakota</option>
                <option value=\"TN\">Tennessee</option>
                <option value=\"TX\">Texas</option>
                <option value=\"UT\">Utah</option>
                <option value=\"VT\">Vermont</option>
                <option value=\"VA\">Virginia</option>
                <option value=\"WA\">Washington</option>
                <option value=\"WV\">West Virginia</option>
                <option value=\"WI\">Wisconsin</option>
                <option value=\"WY\">Wyoming</option>
            </select>
        ";
    }

    public function getStatesArray() {
        return array(
            'AL'=>'Alabama',
            'AK'=>'Alaska',
            'AZ'=>'Arizona',
            'AR'=>'Arkansas',
            'CA'=>'California',
            'CO'=>'Colorado',
            'CT'=>'Connecticut',
            'DE'=>'Delaware',
            'DC'=>'District of Columbia',
            'FL'=>'Florida',
            'GA'=>'Georgia',
            'HI'=>'Hawaii',
            'ID'=>'Idaho',
            'IL'=>'Illinois',
            'IN'=>'Indiana',
            'IA'=>'Iowa',
            'KS'=>'Kansas',
            'KY'=>'Kentucky',
            'LA'=>'Louisiana',
            'ME'=>'Maine',
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
            'OH'=>'Ohio',
            'OK'=>'Oklahoma',
            'OR'=>'Oregon',
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
    }


    public function __get($field) {
        $value = parent::__get($field);
        if ($value == null || empty($value)) {
            if ($field == "PageTitle") {
                $value = $this->PageTitle;
            }
        }
        return $value;
    }
} 