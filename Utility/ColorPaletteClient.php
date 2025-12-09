<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 4/21/15
 * Time: 12:31 PM
 */

require_once 'LabstaffPageClient.php';

class ColorPaletteClient extends LabStaffPageClient {

    private $HexPalettes;
    private $RGBPalettes;

    private $HexPalette;
    private $RGBPalette;

    public function __construct(array $data = null) {

        parent::__construct($data);

        $this->addScript("/js/colors.js");
        $this->addScript("/js/jqColorPicker.js");
        $this->addScript("js/script.js");

        $paletteIndex = 0;
        if ($data != null) {
            if (array_key_exists("PaletteIndex", $data)) {
                $paletteIndex = $data['PaletteIndex'];
            }
        }

        $this->HexPalettes = array (
            array("091D42", "396A92", "9AB4CB", "FDFDFD", "ECEDF2"),
            array("336699", "0000FF", "C6DEF7", "FFFFFF", "808080"),
            array("89CEDE", "DC4E00", "929487", "F4F5ED", "C7C9BE")
        );

        $this->RGBPalettes = array (
            array("9, 29, 66", "57, 106, 146", "154, 180, 203", "253, 253, 253", "236, 237, 242"),
            array("51, 102, 153", "0, 0, 255", "198, 222, 247", "128, 128, 128", "255, 255, 255"),
            array("137, 206, 222", "220, 78, 0", "146, 148, 135", "244, 245, 237", "199, 201, 190")
        );

        $this->HexPalette = $this->HexPalettes[$paletteIndex];
        $this->RGBPalette = $this->RGBPalettes[$paletteIndex];
    }

    public function setStylesheets() {
        $this->setLoginStylesheet();
        $this->setMainStylesheet();
        $this->setOrderEntryStyles();
    }

    public function printPage(array $settings = null) {
        $html = "
        <main>
        <div class=\"container\">
            <div class=\"row\">
                <div class=\"col s12\">
                    <div class=\"section\">
                        <p class=\"caption\">Color Theme Picker</p>

                    </div>
                </div>
                <div class=\"col s12\">
                    <input class=\"color\" />
                </div>
            </div>
        </div>
        </main>
        ";
        echo $html;
    }

