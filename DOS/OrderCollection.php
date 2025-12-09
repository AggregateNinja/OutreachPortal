<?php
/**
 * Created by PhpStorm.
 * User: Ed Bossmeyer
 * Date: 9/29/14
 * Time: 9:34 AM
 */

require_once 'ResultOrder.php';

class OrderCollection {

    private $OrderData = array (
        "OrdersPerPage" => 10,
        "CurrentPage" => 1,
        "Start" => 0,
        "End" => 9,
        "TotalOrders" => 0,
        "TotalPages" => 1,
        "OrderBy" => "orderDate",
        "Direction" => "desc"
    );

    private $Orders = array();

    public function __construct(array $data = null) {
        if ($data != null) {
            foreach ($data as $key => $value) {
                if (array_key_exists($key, $this->OrderData)) {
                    $this->OrderData[$key] = $value;
                }
            }
        }
    }

    public function setOrders(array $orders) {
        $this->Orders = $orders;
    }

    public function setTotalOrders() {
        $this->OrderData['TotalOrders'] = count($this->Orders);
    }

    public function setTotalPages() {
        $tmpTotal = $this->OrderData['TotalOrders'] / $this->OrderData['OrdersPerPage'];
        if ($this->isDecimal($tmpTotal)) {
            $tmpTotal = substr($tmpTotal, 0, strpos($tmpTotal, '.'));
            $tmpTotal += 1;
        }
        if (is_numeric($tmpTotal)) {
            $this->OrderData['TotalPages'] = $tmpTotal;
        }
    }


    public function setStart() {
        if ($this->OrderData['CurrentPage'] != 1) {
            $this->OrderData['Start'] = ($this->OrderData['CurrentPage'] * $this->OrderData['OrdersPerPage']) - $this->OrderData['OrdersPerPage'];
        }
    }

    public function setEnd() {
        if ($this->OrderData['TotalOrders'] > $this->OrderData['Start'] + $this->OrderData['OrdersPerPage']) {
            $this->OrderData['End'] = $this->OrderData['Start'] + $this->OrderData['OrdersPerPage'] - 1;
        } else {
            $this->OrderData['End'] = $this->TotalOrders - 1;
        }
    }

    public function __get($field) {
        $value = null;
        if ($field == "Orders") {
            $value = $this->Orders;
        } else if (array_key_exists($field, $this->OrderData)) {
            $value = $this->OrderData[$field];
        } else if ($field == "OrderData") {
            $value = $this->OrderData;
        }
        return $value;
    }

    public function isDecimal($val) {
        if (is_numeric($val) && floor($val) != $val) {
            return true;
        }
        return false;
    }


    public function getOrderArray() {
        $aryOrders = array();

        foreach($this->Orders as $order) {
            $idPhlebotomy = null;
            $receiptedDate = null;
            if (isset($order->Phlebotomy) && isset($order->Phlebotomy->idPhlebotomy) && $order->Phlebotomy->idPhlebotomy != null) {
                $idPhlebotomy = $order->Phlebotomy->idPhlebotomy;
            }
            //if ($order->DateReceipted != null && $order->TimeReceipted != null) {
            //    $receiptedDate = $order->DateReceipted . " " . $order->TimeReceipted;
            //}

            $idinsurances = null;
            $insuranceName = null;
            if (isset($order->Insurance)) {
                $idinsurances = $order->Insurance->idinsurances;
                $insuranceName =$order->Insurance->name;
            }


            $currOrder = array(
                "idOrders" => $order->idOrders,
                "doctorId" => $order->doctorId,
                "clientId" => $order->clientId,
                "accession" => $order->accession,
                "locationId" => $order->locationId,
                "orderDate" => $order->orderDate,
                "specimenDate" => $order->specimenDate,
                "patientId" => $order->patientId,
                "insurance" => $order->insurance,
                "isAdvancedOrder" => $order->isAdvancedOrder,
                "idClients" => $order->Client->idClients,
                "clientName" => $order->Client->clientName,
                "clientNo" => $order->Client->clientNo,
                "idinsurances" => $idinsurances,
                "insuranceName" => $insuranceName,
                "idDoctors" => $order->Doctor->iddoctors,
                "doctorNo" => $order->Doctor->number,
                "doctorFirstName" => $order->Doctor->firstName,
                "doctorLastName" => $order->Doctor->lastName,
                "idPatients" => $order->Patient->idPatients,
                "patientFirstName" => $order->Patient->firstName,
                "patientLastName" => $order->Patient->lastName,
                "idPhlebotomy" => $idPhlebotomy,
                "receiptedDate" => $receiptedDate,
                "IsReceipted" => $order->IsReceipted
            );
            $aryOrders[] = $currOrder;
        }
        //return serialize($aryOrders);
        return $aryOrders;
    }


} 