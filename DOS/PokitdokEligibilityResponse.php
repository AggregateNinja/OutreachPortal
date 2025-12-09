<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 8/7/2018
 * Time: 3:00 PM
 */
require_once 'BaseObject.php';

class PokitdokEligibilityResponse extends BaseObject {
    protected $Data = array(
        "payer" => "",
        "eligibility_begin_date" => "",
        "plan_begin_date" => "",
        "plan_end_date" => "",
        "active" => "No",
        "coinsurance" => "",
        "contacts" => "",
        "copay" => "",
        "deductibles" => "",
        "out_of_pocket" => "",
        "limitations" => "",
        "reject_reason" => ""
    );

    public $rejected = false;

    public function __construct($eligibility = null) {
        if (array_key_exists("data", $eligibility->body())) {
            $data = $eligibility->body()->data;

            if (array_key_exists("payer", $data)) {
                $this->Data['payer'] = $data->payer->name;
            }
            if (array_key_exists("coverage", $data)) {
                if (array_key_exists("eligibility_begin_date", $data->coverage)) {
                    $this->Data['eligibility_begin_date'] = $data->coverage->eligibility_begin_date;
                }
                if (array_key_exists("plan_begin_date", $data->coverage)) {
                    $this->Data['plan_begin_date'] = $data->coverage->plan_begin_date;
                }
                if (array_key_exists("plan_end_date", $data->coverage)) {
                    $this->Data['plan_end_date'] = $data->coverage->plan_end_date;
                }
                if (array_key_exists("active", $data->coverage)) {
                    if ($data->coverage->active == true) {
                        $this->Data['active'] = "Yes";
                    }
                }
                if (array_key_exists("coinsurance", $data->coverage)) {
                    $this->Data['coinsurance'] = $data->coverage->coinsurance;
                }
                if (array_key_exists("contacts", $data->coverage)) {
                    $this->Data['contacts'] = $data->coverage->contacts;
                }

                if (array_key_exists("copay", $data->coverage)) {
                    $this->Data['copay'] = $data->coverage->copay;
                }
                if (array_key_exists("deductibles", $data->coverage)) {
                    $this->Data['deductibles'] = $data->coverage->deductibles;
                }
                if (array_key_exists("out_of_pocket", $data->coverage)) {
                    $this->Data['out_of_pocket'] = $data->coverage->out_of_pocket;
                }
                if (array_key_exists("limitations", $data->coverage)) {
                    $this->Data['limitations'] = $data->coverage->limitations;
                }
            }

            if (array_key_exists("reject_reason", $data)) {
                $this->Data['reject_reason'] = ucwords(str_replace("_", " ", $data->reject_reason));
                $this->rejected = true;
            }
        }
    }

    public function getHtml() {
        if ($this->rejected == false) {
            $eligibilityBeginDate = parent::formatDate($this->Data['eligibility_begin_date'], 'Y-m-d', 'm/d/Y');
            $planBeginDate = parent::formatDate($this->Data['plan_begin_date'], 'Y-m-d', 'm/d/Y');
            $planEndDate = parent::formatDate($this->Data['plan_end_date'], 'Y-m-d', 'm/d/Y');

            $html = "Payer: " . $this->Data['payer'] . "<br/>";
            $html .= "Eligibility Begin Date: $eligibilityBeginDate<br/>";
            $html .= "Plan Begin Date: $planBeginDate<br/>";
            $html .= "Plan End Date: $planEndDate<br/>";
            $html .= "Plan Active: " . $this->Data['active'] . "<br/>";
            $html .= "<br/>";
            $html .= $this->getCopayHtml();
            $html .= $this->getDeductiblesHtml();
            $html .= $this->getOutOfPocketHtml();
            $html .= $this->getLimitationsHtml();
        } else {
            $html = "Reject Reason: " . $this->Data['reject_reason'];
        }

        return $html;
    }

    private function getCopayHtml() {
        $copayHtml = "";
        if (isset($this->Data['copay']) && !empty($this->Data['copay'])) {
            $copayHtml = "<b>Copayment</b><br/>";
            foreach ($this->Data['copay'] as $currCopay) {
                if (isset($currCopay->messages)) {
                    foreach ($currCopay->messages as $message) {
                        $copayHtml .= $message->message . ", ";
                    }
                    $copayHtml = substr($copayHtml, 0, strlen($copayHtml) - 2);
                } else if (isset($currCopay->service_types)) {
                    foreach ($currCopay->service_types as $serviceType) {
                        $copayHtml .= ucwords(str_replace("_", " ", $serviceType)) . ", ";
                    }
                    $copayHtml = substr($copayHtml, 0, strlen($copayHtml) - 2);
                } else {
                    $copayHtml .= "Copayment Amount";
                }

                $copayHtml .=  ": $" . $currCopay->copayment->amount . "<br/>";
            }

            $copayHtml .= "<br/>";
        }

        return $copayHtml;
    }

