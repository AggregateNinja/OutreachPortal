<?php

require_once 'BaseObject.php';

class TestChoice extends BaseObject {

    protected $Data = array(
        "idMultiChoice" => "",
    	"testId" => "",
        "choice" => "",
        "isAbnormal" => "",
        "choiceOrder" => ""
    );

    public function hasTestChoice($choice) {
        if ($this->Data['choice'] == $choice) {
            return true;
        }

        return false;
    }

    /**
     * @return the choice's name with only the first letter of each word in upper case
     */
    public function getNameFormatted() {
        $strChoice = "";
        $aryChoice = explode(" ", $this->choice);
        foreach ($aryChoice as $word) {
            $strChoice .= ucfirst(strtolower($word)) . " ";
        }
        return trim($strChoice);
    }

}

?>


