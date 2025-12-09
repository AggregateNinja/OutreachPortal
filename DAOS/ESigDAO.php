<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 2/11/16
 * Time: 1:31 PM
 */

require_once 'DataObject.php';
require_once 'DOS/ESignature.php';

class ESigDAO extends DataObject {

    public $Conn;
    protected $Data = array(
        "idESig" => "",
        "userId" => "",
        "fullName" => "",
        "initials" => "",
        "signatureFileName" => "",
        "initialsFileName" => "",
        "signatureType" => "",
        "initialsType" => "",
        "isActive" => true,
        "idUtensilTypes" => "",
        "assignTypeId" => "",
        "doctorId" => "",
        "encodedSignature" => ""
    );

    public function __construct(array $data = null) {
        parent::__construct($data);

        if ($data != null && array_key_exists("Conn", $data) && $data['Conn'] instanceof mysqli) {
            $this->Conn = $data['Conn'];
        } else {
            $this->Conn = parent::connect();
        }

    }

    public function saveESig() {

        $sql = "
            SELECT e.idESig, e.userId
            FROM " . self::DB_CSS . "." . self::TBL_ESIGNATURES . " e
            WHERE e.userId = ?";

        $data = parent::select($sql, array($this->Data['userId']), array("Conn" => $this->Conn));

        $aryInput = array();

        if (count($data) > 0) {

            $idESig = $data[0]['idESig'];

            if ($this->Data['assignTypeId'] == 1) { // assign per user login
                // update
                $sql = "
                    UPDATE " . self::DB_CSS . "." . self::TBL_ESIGNATURES . "
                    SET fullName = ?, initials = ?, signatureFileName = ?, initialsFileName = ?, assignTypeId = ?, signatureType = ?, initialsType = ?
                    WHERE userId = ?
                ";

                $aryInput[] = $this->Data['fullName'];
                $aryInput[] = $this->Data['initials'];
                $aryInput[] = $this->Data['signatureFileName'];
                $aryInput[] = $this->Data['initialsFileName'];
                $aryInput[] = $this->Data['assignTypeId'];
                $aryInput[] = $this->Data['signatureType'];
                $aryInput[] = $this->Data['initialsType'];
                $aryInput[] = $this->Data['userId'];

                parent::manipulate($sql, $aryInput, array("Conn" => $this->Conn));

            } else {
                // update per doctor

                $sql = "
                    UPDATE " . self::DB_CSS . "." . self::TBL_ESIGNATURES . "
                    SET assignTypeId = ?
                    WHERE userId = ?
                ";
                $aryInput[] = $this->Data['assignTypeId'];
                $aryInput[] = $this->Data['userId'];
                parent::manipulate($sql, $aryInput, array("Conn" => $this->Conn));

                $this->saveDoctorESig();
            }



        } else {
            // insert

            if ($this->Data['assignTypeId'] == 1) { // assign per user login
                $sql = "
                INSERT INTO " . self::DB_CSS . "." . self::TBL_ESIGNATURES . " (userId, fullName, initials, signatureFileName, initialsFileName, assignTypeId, signatureType, initialsType)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                $aryInput[] = $this->Data['userId'];
                $aryInput[] = $this->Data['fullName'];
                $aryInput[] = $this->Data['initials'];
                $aryInput[] = $this->Data['signatureFileName'];
                $aryInput[] = $this->Data['initialsFileName'];
                $aryInput[] = $this->Data['assignTypeId'];
                $aryInput[] = $this->Data['signatureType'];
                $aryInput[] = $this->Data['initialsType'];


                $idESig = parent::manipulate($sql, $aryInput, array("Conn" => $this->Conn, "LastInsertId" => true));
            } else {
                $sql = "
                INSERT INTO " . self::DB_CSS . "." . self::TBL_ESIGNATURES . " (userId, assignTypeId, signatureType, initialsType)
                VALUES (?, ?, ?, ?)";
                $aryInput[] = $this->Data['userId'];
                $aryInput[] = $this->Data['assignTypeId'];
                $aryInput[] = $this->Data['signatureType'];
                $aryInput[] = $this->Data['initialsType'];

                $idESig = parent::manipulate($sql, $aryInput, array("Conn" => $this->Conn, "LastInsertId" => true));

                $this->saveDoctorESig();
            }
        }

        $this->updateUtensilLookup($idESig);
    }

