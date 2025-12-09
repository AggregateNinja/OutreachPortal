<?php

require_once 'DOS/DoctorUser.php';
require_once 'UserDAO.php';
/**
 * Description of DoctorDAO
 *
 * @author Edd
 */
class DoctorDAO extends UserDAO {

    public static function getDoctor($inputs, array $arySettings = null) {
        $includeUserSettings = false;
        $includeCommonTests = false;
        $includeExcludedTests = false;
        $includeCommonCodes = false;
        $sqlSettings = array();
        if ($arySettings != null) {
            if (array_key_exists("IncludeUserSettings", $arySettings) && $arySettings["IncludeUserSettings"] == true) {
                $includeUserSettings = true;
            }
            if (array_key_exists("IncludeCommonTests", $arySettings) && $arySettings["IncludeCommonTests"] == true) {
                $includeCommonTests = true;
            }
            if (array_key_exists("IncludeExcludedTests", $arySettings) && $arySettings["IncludeExcludedTests"] == true) {
                $includeExcludedTests = true;
            }
            if (array_key_exists("IncludeCommonCodes", $arySettings) && $arySettings["IncludeCommonCodes"] == true) {
                $includeCommonCodes = true;
            }
            if (array_key_exists("Conn", $arySettings) && $arySettings['Conn'] instanceof mysqli) {
                $sqlSettings['Conn'] = $arySettings['Conn'];
            }
        }

        $sql = "
            SELECT  d.iddoctors, d.number, d.firstName, d.lastName, d.address1, d.address2, d.city, d.state, d.zip, d.NPI, d.UPIN, d.phone,
                    u.idUsers, u.typeId, u.email, u.password, u.userSalt, u.isVerified, u.verificationCode, u.dateCreated, u.dateUpdated";
        $locationJoin = "";
        if (self::HasMultiLocation) {
            $sql .= ", lo.idLocation, lo.locationNo, lo.locationName";
            $locationJoin = "LEFT JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " lo ON d.locationId = lo.idLocation";
        }



        if ($includeUserSettings) {
            $sql .= ", s.idUserSettings, s.settingName, s.settingDescription ";
        }

        $sql .= "
            FROM " . self::DB_CSS . "." . self::TBL_DOCTORS . " d
            $locationJoin
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " l ON d.iddoctors = l.doctorId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_USERS . " u ON u.idUsers = l.userId ";
        if ($includeUserSettings) {
            $sql .= "LEFT JOIN " . self::DB_CSS . "." . self::TBL_USERSETTINGSLOOKUP . " lu ON lu.userId = u.idUsers
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_USERSETTINGS . " s ON s.idUserSettings = lu.userSettingId ";
        }
        $params = array();

        $sql .= " WHERE ";

        foreach ($inputs as $field => $value) {
            if ($field == "email") {
                $sql .= " u.$field = ? AND ";
            } else {
                $sql .= " $field = ? AND ";
            }
            $params[] = $value;
        }
        $sql = substr($sql, 0, strlen($sql) - 4);

        $data = parent::select($sql, $params, $sqlSettings);
        if (count($data) > 0) {
            $doctor = new DoctorUser($data[0]);

            if (count($data) > 1) {
                if ($includeUserSettings) {
                    foreach ($data as $row) {
                        $doctor->addUserSetting($row);
                    }
                }
            }

            if ($includeCommonTests) {
                $doctor = self::setCommonTests($doctor);
            }
            if ($includeExcludedTests) {
                $doctor = self::setExcludedTests($doctor);
            }

            if ($includeCommonCodes) {
                $doctor = self::setCommonDiagnosisCodes($doctor);
            }

            return $doctor;
        }
        return false;
    }

