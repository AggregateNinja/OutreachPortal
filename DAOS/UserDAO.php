 <?php
require_once 'DataObject.php';

require_once 'DOS/User.php';
require_once 'DOS/DoctorUser.php';
require_once 'DOS/ClientUser.php';
require_once 'DOS/AdminUser.php';
require_once 'DOS/SalesmanUser.php';
require_once 'DOS/InsuranceUser.php';
require_once 'DOS/UserSetting.php';
require_once 'DOS/OrderEntrySetting.php';
require_once 'PreferencesDAO.php';

require_once 'DOS/PatientUser.php';
 require_once 'DOS/PatientAdminUser.php';

class UserDAO extends DataObject {
    private $Conn;
    private $User;

    protected $UserId;
    protected $TypeId;
    private $Email;
    private $Ip;
    protected $Settings;

    public function __construct(array $data, array $settings = null) {

        if ($settings != null && array_key_exists("Conn", $settings) && $settings['Conn'] instanceof mysqli) {
            $this->Conn = $settings['Conn'];
        } else {
            $this->Conn = parent::connect();
        }


        if (array_key_exists("userId", $data)) {
            $this->UserId = $data['userId'];
        } else {
            $this->UserId = null;
        }
        if (array_key_exists("typeId", $data)) {
            $this->TypeId = $data['typeId'];
        } else {
            $this->TypeId = null;
        }
        if (array_key_exists("email", $data)) {
            $this->Email = $data['email'];
        } else {
            $this->Email = null;
        }
        if (array_key_exists("ip", $data)) {
            $this->Ip = $data['ip'];
        } else {
            $this->Ip = null;
        }

        $this->Settings = $settings;
    }

    public function setNewLogin($token, $sessionId, $adminUserId = null) {

        // Delete old LoggedInUser records for user
        $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_LOGGEDINUSER . " WHERE userId = ? AND ip = ?";
        parent::manipulate($sql, array($this->UserId, $this->Ip), array("Conn" => $this->Conn));

        // Insert new LoggedInUser record for user

        //error_log("AdminId: " . $adminUserId . ", CurrPage: " . $_SERVER['PHP_SELF']);



        if ($adminUserId != null) {
            $sql = "
            INSERT INTO " . self::DB_CSS . "." . self::TBL_LOGGEDINUSER . " (userId, sessionId, token, ip, loginDate, adminUserId)
            VALUES (?, ?, ?, ?, ?, ?)";
            $aryInput = array($this->UserId, $sessionId, $token, $this->Ip, date("Y-m-d H:i:s"), $adminUserId);
        } else {
            $sql = "
            INSERT INTO " . self::DB_CSS . "." . self::TBL_LOGGEDINUSER . " (userId, sessionId, token, ip, loginDate)
            VALUES (?, ?, ?, ?, ?)";
            $aryInput = array($this->UserId, $sessionId, $token, $this->Ip, date("Y-m-d H:i:s"));
        }


        parent::manipulate(
            $sql,
            $aryInput,
            array("Conn" => $this->Conn)
        );
    }

    public function setOrderBeingEdited($orderId) {
        //date_default_timezone_set('America/Chicago'); // CDT

        $sql = "SELECT ob.orderId FROM " . self::DB_CSS . "." . self::TBL_ORDERBEINGEDITED . " ob WHERE ob.orderId = ? AND ip = ?";
        $data = self::select($sql, array($orderId, $this->Ip), array("Conn" => $this->Conn));

        if (count($data) > 0) {
            $sql = "
                UPDATE " . self::DB_CSS . "." . self::TBL_ORDERBEINGEDITED . "
                SET editDate = ?, sessionId = ?, token = ?
                WHERE orderId = ? AND ip = ?
            ";
            self::manipulate($sql, array(date("Y-m-d H:i:s"), $this->User->sessionId, $this->User->token, $orderId, $this->Ip), array("Conn" => $this->Conn));
        } else {
            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_ORDERBEINGEDITED . " (userId, orderId, sessionId, token, ip, editDate) VALUES (?, ?, ?, ?, ?, ?)";
            self::manipulate($sql, array($this->User->idUsers, $orderId, $this->User->sessionId, $this->User->token, $this->Ip, date("Y-m-d H:i:s")), array("Conn" => $this->Conn));
        }
    }

    public function clearOrderBeingEdited() {
        $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_ORDERBEINGEDITED . " WHERE userId = ? AND ip = ?";
        parent::manipulate($sql, array($this->User->idUsers, $this->Ip), array("Conn" => $this->Conn));
    }

    public function getLastLogin() {
        $sql = "
            SELECT l.logDate
            FROM " . self::DB_CSS . "." . self::TBL_LOG . " l
            WHERE l.userId = ? AND l.typeId = 1
            ORDER BY l.logDate DESC
            LIMIT 2";
        $data = parent::select($sql, array($this->UserId), array("Conn" => $this->Conn));

        if (count($data) > 1) {
            return date("m/d/Y h:i:s A", strtotime($data[1]['logDate']));
        }
        return null;
    }

