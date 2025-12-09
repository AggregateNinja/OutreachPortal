<?php
/**
 * Created by PhpStorm.
 * User: Ed Bossmeyer
 * Date: 9/22/14
 * Time: 4:39 PM
 */

require_once 'ReportCreator.php';
require_once 'JasperReport.php';
require_once 'DAOS/EntryOrderDAO.php';
require_once 'DAOS/ResultLogDAO.php';
require_once 'DAOS/ESigDAO.php';
require_once 'DOS/ESigController.php';

require_once 'PDFMerger/PDFMerger.php';

class RequisitionReportFactory extends ReportCreator {

    //private $PrintAllTestsOnReq = false;

    protected function factoryMethod(array $data, array $settings = null) {

        $specimenDateColHeader = "Specimen Date";
        if (array_key_exists("SpecimenDateColHeader", $data)) {
            $specimenDateColHeader = $data['SpecimenDateColHeader'];
        }
        $hasESigOnReq = false;
        if (array_key_exists("HasESignatureOnReq", $data)) {
            $hasESigOnReq = $data['HasESignatureOnReq'];
        }
        if (array_key_exists("PrintAllTestsOnReq", $data)) {
            $this->PrintAllTestsOnReq = $data['PrintAllTestsOnReq'];
        }
        $printAllTestsOnReq = 0;
        if (array_key_exists("PrintAllTestsOnReq", $data) && $data['PrintAllTestsOnReq'] == true) {
            $printAllTestsOnReq = 1;
        }


        $aryIdOrders = explode(",", $data['idOrders']);
        $aryIsReceipted = explode(",", $data['isReceipted']);

        $pdfMerger = new PDFMerger;
        $numOrderIds = count($aryIdOrders);

        $aryReportNames = array();

        for($i = 0; $i < $numOrderIds; $i++) {
            $idOrders = $aryIdOrders[$i];

            $isReceipted = $aryIsReceipted[$i];
            $entryOrder = EntryOrderDAO::getEntryOrder(
                array(
                    "userId" => $data['userId'],
                    "idOrders" => $idOrders,
                    "isReceipted" => $isReceipted
                ),
                array(
                    "IncludeClientInfo" => true,
                    "IncludeDoctorInfo" => true,
                    "IncludeInsuranceInfo" => true,
                    "IncludeCodeNames" => true,
                    "type" => $data['type']
                )
            );
            //$jasperData = $this->getJasperData2($entryOrder, $specimenDateColHeader, $hasESigOnReq,  $data['userId'], $isReceipted);


            $aryOrderInfo = EntryOrderDAO::getOrderInfo($idOrders);
            $printESigOnReq = $aryOrderInfo['PrintESigOnReq'];
            if (array_key_exists("HasESignatureOnReq", $data) && $data['HasESignatureOnReq'] == false) { // global setting for whether or not to print esigs on reqs
                $printESigOnReq = false;
            }
            $jasperData = $this->getJasperData($idOrders, $printESigOnReq, $data['userId'], $aryOrderInfo['doctorId'], $printAllTestsOnReq);

            $currReport = new JasperReport($jasperData);

            if ($numOrderIds > 1) {
                $currPdf = $currReport->EncodedPdf;

                //$currFileName = $this->saveReport($currPdf, "requisition_" . $entryOrder->accession . "_" . date("Ymdhis"));
                $currFileName = $this->saveReport($currPdf, "requisition_" . $aryOrderInfo['accession'] . "_" . date("Ymdhis"));

                $aryReportNames[] = $currFileName;
                $pdfMerger->addPDF($currFileName, 'all');
            }

            $logData = array (
                "idUsers" => $data['userId'],
                "Ip" => $this->getIpAddress(),
                "orderEntryLogType" => 3,
                "orderId" => $idOrders,
                "advancedOrderId" => null,
                "advancedOrderOnly" => false,

                //"accession" => $entryOrder->accession,
                //"isNewPatient" => $entryOrder->IsNewPatient,
                //"isNewSubscriber" => $entryOrder->IsNewSubscriber,
                //"subscriberChanged" => $entryOrder->SubscriberChanged
                "accession" => $aryOrderInfo['accession'],
                "isNewPatient" => $aryOrderInfo['isNewPatient'],
                "isNewSubscriber" => $aryOrderInfo['isNewSubscriber'],
                "subscriberChanged" => $aryOrderInfo['subscriberChanged']
            );

            /*if ($entryOrder->isAdvancedOrder == true && !isset($entryOrder->Phlebotomy)) {
                $logData['advancedOrderOnly'] = true;
                $logData['advancedOrderId'] = $entryOrder->idOrders;
            } else {
                $logData['orderId'] = $entryOrder->idOrders;
            }*/
            $logData['orderId'] = $idOrders;

            $this->addLogEntry($logData);
        }

        if ($numOrderIds > 1) {
            $fileName = "requisition" . date("Ymdhis") . ".pdf";
            $encodedPdf = $pdfMerger->merge('string', $fileName);

            $report = new Report(array("name" => $fileName, "EncodedPdf" => $encodedPdf));

            $this->Report = $report;

            $_SESSION['fileNames'] = $aryReportNames;

        } else {
            $this->Report = $currReport;
        }

        return $this->Report;
    }

