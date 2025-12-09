<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'UserDAO.php';
require_once 'Utility/Auth.php';

require_once 'Utility/UserValidator.php';

require_once 'DOS/User.php';
require_once 'DOS/ClientUser.php';
require_once 'DOS/DoctorUser.php';
require_once 'DOS/AdminUser.php';
require_once 'DOS/SalesmanUser.php';
require_once 'DOS/InsuranceUser.php';
require_once 'DOS/PatientAdminUser.php';

require_once 'ClientDAO.php';
require_once 'DoctorDAO.php';

require_once 'DOS/UserSetting.php';
require_once 'DOS/AdminSetting.php';

require_once 'ResultLogDAO.php';
require_once 'Utility/ResultUserCreator.php';

require_once 'DOS/PatientUser.php';

/**
 * Description of AdminDAO
 * @author Edd
 */
class AdminDAO extends UserDAO {

    private $UserValidator;
    private $UserFields; // = array(); // an array of fields submitted from and add/edit/delete form
    private $clientId;

    public function __construct(array $data = null, array $settings = null) {
        //$this->UserFields; // = array();
        if ($data != null) {
            parent::__construct($data, $settings);

            if (array_key_exists("clientId", $data) && !empty($data['clientId']) && is_numeric($data['clientId'])) {
                $this->clientId = $data['clientId'];
            }
        }
    }

    public function addUser(array $inputFields) {
        $this->UserValidator = new UserValidator($inputFields);

        $this->UserFields = $inputFields;

        if (array_key_exists("adminUserId", $inputFields)) {
            $adminUserId = $inputFields['adminUserId'];
            $this->UserValidator->userIsValid(true);
        } else {
            $adminUserId = $_SESSION['id'];
            $this->UserValidator->userIsValid();
        }

        $aryAdminLogs = array(
            "adminUserId" => $adminUserId,
            "userTypeId" => $inputFields['typeId'],
            "email" => $inputFields['email'],
            "Ip" => null
        );
        if (array_key_exists("Ip", $inputFields) && $inputFields['Ip'] != null && !empty($inputFields['Ip'])) {
            $aryAdminLogs['Ip'] = $inputFields['Ip'];
        }

        if (!$this->UserValidator->IsValid && array_key_exists("email2", $this->UserValidator->InvalidFields)) {
            // A duplicate email was found for an inactive user.
            // In order to alleviate this situation, the existing email field for the inactive user will be appended with a timestamp.
            // This will allow their old email value to be freed up for the new user to have.
            $sql = "
                UPDATE " . self::DB_CSS . "." . self::TBL_USERS . "
                SET email = ?
                WHERE email = ?";
            $newEmail = $inputFields['email'] . date("YmdHis");
            parent::manipulate($sql, array($newEmail, $inputFields['email']));
            $aryAdminLogs['action'] = 6;
            //ResultLogDAO::addAdminLogEntry($inputFields["idUsers"], $aryAdminLogs);
            ResultLogDAO::addAdminLogEntry($_SESSION['id'], $aryAdminLogs);
            $this->UserValidator->setIsValid(true);
        }

        if ($this->UserValidator->IsValid) {
            //echo "test";
            if (array_key_exists("multiUsers", $inputFields)) {
                $multiUsers = $inputFields['multiUsers'];
                unset($inputFields['multiUsers']);
            }
            $aryInfo = $this->getNewLoginInfo($inputFields);

            //echo "<pre>"; print_r($aryInfo); echo "</pre>";

            // -------------------------------- Add to WebUsers table
            $sql = "
                INSERT INTO " . self::DB_CSS . "." . self::TBL_USERS . "(typeId, email, password, userSalt, verificationCode)
                VALUES (?, ?, ?, ?, ?)
            ";
            //"password" => $this->UserFields['password'],
            //"userSalt" => $this->UserFields['userSalt'],
            //"verificationCode" => $this->UserFields['verificationCode']
            $aryUserFields = array (
                "typeId" => $inputFields['typeId'],
                "email" => $inputFields['email'],
                "password" => $aryInfo[1],
                "userSalt" => $aryInfo[0],
                "verificationCode" => $aryInfo[2]
            );

            /*error_log($sql);
            //error_log(http_build_query($aryUserFields, '', ', '));
            error_log(implode(", ", $aryUserFields));*/


            $idUsers = parent::manipulate($sql, $aryUserFields, array("LastInsertId" => true));
            $inputFields["idUsers"] = $idUsers; // add the new userId to the inputFields array

            $typeId = $inputFields['typeId'];
            if ($typeId != 1) {
                // -------------------------------- Add to Client/Doctor Lookup table
                $this->updateUserLookups($inputFields, true);

                if ($typeId == 2 && is_array($inputFields['userSettings']) && array_key_exists(4, $inputFields['userSettings']) && !empty($multiUsers)) {
                    // -------------------------------- Add Multi Users (clients only)
                    $this->updateMultiUser($idUsers, $multiUsers);
                }

                if ($typeId == 7) {

                }
            } else {
                if (array_key_exists("adminClientId", $inputFields) && $inputFields['adminClientId'] != 0) {
                    // this admin is assigned to a client
                } else {
                    // this admin can manage all users
                }

                $this->updateUserLookups($inputFields, true);
            }

            // -------------------------------- Update User/Admin Settings Lookup table
            $userSettingsUpdated = $this->updateUserSettings($inputFields);

            // ------------------------------------------------- Update any Common Tests/Excluded Tests that may have changed
            $commonTestsUpdated = $this->updateCommonTests($inputFields);
            $excludedTestsUpdated = $this->updateExcludedTests($inputFields);
            // ------------------------------------------------- Update Common Diagnosis Codes
            $commonDiagnosisCodesUpdated = $this->updateCommonDiagnosisCodes($inputFields);

            $commonDrugsUpdates = $this->updateCommonDrugs($inputFields);

            $aryAdminLogs['action'] = 1;
            ResultLogDAO::addAdminLogEntry($inputFields["idUsers"], $aryAdminLogs);

            return true;
        } else {
            $_SESSION['errMsg'] = $this->UserValidator->InvalidFields;
            $_SESSION['inputFields'] = $inputFields;
            return false;
        }

    }