    public function getUserByEmail() {
        $sql = "
            SELECT 	u.idUsers, u.typeId, u.email, u.password, u.userSalt, u.isActive, u.verificationCode, u.isVerified,
                    cl.clientId AS `idClients`, dl.doctorId AS `iddoctors`,
                    sa.idAdminSettings, sa.settingName AS `adminSettingName`, sa.settingDescription AS `adminSettingDescription`, sa.isMasterSetting, sa.isActive AS `adminSettingIsActive`,
                    su.idUserSettings, su.settingName AS `userSettingName`, su.settingDescription AS `userSettingDescription`, su.pageName AS `userSettingPageName`,
                    lu.sessionId, lu.token, lu.ip, lu.loginDate,
                    oes.idOrderEntrySettings, oes.settingName AS `orderEntrySettingName`, oes.settingDescription AS `orderEntrySettingDescription`
            FROM " . self::DB_CSS . "." . self::TBL_USERS . " u
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON u.idUsers = cl.userId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON u.idUsers = dl.userId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_ADMINSETTINGSLOOKUP . " asl ON u.idUsers = asl.userId AND u.typeId = 1
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_ADMINSETTINGS . " sa ON asl.adminSettingId = sa.idAdminSettings
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_USERSETTINGSLOOKUP . " usl ON u.idUsers = usl.userId AND (u.typeId = 2 OR u.typeId = 3)
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_USERSETTINGS . " su ON usl.userSettingId = su.idUserSettings
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_LOGGEDINUSER . " lu ON u.idUsers = lu.userId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_ORDERENTRYSETTINGSLOOKUP . " esl ON u.idUsers = esl.userId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_ORDERENTRYSETTINGS . " oes ON esl.orderEntrySettingId = oes.idOrderEntrySettings
            WHERE u.email = ?";
        $data = parent::select($sql, array($this->Email), array("Conn" => $this->Conn));

        /*error_log($sql);
        error_log($this->Email);*/

        if (count($data) > 0) {
            $this->UserId = $data[0]['idUsers'];
            $this->TypeId = $data[0]['typeId'];
            $typeId = $data[0]['typeId'];
            $aryAddedSettings = array();
            $aryAddedOrderEntrySettings = array();
            if ($typeId == 1) {
                $this->User = new AdminUser($data[0]);
                if ($data[0]['idAdminSettings'] != null) {
                    $aryAddedSettings[] = $data[0]['idAdminSettings'];
                }
                for ($i = 1; $i < count($data); $i++) {
                    if (!in_array($data[$i]['idAdminSettings'], $aryAddedSettings)) {
                        $this->User->addAdminSetting($data[$i]);
                        $aryAddedSettings[] = $data[$i]['idAdminSettings'];
                    }
                }

            } else if ($typeId == 2) {
                $this->User = new ClientUser($data[0]);
                if ($data[0]['idUserSettings'] != null) {
                    $aryAddedSettings[] = $data[0]['idUserSettings'];
                }
                if ($data[0]['idOrderEntrySettings'] != null) {
                    $this->User->addOrderEntrySetting(array(
                        "idOrderEntrySettings" => $data[0]['idOrderEntrySettings'],
                        "settingName" => $data[0]['orderEntrySettingName'],
                        "settingDescription" => $data[0]['orderEntrySettingDescription']
                    ));
                    $aryAddedOrderEntrySettings[] = $data[0]['idOrderEntrySettings'];
                }
                for ($i = 1; $i < count($data); $i++) {
                    if (!in_array($data[$i]['idUserSettings'], $aryAddedSettings)) {
                        $this->User->addUserSetting($data[$i]);
                        $aryAddedSettings[] = $data[$i]['idUserSettings'];
                    }

                    if ($data[$i]['idOrderEntrySettings'] != null && !in_array($data[$i]['idOrderEntrySettings'], $aryAddedOrderEntrySettings)) {
                        $this->User->addOrderEntrySetting(array(
                            "idOrderEntrySettings" => $data[$i]['idOrderEntrySettings'],
                            "settingName" => $data[$i]['orderEntrySettingName'],
                            "settingDescription" => $data[0]['orderEntrySettingDescription']
                        ));
                        $aryAddedOrderEntrySettings[] = $data[$i]['idOrderEntrySettings'];
                    }
                }

                if ($this->User->hasUserSetting(4)) { // client has multi user setting, so set up its multi users
                    $aryMultiUsers = $this->getMultiUsers();
                    if ($aryMultiUsers != null) {
                        $this->User->setMultiUsers($aryMultiUsers);
                    }
                }

            } else if ($typeId == 3) {
                $this->User = new DoctorUser($data[0]);
                if ($data[0]['idUserSettings'] != null) {
                    $aryAddedSettings[] = $data[0]['idUserSettings'];
                }
                if ($data[0]['idOrderEntrySettings'] != null) {
                    $this->User->addOrderEntrySetting(array(
                        "idOrderEntrySettings" => $data[0]['idOrderEntrySettings'],
                        "settingName" => $data[0]['orderEntrySettingName'],
                        "settingDescription" => $data[0]['orderEntrySettingDescription']
                    ));
                    $aryAddedOrderEntrySettings[] = $data[0]['idOrderEntrySettings'];
                }
                for ($i = 1; $i < count($data); $i++) {

                    if (!in_array($data[$i]['idUserSettings'], $aryAddedSettings)) {
                        $this->User->addUserSetting($data[$i]);
                        $aryAddedSettings[] = $data[$i]['idUserSettings'];
                    }
                    if ($data[$i]['idOrderEntrySettings'] != null && !in_array($data[$i]['idOrderEntrySettings'], $aryAddedOrderEntrySettings)) {
                        $this->User->addOrderEntrySetting(array(
                            "idOrderEntrySettings" => $data[$i]['idOrderEntrySettings'],
                            "settingName" => $data[$i]['orderEntrySettingName'],
                            "settingDescription" => $data[0]['orderEntrySettingDescription']
                        ));
                        $aryAddedOrderEntrySettings[] = $data[$i]['idOrderEntrySettings'];
                    }
                }
            } else if ($typeId == 4) {
                $this->User = new PatientUser($data[0]);
                $this->UserId = $this->User->idUsers;
                $this->TypeId = $this->User->typeId;

            } else if ($typeId == 5) {
                $this->User = new SalesmanUser($data[0]);
                for ($i = 1; $i < count($data); $i++) {
                    $this->User->addUserSetting($data[$i]);
                }
            } else if ($typeId == 6) {
                $this->User = new InsuranceUser($data[0]);
            } else if ($typeId == 7) {
                $this->User = new AdminUser($data[0]);

                $sqlClientLookup = "SELECT cl.idClientLookup, cl.clientId, cl.userId FROM " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl WHERE cl.userId = ?";
                $clientLookupData = parent::select($sqlClientLookup, array($this->UserId), array("Conn" => $this->Conn));
                if (count($clientLookupData) > 0) {
                    foreach ($clientLookupData as $clientLookupRow) {
                        $this->User->adminClientIds[] = $clientLookupRow['clientId'];
                    }
                }

            } else if ($typeId == 8) {
                $this->User = new PatientAdminUser($data[0]);
            }
        }
    }

    public function getPatientUserByArNo($arNo) {
        $sql = "SELECT u.idUsers, u.email, u.typeId, u.verificationCode, u.isActive, u.isVerified,
            p.idPatients, p.arNo, p.firstName, p.lastName, p.dob
        FROM " . self::DB_CSS . "." . self::TBL_USERS . " u
        INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTLOOKUP . " pl ON u.idUsers = pl.userId
        INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON pl.patientId = p.idPatients
        WHERE p.arNo = ?";

        $data = parent::select($sql, array($arNo), array("Conn" => $this->Conn));

        if (count($data) > 0) {
            return new PatientUser($data[0]);
        }
        return false;
    }

    public function getLastPasswordUpdate() {
        $sql = "
            SELECT 	u.idUsers,
                    u.dateCreated,
		            MAX(l.logDate) AS `logDate`
		    FROM " . self::DB_CSS . "." . self::TBL_USERS . " u
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_LOG . " l ON u.idUsers = l.userId AND l.typeId = 18
            WHERE l.userId = ?";

        $data = parent::select($sql, array($this->UserId), array("conn" => $this->Conn));

        if (count($data) > 0) {
            if ($data[0]['logDate'] != null) {
                return $data[0]['logDate'];
            }
            //else {
            //    return $data[0]['dateCreated'];
            //}
        }
        return null;
    }

