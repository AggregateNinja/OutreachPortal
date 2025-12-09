<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 4/16/2020
 * Time: 2:39 PM
 */
require_once 'DataObject.php';

class RequiredFieldsDAO extends DataObject {

    private $Conn;

    private $ClientIds;
    private $ClientFields;
    private $GlobalFields;
    private $IgnoreGlobalFields;

    private $ClientRequiredFieldsId = 0;
    private $GlobalRequiredFieldsId = 0;
    private $IgnoreGlobalFieldsId = 0;
    private $ClientPropertyRequiredFieldIds = array();

    public function __construct(array $data, array $settings = null) {

        if ($settings != null && array_key_exists("Conn", $settings) && $settings['Conn'] instanceof mysqli) {
            $this->Conn = $settings['Conn'];
        } else {
            $this->Conn = parent::connect();
        }

        $this->ClientIds = array();
        $this->ClientFields = array();
        $this->GlobalFields = array();
        $this->IgnoreGlobalFields = false;

        if (array_key_exists("clients", $data)) {
            if (!is_array($data['clients'])) {
                $this->ClientIds = array($data['clients']);
            } else {
                $this->ClientIds = $data['clients'];
            }

        }
        if (array_key_exists("clientFields", $data)) {

            $patientHeight = false;
            if (in_array("patientHeightFeet", $data['clientFields']) && in_array("patientHeightInches", $data['clientFields'])) {
                $this->ClientFields[] = "patientHeight";
                $patientHeight = true;
            }
            $patientCityStateZip = false;
            if (in_array("patientCity", $data['clientFields']) && in_array("patientState", $data['clientFields']) && in_array("patientZip", $data['clientFields'])) {
                $this->ClientFields[] = "patientCityStateZip";
                $patientCityStateZip = true;
            }
            $subscriberCityStateZip = false;
            if (in_array("subscriberCity", $data['clientFields']) && in_array("subscriberState", $data['clientFields']) && in_array("subscriberZip", $data['clientFields'])) {
                $this->ClientFields[] = "subscriberCityStateZip";
                $subscriberCityStateZip = true;
            }

            foreach ($data['clientFields'] as $fieldName) {
                if ((($fieldName != "patientHeightFeet" && $fieldName != "patientHeightInches") || $patientHeight == false)
                    && (($fieldName != "patientCity" && $fieldName != "patientState" && $fieldName != "patientZip") || $patientCityStateZip == false)
                    && (($fieldName != "subscriberCity" && $fieldName != "subscriberState" && $fieldName != "subscriberZip") || $subscriberCityStateZip == false)
                ) {
                    $this->ClientFields[] = $fieldName;
                }

            }

            $this->ClientFields = $data['clientFields'];
        }
        if (array_key_exists("globalFields", $data)) {

            $patientHeight = false;
            if (in_array("patientHeightFeet", $data['globalFields']) && in_array("patientHeightInches", $data['globalFields'])) {
                $this->GlobalFields[] = "patientHeight";
                $patientHeight = true;
            }
            $patientCityStateZip = false;
            if (in_array("patientCity", $data['globalFields']) && in_array("patientState", $data['globalFields']) && in_array("patientZip", $data['globalFields'])) {
                $this->GlobalFields[] = "patientCityStateZip";
                $patientCityStateZip = true;
            }
            $subscriberCityStateZip = false;
            if (in_array("subscriberCity", $data['globalFields']) && in_array("subscriberState", $data['globalFields']) && in_array("subscriberZip", $data['globalFields'])) {
                $this->GlobalFields[] = "subscriberCityStateZip";
                $subscriberCityStateZip = true;
            }

            foreach ($data['globalFields'] as $fieldName) {
                if ((($fieldName != "patientHeightFeet" && $fieldName != "patientHeightInches") || $patientHeight == false)
                    && (($fieldName != "patientCity" && $fieldName != "patientState" && $fieldName != "patientZip") || $patientCityStateZip == false)
                    && (($fieldName != "subscriberCity" && $fieldName != "subscriberState" && $fieldName != "subscriberZip") || $subscriberCityStateZip == false)
                ) {
                    $this->GlobalFields[] = $fieldName;
                }
            }
        }
        if (array_key_exists("ignoreGlobalFields", $data) && $data['ignoreGlobalFields'] == 1) {
            $this->IgnoreGlobalFields = true;
        }

        /*echo "<pre>"; print_r($this->ClientIds); echo "</pre>";
        echo "<pre>"; print_r($this->ClientFields); echo "</pre>";
        echo "<pre>"; print_r($this->GlobalFields); echo "</pre>";
        */

        $sql = "SELECT cp.id FROM " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTIES . " cp WHERE cp.name = 'Required Fields'";
        $data = parent::select($sql, null, array("Conn" => $this->Conn));
        if (count($data) > 0) {
            $this->GlobalRequiredFieldsId = $data[0]['id'];
        }

        $sql = "SELECT cp.id FROM " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTIES . " cp WHERE cp.name = 'Client Required Fields'";
        $data = parent::select($sql, null, array("Conn" => $this->Conn));
        if (count($data) > 0) {
            $this->ClientRequiredFieldsId = $data[0]['id'];
        }

        $sql = "SELECT cp.id FROM " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTIES . " cp WHERE cp.name = 'Ignore Global Fields'";
        $data = parent::select($sql, null, array("Conn" => $this->Conn));
        if (count($data) > 0) {
            $this->IgnoreGlobalFieldsId = $data[0]['id'];
        }

        if (isset($this->ClientRequiredFieldsId) && !empty($this->ClientRequiredFieldsId)) {
            $sql = "SELECT cpr.idRequiredFields, cpr.fieldName FROM " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTYREQUIREDFIELDS . " cpr WHERE cpr.clientPropertyId = $this->ClientRequiredFieldsId";
            $data = parent::select($sql, null, array("Conn" => $this->Conn));
            if (count($data) > 0) {
                foreach ($data as $row) {
                    $this->ClientPropertyRequiredFieldIds[$row['fieldName']] = $row['idRequiredFields'];
                }
            }
        }

    }

