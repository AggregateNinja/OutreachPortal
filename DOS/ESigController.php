<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 2/10/16
 * Time: 3:05 PM
 */

require_once 'BaseObject.php';
require_once 'DAOS/ESigDAO.php';
require_once 'IJasperServer.php';

require_once 'Utility/ESignatureValidator.php';

require_once 'jasper/src/Jaspersoft/Client/Client.php';
require_once 'jasper/src/Jaspersoft/Service/Criteria/RepositorySearchCriteria.php';

require_once 'DAOS/ResultLogDAO.php';

class ESigController extends BaseObject implements IJasperServer {

    public $Data = array(
        "userId" => "",
        "ip" => null,
        "fullName" => "",
        "initials" => "",
        "idUtensilTypes" => "",
        "assignTypeId" => "",

        "signatureFileName" => "",
        "initialsFileName" => "",

        "signatureType" => "",
        "initialsType" => "",

        "signatureUrl" => "",
        "initialsUrl" => "",

        "encodedSignature" => "",
        "encodedInitials" => "",

        "decodedSignature" => "",
        "decodedInitials" => "",

        "decodedSignatureCropped" => "",
        "decodedInitialsCropped" => "",

        "assignTypeId" => "",
        "doctorId" => ""
    );

    private $jClient;

    public function __construct(array $data) {
        parent::__construct($data);

        $this->jClient = new \Jaspersoft\Client\Client(
            self::JASPER_HOST, // Hostname
            self::JASPER_USERNAME, // Username
            self::JASPER_PASSWORD, // Password
            self::JASPER_BASEURL // Base URL
        );

        if ($data['assignTypeId'] == 1) {
            $this->Data['signatureFileName'] = "ESignature" . $data['userId'] . ".png";
            $this->Data['initialsFileName'] = "EInitials" . $data['userId'] . ".png";
            $this->Data['croppedSignatureFileName'] = "CroppedESignature" . $data['userId'] . ".png";
            $this->Data['croppedInitialsFileName'] = "CroppedEInitials" . $data['userId'] . ".png";
        } else {
            $this->Data['signatureFileName'] = "DoctorESignature" . $data['doctorId'] . ".png";
            $this->Data['initialsFileName'] = "DoctorEInitials" . $data['doctorId'] . ".png";
            $this->Data['croppedSignatureFileName'] = "CroppedDoctorESignature" . $data['doctorId'] . ".png";
            $this->Data['croppedInitialsFileName'] = "CroppedDoctorEInitials" . $data['doctorId'] . ".png";
        }


        /*$imageBlob = str_replace("data:image/png;base64,", "", $this->Data['signatureUrl']);
        $imageBlob = base64_decode($imageBlob);

        $image = new Imagick();
        $image->readimageblob($imageBlob);
        $image->trimImage(10);
       // $this->Data['signatureUrl'] = $image;

        $encodedImage = base64_encode($image);
        $imageUri = 'data:png;base64,' . $encodedImage;
        echo "<img src=\"$imageUri\" alt=\"\" />";
        echo $image . "<br/><br/>";*/
    }

