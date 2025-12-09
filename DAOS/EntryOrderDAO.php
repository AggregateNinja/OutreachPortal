<?php

require_once 'DataObject.php';
require_once 'DataConnect.php';
require_once 'SubscriberDAO.php';
require_once 'PatientDAO.php';
require_once 'ResultDAO.php';
require_once 'TestDAO.php';
require_once 'PrescriptionDAO.php';
require_once 'OrderCommentDAO.php';
require_once 'DOS/Result.php';
require_once 'DOS/EntryOrder.php';
require_once 'DiagnosisDAO.php';
require_once 'DOS/ResultOrder.php';
require_once 'ClientDAO.php';
require_once 'DoctorDAO.php';
require_once 'InsuranceDAO.php';
require_once 'PhlebotomyDAO.php';
require_once 'DOS/Subscriber.php';
require_once 'ResultLogDAO.php';
require_once 'DOS/OrderEmail.php';


class EntryOrderDAO extends DataObject {

    public static function saveRequisitionPdf($orderId, $userId, $encodedPdf, $fileName) {
        if (self::hasIdsCards($orderId)) {
            $sql = "DELETE FROM " . self::DB_CSS_WEB . "." . self::TBL_REQUISITIONS . " WHERE idOrders = ?";
            parent::manipulate($sql, array($orderId));
        }

        $sql = "INSERT INTO " . self::DB_CSS_WEB . "." . self::TBL_REQUISITIONS . " (idOrders, idUser, fileType, reportName, data) VALUES (?,?,?,?,?)";
        parent::manipulate($sql, array($orderId, $userId, "pdf", $fileName, $encodedPdf));

    }

    public static function saveIdCardsInfo($orderId, $userId, $licenseFrontFileName, $licenseBackFileName, $insuranceFileName) {
        $sql = "SELECT idCards FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDERIDCARDS . " WHERE idOrders = ?";
        $data = parent::select($sql, array($orderId));

        if (count($data) > 0) {
            $sql = "DELETE FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDERIDCARDS . " WHERE idOrders = ?";
            parent::manipulate($sql, array($orderId));
        }

        $sql = "INSERT INTO " . self::DB_CSS_WEB . "." . self::TBL_ORDERIDCARDS . " (idUser, idOrders, licenseFrontFileName, licenseBackFileName, insuranceFileName) VALUES (?,?,?,?,?)";
        parent::manipulate($sql, array($userId, $orderId, $licenseFrontFileName, $licenseBackFileName, $insuranceFileName));
    }

    public static function getIdCardsInfo($orderId) {
        $sql = "SELECT licenseFrontFileName, licenseBackFileName, insuranceFileName FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDERIDCARDS . " WHERE idOrders = ?";
        $data = parent::select($sql, array($orderId));
        return $data;

    }

    public static function hasIdsCards($orderId) {
        $sql = "SELECT idOrders FROM " . self::DB_CSS_WEB . "." . self::TBL_REQUISITIONS . " WHERE idOrders = ?";
        $data = parent::select($sql, array($orderId));

        if (count($data) > 0) {
            return true;
        }

        return false;
    }

    public static function getPrevOrderDate($userId) {
        $sql = "SELECT o.orderDate, rl.webUser, rl.idOrders, rl.receiptedDate
        FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o
        INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERRECEIPTLOG . " rl ON o.accession = rl.webAccession
        WHERE rl.webUser = ?
        ORDER BY o.idOrders DESC
        LIMIT 1";
        $data = parent::select($sql, array($userId));

        if (count($data) > 0) {
            return new DateTime($data[0]['orderDate']);
        }
        return null;
    }

    public static function cancelWebOrder($orderId, $userId, $ip, $userTypeId) {
        $conn = parent::connect();

        $sql = "UPDATE " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " SET active = 0 WHERE idOrders = ?";
        parent::manipulate($sql, array($orderId), array("Conn" => $conn));

        $sql = "UPDATE " . self::DB_CSS_WEB . "." . self::TBL_RESULTS . " SET isInvalidated = 1, invalidatedDate = CURRENT_TIMESTAMP WHERE orderId = ?";
        parent::manipulate($sql, array($orderId), array("Conn" => $conn));

        $sql = "
          SELECT el.idorderEntryLog, el.idUser, el.action, el.description
          FROM " . self::DB_CSS . "." . self::TBL_ORDERENTRYLOG . " el
          WHERE el.action = 'Web Order Deactivated' AND el.description = ?";
        $data = parent::select($sql, array("Web Order Id: " . $orderId), array("Conn" => $conn));

        ResultLogDAO::orderInvalidatedLogEntry(array(
            "orderStatus" => 0,
            "orderId" => $orderId,
            "adminUserId" => $userId,
            "userId" => $userId,
            "Conn" => $conn,
            "Ip" => $ip,
            "userTypeId" => $userTypeId,
            "email" => ""
        ));

        if (count($data) == 0) {
            // zero rows should generally always be returned
            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_ORDERENTRYLOG . " (createdDate, action, description) 
                VALUES (CURRENT_TIMESTAMP, 'Web Order Deactivated', ?)";
            parent::manipulate($sql, array("Web Order Id: " . $orderId), array("Conn" => $conn));
        }
    }

    public static function activateOrder($orderId) {
        $sql = "UPDATE " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " SET active = ? WHERE idOrders = ?";
        parent::manipulate($sql, array(1, $orderId));
    }

    private static function saveDocuments(array $docs, mysqli $conn, $idOrders, $accession, $userId) {
        require_once 'PDFMerger/PDFMerger.php';
        $currDate = date("Ymdhis");
        $filePath = self::OrderEntryDocumentsFilePath;

        $aryPrevFileNames = self::getDocuments($idOrders, null, $conn);
        foreach ($aryPrevFileNames as $prevFileName) {
            unlink($filePath . $prevFileName);
        }

        $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_WEBORDERDOCUMENTS . " WHERE webIdOrders = ?";
        parent::manipulate($sql, array($idOrders), array("Conn" => $conn));

        $aryFileNames = array();
        //$filePath = $_SERVER['DOCUMENT_ROOT'] . "/outreach/orderentry/documents/";


        //error_log($filePath);
        $aryPdfFileNames = array();
        $minWidth = 816;
        for($i = 0; $i < count($docs); $i++) {
            $encodedImage = $docs[$i];
            $imageName = $accession . ($i + 1);

            if (mime_content_type($encodedImage) == "application/pdf") {
                $fileName =  $imageName . "_" . $currDate . ".pdf";

                $imageDataPieces = explode(",", $encodedImage);
                $encodedImage = $imageDataPieces[1];
                $decodedImage = base64_decode($encodedImage);

                //error_log($encodedImage);

                ob_start();
                echo $decodedImage;
                $output_so_far = ob_get_contents();
                ob_clean();

                file_put_contents($filePath . $fileName, $output_so_far);
                $aryPdfFileNames[] = $filePath . $fileName;
            } else {
                $imageDataPieces = explode(",", $encodedImage);
                $encodedImage = $imageDataPieces[1];
                $decodedImage = base64_decode($encodedImage);

                //error_log($encodedImage);

                $image = new Imagick();
                $image->readimageblob($decodedImage);
                $image->trimImage(100);
                $image->setOption('jpeg:extent', '500kb');

                $width = $image->getImageWidth();
                $height = $image->getImageHeight();

                if ($width > $minWidth && count($docs) > 1) { // width is greater than 8.5 inches, so shrink image

                    $newWidth = $minWidth;
                    $newHeight = ($height/$width) * $newWidth;

                    $imageData = $image->identifyimage();
//                    $strImageData = "";
//                    foreach($imageData as $key => $val) {
//                        if (is_array($val)) {
//                            $strImageData .= $key . " - " . implode(", ",$val) . ", ";
//                        } else {
//                            $strImageData .= $key . " - " . $val . ", ";
//                        }
//                    }
//                    error_log($strImageData);
//                    error_log("Width: " . $width . ", Height: " . $height . ", New Width: " . $newWidth . ", New Height: " . $newHeight);
                    $image->resizeImage($newWidth, $newHeight, Imagick::FILTER_UNDEFINED, 1);
                }

                $imageBlob = $image->getImageBlob();

                ob_start();
                echo $imageBlob;
                $output_so_far = ob_get_contents();
                ob_clean();
                $fileName =  $imageName . "_" . $currDate . ".png";
                file_put_contents($filePath . $fileName, $output_so_far);
                $aryFileNames[] = $filePath . $fileName;
            }




            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_WEBORDERDOCUMENTS . " (webIdOrders, webAccession, webUserId, fileName, typeName) VALUES (?,?,?,?,?)";
            parent::manipulate($sql, array($idOrders, $accession, $userId, $fileName, "image"), array("Conn" => $conn));

        }



        if (count($aryFileNames) > 0 && count($aryPdfFileNames) > 0) {
            $pdf = new Imagick($aryFileNames);
            $pdf->setImageFormat('pdf');
            $combinedImagesFileName = $accession . "_images_" . $currDate . '.pdf';
            $pdf->writeImages($filePath . $combinedImagesFileName, true);

            $pdfMerger = new PDFMerger;
            $pdfMerger->addPDF($filePath . $combinedImagesFileName, 'all');

            foreach ($aryPdfFileNames as $currName) {
                $pdfMerger->addPDF($currName, 'all');
            }

            $combinedFileName = $accession . "_" . $currDate . ".pdf";
            $encodedPdf = $pdfMerger->merge('string', $combinedFileName);

            ob_start();
            echo $encodedPdf;
            $output_so_far = ob_get_contents();
            ob_clean();

            file_put_contents($filePath . $combinedFileName, $output_so_far);

        } else if (count($aryPdfFileNames) > 0) {
            $pdfMerger = new PDFMerger;
            foreach ($aryPdfFileNames as $currName) {
                $pdfMerger->addPDF($currName, 'all');
            }

            $combinedFileName = $accession . "_" . $currDate . ".pdf";
            $encodedPdf = $pdfMerger->merge('string', $combinedFileName);

            ob_start();
            echo $encodedPdf;
            $output_so_far = ob_get_contents();
            ob_clean();

            file_put_contents($filePath . $combinedFileName, $output_so_far);
        } else {
            $pdf = new Imagick($aryFileNames);
            $pdf->setImageFormat('pdf');
            $combinedFileName = $accession . "_" . $currDate . '.pdf';
            $pdf->writeImages($filePath . $combinedFileName, true);
        }





        $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_WEBORDERDOCUMENTS . " (webIdOrders, webAccession, webUserId, fileName, typeName) VALUES (?,?,?,?,?)";
        parent::manipulate($sql, array($idOrders, $accession, $userId, $combinedFileName, "pdf"), array("Conn" => $conn));


    }

