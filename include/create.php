<?php

/**
 * @param array $dados
 * @return array
 */
function getServerConstants(array $dados)
{
    $localhost = ($_SERVER['SERVER_NAME'] === "localhost" ? true : false);
    $porta = $_SERVER['SERVER_PORT'];

    $dados['sitesub'] = "";
    $dados['dominio'] = ($localhost ? (in_array($porta, ["80", "8080"]) ? explode('/', $_SERVER['REQUEST_URI'])[1] : $porta) : explode('.', $_SERVER['SERVER_NAME'])[0]);
    $dados['ssl'] = isset($dados['protocol']) && $dados['protocol'];
    $dados['www'] = isset($dados['www']) && $dados['www'];
    $dados['home'] = "http" . ($dados['ssl'] ? "s" : "") . "://" . ($localhost ? "localhost" : "") . (in_array($porta, ["80", "8080"]) ? "/" : ":") .
        ($localhost ? (in_array($porta, ["80", "8080"]) ? explode('/', $_SERVER['REQUEST_URI'])[1] : $porta) : $_SERVER['SERVER_NAME']) . "/";
    $dados['path_home'] = $_SERVER['DOCUMENT_ROOT'] . "/" . (!empty($dados['dominio']) && $localhost ? $dados['dominio'] . "/" : "");
    $dados['logo'] = (!empty($_FILES['logo']['name']) ? 'uploads/site/' . $_FILES['logo']['name'] : "");
    $dados['favicon'] = 'uploads/site/' . $_FILES['favicon']['name'];
    $dados['vendor'] = "vendor/conn/";
    $dados['version'] = "1.00";
    $dados['json_support'] = checkJsonSupport();
    $dados['repositorio'] = "http://uebster.com/";

    return $dados;
}

function requireConnectionDatabase($dados)
{
    define('HOST', $dados['host']);
    define('USER', $dados['user']);
    define('PASS', $dados['pass']);
    define('DATABASE', $dados['database']);

    include_once 'Conn.php';
    include_once 'TesteConnection.php';

    $test = new TesteConnection();
    if ($test->getResult()) {
        return false;
    } else {
        include_once 'SqlCommand.php';
        return true;
    }
}