    public static function getDoctors($input, mysqli $conn = null) {
        $aryInput = array();
        $sql = "SELECT * FROM " .self::DB_CSS . "." . self::TBL_DOCTORS . " d ";

        if (array_key_exists("WebDoctorsOnly", $input) && $input['WebDoctorsOnly'] == true) {
            $sql .= "INNER JOIN " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON d.iddoctors = dl.doctorId ";
        }

        $sql .= "WHERE d.active = true ";

        if (array_key_exists("orderBy", $input)) {
            $sql .= "ORDER BY " . $input['orderBy'] . " ";
        }
        if (array_key_exists("startRow", $input) && array_key_exists("numRows", $input)) {
            $sql .= "LIMIT ?, ?";
            $aryInput[] = $input['startRow'];
            $aryInput[] = $input['numRows'];
        } else if (array_key_exists("startRow", $input)) {
            $sql .= "LIMIT ?, 18446744073709551615";
            $aryInput['startRow'];
        } else if (array_key_exists("numRows", $input)) {
            $sql .= "LIMIT ?";
            $aryInput['numRows'];
        }

        if ($conn != null) {
            $data = parent::select($sql, $aryInput, array("Conn" => $conn));
        } else {
            $data = parent::select($sql, $aryInput);
        }

        $aryClients = array();
        foreach ($data as $row) {
            $currClient = new DoctorUser($row);
            $aryClients[] = $currClient;
        }

        return $aryClients;
    }

    public static function getDoctorWithClients(array $inputs, array $arySettings = null) {
        $includeUserSettings = false;
        $includePreferences = false;
        $includeCommonCodes = false;
        $sqlSettings = array();
        if ($arySettings != null) {
            if (array_key_exists("IncludeUserSettings", $arySettings) && $arySettings["IncludeUserSettings"] == true) {
                $includeUserSettings = true;
            }
            if (array_key_exists("IncludePreferences", $arySettings) && $arySettings["IncludePreferences"] == true) {
                $includePreferences = true;
            }
            if (array_key_exists("IncludeCommonCodes", $arySettings) && $arySettings["IncludeCommonCodes"] == true) {
                $includeCommonCodes = true;
            }
            if (array_key_exists("Conn", $arySettings) && $arySettings['Conn'] instanceof mysqli) {
                $sqlSettings['Conn'] = $arySettings['Conn'];
            }
        }

        $sql = "
            SELECT d.iddoctors, d.number, d.firstName AS `doctorFirstName`, d.lastName AS `doctorLastName`, d.address1, d.address2, d.city, d.state, d.zip, d.externalId,
                    u.idUsers, u.typeId, u.email, u.password, u.userSalt, u.isVerified, u.verificationCode, u.dateCreated, u.dateUpdated
            FROM " . self::DB_CSS . "." . self::TBL_DOCTORS . " d
            INNER JOIN " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " l ON l.doctorId = d.iddoctors
            INNER JOIN " . self::DB_CSS . "." . self::TBL_USERS . " u ON u.idUsers = l.userId
            WHERE ";

        $params = array();
        foreach ($inputs as $field => $value) {
            $sql .= $field . " = ? AND ";
            $params[] = $value;
        }
        $sql = substr($sql, 0, strlen($sql) - 4);
        $data = parent::select($sql, $params, $sqlSettings);

        if (count($data) > 0) {

            $doctor = new DoctorUser($data[0]);


            $sql = "
                SELECT DISTINCT o.doctorId, o.clientId, clientNo, clientName, clientStreet, clientCity, clientState, clientZip, phoneNo, faxNo, c.defaultReportType
                FROM " . self::DB_CSS . "." . self::TBL_CLIENTS . " c
                INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON o.clientId = c.idClients
                INNER JOIN " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON o.doctorId = dl.doctorId
                WHERE dl.userId = ?";
            $data = parent::select($sql, array($inputs['idUsers']));
            if (count($data) > 0) {
                foreach ($data as $row) {
                    $doctor->addClient(array(
                        "idClients" => $row['clientId'],
                        "clientNo" => $row['clientNo'],
                        "clientName" => $row['clientName'],
                        "clientStreet" => $row['clientStreet'],
                        "clientCity" => $row['clientCity'],
                        "clientState" => $row['clientState'],
                        "clientZip" => $row['clientZip'],
                        "phoneNo" => $row['phoneNo'],
                        "faxNo" => $row['faxNo'],
                        "defaultReportType" => $row['defaultReportType']
                    ));
                }
            }
            if ($includeUserSettings) {
                $sql = "SELECT idUserSettings, settingName, settingDescription, pageName
                        FROM " . self::DB_CSS . "." . self::TBL_USERSETTINGS . " s
                        LEFT JOIN " . self::DB_CSS . "." . self::TBL_USERSETTINGSLOOKUP . " lu ON lu.userId = u.idUsers
                        WHERE li.userId = ?";
                $sData = parent::select($sql, array($inputs['idUsers']));
                if (count($sData) > 0) {
                    foreach ($sData as $sRow) {
                        $doctor->addUserSetting($sRow);
                    }
                }
            }

            if ($includePreferences) {
                $sql = "SELECT idpreferences, " . self::TBL_PREFERENCES . ".key, value, type FROM " . self::DB_CSS . "." . self::TBL_PREFERENCES;
                $data = parent::select($sql, null, $sqlSettings);
                if (count($data) > 0) {
                    $doctor->addPreferences($data);
                }
            }

            $doctor->idUsers = $inputs['idUsers'];

            if ($arySettings != null && array_key_exists("IncludeCommonTests", $arySettings) && $arySettings['IncludeCommonTests'] == true) {
                $doctor = self::setCommonTests($doctor);
            }
            if ($arySettings != null && array_key_exists("IncludeExcludedTests", $arySettings) && $arySettings['IncludeExcludedTests'] == true) {
                $doctor = self::setExcludedTests($doctor);
            }

            if ($includeCommonCodes) {
                $doctor = self::setCommonDiagnosisCodes($doctor);
            }

            return $doctor;
        }

        return false;
    }

