<?php
require_once 'DAOS/ClientDAO.php';
require_once 'DAOS/DoctorDAO.php';
require_once 'DAOS/AdminDAO.php';

/**
 * Description of ResultUserCreator
 *
 * @author Edd
 */
class ResultUserCreator {
    
    public static function getResultUser(array $inputs, array $arySettings = null) { //$includeSettings = false) {

        $client = ClientDAO::getClient($inputs, $arySettings);
        if (!is_bool($client)) {
            return $client;
        }
        
        $doctor = DoctorDAO::getDoctor($inputs, $arySettings);
        if (!is_bool($doctor)) {
            return $doctor;
        }
        
        $admin = AdminDAO::getAdmin($inputs, $arySettings);
        if (!is_bool($admin)) {
            return $admin;
        }
        
        return false;
        
    }
    
    public static function setNewLogin($userId, $sessionId, $token, $typeId) {
        if ($typeId == 1) {
            
            AdminDAO::setNewLogin($userId, $sessionId, $token);
            
        } elseif ($typeId == 2) {
            
            ClientDAO::setNewLogin($userId, $sessionId, $token);
            
        } elseif ($typeId == 3) {
            
            DoctorDAO::setNewLogin($userId, $sessionId, $token);
            
        }
    }
        
}

?>
