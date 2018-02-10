<?php
function createDir($dir) {
    if(!file_exists("../../../{$dir}"))
        mkdir("../../../{$dir}", 0777);

    return "../../../{$dir}";
}

function writeFile($file, $content) {
    $fp = fopen("../../../{$file}", "w");
    fwrite($fp, $content);
    fclose($fp);
}

function getValuesServer($dados) {
    $uri = $_SERVER['REQUEST_URI'];
    $domain = $_SERVER['SERVER_NAME'];

    $dados['localhost'] = ($domain === "localhost" ? true : false);
    $domain = ($dados['localhost'] ? explode('/', $uri)[1] : $domain);
    $dados['protocol'] = (isset($dados['protocol']) ? 'https://' : 'http://');
    $dados['dominio'] = $domain;
    $dados['home'] = $dados['protocol'] . ($dados['localhost'] ? 'localhost/' : '') . $dados['dominio'] . "/";
    $dados['path_home'] = ($_SERVER['DOCUMENT_ROOT'] . ($dados['localhost'] ? DIRECTORY_SEPARATOR . $dados['dominio'] : "") . "/");
    $dados['logo'] = (!empty($_FILES['logo']['name']) ? $dados['home'] . '_uploads/' . $_FILES['logo']['name'] : "");
    $dados['favicon'] = (!empty($_FILES['favicon']['name']) ? $dados['home'] . '_uploads/' . $_FILES['favicon']['name'] : "");
    $dados['dev'] = false;

    if(empty($dados['recaptchasite']) || empty($dados['recaptcha']))
        unset($dados['recaptchasite'], $dados['recaptcha']);

    if(empty($dados['email']) || empty($dados['mailgundomain']) || empty($dados['mailgunkey']))
        unset($dados['email'], $dados['mailgundomain'], $dados['mailgunkey']);

    return $dados;
}

function uploadFiles() {
    createDir("uploads");
    $uploaddir = createDir("uploads/site");

    if(!empty($_FILES['logo']['name']) && preg_match('/^image\//i', $_FILES['logo']['type']))
        move_uploaded_file($_FILES['logo']['tmp_name'], $uploaddir . DIRECTORY_SEPARATOR . basename($_FILES['logo']['name']));

    if(!empty($_FILES['favicon']['name']) && preg_match('/^image\//i', $_FILES['favicon']['type']))
        move_uploaded_file($_FILES['favicon']['tmp_name'], $uploaddir . DIRECTORY_SEPARATOR . basename($_FILES['favicon']['name']));
}

function createHtaccess($www = null, $domain = null, $protocol = null)
{
    $dados = "RewriteCond %{HTTP_HOST} ^" . ($www ? "{$domain}\nRewriteRule ^ {$protocol}://www.{$domain}%{REQUEST_URI}" : "www.(.*) [NC]\nRewriteRule ^(.*) http://%1/$1") . " [L,R=301]";
    writeFile(".htaccess", str_replace('{$dados}', $dados, file_get_contents("tpl/htaccess.txt")));
}

function createConfig($dados) {
    unset($dados['www']);
    $conf = "<?php\n";
    foreach ($dados as $dado => $value) {
        $value = (is_bool($value) ? ($value ? 'true' : 'false') : "'{$value}'");
        $conf .= "define('" . strtoupper($dado) . "', {$value});\n";
    }

    $conf .= "\$script = \"<script>const HOME = '\" . HOME . \"';</script>\";\nrequire_once PATH_HOME . 'vendor/autoload.php';\n\n\$link = new \LinkControl\Link();";

    createDir("_config");
    writeFile("_config/config.php", $conf);
}

function createRoute() {
    copy("assets/routes.json", "../../../_config/route.json");
}

function createTheme() {
    $dest = createDir("assets");
    copy("assets/theme-blue.css", $dest . DIRECTORY_SEPARATOR . "theme.css");
}

function createParam($dados) {
    $data = str_replace('{$sitename}', $dados['sitename'], file_get_contents("assets/param.json"));
    writeFile("_config/param.json", $data);
}

if(!empty($dados['sitename']) && !empty($dados['user']) && !empty($dados['host']) && !empty($dados['database']) && !empty($dados['pre']))
{
    $dados = getValuesServer($dados);
    uploadFiles();
    writeFile("index.php", file_get_contents("tpl/index.txt"));
    createConfig($dados);
    createRoute();
    createParam($dados);
    createTheme();
    createHtaccess($dados['www'] ?? null, $dados['dominio'] ?? null, $dados['protocol'] ?? null);
    unlink('startup.php');
    unlink('assets/config.css');
    unlink('assets/config.js');
    unlink('assets/param.json');
    unlink('assets/routes.json');
    unlink('assets/theme-blue.css');
    unlink('include/create.php');
    unlink('include/form.php');
    unlink('tpl/htaccess.txt');
    unlink('tpl/index.txt');
    rmdir('assets');
    rmdir('include');
    rmdir('tpl');

    header("Location: ../../../dashboard");
}