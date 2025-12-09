<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 5/25/16
 * Time: 3:36 PM
 */

namespace Templates;


class HtmlTemplate {

    public $Html;
    public $Head;
    public $Header;
    public $Body;
    public $Footer;

    public function __construct($css = null, $header = null, $body = null, $footer = null) {

        $this->Html = "
        <!doctype html>
        <html>
        <head>
            <meta name='viewport' content='width=device-width'>
            <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        ";

    }

} 