    public static function getDocuments($idOrders, $typeName = null, mysqli $conn = null) {

        $typeNameSql = "";
        $aryParams = array($idOrders);
        if ($typeName != null) {
            $typeNameSql = "AND typeName = ?";
            $aryParams[] = $typeName;
        }

        $sql = "SELECT fileName FROM " . self::DB_CSS . "." . self::TBL_WEBORDERDOCUMENTS . " WHERE webIdOrders = ? $typeNameSql";
        $data = parent::select($sql, $aryParams, array("Conn" => $conn));
        $aryFileNames = array();
        if (count($data) > 0) {
            foreach ($data as $row) {
                $aryFileNames[] = $row['fileName'];
            }
        }
        return $aryFileNames;
    }

    public static function createNewOrder($postArray, array $settings = null) {

        $conn = parent::connect(array("ConnectToWeb" => true));

        foreach ($postArray as $key => $value) {
            if (isset($value) && !empty($value) && !is_numeric($value) && !is_array($value)) {
                $postArray[$key] = str_replace("&#65533;", "", $value);
            }
        }



        $entryOrder = new EntryOrder($postArray, true);

        $entryOrder->isAdvancedOrder = false;

        /*echo "<pre>"; print_r($entryOrder); echo "</pre>";
        echo "<pre>"; print_r($postArray); echo "</pre>";*/

        $subscriberId = $entryOrder->subscriberId;
        $patientId = $entryOrder->patientId;
        
        $orderEntryLogType = 1; // add order
        $deletedPatientId = 0;
        $deletedSubscriberId = 0;
        $deletedAdvancedOrderId = null;
        $deletedOrderId = null;
        $overlappingPanelsEnabled = false;

        $ip = null;
        if ($settings != null) {
        	if (array_key_exists("orderEntryLogType", $settings) && is_numeric($settings['orderEntryLogType'])) {
        		$orderEntryLogType = $settings['orderEntryLogType']; // edit order => 2
        	}
        	if (array_key_exists("DeletedPatientId", $settings) && is_numeric($settings['DeletedPatientId'])) {
        		$deletedPatientId = $settings['DeletedPatientId'];
        	}
        	if (array_key_exists("DeletedSubscriberId", $settings) && is_numeric($settings['DeletedSubscriberId'])) {
        		$deletedSubscriberId = $settings['DeletedSubscriberId'];
        	}
            if (array_key_exists("deletedAdvancedOrderId", $settings) && $settings['deletedAdvancedOrderId'] != null && is_numeric($settings['deletedAdvancedOrderId'])) {
                $deletedAdvancedOrderId = $settings['deletedAdvancedOrderId'];
            }
            if (array_key_exists("deletedOrderId", $settings) && $settings['deletedOrderId'] != null && is_numeric($settings['deletedOrderId'])) {
                $deletedOrderId = $settings['deletedOrderId'];
            }
            if (array_key_exists("Ip", $settings) && $settings['Ip'] != null && !empty($settings['Ip'])) {
                $ip = $settings['Ip'];
            }
        }

        if (array_key_exists("overlappingPanelsEnabled", $postArray) && $postArray['overlappingPanelsEnabled'] != null && ($postArray['overlappingPanelsEnabled'] == '1' || $postArray['overlappingPanelsEnabled'] == true)) {
            $overlappingPanelsEnabled = true;
        }

        $accession = $entryOrder->accession;
        if ($orderEntryLogType == 1) {
            $isUnique = self::isUniqueAccession($accession);

            if ($isUnique == false) {
                while (!$isUnique) {
                    $newAccession = self::getNewAccession();
                    if (self::isUniqueAccession($newAccession)) {
                        $entryOrder->accession = $newAccession;
                        $isUnique = true;
                    } else {
                        usleep(500000); // .5 seconds
                    }
                }
            }
        } else if ($orderEntryLogType == 2) {
            $isUnique = self::isUniqueAccession($accession, array("IsEdit" => true, "OrderId" => $entryOrder->idOrders));

            if ($isUnique == false) {
                while (!$isUnique) {
                    $newAccession = self::getNewAccession();
                    if (self::isUniqueAccession($newAccession, array("IsEdit" => true, "OrderId" => $entryOrder->idOrders))) {
                        $entryOrder->accession = $newAccession;
                        $isUnique = true;
                    } else {
                        usleep(500000); // .5 seconds
                    }
                }
            }
        }

        /*echo "5 - Accession Generated: " . date("h:i:s A") . "<br/><br/>";
        $accessionTime = new DateTime();*/



        $aryLog = array(
        	"userId" => $entryOrder->IdUsers,
        	"typeId" => 6,
        	"accession" => $entryOrder->accession,
        	"orderEntryLogType" => $orderEntryLogType,
        	"orderId" => null,
        	"advancedOrderId" => null,
            "accession" => $entryOrder->accession,
        	"advancedOrderOnly" => false,
        	"isNewPatient" => false,
        	"isNewSubscriber" => false,
        	"subscriberChanged" => false,
            "ip" => $ip,
            "adminUserId" => $entryOrder->AdminUserId,
            "adminTypeId" => $entryOrder->AdminTypeId
        );        

        if ((empty($patientId) || ($deletedSubscriberId != null && $subscriberId == null))
            && strtolower($entryOrder->Patient->relationship) == "self") { //  --------- New patient with relationship self
        	// insert subscriber equal to patient

            //echo "<pre>"; print_r($entryOrder); echo "</pre>";
        	$subscriberId = SubscriberDAO::insertSubscriber(new Subscriber(array(
       			"arNo" => SubscriberDAO::getNewArNo(8),
       			"lastName" => $entryOrder->Patient->lastName,
       			"firstName" => $entryOrder->Patient->firstName,
       			"middleName" => $entryOrder->Patient->middleName,
   				"sex" => $entryOrder->Patient->sex,
   				"ssn"  => $entryOrder->Patient->ssn,
   				"dob" => date("m/d/Y", strtotime($entryOrder->Patient->dob)),
   				"addressStreet" => $entryOrder->Patient->addressStreet,
   				"addressStreet2" => $entryOrder->Patient->addressStreet2,
   				"addressCity" => $entryOrder->Patient->addressCity,
   				"addressState" => $entryOrder->Patient->addressState,
   				"addressZip" => $entryOrder->Patient->addressZip,
   				"phone" => $entryOrder->Patient->phone,	
   				"workPhone" => $entryOrder->Patient->workPhone,
   				"insurance" => $entryOrder->insurance,
   				"secondaryInsurance" => $entryOrder->secondaryInsurance,
   				"policyNumber" => $entryOrder->policyNumber,
   				"groupNumber" => $entryOrder->groupNumber,
   				"secondaryPolicyNumber" => $entryOrder->secondaryPolicyNumber,
   				"secondaryGroupNumber" => $entryOrder->secondaryGroupNumber,
   				"medicareNumber" => $entryOrder->medicareNumber,
   				"medicaidNumber" => $entryOrder->medicaidNumber
       		)));
        	
        	$aryLog['isNewSubscriber'] = true;

            //echo "Subscriber 1<br/>";

       	} elseif (
       	    (
       	        empty($subscriberId)
                || (
                    $deletedSubscriberId != null
                    && $entryOrder->Subscriber->idSubscriber == $deletedSubscriberId
                )
            ) && $entryOrder->Patient->relationship != "self") {
            // ----------------------------- New subscriber
            $subscriberId = SubscriberDAO::insertSubscriber($entryOrder->Subscriber);
       		$aryLog['isNewSubscriber'] = true;

       		//echo "Subscriber 2<br/>";
       	} else if (!empty($patientId) && $entryOrder->Patient->relationship == "self") {
            if ($entryOrder->Patient->subscriber != null && !empty($entryOrder->Patient->subscriber)) {
                $subscriberId = $entryOrder->Patient->subscriber;
            } else { // Existing subscriber changing to relationship self
                $subscriberId = null;
                $aryLog['subscriberChanged'] = true;
            }
           // echo "Subscriber 3<br/>";
       	}

        /*$subscriberTime = new DateTime();
        $interval = $accessionTime->diff($subscriberTime);
        $elapsed = $interval->format('%i minutes %S seconds');
        echo "6 - Subscriber Updated: " . date("h:i:s A") . $elapsed . "<br/><br/>";*/
       	
       	$entryOrder->Patient->subscriber = $subscriberId; // set the subscriberId on the patient
       	
       	//if (empty($patientId) || ($deletedPatientId != null && $entryOrder->Patient->idPatients == $deletedPatientId)) { // ------------------------ Insert new patient
        //if ((!array_key_exists("isNewPatient", $postArray) || $postArray['isNewPatient'] == 0)
        if (($entryOrder->IsNewPatient == true || (array_key_exists("isNewPatient", $postArray) && $postArray['isNewPatient'] == 1))
            && (empty($patientId) || ($deletedPatientId != null && $entryOrder->Patient->idPatients == $deletedPatientId))) {
       		$patientId = PatientDAO::insertPatient($entryOrder->Patient);
       		$aryLog['isNewPatient'] = true;

            if (!isset($patientId) || empty($patientId) || $patientId == 0) {
                error_log("Failed to insert new patient for web accession " . $entryOrder->accession);
            }

       		//echo "Patient 1<br/>";
       	}
       	
       	$entryOrder->Patient->idPatients = $patientId; // set the new patientId for the patient
        
        $entryOrder->patientId = $patientId;
        $entryOrder->subscriberId = $subscriberId;


        $idOrders = self::insertOrder($entryOrder, $deletedOrderId);

        if (array_key_exists("docUrl", $_POST)) {
            self::saveDocuments($_POST['docUrl'], $conn, $idOrders, $_POST['accession'], $entryOrder->IdUsers);
        }

        if ($idOrders == null || $idOrders == 0 || $idOrders == "0" || $idOrders == "" || $idOrders == false) {
            // something might be causing the accession to not be unique in the orderSequence table, so just generate a random number

            $characters = '123456789';
            $length = 7;

            $isUnique = false;
            $newAccession = '';
            while (!$isUnique) {
                $newAccession = '';
                for ($p = 0; $p < $length; $p++) {
                    $newAccession .= $characters[mt_rand(0, strlen($characters) - 1)];
                }
                if (self::isUniqueAccession($newAccession)) {
                    $entryOrder->accession = $newAccession;
                    $isUnique = true;
                }
            }

            $idOrders = self::insertOrder($entryOrder, $deletedOrderId);
            $aryLog['orderId'] = $idOrders;
            error_log("Web Order Inserted: " . implode(", ", $aryLog) . ", newAccession: " . $newAccession);

            $aryLog['accession'] = $newAccession;
        } else {
            $aryLog['orderId'] = $idOrders;
        }

        $entryOrder->idOrders = $idOrders;

        // set OrderEmail fields
        if (array_key_exists("orderEntryPatientEmail", $postArray) && $postArray['orderEntryPatientEmail'] == 1
            && array_key_exists("patientEmail", $postArray)) {
            $entryOrder->OrderEmail->orderId = $idOrders;
            $entryOrder->OrderEmail->isWebPatient = $aryLog['isNewPatient'];

            $entryOrder->OrderEmail->patientNumber = $entryOrder->Patient->arNo;
            $entryOrder->OrderEmail->email = $postArray['patientEmail'];

            $idEmails = self::insertOrderEmail($entryOrder->OrderEmail);
            $entryOrder->OrderEmail->idEmails = $idEmails;
        }

        ResultDAO::insertResults($entryOrder, $conn, array("OverlappingPanelsEnabled" => $overlappingPanelsEnabled));


        self::updateESignaturePrint($idOrders, $entryOrder->PrintESignature);

        /*$resultsTime = new DateTime();
        $interval = $patientTime->diff($resultsTime);
        $elapsed = $interval->format('%i minutes %S seconds');
        echo "8 - Results Inserted: " . date("h:i:s A") . $elapsed . "<br/><br/>";*/
        
        DiagnosisDAO::insertDiagnosisLookups($entryOrder, $conn);


        /*echo "9 - Diagnosis Codes Inserted: " . date("h:i:s A") . "<br/><br/>";*/

        if (isset($entryOrder->Prescriptions) && is_array($entryOrder->Prescriptions) && count($entryOrder->Prescriptions) > 0) {
            foreach ($entryOrder->Prescriptions as $prescription) {
                $prescription->orderId = $entryOrder->idOrders;
                $prescription->idPrescriptions = PrescriptionDAO::insertPrescription($prescription, $conn);
            }
        }

        /*$scriptsTime = new DateTime();
        $interval = $resultsTime->diff($scriptsTime);
        $elapsed = $interval->format('%i minutes %S seconds');
        echo "10 - Prescriptions Inserted: " . date("h:i:s A") . $elapsed . "<br/><br/>";*/

        if ($entryOrder->OrderComment->comment != null && trim($entryOrder->OrderComment->comment) != "") {
            $entryOrder->OrderComment->orderId = $entryOrder->idOrders;
            $entryOrder->OrderComment->idorderComment = OrderCommentDAO::insertOrderComment($entryOrder->OrderComment, $conn);
        }

        /*$commentsTime = new DateTime();
        $interval = $scriptsTime->diff($commentsTime);
        $elapsed = $interval->format('%i minutes %S seconds');
        echo "11 - Order Comment Inserted: " . date("h:i:s A") . $elapsed . "<br/><br/>";*/

        if (array_key_exists("updatePatientScripts", $postArray) && $postArray['updatePatientScripts'] == "1" && array_key_exists("savedPatientScripts", $postArray)) {
            // add the new
            if (is_array($postArray['prescribedDrugs'])) {
                foreach ($postArray['prescribedDrugs'] as $drugId) {
                    if (!is_array($postArray['savedPatientScripts']) || !in_array($drugId, $postArray['savedPatientScripts'])) {
                        // insert script
                        PatientDAO::insertPatientPrescription($entryOrder->patientId, $drugId, $conn);
                    }
                }
            }


            //remove the old
            if (is_array($postArray['savedPatientScripts'])) {
                foreach ($postArray['savedPatientScripts'] as $drugId) {
                    if (!is_array($postArray['prescribedDrugs']) || !in_array($drugId, $postArray['prescribedDrugs'])) {
                        // delete
                        PatientDAO::deletePatientPrescription($entryOrder->patientId, $drugId, $conn);
                    }
                }
            }
        }

        self::logEntryOrder($aryLog);

        /*$loggedTime = new DateTime();
        $interval = $commentsTime->diff($loggedTime);
        $elapsed = $interval->format('%i minutes %S seconds');
        echo "12 - Order Logged: " . date("h:i:s A") . $elapsed . "<br/><br/>";*/

//         echo "<pre>";
//         print_r($entryOrder);
//         echo "</pre><hr/>";
//         echo "<pre>";
//         print_r($postArray);
//         echo "</pre><hr/>";
        
        return $entryOrder;
    }

