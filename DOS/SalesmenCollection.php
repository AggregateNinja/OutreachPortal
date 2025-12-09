<?php
/**
 * Created by PhpStorm.
 * User: Ed Bossmeyer
 * Date: 10/15/14
 * Time: 12:11 PM
 */

require_once 'Salesman.php';
require_once 'ICollection.php';

class SalesmenCollection implements ICollection {

    private $Data = array();
    private $Salesmen = array();

    public function __construct(array $data = null) {
        if ($data != null) {
            $this->Data = $data;
        }
    }

    public function setCollection() {
        foreach ($this->Data as $row) {
            $this->Salesmen[] = new Salesman($row);
        }

    }

    public function getCollection() {
        //echo "<pre>"; print_r($this->Salesmen); echo "</pre>";
        return $this->Salesmen;
    }

} 