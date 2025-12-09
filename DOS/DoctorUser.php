<?php
require_once 'User.php';
require_once 'UserSetting.php';
require_once 'ClientUser.php';

/**
 * Description of DoctorUser
 *
 * @author Edd
 */
class DoctorUser extends User {
    protected $Doctor = array (
        "iddoctors" => "",
        "number" => "",
        "firstName" => "",
        "lastName" => "",
        "NPI" => "",
        "UPIN" => "",
        "address1" => "",
        "address2" => "",
        "city" => "",
        "state" => "",
        "zip" => "",
        "externalId" => "",

        "idLocation" => "",
        "locationNo" => "",
        "locationName" => "",

        "DoctorSignatureSet" => false
    );

    protected $UserSettings = array();

    protected $ClientUsers = array();

    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);

            foreach ($data as $key => $value) {
                if (array_key_exists($key, $this->Doctor)) {
                    $this->Doctor[$key] = $value;
                } elseif ($key == "doctorFirstName") {
                	$this->Doctor['firstName'] = $value;
                } elseif ($key == "doctorLastName") {
                	$this->Doctor['lastName'] = $value;
                } else if ($key == "doctorNumber" || $key == "doctorNo") {
                	$this->Doctor['number'] = $value;
                }
            }

            if (array_key_exists("idUserSettings", $data) && !empty($data['idUserSettings'])) {
                $userSetting = new UserSetting($data);
                $this->UserSettings[$userSetting->idUserSettings] = $userSetting;
            }

            if (array_key_exists("idClients", $data) && !empty($data['idClients'])) {
//               $clientUser = new ClientUser(array(
//                  "idClients" => $data['idClients'],
//                  "clientNo" => $data['clientNo'],
//                  "clientName" => $data['clientName'],
//                  "clientStreet" => $data['clientStreet'],
//                  "clientCity" => $data['clientCity'],
//                  "clientState" => $data['clientState'],
//                  "clientZip" => $data['clientZip'],
//                  "phoneNo" => $data['phoneNo'],
//                  "faxNo" => $data['faxNo'],
//                   "defaultReportType" => $data['defaultReportType']
//               ));
                $clientUser = new ClientUser($data);
               $this->ClientUsers[$data['idClients']] = $clientUser;
            }

            if (array_key_exists("idDoctors", $data)) {
                $this->Data['iddoctors'] = $data['idDoctors'];
            }
        }

    }

    public function __isset($field) {
        $isset = parent::__isset($field);

        if (!$isset) {
            if (($field == "DoctorUser" || $field == "Doctor") && isset($this->Doctor) && is_array($this->Doctor) && array_key_exists("iddoctors", $this->Doctor)) {
                $isset = true;
            }
        }

        return $isset;
    }

   public function addClient(array $data) {
      if (!$this->hasClient($data['idClients'])) {
         $clientUser = new ClientUser($data);
         $this->ClientUsers[$data['idClients']] = $clientUser;
      }
   }

   private function hasClient($idClients) {
      if (!array_key_exists($idClients, $this->ClientUsers)) {
         return false;
      }
      return true;
   }

    public function __get($key) {
        $field = parent::__get($key);

        if (empty($field) && $key != "LastLogin" && $key != "idLoggedIn" && $key != "ip" && $key != "isActive" && $key != "loginDate"
                && $key != "IsRestrictedUser" && $key != "RestrictedUserIds" && $key != "OrderEntrySettings" && $key != "CommonDiagnosisCodes"
                && $key != "CommonDrugs" && $key != "CommonTests") {

            if (array_key_exists($key, $this->Doctor)) {
                $field = $this->Doctor[$key];
            } else if ($key == "idDoctors") {
                $field = $this->Data['iddoctors'];
            } else if ($key == "Doctor") {
                $field = $this->Doctor;
            } else if ($key == "ClientUsers") {
                $field = $this->ClientUsers;

            } else {
                //die ("Doctor User Field Not Found: $key");
                error_log("Doctor User Field Not Found: $key");
                return null;
            }
        }

        return $field;
    }

   public function __set($field, $value) {

       $done = parent::__set($field, $value);
       if (!$done) {
           if (array_key_exists($field, $this->Doctor)) {
               $this->Doctor[$field] = $value;
               $done = true;
           }
       }
       return $done;
   }





    public function setAll(array $data) {
        parent::setAll($data);

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->Doctor))
                $this->Doctor[$key] = $value;
        }

    }

}
