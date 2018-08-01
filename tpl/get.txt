<?php
ob_start();
require_once './_config/config.php';
require_once './vendor/autoload.php';

use LinkControl\Sessao;
use LinkControl\Route;
use LinkControl\Link;

/** RESPONSES
 * 1 -> rota encontrada e corretamente executada
 * 2 -> rota encontrada e erro encontrado
 * 3 -> redirecionamento
 * 4 -> rota não encontrada
 * outro -> não faz nada
 * */

$url = explode('get/', $_SERVER['REQUEST_URI']);
$data = ["response" => 1, "error" => "", "data" => ""];
if (!empty($url[1])) {
    new Sessao();
    $route = new Route($url[1]);

    if ($route->getRoute()) {
        include_once $route->getRoute();

        $home = HOME;

        if (!isset($data) || !isset($data['response']) || !in_array($data['response'], [1, 2, 3, 4])) {
            $data = ["response" => 2, "error" => "data retornada não formatada corretamente", "data" => ""];

        } elseif ($data['response'] === 1) {
            $link = new Link($route->getLib(), $route->getFile(), $route->getVar());
            $data["data"] = [
                "title" => $link->getParam()['title'],
                "descricao" => $link->getParam()['descricao'],
                "css" => $link->getParam()['css'],
                "js" => $link->getParam()['js'],
                "meta" => $link->getParam()['meta'],
                "font" => $link->getParam()['font'],
                "content" => $data['data']
            ];

        } elseif ($data['response'] === 3 && (!is_string($data['data']) || !preg_match("/^http/i", $data['data']))) {
            $data = ["response" => 2, "error" => "url de redirecionamento não encontrada, precisa começar com " . HOME, "data" => ""];
        }
    } else {
        $data["response"] = 4;
    }

    if($data['response'] === 4 && preg_match('/^data\/\w+/i', $url[1]))
        $data["response"] = 5;
} else {
    $data["response"] = 4;
}

echo json_encode($data);

ob_get_flush();