    public function getUser() {
        $userIsSet = false;
        $includeUserSettings = false;
        $includeCommonCodes = false;
        $includeCommonTests = false;
        $includeExcludedTests = false;
        $includeDetailedInfo = false;
        $includeRelatedUsers = false;
        $includeCommonDrugs = false;
        $patientRegistered = false;
        //$includeClientProperties = false;
        if ($this->Settings != null) {
            if (array_key_exists("IncludeUserSettings", $this->Settings) && $this->Settings['IncludeUserSettings'] == true) {
                $includeUserSettings = true;
            }
            if (array_key_exists("IncludeCommonCodes", $this->Settings) && $this->Settings['IncludeCommonCodes'] == true) {
                $includeCommonCodes = true;
            }
            if (array_key_exists("IncludeCommonTests", $this->Settings) && $this->Settings['IncludeCommonTests'] == true) {
                $includeCommonTests = true;
            }
            if (array_key_exists("IncludeExcludedTests", $this->Settings) && $this->Settings['IncludeExcludedTests'] == true) {
                $includeExcludedTests = true;
            }
            if (array_key_exists("IncludeDetailedInfo", $this->Settings) && $this->Settings['IncludeDetailedInfo'] == true) {
                $includeDetailedInfo = true;
            }
            if (array_key_exists("IncludeRelatedUsers", $this->Settings) && $this->Settings['IncludeRelatedUsers'] == true) {
                $includeRelatedUsers = true;
            }
            if (array_key_exists("IncludeCommonDrugs", $this->Settings) && $this->Settings['IncludeCommonDrugs'] == true) {
                $includeCommonDrugs = true;
            }
            /*if (array_key_exists("IncludeClientProperties", $this->Settings) && $this->Settings['IncludeClientProperties'] == true) {
                $includeClientProperties = true;
            }*/
            if (array_key_exists("PatientRegistered", $this->Settings) && $this->Settings['PatientRegistered'] == true) {
                $patientRegistered = true;
            }
        }
        $select = "SELECT u.idUsers, u.typeId, u.email, u.password, u.userSalt, u.isActive, l.userId, u.isActive, u.isVerified, u.verificationCode, l.sessionId, l.token, l.ip, l.loginDate ";
        $from = " 
        FROM " . self::DB_CSS . "." . self::TBL_USERS . " u 
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_LOGGEDINUSER . " l ON l.userId = u.idUsers ";

        if ($this->TypeId == 2) {
            $select .= ", lu.clientId AS `idClients` ";
            $from .= " INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " lu ON u.idUsers = lu.userId ";
        } else if ($this->TypeId == 3) {
            $select .= ", lu.doctorId AS `iddoctors` ";
            $from .= " INNER JOIN " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " lu ON u.idUsers = lu.userId ";
        } else if ($this->TypeId == 4) {
            $select .= ", lu.patientId ";
            $from .= " INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTLOOKUP . " lu ON u.idUsers = lu.userId ";
        } else if ($this->TypeId == 5) {
            $select .= ", lu.salesmenId AS `idsalesmen` ";
            $from .= " INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMENLOOKUP . " lu ON u.idUsers = lu.userId ";
        } else if ($this->TypeId == 6) {
            $select .= ", lu.insuranceId AS `idinsurances` ";
            $from .= " INNER JOIN " . self::DB_CSS . "." . self::TBL_INSURANCELOOKUP . " lu ON u.idUsers = lu.userId ";
        } else if ($this->TypeId == 1) {
            $select .= ", lu.clientId AS `idClients` ";
            $from .= " LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " lu ON u.idUsers = lu.userId ";
        }
        if ($includeDetailedInfo) {
            if ($this->TypeId == 2) {
                if (self::HasMultiLocation) {
                    $select .= ", c.clientName, c.clientNo, c.clientStreet, c.clientStreet2, c.clientCity, c.clientState, c.clientZip, c.defaultReportType, lo.idLocation, lo.locationNo, lo.locationName ";
                    $from .= "LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON lu.clientId = c.idClients
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " lo ON c.location = lo.idLocation ";
                } else {
                    $select .= ", c.clientName, c.clientNo, c.clientStreet, c.clientStreet2, c.clientCity, c.clientState, c.clientZip, c.defaultReportType, 
                    1 AS `idLocation`, 1 AS `locationNo` ";
                    $from .= "LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON lu.clientId = c.idClients ";
                }


            } else if ($this->TypeId == 3) {
                if (self::HasMultiLocation) {
                    $select .= ", d.lastName, d.firstName, d.number, d.address1, d.address2, d.city, d.state, d.zip, lo.idLocation, lo.locationNo, lo.locationName ";
                    $from .= "LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON lu.doctorId = d.iddoctors
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " lo ON d.locationId = lo.idLocation ";
                } else {
                    $select .= ", d.lastName, d.firstName, d.number, d.address1, d.address2, d.city, d.state, d.zip, 
                    1 AS `idLocation`, 1 AS `locationNo` ";
                    $from .= "LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON lu.doctorId = d.iddoctors ";
                }

            } else if ($this->TypeId == 4) {
                // patient

                $select .= ", pa.idPatients, pa.arNo, pa.lastName, pa.firstName, pa.middleName, pa.dob, pa.subscriber, pa.relationship, pa.subscriber ";
                $from .= "LEFT JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " pa ON lu.patientId = pa.idPatients ";

            } else if ($this->TypeId == 5) {
                $select .= ", sm.salesGroup, sg.id AS `salesgroupId`, sg.groupName,
                            e.idemployees, e.lastName, e.firstName,
                            el.idemployees AS `leaderEmployeeId`,
                            el.lastName AS `leaderLastName`, el.firstName AS `leaderFirstName`,
                            t.territoryName,
                            e.address, e.city, e.state, e.zip ";
                $from .= "INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " sm ON lu.salesmenId = sm.idsalesmen
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON sm.employeeID = e.idemployees
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_TERRITORY . " t ON sm.territory = t.idterritory
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sm.salesGroup = sg.id
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " sgl ON sg.groupLeader = sgl.idsalesmen
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " el ON sgl.employeeID = el.idemployees ";
            } else if ($this->TypeId == 6) {
                $select .= ", i.name AS `insuranceName`, i.phone AS `insurancePhone`, i.address AS `insuranceAddress`, i.city AS `insuranceCity`, i.state AS `insuranceState`, i.zip AS `insuranceZip` ";
                $from .= "INNER JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i ON lu.insuranceId = i.idinsurances ";
            }
        }
        if ($includeUserSettings) {
            if ($this->TypeId == 1) {
                $select .= ", s.idAdminSettings, s.settingName, s.settingDescription, s.isMasterSetting, s.isActive AS `adminSettingIsActive` ";
                $from .= "
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_ADMINSETTINGSLOOKUP . " sl ON u.idUsers = sl.userId
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_ADMINSETTINGS . " s ON sl.adminSettingId = s.idAdminSettings
                ";
//            } else if ($this->TypeId == 5) {
//                $select .= ", s.idSalesSettings, s.settingName, s.settingDescription, s.pageName, s.isActive AS `salesSettingIsActive` ";
//                $from .= "
//                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESSETTINGSLOOKUP . " sl ON u.idUsers = sl.userId
//                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESSETTINGS . " s ON sl.salesSettingId = s.idSalesSettings
//                ";
            } else {
                $select .= ", s.idUserSettings, s.settingName, s.settingDescription, s.pageName,
                                oes.idOrderEntrySettings, oes.settingName AS `orderEntrySettingName`, oes.settingDescription AS `orderEntrySettingDescription`,
                                oas.idAccessSettings, oas.settingName AS `accessSettingName`";
                $from .= "
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_USERSETTINGSLOOKUP . " sl ON u.idUsers = sl.userId
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_USERSETTINGS . " s ON sl.userSettingId = s.idUserSettings
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_ORDERENTRYSETTINGSLOOKUP . " esl ON u.idUsers = esl.userId
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_ORDERENTRYSETTINGS . " oes ON esl.orderEntrySettingId = oes.idOrderEntrySettings
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_ORDERACCESSSETTINGSLOOKUP . " asl ON u.idUsers = asl.userId
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_ORDERACCESSSETTINGS . " oas ON asl.settingId = oas.idAccessSettings
                ";
            }
        }
        $sql = $select . $from . " WHERE u.idUsers = ?";

        $aryInput = array($this->UserId);
        if (isset($this->Ip) && !empty($this->Ip) && !$patientRegistered) {
            $sql .= " AND l.ip = ?";
            $aryInput[] = $this->Ip;
        }

        //echo $sql;

        $data = parent::select(
            $sql,
            $aryInput,
            array("Conn" => $this->Conn)
        );

        //echo "<pre>"; print_r($sql); echo "</pre>";
        //echo "<pre>"; print_r($aryInput); echo "</pre>";

        /*error_log($sql);
        error_log(implode(", ", $aryInput));*/



        $numRows = count($data);

        if ($numRows > 0) {
            $userIsSet = true;
            $typeId = $data[0]['typeId'];
            $aryAddedSettings = array();
            $aryAddedOrderEntrySettings = array();

            $aryRestrictedUserIds = array();
            if (array_key_exists("idAccessSettings", $data[0]) && $data[0]['idAccessSettings'] != null && $data[0]['idAccessSettings'] != 1 && $data[0]['idAccessSettings'] != 2 && ($typeId == 2 || $typeId == 3)) {
                // Get restricted user for client/doctor
                $sql = "
                    SELECT ru.restrictedUserId
                    FROM " . self::DB_CSS . "." . self::TBL_RESTRICTEDUSERS . " ru
                    WHERE ru.userId = ?";
                $ruData = parent::select($sql, array($data[0]['idUsers']), array("Conn" => $this->Conn));
                if (count($ruData) > 0) {
                    foreach ($ruData as $row) {
                        $aryRestrictedUserIds[] = $row['restrictedUserId'];
                    }
                }
            }


            if ($typeId == 1) {
                $this->User = new AdminUser($data[0]);

                if (isset($data[0]['idClients'])) {
                    $this->User->clientId = $data[0]['idClients'];
                }

                if (array_key_exists("idAdminSettings", $data[0])) {
                    if ($data[0]['idAdminSettings'] != null) {
                        $aryAddedSettings[] = $data[0]['idAdminSettings'];
                    }
                    if ($numRows > 1) {
                        for ($i = 1; $i < $numRows; $i++) {
                            if (!in_array($data[$i]['idAdminSettings'], $aryAddedSettings)) {
                                $this->User->addAdminSetting($data[$i]);
                                $aryAddedSettings[] = $data[$i]['idAdminSettings'];
                            }
                        }
                    }
                }
            } else if ($typeId == 7) { // Order Entry Admin
                $this->User = new AdminUser($data[0]);

                $sqlClientLookup = "SELECT cl.idClientLookup, cl.clientId, cl.userId FROM " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl WHERE cl.userId = ?";
                $clientLookupData = parent::select($sqlClientLookup, array($this->UserId), array("Conn" => $this->Conn));
                if (count($clientLookupData) > 0) {
                    foreach ($clientLookupData as $clientLookupRow) {
                        $this->User->adminClientIds[] = $clientLookupRow['clientId'];
                    }
                }

                if (array_key_exists("idOrderEntrySettings", $data[0]) && $data[0]['idOrderEntrySettings'] != null) {
                    $this->User->addOrderEntrySetting(array(
                        "idOrderEntrySettings" => $data[0]['idOrderEntrySettings'],
                        "settingName" => $data[0]['orderEntrySettingName'],
                        "settingDescription" => $data[0]['orderEntrySettingDescription']
                    ));
                    $aryAddedOrderEntrySettings[] = $data[0]['idOrderEntrySettings'];
                }

                if ($numRows > 1) {
                    for ($i = 1; $i < $numRows; $i++) {
                        if (array_key_exists("idUserSettings", $data[$i]) && !in_array($data[$i]['idUserSettings'], $aryAddedSettings)) {
                            $this->User->addUserSetting($data[$i]);
                            $aryAddedSettings[] = $data[$i]['idUserSettings'];
                        }
                        if (array_key_exists("idOrderEntrySettings", $data[$i]) && $data[$i]['idOrderEntrySettings'] != null && !in_array($data[$i]['idOrderEntrySettings'], $aryAddedOrderEntrySettings)) {
                            $this->User->addOrderEntrySetting(array(
                                "idOrderEntrySettings" => $data[$i]['idOrderEntrySettings'],
                                "settingName" => $data[$i]['orderEntrySettingName'],
                                "settingDescription" => $data[0]['orderEntrySettingDescription']
                            ));
                            $aryAddedOrderEntrySettings[] = $data[$i]['idOrderEntrySettings'];
                        }
                    }
                }

            } else if ($typeId == 2) {
                $this->User = new ClientUser($data[0]);
                $this->User->setRestrictedUserIds($aryRestrictedUserIds);
                if (array_key_exists('idUserSettings', $data[0]) && $data[0]['idUserSettings'] != null) {
                    $aryAddedSettings[] = $data[0]['idUserSettings'];
                }
                if (array_key_exists("idOrderEntrySettings", $data[0]) && $data[0]['idOrderEntrySettings'] != null) {
                    $this->User->addOrderEntrySetting(array(
                        "idOrderEntrySettings" => $data[0]['idOrderEntrySettings'],
                        "settingName" => $data[0]['orderEntrySettingName'],
                        "settingDescription" => $data[0]['orderEntrySettingDescription']
                    ));
                    $aryAddedOrderEntrySettings[] = $data[0]['idOrderEntrySettings'];
                }

                if (array_key_exists("idAccessSettings", $data[0]) && $data[0]['idAccessSettings'] != null) {
                    $oaSetting = new OrderAccessSetting(array(
                        "idAccessSettings" => $data[0]['idAccessSettings'],
                        "settingName" => $data[0]['accessSettingName']
                    ));
                    $this->User->setAccessSetting($oaSetting);

                    /*if ($data[0]['idAccessSettings'] == 3 || $data[0]['idAccessSettings'] == 4) { // get restricted users
                        $users = self::getRestrictedUsersByClientId($this->User->idClients, $this->User->idUsers, $this->Conn);
                        if (count($users) > 0) {
                            foreach ($users as $relatedUser) {
                                if ($relatedUser->IsRestrictedUser == true) {
                                    $this->User->addRestrictedUserId($relatedUser->idUsers);
                                }
                            }
                        }
                    }*/
                }

                if ($numRows > 1) {
                    for ($i = 1; $i < $numRows; $i++) {
                        if (!in_array($data[$i]['idUserSettings'], $aryAddedSettings)) {
                            $this->User->addUserSetting($data[$i]);
                            $aryAddedSettings[] = $data[$i]['idUserSettings'];
                        }
                        if ($data[$i]['idOrderEntrySettings'] != null && !in_array($data[$i]['idOrderEntrySettings'], $aryAddedOrderEntrySettings)) {
                            $this->User->addOrderEntrySetting(array(
                                "idOrderEntrySettings" => $data[$i]['idOrderEntrySettings'],
                                "settingName" => $data[$i]['orderEntrySettingName'],
                                "settingDescription" => $data[0]['orderEntrySettingDescription']
                            ));
                            $aryAddedOrderEntrySettings[] = $data[$i]['idOrderEntrySettings'];
                        }
                    }
                }
                if ($this->User->hasUserSetting(4)) { // client has multi user setting, so set up its multi users
                    $aryMultiUsers = $this->getMultiUsers();
                    if ($aryMultiUsers != null) {
                        $this->User->setMultiUsers($aryMultiUsers);
                    }
                }
            } else if ($typeId == 3) {
                $this->User = new DoctorUser($data[0]);
                $this->User->setRestrictedUserIds($aryRestrictedUserIds);
                if (array_key_exists("idUserSettings", $data[0]) && $data[0]['idUserSettings'] != null) {
                    $aryAddedSettings[] = $data[0]['idUserSettings'];
                }
                if (array_key_exists("idOrderEntrySettings", $data[0]) && $data[0]['idOrderEntrySettings'] != null) {
                    $this->User->addOrderEntrySetting(array(
                        "idOrderEntrySettings" => $data[0]['idOrderEntrySettings'],
                        "settingName" => $data[0]['orderEntrySettingName'],
                        "settingDescription" => $data[0]['orderEntrySettingDescription']
                    ));
                    $aryAddedOrderEntrySettings[] = $data[0]['idOrderEntrySettings'];
                }

                if (array_key_exists("idAccessSettings", $data[0]) && $data[0]['idAccessSettings'] != null) {
                    $oaSetting = new OrderAccessSetting(array(
                        "idAccessSettings" => $data[0]['idAccessSettings'],
                        "settingName" => $data[0]['accessSettingName']
                    ));
                    $this->User->setAccessSetting($oaSetting);
                }
                if ($numRows > 1) {
                    for ($i = 1; $i < $numRows; $i++) {
                        if (!in_array($data[$i]['idUserSettings'], $aryAddedSettings)) {
                            $this->User->addUserSetting($data[$i]);
                            $aryAddedSettings[] = $data[$i]['idUserSettings'];
                        }
                        if ($data[$i]['idOrderEntrySettings'] != null && !in_array($data[$i]['idOrderEntrySettings'], $aryAddedOrderEntrySettings)) {
                            $this->User->addOrderEntrySetting(array(
                                "idOrderEntrySettings" => $data[$i]['idOrderEntrySettings'],
                                "settingName" => $data[$i]['orderEntrySettingName'],
                                "settingDescription" => $data[0]['orderEntrySettingDescription']
                            ));
                            $aryAddedOrderEntrySettings[] = $data[$i]['idOrderEntrySettings'];
                        }
                    }
                }
            } else if ($typeId == 4) {
                $this->User = new PatientUser($data[count($data) - 1]);

            } else if ($typeId == 5) {
                $this->User = new SalesmanUser($data[0]);
                if ($numRows > 1) {
                    for ($i = 1; $i < $numRows; $i++) {
                        $this->User->addSalesSetting($data[$i]);
                    }
                }

                if ($this->User->IsGroupLeader) {

                }
            } else if ($typeId == 6) {
                $this->User = new InsuranceUser($data[0]);
            } else if ($typeId == 8) {
                $this->User = new PatientAdminUser($data[0]);
            }

            if ($includeCommonCodes && $this->TypeId != 1) {
                $this->setUserCommonCodes();
            }
            if ($includeCommonTests && $this->TypeId != 1) {
                $this->setUserCommonTests();
            }
            if ($includeExcludedTests && $this->TypeId != 1) {
                $this->setUserExcludedTests();
            }
            if ($includeRelatedUsers && $this->TypeId != 1) {
                $this->setRelatedUsers();
            }
            /*if ($includeClientProperties) {
                $this->setClientProperties();
            }*/
            if ($includeCommonDrugs && $this->TypeId != 1) {
                $this->setUserCommonDrugs();
            }

        }

        //echo "<pre>"; print_r($this->User); echo "</pre>";

        return $userIsSet;
    }

