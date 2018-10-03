<?php
$newKey = trim(strip_tags(filter_input(INPUT_POST, 'newKey', FILTER_DEFAULT)));
$data['error'] = "erro";

try {
    //Adiciona constante KEY na config
    if (defined("KEY")) {
        $config = file_get_contents(PATH_HOME . "_config/config.php");
        $config = str_replace("define('KEY', '{$key}')", "define('KEY', '{$newKey}')", $config);
    } else {
        $config = file_get_contents(PATH_HOME . "_config/config.php");
        $config = str_replace("<?php", "<?php\ndefine('KEY', '{$key}');", $config);
    }

    //Salva config
    $f = fopen(PATH_HOME . "_config/config.php", "w");
    fwrite($f, $config);
    fclose($f);

    $data['error'] = "";

} catch (Exception $e) {

}