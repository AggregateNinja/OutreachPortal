<?php
/**
 * Created by PhpStorm.
 * User: Ed Bossmeyer
 * Date: 9/26/14
 * Time: 10:12 AM
 */

if (!isset($_SESSION)) {
    session_start();
}

require_once 'SearchCreator.php';
require_once 'ResultOrder.php';
require_once 'DAOS/PendingOrdersDAO.php';
require_once 'OrderCollection.php';

class PendingSearchFactory extends SearchCreator {

    //private $PendingOrders;

    protected function doSearch(array $data = null) {

        $collection = new OrderCollection($data);

        $userId = $_SESSION['id'];
        if (array_key_exists("AdminType", $_SESSION) && array_key_exists("AdminId", $_SESSION)
            && isset($_SESSION['AdminType']) && isset($_SESSION['AdminId']) && $_SESSION['AdminType'] == 7) {
            $userId = $_SESSION['AdminId'];
        }

        $poDAO = new PendingOrdersDAO($userId, $data);
        $orders = $poDAO->getPendingOrders();

        if ($orders != null) {
            $collection->setOrders($orders);
            $collection->setTotalOrders();
            $collection->setTotalPages();
            $collection->setStart();
            $collection->setEnd();

            $aryRequisitionData = array();
            foreach ($orders as $order) {
                $type = 1;
                if ($order->isAdvancedOrder && isset($order->Phlebotomy)) {
                    $type = 4;
                } else if ($order->isAdvancedOrder) {
                    $type = 3;
                } else if (isset($order->Phlebotomy)) {
                    $type = 2;
                }

                $aryRequisitionData[] = array(
                    $order->idOrders,
                    $type,
                    $order->IsReceipted
                );
            }
            $_SESSION['ReqIds'] = $aryRequisitionData;
        }


        if ($orders != null) {
            return $collection;
        }
        return null;
    }
}