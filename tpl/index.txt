<?php
ob_start();
require_once './_config/config.php';
require_once './vendor/autoload.php';
require_once PATH_HOME . 'vendor/conn/link-control/tpl/tpls.php';

use Helpers\Template;
use LinkControl\Link;
use LinkControl\Route;
use LinkControl\Sessao;

new Sessao();
$route = new Route();
$link = new Link($route->getLib(), $route->getFile(), $route->getVar());

$view = new Template("link-control");
$view->show("index", $link->getParam());

ob_get_flush();