    public function updateUser(array $inputFields) {
        $this->UserValidator = new UserValidator($inputFields);
        $this->UserValidator->userIsValid();
        $aryAdminLogs = array(
            "adminUserId" => $_SESSION['id'],
            "userTypeId" => $inputFields['typeId'],
            "email" => $inputFields['email'],
            "Ip" => null
        );
        if (array_key_exists("Ip", $inputFields) && $inputFields['Ip'] != null && !empty($inputFields['Ip'])) {
            $aryAdminLogs['Ip'] = $inputFields['Ip'];
        }

        if (!$this->UserValidator->IsValid && array_key_exists("email2", $this->UserValidator->InvalidFields)) {
            $sql = "
                UPDATE " . self::DB_CSS . "." . self::TBL_USERS . "
                SET email = ?
                WHERE email = ?";
            $newEmail = $inputFields['email'] . date("YmdHis");
            parent::manipulate($sql, array($newEmail, $inputFields['email']));
            $aryAdminLogs['action'] = 6;
            ResultLogDAO::addAdminLogEntry($inputFields["idUsers"], $aryAdminLogs);
            $this->UserValidator->setIsValid(true);
        }

        if ($this->UserValidator->IsValid) {
            $updatedLookups = $this->updateUserLookups($inputFields);

            // ------------------------------------------------- Update any Common Tests/Excluded Tests that may have changed
            $commonTestsUpdated = $this->updateCommonTests($inputFields);
            $excludedTestsUpdated = $this->updateExcludedTests($inputFields);
            $commonDrugsUpdates = $this->updateCommonDrugs($inputFields);

            // ------------------------------------------------- Update any User Settings that may have changed            
            $userSettingsUpdated = $this->updateUserSettings($inputFields);

            // ------------------------------------------------- Add/Remove any Multi User's that may have changed for this Client
            $multiUsers = $inputFields['multiUsers'];
            unset($inputFields['multiUsers']);
            $this->UserFields = $inputFields;

            if (isset($inputFields['userSettings']) && !empty($inputFields['userSettings']) && array_key_exists(4, $inputFields['userSettings']) && $inputFields['typeId'] == 2) {
                $this->updateMultiUser($inputFields['idUsers'], $multiUsers);
            }

            $updatedLookups = $this->updateUserLookups($inputFields);

            // ------------------------------------------------- Update the typeId, email, and dateUpdated in the WebUsers table
            $this->updateUserInfo($inputFields);

            //if (array_key_exists("commonDiagnosisCodes", $inputFields) && is_array($inputFields['commonDiagnosisCodes']) && count($inputFields['commonDiagnosisCodes']) > 0) {
            $commonDiagnosisCodesUpdated = $this->updateCommonDiagnosisCodes($inputFields);
            //}

            $aryAdminLogs['action'] = 2;
            ResultLogDAO::addAdminLogEntry($inputFields["idUsers"], $aryAdminLogs);
            return true;
        } else {
            $_SESSION['errMsg'] = $this->UserValidator->InvalidFields;
            $_SESSION['inputFields'] = $inputFields;
            return false;
        }
    }


    public function updateUserInfo(array $inputFields) {
        // ------------------------------------------------- Update the typeId, email, and dateUpdated in the WebUsers table
        $sql = "
                UPDATE " . self::DB_CSS . "." . self::TBL_USERS . "
                SET typeId = ?, email = ?, dateUpdated = ? ";
        $input = array ($inputFields['typeId'], $inputFields['email'], date("Y-m-d H:i.s"));
        if (array_key_exists("password", $inputFields) && array_key_exists("password2", $inputFields) && array_key_exists("resetPasswordInput", $inputFields) && $inputFields['resetPasswordInput'] == 1) {
            // Use the Auth class to generate a new password, user salt, and verification code (in case we ever decide to use email verification to reset passwords)
            $aryInfo = $this->getNewLoginInfo($inputFields);
            //$input[] = $this->UserFields['password'];
            //$input[] = $this->UserFields['userSalt'];
            //$input[] = $this->UserFields['verificationCode'];
            $input[] = $aryInfo[1];
            $input[] = $aryInfo[0];
            $input[] = $aryInfo[2];
            $sql .= ", password = ?, userSalt = ?, verificationCode = ? ";

            $passwordLogSql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_LOG . " (userId, typeId, ip) VALUES (?, ?, ?)";

            $ip = null;
            if (array_key_exists("Ip", $inputFields)) {
                $ip = $inputFields['Ip'];
            }

            parent::manipulate($passwordLogSql, array($inputFields['idUsers'], 18, $ip));
        }
        $input[] = $inputFields['idUsers'];
        $sql .= "WHERE idUsers = ?";
        parent::manipulate($sql, $input);
    }

    public function updatePassword(array $inputFields) {
        $sql = "
            UPDATE " . self::DB_CSS . "." . self::TBL_USERS . "
            SET password = ?, userSalt = ?, verificationCode = ?
            WHERE idUsers = ?";
        $aryInfo = $this->getNewLoginInfo($inputFields);
        $password = $aryInfo[1];
        $userSalt = $aryInfo[0];
        $verificationCode = $aryInfo[2];
        $aryInput = array($password, $userSalt, $verificationCode, $inputFields['idUsers']);
        parent::manipulate($sql, $aryInput);

        $ip = null;
        if (array_key_exists("Ip", $inputFields)) {
            $ip = $inputFields['Ip'];
        }

        $passwordLogSql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_LOG . " (userId, typeId, ip) VALUES (?, ?, ?)";
        parent::manipulate($passwordLogSql, array($inputFields['idUsers'], 18, $ip));
    }


    /**
     *
     * @param type $idUsers The id of the user to be deleted
     * @param type $typeId The type of the user to be deleted
     */
    public function deleteUser($idUsers, $typeId, $ip = null) {
        $input = array(false, $idUsers);
        $sql = "
            UPDATE " . self::DB_CSS . "." . self::TBL_USERS . "
            SET isActive = ?
            WHERE idUsers = ?";
        parent::manipulate($sql, $input);
        //$user = ResultUserCreator::getResultUser(array("idUsers" => $idUsers));
        /*$aryAdminLogs = array(
            "adminUserId" => $_SESSION['id'],
            "userTypeId" => $user->typeId,
            "email" => $user->email,
            "action" => 3,
            "Ip" => null
        );*/

        $email = "";
        $uDAO = new UserDAO(array(
            "userId" => $idUsers,
            "typeId" => $typeId
        ));
        $userIsSet = $uDAO->getUser();
        if ($userIsSet && isset($uDAO->User)) {
            $email = $uDAO->User->email;
        }
        $aryAdminLogs = array(
            "adminUserId" => $_SESSION['id'],
            "userTypeId" => $idUsers,
            "email" => $email,
            "action" => 3,
            "Ip" => $ip
        );
        if ($ip != null) {
            $aryAdminLogs['Ip'] = $ip;
        }
        ResultLogDAO::addAdminLogEntry($idUsers, $aryAdminLogs);

        /*
        // ----------------------- Delete from Client Lookup tables
        $sql = "DELETE FROM " . self::TBL_CLIENTLOOKUP . " WHERE userId = ?";
        parent::manipulate($sql, $input);
        // ----------------------- Delete from Client Lookup tables
        $sql = "DELETE FROM " . self::TBL_DOCTORLOOKUP . " WHERE userId = ?";
        parent::manipulate($sql, $input);
        // ----------------------- Delete from User Settings Lookup table
        $sql = "DELETE FROM " . self::TBL_USERSETTINGSLOOKUP . " WHERE userId = ?";
        parent::manipulate($sql, $input);
        // ----------------------- Delete from Admin Settings Lookup table
        $sql = "DELETE FROM " . self::TBL_ADMINSETTINGSLOOKUP . " WHERE userId = ?";
        parent::manipulate($sql, $input);
        // ----------------------- Delete from MultiUser table - if client
        $sql = "DELETE FROM " . self::TBL_MULTIUSER . " WHERE userId = ?";
        parent::manipulate($sql, $input);
        $sql = "DELETE FROM " . self::TBL_MULTIUSER . " WHERE multiUserId = ?";
        parent::manipulate($sql, $input);
        // ---- delete from logged in user table --
        $sql = "DELETE FROM " . self::TBL_LOGGEDINUSER . " WHERE userId = ?";
        parent::manipulate($sql, $input);
        $sql = "DELETE FROM " . self::TBL_COMMONTESTS . " WHERE userId = ?";
        parent::manipulate($sql, $input);
        $sql = "DELETE FROM " . self::TBL_EXCLUDEDTESTS . " WHERE userId = ?";
        parent::manipulate($sql, $input);
        $sql = "DELETE FROM " . self::TBL_COMMONDIAGNOSISCODES . " WHERE userId = ?";
        parent::manipulate($sql, $input);
        // ----------------------- Delete from WebUser table
        $sql = "DELETE FROM " . self::TBL_USERS . " WHERE idUsers = ?";
        parent::manipulate($sql, $input);
        */
    }