    private static function insertOrderEmail(OrderEmail $orderEmail) {
        $sql = "INSERT INTO " . self::DB_CSS_WEB . "." . self::TBL_ORDEREMAIL . " (orderId, patientNumber, email, isWebPatient) VALUES (?,?,?,?)";
        $isWebPatient = 0;
        if ($orderEmail->isWebPatient) {
            $isWebPatient = 1;
        }
        $aryInput = array($orderEmail->orderId, $orderEmail->patientNumber, $orderEmail->email, $isWebPatient);
        $idEmails = parent::manipulate($sql, $aryInput, array("LastInsertId" => true));
        return $idEmails;
    }

    private static function updateESignaturePrint($idOrders, $printESignature) {
        $sql = "DELETE FROM " . self::DB_CSS_WEB . "." . self::TBL_PRINTESIGONREQ . " WHERE orderId = ?";
        parent::manipulate($sql, array($idOrders));

        if ($printESignature == true) {
            $sql = "INSERT INTO " . self::DB_CSS_WEB . "." . self::TBL_PRINTESIGONREQ . " VALUES (?)";
            parent::manipulate($sql, array($idOrders));
        }
    }

    //public static function logEntryOrder(EntryOrder $entryOrder, array $settings = null) {
    public static function logEntryOrder(array $logFields) {

        $userId = $logFields['userId'];
        if (isset($logFields['adminUserId']) && !empty($logFields['adminUserId']) && isset($logFields['adminTypeId']) && $logFields['adminTypeId'] == 7) {
            $userId = $logFields['adminUserId'];
        }

        if ($logFields['orderEntryLogType'] == 1) { // web order added
            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_ORDERRECEIPTLOG . " (webAccession, webUser) VALUES (?, ?)";
            $aryReceiptLogInput = array($logFields['accession']);
            $aryReceiptLogInput[] = $userId;
            parent::manipulate($sql, $aryReceiptLogInput);
        }

        if (array_key_exists("ip", $logFields) && $logFields['ip'] != null && !empty($logFields['ip'])) {
            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_LOG . "(userId, typeId, ip) VALUES (?, ?, ?)";
            $idLogs = parent::manipulate($sql, array($userId, $logFields['typeId'], $logFields['ip']), array("LastInsertId" => true));
        } else {
            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_LOG . "(userId, typeId) VALUES (?, ?)";
            $idLogs = parent::manipulate($sql, array($userId, $logFields['typeId']), array("LastInsertId" => true));
        }
        $aryInput = array($idLogs);
                
        $aryInput[] = $logFields['orderEntryLogType']; // orderEntryLogType
        $aryInput[] = $logFields['orderId']; // orderId
        $aryInput[] = $logFields['advancedOrderId']; // advancedOrderId
        $aryInput[] = $logFields['accession'];
        $aryInput[] = $logFields['advancedOrderOnly'];


        //$aryInput[] = $logFields['isNewPatient']; // isNewPatient
        //$aryInput[] = $logFields['isNewSubscriber']; // isNewSubscriber

        if ($logFields['isNewPatient'] == 1 || $logFields['isNewPatient'] == true) {
            $aryInput[] = 1;
        } else {
            $aryInput[] = 0;
        }

        if ($logFields['isNewSubscriber'] == 1 || $logFields['isNewSubscriber'] == true) {
            $aryInput[] = 1;
        } else {
            $aryInput[] = 0;
        }

        $aryInput[] = $logFields['subscriberChanged']; // subscriberChanged


            
        $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_LOGORDERENTRY . " (logId, orderEntryLogType, orderId, advancedOrderId, accession, advancedOrderOnly, isNewPatient, isNewSubscriber, subscriberChanged) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $idOrderEntryLogs = parent::manipulate($sql, $aryInput, array("LastInsertId" => true));
    }
    
