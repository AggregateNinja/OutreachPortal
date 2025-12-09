<?php
/**
 * Copyright (C) 2014, All Rights Reserved, PokitDok, Inc.
 * http://www.pokitdok.com
 *
 * Please see the LICENSE.txt file for more information.
 * All other rights reserved.
 *
 *	PokitDok Platform Client for PHP
 *		Consume the REST based PokitDok platform API
 *		https://platform.pokitdok.com/
 */


namespace PokitDok\Common;


/**
 * Class Oauth2ApplicationClient General Oauth2 client implementing Basic Auth and client_credentials grant type
 *
 * @package PokitDok\Common
 */
class Oauth2ApplicationClient {

    const DEFAULT_TIMEOUT = 90;

    private $_client_id = '';
    private $_client_secret = '';
    private $_request_timeout = self::DEFAULT_TIMEOUT;
    private $_cert_file = '';

    private $_access_token = '';
    private $_access_token_expires = null;
    private $_access_token_result = null;
    private $_refresh_token = '';
    private $_api_base_url = '';
    private $_api_token_url = '';

    private $_ch = null;

    private $_redirect_uri = null;
    private $_token_refresh_callback = null;
    private $_scope = null;
    private $_code = null;

    /**
     * @var \PokitDok\Common\HttpResponse
     */
    private $_response = null;
    private $_status = 0;

    /**
     * @param string $id Client ID
     * @param string $secret Client Secret
     * @param int $request_timeout Timeout for API requests, default is self::DEFAULT_TIMEOUT
     * @param string $access_token_json JSON string with the access token result from previous request:
     *      {
     *          "access_token": "tOUwQvtoj3vYhbM1AOSDBjebnXjevMQeZjcYBYPL",     // required
     *          "token_type": "bearer",
     *          "expires": 1398735988,                                          // required
     *          "expires_in": 3600,
     *          "refresh_token": "aOUwQvtoj3vYhbM1AOSDBjebnXjevMQeZjcYBYPL"
     *      }
     * @param string $cert_file     Fully qualified path to trusted CA certificates file
     * @param string $redirect_uri  URL to redirect to for the Platform Application, see Application settings
     * @param callable $token_refresh_callback Callback function invoked when the token is refreshed
     * @param array $scope          array of strings representing the requested scopes
     * @param string $code          The authorization code received by the scope grant of the Platform Application
     */
    public function __construct(
        $id,
        $secret,
        $request_timeout = self::DEFAULT_TIMEOUT,
        $access_token_json = null,
        $cert_file = '',
        $redirect_uri = null,
        callable $token_refresh_callback = null,
        $scope = null,
        $code = null)
    {
        $this->_client_id = $id;
        $this->_client_secret = $secret;
        $this->_request_timeout = $request_timeout;

        if (isset($access_token_json)) {
            $this->setAccessToken($access_token_json, false);
        }
        $this->_cert_file = $cert_file;
        if (isset($redirect_uri)) {
            $this->_redirect_uri = $redirect_uri;
        }
        if (isset($token_refresh_callback)) {
            $this->_token_refresh_callback = $token_refresh_callback;
        }
        if (isset($scope)) {
            $this->_scope = $scope;
        }
        if (isset($code)) {
            $this->_code = $code;
        }
    }

