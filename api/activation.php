<?php

if(!defined("KEY") && !empty($key)) {

    //Adiciona constante KEY na config
    $config = file_get_contents(PATH_HOME . "_config/config.php");
    $config = str_replace("<?php", "<?php\ndefine('KEY', '{$key}');\n", $config);

    //Salva config
    $f = fopen(PATH_HOME . "_config/config.php", "w");
    fwrite($f, $config);
    fclose($f);

    //retorna versão das bibliotecas
    $teste = json_decode(file_get_contents(PATH_HOME . "composer.lock"), true);
    $libs = [];
    foreach ($teste['packages'] as $package) {
        if (preg_match('/^conn\//i', $package['name']))
            $libs[] = ["versao" => $package['version'], "nome" => $package['name']];
    }

    $data['data'] = json_encode($libs);
} else {
    $data['error'] = "chave já definida ou não informada";
}