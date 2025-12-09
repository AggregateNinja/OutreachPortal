<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 7/10/2020
 * Time: 10:12 AM
 */
require_once 'DOS/BaseObject.php';
require_once 'Utility/IAPIConfig.php';

class APIAuth extends BaseObject implements IAPIConfig {

    /*
     * 200 - OK
     * 400 - Bad Request
     * 401 - Unauthorized - No valid API key provided
     * 402 - Request Failed - There parameters were valid but the request failed, perhaps due to expired token
     * 404 - Not Found
     */
    public $HttpStatusCode = 400;
    public $HttpStatusMsg = "Bad Request";

    public function __construct(array $data = null) {
        if ($data != null) {
            parent::__construct($data);
        }
    }

    public function generateToken(array $input) {
        $subject = "";
        $publicKey = "";
        if (array_key_exists("sub", $input) && array_key_exists("key", $input)) {
            $subject = $input['sub'];
            $publicKey = $input['key'];
        } else {
            if (!array_key_exists("key", $input) || empty($input['key'])) {
                $this->HttpStatusCode = 401;
                $this->HttpStatusMsg = "Invalid Login";
            } else {
                $this->HttpStatusCode = 400;
                $this->HttpStatusMsg = "Bad Request";
            }
            return false;
        }

        $secretKey = IAPIConfig::SecretKey;
        $dteExpDateTime = new DateTime(self::TokenExpirationInterval);
        $strExpDateTime = $dteExpDateTime->format('YmdHis');

        // Create token header as a JSON string
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]);

        // Create token payload as a JSON string
        $payload = json_encode([
            'sub' => $subject,
            'key' => $publicKey,
            'exp' => $strExpDateTime
        ]);

        // Encode Header to Base64Url String
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

        // Encode Payload to Base64Url String
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        // Create Signature Hash
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secretKey, true);

        // Encode Signature to Base64Url String
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        // Create JWT
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        $this->HttpStatusCode = 200;
        $this->HttpStatusMsg = "Success";

        return $jwt;
    }

    public function validateToken($clientToken, array $data = null) {
        $secretKey = IAPIConfig::SecretKey;

        $arNo = "";
        if ($data != null && array_key_exists("arNo", $data) && isset($data['arNo']) && !empty($data['arNo'])) {
            $arNo = $data['arNo'];
        }

        $tokenParts = explode('.', $clientToken);
        $header = base64_decode($tokenParts[0]);
        $payload = base64_decode($tokenParts[1]);
        $signature = $tokenParts[2];

        $aryPayload = json_decode($payload, true);

//        $subject = $aryPayload['sub'];
//        $publicKey = $aryPayload['key'];

        $subject = self::Username;
        $publicKey = self::Password;


        $strExpDateTime = $aryPayload['exp'];
        $dteExpDateTime = new DateTime($strExpDateTime);

        // if expirationDate is after currDate then the token has expired and is invalid
        $dteCurrDateTime = new DateTime();

        $interval = $dteExpDateTime->diff($dteCurrDateTime);

        /*echo "Exp DateTime: " . $strExpDateTime . "<br/>";
        echo "Curr DateTime: " . $strCurrDateTime . "<br/>";
        echo "<pre>"; print_r($interval); echo "</pre>";*/

        if ($interval->invert == 1) {
            // the current datetime occurs prior to the expiration date, so the token is valid so far
            // Create token header as a JSON string
            $header = json_encode([
                'typ' => 'JWT',
                'alg' => 'HS256'
            ]);

            // Create token payload as a JSON string
            $payload = json_encode([
                'sub' => $subject,
                'key' => $publicKey,
                'exp' => $strExpDateTime
            ]);

            // Encode Header to Base64Url String
            $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

            // Encode Payload to Base64Url String
            $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

            // Create Signature Hash
            $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secretKey, true);

            // Encode Signature to Base64Url String
            $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

            // Create JWT
            $serverToken = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

            if ($clientToken === $serverToken) {
                $this->HttpStatusCode = 200;
                $this->HttpStatusMsg = "Success";
            } else {

                // bad request
                $this->HttpStatusCode = 400;
                $this->HttpStatusMsg = "Bad Request";

                // 401 - Unauthorized - invalid subject or public key

                // TODO: Evaluate which part of the token is invalid, ie: issuer/subject, header, payload, signature, hash algorithm...
            }
        } else {
            // token expired
            $this->HttpStatusCode = 402;
            $this->HttpStatusMsg = "Token Expired";
        }
    }

    /**
     * Get header Authorization
     * https://stackoverflow.com/a/40582472
     * */
    public function getAuthorizationHeader() {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } else if (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    /**
     * get access token from header
     * https://stackoverflow.com/a/40582472
     * */
    public function getBearerToken() {
        $headers = $this->getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}