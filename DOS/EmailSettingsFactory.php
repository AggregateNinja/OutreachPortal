<?php
require_once 'BaseObject.php';
require_once 'DAOS/EmailNotificationDAO.php';
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 8/10/2016
 * Time: 3:51 PM
 */
class EmailSettingsFactory extends BaseObject
{
    protected $Data = array(
        "adminUserId" => "",
        "inactiveUserIds" => ""
    );

    private $EmailDAO;

    public function __construct(array $input) {
        parent::__construct($input);

        $this->EmailDAO = new EmailNotificationDAO();

        $this->updateInactiveEmails();

    }

    private function updateInactiveEmails() {
        $currInactiveUserIds = $this->EmailDAO->getInactiveUserIds();

        if (empty($this->Data['inactiveUserIds'])) {
            // no users were selected, so clear the table
            $this->EmailDAO->removeInactiveUserIds();

        } else if (empty($currInactiveUserIds)) {
            // the table is currently empty, so add ids to the table
            $this->EmailDAO->addInactiveUserIds($this->Data['inactiveUserIds']);

        } else {
            // users were selected and the table is not empty

            $aryRemoveIds = array();
            // delete the ids from the table that are no longer selected
            foreach($currInactiveUserIds as $userId) {
                if (!in_array($userId, $this->Data['inactiveUserIds'])) {
                    $aryRemoveIds[] = $userId;
                }
            }
            if (!empty($aryRemoveIds)) {
                $this->EmailDAO->removeInactiveUserIds($aryRemoveIds);
            }

            $aryAddIds = array();
            // add userIds to the table that are not already in it
            foreach($this->Data['inactiveUserIds'] as $userId) {
                if (!in_array($userId, $currInactiveUserIds)) {
                    $aryAddIds[] = $userId;
                }
            }
            if (!empty($aryAddIds)) {
                $this->EmailDAO->addInactiveUserIds($aryAddIds);
            }
        }

        // remove ids from table

        // add ids to table
    }
}