    public function getClientProperties() {
        /*$sql = "
            SELECT  cp.id, cp.name,
                    cpr.fieldName
            FROM " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTIES . " cp
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTYLOOKUP . " cpl ON cp.id = cpl.clientPropertyId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTYREQUIREDFIELDS . " cpr ON cp.id = cpr.clientPropertyId
            WHERE cpl.clientId = ?";*/
        $sql = "
            SELECT  cp.id, cp.name,
                    cpr.fieldName
            FROM " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTIES . " cp
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTYREQUIREDFIELDS . " cpr ON cp.id = cpr.clientPropertyId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTYREQUIREDFIELDLOOKUP . " cprl ON cpr.idRequiredFields = cprl.requiredFieldId
            WHERE cprl.clientId = ?";

        $data = parent::select($sql, array($this->User->idClients), array("Conn" => $this->Conn));

        $sql2 = "
            SELECT cp.id, cp.name, cpr.fieldName
            FROM " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTIES . " cp
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTYREQUIREDFIELDS . " cpr ON cp.id = cpr.clientPropertyId
            WHERE cp.name = 'Required Fields'";

        $data2 = parent::select($sql2, null, array("Conn" => $this->Conn));

        $sql3 = "
            SELECT cp.id
            FROM " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTYLOOKUP . " cpl
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTIES . " cp ON cpl.clientPropertyId = cp.id AND cp.name = 'Ignore Global Fields'
            WHERE cpl.clientId = ?";
        $data3 = parent::select($sql3, array($this->User->idClients), array("Conn" => $this->Conn));

        if (count($data) > 0) {

            if (count($data3) > 0) {
                // ignore global required fields. only use client specific required fields
                return $data;
            } else {
                // add client specific required fields to the list of global required fields
                foreach($data2 as $row) {
                    $data[] = $row;
                }
                return $data;
            }


        } else {
            // global required fields
            return $data2;
        }
    }

