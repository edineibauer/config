<?php
function createDir($dir)
{
    if (!file_exists("../../../{$dir}"))
        mkdir("../../../{$dir}", 0777);

    return "../../../{$dir}";
}

function writeFile($file, $content)
{
    $fp = fopen("../../../{$file}", "w+");
    fwrite($fp, $content);
    fclose($fp);
}

function getValuesServer($dados)
{
    $uri = $_SERVER['REQUEST_URI'];
    $domain = $_SERVER['SERVER_NAME'];

    $dados['localhost'] = ($domain === "localhost" ? true : false);
    $domain = ($dados['localhost'] ? explode('/', $uri)[1] : $domain);
    $dados['protocol'] = (isset($dados['protocol']) ? 'https://' : 'http://');
    $dados['www'] = $dados['www'] ? "www" : "";
    $dados['dominio'] = $domain;
    $dados['sitesub'] = "";
    $dados['home'] = $dados['protocol'] . ($dados['localhost'] ? 'localhost/' : '') . $dados['dominio'] . "/";
    $dados['path_home'] = ($_SERVER['DOCUMENT_ROOT'] . ($dados['localhost'] ? DIRECTORY_SEPARATOR . $dados['dominio'] : "") . "/");
    $dados['logo'] = (!empty($_FILES['logo']['name']) ? 'uploads/site/' . $_FILES['logo']['name'] : "");
    $dados['favicon'] = (!empty($_FILES['favicon']['name']) ? 'uploads/site/' . $_FILES['favicon']['name'] : "");
    $dados['dev'] = $dados['dev'] ?? false;
    $dados['version'] = "1.00";

    if (isset($dados['recaptcha'])) {
        if (empty($dados['recaptchasite']) || empty($dados['recaptcha']))
            unset($dados['recaptchasite'], $dados['recaptcha']);
    }

    if (empty($dados['email']) || empty($dados['mailgundomain']) || empty($dados['mailgunkey']))
        unset($dados['email'], $dados['mailgundomain'], $dados['mailgunkey']);

    return $dados;
}

function uploadFiles()
{
    createDir("uploads");
    $uploaddir = createDir("uploads/site");

    if (!empty($_FILES['logo']['name']) && preg_match('/^image\//i', $_FILES['logo']['type']))
        move_uploaded_file($_FILES['logo']['tmp_name'], $uploaddir . DIRECTORY_SEPARATOR . basename($_FILES['logo']['name']));

    if (!empty($_FILES['favicon']['name']) && preg_match('/^image\//i', $_FILES['favicon']['type']))
        move_uploaded_file($_FILES['favicon']['tmp_name'], $uploaddir . DIRECTORY_SEPARATOR . basename($_FILES['favicon']['name']));
}

function createHtaccess($data, $www = null, $domain = null, $protocol = null)
{
    $dados = "RewriteCond %{HTTP_HOST} ^" . ($www === "www" ? "{$domain}\nRewriteRule ^ {$protocol}://www.{$domain}%{REQUEST_URI}" : "www.(.*) [NC]\nRewriteRule ^(.*) {$protocol}://%1/$1") . " [L,R=301]";
    writeFile(".htaccess", str_replace(['{$dados}', '{$home}'], [$dados, $data['home']], file_get_contents("tpl/htaccess.txt")));
}

function createConfig($dados)
{
    $dados['homedev'] = "HOME . (DEV?'':'vendor/conn/{$dados['dominio']}/')";
    $conf = "<?php\n";
    foreach ($dados as $dado => $value) {
        $value = (is_bool($value) ? ($value ? 'true' : 'false') : "'{$value}'");
        $conf .= "define('" . strtoupper($dado) . "', {$value});\n";
    }

    createDir("_config");
    writeFile("_config/config.php", $conf);
}

function createRoute($dados)
{
    $data = json_decode(file_get_contents("assets/routes.json"), true);
    if ($dados['dev'] && !in_array($dados['dominio'], $data))
        $data[] = $dados['dominio'];

    writeFile("_config/route.json", json_encode($data));
}

function createParam($dados)
{
    $data = str_replace('{$sitename}', $dados['sitename'], file_get_contents("assets/param.json"));
    writeFile("_config/param.json", $data);
}

function createManifest($dados) {
    $data = str_replace(['{$sitename}', '{$favicon}', '{$theme}', '{$themeColor}'], [$dados['sitename'], $dados['favicon'], '#2196f3', '#FFFFFF'], file_get_contents("tpl/manifest.txt"));
    writeFile("manifest.json", $data);
}

function createServiceWorker() {
    writeFile("service-worker.js", file_get_contents("tpl/service-worker.txt"));
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

if (!empty($dados['sitename']) && !empty($dados['user']) && !empty($dados['host']) && !empty($dados['database']) && !empty($dados['pre'])) {
    $dados = getValuesServer($dados);
    uploadFiles();
    writeFile("index.php", file_get_contents("tpl/index.txt"));
    createConfig($dados);
    createRoute($dados);
    createParam($dados);
    createManifest($dados);
    createServiceWorker();
    writeFile("tim.php", file_get_contents("tpl/tim.txt"));
    writeFile("get.php", file_get_contents("tpl/get.txt"));
    writeFile("_config/entity_not_show.json", '{"1":[],"2":[],"3":[],"0":[]}');
    writeFile("_config/menu_not_show.json", '{"1":[],"2":[],"3":[],"0":[]}');
    createDir("entity");
    createDir("entity/general");
    writeFile("entity/general/general_info.json", "[]");
    writeFile("_config/.htaccess", "Deny from all");
    writeFile("entity/.htaccess", "Deny from all");
    writeFile("vendor/.htaccess", getAccessFile());

    createHtaccess($dados, $dados['www'] ?? null, $dados['dominio'] ?? null, $dados['protocol'] ?? null);
}