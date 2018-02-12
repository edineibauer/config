<?php

unlink('assets/param.json');
unlink('assets/routes.json');
unlink("assets/materialize.min.js");
unlink("assets/jquery.js");
unlink('assets/config.css');
rmdir('assets');

unlink('include/form.php');
unlink('include/create.php');
rmdir('include');

unlink('tpl/htaccess.txt');
unlink('tpl/index.txt');
rmdir('tpl');

unlink('ajax/defecon4.php');

unlink('startup.php');

header("Location: ../../../dashboard");