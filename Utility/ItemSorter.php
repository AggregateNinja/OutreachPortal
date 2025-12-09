<?php

/**
 * Description of ItemSorter
 *
 * @author Edd
 */
class ItemSorter {

    public static function byAccessionDescFixed($a, $b) {
        if ($a->accession == $b->accession) {
            return 0;
        }
        if ($a->accession < $b->accession) {
            return 1;
        } else {
            return -1;
        }
    }
    public static function byDoctorLastNameDescFixed($a, $b) {
        if ($a->doctorLastName == $b->doctorLastName) {
            return 0;
        }
        if ($a->doctorLastName < $b->doctorLastName) {
            return 1;
        } else {
            return -1;
        }
    }
    public static function byDoctorNumDescFixed($a, $b) {
        if ($a->number == $b->number) {
            return 0;
        }
        if ($a->number < $b->number) {
            return 1;
        } else {
            return -1;
        }
    }
    public static function byClientNameDescFixed($a, $b) {
        if ($a->clientName == $b->clientName) {
            return 0;
        }
        if ($a->clientName < $b->clientName) {
            return 1;
        } else {
            return -1;
        }
    }
    public static function byClientNumDescFixed($a, $b) {
        if ($a->clientNo == $b->clientNo) {
            return 0;
        }
        if ($a->clientNo < $b->clientNo) {
            return 1;
        } else {
            return -1;
        }
    }
    public static function byPatientFirstNameDescFixed($a, $b) {
        if ($a->patientFirstName == $b->patientFirstName) {
            return 0;
        }
        if ($a->patientFirstName < $b->patientFirstName) {
            return 1;
        } else {
            return -1;
        }
    }
    public static function byPatientLastNameDescFixed($a, $b) {
        if ($a->patientLastName == $b->patientLastName) {
            return 0;
        }
        if ($a->patientLastName < $b->patientLastName) {
            return 1;
        } else {
            return -1;
        }
    }

    public static function byOrderDateDescFixed($a, $b) {
        if ($a->orderDate == $b->orderDate) {
            return 0;
        }
        if ($a->orderDate < $b->orderDate) {
            return 1;
        } else {
            return -1;
        }
    }
    public static function bySpecimenDateDescFixed($a, $b) {
        if ($a->specimenDate == $b->specimenDate) {
            return 0;
        }
        if ($a->specimenDate < $b->specimenDate) {
            return 1;
        } else {
            return -1;
        }
    }
    public static function byOrderStatusDescFixed($a, $b) {
        if ($a->OrderStatus == $b->OrderStatus) {
            return 0;
        }
        if ($a->OrderStatus < $b->OrderStatus) {
            return 1;
        } else {
            return -1;
        }
    }
    public static function byAccessionAscFixed($a, $b) {
        if ($a->accession == $b->accession) {
            return 0;
        }
        if ($a->accession < $b->accession) {
            return -1;
        } else {
            return 1;
        }
    }
    public static function byDoctorLastNameAscFixed($a, $b) {
        if ($a->doctorLastName == $b->doctorLastName) {
            return 0;
        }
        if ($a->doctorLastName < $b->doctorLastName) {
            return -1;
        } else {
            return 1;
        }
    }
    public static function byDoctorNumAscFixed($a, $b) {
        if ($a->number == $b->number) {
            return 0;
        }
        if ($a->number < $b->number) {
            return -1;
        } else {
            return 1;
        }
    }
    public static function byClientNameAscFixed($a, $b) {
        if ($a->clientName == $b->clientName) {
            return 0;
        }
        if ($a->clientName < $b->clientName) {
            return -1;
        } else {
            return 1;
        }
    }
    public static function byClientNumAscFixed($a, $b) {
        if ($a->clientNo == $b->clientNo) {
            return 0;
        }
        if ($a->clientNo < $b->clientNo) {
            return -1;
        } else {
            return 1;
        }
    }
    public static function byPatientFirstNameAscFixed($a, $b) {
        if ($a->patientFirstName == $b->patientFirstName) {
            return 0;
        }
        if ($a->patientFirstName < $b->patientFirstName) {
            return -1;
        } else {
            return 1;
        }
    }
    public static function byPatientLastNameAscFixed($a, $b) {
        if ($a->patientLastName == $b->patientLastName) {
            return 0;
        }
        if ($a->patientLastName < $b->patientLastName) {
            return -1;
        } else {
            return 1;
        }
    }

