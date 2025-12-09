<?php
if (!isset($_SESSION)) {
    session_start();
}
//require_once 'DAOS/ResultLogDAO.php';
require_once 'DAOS/JasperDAO.php';
require_once 'DAOS/OrderDAO.php';
require_once 'IConfig.php';

/**
 * The purpose of this class is to assist with providing data used directly on the view.php page
 *
 * @author Edd
 */
class ViewClient implements IConfig {
    private $Data = array (
        "ViewOrderIds" => array(),
        "AllOrderIds" => array(),
        "AllOrdersCount" => "",
        "FirstOrderId" => "",
        "FirstReportType" => "",
        "LastOrderId" => "",
        "LastReportType" => "",
        "PrevOrderId" => "",
        "PrevReportType" => "",
        "NextOrderId" => "",
        "NextReportType" => "",
        "CurrOrderId" => "",
        "CurrReportType" => "",
        "CurrOrderIdIndex" => "",
        "OrderIsFound" => false,
        "IdPatients" => "",
        "SpecimenDate" => "",
        "CanViewCumulative" => false
    );

    private $ViewReportURL;
    private $DownloadReportURL;
    private $ViewMultiple = false;


    private $ViewType = ""; // 1 = single, 2 = selected, 3 = all

    private $HasMultipleReportTypes = false;
    private $MultipleReportTypesMessage = "";

    private $DefaultReportType;

    public $SiteUrl = "";

    public function __construct($defaultReportType, mysqli $conn = null) {

        if ((isset($_SERVER['SSL_TLS_SNI']) && !empty($_SERVER['SSL_TLS_SNI']) && $_SERVER['SSL_TLS_SNI'] === 'cardiopathoutreach.com')
            || (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'cardiopathoutreach.com')) {
            $this->SiteUrl = "https://cardiopathoutreach.com/outreach/";
        } else if ((isset($_SERVER['SSL_TLS_SNI']) && !empty($_SERVER['SSL_TLS_SNI']) && $_SERVER['SSL_TLS_SNI'] === 'cardiotropicoutreach.com')
            || (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'cardiotropicoutreach.com')) {
            $this->SiteUrl = "https://cardiotropicoutreach.com/outreach/";
        } else {
            $this->SiteUrl = self::SITE_URL;
        }

        if (!isset($_SESSION['AllIdsOrdered']) || !isset($_SESSION['AllIdsGrouped']) || !isset($_SESSION['PageIdsGrouped'])) { // make sure the list of all orderIds is stored in session
            header("Location: search.php");
        }
        $this->DefaultReportType = $defaultReportType;

        if (isset($_SESSION['ViewType'])) {
            $this->ViewType = $_SESSION['ViewType'];
        } else if (isset($_POST['viewType'])) {
            $this->ViewType = $_POST['viewType'];
            $_SESSION['ViewType'] = $_POST['viewType'];
        } else {
            $this->ViewType = 1;
            $_SESSION['ViewType'] = 1;
        }

        if ($this->ViewType != 1) {
            $this->ViewMultiple = true;

            if ($this->ViewType == 2) {
                $_SESSION['ViewMultiple'] = true;
            }

            $this->setViewMultipleReportIds();
        } else {
            $this->setViewSingleReportIds();
        }

        if (isset($_POST['reportType'])) {
            $_SESSION['reportType'] = $_POST['reportType'];
        }


        /*if ((isset($_SESSION['ViewType']) && $_SESSION['ViewType'] == 2) ||
            (isset($_POST['viewType']) && $_POST['viewType'] == 2 && isset($_POST['view']) && is_array($_POST['view']) && count($_POST['view']) > 0)) { // ----- View Selected
            $this->ViewType = 2;
            $_SESSION['ViewType'] = 2;
            $this->ViewMultiple = true;
            $_SESSION['ViewMultiple'] = true;
            $this->setViewMultipleReportIds();
        } else if ((isset($_SESSION['ViewType']) && $_SESSION['ViewType'] == 3) || (isset($_POST['viewType']) && $_POST['viewType'] == 3)) { // ---------------------- View All
            $this->ViewType = 3;
            $_SESSION['ViewType'] = 3;
            $this->ViewMultiple = true;
            $this->setViewMultipleReportIds();
        } else if ((isset($_SESSION['ViewType']) && $_SESSION['ViewType'] == 4) || (isset($_POST['viewType']) && $_POST['viewType'] == 4)) { // ---------------------- View Current Page
            $this->ViewType = 4;
            $_SESSION['ViewType'] = 4;
            $this->ViewMultiple = true;
            $this->setViewMultipleReportIds();
        } else { // -------------------------------------------------------- View Single Report
            if (isset($_SESSION['AllIdsOrdered']) && isset($_REQUEST['idOrders'])) {
                $this->ViewType = 1;
                $_SESSION['ViewType'] = 1;
                $this->setViewSingleReportIds();
            }
        }*/

        $this->setReportURLs();

//         if ($this->Data['OrderIsFound']) {
//             ResultLogDAO::addLogEntry($_SESSION['id'], 3, array ("orderId" => $this->Data['CurrOrderId']));
//         }

        if (isset($_REQUEST['idPatients']) && !empty($_REQUEST['idPatients'])) {
            $this->Data['IdPatients'] = $_REQUEST['idPatients'];

            //$this->Data['OrderDate'] = $_REQUEST['orderDate'];
            if (isset($this->Data['CurrOrderId']) && !empty($this->Data['CurrOrderId'])) {

                if ($conn != null) {
                    $this->Data['SpecimenDate'] = OrderDAO::getSpecimenDateById($this->Data['CurrOrderId'], $conn);
                } else {
                    $this->Data['SpecimenDate'] = OrderDAO::getSpecimenDateById($this->Data['CurrOrderId']);
                }
            }

            $this->Data['CanViewCumulative'] = true; // ---------------- check the user's "Has Cumulative" setting here
        }
        //$this->setJasperReport();
    }