    private function setLoginStylesheet() {
        $loginStyles = "
        /*****
        Background color/gradient
        *****/
        body {
            background: -moz-linear-gradient(-45deg,  rgba(" . $this->RGBPalette[0] . ",0.75) 0%, rgba(" . $this->RGBPalette[0] . ",0.75) 10%, rgba(" . $this->RGBPalette[1] . ",0.75) 30%, rgba(" . $this->RGBPalette[2] . ",0.75) 40%, rgba(" . $this->RGBPalette[4] . ",0.75) 50%, rgba(" . $this->RGBPalette[2] . ",0.75) 60%, rgba(" . $this->RGBPalette[1] . ",0.75) 70%, rgba(" . $this->RGBPalette[0] . ",0.75) 90%, rgba(" . $this->RGBPalette[0] . ",0.75) 100%);
            background: -webkit-gradient(linear, left top, right bottom, color-stop(0%,rgba(" . $this->RGBPalette[0] . ",0.75)), color-stop(10%,rgba(" . $this->RGBPalette[0] . ",0.75)), color-stop(30%,rgba(" . $this->RGBPalette[1] . ",0.75)), color-stop(40%,rgba(" . $this->RGBPalette[2] . ",0.75)), color-stop(50%,rgba(" . $this->RGBPalette[4] . ",0.75)), color-stop(60%,rgba(" . $this->RGBPalette[2] . ",0.75)), color-stop(70%,rgba(" . $this->RGBPalette[1] . ",0.75)), color-stop(90%,
                rgba(" . $this->RGBPalette[0] . ",0.75)), color-stop(100%,rgba(" . $this->RGBPalette[0] . ",0.75)));
            background: -webkit-linear-gradient(-45deg,  rgba(" . $this->RGBPalette[0] . ",0.75) 0%,rgba(" . $this->RGBPalette[0] . ",0.75) 10%,rgba(" . $this->RGBPalette[1] . ",0.75) 30%,rgba(" . $this->RGBPalette[2] . ",0.75) 40%,rgba(" . $this->RGBPalette[4] . ",0.75) 50%,rgba(" . $this->RGBPalette[2] . ",0.75) 60%,rgba(" . $this->RGBPalette[1] . ",0.75) 70%,rgba(" . $this->RGBPalette[0] . ",0.75) 90%,rgba(" . $this->RGBPalette[0] . ",0.75) 100%);
            background: -o-linear-gradient(-45deg,  rgba(" . $this->RGBPalette[0] . ",0.75) 0%,rgba(" . $this->RGBPalette[0] . ",0.75) 10%,rgba(" . $this->RGBPalette[1] . ",0.75) 30%,rgba(" . $this->RGBPalette[2] . ",0.75) 40%,rgba(" . $this->RGBPalette[4] . ",0.75) 50%,rgba(" . $this->RGBPalette[2] . ",0.75) 60%,rgba(" . $this->RGBPalette[1] . ",0.75) 70%,rgba(" . $this->RGBPalette[0] . ",0.75) 90%,rgba(" . $this->RGBPalette[0] . ",0.75) 100%);
            background: -ms-linear-gradient(-45deg,  rgba(" . $this->RGBPalette[0] . ",0.75) 0%,rgba(" . $this->RGBPalette[0] . ",0.75) 10%,rgba(" . $this->RGBPalette[1] . ",0.75) 30%,rgba(" . $this->RGBPalette[2] . ",0.75) 40%,rgba(" . $this->RGBPalette[4] . ",0.75) 50%,rgba(" . $this->RGBPalette[2] . ",0.75) 60%,rgba(" . $this->RGBPalette[1] . ",0.75) 70%,rgba(" . $this->RGBPalette[0] . ",0.75) 90%,rgba(" . $this->RGBPalette[0] . ",0.75) 100%);
            background: linear-gradient(135deg,  rgba(" . $this->RGBPalette[0] . ",0.75) 0%,rgba(" . $this->RGBPalette[0] . ",0.75) 10%,rgba(" . $this->RGBPalette[1] . ",0.75) 30%,rgba(" . $this->RGBPalette[2] . ",0.75) 40%,rgba(" . $this->RGBPalette[4] . ",0.75) 50%,rgba(" . $this->RGBPalette[2] . ",0.75) 60%,rgba(" . $this->RGBPalette[1] . ",0.75) 70%,rgba(" . $this->RGBPalette[0] . ",0.75) 90%,rgba(" . $this->RGBPalette[0] . ",0.75) 100%);
            filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#bf" . $this->HexPalette[0] . "', endColorstr='#bf" . $this->HexPalette[0] . "',GradientType=1 );
        }

        /*****
        Text color
        *****/
        h4, p, input, label, i { color: #FFFFFF !important;}
        /* http://stackoverflow.com/a/2610741 */
        ::-webkit-input-placeholder { color: #FFFFFF; opacity: 1;}
        :-moz-placeholder { color: #FFFFFF; opacity: 1; }
        ::-moz-placeholder { color: #FFFFFF; opacity: 1; }
        :-ms-input-placeholder { color: #FFFFFF; opacity: 1; }

        /*****
        Form & button background color
        *****/
        #frmLogin, #signin {
            background-color: rgba(" . $this->RGBPalette[0] . ", 0.4);
        }

        /*****
        Form border color
        *****/
        #frmLogin {
            border-color: #000000;
        }

        /*****
        Input border bottom color, box-shadow, and text color
        *****/
        input[type=text], input[type=password]{
            border-bottom-color: #" . $this->HexPalette[2] . ";
        }
        /* line 6043 */
        input[type=text]:focus:not([readonly]), input[type=password]:focus:not([readonly]), input[type=email]:focus:not([readonly]), input[type=url]:focus:not([readonly]), input[type=time]:focus:not([readonly]), input[type=date]:focus:not([readonly]), input[type=datetime-local]:focus:not([readonly]), input[type=tel]:focus:not([readonly]), input[type=number]:focus:not([readonly]), input[type=search]:focus:not([readonly]), textarea.materialize-textarea:focus:not([readonly]) {
            border-bottom-color: #" . $this->HexPalette[3] . ";
            box-shadow: 0 1px 0 0 #" . $this->HexPalette[3] . "; }
        input[type=text]:focus:not([readonly]) + label, input[type=password]:focus:not([readonly]) + label, input[type=email]:focus:not([readonly]) + label, input[type=url]:focus:not([readonly]) + label, input[type=time]:focus:not([readonly]) + label, input[type=date]:focus:not([readonly]) + label, input[type=datetime-local]:focus:not([readonly]) + label, input[type=tel]:focus:not([readonly]) + label, input[type=number]:focus:not([readonly]) + label, input[type=search]:focus:not([readonly]) + label, textarea.materialize-textarea:focus:not([readonly]) + label {
            color: #" . $this->HexPalette[3] . "; }


        /*****
        Input field colors
        *****/
        .input-field {
            background-color: rgba(" . $this->RGBPalette[2] . ", 0.2);
            border-color: #" . $this->HexPalette[2] . ";
        }

        /*****
        Error message text color
        *****/
        #errMsg {
            color: red !important;
        }
        ";

        $loginFile = fopen("../css/palette-login.css", "w");
        fwrite($loginFile, $loginStyles);
        fclose($loginFile);
    }

    private function setMainStylesheet() {
        $mainStyles = "
        body {
            background: rgb(255,255,255); /* Old browsers */
            background: -moz-linear-gradient(135deg,  rgba(255,255,255,1) 75%, rgba(" . $this->RGBPalette[3] . ",1) 100%); /* FF3.6+ */
            background: -webkit-gradient(linear, right bottom, left top, color-stop(75%,rgba(255,255,255,1)), color-stop(100%,rgba(" . $this->RGBPalette[3] . ",1))); /* Chrome,Safari4+ */
            background: -webkit-linear-gradient(135deg,  rgba(255,255,255,1) 75%,rgba(" . $this->RGBPalette[3] . ",1) 100%); /* Chrome10+,Safari5.1+ */
            background: -o-linear-gradient(135deg,  rgba(255,255,255,1) 75%,rgba(" . $this->RGBPalette[3] . ",1) 100%); /* Opera 11.10+ */
            background: -ms-linear-gradient(135deg,  rgba(255,255,255,1) 75%,rgba(" . $this->RGBPalette[3] . ",1) 100%); /* IE10+ */
            background: linear-gradient(135deg,  rgba(255,255,255,1) 75%,rgba(" . $this->RGBPalette[3] . ",1) 100%); /* W3C */
            filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#" . $this->HexPalette[3] . "', endColorstr='#" . $this->HexPalette[3] . "',GradientType=1 ); /* IE6-9 fallback on horizontal gradient */
        }

        /*i { color: #FFFFFF; }*/

        nav a {
            color: #" . $this->HexPalette[3] . " !important;
        }

        .nav-wrapper, nav {
            background: #" . $this->HexPalette[1] . ";
        }

        #toggle {
            color: #" . $this->HexPalette[3] . ";
        }

        .top-nav {
            border-color: #" . $this->HexPalette[0] . ";
        }

        .side-nav {
            border-right-color: #" . $this->HexPalette[0] . ";
            /*background-color: rgba(255,255,255,0.1);*/
        }

        .side-nav li {
            border-bottom-color: #" . $this->HexPalette[4] . ";
        }

        /*
        .side-nav li:hover {
            background: #" . $this->HexPalette[1] . ";
            color: #" . $this->HexPalette[3] . " !important;
        }
        .side-nav li:hover a {
            color: #" . $this->HexPalette[3] . " !important;
        }


        /*array('091D42', '396A92', '9AB4CB', 'FDFDFD', 'ECEDF2')*/

        .side-nav li:hover a, .collapsible-body li.active, .collapsible-header.active, .btn-large {
            background: #" . $this->HexPalette[1] . " !important;
            color: #" . $this->HexPalette[3] . " !important;
        }
        .side-nav li:hover .collapsible-body li a, .collapsible-body ul li a, .collapsible-header {
            background: #" . $this->HexPalette[3] . " !important;
            color: #" . $this->HexPalette[0] . " !important;
        }
        .side-nav li:active a {
            background: #" . $this->HexPalette[3] . " !important;
            color: #" . $this->HexPalette[0] . " !important;
        }
        .side-nav li ul {
            background: #" . $this->HexPalette[1] . " !important;
        }
        .side-nav .collapsible-body li {
            border-bottom: none;
        }
        .side-nav .collapsible-body li a {
            margin-right: 0 !important;
        }
        /*.collapsible-accordion  li:active {
            background: #" . $this->HexPalette[1] . " !important;
        }*/
        /*.side-nav li:hover a > .collapsible-body, .collapsible-body li.active {
            color: #" . $this->HexPalette[3] . " !important;
        }*/




        .logo:hover {
            background: #" . $this->HexPalette[3] . " !important;
        }

        footer {
            border-top-color: #" . $this->HexPalette[0] . ";
            background: #" . $this->HexPalette[1] . ";
            color: #" . $this->HexPalette[3] . ";
        }

        .waves-effect.waves-palette .waves-ripple {
            background-color: rgba(" . $this->RGBPalette[2] . ", 0.4);
        }


        #liHomeStyle, #liOrderEntryStyle, #liEnquiryStyle, #liReportsStyle, #liPrefsStyle {
            background: #" . $this->HexPalette[1] . ";
            border-top: 1px solid #" . $this->HexPalette[0] . ";
            border-bottom: 1px solid #" . $this->HexPalette[0] . ";
        }
        #aHomeStyle, #aOrderEntryStyle, #aEnquiryStyle, #aReportsStyle, #aPrefsStyle, #aAddColorStyle, #aEditColorStyle {
            color: #" . $this->HexPalette[3] . " !important;
        }
        #liLogoBorderBottom, #liHomeBorder, #liOrderEntryBorder, #liEnquiryBorder, #liReportsBorder, #liPrefsBorder {
            border-bottom: none;
        }

                /* ----------------- Screen lock -------- */
        #screen_lock {
            background: #" . $this->HexPalette[3] . ";
            border-color: #" . $this->HexPalette[0] . ";
        }

