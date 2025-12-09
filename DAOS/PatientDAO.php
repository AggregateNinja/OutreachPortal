<?php
require_once 'DataObject.php';
require_once 'DOS/Patient.php';


class PatientDAO extends DataObject {

    private $Conn;

    public function __construct(array $data = null) {
        parent::__construct($data);
        $this->Conn = parent::connect();
    }

    public static function insertPatient(Patient $patient, $connectToWeb = true) {
        $patientId = "";
        //error_log($patient);
        $schema = self::DB_CSS_WEB;
        if ($connectToWeb == false) {
            $schema = self::DB_CSS;
        }

        $sql = "
            INSERT INTO " . $schema . "." . self::TBL_PATIENTS . "(
                arNo, lastName, firstName, middleName, sex, ssn, dob, addressStreet, addressStreet2, addressCity, 
                addressState, addressZip, phone, workPhone, subscriber, species, height, weight, ethnicity, smoker, relationship
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";

        if (!empty($patient->subscriber)) {
            $subscriber = $patient->subscriber;
        } else {
            $subscriber = null;
        }
        /*  $dob = null;
         if (isset($patient->dob) && !empty($patient->dob)) {
             $dob = $patient->dob;
         } */
        /*$ssn = "";
        if (!empty($patient->ssn)) {
            $ssn =
        }*/
        $input = array(
            $patient->arNo, $patient->lastName, $patient->firstName, $patient->middleName, $patient->sex, $patient->ssn,
            $patient->dob, $patient->addressStreet, $patient->addressStreet2, $patient->addressCity,
            $patient->addressState, $patient->addressZip, $patient->phone, $patient->workPhone, $subscriber, $patient->species,
            $patient->height, $patient->weight, $patient->ethnicity, $patient->smoker, $patient->relationship
        );
        $qryInput = array();
        foreach ($input as $key => $value) {
            if (empty($value)) {
                $qryInput[] = null;
            } else {
                $qryInput[] = $value;
            }
        }
        $patientId = parent::manipulate($sql, $qryInput, array("ConnectToWeb" => $connectToWeb, "LastInsertId" => true));

        if (!isset($patientId) || empty($patientId) || $patientId == 0) {
            error_log($sql);
            error_log(implode(",", $input));
        }

        if (!isset($patientId) || empty($patientId) || $patientId == 0 || $patientId == null || $patientId == '') {
            error_log($sql);
            error_log(implode(",", $input));
        }

        return $patientId;
    }
    
    public static function deletePatient($idPatients) {
        $sql = "DELETE FROM " . self::TBL_PATIENTS . " WHERE idPatients = ?";
        $affectedRows = parent::manipulate($sql, array($idPatients), array("ConnectToWeb" => true, "AffectedRows" => true));
        return $affectedRows;
    }

    public static function getPatients(array $inputFields, array $settings = null) {
        $aryQueryInput = array();
        $sql = "
            SELECT  p.idPatients, p.arNo, p.lastName, p.middleName, p.firstName, p.dob, p.sex, p.subscriber,
                    p.ssn, p.ethnicity, p.smoker, p.height, p.weight, p.addressStreet, p.addressStreet2,
                    p.addressCity, p.addressState, p.addressZip, p.phone, p.workPhone, p.relationship,
                    i.idinsurances AS `insurance`, i.name AS `insuranceName`, s.groupNumber, s.policyNumber,
                    i2.idinsurances AS `secondaryInsurance`, i2.name AS `secondaryInsuranceName`, s.secondaryGroupNumber, s.secondaryPolicyNumber,
                    s.idsubscriber, s.policyNumber, s.secondaryPolicyNumber, s.groupNumber, s.secondaryGroupNumber, s.medicareNumber, s.medicaidNumber
            FROM " . self::TBL_PATIENTS . " p
            LEFT JOIN " . self::TBL_SUBSCRIBER . " s ON p.subscriber = s.idsubscriber
            LEFT JOIN " . self::TBL_INSURANCES . " i ON s.insurance = i.idinsurances
            LEFT JOIN " . self::TBL_INSURANCES . " i2 ON s.secondaryInsurance = i2.idinsurances
        ";

        $hasOrdersTable = false;
        if (array_key_exists("doctorId", $inputFields) || array_key_exists("clientId", $inputFields)) {
            $sql .= " INNER JOIN " . self::TBL_ORDERS . " o ON o.patientId = p.idPatients ";
            $hasOrdersTable = true;
        }
        $where = " WHERE ";
        $multiUserSearch = false;
        if (array_key_exists("clientId", $inputFields) && array_key_exists("multiUserIds", $inputFields) && !empty($inputFields['multiUserIds'])) {
            $multiUserSearch = true;

            $aryMultiUserIds = explode(",", $inputFields['multiUserIds']);
            if (is_array($aryMultiUserIds) && count($aryMultiUserIds) > 0) {
                $sql2 = "SELECT cl.clientId FROM " . self::DB_CSS . "." . self::TBL_CLIENTLOOKUP . " cl WHERE cl.userId IN (";
                foreach ($aryMultiUserIds as $userId) {
                    $sql2 .= "?,";
                }
                $sql2 = substr($sql2, 0, strlen($sql2) - 1) . ")";
                $data2 = parent::select($sql2, $aryMultiUserIds);
                if (count($data2) > 0) {
                    $where .= "clientId IN (?,";
                    $aryQueryInput[] = $inputFields['clientId'];
                    foreach ($data2 as $row) {
                        $where .= "?,";
                        $aryQueryInput[] = $row['clientId'];
                    }
                    $where = substr($where, 0, strlen($where) - 1) . ") AND ";
                }
            }
        }
        foreach ($inputFields as $field => $value) {
            if ($field == "p.lastName" || $field == "lastName" || $field == "p.arNo") {
                $where .= "$field LIKE ? AND ";
                $aryQueryInput[] = "$value%";
            //} else if ($field == "dob") {
                //$where .= "$field ="
            } else if ($multiUserSearch && ($field == "clientId" || $field == "multiUserIds")) {
                // do nothing. multi user search handled outside of loop
            } else {
                $where .= "$field = ? AND ";
                $aryQueryInput[] = $value;
            }
        }
        $where = substr($where, 0, strlen($where) - 4);
        $sql .= $where;
        if ($hasOrdersTable) {
            $sql .= " GROUP BY idPatients ";
        }
        $sql .= "   ORDER   BY lastName LIMIT 50";

        /*error_log($sql);
        error_log(implode(", ", $aryQueryInput));*/

        /*error_log($sql2);
        error_log(implode($aryMultiUserIds, ","));*/

        if (isset(self::$Conn) && self::$Conn != null) {
            $data = parent::select($sql, $aryQueryInput, array("Conn" => self::Conn));
        } else {
            $data = parent::select($sql, $aryQueryInput);
        }


        $aryPatients = array();
        if (count($data) > 0) {
            foreach ($data as $row) {
                $patient = new Patient($row, $settings);
                $patient->PatientSource = 0;

                if (isset($row['insurance']) && $row['insurance'] != null) {
                    $patient->setInsurance(array("idinsurances" => $row['insurance'], "name" => $row['insuranceName']));
                }

                if (isset($row['secondaryInsurance']) && $row['secondaryInsurance'] != null) {
                    $patient->setSecondaryInsurance(array("idinsurances" => $row['secondaryInsurance'], "name" => $row['secondaryInsuranceName']));
                }
                if (isset($row['idsubscriber']) && $row['idsubscriber'] != null) {
                    $patient->setSubscriber(array(
                        "idsubscriber" => $row['idsubscriber'],
                        "policyNumber" => $row['policyNumber'],
                        "secondaryPolicyNumber" => $row['secondaryPolicyNumber'],
                        "groupNumber" => $row['groupNumber'],
                        "secondaryGroupNumber" => $row['secondaryGroupNumber'],
                        "medicareNumber" => $row['medicareNumber'],
                        "medicaidNumber" => $row['medicaidNumber']
                    ));
                }

                $aryPatients[] = $patient;
            }
        }

        return $aryPatients;
    }

    public static function insertPatientPrescription($patientId, $drugId, mysqli $conn = null) {
        $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_PATIENTPRESCRIPTIONS . " (patientId, drugId) VALUES (?, ?)";
        return parent::manipulate($sql, array($patientId, $drugId), array("Conn" => $conn));
    }

    public static function deletePatientPrescription($patientId, $drugId, mysqli $conn = null) {
        $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_PATIENTPRESCRIPTIONS . " WHERE patientId = ? AND drugId = ?";
        return parent::manipulate($sql, array($patientId, $drugId), array("Conn" => $conn));
    }

    public function getPatientPrescriptions($patientId) {
        require_once 'DOS/PatientPrescription.php';

        $sql = "SELECT pp.idPatientPrescriptions, pp.patientId, pp.drugId, pp.isActive, pp.dateCreated,
              d.iddrugs, d.genericName, d.substance1, d.substance2, d.substance3,
              s.idsubstances AS `idsubstances1`, s.substance AS `substance1`
            FROM " . self::DB_CSS . "." . self::TBL_PATIENTPRESCRIPTIONS . " pp
            INNER JOIN " . self::DB_CSS . "." . self::TBL_DRUGS . " d ON pp.drugId = d.iddrugs 
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SUBSTANCES . " s ON d.substance1 = s.idsubstances 
            WHERE pp.patientId = ?";

        $data = parent::select($sql, array($patientId), array("Conn" => $this->Conn));

        $aryScripts = array();
        if (count($data) > 0) {
            foreach ($data as $row) {
                $aryScripts[] = new PatientPrescription($row);
            }
        }
        return $aryScripts;
    }

    public function getPreviouslyOrderedScripts(array $inputFields, array $settings = null) {
        require_once 'DOS/Drug.php';

        $aryScripts = array();

        /*$sql = "
        SELECT 	o.idOrders, o.accession,
                p.idPatients, p.arNo, p.lastName, p.firstName,
                d.iddrugs, d.genericName,
                s.idsubstances AS `idsubstances1`, s.substance AS `substance1`,
                o.orderDate, o.specimenDate
        FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o
        INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
        INNER JOIN " . self::DB_CSS_WEB . "." . self::TBL_PRESCRIPTIONS . " pr ON o.idOrders = pr.orderId
        INNER JOIN " . self::DB_CSS . "." . self::TBL_DRUGS . " d ON pr.drugId = d.iddrugs
        INNER JOIN " . self::DB_CSS . "." . self::TBL_SUBSTANCES . " s ON d.substance1 = s.idsubstances
        WHERE	p.idPatients = ?
                AND o.idOrders = (
                    SELECT o.idOrders
                    FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
                    WHERE p.idPatients = ?
                    ORDER BY o.orderDate DESC
                    LIMIT 1
                )";*/
        $sql = "
        SELECT 	o.idOrders, o.accession,
                p.idPatients, p.arNo, p.lastName, p.firstName,
                d.iddrugs, d.genericName,
                s.idsubstances AS `idsubstances1`, s.substance AS `substance1`,
                o.orderDate, o.specimenDate
        FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
        INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
        INNER JOIN " . self::DB_CSS . "." . self::TBL_PRESCRIPTIONS . " pr ON o.idOrders = pr.orderId
        INNER JOIN " . self::DB_CSS . "." . self::TBL_DRUGS . " d ON pr.drugId = d.iddrugs
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_SUBSTANCES . " s ON d.substance1 = s.idsubstances
        WHERE	p.idPatients = ?
                AND o.idOrders = (
                    SELECT rl.idOrders
                    FROM " . self::DB_CSS . "." . self::TBL_ORDERRECEIPTLOG . " rl
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON rl.idOrders = o.idOrders
                    INNER JOIN " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o2 ON rl.webAccession = o2.accession
                    WHERE o.patientId = ?
                    ORDER BY o2.orderDate DESC
                    LIMIT 1
                )";

        /*error_log($sql);
        error_log(implode(array($inputFields['idPatients'], $inputFields['idPatients']), ","));*/

        $data = parent::select($sql, array($inputFields['idPatients'], $inputFields['idPatients']), array("Conn" => $this->Conn));
        if (count($data) > 0) {
            foreach($data as $row) {
                $aryScripts[] = new Drug($row, true);
            }
        }

        return $aryScripts;
    }

    public function getPreviouslyOrderedIcdCodes(array $inputFields, array $settings = null) {
        require_once 'DOS/DiagnosisCode.php';

        /*$sql = "
        SELECT 	o.idOrders, o.accession,
                p.idPatients, p.arNo, p.lastName, p.firstName,
                dc.idDiagnosisCodes, dc.code, dc.description, dc.version,
                o.orderDate, o.specimenDate
        FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o
        INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
        LEFT JOIN " . self::DB_CSS_WEB . "." . self::TBL_ORDERDIAGNOSISLOOKUP . " odl ON o.idOrders = odl.idOrders
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_DIAGNOSISCODES . " dc ON odl.idDiagnosisCodes = dc.idDiagnosisCodes
        WHERE	p.idPatients = ?
                AND dc.idDiagnosisCodes IS NOT NULL
                AND o.idOrders = (
                    SELECT o.idOrders
                    FROM " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
                    WHERE p.idPatients = ?
                    ORDER BY o.orderDate DESC
                    LIMIT 1
                )";*/
        $sql = "
        SELECT 	o.idOrders, o.accession,
                p.idPatients, p.arNo, p.lastName, p.firstName,
                dc.idDiagnosisCodes, dc.code, dc.description, dc.version,
                o.orderDate, o.specimenDate
        FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
        INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_ORDERDIAGNOSISLOOKUP . " odl ON o.idOrders = odl.idOrders
        LEFT JOIN " . self::DB_CSS . "." . self::TBL_DIAGNOSISCODES . " dc ON odl.idDiagnosisCodes = dc.idDiagnosisCodes
        WHERE	p.idPatients = ?
                AND dc.idDiagnosisCodes IS NOT NULL
                AND o.idOrders = (
                    SELECT rl.idOrders
                    FROM " . self::DB_CSS . "." . self::TBL_ORDERRECEIPTLOG . " rl
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON rl.idOrders = o.idOrders
                    INNER JOIN " . self::DB_CSS_WEB . "." . self::TBL_ORDERS . " o2 ON rl.webAccession = o2.accession
                    WHERE o.patientId = ?
                    ORDER BY o2.orderDate DESC
                    LIMIT 1
                )";

        $data = parent::select($sql, array($inputFields['idPatients'], $inputFields['idPatients']), array("Conn" => $this->Conn));

        $aryIcdCodes = array();
        if (count($data) > 0) {
            foreach($data as $row) {
                $aryIcdCodes[] = new DiagnosisCode($row);
            }
        }

        return $aryIcdCodes;
    }

    public static function patientExists($idPatients, array $settings = null) {
        $connectToWeb = false;
        if ($settings != null) {
            if (array_key_exists("ConnectToWeb", $settings) && $settings['ConnectToWeb'] == true) {
                $connectToWeb = true;
            }
        }
        
        $sql = "
            SELECT COUNT(*) as 'cnt'
            FROM " . self::TBL_PATIENTS . "
            WHERE idPatients = ?";

        
        if ($connectToWeb) {
            $data = parent::select($sql, array($idPatients), array("ConnectToWeb" => true));
        } else {
            $data = parent::select($sql, array($idPatients));
        }

        if (array_key_exists("cnt", $data[0]) && $data[0]['cnt'] > 0) {
            return true;
        }        
        
        return false;
    }
    
    public static function updatePatient(Patient $patient) {
        $sql = "
            UPDATE " . self::TBL_PATIENTS . " 
            SET arNo = ?,
                lastName = ?,
                firstName = ?,
                middleName = ?,
                sex = ?,
                ssn = ?,
                dob = ?,
                addressStreet = ?,
                addressStreet2 = ?,
                addressCity = ?,
                addressState = ?,
                addressZip = ?,
                phone = ?,
                workPhone = ?,
                subscriber = ?,
                relationship = ?,
                counselor = ?,
                species = ?,
                height = ?,
                weight = ?,
                ethnicity = ?,
                smoker = ?
            WHERE idPatients = ?
        ";
        $qryInput = array(
            $patient->arNo,
            $patient->lastName,
            $patient->firstName,
            $patient->middleName,
            $patient->sex,
            $patient->ssn,
            $patient->dob,
            $patient->addressStreet,
            $patient->addressStreet2,
            $patient->addressCity,
            $patient->addressState,
            $patient->addressZip,
            $patient->phone,
            $patient->workPhone,
            $patient->subscriber,
            $patient->relationship,
            $patient->counselor,
            $patient->species,
            $patient->height,
            $patient->weight,
            $patient->ethnicity,
            $patient->smoker,
            $patient->idPatients
        );

        //echo $sql . "<pre>"; print_r($qryInput); echo "</pre>";
        parent::manipulate($sql, $qryInput, array("ConnectToWeb" => true));
        
        return true;
    }

    public static function getPatient(array $input, array $settings = null) {
        $useConn = false;
        $connectToWeb = false;

        if ($settings != null) {
            if (array_key_exists("ConnectToWeb", $settings) && $settings['ConnectToWeb'] == true) {
                $connectToWeb = true;
            } else if (array_key_exists("Conn", $settings) && $settings['Conn'] instanceof mysqli) {
                $useConn = true;
            }
        }

        $sqlInput = array();
        $where = "WHERE ";
        if (array_key_exists("idPatients", $input)) {
            $where .= "p.idPatients = ? ";
            $sqlInput[] = $input['idPatients'];
        }
        if (array_key_exists("arNo", $input)) {
            if (count($sqlInput) > 0) {
                $where .= "AND p.arNo = ? ";
            } else {
                $where .= "p.arNo = ? ";
            }
            $sqlInput[] = $input['arNo'];
        }
        if (array_key_exists("firstName", $input)) {
            if (count($sqlInput) > 0) {
                $where .= "AND p.firstName = ? ";
            } else {
                $where .= "p.firstName = ? ";
            }
            $sqlInput[] = $input['firstName'];
        }
        if (array_key_exists("lastName", $input)) {
            if (count($sqlInput) > 0) {
                $where .= "AND p.lastName = ? ";
            } else {
                $where .= "p.lastName = ? ";
            }
            $sqlInput[] = $input['lastName'];
        }
        if (array_key_exists("dob", $input)) {
            if (count($sqlInput) > 0) {
                $where .= "AND p.dob = ? ";
            } else {
                $where .= "p.dob = ? ";
            }
            $sqlInput[] = date("Y-m-d", strtotime($input['dob'])) . " 00:00:00";
        }

        $sql = "
            SELECT  p.idPatients, p.arNo, p.lastName, p.firstName, p.middleName, p.sex, p.ssn, p.dob, 
                    p.addressStreet, p.addressStreet2, p.addressCity, p.addressState, p.addressZip, 
                    p.phone, p.workPhone, p.species, p.height, p.weight, p.ethnicity, p.smoker, p.subscriber,
                    p.relationship
            FROM " . self::TBL_PATIENTS . " p
            $where";

        if ($useConn) {
            $data = parent::select($sql, $sqlInput, array("Conn" => $settings['Conn'])); // select new patient
            $patientSource = 1;
        } else if ($connectToWeb) {
            $data = parent::select($sql, $sqlInput, array("ConnectToWeb" => true)); // select new patient
            $patientSource = 1;
        } else {
            $data = parent::select($sql, $sqlInput); // select existing patient
            $patientSource = 0;
        }

        /*error_log($sql);
        error_log(implode(",", $sqlInput));*/



        //  if (count($data) == 0) {
        // 	$data = parent::select($sql, array($input['idPatients']), array("ConnectToWeb" => true));
        if (count($data) > 0) {
            $patient = new Patient($data[0], $settings);
            $patient->PatientSource = $patientSource;
            return $patient;
        }
        //} else {
        //    $patient = new Patient($data[0], $settings);
        //    $patient->PatientSource = 0;
        //   return $patient;
        //}

        return false;
    }
    
    public static function getLoggedInPatient($patientId, array $settings = null) {
    	
    	$sql = "
    		SELECT 	l.idLoggedIn, l.patientId, l.sessionId, l.token, l.loginDate, 
    				p.idPatients, p.arNo, p.lastName, p.firstName, p.middleName, p.sex, p.ssn, p.dob, 
                   	p.addressStreet, p.addressStreet2, p.addressCity, p.addressState, p.addressZip, 
                   	p.phone, p.workPhone, p.species, p.height, p.weight, p.ethnicity, p.smoker, p.subscriber,
                   	p.relationship
    		FROM " . self::TBL_LOGGEDINPATIENT . " l
    		INNER JOIN " . self::TBL_PATIENTS . " p ON l.patientId = p.idPatients
    		WHERE l.patientId = ?";
    	$data = parent::select($sql, array($patientId), $settings);
    	if (count($data) > 0) {
    		$patient = new Patient($data[0]);
    		$patient->LoggedInPatient = $data[0];
    		
    		return $patient;
    	}
    	
    	return false;
    }
    
    public static function getPatientId(array $input, array $settings = null) {
        $approvedOnly = false;
    	if ($settings != null) {
    		if (array_key_exists("ApprovedOrdersOnly", $settings) && $settings['ApprovedOrdersOnly'] == true) {
    			$approvedOnly = true;
    		}
    	}
        
    	if (count($input) > 0) {
                $sql = "
	    		SELECT p.idPatients
	    		FROM " . self::TBL_PATIENTS . " p
	    		INNER JOIN " . self::TBL_ORDERS . " o ON p.idPatients = o.patientId ";
                // ensures that orders without any approved results will not be selected, thus, not letting the patient view them, 
	    	// or not letting the patient log in if they have no approved orders
	    	if ($approvedOnly) {
	    		$sql .= "
	    			INNER JOIN (
                        SELECT DISTINCT orderId
                        FROM " . self::TBL_RESULTS . "
                        WHERE isApproved = 1
                	) ra ON o.idOrders = ra.orderId	
	    		";
	    		
	    	}
                
    		$sql .= " WHERE ";
                
	    	$qryInput = array();
    		foreach ($input as $key => $value) {
    			$sql .= $key . " = ? AND ";
    			$qryInput[] = $value;	
    		}
    		$sql = substr($sql, 0, strlen($sql) - 4);
    		
    		//echo $sql;
    		//echo "<pre>"; print_r($qryInput); echo "</pre>";
    		
    		$data = parent::select($sql, $qryInput);
    		if (count($data) > 0) {
    			return $data[0]['idPatients'];
    		}
    		
    	}

    	return false;   
    }
    
    public static function setNewLogin($patientId, $sessionId, $token, array $settings = null) {


    	$sql = "DELETE FROM " . self::TBL_LOGGEDINPATIENT . " WHERE patientId = ?";
    	parent::manipulate($sql, array($patientId), $settings);
    	
    	$sql = "
            INSERT INTO " . self::TBL_LOGGEDINPATIENT . " (patientId, sessionId, token)
            VALUES (?, ?, ?)";
    	self::manipulate($sql, array($patientId, $sessionId, $token), $settings);
    }
    
    public static function logout($patientId) {
    	$sql = "DELETE FROM " . self::TBL_LOGGEDINPATIENT . " WHERE patientId = ?";
    	parent::manipulate($sql, array($patientId));
    
    }
    
    public static function addPatientLogEntry($patientId, $typeId, array $other = null) {
        $conn = null;
        if ($other != null && array_key_exists("Conn", $other) && $other['Conn'] instanceof mysqli) {
            $conn = $other['Conn'];
        }

    	$sql = "INSERT INTO " . self::TBL_PATIENTLOG . " (patientId, typeId) VALUES (?, ?)";
        $logSettings = array(
            "LastInsertId" => true,
            "Conn" => $conn
        );
    	$idPatientLogs = parent::manipulate($sql, array($patientId, $typeId), $logSettings);
    	
    	if ($typeId == 3) { // Add to Patient View Log
    		if ($other != null && array_key_exists("OrderIds", $other)) {
    			$sql = "INSERT INTO " . self::TBL_PATIENTVIEWLOG . " (patientLogId, orderId) VALUES ";
    			$input = array();
    			foreach ($other['OrderIds'] as $id) {
    				$sql .= "(?, ?), ";
    				$input[] = $idPatientLogs;
    				$input[] = $id;
    			}
    			$sql = substr($sql, 0, strlen($sql) - 2);
    			parent::manipulate($sql, $input, array("Conn" => $conn));
    		}
    	}
    	
    	//return $idPatientLogs;
    }

    public static function patientArNoExists($arNo, $includeWebPatients = false) {
        $arNoExists = true;

        $sql = "
            SELECT COUNT(*) AS `cnt`
            FROM " . self::DB_CSS . "." . self::TBL_PATIENTS . " p
            WHERE arNo = ?";
        $data = parent::select($sql, array($arNo));
        if (count($data) > 0 && $data[0]['cnt'] == 0) {
            $arNoExists = false;
        }

        if ($includeWebPatients == true) {
            $sql = "
                SELECT COUNT(*) AS `cnt`
                FROM " . self::DB_CSS_WEB . "." . self::TBL_PATIENTS . " p
            WHERE arNo = ?";
            $data = parent::select($sql, array($arNo));
            if (count($data) > 0 && $data[0]['cnt'] == 0) {
                $arNoExists = false;
            }
        }

        return $arNoExists;
    }
}
?>