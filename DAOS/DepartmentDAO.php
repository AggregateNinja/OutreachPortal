<?php
require_once 'DataObject.php';

/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 11/28/2017
 * Time: 4:41 PM
 */
class DepartmentDAO extends DataObject {

    public static function getOrdersPerDepartment(array $input) {
        $sql = "
            SELECT	d.idDepartment, d.deptNo, d.deptName,
                    d.ReferenceLab,
                    COUNT(DISTINCT o.idOrders) AS `OrderCount`
            FROM " . self::DB_CSS . "." . self::TBL_DEPARTMENTS . " d
            INNER JOIN " . self::DB_CSS . "." . self::TBL_TESTS . " t ON d.idDepartment = t.department
            INNER JOIN " . self::DB_CSS . "." . self::TBL_RESULTS . " r ON t.idtests = r.testId
            INNER JOIN " . self::DB_CSS . "." . self::TBL_ORDERS . " o ON r.orderId = o.idOrders
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_PREFERENCES . " pre ON pre.`key` = 'POCTest' AND (r.testId = pre.value OR r.panelId = pre.value)
            WHERE   (pre.`key` IS NULL OR pre.`key` != 'POCTest')
                    AND o.orderDate BETWEEN ? AND ?
            GROUP BY d.deptNo
            ORDER BY d.deptName ASC
        ";

        $dateFrom = $input['dateFrom'];
        $dateTo = $input['dateTo'];

        $aryInput = array($dateFrom, $dateTo);

        return parent::select($sql, $aryInput);
    }

}