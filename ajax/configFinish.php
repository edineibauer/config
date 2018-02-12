<?php
$lib = strip_tags(trim(filter_input(INPUT_POST, "local", FILTER_DEFAULT)));
unlink(PATH_HOME . "vendor/conn/{$lib}/config.php");

header("Location: " . HOME . "checkDependencies");