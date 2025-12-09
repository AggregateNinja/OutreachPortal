<?php
require_once 'DOS/BaseObject.php';
//require_once 'DAOS/EntryOrderDAO.php';
require_once 'DAOS/PendingOrdersDAO.php';
require_once 'Utility/ItemSorter.php';
require_once 'PageClient.php';
require_once 'DOS/PendingSearchFactory.php';
require_once 'IClient.php';

class PendingOrdersClient extends PageClient implements IClient {

    private $OrderCollection;

	public function __construct(array $data = null) {
        //$this->startPageLoadTimer();

        if ($data != null) {
            $data['IncludeDetailedInfo'] = true;
            $data['IncludeUserSettings'] = true;
        } else {
            $data = array("IncludeDetailedInfo" => true, "IncludeUserSettings" => true);
        }

        parent::__construct($data);

        $this->addStylesheet("css/pending.css");
        $this->addStylesheet("/outreach/css/pagination.css");
        $this->addStylesheet("/outreach/css/menu-slider.css");
        /*$this->addStylesheet("/outreach/css/dropdown.css");*/

        /*$this->addScript("/outreach/js/dropdown.min.js");*/
        $this->addScript("js/pending.js");
        $this->addScript("/outreach/js/velocity.min.js");
        $this->addScript("/outreach/js/tooltip.js");

        $factory = new PendingSearchFactory();

        if ($data != null) {
            $data['Conn'] = $this->Conn;
        } else {
            $data = array("Conn" => $this->Conn);
        }

        $typeId = $this->User->typeId;
        if (array_key_exists("AdminType", $_SESSION) && array_key_exists("AdminId", $_SESSION)
            && isset($_SESSION['AdminType']) && isset($_SESSION['AdminId']) && $_SESSION['AdminType'] == 7) {
            //$typeId = 7;

            if ($this->User->typeId == 2) {
                $data['ClientNo'] = $this->User->clientNo;
            } else if ($this->User->typeId == 3) {
                $data['DoctorNo'] = $this->User->number;
            }

        } else {
            if ($this->User->typeId == 2) {
                $data['ClientNo'] = $this->User->clientNo;
            } else if ($this->User->typeId == 3) {
                $data['DoctorNo'] = $this->User->number;
            }
        }

        $data['TypeId'] = $typeId;

        /*if (!array_key_exists("SearchFields", $data) || !array_key_exists("displayReceived", $data['SearchFields'])) {
            $data['SearchFields']['displayReceived'] = 0;
        }*/

        if ($data == null || !array_key_exists("SearchFields", $data)) {
            $data['SearchFields']['displayReceived'] = 0;
            $data['SearchFields']['displayCanceled'] = 0;
        }



        if (count($this->User->RestrictedUserIds) > 0) {
            $data['RestrictedUserIds'] = $this->User->RestrictedUserIds;
        }
        if (isset($this->User->OrderAccessSetting)) {
            $data['OrderAccess'] = $this->User->OrderAccessSetting->idAccessSettings;
        }



        //echo "<pre>"; print_r($data); echo "</pre>";

        $this->OrderCollection = $factory->startFactory($data);

        $labelOrderId = "";
        if (array_key_exists("OrderId", $_SESSION)) {
            $labelOrderId = $_SESSION['OrderId'];
            $_SESSION['OrderId'] = "";
            unset($_SESSION['OrderId']);
        }
        $hideOverlay = 0;
        if (array_key_exists("HideOverlay", $_SESSION)) {
            $hideOverlay = $_SESSION['HideOverlay'];
            $_SESSION['HideOverlay'] = "";
            unset($_SESSION['HideOverlay']);
        }

        $verbiage = "Please print a new requisition to reflect the changes made to this order.";
        if (array_key_exists("isNew", $_GET)) {
            $verbiage = "Would you like to print a requisition?";
        }

        if (self::OrderEntryAutoPrint == true && !empty($labelOrderId)) {
            $this->addOverlay("
                <div id='printReqOverlay' class='rounded'>
                    <input type='hidden' name='reqOrderId' id='reqOrderId' value='$labelOrderId' />
                    <input type='hidden' name='hideOverlay2' id='hideOverlay2' value='$hideOverlay' />
                    <div class='row'>
                        <div class='one mobile whole'>
                            <h5 style='margin-bottom: 0;'>Requisition Print</h5>
                        </div>
                    </div>
                    <div class='row pad-bottom'>
                        <div class='one mobile whole pad-top'>
                            $verbiage
                        </div>
                    </div>
                    <div class='row'>
                        <div class='one mobile whole'>
                            <a href='javascript:void(0)' id='printReq' class='button' title='Print Requisition'>
                                <i class='icon-print'></i> Print</a>
                        </div>
                        
                    </div>
                </div>
            ");
        }

        if (self::HasLabelPrint == true) {
            $this->addOverlay("
                <div id='printLabelsOverlay' class='rounded'>
                    <input type='hidden' name='labelOrderId' id='labelOrderId' value='$labelOrderId' />
                    <input type='hidden' name='hideOverlay' id='hideOverlay' value='$hideOverlay' />
                    <div class='row'>
                        <div class='one mobile whole'>
                            <h5 style='margin-bottom: 0;'>Label Print</h5>
                        </div>
                    </div>
                    <div class='row pad-bottom'>
                        <div class='one mobile whole'>
                            <label for='numLabels'># of Labels:</label>
                            <input type='number' name='numLabels' id='numLabels' value='2' />
                        </div>
                    </div>
                    <div class='row'>
                        <div class='one mobile half pad-right'>
                            <a href='javascript:void(0)' id='printLabels' class='button' title='Print Labels'>
                                <i class='icon-print'></i></a>
                        </div>
                        <div class='one mobile half'>
                            <a href='javascript:void(0)' id='skipLabelPrint' class='button' title='Skip Printing Labels'>
                                <i class='icon-remove-sign'></i></a>
                        </div>
                    </div>
                </div>
            ");
        }
        $this->addOverlay("
            <div id='cancelOrderOverlay' class='rounded'>
                <div class='row'>
                    <div class='one mobile whole'>
                        <h5 style='margin-bottom: 0;'>Are you sure you would like to cancel this order?</h5>
                    </div>
                </div>
                <div class='row pad-top'>
                    <div class='one mobile half pad-right'>
                        <a href='javascript:void(0)' id='cancelYes' class='button' style='padding: 2px 6px 3px 6px; margin-right: 2px; float: right'>Yes</a>
                    </div>
                    <div class='one mobile half'>
                        <a href='javascript:void(0)' id='cancelNo' class='button' style='padding: 2px 6px 3px 6px; margin-right: 2px;'>No</a>
                    </div>
                </div>
            </div>
        ");
	}

	public function __get($field) {
		$value = parent::__get($field);
		if (empty($value)) {
			if ($field == "Data") {
				$value = $this->Data;
			}
		}
		return $value;
	}

    public function printPage() {
        $this->resetSession();
        $totalOrders = 0;
        $title = "title='Sort in ascending order'";
        $direction = "desc";
        if ($this->OrderCollection != null) {
            $totalOrders = $this->OrderCollection->TotalOrders;
            if (!empty($this->OrderCollection->OrderBy)) {
                $orderBy = $this->OrderCollection->OrderBy;
            }
        }

        $errMsg = "";
        if (array_key_exists("MSG", $_SESSION) && !empty($_SESSION['MSG'])) {
            $errMsg .= "<div id='msg'>" . $_SESSION['MSG'] . "</div>";
            $_SESSION['MSG'] = "";
            unset($_SESSION['MSG']);
        }

        $ordersPerPageHtml = $this->getOrdersPerPageHtml();
        $pendingOrdersHtml = $this->getPendingOrdersHtml();

        $specimenDateColHeader = self::SpecimenDateColHeader;
        $orderDateColHeader = self::OrderDateColHeaderText;

        $html = "
            <div class='transition-container'>
                <div class='slide-container' id='pending'>
                    <button class='close-button' id='close-button'>X</button>
                    <div class='menu-wrap box_shadow'>
                        <h3>Filter Pending Orders</h3>
                        <nav class='menu rounded'>
                            <div class='filter-list'>
                                <fieldset>
                                    <label for='txtAccession'>Accession</label>
                                    <input type='text' name='txtAccession' id='txtAccession' value='' />
                                </fieldset>
                                <fieldset>
                                    <label for='txtPatientName'>Patient Name</label>
                                    <input type='text' name='txtPatientName' id='txtPatientName' value='' />
                                </fieldset>
                                <fieldset>
                                    <label for='txtDoctorName'>Doctor Name</label>
                                    <input type='text' name='txtDoctorName' id='txtDoctorName' value='' />
                                </fieldset>
                                <fieldset>
                                    <label for='txtClientName'>Client Name</label>
                                    <input type='text' name='txtClientName' id='txtClientName' value='' />
                                </fieldset>
                                <fieldset>
                                    <label for='txtInsuranceName'>Insurance Name</label>
                                    <input type='text' name='txtInsuranceName' id='txtInsuranceName' value='' />
                                </fieldset>
                                <fieldset>
                                    <div class='row'>
                                        <label for='orderDateFrom'>$orderDateColHeader</label>
                                        <div class='one mobile half'>
                                            <input class='datepicker' data-tooltip='Date must be in format MM/DD/YYYY' data-position='top' type='text' name='orderDateFrom' id='orderDateFrom' value='' placeholder='From' />
                                        </div>
                                        <div class='one mobile half'>
                                            <input class='datepicker' data-tooltip='Date must be in format MM/DD/YYYY' data-position='top' type='text' name='orderDateTo' id='orderDateTo' value='' placeholder='To' />
                                        </div>
                                    </div>
                                </fieldset>
                                <fieldset style='margin-bottom: 0;'>
                                    <label for='displayReceived'>Display orders received by lab: </label>
                                    <input type='checkbox' name='displayReceived' id='displayReceived' value='1' />
                                    <br/>
                                    <label for='displayCanceled'>Display canceled orders: </label>
                                    <input type='checkbox' name='displayCanceled' id='displayCanceled' value='1' />
                                </fieldset>
                            </div>
                        </nav>
                    </div>
                </div>
                <a href='javascript:void(0)' data-tooltip='Search &amp; filter your pending orders' data-position='right' class='tooltipped menu-button rounded box_shadow button open-button' id='open-button'><i class='icon icon-filter'></i></a>
            </div>

            <div class='container responsive' data-compression='80' id='wrapper' style='margin-top: 15px;'>
                <div class='row'>
                    <div class='one mobile whole padded'>
                        <div class='row'>
                            <div class='one mobile sixth'>&nbsp;</div>
                            <div class='four mobile sixths'>
                                $errMsg
                            </div>
                            <div class='one mobile sixth'>
                                <label for='amountPerPage' style='font-weight: bold;'>Amount Per Page</label>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='two mobile sixths'>
                                <p style='margin:10px 0 0 0;font-weight: bold;'><span id='pendingTotal'>$totalOrders</span> pending orders</p>
                            </div>
                            <div class='two mobile sixths'>
                                <h3 style='text-align: center;margin:0;'>Pending Orders</h3>
                            </div>
                            <div class='one mobile sixth'></div>
                            <div class='one mobile sixth'>
                                $ordersPerPageHtml
                            </div>
                        </div>
                    </div>
                </div>

                <div class='row'>
                    <div class='one mobile whole pad-left pad-right centered'>
                        <div id='loading'>&nbsp;<i class='icon-spinner icon-spin icon-4x'></i></div>
                        <div class='row' style='border-bottom: 1px solid #5a5a5a; font-size: 12px; font-weight: bold;'>
                            <div class='one mobile twelfth menuCol' style='text-align: right;'>
                                <input type='checkbox' name='chkSelectAll' id='chkSelectAll' class='tooltipped' value='1' data-position='right' data-tooltip='Select all' />
                            </div>
                            <div class='one mobile twelfth webAccCol'>Web Acc.
                                <a href='javascript:void(0)' id='accession' class='sort' $title><i class='icon-sort'></i></a>
                            </div>
                            <div class='one mobile twelfth avalonAccCol'>Avalon Acc.
                                <a href='javascript:void(0)' id='avalonAccession' class='sort' $title><i class='icon-sort'></i></a>
                            </div>
                            <div class='one mobile twelfth patientCol'>Patient Name
                                <a href='javascript:void(0)' id='patientFirstName' class='sort' $title><i class='icon-sort'></i></a>
                            </div>
                            <div class='two mobile twelfths doctorCol'>Doctor Name
                                <a href='javascript:void(0)' id='doctorFirstName' class='sort' $title><i class='icon-sort'></i></a>
                            </div>
                            <div class='two mobile twelfths clientCol'>Client Name
                                <a href='javascript:void(0)' id='clientName' class='sort' $title><i class='icon-sort'></i></a>
                            </div>
                            <div class='one mobile twelfth desktop-only'>Insurance
                                <a href='javascript:void(0)' id='insuranceName' class='sort' $title><i class='icon-sort'></i></a>
                            </div>
                            <div class='one mobile twelfth specimenDateCol' style='font-size:10px;'>$specimenDateColHeader
                                <a href='javascript:void(0)' id='specimenDate' class='sort' $title><i class='icon-sort'></i></a>
                            </div>
                            <div class='one mobile twelfth hide-on-small-tablet hide-on-mobile'>$orderDateColHeader
                                <a href='javascript:void(0)' id='orderDate' class='sort' $title><i class='icon-sort'></i></a>
                            </div>
                            <div class='one mobile twelfth desktop-only'>Received Date
                                <a href='javascript:void(0)' id='receiptedDate' class='sort' $title><i class='icon-sort'></i></a>
                            </div>
                        </div>
                        <div id='pendingOrders'>
                            $pendingOrdersHtml
                        </div>
                    </div>
                </div>
            </div>
            <div id='printLoading'>&nbsp;<i class='icon-spinner icon-spin icon-4x'></i></div>
            <div id='frameContainer'><div id='box'></box></div>
            ";

        echo $html;

        //PendingOrdersDAO::clearOrdersBeingEdited($this->User->idUsers, $this->Data['Ip'], $this->Conn);
    }

    private function resetSession() {
        if (array_key_exists("Orders", $_SESSION)) {
            $_SESSION['Orders'] = null;
            unset($_SESSION['Orders']);
        }
        if (array_key_exists("ReqData", $_SESSION)) {
            $_SESSION['ReqData'] = null;
            unset($_SESSION['ReqData']);
        }
        if (array_key_exists("ReqIds", $_SESSION)) {
            $_SESSION['ReqIds'] = null;
            unset($_SESSION['ReqIds']);
        }
    }

    public function getPendingOrdersHtml() {
        $totalOrders = 0;
        $orderBy = "orderDate";
        $direction = "desc";
        if ($this->OrderCollection != null) {
            $orderBy = $this->OrderCollection->OrderBy;
            $direction = $this->OrderCollection->Direction;
            $totalOrders = $this->OrderCollection->TotalOrders;
        }

        $pendingOrdersHtml = "
            <input type='hidden' name='orderBy' id='orderBy' value='$orderBy' />
            <input type='hidden' name='direction' id='direction' value='$direction' />
            <input type='hidden' name='totalOrders' id='totalOrders' value='$totalOrders' />";
        $aryReqData = array();
        if ($this->OrderCollection != null && $totalOrders > 0) {
            $orders = $this->OrderCollection->Orders;
            //echo "<pre>"; print_r($orders); echo "</pre>";
            $start = $this->OrderCollection->Start;
            $end = $this->OrderCollection->End;

            $pendingOrdersHtml .= "<form action='requisition.php' method='post' name='frmPending' id='frmPending'>";

            for($i = $start; $i <= $end; $i++) {
                $order = $orders[$i];

                $title = "";
                $style = "";
                $type = 1;
                $rowStyle = "border-bottom: 1px solid #CCCCCC; padding: 3px 0; font-size: 11px;";
                $isReceipted = $order->IsReceipted;
                $patientName = $order->Patient->firstName . " " . $order->Patient->lastName;
                $isCanceled = $order->WebOrderCanceled;

                $doctorName = "";
                if (isset($order->Doctor)) {
                    $doctorName = $order->Doctor->firstName . " " . $order->Doctor->lastName;
                }

                $clientName = $order->Client->clientName;

                $insuranceName = "";
                if (isset($order->Insurance->name)) {
                    $insuranceName = $order->Insurance->name;
                }

                $idOrders = $order->idOrders;
                $orderDate = date("m/d/Y", strtotime($order->orderDate));
                $specimenDate = date("m/d/Y", strtotime($order->specimenDate));

                $avalonAccession = "";
                if (empty($order->webAccession)) {
                    $webAccession = $order->accession;
                } else {
                    $webAccession = $order->webAccession;
                    $avalonAccession = $order->accession;
                }
                if ($order->isAdvancedOrder && isset($order->Phlebotomy)) {
                    $title = "title='This is an Advanced Order with Phlebotomy.'";
                    $style = "style='font-weight: bold;'";
                    $type = 4;
                } else if ($order->isAdvancedOrder) {
                    $title = "title='This is an Advanced Order.'";
                    $style = "style='font-weight: bold;'";
                    $type = 3;
                } else if (isset($order->Phlebotomy)) {
                    $title = "title='This is a Phlebotomy Order.'";
                    $style = "style='font-weight: bold;'";
                    $type = 2;
                }

                //$reqUrl = "requisition.php?id=$idOrders&type=$type&isReceipted=$isReceipted&isCanceled=$isCanceled";
                //$reqUrl = "requisition.php?id=" . $order->webOrderId . "&type=$type&isReceipted=$isReceipted&isCanceled=$isCanceled";
                if ($isReceipted) {
                    $reqUrl = "requisition.php?id=" . $order->idOrders . "&type=$type&isReceipted=$isReceipted&isCanceled=$isCanceled";
                } else {
                    $reqUrl = "requisition.php?id=" . $order->webOrderId . "&type=$type&isReceipted=$isReceipted&isCanceled=$isCanceled";
                }

                if ($isCanceled) {
                    $lnkIconsHtml = "<a href='$reqUrl' id='$order->webOrderId' class='button lnkViewReq tooltipped' data-tooltip='View Requisition' data-position='top'><i class='icon-book'></i></a>";

                    $rowStyle .= "color: #CCCCCC;font-style: italic; height: 34px;";
                    $title = "title='This order has been canceled'";
                } else {
                    $aryReqData[] = array($idOrders, $type, $isReceipted);

                    $lnkIconsHtml = "
                        <div class='dropdown'>
                            <button class='btn btn-default dropdown-toggle button' type='button' id='menu1' data-toggle='dropdown' title='View, edit, or cancel order'><i class='icon-ellipsis-vertical'></i></button>
                            <ul class='dropdown-menu' role='menu' aria-labelledby='menu1'>
                                <li role='presentation'><a href='$reqUrl' id='$order->webOrderId'>View Requisition</a></li>";

                    if (self::HasLabelPrint == true) {
                        $lnkIconsHtml .= "<li role='separator' class='divider'></li>
                        <li role='presentation'><a href='javascript:void(0)' role='menuitem' tabindex='-1' data-id='$order->idOrders' class='lnkPrintLabel'>Print Labels</a></li>";
                    }

                    if ($isReceipted) {
                        $rowStyle .= "color: #CCCCCC;font-style: italic;";
                        if (empty($title)) {
                            $title = "title='This order was receipted on " . $order->DateReceipted . " at " . $order->TimeReceipted . ".'";
                        } else {
                            $title = substr($title, 0, strlen($title) - 1);
                            $title .= " This order was receipted on " . $order->DateReceipted . " at " . $order->TimeReceipted . ".'";
                        }
                    } else {
                        $dteEditOrderTimeout = new DateTime(date("Y-m-d H:i:s", mktime(date("H"), date("i") - self::EditOrderTimeoutInterval, date("s"), date("m"), date("d"), date("Y"))));


                        if (($order->OrderEditDate == null || empty($order->OrderEditDate)) && $dteEditOrderTimeout->diff(new DateTime($order->OrderEditDate))->invert != 0) {
                            // order is not being edited or the time has expired
                            $editUrl = "add.php?action=edit&id=$order->idOrders&type=$type";
                            $lnkIconsHtml .= "<li role='separator' class='divider'></li>
                            <li role='presentation'><a href='$editUrl' role='menuitem' tabindex='-1'>Edit Order</a></li>";
                        } else {
                            if (isset($order->UserIdEditingOrder) && !empty($order->UserIdEditingOrder) && $order->OrderEditDate != null && !empty($order->OrderEditDate) && $dteEditOrderTimeout->diff(new DateTime($order->OrderEditDate))->invert == 0) {
                                // Someone started editing this order less than 15 minutes ago - Disable editing the order
                                $editUrl = "#";
                                $lnkIconsHtml .= "<li role='separator' class='divider'></li>
                                <li role='presentation'><a href='$editUrl' role='menuitem' tabindex='-1' disabled='disabled' class='tooltipped' data-tooltip='This order is currently being edited by another user' data-position='top'>Edit Order</a></li>";
                            } else {
                                $editUrl = "add.php?action=edit&id=$order->idOrders&type=$type";
                                $lnkIconsHtml .= "<li role='separator' class='divider'></li>
                                <li role='presentation'><a href='$editUrl' role='menuitem' tabindex='-1'>Edit Order</a></li>";
                            }

                            $lnkIconsHtml .= "<li role='separator' class='divider'></li>
                                <li role='presentation'><a href='javascript:void(0)' role='menuitem' tabindex='-1' class='lnkCancel' data-id='$order->idOrders'>Cancel Order</a></li>";
                        }
                    }

                    $lnkIconsHtml .= "
                            </ul>
                        </div>";
                }
                $lnkIconsHtml .= "<input type='checkbox' name='chkOrderIds[]' id='$order->webOrderId' class='chkOrderIds' data-receipted='$isReceipted' value='$order->webOrderId' />";

                $pendingOrdersHtml .= "
                <div class='row pending' style='" . $rowStyle . "'" . $title . ">
                    <div class='one twelfth mobile menuCol'>
                        $lnkIconsHtml
                    </div>
                    <div class='one twelfth mobile webAccCol' $style>$webAccession</div>
                    <div class='one twelfth mobile avalonAccCol' $style>$avalonAccession</div>
                    <div class='one twelfths mobile patientCol' $style>$patientName</div>
                    <div class='two twelfths mobile doctorCol' $style>$doctorName</div>
                    <div class='two twelfths mobile clientCol' $style>$clientName</div>
                    <div class='one twelfth mobile desktop-only' $style>$insuranceName</div>
                    <div class='one twelfth mobile specimenDateCol' $style>$specimenDate</div>
                    <div class='one twelfth mobile hide-on-small-tablet hide-on-mobile' $style>$orderDate</div>
                    <div class='one twelfth mobile desktop-only' $style>" . $order->DateReceipted . "</div>
                </div>";
            }

            $pendingOrdersHtml .= "
                <div class='row' style='margin-top: 10px;'>
                    <div class='one mobile whole' style='text-align: center'>
                        <a href='javascript:void(0)' id='btnViewSelected' class='button green'>View Selected</a>
                        <a href='javascript:void(0)' id='btnViewCurrentPage' class='green button'>View Current Page</a>
                    </div>
                </div>
                </form>
            ";

            $pendingOrdersHtml .= $this->getPaginationHtml();

        } else {
            $pendingOrdersHtml .= "
                <div class='row' style='margin-top: 10px;'>
                    <div class='one whole padded rounded box_shadow'>
                        <h5 style='text-align: center; margin-bottom: 0;'>There are currently no pending orders</h5>
                    </div>
                </div>";
        }

        $_SESSION['ReqData'] = $aryReqData;

        //$this->printPageLoadTime();
        return $pendingOrdersHtml;
    }

    private function getPaginationHtml() {
        $liHtml = "";
        $rowWidth = $this->getRowWidth();
        $currentPage = $this->OrderCollection->CurrentPage;
        $totalPages = $this->OrderCollection->TotalPages;
        for($i = 1; $i <= $totalPages; $i++) {
            $lnkStyle = "";
            if ($i == $currentPage) {
                $lnkStyle = "style='background: #29b765;'";
            }
            if ($i < 10) {
                $liStyle = "width: 22px;";
            } else if ($i < 100) {
                $liStyle = "width: 30px;";
            } else {
                $liStyle = "width: 37px;";
            }
            $liHtml .= "<li class='rounded' id='page' style='$liStyle'><a href='javascript:void(0)' class='pages button' id='" . $i . "' $lnkStyle>$i</a></li>";
        }
        $paginationHtml = "
            <input type='hidden' name='page-clicks' id='page-clicks' value='0' />
            <div class='row' style='margin-top: 5px;'>
                <div class='one whole centered mobile'>
                    <p id='total' style='font-weight: bold;'>
                        <strong>Page $currentPage of $totalPages</strong>
                    </p>
                 </div>
            </div>
            <div class='pagination-row' style='$rowWidth'>
                <div class='one third mobile' id='first-page'>
                    <li class='rounded' id='scroll-left'><a href='javascript:void(0)' class='button' id='1'><i class='icon-double-angle-left'></i></a></li>
                    <li class='rounded' id='page'><a href='javascript:void(0)' class='pages button' id='1'>First</a></li>
                </div>
                <div class='one third mobile'>
                    <ul id='pagination'>
                        $liHtml
                    </ul>
                </div>
                <div class='one third mobile' id='last-page'>
                    <li class='rounded' id='scroll-right'><a href='javascript:void(0)' class='button' id='$totalPages'><i class='icon-double-angle-right'></i></a></li>
                    <li class='rounded' id='page'><a href='javascript:void(0)' class='pages button' id='$totalPages'>Last</a></li>
                </div>
            </div>";
        return $paginationHtml;
    }

    private function getRowWidth() {
        switch ($this->OrderCollection->TotalPages) {
            case 1:
                $rowWidth = "width: 32%;";
                break;
            case 2:
                $rowWidth = "width: 32%;";
                break;
            case 3:
                $rowWidth = "width: 32%;";
                break;
            case 4:
                $rowWidth = "width: 32%;";
                break;
            case 5:
                $rowWidth = "width: 33%;";
                break;
            case 6:
                $rowWidth = "width: 40%;";
                break;
            case 7:
                $rowWidth = "width: 46.5%;";
                break;
            case 8:
                $rowWidth = "width: 53%;";
                break;
            case 9:
                $rowWidth = "width: 60%;";
                break;
            case 10:
                $rowWidth = "width: 69%;";
                break;
            case 11:
                $rowWidth = "width: 77.5%;";
                break;
            case 12:
                $rowWidth = "width: 86.5%;";
                break;
            case 13:
                $rowWidth = "width: 95.5%;";
                break;
            default:
                $rowWidth = "width: 100%;";
        }
        return $rowWidth; // This is the width of the row.
    }

    public function getOrdersPerPageHtml() {
        $ordersPerPage = 10;
        if ($this->OrderCollection != null) {
            $ordersPerPage = $this->OrderCollection->OrdersPerPage;
        }

        $fiveSelected = "";
        $tenSelected = "";
        $twentySelected = "";
        $fiftySelected = "";
        $oneHundredSelected = "";
        switch ($ordersPerPage) {
            case 5:
                $fiveSelected = "selected='selected'";
                break;
            case 20:
                $twentySelected = "selected='selected'";
                break;
            case 50:
                $fiftySelected = "selected='selected'";
                break;
            case 100:
                $oneHundredSelected = "selected='selected'";
                break;
            default:
                $tenSelected = "selected='selected'";
        }
        $html = "<form>
            <select name='amountPerPage' id='amountPerPage' class='pull-right' style='margin-bottom: 0;'>
                <option value='5' $fiveSelected>5</option>
                <option value='10' $tenSelected>10</option>
                <option value='20' $twentySelected>20</option>
                <option value='50' $fiftySelected>50</option>
                <option value='100' $oneHundredSelected>100</option>
            </select>
        </form>";
        return $html;
    }
}
?>