    /**
     * Most of this function involves finding out if the patient/subscriber is in webcss schema and deleting them.
     * Then it deletes the rest of the order, and finally passes the $postArray over to the createNewOrder function
     */
    public static function editOrder(array $postArray, array $settings = null) {
    	
    	$arySettings = array("orderEntryLogType" => 2);
        if ($settings != null && array_key_exists("Ip", $settings) && $settings['Ip'] != null && !empty($settings['Ip'])) {
            $arySettings['Ip'] = $settings['Ip'];
        }
        $entryOrder = new EntryOrder($postArray, true);

		$idOrders = $entryOrder->idOrders;


        // orderType == 4 == advanced order with phlebotomy == has idAdvancedOrders and idOrders
        //orderType == 3 == advanced order only == has idAdvancedOrders only
        // orderType == 1 == regular order == has idOrders only
        if ($postArray['orderType'] == 3) {
            $logData = ResultLogDAO::getOrderLogData($idOrders, 3);
        } else {
            $logData = ResultLogDAO::getOrderLogData($idOrders);
        }

        /*echo "2 - Order Log Date Retrieved: " . date("h:i:s A") . "<br/><br/>";*/

		$isNewPatient = $logData[0]['isNewPatient'];
		$isNewSubscriber = $logData[0]['isNewSubscriber'];
		$advancedOrderOnly = $logData[0]['advancedOrderOnly'];
        $arySettings['deletedAdvancedOrderId'] = $logData[0]['advancedOrderId'];
        $arySettings['deletedOrderId'] = $logData[0]['orderId'];

		if ($isNewPatient || $isNewSubscriber) { // there is someone to delete

			if (!$advancedOrderOnly) {
				$sql = "SELECT o.patientId, o.subscriberId FROM " . self::TBL_ORDERS . " o WHERE o.idOrders = ?";
			} else { // get the ids from the advancedOrders table
				$sql = "SELECT o.patientId, o.subscriberId FROM " . self::TBL_ADVANCEDORDERS . " o WHERE o.idAdvancedOrders = ?";
			}

			$data = parent::select($sql, array($idOrders), array("ConnectToWeb" => true));
            //echo "<pre>"; print_r($data); echo "</pre>";
            //echo "<pre>"; print_r($postArray); echo "</pre>";
			$patientId = $data[0]['patientId'];
			$subscriberId = $data[0]['subscriberId'];
			if ($isNewPatient) { //&& $patientId != $postArray['idPatients']) {  // do not delete the patient if it was left unchanged

//                $entryOrder->patientId = null;
//                $entryOrder->subscriberId = null;
//                $entryOrder->Patient->idPatients = null;
//                if (isset($entryOrder->Subscriber)) {
//                    $entryOrder->Subscriber->idSubscriber = null;
//                }
                //echo "<pre>"; print_r($entryOrder); echo "</pre>";
				$patientDeleted = PatientDAO::deletePatient($patientId);
				if ($patientDeleted > 0) {
                    $arySettings['DeletedPatientId'] = $patientId;
				}
			}

			if ($isNewSubscriber) { // do not delete the subscriber if it was left unchanged
				$subscriberDeleted = SubscriberDAO::deleteSubscriber($subscriberId);
				if ($subscriberDeleted > 0) {
                    $arySettings['DeletedSubscriberId'] = $subscriberId;
				}
			}

            /*echo "3 - New Patient/Subscriber Data Deleted: " . date("h:i:s A") . "<br/><br/>";*/
		}


        // $postArray['type'] tells us if the order was advanced/phlebotomy/regular before changes were made
        $entryOrderDeleted = self::deleteOrderData($entryOrder, array("orderType" => $postArray['orderType'], "idAdvancedOrders" => $postArray['advancedId']));

        /*echo "4 - Order Data Deleted " . date("h:i:s A") . "<br/><br/>";*/

        if ($entryOrderDeleted) {
            $arySettings['UpdateOrder'] = true;
            return self::createNewOrder($postArray, $arySettings);
        } else {
            return 0;
        }
    }