    private function getJasperData($idOrders, $hasESigOnReq, $userId, $doctorId, $printAllTestsOnReq) {

        $imageFileName = "blankImage.png";
        if ($hasESigOnReq == true) {
            $eSigDAO = new ESigDAO(array("userId" => $userId));
            $eSignature = $eSigDAO->getESig();
            if ($eSignature != null && $eSignature->assignTypeId == 1) {
                $imageFileName = "CroppedESignature$userId.png";
            } else if ($doctorId != null && !empty($doctorId)) {
                $eController = new ESigController(array("doctorId" => $doctorId, "assignTypeId" => 2));
                $doctorFileName = "CroppedDoctorESignature" . $doctorId . ".png";
                $results = $eController->searchJasperImage($doctorFileName);
                if ($results->totalCount > 0) {
                    $imageFileName = $doctorFileName;
                }
            }
        }

        return array(array(
            "name" => "Requisition",
            "filePath" => "Requisition.jrxml",
            "Parameters" => array (
                "idOrders" => $idOrders,
                "eSignatureImageFile" => $imageFileName,
                "printAllTestsOnReq" => $printAllTestsOnReq
            )
        ));
    }

    private function getJasperData2(EntryOrder $entryOrder, $specimenDateColHeader, $hasESigOnReq, $userId, $isReceipted) {
        $strOrderTests1 = "";
        $strOrderTests2 = "";
        $strOrderTests3 = "";
        $strOrderTests4 = "";
        $strOrderTests5 = "";

        $strPOCTests1 = "";
        $strPOCTests2 = "";
        $strPOCResults1 = "";
        $strPOCResults2 = "";

        $i = 0;
        $j = 0;

        $numTests = 0;
        foreach ($entryOrder->Results as $result) {
            if ($result->IsPOC == false) {
                $currTest = $result->Test->printedName . " (" . $result->Test->number . ")";
                if (($this->PrintAllTestsOnReq == false && empty($result->panelId)) || $this->PrintAllTestsOnReq == true) {
                    $numTests++;

                    if (strlen($currTest) > 33) {
                        $numTests++;
                    }
                }
            }
        }

        $maxTestsCol1 = round($numTests / 4) + ($numTests % 4 > 0 ? 1 : 0);
        $maxTestsCol2 = $maxTestsCol1 + round($numTests / 4) + ($numTests % 4 > 1 ? 1 : 0);
        $maxTestsCol3 = $maxTestsCol2 + round($numTests / 4) + ($numTests % 4 > 2 ? 1 : 0);

        if ($numTests > 0) {

            /*foreach ($entryOrder->Results as $result) {
                $currTest = $result->Test->printedName . " (" . $result->Test->number . ")";
                if (strlen($currTest) >= 29) {
                    $numTests++;
                }
            }*/

            foreach ($entryOrder->Results as $result) {

                if (isset($result->Test)) {
                    if ($result->IsPOC == true && $result->Test->testType == 1) {
                        if ($j % 2 != 0) {
                            $strPOCTests1 .= $result->Test->abbr . " (" . $result->Test->number . ")" . "<br />";
                            $strPOCResults1 .= $result->resultText . "<br/>";
                        } else {
                            $strPOCTests2 .= $result->Test->abbr . " (" . $result->Test->number . ")" . "<br />";
                            $strPOCResults2 .= $result->resultText . "<br/>";
                        }

                        $j++;

                        $numTests--;
                    //} else if ($result->IsPOC == false) { //if ($result->IsPOC == false && ($result->panelId == null || empty($result->panelId))) {
                    } else if ($this->PrintAllTestsOnReq == true && $result->IsPOC == false) {
                        if ($result->Test->testType == 0) {
                            $currTest = "<b>" . $result->Test->name . " (" . $result->Test->number . ")</b><br/>";
                        } else {
                            $currTest = $result->Test->name . " (" . $result->Test->number . ")<br/>";
                        }




                        if ($numTests <= 48) {
                            $currTest .= "<br/>";
                            if ($i < 13) {
                                $strOrderTests1 .= $currTest;
                            } else if ($i < 25) {
                                $strOrderTests2 .= $currTest;
                            } else if ($i < 37) {
                                $strOrderTests3 .= $currTest;
                            } else {
                                $strOrderTests4 .= $currTest;
                            }
                        } else if ($numTests <= 92) {
                            if ($i < 23) {
                                $strOrderTests1 .= $currTest;
                            } else if ($i < 46) {
                                $strOrderTests2 .= $currTest;
                            } else if ($i < 69) {
                                $strOrderTests3 .= $currTest;
                            } else {
                                $strOrderTests4 .= $currTest;
                            }
                        } else if ($numTests <= 130) {
                            if ($i < 26) {
                                $strOrderTests1 .= $currTest;
                            } else if ($i < 52) {
                                $strOrderTests2 .= $currTest;
                            } else if ($i < 78) {
                                $strOrderTests3 .= $currTest;
                            } else if ($i < 104) {
                                $strOrderTests4 .= $currTest;
                            } else {
                                $strOrderTests5 .= $currTest;
                            }
                        } else {
                            if ($i < 31) {
                                $strOrderTests1 .= $currTest;
                            } else if ($i < 62) {
                                $strOrderTests2 .= $currTest;
                            } else if ($i < 93) {
                                $strOrderTests3 .= $currTest;
                            } else if ($i < 124) {
                                $strOrderTests4 .= $currTest;
                            } else {
                                $strOrderTests5 .= $currTest;
                            }
                        }

                        //if (strlen($currTest) - 5 >= 34) {
                        //    $i++;
                        //}

                        $currTestTmp = $result->Test->printedName . " (" . $result->Test->number . ")";
                        if (strlen($currTestTmp) >= 34) {
                            $i = $i + 2;
                        } else {
                            $i++;
                        }
                    } else if ($this->PrintAllTestsOnReq == false && $result->IsPOC == false && ($result->panelId == null || empty($result->panelId))) {
                        if ($result->Test->testType == 0) {
                            $currTest = "<b>" . $result->Test->name . " (" . $result->Test->number . ")</b><br/>";
                        } else {
                            $currTest = $result->Test->name . " (" . $result->Test->number . ")<br/>";
                        }

                        if ($i < $maxTestsCol1) {
                            $strOrderTests1 .= $currTest;
                        } else if ($i < $maxTestsCol2) {
                            $strOrderTests2 .= $currTest;
                        } else if ($i < $maxTestsCol3) {
                            $strOrderTests3 .= $currTest;
                        } else {
                            $strOrderTests4 .= $currTest;
                        }

                        /*if ($i < 6) {
                            $strOrderTests1 .= $currTest;
                        } else if ($i < 12) {
                            $strOrderTests2 .= $currTest;
                        } else if ($i < 18) {
                            $strOrderTests3 .= $currTest;
                        } else {
                            $strOrderTests4 .= $currTest;
                        }*/

                        /*if ($i < 31) {
                            $strOrderTests1 .= $currTest;
                        } else if ($i < 62) {
                            $strOrderTests2 .= $currTest;
                        } else if ($i < 93) {
                            $strOrderTests3 .= $currTest;
                        } else if ($i < 124) {
                            $strOrderTests4 .= $currTest;
                        } else {
                            $strOrderTests5 .= $currTest;
                        }*/

                        $currTestTmp = $result->Test->printedName . " (" . $result->Test->number . ")";
                        if (strlen($currTestTmp) > 33) {
                            $i = $i + 2;
                        } else {
                            $i++;
                        }
                    }
                }
            }
            if (!empty($strOrderTests1)) {
                $strOrderTests1 = substr($strOrderTests1, 0, strlen($strOrderTests1) - 2);
            }
            if (!empty($strOrderTests2)) {
                $strOrderTests2 = substr($strOrderTests2, 0, strlen($strOrderTests2) - 2);
            }
            if (!empty($strOrderTests3)) {
                $strOrderTests3 = substr($strOrderTests3, 0, strlen($strOrderTests3) - 2);
            }
            if (!empty($strOrderTests4)) {
                $strOrderTests4 = substr($strOrderTests4, 0, strlen($strOrderTests4) - 2);
            }
            if (!empty($strOrderTests5)) {
                $strOrderTests5 = substr($strOrderTests5, 0, strlen($strOrderTests5) - 2);
            }
            if (!empty($strPOCTests1)) {
                $strPOCTests1 = substr($strPOCTests1, 0, strlen($strPOCTests1) - 12);
            }
            if (!empty($strPOCTests2)) {
                $strPOCTests2 = substr($strPOCTests2, 0, strlen($strPOCTests2) - 12);
            }
            if (!empty($strPOCTests3)) {
                $strPOCTests3 = substr($strPOCTests3, 0, strlen($strPOCTests3) - 12);
            }
            if (!empty($strPOCTests4)) {
                $strPOCTests4 = substr($strPOCTests4, 0, strlen($strPOCTests4) - 12);
            }
        }

        $prescribedDrugs1 = "";
        $prescribedDrugs2 = "";

        if (isset($entryOrder->Prescriptions) && is_array($entryOrder->Prescriptions) && count($entryOrder->Prescriptions) > 0) {
            $prescribedDrugs = $entryOrder->Prescriptions;
            $numScripts = count($prescribedDrugs);

            if ($numScripts == 1) {
                $prescribedDrugs1 = $prescribedDrugs[0]->Drug->genericName;
            } else {
                $numScriptsCol1 = ceil($numScripts/2); // the number of drugs to print in the first Prescribed Drugs column

                for($i = 0; $i < $numScriptsCol1; $i++) {
                    $prescribedDrugs1 .= $prescribedDrugs[$i]->Drug->genericName . "<br/>";
                }

                for($i = $numScriptsCol1; $i < $numScripts; $i++) {
                    $prescribedDrugs2 .= $prescribedDrugs[$i]->Drug->genericName . "<br/>";
                }
            }
        }

        $diagnosisCodes1 = "";
        $diagnosisCodes2 = "";
        $diagnosisCodes3 = "";
        if (isset($entryOrder->DiagnosisCodes)) {
            $i = 0;
            /*foreach ($entryOrder->DiagnosisCodes as $code) {
                if ($i < 8) {
                    $diagnosisCodes1 .= $code->code . "<br /><br />";
                } else if ($i < 16) {
                    $diagnosisCodes2 .= $code->code . "<br /><br />";
                } else {
                    $diagnosisCodes3 .= $code->code . "<br /><br />";
                }
                $i++;
            }*/

            foreach ($entryOrder->DiagnosisCodes as $code) {
                if ($i % 3 == 0) {
                    $diagnosisCodes1 .= $code->code . "<br /><br />";
                } else if ($i % 3 == 1) {
                    $diagnosisCodes2 .= $code->code . "<br /><br />";
                } else {
                    $diagnosisCodes3 .= $code->code . "<br /><br />";
                }
                $i++;
            }

        }

        $insName = "";
        $insPhone = "";
        $insAddress = "";
        $insCity = "";
        $insState = "";
        $insZip = "";
        $insMedicare = "";
        $insId = "";
        $insPolicyNum = "";

        $insSecondaryName = "";
        $insMedicaid = "";
        $insGroupNum = "";
        $insSecondaryGroupNum = "";
        $insSecondaryPolicyNum = "";
        $insSecondaryId = "";
        if (isset($entryOrder->Insurance)) {
            $insName = $entryOrder->Insurance->name;
            $insPhone = $entryOrder->Insurance->phone;
            $insAddress = $entryOrder->Insurance->address;
            $insCity = $entryOrder->Insurance->city;
            $insState = $entryOrder->Insurance->state;
            $insZip = $entryOrder->Insurance->zip;
            $insMedicare = $entryOrder->medicareNumber;
            $insId = $entryOrder->insurance;
            $insPolicyNum = $entryOrder->policyNumber;

            $insGroupNum = $entryOrder->groupNumber;
            $insSecondaryGroupNum = $entryOrder->secondaryGroupNumber;
            $insSecondaryPolicyNum = $entryOrder->secondaryPolicyNumber;
            $insMedicaid = $entryOrder->medicaidNumber;
            $insSecondaryId = $entryOrder->secondaryInsurance;
            if (isset($entryOrder->SecondaryInsurance)) {
                $insSecondaryName = $entryOrder->SecondaryInsurance->name;
            }
        }

        $subLastName = "";
        $subFirstName = "";
        $subMiddleName = "";
        $subRelationship = "";
        $subSsn = "";
        $subPhone = "";
        if (isset($entryOrder->Subscriber) && $entryOrder->Subscriber instanceof Subscriber) {
            $subLastName = $entryOrder->Subscriber->lastName;
            $subFirstName = $entryOrder->Subscriber->firstName;
            $subMiddleName = $entryOrder->Subscriber->middleName;
            $subRelationship = $entryOrder->Patient->relationship;
            $subSsn = $entryOrder->Subscriber->ssn;
            $subPhone = $entryOrder->Subscriber->phone;
        }

        $orderComment = "";
        if (isset($entryOrder->OrderComment) && $entryOrder->OrderComment instanceof OrderComment) {
            $orderComment = $entryOrder->OrderComment;
            $orderComment = $orderComment->comment;
        }

        $roomNumber = "";
        if ($entryOrder->room != null) {
            $roomNumber = $entryOrder->room;
        }

        $bedNumber = "";
        if ($entryOrder->bed != null) {
            $bedNumber = $entryOrder->bed;
        }

        /*$imageFileName = "CroppedESignature" . $data['userId'] . ".png";
        $eSigDAO = new ESigDAO(array("userId" => $data['userId']));
        $eSignature = $eSigDAO->getESig();
        if (!isset($eSignature) || (array_key_exists("HasESignatureOnReq", $data) && $data['HasESignatureOnReq'] == false)) {
            $imageFileName = "blankImage.png";
        }*/

        $imageFileName = "blankImage.png";

        if ($hasESigOnReq == true && $entryOrder->PrintESignature == true) {
            $eSigDAO = new ESigDAO(array("userId" => $userId));
            $eSignature = $eSigDAO->getESig();

            if ($eSignature != null && $eSignature->assignTypeId == 1) {
                $imageFileName = "CroppedESignature$userId.png";
            } else {
                $eController = new ESigController(array("doctorId" => $entryOrder->doctorId, "assignTypeId" => 2));
                $doctorFileName = "CroppedDoctorESignature" . $entryOrder->doctorId . ".png";
                $results = $eController->searchJasperImage($doctorFileName);
                if ($results->totalCount > 0) {
                    $imageFileName = $doctorFileName;
                }
            }
        }

        $doctorName = "";
        $doctorAddress = "";
        $doctorCityStateZip = "";
        $doctorPhone = "";
        $doctorNpi = "";
        $doctorUpin = "";
        if (isset($entryOrder->Doctor) && $entryOrder->Doctor instanceof DoctorUser) {
            $doctorName = $entryOrder->Doctor->firstName . " " . $entryOrder->Doctor->lastName;

            if (!empty($entryOrder->Doctor->address1) && !empty($entryOrder->Doctor->address2)) {
                $doctorAddress = $entryOrder->Doctor->address1 . " " . $entryOrder->Doctor->address2;
            } else if (!empty($entryOrder->Doctor->address1)) {
                $doctorAddress = $entryOrder->Doctor->address1;
            } else if (!empty($entryOrder->Doctor->address2)) {
                $doctorAddress = $entryOrder->Doctor->address2;
            }

            if (!empty($entryOrder->Doctor->city)) {
                $doctorCityStateZip = $entryOrder->Doctor->city;
            }
            if (!empty($entryOrder->Doctor->state)) {
                if (!empty($doctorCityStateZip)) {
                    $doctorCityStateZip .= ", " . $entryOrder->Doctor->state;
                } else {
                    $doctorCityStateZip = $entryOrder->Doctor->state;
                }
            }
            if (!empty($entryOrder->Doctor->zip)) {
                $doctorCityStateZip .= " " . $entryOrder->Doctor->zip;
            }

            if (!empty($entryOrder->Doctor->phone)) {
                $doctorPhone = $entryOrder->Doctor->phone;
            }

            if (!empty($entryOrder->Doctor->NPI)) {
                $doctorNpi = $entryOrder->Doctor->NPI;
            }
            if (!empty($entryOrder->Doctor->UPIN)) {
                $doctorUpin = $entryOrder->Doctor->UPIN;
            }
        }

        $clientPhone = "";
        $clientName = "";
        $clientNo = "";
        $clientAddress = "";
        $clientCityStateZip = "";
        if (isset($entryOrder->Client)) {
            $clientName = $entryOrder->Client->clientName;
            $clientNo = $entryOrder->Client->clientNo;

            if (isset($entryOrder->Client->phoneNo) && $entryOrder->Client->phoneNo != null) {
                $clientPhone = $entryOrder->Client->phoneNo;
            }

            if (!empty($entryOrder->Client->clientStreet) && !empty($entryOrder->Client->clientStreet2)) {
                $clientAddress = $entryOrder->Client->clientStreet . " " . $entryOrder->Client->clientStreet2;
            } else if (!empty($entryOrder->Client->clientStreet)) {
                $clientAddress = $entryOrder->Client->clientStreet;
            } else if (!empty($entryOrder->Client->clientStreet2)) {
                $clientAddress = $entryOrder->Client->clientStreet2;
            }

            if (!empty($entryOrder->Client->clientCity)) {
                $clientCityStateZip = $entryOrder->Client->clientCity;
            }
            if (!empty($entryOrder->Client->clientState)) {
                if (!empty($clientCityStateZip)) {
                    $clientCityStateZip .= ", " . $entryOrder->Client->clientState;
                } else {
                    $clientCityStateZip = $entryOrder->Client->clientState;
                }
            }
            if (!empty($entryOrder->Client->clientZip)) {
                $clientCityStateZip .= " " . $entryOrder->Client->clientZip;
            }

        }

        return array(array(
            "name" => "Requisition",
            "filePath" => "Requisition.jrxml",
            "Parameters" => array (
                "accession" => $entryOrder->accession,
                "specimenDate" => date("m/d/Y h:i A", strtotime($entryOrder->specimenDate)),
                "orderDate" => date("m/d/Y h:i A", strtotime($entryOrder->orderDate)),
                "clientName" => $clientName,
                "doctorName" => $doctorName,
                "patientLastName" => $entryOrder->Patient->lastName,
                "patientFirstName" => $entryOrder->Patient->firstName,
                "patientMiddleName" => $entryOrder->Patient->middleName,
                "patientDob" => date("m/d/Y", strtotime($entryOrder->Patient->dob)),
                "patientGender" => $entryOrder->Patient->sex,
                "patientAddress" => $entryOrder->Patient->addressStreet,
                "patientApt" => $entryOrder->Patient->addressStreet2,
                "patientCity" => $entryOrder->Patient->addressCity,
                "patientState" => $entryOrder->Patient->addressState,
                "patientZip" => $entryOrder->Patient->addressZip,
                "patientSsn" => $entryOrder->Patient->ssn,
                "patientPhone" => $entryOrder->Patient->phone,
                "subscriberLastName" => $subLastName,
                "subscriberFirstName" => $subFirstName,
                "subscriberMiddleName" => $subMiddleName,
                "subscriberRelationship" => $subRelationship,
                "subscriberSsn" => $subSsn,
                "subscriberPhone" => $subPhone,
                "insuranceName" => $insName,
                "insurancePhone" => $insPhone,
                "insuranceAddress" => $insAddress,
                "insuranceCity" => $insCity,
                "insuranceState" => $insState,
                "insuranceZip" => $insZip,
                "insuranceMedicare" => $insMedicare,
                "insuranceId" => $insId,
                "insurancePolicyNumber" => $insPolicyNum,
                "testsOrdered1" => $strOrderTests1,
                "testsOrdered2" => $strOrderTests2,
                "testsOrdered3" => $strOrderTests3,
                "testsOrdered4" => $strOrderTests4,
                "testsOrdered5" => $strOrderTests5,
                "diagnosisCodes1" => $diagnosisCodes1,
                "diagnosisCodes2" => $diagnosisCodes2,
                "diagnosisCodes3" => $diagnosisCodes3,
                "pocTestsOrdered1" => $strPOCTests1,
                "pocTestsOrdered2" => $strPOCTests2,
                "pocTestsResults1" => $strPOCResults1,
                "pocTestsResults2" => $strPOCResults2,
                "prescribedDrugs1" => $prescribedDrugs1,
                "prescribedDrugs2" => $prescribedDrugs2,
                "orderComment" => $orderComment,
                "eSignatureImageFile" => $imageFileName,
                "insuranceSecondaryName" => $insSecondaryName,
                "insuranceMedicaid" => $insMedicaid,
                "insuranceGroupNumber" => $insGroupNum,
                "insuranceSecondaryGroupNumber" => $insSecondaryGroupNum,
                "insuranceSecondaryPolicyNumber" => $insSecondaryPolicyNum,
                "insuranceSecondaryId" => $insSecondaryId,
                "roomNumber" => $roomNumber,
                "bedNumber" => $bedNumber,
                "clientNumber" => $clientNo,
                "clientPhone" => $clientPhone,
                "doctorNumber" => $entryOrder->Doctor->number,
                "SpecimenDateColHeader" => $specimenDateColHeader,
                "NumTests" => $numTests,
                "isReceipted" => $isReceipted,
                "clientAddress" => $clientAddress,
                "clientCityStateZip" => $clientCityStateZip,
                "doctorAddress" => $doctorAddress,
                "doctorCityStateZip" => $doctorCityStateZip,
                "doctorPhone" => $doctorPhone,
                "doctorNpi" => $doctorNpi,
                "doctorUpin" => $doctorUpin
            )
        ));
    }

    protected function addLogEntry(array $logData = null) {
        $aryLogInput = array (
            "idUsers" => $logData['idUsers'],
            "Ip" => $logData['Ip'],
            "orderEntryLogType" => $logData['orderEntryLogType'],
            "orderId" => $logData['orderId'],
            "advancedOrderId" => $logData['advancedOrderId'],
            "accession" => $logData['accession'],
            "advancedOrderOnly" => $logData['advancedOrderOnly'],
            "isNewPatient" => $logData['isNewPatient'],
            "isNewSubscriber" => $logData['isNewSubscriber'],
            "subscriberChanged" => $logData['subscriberChanged']
        );

        ResultLogDAO::addReqViewLogEntry($aryLogInput);
    }
} 