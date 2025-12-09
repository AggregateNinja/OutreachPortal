<?php
if (!isset($_SESSION)) {
    session_start();
}
/**
 * Reference: http://weebtutorials.com/2012/09/make-a-simple-form-validation-class-using-php/
 * Description of FormValidator
 */
class FormValidator {
        
    //Returns true if empty, else returns false.
    public static function isEmpty($string) {
 
            //Check string length, if 0 then return empty
            if ( strlen($string) === 0 ) {
                return true;
            }
            //Otherwise the string is valid, return true
            else {
                return false;
            }
    }
 
    //Returns true if $char in $string, otherwise returns false.
    public static function containsChar($string, $char) {
 
        //First of all check the datatype to confirm a string has been passed in
        if ( is_string( $string ) ) {
 
            //If so use string type to locate the character provided, returns false if it doesnt exist
            if ( strpos($string, $char) ) {
                return true;
            }
            else {
                return false;
            }
 
        }
        else {
            return false;
        }
    }

    // Validate a Social Security Number.
    public static function isValidSsn($ssn) {
        if (preg_match("/^\d{3}-\d{2}-\d{4}$/", $ssn)) {
            // SSN is valid
            return true;
        }
        return false;
    }


    protected static function isValidDateFormat($date, $format = 'm/d/Y') {
        $d = DateTime::createFromFormat($format, $date);
    	
    	if ($d && $d->format($format) == $date) {
    		return true;
    	}
    	return false;
    }
    
    public static function isValidDate(array $dates, $format = 'm/d/Y') {
        $isValidDate = true;
         if (count($dates) == 1) {
             /*if (is_bool(strtotime($dates[0])) && strtotime($dates[0]) == false) {
                $isValidDate = false;
             }*/
             $isValidDate = self::isValidDateFormat($dates[0], $format);
         } elseif (count($dates) == 2) {
            if (!isset($dates[0]) || !isset($dates[1]) || empty($dates[0]) || empty($dates[1]) || $dates[0] == '' || $dates[1] == '') {
                $isValidDate = false;
            } if (strtotime($dates[0]) > strtotime($dates[1])) {
               $isValidDate = false;
            }
         } else {
             $isValidDate = false; // $dates is either empty array or has more than 2 variables
         }
         
       return $isValidDate;
    }
 
    //Checks that the string provided is a valid email address.
    public static function isValidEmail($string, $extraValidation = false) {
        $isValid = false;
        //First of all check the datatype to confirm a string has been passed in
        if ( is_string( $string ) ) {
            /*
            //Regular expression pattern.
            //Pattern breakdown:
                //** [a-zA-Z0-9_] - any character between a-z, A-Z or 0-9
                //** + - require one or more of the preceeding item.
                //** @{1} - Simply means 1 '@' symbol required.
                //** [a-zA-Z]+ - any character between a-z, A-Z (1 or more required).
                //** \.{1} - Single '.' required. Backslash escapes the '.'
                //** [a-zA-Z]+ - One or more of the these characters required.
            $pattern = "/^[a-zA-Z0-9_]+@{1}[a-zA-Z]+\.{1}[a-zA-Z]+/";

            //If the pattern matches then return true, else email is invalid, return false.
            if ( preg_match($pattern, $string) ){
                return true;
            }
            else {
                return false;
            }
            */

            //http://php.net/manual/en/filter.examples.validation.php
            $sanitized_string = filter_var($string, FILTER_SANITIZE_EMAIL);
            if (filter_var($sanitized_string, FILTER_VALIDATE_EMAIL)) {
                if ($extraValidation) {

                    $emailBlacklist = array(
                        "noemail",
                        "abcd"
                    );
                    $domainBlacklist = array(
                        "email",
                        "mail"
                    );

                    $email = substr($sanitized_string, 0, strpos($sanitized_string, "@"));

                    if (!is_numeric($email) // email must contain at least one non-numeric character
                        //strpos(strtolower($sanitized_string), "noemail") === false // email cannot contain the word noemail
                        //&& strlen(substr($sanitized_string, 0, strpos($sanitized_string, "@"))) > 3 // email length must be greater than 3 characters
                    ) {

                        $isValid2 = true;
                        foreach ($emailBlacklist as $currEmail) { // check that email address doesn't contain known invalid fake characters
                            if (strpos(strtolower($sanitized_string), $currEmail) !== false) { // strpos(haystack, needle)
                                $isValid2 = false;
                            }
                        }

                        if ($isValid2) {
                            $domain = substr($sanitized_string, strpos($sanitized_string, "@") + 1, strlen($sanitized_string) - strrpos($sanitized_string, ".") + 1);

                            if (!in_array($domain, $domainBlacklist)) { // the email domain cannon be an obviously fake address
                                $isValid = true;
                            } else {
                                error_log("FormValidator Invalid email domain: " . $sanitized_string . ", domain: " . $domain);
                            }
                        }


                    } else {
                        error_log("FormValidator Invalid email: " . $sanitized_string);
                    }

                } else {
                    $isValid = true;
                }
            }
        }
        return $isValid;
    }
    
    public static function isValidPassword($password, $password2) {
        if (strcmp($password, $password2) == 0 && self::isValidLength($password, 6)) {
            return true;
        }
        return false;
    }

    public static function passwordsMatch($password, $password2) {
        if (strcmp($password, $password2) == 0) {
            return true;
        }
        return false;
    }

    public static function isValidPasswordLength($password) {
        if (self::isValidLength($password, 6)) {
            return true;
        }
        return false;
    }

    // http://stackoverflow.com/a/10753064
    public static function isValidPasswordComplexity($password) {
        if (!preg_match("#[0-9]+#", $password)) {
            //$errors[] = "Password must include at least one number!";
            return false;
        }

        if (!preg_match("#[a-zA-Z]+#", $password)) {
            //$errors[] = "Password must include at least one letter!";
            return false;
        }

        return true;
    }

    //Takes a single allowed file type, or an array of values and checks against $filename
    public static function isValidFileType($filename, $whitelist) {
 
        //Check if array of values or single value
        if ( is_array($whitelist) ) {
 
            //string to hold allowed filetypes
            $allowed ='';
 
            //add each item in array to the string
            foreach ( $whitelist as $filetype ) {
 
                 $allowed.= $filetype . "|";
            }
 
        }
        else {
            $allowed = $filename;
        }
 
        //Pattern breakdown:
            //** \.{1} - single '.' required
            //** [" . $allowed . "]+ - check for filetypes passed into parameter, 1 or more required.
            //** $- String must end with this.
        $pattern = "!\.{1}[" . $allowed . "]+$!";
 
        //Valid file, return true.
        if ( preg_match($pattern, $filename) ) {
            return true;
        }
        //else invalid file type.
        else {
            return false;
        }
    }
 
    //Checks if $string is as long, or longer than $minLength. IF $exact is passed
    //in, then function looks for exact match in length.
    public static function isValidLength($string, $minLength, $exact = 0) {
 
        //Looking for exact match
        if ($exact) {
            if ( strlen($string) == $minLength ) {
            return true;
            }
            else {
                return false;
            }
        }
 
        //Minimum length
        if ( strlen($string) >= $minLength ) {
            return true;
        }
        else {
            return false;
        }
    }
}
?>
