<?php
$data['error'] = "erro";

try {
    //Adiciona constante KEY na config
    if (defined("KEY")) {
        $config = file_get_contents(PATH_HOME . "_config/config.php");
        $config = str_replace("define('KEY', '{$key}');", "", $config);

        //Salva config
        $f = fopen(PATH_HOME . "_config/config.php", "w");
        fwrite($f, $config);
        fclose($f);
    }

    $data['error'] = "";

} catch (Exception $e) {

}