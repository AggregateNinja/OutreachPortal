<?php
require_once 'Utility/IConfig.php';
interface IDatabase extends IConfig {

    const DB_USERNAME = "user";
    const DB_PASSWORD = "password";
    const DB_CSS = "schema";
    const DB_CSS_WEB = "schemaweb";
    const DB_CSSBILLING = "schemabilling";

    const DEV_MODE = false;

    const RequireCompleted = false; 
    const HoldUntilCompleteOnly = false;
}
?>