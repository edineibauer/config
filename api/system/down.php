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

    //remove composer biblioteca controle fixo
    if(file_exists(PATH_HOME . "composer.json")) {
        $comp = json_decode(file_get_contents(PATH_HOME . "composer.json"), true);
        foreach ($comp['require'] as $lib => $version) {
            if(preg_match('/^conn\//i', $lib)) {
                $v = explode('.', $version);
                $comp['require'][$lib] = $v[0] . '.' . $v[1] . ".*";
            }
        }

        //Salva composer
        $f = fopen(PATH_HOME . "composer.json", "w");
        fwrite($f, json_encode($comp));
        fclose($f);
    }

    $data['error'] = "";

} catch (Exception $e) {

}