    public function saveESig() {
        $signatureDataPieces = explode(",", $this->Data['signatureUrl']);
        $this->Data['encodedSignature'] = $signatureDataPieces[1];
        $this->Data['decodedSignature'] = base64_decode($this->Data['encodedSignature']);

        $image = new Imagick();
        $image->readimageblob($this->Data['decodedSignature']);

        if ($this->Data['signatureType'] == 2) { // no need to trim whitespace from drawn signatures
            $image->trimImage(100);
        }

        //$imageWidth = $image->getImageWidth();
        //$imageHeight = $image->getImageHeight();
        //$newHeight = $imageWidth / 2;

        //echo "Width: $imageWidth <br/>";
        //echo "Height: $imageHeight<br/>";
        //echo "New Height: $newHeight<br/>";

        //$image->thumbnailImage($imageWidth, $newHeight, false, false);
        //$image->scaleImage($imageWidth,$newHeight , false);
        //$image->borderImage('white', $imageWidth / 2,($imageHeight - $newHeight ) / 2);
        //$image->borderImage("#000000", 1, 1);

        $this->Data['decodedSignatureCropped'] = $image->getImageBlob();

        $finfoSignature = new finfo(FILEINFO_MIME);
        $signatureFileMimetype = $finfoSignature->buffer($this->Data['decodedSignature']);

        if (!empty($this->Data['initialsUrl'])) {
            $initialsDataPieces = explode(",", $this->Data['initialsUrl']);
            $this->Data['encodedInitials'] = $initialsDataPieces[1];
            $this->Data['decodedInitials'] = base64_decode(
                $this->Data['encodedInitials']
            );

            $image = new Imagick();
            $image->readimageblob($this->Data['decodedInitials']);

            if ($this->Data['signatureType'] == 2) {
                $image->trimImage(100);
            }

            $this->Data['decodedInitialsCropped'] = $image->getImageBlob();

            $finfoInitials = new finfo(FILEINFO_MIME);
            $initialsFileMimetype = $finfoInitials->buffer(
                $this->Data['decodedInitials']
            );
        }

        $eSigDAO = new ESigDAO($this->Data);
        
        $prevESig = $eSigDAO->getESig();
        $prevDoctorTypeId = null;
        if ($this->Data['assignTypeId'] == 2 && isset($this->Data['doctorId'])) {
            $prevDoctorESig = $eSigDAO->getDoctorSignatures(array($this->Data['doctorId']));
            if (count($prevDoctorESig) > 0) {
                $prevDoctorTypeId = $prevDoctorESig[$this->Data['doctorId']]['signatureType'];
            }
        }


        $prevDecodedSignature = null;
        $signatureResults = $this->searchJasperImage($this->Data['signatureFileName']);
        if ($signatureResults->totalCount != 0 && count($signatureResults->items) >= 2 && $signatureResults->items[1]->label == $this->Data['signatureFileName']) {
            $signatureResource = $this->getJasperImage($this->Data['signatureFileName'], true);
            $prevDecodedSignature = $signatureResource->content;
        }

        $prevDecodedInitials = null;
        $initialsResults = $this->searchJasperImage($this->Data['initialsFileName']);
        if ($initialsResults->totalCount != 0 && count($initialsResults->items) >= 2 && $initialsResults->items[1]->label == $this->Data['initialsFileName']) {
            $initialsResource = $this->getJasperImage($this->Data['initialsFileName'], true);
            $prevDecodedInitials = $initialsResource->content;
        }
        
        $eSigDAO->saveESig();

        $this->saveJasperImage($this->Data['signatureFileName'], $this->Data['decodedSignature']);
        $this->saveJasperImage($this->Data['croppedSignatureFileName'], $this->Data['decodedSignatureCropped']);

        if (!empty($this->Data['initialsUrl'])) {
            $this->saveJasperImage($this->Data['initialsFileName'], $this->Data['decodedInitials']);
            $this->saveJasperImage($this->Data['croppedInitialsFileName'], $this->Data['decodedInitialsCropped']);
        } else {
            $this->deleteJasperImage($this->Data['initialsFileName']);
            $this->deleteJasperImage($this->Data['croppedInitialsFileName']);
        }

        //$data_uri = $this->Data['signatureUrl'];
        /*$encodedImage = base64_encode($image);
        $imageUri = 'data:png;base64,' . $encodedImage;
        echo "<img src=\"$imageUri\" alt=\"\" />";*/

        //file_put_contents( "signature.png",$decoded_image);

        //header("Content-type: $file_mimetype");
        //header("Content-disposition: inline; filename=filename.png");
        //echo $this->Data['decodedImage'];

        //echo $this->Data['decodedImage'] . "<br/><br/>";
        //$imageFile = file_get_contents("/var/www/outreach/images/eSignature2.png");
        //echo $imageFile;

        $prevSignatureTypeId = null;
        $prevAssignTypeId = null;
        if ($prevESig != null && $prevESig instanceof ESignature) {
            $prevSignatureTypeId = $prevESig->signatureType;
            $prevAssignTypeId = $prevESig->assignTypeId;
        }
        
        $aryLogData = array(
            "userId" => $this->Data['userId'],
            "ip" => $this->Data['ip'],
            "prevSignatureTypeId" => $prevSignatureTypeId,

            "prevAssignTypeId" => $prevAssignTypeId,
            "doctorId" => $this->Data['doctorId'],
            "assignTypeId" => $this->Data['assignTypeId'],
            "prevDoctorSignatureTypeId" => $prevDoctorTypeId,

            "prevDecodedSignature" => $prevDecodedSignature,
            "prevDecodedInitials" => $prevDecodedInitials
        );

        //echo "<pre>"; print_r($aryLogData); echo "</pre>";
        

        ResultLogDAO::addESignatureLogEntry($aryLogData, array("Conn" => $eSigDAO->Conn));

    }

