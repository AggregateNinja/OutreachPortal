<?php
require_once "UserDAO.php";
require_once "DOS/ClientUser.php";

/**
 * Description of clients
 *
 * @author Edd
 */
class ClientDAO extends UserDAO {
    //public static function getClients($startRow, $numRows, $order, mysqli $conn = null) {
    public static function getClients(array $input, mysqli $conn = null) {
        $aryInput = array();

        $includeRequiredFields = false;
        $hasMultiLocation = false;
        if (array_key_exists("IncludeRequiredFields", $input) && $input['IncludeRequiredFields'] == true) {
            $includeRequiredFields = true;
        }
        if (array_key_exists("HasMultiLocation", $input) && $input['HasMultiLocation'] == true) {
            $hasMultiLocation = true;
        }

        if ($includeRequiredFields) {
            $sql = "SELECT c.*,
              GROUP_CONCAT(DISTINCT rf.fieldName) AS `requiredFieldNames` ";
            if ($hasMultiLocation) {
                $sql .= ", l.idLocation, l.locationNo, l.locationName ";
            }

            $sql .= "FROM " .self::DB_CSS . "." . self::TBL_CLIENTS . " c 
              LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTYREQUIREDFIELDLOOKUP . " rfl ON c.idClients = rfl.clientId
              LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTYREQUIREDFIELDS . " rf ON rfl.requiredFieldId = rf.idRequiredFields ";
            if ($hasMultiLocation) {
                $sql .= "LEFT JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " l ON c.location = l.idLocation ";
            }
        } else {
            $sql = "SELECT * ";
            if ($hasMultiLocation) {
                $sql .= ", l.idLocation, l.locationNo, l.locationName ";
            }
            $sql .= "FROM " .self::DB_CSS . "." . self::TBL_CLIENTS . " c ";
            if ($hasMultiLocation) {
                $sql .= "LEFT JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " l ON c.location = l.idLocation ";
            }
        }

        if (array_key_exists("WebClientsOnly", $input) && $input['WebClientsOnly'] == true) {
            $sql .= "INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON c.idClients = cl.clientId 
                INNER JOIN " . self::DB_CSS . "." . self::TBL_USERS . " u ON cl.userId = u.idUsers AND u.isActive = true ";
        }

        $sql .= "WHERE c.active = true ";

        if ($includeRequiredFields) {
            $sql .= "GROUP BY c.idClients ";
        }

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

        /*error_log($sql);
        error_log(implode(", ", $aryInput));*/

        if ($conn != null) {
            $data = parent::select($sql, $aryInput, array("Conn" => $conn));
        } else {
            $data = parent::select($sql, $aryInput);
        }
        
        $aryClients = array();
        foreach ($data as $row) {
            if (isset($row['requiredFieldNames']) && !empty($row['requiredFieldNames'])) {
                $row['requiredFieldNames'] = explode(",", $row['requiredFieldNames']);
            }
            $currClient = new ClientUser($row);
            $aryClients[] = $currClient;
        }
        
        return $aryClients;
    }

    public static function getAutoTests($clientId, mysqli $conn) {
        require_once 'DOS/Test.php';

        $aryAutoTests = array();

        $sql = "
            SELECT  t.idtests, t.number, t.name, t.printedName, t.testType, t.resultType, t.department,
                    d.idDepartment, d.deptNo, d.deptName, d.promptPOC,
                    st.idspecimenTypes, st.name AS `specimenTypeName`, st.code
            FROM " . self::DB_CSS . "." . self::TBL_AUTOORDERTESTS . " ot
            INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON ot.testNumber = t.number AND t.active = true
            INNER JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTS . " d ON t.department = d.idDepartment
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SPECIMENTYPES . " st ON t.specimenType = st.idspecimenTypes
            WHERE ot.clientId = ?
        ";

        $data = parent::select($sql, array($clientId), array("Conn" => $conn));

        if (count($data) > 0) {
            foreach($data as $row) {
                $aryAutoTests[] = new Test($row);
            }
        }

        return $aryAutoTests;
    }

