<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'DAOS/AdminDAO.php';
require_once 'Utility/ItemSorter.php';

class AdminClient {
    
    private $AdminDAO;
    public $Users;
    
    private $Message = "";
    
    // sorting variables
    private $Direction;
    
    public function __construct(array $data = null) {
        
        $this->Direction = "desc";
        $this->AdminDAO = new AdminDAO($data);

        $this->AdminDAO->resetErrorFields();
        $this->setMessage();
        $this->getUsers();
    }
    
    public function __get($field) {
        if ($field == "Message") {
            return $this->Message;
        } else if ($field == "Users") {
            return $this->Users;
        } else if ($field == "Direction") {
            return $this->Direction;
        }
    }    
    
    private function getUsers() {
        if(!isset($_SESSION['users']) || (isset($_SESSION['msg']) || isset($_SESSION['signedout'])) || (isset($_GET['msg']) && ($_GET['msg'] == 1 || $_GET['msg'] == 2 || $_GET['msg'] == 3))) {
            $this->Users = $this->AdminDAO->getUsers();
            $_SESSION['users'] = serialize($this->Users);

            if (isset($_SESSION['signedout'])) {
                $_SESSION['signedout'] = "";
                unset($_SESSION['signedout']);
            }
        } else {
            $this->Users = unserialize($_SESSION['users']);

            //echo "<pre>"; print_r($this->Users); echo "</pre>";
        }

        //$aryInput = array();
        /*if (array_key_exists("type", $_GET)) {
            if ($_GET['type'] == "Administrators") {
                $aryInput['typeId'] = 1;
            } else if ($_GET['type'] == "Clients") {
                $aryInput['typeId'] = 2;
            } else if ($_GET['type'] == "Doctors") {
                $aryInput['typeId'] = 3;
            } else if ($_GET['type'] == "Salesmen") {
                $aryInput['typeId'] = 5;
            } else if ($_GET['type'] == "Insurances") {
                $aryInput['typeId'] = 6;
            } else if ($_GET['type'] == "OrderEntryAdmins") {
                $aryInput['typeId'] = 7;
            } else if ($_GET['type'] == "Patients") {
                $aryInput['typeId'] = 4;
            }
        }*/
        /*if (array_key_exists("clientName", $_GET)) {
            $aryInput['clientName'] = $_GET['clientName'];
        }

        $this->Users = $this->AdminDAO->getUsers($aryInput);*/

        $this->sortUsers();
    }
    
    private function sortUsers() {
        if (isset($_GET['direction']) && isset($_GET['sortby'])) {
            if ($_GET['direction'] == "asc") {
                $this->Direction = "desc";
            } else {
                $this->Direction = "asc";
            }
        }

        if (isset($_GET['type']) && isset($_GET['sortby'])) {
            if ($_GET['type'] == 1) {
                if ($_GET['sortby'] == "id") {
                    usort($this->Users['Administrators'], array("ItemSorter", "byId"));
                } elseif ($_GET['sortby'] == "email") {
                    usort($this->Users['Administrators'], array("ItemSorter", "byEmail"));
                } elseif ($_GET['sortby'] == "dateCreated") {
                    usort($this->Users['Administrators'], array("ItemSorter", "byDateCreated"));
                }
            } elseif ($_GET['type'] == 2) {
                if ($_GET['sortby'] == "clientNo") {
                    usort($this->Users['Clients'], array("ItemSorter", "byClientNo"));
                } elseif ($_GET['sortby'] == "email") {
                    usort($this->Users['Clients'], array("ItemSorter", "byEmail"));
                } elseif ($_GET['sortby'] == "clientName") {
                    usort($this->Users['Clients'], array("ItemSorter", "byClientName"));
                } elseif ($_GET['sortby'] == "clientStreet") {
                    usort($this->Users['Clients'], array("ItemSorter", "byClientStreet"));
                }
            } elseif ($_GET['type'] == 3) {
                if ($_GET['sortby'] == "number") {
                    usort($this->Users['Doctors'], array("ItemSorter", "byNumber"));
                } elseif ($_GET['sortby'] == "email") {
                    usort($this->Users['Doctors'], array("ItemSorter", "byEmail"));
                } elseif ($_GET['sortby'] == "name") {
                    usort($this->Users['Doctors'], array("ItemSorter", "byName"));
                } elseif ($_GET['sortby'] == "address") {
                    usort($this->Users['Doctors'], array("ItemSorter", "byAddress"));
                }       
            } else if ($_GET['type'] == 4) { // patient
                if ($_GET['sortby'] == "email") {
                    usort($this->Users['Patients'], array("ItemSorter", "byEmail"));
                } elseif ($_GET['sortby'] == "name") {
                    usort($this->Users['Patients'], array("ItemSorter", "byPatientFirstName"));
                } elseif ($_GET['sortby'] == "dob") {
                    usort($this->Users['Patients'], array("ItemSorter", "byPatientDob"));
                }
            } else if ($_GET['type'] == 5) { // sales person
                if ($_GET['sortby'] == "email") {
                    usort($this->Users['Salesmen'], array("ItemSorter", "byEmail"));
                } elseif ($_GET['sortby'] == "name") {
                    usort($this->Users['Salesmen'], array("ItemSorter", "byName"));
                } elseif ($_GET['sortby'] == "territory") {
                    usort($this->Users['Salesmen'], array("ItemSorter", "byTerritory"));
                } elseif ($_GET['sortby'] == "groupName") {
                    usort($this->Users['Salesmen'], array("ItemSorter", "byGroupName"));
                }
            } else if ($_GET['type'] == 8) {
                if ($_GET['sortby'] == "id") {
                    usort($this->Users['PatientAdmins'], array("ItemSorter", "byId"));
                } elseif ($_GET['sortby'] == "email") {
                    usort($this->Users['PatientAdmins'], array("ItemSorter", "byEmail"));
                } elseif ($_GET['sortby'] == "dateCreated") {
                    usort($this->Users['PatientAdmins'], array("ItemSorter", "byDateCreated"));
                }
            }
            $_SESSION['users'] = serialize($this->Users);
        } else {
            usort($this->Users['Clients'], array("ItemSorter", "byClientNumAscFixed"));
            usort($this->Users['Doctors'], array("ItemSorter", "byDoctorNumAscFixed"));
            $_SESSION['users'] = serialize($this->Users);
        }
    }

    private function setMessage() {
        if (isset($_SESSION['msg'])) {
            $this->Message = "<h4 style=\"text-align: center;\" id='msg'>" . $_SESSION['msg'] . "</h4>";
            $_SESSION['msg'] = "";
            unset($_SESSION['msg']);
        }
    }   
    
}

?>