    /**
     * @return resource Curl resource handle
     * @throws \Exception On error initializing curl
     */
    private function get_handle()
    {
        $this->_ch = curl_init();
        if ($this->_ch === false) {
            throw new \Exception(curl_error($this->_ch), curl_errno($this->_ch));
        }

        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_ch, CURLOPT_TIMEOUT, $this->_request_timeout);
        curl_setopt($this->_ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($this->_ch, CURLOPT_USERPWD, $this->_client_id .":". $this->_client_secret);
        if ($this->_cert_file === '') {
            curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, false);
        } else {
            curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($this->_ch, CURLOPT_CAINFO, $this->_cert_file);
        }
        curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, 2);

        return $this->_ch;
    }

    /**
     * @return null|string Access token JSON document
     */
    private function retrieve_access_token()
    {
        if (isset($this->_refresh_token) && strlen($this->_refresh_token) > 0) {
            return $this->refresh_access_token();
        } elseif (!isset($this->_code)) {
            return $this->retrieve_client_credentials_access_token();
        } else {
            return $this->retrieve_auth_code_access_token($this->_code);
        }
    }

    /**
     * @return string JSON string of access token response
     *  {
     *      "access_token": "s8KYRJGTO0rWMy0zz1CCSCwsSesDyDlbNdZoRqVR",
     *      "token_type": "bearer",
     *      "expires": 1393350569,
     *      "expires_in": 3600,
     *      "refresh_token": "aOUwQvtoj3vYhbM1AOSDBjebnXjevMQeZjcYBYPL"
     *  }
     * @throws \Exception On error configure Curl access token request
     */
    private function retrieve_client_credentials_access_token()
    {
        if (!$this->isTokenExpired()) {
            return $this->_access_token_result;
        }

        $this->get_handle();
        curl_setopt($this->_ch, CURLOPT_URL, $this->_api_token_url);
        curl_setopt($this->_ch, CURLOPT_POST, true);
        curl_setopt(
            $this->_ch,
            CURLOPT_POSTFIELDS,
            array(
                "grant_type"=>"client_credentials",
                "client_id"=>$this->_client_id,
                "client_secret"=>$this->_client_secret
            )
        );
        if ($this->_ch === false) {
            throw new \Exception(curl_error($this->_ch), curl_errno($this->_ch));
        }

        $result = curl_exec($this->_ch);
        if ($result === false) {
            throw new \Exception(curl_error($this->_ch), curl_errno($this->_ch));
        }
        $this->setAccessToken($result);
        curl_close($this->_ch);

        return $this->_access_token_result;
    }

    /**
     * @param string $code The authorization code received by the scope grant
     * @return string JSON string of access token response
     *  {
     *      "access_token": "s8KYRJGTO0rWMy0zz1CCSCwsSesDyDlbNdZoRqVR",
     *      "token_type": "bearer",
     *      "expires": 1393350569,
     *      "expires_in": 3600,
     *      "refresh_token": "aOUwQvtoj3vYhbM1AOSDBjebnXjevMQeZjcYBYPL"
     *  }
     * @throws \Exception On error configure Curl access token request
     */
    private function retrieve_auth_code_access_token($code)
    {
        if (!$this->isTokenExpired()) {
            return $this->_access_token_result;
        }

        $this->get_handle();
        curl_setopt($this->_ch, CURLOPT_URL, $this->_api_token_url);
        curl_setopt($this->_ch, CURLOPT_POST, true);
        curl_setopt(
            $this->_ch,
            CURLOPT_POSTFIELDS,
            array(
                "grant_type"=>"authorization_code",
                "client_id"=>$this->_client_id,
                "client_secret"=>$this->_client_secret,
                "redirect_uri"=>$this->_redirect_uri,
                "code"=>$code,
                "scope"=>$this->_scope,
            )
        );
        if ($this->_ch === false) {
            throw new \Exception(curl_error($this->_ch), curl_errno($this->_ch));
        }

        $result = curl_exec($this->_ch);
        if ($result === false) {
            throw new \Exception(curl_error($this->_ch), curl_errno($this->_ch));
        }
        $this->setAccessToken($result);
        curl_close($this->_ch);

        return $this->_access_token_result;
    }

    /**
     * @return string JSON string of access token response
     *  {
     *      "access_token": "s8KYRJGTO0rWMy0zz1CCSCwsSesDyDlbNdZoRqVR",
     *      "token_type": "bearer",
     *      "expires": 1393350569,
     *      "expires_in": 3600,
     *      "refresh_token": "aOUwQvtoj3vYhbM1AOSDBjebnXjevMQeZjcYBYPL"
     *  }
     * @throws \Exception On error configure Curl access token request
     */
    private function refresh_access_token()
    {
        if (!$this->isTokenExpired()) {
            return $this->_access_token_result;
        }

        $this->get_handle();
        curl_setopt($this->_ch, CURLOPT_URL, $this->_api_token_url);
        curl_setopt($this->_ch, CURLOPT_POST, true);
        curl_setopt(
            $this->_ch,
            CURLOPT_POSTFIELDS,
            array(
                "grant_type" => "refresh_token",
                "client_id" => $this->_client_id,
                "client_secret" => $this->_client_secret,
                "refresh_token" => $this->_refresh_token,
                "scope" => $this->_scope
            )
        );
        if ($this->_ch === false) {
            throw new \Exception(curl_error($this->_ch), curl_errno($this->_ch));
        }

        $result = curl_exec($this->_ch);
        if ($result === false) {
            throw new \Exception(curl_error($this->_ch), curl_errno($this->_ch));
        }
        $this->setAccessToken($result);
        curl_close($this->_ch);

        return $this->_access_token_result;
    }

    /**
     * @param string $access_token_json JSON string returned from retrieve_access_token()
     *  {
     *      "access_token": "s8KYRJGTO0rWMy0zz1CCSCwsSesDyDlbNdZoRqVR",
     *      "token_type": "bearer",
     *      "expires": 1393350569, // optional
     *      "expires_in": 3600,
     *      "refresh_token": "aOUwQvtoj3vYhbM1AOSDBjebnXjevMQeZjcYBYPL" // optional
     *  }
     * @param bool $call_refresh_callback When true invoke the token refresh callback if set
     * @param bool $expire_token When true the access token expire time will be set to now
     * @return string JSON string of access token response
     * @throws \Exception On error returned from access token request
     */
    private function setAccessToken($access_token_json, $call_refresh_callback=true, $expire_token=false)
    {
        $this->_access_token_result = json_decode($access_token_json);
        $token_elements = get_object_vars($this->_access_token_result);
        if (!array_key_exists("access_token", $token_elements) ||
            !array_key_exists("token_type", $token_elements) ||
            !array_key_exists("expires_in", $token_elements)) {
            throw new \Exception("Invalid access token.");
        }

        $this->_access_token = '';
        $this->_access_token_expires = null;
        $this->_refresh_token = '';

        if (isset($this->_access_token_result->error)) {
            throw new \Exception($this->_access_token_result->error);
        } else {
            $this->_access_token = $this->_access_token_result->access_token;
            if ($expire_token) {
                $this->_access_token_expires = time() - 1;
            } elseif (array_key_exists("expires", $token_elements)) {
                $this->_access_token_expires = $this->_access_token_result->expires;
            } else {
                $this->_access_token_expires = time() + $this->_access_token_result->expires_in - 60;
            }
            $this->_access_token_result->expires = $this->_access_token_expires;
            if (isset($this->_access_token_result->refresh_token)) {
                $this->_refresh_token = $this->_access_token_result->refresh_token;
            }
        }

        if ($call_refresh_callback && isset($this->_token_refresh_callback)) {
            call_user_func($this->_token_refresh_callback, json_encode($this->_access_token_result));
        }

        return $this->_access_token_result;
    }

    /**
     * Initialize access token
     */
    public function init()
    {
        $this->retrieve_access_token();
    }

    /**
     * @param string $request_type HTTP verbs: 'GET', 'POST', 'PUT', etc.
     * @param string $request_path URL path to the API call
     * @param null $parameters Query parameters (array: formatted as query params or string: appended to URL path)
     * @param string $content_type MIME type of content
     * @return mixed HTTP status code returned
     * @throws \Exception On errors (>299 status response)
     */
    public function request($request_type, $request_path, $parameters = null, $content_type = '')
    {
        $this->_response = null;
        $this->_status = 0;

        $this->retrieve_access_token();
        $this->get_handle();

        curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, $request_type);

        $headers = array(sprintf('Authorization: Bearer %s', $this->_access_token));

        if ($request_type === 'GET' || $request_type === 'DELETE') {
            curl_setopt(
                $this->_ch,
                CURLOPT_URL,
                $this->_api_base_url .
                    $request_path .
                    (is_array($parameters) ? '?'. http_build_query($parameters) : $parameters)
            );
        } else {
            curl_setopt($this->_ch, CURLOPT_URL, $this->_api_base_url . $request_path);
            curl_setopt(
                $this->_ch,
                CURLOPT_POSTFIELDS,
                ($content_type === "application/json" ? json_encode($parameters) : $parameters)
            );
        }

        if ($content_type !== '') {
            $headers[] = sprintf('Content-Type:  %s', $content_type);
        }

        curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->_ch, CURLOPT_HEADER, true);
        $user_agent = "php-pokitdok#1.1.0#" . php_uname("s") . "#" . php_uname("r");
        curl_setopt($this->_ch, CURLOPT_USERAGENT, $user_agent);

        $response = curl_exec($this->_ch);

        $this->_response = new HttpResponse($response);
        $this->_status = curl_getinfo($this->_ch, CURLINFO_HTTP_CODE);

        curl_close($this->_ch);

        if ($this->_status > 299) {
            throw new \Exception(json_encode($this->_response->body()), $this->_status);
        }

        return $this->_status;
    }

    /**
     * @return bool True if access token is expired
     */
    public function isTokenExpired()
    {
        return (time() > ($this->_access_token_expires - $this->_request_timeout));
    }

    /**
     * @param string $api_base_url Base URL API endpoint
     */
    public function setApiBaseUrl($api_base_url)
    {
        $this->_api_base_url = $api_base_url;
    }

    /**
     * @return string Base URL API endpoint
     */
    public function getApiBaseUrl()
    {
        return $this->_api_base_url;
    }

    /**
     * @param string $api_token_url
     */
    public function setApiTokenUrl($api_token_url)
    {
        $this->_api_token_url = $api_token_url;
    }

    /**
     * @return string
     */
    public function getApiTokenUrl()
    {
        return $this->_api_token_url;
    }

    /**
     * @param int $request_timeout Time interval (seconds) to wait for request completion
     */
    public function setRequestTimeout($request_timeout)
    {
        $this->_request_timeout = $request_timeout;
    }

    /**
     * @return int Time interval (seconds) to wait for request completion
     */
    public function getRequestTimeout()
    {
        return $this->_request_timeout;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->_access_token;
    }

    /**
     * @return null Unix timestamp
     */
    public function getAccessTokenExpires()
    {
        return $this->_access_token_expires;
    }

    /**
     * @return int HTTP status code
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * @return \PokitDok\Common\HttpResponse
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * @return mixed
     */
    public function getResponseHeader()
    {
        return $this->_response->header();
    }

    /**
     * @return mixed
     */
    public function getResponseBody()
    {
        return $this->_response->body();
    }
}