    private function updateCommonDiagnosisCodes($inputFields) {
        $idUsers = $inputFields['idUsers'];
        $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_COMMONDIAGNOSISCODES . " WHERE userId = ?";
        parent::manipulate($sql, array($idUsers), array("Conn" => $this->Conn));
        if (array_key_exists("commonDiagnosisCodes", $inputFields) && is_array($inputFields['commonDiagnosisCodes']) && count($inputFields['commonDiagnosisCodes']) > 0) {

            $commonDiagnosisCodes = $inputFields['commonDiagnosisCodes'];
            $aryInput = array();
            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_COMMONDIAGNOSISCODES . " (userId, diagnosisCodeId) VALUES ";
            foreach ($commonDiagnosisCodes as $code) {
                $sql .= "(?, ?), ";
                $aryInput[] = $idUsers;
                $aryInput[] = $code;
            }
            $sql = substr($sql, 0, strlen($sql) - 2);

            /*echo "<pre>"; print_r($inputFields); echo "</pre>";
            echo $sql;
            echo "<pre>"; print_r($aryInput); echo "</pre>";*/

            parent::manipulate($sql, $aryInput, array("Conn" => $this->Conn));
        }
        return true;
    }

    private function updateCommonTests(array $inputFields) {
        $idUsers = $inputFields['idUsers'];
        $sql = "SELECT c.idCommonTests, c.userId, c.testNumber FROM " . self::DB_CSS . "." . self::TBL_COMMONTESTS . " c WHERE c.userId = ?";
        $data = parent::select($sql, array($idUsers));
        if (count($data) > 0) {
            $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_COMMONTESTS . " WHERE userId = ? AND ( ";
            $aryParams = array($idUsers);
            foreach ($data as $row) {
                $sql .= " idCommonTests = ? OR ";
                $aryParams[] = $row['idCommonTests'];
            }
            $sql = substr($sql, 0, strlen($sql) - 4);
            $sql .= ")";
            parent::manipulate($sql, $aryParams);
        }
        if (array_key_exists("commonTests", $inputFields) && is_array($inputFields['commonTests'])) {
            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_COMMONTESTS . " (userId, testNumber) VALUES ";
            $aryParams = array();
            foreach ($inputFields['commonTests'] as $testNumber) {
                $sql .= "(?, ?), ";
                $aryParams[] = $idUsers;
                $aryParams[] = $testNumber;
            }
            $sql = substr($sql, 0, strlen($sql) - 2);
            parent::manipulate($sql, $aryParams);
        }
        return true;
    }

    private function updateCommonDrugs(array $inputFields) {
        $idUsers = $inputFields['idUsers'];
        $sql = "
            SELECT cd.idCommonDrugs, cd.userId, cd.drugId
            FROM " . self::DB_CSS . "." . self::TBL_COMMONDRUGS . " cd
            WHERE cd.userId = ?";
        $data = parent::select($sql, array($idUsers));
        if (count($data) > 0) {
            $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_COMMONDRUGS . " WHERE userId = ? AND ( ";
            $aryParams = array($idUsers);
            foreach ($data as $row) {
                $sql .= " idCommonDrugs = ? OR ";
                $aryParams[] = $row['idCommonDrugs'];
            }
            $sql = substr($sql, 0, strlen($sql) - 4);
            $sql .= ")";
            parent::manipulate($sql, $aryParams);
        }
        if (array_key_exists("commonDrugs", $inputFields) && is_array($inputFields['commonDrugs'])) {
            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_COMMONDRUGS . " (userId, drugId) VALUES ";
            $aryParams = array();
            foreach ($inputFields['commonDrugs'] as $drugId) {
                $sql .= "(?, ?), ";
                $aryParams[] = $idUsers;
                $aryParams[] = $drugId;
            }
            $sql = substr($sql, 0, strlen($sql) - 2);
            parent::manipulate($sql, $aryParams);
        }

        return true;
    }

    private function updateExcludedTests(array $inputFields) {
        $idUsers = $inputFields['idUsers'];
        $sql = "SELECT e.idExcludedTests, e.userId, e.testNumber FROM " . self::TBL_EXCLUDEDTESTS . " e WHERE e.userId = ?";
        $data = parent::select($sql, array($idUsers));
        if (count($data) > 0) {
            $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_EXCLUDEDTESTS . " WHERE userId = ? AND ( ";
            $aryParams = array($idUsers);
            foreach ($data as $row) {
                $sql .= " idExcludedTests = ? OR ";
                $aryParams[] = $row['idExcludedTests'];
            }
            $sql = substr($sql, 0, strlen($sql) - 4);
            $sql .= ")";
            parent::manipulate($sql, $aryParams);
        }
        if (array_key_exists("excludedTests", $inputFields) && is_array($inputFields['excludedTests'])) {
            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_EXCLUDEDTESTS . " (userId, testNumber) VALUES ";
            $aryParams = array();
            foreach ($inputFields['excludedTests'] as $testId) {
                $sql .= "(?, ?), ";
                $aryParams[] = $idUsers;
                $aryParams[] = $testId;
            }
            $sql = substr($sql, 0, strlen($sql) - 2);
            parent::manipulate($sql, $aryParams);
        }
        return true;
    }

