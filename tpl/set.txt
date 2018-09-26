<?php
ob_start();
require_once './_config/config.php';
require_once './vendor/autoload.php';

use LinkControl\Sessao;
use LinkControl\Route;
use \Helpers\Check;

/** RESPONSES
 * 1 -> rota encontrada e corretamente executada
 * 2 -> rota encontrada e erro encontrado
 * 3 -> redirecionamento
 * 4 -> rota não encontrada
 * outro -> não faz nada
 * */

$file = filter_input(INPUT_POST, 'file', FILTER_DEFAULT);
$lib = filter_input(INPUT_POST, 'lib', FILTER_DEFAULT);
$data = ["response" => 1, "error" => "", "data" => ""];

if (empty($file) && empty($lib)) {
    $url = explode('set/', $_SERVER['REQUEST_URI'])[1];
    $route = new Route($url, 'ajax');
    if ($route->getRoute())
        $include = $route->getRoute();
} else {
    $include = PATH_HOME . VENDOR . $lib . "/ajax/" . $file . ".php";
}

if (isset($include)) {
    new Sessao();
    if (Check::ajax()) {
        include_once $include;

        if (!isset($data) || !isset($data['response']) || !in_array($data['response'], [1, 2, 3, 4]))
            $data = ["response" => 2, "error" => "data retornada não formatada corretamente", "data" => ""];
        elseif ($data['response'] === 3 && (!is_string($data['data']) || !preg_match("/^" . HOME . "/i", $data['data'])))
            $data = ["response" => 2, "error" => "url de redirecionamento não encontrada, precisa começar com " . HOME, "data" => ""];

    } else {
        $data = ["response" => 2, "error" => "request nao permitido", "data" => ""];
    }
} else {
    $data["response"] = 4;
}

echo json_encode($data);

ob_get_flush();
