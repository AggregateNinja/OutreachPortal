<?php
/**
 * Created by PhpStorm.
 * User: Ed Bossmeyer
 * Date: 10/15/14
 * Time: 12:01 PM
 *
 * SalesDAO_Billing
 */
require_once 'DataObject.php';
require_once 'DOS/SalesmenCollection.php';
require_once 'DOS/SalesSetting.php';
require_once 'DOS/SalesGoalType.php';
require_once 'DOS/SalesGoalInterval.php';
require_once 'DOS/SalesGoal.php';
require_once 'ResultLogDAO.php';

require_once 'Numbers/Words.php';

class SalesDAO extends DataObject {

    private $WhereClause2 = array();
    private $Conn2;
    private $Data2 = array();

    private $WhereClause = array();
    private $Conn;
    protected $Data = array();

    public function __construct(array $data = null) {

        $this->Conn = parent::connect();

        if ($data != null) {
            $this->Data = $data;
            if (array_key_exists("group", $data) && !empty($data['group'])) {
                $this->WhereClause[] = array("sg.groupName", "LIKE", "%" . $data['group'] . "%");
            }
            if (array_key_exists("name", $data) && !empty($data['name'])) {
                $this->WhereClause[] = array("CONCAT(e.firstName, ' ', e.lastName)", "LIKE", "%" . $data['name'] . "%");
            }
            if (array_key_exists("groupLeader", $data) && !empty($data['groupLeader'])) {
                $this->WhereClause[] = array("CONCAT(el.firstName, ' ', el.lastName)", "LIKE", "%" . $data['groupLeader'] . "%");
            }
            if (array_key_exists("salesTerritory", $data) && !empty($data['salesTerritory'])) {
                $this->WhereClause[] = array("t.territoryName", "LIKE", "%" . $data['salesTerritory'] . "%");
            }
            if (array_key_exists("address", $data) && !empty($data['address'])) {
                $this->WhereClause[] = array("CONCAT(e.address, ' ', e.address2)", "LIKE", "%" . $data['address'] . "%");
            }
            if (array_key_exists("city", $data) && !empty($data['city'])) {
                $this->WhereClause[] = array("e.city", "LIKE", "%" . $data['city'] . "%");
            }
            if (array_key_exists("state", $data) && !empty($data['state'])) {
                $this->WhereClause[] = array("e.state", "LIKE", "%" . $data['state'] . "%");
            }
            if (array_key_exists("zip", $data) && !empty($data['zip'])) {
                $this->WhereClause[] = array("e.zip", "LIKE", "%" . $data['zip'] . "%");
            }
            if (array_key_exists("selectedSalesmen", $data) && is_array($data['selectedSalesmen'])) {
                $selectedSalesmen = $data['selectedSalesmen'];
                foreach ($selectedSalesmen as $currId) {
                    $this->WhereClause[] = array("s.idsalesmen", "!=", $currId);
                }
            }
        }
    }

    public function __get($field) {
        $value = "";
        if ($field == "Conn") {
            $value = $this->Conn;
        }
        return $value;
    }

    public function getSalesGroupsWithEmployees() {
        $sql = "
            SELECT 	s.idsalesmen, s.employeeID, s.commisionRate, s.territory, s.classification, s.salesGroup,
                    s.byOrders, s.byTests, s.byBilled, s.byReceived, s.byGroup, s.byPercentage, s.byAmount, s.created, s.createdBy,
                    sg.id, sg.groupName, sg.groupLeader, sg.created, sg.createdBy,
                    el.idemployees AS `leaderEmployeeId`, el.lastName AS `leaderLastName`, el.firstName AS `leaderFirstName`,
                    e.idemployees, e.firstName, e.lastName, e.department, e.position, e.facilityName, e.homePhone, e.mobilePhone, e.address, e.address2, e.city, e.state, e.zip,
                    t.idterritory, t.territoryName, t.description,
                    ed.idemployeeDepartments, ed.name, ed.defaultUserGroup
            FROM " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s  ON sg.id = s.salesGroup
            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TERRITORY . " t ON s.territory = t.idterritory
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEEDEPARTMENTS . " ed ON e.department = ed.idemployeeDepartments
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " sgl ON sg.groupLeader = sgl.idsalesmen
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " el ON sgl.employeeID = el.idemployees
            ORDER BY sg.groupName ASC, e.lastName ASC, e.firstName ASC
        ";

        $data = parent::select($sql, null, array("Conn" => $this->Conn));

        if ($data != null && count($data) > 0) {

            $arySalesGroups = array();

            $currGroup = new SalesGroup($data[0]);

            $prevGroupId = $data[0]['id'];
            foreach ($data as $row) {

                if ($row['id'] != $prevGroupId) { // new sales group
                    $arySalesGroups[] = $currGroup; // add the previous salesgroup to the array
                    $currGroup = new SalesGroup($row); // create a new salesgroup
                    $prevGroupId = $row['id'];
                } else { // sale sales group
                    $currGroup->addSalesman($row); // add the salesman

                    $prevGroupId = $row['id'];
                }
            }

            if ($data[count($data) - 1]['id'] == $prevGroupId) {
                // there is only one sales group, so add it to the array
                $arySalesGroups[] = $currGroup;
            }
            return $arySalesGroups;
        }
        return null;
    }