    public static function getGlobalRequiredFields() {
        $sql = "
            SELECT cp.id, cp.name, cpr.fieldName
            FROM " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTIES . " cp
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTYREQUIREDFIELDS . " cpr ON cp.id = cpr.clientPropertyId
            WHERE cp.name = 'Required Fields'";
        $data = parent::select($sql, null);

        $aryRequiredFields = array();

        if (count($data) > 0) {
            foreach($data as $row) {
                $aryRequiredFields[] = $row['fieldName'];
            }
        }

        return $aryRequiredFields;
    }

    private function setRelatedUsers() {
        $hasClientDoctorRelations = PreferencesDAO::getPreferenceByKey("ClientDoctorsOnly");

//        CASE WHEN ds.idDoctorSigs IS NOT NULL THEN true ELSE false END AS `DoctorSignatureSet`
//        LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORESIGNATURES . " ds ON d.iddoctors = ds.doctorId AND ds.isActive = true

        if ($hasClientDoctorRelations == null || $hasClientDoctorRelations->value === 'true') {
            $fromTable = "
                FROM " . self::DB_CSS . "." . self::TBL_CLIENTDOCTORRELATIONSHIP . " cd
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON cd.iddoctors = d.iddoctors
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON cd.idclients = c.idClients 
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORESIGNATURES . " ds ON d.iddoctors = ds.doctorId AND ds.isActive = true ";
        } else {
            $fromTable = "
                FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients 
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORESIGNATURES . " ds ON d.iddoctors = ds.doctorId AND ds.isActive = true ";
        }

        /*if ($hasClientDoctorRelations == null || $hasClientDoctorRelations->value === 'true') {
            $fromTable = "
                FROM " . self::DB_CSS . "." . self::TBL_CLIENTDOCTORRELATIONSHIP . " cd
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d2 ON cd.iddoctors = d2.iddoctors
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON d2.NPI = d.NPI
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c2 ON cd.idclients = c2.idClients
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON c2.npi = c.npi ";
        } else {
            $fromTable = "
                FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d2 ON o.doctorId = d2.iddoctors
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON d2.NPI = d.NPI
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c2 ON o.clientId = c2.idClients
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON c2.npi = c.npi ";
        }*/

        $sql = "
            SELECT DISTINCT c.idClients, c.clientNo, c.clientName, c.defaultReportType, c.active AS `clientActive`,
                            d.iddoctors, d.number, d.lastName, d.firstName, d.active AS `doctorActive`,
                            CASE WHEN ds.idDoctorSigs IS NOT NULL THEN true ELSE false END AS `DoctorSignatureSet`
            $fromTable
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON d.iddoctors = dl.doctorId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON c.idClients = cl.clientId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_USERS . " u ON dl.userId = u.idUsers OR cl.userId = u.idUsers
            WHERE u.isActive = ? AND d.iddoctors IS NOT NULL AND u.idUsers = ? ";

        $data = parent::select($sql, array(true, $this->User->idUsers));

        if (count($data) > 0) {
            foreach ($data as $row) {
                if ($this->TypeId == 2 && $row['doctorActive'] == 1) {
                    $this->User->addDoctor(array(
                        "iddoctors" => $row['iddoctors'],
                        "number" => $row['number'],
                        "lastName" => $row['lastName'],
                        "firstName" => $row['firstName'],
                        "DoctorSignatureSet" => $row['DoctorSignatureSet']
                    ));
                } else if ($this->TypeId == 3 && $row['clientActive'] == 1) {
                    $this->User->addClient(array(
                        "idClients" => $row['idClients'],
                        "clientNo" => $row['clientNo'],
                        "clientName" => $row['clientName'],
                        "defaultReportType" => $row['defaultReportType']
                    ));
                }
            }
        }
    }

