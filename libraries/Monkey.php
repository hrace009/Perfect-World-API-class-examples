<?php
/* -------------------------------------------------- */
if (!defined('ROOT'))
    define('ROOT', '/var/www/');
/* -------------------------------------------------- */
$folder = ROOT . "libraries/Monkey/";

require_once($folder . "MonkeyList.php");
require_once($folder . "MonkeyMap.php");
require_once($folder . "MonkeyStack.php");
require_once($folder . "MonkeyDatabase.php");
require_once($folder . "MonkeyFilter.php");
require_once($folder . "MonkeyString.php");

unset($folder);
