<?php

use Helpers\Helper;

$lib = strip_tags(trim(filter_input(INPUT_POST, "local", FILTER_DEFAULT)));
unlink(PATH_HOME . "vendor/conn/{$lib}/config.php");

Helper::createFolderIfNoExist(PATH_HOME . "_config/updates");
$f = fopen(PATH_HOME . "_config/updates" . DIRECTORY_SEPARATOR . $lib . ".txt", "w");
fwrite($f, "1");
fclose($f);

$data['data'] = '1';