    public function getRelatedDoctors() {
        $hasClientDoctorRelations = PreferencesDAO::getPreferenceByKey("ClientDoctorsOnly");

        if ($hasClientDoctorRelations == null || $hasClientDoctorRelations->value === 'true') {
            $fromTable = "
                FROM " . self::DB_CSS . "." . self::TBL_CLIENTDOCTORRELATIONSHIP . " cd
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON cd.iddoctors = d.iddoctors
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON cd.idclients = c.idClients ";
        } else {
            $fromTable = "
                FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients ";
        }

        $sql = "
            SELECT DISTINCT c.idClients, c.clientNo, c.clientName, c.defaultReportType, c.active AS `clientActive`,
                            d.iddoctors, d.number, d.lastName, d.firstName, d.active AS `doctorActive`, d.address1, d.city, d.state, d.zip,
                            u.idUsers, u.email, u.typeId, u.dateCreated, u.dateUpdated
            $fromTable
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON d.iddoctors = dl.doctorId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON c.idClients = cl.clientId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_USERS . " u ON dl.userId = u.idUsers
            WHERE u.isActive = ? AND d.iddoctors IS NOT NULL AND cl.userId = ? ";

        $data = parent::select($sql, array(true, $this->UserId));

        $aryReturn = array();
        if (count($data) > 0) {
            foreach ($data as $row) {
                if ($row['doctorActive'] == 1) {
                    $aryReturn[] = new DoctorUser(array(
                        "idUsers" => $row['idUsers'],
                        "email" => $row['email'],
                        "typeId" => $row['typeId'],
                        "dateCreated" => $row['dateCreated'],
                        "dateUpdated" => $row['dateUpdated'],
                        "iddoctors" => $row['iddoctors'],
                        "number" => $row['number'],
                        "lastName" => $row['lastName'],
                        "firstName" => $row['firstName'],
                        "address1" => $row['address1'],
                        "city" => $row['city'],
                        "state" => $row['state'],
                        "zip" => $row['zip']
                    ));
                }
            }
        }

        return $aryReturn;
    }


    private function setUserCommonCodes() {
        $sql = "
            SELECT dc.idDiagnosisCodes, dc.code, dc.description, dc.FullDescription, dc.version
            FROM " . self::DB_CSS . "." . self::TBL_COMMONDIAGNOSISCODES . " cd
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DIAGNOSISCODES . " dc ON cd.diagnosisCodeId = dc.idDiagnosisCodes
            WHERE cd.userId = ?
        ";
        $data = parent::select($sql, array($this->UserId));
        if (count($data) > 0) {
            foreach ($data as $row) {
                $this->User->addCommonDiagnosisCode($row);
            }
        }
    }

    private function setUserCommonTests() {

        $where = "WHERE ct.userId = ? AND t.active = true ";
        $aryInput = array($this->UserId);
        if (self::HasMultiLocation == true) {
            $where .= "AND t.locationId = ? ";
            $aryInput[] = $this->User->idLocation;
        }

        $sql = "
            SELECT t.idtests, t.number, t.name, t.testType, d.idDepartment, d.deptNo, d.deptName, s.idspecimenTypes, s.name AS `specimenTypeName`
            FROM " . self::DB_CSS . "." . self::TBL_COMMONTESTS . " ct
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON ct.testNumber = t.number
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTS . " d ON t.department = d.idDepartment
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SPECIMENTYPES . " s ON t.specimenType = s.idspecimenTypes
            $where
            ORDER BY s.name ASC, t.number ASC
        ";
        $data = parent::select($sql, $aryInput);
        if (count($data) > 0) {
            foreach ($data as $row) {
                $this->User->addCommonTest($row);
            }
        }
    }

    public function getUserCommonTests() {
        $where = "WHERE ct.userId = ? AND t.active = true ";
        $aryInput = array($this->UserId);
        if (self::HasMultiLocation == true) {
            $where .= "AND t.locationId = ? ";
            $aryInput[] = $this->User->idLocation;
        }

        $sql = "
            SELECT t.idtests, t.number, t.name, t.testType, d.idDepartment, d.deptNo, d.deptName, s.idspecimenTypes, s.name AS `specimenTypeName`
            FROM " . self::DB_CSS . "." . self::TBL_COMMONTESTS . " ct
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON ct.testNumber = t.number
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTS . " d ON t.department = d.idDepartment
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SPECIMENTYPES . " s ON t.specimenType = s.idspecimenTypes
            $where
            ORDER BY s.name ASC, t.number ASC
        ";
        $data = parent::select($sql, $aryInput);
        $aryCommonTests = array();
        if (count($data) > 0) {
            foreach ($data as $row) {
                $aryCommonTests[$row['idtests']] = new Test($row);
            }

        }
        return $aryCommonTests;
    }