    private static function setCommonDiagnosisCodes(DoctorUser $doctor) {

        $sql = "
            SELECT d.idDiagnosisCodes, d.code, d.description, d.dateCreated, d.dateUpdated
            FROM " . self::TBL_DIAGNOSISCODES . " d 
            INNER JOIN " . self::TBL_COMMONDIAGNOSISCODES . " cd ON d.idDiagnosisCodes = cd.diagnosisCodeId
            WHERE cd.userId = ? 
            ORDER BY code";
        $data = parent::select($sql, array($doctor->idUsers));

        if (count($data) > 0) {
            foreach ($data as $dataRow) {
                $doctor->addCommonDiagnosisCode($dataRow);
            }
        }

        return $doctor;
    }

    private static function setCommonTests(DoctorUser $doctor) {
        $sql = "SELECT t.idtests, t.number, t.name, t.testType
                FROM " . self::TBL_COMMONTESTS . " ct 
                INNER JOIN " . self::TBL_TESTS . " t ON ct.testId = t.idtests
                INNER JOIN " . self::TBL_USERS . " u ON ct.userId = u.idUsers 
                INNER JOIN " . self::TBL_DOCTORLOOKUP . " dl ON u.idUsers = dl.userId
                WHERE dl.doctorId = ?
                ORDER BY t.name";
        $data = parent::select($sql, array($doctor->iddoctors));
        if (count($data) > 0) {
            foreach ($data as $row) {
                $doctor->addCommonTest($row);
            }
        }
//        else {
//            $doctor->addCommonTest(null);
//        }
        return $doctor;
    }
    private static function setExcludedTests(DoctorUser $doctor) {
        $sql = "SELECT t.idtests, t.number, t.name, t.testType
                FROM " . self::TBL_EXCLUDEDTESTS . " ct 
                INNER JOIN " . self::TBL_TESTS . " t ON ct.testId = t.idtests
                INNER JOIN " . self::TBL_USERS . " u ON ct.userId = u.idUsers 
                INNER JOIN " . self::TBL_DOCTORLOOKUP . " dl ON u.idUsers = dl.userId
                WHERE dl.doctorId = ?
                ORDER BY t.name";
        $data = parent::select($sql, array($doctor->iddoctors));
        if (count($data) > 0) {
            foreach ($data as $row) {
                $doctor->addExcludedTest($row);
            }
        }
//        else {
//            $doctor->addExcludedTest(null);
//        }
        return $doctor;
    }

}
?>
