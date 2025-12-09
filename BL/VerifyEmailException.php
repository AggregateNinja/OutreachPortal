<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 11/20/2020
 * Time: 9:25 AM
 */

/**
 * verifyEmail exception handler
 */
class VerifyEmailException extends Exception {

    /**
     * Prettify error message output
     * @return string
     */
    public function errorMessage() {
        $errorMsg = $this->getMessage();
        return $errorMsg;
    }
}