<?php
ob_start();
if (!file_exists('../../../_config')) {
    $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
    if ($dados)
        include_once 'include/create.php';
    else
        include_once 'include/form.php';
}
ob_end_flush();