    private static function deleteOrderData(EntryOrder $entryOrder, array $settings) {
        /*$orderDeleted = self::deleteEntryOrder($entryOrder, $settings);
        if ($orderDeleted) {
            $resultsDeleted = ResultDAO::deleteResults($entryOrder, $settings);        
            $phlebotomyDeleted = PhlebotomyDAO::deletePhlebotomy(array("idOrders" => $entryOrder->idOrders));        
            $scriptsDeleted = PrescriptionDAO::deletePrescriptions($entryOrder->idOrders);
            $commentDeleted = OrderCommentDAO::deleteOrderComment($entryOrder->idOrders);
            $codesDeleted = DiagnosisDAO::deleteOrderCodes($entryOrder->idOrders);
            return true;
        } else {
            return false;
        }*/
        $resultsDeleted = ResultDAO::deleteResults($entryOrder, $settings);
        $phlebotomyDeleted = PhlebotomyDAO::deletePhlebotomy(array("idOrders" => $entryOrder->idOrders));
        $scriptsDeleted = PrescriptionDAO::deletePrescriptions($entryOrder->idOrders);
        $commentDeleted = OrderCommentDAO::deleteOrderComment($entryOrder->idOrders);
        $codesDeleted = DiagnosisDAO::deleteOrderCodes($entryOrder->idOrders);
        $orderDeleted = self::deleteEntryOrder($entryOrder, $settings);
        if ($orderDeleted) {
            return true;
        } else {
            return false;
        }

    }
    
    private static function orderExists($idOrders, array $settings = null) {
        $isAdvancedOrder = false;
        if ($settings != null) {
            if (array_key_exists("IsAdvancedOrder", $settings) && $settings['IsAdvancedOrder'] == true) {
                $isAdvancedOrder = true;
            }
        }
        
        if ($isAdvancedOrder) {
            $sql = "SELECT idAdvancedOrders FROM " . self::TBL_ADVANCEDORDERS . " WHERE idAdvancedOrders = ?";
            $data = parent::select($sql, array($idOrders), array("ConnectToWeb" => true));
        } else {
            $sql = "SELECT idOrders FROM " . self::TBL_ORDERS . " WHERE idOrders = ?";
            $data = parent::select($sql, array($idOrders), array("ConnectToWeb" => true));
        }
        if (count($data) == 0) {
            return false;
        }
        return true;
    }
    
    private static function updateOrder($entryOrder) {
        $orderFields = $entryOrder->Data;
        /*
            [idOrders] => 54
            [doctorId] => 405
            [clientId] => 1
            [accession] => i06qXp
            [locationId] => 0
            [orderDate] => 2014-01-22 05:05:10
            [specimenDate] => 2014-01-01 08:14:00
            [patientId] => 3
            [isAdvancedOrder] => 
            [phlebotomyId] => 
            [insurance] => 1097
            [secondaryInsurance] => 0
            [policyNumber] => 1234567
            [groupNumber] => 
            [secondaryPolicyNumber] => 
            [secondaryGroupNumber] => 
            [medicareNumber] => 
            [medicaidNumber] => 
            [reportType] => 
            [requisition] => 
            [billOnly] => 0
            [active] => 
            [hold] => 0
            [stage] => 
            [holdComment] => 
            [resultComment] => 
            [internalComment] => 
            [firstName] => 
            [lastName] => 
            [clientNo] => 
            [number] => 
            [IsInvalidated] => 
            [IsAbnormal] =>  
        */
        if ($entryOrder->IsAdvancedOrder) {
            $sql = "
                UPDATE  " . self::TBL_ADVANCEDORDERS . "
                SET     doctorId = ?, clientId = ?, accession = ?, locationId = ?, orderDate = ?, specimenDate = ?, patientId = ?, insurance = ?, secondaryInsurance = ?,
                        policyNumber = ?, groupNumber = ?, secondaryPolicyNumber = ?, secondaryGroupNumber = ?, medicareNumber = ?, reportType = ?, requisition = ?,
                        room = ?, bed = ?
                WHERE   idAdvancedOrders = ?";
            $qryInput = array (
                $orderFields['doctorId'], $orderFields['clientId'], $orderFields['accession'], $orderFields['locationId'], date("Y-m-d h:i:s"), $orderFields['specimenDate'], $orderFields['patientId'], 
                $orderFields['insurance'], $orderFields['secondaryInsurance'], $orderFields['policyNumber'], $orderFields['groupNumber'], $orderFields['secondaryPolicyNumber'], 
                $orderFields['secondaryGroupNumber'], $orderFields['medicareNumber'], $orderFields['reportType'], $orderFields['requisition'], 
                $orderFields['room'], $orderFields['bed'], $orderFields['idOrders'],
                
            );
        } else {
            $sql = "
                UPDATE  " . self::TBL_ORDERS . "
                SET     doctorId = ?, clientId = ?, accession = ?, locationId = ?, orderDate = ?, specimenDate = ?, patientId = ?, insurance = ?, secondaryInsurance = ?,
                        policyNumber = ?, groupNumber = ?, secondaryPolicyNumber = ?, secondaryGroupNumber = ?, medicareNumber = ?, reportType = ?, requisition = ?,
                        room = ?, bed = ?
                WHERE   idOrders = ?";
            $qryInput = array (
                $orderFields['doctorId'], $orderFields['clientId'], $orderFields['accession'], $orderFields['locationId'], date("Y-m-d h:i:s"), $orderFields['specimenDate'], $orderFields['patientId'], 
                $orderFields['insurance'], $orderFields['secondaryInsurance'], $orderFields['policyNumber'], $orderFields['groupNumber'], $orderFields['secondaryPolicyNumber'], 
                $orderFields['secondaryGroupNumber'], $orderFields['medicareNumber'], $orderFields['reportType'], $orderFields['requisition'],
                $orderFields['room'], $orderFields['bed'], $orderFields['idOrders']
            );
        }
        
        parent::manipulate($sql, $qryInput, array("ConnectToWeb" => true));
    }

    private static function deleteEntryOrder(EntryOrder $entryOrder, array $settings) {
        //if ($entryOrder->isAdvancedOrder && isset($entryOrder->Phlebotomy)) {
        if ($settings['orderType'] == 4) {
            $qryInput = array();
            if (array_key_exists("idAdvancedOrders", $settings) && !empty($settings['idAdvancedOrders'])) {
                // settings['idAdvancedOrders'] should always have the idAdvancedOrders, but check and use the Phlebotomy as a fallback just in case.
                $qryInput[] = $settings['idAdvancedOrders'];
            } else {
                $qryInput[] = $entryOrder->Phlebotomy->idAdvancedOrder;
            }
            // delete from orders & advancedOrders tables
            $sql = "DELETE FROM " . self::TBL_ADVANCEDORDERS . " WHERE idAdvancedOrders = ?";
            parent::manipulate($sql, $qryInput, array("ConnectToWeb" => true));
           
            $sql = "DELETE FROM " . self::TBL_ORDERS . " WHERE idOrders = ?";
            parent::manipulate($sql, array($entryOrder->idOrders), array("ConnectToWeb" => true));
        //} else if ($entryOrder->isAdvancedOrder) { // Since there is no phlebotomy to lookup, idOrders is the idAdvancedOrders
        } else if ($settings['orderType'] == 3) {
            // delete from advancedOrders table only
            $sql = "DELETE FROM " . self::TBL_ADVANCEDORDERS . " WHERE idAdvancedOrders = ?";
            parent::manipulate($sql, array($entryOrder->idOrders), array("ConnectToWeb" => true));
            
        //} else {
        } else if ($settings['orderType'] == 1) {
            $sql = "DELETE FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDEREMAIL . " WHERE orderId = ?";
            parent::manipulate($sql, array($entryOrder->idOrders));
            $sql = "DELETE FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " WHERE idOrders = ?";
            parent::manipulate($sql, array($entryOrder->idOrders));
        } else {
            return false;
        }
        
        return true;        
    }