    private function setUserCommonDrugs() {
        $sql = "
            SELECT  cd.idCommonDrugs, cd.userId, cd.drugId,
                    d.iddrugs, d.genericName,
                    s1.idsubstances AS `idsubstances1`, s1.substance AS `substance1`,
                    s2.idsubstances AS `idsubstances2`, s2.substance AS `substance2`,
                    s3.idsubstances AS `idsubstances3`, s3.substance AS `substance3`
            FROM " . self::DB_CSS . "." . self::TBL_COMMONDRUGS . " cd
            INNER JOIN " . self::DB_CSS . "." . self::TBL_DRUGS . " d ON cd.drugId = d.iddrugs
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SUBSTANCES . " s1 ON d.substance1 = s1.idsubstances
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SUBSTANCES . " s2 ON d.substance2 = s2.idsubstances
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SUBSTANCES . " s3 ON d.substance3 = s3.idsubstances
            WHERE cd.userId = ?";
        $data = parent::select($sql, array($this->UserId));

        if (count($data) > 0) {
            foreach ($data as $row) {
                $this->User->addCommonDrug($row);
            }
        }
    }

    private function setUserExcludedTests() {
        $where = "WHERE et.userId = ? AND t.active = true ";
        $aryInput = array($this->UserId);
        if (self::HasMultiLocation == true) {
            $where .= "AND t.locationId = ? ";
            $aryInput[] = $this->User->idLocation;
        }

        $sql = "
            SELECT t.idtests, t.number, t.name, t.testType, d.idDepartment, d.deptNo, d.deptName, s.idspecimenTypes, s.name AS `specimenTypeName`
            FROM " . self::DB_CSS . "." . self::TBL_EXCLUDEDTESTS . " et
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON et.testNumber = t.number
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTS . " d ON t.department = d.idDepartment
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SPECIMENTYPES . " s ON t.specimenType = s.idspecimenTypes
            $where
        ";
        $data = parent::select($sql, $aryInput);
        if (count($data) > 0) {
            foreach ($data as $row) {
                $this->User->addExcludedTest($row);
            }
        }
    }

    protected function getMultiUsers() {
        $sql = "
            SELECT mu.multiUserId
            FROM " . self::DB_CSS . "." . self::TBL_MULTIUSER . " mu
            INNER JOIN " . self::DB_CSS . "." . self::TBL_USERS . " u ON mu.multiUserId = u.idUsers
            WHERE u.isActive = ? AND mu.userId = ?";

        $data = parent::select($sql, array(true, $this->UserId), array("Conn" => $this->Conn));

        if (count($data) > 0) {
            $multiUsers = array();
            foreach ($data as $row) {
                $multiUsers[] = $row['multiUserId'];
            }
            return $multiUsers;

        }
        return null;
    }

    public static function getRestrictedUsersByClientId($idClients, $userId = null, mysqli $conn = null) {
        $aryInput = array();
        $select = "SELECT u.idUsers, u.email, c.idClients, c.clientNo, c.clientName ";
        $tables = "FROM " . self::DB_CSS . "." . self::TBL_CLIENTS . " c
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON c.idClients = cl.clientId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_USERS . " u ON cl.userId = u.idUsers AND u.isActive = true ";

        if ($userId != null) {
            $select .= ", CASE WHEN ru.idRestrictedUsers IS NOT NULL THEN true ELSE false END AS `userIsRestricted` ";
            $tables .= "LEFT JOIN " . self::DB_CSS . "." . self::TBL_RESTRICTEDUSERS . " ru ON u.idUsers = ru.restrictedUserId AND ru.userId = ? ";
            $aryInput[] = $userId;
        }

        $sql = $select . $tables . "WHERE c.idClients = ?;";
        $aryInput[] = $idClients;

        $data = parent::select($sql, $aryInput);

        $aryUsers = array();
        if (count($data) > 0) {
            foreach ($data as $row) {
                $clientUser = new ClientUser($row);
                if ($userId != null) {
                    $clientUser->IsRestrictedUser = $row['userIsRestricted'];
                }
                $aryUsers[] = $clientUser;
            }
        }

        return $aryUsers;
    }

    public static function getRestrictedUsersByDoctorId($iddoctors, $userId = null, mysqli $conn = null) {
        $aryInput = array();
        $select = "SELECT 	u.idUsers, u.email, d.iddoctors, d.number, d.lastName, d.firstName ";
        $tables = "FROM " . self::DB_CSS . "." . self::TBL_DOCTORS . " d
            INNER JOIN " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON d.iddoctors = dl.doctorId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_USERS . " u ON dl.userId = u.idUsers AND u.isActive = true ";

        if ($userId != null) {
            $select .= ", CASE WHEN ru.idRestrictedUsers IS NOT NULL THEN true ELSE false END AS `userIsRestricted` ";
            $tables .= "LEFT JOIN " . self::DB_CSS . "." . self::TBL_RESTRICTEDUSERS . " ru ON u.idUsers = ru.restrictedUserId AND ru.userId = ? ";
            $aryInput[] = $userId;
        }

        $sql = $select . $tables . "WHERE d.iddoctors = ?;";
        $aryInput[] = $iddoctors;

        $data = parent::select($sql, $aryInput);

        $aryUsers = array();
        if (count($data) > 0) {
            foreach ($data as $row) {
                $doctorUser = new DoctorUser($row);
                if ($userId != null) {
                    $doctorUser->IsRestrictedUser = $row['userIsRestricted'];
                }
                $aryUsers[] = $doctorUser;
            }
        }

        return $aryUsers;
    }

    public static function getUserIdsByClientId($clientId, mysqli $conn) {
        $sql = "
            SELECT  u.idUsers, u.email, cl.clientId
            FROM " . self::DB_CSS . "." . self::TBL_USERS . " u
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON u.idUsers = cl.userId
            WHERE u.isActive = 1 AND cl.clientId = ?";
        $data = parent::select($sql, array($clientId), array("Conn" => $conn));

        $aryUserIds = array();
        if (count($data) > 0) {
            foreach($data as $row) {
                $aryUserIds[] = $row['idUsers'];
            }
        }
        return $aryUserIds;
    }

    public static function getUserIdsByDoctorId($doctorId, mysqli $conn) {
        $sql = "
            SELECT  u.idUsers, u.email, dl.doctorId
            FROM " . self::DB_CSS . "." . self::TBL_USERS . " u
            INNER JOIN " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON u.idUsers = dl.userId
            WHERE u.isActive = 1 AND dl.doctorId = ?";
        $data = parent::select($sql, array($doctorId), array("Conn" => $conn));

        $aryUserIds = array();
        if (count($data) > 0) {
            foreach($data as $row) {
                $aryUserIds[] = $row['idUsers'];
            }
        }
        return $aryUserIds;
    }

    public static function getRestrictedUserIds($userId) {
        $sql = "
            SELECT restrictedUserId
            FROM " . self::DB_CSS . "." . self::TBL_RESTRICTEDUSERS . " ru
            WHERE ru.userId = ?";
        $data = parent::select($sql, array($userId));
        $aryUserIds = array();
        if (count($data) > 0) {
            foreach ($data as $row) {
                $aryUserIds[] = $row['restrictedUserId'];
            }
        }
        return $aryUserIds;
    }

    public static function getUserSettings($userId = null, array $settings = null) {
        $sql = "SELECT s.idUserSettings, s.settingName, s.settingDescription, s.pageName FROM " . self::DB_CSS . "." . self::TBL_USERSETTINGS . " s ";

        if ($userId != null) {

            $sql .= " INNER JOIN " . self::DB_CSS . "." . self::TBL_USERSETTINGSLOOKUP . " sl ON s.idUserSettings = sl.userSettingsd"
                . "WHERE sl.userId = ? "
                . "ORDER BY sl.settingName";
            $data = parent::select($sql, array($userId), $settings);
        } else {
            $data = parent::select($sql, null, $settings);
        }

        if (count($data) > 0) {
            $aryUserSettings = array();
            foreach ($data as $row) {
                $userSetting = new UserSetting($row);
                $aryUserSettings[] = $userSetting;
            }
            return $aryUserSettings;
        }
        return null;
    }

