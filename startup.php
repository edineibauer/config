<?php

if (!file_exists('../../../_config')) {
    $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
    if ($dados) {
        include_once 'include/create.php';

        require('../../../_config/config.php');

        if (class_exists('\SessionControl\Login'))
            include_once 'include/adminCreator.php';
        else
            include_once 'ajax/defecon4.php';

    } else {
        $domain = $_SERVER['SERVER_NAME'];
        $domain = ($domain === "localhost" ? explode('/', $_SERVER['REQUEST_URI'])[1] : $domain);
        $table = explode(".", $domain)[0];
        $pre = substr(str_replace(array('a', 'e', 'i', 'o', 'u'), '', $table), 0, 3) . "_";

        include_once 'include/form.php';
    }
} else {
    if (file_exists('ajax/defecon4.php'))
        include_once 'ajax/defecon4.php';
}