    private function saveDoctorESig() {

        $sql = "
            SELECT de.idDoctorSigs, de.doctorId
            FROM " . self::DB_CSS . "." . self::TBL_DOCTORESIGNATURES . " de
            WHERE de.doctorId = ?";

        $data = parent::select($sql, array($this->Data['doctorId']), array("Conn" => $this->Conn));

        if (count($data) > 0) {
            // update doctorESignature table
            $sql = "
                    UPDATE " . self::DB_CSS . "." . self::TBL_DOCTORESIGNATURES . "
                    SET fullName = ?, initials = ?, signatureFileName = ?, initialsFileName = ?, signatureType = ?, initialsType = ?
                    WHERE doctorId = ?
                ";

            $aryInput = array(
                $this->Data['fullName'],
                $this->Data['initials'],
                $this->Data['signatureFileName'],
                $this->Data['initialsFileName'],
                $this->Data['signatureType'],
                $this->Data['initialsType'],
                $this->Data['doctorId']
            );

            parent::manipulate($sql, $aryInput, array("Conn" => $this->Conn));


        } else {
            // insert
            $sql = "
                INSERT INTO " . self::DB_CSS . "." . self::TBL_DOCTORESIGNATURES . " (fullName, initials, signatureFileName, initialsFileName, signatureType, initialsType, doctorId)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";

            $aryInput = array(
                $this->Data['fullName'],
                $this->Data['initials'],
                $this->Data['signatureFileName'],
                $this->Data['initialsFileName'],
                $this->Data['signatureType'],
                $this->Data['initialsType'],
                $this->Data['doctorId']
            );

            parent::manipulate($sql, $aryInput, array("Conn" => $this->Conn));
        }

        $sql = "SELECT esi.idDoctorESignatureImages, esi.doctorId FROM " . self::DB_CSS . "." . self::TBL_DOCTORESIGNATUREIMAGES . " esi WHERE doctorId = ?";
        $data = parent::select($sql, array($this->Data['doctorId']), array("Conn" => $this->Conn));
        if (count($data) > 0) {
            $dateUpdated = date("Y-m-d H:i.s");
            $sql = "UPDATE " . self::DB_CSS . "." . self::TBL_DOCTORESIGNATUREIMAGES . " SET base64Image = ?, dateUpdated = ? WHERE idDoctorESignatureImages = ?";
            parent::manipulate($sql, array($this->Data['encodedSignature'], $dateUpdated, $data[0]['idDoctorESignatureImages']), array("Conn" => $this->Conn));

        } else {
            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_DOCTORESIGNATUREIMAGES . " (doctorId, base64Image) VALUES (?, ?)";
            parent::manipulate($sql, array($this->Data['doctorId'], $this->Data['encodedSignature']), array("Conn" => $this->Conn));
        }
    }

    /*public function saveESig() {

        $sql = "
            SELECT e.idESig, e.userId
            FROM " . self::DB_CSS . "." . self::TBL_ESIGNATURES . " e
            WHERE e.userId = ?";

        $data = parent::select($sql, array($this->Data['userId']), array("Conn" => $this->Conn));

        $aryInput = array();

        if (count($data) > 0) {

            $idESig = $data[0]['idESig'];

            // update
            $sql = "
                UPDATE " . self::DB_CSS . "." . self::TBL_ESIGNATURES . "
                SET fullName = ?, initials = ?, signatureFileName = ?, initialsFileName = ?, assignTypeId = ?, signatureType = ?, initialsType = ?
                WHERE userId = ?
            ";

            $aryInput[] = $this->Data['fullName'];
            $aryInput[] = $this->Data['initials'];
            $aryInput[] = $this->Data['signatureFileName'];
            $aryInput[] = $this->Data['initialsFileName'];
            $aryInput[] = $this->Data['assignTypeId'];
            $aryInput[] = $this->Data['signatureType'];
            $aryInput[] = $this->Data['initialsType'];
            $aryInput[] = $this->Data['userId'];

            parent::manipulate($sql, $aryInput, array("Conn" => $this->Conn));

            $this->updateUtensilLookup($idESig);

        } else {
            // insert
            $sql = "
                INSERT INTO " . self::DB_CSS . "." . self::TBL_ESIGNATURES . " (userId, fullName, initials, signatureFileName, initialsFileName, assignTypeId, signatureType, initialsType)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $aryInput[] = $this->Data['userId'];
            $aryInput[] = $this->Data['fullName'];
            $aryInput[] = $this->Data['initials'];
            $aryInput[] = $this->Data['signatureFileName'];
            $aryInput[] = $this->Data['initialsFileName'];
            $aryInput[] = $this->Data['assignTypeId'];
            $aryInput[] = $this->Data['signatureType'];
            $aryInput[] = $this->Data['initialsType'];


            $idESig = parent::manipulate($sql, $aryInput, array("Conn" => $this->Conn, "LastInsertId" => true));

            $this->updateUtensilLookup($idESig);
        }
    }*/

