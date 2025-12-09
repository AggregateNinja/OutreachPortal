<?php

require_once 'Test.php';

require_once 'TestChoice.php';


class POCTest extends Test {
   protected $Choices = array();
   protected $panelId = 0;
   
   
   public function __construct($data) {
      parent::__construct($data);
      
      if (array_key_exists("panelId", $data)) {
      	$this->panelId = $data['panelId'];
      }
      
      
      $testChoice = new TestChoice($data);
      $this->Choices[]  = $testChoice;
   }

   public function addChoice($data) {
      $testChoice = new TestChoice($data);
      $this->Choices[] = $testChoice;
   }

   public function __get($key) {
      $field = parent::__get($key);

      if (empty($field)) {
         if ($key == "Choices") {
            $field = $this->Choices;
         } else if ($key == "panelId") {
         	$field = $this->panelId;
         }
      }

      return $field;
   }

   public function hasTestChoice($choice){
      $hasTestChoice = false;

      for ($i = 0; $i < count($this->Choices) || !$hasTestChoice; $i++) {
         if ($this->Choices[$i]->hasTestChoice($choice)) {
            $hasTestChoice = true;
         }
      }

      return $hasTestChoice;
   }

   public function __toString() {
      $strPOCTest = parent::__toString();
      foreach ($this->Choices AS $key => $value) {
         $strPOCTest .= $key . ": " . $value . "<br />";
      }
      return $strPOCTest;      
   }

}

?>