    /**
     * Update the Client/Doctor lookup table(s) for this User
     */
    private function updateUserLookups(array $inputFields, $isNewUser = false) {
        $typeId = $inputFields['typeId'];
        $input = array($inputFields['idUsers']);
        if ($typeId == 1) { // ---------- This User is an Admin
            $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " WHERE userId = ?";
            parent::manipulate($sql, $input);

            if (array_key_exists("adminClientId", $inputFields) && $inputFields['adminClientId'] != 0) {
                $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " (userId, clientId) VALUES (?, ?)";
                $input[] = $inputFields['adminClientId'];
                parent::manipulate($sql, $input);
            }

            // delete from doctor lookup
            $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " WHERE userId = ?";
            parent::manipulate($sql, $input);
            // delete from salesmen user lookup
            $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_SALESMENLOOKUP . " WHERE userId = ?";
            parent::manipulate($sql, $input);
            // delete from insurance user lookup
            $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_INSURANCELOOKUP . " WHERE userId = ?";
            parent::manipulate($sql, $input);
            return true;
        } else if ($typeId == 2) { // ---------- This User is a Client
            $client = ClientDAO::getClient(array("idUsers" => $inputFields['idUsers']));
            if (is_bool($client) && !$isNewUser) {
                // delete from doctor lookup
                $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " WHERE userId = ?";
                parent::manipulate($sql, $input);
                // delete from salesmen user lookup
                $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_SALESMENLOOKUP . " WHERE userId = ?";
                parent::manipulate($sql, $input);
                // delete from insurance user lookup
                $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_INSURANCELOOKUP . " WHERE userId = ?";
                parent::manipulate($sql, $input);
            }
            //if (!is_bool($client) && $client->idClients != $inputFields['clientId']) { // was already a client, but changed the selected client - delete old client lookup id and insert new client lookup id

            if ($client instanceof  ClientUser) {
                $oldClientId = $client->idClients;
                $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " WHERE clientId = ? AND userId = ?";
                parent::manipulate($sql, array($oldClientId, $inputFields['idUsers']));
            }

            //}
            //if ($isNewUser) {
            // insert new client id into client lookup table
            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " (userId, clientId) VALUES (?, ?)";
            $input[] = $inputFields['clientId'];
            parent::manipulate($sql, $input);

            // add any notifications that may be set for this client/doctor

            $sql = "SELECT DISTINCT nl.notificationId
                    FROM " . self::DB_CSS . "." . self::TBL_USERNOTIFICATIONLOOKUP . " nl
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON nl.userId = cl.userId
                    WHERE cl.clientId = ?";
            $data = parent::select($sql, array($inputFields['clientId']));

            if (count($data) > 0) {
                $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_USERNOTIFICATIONLOOKUP . " (userId, notificationId) VALUES ";
                $aryInput = array();
                foreach ($data as $row) {
                    $sql .= "(?, ?), ";
                    $aryInput[] = $inputFields['idUsers'];
                    $aryInput[] = $row['notificationId'];
                }
                $sql = substr($sql, 0, strlen($sql) - 2);
                parent::manipulate($sql, $aryInput);
            }
            //}
            return true;
        } else if ($typeId == 3) { // ---------- This User is a Doctor
            $doctor = DoctorDAO::getDoctor(array("idUsers" => $inputFields['idUsers']));
            if ($isNewUser) { // this is a new user, so insert the record to the doctor lookup
                $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " (userId, doctorId) VALUES (?, ?)";
                $input[] = $inputFields['doctorId'];
                parent::manipulate($sql, $input);
            } else if (is_bool($doctor)) { // this doctor was previously another user type, so delete the user's client lookup (if any) and insert into doctor lookup
                // delete from client lookup
                $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " WHERE userId = ?";
                parent::manipulate($sql, $input);
                // delete from salesmen user lookup
                $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_SALESMENLOOKUP . " WHERE userId = ?";
                parent::manipulate($sql, $input);
                // delete from insurance user lookup
                $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_INSURANCELOOKUP . " WHERE userId = ?";
                parent::manipulate($sql, $input);
                // insert into doctor lookup
                $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " (userId, doctorId) VALUES (?, ?)";
                $input[] = $inputFields['doctorId'];
                parent::manipulate($sql, $input);
            } else { // this user was already a doctor, so just update their existing record in the doctor lookup table
                $sql = "UPDATE " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " SET doctorId = ? WHERE userId = ?";
                $input['doctorId'] = $inputFields['doctorId'];
                $doctorInput = array($inputFields['doctorId'], $inputFields['idUsers']);
                parent::manipulate($sql, $doctorInput);
            }
            return true;
        } else if ($typeId == 4) { // This is a patient
            require_once 'DAOS/PatientDAO.php';
            $patientExists = PatientDAO::patientExists($inputFields['patientId']);

            //if (!$patientExists) {
                $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_PATIENTLOOKUP . " (userId, patientId) VALUES (?, ?)";
                $input[] = $inputFields['patientId'];
                parent::manipulate($sql, $input);
            //}

        } else if ($typeId == 5) { // This is a Salesman
            if (!$isNewUser) {
                // delete from client lookup
                $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " WHERE userId = ?";
                parent::manipulate($sql, $input);
                // delete from doctor lookup
                $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " WHERE userId = ?";
                parent::manipulate($sql, $input);
                // delete from insurance user lookup
                $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_INSURANCELOOKUP . " WHERE userId = ?";
                parent::manipulate($sql, $input);

                $sql = "
                    UPDATE " . self::DB_CSS . "." . self::TBL_SALESMENLOOKUP . "
                    SET salesmenId = ?
                    WHERE userId = ?
                ";
                $salesmanInput = array($inputFields['salesmanId'], $inputFields['idUsers']);
                parent::manipulate($sql, $salesmanInput);
            } else {
                // delete from salesmen user lookup
                $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_SALESMENLOOKUP . " WHERE userId = ?";
                parent::manipulate($sql, $input);
                // insert into the salesmen lookup
                $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_SALESMENLOOKUP . " (userId, salesmenId) VALUES (?, ?)";
                $input[] = $inputFields['salesmanId'];
                parent::manipulate($sql, $input);
            }
            return true;
        } else if ($typeId == 6) { // This is an Insurance user
            if (!$isNewUser) {
                // delete from client lookup
                $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " WHERE userId = ?";
                parent::manipulate($sql, $input);
                // delete from doctor lookup
                $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " WHERE userId = ?";
                parent::manipulate($sql, $input);
                // delete from insurance user lookup
                $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_SALESMENLOOKUP . " WHERE userId = ?";

                $sql = "
                    UPDATE " . self::DB_CSS . "." . self::TBL_INSURANCELOOKUP . "
                    SET insuranceId = ?
                    WHERE userId = ?
                ";
                $insuranceInput = array($inputFields['insuranceId'], $inputFields['idUsers']);
                parent::manipulate($sql, $insuranceInput);
            } else {
                // delete from salesmen user lookup
                $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_INSURANCELOOKUP . " WHERE userId = ?";
                parent::manipulate($sql, $input);
                // insert into the salesmen lookup
                $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_INSURANCELOOKUP . " (userId, insuranceId) VALUES (?, ?)";
                $input[] = $inputFields['insuranceId'];
                parent::manipulate($sql, $input);
            }
            return true;
        } else if ($typeId == 7) { // Order Entry Admin
            if (!$isNewUser) {
                $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " WHERE userId = ?";
                parent::manipulate($sql, $input);
            }
            if (array_key_exists("adminClients", $inputFields) && count($inputFields['adminClients']) > 0) {
                $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " (userId, clientId) VALUES ";
                $aryInput = array();
                foreach ($inputFields['adminClients'] as $clientId) {
                    $sql .= "(?, ?),";
                    $aryInput[] = $inputFields['idUsers'];
                    $aryInput[] = $clientId;
                }
                $sql = substr($sql, 0, strlen($sql) - 1);
                parent::manipulate($sql, $aryInput);
            }
        }

        return false;
    }