    private function saveJasperImage($fileName, $decodedImage) {
        // check that the signature image exists in jasper server
        $results = $this->searchJasperImage($fileName);
        //echo $fileName;
        //echo "<pre>"; print_r($results); echo "</pre>";

        $fileNameSub = substr($fileName, 0, 7);

        if ($results->totalCount == 0
            || ($fileNameSub == "Cropped" && $results->items[0]->label != $fileName)
            || (count($results->items) > 1 && $results->items[1]->label != $fileName)) {
            // create image
            $this->uploadJasperImage($fileName, $decodedImage);
        } else {
            // update image
            $this->updateJasperImage($fileName, $decodedImage);
        }
    }

    public function deleteJasperImage($fileName) {
        $this->jClient->repositoryService()->deleteResources("/images/$fileName");
    }


    public function searchJasperImage($fileName) {
        // Search for specific items in repository
        $criteria = new \Jaspersoft\Service\Criteria\RepositorySearchCriteria();
        $criteria->q = $fileName;

        $results = $this->jClient->repositoryService()->searchResources($criteria);

        /*foreach ($results->items as $res) {
            echo $res->label . "<br>";
        }*/

        return $results;
    }

    private function uploadJasperImage($fileName, $decodedImage) {
        // Create an image
        //$imageFile = file_get_contents("/var/www/outreach/images/eSignature2.png");

        $jasperFile = new \Jaspersoft\Dto\Resource\File;
        $jasperFile->description = $fileName;
        $jasperFile->label = $fileName;
        $jasperFile->type = "img";

        $this->jClient->repositoryService()->createFileResource(
            $jasperFile,
            $decodedImage,
            //$imageFile,
            "/images"
        );
    }

    private function updateJasperImage($fileName, $decodedImage) {
        // Update an image resource
        $imageResource = $this->getJasperImage($fileName);
        $updatedResource = $this->jClient->repositoryService()->updateFileResource($imageResource, $decodedImage);
    }

    public function getJasperImage($jasperFileName, $withBinaryFileData = false) {
        // Get an image resource
        $imageResource = $this->jClient->repositoryService()->getResource("/images/$jasperFileName", true);
        //$imageResource = $client->repositoryService()->getResource("/images/ESignature2.png", true);

        if ($withBinaryFileData == true) {
            $data = $this->jClient->repositoryService()->getBinaryFileData($imageResource);
            $imageResource->content = $data;
        }

        return $imageResource;
    }

    private function createJasperFolder() {
        // Create a folder
        $folder = new \Jaspersoft\Dto\Resource\Folder;

        $folder->label = "TestFolder";
        $folder->description = "This is a test folder";

        $this->jClient->repositoryService()->createResource($folder, "/images");
    }

    private function uploadJasperResource() {

        // On the e-signature edit page, upload the user's e-signature image file to jasper server
        // Create a table to look up the user's id and image file name that coresponds to the image saved in jasper server
        // When generating requisitions, select the image file name from the database and pass it in as a parameter to the report.

        /*$repositoryService = $this->jClient->repositoryService();*/

        // Get an image resource
        /*$imageResource = $repositoryService->getResource("/images/eSignature.png", true);
        echo "<pre>"; print_r($imageResource); echo "</pre>";*/


        // Create an image
        /*$imageFile = file_get_contents("/var/www/outreach/images/eSignature2.png");

        $jasperFile = new \Jaspersoft\Dto\Resource\File;
        $jasperFile->description = "ESignature2";
        $jasperFile->label = "ESignature2.png";
        $jasperFile->type = "img";

        $client->repositoryService()->createFileResource(
            $jasperFile,
            $imageFile,
            "/images"
        );*/

        // Update an image resource
        /*$imageResource = $repositoryService->getResource("/images/ESignature2.png", true);
        $imageResource->label = "ESignature2.png";

        $updatedResource = $repositoryService->updateResource($imageResource, true);*/

        // Create a folder
        /*$folder = new \Jaspersoft\Dto\Resource\Folder;

        $folder->label = "TestFolder";
        $folder->description = "This is a test folder";

        $client->repositoryService()->createResource($folder, "/images");*/
    }
} 