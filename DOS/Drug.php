<?php
require_once 'BaseObject.php';
require_once 'Substance.php';

class Drug extends BaseObject {

	protected $Data = array (
      "iddrugs" => "",
      "genericName" => ""	
	);
   protected $Substances = array();

   public function __construct(array $data, $withSubstances = false) {
      parent::__construct($data); // set the Order fields

      if ($withSubstances) {
         if (array_key_exists("idsubstances1", $data) && !empty($data['idsubstances1']) && $data['idsubstances1'] != null) {
            $substance1 = new Substance(array("idsubstances" => $data['idsubstances1'], "substance" => $data['substance1']));
            $this->Substances[] = $substance1;
         }
         if (array_key_exists("idsubstances2", $data) && !empty($data['idsubstances2']) && $data['idsubstances2'] != null) {
            $substance2 = new Substance(array("idsubstances" => $data['idsubstances2'], "substance" => $data['substance2']));
            $this->Substances[] = $substance2;
         }
         if (array_key_exists("idsubstances3", $data) && !empty($data['idsubstances3']) && $data['idsubstances3'] != null) {
            $substance3 = new Substance(array("idsubstances" => $data['idsubstances3'], "substance" => $data['substance3']));
            $this->Substances[] = $substance3;
         }
      }      
   }

   public function __get($key) {
      $field = parent::__get($key);

      if (empty($field)) {
         if ($key == "Substances") {
            return $this->Substances;
         } else if (count($this->Substances) > 0 && array_key_exists($key, $this->Substances[0])) {
             return $this->Substances[0]->$key;
         }
      }

      return $field;
   }

   public function __isset($field) {
       $isset = parent::__isset($field);
       if (!$isset) {
           if ($field == "Substances" && count($this->Substances) == 0) {
               $isset = false;
           }
       }
       return $isset;
   }

}


?>