    /*
     * Always call function updateUserSettings before calling this function to ensure the Has Order Entry setting is updated first
     */
    private function updateOrderEntrySettings(array $inputFields) {

        $sql = "
            SELECT orderEntrySettingId
            FROM " . self::DB_CSS . "." . self::TBL_ORDERENTRYSETTINGSLOOKUP . "
            WHERE userId = ?";
        $data = parent::select($sql, array($inputFields['idUsers']), array("Conn" => $this->Conn));
        $arySettingIds = array();
        if (count($data) > 0) {
            foreach ($data as $row) {
                $arySettingIds[] = $row['orderEntrySettingId'];
            }
        }

        $userId = $inputFields['idUsers'];
        $sqlDelete = "DELETE FROM " . self::DB_CSS . "." . self::TBL_ORDERENTRYSETTINGSLOOKUP . " WHERE userId = ? AND ";
        if ((array_key_exists("orderEntrySettings", $inputFields) && is_array($inputFields['orderEntrySettings'])) || $inputFields['typeId'] == 7) {
            // this user has order entry and also has order entry settings
            $sqlInsert = "INSERT INTO " . self::DB_CSS . "." . self::TBL_ORDERENTRYSETTINGSLOOKUP . " (userId, orderEntrySettingId) VALUES ";
            $aryInsertInput = array();
            $aryDeleteInput = array($inputFields['idUsers']);
            foreach ($inputFields['orderEntrySettings'] as $orderEntrySettingId) {
                if (!in_array($orderEntrySettingId, $arySettingIds)) { // insert setting if not previously added for user
                    $sqlInsert .= "(?, ?), ";
                    $aryInsertInput[] = $userId;
                    $aryInsertInput[] = $orderEntrySettingId;
                }
                $sqlDelete .= " orderEntrySettingId != ? AND "; // do not delete selected settings
                $aryDeleteInput[] = $orderEntrySettingId;
            }

            if (count($aryInsertInput) > 0) {
                $sqlInsert = substr($sqlInsert, 0, strlen($sqlInsert) - 2);
                parent::manipulate($sqlInsert, $aryInsertInput, array("Conn" => $this->Conn));
            }

            $sqlDelete = substr($sqlDelete, 0, strlen($sqlDelete) - 4);
            parent::manipulate($sqlDelete, $aryDeleteInput, array("Conn" => $this->Conn));
        } else {
            $sqlDelete = substr($sqlDelete, 0, strlen($sqlDelete) - 4);
            parent::manipulate($sqlDelete, array($userId), array("Conn" => $this->Conn));
        }

        if (array_key_exists("orderAccess", $inputFields)) {
            $sql = "
                SELECT sl.idAccessLookups, sl.settingId, sl.userId
                FROM " . self::DB_CSS . "." . self::TBL_ORDERACCESSSETTINGSLOOKUP . " sl
                WHERE sl.userId = ?";
            $data = parent::select($sql, array($userId), array("Conn" => $this->Conn));

            if (count($data) > 0) {
                // Update access setting
                $sql = "UPDATE " . self::DB_CSS . "." . self::TBL_ORDERACCESSSETTINGSLOOKUP . " SET settingId = ? WHERE userId = ?;";
                parent::manipulate($sql, array($inputFields['orderAccess'], $userId), array("Conn" => $this->Conn));
            } else {
                // Insert access setting
                $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_ORDERACCESSSETTINGSLOOKUP . " (settingId, userId) VALUES (?, ?);";
                parent::manipulate($sql, array($inputFields['orderAccess'], $userId), array("Conn" => $this->Conn));
            }

            $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_RESTRICTEDUSERS . " WHERE userId = ?;";
            parent::manipulate($sql, array($userId), array("Conn" => $this->Conn));

            if (array_key_exists("restrictedUsers", $inputFields) && is_array($inputFields['restrictedUsers']) && ($inputFields['orderAccess'] == 3 || $inputFields['orderAccess'] == 4)) {

                $aryQueryParams = array();
                $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_RESTRICTEDUSERS . " (userId, restrictedUserId) VALUES ";
                foreach ($inputFields['restrictedUsers'] as $restrictedUserId) {
                    $sql .= "(?, ?), ";
                    $aryQueryParams[] = $userId;
                    $aryQueryParams[] = $restrictedUserId;
                }
                $sql = substr($sql, 0, strlen($sql) - 2);
                parent::manipulate($sql, $aryQueryParams, array("Conn" => $this->Conn));
            }

        } else {
            // Delete access setting
            $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_ORDERACCESSSETTINGSLOOKUP . " WHERE userId = ?;";
            parent::manipulate($sql, array($userId), array("Conn" => $this->Conn));
            $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_RESTRICTEDUSERS . " WHERE userId = ?;";
            parent::manipulate($sql, array($userId), array("Conn" => $this->Conn));
        }

        $drawDisabledSettingId = null;
        $uploadDisabledSettingId = null;
        if (array_key_exists("eSignatureType", $inputFields)) {
            $sql = "SELECT idOrderEntrySettings FROM " . self::DB_CSS . "." . self::TBL_ORDERENTRYSETTINGS . " WHERE settingName = ?";

            $data = parent::select($sql, array("Draw ESignature Disabled"), array("Conn" => $this->Conn));
            if (count($data) > 0) {
                $drawDisabledSettingId = $data[0]['idOrderEntrySettings'];
            }

            $data = parent::select($sql, array("Upload ESignature Disabled"), array("Conn" => $this->Conn));
            if (count($data) > 0) {
                $uploadDisabledSettingId = $data[0]['idOrderEntrySettings'];
            }

            if ($drawDisabledSettingId != null && $uploadDisabledSettingId != null) {
                $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_ORDERENTRYSETTINGSLOOKUP . " WHERE userId = ? AND (orderEntrySettingId = ? OR orderEntrySettingId = ?)";
                parent::manipulate($sql, array($userId, $drawDisabledSettingId, $uploadDisabledSettingId), array("Conn" => $this->Conn));

                $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_ORDERENTRYSETTINGSLOOKUP . " (userId, orderEntrySettingId) VALUES (?, ?)";

                if ($inputFields['eSignatureType'] == 2) {
                    parent::manipulate($sql, array($userId, $uploadDisabledSettingId), array("Conn" => $this->Conn));
                } else if ($inputFields['eSignatureType'] == 3) {
                    parent::manipulate($sql, array($userId, $drawDisabledSettingId), array("Conn" => $this->Conn));
                }
            }
        }





        /*if ($setting == 3 && array_key_exists("orderEntrySettings", $inputFields)) {
            // current setting is "Has Order Entry" and there are Order Entry Settings selected
            $sql2 = "INSERT INTO " . self::DB_CSS . "." . self::TBL_ORDERENTRYSETTINGSLOOKUP . " VALUES "
        }*/
    }

