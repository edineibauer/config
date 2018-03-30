<?php
ob_start();
require_once 'config.php';
require_once '../vendor/autoload.php';

function updateVersionTxt() {
    $f = fopen(PATH_HOME . "_config/updates/version.txt", "w+");
    fwrite($f, file_get_contents(PATH_HOME . "composer.lock"));
    fclose($f);
}

if(file_exists(PATH_HOME . "_config/updates/version.txt")) {
    $old = file_get_contents(PATH_HOME . "_config/updates/version.txt");
    $actual = file_get_contents(PATH_HOME . "composer.lock");
    if($old !== $actual) {
        $conf = file_get_contents(PATH_HOME . "_config/config.php");
        $version = (float) explode(')', explode("'VERSION', ", $conf)[1])[0];
        $newVersion = $version + 0.01;
        $conf = str_replace("'VERSION', {$version})", "'VERSION', {$newVersion})", $conf);
        $f = fopen(PATH_HOME . "_config/config.php", "w");
        fwrite($f, $conf);
        fclose($f);
        updateVersionTxt();
    }

} else {
    \Helpers\Helper::createFolderIfNoExist(PATH_HOME . "_config/updates");
    updateVersionTxt();
}

ob_get_flush();