    public static function getClientWithDoctors(array $inputs, array $arySettings = null) {
        $includeCommonCodes = false;
        $includeUserSettings = false;
        $includeCommonTests = false;
        $includeExcludedTests = false;
        $includePreferences = false;
        $sqlSettings = array();
        if ($arySettings != null) {
        	if (array_key_exists("IncludePreferences", $arySettings) && $arySettings["IncludePreferences"] == true) {
        		$includePreferences = true;
        	}
            if (array_key_exists("IncludeCommonCodes", $arySettings) && $arySettings["IncludeCommonCodes"] == true) {
                $includeCommonCodes = true;
            }            
            if (array_key_exists("IncludeUserSettings", $arySettings) && $arySettings["IncludeUserSettings"] == true) {
                $includeUserSettings = true;
            }
            if (array_key_exists("IncludeCommonTests", $arySettings) && $arySettings['IncludeCommonTests'] == true) {
                $includeCommonTests = true;
            }
            if (array_key_exists("IncludeExcludedTests", $arySettings) && $arySettings['IncludeExcludedTests'] == true) {
                $includeExcludedTests = true;
            }
            if (array_key_exists("Conn", $arySettings) && $arySettings['Conn'] instanceof mysqli) {
            	$sqlSettings['Conn'] = $arySettings['Conn'];
            }
        }
        $sql = "
            SELECT *
            FROM " . self::DB_CSS . "." . self::TBL_CLIENTS . " c
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " l ON l.clientId = c.idClients
            INNER JOIN " . self::DB_CSS . "." . self::TBL_USERS . " u ON u.idUsers = l.userId WHERE ";

        $params = array();
        foreach ($inputs as $field => $value) {
            $sql .= $field . " = ? AND ";
            $params[] = $value;
        }
        $sql = substr($sql, 0, strlen($sql) - 4);
        echo $sql;
        $data = parent::select($sql, $params, $sqlSettings);
        
        if (count($data) > 0) {
            $client = new ClientUser($data[0]);


            $dSql = "SELECT DISTINCT o.clientId, doctorId, number, d.firstName AS `doctorFirstName`, d.lastName AS `doctorLastName`, address1, city, state, zip
                FROM " . self::DB_CSS . "." . self::TBL_DOCTORS . " d
                INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON o.doctorId = d.iddoctors
                INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON o.clientId = cl.clientId
                WHERE cl.userId = ?";
            $dData = parent::select($dSql, array($inputs['idUsers']));
            if (count($dData) > 0) {
                foreach ($dData as $dRow) {
                    $client->addDoctor(array(
                        //"clientId" => $data[$i]['clientId'],
                        "iddoctors" => $dRow['doctorId'],
                        "number" => $dRow['number'],
                        "firstName" => $dRow['doctorFirstName'],
                        "lastName" => $dRow['doctorLastName'],
                        "address1" => $dRow['address1'],
                        "city" => $dRow['city'],
                        "state" => $dRow['state'],
                        "zip" => $dRow['zip']
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
                        $client->addUserSetting($sRow);
                    }
                }
            }

            
            if ($includePreferences) {
            	$sql = "SELECT idpreferences, " . self::TBL_PREFERENCES . ".key, value, type FROM " . self::DB_CSS . "." . self::TBL_PREFERENCES;
            	$data = parent::select($sql, null, $sqlSettings);
            	if (count($data) > 0) {
            		$client->addPreferences($data);
            	}
            }
            
            $client->idUsers = $inputs['idUsers'];
            
            if ($includeCommonTests) {
                $client = self::setCommonTests($client);
            }
            if ($includeExcludedTests) {
                $client = self::setExcludedTests($client);
            }            
            if ($includeCommonCodes) {
                $client = self::setCommonDiagnosisCodes($client);
            }
            
            return $client;
        }
        
        return false;
    }

    
//    public static function test($id) {
//        $sql = "SELECT clientName FROM css.clients WHERE idClients = ? LIMIT 1";
//        $data = parent::select($sql, array($id));
//        return $data;
//    }
    
    
    public static function getClient(array $inputs, array $arySettings = null) {
        $includeUserSettings = false;
        // ************** WARNING: Do not set both $includeSearchLogEntries and $includeLastLogin to true at the same time ************* //
        $includeSearchLogEntries = false;
        $searchLogEntryLimit = false;
        $includeLastLogin = false;
        $includeCommonTests = false;
        $includeExcludedTests = false;
        $includeCommonCodes = false;
        $sqlSettings = array();
        if ($arySettings != null) {
            if (array_key_exists("IncludeUserSettings", $arySettings) && $arySettings["IncludeUserSettings"] == true) {
                $includeUserSettings = true;                
            }
            if (array_key_exists("IncludeSearchLogEntries", $arySettings) && $arySettings["IncludeSearchLogEntries"] == true) {
                $includeSearchLogEntries = true;                
            }
            if (array_key_exists("SearchLogEntriesLimit", $arySettings) && is_numeric($arySettings["SearchLogEntriesLimit"])) {
                $searchLogEntryLimit = $arySettings["SearchLogEntriesLimit"];            
            }
            if (array_key_exists("IncludeLastLogin", $arySettings) && $arySettings["IncludeLastLogin"] == true) {
                $includeLastLogin = true;
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
            SELECT  c.clientName, c.clientNo, c.idClients, u.idUsers, u.typeId, u.email, u.password, u.userSalt, u.isVerified, u.verificationCode, u.dateCreated, u.dateUpdated,
                    c.clientStreet, c.clientStreet2, c.clientCity, c.clientState, c.clientZip, c.phoneNo, c.npi,
                    lo.idLocation, lo.locationNo, lo.locationName ";
        if ($includeUserSettings) {
            $sql .= ", s.idUserSettings, s.settingName, s.settingDescription ";
        }

        if ($includeSearchLogEntries || $includeLastLogin) {
            $sql .= ", log.idLogs, log.logDate, log.idTypes, log.logTypeName, log.description ";
        }
        $sql .= "
            FROM " . self::DB_CSS . "." . self::TBL_CLIENTS . " c
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " lo ON c.location = lo.idLocation
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " l ON c.idClients = l.clientId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_USERS . " u ON u.idUsers = l.userId ";
        if ($includeUserSettings) {
            $sql .= "LEFT JOIN " . self::DB_CSS . "." . self::TBL_USERSETTINGSLOOKUP . " lu ON lu.userId = u.idUsers
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_USERSETTINGS . " s ON s.idUserSettings = lu.userSettingId ";
        }
        
        $params = array();
        
        if ($includeSearchLogEntries || $includeLastLogin) {
            $sql .= "
                LEFT JOIN (
                    SELECT wl.userId, wl.idLogs, wl.logDate, wt.idTypes, wt.name as 'logTypeName', wt.description
                    FROM " . self::DB_CSS . "." . self::TBL_LOG . " wl
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_LOGTYPES . " wt ON wl.typeId = wt.idTypes ";
            
            if ($includeSearchLogEntries && $includeLastLogin) {
                $sql .= " WHERE (wt.idTypes = ? OR wt.idTypes = ?) AND userId = ? ";
                $params[] = 2;
                $params[] = 1;
            } else if ($includeSearchLogEntries) {
                $sql .= " WHERE wt.idTypes = ? AND userId = ? ";
                $params[] = 2;
            } else if ($includeLastLogin) {
                $sql .= " WHERE wt.idTypes = ? AND userId = ? ";
                $params[] = 1;
            }
            $params[] = $inputs['idUsers'];
                    
            $sql .= " ORDER BY logDate DESC ";
            
            if (is_numeric($searchLogEntryLimit)) {
                $sql .= " LIMIT ? ";
                $params[] = $searchLogEntryLimit;
            }
            
            $sql .= " ) log ON u.idUsers = log.userId ";
        }
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
        
        if ($includeSearchLogEntries || $includeLastLogin) {
            $sql .= " ORDER BY logDate DESC "; 
        }
        /*error_log($sql);
        error_log(implode(", ", $params));*/
        
        $data = parent::select($sql, $params, $sqlSettings);
        //echo count($data);
        if (count($data) > 0) {
            //echo "TEST";
            $client = new ClientUser($data[0]);
            //echo "<pre>"; print_r($client); echo "</pre>";

            if (count($data) > 1) {
                if ($includeLastLogin) {
                    $client->setLastLogin($data[1]);
                }
                
                if ($includeUserSettings) {
                    foreach ($data as $row) {
                        $client->addUserSetting($row);
                    }
                }
                if ($includeSearchLogEntries) {
                    foreach ($data as $row) {
                        $client->addSearchLogEntry($row);
                    }
                }
            }   
            
            
            if ($includeCommonTests) {
            	$client = self::setCommonTests($client);
            }
            if ($includeExcludedTests) {
            	$client = self::setExcludedTests($client);
            }
            
            if ($client->hasUserSetting(4)) { // client has multi user setting, so set up its multi users
                $sql = "SELECT multiUserId FROM " . self::DB_CSS . "." . self::TBL_MULTIUSER . " WHERE userId = ?";
                $qryMultiUser = parent::select($sql, array($client->idUsers), $sqlSettings);
                if (count($qryMultiUser) > 0) {
                    $multiUsers = array();
                    foreach ($qryMultiUser as $multiUser) {
                        $multiUsers[] = $multiUser['multiUserId'];
                    }
                    $client->setMultiUsers($multiUsers);

                }
            }
            
            if ($includeCommonCodes) {
                $client = self::setCommonDiagnosisCodes($client);
            }
                
            return $client;
        }
        return false;        
    }

    public static function getClientByUserId($userId, mysqli $conn = null) {
        $sql = "
            SELECT  c.idClients, c.clientNo, c.clientName
            FROM " . self::DB_CSS . "." . self::TBL_CLIENTS . " c
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON c.idClients = cl.clientId
            WHERE cl.userId = ?";
        $data = parent::select($sql, array($userId), array("Conn" => $conn));


        if (count($data) > 0) {
            return new ClientUser($data[0]);
        }
        return null;
    }

    public static function getUserIdByClientNo($clientNo, mysqli $conn = null) {
        $sql = "
            SELECT  u.idUsers
            FROM " . self::DB_CSS . "." . self::TBL_CLIENTS . " c
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON c.idClients = cl.clientId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_USERS . " u ON cl.userId = u.idUsers 
            WHERE u.isActive = true
                AND c.clientNo = ?";
        $data = parent::select($sql, array($clientNo), array("Conn" => $conn));


        if (count($data) > 0) {
            return $data[0]['idUsers'];
        }
        return null;
    }
    
    private static function setCommonDiagnosisCodes(ClientUser $client) {
        
        $sql = "
            SELECT d.idDiagnosisCodes, d.code, d.description, d.dateCreated, d.dateUpdated
            FROM " . self::TBL_DIAGNOSISCODES . " d 
            INNER JOIN " . self::TBL_COMMONDIAGNOSISCODES . " cd ON d.idDiagnosisCodes = cd.diagnosisCodeId
            WHERE cd.userId = ? 
            ORDER BY code";
        $data = parent::select($sql, array($client->idUsers));
        
        if (count($data) > 0) {
            foreach ($data as $dataRow) {
                $client->addCommonDiagnosisCode($dataRow);
            }
        }
        
        return $client;
    }
    
    private static function setCommonTests(ClientUser $client) {
        $sql = "SELECT t.idtests, t.number, t.name, t.testType
                FROM " . self::TBL_COMMONTESTS . " ct 
                INNER JOIN " . self::TBL_TESTS . " t ON ct.testId = t.idtests
                INNER JOIN " . self::TBL_USERS . " u ON ct.userId = u.idUsers 
                INNER JOIN " . self::TBL_CLIENTLOOKUP . " cl ON u.idUsers = cl.userId
                WHERE cl.clientId = ?
                ORDER BY t.name";
        $data = parent::select($sql, array($client->idClients));
        
        if (count($data) > 0) {
            foreach ($data as $row) {
                $client->addCommonTest($row);
            }
        } 
        
        return $client;
    }
    private static function setExcludedTests(ClientUser $client) {
        $sql = "SELECT t.idtests, t.number, t.name, t.testType
                FROM " . self::TBL_EXCLUDEDTESTS . " ct 
                INNER JOIN " . self::TBL_TESTS . " t ON ct.testId = t.idtests
                INNER JOIN " . self::TBL_USERS . " u ON ct.userId = u.idUsers 
                INNER JOIN " . self::TBL_CLIENTLOOKUP . " cl ON u.idUsers = cl.userId
                WHERE cl.clientId = ?
                ORDER BY t.name";
        $data = parent::select($sql, array($client->idClients));
        if (count($data) > 0) {
            foreach ($data as $row) {
                $client->addExcludedTest($row);
            }
        } 
        return $client;
    }
}
?>