    private function updateUserSettings(array $inputFields, $isNewUser = false) {
        $typeId = $inputFields['typeId'];
        if (!$isNewUser) {
            $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_USERSETTINGSLOOKUP . " WHERE userId = ?";
            parent::manipulate($sql, array($inputFields['idUsers']));
            $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_ADMINSETTINGSLOOKUP . " WHERE userId = ? AND adminSettingId != ?";
            parent::manipulate($sql, array($inputFields['idUsers'], 8)); // 8 = "Is Master Admin"
//            $sql = "DELETE FROM " . self::TBL_SALESSETTINGSLOOKUP . " WHERE userId = ?";
//            parent::manipulate($sql, array($inputFields['idUsers']));
        }
        if ($typeId != 1 && isset($inputFields['userSettings']) && !empty($inputFields['userSettings']) && is_array($inputFields['userSettings']) && count($inputFields['userSettings']) > 0) {
            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_USERSETTINGSLOOKUP . " (userId, userSettingId) VALUES ";
            $input = array();
            $i = 1;
            foreach ($inputFields['userSettings'] as $setting) {
                if (($setting == 4 && $typeId == 2) || $setting != 4) {
                    $sql .= "(?, ";
                    $input[] = $inputFields['idUsers'];
                    $i++;
                    $sql .= "?), ";
                    $input[] = $setting;
                    $i++;
                }
            }
            $sql = substr($sql, 0, strlen($sql) - 2);
            parent::manipulate($sql, $input);

            $this->updateOrderEntrySettings($inputFields);

        } elseif (isset($inputFields['adminSettings']) && !empty($inputFields['adminSettings']) && is_array($inputFields['adminSettings']) && count($inputFields['adminSettings']) > 0) {
            // insert admin settings
            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_ADMINSETTINGSLOOKUP . " (userId, adminSettingId) VALUES ";
            $input = array();
            $i = 1;
            foreach ($inputFields['adminSettings'] as $setting) {
                $sql .= "(?, ";
                $input[] = $inputFields['idUsers'];
                $i++;
                $sql .= "?), ";
                $input[] = $setting;
                $i++;
            }
            $sql = substr($sql, 0, strlen($sql) - 2);
            parent::manipulate($sql, $input);
        } else if (isset($inputFields['salesSettings']) && !empty($inputFields['salesSettings']) && is_array($inputFields['salesSettings']) && count($inputFields['salesSettings']) > 0) {
//            $sql = "INSERT INTO " . self::TBL_SALESSETTINGSLOOKUP . " (userId, salesSettingId) VALUES ";
//            $input = array();
//            $i = 1;
//            foreach ($inputFields['salesSettings'] as $setting) {
//                if (($setting == 4 && $typeId == 2) || $setting != 4) {
//                    $sql .= "(?, ";
//                    $input[] = $inputFields['idUsers'];
//                    $i++;
//                    $sql .= "?), ";
//                    $input[] = $setting;
//                    $i++;
//                }
//            }
//            $sql = substr($sql, 0, strlen($sql) - 2);
//            parent::manipulate($sql, $input);
        } else if ($inputFields['typeId'] == 7) {
            $this->updateOrderEntrySettings($inputFields);
        }
        return true;
    }

    private function updateMultiUser($idUsers, $multiUsers) {
        $aryUserList = $this->getMultiUserList($idUsers); // get list of current multi views for this client
        $this->deleteMultiUsers($idUsers, $aryUserList, $multiUsers);
        $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_MULTIUSER . " (userId, multiUserId) VALUES ";
        $bindValues = array();
        $i = 1;
        if (isset($multiUsers) && is_array($multiUsers) && count($multiUsers) > 0) {
            foreach ($multiUsers as $multiUser) {
                if (!in_array($multiUser, $aryUserList)) {
                    $sql .= "(?, ?), ";
                    //$sql .= "(" . $idUsers . ", " . $multiUser . "), ";
                    $bindValues[] = $idUsers;
                    $i++;
                    $bindValues[] = $multiUser;
                    $i++;
                }
            }
        }

        $sql = substr($sql, 0, strlen($sql) - 2);
        if ($i > 1) {
            parent::manipulate($sql, $bindValues);
        }
    }