    private function getDeductiblesHtml() {
        $deductiblesHtml = "";
        if (isset($this->Data['deductibles']) && !empty($this->Data['deductibles'])) {
            $deductiblesHtml = "<b>Deductibles</b><br/>";
            foreach ($this->Data['deductibles'] as $currDeductible) {
                if (isset($currDeductible->messages)) {
                    foreach ($currDeductible->messages as $message) {
                        $deductiblesHtml .= $message->message . ", ";
                    }
                    $deductiblesHtml = substr($deductiblesHtml, 0, strlen($deductiblesHtml) - 2);
                } else if (isset($currDeductible->service_types)) {
                    foreach ($currDeductible->service_types as $serviceType) {
                        $deductiblesHtml .= ucwords(str_replace("_", " ", $serviceType)) . ", ";
                    }
                    $deductiblesHtml = substr($deductiblesHtml, 0, strlen($deductiblesHtml) - 2);
                } else {
                    $deductiblesHtml .= "Deductible Amount";
                }

                $currTimePeriod = "";
                if (isset($currDeductible->time_period)) {
                    $currTimePeriod = ucwords(str_replace("_", " ", $currDeductible->time_period));
                }
                $currCoverageLevel = "";
                if (isset($currDeductible->coverage_level)) {
                    $currCoverageLevel = ucwords($currDeductible->coverage_level);
                }

                $deductiblesHtml .= ": $" . $currDeductible->benefit_amount->amount . "<br/>";
                $deductiblesHtml .= "In Plan Network: " . ucwords(str_replace("_", " ", $currDeductible->in_plan_network)) . "<br/>";
                $deductiblesHtml .= "Coverage Level: $currCoverageLevel<br/>";
                $deductiblesHtml .= "Time Period: $currTimePeriod<br/><br/>";
            }
        }


        return $deductiblesHtml;
    }

    private function getOutOfPocketHtml() {
        $outOfPocketHtml = "";
        if (isset($this->Data['out_of_pocket']) && !empty($this->Data['out_of_pocket'])) {
            $outOfPocketHtml = "<b>Out Of Pocket</b><br/>";
            foreach ($this->Data['out_of_pocket'] as $currOutOfPocket) {
                $outOfPocketHtml .= "Benefit Amount: $" . $currOutOfPocket->benefit_amount->amount . "<br/>";
                $outOfPocketHtml .= "In Plan Network: " . str_replace("_", " ", ucwords($currOutOfPocket->in_plan_network)) . "<br/>";
                $outOfPocketHtml .= "Coverage Level: " . ucwords($currOutOfPocket->coverage_level) . "<br/>";
                if (array_key_exists("time_period", $currOutOfPocket)) {
                    $outOfPocketHtml .= "Time Period: " . str_replace("_", " ", ucwords($currOutOfPocket->time_period)) . "<br/>";
                }
                $outOfPocketHtml .= "<br/>";
            }
        }

        return $outOfPocketHtml;
    }

    private function getLimitationsHtml() {
        $limitationsHtml = "";
        if (isset($this->Data['limitations']) && !empty($this->Data['limitations'])) {
            $limitationsHtml = "<b>Limitations</b><br/>";
            foreach ($this->Data['limitations'] as $currLimitation) {
                if (isset($currLimitation->messages)) {
                    foreach ($currLimitation->messages as $message) {
                        $limitationsHtml .= $message->message . ", ";
                    }
                    $limitationsHtml = substr($limitationsHtml, 0, strlen($limitationsHtml) - 2) . "<br/>";
                }


                if (array_key_exists("time_period_qualifier", $currLimitation)) {
                    $limitationsHtml .= "Time Period Qualifier: " . str_replace("_", " ", ucwords($currLimitation->time_period_qualifier)) . "<br/>";
                }
                $currCoverageLevel = "";
                if (isset($currLimitation->coverage_level)) {
                    $currCoverageLevel = ucwords($currLimitation->coverage_level);
                }

                $limitationsHtml .= "Coverage Level: $currCoverageLevel<br/>";
                $limitationsHtml .= "Service Types: " . str_replace("_", " ",ucwords(implode(", ", $currLimitation->service_types))) . "<br/>";
                if (array_key_exists("in_plan_network", $currLimitation)) {
                    $limitationsHtml .= "In Plan Network: " . str_replace("_", " ", ucwords($currLimitation->in_plan_network)) . "<br/>";
                }
                $limitationsHtml .= "<br/>";
            }
        }

        return $limitationsHtml;
    }




}