    public function isValidUser(array $userIds, mysqli $conn, $idOrders) {
        $data = OrderDAO::getUserIdByOrderId($userIds, $conn, $idOrders);

        if (count($data) == 0) {
            return false;
        }
        return true;
    }

    private function setViewMultipleReportIds() {
        if ($this->ViewType == 3) { // ------ Veiw All
            //$this->Data['ViewOrderIds'] = $_SESSION['idOrdersList'];
            $this->Data['ViewOrderIds'] = $_SESSION['AllIdsGrouped'];
        } else if ($this->ViewType == 4) { // --------- View Current Page
            $this->Data['ViewOrderIds'] = $_SESSION['PageIdsGrouped'];
        } else if ($this->ViewType == 2) { // ----------------------- View Selected
            $arySelectedIds = array();
            if (array_key_exists("view", $_POST) && is_array($_POST['view']) && array_key_exists(0, $_POST['view'])) {
                if (count($_POST['view']) == 1) {
                    $arySelectedIds[$this->DefaultReportType] = $_POST['view'][0];
                } else {
                    if (array_key_exists($this->DefaultReportType, $_POST['view'])) {
                        foreach ($_POST['view'] as $reportType => $aryOrderIds) {
                            if ($reportType == $this->DefaultReportType) {
                                $arySelectedIds[$reportType] = array_merge($_POST['view'][0], $aryOrderIds);
                            } else if ($reportType != 0) {
                                $arySelectedIds[$reportType] = $aryOrderIds;
                            }
                        }
                    } else {
                        foreach ($_POST['view'] as $reportType => $aryOrderIds) {
                            if ($reportType != 0) {
                                $arySelectedIds[$reportType] = $aryOrderIds;
                            }
                        }
                        $arySelectedIds[$this->DefaultReportType] = $_POST['view'][0];
                    }
                }
                $this->Data['ViewOrderIds'] = $arySelectedIds;
            } elseif (array_key_exists("view", $_POST)) {
                $this->Data['ViewOrderIds'] = $_POST['view'];
            }
        }
    }

