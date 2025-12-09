<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 11/17/14
 * Time: 9:44 AM
 */

abstract class AjaxRequestCreator {

    protected $Data = array(
        "Action" => "",
        "UserId" => ""
    );

    protected function __construct(array $data = null) {
        /*if ($data != null) {
            foreach ($data as $key => $value) {
                if (array_key_exists($key, $this->Data)) {
                    $this->Data[$key] = $value;
                }
            }
        }*/
    }

    protected abstract function factoryMethod();


    public function startFactory() {
        return $this->factoryMethod();
    }

} 