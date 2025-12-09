<?php

require_once 'DataObject.php';
require_once 'DOS/Drug.php';

class DrugDAO extends DataObject {

    public static function getDrugs(array $data = null, array $settings = null) {
        $aryDrugs = array();
        $includeSubstances = false;
        $includeWhereClause = false;
        if ($settings != null) {
            if (array_key_exists("IncludeSubstances", $settings) && $settings['IncludeSubstances'] == true) {
                $includeSubstances = true;
            }
            if ($data != null) {
                $includeWhereClause = true;
            }
        }


        $sql = "SELECT     iddrugs, genericName ";

        if ($includeSubstances) {
            $sql .= " 
                ,s1.idsubstances AS 'idsubstances1', s1.substance AS 'substance1', 
                s2.idsubstances AS 'idsubstances2', s2.substance AS 'substance2',
                s3.idsubstances AS 'idsubstances3', s3.substance AS 'substance3' ";
        }

        $sql .= " FROM " . self::DB_CSS . "." . self::TBL_DRUGS . " d ";

        if ($includeSubstances) {
            $sql .= "
                LEFT JOIN  " . self::DB_CSS . "." . self::TBL_SUBSTANCES . " s1 ON s1.idsubstances = d.substance1
                LEFT JOIN  " . self::DB_CSS . "." . self::TBL_SUBSTANCES . " s2 ON s2.idsubstances = d.substance2
                LEFT JOIN  " . self::DB_CSS . "." . self::TBL_SUBSTANCES . " s3 ON s3.idsubstances = d.substance3 ";
        }

        $aryInput = array();
        if ($includeWhereClause) {
            $sql .= " WHERE ";
            foreach ($data as $aryClause) {
                //array("Field", "Condition", "Value")
                $sql .= $aryClause[0] . " " . $aryClause[1] . " ? AND ";
                $aryInput[] = $aryClause[2];
            }
            $sql = substr($sql, 0, strlen($sql) - 4);
        }


        $sql .= " ORDER BY   genericName ";

        //echo "<pre>$sql</pre>"; echo "<pre"; print_r($aryInput); echo "</pre>";
		
        $results = parent::select($sql, $aryInput, $settings);
		
		if (count($results) > 0) {
			foreach ($results as $row) {
				$drug = new Drug($row, $includeSubstances);
				$aryDrugs[] = $drug;
			}
		}

        return $aryDrugs;
    }



}

?>