    public function getUsers(array $input = null) {
        $sql = "
            SELECT      u.idUsers, u.typeId, u.email, u.password, u.userSalt, u.isVerified, u.verificationCode, u.dateCreated, u.dateUpdated,
                        d.iddoctors, d.number, d.firstName, d.lastName, d.address1, d.city, d.state, d.zip,
                        c.idClients, c.clientNo, c.clientName, c.clientStreet, c.clientCity, c.clientState, c.clientZip,
                        s.idsalesmen, t.territoryName, sg.groupName,
                        e.firstName AS `salesmanFirstName`, e.lastName AS `salesmanLastName`,
                        i.idinsurances, i.name as `insuranceName`, i.phone AS `insurancePhone`, i.address AS `insuranceAddress`,
                        p.idPatients, p.arNo, p.firstName AS `patientFirstName`, p.lastName AS `patientLastName`, p.dob,
                        i.city AS `insuranceCity`, i.state AS `insuranceState`, i.zip AS `insuranceZip`,
                        CAST(CONCAT(el.lastName, ', ', el.firstName) AS CHAR) AS `groupLeader`,
                        ut.typeName, was.idAdminSettings, was.settingName, was.settingDescription,
                        lu.idLoggedIn, lu.loginDate
            FROM        " . self::DB_CSS . "." . self::TBL_USERS . " u
            INNER JOIN  " . self::DB_CSS . "." . self::TBL_USERTYPES . " ut ON u.typeId = ut.idTypes
            LEFT JOIN   " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON u.idUsers = cl.userId
            LEFT JOIN   " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON u.idUsers = dl.userId
            LEFT JOIN	" . self::DB_CSS . "." . self::TBL_SALESMENLOOKUP . " sl ON u.idUsers = sl.userId
            LEFT JOIN   " . self::DB_CSS . "." . self::TBL_INSURANCELOOKUP . " il ON u.idUsers = il.userId
            LEFT JOIN   " . self::DB_CSS . "." . self::TBL_PATIENTLOOKUP . " pl ON u.idUsers = pl.userId
            LEFT JOIN   " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON c.idClients = cl.clientId
            LEFT JOIN   " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON d.iddoctors = dl.doctorId
            LEFT JOIN 	" . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON sl.salesmenId = s.idsalesmen
            LEFT JOIN   " . self::DB_CSS . "." . self::TBL_INSURANCES . " i ON il.insuranceId = i.idinsurances
            LEFT JOIN   " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON pl.patientId = p.idPatients
            LEFT JOIN	" . self::DB_CSS . "." . self::TBL_EMPLOYEES ." e ON s.employeeID = e.idemployees
            LEFT JOIN	" . self::DB_CSS . "." . self::TBL_TERRITORY . " t ON s.territory = t.idterritory
            LEFT JOIN	" . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON s.salesGroup = sg.id
            LEFT JOIN 	" . self::DB_CSS . "." . self::TBL_SALESMEN . " sgl ON sg.groupLeader = sgl.idsalesmen
            LEFT JOIN 	" . self::DB_CSS . "." . self::TBL_EMPLOYEES . " el ON sgl.employeeID = el.idemployees
            LEFT JOIN   " . self::DB_CSS . "." . self::TBL_ADMINSETTINGSLOOKUP . " asl ON u.idUsers = asl.userId
            LEFT JOIN   " . self::DB_CSS . "." . self::TBL_ADMINSETTINGS . " was ON asl.adminSettingId = was.idAdminSettings
            LEFT JOIN   " . self::DB_CSS . "." . self::TBL_LOGGEDINUSER . " lu ON u.idUsers = lu.userId
            WHERE       u.isActive = ? AND ut.isActive = ?";

        $aryInput = array(1, 1);

        if (isset($this->clientId) && !empty($this->clientId)) {
            $sql .= " AND c.idClients = ? ";
            $aryInput[] = $this->clientId;
        }

        if ($input != null && is_array($input)) {
            /*if (array_key_exists("typeId", $input)) {
                $sql .= " AND u.typeId = ? ";
                $aryInput[] = $input['typeId'];
            }*/
            if (array_key_exists("clientName", $input)) {
                $sql .= " AND (c.clientName IS NULL OR c.clientName LIKE ?)";
                $aryInput[] = "%" . $input['clientName'] . "%";
            }
        }

        $sql .= " ORDER BY ut.idTypes, u.idUsers";

        /*error_log($sql);
        error_log(implode(", ", $aryInput));*/

        $qryUsers = parent::select($sql, $aryInput, array("Conn" => $this->Conn));
        $users = array(
            "Administrators" => array(),
            "Clients" => array(),
            "Doctors" => array(),
            "Salesmen" => array(),
            "Insurances" => array(),
            "OrderEntryAdmins" => array(),
            "Patients" => array(),
            "PatientAdmins" => array()
        );
        $i = 0;
        foreach ($qryUsers as $row) {
            $typeId = $row['typeId'];
            if ($i == 0) {
                $currUserId = $row['idUsers'];
            }
            if ($i == 0 || $currUserId != $row['idUsers']) {
                $currUserId = $row['idUsers'];
                if ($typeId == 1) {
                    $admin = new AdminUser($row);
                    $users['Administrators'][] = $admin;
                } else if ($typeId == 2) {
                    $client = new ClientUser($row);
                    $users['Clients'][] = $client;
                } else if ($typeId == 3) {
                    $doctor = new DoctorUser($row);
                    $users['Doctors'][] = $doctor;
                } else if ($typeId == 4) {
                    if (isset($row['dob']) && !empty($row['dob']) && !is_bool($row['dob'])) {
                        $objDate = DateTime::createFromFormat('Y-m-d h:i:s', $row['dob']);
                        $dob = $objDate->format('n/j/Y');
                        $row['dob'] = $dob;
                    }

                    $patient = new PatientUser($row);
                    $users['Patients'][] = $patient;
                } else if ($typeId == 5) {
                    $salesman = new SalesmanUser($row);
                    $users['Salesmen'][] = $salesman;
                } else if ($typeId == 6) {
                    $users['Insurances'][] = new InsuranceUser($row);
                } else if ($typeId == 7) {
                    $users['OrderEntryAdmins'][] = new AdminUser($row);
                } else if ($typeId == 8) {
                    $users['PatientAdmins'][] = new PatientAdminUser($row);
                }
            } else if ($row['typeId'] == 1 && $row['idUsers'] == $users['Administrators'][count($users['Administrators']) - 1]->idUsers) {
                $users['Administrators'][count($users['Administrators']) - 1]->addAdminSetting($row);
            }
            $i++;
        }

        if (isset($this->UserId) && !empty($this->UserId) && isset($this->clientId) && !empty($this->clientId)) {
            $doctorUsers = $this->getRelatedDoctors();
            if (count($doctorUsers) > 0) {
                foreach ($doctorUsers as $doctorUser) {
                    $users['Doctors'][] = $doctorUser;
                }
            }
        }

        return $users;
    }

    public static function getAdminSettings(array $settings = null) {
        $sql = "
            SELECT s.idAdminSettings, s.settingName, s.settingDescription, s.isMasterSetting, s.isActive
            FROM " . self::DB_CSS . "." . self::TBL_ADMINSETTINGS . " s
            WHERE s.idAdminSettings != 8
            ORDER BY s.settingName ASC";
        $data = parent::select($sql, null, $settings);
        $aryAdminSettings = array();
        foreach ($data as $row) {
            $adminSetting = new AdminSetting($row);
            $aryAdminSettings[] = $adminSetting;
        }
        return $aryAdminSettings;
    }

    public static function getAdmin (array $inputs, array $arySettings = null) {

        $includeAdminSettings = false;

        if ($arySettings != null && array_key_exists("IncludeAdminSettings", $arySettings) && $arySettings["IncludeAdminSettings"] == true) {
            $includeAdminSettings = true;
        }

        $sql = "
            SELECT *
            FROM " . self::DB_CSS . "." . self::TBL_USERS . " u ";
        if ($includeAdminSettings) {
            $sql .= "LEFT JOIN " . self::DB_CSS . "." . self::TBL_ADMINSETTINGSLOOKUP . " al ON al.userId = u.idUsers
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_ADMINSETTINGS . " s ON s.idAdminSettings = al.adminSettingId ";
        }
        $sql .= " WHERE ";
        $params = array();
        foreach ($inputs as $field => $value) {
            if ($field == "email") {
                $sql .= " u.$field = ? AND ";
            } else {
                $sql .= " $field = ? AND ";
            }
            $params[] = $value;
        }
        $sql .= " typeId = ? ";
        $params[] = 1;

        $sqlSettings = null;
        if ($arySettings != null && array_key_exists("Conn", $arySettings) && $arySettings['Conn'] instanceof mysqli) {
            $sqlSettings = array("Conn" => $arySettings['Conn']);
        }
        $data = parent::select($sql, $params, $sqlSettings);


        if (count($data) > 0) {
            $admin = new AdminUser($data[0]);
            if (count($data) > 1) {
                foreach ($data as $row) {
                    $admin->addAdminSetting($row);
                }
            }

            return $admin;
        }

        return false;
    }