    public static function byOrderDateAscFixed($a, $b) {
        if ($a->orderDate == $b->orderDate) {
            return 0;
        }
        if ($a->orderDate < $b->orderDate) {
            return -1;
        } else {
            return 1;
        }
    }
    public static function bySpecimenDateAscFixed($a, $b) {
        if ($a->specimenDate == $b->specimenDate) {
            return 0;
        }
        if ($a->specimenDate < $b->specimenDate) {
            return -1;
        } else {
            return 1;
        }
    }
    public static function byOrderStatusAscFixed($a, $b) {
        if ($a->OrderStatus == $b->OrderStatus) {
            return 0;
        }
        if ($a->OrderStatus < $b->OrderStatus) {
            return -1;
        } else {
            return 1;
        }
    }



    // ------------------------------------------- client/doctor/admin sorting functions
    public static function byEmail($a, $b) {
        return self::compare($a, $b, "email");
    }
    
    // ------------------------------------------- admin sorting functions
    public static function byId($a, $b) {
        return self::compare($a, $b, "idAuth");
    }
    public static function byDateCreated($a, $b) {
        return self::compare($a, $b, "dateCreated");
    }    
    
    // -------------------------------------------- client sorting functions
    public static function byClientNo($a, $b) {
        return self::compare($a, $b, "clientNo");
    }
    public static function byClientName($a, $b) {
        return self::compare($a, $b, "clientName");
    }
    public static function byClientStreet($a, $b) {
        return self::compare($a, $b, "clientStreet");
    }
    
    // ---------------------------------------------- doctor sorting functions
    public static function byName($a, $b) {
        return self::compare($a, $b, "firstName");
    }
    public static function byAddress($a, $b) {
        return self::compare($a, $b, "address1");
    }
    public static function byNumber($a, $b) {
        return self::compare($a, $b, "number");
    }
    
    // ---------------------------------------------- result/order sorting functions
    public static function byAccession($a, $b) {
        return self::compare($a, $b, "accession");
    }
    public static function byOrderDate($a, $b) {
        return self::compare($a, $b, "orderDate");
    }
    public static function byOrderDateDESC($a, $b) {
        return self::compare($a, $b, "orderDate");
    }

    // ---------------------------------------------- patient sorting functions
    public static function byPatientFirstName($a, $b) {
        return self::compare($a, $b, "firstName");
    }
    public static function byPatientLastName($a, $b) {
        return self::compare($a, $b, "lastName");
    }
    public static function byPatientDob($a, $b) {
        return self::compare($a, $b, "dob");
    }
    
    // ---------------------------------------------- subscriber sorting functions
    public static function bySubscriberLastName($a, $b) {
        return self::compare($a, $b, "lastName");
    }
    
    // -------------------------------------------- View pending orders sorting functions
    public static function bySpecimenDate($a, $b) {
        return self::compare($a, $b, "specimenDate");
    }
    public static function byDoctorFirstName($a, $b) {
        return self::compare($a, $b, "doctorFirstName");
    }
    public static function byPatientFirstName2($a, $b) {
        return self::compare($a, $b, "patientFirstName");
    }
    public static function byInsuranceName($a, $b) {
        return self::compare($a, $b, "insuranceName");
    }
    
    public static function byDoctorLastName($a, $b) {
        return self::compare($a, $b, "doctorLastName");
    }

    public static function byTerritory($a, $b) {
        return self::compare($a, $b, "territoryName");
    }

    public static function byGroupName($a, $b) {
        return self::compare($a, $b, "groupName");
    }

    public static function getDirection(array $settings = null) {
        if ($settings != null && array_key_exists("Direction", $settings) && ($settings['Direction'] == "desc" || $settings['Direction'] == "asc")) {
            return $settings['Direction'];
        } else {
            if (isset($_REQUEST['direction'])) {
                return $_REQUEST['direction'];
            } else {
                return "asc";
            }  
        }
                                      
    }

    public static function compare($a, $b, $field, array $settings = null) {
        $direction = self::getDirection($settings);

        if (strtolower($a->$field) == strtolower($b->$field)) {
            return 0;
        }

        if (strtolower($a->$field) < strtolower($b->$field)) {
            if ($direction == "desc") {
                return 1;
            } else {
                return -1;
            }

        } else {
            if ($direction == "asc") {
                return 1;
            } else {
                return -1;
            }
        }
    }
}
?>
