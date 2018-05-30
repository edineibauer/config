<?php
ob_start();

use Helpers\Helper;
use ConnCrud\SqlCommand;

$config = false;

function writeConfig($field, $value)
{
    $file = file_get_contents(PATH_HOME . "_config/config.php");
    if (preg_match("/\'{$field}\',/i", $file)) {
        $valueOld = explode("'", explode("('{$field}', '", $file)[1])[0];
        $file = str_replace("'{$field}', '{$valueOld}'", "'{$field}', '{$value}'", $file);
    } else {
        $file = str_replace("<?php", "<?php\ndefine('{$field}', '{$value}');", $file);
    }

    $f = fopen(PATH_HOME . "_config/config.php", "w+");
    fwrite($f, $file);
    fclose($f);
}

/**
 * Check Json Support constant
 */
if (!defined('JSON_SUPPORT')) {
    $sql = new SqlCommand();
    $sql->exeCommand("CREATE TABLE IF NOT EXISTS `testJsonSupport` (`json_test` json DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    $jsonSupport = !$sql->getResult();
    writeConfig("JSON_SUPPORT", $jsonSupport);
    if ($jsonSupport)
        $sql->exeCommand("DROP TABLE `testJsonSupport`");
}

foreach (Helper::listFolder(PATH_HOME . "vendor/conn") as $item) {
    if (!file_exists(PATH_HOME . "_config/updates/{$item}.txt") && file_exists(PATH_HOME . "vendor/conn/{$item}/config.php")) {
        require_once PATH_HOME . "vendor/conn/{$item}/config.php";
        $config = true;
        break;
    }
}

if (!$config && file_exists(PATH_HOME . "vendor/conn/config/ajax/inc/defecon4.php")) {
    include_once PATH_HOME . "vendor/conn/config/ajax/inc/defecon4.php";
    $data['response'] = 3;
    $data['data'] = HOME . "dashboard";
} elseif (!$config) {
    $data['response'] = 3;
    $data['data'] = HOME . "dashboard";
} else {
    $data['data']['content'] = ob_get_contents();
}

ob_end_clean();