    public function getSalesmen() {
        $whereClause = "";
        $aryInput = null;
        if (count($this->WhereClause) > 0) {
            $whereClause = "WHERE ";
            $aryInput = array();
            foreach($this->WhereClause as $condition) {
                $whereClause .= $condition[0] . " " . $condition[1] . " ? AND ";
                $aryInput[] = $condition[2];
            }
            $whereClause = substr($whereClause, 0, strlen($whereClause) - 4);
        }

        if (array_key_exists("isAdmin", $this->Data) && $this->Data['isAdmin'] == false) {

            $salesgroupId = $this->getSalesGroupId(array("userId" => $this->Data['userId']));
            $whereClause .= " AND s.salesGroup = ?";
            $aryInput[] = $salesgroupId;
        }

        $sql = "
            SELECT 	s.idsalesmen, s.employeeID, s.commisionRate, s.territory, s.classification, s.salesGroup,
                    s.byOrders, s.byTests, s.byBilled, s.byReceived, s.byGroup, s.byPercentage, s.byAmount, s.created, s.createdBy,
                    sg.groupName, sg.groupLeader, sg.created, sg.createdBy,
                    el.lastName AS `leaderLastName`, el.firstName AS `leaderFirstName`,
                    e.idemployees, e.firstName, e.lastName, e.department, e.position, e.facilityName, e.homePhone, e.mobilePhone, e.address, e.address2, e.city, e.state, e.zip,
                    t.idterritory, t.territoryName, t.description,
                    ed.idemployeeDepartments, ed.name, ed.defaultUserGroup
            FROM " . self::DB_CSS . "." . self::TBL_SALESMEN . " s
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON s.idsalesmen = sgl.salesmanId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id
            
            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TERRITORY . " t ON s.territory = t.idterritory
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEEDEPARTMENTS . " ed ON e.department = ed.idemployeeDepartments
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s2 ON sg.groupLeader = s2.idsalesmen
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " el ON s2.employeeID = el.idemployees
            $whereClause
            ORDER BY sg.groupName ASC, e.lastName ASC, e.firstName ASC";

        //echo "<pre>$sql</pre><pre>"; print_r($aryInput); echo "</pre>";
        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));
        if ($data != null && count($data) > 0) {
            $sCol = new SalesmenCollection($data);
            $sCol->setCollection();

            return $sCol->getCollection();

        }
        return null;
    }

    public function getSalesPerMonth(array $input) {
        $sql = "
            SELECT 	u.idUsers AS `UserId`, u.email AS `UserEmail`,
                    CONCAT(e1.firstName, ' ', e1.lastName) AS `EmployeeName`,
                    sg.groupName AS `GroupName`,
                    s2.idsalesmen AS `GroupLeaderId`,
                    CONCAT(e2.firstName, ' ', e2.lastName) AS `GroupLeaderName`,
                    e1.position, e1.position, d.deptName, c.clientNo, c.clientName,
                    o.idOrders, o.accession,
                    #DATE_FORMAT(o.orderDate, '%Y') AS `Year`,
                    DATE_FORMAT(o.orderDate, '%m') AS `OrderMonth`,
                    COUNT(DISTINCT o.idOrders) AS `OrderCount`
            FROM " . self::DB_CSS . "." . self::TBL_USERS . " u
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMENLOOKUP . " sl ON u.idUsers = sl.userId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s1 ON sl.salesmenId = s1.idsalesmen
            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e1 ON s1.employeeID = e1.idemployees
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON s1.idsalesmen = c.salesmen
            INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON c.idClients = o.clientId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTS . " d ON e1.department = d.idDepartment
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . "  sg ON s1.salesGroup = sg.id
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s2 ON sg.groupLeader = s2.idsalesmen
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e2 ON s2.employeeID = e2.idemployees
            WHERE   u.typeId = 5
		            AND u.idUsers = ?
                    AND o.orderDate BETWEEN ? AND ?
            GROUP BY OrderMonth
            ORDER BY OrderMonth ASC
        ";


        $data = parent::select($sql, $input, array("Conn" => $this->Conn));

        return $data;
    }

    public function totalSalesPerClient(array $data) {
        $sql = "
            SELECT 	u.idUsers AS `UserId`, u.email AS `UserEmail`,
                    CONCAT(e1.firstName, ' ', e1.lastName) AS `EmployeeName`,
                    e1.position, e1.position, c.clientNo, c.clientName,
                    COUNT(DISTINCT o.idOrders) AS `OrderCount`
            FROM " . self::DB_CSS . "." . self::TBL_USERS . " u
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMENLOOKUP . " sl ON u.idUsers = sl.userId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s1 ON sl.salesmenId = s1.idsalesmen
            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e1 ON s1.employeeID = e1.idemployees
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON s1.idsalesmen = c.salesmen
            INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON c.idClients = o.clientId
            WHERE 	u.typeId = 5
                    AND u.idUsers = ? ";

        $input = array($data['UserId']);
        if (array_key_exists("OrderDateFrom", $data) && array_key_exists("OrderDateTo", $data)) {
            $sql .= "AND o.orderDate BETWEEN ? AND ? ";
            $input[] = $data['OrderDateFrom'];
            $input[] = $data['OrderDateTo'];
        } else if (array_key_exists("OrderDateFrom", $data)) {
            $sql .= "AND o.orderDate >= ? ";
            $input[] = $data['OrderDateFrom'];
        } else if (array_key_exists("OrderDateTo", $data)) {
            $sql .= "AND o.orderDate <= ? ";
            $input[] = $data['OrderDateTo'];
        }

        $sql .= "GROUP BY clientName ORDER BY OrderCount DESC";

        $data = parent::select($sql, $input, array("Conn" => $this->Conn));

        return $data;
    }

    public function getSalesUngrouped(array $data) {
        $sql = "
            SELECT 	u.idUsers AS `UserId`, u.email AS `UserEmail`,
                CONCAT(e1.firstName, ' ', e1.lastName) AS `EmployeeName`,
                c.clientNo, c.clientName,
                o.idOrders, o.accession,
                o.orderDate AS `OrderDate`
            FROM " . self::DB_CSS . "." . self::TBL_USERS . " u
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMENLOOKUP . " sl ON u.idUsers = sl.userId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s1 ON sl.salesmenId = s1.idsalesmen
            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e1 ON s1.employeeID = e1.idemployees
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON s1.idsalesmen = c.salesmen
            INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON c.idClients = o.clientId
            WHERE   u.typeId = 5
                    AND u.idUsers = ?
        ";

        $input = array($data['UserId']);
        if (array_key_exists("OrderDateFrom", $data) && array_key_exists("OrderDateTo", $data)) {
            $sql .= "AND o.orderDate BETWEEN ? AND ? ";
            $input[] = $data['OrderDateFrom'];
            $input[] = $data['OrderDateTo'];
        } else if (array_key_exists("OrderDateFrom", $data)) {
            $sql .= "AND o.orderDate >= ? ";
            $input[] = $data['OrderDateFrom'];
        } else if (array_key_exists("OrderDateTo", $data)) {
            $sql .= "AND o.orderDate <= ? ";
            $input[] = $data['OrderDateTo'];
        }

        $sql .= "ORDER BY o.orderDate ASC";

        $data = parent::select($sql, $input, array("Conn" => $this->Conn));

        return $data;
    }

    public function getSalesGoalTypes(array $settings = null) {
        $sql = "
            SELECT st.idTypes, st.typeName, st.typeDescription, st.isActive
            FROM " . self::DB_CSS . "." . self::TBL_SALESGOALTYPES . " st ";

        if ($settings != null && array_key_exists("ActiveOnly", $settings) && $settings['ActiveOnly'] == true) {
            $sql .= "WHERE st.isActive = true";
        }

        $data = parent::select($sql, null, array("Conn" => $this->Conn));

        if (count($data) > 0) {
            $aryTypes = array();
            foreach ($data as $row) {
                $aryTypes[$row['idTypes']] = new SalesGoalType($row);
            }
            return $aryTypes;
        }

        return null;
    }

    public function getSalesGoalIntervals(array $settings = null) {
        $sql = "
            SELECT si.idIntervals, si.intervalName, si.intervalDescription, si.isActive
            FROM " . self::DB_CSS . "." . self::TBL_SALESGOALINTERVALS . " si ";

        if ($settings != null && array_key_exists("ActiveOnly", $settings) && $settings['ActiveOnly'] == true) {
            $sql .= "WHERE si.isActive = true";
        }

        $data = parent::select($sql, null, array("Conn" => $this->Conn));

        if (count($data) > 0) {
            $aryIntervals = array();
            foreach ($data as $row) {
                $aryIntervals[$row['idIntervals']] = new SalesGoalInterval($row);
            }
            return $aryIntervals;
        }

        return null;
    }

    public function getSalesGoals(array $input, array $settings = null) {
        $sql = "
            SELECT  sg.idGoals, sg.userId, sg.typeId, sg.intervalId, sg.salesgroupId, sg.goal, sg.isActive, sg.isDefault, sg.dateCreated, sg.dateUpdated,
                    si.idIntervals, si.intervalName, si.intervalDescription, si.isActive,
                    st.idTypes, st.typeName, st.typeDescription, st.isActive,
                    sgl.salesmenId AS `idsalesmen`, e.lastName, e.firstName
            FROM        " . self::DB_CSS . "." . self::TBL_SALESGOALS . " sg
            INNER JOIN  " . self::DB_CSS . "." . self::TBL_SALESGOALINTERVALS . " si ON sg.intervalId = si.idIntervals
            INNER JOIN  " . self::DB_CSS . "." . self::TBL_SALESGOALTYPES . " st ON sg.typeId = st.idTypes
            LEFT JOIN   " . self::DB_CSS . "." . self::TBL_SALESGOALLOOKUP . " sgl ON sg.idGoals = sgl.goalId
            LEFT JOIN   " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON sgl.salesmenId = s.idsalesmen
            LEFT JOIN   " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
            LEFT JOIN 	" . self::DB_CSS . "." . self::TBL_SALESGROUP . " sgr ON s.salesGroup = sgr.id
            WHERE sg.isActive = true AND ";

        $aryInput = array();
        foreach ($input as $key => $value) {
            if ($key == "idsalesmen") {
                $sql .= "(sgr.groupLeader = ? OR sgl.salesmenId IS NULL OR sgl.salesmenId = ?) AND ";
                $aryInput[] = $value;
                $aryInput[] = $value;
            } else {
                $sql .= $key . " = ? AND ";
                $aryInput[] = $value;
            }

        }
        $sql = substr($sql, 0, strlen($sql) - 4);

        $sql .= " ORDER BY si.idIntervals DESC, sg.idGoals ASC ";

        //error_log($sql);
        //error_log(implode(",", $aryInput));

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $aryGoals = array();
        $numGoals = count($data);
        if ($numGoals > 0) {
            $aryGoals = array();
            $prevGoalId = $data[0]['idGoals'];
            $currGoal = new SalesGoal($data[0]);
            $i = 0;

            foreach ($data as $row) {
                $i++;
                if ($row['idGoals'] != $prevGoalId || $i == 1) {
                    $aryGoals[$prevGoalId] = $currGoal;
                    $currGoal = new SalesGoal($row);
                    if ($i == $numGoals) { // last row
                        $aryGoals[$row['idGoals']] = $currGoal;
                    }
                } else {
                    $currGoal->addSalesman(new Salesman($row));
                    if ($i == $numGoals) { // last row
                        $aryGoals[$row['idGoals']] = $currGoal;
                    }
                }
                $prevGoalId = $row['idGoals'];

            }
            return $aryGoals;
        }
        return null;
    }

    public function getSalesGoal(array $data, array $settings = null) {

    }

    public function insertSalesGoal(array $data, array $settings = null) {
        $salesgroupId = null;
        if ($data['isAdmin'] == 0) {
            $salesgroupId = $this->getSalesGroupId(array("userId" => $data['userId']));
        }

        $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_SALESGOALS . " (typeId, intervalId, userId, salesgroupId, goal, isActive, isDefault) VALUES (?,?,?,?,?,?, ?);";
        $input = array($data['goalType'], $data['goalInterval'], $data['userId'], $salesgroupId, $data['goal'], 1);
        if (array_key_exists("isDefault", $data) && $data['isDefault'] == 1) {
            $input[] = true;
            $sql2 = "UPDATE " . self::DB_CSS . "." . self::TBL_SALESGOALS . "
                SET isDefault = ?
                WHERE userId = ?";
            parent::manipulate($sql2, array(false, $data['userId']), array("Conn" => $this->Conn));
        } else {
            $input[] = false;
        }

        $idGoals = parent::manipulate($sql, $input, array("Conn" => $this->Conn, "LastInsertId" => true));

        /*if (array_key_exists("salesman", $data) && $data['salesman'] != 0) {
            $sql3 = "INSERT INTO " . self::DB_CSS . "." . self::TBL_SALESGOALLOOKUP . " (goalId, salesmenId) VALUES (?, ?);";
            $aryInput3 = array($idGoals, $data['salesman']);
            parent::manipulate($sql3, $aryInput3, array("Conn" => $this->Conn));
        }*/
        if (array_key_exists("selectedSalesmen", $data) && is_array($data['selectedSalesmen'])) {
            $sql3 = "INSERT INTO " . self::DB_CSS . "." . self::TBL_SALESGOALLOOKUP . " (goalId, salesmenId) VALUES ";
            $aryInput3 = array();
            $selectedSalesmen = $data['selectedSalesmen'];
            foreach($selectedSalesmen as $currIdSalesmen) {
                $sql3 .= "(?, ?), ";
                $aryInput3[] = $idGoals;
                $aryInput3[] = $currIdSalesmen;
            }
            $sql3 = substr($sql3, 0, strlen($sql3) - 2);
            parent::manipulate($sql3, $aryInput3, array("Conn" => $this->Conn));
        }

        $logData = array(
            "userId" => $data['userId'],
            "typeId" => 10,
            "ip" => $data['ip'],
            "action" => 2,
            "goal" => $data['goal'],
            "goalTypeId" => $data['goalType'],
            "intervalId" => $data['goalInterval']
        );
        ResultLogDAO::addSalesLogEntry($logData, array("Conn" => $this->Conn));

        return true;
    }

    public function updateSalesGoal(array $data, array $settings = null) {
        //echo "<pre>"; print_r($data); echo "</pre>";
        $sql = "
            UPDATE " . self::DB_CSS . "." . self::TBL_SALESGOALS . "
            SET typeId = ?, intervalId = ?, goal = ?, isDefault = ?, dateUpdated = ?
            WHERE idGoals = ? ";
        $input = array($data['goalType'], $data['goalInterval'], $data['goal']);

        if (array_key_exists("isDefault", $data) && $data['isDefault'] == 1) {
            $input[] = 1;
            $sql2 = "
                UPDATE " . self::DB_CSS . "." . self::TBL_SALESGOALS . "
                SET isDefault = ? ";
            parent::manipulate($sql2, array(0), array("Conn" => $this->Conn));
        } else {
            $input[] = 0;
        }
        $input[] = date("Y-m-d h:i:s");
        $input[] = $data['idGoals'];

        $this->updateGoalSalesmen($data);

        parent::manipulate($sql, $input, array("Conn" => $this->Conn));

        $logData = array(
            "userId" => $data['userId'],
            "typeId" => 10,
            "ip" => $data['ip'],
            "action" => 3,
            "goal" => $data['goal'],
            "goalTypeId" => $data['goalType'],
            "intervalId" => $data['goalInterval']
        );
        ResultLogDAO::addSalesLogEntry($logData, array("Conn" => $this->Conn));

        return true;
    }

    private function updateGoalSalesmen(array $data) {
        // delete current salesmen from goal
        $sql = "
                DELETE FROM " . self::DB_CSS . "." . self::TBL_SALESGOALLOOKUP . "
                WHERE goalId = ?
            ";
        parent::manipulate($sql, array($data['idGoals']));

        // add any new salesmen that may be selected
        if (array_key_exists("selectedSalesmen", $data) && is_array($data['selectedSalesmen']) && count($data['selectedSalesmen']) > 0) {

            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_SALESGOALLOOKUP . " (goalId, salesmenId) VALUES ";
            $selectedSalesmen = $data['selectedSalesmen'];
            $aryInput = array();
            foreach ($selectedSalesmen as $currSalesmenId) {
                $sql .= "(?, ?), ";
                $aryInput[] = $data['idGoals'];
                $aryInput[] = $currSalesmenId;
            }
            $sql = substr($sql, 0, strlen($sql) - 2);
            parent::manipulate($sql, $aryInput);
        }
    }

    public function deleteSalesGoal(array $data, array $settings = null) {
        $sql = "
            SELECT goal, typeId, intervalId
            FROM " . self::DB_CSS . "." . self::TBL_SALESGOALS . " sg
            WHERE idGoals = ?";
        $goalData = parent::select($sql, array($data['goalId']));
        if (count($goalData) > 0) {
            $sql = "UPDATE " . self::DB_CSS . "." . self::TBL_SALESGOALS . "
            SET isActive = false
            WHERE idGoals = ?
            ";
            parent::manipulate($sql, array($data['goalId']));

            $logData = array(
                "userId" => $data['userId'],
                "typeId" => 10,
                "ip" => $data['ip'],
                "action" => 4,
                "goalId" => $data['goalId'],
                "goal" => $goalData[0]['goal'],
                "goalTypeId" => $goalData[0]['typeId'],
                "intervalId" => $goalData[0]['intervalId']
            );
            ResultLogDAO::addSalesLogEntry($logData, array("Conn" => $this->Conn));
        }


//        $sql = "
//            SELECT sg.idGoals, sg.userId, sg.goal, sg.typeId, sg.intervalId
//            FROM " . self::DB_CSS . "." . self::TBL_SALESGOALS . " sg
//            WHERE sg.idGoals = ?";
//        $goalData = parent::select($sql, array($data['goalId']), array("Conn" => $this->Conn));
//
//        if (count($goalData) > 0) {
//            $sql = "
//                UPDATE " . self::DB_CSS . "." . self::TBL_SALESGOALS . "
//                SET isActive = ?
//                WHERE idGoals = ? ";
//            $aryInput = array(0, $data['goalId']);
//            parent::manipulate($sql, $aryInput, array("Conn" => $this->Conn));
//
//            $logData = array(
//                "userId" => $data['userId'],
//                "typeId" => 10,
//                "ip" => $data['ip'],
//                "action" => 4,
//                "goal" => $goalData[0]['goal'],
//                "goalTypeId" => $goalData[0]['typeId'],
//                "intervalId" => $goalData[0]['intervalId']
//            );
//            ResultLogDAO::addSalesLogEntry($logData, array("Conn" => $this->Conn));
//            return true;
//        }
//        return false;
    }

    public function getSalesGroupId(array $input) {
        $id = null;

        $sql = "
            SELECT s.salesGroup AS `id`
            FROM " . self::DB_CSS . "." . self::TBL_SALESMEN . " s

        ";

        if (count($input) == 1 && array_key_exists("userId", $input)) {
            $sql .= "
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMENLOOKUP . " sl ON s.idsalesmen = sl.salesmenId
                WHERE sl.userId = ? ";

        }

        $data = parent::select($sql, $input, array("Conn" => $this->Conn));

        if ($data != null) {
            $id = $data[0]['id'];
        }

        return $id;
    }

    /*    public static function getSalesSettings(array $settings = null) {
            $sql = "
                SELECT s.idSalesSettings, s.settingName, s.settingDescription, s.isActive
                FROM " . self::TBL_SALESSETTINGS . " s
                ORDER BY s.settingName ASC";

            $data = parent::select($sql, null, $settings);
            $arySalesSettings = array();
            foreach ($data as $row) {
                $arySalesSettings[] = new SalesSetting($row);
            }
            return $arySalesSettings;
        }*/

    public function getSalesPerson($userId) {
//        $sql = "
//            SELECT	u.idUsers, u.email, u.password, u.userSalt,
//                    s.idsalesmen, s.salesGroup,
//                    e.idemployees AS `employeeId`, e.lastName, e.firstName,
//                    sg.id as `groupId`, sg.groupName, sg.groupLeader AS `groupLeaderId`
//
//            FROM " . self::DB_CSS . "." . self::TBL_USERS . " u
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMENLOOKUP . " sl ON u.idUsers = sl.userId
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON sl.salesmenId = s.idsalesmen
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON s.salesGroup = sg.id
//            WHERE u.idUsers = ?
//        ";
        /*$sql = "
            SELECT	u.idUsers, u.email, u.password, u.userSalt,
                    s.idsalesmen, s.salesGroup,
                    e.idemployees AS `employeeId`, e.lastName, e.firstName,
                    sg.id as `groupId`, sg.groupName, sg.groupLeader AS `groupLeaderId`,
                    CASE WHEN was.adminSettingId IS NOT NULL THEN true ELSE false END AS `isManager`
            FROM " . self::DB_CSS . "." . self::TBL_USERS . " u
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESMENLOOKUP . " sl ON u.idUsers = sl.userId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON sl.salesmenId = s.idsalesmen
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON s.salesGroup = sg.id
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_ADMINSETTINGS . " wa ON wa.idAdminSettings = 13
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_ADMINSETTINGSLOOKUP . " was ON wa.idAdminSettings = was.adminSettingId AND u.idUsers = was.userId
            WHERE u.idUsers = ?
        ";*/

        $sql = "
            SELECT	u.idUsers, u.email, u.password, u.userSalt,
                    s.idsalesmen,
                    e.idemployees AS `employeeId`, e.lastName, e.firstName,
                    CASE WHEN FIND_IN_SET(13, GROUP_CONCAT(was.adminSettingId)) != 0 THEN true ELSE false END AS `isManager`,
                    CASE WHEN FIND_IN_SET(16, GROUP_CONCAT(was.adminSettingId)) != 0 THEN true ELSE false END AS `isSalesAdmin`,
                    CASE WHEN sg2.id IS NULL THEN sg.id ELSE sg2.id END AS `groupId`,
                    CASE WHEN sg2.groupName IS NULL THEN sg.groupName ELSE sg2.groupName END AS `groupName`,
                    CASE WHEN sg2.groupLeader IS NULL THEN sg.groupLeader ELSE sg2.groupLeader END AS `groupLeader`,
                    s.commisionRate
            FROM " . self::DB_CSS . "." . self::TBL_USERS . " u
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESMENLOOKUP . " sl ON u.idUsers = sl.userId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON sl.salesmenId = s.idsalesmen
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON s.salesGroup = sg.id
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_ADMINSETTINGSLOOKUP . " was ON u.idUsers = was.userId AND (was.adminSettingId = 13 OR was.adminSettingId = 16)
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON s.idsalesmen = sgl.salesmanId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg2 ON sgl.salesGroupId = sg2.id
            WHERE u.idUsers = ?
            GROUP BY u.idUsers, sg2.id ";

        /*$aryInput = array($userId);
        error_log($sql);
        error_log(implode($aryInput, ","));*/

        $data = parent::select($sql, array($userId), array("Conn" => $this->Conn));

        $aryReturn = array(
            "idUsers" => $data[0]['idUsers'],
            "email" => $data[0]['email'],
            "password" => $data[0]['password'],
            "userSalt" => $data[0]['userSalt'],
            "idsalesmen" => $data[0]['idsalesmen'],
            "employeeId" => $data[0]['employeeId'],
            "lastName" => $data[0]['lastName'],
            "firstName" => $data[0]['firstName'],
            "isManager" => $data[0]['isManager'],
            "isSalesAdmin" => $data[0]['isSalesAdmin'],
            "salesGroups" => array(
                array(
                    "groupId" => $data[0]['groupId'],
                    "groupName" => $data[0]['groupName'],
                    "groupLeader" => $data[0]['groupLeader']
                )
            ),
            "subSalesGroups" => array(),
            "commissionRate" => $data[0]['commisionRate']
        );

        if (count($data) > 1) {
            for ($i = 1; $i < count($data); $i++) {
                $row = $data[$i];
                $groupId = $row['groupId'];
                $groupName = $row['groupName'];
                $groupLeader = $row['groupLeader'];

                $aryReturn['salesGroups'][] = array(
                    "groupId" => $groupId,
                    "groupName" => $groupName,
                    "groupLeader" => $groupLeader
                );
            }
        }

        if ($data[0]['idsalesmen'] == $data[0]['groupLeader']) {
            // this is a group leader, so check if they have any members in a sub-sales-group
            $sql = "SELECT sg2.id, sg2.groupName, sg2.groupLeader
                FROM " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON sg.groupLeader = s.idsalesmen
                INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
                
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON sg.id = sgl.salesGroupId
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s2 ON sgl.salesmanId = s2.idsalesmen
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e2 ON s2.employeeID = e2.idemployees
                
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg2 ON sgl.salesmanId = sg2.groupLeader AND sg2.id != sg.id
                
                WHERE s.idsalesmen = ?
                    AND sg2.id IS NOT NULL
                ORDER BY sg.id, sgl.salesmanId";
            $data = parent::select($sql, array($data[0]['idsalesmen']), array("Conn" => $this->Conn));
            if (count($data) > 0) {
                foreach ($data as $row) {
                    $subGroupId = $row['id'];
                    $subGroupName = $row['groupName'];
                    $subGroupLeader = $row['groupLeader'];
                    $aryReturn['subSalesGroups'][] = array(
                        "groupId" => $subGroupId,
                        "groupName" => $subGroupName,
                        "groupLeader" => $subGroupLeader
                    );
                }
            }
        }



        return $aryReturn;
    }

    public function getSalesSummaryData($dateFrom, $dateTo, $groupId) {
        $sql = "
            SELECT 	1 AS `ReportGroup`,
                o.idOrders, s.idsalesmen, sg.id AS `groupId`,
                e.lastName AS `salesmanLastName`, e.firstName AS `salesmanFirstName`,
                t.territoryName, sg.groupName,
                c.idClients, c.clientNo, c.clientName,
                gle.lastName AS `leaderLastName`, gle.firstName AS `leaderFirstName`,
                CASE WHEN FIND_IN_SET('O', GROUP_CONCAT(st.code)) <> 0 OR FIND_IN_SET('B', GROUP_CONCAT(st.code)) <> 0 THEN 1 ELSE 0 END AS `IsOral`,
                DATE_FORMAT(o.orderDate, '%m') AS `orderMonth`,
                DATE_FORMAT(o.orderDate, '%d') AS `orderDay`,
                DATE_FORMAT(o.orderDate, '%w') AS `orderWeekDay`,
                DATE_FORMAT(o.orderDate, '%Y-%m-%d') AS `ShortOrderDate`,
                o.orderDate AS `orderDate`
            FROM " . self::DB_CSS . "." . self::TBL_SALESMEN . " s
            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON s.idsalesmen = c.salesmen
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON s.salesGroup = sg.id
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON c.idClients = o.clientId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " te ON r.testId = te.idtests
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SPECIMENTYPES . " st ON te.specimenType = st.idspecimenTypes
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TERRITORY . " t ON s.territory = t.idterritory
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " gl ON sg.groupLeader = gl.idsalesmen
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " gle ON gl.employeeID = gle.idemployees
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_PREFERENCES . " p ON p.`key` = 'POCTest' AND (r.testId = p.value OR r.panelId = p.value)
            WHERE   o.orderDate BETWEEN ? AND ?
                AND sg.id = ?
                AND p.`key` IS NULL
            GROUP BY s.idsalesmen, c.idClients, o.idOrders
            ORDER BY t.territoryName ASC, t.idterritory ASC, e.lastName ASC, e.idemployees ASC, c.clientNo ASC, orderDay ASC
        ";

        $aryInput = array($dateFrom, $dateTo, $groupId);
        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $arySalesGroups = array();
        $arySalesmen = array();
        $aryOrderDates = array();
        $aryClients = array();

        $aryData = array();
        /*
        array(
            idSalesmen => array(
                [0] => array(salesmanFirstName, salesmanLastName, territoryName), // Sales person info
                [1] => array(
            )
        )
        */
        $arySales = array();
        if (count($data) > 0) {
            foreach ($data as $row) {
                // make array of distinct order dates
                $orderDate = new DateTime($row['ShortOrderDate']);
                $strOrderDate = $orderDate->format('m/d/Y');
                $currTimeStamp = $orderDate->getTimestamp();
                if (!array_key_exists($currTimeStamp, $aryOrderDates)) {
                    $aryOrderDates[$currTimeStamp] = $strOrderDate;
                }
                // make array of distinct salesmen
                $idsalesmen = $row['idsalesmen'];
                $salesmanLastName = $row['salesmanLastName'];
                $salesmanFirstName = $row['salesmanFirstName'];
                $salesmanTerritory = $row['territoryName'];
                $groupName = $row['groupName'];
                if (!array_key_exists($idsalesmen, $arySalesmen)) {
                    $arySalesmen[$idsalesmen] = array($salesmanFirstName, $salesmanLastName, $salesmanTerritory, $groupName);
                }

                // make array of distinct clients
                $idClients = $row['idClients'];
                $clientNo = $row['clientNo'];
                $clientName = $row['clientName'];
                if (!array_key_exists($idClients, $aryClients)) {
                    $aryClients[$idClients] = array($clientNo, $clientName);
                }
                if (!array_key_exists($idsalesmen, $arySales)) {
                    $arySales[$idsalesmen] = array();
                }
                if (!array_key_exists($idClients, $arySales[$idsalesmen])) {
                    $arySales[$idsalesmen][$idClients] = array();
                }
                if (!array_key_exists($currTimeStamp, $arySales[$idsalesmen][$idClients])) {
                    $arySales[$idsalesmen][$idClients][$currTimeStamp] = 1;
                } else {
                    $arySales[$idsalesmen][$idClients][$currTimeStamp] += 1;
                }
            }
        }
        ksort($aryOrderDates);
        return array($aryOrderDates, $arySalesmen, $aryClients, $arySales);
    }

    public static function getTotalBillableUnbillableRejected(array $input) {
        $sql = "
            SELECT	COUNT(DISTINCT CASE WHEN rej.orderId IS NOT NULL THEN o.idOrders ELSE NULL END) AS `TotalRejected`,
                    COUNT(DISTINCT CASE WHEN rej.orderId IS NULL AND o.billable = 1 THEN o.idOrders ELSE NULL END) AS `TotalBillable`,
                    COUNT(DISTINCT CASE WHEN rej.orderId IS NULL AND o.billable = 0 THEN o.idOrders ELSE NULL END) AS `TotalUnbillable`
            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            LEFT JOIN (
                SELECT DISTINCT r.orderId
                FROM " . self::DB_CSS . "." . self::TBL_RESULTS . " r
                INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests AND t.number = 490
            ) rej ON o.idOrders = rej.orderId
            WHERE o.orderDate BETWEEN ? AND ?";

        $dateFrom = $input['dateFrom'];
        $dateTo = $input['dateTo'];

        $aryInput = array($dateFrom, $dateTo);

        return parent::select($sql, $aryInput);
    }

    /* https://stackoverflow.com/a/52641198
    Converts invalid characters to UTF-8 so array can be used in json_encode
    */
    private function utf8ize( $mixed ) {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
        }
        return $mixed;
    }

    private function getDateSearchField(array $input) {
        $dateField = "o.specimenDate";
        if (array_key_exists("dateField", $input)) {
            $n = $input['dateField'];
            switch ($n) {
                case "DOI":
                    $dateField = "o.DOI";
                    break;
                case "orderDate":
                    $dateField = "o.orderDate";
                    break;
                case "paymentDate":
                    $dateField = "do.lastPaymentDate";
                    break;
            }
        }
        return $dateField;
    }

    private function getWhere(array $input) {

        $dateField = $this->getDateSearchField($input);

        $dateFrom = $input['dateFrom'];
        $dateTo = $input['dateTo'];

        $where = "WHERE $dateField BETWEEN ? AND ? 
            AND o.active = true ";
        $aryInput = array($dateFrom, $dateTo);

        /* if (array_key_exists("groupId", $input) && isset($input['groupId']) && $input['groupId'] != "null"
             && array_key_exists("idsalesmen", $input) && isset($input['idsalesmen']) && $input['idsalesmen'] != "null") {

             // (sg.id IN (2,5) AND (sg.groupLeader = 4 OR s.idsalesmen = 4))

             $aryGroupIds = explode(",", $input['groupId']);

             $where .= "AND (sg.id IN(";
             foreach ($aryGroupIds as $groupId) {
                 $where .= "?,";
                 $aryInput[] = $groupId;
             }
             $where = substr($where, 0, strlen($where) - 1) . ") AND (sg.groupLeader = ? OR s.idsalesmen = ?))";
             $aryInput[] = $input['idsalesmen'];
             $aryInput[] = $input['idsalesmen'];

         } */

        if (array_key_exists("groupId", $input) && isset($input['groupId']) && $input['groupId'] != "null" && !empty($input['groupId'])) {
            $where .= "AND sg.id IN (";

            $aryGroupIds = explode(",", $input['groupId']);
            foreach ($aryGroupIds as $groupId) {
                $where .= "?,";
                $aryInput[] = $groupId;
            }

            if (!empty($input['subSalesGroupIds'])) {
                $arySubGroupIds = explode(",", $input['subSalesGroupIds']);
                foreach ($arySubGroupIds as $groupId) {
                    $where .= "?,";
                    $aryInput[] = $groupId;
                }
            }

            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }

        if (array_key_exists("idsalesmen", $input) && isset($input['idsalesmen']) && $input['idsalesmen'] != "null" && !empty($input['idsalesmen'])) {
            $where .= "AND (sg.groupLeader = ? OR s.idsalesmen = ?";
            $aryInput[] = $input['idsalesmen'];
            $aryInput[] = $input['idsalesmen'];

            if (!empty($input['subGroupLeaderIds'])) {
                $arySubGroupLeaderIds = explode(",", $input['subGroupLeaderIds']);
                foreach ($arySubGroupLeaderIds as $groupLeaderId) {
                    $where .= " OR sg.groupLeader = ?";
                    $aryInput[] = $groupLeaderId;
                }
            }

            $where .= ") ";
        }

        if (array_key_exists("selectedGroupMembers", $input) && !empty($input['selectedGroupMembers'])) {
            $arySelectedGroupMembers = explode(",", $input['selectedGroupMembers']);
            $where .= "AND s.idsalesmen IN (";
            foreach ($arySelectedGroupMembers as $salesmanId) {
                $where .= "?,";
                $aryInput[] = $salesmanId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }

        if (array_key_exists("selectedSalesGroups", $input) && !empty($input['selectedSalesGroups'])) {
            $arySelectedSalesGroups = explode(",", $input['selectedSalesGroups']);
            $where .= "AND sg.id IN (";
            foreach ($arySelectedSalesGroups as $groupId) {
                $where .= "?,";
                $aryInput[] = $groupId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }

        return array(
            "where" => $where,
            "input" => $aryInput
        );
    }

    public function getSalesGroupMembers(array $input) {
        $where = "";
        $aryInput = array();

        if (array_key_exists("groupId", $input) && isset($input['groupId']) && $input['groupId'] != "null" && !empty($input['groupId'])) {
            $where = "WHERE sg.id IN (";

            $aryGroupIds = explode(",", $input['groupId']);
            foreach ($aryGroupIds as $groupId) {
                $where .= "?,";
                $aryInput[] = $groupId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }

        if (array_key_exists("idsalesmen", $input) && isset($input['idsalesmen']) && $input['idsalesmen'] != "null" && !empty($input['idsalesmen'])) {
            if (!empty($where)) {
                $where .= "AND (sg.groupLeader = ? OR s.idsalesmen = ?) ";
            } else {
                $where .= "WHERE (sg.groupLeader = ? OR s.idsalesmen = ?) ";
            }
            $aryInput[] = $input['idsalesmen'];
            $aryInput[] = $input['idsalesmen'];
        }
        $sql = "
            SELECT s.idsalesmen, e.idemployees, e.lastName, e.firstName,
            sg.id, 
            CASE WHEN sg.groupName IS NOT NULL THEN sg.groupName ELSE 'Unassigned' END AS `groupName`, 
            sg.groupLeader,
            
            CASE WHEN s2.idsalesmen IS NOT NULL THEN sg2.id ELSE NULL END AS `subGroupId`, 
            CASE WHEN s2.idsalesmen IS NOT NULL THEN sg2.groupName ELSE NULL END AS `subGroupName`, 
            CASE WHEN s2.idsalesmen IS NOT NULL THEN sg2.groupLeader ELSE NULL END AS `subGroupLeaderId`,
            s2.idsalesmen AS `subSalesmanId`, e2.idemployees AS `subEmployeeId`, e2.lastName AS `subLastName`, e2.firstName AS `subFirstName`
            
            FROM " . self::DB_CSS . "." . self::TBL_SALESMEN . " s
            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON s.idsalesmen = sgl.salesmanId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id
            
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg2 ON s.idsalesmen = sg2.groupLeader AND sg.groupLeader != sg2.groupLeader
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl2 ON sg2.id = sgl2.salesGroupId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s2 ON sgl2.salesmanId = s2.idsalesmen
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e2 ON s2.employeeID = e2.idemployees

            $where
            ORDER BY subGroupName ASC, groupName ASC, e.lastName ASC, e.firstName ASC";

//        error_log("/*getSalesGroupMembers - Sales Person select input in sales-page-content*/ " . $sql);
//        error_log(implode($aryInput, ","));

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $arySalesmanIds = array();
        if (count($data) > 0) {
            $arySalesmen = array();
            foreach ($data as $row) {
                if (!in_array($row['idsalesmen'], $arySalesmanIds)) {
                    $arySalesmen[] = new Salesman($row);
                    $arySalesmanIds[] = $row['idsalesmen'];
                }

                if ($row['subSalesmanId'] != null && $row['groupLeader'] == $input['idsalesmen']) {
                    $arySalesmen[] = new Salesman(array(
                        "idsalesmen" => $row['subSalesmanId'],
                        "idemployees" => $row['subEmployeeId'],
                        "lastName" => $row['subLastName'],
                        "firstName" => $row['subFirstName'],
                        "id" => $row['subGroupId'],
                        "groupName" => $row['subGroupName'],
                        "groupLeader" => $row['subGroupLeaderId']
                    ));
                }


            }

            //echo "<pre>"; print_r($arySalesmen); echo "</pre>";

            return $arySalesmen;
        }
        return null;
    }

    public function getTopAccounts(array $input) {
        $aryWhere = $this->getWhere($input);
        $where = $aryWhere['where'];
        $aryInput = $aryWhere['input'];

        $sql = "
            SELECT 	d.iddoctors, d.number AS `doctorNumber`, 
                    CASE WHEN d.iddoctors IS NOT NULL THEN d.lastName ELSE 'Unassigned' END AS `lastName`, 
                    d.firstName,
                    l.idLocation, l.locationNo, l.locationName,
                    COUNT(DISTINCT o.idOrders) AS `OrderCount`,
                    SUM(dcc.billAmount) AS `TotalBilled`,
                    SUM(dcc.paid) AS `TotalPaid`,
                    SUM(dcc.billAmount) - SUM(dcc.paid) AS `TotalBalance`
            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors
            
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON c.salesmen = s.idsalesmen
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON s.idsalesmen = sgl.salesmanId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id
            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
            INNER JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " l ON o.locationId = l.idLocation
            
            LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILORDERS . " do ON o.idOrders = do.orderId AND do.active = true
            LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILCPTCODES . " dcc ON do.iddetailOrders = dcc.detailOrderId
            $where
            GROUP BY d.iddoctors, l.idLocation
            ORDER BY d.iddoctors ASC, l.idLocation ASC ";

        /*$sql = "
            SELECT 	d.iddoctors, d.number AS `doctorNumber`, d.lastName, d.firstName,
                    l.idLocation, l.locationNo, l.locationName,
                    COUNT(o.idOrders) AS `OrderCount`,
                    SUM(o.payment) AS `TotalPaid`
            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            INNER JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON c.salesmen = s.idsalesmen
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON s.salesGroup = sg.id
            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
            INNER JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " l ON o.locationId = l.idLocation
            $where
            GROUP BY d.number, l.idLocation
            ORDER BY d.iddoctors ASC, l.idLocation ASC";*/

        /*error_log($sql);
        error_log(implode($aryInput, ","));*/

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $aryReturn = array();
        if (count($data) > 0) {
            $iddoctors = $data[0]['iddoctors'];
            $doctorNumber = $data[0]['doctorNumber'];
            $firstName = $data[0]['firstName'];
            $lastName = $data[0]['lastName'];
            $doctorName = $firstName . " " . $lastName;
            $idLocation = $data[0]['idLocation'];
            $locationName = $data[0]['locationName'];
            $orderCount = $data[0]['OrderCount'];
            $totalPayment = $data[0]['TotalPaid'];
            $y = 0;
            $aryCurr = array(
                "key" => $doctorName,
                "y" => 0,
                "tooltip" => "<b>$doctorName</b><br/>"
            );

            foreach($data as $row) {
                $iddoctors = $row['iddoctors'];
                $firstName = $row['firstName'];
                $lastName = $row['lastName'];
                $doctorName = $firstName . " " . $lastName;
                $idLocation = $row['idLocation'];
                $locationName = $row['locationName'];
                $orderCount = $row['OrderCount'];
                $totalPayment = $row['TotalPaid'];
                $y = $row['OrderCount'];

                if ($row['doctorNumber'] != $doctorNumber) {
                    $aryReturn[] = $aryCurr;
                    $aryCurr = array(
                        "key" => $doctorName,
                        "y" => $y,
                        "tooltip" => "<b>$doctorName</b><br/>$locationName" . ": $y <br/>"
                    );
                } else {
                    $aryCurr['y'] = $aryCurr['y'] + $y;
                    $aryCurr['tooltip'] = $aryCurr['tooltip'] . $locationName . ": " . $y . "<br/>";
                }

                $doctorNumber = $row['doctorNumber'];

                //$aryReturn[] = array('key' => $doctorName, 'y' => $y);
            }
            $aryReturn[] = $aryCurr;

            // PHP 7+
            /*usort($aryReturn, function($item1, $item2) {
                return $item2['y'] <=> $item1['y'];
            });*/

            // PHP 5.3+ and <7
            // https://stackoverflow.com/a/19454643
            usort($aryReturn, function ($item1, $item2) {
                if ($item1['y'] == $item2['y']) return 0;
                return $item1['y'] > $item2['y'] ? -1 : 1;
            });

            $aryReturn = array_slice($aryReturn, 0, 20); // get first 10 elements of array
        }

        return $aryReturn;
    }

    public function getSalesPerClientBarChartData(array $input) {
        $aryWhere = $this->getWhere($input);
        $where = $aryWhere['where'];

        $aryInput = array($input['dateFrom'], $input['dateTo']);
        $aryInput = array_merge($aryInput, $aryWhere['input']);

        $dateField = $this->getDateSearchField($input);

        $orderBy = "OrderCount";

        $sql = "
            SELECT *
            FROM (
                SELECT	c.idClients, c.clientNo, c.clientName,
                        l.idLocation, l.locationNo, l.locationName,
                        COUNT(DISTINCT o.idOrders) AS `OrderCount`,
                        #CASE WHEN SUM(o.payment) IS NULL THEN 0 ELSE SUM(o.payment) END AS `TotalPayment`,
                        SUM(dcc.paid) AS `TotalPayment`,
                        /*ROUND(SUM(CASE 
                            WHEN cr.idCommissions IS NOT NULL AND crt.typeNumber = 1 THEN o.payment * cr.commissionRate # Percentage by order
                            WHEN cr.idCommissions IS NOT NULL AND crt.typeNumber = 2 THEN cr.commissionRate # Amount by order
                            WHEN cr.idCommissions IS NULL AND s.byOrders = true AND s.byPercentage = true THEN o.payment * s.commisionRate
                            WHEN cr.idCommissions IS NULL AND s.byOrders = true AND s.byAmount = true THEN s.commisionRate
                            ELSE 0
                        END), 2) AS `TotalCommission`*/
                        SUM(CASE
                          WHEN cr.idCommissions IS NOT NULL AND o.payment IS NOT NULL AND o.payment > cr.commissionRate THEN cr.commissionRate
                          ELSE 0
                        END) AS `TotalCommission`
                FROM  " . self::DB_CSS . "." . self::TBL_SALESMEN . " s
                INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
                INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON s.idsalesmen = c.salesmen
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON s.idsalesmen = sgl.salesmanId
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id
                INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON c.idClients = o.clientId
                INNER JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " l ON o.locationId = l.idLocation
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_TERRITORY . " t ON s.territory = t.idterritory
                LEFT JOIN (
                    SELECT r.orderId, t.idtests
                    FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests AND t.number = 490
                    WHERE   $dateField BETWEEN ? AND ?
                    GROUP BY r.orderId
                ) rt ON o.idOrders = rt.orderId
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr ON o.reportType = cr.reportTypeId AND s.idsalesmen = cr.salesmanId
                
                LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILORDERS . " do ON o.idOrders = do.orderId AND do.active = true
                LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILCPTCODES . " dcc ON do.iddetailOrders = dcc.detailOrderId
                
                $where
                GROUP BY c.idClients
                ORDER BY $orderBy DESC
                LIMIT 10
            ) a
            ORDER BY a.clientName ASC";

        /*error_log($sql);
        error_log(implode($aryInput, ","));*/

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $key = "Sales Per Client";
        if ($dateField == "o.DOI" && $input['isManager'] == true) {
            $key = "Payments Per Client";
        }

        $aryReturn = array(array(
            "key" => $key,
            "values" => array()
        ));
        if (count($data) > 0) {
            foreach ($data as $row) {
                $value = $row['OrderCount'];
                if ($dateField == "o.DOI" && $input['isManager'] == true) {
                    $value = $row['TotalPayment'];
                }
                /*else if ($dateField == "o.DOI") {
                    $value = $row['TotalPayment'];
                }*/

                $aryReturn[0]['values'][] = array(
                    "label" => $row['clientName'],
                    "value" => $value,
                    "locationName" => $row['locationName']
                );
            }
        }
        return $aryReturn;
    }

    /*
        [
            {
                key: "Stream0",
                values: [
                    {
                        key: "Stream0",
                        series: 0,
                        size: 2.853664959181823,
                        x: 0,
                        y: 2.853664959181823,
                        y0: 0,
                        y1: 2.853664959181823
                    }
                ]
            }

        ]
    */
    public function getMultiBar(array $input) {
        $aryWhere = $this->getWhere($input);
        $where = $aryWhere['where'];
        $aryInput = array($input['dateFrom'], $input['dateTo']);
        $aryInput = array_merge($aryInput, $aryWhere['input']);
        $dateField = $this->getDateSearchField($input);

        $sql = "
          SELECT 	o.idOrders,
                    d.iddoctors, d.number AS `doctorNumber`, d.lastName, d.firstName,
                    x.idDepartment, x.deptNo, x.deptName,
                    i.idinsurances, i.number AS `insuranceNo`, i.name AS `insuranceName`,
                    l.idLocation, l.locationNo, l.locationName,
                    rt.idreportType, rt.number AS `reportTypeNo`, rt.name AS `reportTypeName`,
                    #SUM(dcc.paid) AS `TotalPayment`,
                    /*ROUND(SUM(CASE 
                        WHEN cr.idCommissions IS NOT NULL AND crt.typeNumber = 1 THEN o.payment * cr.commissionRate # Percentage by order
                        WHEN cr.idCommissions IS NOT NULL AND crt.typeNumber = 2 THEN cr.commissionRate # Amount by order
                        WHEN cr.idCommissions IS NULL AND s.byOrders = true AND s.byPercentage = true THEN o.payment * s.commisionRate
                        WHEN cr.idCommissions IS NULL AND s.byOrders = true AND s.byAmount = true THEN s.commisionRate
                        ELSE 0
                    END), 2) AS `TotalCommission`*/
                    COUNT(DISTINCT o.idOrders) AS `OrderCount`,
                    SUM(CASE
                      WHEN cr.idCommissions IS NOT NULL AND o.payment IS NOT NULL AND o.payment >= cr.commissionRate THEN cr.commissionRate
                      ELSE 0
                    END) AS `TotalCommission`
            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            INNER JOIN (
                SELECT	o.idOrders,    
                    COUNT(DISTINCT r.idResults) AS `OrderCount`,
                    COUNT(DISTINCT CASE WHEN r.printAndTransmitted THEN r.idResults ELSE NULL END) AS `TransmitCount`,
                    d.idDepartment, d.deptNo, d.deptName                    
                FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
                INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests
                INNER JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTS . " d ON t.department = d.idDepartment 
                WHERE $dateField BETWEEN ? AND ?
                    AND r.isInvalidated = false
                GROUP BY o.idOrders		
            ) x ON o.idOrders = x.idOrders
            INNER JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i ON o.insurance = i.idinsurances
            INNER JOIN " . self::DB_CSS . "." . self::TBL_REPORTTYPE . " rt ON o.reportType = rt.idreportType
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON c.salesmen = s.idsalesmen
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON s.idsalesmen = sgl.salesmanId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id
            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
            INNER JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " l ON o.locationId = l.idLocation
	        LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr ON o.reportType = cr.reportTypeId AND s.idsalesmen = cr.salesmanId
	        
	        #INNER JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILORDERS . " do ON o.idOrders = do.orderId AND do.active = true
            #LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILCPTCODES . " dcc ON do.iddetailOrders = dcc.detailOrderId	        
            $where
            GROUP BY x.idDepartment, i.idinsurances, l.idLocation
            ORDER BY x.idDepartment ASC, i.idinsurances ASC, l. idLocation ASC";

        /*error_log($sql);
        error_log(implode($aryInput, ","));*/

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $aryTopInsurances = array();
        foreach ($data as $row) {
            $insuranceNo = $row['insuranceNo'];
            $orderCount = $row['OrderCount'];
            //$totalPayment = $row['TotalPayment'];
            $totalCommission = $row['TotalCommission'];

            if (array_key_exists($insuranceNo, $aryTopInsurances)) {
                /*if ($orderBy == "TotalPayment" && $input['isManager'] == true) {
                    $aryTopInsurances[$insuranceNo] = $aryTopInsurances[$insuranceNo] + $totalPayment;
                } else{
                    $aryTopInsurances[$insuranceNo] = $aryTopInsurances[$insuranceNo] + $orderCount;
                }*/
                $aryTopInsurances[$insuranceNo] = $aryTopInsurances[$insuranceNo] + $orderCount;
            } else {
                /*if ($orderBy == "TotalPayment" && $input['isManager'] == true) {
                    $aryTopInsurances[$insuranceNo] = $totalPayment;
                } else{
                    $aryTopInsurances[$insuranceNo] = $orderCount;
                }*/
                $aryTopInsurances[$insuranceNo] = $orderCount;
            }

        }
        arsort($aryTopInsurances);
        $aryTopInsurances = array_slice($aryTopInsurances, 0, 10, true);

        $aryData = array();
        $aryDepartments = array();
        $aryInsurances = array();
        if (count($data) > 0) {
            foreach ($data as $row) {

                $insuranceNo = $row['insuranceNo'];
                $insuranceName = $row['insuranceName'];
                $reportTypeNo = $row['reportTypeNo'];
                $reportTypeName = $row['reportTypeName'];
                $idDepartment = $row['idDepartment'];
                $deptNo = $row['deptNo'];
                $deptName = $row['deptName'];
                $orderCount = $row['OrderCount'];
                //$totalPayment = $row['TotalPayment'];

                //if (!array_key_exists($insuranceNo, $aryInsurances) && in_array($insuranceNo, $aryInsuranceNo)) {
                if (!array_key_exists($insuranceNo, $aryInsurances) && array_key_exists($insuranceNo, $aryTopInsurances)) {
                    $aryInsurances[$insuranceNo] = $insuranceName;
                }

                if (!in_array($idDepartment, $aryDepartments)) {
                    $aryDepartments[] = $idDepartment;

                    $aryData[] = array(
                        "key" => $deptName,
                        "values" => array()
                    );
                }
            }

            for ($i = 0; $i < count($aryData); $i++) {
                $deptName = $aryData[$i]['key'];

                foreach ($aryInsurances as $insuranceNo => $insuranceName) {
                    $aryData[$i]['values'][] = array(
                        "key" => $deptName,
                        "x" => $insuranceName,
                        "y" => 0,
                        "tooltip" => "<b>$insuranceName</b><br/><b>$deptName</b><br/>"
                    );
                }
            }

            $i = 0;
            $currIdDepartment = $data[0]['idDepartment'];

            foreach ($data as $row) {
                $insuranceNo = $row['insuranceNo'];
                $insuranceName = $row['insuranceName'];
                $reportTypeNo = $row['reportTypeNo'];
                $reportTypeName = $row['reportTypeName'];
                $idDepartment = $row['idDepartment'];
                $deptNo = $row['deptNo'];
                $deptName = $row['deptName'];
                $orderCount = $row['OrderCount'];
                //$totalPayment = $row['TotalPayment'];
                $totalCommission = $row['TotalCommission'];
                $idLocation = $row['idLocation'];
                $locationNo = $row['locationNo'];
                $locationName = $row['locationName'];

                /*
                if (!in_array($row['idsalesmen'], array_column($arySalesmen, 'id'))) { // make array of distinct salesmen
                $key = array_search($row['idsalesmen'], array_column($arySalesmen, 'id'));

                $salesmanKey = array_search($currIdSalesmen, array_column($arySalesData, 'id'));
                $aryCurrSalesman = $arySalesData[$salesmanKey];

                $clientKey = array_search($currIdClients, array_column($arySalesData[$salesmanKey]['clients'], 'idClients'));
                $aryCurrClient = $arySalesData[$salesmanKey]['clients'][$clientKey];

                $arySalesData[$salesmanKey]['clients'][$clientKey]['sales'][$currOrderDate] = $currOrderCount;

                $currNumericOrderDate = strtotime($currOrderDate . " 12:00:00");
                //$currNumericOrderDate = date('Y,m,d,H,i,s', strtotime($currOrderDate));

                $salesmanChartKey = array_search($currSalesmanName, array_column($aryLineChartData, 'key'));
                $dateKey = array_search($currNumericOrderDate, array_column($aryLineChartData[$salesmanChartKey]['values'], 'x'));
                */

                if ($idDepartment != $currIdDepartment) {
                    $i++;
                }

                $insuranceKey = array_search($insuranceName, array_column($aryData[$i]['values'], 'x'));


                /*if ($orderBy == "TotalPayment" && $input['isManager'] == false) {
                    $aryData[$i]['values'][$insuranceKey]['y'] = $totalCommission;
                } else if ($orderBy == "TotalPayment") {
                    $aryData[$i]['values'][$insuranceKey]['y'] = $totalPayment;
                } else{
                    $aryData[$i]['values'][$insuranceKey]['y'] = $orderCount;
                }*/

                if (array_key_exists($insuranceNo, $aryInsurances)) {
                    /*if ($orderBy == "TotalPayment" && $input['isManager'] == true) {
                        $aryData[$i]['values'][$insuranceKey]['y'] += $totalPayment;
                        $aryData[$i]['values'][$insuranceKey]['tooltip'] .= $locationName . ": " . $totalPayment . "<br/>";
                    } else{
                        $aryData[$i]['values'][$insuranceKey]['y'] += $orderCount;
                        $aryData[$i]['values'][$insuranceKey]['tooltip'] .= $locationName . ": " . $orderCount . "<br/>";
                    }*/
                    $aryData[$i]['values'][$insuranceKey]['y'] += $orderCount;
                    $aryData[$i]['values'][$insuranceKey]['tooltip'] .= $locationName . ": " . $orderCount . "<br/>";
                }
                $currIdDepartment = $row['idDepartment'];
            }
        }

        //echo "<pre>"; print_r($aryData); echo "</pre>";
        return $aryData;
    }

    public function getSalesPerPayorPieChartData(array $input) {
        $aryWhere = $this->getWhere($input);
        $where = $aryWhere['where'];
        $aryInput = $aryWhere['input'];

        $dateField = $this->getDateSearchField($input);

        $sql = "
            SELECT 	s.idsalesmen, sg.id AS `groupId`,
                i.idinsurances, i.number AS `insuranceNumber`, i.name AS `insuranceName`,
                t.territoryName, sg.groupName,
                l.idLocation, l.locationNo, l.locationName,
                COUNT(DISTINCT o.idOrders) AS `OrderCount`,
                SUM(dcc.paid) AS `TotalPayment`,
                /*ROUND(SUM(CASE 
                    WHEN cr.idCommissions IS NOT NULL AND crt.typeNumber = 1 THEN o.payment * cr.commissionRate # Percentage by order
                    WHEN cr.idCommissions IS NOT NULL AND crt.typeNumber = 2 THEN cr.commissionRate # Amount by order
                    WHEN cr.idCommissions IS NULL AND s.byOrders = true AND s.byPercentage = true THEN o.payment * s.commisionRate
                    WHEN cr.idCommissions IS NULL AND s.byOrders = true AND s.byAmount = true THEN s.commisionRate
                    ELSE 0
                END), 2) AS `TotalCommission`*/
                SUM(CASE
                  WHEN cr.idCommissions IS NOT NULL AND o.payment IS NOT NULL AND o.payment > cr.commissionRate THEN cr.commissionRate
                  ELSE 0
                END) AS `TotalCommission`
            FROM  " . self::DB_CSS . "." . self::TBL_SALESMEN . " s
            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON s.idsalesmen = c.salesmen
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON s.idsalesmen = sgl.salesmanId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id
            INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON c.idClients = o.clientId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i ON o.insurance = i.idinsurances
            INNER JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " l ON o.locationId = l.idLocation
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TERRITORY . " t ON s.territory = t.idterritory
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr ON o.reportType = cr.reportTypeId AND s.idsalesmen = cr.salesmanId
            
            LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILORDERS . " do ON o.idOrders = do.orderId AND do.active = true
            LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILCPTCODES . " dcc ON do.iddetailOrders = dcc.detailOrderId
            
            $where
            GROUP BY i.idinsurances, l.idLocation
            ORDER BY OrderCount DESC, i.idinsurances ASC ";

        /*error_log($sql);
        error_log(implode($aryInput, ","));*/

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $aryReturn = array();
        if (count($data) > 0) {
            $idinsurances = $data[0]['idinsurances'];
            $insuranceName = $data[0]['insuranceName'];
            $idLocation = $data[0]['idLocation'];
            $locationName = $data[0]['locationName'];
            $orderCount = $data[0]['OrderCount'];
            $totalPayment = $data[0]['TotalPayment'];
            $totalCommission = $data[0]['TotalCommission'];
            $y = 0;
            $aryCurr = array(
                "key" => $insuranceName,
                "y" => 0,
                "tooltip" => "<b>$insuranceName</b><br/>"
            );

            foreach ($data as $row) {

                $insuranceName = $row['insuranceName'];
                $idLocation = $row['idLocation'];
                $locationName = $row['locationName'];
                $orderCount = $row['OrderCount'];
                $totalPayment = $row['TotalPayment'];
                $totalCommission = $row['TotalCommission'];
                if ($dateField == "o.DOI" && $input['isManager'] == true) {
                    $y = $totalPayment;
                } else {
                    $y = $orderCount;
                }

                if ($row['idinsurances'] != $idinsurances) {
                    $aryReturn[] = $aryCurr;
                    $aryCurr = array(
                        "key" => $insuranceName,
                        "y" => $y,
                        "tooltip" => "<b>$insuranceName</b><br/>$locationName" . ": $y <br/>"
                    );
                } else {
                    $aryCurr['y'] = $aryCurr['y'] + $y;
                    $aryCurr['tooltip'] = $aryCurr['tooltip'] . $locationName . ": " . $y . "<br/>";
                }

                $idinsurances = $row['idinsurances'];

            }
            $aryReturn[] = $aryCurr;

            // PHP 7+
            /*usort($aryReturn, function($item1, $item2) {
                return $item2['y'] <=> $item1['y'];
            });*/

            // PHP 5.3+ and <7
            // https://stackoverflow.com/a/19454643
            usort($aryReturn, function ($item1, $item2) {
                if ($item1['y'] == $item2['y']) return 0;
                return $item1['y'] > $item2['y'] ? -1 : 1;
            });

            $aryReturn = array_slice($aryReturn, 0, 10); // get first 10 elements of array
        }

        return $aryReturn;
    }

    public function getSalesPerSalesPersonPieChartData(array $input) {
        $aryWhere = $this->getWhere($input);
        $where = $aryWhere['where'];
        $aryInput = $aryWhere['input'];

        $dateField = $this->getDateSearchField($input);

        $sql = "
            SELECT 	s.idsalesmen, sg.id AS `groupId`,
                e.lastName AS `salesmanLastName`, e.firstName AS `salesmanFirstName`,
                t.territoryName, sg.groupName,
                l.idLocation, l.locationNo, l.locationName,
                COUNT(DISTINCT o.idOrders) AS `OrderCount`,
                SUM(dcc.paid) AS `TotalPayment`,
                /*ROUND(SUM(CASE 
                    WHEN cr.idCommissions IS NOT NULL AND crt.typeNumber = 1 THEN o.payment * cr.commissionRate # Percentage by order
                    WHEN cr.idCommissions IS NOT NULL AND crt.typeNumber = 2 THEN cr.commissionRate # Amount by order
                    WHEN cr.idCommissions IS NULL AND s.byOrders = true AND s.byPercentage = true THEN o.payment * s.commisionRate
                    WHEN cr.idCommissions IS NULL AND s.byOrders = true AND s.byAmount = true THEN s.commisionRate
                    ELSE 0
                END), 2) AS `TotalCommission`*/
                SUM(CASE
                  WHEN cr.idCommissions IS NOT NULL AND o.payment IS NOT NULL AND o.payment > cr.commissionRate THEN cr.commissionRate
                  ELSE 0
                END) AS `TotalCommission`
            FROM  " . self::DB_CSS . "." . self::TBL_SALESMEN . " s
            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON s.idsalesmen = c.salesmen
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON s.idsalesmen = sgl.salesmanId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id
            INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON c.idClients = o.clientId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " l ON o.locationId = l.idLocation
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TERRITORY . " t ON s.territory = t.idterritory
            /*LEFT JOIN (
                SELECT r.orderId, t.idtests
                FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
                INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests AND t.number = 490
                WHERE   $dateField BETWEEN ? AND ?
                GROUP BY r.orderId
            ) rt ON o.idOrders = rt.orderId*/
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr ON o.reportType = cr.reportTypeId AND s.idsalesmen = cr.salesmanId
            
            LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILORDERS . " do ON o.idOrders = do.orderId AND do.active = true
            LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILCPTCODES . " dcc ON do.iddetailOrders = dcc.detailOrderId
          
            $where
            GROUP BY s.idsalesmen, l.idLocation
            ORDER BY s.idsalesmen ASC";

        /*error_log($sql);
        error_log(implode($aryInput, ","));*/

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $aryReturn = array();

        if (count($data) > 0) {
            $idsalesmen = $data[0]['idsalesmen'];
            $salesmanFirstName = $data[0]['salesmanFirstName'];
            $salesmanLastName = $data[0]['salesmanLastName'];
            $idLocation = $data[0]['idLocation'];
            $locationName = $data[0]['locationName'];
            $orderCount = $data[0]['OrderCount'];
            $totalPayment = $data[0]['TotalPayment'];
            $totalCommission = $data[0]['TotalCommission'];
            $y = 0;
            $aryCurr = array(
                "key" => $salesmanFirstName . " " . $salesmanLastName,
                "y" => 0,
                "tooltip" => "<b>$salesmanFirstName $salesmanLastName</b><br/>"
            );

            foreach ($data as $row) {
                $salesmanFirstName = $row['salesmanFirstName'];
                $salesmanLastName = $row['salesmanLastName'];
                $idLocation = $row['idLocation'];
                $locationName = $row['locationName'];
                $orderCount = $row['OrderCount'];
                $totalPayment = $row['TotalPayment'];
                $totalCommission = $row['TotalCommission'];
                if ($dateField == "o.DOI" && $input['isManager'] == true) {
                    $y = $totalPayment;
                } else {
                    $y = $orderCount;
                }

                if ($row['idsalesmen'] != $idsalesmen) {
                    $idsalesmen = $row['idsalesmen'];
                    $aryReturn[] = $aryCurr;
                    $aryCurr = array(
                        "key" => $salesmanFirstName . " " . $salesmanLastName,
                        "y" => $y,
                        "tooltip" => "<b>$salesmanFirstName $salesmanLastName</b><br/>$locationName" . ": " . $y . "<br/>"
                    );

                } else {
                    $aryCurr['y'] = $aryCurr['y'] + $y;
                    $aryCurr['tooltip'] = $aryCurr['tooltip'] . $locationName . ": " . $y . "<br/>";
                }

                /*if ($dateField == "o.DOI" && $input['isManager'] == true) {
                    $y = $totalPayment;
                } else {
                    $y = $orderCount;
                }
                $aryReturn[] = array(
                    "key" => $row['salesmanFirstName'] . " " . $row['salesmanLastName'],
                    "y" => $y,
                    "tooltip" => $tooltip
                );*/
            }
            $aryReturn[] = $aryCurr;
        }

        return $aryReturn;
    }

    /*
    {
        "Blood Wellness": [
            {"key":"ALEXANDER IVANOV","y":247},
            {"key":"SUZANNE ZEMEL","y":22},
            {"key":"DEBORAH,M.D. BESSEN","y":116},
            {"key":"EVELYN,NP-C AKANBI","y":58},
            {"key":"ALICE NP-C HINCK","y":35}
        ],
        "PGX": [
            {"key":"ALEXANDER IVANOV","y":15},
            {"key":"DEBORAH,M.D. BESSEN","y":24},
            {"key":"SUZANNE ZEMEL","y":3},
            {"key":"GRACE,APN-C SAMUEL","y":12},
            {"key":"EMILY APNC KELLER","y":12}
        ],
        "Hereditary Cancer": [
            {"key":"ALEXANDER IVANOV","y":19},
            {"key":"SUZANNE ZEMEL","y":5},
            {"key":"Aaron Roth","y":32},
            {"key":"RANDY ZEID","y":38},
            {"key":"ALICE NP-C HINCK","y":4}
        ],
        "Pharmacogenomics": [
            {"key":"ALEXANDER IVANOV","y":5},
            {"key":"ALICE NP-C HINCK","y":1},
            {"key":"DEBORAH,M.D. BESSEN","y":7},
            {"key":"EMILY APNC KELLER","y":4},
            {"key":"DIANE MACKENZIE","y":2}
        ]
    }
    */
    public function getTopAccountsByReportType(array $input) {
        $aryWhere = $this->getWhere($input);
        $where = $aryWhere['where'];
        $aryInput = array($input['dateFrom'], $input['dateTo']);
        $aryInput = array_merge($aryInput, $aryWhere['input']);
        $dateField = $this->getDateSearchField($input);

        $sql = "
            SELECT 	o.idOrders,
                    d.iddoctors, d.number AS `doctorNumber`, d.lastName, d.firstName,
                    rt.idreportType, rt.name AS `reportTypeName`,
                    x.idDepartment, x.deptNo, x.deptName,
                    l.idLocation, l.locationNo, l.locationName,
                    COUNT(DISTINCT o.idOrders) AS `OrderCount`
                    #CASE WHEN SUM(o.payment) IS NULL THEN 0 ELSE SUM(o.payment) END AS `TotalPayment`
                    
                    #COUNT(DISTINCT CASE WHEN rt.orderId IS NULL THEN NULL ELSE o.idOrders END) AS `RejectedOrderCount`,
                    #CASE WHEN SUM(o.payment) IS NOT NULL AND rt.orderId IS NOT NULL THEN SUM(o.payment) ELSE 0 END AS `RejectedTotalPayment`,
                    
                    /*ROUND(SUM(CASE 
                        WHEN cr.idCommissions IS NOT NULL AND crt.typeNumber = 1 THEN o.payment * cr.commissionRate # Percentage by order
                        WHEN cr.idCommissions IS NOT NULL AND crt.typeNumber = 2 THEN cr.commissionRate # Amount by order
                        WHEN cr.idCommissions IS NULL AND s.byOrders = true AND s.byPercentage = true THEN o.payment * s.commisionRate
                        WHEN cr.idCommissions IS NULL AND s.byOrders = true AND s.byAmount = true THEN s.commisionRate
                        ELSE 0
                    END), 2) AS `TotalCommission`*/
                    #SUM(CASE
                    #    WHEN cr.idCommissions IS NOT NULL AND o.payment IS NOT NULL AND o.payment > cr.commissionRate THEN cr.commissionRate
                    #    ELSE 0
                    #END) AS `TotalCommission`
                    
                    #SUM(dcc.billAmount) AS `TotalBilled`,
                    #SUM(dcc.paid) AS `TotalPaid`,
                    #SUM(dcc.billAmount) - SUM(dcc.paid) AS `TotalBalance`
                FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                INNER JOIN (
                    SELECT	o.idOrders,    
                        COUNT(DISTINCT r.idResults) AS `OrderCount`,
                        COUNT(DISTINCT CASE WHEN r.printAndTransmitted THEN r.idResults ELSE NULL END) AS `TransmitCount`,
                        d.idDepartment, d.deptNo, d.deptName                    
                    FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTS . " d ON t.department = d.idDepartment 
                    WHERE $dateField BETWEEN ? AND ?
                        AND r.isInvalidated = false
                    GROUP BY o.idOrders		
                ) x ON o.idOrders = x.idOrders
                INNER JOIN " . self::DB_CSS . "." . self::TBL_REPORTTYPE . " rt ON o.reportType = rt.idreportType
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors
                INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON c.salesmen = s.idsalesmen
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON s.idsalesmen = sgl.salesmanId
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id
                INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
                INNER JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " l ON o.locationId = l.idLocation
                
                #INNER JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILORDERS . " do ON o.idOrders = do.orderId AND do.active = true
                #LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILCPTCODES . " dcc ON do.iddetailOrders = dcc.detailOrderId
                
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr ON o.reportType = cr.reportTypeId AND s.idsalesmen = cr.salesmanId
                
                $where
                GROUP BY x.idDepartment, l.idLocation
                ORDER BY x.deptName ASC";

        /*error_log($sql);
        error_log(implode($aryInput, ","));*/

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $rejectedOrderCount = 0;
        $rejectedTotalPayment = 0;

        $aryReturn = array();
        if (count($data) > 0) {
            $idreportType = $data[0]['idreportType'];
            $reportTypeName = $data[0]['reportTypeName'];
            $idDepartment = $data[0]['idDepartment'];
            $deptNo = $data[0]['deptNo'];
            $deptName = $data[0]['deptName'];
            $idLocation = $data[0]['idLocation'];
            $locationName = $data[0]['locationName'];
            $orderCount = $data[0]['OrderCount'];
            //$totalPayment = $data[0]['TotalPayment'];
            //$totalCommission = $data[0]['TotalCommission'];
            $y = 0;
            $aryCurr = array(
                "key" => $reportTypeName,
                "y" => 0,
                "tooltip" => "<b>$reportTypeName</b><br/>"
            );

            foreach ($data as $row) {

                $reportTypeName = $row['reportTypeName'];
                $idLocation = $row['idLocation'];
                $locationName = $row['locationName'];

                $idDepartment = $row['idreportType'];
                $deptName = $row['deptName'];

                $orderCount = $row['OrderCount'];
                //$totalPayment = $row['TotalPayment'];
                //$totalCommission = $row['TotalCommission'];
                /*if ($dateField == "o.DOI" && $input['isManager'] == true) {
                    $y = $totalPayment;
                } else {
                    $y = $orderCount;
                }*/
                $y = $orderCount;

                if ($row['deptNo'] != $deptNo) {
                    $aryReturn[] = $aryCurr;
                    $aryCurr = array(
                        "key" => $deptName,
                        "y" => $y,
                        "tooltip" => "<b>$deptName</b><br/>$locationName" . ": $y <br/>"
                    );

                } else {
                    $aryCurr['y'] = $aryCurr['y'] + $y;
                    $aryCurr['tooltip'] = $aryCurr['tooltip'] . $locationName . ": " . $y . "<br/>";
                }

                $deptNo = $row['deptNo'];

                /*$doctorName = $row['firstName'] . " " . $row['lastName'];
                $reportTypeName = $row['reportTypeName'];
                $y = $row['OrderCount'];
                if ($dateField == "o.DOI" && $input['isManager'] == true) {
                    $y = $row['TotalPayment'];
                }
                $aryReturn[] = array(
                    'key' => $reportTypeName,
                    'y' => $y
                );
                */

                ///$rejectedOrderCount += $row['RejectedOrderCount'];
                //$rejectedTotalPayment += $row['RejectedTotalPayment'];
            }
            $aryReturn[] = $aryCurr;

            /*if ($dateField == "o.DOI") {
                if ($rejectedTotalPayment > 0) {
                    $aryReturn[] = array(
                        'key' => 'Rejected',
                        'y' => $rejectedTotalPayment,
                        'tooltip' => '<b>Reject Payments</b>'
                    );
                }
            } else {
                if ($rejectedOrderCount > 0) {
                    $aryReturn[] = array(
                        'key' => 'Rejected',
                        'y' => $rejectedOrderCount,
                        'tooltip' => '<b>Rejected Orders</b>'
                    );
                }
            }*/
        }

        return $aryReturn;
    }

    // sales-info-sidebar
    public function getTotalCommission(array $input) {
        $dateField = $this->getDateSearchField($input);

        $aryWhere = $this->getWhere($input);
        $where = $aryWhere['where'];
        $aryInput = array($input['dateFrom'], $input['dateTo']);
        $aryInput = array_merge($aryInput, $aryWhere['input']);


        /*$aryInput = array($input['dateFrom'], $input['dateTo']);
        $aryInput = array_merge($aryInput, $aryWhere['input']);*/

//        $sql = "
//            SELECT
//                    sg.id AS `groupId`,
//                    sg.groupName,
//                    rt.idreportType, rt.name AS `reportTypeName`,
//                    COUNT(DISTINCT o.idOrders) AS `OrderCount`,
//                    o.payment,
//                    #CAST(o.payment AS DECIMAL(7,2)) AS `payment`,
//                    /*ROUND(CASE
//                        WHEN cr.idCommissions IS NOT NULL AND crt.typeNumber = 1 THEN o.payment * cr.commissionRate # Percentage by order
//                        WHEN cr.idCommissions IS NOT NULL AND crt.typeNumber = 2 THEN cr.commissionRate # Amount by order
//                        WHEN cr.idCommissions IS NULL AND s.byOrders = true AND s.byPercentage = true THEN o.payment * s.commisionRate
//                        WHEN cr.idCommissions IS NULL AND s.byOrders = true AND s.byAmount = true THEN s.commisionRate
//                        ELSE 0
//                    END, 2) AS `commission`,*/
//                    CASE
//                      WHEN cr.idCommissions IS NOT NULL AND o.payment IS NOT NULL AND o.payment > cr.commissionRate THEN cr.commissionRate
//                      ELSE 0
//                    END AS `commission`,
//                    cr.commissionRate,
//                    o.idOrders, o.accession,
//                    o.doctorId, o.clientId, o.patientId,
//                    CASE WHEN GROUP_CONCAT(t.idtests) IS NOT NULL THEN 1 ELSE 0 END AS `IsRejected`,
//                    o.billable, o.billOnly, o.active, o.hold, o.stage, o.EOA,
//                    o.reportType,
//                    o.allPAndT,
//                    o.orderDate, o.specimenDate
//            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_REPORTTYPE . " rt ON o.reportType = rt.idreportType
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON c.salesmen = s.idsalesmen
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON s.salesGroup = sg.id
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests AND t.number = 490
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr ON o.reportType = cr.reportTypeId AND s.idsalesmen = cr.salesmanId
//            $where
//            GROUP BY o.idOrders
//            HAVING IsRejected = 0
//            ORDER BY o.reportType ";

//        $sql = "
//          SELECT	x.groupId, x.groupName, x.idreportType, x.reportTypeName, x.idDepartment, x.deptNo, x.deptName,
//            x.CostBasis,
//            COUNT(x.idOrders) AS `OrderCount`,
//            ROUND(SUM(x.payment), 2) AS `payment`,
//            ROUND(SUM(x.commission), 2) AS `commission`,
//            ROUND(SUM(x.groupLeaderCommission), 2) AS `groupLeaderCommission`,
//            ROUND(SUM(x.territoryLeaderCommission), 2) AS `territoryLeaderCommission`
//          FROM (
//              SELECT
//                    o.idOrders, o.accession,
//                    sg.id AS `groupId`,
//                    sg.groupName,
//                    rt.idreportType, rt.name AS `reportTypeName`,
//                    x.idDepartment, x.deptNo, x.deptName,
//                    COUNT(DISTINCT o.idOrders) AS `OrderCount`,
//                    s.idsalesmen,
//                    SUM(dcc.paid) AS `payment`,
//                    x.CostBasis,
//                    #SUM(dcc.billAmount) AS `billAmount`,
//                    CASE
//                        WHEN y.commisionRate > 0 AND SUM(dcc.paid) - x.CostBasis > 0 THEN
//                            CASE WHEN ((SUM(dcc.paid) - x.CostBasis) * (y.commisionRate/100)) - (((SUM(dcc.paid) - x.CostBasis) * (s2.commisionRate/100)) - ((SUM(dcc.paid) - x.CostBasis) * (s.commisionRate/100))) > 0 THEN
//                                ((SUM(dcc.paid) - x.CostBasis) * (y.commisionRate/100)) - (((SUM(dcc.paid) - x.CostBasis) * (s2.commisionRate/100)) - ((SUM(dcc.paid) - x.CostBasis) * (s.commisionRate/100)))
//                            ELSE ((SUM(dcc.paid) - x.CostBasis) * (y.commisionRate/100))
//                            END
//                        ELSE 0
//                    END AS `territoryLeaderCommission`,
//                    CASE
//                        WHEN cr2.idCommissions IS NOT NULL AND cr2.commissionRate > 0 AND SUM(dcc.paid) >= cr2.minPayment THEN
//                            CASE
//                                WHEN s2.byPercentage = 1 AND cr2.commissionRate > 0 THEN cr2.commissionRate/100
//                                ELSE cr2.commissionRate
//                            END
//                        WHEN (cr2.idCommissions IS NULL OR cr2.commissionRate <= 0) AND s2.commisionRate > 0 THEN
//                            CASE
//                                WHEN s2.byPercentage = 1 AND s2.commisionRate > 0 THEN (s2.commisionRate/100)
//                                ELSE s2.commisionRate
//                            END
//                        ELSE 0
//                    END AS `groupLeaderCommissionRate`,
//                    ROUND(CASE
//                        WHEN cr2.idCommissions IS NOT NULL AND cr2.commissionRate > 0  AND SUM(dcc.paid) >= cr2.minPayment AND SUM(dcc.paid) - x.CostBasis > 0 THEN
//                            CASE
//                                WHEN s2.byPercentage = 1 THEN ((SUM(dcc.paid) - x.CostBasis) * (cr2.commissionRate/100)) - ((SUM(dcc.paid) - x.CostBasis) * (cr.commissionRate/100))
//                                ELSE cr2.commissionRate
//                            END
//                        WHEN (cr2.idCommissions IS NULL OR cr2.commissionRate <= 0) AND s2.commisionRate > 0 AND SUM(dcc.paid) - x.CostBasis > 0 THEN
//                            CASE
//                                WHEN s2.byPercentage = 1 THEN ((SUM(dcc.paid) - x.CostBasis) * (s2.commisionRate/100)) - ((SUM(dcc.paid) - x.CostBasis) * (s.commisionRate/100))
//                                ELSE s2.commisionRate
//                            END
//                        ELSE 0
//                    END, 2) AS `groupLeaderCommission`,
//
//                    CASE
//                        WHEN cr.idCommissions IS NOT NULL AND cr.commissionRate > 0 AND SUM(dcc.paid) >= cr.minPayment THEN
//                            CASE
//                                WHEN s.byPercentage = 1 AND cr.commissionRate > 0 THEN cr.commissionRate/100
//                                ELSE cr.commissionRate
//                            END
//                        WHEN (cr.idCommissions IS NULL OR cr.commissionRate <= 0) AND s.commisionRate > 0 THEN
//                            CASE
//                                WHEN s.byPercentage = 1 AND s.commisionRate > 0 THEN (s.commisionRate/100)
//                                ELSE s.commisionRate
//                            END
//                        ELSE 0
//                    END AS `commissionRate`,
//                    ROUND(CASE
//                        WHEN cr.idCommissions IS NOT NULL AND cr.commissionRate > 0  AND SUM(dcc.paid) >= cr.minPayment AND SUM(dcc.paid) - x.CostBasis > 0 THEN
//                            CASE
//                                WHEN s.byPercentage = 1 THEN ((SUM(dcc.paid) - x.CostBasis) * (cr.commissionRate/100))
//                                ELSE cr.commissionRate
//                            END
//                        WHEN (cr.idCommissions IS NULL OR cr.commissionRate <= 0) AND s.commisionRate > 0 AND SUM(dcc.paid) - x.CostBasis > 0 THEN
//                            CASE
//                                WHEN s.byPercentage = 1 THEN ((SUM(dcc.paid) - x.CostBasis) * (s.commisionRate/100))
//                                ELSE s.commisionRate
//                            END
//                        ELSE 0
//                    END, 2) AS `commission`,
//
//                    GROUP_CONCAT(DISTINCT o.idOrders) AS `OrderIds`,
//                    o.doctorId, o.clientId, o.patientId,
//                    o.billable, o.billOnly, o.active, o.hold, o.stage, o.EOA,
//                    o.reportType,
//                    o.allPAndT,
//                    o.orderDate, o.specimenDate
//
//                FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
//                INNER JOIN (
//                    SELECT	o.idOrders,
//                        COUNT(DISTINCT r.idResults) AS `OrderCount`,
//                        COUNT(DISTINCT CASE WHEN r.printAndTransmitted THEN r.idResults ELSE NULL END) AS `TransmitCount`,
//                        d.idDepartment, d.deptNo, d.deptName,
//                        SUM(CASE
//                            WHEN r.panelId IS NULL THEN t.cost
//                            ELSE 0
//                        END) AS `CostBasis`
//                    FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
//                    INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
//                    INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests
//                    INNER JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTS . " d ON t.department = d.idDepartment
//                    WHERE $dateField BETWEEN ? AND ?
//                        AND r.isInvalidated = false
//                    GROUP BY o.idOrders
//                ) x ON o.idOrders = x.idOrders
//
//                INNER JOIN " . self::DB_CSS . "." . self::TBL_REPORTTYPE . " rt ON o.reportType = rt.idreportType
//                INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
//                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON c.salesmen = s.idsalesmen
//                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON s.idsalesmen = sgl.salesmanId
//                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id
//                INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
//                LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr ON o.reportType = cr.reportTypeId AND s.idsalesmen = cr.salesmanId
//
//                LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s2 ON sg.groupLeader = s2.idsalesmen
//                LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr2 ON s2.idsalesmen = cr2.salesmanId AND o.reportType = cr2.reportTypeId
//
//                LEFT JOIN (
//                    SELECT s.idsalesmen, s2.idsalesmen AS `territoryLeaderId`, s2.commisionRate, sg.id, sg.groupLeader
//                    FROM " . self::DB_CSS . "." . self::TBL_SALESMEN . " s
//                    INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON s.idsalesmen = sgl.salesmanId
//                    INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id
//                    INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s2 ON sg.groupLeader = s2.idsalesmen
//                    GROUP BY s.idsalesmen
//                ) y ON s2.idsalesmen = y.idsalesmen AND s2.idsalesmen != y.groupLeader
//
//                LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILORDERS . " do ON o.idOrders = do.orderId AND do.active = true
//                LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILCPTCODES . " dcc ON do.iddetailOrders = dcc.detailOrderId AND dcc.transferredTo IS NULL
//
//                $where
//                GROUP BY o.idOrders
//            ) x
//            GROUP BY x.idDepartment
//            ORDER BY x.deptName ASC";

        $sql = "
          SELECT	x.groupId, x.groupName, x.idreportType, x.reportTypeName, x.idDepartment, x.deptNo, x.deptName, 
            ROUND(SUM(x.CostBasis), 2) AS `CostBasis`,
            COUNT(x.idOrders) AS `OrderCount`,
            ROUND(SUM(x.payment), 2) AS `payment`,
            ROUND(SUM(x.commission), 2) AS `commission`,
            ROUND(SUM(x.groupLeaderCommission), 2) AS `groupLeaderCommission`,
            ROUND(SUM(x.territoryLeaderCommission), 2) AS `territoryLeaderCommission`
          FROM (
              SELECT
                    o.idOrders, o.accession,
                    sg.id AS `groupId`,
                    sg.groupName,
                    rt.idreportType, rt.name AS `reportTypeName`,
                    x.idDepartment, x.deptNo, x.deptName,
                    COUNT(DISTINCT o.idOrders) AS `OrderCount`,
                    s.idsalesmen,
                    SUM(dcc.paid) AS `payment`,
                    CASE
                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND dcc.paid IS NOT NULL THEN ROUND(SUM(dcc.paid) * (x.cost / 100), 2)
                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND dcc.paid IS NULL THEN 0.00
                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Fixed Cost' THEN ROUND(x.cost, 2)
                        ELSE ROUND(x.CostBasis, 2)
                    END AS `CostBasis`,
                    #SUM(dcc.billAmount) AS `billAmount`,                
                    ROUND(CASE 
                        WHEN y.commisionRate > 0 AND (
                            (x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0) # department cost basis
                            OR (SUM(dcc.paid) - x.CostBasis > 0)
                        ) THEN
                            CASE 
                                WHEN x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0 THEN # department cost basis by percentage
                                    CASE
                                        WHEN ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (y.commisionRate/100)) - (((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (s2.commisionRate/100)) - ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (s.commisionRate/100))) > 0 THEN
                                            ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (y.commisionRate/100)) - (((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (s2.commisionRate/100)) - ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (s.commisionRate/100)))
                                        ELSE (SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (y.commisionRate/100)
                                    END
                                WHEN x.idCosts IS NOT NULL AND x.costType = 'Fixed Cost' THEN # department cost basis by fixed cost
                                    CASE
                                        WHEN ((SUM(dcc.paid) - x.cost) * (y.commisionRate/100)) - (((SUM(dcc.paid) - x.cost) * (s2.commisionRate/100)) - ((SUM(dcc.paid) - x.cost) * (s.commisionRate/100))) > 0 THEN
                                            ((SUM(dcc.paid) - x.cost) * (y.commisionRate/100)) - (((SUM(dcc.paid) - x.cost) * (s2.commisionRate/100)) - ((SUM(dcc.paid) - x.cost) * (s.commisionRate/100)))
                                        ELSE (SUM(dcc.paid) - x.cost) * (y.commisionRate/100)
                                    END
                                ELSE
                                    CASE
                                        WHEN ((SUM(dcc.paid) - x.CostBasis) * (y.commisionRate/100)) - (((SUM(dcc.paid) - x.CostBasis) * (s2.commisionRate/100)) - ((SUM(dcc.paid) - x.CostBasis) * (s.commisionRate/100))) > 0 THEN
                                            ((SUM(dcc.paid) - x.CostBasis) * (y.commisionRate/100)) - (((SUM(dcc.paid) - x.CostBasis) * (s2.commisionRate/100)) - ((SUM(dcc.paid) - x.CostBasis) * (s.commisionRate/100)))
                                        ELSE (SUM(dcc.paid) - x.CostBasis) * (y.commisionRate/100)
                                    END
                            END
                        ELSE 0
                    END, 2) AS `territoryLeaderCommission`,
                    CASE 
                        WHEN cr2.idCommissions IS NOT NULL AND cr2.commissionRate > 0 AND SUM(dcc.paid) >= cr2.minPayment THEN 
                            CASE
                                WHEN s2.byPercentage = 1 AND cr2.commissionRate > 0 THEN cr2.commissionRate/100
                                ELSE cr2.commissionRate
                            END
                        WHEN (cr2.idCommissions IS NULL OR cr2.commissionRate <= 0) AND s2.commisionRate > 0 THEN
                            CASE
                                WHEN s2.byPercentage = 1 AND s2.commisionRate > 0 THEN (s2.commisionRate/100)
                                ELSE s2.commisionRate
                            END            
                        ELSE 0
                    END AS `groupLeaderCommissionRate`,        
                    ROUND(CASE
                        WHEN cr2.idCommissions IS NOT NULL AND cr2.commissionRate > 0  AND SUM(dcc.paid) >= cr2.minPayment AND (
                            (x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0) # department cost basis
                            OR (SUM(dcc.paid) - x.CostBasis > 0)
                        ) THEN 
                            CASE
                                WHEN s2.byPercentage = 1 THEN 
                                    CASE
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0 THEN # department cost basis by percentage
                                            ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (cr2.commissionRate/100)) - ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (cr.commissionRate/100))
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Fixed Cost' THEN # department cost basis by fixed cost
                                            ((SUM(dcc.paid) - x.cost) * (cr2.commissionRate/100)) - ((SUM(dcc.paid) - x.cost) * (cr.commissionRate/100))
                                        ELSE ((SUM(dcc.paid) - x.CostBasis) * (cr2.commissionRate/100)) - ((SUM(dcc.paid) - x.CostBasis) * (cr.commissionRate/100))
                                    END
                                ELSE cr2.commissionRate
                            END
                        WHEN (cr2.idCommissions IS NULL OR cr2.commissionRate <= 0) AND s2.commisionRate > 0 AND (
                            (x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0) # department cost basis
                            OR (SUM(dcc.paid) - x.CostBasis > 0)
                        ) THEN
                            CASE
                                WHEN s2.byPercentage = 1 THEN 
                                    CASE
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0 THEN # department cost basis by percentage
                                            ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (s2.commisionRate/100)) - ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (s.commisionRate/100))
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Fixed Cost' THEN # department cost basis by fixed cost
                                            ((SUM(dcc.paid) - x.cost) * (s2.commisionRate/100)) - ((SUM(dcc.paid) - x.cost) * (s.commisionRate/100))
                                        ELSE ((SUM(dcc.paid) - x.CostBasis) * (s2.commisionRate/100)) - ((SUM(dcc.paid) - x.CostBasis) * (s.commisionRate/100))
                                    END
                                ELSE s2.commisionRate
                            END            
                        ELSE 0
                    END, 2) AS `groupLeaderCommission`, 
                    
                    CASE 
                        WHEN cr.idCommissions IS NOT NULL AND cr.commissionRate > 0 AND SUM(dcc.paid) >= cr.minPayment THEN 
                            CASE
                                WHEN s.byPercentage = 1 AND cr.commissionRate > 0 THEN cr.commissionRate/100
                                ELSE cr.commissionRate
                            END
                        WHEN (cr.idCommissions IS NULL OR cr.commissionRate <= 0) AND s.commisionRate > 0 THEN
                            CASE
                                WHEN s.byPercentage = 1 AND s.commisionRate > 0 THEN (s.commisionRate/100)
                                ELSE s.commisionRate
                            END            
                        ELSE 0
                    END AS `commissionRate`,
                    ROUND(CASE
                        WHEN cr.idCommissions IS NOT NULL AND cr.commissionRate > 0  AND SUM(dcc.paid) >= cr.minPayment AND (
                            (x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0) # department cost basis
                            OR (SUM(dcc.paid) - x.CostBasis > 0)
                        ) THEN 
                            CASE
                                WHEN s.byPercentage = 1 THEN 
                                    CASE
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0 THEN # department cost basis by percentage
                                            ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (cr.commissionRate/100))
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Fixed Cost' THEN # department cost basis by fixed cost
                                            ((SUM(dcc.paid) - x.cost) * (cr.commissionRate/100))
                                        ELSE ((SUM(dcc.paid) - x.CostBasis) * (cr.commissionRate/100))
                                    END
                                    
                                
                                ELSE cr.commissionRate
                            END
                        WHEN (cr.idCommissions IS NULL OR cr.commissionRate <= 0) AND s.commisionRate > 0 AND (
                            (x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0) # department cost basis
                            OR (SUM(dcc.paid) - x.CostBasis > 0)
                        ) THEN
                            CASE
                                WHEN s.byPercentage = 1 THEN 
                                    CASE
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0 THEN # department cost basis by percentage
                                            ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (s.commisionRate/100))
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Fixed Cost' THEN # department cost basis by fixed cost
                                            ((SUM(dcc.paid) - x.cost) * (s.commisionRate/100))
                                        ELSE ((SUM(dcc.paid) - x.CostBasis) * (s.commisionRate/100))
                                    END
                                    
                                ELSE s.commisionRate
                            END            
                        ELSE 0
                    END, 2) AS `commission`,       
                    
                    GROUP_CONCAT(DISTINCT o.idOrders) AS `OrderIds`, 
                    o.doctorId, o.clientId, o.patientId,
                    o.billable, o.billOnly, o.active, o.hold, o.stage, o.EOA,
                    o.reportType,
                    o.allPAndT,
                    o.orderDate, o.specimenDate                 
                    
                FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                INNER JOIN (
                    SELECT	o.idOrders,    
                        COUNT(DISTINCT r.idResults) AS `OrderCount`,
                        COUNT(DISTINCT CASE WHEN r.printAndTransmitted THEN r.idResults ELSE NULL END) AS `TransmitCount`,
                        d.idDepartment, d.deptNo, d.deptName,
                        SUM(CASE
                            WHEN r.panelId IS NULL THEN t.cost
                            ELSE 0
                        END) AS `CostBasis`,
                        dc.idCosts, dc.cost,
			            ct.idCostTypes, ct.costType
                    FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTS . " d ON t.department = d.idDepartment
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTCOSTS . " dc ON d.idDepartment = dc.departmentId
                    LEFT JOIN " . self::DB_CSS . "." . self::TBL_COSTTYPES . " ct ON dc.costTypeId = ct.idCostTypes
                    WHERE $dateField BETWEEN ? AND ?
                        AND r.isInvalidated = false
                    GROUP BY o.idOrders		
                ) x ON o.idOrders = x.idOrders
                            
                INNER JOIN " . self::DB_CSS . "." . self::TBL_REPORTTYPE . " rt ON o.reportType = rt.idreportType
                INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON c.salesmen = s.idsalesmen
                
                INNER JOIN (
                    SELECT *
                    FROM " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id
                    GROUP BY salesmanId
                ) sg ON s.idsalesmen = sg.salesmanId
                INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr ON o.reportType = cr.reportTypeId AND s.idsalesmen = cr.salesmanId
                
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s2 ON sg.groupLeader = s2.idsalesmen
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr2 ON s2.idsalesmen = cr2.salesmanId AND o.reportType = cr2.reportTypeId
                
                LEFT JOIN (
                    SELECT s.idsalesmen, s2.idsalesmen AS `territoryLeaderId`, s2.commisionRate, sg.id, sg.groupLeader
                    FROM " . self::DB_CSS . "." . self::TBL_SALESMEN . " s
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON s.idsalesmen = sgl.salesmanId
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s2 ON sg.groupLeader = s2.idsalesmen
                    GROUP BY s.idsalesmen
                ) y ON s2.idsalesmen = y.idsalesmen AND s2.idsalesmen != y.groupLeader
                     
                LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILORDERS . " do ON o.idOrders = do.orderId AND do.active = true
                LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILCPTCODES . " dcc ON do.iddetailOrders = dcc.detailOrderId AND dcc.transferredTo IS NULL
                
                $where
                GROUP BY o.idOrders
            ) x
            GROUP BY x.idDepartment
            ORDER BY x.deptName ASC";

        //LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr ON s.idsalesmen = cr.salesmanId AND o.reportType = cr.reportTypeId AND o.locationId = cr.locationId
        //LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATETYPES . " crt ON cr.rateTypeId = crt.idRateTypes

        //INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON s.idsalesmen = sgl.salesmanId
        //INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id