    public function updateRequiredFields() {
        if (count($this->ClientIds) > 0) {
            // delete lookups for selected clients
            $input = array();
            $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTYREQUIREDFIELDLOOKUP . " WHERE clientId IN (";
            foreach ($this->ClientIds as $clientId) {
                $sql .= "?,";
                $input[] = $clientId;
            }
            $sql = substr($sql, 0, strlen($sql) -1) . ")";
            parent::manipulate($sql, $input, array("Conn" => $this->Conn));

            if (count($this->ClientFields) > 0) {
                // insert lookups for selected clients
                $input = array();
                $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTYREQUIREDFIELDLOOKUP . " (clientId, requiredFieldId) VALUES ";
                foreach ($this->ClientIds as $clientId) {
                    foreach ($this->ClientFields as $fieldName) {
                        if (array_key_exists($fieldName, $this->ClientPropertyRequiredFieldIds)) {
                            $sql .= "(?, ?),";
                            $input[] = $clientId;
                            $input[] = $this->ClientPropertyRequiredFieldIds[$fieldName];
                        }

                    }
                }
                if (count($input) > 0) {
                    $sql = substr($sql, 0, strlen($sql) -1);
                    parent::manipulate($sql, $input, array("Conn" => $this->Conn));
                }

            }

            // delete ignore globals flag for selected clients
            $input = array($this->IgnoreGlobalFieldsId);
            $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTYLOOKUP . " WHERE clientPropertyId = ? AND clientId IN (";
            foreach ($this->ClientIds as $clientId) {
                $sql .= "?,";
                $input[] = $clientId;
            }
            $sql = substr($sql, 0, strlen($sql) -1) . ")";
            parent::manipulate($sql, $input, array("Conn" => $this->Conn));

            if ($this->IgnoreGlobalFields == true) {
                // insert ignore globals flag for selected clients
                $input = array();
                $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTYLOOKUP . " (clientId, clientPropertyId, createdByUserId) VALUES ";
                foreach ($this->ClientIds as $clientId) {
                    $sql .= "(?, ?, ?),";
                    $input[] = $clientId;
                    $input[] = $this->IgnoreGlobalFieldsId;
                    $input[] = 0;
                }
                $sql = substr($sql, 0, strlen($sql) -1);
                parent::manipulate($sql, $input, array("Conn" => $this->Conn));
            }
        }

        // delete lookups for global fields
        $sql = "DELETE FROM " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTYREQUIREDFIELDS . " WHERE clientPropertyId = ?";
        parent::manipulate($sql, array($this->GlobalRequiredFieldsId), array("Conn" => $this->Conn));
        if (count($this->GlobalFields) > 0 && isset($this->GlobalRequiredFieldsId) && !empty($this->GlobalRequiredFieldsId)) {
            // insert global fields
            $input = array();
            $sql = "INSERT INTO " . self::DB_CSS . "." . self::TBL_CLIENTPROPERTYREQUIREDFIELDS . " (clientPropertyId, fieldName) VALUES ";
            foreach ($this->GlobalFields as $fieldName) {
                $sql .= "(?, ?),";
                $input[] = $this->GlobalRequiredFieldsId;
                $input[] = $fieldName;
            }
            $sql = substr($sql, 0, strlen($sql) -1);
            parent::manipulate($sql, $input, array("Conn" => $this->Conn));
        }




    }
}