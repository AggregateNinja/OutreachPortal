<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 12/5/2019
 * Time: 10:36 AM
 */

require_once 'DOS/BaseObject.php';
require_once 'DOS/IJasperServer.php';
require_once 'jasper/src/Jaspersoft/Client/Client.php';
require_once 'jasper/src/Jaspersoft/Service/Criteria/RepositorySearchCriteria.php';

class Jasper extends BaseObject implements  IJasperServer {

    private $jClient;

    public function __construct(array $data = null) {

        $this->jClient = new \Jaspersoft\Client\Client(
            self::JASPER_HOST, // Hostname
            self::JASPER_USERNAME, // Username
            self::JASPER_PASSWORD, // Password
            self::JASPER_BASEURL // Base URL
        );

    }

    public function getAllImages($saveImages = false) {
        $criteria = new \Jaspersoft\Service\Criteria\RepositorySearchCriteria();
        $criteria->folderUri = "/images";

        $results = $this->jClient->repositoryService()->searchResources($criteria);

        $imagesHtml = "";
        foreach ($results->items as $key => $result) {
            $imageResource = $this->jClient->repositoryService()->getResource($result->uri, true);
            $data = $this->jClient->repositoryService()->getBinaryFileData($imageResource);
            $imageResource->content = $data;

            $base64   = base64_encode($imageResource->content);
            $dataUri = 'data:png;base64,' . $base64;

            if ($saveImages == true) {
                $this->saveImage($dataUri, $result->label);
            }

            $imagesHtml .= "<img src='$dataUri' alt='" . $result->label . "' id='" . $result->label . "' name='" . $result->label . "' /><br/>";
        }

        return $imagesHtml;
    }

    public function saveImage($img, $fileName) {
        $folderPath = "images/";
        $image_parts = explode(";base64,", $img);
        $image_base64 = base64_decode($image_parts[1]);
        $file = $folderPath . $fileName;
        file_put_contents($file, $image_base64);
    }
}