        #screen_lock h4, #screen_lock h5 {
            color: #" . $this->HexPalette[0] . " !important;
        }
        #cancel_logout {
            background: #" . $this->HexPalette[2] . ";
            color: #" . $this->HexPalette[3] . ";
            border-color: #" . $this->HexPalette[0] . ";
        }


        .collection .collection-item.active {
            background-color: #" . $this->HexPalette[1] . ";
            color: #" . $this->HexPalette[3] . ";
        }

        [type=\"radio\"].with-gap:checked+label:before {
            border-radius:50%;
            border:2px solid #" . $this->HexPalette[1] . ";
        }

        [type=\"checkbox\"]:checked+label:before {
            border-right:2px solid #" . $this->HexPalette[1] . ";
            border-bottom:2px solid #" . $this->HexPalette[1] . ";
        }

        [type=\"checkbox\"]:indeterminate+label:before {
            border-right:2px solid #" . $this->HexPalette[1] . ";
        }

        input[type=text]:focus:not([readonly]),
        input[type=password]:focus:not([readonly]),
        input[type=email]:focus:not([readonly]),
        input[type=url]:focus:not([readonly]),
        input[type=time]:focus:not([readonly]),
        input[type=date]:focus:not([readonly]),
        input[type=datetime-local]:focus:not([readonly]),
        input[type=tel]:focus:not([readonly]),
        input[type=number]:focus:not([readonly]),
        input[type=search]:focus:not([readonly]),
        textarea.materialize-textarea:focus:not([readonly]) {
            border-bottom:1px solid #" . $this->HexPalette[1] . ";
            box-shadow:0 1px 0 0 #" . $this->HexPalette[1] . "
        }

        input[type=text]:focus:not([readonly])+label,
        input[type=password]:focus:not([readonly])+label,
        input[type=email]:focus:not([readonly])+label,
        input[type=url]:focus:not([readonly])+label,
        input[type=time]:focus:not([readonly])+label,
        input[type=date]:focus:not([readonly])+label,
        input[type=datetime-local]:focus:not([readonly])+label,
        input[type=tel]:focus:not([readonly])+label,
        input[type=number]:focus:not([readonly])+label,
        input[type=search]:focus:not([readonly])+label,
        textarea.materialize-textarea:focus:not([readonly])+label,
        .secondary-content,
        .dropdown-content li>span,
        .input-field .prefix.active,
        input[type=range]+.thumb .value,
        .picker__day.picker__day--today,
        .picker__close,
        .picker__today {
            color:#" . $this->HexPalette[1] . ";
        }

        [type=\"checkbox\"].filled-in:checked+label:after,
        [type=\"radio\"]:checked+label:after,
        [type=\"radio\"].with-gap:checked+label:after  {
            border:2px solid #" . $this->HexPalette[1] . ";
            background-color:#" . $this->HexPalette[1] . ";
        }

        input[type=range]::-webkit-slider-thumb,
        input[type=range]::-moz-range-thumb,
        input[type=range]::-ms-thumb {
            background-color:#" . $this->HexPalette[1] . ";
        }

        .picker__date-display,
        span.badge.new,
        .progress .determinate,
        .switch label input[type=checkbox]:checked+.lever:after,
        .picker__day--selected,
        .picker__day--selected:hover,
        .picker--focused .picker__day--selected {
            background-color:#" . $this->HexPalette[1] . ";
        }

        .picker__nav--prev:hover,
        .picker__nav--next:hover,
        button:focus,
        button.picker__today:focus,
        button.picker__clear:focus,
        button.picker__close:focus {
            color:#000000;
            background:#" . $this->HexPalette[2] . ";

        .pagination li.active {
            background-color: #" . $this->HexPalette[1] . ";
        }


        .Zebra_DatePicker .dp_daypicker th              { background: #" . $this->HexPalette[1] . " !important; }
        .Zebra_DatePicker td.dp_week_number             {
            background: #" . $this->HexPalette[1] . " !important;
            color: #" . $this->HexPalette[4] . ";
            cursor: text;
            font-style: italic
        }




        .spinner-blue, .spinner-blue-only {
            border-color: #" . $this->HexPalette[1] . ";
        }

        #amountPerPage {
            border: 1px solid #" . $this->HexPalette[2] . " !important;
        }
        #amountPerPage:active, #amountPerPage:focus {
            border: 1px solid #" . $this->HexPalette[1] . " !important;
        }

        #modal {
            border-top-color: #" . $this->HexPalette[1] . ";
        }

        .tooltipped {
            color: #" . $this->HexPalette[2] . ";
        }

        ";

        if  ($this->PageTitle == "Home") {
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
        }

        $mainFile = fopen("../css/palette-main.css", "w");
        fwrite($mainFile, $mainStyles);
        fclose($mainFile);
    }


    private function setOrderEntryStyles() {
        $styles = "
           /* #orderLookUp i, #generateAccession i, #lnkPatientSearch i { */
           .mdi-action-settings {
                color: #" . $this->HexPalette[1] . ";
            }

            blockquote {
                border-left: 5px solid #" . $this->HexPalette[1] . ";
            }
            .tabs .tab a {
                color:#" . $this->HexPalette[1] . ";
            }
            .tabs .tab a:hover {
                color:#" . $this->HexPalette[0] . ";
            }
            .tabs .indicator {
                background-color:#" . $this->HexPalette[1] . ";
            }
        ";

        $mainFile = fopen("../orderentry/css/palette.css", "w");
        fwrite($mainFile, $styles);
        fclose($mainFile);

    }


} 