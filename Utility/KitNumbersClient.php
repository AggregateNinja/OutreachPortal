<?php
/**
 * Created by PhpStorm.
 * User: eboss
 * Date: 8/5/2025
 * Time: 3:32 PM
 */

require_once 'PageClient.php';
require_once 'DAOS/KitNumberDAO.php';
require_once 'DOS/KitNumber.php';
require_once 'DOS/KitNumberCollection.php';

class KitNumbersClient extends PageClient {

    private $KitNumberCollection;

    public function __construct(array $data = null) {
        parent::__construct($data);

        $errorMsg = "";
        if (isset($_GET['msg']) && !empty($_GET['msg'])) {
            $errorMsg = $_GET['msg'];
        }

        $this->addStylesheet("/outreach/admin/css/kitnumbers.css");
        $this->addStylesheet("/outreach/css/pagination.css");
        $this->addStylesheet("/outreach/css/menu-slider.css");
        $this->addScript("/outreach/admin/js/kitnumbers.js");
        $this->addOverlay("
            <div id='errorOverlay' class='rounded'>
                <div class='row'>
                    <div class='one mobile whole' style='text-align: center'>
                        <h4>Error Processing Request</h4>
                    </div>
                </div>
                <div class='row'>
                    <div class='one mobile whole' style='text-align: center'>
                        <h5 id='errorMsg'>$errorMsg</h5>
                    </div>
                </div>
                <div class='row'>
                    <div class='one mobile whole' style='text-align: center'>
                        <button class='green' id='btnErrorSubmit'>Ok</button>
                    </div>
                </div>
            </div>
        ");
        $this->addOverlay("
            <div id='uploadOverlay' class='rounded'>
                <form id=\"frmUpload\" enctype=\"multipart/form-data\" action=\"kitnumbersb.php\" method=\"post\">
                    <input type=\"file\" id=\"fileSpreadsheet\" name=\"fileSpreadsheet\">
                    <input type=\"hidden\" name=\"action\" value=\"1\" />
                    <div class='row'>
                        <div class='one mobile whole' style='text-align: center'>
                            <h4>Upload Spreadsheet</h4>
                            <h5 id='fileInfo'></h5>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='one mobile half' style='text-align: center'>
                            <button class='green' id='btnUploadSubmit'>Upload</button>
                        </div>
                        <div class='one mobile half' style='text-align: center'>
                            <button class='green' id='btnUploadCancel'>Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        ");
        $this->addOverlay("
            <div id='editOverlay' class='rounded'>
                <form id='frmEdit' action='kitnumbersb.php' method='post'>
                    <input type='hidden' name='action' value='2' />
                    <input type='hidden' name='editId' id='editId' />
                    <div class='row'>
                        <div class='one mobile whole' style='text-align: center'>
                            <h4>Edit Kit Number</h4>
                        </div>
                    </div>
                    <div class='row' style='margin-top: 20px;'>
                        <div class='two mobile twelfths' style='padding-right: 5px;'>Product Name</div>
                        <div class='two mobile twelfths' style='padding-right: 5px;'>Lot Number</div>
                        <div class='two mobile twelfths' style='padding-right: 5px;'>Kit Number</div>
                        <div class='four mobile twelfths' style='padding-right: 5px;'>Description</div>
                        <div class='two mobile twelfths'>Pre-Paid</div>
                    </div>
                    <div class='row' style='margin-bottom: 20px'>
                        <div class='two mobile twelfths' style='padding-right: 5px;'>
                            <input type='text' name='editProductName' id='editProductName' />
                        </div>
                        <div class='two mobile twelfths' style='padding-right: 5px;'>
                            <input type='text' name='editLotNumber' id='editLotNumber' />
                        </div>
                        <div class='two mobile twelfths' style='padding-right: 5px;'>
                            <input type='text' name='editKitNumber' id='editKitNumber' />
                        </div>
                        <div class='four mobile twelfths' style='padding-right: 5px;'>
                            <input type='text' name='editDescription' id='editDescription' />
                        </div>
                        <div class='two mobile twelfths'>
                            <select name='editPrepaid' id='editPrepaid'>
                                <option value='1'>Yes</option>
                                <option value='0'>No</option>
                            </select>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='one mobile half' style='text-align: center'>
                            <button class='green' id='btnEditSubmit'>Submit</button>
                        </div>
                        <div class='one mobile half' style='text-align: center'>
                            <button class='green' id='btnEditCancel'>Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        ");
        $this->addOverlay("
            <div id='deleteOverlay' class='rounded'>
                <form id='frmDelete' action='kitnumbersb.php' method='post'>
                    <input type='hidden' name='action' value='3' />
                    <input type='hidden' name='deleteId' id='deleteId' />
                    <div class='row'>
                        <div class='one mobile whole' style='text-align: center'>
                            <h4>Would like to delete Kit Number <div id='deleteKitNumber'></div>?</h4>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='one mobile half' style='text-align: center'>
                            <button class='green' id='btnDeleteSubmit'>Delete</button>
                        </div>
                        <div class='one mobile half' style='text-align: center'>
                            <button class='green' id='btnDeleteCancel'>Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        ");
        $this->addOverlay("
            <div id='successOverlay' class='rounded'>
                <div class='row'>
                    <div class='one mobile whole' style='text-align: center'>
                        <h4>Upload Successful</h4>
                    </div>
                </div>
                <div class='row'>
                    <div class='one mobile whole' style='text-align: center'>
                        <button class='green' id='btnSuccessClose'>Close</button>
                    </div>
                </div>
            </div>
        ");


        $kitDAO = new KitNumberDAO($data);
        $aryKitNumbers = $kitDAO->getKitNumbers();
        //$_SESSION['kitNumbers'] = serialize($aryKitNumbers);

        $this->KitNumberCollection = new KitNumberCollection($data);
        $this->KitNumberCollection->setKitNumbers($aryKitNumbers);
        $this->KitNumberCollection->setTotalKitNumbers();
        $this->KitNumberCollection->setTotalPages();
        $this->KitNumberCollection->setStart();
        $this->KitNumberCollection->setEnd();
    }

    public function printPage() {
        $kitsPerPage = 100;
        if ($this->KitNumberCollection != null) {
            $kitsPerPage = $this->KitNumberCollection->KitsPerPage;
        }


        $tenSelected = "";
        $fiftySelected = "";
        $oneHundredSelected = "";
        $fiveHundredSelected = "";
        $oneThousandSelected = "";
        switch ($kitsPerPage) {
            case 10:
                $tenSelected = "selected='selected'";
                break;
            case 50:
                $fiftySelected = "selected='selected'";
                break;
            case 100:
                $oneHundredSelected = "selected='selected'";
                break;
            case 500:
                $fiveHundredSelected = "selected='selected'";
                break;
            case 1000:
                $oneThousandSelected = "selected='selected'";
                break;
            default:
                $tenSelected = "selected='selected'";
        }

        $html = "<div id='loading'>&nbsp;<i class='icon-spinner icon-spin icon-4x'></i></div>
            <div class=\"container rounded box_shadow\" style=\"margin-top: 20px; padding-bottom: 20px;\">
            <div class=\"row\">
                <div class=\"one mobile fourth pad-top pad-left\">
                    <h4>Manage Kit Numbers</h4>
                </div>
                <div class=\"one mobile fourth pad-top pad-right\">
                    <input type='text' name='kitNumberSearch' id='kitNumberSearch' placeholder='Search Kit Number'/>
                </div>
                <div class=\"one mobile fourth pad-top pad-right\">
                    
                    <form>
                        <label for='amountPerPage'>Amount Per Page: </label>
                        <select name='amountPerPage' id='amountPerPage' style='margin-bottom: 0; width: 100px;'>
                            <option value='10' $tenSelected>10</option>
                            <option value='50' $fiftySelected>50</option>
                            <option value='100' $oneHundredSelected>100</option>
                            <option value='500' $fiveHundredSelected>500</option>
                            <option value='1000' $oneThousandSelected>1000</option>
                        </select>
                    </form>
                </div>
                <div class=\"one mobile fourth pad-top pad-right\">
                    
                    <button class='green submit pull-right' id='btnUpload'>Upload</button>
                </div>
            </div>
            
            
            <div class=\"row\" style=\"margin-top: 20px;\">
                <div class=\"one mobile twelfths\">
                    <a href='javascript:void(0)' class='sort' id='productName'>Product Name</a>
                </div>
                <div class=\"one mobile twelfths\">
                    <a href='javascript:void(0)' class='sort' id='lotNumber'>Lot Number</a>
                </div>
                <div class=\"one mobile twelfths\">
                    <a href='javascript:void(0)' class='sort' id='kitNumber'>Kit Number</a>
                </div>
                <div class=\"three mobile twelfths\">
                    <a href='javascript:void(0)' class='sort' id='description'>Description</a>
                </div>
                <div class=\"two mobile twelfths\">
                    <a href='javascript:void(0)' class='sort' id='dateCreated'>Date Created</a>
                </div>
                <div class=\"one mobile twelfths\">
                    <a href='javascript:void(0)' class='sort' id='dateUpdated'>Date Updated</a>
                </div>
                <div class=\"one mobile twelfth\">
                    <a href='javascript:void(0)' class='sort' id='prePaid'>Pre-Paid</a>
                </div>
                <div class=\"two mobile twelfths\" style='text-align: center'>
                    <a href='javascript:void(0)' id=''>Action</a>
                </div>
            </div>";

        $html .= "<div id='kitNumbers'>" . $this->getKitNumbersHtml() . "</div>";



        $html .= "</div>";

        echo $html;
    }

    public function getKitNumbersHtml() {
        $totalOrders = 0;
        $orderBy = "kitNumber";
        $direction = "desc";
        if ($this->KitNumberCollection != null) {

            if (array_key_exists("KitNumberSearch", $this->KitNumberCollection->KitNumberData) && !empty($this->KitNumberCollection->KitNumberData['KitNumberSearch'])) {
                $kitNumberSearch = $this->KitNumberCollection->KitNumberData['KitNumberSearch'];

                $aryKitNumberSearch = array();
                foreach ($this->KitNumberCollection->KitNumbers as $kitNumber) {
                    if (empty($kitNumberSearch) || strpos(strtolower($kitNumber->kitNumber), strtolower($kitNumberSearch)) !== false) {
                        $aryKitNumberSearch[] = $kitNumber;
                    }
                }

                $this->KitNumberCollection->setKitNumbers($aryKitNumberSearch);
                $this->KitNumberCollection->setTotalKitNumbers();
                $this->KitNumberCollection->setTotalPages();
                $this->KitNumberCollection->setStart();
                $this->KitNumberCollection->setEnd();


            }

            $orderBy = $this->KitNumberCollection->OrderBy;
            $direction = $this->KitNumberCollection->Direction;
            $totalOrders = $this->KitNumberCollection->TotalOrders;
        }

        $html = "
            <input type='hidden' name='orderBy' id='orderBy' value='$orderBy' />
            <input type='hidden' name='direction' id='direction' value='$direction' />
            <input type='hidden' name='totalOrders' id='totalOrders' value='$totalOrders' />";

       //$html .= "Start: " . $this->KitNumberCollection->Start . ", End: " . $this->KitNumberCollection->End . ", Current Page: " . $this->KitNumberCollection->CurrentPage
        //    . ", Kit #: " . $this->KitNumberCollection->KitNumbers[0];
        if ($this->KitNumberCollection != null && $this->KitNumberCollection->TotalKits > 0) {
            $start = $this->KitNumberCollection->Start;
            $end = $this->KitNumberCollection->End;

            for($i = $start; $i <= $end; $i++) {
                $kitNumber = $this->KitNumberCollection->KitNumbers[$i];

                $isActive = "Yes";
                if ($kitNumber->isActive == false) {
                    $isActive = "No";
                }

                $isPrepaid = "Yes";
                if ($kitNumber->isPrepaid == false) {
                    $isPrepaid = "No";
                }

                $dteCreated = new DateTime($kitNumber->dateCreated);
                $dateCreated = $dteCreated->format("m/d/Y h:i A");

                $dateUpdated = "";
                if (isset($kitNumber->dateUpdated) && !empty($kitNumber->dateUpdated)) {
                    $dteUpdated = new DateTime($kitNumber->dateUpdated);
                    $dateUpdated = $dteUpdated->format("m/d/Y h:i A");
                }


                $html .= "<div class=\"row\" style=\"margin-top: 20px;\">
                    <div class=\"one mobile twelfths pad-left\" style='overflow: auto;'>" . $kitNumber->productName . "</div>
                    <div class=\"one mobile twelfths pad-left\" style='overflow: auto;'>" . $kitNumber->lotNumber . "</div>
                    <div class=\"one mobile twelfths pad-left\" style='overflow: auto;'>" . $kitNumber->kitNumber . "</div>
                    <div class=\"three mobile twelfths pad-left\" style='overflow: auto;'>" . $kitNumber->description . "</div>
                    <div class=\"two mobile twelfths pad-left\" style='overflow: auto;'>$dateCreated</div>
                    <div class=\"one mobile twelfths pad-left\" style='overflow: auto;'>$dateUpdated</div>
                    <div class=\"one mobile twelfth pad-left\" style='overflow: auto;'>$isPrepaid</div>
                    <div class=\"two mobile twelfths pad-left\" style='text-align: center; overflow: auto;'>
                        <button class='green submit btnEdit' data-id='" . $kitNumber->idKitNumbers . "'>Edit</button>
                        <button class='green submit btnDelete' data-id='" . $kitNumber->idKitNumbers . "'>Delete</button>
                    </div>
                </div>";
            }

        } else {
            $html .= "<div class=\"row\">
                <div class=\"one mobile whole\" style='text-align: center;'>
                    There are no kit numbers to display at this time.
                </div>
            </div>";
        }

        $html .= $this->getPaginationHtml();


        return $html;
    }

    private function getPaginationHtml() {
        $liHtml = "";
        $rowWidth = $this->getRowWidth();
        $currentPage = $this->KitNumberCollection->CurrentPage;
        $totalPages = $this->KitNumberCollection->TotalPages;
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
        switch ($this->KitNumberCollection->TotalPages) {
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

}