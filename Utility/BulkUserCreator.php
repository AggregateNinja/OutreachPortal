<?php
require_once 'DAOS/DataObject.php';

class BulkUserCreator extends DataObject {
	
	private $UserData;
    private $Conn;
	
	public function __construct(array $data) {

        /*
        Array (
            Array(
                "clientId" => int,
                "email" => string,
                "password" => string,
                "canViewStatistics" => bool,
                "multiUsers" => Array(n1, n1, ..., nn)
            )
        )
        */

		$this->UserData = $data;
        $this->Conn = parent::connect();
	}
	
	public function insertClients() {
		$multiUserClients = array();
		foreach ($this->UserData as $aryCurr) {
			
			$clientNo = $aryCurr['clientId'];
			$email = $aryCurr['email'];
			$password =$aryCurr['password'];
			$canViewStatistics = $aryCurr['canViewStatistics'];
			$multiUsers = $aryCurr['multiUsers'];

            $sql = "
                SELECT idClients
                FROM acs.clients
                WHERE clientNo = ?
            ";
            $data = parent::select($sql, array($clientNo), array("Conn" => $this->Conn)); // make sure the client number exists

            if (count($data) > 0) {
                $clientId = $data[0]['idClients'];

                $userSalt = $this->generateSalt();
                $passwordHash = $this->generateHash($userSalt, $password);

                $verificationCode = $this->generateVerificationCode();
                $passwordTest = md5($password . $userSalt);

                $sql = "INSERT INTO " . self::TBL_USERS . " (typeId, email, password, userSalt, verificationCode) VALUES (?, ?, ?, ?, ?)";
                $userId = parent::manipulate($sql, array(2, $email, $passwordHash, $userSalt, $verificationCode), array("Conn" => $this->Conn, "LastInsertId" => true));

                $sql = "INSERT INTO " . self::TBL_CLIENTLOOKUP . " (userId, clientId) VALUES (?, ?)";
                parent::manipulate($sql, array($userId, $clientId), array("Conn" => $this->Conn));

                if ($canViewStatistics == 1) {
                    $sql = "INSERT INTO " . self::TBL_USERSETTINGSLOOKUP . " (userId, userSettingId) VALUES (?, ?)";
                    parent::manipulate($sql, array($userId, 1), array("Conn" => $this->Conn));
                }

                $hasMultiUser = false;
                if ($multiUsers != null && is_array($multiUsers)) {
                    $sql = "INSERT INTO " . self::TBL_USERSETTINGSLOOKUP . " (userId, userSettingId) VALUES (?, ?)";
                    parent::manipulate($sql, array($userId, 4), array("Conn" => $this->Conn));
                    $hasMultiUser = true;
                    $multiUserClients[] = array("idUsers" => $userId, "multiUsers" => $multiUsers);
                }
                echo "<b>Email:</b>: " . $email . "<br/>";
                echo "Password: " . $password . "<br/>";
                echo "Verification Code: " . $verificationCode . "<br/>";
                echo "User Salt: " . $userSalt . "<br/>";
                echo "Password Hash: " . $passwordHash . "<br/>";
                echo "Password Test: " . $passwordTest . "<br/>";
                echo "Has Statistics: " . $canViewStatistics . "<br/>";
                echo "Has Multi Users: " . $hasMultiUser . "<br/><br/>";
            }
		}

        if (count($multiUserClients) > 0) {
            echo "<br/><b>Multi Users</b><br/><br/>";
            foreach ($multiUserClients as $aryCurr) {
                //$multiUsers = $aryCurr['multiUsers'];
                $multiUsers = implode(",", $aryCurr['multiUsers']);
                $idUsers = $aryCurr['idUsers'];
                $sql = "
                    SELECT idUsers AS `multiUserId`
                    FROM " . self::DB_CSS . "." . self::TBL_USERS . " u
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON u.idUsers = cl.userId
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON cl.clientId = c.idClients
                    WHERE c.clientNo IN ($multiUsers)
                ";
                $data = parent::select($sql, null, array("Conn" => $this->Conn));
                if (count($data) > 0) {
                    foreach ($data as $row) {
                        $multiUserId = $row['multiUserId'];
                        $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_MULTIUSER . " (userId, multiUserId) VALUES (?, ?)";
                        parent::manipulate($sql, array($idUsers, $multiUserId), array("Conn" => $this->Conn));
                        echo "UserId: $idUsers<br/>";
                        echo "MultiUserId: $multiUserId<br/>";
                    }
                    echo "<br/>";
                }
            }
        }

	}
	
