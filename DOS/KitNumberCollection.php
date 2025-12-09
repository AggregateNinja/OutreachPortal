<?php
/**
 * Created by PhpStorm.
 * User: eboss
 * Date: 9/23/2025
 * Time: 1:18 PM
 */

class KitNumberCollection
{
    private $KitNumberData = array (
        "KitsPerPage" => 100,
        "CurrentPage" => 1,
        "Start" => 0,
        "End" => 9,
        "TotalKits" => 0,
        "TotalPages" => 1,
        "OrderBy" => "kitNumber",
        "Direction" => "asc",
        "KitNumberSearch" => ""
    );

    private $KitNumbers = array();

    public function __construct(array $data = null) {
        if ($data != null) {
            foreach ($data as $key => $value) {
                if (array_key_exists($key, $this->KitNumberData)) {
                    $this->KitNumberData[$key] = $value;
                }
            }
        }
    }

    public function setKitNumbers(array $kitNumbers) {
        $this->KitNumbers = $kitNumbers;
    }

    public function setTotalKitNumbers() {
        $this->KitNumberData['TotalKits'] = count($this->KitNumbers);
    }

    public function setTotalPages() {
        $tmpTotal = $this->KitNumberData['TotalKits'] / $this->KitNumberData['KitsPerPage'];
        if ($this->isDecimal($tmpTotal)) {
            $tmpTotal = substr($tmpTotal, 0, strpos($tmpTotal, '.'));
            $tmpTotal += 1;
        }
        if (is_numeric($tmpTotal)) {
            $this->KitNumberData['TotalPages'] = $tmpTotal;
        }
    }


    public function setStart() {
        if ($this->KitNumberData['CurrentPage'] != 1) {
            $this->KitNumberData['Start'] = ($this->KitNumberData['CurrentPage'] * $this->KitNumberData['KitsPerPage']) - $this->KitNumberData['KitsPerPage'];
        }
    }

    public function setEnd() {
        if ($this->KitNumberData['TotalKits'] > $this->KitNumberData['Start'] + $this->KitNumberData['KitsPerPage']) {
            $this->KitNumberData['End'] = $this->KitNumberData['Start'] + $this->KitNumberData['KitsPerPage'] - 1;
        } else {
            $this->KitNumberData['End'] = $this->KitNumberData['TotalKits'] - 1;
        }
    }

    public function __get($field) {
        $value = null;
        if ($field == "KitNumbers") {
            $value = $this->KitNumbers;
        } else if (array_key_exists($field, $this->KitNumberData)) {
            $value = $this->KitNumberData[$field];
        } else if ($field == "KitNumberData") {
            $value = $this->KitNumberData;
        }
        return $value;
    }

    public function isDecimal($val) {
        if (is_numeric($val) && floor($val) != $val) {
            return true;
        }
        return false;
    }


    public function getKitNumberArray() {
        $aryKitNumbers = array();

        foreach($this->KitNumbers as $kitNumber) {
            $currKitNumber = array(
                "idKitNumbers" => $kitNumber->idKitNumbers,
                "productName" => $kitNumber->productName,
                "lotNumber" => $kitNumber->lotNumber,
                "kitNumber" => $kitNumber->kitNumber,
                "description" => $kitNumber->description,
                "dateCreated" => $kitNumber->dateCreated,
                "dateUpdated" => $kitNumber->dateUpdated,
                "isActive" => $kitNumber->isActive,
                "isPrepaid" => $kitNumber->isPrepaid

            );
            $aryKitNumbers[] = $currKitNumber;
        }
        //return serialize($aryOrders);
        return $aryKitNumbers;
    }

}