function checkJsonSupport()
{
    $sql = new SqlCommand();
    $sql->exeCommand("CREATE TABLE IF NOT EXISTS `testJsonSupport` (`json_test` json DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    $jsonSupport = !$sql->getResult();
    if ($jsonSupport)
        $sql->exeCommand("DROP TABLE `testJsonSupport`");

    return $jsonSupport;
}

/**
 * Realiza uploads da logo e favicon
 */
function uploadFiles()
{
    if (!empty($_FILES['logo']['name']) && preg_match('/^image\//i', $_FILES['logo']['type']))
        move_uploaded_file($_FILES['logo']['tmp_name'], "../../../uploads/site/" . basename($_FILES['logo']['name']));

    if (preg_match('/^image\//i', $_FILES['favicon']['type']))
        move_uploaded_file($_FILES['favicon']['tmp_name'], "../../../uploads/site/" . basename($_FILES['favicon']['name']));
}

/**
 * Cria Arquivo de Rota e adiciona o atual domínio como uma rota alteranativa
 * @param array $dados
 */
function createRoute(array $dados)
{
    $data = json_decode(file_get_contents("tpl/route.json"), true);
    if (!empty($dados['dominio']) && !in_array($dados['dominio'], $data))
        $data[] = $dados['dominio'];

    Config::writeFile("_config/route.json", json_encode($data));
}

/**
 * Cria Arquivo de Parâmetros Padrões do Sistema Singular
 * @param array $dados
 */
function createParam(array $dados)
{
    $data = json_decode(file_get_contents("tpl/param.json"), true);
    $data['title'] = $dados['sitename'];
    Config::writeFile("_config/param.json", json_encode($data));
}

/**
 * @param array $data
 * @param string $domain
 * @param string $www
 * @param string $protocol
 */
function createHtaccess(array $data, string $domain, string $www, string $protocol)
{
    $dados = "RewriteCond %{HTTP_HOST} ^" . ($www ? "{$domain}\nRewriteRule ^ http" . ($protocol ? "s" : "") . "://www.{$domain}%{REQUEST_URI}" : "www.(.*) [NC]\nRewriteRule ^(.*) http" . ($protocol ? "s" : "") . "://%1/$1") . " [L,R=301]";
    Config::writeFile(".htaccess", str_replace(['{$dados}', '{$home}'], [$dados, $data['home']], file_get_contents("tpl/htaccess.txt")));
}

function getAccessFile()
{
    return '<Files "*.json">
            Order Deny,Allow
            Deny from all
        </Files>
        <Files "*.php">
            Order Deny,Allow
            Deny from all
        </Files>
        <Files "*.html">
            Order Deny,Allow
            Deny from all
        </Files>
        <Files "*.tpl">
            Order Deny,Allow
            Deny from all
        </Files>';
}

if (!empty($dados['sitename']) && !empty($_FILES['favicon']['name'])) {
    if(requireConnectionDatabase($dados)) {
        $dados = getServerConstants($dados);

        //Create Dir
        Config::createDir("entity");
        Config::createDir("entity/general");
        Config::createDir("uploads");
        Config::createDir("uploads/site");
        Config::createDir("_config");
        Config::createDir("public");
        Config::createDir("public/view");
        Config::createDir("public/ajax");
        Config::createDir("public/api");
        Config::createDir("public/apiPublic");
        Config::createDir("public/react");
        Config::createDir("public/react/function");
        Config::createDir("public/param");
        Config::createDir("public/assets");
        Config::createDir("public/dash");
        Config::createDir("public/tpl");
        Config::createDir("assetsPublic");
        Config::createDir("assetsPublic/img");
        copy('assets/dino.png', "../../../assetsPublic/img/dino.png");

        uploadFiles();
        Config::createConfig($dados);
        createRoute($dados);
        createParam($dados);

        Config::writeFile("index.php", file_get_contents("tpl/index.txt"));
        Config::writeFile("tim.php", file_get_contents("tpl/tim.txt"));
        Config::writeFile("apiGet.php", file_get_contents("tpl/apiGet.txt"));
        Config::writeFile("apiGetPublic.php", file_get_contents("tpl/apiGetPublic.txt"));
        Config::writeFile("apiSet.php", file_get_contents("tpl/apiSet.txt"));
        Config::writeFile("apiRequest.php", file_get_contents("tpl/apiRequest.txt"));
        Config::writeFile("public/view/index.php", file_get_contents("tpl/viewIndex.txt"));
        Config::writeFile("_config/entity_not_show.json", '{"1":[],"2":[],"3":[],"0":[]}');
        Config::writeFile("_config/menu_not_show.json", '{"1":[],"2":[],"3":[],"0":[]}');
        Config::writeFile("entity/general/general_info.json", "[]");
        Config::writeFile("_config/.htaccess", "Deny from all");
        Config::writeFile("entity/.htaccess", "Deny from all");
        Config::writeFile("public/react/.htaccess", "Deny from all");
        Config::writeFile("public/api/.htaccess", "Deny from all");
        Config::writeFile("vendor/.htaccess", getAccessFile());

        Config::createHtaccess($dados['dominio'], $dados['www'], $dados['ssl']);

        header("Location: ../../../updateSystem");
    } else {
        echo "<h3 class='container' style='text-align:center;padding-top:30px;color:red'>Erro ao se Comunicar com o Banco de Dados</h3>";
        require_once 'form.php';
    }
} else {
    echo "<h3 class='container' style='text-align:center;padding-top:30px'>Nome e Ícone são obrigatórios</h3>";
    require_once 'form.php';
}