    private static function insertOrder(EntryOrder $entryOrder, $deletedOrderId = null) {

        $active = 1;
        /*if ($entryOrder->active == 0 || $entryOrder->active = false) {
            $active = 0;
        }*/

        $sqlInsert = "INSERT INTO " . self::TBL_ORDERS . " (";
        $sqlValues = "VALUES (";
        $input = array(
            $entryOrder->doctorId, $entryOrder->clientId, $entryOrder->accession, $entryOrder->locationId, $entryOrder->orderDate,
            $entryOrder->specimenDate, $entryOrder->Patient->idPatients, $entryOrder->subscriberId, $entryOrder->insurance, $entryOrder->secondaryInsurance,
            $entryOrder->policyNumber, $entryOrder->groupNumber, $entryOrder->secondaryPolicyNumber,
            $entryOrder->secondaryGroupNumber, $entryOrder->medicareNumber, $entryOrder->medicaidNumber, $entryOrder->reportType,
            null, $entryOrder->internalComment, $entryOrder->room, $entryOrder->bed, $entryOrder->isAdvancedOrder, $active, $entryOrder->isFasting
        );
        if (isset($deletedOrderId) && !empty($deletedOrderId)) {
            $sqlInsert .= "idOrders, ";
            $sqlValues .= "?, ";
            $input = array(
                $deletedOrderId, $entryOrder->doctorId, $entryOrder->clientId, $entryOrder->accession, $entryOrder->locationId, $entryOrder->orderDate,
                $entryOrder->specimenDate, $entryOrder->Patient->idPatients, $entryOrder->subscriberId, $entryOrder->insurance, $entryOrder->secondaryInsurance,
                $entryOrder->policyNumber, $entryOrder->groupNumber, $entryOrder->secondaryPolicyNumber,
                $entryOrder->secondaryGroupNumber, $entryOrder->medicareNumber, $entryOrder->medicaidNumber, $entryOrder->reportType,
                null, $entryOrder->internalComment, $entryOrder->room, $entryOrder->bed, $entryOrder->isAdvancedOrder, $active, $entryOrder->isFasting
            );
        }
        $sqlInsert .= "doctorId, clientId, accession, locationId, orderDate, specimenDate, patientId, subscriberId, insurance, secondaryInsurance,
            policyNumber, groupNumber, secondaryPolicyNumber, secondaryGroupNumber, medicareNumber, medicaidNumber, 
            reportType, requisition, internalComment, room, bed, isAdvancedOrder, active, isFasting
        ) ";
        $sqlValues .= "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ";

        $sql = $sqlInsert . $sqlValues;

        if (!isset($entryOrder->Patient->idPatients) || empty($entryOrder->Patient->idPatients) || $entryOrder->Patient->idPatients == 0 || $entryOrder->Patient->idPatients == null || $entryOrder->Patient->idPatients == '') {
            error_log($sql);
            error_log(implode(",", $input));
        }

        $orderId = parent::manipulate($sql, $input, array("ConnectToWeb" => true, "LastInsertId" => true));

