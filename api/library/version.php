<?php

$teste = json_decode(file_get_contents(PATH_HOME . "composer.lock"), true);
$libs = [];
foreach ($teste['packages'] as $package) {
    if (preg_match('/^conn\//i', $package['name']))
        $libs[] = ["versao" => $package['version'], "nome" => $package['name']];
}

$data['data'] = json_encode($libs);
