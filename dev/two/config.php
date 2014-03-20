<?php
define('DEBUG', 0);
define('DS', DIRECTORY_SEPARATOR);
define('ROOT',  dirname(__FILE__) . DS);
define('PHOTOS_PATH', dirname(dirname(__FILE__)) . DS . 'photos' . DS);

include_once(ROOT . 'Lib' . DS . 'ColorPalette.php');
include_once(ROOT . 'Lib' . DS . 'Flickr.php');
include_once(ROOT . 'Lib' . DS . 'MySQL.php');
include_once(ROOT . 'Lib' . DS . 'KLogger.php');

$db = new MySQL('flickr', 'root', '');