    private function setViewSingleReportIds() {
        $this->Data['AllOrderIds'] = $_SESSION['AllIdsOrdered'];
        $this->Data['AllOrdersCount'] = count($_SESSION['AllIdsOrdered']);
        $this->Data['CurrOrderId'] = $_REQUEST['idOrders'];
        //if (!isset($_GET['reportType']) || empty($_GET['reportType'])) {
        if ((!isset($_GET['reportType']) && $_GET['reportType'] != 0) || (empty($_GET['reportType']) && $_GET['reportType'] != 0)) {
            $this->Data['CurrReportType'] = $this->DefaultReportType;
        } else {
            $this->Data['CurrReportType'] = $_GET['reportType'];

        }

        $i = 0;
        $setNextOrderId = false;
        $prevOrderId = null;

        if (!is_array($this->Data['AllOrderIds'])) {
            $allOrderIds = "";
            if (isset($this->Data['AllOrderIds'])) {
                $allOrderIds = $this->Data['AllOrderIds'];
            }
            $userId = "";
            if (isset($_SESSION['id'])) {
                $userId = $_SESSION['id'];
            }
            $adminId = "";
            if (isset($_SESSION['AdminId'])) {
                $adminId = $_SESSION['AdminId'];
            }

            error_log("ViewClient->setViewSingleReportIds->Data['AllOrderIds'] is not an array: AllOrderIds: " . $allOrderIds . ", UserId: " . $userId . ", AdminId: " . $adminId);
        }

        foreach ($this->Data['AllOrderIds'] as $orderId => $reportType) {

            $useDefaultReportType = false;
            if (empty($reportType)) {
                $useDefaultReportType = true;

            }

            if ($i == 0) { // --------------------------------------------- set the first order id
                $this->Data['FirstOrderId'] = $orderId;
                if (array_key_exists("reportType", $_GET) && $_GET['reportType'] == 0) {
                    $this->Data['FirstReportType'] = 0;
                } else if ($useDefaultReportType) {
                    $this->Data['FirstReportType'] = $this->DefaultReportType;
                } else {
                    $this->Data['FirstReportType'] = $reportType;
                }

            } else if ($i == $this->Data['AllOrdersCount'] - 1) { // ------ set the last order id
                $this->Data['LastOrderId'] = $orderId;
                if (array_key_exists("reportType", $_GET) && $_GET['reportType'] == 0) {
                    $this->Data['LastReportType'] = 0;
                } else if ($useDefaultReportType) {
                    $this->Data['LastReportType'] = $this->DefaultReportType;
                } else {
                    $this->Data['LastReportType'] = $reportType;
                }
            }

            if ($setNextOrderId) { // the flag was triggered on the previous iteration, so set the next order id as the current one
                $this->Data['NextOrderId'] = $orderId;
                if (array_key_exists("reportType", $_GET) && $_GET['reportType'] == 0) {
                    $this->Data['NextReportType'] = 0;
                } else if ($useDefaultReportType) {
                    $this->Data['NextReportType'] = $this->DefaultReportType;
                } else {
                    $this->Data['NextReportType'] = $reportType;
                }
                $setNextOrderId = false;
            }

            if ($orderId == $this->Data['CurrOrderId']) {
                $this->Data['CurrOrderIdIndex'] = $i; // -------------- set the current order's index
                if ($i != 0) { // --------------------------------------------- set the previous order id
                    $this->Data['PrevOrderId'] = $prevOrderId;
                    if (array_key_exists("reportType", $_GET) && $_GET['reportType'] == 0) {
                        $this->Data['PrevReportType'] = 0;
                    } else if ($useDefaultReportType) {
                        $this->Data['PrevReportType'] = $this->DefaultReportType;
                    } else {
                        $this->Data['PrevReportType'] = $reportType;
                    }
                }
                if ($i != $this->Data['AllOrdersCount'] - 1) { // trigger the flag to set the next order id on the next iteration
                    $setNextOrderId = true;
                }
                $this->Data['OrderIsFound'] = true;
            }

            $prevOrderId = $orderId;

            if ($useDefaultReportType) {
                $prevReportType = $this->DefaultReportType;
            } else {
                $prevReportType = $reportType;
            }
            $i++;
        }
    }

    private function setReportURLs() {
        if ($this->ViewMultiple) {
            $_SESSION['ViewOrderIds'] = $this->Data['ViewOrderIds'];
            $_SESSION['ViewMultiple'] = true; //$this->ViewMultiple;
            $this->ViewReportURL = $this->SiteUrl . "viewb.php?&reportType=" . $this->Data['CurrReportType'];
            $this->DownloadReportURL = $this->SiteUrl . "downloadreport.php?reportType=" . $this->Data['CurrReportType'];
        } else {
            if (isset($_REQUEST['idOrders']) && isset($_REQUEST['reportType'])) {
                $_SESSION['ViewMultiple'] = false; // $this->ViewMultiple
                $this->ViewReportURL = $this->SiteUrl . "viewb.php?&idOrders=" . $_REQUEST['idOrders'] . "&reportType=" . $_REQUEST['reportType']; //$this->Data['CurrOrderId'];
                $this->DownloadReportURL = $this->SiteUrl . "downloadreport.php?idOrders=" . $_REQUEST['idOrders'] . "&reportType=" . $_REQUEST['reportType']; //$this->Data['CurrOrderId'];
            }
        }
    }

    public function __get($key) {
        if (array_key_exists($key, $this->Data)) {
            return $this->Data[$key];
        } else if ($key == "ViewMultiple") {
            return $this->ViewMultiple;
        } else if ($key == "ViewReportURL") {
            return $this->ViewReportURL;
        } else if ($key == "DownloadReportURL") {
            return $this->DownloadReportURL;
        } else if ($key == "ViewType") {
            return $this->ViewType;
        } else if ($key == "HasMultipleReportTypes") {
            return $this->HasMultipleReportTypes;
        } else if ($key == "MultipleReportTypesMessage") {
            return $this->MultipleReportTypesMessage;
        }
    }


    //http://stackoverflow.com/a/9262137
    function decrypt($field) {
        $salt = "23easkldjk2";
        $decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($salt), base64_decode($field), MCRYPT_MODE_CBC, md5(md5($salt))), "\0");
        return $decrypted;
    }
}
?>
