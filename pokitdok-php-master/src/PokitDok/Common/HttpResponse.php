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


class HttpResponse {

    const HTTP_HEADER_BREAK = "\r\n\r\n";

    private $_response;
    private $_header_length;
    private $_headers;
    private $_body;

    public function __construct($response)
    {
        $this->_response = $response;
        $this->_header_length = strrpos($response, self::HTTP_HEADER_BREAK) + strlen(self::HTTP_HEADER_BREAK);
        $this->_headers = array();
        $this->_body = "";

        $this->header();
        $this->body();
    }

    /**
     * @return array
     */
    public function header()
    {
        if (!empty($this->_headers)) {
            return $this->_headers;
        }

        $raw_header = substr($this->_response, 0, $this->_header_length);
        $lines = explode("\r\n", $raw_header);

        foreach ($lines as $line)
        {
            if (empty($line)) {
                continue;
            }
            $sep_pos = strpos($line, ':');
            if ($sep_pos !== false) {
                $this->_headers[trim(substr($line, 0, $sep_pos))] =
                    trim(substr($line, $sep_pos+1, strlen($line) - ($sep_pos+1)));
            } else {
                $this->_headers[trim($line)] = '';
            }
        }

        return $this->_headers;
    }

    /**
     * @return mixed
     */
    public function body()
    {
        if (!empty($this->_body)) {
            return $this->_body;
        }

        $raw_body = substr($this->_response, $this->_header_length);

        $body = json_decode($raw_body);
        $this->_body = ($body === false ? $raw_body : $body);

        return $this->_body;
    }
}