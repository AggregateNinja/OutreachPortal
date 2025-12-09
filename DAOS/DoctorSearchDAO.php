<?php
require_once "DataObject.php";
require_once "DOS/DoctorUser.php";

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DoctorSearchDAO
 *
 * @author Edd
 */
class DoctorSearchDAO extends DataObject {
    protected $Data = array (
        "UnusedOnly" => true
    );

    private $SearchFields = array("number", "name", "address1", "city", "state", "zip", "locationId");
    private $UsedFields = array();

    public function __construct(array $searchFields, array $data = null) {
        parent::__construct($data);

        foreach ($searchFields as $field => $value) {
            if (in_array($field, $this->SearchFields) && $value !== '') {
                $this->UsedFields[$field] = $value;
            }
        }
    }

    public function getDoctors() {
        $sql = "SELECT d.iddoctors, d.number, d.firstName, d.lastName, d.address1, d.city, d.state, d.zip ";

        $locationJoin = "";
        if (self::HasMultiLocation) {
            $sql .= ", l.idLocation, l.locationNo, l.locationName ";
            $locationJoin = "LEFT JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " l ON d.locationId = l.idLocation ";
        }

        $sql .= "FROM " . self::DB_CSS . "." . self::TBL_DOCTORS . " d $locationJoin";

        if ($this->Data['UnusedOnly'] == true) {
            $sql .= "LEFT JOIN " . self::TBL_DOCTORLOOKUP . " dl ON d.iddoctors = dl.doctorId ";
            $sql .= "WHERE dl.userId IS NULL AND ";
        } else {
            $sql .= "WHERE ";
        }

        foreach ($this->UsedFields as $field => $value) {
            if ($field == "number" || $field == "locationId") {
                $sql .= " $field = ? AND ";
            } else if ($field == "name") {
                $sql .= " (firstName LIKE ? || lastName LIKE ?) AND ";
                $this->UsedFields[$field] = "%$value%";
            } else if ($field == "address" || $field == "city" || $field == "state" || $field == "zip") {
                $sql .= " d." . $field . " LIKE ? AND ";
                $this->UsedFields[$field] = "%$value%";
            } else {
                $sql .= "$field LIKE ? AND ";
                $this->UsedFields[$field] = "%$value%";
            }
        }
        $sql = substr($sql, 0, -4); // remove the last "AND " from sql string

        if (array_key_exists("name", $this->UsedFields)) { // remove "name" from UsedFields and add bind values for firstName and lastName
            $name = $this->UsedFields['name'];
            $this->UsedFields['firstName'] = $name;
            $this->UsedFields['lastName'] = $name;
            unset($this->UsedFields['name']);
        }

        $sql .= " ORDER BY number";
        //print_r($this->UsedFields) . "<br /><br />";
        //return $sql;
        $results = parent::select($sql, $this->UsedFields);

        $doctors = array();
        foreach ($results as $row) {
            $currDoctor = new DoctorUser($row);
            //$currDoctor->setData($row);
            $doctors[] = $currDoctor;
        }

        return $doctors;
    }

    public function __get($field) {
        $value = "";
        if (array_key_exists($field, $this->Data)) {
            $value = $this->Data[$field];
        }
        return $value;
    }

    public function __set($field, $value) {
        if (array_key_exists($field, $this->Data)) {
            $this->Data[$field] = $value;
            return true;
        } else {
            //die("Set Parent Field not found");
            return false;
        }
    }
}

?>
