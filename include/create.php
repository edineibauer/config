<?php

/**
 * Cria Diretório
 * @param string $dir
 * @return string
 */
function createDir(string $dir)
{
    if (!file_exists("../../../{$dir}"))
        mkdir("../../../{$dir}", 0777);

    return "../../../{$dir}";
}

/**
 * Cria Arquivo
 * @param string $file
 * @param string $content
 */
function writeFile(string $file, string $content)
{
    $fp = fopen("../../../{$file}", "w+");
    fwrite($fp, $content);
    fclose($fp);
}

/**
 * @param array $dados
 * @return array
 */
function getServerConstants(array $dados)
{
    $localhost = ($_SERVER['SERVER_NAME'] === "localhost" ? true : false);

    $porta = $_SERVER['SERVER_PORT'];

    $dados['sitesub'] = "";
    $dados['dominio'] = ($localhost ? (in_array($porta, ["80", "8080"]) ? explode('/', $_SERVER['REQUEST_URI'])[1] : $porta) : $_SERVER['SERVER_NAME']);
    $dados['ssl'] = isset($dados['protocol']) && $dados['protocol'];
    $dados['www'] = isset($dados['www']) && $dados['www'];
    $dados['home'] = "http" . ($dados['ssl'] ? "s" : "") . "://" . ($localhost ? "localhost" : "") . (in_array($porta, ["80", "8080"]) ? "/" : ":") . (!empty($dados['dominio']) ? $dados['dominio'] . "/" : "");
    $dados['path_home'] = $_SERVER['DOCUMENT_ROOT'] . "/" . (!empty($dados['dominio']) ? $dados['dominio'] . "/" : "");
    $dados['logo'] = (!empty($_FILES['logo']['name']) ? 'uploads/site/' . $_FILES['logo']['name'] : "");
    $dados['favicon'] = 'uploads/site/' . $_FILES['favicon']['name'];
    $dados['vendor'] = "vendor/conn/";
    $dados['version'] = "1.00";

    return $dados;
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
 * Criar Arquivo de Configurações
 * @param array $dados
 */
function createConfig(array $dados)
{
    $conf = "<?php\n";
    foreach ($dados as $dado => $value) {
        $value = (is_bool($value) ? ($value ? 'true' : 'false') : "'{$value}'");
        $conf .= "define('" . strtoupper(trim($dado)) . "', {$value});\n";
    }

    writeFile("_config/config.php", $conf);
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

    writeFile("_config/route.json", json_encode($data));
}

/**
 * Cria Arquivo de Parâmetros Padrões do Sistema Singular
 * @param array $dados
 */
function createParam(array $dados)
{
    $data = json_decode(file_get_contents("tpl/param.json"), true);
    $data['title'] = $dados['sitename'];
    writeFile("_config/param.json", json_encode($data));
}

/**
 * Cria Arquivo de Manifest e Service Worker para PWA
 * @param array $dados
 */
function createManifest(array $dados) {
    $data = str_replace(['{$sitename}', '{$favicon}', '{$theme}', '{$themeColor}'], [$dados['sitename'], $dados['favicon'], '#2196f3', '#FFFFFF'], file_get_contents("tpl/manifest.txt"));
    writeFile("manifest.json", $data);
    writeFile("service-worker.js", file_get_contents("tpl/service-worker.txt"));
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
    writeFile(".htaccess", str_replace(['{$dados}', '{$home}'], [$dados, $data['home']], file_get_contents("tpl/htaccess.txt")));
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
    $dados = getServerConstants($dados);

    //Create Dir
    createDir("entity");
    createDir("entity/general");
    createDir("uploads");
    createDir("uploads/site");
    createDir("_config");
    createDir("public");
    createDir("public/view");
    createDir("public/ajax");
    createDir("public/param");
    createDir("public/assets");
    createDir("public/dash");
    createDir("public/tpl");

    uploadFiles();
    createConfig($dados);
    createRoute($dados);
    createParam($dados);
    createManifest($dados);

    writeFile("index.php", file_get_contents("tpl/index.txt"));
    writeFile("tim.php", file_get_contents("tpl/tim.txt"));
    writeFile("apiGet.php", file_get_contents("tpl/apiGet.txt"));
    writeFile("apiSet.php", file_get_contents("tpl/apiSet.txt"));
    writeFile("public/view/index.php", file_get_contents("tpl/viewIndex.txt"));
    writeFile("_config/entity_not_show.json", '{"1":[],"2":[],"3":[],"0":[]}');
    writeFile("_config/menu_not_show.json", '{"1":[],"2":[],"3":[],"0":[]}');
    writeFile("entity/general/general_info.json", "[]");
    writeFile("_config/.htaccess", "Deny from all");
    writeFile("entity/.htaccess", "Deny from all");
    writeFile("vendor/.htaccess", getAccessFile());

    createHtaccess($dados, $dados['dominio'], $dados['www'], $dados['ssl']);

    header("Location: ../../../");
} else {
    require_once 'form.php';
}