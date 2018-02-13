<?php

use Helpers\Helper;

$config = false;

foreach (Helper::listFolder(PATH_HOME . "vendor/conn") as $item) {
    if(!file_exists(PATH_HOME . "_config/updates/{$item}.txt") && file_exists(PATH_HOME . "vendor/conn/{$item}/config.php")) {
        require_once PATH_HOME . "vendor/conn/{$item}/config.php";
        $config = true;
        break;
    }
}

if (!$config && file_exists(PATH_HOME . "vendor/conn/config/ajax/defecon4.php")) {
    include_once PATH_HOME . "vendor/conn/config/ajax/defecon4.php";
}elseif(!$config) {
    header("Location: " . HOME . "dashboard");
}