    public static function getOrderEntrySettings(array $data = null) {
        require_once 'DOS/OrderEntrySetting.php';

        $byUserId = false;
        $conn = null;
        if ($data != null) {
            if (array_key_exists("userId", $data) && $data['userId'] != null) {
                $byUserId = true;
            }
            if (array_key_exists("Conn", $data) && $data['Conn'] instanceof mysqli) {
                $conn = $data['Conn'];
            }
        }

        $sql = "
            SELECT oes.idOrderEntrySettings, oes.settingName, oes.settingDescription, oes.isActive, oes.checkedByDefault
            FROM " . self::DB_CSS . "." . self::TBL_ORDERENTRYSETTINGS . " oes
            WHERE oes.isActive = true
            ORDER BY oes.settingName";

        $data = parent::select($sql, null, array("Conn" => $conn));

        $arySettings = array();

        if (count($data) > 0) {
            foreach($data as $row) {
                $arySettings[] = new OrderEntrySetting($row);
            }
        }

        return $arySettings;
    }

    public static function getUserIdListBySearch(array $searchFields, array $settings = null) {

        if (array_key_exists("typeId", $searchFields)) {
            $typeId = $searchFields['typeId'];
            $sql = "
                    SELECT u.idUsers
                    FROM " . self::DB_CSS . "." . self::TBL_USERS . " u ";

            if ($typeId == 2) {
                $sql .= "INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON u.idUsers = cl.userId
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON cl.clientId = c.idClients
                    WHERE c.clientName LIKE ?
                    ORDER BY u.idUsers
                ";

                $qryInput = array("%" . $searchFields['name'] . "%");

                $data = parent::select($sql, $qryInput);

            } else if ($typeId = 3) {
                $sql .= "INNER JOIN " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON u.idUsers = dl.userId
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON dl.doctorId = d.iddoctors
                    WHERE CONCAT(d.firstName, ' ', d.lastName) LIKE ?
                    ORDER BY u.idUsers
                ";

                $qryInput = array("%" . $searchFields['name'] . "%");

                $data = parent::select($sql, $qryInput);
            }

            if (count($data) > 0) {
                $aryIdUsers = array();
                foreach($data as $row) {
                    $aryIdUsers[] = $row['idUsers'];
                }

                return array("ids" => $aryIdUsers);
            }
        }


        return false;
    }

    public static function userEmailExists($email, $idUsers, $activeOnly = null) {

        $sql = "
            SELECT COUNT(*) AS `cnt`
            FROM " . self::DB_CSS . "." . self::TBL_USERS . " u
            WHERE u.email = ? AND u.idUsers != ?";
        $aryInput = array($email, $idUsers);

        if ($activeOnly != null) {
            if ($activeOnly) {
                $sql .= " AND u.isActive = true ";
            } else {
                $sql .= " AND u.isActive = false ";
            }
        }

        $data = parent::select($sql, $aryInput);
        if ($data[0]['cnt'] > 0) {
            return true;
        }
        return false;
    }


    public static function verifyWebUser(array $input) {

        $sql = "SELECT * FROM " . self::DB_CSS . "." . self::TBL_USERS . " u WHERE u.verificationCode = ?";
        $data = parent::select($sql, array($input['verificationCode']));

        /*error_log($sql);
        error_log($input['verificationCode']);*/

        if (count($data) > 0) {
            $idUsers = $data[0]['idUsers'];

            $sql = "UPDATE " . self::DB_CSS . "." . self::TBL_USERS . " SET isVerified = 1 WHERE idUsers = ?";

            /*error_log($sql);
            error_log($idUsers);*/

            $data = parent::manipulate($sql, array($idUsers), array("AffectedRows" => true));
            return 1;
        }
        return 0;
    }

    public function getClientDoctorUsers(array $inputs = null) {
        $sql = "
            SELECT  u.idUsers, u.email, u.typeId,
                    c.idClients, c.clientNo, c.clientName,
                    d.iddoctors, d.number, d.lastName, d.firstName
            FROM " . self::DB_CSS . "." . self::TBL_USERS . " u
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl ON u.idUsers = cl.userId AND u.typeId = 2
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORLOOKUP . " dl ON u.idUsers = dl.userId AND u.typeId = 3
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON cl.clientId = c.idClients
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON dl.doctorId = d.iddoctors
            WHERE u.isActive = 1 AND (u.typeId = 2 OR u.typeId = 3)";

        $sql .= " ORDER BY u.typeId ASC, c.clientName ASC, d.lastName ASC";

        $data = parent::select($sql, null, array("Conn" => $this->Conn));

        $aryUsers = array();

        if (count($data) > 0) {
            foreach ($data as $row) {
                if ($row['typeId'] == 2) {
                    $aryUsers[] = new ClientUser($row);
                } else if ($row['typeId'] == 3) {
                    $aryUsers[] = new DoctorUser($row);
                }
            }
        }

        return $aryUsers;
    }


    public function deactivateUser($userId = null) {
        $sql = "UPDATE " . self::DB_CSS . "." . self::TBL_USERS . " SET isActive = 0 WHERE idUsers = ?";

        if (isset($userId)) {
            parent::manipulate($sql, array($userId));
            return true;
        } else if (isset($this->User) && isset($this->User->idUsers)) {
            parent::manipulate($sql, array($this->User->idUsers));
            return true;
        }
        return false;
    }

    public function updateVerificationCode($userId = null) {
        require_once 'Utility/Auth.php';
        $code = Auth::generateVerificationCode2();
        $sql = "UPDATE " . self::DB_CSS . "." . self::TBL_USERS . " SET verificationCode = ? WHERE idUsers = ?";
        if (isset($userId)) {
            parent::manipulate($sql, array($code, $userId));
        } else if (isset($this->User) && isset($this->User->idUsers)) {
            parent::manipulate($sql, array($code, $this->User->idUsers));
        }
        return $code;
    }

    public function resetPassword($hashedPassword, $userSalt, $userId) {
        $dteNow = new DateTime("now", new DateTimeZone("EDT"));
        $now = $dteNow->format("Y-m-d H:i:s");
        $sql = "UPDATE " . self::DB_CSS . "." . self::TBL_USERS . " SET password = ?, userSalt = ?, dateUpdated = ?, isActive = ? WHERE idUsers = ?";
        parent::manipulate($sql, array($hashedPassword, $userSalt, $now, 1, $userId));
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
        if ($field == "User" && isset($this->User) && $this->User != null && $this->User instanceof User) {
            $isset = true;
        }
        return $isset;
    }

    public function addSetting($field, $value) {
        $this->Settings[$field] = $value;
    }

    public function getLabInfo() {
        require_once 'DOS/LabInfo.php';

        $data = parent::select(
            "SELECT * FROM" . " " . self::DB_CSS . "." . self::TBL_LABMASTER,
            null,
            array("Conn" => $this->Conn)
        );
        return new LabInfo($data[0]);
    }


}


?>