    public static function getUserList(array $options = null) {
        $sql = "
            SELECT  u.idUsers, u.email, c.idClients, c.clientNo, c.clientName,
              l.idLocation, l.locationNo, l.locationName
            FROM " . self::DB_CSS . "." . self::TBL_USERS . " u
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON u.idUsers = cl.userId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON cl.clientId = c.idClients
            INNER JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " l ON c.location = l.idLocation OR c.location = 0
            WHERE u.isActive = ? ";
        $aryParams = array(true);
        $conn = null;
        $orderBy = "";
        if ($options != null) {
            if (array_key_exists("idUsers", $options)) {
                $sql .= " AND u.idUsers != ? ";
                $aryParams[] = $options['idUsers'];
            }
            if (array_key_exists("clientId", $options)) {
                $sql .= " AND c.idClients = ? ";
                $aryParams[] = $options['clientId'];
            }

            if (array_key_exists("OrderBy", $options)) {
                if ($options['OrderBy'] == "email") {
                    $orderBy .= "ORDER BY u.email";
                    if (array_key_exists("Direction", $options)) {
                        if (strtolower($options['Direction']) == "asc") {
                            $orderBy .= " ASC";
                        } else if (strtolower($options['Direction']) == "desc") {
                            $orderBy .= " DESC";
                        }
                    }
                } else if ($options['OrderBy'] == "clientNo") {
                    $orderBy .= "ORDER BY c.clientNo ";
                    if (array_key_exists("Direction", $options)) {
                        if (strtolower($options['Direction']) == "asc") {
                            $orderBy .= " ASC";
                        } else if (strtolower($options['Direction']) == "desc") {
                            $orderBy .= " DESC";
                        }
                    }
                } else if ($options['OrderBy'] == "clientName") {
                    $orderBy .= "ORDER BY c.clientName ";
                    if (array_key_exists("Direction", $options)) {
                        if (strtolower($options['Direction']) == "asc") {
                            $orderBy .= " ASC";
                        } else if (strtolower($options['Direction']) == "desc") {
                            $orderBy .= " DESC";
                        }
                    }
                }
            }
            $sql .= " GROUP BY u.idUsers ";
            $sql .= $orderBy;

            if (array_key_exists("Conn", $options) && $options['Conn'] instanceof mysqli) {
                $conn = $options['Conn'];
            }
        }

        $qryUsers = parent::select($sql, $aryParams, array("Conn" => $conn));

        return $qryUsers;
    }

    public function signInAsUser($ip) {
        $this->addSetting("IncludeUserSettings", true);

        $userIsSet = $this->getUser();

        if ($userIsSet) {

            $adminUserId = null;
            $adminTypeId = null;
            if (isset($_SESSION['AdminId']) && !empty($_SESSION['AdminId']) && isset($_SESSION['AdminType']) && $_SESSION['AdminType'] == 7) {
                $adminUserId = $_SESSION['AdminId'];
                $adminTypeId = 7;
            }

            $_SESSION['type'] = $this->User->typeId;

            $auth = new Auth(array("email" => $this->User->email, "password" => $this->User->password));

            if (isset($_SESSION['id'])) {
                self::logout($_SESSION['id'],$ip); // delete admin's user id from WebSignedInUser table
            }

            if (isset($adminUserId)) {
                $_SESSION['AdminId'] = $adminUserId;
            }

            $loginSuccessful = $auth->login(false); // false because the password is already hashed

            //if ($loginSuccessful == 0 || $loginSuccessful == 4 || $loginSuccessful == 1) {
            if ($loginSuccessful != 0) {
                //is either a client/doctor, salesman, or sales owner

                if (isset($_SESSION['users'])) {
                    $_SESSION['users'] = "";
                    unset($_SESSION['users']);
                }

                if ($this->User->typeId == 5 || $this->User->typeId == 1) {
                    return 2;
                } else {

                    if (isset($adminUserId)) {
                        $this->UserId = $adminUserId;
                        $this->TypeId = $adminTypeId;
                        $this->Settings['IncludeUserSettings'] = true;
                        $this->getUser();
                    }

                    if ($this->User->hasOrderEntrySetting(1)) { // result search is disabled
                        return 3;
                    }
                    return 1;
                }
            }
        }
        return 0;
    }

    private function getNewLoginInfo(array $inputFields = null) {
        $auth = new Auth(array("redirect" => false));
        if ($inputFields == null) {

            $auth->setLoginCredentials($this->UserFields['email'], $this->UserFields['password']);
            $auth->generateNewHash(10);

            $this->UserFields['password'] = $auth->Password;
            $this->UserFields['userSalt'] = $auth->Salt;
            $this->UserFields['verificationCode'] = $auth->VerificationCode;
        } else {

            $aryReturn = $auth->getNewUserInfo($inputFields['password'], 10);

            return $aryReturn;
        }
    }

    private function getMultiUserList($idUsers) {
        $sql = "SELECT multiUserId FROM " . self::DB_CSS . "." . self::TBL_MULTIUSER . " WHERE userId = ?";
        $multiUserList = parent::select($sql, array($idUsers));
        $aryUserList = array();
        foreach ($multiUserList as $userId) {
            $aryUserList[] = $userId['multiUserId'];
        }
        return $aryUserList;
    }

    private function deleteMultiUsers($idUsers, $currentUsers, $newUsers) {
        if (count($currentUsers) > 0) {
            // delete any that aren't in the $multiUsers list
            $deleteList = array();
            $i = 1;
            foreach ($currentUsers as $multiUser) { // check if the current multiUserId was checked in the form
                if ((is_array($newUsers) && !in_array($multiUser, $newUsers)) || $newUsers == "") {
                    // add it to the delete list
                    $deleteList[] = $multiUser;
                    $i++;
                }
            }
            if (count($deleteList) > 0) {
                $list = join(',', array_fill(0, count($deleteList), '?')); // a question mark for each id being deleted

                //$idList = implode(",", $deleteList);
                $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_MULTIUSER . " WHERE multiUserId IN ($list) AND userId = ?";
                $deleteList[] = $idUsers;
                parent::manipulate($sql, $deleteList);
            }
        }
    }

    public static function logout($userId, $ip, array $settings = null) {
        $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_LOGGEDINUSER . " WHERE userId = ? AND ip = ?";
        parent::manipulate($sql, array($userId, $ip), $settings);
        //if ($settings != null && array_key_exists("Conn", $settings) && $settings['Conn'] instanceof mysqli) {
        //    parent::manipulate($sql, array($userId), array("Conn" => $settings['Conn']));
        //} else {
        //    parent::manipulate($sql, array($userId));
        //}

        $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_ORDERBEINGEDITED . " WHERE userId = ?";
        parent::manipulate($sql, array($userId), $settings);
    }

    public static function getUserStatistics() {
        $userStats = array();

        $sql = "
            SELECT u.email, typeName, c.clientName, CONCAT(d.firstName, ' ', d.lastName) AS 'doctorName', t.name as 'logType', orderId, l.logDate
            FROM " . self::DB_CSS . "." . self::TBL_LOG . " l
            INNER JOIN " . self::DB_CSS . "." . self::TBL_LOGTYPES . " t ON l.typeId = t.idTypes
            INNER JOIN " . self::DB_CSS . "." . self::TBL_USERS . " u ON l.userId = u.idUsers
            INNER JOIN " . self::DB_CSS . "." . self::TBL_USERTYPES . " ut ON u.typeId = ut.idTypes
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_LOGVIEWS . " v ON l.idLogs = v.logId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON u.idUsers = cl.userId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON u.idUsers = dl.userId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON cl.clientId = c.idClients
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON dl.doctorId = d.idDoctors
            ORDER BY ?, ?, ? DESC
        ";
        $data = parent::select($sql, array("typeName", "t.name", "logDate"));
        return $data;
    }

    public function resetErrorFields() {
        if (isset($_SESSION['errMsg'])) {
            $_SESSION['errMsg'] = "";
            unset($_SESSION['errMsg']);
        }
        if (isset($_SESSION['errId'])) {
            $_SESSION['errId'] = "";
            unset($_SESSION['errId']);
        }
        if (isset($_SESSION['errType'])) {
            $_SESSION['errType'] = "";
            unset($_SESSION['errType']);
        }
    }
}
?>
