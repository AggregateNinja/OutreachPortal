<?php
require_once "DataObject.php";
require_once "DOS/ClientUser.php";

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ClientSearch
 *
 * @author Edd
 */
class ClientSearchDAO extends DataObject {
	protected $Data = array (
		"UnusedOnly" => true
	);
	
    private $SearchFields = array ("clientNo", "clientName", "clientStreet", "clientCity", "clientState", "clientZip", "location");
    private $UsedFields = array();
    
    public function __construct(array $searchFields, array $data = null) {
    	parent::__construct($data);
    	
        foreach ($searchFields as $field => $value) {
            if (in_array($field, $this->SearchFields) && !empty($value)) {
                $this->UsedFields[$field] = $value;
            }
        }
    }
    
    public function getClients() {
        $sql = "
            SELECT c.idClients, c.clientNo, c.clientName, c.clientStreet, c.clientCity, c.clientState, c.clientZip,
              l.idLocation, l.locationNo, l.locationName
            FROM " . self::DB_CSS . "." . self::TBL_CLIENTS . " c 
            LEFT JOIN " . self::DB_CSS . "." . self::TBL_LOCATIONS . " l ON c.location = l.idLocation";

        if ($this->Data['UnusedOnly'] == true) {
        	$sql .= " LEFT JOIN " . self::TBL_CLIENTLOOKUP . " cl ON c.idClients = cl.clientId ";
        	$sql .= " WHERE cl.userId IS NULL AND ";
        } else {
            if (is_array($this->UsedFields) && count($this->UsedFields) > 0) {
                $sql .= " WHERE ";
            }
        }

        if (is_array($this->UsedFields) && count($this->UsedFields) > 0) {
            foreach ($this->UsedFields as $field => $value) {
                if ($field == "clientNo") {
                    $sql .= "clientNo = ? AND ";
                } else if ($field == "location") {
                    $sql .= "c.location = ? AND ";
                } else {
                    $sql .= "$field LIKE ? AND ";
                    $this->UsedFields[$field] = "%$value%";
                }
            }
            $sql = substr($sql, 0, -4);
        }
        $sql .= " ORDER BY clientNo";
        
        $results = parent::select($sql, $this->UsedFields);
        
        $clients = array();
        foreach ($results as $row) {
            $currClient = new ClientUser($row);
            //$currClient->setData($row);
            $clients[] = $currClient;
        }
        
        //print_r($this->UsedFields);
        //print_r($sql);
        
        return $clients;
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
