<?php
require_once 'BaseObject.php';
require_once "DOS/ResultOrder.php";
require_once 'Patient.php';

/**
 * Description of Cumulative
 *
 * @author Edd
 */


require_once 'pChart/class/pData.class.php';

class Cumulative extends BaseObject {
    public $ResultOrders = array();

    private $Patient;

    public function __construct() {
        
    }
    
    public function addResultOrder(ResultOrder $resultOrder) {
        $this->ResultOrders[] = $resultOrder;
    }
    
    public function getDistinctTests() {
        $distinctTests = array();
        
        foreach ($this->ResultOrders as $resultOrder) {
            
            foreach ($resultOrder->Results as $result) {
                $test = $result->Test;
                
                if (isset($test->number) && !array_key_exists($test->number, $distinctTests)) {
                    $distinctTests[$test->number] = $test->name;
                }
                        
            }
        }

        asort($distinctTests);
        
        return $distinctTests;
    }
    
//    public function getDates() {
//        $dates = array();$datetime1 = new DateTime("2010-01-01");
//        $oldest = strtotime($this->ResultOrders[0]->orderDate);
//        $newest = strtotime($this->ResultOrders[0]->orderDate);
//        
//
//        foreach ($this->ResultOrders as $ro) {
//            $currDate = strtotime($ro->orderDate);
//            $dates[] = date("m/d/Y", $currDate);       
//            
//            if ($currDate < $oldest) {
//                $oldest = $currDate;
//            }
//            if ($currDate > $newest) {
//                $newest = $currDate;
//            }
//        }
//        
//        $xAxis = array();
//        
//        $numMonths = $this->getNumMonths($oldest, $newest);
//        
//        for ($i = 0; $i < $numMonths; $i++) {
//            
//            $nextMonth = mktime(0, 0, 0, date("m", $oldest) + $i, 1,   date("Y", $oldest));
//            $xAxis[] = date ("M-d-Y", $nextMonth);
//        }
//        
//        return $xAxis;
//    }
//    
//    /* reference: http://stackoverflow.com/a/13416973 */
//    private function getNumMonths($date1, $date2) {
//        
//        $d1a = mktime(0, 0, 0, date("m", $date1), 0,   date("Y", $date1));
//        $d2a = mktime(0, 0, 0, date("m", $date2), 1,   date("Y", $date2));
//        
//        $d1 = new DateTime(date ("Y-m", $d1a));
//        $d2 = new DateTime(date ("Y-m", $d2a));
//        
//        
//        $diff = $d1->diff($d2);
//        
//        return $diff->m;
//
//        //var_dump($d1->diff($d2)->m); // int(4)
//        
//        
//        //var_dump($d1->diff($d2)->m + ($d1->diff($d2)->y*12)); // int(8)
//        //return $d1->diff($d2)->m + ($d1->diff($d2)->y*12);
//    }
    
    
    public function getDates() {
        $dates = array();
        
//        // check if all the dates are a part of the same year
//        $year = date("y", strtotime($this->ResultOrders[0]->orderDate));
//        $sameYear = true;
//        if (count($this->ResultOrders) <= 12) {
//            for ($i = 1; $i <= count($this->ResultOrders) && $sameYear; $i++) {
//                if (date("y", strtotime($this->ResultOrders[$i]->orderDate)) != $year) {
//                    $sameYear = false;
//                }
//            }
//            
//        } else {
//            $sameYear = false;
//        }
        
        foreach ($this->ResultOrders as $ro) {
            $dates[] = date("n/d/y", strtotime($ro->orderDate));
        }
        
        return $dates;
    }
    
    public function getGraphLine($number) {
        $aryResultText = array(); 
        
        foreach ($this->ResultOrders as $ro) {
            $aryResults = $ro->Results;
            $testFound = false;
            foreach ($aryResults as $result) {
                //echo $result->Test->idtests . "<br/>";
                if ($result->Test->number == $number) {
                    //echo $result->resultText . "<br/>";
                    if (is_numeric($result->resultText)) {
                        $aryResultText[] = $result->resultText;
                    } else if (is_numeric($result->resultNo)) {
                        $aryResultText[] = $result->resultNo;
                    } else {
                        $aryResultText[] = VOID;
                    }
                    $testFound = true;                    
                }
            }
            if (!$testFound) {
                $aryResultText[] = VOID;
            }
        }
        
        return $aryResultText;
    }

    public function getGraphLines() {
        $aryGraphLines = array();
        foreach ($this->ResultOrders as $ro) {
            foreach ($ro->Results as $result) {
                $currTestNumber = $result->Test->number;
                $currResultText = $result->resultText;
                if (!is_numeric($result->resultText)) {
                    $currResultText = VOID;
                }

                $aryGraphLines[$currTestNumber][] = $currResultText;
            }
        }
        return $aryGraphLines;
    }
    
    public function __get($key) {
        return parent::__get($key);

    }
}

?>
