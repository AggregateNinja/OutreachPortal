<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 4/14/15
 * Time: 9:43 AM
 */

require_once 'DataObject.php';
require_once 'DOS/LabUser.php';

class LabUserDAO extends DataObject {

    private $Conn;
    private $User;

    private $UserId;
    private $Username;
    private $Ip;
    private $Settings;

    public function __construct(array $data, array $settings = null) {

        if (array_key_exists("UserId", $data)) {
            $this->UserId = $data['UserId'];
        } else {
            $this->UserId = null;
        }
        if (array_key_exists("Username", $data)) {
            $this->Username = $data['Username'];
        } else {
            $this->Username = null;
        }
        if (array_key_exists("Ip", $data)) {
            $this->Ip = $data['Ip'];
        } else {
            $this->Ip = null;
        }

        $this->Settings = $settings;

        $this->Conn = parent::connect();
    }

    public function getUserByUsername() {
        $sql = "
            SELECT u.idUser, u.logon, u.password, u.lastLogon, u.isAdmin, u.ugroup, u.position, u.createdBy, u.created, u.employeeId, u.active, u.changePassword
            FROM " . self::DB_CSS . "." . self::TBL_LABUSERS . " u
            WHERE logon = ? ";

        //echo "<pre>"; print_r($sql); echo "</pre>";

        $data = parent::select($sql, array($this->Username));

        if (count($data) > 0) {
            $this->User = new LabUser($data[0]);

            $this->UserId = $this->User->idUser;
        }
    }

    public function getLabUser() {
        $sql = "
            SELECT  u.idUser, u.logon, u.password, u.lastLogon, u.isAdmin, u.ugroup, u.position, u.createdBy, u.created, u.employeeId, u.active, u.changePassword,
                    lu.idLoggedIn, lu.userId, lu.sessionId, lu.token, lu.ip, lu.loginDate
            FROM " . self::DB_CSS . "." . self::TBL_LABUSERS . " u
            INNER JOIN " . self::DB_CSS . "." . self::TBL_LOGGEDINLABUSER . " lu ON u.idUser = lu.userId
            WHERE u.idUser = ? ";

        $data = parent::select($sql, array($this->UserId));

        if (count($data) > 0) {
            $this->User = new LabUser($data[0]);
            $this->Username = $this->User->logon;
        }
    }

    /** Saves information about a login session to the database for validation on each page request
     * @param $token
     * @param $sessionId
     */
    public function setNewLogin($token, $sessionId) {
        // Delete old LoggedInUser records for user
        $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_LOGGEDINLABUSER . " WHERE userId = ?";
        parent::manipulate($sql, array($this->UserId), array("Conn" => $this->Conn));

        // Insert new LoggedInUser record for user
        $sql = "
            INSERT INTO " . self::DB_CSS . "." . self::TBL_LOGGEDINLABUSER . " (userId, sessionId, token, ip, loginDate)
            VALUES (?, ?, ?, ?, ?)";

        parent::manipulate(
            $sql,
            array($this->UserId, $sessionId, $token, $this->Ip, date("Y-m-d H:i:s")),
            array("Conn" => $this->Conn)
        );
    }

    public function logout($userId) {
        $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_LOGGEDINLABUSER . " WHERE userId = ?";

        parent::manipulate($sql, array($userId), array("Conn" => $this->Conn));
    }


    public function __get($field) {
        $value = "";
        if ($field == "User") {
            $value = $this->User;
        } else if ($field == "Conn") {
            $value = $this->Conn;
        }
        return $value;
    }

    public function __isset($field) {
        $isset = false;
        if ($field == "User" && isset($this->User) && $this->User != null && $this->User instanceof LabUser) {
            $isset = true;
        }
        return $isset;
    }

} 