<?php

use Helpers\Helper;

$config = false;

foreach (Helper::listFolder(PATH_HOME . "vendor/conn") as $item) {
    if(file_exists(PATH_HOME . "vendor/conn/{$item}/config.php")) {
        require_once PATH_HOME . "vendor/conn/{$item}/config.php";
        $config = true;
        break;
    }
}

if (!$config)
    include_once PATH_HOME . "vendor/conn/config/ajax/defecon4.php";