//        error_log("/*sales-info-sidebar*/ " . $sql);
//        error_log(implode($aryInput, ","));

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $aryTotalSales = array();

        if (count($data) > 0) {
            foreach ($data as $row) {
                //$idOrders = $row['idOrders'];
                $idreportType = $row['idreportType'];
                $reportTypeName = $row['reportTypeName'];
                $idDepartment = $row['idDepartment'];
                $deptNo = $row['deptNo'];
                $deptName = $row['deptName'];

                $orderCount = $row['OrderCount'];
                //$payment = 0.00;
                //$commission = 0.00;

                $payment = $row['payment'];
                $commission = $row['commission'];

                $groupLeaderCommission = $row['groupLeaderCommission'];
                $territoryLeaderCommission = $row['territoryLeaderCommission'];

                /*
                if ($payment == null) {
                    $payment = 0;
                }
                if ($payment == "0.01") {
                    $payment = 0.01;
                }*/

                //if (!array_key_exists($idreportType, $aryTotalSales)) {
                if (!in_array($idDepartment, array_column($aryTotalSales, 'idDepartment'))) {
                    $aryTotalSales[] = array(
                        "reportTypeName" => $reportTypeName,
                        "idDepartment" => $idDepartment,
                        "deptNo" => $deptNo,
                        "deptName" => $deptName,
                        "totalSales" => $orderCount,
                        "payment" => $payment,
                        "commission" => $commission,
                        "groupLeaderCommission" => $groupLeaderCommission,
                        "territoryLeaderCommission" => $territoryLeaderCommission
                    );
                } else {
                    $key = array_search($idDepartment, array_column($aryTotalSales, 'idDepartment'));

                    $aryTotalSales[$key]['totalSales'] = $aryTotalSales[$key]['totalSales'] + $orderCount;

                    $aryTotalSales[$key]['payment'] = $aryTotalSales[$key]['payment'] + $payment;

                    $aryTotalSales[$key]['commission'] = $aryTotalSales[$key]['commission'] + $commission;
                    $aryTotalSales[$key]['groupLeaderCommission'] = $aryTotalSales[$key]['groupLeaderCommission'] + $groupLeaderCommission;
                    $aryTotalSales[$key]['territoryLeaderCommission'] = $aryTotalSales[$key]['territoryLeaderCommission'] + $territoryLeaderCommission;
                }
            }
        }

        return $aryTotalSales;
    }

    // commission-dialog
    public function getSalesCommissionData(array $input) {
        $aryWhere = $this->getWhere($input);
        $where = $aryWhere['where'];

        $aryInput = array($input['dateFrom'], $input['dateTo']);
        $aryInput = array_merge($aryInput, $aryWhere['input']);

        $dateField = $this->getDateSearchField($input);

        /*$groupId = $input['groupId'];
        $idsalesmen = $input['idsalesmen'];

        $where = "WHERE $dateField BETWEEN ? AND ?
            AND o.active = true ";

        $aryInput = array($dateFrom, $dateTo);

        if (isset($groupId) && !empty($groupId) && $groupId != "null") {
            $where .= "AND sg.id = ? ";
            $aryInput[] = $groupId;
        }

        if (isset($idsalesmen) && !empty($idsalesmen) && $idsalesmen != "null") {
            $where .= "AND (sg.groupLeader = ? OR sa.idsalesmen = ?) ";
            $aryInput[] = $idsalesmen;
            $aryInput[] = $idsalesmen;
        }

        if (array_key_exists("selectedGroupMembers", $input) && !empty($input['selectedGroupMembers'])) {
            $arySelectedGroupMembers = explode(",", $input['selectedGroupMembers']);
            $where .= "AND sa.idsalesmen IN (";
            foreach ($arySelectedGroupMembers as $salesmanId) {
                $where .= "?,";
                $aryInput[] = $salesmanId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }

        if (array_key_exists("selectedSalesGroups", $input) && !empty($input['selectedSalesGroups'])) {
            $arySelectedSalesGroups = explode(",", $input['selectedSalesGroups']);
            $where .= "AND sg.id IN (";
            foreach ($arySelectedSalesGroups as $groupId) {
                $where .= "?,";
                $aryInput[] = $groupId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }

        if (array_key_exists("selectedStatus", $input) && !empty($input['selectedStatus'])) {
            if ($input['selectedStatus'] == "all") {

            } else if ($input['selectedStatus'] == "paid") {
                $where .= "AND cpl.idPaymentLogs IS NOT NULL ";
            } else {
                // unpaid
                $where .= "AND cpl.idPaymentLogs IS NULL ";
            }

        } else {
            $where .= "AND cpl.idPaymentLogs IS NULL ";
        }*/

//        $sql = "
//            SELECT 	o.idOrders, o.accession,
//                    o.payment,
//                    CASE
//                      WHEN cr.idCommissions IS NOT NULL AND o.payment IS NOT NULL AND o.payment > cr.commissionRate THEN cr.commissionRate
//                      ELSE 0
//                    END AS `commission`,
//                    cr.commissionRate,
//                    CASE
//                        WHEN FIND_IN_SET(490, GROUP_CONCAT(t.number)) != 0 AND o.payment IS NOT NULL AND o.payment BETWEEN 0.01 AND 0.99 THEN CONCAT('Information Needed: ', o.payment)
//                        WHEN FIND_IN_SET(490, GROUP_CONCAT(t.number)) != 0 THEN 'Rejected'
//                        #WHEN o.payment IS NOT null AND o.payment != '' THEN 'Complete'
//                        WHEN o.DOI IS NOT NULL THEN 'Complete'
//                        WHEN COUNT(o.idOrders) = SUM(r.printAndTransmitted) THEN 'Pending'
//                        ELSE 'Processing'
//                    END AS `status`,//
//                    IF(ir.idinsuranceRules IS NOT NULL AND MAX(ir.doctorRequired) = 1,
//                        IF(o.doctorid IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `DoctorPresent`,
//                    IF(ir.idinsuranceRules IS NOT NULL AND MAX(ir.diagnosisRequired) = 1,
//                        IF(odl.idDiagnosisLookup IS NOT NULL AND COUNT(DISTINCT odl.idDiagnosisLookup) > 0, 'Present', 'Absent'), 'Not Required') AS `DiagnosisPresent`,
//                    IF(ir.idinsuranceRules IS NOT NULL AND MAX(ir.genderRequired) = 1,
//                        IF(p.sex IS NOT NULL AND p.sex NOT LIKE 'unknown%', 'Present', 'Absent'), 'Not Required') AS `GenderPresent`,
//                    IF(ir.idinsuranceRules IS NOT NULL AND MAX(ir.ageRequired) = 1,
//                        IF(p.dob IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `AgePresent`,
//                    IF(ir.idinsuranceRules IS NOT NULL AND MAX(ir.relationshipRequired) = 1,
//                        IF(p.relationship IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `RelationshipPresent`,
//                    IF(ir.idinsuranceRules IS NOT NULL AND MAX(ir.addressRequired) = 1,
//                        IF(p.addressStreet IS NOT NULL AND p.addressCity IS NOT NULL AND p.addressState IS NOT NULL AND p.addressZip IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `AddressPresent`,
//                    IF(ir.idinsuranceRules IS NOT NULL AND MAX(ir.locationRequired) = 1,
//                        IF(o.locationId IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `LocationPresent`,
//                    IF(ir.idinsuranceRules IS NOT NULL AND MAX(ir.subscriberRequired) = 1,
//                        IF(s.idsubscriber IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `SubscriberPresent`,
//                    IF(ir.idinsuranceRules IS NOT NULL AND MAX(ir.groupNumberRequired) = 1,
//                        IF(o.groupNumber IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `GroupNumberPresent`,
//                    IF(ir.idinsuranceRules IS NOT NULL AND MAX(ir.group2NumberRequired) = 1,
//                        IF(o.secondaryGroupNumber IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `SecondaryGroupNumberPresent`,
//                    IF(ir.idinsuranceRules IS NOT NULL AND MAX(ir.policyNumberRequired) = 1,
//                        IF(o.policyNumber IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `PolicyNumberPresent`,
//                    IF(ir.idinsuranceRules IS NOT NULL AND MAX(ir.policy2NumberRequired) = 1,
//                        IF(o.secondaryPolicyNumber IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `SecondaryPolicyNumberPresent`,
//                    IF(ir.idinsuranceRules IS NOT NULL AND MAX(ir.medicareNumberRequired) = 1,
//                        IF(o.medicareNumber IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `MedicareNumberPresent`,
//                    IF(ir.idinsuranceRules IS NOT NULL AND MAX(ir.medicaidNumberRequired) = 1,
//                        IF(o.medicaidNumber IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `MedicaidNumberPresent`,//
//                    CASE WHEN o.DOI IS NOT NULL THEN 'Paid' ELSE 'Unpaid' END AS `commissionStatus`,
//                    l.idLocation, l.locationNo,
//                    CASE WHEN l.idLocation != 1 THEN 'Hospital' ELSE l.locationName END AS `locationName`,
//                    i.idinsurances, i.number AS `insuranceNo`, i.name AS `insuranceName`,
//                    c.idClients, c.clientNo, c.clientName,
//                    d.iddoctors, d.number AS `doctorNo`, d.lastName AS `doctorLastName`, d.firstName AS `doctorFirstName`,
//                    p.idPatients, p.arNo, p.lastName AS `patientLastName`, p.firstName AS `patientFirstName`, p.middleName AS `patientMiddleName`,
//                    sa.idsalesmen, e.lastName AS `salesmanLastName`, e.firstName AS `salesmanFirstName`,
//                    sg.id AS `groupId`, sg.groupName,
//                    te.idterritory, te.territoryName,
//                    rt.idreportType, rt.number AS `reportTypeNo`, rt.name AS `reportTypeName`,
//                    CASE WHEN cpl.idPaymentLogs IS NOT NULL THEN 'paid' ELSE 'unpaid' END AS `paymentLogStatus`,
//                    o.orderDate, o.DOI AS `paymentDate`, o.specimenDate
//            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " sa ON c.salesmen = sa.idsalesmen
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sa.salesGroup = sg.id
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON sa.employeeID = e.idemployees
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " l ON o.locationId = l.idLocation
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_REPORTTYPE . " rt ON o.reportType = rt.idreportType
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TERRITORY . " te ON sa.territory = te.idterritory
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i ON o.insurance = i.idinsurances//
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i2 ON o.secondaryInsurance = i2.idinsurances
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_INSURANCERULES . " ir ON i.idinsurances = ir.idInsuranceRules
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_ORDERDIAGNOSISLOOKUP . " odl ON o.idOrders = odl.idOrders
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SUBSCRIBER . " s ON s.idSubscriber = o.subscriberId//
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr ON o.reportType = cr.reportTypeId AND sa.idsalesmen = cr.salesmanId
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONPAYMENTLOG . " cpl ON o.idOrders = cpl.orderId
//            $where
//            GROUP BY o.idOrders
//            ORDER BY o.DOI ASC";

//        $sql = "
//            SELECT 	DISTINCT o.idOrders, o.accession, do.iddetailOrders, dcc.iddetailCptCodes,
//                    SUM(CASE WHEN dcc.billAmount IS NOT NULL THEN dcc.billAmount ELSE 0 END) AS `billAmount`,
//			        SUM(CASE WHEN dcc.paid IS NOT NULL THEN dcc.paid ELSE 0 END) AS `payment`,
//			        x.CostBasis,
//			        s2.idsalesmen AS `groupLeaderSalesmanId`, e2.lastName AS `groupLeaderLastName`, e2.firstName AS `groupLeaderFirstName`,
//                    CASE
//                        WHEN cr2.idCommissions IS NOT NULL AND cr2.commissionRate > 0 AND SUM(dcc.paid) >= cr2.minPayment THEN
//                            CASE
//                                WHEN s2.byPercentage = 1 AND cr2.commissionRate > 0 THEN cr2.commissionRate/100
//                                ELSE cr2.commissionRate
//                            END
//                        WHEN (cr2.idCommissions IS NULL OR cr2.commissionRate <= 0) AND s2.commisionRate > 0 THEN
//                            CASE
//                                WHEN s2.byPercentage = 1 AND s2.commisionRate > 0 THEN (s2.commisionRate/100)
//                                ELSE s2.commisionRate
//                            END
//                        ELSE 0
//                    END AS `groupLeaderCommissionRate`,
//                    ROUND(CASE
//                        WHEN cr2.idCommissions IS NOT NULL AND cr2.commissionRate > 0  AND SUM(dcc.paid) >= cr2.minPayment AND SUM(dcc.paid) - x.CostBasis > 0 THEN
//                            CASE
//                                WHEN s2.byPercentage = 1 THEN ((SUM(dcc.paid) - x.CostBasis) * (cr2.commissionRate/100)) - ((SUM(dcc.paid) - x.CostBasis) * (cr.commissionRate/100))
//                                ELSE cr2.commissionRate
//                            END
//                        WHEN (cr2.idCommissions IS NULL OR cr2.commissionRate <= 0) AND s2.commisionRate > 0 AND SUM(dcc.paid) - x.CostBasis > 0 THEN
//                            CASE
//                                WHEN s2.byPercentage = 1 THEN ((SUM(dcc.paid) - x.CostBasis) * (s2.commisionRate/100)) - ((SUM(dcc.paid) - x.CostBasis) * (s.commisionRate/100))
//                                ELSE s2.commisionRate
//                            END
//                        ELSE 0
//                    END, 2) AS `groupLeaderCommission`,
//
//                    s.idsalesmen, e.lastName AS `salesmanLastName`, e.firstName AS `salesmanFirstName`,
//                    sg.id AS `groupId`, sg.groupName,
//                    CASE
//                        WHEN cr.idCommissions IS NOT NULL AND cr.commissionRate > 0 AND SUM(dcc.paid) >= cr.minPayment THEN
//                            CASE
//                                WHEN s.byPercentage = 1 AND cr.commissionRate > 0 THEN cr.commissionRate/100
//                                ELSE cr.commissionRate
//                            END
//                        WHEN (cr.idCommissions IS NULL OR cr.commissionRate <= 0) AND s.commisionRate > 0 THEN
//                            CASE
//                                WHEN s.byPercentage = 1 AND s.commisionRate > 0 THEN (s.commisionRate/100)
//                                ELSE s.commisionRate
//                            END
//                        ELSE 0
//                    END AS `commissionRate`,
//                    ROUND(CASE
//                        WHEN cr.idCommissions IS NOT NULL AND cr.commissionRate > 0  AND SUM(dcc.paid) >= cr.minPayment AND SUM(dcc.paid) - x.CostBasis > 0 THEN
//                            CASE
//                                WHEN s.byPercentage = 1 THEN ((SUM(dcc.paid) - x.CostBasis) * (cr.commissionRate/100))
//                                ELSE cr.commissionRate
//                            END
//                        WHEN (cr.idCommissions IS NULL OR cr.commissionRate <= 0) AND s.commisionRate > 0 AND SUM(dcc.paid) - x.CostBasis > 0 THEN
//                            CASE
//                                WHEN s.byPercentage = 1 THEN ((SUM(dcc.paid) - x.CostBasis) * (s.commisionRate/100))
//                                ELSE s.commisionRate
//                            END
//                        ELSE 0
//                    END, 2) AS `commission`,
//
//                    CASE
//                        WHEN SUM(dcc.paid) > 0 THEN 'Complete'
//                        WHEN x.OrderCount = x.TransmitCount THEN 'Pending'
//                        ELSE 'Processing'
//                    END AS `status`,
//                    x.OrderCount, x.TransmitCount,
//                    /*
//                    CASE
//                        WHEN x.idinsuranceRules IS NOT NULL AND (
//                            (x.doctorRequired = 1 AND o.doctorId IS NULL)
//                            OR (x.diagnosisRequired = 1 AND x.DiagnosisCount = 0)
//                            OR (x.genderRequired = 1 AND (p.sex IS NULL OR p.sex LIKE 'unknown%'))
//                            OR (x.ageRequired = 1 AND p.dob IS NULL)
//                            OR (x.relationshipRequired AND p.relationship IS NULL)
//                            OR (x.addressRequired = 1 AND (p.addressStreet IS NULL OR p.addressCity IS NULL OR p.addressState IS NULL OR p.addressZip IS NULL))
//                            OR (x.locationRequired = 1 AND o.locationId IS NULL)
//                            OR (x.subscriberRequired = 1 AND s.idSubscriber IS NULL)
//                            OR (x.groupNumberRequired = 1 AND o.groupNumber IS NULL)
//                            OR (x.group2NumberRequired = 1 AND o.secondaryGroupNumber IS NULL)
//                            OR (x.policyNumberRequired = 1 AND o.policyNumber IS NULL)
//                            OR (x.policy2NumberRequired = 1 AND o.secondaryPolicyNumber IS NULL)
//                            OR (x.medicareNumberRequired = 1 AND o.medicareNumber IS NULL)
//                            OR (x.medicaidNumberRequired = 1 AND o.medicaidNumber IS NULL)
//                        ) THEN 'Rejected'
//                        WHEN SUM(dcc.paid) > 0 THEN 'Complete'
//                        WHEN x.OrderCount = x.TransmitCount THEN 'Pending'
//                        ELSE 'Processing'
//                    END AS `status`,
//                    x.OrderCount,
//                    x.TransmitCount,
//                    IF(x.idinsuranceRules IS NOT NULL AND x.doctorRequired = 1,
//                        IF(o.doctorid IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `DoctorPresent`,
//                    IF(x.idinsuranceRules IS NOT NULL AND x.diagnosisRequired = 1,
//                        IF(x.DiagnosisCount > 0, 'Present', 'Absent'), 'Not Required') AS `DiagnosisPresent`,
//                    IF(x.idinsuranceRules IS NOT NULL AND x.genderRequired = 1,
//                        IF(p.sex IS NOT NULL AND p.sex NOT LIKE 'unknown%', 'Present', 'Absent'), 'Not Required') AS `GenderPresent`,
//                    IF(x.idinsuranceRules IS NOT NULL AND x.ageRequired = 1,
//                        IF(p.dob IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `AgePresent`,
//                    IF(x.idinsuranceRules IS NOT NULL AND x.relationshipRequired = 1,
//                        IF(p.relationship IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `RelationshipPresent`,
//                    IF(x.idinsuranceRules IS NOT NULL AND x.addressRequired = 1,
//                        IF(p.addressStreet IS NOT NULL AND p.addressCity IS NOT NULL AND p.addressState IS NOT NULL AND p.addressZip IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `AddressPresent`,
//                    IF(x.idinsuranceRules IS NOT NULL AND x.locationRequired = 1,
//                        IF(o.locationId IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `LocationPresent`,
//                    IF(x.idinsuranceRules IS NOT NULL AND x.subscriberRequired = 1,
//                        IF(s.idsubscriber IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `SubscriberPresent`,
//                    IF(x.idinsuranceRules IS NOT NULL AND x.groupNumberRequired = 1,
//                        IF(o.groupNumber IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `GroupNumberPresent`,
//                    IF(x.idinsuranceRules IS NOT NULL AND x.group2NumberRequired = 1,
//                        IF(o.secondaryGroupNumber IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `SecondaryGroupNumberPresent`,
//                    IF(x.idinsuranceRules IS NOT NULL AND x.policyNumberRequired = 1,
//                        IF(o.policyNumber IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `PolicyNumberPresent`,
//                    IF(x.idinsuranceRules IS NOT NULL AND x.policy2NumberRequired = 1,
//                        IF(o.secondaryPolicyNumber IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `SecondaryPolicyNumberPresent`,
//                    IF(x.idinsuranceRules IS NOT NULL AND x.medicareNumberRequired = 1,
//                        IF(o.medicareNumber IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `MedicareNumberPresent`,
//                    IF(x.idinsuranceRules IS NOT NULL AND x.medicaidNumberRequired = 1,
//                        IF(o.medicaidNumber IS NOT NULL, 'Present', 'Absent'), 'Not Required') AS `MedicaidNumberPresent`,
//                    */
//                    /*CASE WHEN o.DOI IS NOT NULL THEN 'Paid' ELSE 'Unpaid' END AS `commissionStatus`,	*/
//                    x.idDepartment, x.deptNo, x.deptName,
//                    l.idLocation, l.locationNo, l.locationName,
//                    i.idinsurances, i.number AS `insuranceNo`, i.name AS `insuranceName`,
//                    c.idClients, c.clientNo, c.clientName,
//                    d.iddoctors, d.number AS `doctorNo`, d.lastName AS `doctorLastName`, d.firstName AS `doctorFirstName`,
//                    #p.idPatients, p.arNo, p.lastName AS `patientLastName`, p.firstName AS `patientFirstName`, p.middleName AS `patientMiddleName`,
//                    te.idterritory, te.territoryName,
//                    rt.idreportType, rt.number AS `reportTypeNo`, rt.name AS `reportTypeName`,
//                    CASE WHEN cpl.idPaymentLogs IS NOT NULL THEN 'paid' ELSE 'unpaid' END AS `paymentLogStatus`,
//                    o.orderDate,
//                    do.lastPaymentDate AS `paymentDate`,
//                    o.specimenDate
//            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
//            INNER JOIN (
//                SELECT	o.idOrders,
//                    COUNT(DISTINCT r.idResults) AS `OrderCount`,
//                    COUNT(DISTINCT CASE WHEN r.printAndTransmitted THEN r.idResults ELSE NULL END) AS `TransmitCount`,
//                    d.idDepartment, d.deptNo, d.deptName,
//                    SUM(CASE
//                        WHEN r.panelId IS NULL THEN t.cost
//                        ELSE 0
//                    END) AS `CostBasis`
//                FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
//                INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
//                INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests
//                INNER JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTS . " d ON t.department = d.idDepartment
//                WHERE $dateField BETWEEN ? AND ?
//                    AND r.isInvalidated = false
//                GROUP BY o.idOrders
//            ) x ON o.idOrders = x.idOrders
//
//
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON c.salesmen = s.idsalesmen
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON s.idsalesmen = sgl.salesmanId
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " l ON o.locationId = l.idLocation
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_REPORTTYPE . " rt ON o.reportType = rt.idreportType
//
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s2 ON sg.groupLeader = s2.idsalesmen
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr2 ON s2.idsalesmen = cr2.salesmanId AND o.reportType = cr2.reportTypeId
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e2 ON s2.employeeID = e2.idemployees
//
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TERRITORY . " te ON s.territory = te.idterritory
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i ON o.insurance = i.idinsurances
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i2 ON o.secondaryInsurance = i2.idinsurances
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr ON s.idsalesmen = cr.salesmanId AND o.reportType = cr.reportTypeId
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONPAYMENTLOG . " cpl ON o.idOrders = cpl.orderId
//
//            LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILORDERS . " do ON o.idOrders = do.orderId AND do.active = true
//            LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILCPTCODES . " dcc ON do.iddetailOrders = dcc.detailOrderId AND dcc.transferredTo IS NULL
//            $where
//            GROUP BY o.idOrders
//            ORDER BY o.idOrders ASC";


        $sql = "
            SELECT 	DISTINCT o.idOrders, o.accession, do.iddetailOrders, dcc.iddetailCptCodes,
                    SUM(CASE WHEN dcc.billAmount IS NOT NULL THEN dcc.billAmount ELSE 0 END) AS `billAmount`,
			        SUM(CASE WHEN dcc.paid IS NOT NULL THEN dcc.paid ELSE 0 END) AS `payment`,
			        x.idCostTypes, x.costType,
                    CASE
                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND dcc.paid IS NOT NULL THEN ROUND(SUM(dcc.paid) * (x.cost / 100), 2)
                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND dcc.paid IS NULL THEN 0.00
                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Fixed Cost' THEN x.cost
                        ELSE x.CostBasis
                    END AS `CostBasis`,
			        s2.idsalesmen AS `groupLeaderSalesmanId`, e2.lastName AS `groupLeaderLastName`, e2.firstName AS `groupLeaderFirstName`,
                    CASE 
                        WHEN cr2.idCommissions IS NOT NULL AND cr2.commissionRate > 0 AND SUM(dcc.paid) >= cr2.minPayment THEN 
                            CASE
                                WHEN s2.byPercentage = 1 AND cr2.commissionRate > 0 THEN cr2.commissionRate/100
                                ELSE cr2.commissionRate
                            END
                        WHEN (cr2.idCommissions IS NULL OR cr2.commissionRate <= 0) AND s2.commisionRate > 0 THEN
                            CASE
                                WHEN s2.byPercentage = 1 AND s2.commisionRate > 0 THEN (s2.commisionRate/100)
                                ELSE s2.commisionRate
                            END            
                        ELSE 0
                    END AS `groupLeaderCommissionRate`,        
                    ROUND(CASE
                        WHEN cr2.idCommissions IS NOT NULL AND cr2.commissionRate > 0  AND SUM(dcc.paid) >= cr2.minPayment AND (
                            (x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0) # department cost basis
                            OR (SUM(dcc.paid) - x.CostBasis > 0)
                        ) THEN 
                            CASE
                                WHEN s2.byPercentage = 1 THEN 
                                    CASE
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0 THEN # department cost basis by percentage
                                            ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (cr2.commissionRate/100)) - ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (cr.commissionRate/100))
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Fixed Cost' THEN # department cost basis by fixed cost
                                            ((SUM(dcc.paid) - x.cost) * (cr2.commissionRate/100)) - ((SUM(dcc.paid) - x.cost) * (cr.commissionRate/100))
                                        ELSE ((SUM(dcc.paid) - x.CostBasis) * (cr2.commissionRate/100)) - ((SUM(dcc.paid) - x.CostBasis) * (cr.commissionRate/100))
                                    END
                                ELSE cr2.commissionRate
                            END
                        WHEN (cr2.idCommissions IS NULL OR cr2.commissionRate <= 0) AND s2.commisionRate > 0 AND (
                            (x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0) # department cost basis
                            OR (SUM(dcc.paid) - x.CostBasis > 0)
                        ) THEN
                            CASE
                                WHEN s2.byPercentage = 1 THEN 
                                    CASE
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0 THEN # department cost basis by percentage
                                            ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (s2.commisionRate/100)) - ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (s.commisionRate/100))
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Fixed Cost' THEN # department cost basis by fixed cost
                                            ((SUM(dcc.paid) - x.cost) * (s2.commisionRate/100)) - ((SUM(dcc.paid) - x.cost) * (s.commisionRate/100))
                                        ELSE ((SUM(dcc.paid) - x.CostBasis) * (s2.commisionRate/100)) - ((SUM(dcc.paid) - x.CostBasis) * (s.commisionRate/100))
                                    END
                                ELSE s2.commisionRate
                            END            
                        ELSE 0
                    END, 2) AS `groupLeaderCommission`, 
                    
                    s.idsalesmen, e.lastName AS `salesmanLastName`, e.firstName AS `salesmanFirstName`,
                    sg.id AS `groupId`, sg.groupName,
                    CASE 
                        WHEN cr.idCommissions IS NOT NULL AND cr.commissionRate > 0 AND SUM(dcc.paid) >= cr.minPayment THEN 
                            CASE
                                WHEN s.byPercentage = 1 AND cr.commissionRate > 0 THEN cr.commissionRate/100
                                ELSE cr.commissionRate
                            END
                        WHEN (cr.idCommissions IS NULL OR cr.commissionRate <= 0) AND s.commisionRate > 0 THEN
                            CASE
                                WHEN s.byPercentage = 1 AND s.commisionRate > 0 THEN (s.commisionRate/100)
                                ELSE s.commisionRate
                            END            
                        ELSE 0
                    END AS `commissionRate`,
                    ROUND(CASE
                        WHEN cr.idCommissions IS NOT NULL AND cr.commissionRate > 0  AND SUM(dcc.paid) >= cr.minPayment AND (
                            (x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0) # department cost basis
                            OR (SUM(dcc.paid) - x.CostBasis > 0)
                        ) THEN 
                            CASE
                                WHEN s.byPercentage = 1 THEN 
                                    CASE
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0 THEN # department cost basis by percentage
                                            ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (cr.commissionRate/100))
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Fixed Cost' THEN # department cost basis by fixed cost
                                            ((SUM(dcc.paid) - x.cost) * (cr.commissionRate/100))
                                        ELSE ((SUM(dcc.paid) - x.CostBasis) * (cr.commissionRate/100))
                                    END
                                    
                                
                                ELSE cr.commissionRate
                            END
                        WHEN (cr.idCommissions IS NULL OR cr.commissionRate <= 0) AND s.commisionRate > 0 AND (
                            (x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0) # department cost basis
                            OR (SUM(dcc.paid) - x.CostBasis > 0)
                        ) THEN
                            CASE
                                WHEN s.byPercentage = 1 THEN 
                                    CASE
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0 THEN # department cost basis by percentage
                                            ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (s.commisionRate/100))
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Fixed Cost' THEN # department cost basis by fixed cost
                                            ((SUM(dcc.paid) - x.cost) * (s.commisionRate/100))
                                        ELSE ((SUM(dcc.paid) - x.CostBasis) * (s.commisionRate/100))
                                    END
                                    
                                ELSE s.commisionRate
                            END            
                        ELSE 0
                    END, 2) AS `commission`,
                    
                    CASE
                        WHEN SUM(dcc.paid) > 0 THEN 'Complete'
                        WHEN x.OrderCount = x.TransmitCount THEN 'Pending'
                        ELSE 'Processing'
                    END AS `status`,      
                    x.OrderCount, x.TransmitCount,
                    	
                    x.idDepartment, x.deptNo, x.deptName, 
                    l.idLocation, l.locationNo, l.locationName,
                    i.idinsurances, i.number AS `insuranceNo`, i.name AS `insuranceName`,
                    c.idClients, c.clientNo, c.clientName,
                    d.iddoctors, d.number AS `doctorNo`, d.lastName AS `doctorLastName`, d.firstName AS `doctorFirstName`,
                    te.idterritory, te.territoryName,
                    rt.idreportType, rt.number AS `reportTypeNo`, rt.name AS `reportTypeName`,
                    CASE WHEN cpl.idPaymentLogs IS NOT NULL THEN 'paid' ELSE 'unpaid' END AS `paymentLogStatus`,
                    o.orderDate, 
                    do.lastPaymentDate AS `paymentDate`,
                    o.specimenDate
            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            INNER JOIN (
                SELECT	o.idOrders,    
                    COUNT(DISTINCT r.idResults) AS `OrderCount`,
                    COUNT(DISTINCT CASE WHEN r.printAndTransmitted THEN r.idResults ELSE NULL END) AS `TransmitCount`,
                    d.idDepartment, d.deptNo, d.deptName,
                    SUM(CASE
                        WHEN r.panelId IS NULL THEN t.cost
                        ELSE 0
                    END) AS `CostBasis`,
                    dc.idCosts, dc.cost,
                    ct.idCostTypes, ct.costType
                FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
                INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests
                INNER JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTS . " d ON t.department = d.idDepartment
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTCOSTS . " dc ON d.idDepartment = dc.departmentId
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_COSTTYPES . " ct ON dc.costTypeId = ct.idCostTypes
                WHERE $dateField BETWEEN ? AND ?
                    AND r.isInvalidated = false
                GROUP BY o.idOrders		
            ) x ON o.idOrders = x.idOrders
            
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON c.salesmen = s.idsalesmen
            
            INNER JOIN (
                SELECT *
                FROM " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id
                GROUP BY salesmanId
            ) sg ON s.idsalesmen = sg.salesmanId
            
            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
            INNER JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " l ON o.locationId = l.idLocation
            INNER JOIN " . self::DB_CSS . "." . self::TBL_REPORTTYPE . " rt ON o.reportType = rt.idreportType
            
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s2 ON sg.groupLeader = s2.idsalesmen
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr2 ON s2.idsalesmen = cr2.salesmanId AND o.reportType = cr2.reportTypeId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e2 ON s2.employeeID = e2.idemployees
                        
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TERRITORY . " te ON s.territory = te.idterritory
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i ON o.insurance = i.idinsurances
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i2 ON o.secondaryInsurance = i2.idinsurances
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr ON s.idsalesmen = cr.salesmanId AND o.reportType = cr.reportTypeId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONPAYMENTLOG . " cpl ON o.idOrders = cpl.orderId
          
            LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILORDERS . " do ON o.idOrders = do.orderId AND do.active = true
            LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILCPTCODES . " dcc ON do.iddetailOrders = dcc.detailOrderId AND dcc.transferredTo IS NULL       
            $where             
            GROUP BY o.idOrders
            ORDER BY o.idOrders ASC";

        //INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON s.idsalesmen = sgl.salesmanId
        //INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id

        /*error_log($sql);
        error_log(implode($aryInput, ","));*/

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $aryReturnData = array();
        if (count($data) > 0) {
            $arySalesGroups = array();
            if ($input['idsalesmen'] == "null") {
                // admin/manager - get all sales groups with group leaders and their commission rates
                $sql = "SELECT sg.id, sg.groupName, sg.groupLeader, 
                    e.firstName AS `leaderFirstName`, e.lastName AS `leaderLastName`, s.commisionRate AS `leaderCommissionRate`,
                    s2.idsalesmen, e2.firstName AS `salesmanFirstName`, e2.lastName AS `salesmanLastName`, s2.commisionRate AS `salesmanCommissionRate`
                FROM " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON sg.groupLeader = s.idsalesmen
                INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON sg.id = sgl.salesGroupId
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s2 ON sgl.salesmanId = s2.idsalesmen
                INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e2 ON s2.employeeID = e2.idemployees
                WHERE s.idsalesmen != s2.idsalesmen
                ORDER BY sg.id ASC";

                $data2 = parent::select($sql, null, array("Conn" => $this->Conn));

                foreach ($data2 AS $row) {
                    $groupId = $row['id'];
                    $groupName = $row['groupName'];
                    $groupLeaderId = $row['groupLeader'];
                    $leaderFirstName = $row['leaderFirstName'];
                    $leaderLastName = $row['leaderLastName'];
                    $leaderCommissionRate = $row['leaderCommissionRate'];
                    $salesmanId = $row['idsalesmen'];
                    $salesmanFirstName = $row['salesmanFirstName'];
                    $salesmanLastName = $row['salesmanLastName'];
                    $salesmanCommissionRate = $row['salesmanCommissionRate'];

                    if (!array_key_exists($groupId, $arySalesGroups)) {
                        $arySalesGroups[$groupId] = array(
                            "groupId" => $groupId,
                            "groupName" => $groupName,
                            "groupLeaderId" => $groupLeaderId,
                            "leaderFirstName" => $leaderFirstName,
                            "leaderLastName" => $leaderLastName,
                            "leaderCommissionRate" => $leaderCommissionRate,
                            "groupMembers" => array($salesmanId)
                        );
                    } else {
                        $arySalesGroups[$groupId]['groupMembers'][] = $salesmanId;
                    }
                }
            }

            //echo "<pre>"; print_r($arySalesGroups); echo "</pre>";

            foreach ($data as $row) {
                $idOrders = $row['idOrders'];
                $accession = $row['accession'];
                $payment = $row['payment'];
                $commission = $row['commission'];
                $commissionRate = $row['commissionRate'];
                $locationName = $row['locationName'];
                $insuranceName = $row['insuranceName'];
                $doctorName = $row['doctorFirstName'] . " " . $row['doctorLastName'];
                //$arNo = $row['arNo'];
                //$patientName = $row['patientFirstName'] . " " . $row['patientLastName'];
                $orderDate = $row['orderDate'];
                $paymentDate = $row['paymentDate'];
                $status = $row['status'];
                //$commissionStatus = $row['commissionStatus'];

                $salesmanName = $row['salesmanFirstName'] . " " . $row['salesmanLastName'];
                $groupName = $row['groupName'];

                $idClients = $row['idClients'];
                $clientNo = $row['clientNo'];
                $clientName = $row['clientName'];

                $reportType = $row['reportTypeName'];
                $territoryName = $row['territoryName'];

                $specimenDate = $row['specimenDate'];

                $idinsurances = $row['idinsurances'];
                $idLocation = $row['idLocation'];
                $iddoctors = $row['iddoctors'];

                $paymentLogStatus = $row['paymentLogStatus'];

                $missingBillingInfo = false;
                $missingBillingFields = "";

                $deptName = $row['deptName'];

                /*$doctorPresent = $row['DoctorPresent'];
                $diagnosisPresent = $row['DiagnosisPresent'];
                $genderPresent = $row['GenderPresent'];
                $agePresent = $row['AgePresent'];
                $relationshipPresent = $row['RelationshipPresent'];
                $addressPresent = $row['AddressPresent'];
                $locationPresent = $row['LocationPresent'];
                $subscriberPresent = $row['SubscriberPresent'];
                $groupNumberPresent = $row['GroupNumberPresent'];
                $secondaryGroupNumberPresent = $row['SecondaryGroupNumberPresent'];
                $policyNumberPresent = $row['PolicyNumberPresent'];
                $secondaryPolicyNumberPresent = $row['SecondaryPolicyNumberPresent'];
                $medicareNumberPresent = $row['MedicareNumberPresent'];
                $medicaidNumberPresent = $row['MedicaidNumberPresent'];

                if ($doctorPresent === 'Absent') {
                    $missingBillingInfo = true;
                    $missingBillingFields = "Doctor, ";
                }
                if ($diagnosisPresent === 'Absent') {
                    $missingBillingInfo = true;
                    $missingBillingFields .= "Diagnosis Codes, ";
                }
                if ($genderPresent === 'Absent') {
                    $missingBillingInfo = true;
                    $missingBillingFields .= "Patient Gender, ";
                }
                if ($agePresent === 'Absent') {
                    $missingBillingInfo = true;
                    $missingBillingFields .= "Patient Age, ";
                }
                if ($relationshipPresent === 'Absent') {
                    $missingBillingInfo = true;
                    $missingBillingFields .= "Patient Relationship, ";
                }
                if ($addressPresent === 'Absent') {
                    $missingBillingInfo = true;
                    $missingBillingFields .= "Patient Address, ";
                }
                if ($locationPresent === 'Absent') {
                    $missingBillingInfo = true;
                    $missingBillingFields .= "Location, ";
                }
                if ($subscriberPresent === 'Absent') {
                    $missingBillingInfo = true;
                    $missingBillingFields .= "Subscriber, ";
                }
                if ($groupNumberPresent === 'Absent') {
                    $missingBillingInfo = true;
                    $missingBillingFields .= "Group Number, ";
                }
                if ($secondaryGroupNumberPresent === 'Absent') {
                    $missingBillingInfo = true;
                    $missingBillingFields .= "Secondary Group Number, ";
                }
                if ($policyNumberPresent === 'Absent') {
                    $missingBillingInfo = true;
                    $missingBillingFields .= "Policy Number, ";
                }
                if ($secondaryPolicyNumberPresent === 'Absent') {
                    $missingBillingInfo = true;
                    $missingBillingFields .= "Secondary Policy Number, ";
                }
                if ($medicareNumberPresent === 'Absent') {
                    $missingBillingInfo = true;
                    $missingBillingFields .= "Medicare Number, ";
                }
                if ($medicaidNumberPresent === 'Absent') {
                    $missingBillingInfo = true;
                    $missingBillingFields .= "Medicaid Number, ";
                }

                if ($missingBillingInfo) {
                    $missingBillingFields = substr($missingBillingFields, 0, strlen($missingBillingFields) - 2);
                }*/

                $costBasis = $row['CostBasis'];

                $groupLeaderCommissionRate = $row['groupLeaderCommissionRate'];
                $groupLeaderCommission = $row['groupLeaderCommission'];

                $territoryLeaderCommission = 0;
                $territoryLeaderCommissionRate = 0;
                if (!empty($input['subSalesGroupIds']) && $row['groupLeaderSalesmanId'] != $input['idsalesmen']) {
                    // there are sub-sales-groups, so this is a territory leader
                    $territoryLeaderCommissionRate = $input['commissionRate'] / 100;
                    if ($payment != null && $payment > 0 && $territoryLeaderCommissionRate != null && is_numeric($territoryLeaderCommissionRate) && $territoryLeaderCommissionRate > 0 && ($payment - $costBasis) > 0) {
                        if ($groupLeaderCommission > 0) {
                            $territoryLeaderCommission = (($payment - $costBasis) * $territoryLeaderCommissionRate) - $groupLeaderCommission;
                        } else {
                            $territoryLeaderCommission = (($payment - $costBasis) * $territoryLeaderCommissionRate);
                        }

                    }

                }
                if ($row['groupLeaderSalesmanId'] == $row['idsalesmen']) {
                    $groupLeaderCommission = 0;
                }

                $hasTerritoryLeader = false;
                $territoryGroupId = 0;
                if ($input['idsalesmen'] == "null") {
                    // admin/manager
                    foreach ($arySalesGroups as $groupId => $arySalesGroup) {
                        if (in_array($row['groupLeaderSalesmanId'], $arySalesGroup['groupMembers'])) {
                            $hasTerritoryLeader = true;
                            $territoryGroupId = $groupId;
                            $territoryLeaderCommissionRate = $arySalesGroup['leaderCommissionRate'] / 100;
                            if ($groupLeaderCommission > 0) {
                                $territoryLeaderCommission = (($payment - $costBasis) * $territoryLeaderCommissionRate) - $groupLeaderCommission;

                            } else {
                                $territoryLeaderCommission = (($payment - $costBasis) * $territoryLeaderCommissionRate);
                            }
                        }
                    }
                    if ($territoryLeaderCommission < 0) {
                        $territoryLeaderCommission = 0;
                    }
                }


                $aryReturnData[] = array(
                    "idOrders" => $idOrders,
                    "accession" => $accession,
                    "payment" => $payment,
                    "commission" => $commission,
                    "commissionRate" => $commissionRate,
                    "salesmanName" => $salesmanName,
                    "territoryName" => $territoryName,
                    "groupName" => $groupName,
                    "clientName" => $clientName,
                    "locationName" => $locationName,
                    "insuranceName" => $insuranceName,
                    "doctorName" => $doctorName,
                    //"patientNumber" => $arNo,
                    "status" => $status,
                    //"commissionStatus" => $commissionStatus,
                    "reportType" => $reportType,
                    "orderDate" => $orderDate,
                    "specimenDate" => $specimenDate,
                    "paymentDate" => $paymentDate,
                    "clientId" => $idClients,
                    "insuranceId" => $idinsurances,
                    "locationId" => $idLocation,
                    "doctorId" => $iddoctors,
                    "paymentLogStatus" => $paymentLogStatus,
                    "missingBillingInfo" => $missingBillingInfo,
                    "missingBillingFields" => $missingBillingFields,
                    "deptName" => $deptName,
                    'groupLeaderCommissionRate' => $groupLeaderCommissionRate,
                    'groupLeaderCommission' => $groupLeaderCommission,
                    'territoryLeaderCommission' => $territoryLeaderCommission,
                    'territoryLeaderCommissionRate' => $territoryLeaderCommissionRate,
                    'territoryGroupId' => $territoryGroupId,
                    'costBasis' => $costBasis
                );
            }
        }

        return $this->utf8ize($aryReturnData);
    }

    public function getSalesTableData(array $input) {
        $dateField = $this->getDateSearchField($input);

        $clientIds = $input['clientIds'];
        $payorIds = $input['payorIds'];
        $locationIds = $input['locationIds'];
        $doctorIds = $input['doctorIds'];
        $reportTypes = $input['reportTypes'];
        $statuses = $input['statuses'];

        $aryWhere = $this->getWhere($input);
        $where = $aryWhere['where'];
        $aryInput = array($input['dateFrom'], $input['dateTo']);
        $aryInput = array_merge($aryInput, $aryWhere['input']);

        $having = "";

        /*$where = "WHERE   $dateField BETWEEN ? AND ?
            AND o.active = true ";

        $aryInput = array($dateFrom, $dateTo);

        if (isset($groupId) && !empty($groupId) && $groupId != "null") {
            $where .= "AND sg.id = ? ";
            $aryInput[] = $groupId;
        }

        if (isset($idsalesmen) && !empty($idsalesmen) && $idsalesmen != "null") {
            $where .= "AND (sg.groupLeader = ? OR s.idsalesmen = ?) ";
            $aryInput[] = $idsalesmen;
            $aryInput[] = $idsalesmen;
        }*/

        if (!empty($clientIds)) {
            $where .= "AND c.clientNo IN (";
            $aryClientIds = explode(',', $clientIds);
            foreach ($aryClientIds as $currClientId) {
                $where .= "?,";
                $aryInput[] = $currClientId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }
        if (!empty($payorIds)) {
            $where .= "AND i.idinsurances IN (";
            $aryPayorIds = explode(',', $payorIds);
            foreach ($aryPayorIds as $currPayorId) {
                $where .= "?,";
                $aryInput[] = $currPayorId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }
        if (!empty($locationIds)) {
            $where .= "AND l.idLocation IN (";
            $aryLocationIds = explode(',', $locationIds);
            foreach ($aryLocationIds as $currLocationId) {
                $where .= "?,";
                $aryInput[] = $currLocationId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }
        if (!empty($doctorIds)) {
            $where .= "AND d.iddoctors IN (";
            $aryDoctorIds = explode(',', $doctorIds);
            foreach ($aryDoctorIds as $currDoctorId) {
                $where .= "?,";
                $aryInput[] = $currDoctorId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }
        if (!empty($reportTypes)) {
            //$where .= "AND rt.name IN (";
            $where .= "AND x.deptName IN (";
            $aryReportTypeNames = explode(',', $reportTypes);
            foreach ($aryReportTypeNames as $currReportTypeName) {
                $where .= "?,";
                $aryInput[] = $currReportTypeName;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }
        if (array_key_exists("selectedGroupMembers", $input) && !empty($input['selectedGroupMembers'])) {
            $arySelectedGroupMembers = explode(",", $input['selectedGroupMembers']);
            $where .= "AND s.idsalesmen IN (";
            foreach ($arySelectedGroupMembers as $salesmanId) {
                $where .= "?,";
                $aryInput[] = $salesmanId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }

        if (array_key_exists("selectedSalesGroups", $input) && !empty($input['selectedSalesGroups'])) {
            $arySelectedSalesGroups = explode(",", $input['selectedSalesGroups']);
            $where .= "AND sg.id IN (";
            foreach ($arySelectedSalesGroups as $groupId) {
                $where .= "?,";
                $aryInput[] = $groupId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }

        if (!empty($statuses)) {
            $aryStatuses = explode(',', $statuses);
            if (in_array('Information Needed', $aryStatuses) && count($aryStatuses) == 1) { // 'Information Needed' is only selected status
                $having = "HAVING status LIKE 'Information Needed%' ";

            } else if (in_array('Information Needed', $aryStatuses)) { // multiple statuses selected including 'Information Needed'
                $having = "HAVING status LIKE 'Information Needed%' OR status IN(";

                foreach ($aryStatuses as $currStatus) {
                    if ($currStatus !== 'Information Needed') {
                        $having .=  "?, ";
                        $aryInput[] = $currStatus;
                    }
                }
                $having = substr($having, 0, strlen($having) - 2) . ") ";
            } else { // multiple statuses selected excluding 'Information Needed'
                $having = "HAVING status IN (";

                foreach ($aryStatuses as $currStatus) {
                    $having .=  "?, ";
                    $aryInput[] = $currStatus;
                }
                $having = substr($having, 0, strlen($having) - 2) . ") ";
            }
        }

//        $sql = "
//            SELECT 	o.idOrders, o.accession,
//                    o.payment,
//
//                    CASE
//                      WHEN cr.idCommissions IS NOT NULL AND o.payment IS NOT NULL AND o.payment > cr.commissionRate THEN cr.commissionRate
//                      ELSE 0
//                    END AS `commission`,
//                    cr.commissionRate,
//                    CASE WHEN o.DOI IS NOT NULL THEN 'Paid' ELSE 'Unpaid' END AS `commissionStatus`,
//                    CASE
//                      WHEN FIND_IN_SET(490, GROUP_CONCAT(t.number)) != 0 AND o.payment IS NOT NULL AND o.payment BETWEEN 0.01 AND 0.99 THEN CONCAT('Information Needed: ', o.payment)
//                        WHEN FIND_IN_SET(490, GROUP_CONCAT(t.number)) != 0 THEN 'Rejected'
//                        #WHEN o.payment IS NOT null AND o.payment != '' THEN 'Complete'
//                        WHEN o.DOI IS NOT NULL THEN 'Complete'
//                        WHEN COUNT(o.idOrders) = SUM(r.printAndTransmitted) THEN 'Pending'
//                        ELSE 'Processing'
//                    END AS `status`,
//                    l.idLocation, l.locationNo,
//                    CASE WHEN l.idLocation != 1 THEN 'Hospital' ELSE l.locationName END AS `locationName`,
//                    i.idinsurances, i.number AS `insuranceNo`, i.name AS `insuranceName`,
//                    c.idClients, c.clientNo, c.clientName,
//                    d.iddoctors, d.number AS `doctorNo`, d.lastName AS `doctorLastName`, d.firstName AS `doctorFirstName`,
//                    p.idPatients, p.arNo, p.lastName AS `patientLastName`, p.firstName AS `patientFirstName`, p.middleName AS `patientMiddleName`,
//                    s.idsalesmen, e.lastName AS `salesmanLastName`, e.firstName AS `salesmanFirstName`,
//                    sg.id AS `groupId`, sg.groupName,
//                    te.idterritory, te.territoryName,
//                    rt.idreportType, rt.number AS `reportTypeNo`, rt.name AS `reportTypeName`,
//                    o.orderDate, o.DOI AS `paymentDate`, o.specimenDate
//            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON c.salesmen = s.idsalesmen
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON s.salesGroup = sg.id
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " l ON o.locationId = l.idLocation
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_REPORTTYPE . " rt ON o.reportType = rt.idreportType
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TERRITORY . " te ON s.territory = te.idterritory
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i ON o.insurance = i.idinsurances
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr ON o.reportType = cr.reportTypeId AND s.idsalesmen = cr.salesmanId
//            /*
//            LEFT JOIN (
//                SELECT r.orderId, t.idtests
//                FROM " . self::DB_CSS . "." . self::TBL_RESULTS . " r
//                INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests AND t.number = 490
//                GROUP BY r.orderId
//            ) rt ON o.idOrders = rt.orderId
//            */
//            $where
//            GROUP BY o.idOrders
//            $having
//            ORDER BY o.DOI ASC";

        $sql = "
            SELECT 	DISTINCT o.idOrders, o.accession, do.iddetailOrders, dcc.iddetailCptCodes,
                    
                    SUM(CASE WHEN dcc.billAmount IS NOT NULL THEN dcc.billAmount ELSE 0 END) AS `billAmount`,
                    SUM(CASE WHEN dcc.paid IS NOT NULL THEN dcc.paid ELSE 0 END) AS `payment`,
                    x.idCostTypes, x.costType,
                    CASE
                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND dcc.paid IS NOT NULL THEN ROUND(SUM(dcc.paid) * (x.cost / 100), 2)
                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND dcc.paid IS NULL THEN 0.00
                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Fixed Cost' THEN x.cost
                        ELSE x.CostBasis
                    END AS `CostBasis`,
                    s2.idsalesmen AS `groupLeaderSalesmanId`, e2.lastName AS `groupLeaderLastName`, e2.firstName AS `groupLeaderFirstName`,
                    CASE 
                        WHEN cr2.idCommissions IS NOT NULL AND cr2.commissionRate > 0 AND SUM(dcc.paid) >= cr2.minPayment THEN 
                            CASE
                                WHEN s2.byPercentage = 1 AND cr2.commissionRate > 0 THEN cr2.commissionRate/100
                                ELSE cr2.commissionRate
                            END
                        WHEN (cr2.idCommissions IS NULL OR cr2.commissionRate <= 0) AND s2.commisionRate > 0 THEN
                            CASE
                                WHEN s2.byPercentage = 1 AND s2.commisionRate > 0 THEN (s2.commisionRate/100)
                                ELSE s2.commisionRate
                            END            
                        ELSE 0
                    END AS `groupLeaderCommissionRate`,        
                    ROUND(CASE
                        WHEN cr2.idCommissions IS NOT NULL AND cr2.commissionRate > 0  AND SUM(dcc.paid) >= cr2.minPayment AND (
                            (x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0) # department cost basis
                            OR (SUM(dcc.paid) - x.CostBasis > 0)
                        ) THEN 
                            CASE
                                WHEN s2.byPercentage = 1 THEN 
                                    CASE
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0 THEN # department cost basis by percentage
                                            ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (cr2.commissionRate/100)) - ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (cr.commissionRate/100))
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Fixed Cost' THEN # department cost basis by fixed cost
                                            ((SUM(dcc.paid) - x.cost) * (cr2.commissionRate/100)) - ((SUM(dcc.paid) - x.cost) * (cr.commissionRate/100))
                                        ELSE ((SUM(dcc.paid) - x.CostBasis) * (cr2.commissionRate/100)) - ((SUM(dcc.paid) - x.CostBasis) * (cr.commissionRate/100))
                                    END
                                ELSE cr2.commissionRate
                            END
                        WHEN (cr2.idCommissions IS NULL OR cr2.commissionRate <= 0) AND s2.commisionRate > 0 AND (
                            (x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0) # department cost basis
                            OR (SUM(dcc.paid) - x.CostBasis > 0)
                        ) THEN
                            CASE
                                WHEN s2.byPercentage = 1 THEN 
                                    CASE
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0 THEN # department cost basis by percentage
                                            ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (s2.commisionRate/100)) - ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (s.commisionRate/100))
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Fixed Cost' THEN # department cost basis by fixed cost
                                            ((SUM(dcc.paid) - x.cost) * (s2.commisionRate/100)) - ((SUM(dcc.paid) - x.cost) * (s.commisionRate/100))
                                        ELSE ((SUM(dcc.paid) - x.CostBasis) * (s2.commisionRate/100)) - ((SUM(dcc.paid) - x.CostBasis) * (s.commisionRate/100))
                                    END
                                ELSE s2.commisionRate
                            END            
                        ELSE 0
                    END, 2) AS `groupLeaderCommission`, 
                    
                    s.idsalesmen, e.lastName AS `salesmanLastName`, e.firstName AS `salesmanFirstName`,
                    sg.id AS `groupId`, sg.groupName,
                    CASE 
                        WHEN cr.idCommissions IS NOT NULL AND cr.commissionRate > 0 AND SUM(dcc.paid) >= cr.minPayment THEN 
                            CASE
                                WHEN s.byPercentage = 1 AND cr.commissionRate > 0 THEN cr.commissionRate/100
                                ELSE cr.commissionRate
                            END
                        WHEN (cr.idCommissions IS NULL OR cr.commissionRate <= 0) AND s.commisionRate > 0 THEN
                            CASE
                                WHEN s.byPercentage = 1 AND s.commisionRate > 0 THEN (s.commisionRate/100)
                                ELSE s.commisionRate
                            END            
                        ELSE 0
                    END AS `commissionRate`,
                    
                    ROUND(CASE
                        WHEN cr.idCommissions IS NOT NULL AND cr.commissionRate > 0  AND SUM(dcc.paid) >= cr.minPayment AND (
                            (x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0) # department cost basis
                            OR (SUM(dcc.paid) - x.CostBasis > 0)
                        ) THEN 
                            CASE
                                WHEN s.byPercentage = 1 THEN 
                                    CASE
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0 THEN # department cost basis by percentage
                                            ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (cr.commissionRate/100))
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Fixed Cost' THEN # department cost basis by fixed cost
                                            ((SUM(dcc.paid) - x.cost) * (cr.commissionRate/100))
                                        ELSE ((SUM(dcc.paid) - x.CostBasis) * (cr.commissionRate/100))
                                    END
                                    
                                
                                ELSE cr.commissionRate
                            END
                        WHEN (cr.idCommissions IS NULL OR cr.commissionRate <= 0) AND s.commisionRate > 0 AND (
                            (x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0) # department cost basis
                            OR (SUM(dcc.paid) - x.CostBasis > 0)
                        ) THEN
                            CASE
                                WHEN s.byPercentage = 1 THEN 
                                    CASE
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Percentage' AND SUM(dcc.paid) > 0 THEN # department cost basis by percentage
                                            ((SUM(dcc.paid) - ROUND(SUM(dcc.paid) * (x.cost / 100), 2)) * (s.commisionRate/100))
                                        WHEN x.idCosts IS NOT NULL AND x.costType = 'Fixed Cost' THEN # department cost basis by fixed cost
                                            ((SUM(dcc.paid) - x.cost) * (s.commisionRate/100))
                                        ELSE ((SUM(dcc.paid) - x.CostBasis) * (s.commisionRate/100))
                                    END
                                    
                                ELSE s.commisionRate
                            END            
                        ELSE 0
                    END, 2) AS `commission`,
                  
                    CASE WHEN do.iddetailOrders IS NOT NULL AND do.balance <= 0 THEN 'Paid' ELSE 'Unpaid' END AS `commissionStatus`,          
                    
                    CASE
                        WHEN SUM(dcc.paid) > 0 THEN 'Complete'
                        WHEN x.OrderCount = x.TransmitCount THEN 'Pending'
                        ELSE 'Processing'
                    END AS `status`,      
                    /*
                    CASE
                     
                        WHEN x.idinsuranceRules IS NOT NULL AND (
                            (x.doctorRequired = 1 AND o.doctorId IS NULL)
                            OR (x.diagnosisRequired = 1 AND x.DiagnosisCount = 0)
                            OR (x.genderRequired = 1 AND (p.sex IS NULL OR p.sex LIKE 'unknown%'))
                            OR (x.ageRequired = 1 AND p.dob IS NULL)
                            OR (x.relationshipRequired AND p.relationship IS NULL)
                            OR (x.addressRequired = 1 AND (p.addressStreet IS NULL OR p.addressCity IS NULL OR p.addressState IS NULL OR p.addressZip IS NULL))
                            OR (x.locationRequired = 1 AND o.locationId IS NULL)
                            OR (x.subscriberRequired = 1 AND su.idSubscriber IS NULL)
                            OR (x.groupNumberRequired = 1 AND o.groupNumber IS NULL)
                            OR (x.group2NumberRequired = 1 AND o.secondaryGroupNumber IS NULL)
                            OR (x.policyNumberRequired = 1 AND o.policyNumber IS NULL)
                            OR (x.policy2NumberRequired = 1 AND o.secondaryPolicyNumber IS NULL)
                            OR (x.medicareNumberRequired = 1 AND o.medicareNumber IS NULL)
                            OR (x.medicaidNumberRequired = 1 AND o.medicaidNumber IS NULL)
                        ) THEN 'Rejected'
                        WHEN SUM(dcc.paid) > 0 THEN 'Complete'
                        WHEN x.OrderCount = x.TransmitCount THEN 'Pending'
                        ELSE 'Processing'
                    END AS `status`,
                    CASE
                        WHEN cs.idClaimStatuses IS NOT NULL THEN cs.statusName
                        ELSE 'Processing'
                    END AS `claimStatus`,
                    */
                    
                    x.OrderCount, x.TransmitCount,
                    l.idLocation, l.locationNo, l.locationName,                
                    i.idinsurances, i.number AS `insuranceNo`, i.name AS `insuranceName`,
                    c.idClients, c.clientNo, c.clientName,
                    d.iddoctors, d.number AS `doctorNo`, d.lastName AS `doctorLastName`, d.firstName AS `doctorFirstName`, d.NPI AS `doctorNPI`,
                    p.idPatients, p.arNo, p.lastName AS `patientLastName`, p.firstName AS `patientFirstName`, p.middleName AS `patientMiddleName`,
                    te.idterritory, te.territoryName,
                    rt.idreportType, rt.number AS `reportTypeNo`, rt.name AS `reportTypeName`,
                    o.orderDate, 
                    do.lastPaymentDate AS `paymentDate`,
                    o.specimenDate,
                    x.idDepartment, x.deptNo, x.deptName
            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            INNER JOIN (
                SELECT	o.idOrders,    
                    COUNT(DISTINCT r.idResults) AS `OrderCount`,
                    COUNT(DISTINCT CASE WHEN r.printAndTransmitted THEN r.idResults ELSE NULL END) AS `TransmitCount`,
                    d.idDepartment, d.deptNo, d.deptName,
                    SUM(CASE
                        WHEN r.panelId IS NULL THEN t.cost
                        ELSE 0
                    END) AS `CostBasis`,
                    dc.idCosts, dc.cost,
                    ct.idCostTypes, ct.costType
                FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
                INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests
                INNER JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTS . " d ON t.department = d.idDepartment
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_DEPARTMENTCOSTS . " dc ON d.idDepartment = dc.departmentId
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_COSTTYPES . " ct ON dc.costTypeId = ct.idCostTypes
                WHERE $dateField BETWEEN ? AND ?
                    AND r.isInvalidated = false
                GROUP BY o.idOrders		
            ) x ON o.idOrders = x.idOrders
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON c.salesmen = s.idsalesmen
            
            INNER JOIN (
                SELECT *
                FROM " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id
                GROUP BY salesmanId
            ) sg ON s.idsalesmen = sg.salesmanId
            
            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
            INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
            INNER JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " l ON o.locationId = l.idLocation
            INNER JOIN " . self::DB_CSS . "." . self::TBL_REPORTTYPE . " rt ON o.reportType = rt.idreportType
            
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s2 ON sg.groupLeader = s2.idsalesmen
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr2 ON s2.idsalesmen = cr2.salesmanId AND o.reportType = cr2.reportTypeId
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e2 ON s2.employeeID = e2.idemployees
            
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_SUBSCRIBER . " su ON o.subscriberId = su.idSubscriber
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TERRITORY . " te ON s.territory = te.idterritory
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i ON o.insurance = i.idinsurances
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr ON o.reportType = cr.reportTypeId AND s.idsalesmen = cr.salesmanId
          
            LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILORDERS . " do ON o.idOrders = do.orderId AND do.active = true
            LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILCPTCODES . " dcc ON do.iddetailOrders = dcc.detailOrderId AND dcc.transferredTo IS NULL
            $where                
            GROUP BY o.idOrders
            $having
            ORDER BY o.idOrders ASC";

        //INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON s.idsalesmen = sgl.salesmanId
        //INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id

//        error_log("/*getSalesTableData*/ " . $sql);
//        error_log(implode($aryInput, ","));

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $aryReturnData = array();
        if (count($data) > 0) {

            $arySalesGroups = array();
            if ($input['idsalesmen'] == "null" || empty($input['idsalesmen'])) {
                // admin/manager - get all sales groups with group leaders and their commission rates
                $sql = "SELECT sg.id, sg.groupName, sg.groupLeader, 
                    e.firstName AS `leaderFirstName`, e.lastName AS `leaderLastName`, s.commisionRate AS `leaderCommissionRate`,
                    s2.idsalesmen, e2.firstName AS `salesmanFirstName`, e2.lastName AS `salesmanLastName`, s2.commisionRate AS `salesmanCommissionRate`
                FROM " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON sg.groupLeader = s.idsalesmen
                INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON sg.id = sgl.salesGroupId
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s2 ON sgl.salesmanId = s2.idsalesmen
                INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e2 ON s2.employeeID = e2.idemployees
                WHERE s.idsalesmen != s2.idsalesmen
                ORDER BY sg.id ASC";

                $data2 = parent::select($sql, null, array("Conn" => $this->Conn));

                foreach ($data2 AS $row) {
                    $groupId = $row['id'];
                    $groupName = $row['groupName'];
                    $groupLeaderId = $row['groupLeader'];
                    $leaderFirstName = $row['leaderFirstName'];
                    $leaderLastName = $row['leaderLastName'];
                    $leaderCommissionRate = $row['leaderCommissionRate'];
                    $salesmanId = $row['idsalesmen'];
                    $salesmanFirstName = $row['salesmanFirstName'];
                    $salesmanLastName = $row['salesmanLastName'];
                    $salesmanCommissionRate = $row['salesmanCommissionRate'];

                    if (!array_key_exists($groupId, $arySalesGroups)) {
                        $arySalesGroups[$groupId] = array(
                            "groupId" => $groupId,
                            "groupName" => $groupName,
                            "groupLeaderId" => $groupLeaderId,
                            "leaderFirstName" => $leaderFirstName,
                            "leaderLastName" => $leaderLastName,
                            "leaderCommissionRate" => $leaderCommissionRate,
                            "groupMembers" => array($salesmanId)
                        );
                    } else {
                        $arySalesGroups[$groupId]['groupMembers'][] = $salesmanId;
                    }
                }
            }

            foreach ($data as $row) {
                $accession = $row['accession'];
                $payment = $row['payment'];
                $locationName = $row['locationName'];
                $insuranceName = $row['insuranceName'];
                $doctorName = $row['doctorFirstName'] . " " . $row['doctorLastName'];
                $arNo = $row['arNo'];
                $patientName = $row['patientFirstName'] . " " . $row['patientLastName'];
                $orderDate = $row['orderDate'];
                $paymentDate = $row['paymentDate'];
                $status = $row['status'];

                $salesmanName = $row['salesmanFirstName'] . " " . $row['salesmanLastName'];
                $groupName = $row['groupName'];

                $idClients = $row['idClients'];
                $clientNo = $row['clientNo'];
                $clientName = $row['clientName'];

                $reportType = $row['reportTypeName'];
                $territoryName = $row['territoryName'];

                $specimenDate = $row['specimenDate'];

                $idinsurances = $row['idinsurances'];
                $idLocation = $row['idLocation'];
                $iddoctors = $row['iddoctors'];

                $commission = $row['commission'];
                $commissionRate = $row['commissionRate'];
                $commissionStatus = $row['commissionStatus'];

                $locationNo = $row['locationNo'];
                //$claimStatus = $row['claimStatus'];
                $cptCodeCount = $row['OrderCount'];
                $deptName = $row['deptName'];
                $doctorNo = $row['doctorNo'];
                $doctorNPI = $row['doctorNPI'];

                $costBasis = $row['CostBasis'];

                $groupLeaderCommissionRate = $row['groupLeaderCommissionRate'];
                $groupLeaderCommission = $row['groupLeaderCommission'];

                $territoryLeaderCommission = 0;
                $territoryLeaderCommissionRate = 0;
                if (!empty($input['subSalesGroupIds']) && $row['groupLeaderSalesmanId'] != $input['idsalesmen']) {
                    // there are sub-sales-groups, so this is a territory leader
                    $territoryLeaderCommissionRate = $input['commissionRate'] / 100;
                    if ($payment != null && $payment > 0 && $territoryLeaderCommissionRate != null && is_numeric($territoryLeaderCommissionRate) && $territoryLeaderCommissionRate > 0 && ($payment - $costBasis) > 0) {
                        if ($groupLeaderCommission > 0) {
                            $territoryLeaderCommission = (($payment - $costBasis) * $territoryLeaderCommissionRate) - $groupLeaderCommission;
                        } else {
                            $territoryLeaderCommission = (($payment - $costBasis) * $territoryLeaderCommissionRate);
                        }
                    }
                }

                if ($row['groupLeaderSalesmanId'] == $row['idsalesmen']) {
                    $groupLeaderCommission = 0;
                }

                $hasTerritoryLeader = false;
                $territoryGroupId = 0;
                if ($input['idsalesmen'] == "null" || empty($input['idsalesmen'])) {
                    // admin/manager
                    foreach ($arySalesGroups as $groupId => $arySalesGroup) {
                        if (in_array($row['groupLeaderSalesmanId'], $arySalesGroup['groupMembers'])) {
                            $hasTerritoryLeader = true;
                            $territoryGroupId = $groupId;
                            $territoryLeaderCommissionRate = $arySalesGroup['leaderCommissionRate'] / 100;
                            if ($groupLeaderCommission > 0) {
                                $territoryLeaderCommission = (($payment - $costBasis) * $territoryLeaderCommissionRate) - $groupLeaderCommission;

                            } else {
                                $territoryLeaderCommission = (($payment - $costBasis) * $territoryLeaderCommissionRate);
                            }
                        }
                    }
                    if ($territoryLeaderCommission < 0) {
                        $territoryLeaderCommission = 0;
                    }
                }

                $aryReturnData[] = array(
                    "accession" => $accession,
                    "payment" => $payment,
                    "commission" => $commission,
                    "commissionRate" => $commissionRate,
                    "commissionStatus" => $commissionStatus,
                    "salesmanName" => $salesmanName,
                    "territoryName" => $territoryName,
                    "groupName" => $groupName,
                    "locationName" => $locationName,
                    "insuranceName" => $insuranceName,
                    "patientNumber" => $arNo,
                    "status" => $status,
                    "reportType" => $reportType,
                    "orderDate" => $orderDate,
                    "specimenDate" => $specimenDate,
                    "paymentDate" => $paymentDate,

                    "clientName" => $clientName,
                    "clientId" => $idClients,
                    "clientNo" => $clientNo,
                    "insuranceId" => $idinsurances,
                    "locationId" => $idLocation,

                    "doctorName" => $doctorName,
                    "doctorId" => $iddoctors,
                    "doctorNo" => $doctorNo,
                    "doctorNPI" => $doctorNPI,
                    "deptName" => $deptName,

                    'groupLeaderCommissionRate' => $groupLeaderCommissionRate,
                    'groupLeaderCommission' => $groupLeaderCommission,
                    'territoryLeaderCommission' => $territoryLeaderCommission,
                    'territoryLeaderCommissionRate' => $territoryLeaderCommissionRate,
                    'territoryGroupId' => $territoryGroupId,
                    'costBasis' => $costBasis
                );
            }
        }
        return $this->utf8ize($aryReturnData);
    }

    public function getTopAccountsByPayor(array  $input) {
        $orderBy = "OrderCount";
        $dateField = $this->getDateSearchField($input);
        if ($dateField == "o.DOI") {
            $orderBy = "TotalPaid";
        }

        $where = "WHERE	$dateField BETWEEN ? AND ?
                    AND o.action = true
                    AND sg.id = ?
                    AND (sg.groupLeader = ? OR s.idsalesmen = ?) ";

        $dateFrom = $input['dateFrom'];
        $dateTo = $input['dateTo'];
        $groupId = $input['groupId'];
        $idsalesmen = $input['idsalesmen'];
        $isManager = $input['isManager'];

        $aryInput = array($dateFrom, $dateTo, $groupId, $idsalesmen, $idsalesmen);

        if (array_key_exists("selectedGroupMembers", $input) && !empty($input['selectedGroupMembers'])) {
            $arySelectedGroupMembers = explode(",", $input['selectedGroupMembers']);
            $where .= "AND s.idsalesmen IN (";
            foreach ($arySelectedGroupMembers as $salesmanId) {
                $where .= "?,";
                $aryInput[] = $salesmanId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }

        $sql = "
            SELECT 	i.idinsurances, i.number, i.name, i.abbreviation,
                    COUNT(DISTINCT o.idOrders) AS `OrderCount`,
                    SUM(dcc.paid) AS `TotalPaid`,
                    /*ROUND(SUM(CASE 
                        WHEN cr.idCommissions IS NOT NULL AND crt.typeNumber = 1 THEN o.payment * cr.commissionRate # Percentage by order
                        WHEN cr.idCommissions IS NOT NULL AND crt.typeNumber = 2 THEN cr.commissionRate # Amount by order
                        WHEN cr.idCommissions IS NULL AND s.byOrders = true AND s.byPercentage = true THEN o.payment * s.commisionRate
                        WHEN cr.idCommissions IS NULL AND s.byOrders = true AND s.byAmount = true THEN s.commisionRate
                        ELSE 0
                    END), 2) AS `TotalCommission`*/
                    SUM(CASE
                      WHEN cr.idCommissions IS NOT NULL AND o.payment IS NOT NULL AND o.payment >= cr.commissionRate THEN cr.commissionRate
                      ELSE 0
                    END) AS `TotalCommission`
            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            INNER JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i ON o.insurance = i.idinsurances
            INNER JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON c.salesmen = s.idsalesmen
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON s.salesGroup = sg.id
            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr ON o.reportType = cr.reportTypeId AND s.idsalesmen = cr.salesmanId            
            INNER JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILORDERS . " do ON o.idOrders = do.orderId AND do.active = true
            LEFT JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILCPTCODES . " dcc ON do.iddetailOrders = dcc.detailOrderId
            
            $where            
            GROUP BY i.number
            ORDER BY $orderBy DESC
            LIMIT 5";

        /*error_log($sql);
        error_log(implode($aryInput, ","));*/

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $aryChartData = array();
        if (count($data) > 0) {
            foreach ($data as $row) {
                $idinsurances = $row['idinsurances'];
                $number = $row['number'];
                $name = $row['name'];
                $abbreviation = $row['abbreviation'];

                $y = $row['OrderCount'];
                /*if ($orderBy == "TotalPaid" && $isManager == false) {
                    $y = $row['TotalCommission'];
                } else {
                    $y = $row['TotalPaid'];
                }*/

                if ($orderBy == "TotalPaid" && $isManager == true) {
                    $y = $row['TotalPaid'];
                }

                $aryChartData[] = array('key' => $name, 'y' => $y);
            }
        }
        return $aryChartData;
    }

    public function getTopPayorsByReportType(array $input) {
        $dateField = $this->getDateSearchField($input);

        $sql = "
            SELECT 	i.idinsurances, i.number AS `insuranceNumber`, i.name AS `insuranceName`,
                    rt.idreportType, rt.name AS `reportTypeName`,
                    COUNT(DISTINCT o.idOrders) AS `OrderCount`
            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            INNER JOIN " . self::DB_CSS . "." . self::TBL_REPORTTYPE . " rt ON o.reportType = rt.idreportType
            INNER JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON c.salesmen = s.idsalesmen
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON s.salesGroup = sg.id
            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
            INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i ON o.insurance = i.idinsurances
            LEFT JOIN (
                SELECT r.orderId, t.idtests
                FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
                INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests AND t.number = 490
                WHERE	$dateField BETWEEN ? AND ?
                GROUP BY r.orderId
            ) rt ON o.idOrders = rt.orderId
            WHERE	$dateField BETWEEN ? AND ?
                    AND sg.id = ?
                    AND rt.orderId IS NULL
                    AND o.active = true
                    AND (sg.groupLeader = ? OR s.idsalesmen = ?)
            GROUP BY reportTypeName, i.idinsurances
            ORDER BY reportTypeName, OrderCount DESC
        ";

        $dateFrom = $input['dateFrom'];
        $dateTo = $input['dateTo'];
        $groupId = $input['groupId'];
        $idsalesmen = $input['idsalesmen'];

        $aryInput = array($dateFrom, $dateTo, $dateFrom, $dateTo, $groupId, $idsalesmen, $idsalesmen);

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $maxPieSlices = 5;
        $pieIsFull = false;

        $aryChartData = array();
        if (count($data) > 0) {

            foreach ($data as $row) {

                //$doctorName = $row['firstName'] . " " . $row['lastName'];
                $insuranceName = $row['insuranceName'];
                $orderCount = $row['OrderCount'];

                if (!array_key_exists($row['reportTypeName'], $aryChartData)) {
                    $aryChartData[$row['reportTypeName']] = array();
                    $pieIsFull = false;
                }

                if (!$pieIsFull && count($aryChartData[$row['reportTypeName']]) < $maxPieSlices) {

                    $aryChartData[$row['reportTypeName']][] = array('key' => $insuranceName, 'y' => $orderCount);

                } else {
                    $pieIsFull = true;
                }

            }
        }

        return $aryChartData;
    }

    public function getRejectionsByReportType(array  $input) {
        $dateField = $this->getDateSearchField($input);

        $sql = "
            SELECT 	rt.idreportType, rt.name AS `reportTypeName`,
                    COUNT(DISTINCT o.idOrders) AS `OrderCount`
            FROM orders o
            INNER JOIN reportType rt ON o.reportType = rt.idreportType
            INNER JOIN doctors d ON o.doctorId = d.iddoctors
            INNER JOIN clients c ON o.clientId = c.idClients
            INNER JOIN salesmen s ON c.salesmen = s.idsalesmen
            INNER JOIN salesGroup sg ON s.salesGroup = sg.id
            INNER JOIN employees e ON s.employeeID = e.idemployees
            INNER JOIN results r ON o.idOrders = r.orderId
            LEFT JOIN (
                SELECT r.orderId, t.idtests
                FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
                INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests AND t.number = 490
                WHERE	$dateField BETWEEN ? AND ?
                GROUP BY r.orderId
            ) rt ON o.idOrders = rt.orderId
            WHERE	$dateField BETWEEN ? AND ?
                    AND sg.id = ?
                    AND rt.orderId IS NOT NULL
                    AND o.active = true
            GROUP BY rt.name
            ORDER BY rt.idreportType, OrderCount DESC
        ";

        $dateFrom = $input['dateFrom'];
        $dateTo = $input['dateTo'];
        $groupId = $input['groupId'];

        $aryInput = array($dateFrom, $dateTo, $dateFrom, $dateTo, $groupId);

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $aryChartData = array();
        /*
        [
            { "key": "Toxicology", "y": 19 },
            { "key": "Core Lab", "y": 5 },
            { "key": "Genetics", "y": 32 }
        ]
        */
        if (count($data) > 0) {
            foreach ($data as $row) {
                $reportTypeName = $row['reportTypeName'];
                $orderCount = $row['OrderCount'];

                $aryChartData[] = array("key" => $reportTypeName, "y" => $orderCount);

            }
        }
        return $aryChartData;
    }

//    public function getSalesPerPayorPieChartData(array $input) {
//        $dateFrom = $input['dateFrom'];
//        $dateTo = $input['dateTo'];
//        $groupId = $input['groupId'];
//        $idsalesmen = $input['idsalesmen'];
//        $dateField = "o.specimenDate";
//        $orderBy = "OrderCount";
//        if (array_key_exists("dateField", $input)) {
//            if ($input['dateField'] == "DOI") {
//                $dateField = "o.DOI";
//                $orderBy = "TotalPayment";
//            } else if ($input['dateField'] == "orderDate") {
//                $dateField = "o.orderDate";
//            }
//        }
//
//        $where = "WHERE   $dateField BETWEEN ? AND ?
//                    AND rt.orderId IS NULL ";
//
//        $aryInput = array($dateFrom, $dateTo, $dateFrom, $dateTo);
//
//        if (isset($groupId) && !empty($groupId) && $groupId != "null") {
//            $where .= "AND sg.id = ? ";
//            $aryInput[] = $groupId;
//        }
//
//        if (isset($idsalesmen) && !empty($idsalesmen) && $idsalesmen != "null") {
//            $where .= "AND (sg.groupLeader = ? OR s.idsalesmen = ?) ";
//            $aryInput[] = $idsalesmen;
//            $aryInput[] = $idsalesmen;
//        }
//
//        if (array_key_exists("selectedGroupMembers", $input) && !empty($input['selectedGroupMembers'])) {
//            $arySelectedGroupMembers = explode(",", $input['selectedGroupMembers']);
//            $where .= "AND s.idsalesmen IN (";
//            foreach ($arySelectedGroupMembers as $salesmanId) {
//                $where .= "?,";
//                $aryInput[] = $salesmanId;
//            }
//            $where = substr($where, 0, strlen($where) - 1) . ") ";
//        }
//
//        if (array_key_exists("selectedSalesGroups", $input) && !empty($input['selectedSalesGroups'])) {
//            $arySelectedSalesGroups = explode(",", $input['selectedSalesGroups']);
//            $where .= "AND sg.id IN (";
//            foreach ($arySelectedSalesGroups as $groupId) {
//                $where .= "?,";
//                $aryInput[] = $groupId;
//            }
//            $where = substr($where, 0, strlen($where) - 1) . ") ";
//        }
//
//        $sql = "
//            SELECT 	s.idsalesmen, sg.id AS `groupId`,
//                i.idinsurances, i.number AS `insuranceNumber`, i.name AS `insuranceName`,
//                t.territoryName, sg.groupName,
//                COUNT(DISTINCT o.idOrders) AS `OrderCount`,
//                CASE WHEN SUM(o.payment) IS NULL THEN 0 ELSE SUM(o.payment) END AS `TotalPayment`,
//                /*ROUND(SUM(CASE
//                    WHEN cr.idCommissions IS NOT NULL AND crt.typeNumber = 1 THEN o.payment * cr.commissionRate # Percentage by order
//                    WHEN cr.idCommissions IS NOT NULL AND crt.typeNumber = 2 THEN cr.commissionRate # Amount by order
//                    WHEN cr.idCommissions IS NULL AND s.byOrders = true AND s.byPercentage = true THEN o.payment * s.commisionRate
//                    WHEN cr.idCommissions IS NULL AND s.byOrders = true AND s.byAmount = true THEN s.commisionRate
//                    ELSE 0
//                END), 2) AS `TotalCommission`*/
//                SUM(CASE
//                  WHEN cr.idCommissions IS NOT NULL AND o.payment IS NOT NULL AND o.payment > cr.commissionRate THEN cr.commissionRate
//                  ELSE 0
//                END) AS `TotalCommission`
//            FROM  " . self::DB_CSS . "." . self::TBL_SALESMEN . " s
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON s.idsalesmen = c.salesmen
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON s.salesGroup = sg.id
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON c.idClients = o.clientId
//            INNER JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i ON o.insurance = i.idinsurances
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_TERRITORY . " t ON s.territory = t.idterritory
//            LEFT JOIN (
//                SELECT r.orderId, t.idtests
//                FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
//                INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
//                INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests AND t.number = 490
//                WHERE   $dateField BETWEEN ? AND ?
//                GROUP BY r.orderId
//            ) rt ON o.idOrders = rt.orderId
//            LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr ON o.reportType = cr.reportTypeId AND s.idsalesmen = cr.salesmanId
//            $where
//            GROUP BY i.idinsurances
//            ORDER BY $orderBy DESC
//            LIMIT 10";
//
//        /*error_log($sql);
//        error_log(implode($aryInput, ","));*/
//
//        $data = parent::select($sql, $aryInput);
//
//        $aryReturn = array();
//        if (count($data) > 0) {
//            foreach ($data as $row) {
//                $y = $row['OrderCount'];
//                /*if ($orderBy == "TotalPayment" && $input['isManager'] == false) {
//                    $y = $row['TotalCommission'];
//                } else if ($orderBy == "TotalPayment") {
//                    $y = $row['TotalPayment'];
//                }*/
//
//                if ($orderBy == "TotalPayment" && $input['isManager'] == true) {
//                    $y = $row['TotalPayment'];
//                }
//
//                $aryReturn[] = array(
//                    "key" => $row['insuranceName'],
//                    "y" => $y
//                );
//            }
//        }
//
//        return $aryReturn;
//    }

    public function getSalesDatesTableHeaders(array $input) {
        $dateField = $this->getDateSearchField($input);
        $aryWhere = $this->getWhere($input);
        $where = $aryWhere['where'];
        $aryInput = array($input['dateFrom'], $input['dateTo']);
        $aryInput = array_merge($aryInput, $aryWhere['input']);

        $clientIds = $input['clientIds'];
        $payorIds = $input['payorIds'];
        $locationIds = $input['locationIds'];
        $doctorIds = $input['doctorIds'];
        $reportTypes = $input['reportTypes'];
        $statuses = $input['statuses'];

        if (!empty($clientIds)) {
            $where .= "AND c.idClients IN (";
            $aryClientIds = explode(',', $clientIds);
            foreach ($aryClientIds as $currClientId) {
                $where .= "?,";
                $aryInput[] = $currClientId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }
        if (!empty($payorIds)) {
            $where .= "AND i.idinsurances IN (";
            $aryPayorIds = explode(',', $payorIds);
            foreach ($aryPayorIds as $currPayorId) {
                $where .= "?,";
                $aryInput[] = $currPayorId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }
        if (!empty($locationIds)) {
            $where .= "AND o.locationId IN (";
            $aryLocationIds = explode(',', $locationIds);
            foreach ($aryLocationIds as $currLocationId) {
                $where .= "?,";
                $aryInput[] = $currLocationId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }
        if (!empty($doctorIds)) {
            $where .= "AND o.doctorId IN (";
            $aryDoctorIds = explode(',', $doctorIds);
            foreach ($aryDoctorIds as $currDoctorId) {
                $where .= "?,";
                $aryInput[] = $currDoctorId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }
        if (!empty($reportTypes)) {
            $where .= "AND rt.name IN (";
            $aryReportTypeNames = explode(',', $reportTypes);
            foreach ($aryReportTypeNames as $currReportTypeName) {
                $where .= "?,";
                $aryInput[] = $currReportTypeName;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }

        if (array_key_exists("selectedGroupMembers", $input) && !empty($input['selectedGroupMembers'])) {
            $arySelectedGroupMembers = explode(",", $input['selectedGroupMembers']);
            $where .= "AND s.idsalesmen IN (";
            foreach ($arySelectedGroupMembers as $salesmanId) {
                $where .= "?,";
                $aryInput[] = $salesmanId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }

        if (array_key_exists("selectedSalesGroups", $input) && !empty($input['selectedSalesGroups'])) {
            $arySelectedSalesGroups = explode(",", $input['selectedSalesGroups']);
            $where .= "AND sg.id IN (";
            foreach ($arySelectedSalesGroups as $groupId) {
                $where .= "?,";
                $aryInput[] = $groupId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }
        $having = "";
        if (!empty($statuses)) {
            $aryStatuses = explode(',', $statuses);

            if (in_array('Information Needed', $aryStatuses) && count($aryStatuses) == 1) { // 'Information Needed' is only selected status
                $having = "HAVING status LIKE 'Information Needed%' ";

            } else if (in_array('Information Needed', $aryStatuses)) { // multiple statuses selected including 'Information Needed'
                $having = "HAVING status LIKE 'Information Needed%' OR status IN(";

                foreach ($aryStatuses as $currStatus) {
                    if ($currStatus !== 'Information Needed') {
                        $having .=  "?, ";
                        $aryInput[] = $currStatus;
                    }
                }
                $having = substr($having, 0, strlen($having) - 2) . ") ";
            } else { // multiple statuses selected excluding 'Information Needed'
                $having = "HAVING status IN (";

                foreach ($aryStatuses as $currStatus) {
                    $having .=  "?, ";
                    $aryInput[] = $currStatus;
                }
                $having = substr($having, 0, strlen($having) - 2) . ") ";
            }
        }

        $sql = "
            SELECT a.*
            FROM (
                SELECT 	o.idOrders, s.idsalesmen, sg.id AS `groupId`,
                        e.lastName AS `salesmanLastName`, e.firstName AS `salesmanFirstName`,
                        te.territoryName, sg.groupName,
                        c.idClients, c.clientNo, c.clientName,
                        CASE
                            WHEN SUM(dcc.paid) > 0 THEN 'Complete'
                            WHEN x.OrderCount = x.TransmitCount THEN 'Pending'
                            ELSE 'Processing'
                        END AS `status`,  
                        
                        DATE_FORMAT(CASE WHEN 'o.specimenDate' = 'o.DOI' THEN o.DOI ELSE o.specimenDate END, '%m') AS `orderMonth`,
                        DATE_FORMAT(CASE WHEN 'o.specimenDate' = 'o.DOI' THEN o.DOI ELSE o.specimenDate END, '%d') AS `orderDay`,
                        DATE_FORMAT(CASE WHEN 'o.specimenDate' = 'o.DOI' THEN o.DOI ELSE o.specimenDate END, '%w') AS `orderWeekDay`,
                        DATE_FORMAT(CASE WHEN 'o.specimenDate' = 'o.DOI' THEN o.DOI ELSE o.specimenDate END, '%Y-%m-%d') AS `ShortOrderDate`,
                        CASE WHEN 'o.specimenDate' = 'o.DOI' THEN o.DOI ELSE o.specimenDate END AS `orderDate`
                FROM  " . self::DB_CSS . "." . self::TBL_SALESMEN . " s
                INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
                INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON s.idsalesmen = c.salesmen
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON s.idsalesmen = sgl.salesmanId
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id
                INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON c.idClients = o.clientId
                INNER JOIN " . self::DB_CSS . "." . self::TBL_REPORTTYPE . " rt ON o.reportType = rt.idreportType
                INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_SUBSCRIBER . " sa ON o.subscriberId = sa.idSubscriber
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_TERRITORY . " te ON s.territory = te.idterritory
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i ON o.insurance = i.idinsurances
                
                LEFT JOIN (
                    SELECT	o.idOrders,  
                    COUNT(DISTINCT r.idResults) AS `OrderCount`,
                    COUNT(DISTINCT CASE WHEN r.printAndTransmitted THEN r.idResults ELSE NULL END) AS `TransmitCount`
                    FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
                    INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests
                    WHERE o.active = true
                        AND r.isInvalidated = false
                        AND $dateField BETWEEN ? AND ?
                    GROUP BY o.idOrders		
                ) x ON o.idOrders = x.idOrders
            
                LEFT JOIN cssbilling.detailOrders do ON o.idOrders = do.orderId AND do.active = true
                LEFT JOIN cssbilling.detailCptCodes dcc ON do.iddetailOrders = dcc.detailOrderId
                
                $where
                GROUP BY o.idOrders
                $having
                
            ) a
            GROUP BY ShortOrderDate
            ORDER BY ShortOrderDate ASC ";

//        error_log("/* getSalesDatesTableHeaders */" . $sql);
//        error_log(implode($aryInput, ","));

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $arySalesData = array();
        $numberToWord = new Numbers_Words();
        if (count($data) > 0) {
            $i = 1;
            foreach ($data as $row) {
                //$arySalesData[] = $row['ShortOrderDate'];
                $arySalesData[] = array(
                    "id" => $numberToWord->toWords($i),
                    //"id" => $i,
                    "date" => $row['ShortOrderDate']
                );
                $i++;
            }
        }
        return $arySalesData;
    }

    public function getSalesLineGraphData(array $input) {
        $dateField = $this->getDateSearchField($input);
        $aryWhere = $this->getWhere($input);
        $where = $aryWhere['where'];
        $aryInput = array($input['dateFrom'], $input['dateTo']);
        $aryInput = array_merge($aryInput, $aryWhere['input']);

        $clientIds = $input['clientIds'];
        $payorIds = $input['payorIds'];
        $locationIds = $input['locationIds'];
        $doctorIds = $input['doctorIds'];
        $reportTypes = $input['reportTypes'];
        $statuses = $input['statuses'];

        if (!empty($clientIds)) {
            $where .= "AND c.idClients IN (";
            $aryClientIds = explode(',', $clientIds);
            foreach ($aryClientIds as $currClientId) {
                $where .= "?,";
                $aryInput[] = $currClientId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }
        if (!empty($payorIds)) {
            $where .= "AND i.idinsurances IN (";
            $aryPayorIds = explode(',', $payorIds);
            foreach ($aryPayorIds as $currPayorId) {
                $where .= "?,";
                $aryInput[] = $currPayorId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }
        if (!empty($locationIds)) {
            $where .= "AND o.locationId IN (";
            $aryLocationIds = explode(',', $locationIds);
            foreach ($aryLocationIds as $currLocationId) {
                $where .= "?,";
                $aryInput[] = $currLocationId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }
        if (!empty($doctorIds)) {
            $where .= "AND o.doctorId IN (";
            $aryDoctorIds = explode(',', $doctorIds);
            foreach ($aryDoctorIds as $currDoctorId) {
                $where .= "?,";
                $aryInput[] = $currDoctorId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }
        if (!empty($reportTypes)) {
            $where .= "AND rt.name IN (";
            $aryReportTypeNames = explode(',', $reportTypes);
            foreach ($aryReportTypeNames as $currReportTypeName) {
                $where .= "?,";
                $aryInput[] = $currReportTypeName;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }

        if (array_key_exists("selectedGroupMembers", $input) && !empty($input['selectedGroupMembers'])) {
            $arySelectedGroupMembers = explode(",", $input['selectedGroupMembers']);
            $where .= "AND s.idsalesmen IN (";
            foreach ($arySelectedGroupMembers as $salesmanId) {
                $where .= "?,";
                $aryInput[] = $salesmanId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }

        if (array_key_exists("selectedSalesGroups", $input) && !empty($input['selectedSalesGroups'])) {
            $arySelectedSalesGroups = explode(",", $input['selectedSalesGroups']);
            $where .= "AND sg.id IN (";
            foreach ($arySelectedSalesGroups as $groupId) {
                $where .= "?,";
                $aryInput[] = $groupId;
            }
            $where = substr($where, 0, strlen($where) - 1) . ") ";
        }
        $having = "";
        if (!empty($statuses)) {
            $aryStatuses = explode(',', $statuses);
            if (in_array('Information Needed', $aryStatuses) && count($aryStatuses) == 1) { // 'Information Needed' is only selected status
                $having = "HAVING status LIKE 'Information Needed%' ";

            } else if (in_array('Information Needed', $aryStatuses)) { // multiple statuses selected including 'Information Needed'
                $having = "HAVING status LIKE 'Information Needed%' OR status IN(";

                foreach ($aryStatuses as $currStatus) {
                    if ($currStatus !== 'Information Needed') {
                        $having .=  "?, ";
                        $aryInput[] = $currStatus;
                    }
                }
                $having = substr($having, 0, strlen($having) - 2) . ") ";
            } else { // multiple statuses selected excluding 'Information Needed'
                $having = "HAVING status IN (";

                foreach ($aryStatuses as $currStatus) {
                    $having .=  "?, ";
                    $aryInput[] = $currStatus;
                }
                $having = substr($having, 0, strlen($having) - 2) . ") ";
            }
        }

        $sql = "
            SELECT 	a.*,
                    COUNT(DISTINCT a.idOrders) AS `OrderCount`,
                    SUM(a.payment) AS `TotalPayment`
            FROM (
                SELECT 	o.idOrders, s.idsalesmen, sg.id AS `groupId`,
                        e.lastName AS `salesmanLastName`, e.firstName AS `salesmanFirstName`,
                        te.territoryName, sg.groupName,
                        c.idClients, c.clientNo, c.clientName,
                        /*CASE
                          WHEN FIND_IN_SET(490, GROUP_CONCAT(t.number)) != 0 AND o.payment IS NOT NULL AND o.payment BETWEEN 0.01 AND 0.99 THEN CONCAT('Information Needed: ', o.payment)
                            WHEN FIND_IN_SET(490, GROUP_CONCAT(t.number)) != 0 THEN 'Rejected'
                            WHEN o.payment IS NOT null AND o.payment != '' THEN 'Complete'
                            WHEN COUNT(o.idOrders) = SUM(r.printAndTransmitted) THEN 'Pending'
                            ELSE 'Processing'
                        END AS `status`,*/
                        CASE
                            WHEN SUM(dcc.paid) > 0 THEN 'Complete'
                            WHEN x.OrderCount = x.TransmitCount THEN 'Pending'
                            ELSE 'Processing'
                        END AS `status`,  
                        SUM(dcc.paid) AS `payment`,
            
                        DATE_FORMAT(CASE WHEN 'o.specimenDate' = 'o.DOI' THEN o.DOI ELSE o.specimenDate END, '%m') AS `orderMonth`,
                        DATE_FORMAT(CASE WHEN 'o.specimenDate' = 'o.DOI' THEN o.DOI ELSE o.specimenDate END, '%d') AS `orderDay`,
                        DATE_FORMAT(CASE WHEN 'o.specimenDate' = 'o.DOI' THEN o.DOI ELSE o.specimenDate END, '%w') AS `orderWeekDay`,
                        DATE_FORMAT(CASE WHEN 'o.specimenDate' = 'o.DOI' THEN o.DOI ELSE o.specimenDate END, '%Y-%m-%d') AS `ShortOrderDate`,
                        CASE WHEN 'o.specimenDate' = 'o.DOI' THEN o.DOI ELSE o.specimenDate END AS `orderDate`
                FROM  " . self::DB_CSS . "." . self::TBL_SALESMEN . " s
                INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
                INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON s.idsalesmen = c.salesmen
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUPLOOKUP . " sgl ON s.idsalesmen = sgl.salesmanId
                INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON sgl.salesGroupId = sg.id
                INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON c.idClients = o.clientId
                INNER JOIN patients p ON o.patientId = p.idPatients
                LEFT JOIN subscriber sa ON o.subscriberId = sa.idSubscriber
                INNER JOIN " . self::DB_CSS . "." . self::TBL_REPORTTYPE . " rt ON o.reportType = rt.idreportType
                LEFT JOIN " . self::DB_CSS . "." . self::TBL_TERRITORY . " te ON s.territory = te.idterritory
                
                LEFT JOIN (
                    SELECT	o.idOrders,
                    COUNT(DISTINCT r.idResults) AS `OrderCount`,
                    COUNT(DISTINCT CASE WHEN r.printAndTransmitted THEN r.idResults ELSE NULL END) AS `TransmitCount`
                    FROM orders o
                    INNER JOIN results r ON o.idOrders = r.orderId
                    INNER JOIN tests t ON r.testId = t.idtests
                    WHERE o.active = true
                        AND r.isInvalidated = false
                        AND $dateField BETWEEN ? AND ?
                    GROUP BY o.idOrders		
                ) x ON o.idOrders = x.idOrders
            
                LEFT JOIN cssbilling.detailOrders do ON o.idOrders = do.orderId AND do.active = true
                LEFT JOIN cssbilling.detailCptCodes dcc ON do.iddetailOrders = dcc.detailOrderId
                
                $where
                GROUP BY o.idOrders
                $having
            ) a
            GROUP BY a.idsalesmen, a.idClients, a.ShortOrderDate
            ORDER BY a.idsalesmen ASC, a.idClients ASC, a.ShortOrderDate ASC ";

//        error_log("/* getSalesLineGraphData */" . $sql);
//        error_log(implode($aryInput, ","));

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $arySalesDates = array();
        $arySalesmen = array();
        $aryClients = array();

        $arySalesData = array();

        $aryReturnData = array();
        $aryLineChartData = array();

        if (count($data) > 0) {
            foreach ($data as $row) {
                if (!in_array($row['ShortOrderDate'], $arySalesDates)) { // make array of distinct order dates
                    $arySalesDates[] = $row['ShortOrderDate'];
                }

                $currSalesmanName = $row['salesmanFirstName'] . " " . $row['salesmanLastName'];
                if (!in_array($row['idsalesmen'], array_column($arySalesmen, 'id'))) { // make array of distinct salesmen
                    $arySalesmen[] = array(
                        "id" => $row['idsalesmen'],
                        "name" => $currSalesmanName,
                        "territoryName" => $row['territoryName'],
                        "groupName" => $row['groupName'],
                        "clientNo" => $row['clientNo'],
                        "clientName" => $row['clientName'],
                        "clientIds" => array($row['idClients'])
                    );
                } else {
                    $key = array_search($row['idsalesmen'], array_column($arySalesmen, 'id'));

                    if (!in_array($row['idClients'], $arySalesmen[$key]['clientIds'])) {
                        $arySalesmen[$key]['clientIds'][] = $row['idClients'];
                    }
                }

                if (!array_key_exists($row['idClients'], $aryClients)) {
                    $aryClients[$row['idClients']] = array(
                        "clientNo" => $row['clientNo'],
                        "clientName" => $row['clientName']
                    );
                }
            }
            usort($arySalesDates, 'date_sort');

            foreach ($arySalesmen as $arySalesmanName) {
                $id = $arySalesmanName['id'];
                $name = $arySalesmanName['name'];
                $territoryName = $arySalesmanName['territoryName'];
                $groupName = $arySalesmanName['groupName'];

                $aryCurrSalesClient = array(
                    "id" => $id,
                    "name" => $name,
                    "territoryName" => $territoryName,
                    "groupName" => $groupName,
                    "clients" => array()
                );

                $aryTempRow = array(
                    'key' => $name,
                    //'color' => '',
                    'values' => array()
                );
                foreach ($arySalesDates as $currDate) {
                    $currNumericOrderDate = strtotime($currDate . " 12:00:00");
                    //$currNumericOrderDate = date('Y,m,d,H,i,s', strtotime($currDate));

                    $aryTempRow['values'][] = array(
                        'x' => $currNumericOrderDate,
                        'y' => 0
                    );
                }
                $aryLineChartData[] = $aryTempRow;


                foreach ($arySalesmanName['clientIds'] as $idClients) {

                    $clientNo = $aryClients[$idClients]['clientNo'];
                    $clientName = $aryClients[$idClients]['clientName'];

                    $aryCurrClient = array(
                        "idClients" => $idClients,
                        "clientNo" => $clientNo,
                        "clientName" => $clientName,
                        "sales" => array()
                    );

                    foreach ($arySalesDates as $currDate) {
                        $aryCurrClient['sales'][$currDate] = 0;
                    }

                    $aryCurrSalesClient['clients'][] = $aryCurrClient;
                }

                $arySalesData[] = $aryCurrSalesClient;
            }

            $prevIdSalesmen = $data[0]['idsalesmen'];
            $aryTempRow = array(
                'key' => $data[0]['salesmanFirstName'] . " " . $data[0]['salesmanLastName'],
                'values' => array()
            );
            foreach ($data as $row) {
                $currIdSalesmen = $row['idsalesmen'];
                $currOrderDate = $row['ShortOrderDate'];
                $currOrderCount = $row['OrderCount'];
                $currSalesmanName = $row['salesmanFirstName'] . " " . $row['salesmanLastName'];
                $currIdClients = $row['idClients'];

                $salesmanKey = array_search($currIdSalesmen, array_column($arySalesData, 'id'));
                $aryCurrSalesman = $arySalesData[$salesmanKey];

                $clientKey = array_search($currIdClients, array_column($arySalesData[$salesmanKey]['clients'], 'idClients'));
                $aryCurrClient = $arySalesData[$salesmanKey]['clients'][$clientKey];

                $arySalesData[$salesmanKey]['clients'][$clientKey]['sales'][$currOrderDate] = $currOrderCount;

                $currNumericOrderDate = strtotime($currOrderDate . " 12:00:00");

                $salesmanChartKey = array_search($currSalesmanName, array_column($aryLineChartData, 'key'));
                $dateKey = array_search($currNumericOrderDate, array_column($aryLineChartData[$salesmanChartKey]['values'], 'x'));

                $aryLineChartData[$salesmanChartKey]['values'][$dateKey]['y'] += $currOrderCount;
            }

            $numberToWord = new Numbers_Words();
            foreach ($arySalesData as $aryCurrSalesman) {
                $id = $aryCurrSalesman['id'];
                $name = $aryCurrSalesman['name'];
                $territoryName = $aryCurrSalesman['territoryName'];
                $groupName = $aryCurrSalesman['groupName'];

                foreach ($aryCurrSalesman['clients'] as $aryCurrClient) {
                    $idClients = $aryCurrClient['idClients'];
                    $clientNo = $aryCurrClient['clientNo'];
                    $clientName = $aryCurrClient['clientName'];

                    $aryCurrRow = array(
                        "id" => $id,
                        "name" => $name,
                        "territoryName" => $territoryName,
                        "groupName" => $groupName,
                        "idClients" => $idClients,
                        "clientNo" => $clientNo,
                        "clientName" => $clientName
                    );

                    $i = 1;
                    $totalOrders = 0;
                    foreach ($aryCurrClient['sales'] as $orderDate => $orderCount) {
                        $number = $numberToWord->toWords($i);
                        $aryCurrRow[$number] = $orderCount;
                        $i++;
                        $totalOrders += $orderCount;
                    }
                    $aryCurrRow['TotalOrders'] = $totalOrders;
                    $aryReturnData[] = $aryCurrRow;
                }
            }
        }

        //values - represents the array of {x,y} data points
        //key  - the name of the series.
        //color - optional: choose your own line color.
        //area - set to true if you want this line to turn into a filled area chart.
        /*$aryChartData = array(
            array(
                'key' => 'Salesman 1',
                'color' => '#ff7f0e',
                'values' => array(
                    array(
                        '01/01/2018' => 0,
                        'y' => 1
                    ),
                    array(
                        '01/02/2018' => 0,
                        'y' => 1
                    ),
                    array(
                        '01/03/2018' => 0,
                        'y' => 1
                    )
                ),
            ),
            array(
                'key' => 'Salesman 2',
                'color' => '#2ca02c',
                'values' => array()
            ),
            array(
                'key' => 'Salesman 3',
                'color' => '#7777ff',
                'area' => true,
                'values' => array()
            )
        );*/

        $aryReturn = array(
            "tableData" => $aryReturnData,
            "lineChartData" => $aryLineChartData
        );

        return $this->utf8ize($aryReturn);
    }

    public function getSalesDetailTableData(array $input) {
        $dateField = $this->getDateSearchField($input);

        $sql = "
            SELECT 	o.idOrders, o.accession,
                    o.payment,
                    
                    CASE
                        #WHEN FIND_IN_SET(4, GROUP_CONCAT(DISTINCT bi.idEventTypes)) != 0 THEN 'Rejected'
                        #WHEN FIND_IN_SET(3, GROUP_CONCAT(DISTINCT bi.idEventTypes)) != 0  THEN 'Sent'
                        #WHEN o.allPAndT THEN 'Pending'
                        WHEN rt.orderId IS NOT NULL THEN 'Rejected'
                        WHEN o.payment IS NOT null AND o.payment != '' THEN 'Complete'
                        WHEN COUNT(o.idOrders) = SUM(r.printAndTransmitted) THEN 'Pending'
                        ELSE 'Processing'
                    END AS `status`,                    
                    l.idLocation, l.locationNo,
                    CASE WHEN l.idLocation != 1 THEN 'Hospital' ELSE l.locationName END AS `locationName`,
                    i.idinsurances, i.number AS `insuranceNo`, i.name AS `insuranceName`,
                    c.idClients, c.clientNo, c.clientName,
                    d.iddoctors, d.number AS `doctorNo`, d.lastName AS `doctorLastName`, d.firstName AS `doctorFirstName`,
                    p.idPatients, p.arNo, p.lastName AS `patientLastName`, p.firstName AS `patientFirstName`, p.middleName AS `patientMiddleName`,
                    s.idsalesmen, e.lastName AS `salesmanLastName`, e.firstName AS `salesmanFirstName`,
                    o.orderDate, o.DOI AS `paymentDate`, o.specimenDate
            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON c.salesmen = s.idsalesmen
            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
            INNER JOIN " . self::DB_CSS . "." . self::TBL_PATIENTS . " p ON o.patientId = p.idPatients
            INNER JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " l ON o.locationId = l.idLocation
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_DOCTORS . " d ON o.doctorId = d.iddoctors
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_INSURANCES . " i ON o.insurance = i.idinsurances
            LEFT JOIN (
                SELECT r.orderId, t.idtests
                FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
                INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
                INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests AND t.number = 490
                WHERE   $dateField BETWEEN ? AND ?
                GROUP BY r.orderId
            ) rt ON o.idOrders = rt.orderId
            
            /*
            LEFT JOIN (
                SELECT	do.iddetailOrders, do.orderId,
                        et.idEventTypes, et.name
                FROM " . self::DB_CSSBILLING . "." . self::TBL_DETAILORDERS . " do
                INNER JOIN " . self::DB_CSSBILLING . "." . self::TBL_DETAILORDEREVENTS . " doe ON do.iddetailOrders = doe.detailOrderId
                INNER JOIN " . self::DB_CSSBILLING . "." . self::TBL_EVENTS . " e ON doe.eventId = e.idEvents
                INNER JOIN " . self::DB_CSSBILLING . "." . self::TBL_EVENTTYPES . " et ON e.idEvents = et.idEventTypes AND (et.idEventTypes = 3 OR et.idEventTypes = 4)
            ) bi ON o.idOrders = bi.orderId
            */
            
            WHERE   $dateField BETWEEN ? AND ?
                AND s.idsalesmen = ?
                AND c.idClients = ?
                #AND rt.orderId IS NULL
                AND o.active = true
            GROUP BY o.idOrders
            ORDER BY $dateField ASC";

        $dateFrom = $input['dateFrom'];
        $dateTo = $input['dateTo'];
        $idsalesmen = $input['idsalesmen'];
        $idClients = $input['idClients'];

        $aryInput = array($dateFrom, $dateTo, $dateFrom, $dateTo, $idsalesmen, $idClients);

        $data = parent::select($sql, $aryInput, array("Conn" => $this->Conn));

        $aryReturnData = array();
        if (count($data) > 0) {
            foreach ($data as $row) {
                $accession = $row['accession'];
                $payment = $row['payment'];
                $locationName = $row['locationName'];
                $insuranceName = $row['insuranceName'];
                $doctorName = $row['doctorFirstName'] . " " . $row['doctorLastName'];
                $arNo = $row['arNo'];
                $patientName = $row['patientFirstName'] . " " . $row['patientLastName'];
                $orderDate = $row['orderDate'];
                $paymentDate = $row['paymentDate'];
                $status = $row['status'];

                $aryReturnData[] = array(
                    "accession" => $accession,
                    "payment" => $payment,
                    "locationName" => $locationName,
                    "insuranceName" => $insuranceName,
                    "doctorName" => $doctorName,
                    "patientNumber" => $arNo,
                    "status" => $status,
                    "orderDate" => $orderDate,
                    "paymentDate" => $paymentDate
                );
            }
        }
        return $aryReturnData;
    }

    public function getSalesGroups(array $input) {
        $sql = "
            SELECT sg.id AS `groupId`, sg.groupName, sg.groupLeader, sg.created, sg.createdBy,
              CAST(GROUP_CONCAT(DISTINCT s.idsalesmen) AS CHAR) AS `groupMemberIds`
            FROM " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON sg.id = s.salesGroup
            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
            GROUP BY sg.id
            ORDER BY sg.groupName ASC";
        $data = parent::select($sql, null, null);
        $aryReturn = array();
        if (count($data) > 0) {
            foreach ($data as $row) {
                $aryGroupMemberIds = array();
                if ($row['groupMemberIds'] != null && !empty($row['groupMemberIds'])) {
                    $aryGroupMemberIds = explode(",", $row['groupMemberIds']);
                }
                $aryReturn[] = array(
                    "groupId" => $row['groupId'],
                    "groupName" => $row['groupName'],
                    "groupLeader" => $row['groupLeader'],
                    "created" => $row['created'],
                    "createdBy" => $row['createdBy'],
                    "groupMemberIds" => $aryGroupMemberIds
                );
            }
        }
        return $aryReturn;
    }

    public function logPaidCommissions(array $input) {
        $aryIdOrders = explode(",", $input['aryIdOrders']);
        $idUsers = $input['idUsers'];

        $aryInput = array();
        $where = "WHERE o.idOrders IN (";
        foreach ($aryIdOrders as $orderId) {
            $where .= "?, ";
            $aryInput[] = $orderId;
        }
        $where = substr($where, 0, strlen($where) - 2) . ")";

        $sql = "
            SELECT 	o.idOrders, o.accession,
                    o.payment,
                    CASE
                      WHEN cr.idCommissions IS NOT NULL AND o.payment IS NOT NULL AND o.payment > cr.commissionRate THEN cr.commissionRate
                      ELSE 0
                    END AS `commission`,
                    cr.commissionRate,
                    CASE 
                      WHEN FIND_IN_SET(490, GROUP_CONCAT(t.number)) != 0 AND o.payment IS NOT NULL AND o.payment BETWEEN 0.01 AND 0.99 THEN CONCAT('Information Needed: ', o.payment)
                        WHEN FIND_IN_SET(490, GROUP_CONCAT(t.number)) != 0 THEN 'Rejected'
                        WHEN o.payment IS NOT null AND o.payment != '' THEN 'Complete'
                        WHEN COUNT(o.idOrders) = SUM(r.printAndTransmitted) THEN 'Pending'
                        ELSE 'Processing'
                    END AS `status`,
                    CASE WHEN o.DOI IS NOT NULL THEN 'Paid' ELSE 'Unpaid' END AS `commissionStatus`,
                    c.idClients, c.clientNo, c.clientName,
                    s.idsalesmen, e.lastName AS `salesmanLastName`, e.firstName AS `salesmanFirstName`,
                    sg.id AS `groupId`, sg.groupName,
                    rt.idreportType, rt.number AS `reportTypeNo`, rt.name AS `reportTypeName`,
                    o.orderDate, o.DOI AS `paymentDate`, o.specimenDate
            FROM " . self::DB_CSS . "." . self::TBL_ORDERS . " o
            INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON o.idOrders = r.orderId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON r.testId = t.idtests
            INNER JOIN " . self::DB_CSS . "." . self::TBL_CLIENTS . " c ON o.clientId = c.idClients
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESMEN . " s ON c.salesmen = s.idsalesmen
            INNER JOIN " . self::DB_CSS . "." . self::TBL_SALESGROUP . " sg ON s.salesGroup = sg.id
            INNER JOIN " . self::DB_CSS . "." . self::TBL_EMPLOYEES . " e ON s.employeeID = e.idemployees
            INNER JOIN " . self::DB_CSS . "." . self::TBL_REPORTTYPE . " rt ON o.reportType = rt.idreportType
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_COMMISSIONRATES . " cr ON o.reportType = cr.reportTypeId AND s.idsalesmen = cr.salesmanId
            $where
            GROUP BY o.idOrders
            ORDER BY o.DOI ASC";
        $data = parent::select($sql, $aryInput);


        $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_COMMISSIONPAYMENTLOG . " (orderId, salesmanId, userId, paymentAmount, commissionAmount) VALUES ";
        $aryInput = array();

        foreach ($data as $row) {
            $sql .= "(?, ?, ?, ?, ?), ";
            $aryInput[] = $row['idOrders'];
            $aryInput[] = $row['idsalesmen'];
            $aryInput[] = $idUsers;
            $aryInput[] = $row['payment'];
            $aryInput[] = $row['commission'];
        }
        $sql = substr($sql, 0, strlen($sql) - 2);

        parent::manipulate($sql, $aryInput);

        /*error_log($sql);
        error_log(implode($aryInput, ","));*/


        /*if (count($aryLogData) > 0) {

            $aryInput = array();
            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_COMMISSIONPAYMENTLOG . " (orderId, salesmanId, userId, paymentAmount, commissionAmount) VALUES ";
            foreach($aryLogData as $row) {
                $sql .= "(?, ?, ?, ?, ?), ";
                $orderId = $row['idOrders'];
                $salesmanId = null;
                $userId = $row['userId'];
                $paymentAmount = 0;
                $commissionAmount = 0;

                if (isset($row['salesmanId']) && !empty($row['salesmanId'])) {
                    $salesmanId = $row['salesmanId'];
                }
                if (isset($row['paymentAmount']) && !empty($row['paymentAmount'])) {
                    $paymentAmount = $row['paymentAmount'];
                }
                if (isset($row['commissionAmount']) && !empty($row['commissionAmount'])) {
                    $commissionAmount = $row['commissionAmount'];
                }
                $aryInput[] = $orderId;
                $aryInput[] = $salesmanId;
                $aryInput[] = $userId;
                $aryInput[] = $paymentAmount;
                $aryInput[] = $commissionAmount;
            }

            $sql = substr($sql, 0, strlen($sql) -2);

            error_log($sql);
            error_log(implode($aryInput, ","));

            //parent::manipulate($sql, $aryInp
        }*/
    }

    public function getTestData() {

        $sql = "
            SELECT  p.lastName as `name`,
                    CAST(CONCAT(p.lastName, '@csslis.com') AS CHAR) AS `email`,
                    TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) AS `age`,
                    p.addressCity as `city`
            FROM " . self::DB_CSS . "." . self::TBL_PATIENTS . " p
            LIMIT 100
        ";

        $data = parent::select($sql, null, null);

        return $data;

    }
}

function date_sort($a, $b) {
    return strtotime($a) - strtotime($b);
}