        return $orderId;
    }
    private static function insertAdvancedOrder(EntryOrder $entryOrder, $deletedAdvancedOrderId = null) {
        $sqlInsert = "INSERT INTO " . self::TBL_ADVANCEDORDERS . " ( ";
        $sqlValues = " VALUES ( ";
        $input = array(
            $entryOrder->doctorId, $entryOrder->clientId, $entryOrder->accession, $entryOrder->locationId, $entryOrder->orderDate,
            $entryOrder->specimenDate, $entryOrder->Patient->idPatients, $entryOrder->insurance, $entryOrder->secondaryInsurance,
            $entryOrder->policyNumber, $entryOrder->groupNumber, $entryOrder->secondaryPolicyNumber,
            $entryOrder->secondaryGroupNumber, $entryOrder->medicareNumber, $entryOrder->medicaidNumber, $entryOrder->reportType,
            null, $entryOrder->internalComment, $entryOrder->room, $entryOrder->bed
        );
        if (isset($deletedAdvancedOrderId) && !empty($deletedAdvancedOrderId)) {
            $sqlInsert .= "idAdvancedOrders, ";
            $sqlValues .= "?, ";
            $input = array(
                $deletedAdvancedOrderId, $entryOrder->doctorId, $entryOrder->clientId, $entryOrder->accession, $entryOrder->locationId, $entryOrder->orderDate,
                $entryOrder->specimenDate, $entryOrder->Patient->idPatients, $entryOrder->insurance, $entryOrder->secondaryInsurance,
                $entryOrder->policyNumber, $entryOrder->groupNumber, $entryOrder->secondaryPolicyNumber,
                $entryOrder->secondaryGroupNumber, $entryOrder->medicareNumber, $entryOrder->medicaidNumber, $entryOrder->reportType,
                null, $entryOrder->internalComment, $entryOrder->room, $entryOrder->bed
            );
        }

        $sqlInsert .= " doctorId, clientId, accession, locationId, orderDate, specimenDate, patientId, insurance, secondaryInsurance,
            policyNumber, groupNumber, secondaryPolicyNumber, secondaryGroupNumber, medicareNumber, medicaidNumber, 
            reportType, requisition, internalComment, room, bed
        )";
        $sqlValues .= " ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ";

        $sql = $sqlInsert . $sqlValues;
        return parent::manipulate($sql, $input, array("ConnectToWeb" => true, "LastInsertId" => true));
    }
 
    /**
     * Used for fetching entry orders by the OrderEntryClient to edit them, 
     * to generate requisition forms, and to proccess order new/existing orders
     * @param array $input - used for query parameters
     * @param array $settings - used for telling the function what sort of information to include in the entry order.
     * @return \EntryOrder|boolean
     */
    public static function getEntryOrder(array $input, array $settings = null) {
        $includeClientInfo = false;
        $includeDoctorInfo = false;
        $includeInsuranceInfo = false;
        $includeCodeNames = false;
        $advancedOrderOnly = false;
        $type = 1;
        $isReceipted = false;
        $hasPatientEmail = false;
        if ($settings != null) {
            if (array_key_exists("IncludeClientInfo", $settings) && $settings['IncludeClientInfo'] == true) {
                $includeClientInfo = true;
            }
            if (array_key_exists("IncludeDoctorInfo", $settings) && $settings['IncludeDoctorInfo'] == true) {
                $includeDoctorInfo = true;
            }
            if (array_key_exists("IncludeInsuranceInfo", $settings) && $settings['IncludeInsuranceInfo'] == true) {
                $includeInsuranceInfo = true;
            }
            if (array_key_exists("IncludeCodeNames", $settings) && $settings['IncludeCodeNames'] == true) {
                $includeCodeNames = true;
            }
            if (array_key_exists("type", $settings)) {
                if ($settings['type'] == 3) {
                    $advancedOrderOnly = true;
                }
                $type = $settings['type'];
            }
            if (array_key_exists("isReceipted", $input)) {
                $isReceipted = $input['isReceipted'];
            }
            if (array_key_exists("OrderEntryPatientEmail", $settings) && $settings['OrderEntryPatientEmail'] == true) {
                $hasPatientEmail = true;
            }
        }

        if ($isReceipted == true) { // this order has been receipted and a user wants to view the req, so select from the css schema instead of the web schema
            $conn = DataConnect::getConn(array("ConnectToWeb" => false));

            $sql = "
            SELECT  o.idOrders, o.accession, o.locationId, o.clientId, o.doctorId, o.patientId, o.insurance, o.subscriberId,
                    o.orderDate, o.specimenDate, o.insurance, o.secondaryInsurance, o.policyNumber, o.groupNumber,
                    o.secondaryPolicyNumber, o.secondaryGroupNumber, o.medicareNumber, o.medicaidNumber, o.reportType, o.requisition,
                    o.room, o.bed, o.isAdvancedOrder, rl.`receiptedDate`,
                    if (rl.`idOrders` IS NULL, 0, 1) AS `IsReceipted`,
                    ol.comment, ol.idorderComment,
                    CASE WHEN es.orderId IS NOT NULL THEN true ELSE false END AS `printESignature`,
                    o.isFasting
            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_ORDERRECEIPTLOG . " rl ON o.idOrders = rl.idOrders
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_ORDERCOMMENT . " ol ON o.idOrders = ol.orderId
            LEFT JOIN " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o2 ON rl.webAccession = o2.accession
            LEFT JOIN " . self::DB_CSS_WEB . "." . self::TBL_PRINTESIGONREQ . " es ON o2.idOrders = es.orderId
            WHERE o.idOrders = ?
            ORDER BY o.orderDate DESC";

        } else if ($advancedOrderOnly) {
            $conn = DataConnect::getConn(array("ConnectToWeb" => true));

            $sql = "
            SELECT  o.idAdvancedOrders AS 'idOrders', o.accession, o.locationId, o.clientId, o.doctorId, o.patientId, o.insurance, 
                    o.subscriberId, o.orderDate, o.specimenDate, o.insurance, o.secondaryInsurance, o.policyNumber, o.groupNumber, 
                    o.secondaryPolicyNumber, o.secondaryGroupNumber, o.medicareNumber, o.medicaidNumber, o.reportType, o.requisition,
                    o.room, o.bed, rl.`receiptedDate`,
                    if (rl.`idOrders` IS NULL, 0, 1) AS `IsReceipted`,
                    ol.comment, ol.idorderComment,
                    o.isFasting
            FROM " . self::DB_CSS_WEB . "." . self::TBL_ADVANCEDORDERS . " o
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_ORDERRECEIPTLOG . " rl ON o.accession = rl.webAccession
            LEFT JOIN " . self::DB_CSS_WEB . "." . self::TBL_ORDERCOMMENT . " ol ON o.idOrders = ol.orderId
            WHERE o.idAdvancedOrders = ?
            ORDER BY o.orderDate DESC";

            $whereOrderId = "advancedOrderId";
            
        } else {
            $conn = DataConnect::getConn(array("ConnectToWeb" => true));

            $sql = "
            SELECT  o.idOrders, o.accession, o.locationId, o.clientId, o.doctorId, o.patientId, o.insurance, o.subscriberId, 
                    o.orderDate, o.specimenDate, o.insurance, o.secondaryInsurance, o.policyNumber, o.groupNumber, 
                    o.secondaryPolicyNumber, o.secondaryGroupNumber, o.medicareNumber, o.medicaidNumber, o.reportType, o.requisition,
                    o.room, o.bed, o.isAdvancedOrder, rl.`receiptedDate`,
                    if (rl.`idOrders` IS NULL, 0, 1) AS `IsReceipted`,
                    ol.comment, ol.idorderComment,
                    CASE WHEN es.orderId IS NOT NULL THEN true ELSE false END AS `printESignature`,
                    o.isFasting
            FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_ORDERRECEIPTLOG . " rl ON o.accession = rl.webAccession
            LEFT JOIN " . self::DB_CSS_WEB . "." . self::TBL_ORDERCOMMENT . " ol ON o.idOrders = ol.orderId
            LEFT JOIN " . self::DB_CSS_WEB . "." . self::TBL_PRINTESIGONREQ . " es ON o.idOrders = es.orderId
            WHERE o.idOrders = ?
            ORDER BY o.orderDate DESC";

            $whereOrderId = "orderId";            
        }
        //echo "<pre>$sql</pre>" . $input['idOrders'];
        $data = parent::select($sql, array($input['idOrders']), array("Conn" => $conn));

        $logData = array();
        if (!$isReceipted) {
            $logDataSql = "
            SELECT isNewPatient, isNewSubscriber
            FROM " . self::DB_CSS . "." . self::TBL_LOGORDERENTRY . " l
        	WHERE " . $whereOrderId . " = ?
        	ORDER BY idOrderEntryLog DESC
        	LIMIT 1";
            //echo "<pre>$logDataSql</pre>" . $input['idOrders'];
            $logData = parent::select(
                $logDataSql,
                array($input['idOrders']),
                array("Conn" => $conn)
            );

            $isNewPatient = $logData[0]['isNewPatient'];
            $isNewSubscriber = $logData[0]['isNewSubscriber'];
        } else {
            // since this order was receipted, just assume its not a new patient/subscriber because they would have been inserted into the css schema by now anyway
            $isNewPatient = false;
            $isNewSubscriber = false;
        }


        if (count($data) > 0 && (count($logData) > 0 || $isReceipted == true)) {
            $entryOrder = new EntryOrder($data[0]);

            if ($hasPatientEmail) {
                $emailSql = "SELECT oe.idEmails, oe.orderId, oe.patientNumber, oe.email, oe.isWebPatient, oe.dateCreated, oe.isActive
                FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDEREMAIL . " oe
                WHERE oe.orderId = ?";
                $emailData = parent::select($emailSql, array($entryOrder->idOrders), array("Conn" => $conn));
                if (count($emailData) > 0) {
                    $entryOrder->OrderEmail = new OrderEmail($emailData[0]);
                }
            }


            //echo "<pre>"; print_r($entryOrder); echo "</pre>";

            // get patient
            $patientSettings = array("InputDateFormat" => "Y-m-d H:i:s", "OutputDateFormat" => "m/d/Y");
            if ($isNewPatient) {
            	$patientSettings['ConnectToWeb'] = true;
                $entryOrder->IsNewPatient = true;
            	$patient = PatientDAO::getPatient(
            		array("idPatients" => $entryOrder->patientId),
            		$patientSettings
            	);
            } else {
            	$patient = PatientDAO::getPatient(
            		array("idPatients" => $entryOrder->patientId),
            		$patientSettings
            	);
            }                 
            $entryOrder->Patient = $patient;

            if (!empty($entryOrder->subscriberId) && $entryOrder->Patient->relationship != "self") { // there is some subscriber for this order
            	
            	$subscriberSettings = array("InputDateFormat" => "Y-m-d H:i:s", "OutputDateFormat" => "m/d/Y");
            	if ($isNewSubscriber) {
            		$subscriberSettings['ConnectToWeb'] = true;
                    $entryOrder->IsNewSubscriber = true;
            		$subscriber = SubscriberDAO::getSubscriber(
            				array("idSubscriber" => $entryOrder->subscriberId),
            				$subscriberSettings
            		);
            	} else {
            		$subscriber = SubscriberDAO::getSubscriber(
            				array("idSubscriber" => $entryOrder->subscriberId),
            				$subscriberSettings
            		);
            	}
                $entryOrder->Subscriber = $subscriber;
                
            } else {

                if ($isNewSubscriber) {
                    $entryOrder->IsNewSubscriber = true;
                }

            	$entryOrder->Patient->relationship = "self";
            }
            
            // set results
            $results = ResultDAO::getResults(
                array("orderId" => $entryOrder->idOrders), 
                array("AdvancedOrderOnly" => $advancedOrderOnly, "Conn" => $conn)
            );
            $entryOrder->Results = $results;
          
            // set prescriptions
            $prescriptions = PrescriptionDAO::getPrescriptions(array("idOrders" => $entryOrder->idOrders), array("Conn" => $conn));
            $entryOrder->Prescriptions = $prescriptions;
            
            // set diagnosis codes
            $codes = DiagnosisDAO::getOrderDiagnosisCodes($entryOrder->idOrders, array("Conn" => $conn));
            $entryOrder->DiagnosisCodes = $codes;

            // set order comment
            $orderComment = OrderCommentDAO::getOrderComment($entryOrder->idOrders, array("Conn" => $conn));
            if (!is_bool($orderComment)) {
                $entryOrder->OrderComment = $orderComment;
            }
            
            if ($includeClientInfo) {
                $client = ClientDAO::getClient(array("idClients" => $entryOrder->clientId), array("Conn" => $conn));
                $entryOrder->Client = $client;

            }
            
            if ($includeDoctorInfo) {
                $doctor = DoctorDAO::getDoctor(array("iddoctors" => $entryOrder->doctorId), array("Conn" => $conn));
                $entryOrder->Doctor = $doctor;
            }
            if ($includeInsuranceInfo) {
                $insurance = InsuranceDAO::getInsurance(array("idinsurances" => $entryOrder->insurance), array("Conn" => $conn));
                if (!is_bool($insurance)) {
                    $entryOrder->Insurance = $insurance;
                }

                if ($entryOrder->secondaryInsurance != null && !empty($entryOrder->secondaryInsurance)) {
                    $secondaryInsurance = InsuranceDAO::getInsurance(array("idinsurances" => $entryOrder->secondaryInsurance), array("Conn" => $conn));
                    if (!is_bool($secondaryInsurance)) {
                        $entryOrder->SecondaryInsurance = $secondaryInsurance;
                    }
                }

            }       
            
            // 4 => Advanced Order & Phlebotomy, 2 => Phlebotomy order
            if ($type == 4 || $type == 2) { // ---------------------------------- Get Phlebotomy Information
                $phleb = PhlebotomyDAO::getPhlebotomy(array("idOrders" => $entryOrder->idOrders), array("Conn" => $conn));
                $entryOrder->Phlebotomy = $phleb;
            }

            return $entryOrder;
        }        
        return false;
    }

    public static function getOrderInfo($idOrders, mysqli $conn = null) {
        $aryInfo = array();

        $sql = "
            SELECT  lo.accession, lo.orderId, lo.isNewPatient, lo.isNewSubscriber, lo.subscriberChanged,
                    o.doctorId,
                    CASE WHEN er.orderId IS NOT NULL THEN true ELSE false END AS `PrintESigOnReq`
            FROM " . self::DB_CSS . "." . self::TBL_LOGORDERENTRY . " lo
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_LOGORDERENTRY . " lo2 ON lo.orderId = lo2.orderId AND lo2.orderEntryLogType != 3 AND lo.idOrderEntryLog < lo2.idOrderEntryLog
	        LEFT JOIN " . self::DB_CSS . "." . self::TBL_LOGORDERENTRY . " lo3 ON lo.orderId = lo3.orderId AND lo3.orderEntryLogType != 3 AND lo.idOrderEntryLog < lo3.idOrderEntryLog AND lo3.idOrderEntryLog < lo2.idOrderEntryLog
            LEFT JOIN " . self::DB_CSS_WEB . "." . self::TBL_PRINTESIGONREQ . " er ON lo.orderId = er.orderId
            LEFT JOIN " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o ON lo.orderId = o.idOrders
            WHERE   lo.orderId = ?
                    AND lo2.idOrderEntryLog IS NULL
			        AND lo.orderEntryLogType != 3
        ";
        $data = parent::select($sql, array($idOrders), array("Conn" => $conn));

        if (count($data) > 0) {
            $aryInfo = $data[0];
        }

        return $aryInfo;
    }
    
    public static function getReceiptedOrderInfo($accession) {
        $sql = "SELECT rl.idWebOrderReceiptLog, rl.webAccession, rl.webUser, rl.webCreated, rl.idOrders, rl.user, rl.receiptedDate
                FROM " . self::DB_CSS . "." . self::TBL_ORDERRECEIPTLOG . " rl
                WHERE rl.webAccession = ?";
        $data = parent::select($sql, array($accession));
        
        return $data;
    }

    public static function getNewAccession() {
        $sql = "SELECT accession FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDERSEQUENCE;
        $data = parent::select($sql, null, array("ConnectToWeb" => true));

        if (count($data) > 0) {
            $accession = $data[0]['accession'];

            if (self::isUniqueAccession($accession)) { // good
                $sql = "UPDATE " . self::DB_CSS_WEB . "." . self::TBL_ORDERSEQUENCE . " SET accession = ? WHERE idOrderSequence = 1";
                parent::manipulate($sql, array($accession + 1), array("ConnectToWeb" => true));

            } else { // bad

                // increment the orderSequence table until the accession does not exist in the WebOrderReceiptLog nor the web orders table
                while (!self::isUniqueAccession($accession)) {
                    $accession = $accession + 1;
                    $sql = "UPDATE " . self::DB_CSS_WEB . "." . self::TBL_ORDERSEQUENCE . " SET accession = ? WHERE idOrderSequence = 1";
                    parent::manipulate($sql, array($accession), array("ConnectToWeb" => true));
                    usleep(500000); // .5 seconds
                }

                /*
                $sql = "
                    SELECT MAX(CONVERT(SUBSTRING_INDEX(o.accession,'-',-1), UNSIGNED INT)) + 1 AS `maxAccession`
                    FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o
                ";
                $highestAccession = parent::select($sql);
                $accession = $highestAccession[0]['maxAccession'];
                */

                // Increment the order sequence table for the next order
                $sql = "UPDATE " . self::DB_CSS_WEB . "." . self::TBL_ORDERSEQUENCE . " SET accession = ? WHERE idOrderSequence = 1";
                parent::manipulate($sql, array($accession + 1), array("ConnectToWeb" => true));
            }

            return $accession;
        } else {
            // something got screwed up and there are no rows in the orderSequence table
            // first check if there are any web orders
            $sql = "SELECT COUNT(*) AS `OrderCount` FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o;";
            $numOrders = parent::select($sql);
            if ($numOrders[0]['OrderCount'] > 0) {
                // some orders exist so get the next highest accession
                $sql = "
                    SELECT MAX(CONVERT(SUBSTRING_INDEX(o.accession,'-',-1), UNSIGNED INT)) + 1 AS `maxAccession`
                    FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o
                ";
                $highestAccession = parent::select($sql);
                if (count($highestAccession) > 0) {
                    $sql = "INSERT INTO " . self::DB_CSS_WEB . "." . self::TBL_ORDERSEQUENCE . " (accession) VALUES (?);";
                    parent::manipulate($sql, array($highestAccession[0]['maxAccession']));
                    return $highestAccession[0]['maxAccession'];
                } else {
                    // for whatever reason the previous query didnt do what it was supposed to do so just add accession 1
                    $sql = "INSERT INTO " . self::DB_CSS_WEB . "." . self::TBL_ORDERSEQUENCE . " (accession) VALUES (?);";
                    parent::manipulate($sql, array(1));
                    return 1;
                }
            } else {
                // there are no orders so add a row with a value of 1
                $sql = "INSERT INTO " . self::DB_CSS_WEB . "." . self::TBL_ORDERSEQUENCE . " (accession) VALUES (?);";
                parent::manipulate($sql, array(1));
                return 1;
            }
        }
    }

    public static function isUniqueAccession($accession, array $settings = null) {
        $accession = trim($accession);
        $isEdit = false;
        $isUnique = true;

        if ($settings != null) {
            if (array_key_exists("IsEdit", $settings) && $settings['IsEdit'] == true) {
                $isEdit = true;
            }
        }

        if (!$isEdit) {
            // check if the accession exists in the order receipt log
//            $sql = "
//                SELECT webAccession
//                FROM " . self::DB_CSS . "." . self::TBL_ORDERRECEIPTLOG . "
//                #WHERE webAccession LIKE ?
//                WHERE CAST(webAccession AS UNSIGNED) = ?";

            $sql = "
                SELECT webAccession
                FROM " . self::DB_CSS . "." . self::TBL_ORDERRECEIPTLOG . "
                WHERE webAccession = ?";

            $data = parent::select($sql, array($accession));
            if (count($data) > 0) { // check the css schema
                $isUnique = false;
            }

            // check if the accession exists in the web orders table
//            $sql = "
//                SELECT DISTINCT o.accession
//                FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o
//                #WHERE o.accession LIKE ?
//                WHERE CAST(o.accession AS UNSIGNED) = ?";
            $sql = "
                SELECT DISTINCT o.accession
                FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o
                WHERE o.accession = ?";
            $data = parent::select($sql, array($accession));

            if (count($data) > 0) {
                $isUnique = false;
            }

        } else {
            // check if the accession is unique for an order being edited
            // just make sure another order does not exist with the same accession
//            $sql = "
//                SELECT DISTINCT o.accession
//                FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o
//                #WHERE   o.accession LIKE ?
//                WHERE   CAST(o.accession AS UNSIGNED) = ?
//                        AND o.idOrders != ?";
            $sql = "
                SELECT DISTINCT o.accession
                FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o
                WHERE   o.accession = ?
                        AND o.idOrders != ?";
            $data = parent::select($sql, array($accession, $settings['OrderId']));

            if (count($data) > 0) {
                $isUnique = false;
            }
        }

        return $isUnique;
    }
}
?>