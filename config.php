<?php
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__) . DS);

include_once(ROOT . 'Lib' . DS . 'MySQL.php');

$db = new MySQL('flickr', 'root', '');