    private function updateUtensilLookup($idESig) {
        if ($this->Data['signatureType'] == 2 || empty($this->Data['idUtensilTypes'])) {
            // image file was uploaded
            parent::manipulate(
                "DELETE FROM " . self::DB_CSS . "." . self::TBL_ESIGUTENSILLOOKUP . " WHERE esigId = ?",
                array($idESig),
                array("Conn" => $this->Conn)
            );
        } else {
            $sql = "SELECT ul.idUtensilLookups, ul.esigId, ul.utensilTypeId FROM " . self::DB_CSS . "." . self::TBL_ESIGUTENSILLOOKUP . " ul WHERE ul.esigId = ?;";
            $lookupData = parent::select($sql, array($idESig), array("Conn" => $this->Conn));
            if (count($lookupData) > 0) {
                // update
                parent::manipulate(
                    "UPDATE " . self::DB_CSS . "." . self::TBL_ESIGUTENSILLOOKUP . " SET utensilTypeId = ? WHERE esigId = ?",
                    array($this->Data['idUtensilTypes'], $idESig),
                    array("Conn" => $this->Conn)
                );
            } else {
                // insert
                parent::manipulate(
                    "INSERT INTO " . self::DB_CSS . "." . self::TBL_ESIGUTENSILLOOKUP . " (esigId, utensilTypeId) VALUES (?, ?);",
                    array($idESig, $this->Data['idUtensilTypes']),
                    array("Conn" => $this->Conn)
                );
            }
        }
    }

    public function getESig() {
        $sql = "
            SELECT  e.idESig, e.userId, e.fullName, e.initials, e.signatureFileName, e.initialsFileName, e.assignTypeId, e.signatureType, e.initialsType,
                    ut.idUtensilTypes, ut.utensilTypeName, ut.lineWidth,
                    e.isActive
            FROM " . self::DB_CSS . "." . self::TBL_ESIGNATURES . " e
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_ESIGUTENSILLOOKUP . " ul ON e.idESig = ul.esigId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_ESIGUTENSILTYPES . " ut ON ul.utensilTypeId = ut.idUtensilTypes AND ut.isActive = 1
            WHERE e.userId = ? AND e.isActive = true";
        $data = parent::select($sql, array($this->Data['userId']), array("Conn" => $this->Conn));

        if (count($data) > 0) {
            return new ESignature($data[0]);
        }

        return null;
    }

    public function getDoctorSignatures(array $doctorIds) {
        $sql = "
            SELECT ds.doctorId, ds.fullName, ds.initials, ds.signatureType, ds.initialsType
            FROM " . self::DB_CSS . "." . self::TBL_DOCTORESIGNATURES . " ds
            WHERE ";
        foreach ($doctorIds as $doctorId) {
            $sql .= "ds.doctorId = ? OR ";
        }
        $sql = substr($sql, 0, strlen($sql) - 4);

        $data = parent::select($sql, $doctorIds, array("Conn" => $this->Conn));

        $aryReturn = array();

        foreach($data as $row) {
            $aryReturn[$row['doctorId']] = $row;
        }

        return $aryReturn;
    }



    public function getAssignTypes() {
        $sql = "SELECT at.idAssignTypes, at.typeName, at.typeDescription, at.isActive FROM " . self::DB_CSS . "." . self::TBL_ESIGASSIGNTYPES . " at";
        return parent::select($sql, null, array("Conn" => $this->Conn));
    }

    public static function getDoctorIdFromImageName($imageName) {
        $sql = "SELECT doctorId FROM css.doctorESignatures WHERE signatureFileName = ?";
        $input = array($imageName);
        $data = parent::select($sql, $input);
        if (count($data) > 0) {
            return $data[0]['doctorId'];
        }
        return null;
    }

    public static function insertESignatureImage($doctorId, $base64Image) {
        //$sql = "INSERT INTO css.doctorESignatureImages (doctorId, base64Image) VALUES (?, ?)";
        //$input = array($doctorId, $base64Image);

        $sql = "UPDATE css.doctorESignatureImages SET base64Image = ? WHERE doctorId = ?";
        $input = array($base64Image, $doctorId);

        parent::manipulate($sql, $input);
    }

    public function deleteESignature($userId, $assignTypeId, $doctorId) {



            if ($assignTypeId == 1) {
                // delete user esignature



                $sql = "
                SELECT e.idESig, e.userId
                FROM " . self::DB_CSS . "." . self::TBL_ESIGNATURES . " e
                WHERE e.userId = ?";

                $data = parent::select($sql, array($this->Data['userId']), array("Conn" => $this->Conn));

                if (count($data) > 0) {
                    $idESIg = $data[0]['idESig'];

                    $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_ESIGUTENSILLOOKUP . " WHERE eSigId = ?";
                    parent::manipulate($sql, array($idESIg), array("Conn" => $this->Conn));

                    $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_ESIGNATURES . " WHERE idESig = ?";
                    parent::manipulate($sql, array($idESIg), array("Conn" => $this->Conn));
                }

            } else if ($assignTypeId == 2) {
                // delete doctor esignature

                $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_DOCTORESIGNATURES . " WHERE doctorId = ?";
                parent::manipulate($sql, array($doctorId), array("Conn" => $this->Conn));
            }
    }
} 