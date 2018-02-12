<?php
unlink(PATH_HOME . 'vendor/conn/config/assets/param.json');
unlink(PATH_HOME . 'vendor/conn/config/assets/routes.json');
unlink(PATH_HOME . 'vendor/conn/config/assets/materialize.min.js');
unlink(PATH_HOME . 'vendor/conn/config/assets/jquery.js');
unlink(PATH_HOME . 'vendor/conn/config/assets/config.css');
rmdir(PATH_HOME . 'vendor/conn/config/assets');

unlink(PATH_HOME . 'vendor/conn/config/include/form.php');
unlink(PATH_HOME . 'vendor/conn/config/include/create.php');
rmdir(PATH_HOME . 'vendor/conn/config/include');

unlink(PATH_HOME . 'vendor/conn/config/tpl/htaccess.txt');
unlink(PATH_HOME . 'vendor/conn/config/tpl/index.txt');
rmdir(PATH_HOME . 'vendor/conn/config/tpl');

unlink(PATH_HOME . 'vendor/conn/config/ajax/defecon4.php');

unlink(PATH_HOME . 'vendor/conn/config/startup.php');

header("Location: " . HOME . "dashboard");