	public function insertDoctors() {
		foreach ($this->UserData as $aryCurr) {
			$doctorId = $aryCurr['doctorId'];
			$email = $aryCurr['email'];
			$password =$aryCurr['password'];
			$canViewStatistics = $aryCurr['canViewStatistics'];
			
			$userSalt = $this->generateSalt();
			$passwordHash = $this->generateHash($userSalt, $password);
				
			$verificationCode = $this->generateVerificationCode();
			$passwordTest = md5($password . $userSalt);
			
 			$sql = "INSERT INTO " . self::TBL_USERS . " (typeId, email, password, userSalt, verificationCode) VALUES (?, ?, ?, ?, ?)";
 			$userId = parent::manipulate($sql, array(3, $email, $passwordHash, $userSalt, $verificationCode), array("LastInsertId" => true, "Conn" => $this->Conn));
	
 			$sql = "INSERT INTO " . self::TBL_DOCTORLOOKUP . " (userId, doctorId) VALUES (?, ?)";
 			parent::manipulate($sql, array($userId, $doctorId), array("Conn" => $this->Conn));
	
 			if ($canViewStatistics == 1) {
 				$sql = "INSERT INTO " . self::TBL_USERSETTINGSLOOKUP . " (userId, userSettingId) VALUES (?, ?)";
 				parent::manipulate($sql, array($userId, 1), array("Conn" => $this->Conn));
 			}

            echo "Email: " . $email . "<br/>";
 			echo "Password: " . $password . "<br/>";
 			echo "Verification Code: " . $verificationCode . "<br/>";
 			echo "User Salt: " . $userSalt . "<br/>";
 			echo "Password Hash: " . $passwordHash . "<br/>";
 			echo "Password Test: " . $passwordTest . "<br/><br/>";
		}
	}

    public function insertAdmins() {
        foreach ($this->UserData as $aryCurr) {
            $email = $aryCurr['email'];
            $password =$aryCurr['password'];

            $userSalt = $this->generateSalt();
            $passwordHash = $this->generateHash($userSalt, $password);
            $verificationCode = $this->generateVerificationCode();
            $passwordTest = md5($password . $userSalt);

            $sql = "INSERT INTO " . self::TBL_USERS . " (typeId, email, password, userSalt, verificationCode) VALUES (?, ?, ?, ?, ?)";
            $userId = parent::manipulate($sql, array(1, $email, $passwordHash, $userSalt, $verificationCode), array("LastInsertId" => true, "Conn" => $this->Conn));

            echo "Email: " . $email . "<br/>";
            echo "Password: " . $password . "<br/>";
            echo "Verification Code: " . $verificationCode . "<br/>";
            echo "User Salt: " . $userSalt . "<br/>";
            echo "Password Hash: " . $passwordHash . "<br/>";
            echo "Password Test: " . $passwordTest . "<br/><br/>";
        }
    }
	
	private function generateSalt() {
		$intermediateSalt = md5(uniqid(rand(), true));
		$salt = substr($intermediateSalt, 0, 6);
		return $salt;
	}	
	private function generateHash($salt, $password) {
		return md5($password . $salt);
	}	
	private function hashData($data) {
		//return hash_hmac('sha512', $data, $this->_siteKey);
		return md5($data);
	}	
	private function generateVerificationCode($length = 32, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890') {
		// Length of character list
		$chars_length = (strlen($chars) - 1);	
		// Start our string
		$code = $chars{rand(0, $chars_length)};	
		// Generate random string
		for ($i = 1; $i < $length; $i = strlen($code)) {
			// Grab a random character from our list
			$r = $chars{rand(0, $chars_length)};	
			// Make sure the same two characters don't appear next to each other
			if ($r != $code{$i - 1})
				$code .= $r;
		}
		return $code;
	}
}

?>