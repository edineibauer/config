<?php
$dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
if($dados){
    
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

        if(empty($dados['recaptchasite']) || empty($dados['recaptcha'])) {
            unset($dados['recaptchasite'], $dados['recaptcha']);
        }

        if(empty($dados['email']) || empty($dados['mailgundomain']) || empty($dados['mailgunkey'])) {
            unset($dados['email'], $dados['mailgundomain'], $dados['mailgunkey']);
        }

        return $dados;
    }

    function uploadFiles() {
        mkdir('../../../_uploads', 0777);
        $uploaddir = '../../../_uploads/';

        if(!empty($_FILES['logo']['name']) && preg_match('/^image\//i', $_FILES['logo']['type'])) {
            move_uploaded_file($_FILES['logo']['tmp_name'], $uploaddir . basename($_FILES['logo']['name']));
        }

        if(!empty($_FILES['favicon']['name']) && preg_match('/^image\//i', $_FILES['favicon']['type'])) {
            move_uploaded_file($_FILES['favicon']['tmp_name'], $uploaddir . basename($_FILES['favicon']['name']));
        }
    }

    function createHtaccess($www = null, $domain = null, $protocol = null) {

        if($www) {
            $dados = "RewriteCond %{HTTP_HOST} ^{$domain}\nRewriteRule ^ {$protocol}://www.{$domain}%{REQUEST_URI} [L,R=301]";
        } else {
            $dados = "RewriteCond %{HTTP_HOST} ^www.(.*) [NC]\nRewriteRule ^(.*) http://%1/$1 [R=301,L]";
        }

        $fp = fopen("../../../.htaccess", "w");
        $escreve = fwrite($fp,
"RewriteEngine On
{$dados}

RewriteCond %{SCRIPT_FILENAME} !-f
RewriteCond %{SCRIPT_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1

<IfModule mod_expires.c>
ExpiresActive On
ExpiresByType image/jpg \"access 1 year\"
ExpiresByType image/jpeg \"access 1 year\"
ExpiresByType image/gif \"access 1 year\"
ExpiresByType image/png \"access 1 year\"
ExpiresByType text/css \"access 1 day\"
ExpiresByType text/html \"access 1 day\"
ExpiresByType application/pdf \"access 1 month\"
ExpiresByType text/x-javascript \"access 1 day\"
ExpiresByType application/x-shockwave-flash \"access 1 month\"
ExpiresByType image/x-icon \"access 1 year\"
ExpiresDefault \"access 1 month\"
</IfModule>");
        fclose($fp);
    }

    function createIndex() {
        $fp = fopen("../../../index.php", "w");
        $escreve = fwrite($fp, "<?php
require('./_config/config.php');

use Helpers\Check;
use Helpers\View;

\$view = new View();

if(!Check::ajax()){

    \$view->show(\"header\", \$link->getParam());
    require_once \$link->getRoute();
    echo \$script;
    \$view->show(\"footer\", \$link->getParam());

} else {

    require_once \$link->getRoute();
}");
        fclose($fp);
    }

    function createConfig($dados) {
        unset($dados['www']);
        $conf = "<?php\n";
        foreach ($dados as $dado => $value) {
            $value = ($dado === "localhost" ? ($value ? 'true' : 'false') : "'{$value}'");
            $conf .= "define('" . strtoupper($dado) . "', {$value});\n";
        }

        $conf .= "\$script = \"<script>const HOME = '\" . HOME . \"';</script>\";\n
require_once PATH_HOME . 'vendor/autoload.php';\n\n";

       /* $conf .= "\$session = new \SessionControl\Session();
\$session->setLevelAccess(1, \"usuário\", \"user\",\"usuário do site, acesso a recursos e consumidor de produtos.\");
\$session->setLevelAccess(2, \"produtor\", \"producer\", \"produtor de conteúdos para o site, ou abastecimento de informações.\");
\$session->setLevelAccess(3, \"analista\", \"managment\", \"analista, verificador de conteúdo. Gerenciador dos usuários de produção e usuários do site\");
\$session->setLevelAccess(4, \"gerente\", \"adm\", \"acesso total ao sistema com excessão ao gerenciamento de usuários de mesmo nível ou superior.\");
\$session->setLevelAccess(5, \"administrador\", \"adm\", \"acesso total ao sistema e controle.\");\n\n";*/

$conf .= "\$link = new \LinkControl\Link();";

        mkdir('../../../_config', 0777);
        $fp = fopen("../../../_config/config.php", "w");
        $escreve = fwrite($fp, $conf);
        fclose($fp);
    }

    if(isset($dados['sitename']) && !empty($dados['sitename'])
    && isset($dados['user']) && !empty($dados['user'])
    && isset($dados['host']) && !empty($dados['host'])
    && isset($dados['database']) && !empty($dados['database'])
    && isset($dados['pre']) && !empty($dados['pre']))
    {
        $dados = getValuesServer($dados);
        uploadFiles();
        createIndex();
        createConfig($dados);
        createHtaccess($dados['www'] ?? null, $dados['dominio'] ?? null, $dados['protocol'] ?? null);
        unlink('generate.php');

        header("Location: index.php");
    }

} else {
    $domain = $_SERVER['SERVER_NAME'];
    $domain = ($domain === "localhost" ? explode('/', $_SERVER['REQUEST_URI'])[1] : $domain);
    $table = explode(".", $domain)[0];
    $pre = substr(str_replace(array('a', 'e', 'i', 'o', 'u'), '', $table), 0, 3) . "_";
    ?>
    <div class="row">
        <div class="container">
            <form class="card" method="post" action="" enctype="multipart/form-data"
                  style="background: #FFF; padding:30px; margin-top:20px; border-radius: 5px">

                <br>
                <h4>Informações do Projeto</h4>
                <div class="input-field col s12 m6">
                    <input id="sitename" name="sitename" type="text" class="validate">
                    <label for="sitename">Nome do Projeto</label>
                </div>
                <div class="input-field col s12 m6">
                    <input id="sitedesc" name="sitedesc" type="text" class="validate">
                    <label for="sitedesc">Projeto Descrição</label>
                </div>

                <div class="file-field input-field col s12 m6">
                    <div class="btn">
                        <span>Logo</span>
                        <input type="file" name="logo"  ccept="image/*">
                    </div>
                    <div class="file-path-wrapper">
                        <input class="file-path validate" type="text">
                    </div>
                </div>

                <div class="file-field input-field col s12 m6">
                    <div class="btn">
                        <span>Favicon</span>
                        <input type="file" name="favicon" accept="image/*">
                    </div>
                    <div class="file-path-wrapper">
                        <input class="file-path validate" type="text">
                    </div>
                </div>

                <div class="row clearfix">
                    <br>
                    <div class="switch col s6 m4">
                        <label>
                            HTTP
                            <input type="checkbox" name="protocol">
                            <span class="lever"></span>
                            HTTPS
                        </label>
                    </div>
                    <div class="switch col s6 m4">
                        <label>
                            sem WWW
                            <input type="checkbox" name="www">
                            <span class="lever"></span>
                            com WWW
                        </label>
                    </div>
                </div>


                <div class="row clearfix">
                    <br>
                    <h4>Conexão ao Banco</h4>

                    <div class="input-field col s12 m6">
                        <input id="user" name="user" type="text" class="validate">
                        <label for="user">Usuário</label>
                    </div>
                    <div class="input-field col s12 m6">
                        <input id="pass" name="pass" type="text" class="validate">
                        <label for="pass">Senha</label>
                    </div>

                    <div class="input-field col s12 m6">
                        <input id="database" name="database" type="text" class="validate" value="<?=$table?>">
                        <label for="database">Nome do Banco</label>
                    </div>

                    <div class="input-field col s12 m6">
                        <input id="host" name="host" value="localhost" type="text" class="validate">
                        <label for="host">Host</label>
                    </div>

                    <div class="input-field col s12 m6">
                        <input id="pre" name="pre" type="text" class="validate" value="<?=$pre?>">
                        <label for="pre">Prefixo das Tabelas</label>
                    </div>
                </div>

                <div class="row clearfix">
                    <br>
                    <h4>Email Mailgun Config</h4>
                    <p><a href="https://www.mailgun.com/" target="_blank">link para mailgun</a></p>
                    <div class="clearfix"><br></div>

                    <div class="input-field col s12">
                        <input id="email" name="email" type="email" class="validate" value="contato@buscaphone.com">
                        <label for="email">Email</label>
                    </div>
                    <div class="input-field col s12 m6">
                        <input id="mailgunkey" name="mailgunkey" type="text" class="validate" value="key-0786754334d08fedfd9317e7b2298359">
                        <label for="mailgunkey">Key</label>
                    </div>

                    <div class="input-field col s12 m6">
                        <input id="mailgundomain" name="mailgundomain" type="text" class="validate" value="buscaphone.com">
                        <label for="mailgundomain">Domain</label>
                    </div>
                </div>

                <div class="row clearfix">
                    <br>
                    <h4>Recaptcha Google Config</h4>
                    <p><a href="https://www.google.com/recaptcha/admin" target="_blank">link para recaptcha</a></p>
                    <div class="clearfix"><br></div>

                    <div class="input-field col s12 m6">
                        <input id="recaptcha" name="recaptcha" type="text" class="validate">
                        <label for="recaptcha">Recaptcha</label>
                    </div>
                    <div class="input-field col s12 m6">
                        <input id="recaptchasite" name="recaptchasite" type="text" class="validate">
                        <label for="recaptchasite">Recaptcha Site</label>
                    </div>

                </div>

                <button type="submit" class="waves-effect waves-light btn">Criar Projeto</button>

            </form>
        </div>
    </div>

    <style>
        html {
            background: #EEE
        }

        .materialize-red {
            background-color: #e51c23 !important
        }

        .materialize-red-text {
            color: #e51c23 !important
        }

        .materialize-red.lighten-5 {
            background-color: #fdeaeb !important
        }

        .materialize-red-text.text-lighten-5 {
            color: #fdeaeb !important
        }

        .materialize-red.lighten-4 {
            background-color: #f8c1c3 !important
        }

        .materialize-red-text.text-lighten-4 {
            color: #f8c1c3 !important
        }

        .materialize-red.lighten-3 {
            background-color: #f3989b !important
        }

        .materialize-red-text.text-lighten-3 {
            color: #f3989b !important
        }

        .materialize-red.lighten-2 {
            background-color: #ee6e73 !important
        }

        .materialize-red-text.text-lighten-2 {
            color: #ee6e73 !important
        }

        .materialize-red.lighten-1 {
            background-color: #ea454b !important
        }

        .materialize-red-text.text-lighten-1 {
            color: #ea454b !important
        }

        .materialize-red.darken-1 {
            background-color: #d0181e !important
        }

        .materialize-red-text.text-darken-1 {
            color: #d0181e !important
        }

        .materialize-red.darken-2 {
            background-color: #b9151b !important
        }

        .materialize-red-text.text-darken-2 {
            color: #b9151b !important
        }

        .materialize-red.darken-3 {
            background-color: #a21318 !important
        }

        .materialize-red-text.text-darken-3 {
            color: #a21318 !important
        }

        .materialize-red.darken-4 {
            background-color: #8b1014 !important
        }

        .materialize-red-text.text-darken-4 {
            color: #8b1014 !important
        }

        .red {
            background-color: #F44336 !important
        }

        .red-text {
            color: #F44336 !important
        }

        .red.lighten-5 {
            background-color: #FFEBEE !important
        }

        .red-text.text-lighten-5 {
            color: #FFEBEE !important
        }

        .red.lighten-4 {
            background-color: #FFCDD2 !important
        }

        .red-text.text-lighten-4 {
            color: #FFCDD2 !important
        }

        .red.lighten-3 {
            background-color: #EF9A9A !important
        }

        .red-text.text-lighten-3 {
            color: #EF9A9A !important
        }

        .red.lighten-2 {
            background-color: #E57373 !important
        }

        .red-text.text-lighten-2 {
            color: #E57373 !important
        }

        .red.lighten-1 {
            background-color: #EF5350 !important
        }

        .red-text.text-lighten-1 {
            color: #EF5350 !important
        }

        .red.darken-1 {
            background-color: #E53935 !important
        }

        .red-text.text-darken-1 {
            color: #E53935 !important
        }

        .red.darken-2 {
            background-color: #D32F2F !important
        }

        .red-text.text-darken-2 {
            color: #D32F2F !important
        }

        .red.darken-3 {
            background-color: #C62828 !important
        }

        .red-text.text-darken-3 {
            color: #C62828 !important
        }

        .red.darken-4 {
            background-color: #B71C1C !important
        }

        .red-text.text-darken-4 {
            color: #B71C1C !important
        }

        .red.accent-1 {
            background-color: #FF8A80 !important
        }

        .red-text.text-accent-1 {
            color: #FF8A80 !important
        }

        .red.accent-2 {
            background-color: #FF5252 !important
        }

        .red-text.text-accent-2 {
            color: #FF5252 !important
        }

        .red.accent-3 {
            background-color: #FF1744 !important
        }

        .red-text.text-accent-3 {
            color: #FF1744 !important
        }

        .red.accent-4 {
            background-color: #D50000 !important
        }

        .red-text.text-accent-4 {
            color: #D50000 !important
        }

        .pink {
            background-color: #e91e63 !important
        }

        .pink-text {
            color: #e91e63 !important
        }

        .pink.lighten-5 {
            background-color: #fce4ec !important
        }

        .pink-text.text-lighten-5 {
            color: #fce4ec !important
        }

        .pink.lighten-4 {
            background-color: #f8bbd0 !important
        }

        .pink-text.text-lighten-4 {
            color: #f8bbd0 !important
        }

        .pink.lighten-3 {
            background-color: #f48fb1 !important
        }

        .pink-text.text-lighten-3 {
            color: #f48fb1 !important
        }

        .pink.lighten-2 {
            background-color: #f06292 !important
        }

        .pink-text.text-lighten-2 {
            color: #f06292 !important
        }

        .pink.lighten-1 {
            background-color: #ec407a !important
        }

        .pink-text.text-lighten-1 {
            color: #ec407a !important
        }

        .pink.darken-1 {
            background-color: #d81b60 !important
        }

        .pink-text.text-darken-1 {
            color: #d81b60 !important
        }

        .pink.darken-2 {
            background-color: #c2185b !important
        }

        .pink-text.text-darken-2 {
            color: #c2185b !important
        }

        .pink.darken-3 {
            background-color: #ad1457 !important
        }

        .pink-text.text-darken-3 {
            color: #ad1457 !important
        }

        .pink.darken-4 {
            background-color: #880e4f !important
        }

        .pink-text.text-darken-4 {
            color: #880e4f !important
        }

        .pink.accent-1 {
            background-color: #ff80ab !important
        }

        .pink-text.text-accent-1 {
            color: #ff80ab !important
        }

        .pink.accent-2 {
            background-color: #ff4081 !important
        }

        .pink-text.text-accent-2 {
            color: #ff4081 !important
        }

        .pink.accent-3 {
            background-color: #f50057 !important
        }

        .pink-text.text-accent-3 {
            color: #f50057 !important
        }

        .pink.accent-4 {
            background-color: #c51162 !important
        }

        .pink-text.text-accent-4 {
            color: #c51162 !important
        }

        .purple {
            background-color: #9c27b0 !important
        }

        .purple-text {
            color: #9c27b0 !important
        }

        .purple.lighten-5 {
            background-color: #f3e5f5 !important
        }

        .purple-text.text-lighten-5 {
            color: #f3e5f5 !important
        }

        .purple.lighten-4 {
            background-color: #e1bee7 !important
        }

        .purple-text.text-lighten-4 {
            color: #e1bee7 !important
        }

        .purple.lighten-3 {
            background-color: #ce93d8 !important
        }

        .purple-text.text-lighten-3 {
            color: #ce93d8 !important
        }

        .purple.lighten-2 {
            background-color: #ba68c8 !important
        }

        .purple-text.text-lighten-2 {
            color: #ba68c8 !important
        }

        .purple.lighten-1 {
            background-color: #ab47bc !important
        }

        .purple-text.text-lighten-1 {
            color: #ab47bc !important
        }

        .purple.darken-1 {
            background-color: #8e24aa !important
        }

        .purple-text.text-darken-1 {
            color: #8e24aa !important
        }

        .purple.darken-2 {
            background-color: #7b1fa2 !important
        }

        .purple-text.text-darken-2 {
            color: #7b1fa2 !important
        }

        .purple.darken-3 {
            background-color: #6a1b9a !important
        }

        .purple-text.text-darken-3 {
            color: #6a1b9a !important
        }

        .purple.darken-4 {
            background-color: #4a148c !important
        }

        .purple-text.text-darken-4 {
            color: #4a148c !important
        }

        .purple.accent-1 {
            background-color: #ea80fc !important
        }

        .purple-text.text-accent-1 {
            color: #ea80fc !important
        }

        .purple.accent-2 {
            background-color: #e040fb !important
        }

        .purple-text.text-accent-2 {
            color: #e040fb !important
        }

        .purple.accent-3 {
            background-color: #d500f9 !important
        }

        .purple-text.text-accent-3 {
            color: #d500f9 !important
        }

        .purple.accent-4 {
            background-color: #a0f !important
        }

        .purple-text.text-accent-4 {
            color: #a0f !important
        }

        .deep-purple {
            background-color: #673ab7 !important
        }

        .deep-purple-text {
            color: #673ab7 !important
        }

        .deep-purple.lighten-5 {
            background-color: #ede7f6 !important
        }

        .deep-purple-text.text-lighten-5 {
            color: #ede7f6 !important
        }

        .deep-purple.lighten-4 {
            background-color: #d1c4e9 !important
        }

        .deep-purple-text.text-lighten-4 {
            color: #d1c4e9 !important
        }

        .deep-purple.lighten-3 {
            background-color: #b39ddb !important
        }

        .deep-purple-text.text-lighten-3 {
            color: #b39ddb !important
        }

        .deep-purple.lighten-2 {
            background-color: #9575cd !important
        }

        .deep-purple-text.text-lighten-2 {
            color: #9575cd !important
        }

        .deep-purple.lighten-1 {
            background-color: #7e57c2 !important
        }

        .deep-purple-text.text-lighten-1 {
            color: #7e57c2 !important
        }

        .deep-purple.darken-1 {
            background-color: #5e35b1 !important
        }

        .deep-purple-text.text-darken-1 {
            color: #5e35b1 !important
        }

        .deep-purple.darken-2 {
            background-color: #512da8 !important
        }

        .deep-purple-text.text-darken-2 {
            color: #512da8 !important
        }

        .deep-purple.darken-3 {
            background-color: #4527a0 !important
        }

        .deep-purple-text.text-darken-3 {
            color: #4527a0 !important
        }

        .deep-purple.darken-4 {
            background-color: #311b92 !important
        }

        .deep-purple-text.text-darken-4 {
            color: #311b92 !important
        }

        .deep-purple.accent-1 {
            background-color: #b388ff !important
        }

        .deep-purple-text.text-accent-1 {
            color: #b388ff !important
        }

        .deep-purple.accent-2 {
            background-color: #7c4dff !important
        }

        .deep-purple-text.text-accent-2 {
            color: #7c4dff !important
        }

        .deep-purple.accent-3 {
            background-color: #651fff !important
        }

        .deep-purple-text.text-accent-3 {
            color: #651fff !important
        }

        .deep-purple.accent-4 {
            background-color: #6200ea !important
        }

        .deep-purple-text.text-accent-4 {
            color: #6200ea !important
        }

        .indigo {
            background-color: #3f51b5 !important
        }

        .indigo-text {
            color: #3f51b5 !important
        }

        .indigo.lighten-5 {
            background-color: #e8eaf6 !important
        }

        .indigo-text.text-lighten-5 {
            color: #e8eaf6 !important
        }

        .indigo.lighten-4 {
            background-color: #c5cae9 !important
        }

        .indigo-text.text-lighten-4 {
            color: #c5cae9 !important
        }

        .indigo.lighten-3 {
            background-color: #9fa8da !important
        }

        .indigo-text.text-lighten-3 {
            color: #9fa8da !important
        }

        .indigo.lighten-2 {
            background-color: #7986cb !important
        }

        .indigo-text.text-lighten-2 {
            color: #7986cb !important
        }

        .indigo.lighten-1 {
            background-color: #5c6bc0 !important
        }

        .indigo-text.text-lighten-1 {
            color: #5c6bc0 !important
        }

        .indigo.darken-1 {
            background-color: #3949ab !important
        }

        .indigo-text.text-darken-1 {
            color: #3949ab !important
        }

        .indigo.darken-2 {
            background-color: #303f9f !important
        }

        .indigo-text.text-darken-2 {
            color: #303f9f !important
        }

        .indigo.darken-3 {
            background-color: #283593 !important
        }

        .indigo-text.text-darken-3 {
            color: #283593 !important
        }

        .indigo.darken-4 {
            background-color: #1a237e !important
        }

        .indigo-text.text-darken-4 {
            color: #1a237e !important
        }

        .indigo.accent-1 {
            background-color: #8c9eff !important
        }

        .indigo-text.text-accent-1 {
            color: #8c9eff !important
        }

        .indigo.accent-2 {
            background-color: #536dfe !important
        }

        .indigo-text.text-accent-2 {
            color: #536dfe !important
        }

        .indigo.accent-3 {
            background-color: #3d5afe !important
        }

        .indigo-text.text-accent-3 {
            color: #3d5afe !important
        }

        .indigo.accent-4 {
            background-color: #304ffe !important
        }

        .indigo-text.text-accent-4 {
            color: #304ffe !important
        }

        .blue {
            background-color: #2196F3 !important
        }

        .blue-text {
            color: #2196F3 !important
        }

        .blue.lighten-5 {
            background-color: #E3F2FD !important
        }

        .blue-text.text-lighten-5 {
            color: #E3F2FD !important
        }

        .blue.lighten-4 {
            background-color: #BBDEFB !important
        }

        .blue-text.text-lighten-4 {
            color: #BBDEFB !important
        }

        .blue.lighten-3 {
            background-color: #90CAF9 !important
        }

        .blue-text.text-lighten-3 {
            color: #90CAF9 !important
        }

        .blue.lighten-2 {
            background-color: #64B5F6 !important
        }

        .blue-text.text-lighten-2 {
            color: #64B5F6 !important
        }

        .blue.lighten-1 {
            background-color: #42A5F5 !important
        }

        .blue-text.text-lighten-1 {
            color: #42A5F5 !important
        }

        .blue.darken-1 {
            background-color: #1E88E5 !important
        }

        .blue-text.text-darken-1 {
            color: #1E88E5 !important
        }

        .blue.darken-2 {
            background-color: #1976D2 !important
        }

        .blue-text.text-darken-2 {
            color: #1976D2 !important
        }

        .blue.darken-3 {
            background-color: #1565C0 !important
        }

        .blue-text.text-darken-3 {
            color: #1565C0 !important
        }

        .blue.darken-4 {
            background-color: #0D47A1 !important
        }

        .blue-text.text-darken-4 {
            color: #0D47A1 !important
        }

        .blue.accent-1 {
            background-color: #82B1FF !important
        }

        .blue-text.text-accent-1 {
            color: #82B1FF !important
        }

        .blue.accent-2 {
            background-color: #448AFF !important
        }

        .blue-text.text-accent-2 {
            color: #448AFF !important
        }

        .blue.accent-3 {
            background-color: #2979FF !important
        }

        .blue-text.text-accent-3 {
            color: #2979FF !important
        }

        .blue.accent-4 {
            background-color: #2962FF !important
        }

        .blue-text.text-accent-4 {
            color: #2962FF !important
        }

        .light-blue {
            background-color: #03a9f4 !important
        }

        .light-blue-text {
            color: #03a9f4 !important
        }

        .light-blue.lighten-5 {
            background-color: #e1f5fe !important
        }

        .light-blue-text.text-lighten-5 {
            color: #e1f5fe !important
        }

        .light-blue.lighten-4 {
            background-color: #b3e5fc !important
        }

        .light-blue-text.text-lighten-4 {
            color: #b3e5fc !important
        }

        .light-blue.lighten-3 {
            background-color: #81d4fa !important
        }

        .light-blue-text.text-lighten-3 {
            color: #81d4fa !important
        }

        .light-blue.lighten-2 {
            background-color: #4fc3f7 !important
        }

        .light-blue-text.text-lighten-2 {
            color: #4fc3f7 !important
        }

        .light-blue.lighten-1 {
            background-color: #29b6f6 !important
        }

        .light-blue-text.text-lighten-1 {
            color: #29b6f6 !important
        }

        .light-blue.darken-1 {
            background-color: #039be5 !important
        }

        .light-blue-text.text-darken-1 {
            color: #039be5 !important
        }

        .light-blue.darken-2 {
            background-color: #0288d1 !important
        }

        .light-blue-text.text-darken-2 {
            color: #0288d1 !important
        }

        .light-blue.darken-3 {
            background-color: #0277bd !important
        }

        .light-blue-text.text-darken-3 {
            color: #0277bd !important
        }

        .light-blue.darken-4 {
            background-color: #01579b !important
        }

        .light-blue-text.text-darken-4 {
            color: #01579b !important
        }

        .light-blue.accent-1 {
            background-color: #80d8ff !important
        }

        .light-blue-text.text-accent-1 {
            color: #80d8ff !important
        }

        .light-blue.accent-2 {
            background-color: #40c4ff !important
        }

        .light-blue-text.text-accent-2 {
            color: #40c4ff !important
        }

        .light-blue.accent-3 {
            background-color: #00b0ff !important
        }

        .light-blue-text.text-accent-3 {
            color: #00b0ff !important
        }

        .light-blue.accent-4 {
            background-color: #0091ea !important
        }

        .light-blue-text.text-accent-4 {
            color: #0091ea !important
        }

        .cyan {
            background-color: #00bcd4 !important
        }

        .cyan-text {
            color: #00bcd4 !important
        }

        .cyan.lighten-5 {
            background-color: #e0f7fa !important
        }

        .cyan-text.text-lighten-5 {
            color: #e0f7fa !important
        }

        .cyan.lighten-4 {
            background-color: #b2ebf2 !important
        }

        .cyan-text.text-lighten-4 {
            color: #b2ebf2 !important
        }

        .cyan.lighten-3 {
            background-color: #80deea !important
        }

        .cyan-text.text-lighten-3 {
            color: #80deea !important
        }

        .cyan.lighten-2 {
            background-color: #4dd0e1 !important
        }

        .cyan-text.text-lighten-2 {
            color: #4dd0e1 !important
        }

        .cyan.lighten-1 {
            background-color: #26c6da !important
        }

        .cyan-text.text-lighten-1 {
            color: #26c6da !important
        }

        .cyan.darken-1 {
            background-color: #00acc1 !important
        }

        .cyan-text.text-darken-1 {
            color: #00acc1 !important
        }

        .cyan.darken-2 {
            background-color: #0097a7 !important
        }

        .cyan-text.text-darken-2 {
            color: #0097a7 !important
        }

        .cyan.darken-3 {
            background-color: #00838f !important
        }

        .cyan-text.text-darken-3 {
            color: #00838f !important
        }

        .cyan.darken-4 {
            background-color: #006064 !important
        }

        .cyan-text.text-darken-4 {
            color: #006064 !important
        }

        .cyan.accent-1 {
            background-color: #84ffff !important
        }

        .cyan-text.text-accent-1 {
            color: #84ffff !important
        }

        .cyan.accent-2 {
            background-color: #18ffff !important
        }

        .cyan-text.text-accent-2 {
            color: #18ffff !important
        }

        .cyan.accent-3 {
            background-color: #00e5ff !important
        }

        .cyan-text.text-accent-3 {
            color: #00e5ff !important
        }

        .cyan.accent-4 {
            background-color: #00b8d4 !important
        }

        .cyan-text.text-accent-4 {
            color: #00b8d4 !important
        }

        .teal {
            background-color: #009688 !important
        }

        .teal-text {
            color: #009688 !important
        }

        .teal.lighten-5 {
            background-color: #e0f2f1 !important
        }

        .teal-text.text-lighten-5 {
            color: #e0f2f1 !important
        }

        .teal.lighten-4 {
            background-color: #b2dfdb !important
        }

        .teal-text.text-lighten-4 {
            color: #b2dfdb !important
        }

        .teal.lighten-3 {
            background-color: #80cbc4 !important
        }

        .teal-text.text-lighten-3 {
            color: #80cbc4 !important
        }

        .teal.lighten-2 {
            background-color: #4db6ac !important
        }

        .teal-text.text-lighten-2 {
            color: #4db6ac !important
        }

        .teal.lighten-1 {
            background-color: #26a69a !important
        }

        .teal-text.text-lighten-1 {
            color: #26a69a !important
        }

        .teal.darken-1 {
            background-color: #00897b !important
        }

        .teal-text.text-darken-1 {
            color: #00897b !important
        }

        .teal.darken-2 {
            background-color: #00796b !important
        }

        .teal-text.text-darken-2 {
            color: #00796b !important
        }

        .teal.darken-3 {
            background-color: #00695c !important
        }

        .teal-text.text-darken-3 {
            color: #00695c !important
        }

        .teal.darken-4 {
            background-color: #004d40 !important
        }

        .teal-text.text-darken-4 {
            color: #004d40 !important
        }

        .teal.accent-1 {
            background-color: #a7ffeb !important
        }

        .teal-text.text-accent-1 {
            color: #a7ffeb !important
        }

        .teal.accent-2 {
            background-color: #64ffda !important
        }

        .teal-text.text-accent-2 {
            color: #64ffda !important
        }

        .teal.accent-3 {
            background-color: #1de9b6 !important
        }

        .teal-text.text-accent-3 {
            color: #1de9b6 !important
        }

        .teal.accent-4 {
            background-color: #00bfa5 !important
        }

        .teal-text.text-accent-4 {
            color: #00bfa5 !important
        }

        .green {
            background-color: #4CAF50 !important
        }

        .green-text {
            color: #4CAF50 !important
        }

        .green.lighten-5 {
            background-color: #E8F5E9 !important
        }

        .green-text.text-lighten-5 {
            color: #E8F5E9 !important
        }

        .green.lighten-4 {
            background-color: #C8E6C9 !important
        }

        .green-text.text-lighten-4 {
            color: #C8E6C9 !important
        }

        .green.lighten-3 {
            background-color: #A5D6A7 !important
        }

        .green-text.text-lighten-3 {
            color: #A5D6A7 !important
        }

        .green.lighten-2 {
            background-color: #81C784 !important
        }

        .green-text.text-lighten-2 {
            color: #81C784 !important
        }

        .green.lighten-1 {
            background-color: #66BB6A !important
        }

        .green-text.text-lighten-1 {
            color: #66BB6A !important
        }

        .green.darken-1 {
            background-color: #43A047 !important
        }

        .green-text.text-darken-1 {
            color: #43A047 !important
        }

        .green.darken-2 {
            background-color: #388E3C !important
        }

        .green-text.text-darken-2 {
            color: #388E3C !important
        }

        .green.darken-3 {
            background-color: #2E7D32 !important
        }

        .green-text.text-darken-3 {
            color: #2E7D32 !important
        }

        .green.darken-4 {
            background-color: #1B5E20 !important
        }

        .green-text.text-darken-4 {
            color: #1B5E20 !important
        }

        .green.accent-1 {
            background-color: #B9F6CA !important
        }

        .green-text.text-accent-1 {
            color: #B9F6CA !important
        }

        .green.accent-2 {
            background-color: #69F0AE !important
        }

        .green-text.text-accent-2 {
            color: #69F0AE !important
        }

        .green.accent-3 {
            background-color: #00E676 !important
        }

        .green-text.text-accent-3 {
            color: #00E676 !important
        }

        .green.accent-4 {
            background-color: #00C853 !important
        }

        .green-text.text-accent-4 {
            color: #00C853 !important
        }

        .light-green {
            background-color: #8bc34a !important
        }

        .light-green-text {
            color: #8bc34a !important
        }

        .light-green.lighten-5 {
            background-color: #f1f8e9 !important
        }

        .light-green-text.text-lighten-5 {
            color: #f1f8e9 !important
        }

        .light-green.lighten-4 {
            background-color: #dcedc8 !important
        }

        .light-green-text.text-lighten-4 {
            color: #dcedc8 !important
        }

        .light-green.lighten-3 {
            background-color: #c5e1a5 !important
        }

        .light-green-text.text-lighten-3 {
            color: #c5e1a5 !important
        }

        .light-green.lighten-2 {
            background-color: #aed581 !important
        }

        .light-green-text.text-lighten-2 {
            color: #aed581 !important
        }

        .light-green.lighten-1 {
            background-color: #9ccc65 !important
        }

        .light-green-text.text-lighten-1 {
            color: #9ccc65 !important
        }

        .light-green.darken-1 {
            background-color: #7cb342 !important
        }

        .light-green-text.text-darken-1 {
            color: #7cb342 !important
        }

        .light-green.darken-2 {
            background-color: #689f38 !important
        }

        .light-green-text.text-darken-2 {
            color: #689f38 !important
        }

        .light-green.darken-3 {
            background-color: #558b2f !important
        }

        .light-green-text.text-darken-3 {
            color: #558b2f !important
        }

        .light-green.darken-4 {
            background-color: #33691e !important
        }

        .light-green-text.text-darken-4 {
            color: #33691e !important
        }

        .light-green.accent-1 {
            background-color: #ccff90 !important
        }

        .light-green-text.text-accent-1 {
            color: #ccff90 !important
        }

        .light-green.accent-2 {
            background-color: #b2ff59 !important
        }

        .light-green-text.text-accent-2 {
            color: #b2ff59 !important
        }

        .light-green.accent-3 {
            background-color: #76ff03 !important
        }

        .light-green-text.text-accent-3 {
            color: #76ff03 !important
        }

        .light-green.accent-4 {
            background-color: #64dd17 !important
        }

        .light-green-text.text-accent-4 {
            color: #64dd17 !important
        }

        .lime {
            background-color: #cddc39 !important
        }

        .lime-text {
            color: #cddc39 !important
        }

        .lime.lighten-5 {
            background-color: #f9fbe7 !important
        }

        .lime-text.text-lighten-5 {
            color: #f9fbe7 !important
        }

        .lime.lighten-4 {
            background-color: #f0f4c3 !important
        }

        .lime-text.text-lighten-4 {
            color: #f0f4c3 !important
        }

        .lime.lighten-3 {
            background-color: #e6ee9c !important
        }

        .lime-text.text-lighten-3 {
            color: #e6ee9c !important
        }

        .lime.lighten-2 {
            background-color: #dce775 !important
        }

        .lime-text.text-lighten-2 {
            color: #dce775 !important
        }

        .lime.lighten-1 {
            background-color: #d4e157 !important
        }

        .lime-text.text-lighten-1 {
            color: #d4e157 !important
        }

        .lime.darken-1 {
            background-color: #c0ca33 !important
        }

        .lime-text.text-darken-1 {
            color: #c0ca33 !important
        }

        .lime.darken-2 {
            background-color: #afb42b !important
        }

        .lime-text.text-darken-2 {
            color: #afb42b !important
        }

        .lime.darken-3 {
            background-color: #9e9d24 !important
        }

        .lime-text.text-darken-3 {
            color: #9e9d24 !important
        }

        .lime.darken-4 {
            background-color: #827717 !important
        }

        .lime-text.text-darken-4 {
            color: #827717 !important
        }

        .lime.accent-1 {
            background-color: #f4ff81 !important
        }

        .lime-text.text-accent-1 {
            color: #f4ff81 !important
        }

        .lime.accent-2 {
            background-color: #eeff41 !important
        }

        .lime-text.text-accent-2 {
            color: #eeff41 !important
        }

        .lime.accent-3 {
            background-color: #c6ff00 !important
        }

        .lime-text.text-accent-3 {
            color: #c6ff00 !important
        }

        .lime.accent-4 {
            background-color: #aeea00 !important
        }

        .lime-text.text-accent-4 {
            color: #aeea00 !important
        }

        .yellow {
            background-color: #ffeb3b !important
        }

        .yellow-text {
            color: #ffeb3b !important
        }

        .yellow.lighten-5 {
            background-color: #fffde7 !important
        }

        .yellow-text.text-lighten-5 {
            color: #fffde7 !important
        }

        .yellow.lighten-4 {
            background-color: #fff9c4 !important
        }

        .yellow-text.text-lighten-4 {
            color: #fff9c4 !important
        }

        .yellow.lighten-3 {
            background-color: #fff59d !important
        }

        .yellow-text.text-lighten-3 {
            color: #fff59d !important
        }

        .yellow.lighten-2 {
            background-color: #fff176 !important
        }

        .yellow-text.text-lighten-2 {
            color: #fff176 !important
        }

        .yellow.lighten-1 {
            background-color: #ffee58 !important
        }

        .yellow-text.text-lighten-1 {
            color: #ffee58 !important
        }

        .yellow.darken-1 {
            background-color: #fdd835 !important
        }

        .yellow-text.text-darken-1 {
            color: #fdd835 !important
        }

        .yellow.darken-2 {
            background-color: #fbc02d !important
        }

        .yellow-text.text-darken-2 {
            color: #fbc02d !important
        }

        .yellow.darken-3 {
            background-color: #f9a825 !important
        }

        .yellow-text.text-darken-3 {
            color: #f9a825 !important
        }

        .yellow.darken-4 {
            background-color: #f57f17 !important
        }

        .yellow-text.text-darken-4 {
            color: #f57f17 !important
        }

        .yellow.accent-1 {
            background-color: #ffff8d !important
        }

        .yellow-text.text-accent-1 {
            color: #ffff8d !important
        }

        .yellow.accent-2 {
            background-color: #ff0 !important
        }

        .yellow-text.text-accent-2 {
            color: #ff0 !important
        }

        .yellow.accent-3 {
            background-color: #ffea00 !important
        }

        .yellow-text.text-accent-3 {
            color: #ffea00 !important
        }

        .yellow.accent-4 {
            background-color: #ffd600 !important
        }

        .yellow-text.text-accent-4 {
            color: #ffd600 !important
        }

        .amber {
            background-color: #ffc107 !important
        }

        .amber-text {
            color: #ffc107 !important
        }

        .amber.lighten-5 {
            background-color: #fff8e1 !important
        }

        .amber-text.text-lighten-5 {
            color: #fff8e1 !important
        }

        .amber.lighten-4 {
            background-color: #ffecb3 !important
        }

        .amber-text.text-lighten-4 {
            color: #ffecb3 !important
        }

        .amber.lighten-3 {
            background-color: #ffe082 !important
        }

        .amber-text.text-lighten-3 {
            color: #ffe082 !important
        }

        .amber.lighten-2 {
            background-color: #ffd54f !important
        }

        .amber-text.text-lighten-2 {
            color: #ffd54f !important
        }

        .amber.lighten-1 {
            background-color: #ffca28 !important
        }

        .amber-text.text-lighten-1 {
            color: #ffca28 !important
        }

        .amber.darken-1 {
            background-color: #ffb300 !important
        }

        .amber-text.text-darken-1 {
            color: #ffb300 !important
        }

        .amber.darken-2 {
            background-color: #ffa000 !important
        }

        .amber-text.text-darken-2 {
            color: #ffa000 !important
        }

        .amber.darken-3 {
            background-color: #ff8f00 !important
        }

        .amber-text.text-darken-3 {
            color: #ff8f00 !important
        }

        .amber.darken-4 {
            background-color: #ff6f00 !important
        }

        .amber-text.text-darken-4 {
            color: #ff6f00 !important
        }

        .amber.accent-1 {
            background-color: #ffe57f !important
        }

        .amber-text.text-accent-1 {
            color: #ffe57f !important
        }

        .amber.accent-2 {
            background-color: #ffd740 !important
        }

        .amber-text.text-accent-2 {
            color: #ffd740 !important
        }

        .amber.accent-3 {
            background-color: #ffc400 !important
        }

        .amber-text.text-accent-3 {
            color: #ffc400 !important
        }

        .amber.accent-4 {
            background-color: #ffab00 !important
        }

        .amber-text.text-accent-4 {
            color: #ffab00 !important
        }

        .orange {
            background-color: #ff9800 !important
        }

        .orange-text {
            color: #ff9800 !important
        }

        .orange.lighten-5 {
            background-color: #fff3e0 !important
        }

        .orange-text.text-lighten-5 {
            color: #fff3e0 !important
        }

        .orange.lighten-4 {
            background-color: #ffe0b2 !important
        }

        .orange-text.text-lighten-4 {
            color: #ffe0b2 !important
        }

        .orange.lighten-3 {
            background-color: #ffcc80 !important
        }

        .orange-text.text-lighten-3 {
            color: #ffcc80 !important
        }

        .orange.lighten-2 {
            background-color: #ffb74d !important
        }

        .orange-text.text-lighten-2 {
            color: #ffb74d !important
        }

        .orange.lighten-1 {
            background-color: #ffa726 !important
        }

        .orange-text.text-lighten-1 {
            color: #ffa726 !important
        }

        .orange.darken-1 {
            background-color: #fb8c00 !important
        }

        .orange-text.text-darken-1 {
            color: #fb8c00 !important
        }

        .orange.darken-2 {
            background-color: #f57c00 !important
        }

        .orange-text.text-darken-2 {
            color: #f57c00 !important
        }

        .orange.darken-3 {
            background-color: #ef6c00 !important
        }

        .orange-text.text-darken-3 {
            color: #ef6c00 !important
        }

        .orange.darken-4 {
            background-color: #e65100 !important
        }

        .orange-text.text-darken-4 {
            color: #e65100 !important
        }

        .orange.accent-1 {
            background-color: #ffd180 !important
        }

        .orange-text.text-accent-1 {
            color: #ffd180 !important
        }

        .orange.accent-2 {
            background-color: #ffab40 !important
        }

        .orange-text.text-accent-2 {
            color: #ffab40 !important
        }

        .orange.accent-3 {
            background-color: #ff9100 !important
        }

        .orange-text.text-accent-3 {
            color: #ff9100 !important
        }

        .orange.accent-4 {
            background-color: #ff6d00 !important
        }

        .orange-text.text-accent-4 {
            color: #ff6d00 !important
        }

        .deep-orange {
            background-color: #ff5722 !important
        }

        .deep-orange-text {
            color: #ff5722 !important
        }

        .deep-orange.lighten-5 {
            background-color: #fbe9e7 !important
        }

        .deep-orange-text.text-lighten-5 {
            color: #fbe9e7 !important
        }

        .deep-orange.lighten-4 {
            background-color: #ffccbc !important
        }

        .deep-orange-text.text-lighten-4 {
            color: #ffccbc !important
        }

        .deep-orange.lighten-3 {
            background-color: #ffab91 !important
        }

        .deep-orange-text.text-lighten-3 {
            color: #ffab91 !important
        }

        .deep-orange.lighten-2 {
            background-color: #ff8a65 !important
        }

        .deep-orange-text.text-lighten-2 {
            color: #ff8a65 !important
        }

        .deep-orange.lighten-1 {
            background-color: #ff7043 !important
        }

        .deep-orange-text.text-lighten-1 {
            color: #ff7043 !important
        }

        .deep-orange.darken-1 {
            background-color: #f4511e !important
        }

        .deep-orange-text.text-darken-1 {
            color: #f4511e !important
        }

        .deep-orange.darken-2 {
            background-color: #e64a19 !important
        }

        .deep-orange-text.text-darken-2 {
            color: #e64a19 !important
        }

        .deep-orange.darken-3 {
            background-color: #d84315 !important
        }

        .deep-orange-text.text-darken-3 {
            color: #d84315 !important
        }

        .deep-orange.darken-4 {
            background-color: #bf360c !important
        }

        .deep-orange-text.text-darken-4 {
            color: #bf360c !important
        }

        .deep-orange.accent-1 {
            background-color: #ff9e80 !important
        }

        .deep-orange-text.text-accent-1 {
            color: #ff9e80 !important
        }

        .deep-orange.accent-2 {
            background-color: #ff6e40 !important
        }

        .deep-orange-text.text-accent-2 {
            color: #ff6e40 !important
        }

        .deep-orange.accent-3 {
            background-color: #ff3d00 !important
        }

        .deep-orange-text.text-accent-3 {
            color: #ff3d00 !important
        }

        .deep-orange.accent-4 {
            background-color: #dd2c00 !important
        }

        .deep-orange-text.text-accent-4 {
            color: #dd2c00 !important
        }

        .brown {
            background-color: #795548 !important
        }

        .brown-text {
            color: #795548 !important
        }

        .brown.lighten-5 {
            background-color: #efebe9 !important
        }

        .brown-text.text-lighten-5 {
            color: #efebe9 !important
        }

        .brown.lighten-4 {
            background-color: #d7ccc8 !important
        }

        .brown-text.text-lighten-4 {
            color: #d7ccc8 !important
        }

        .brown.lighten-3 {
            background-color: #bcaaa4 !important
        }

        .brown-text.text-lighten-3 {
            color: #bcaaa4 !important
        }

        .brown.lighten-2 {
            background-color: #a1887f !important
        }

        .brown-text.text-lighten-2 {
            color: #a1887f !important
        }

        .brown.lighten-1 {
            background-color: #8d6e63 !important
        }

        .brown-text.text-lighten-1 {
            color: #8d6e63 !important
        }

        .brown.darken-1 {
            background-color: #6d4c41 !important
        }

        .brown-text.text-darken-1 {
            color: #6d4c41 !important
        }

        .brown.darken-2 {
            background-color: #5d4037 !important
        }

        .brown-text.text-darken-2 {
            color: #5d4037 !important
        }

        .brown.darken-3 {
            background-color: #4e342e !important
        }

        .brown-text.text-darken-3 {
            color: #4e342e !important
        }

        .brown.darken-4 {
            background-color: #3e2723 !important
        }

        .brown-text.text-darken-4 {
            color: #3e2723 !important
        }

        .blue-grey {
            background-color: #607d8b !important
        }

        .blue-grey-text {
            color: #607d8b !important
        }

        .blue-grey.lighten-5 {
            background-color: #eceff1 !important
        }

        .blue-grey-text.text-lighten-5 {
            color: #eceff1 !important
        }

        .blue-grey.lighten-4 {
            background-color: #cfd8dc !important
        }

        .blue-grey-text.text-lighten-4 {
            color: #cfd8dc !important
        }

        .blue-grey.lighten-3 {
            background-color: #b0bec5 !important
        }

        .blue-grey-text.text-lighten-3 {
            color: #b0bec5 !important
        }

        .blue-grey.lighten-2 {
            background-color: #90a4ae !important
        }

        .blue-grey-text.text-lighten-2 {
            color: #90a4ae !important
        }

        .blue-grey.lighten-1 {
            background-color: #78909c !important
        }

        .blue-grey-text.text-lighten-1 {
            color: #78909c !important
        }

        .blue-grey.darken-1 {
            background-color: #546e7a !important
        }

        .blue-grey-text.text-darken-1 {
            color: #546e7a !important
        }

        .blue-grey.darken-2 {
            background-color: #455a64 !important
        }

        .blue-grey-text.text-darken-2 {
            color: #455a64 !important
        }

        .blue-grey.darken-3 {
            background-color: #37474f !important
        }

        .blue-grey-text.text-darken-3 {
            color: #37474f !important
        }

        .blue-grey.darken-4 {
            background-color: #263238 !important
        }

        .blue-grey-text.text-darken-4 {
            color: #263238 !important
        }

        .grey {
            background-color: #9e9e9e !important
        }

        .grey-text {
            color: #9e9e9e !important
        }

        .grey.lighten-5 {
            background-color: #fafafa !important
        }

        .grey-text.text-lighten-5 {
            color: #fafafa !important
        }

        .grey.lighten-4 {
            background-color: #f5f5f5 !important
        }

        .grey-text.text-lighten-4 {
            color: #f5f5f5 !important
        }

        .grey.lighten-3 {
            background-color: #eee !important
        }

        .grey-text.text-lighten-3 {
            color: #eee !important
        }

        .grey.lighten-2 {
            background-color: #e0e0e0 !important
        }

        .grey-text.text-lighten-2 {
            color: #e0e0e0 !important
        }

        .grey.lighten-1 {
            background-color: #bdbdbd !important
        }

        .grey-text.text-lighten-1 {
            color: #bdbdbd !important
        }

        .grey.darken-1 {
            background-color: #757575 !important
        }

        .grey-text.text-darken-1 {
            color: #757575 !important
        }

        .grey.darken-2 {
            background-color: #616161 !important
        }

        .grey-text.text-darken-2 {
            color: #616161 !important
        }

        .grey.darken-3 {
            background-color: #424242 !important
        }

        .grey-text.text-darken-3 {
            color: #424242 !important
        }

        .grey.darken-4 {
            background-color: #212121 !important
        }

        .grey-text.text-darken-4 {
            color: #212121 !important
        }

        .black {
            background-color: #000 !important
        }

        .black-text {
            color: #000 !important
        }

        .white {
            background-color: #fff !important
        }

        .white-text {
            color: #fff !important
        }

        .transparent {
            background-color: transparent !important
        }

        .transparent-text {
            color: transparent !important
        }

        /*! normalize.css v3.0.3 | MIT License | github.com/necolas/normalize.css */
        html {
            font-family: sans-serif;
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%
        }

        body {
            margin: 0
        }

        article, aside, details, figcaption, figure, footer, header, hgroup, main, menu, nav, section, summary {
            display: block
        }

        audio, canvas, progress, video {
            display: inline-block;
            vertical-align: baseline
        }

        audio:not([controls]) {
            display: none;
            height: 0
        }

        [hidden], template {
            display: none
        }

        a {
            background-color: transparent
        }

        a:active, a:hover {
            outline: 0
        }

        abbr[title] {
            border-bottom: 1px dotted
        }

        b, strong {
            font-weight: bold
        }

        dfn {
            font-style: italic
        }

        h1 {
            font-size: 2em;
            margin: 0.67em 0
        }

        mark {
            background: #ff0;
            color: #000
        }

        small {
            font-size: 80%
        }

        sub, sup {
            font-size: 75%;
            line-height: 0;
            position: relative;
            vertical-align: baseline
        }

        sup {
            top: -0.5em
        }

        sub {
            bottom: -0.25em
        }

        img {
            border: 0
        }

        svg:not(:root) {
            overflow: hidden
        }

        figure {
            margin: 1em 40px
        }

        hr {
            -webkit-box-sizing: content-box;
            box-sizing: content-box;
            height: 0
        }

        pre {
            overflow: auto
        }

        code, kbd, pre, samp {
            font-family: monospace, monospace;
            font-size: 1em
        }

        button, input, optgroup, select, textarea {
            color: inherit;
            font: inherit;
            margin: 0
        }

        button {
            overflow: visible
        }

        button, select {
            text-transform: none
        }

        button, html input[type="button"], input[type="reset"], input[type="submit"] {
            -webkit-appearance: button;
            cursor: pointer
        }

        button[disabled], html input[disabled] {
            cursor: default
        }

        button::-moz-focus-inner, input::-moz-focus-inner {
            border: 0;
            padding: 0
        }

        input {
            line-height: normal
        }

        input[type="checkbox"], input[type="radio"] {
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
            padding: 0
        }

        input[type="number"]::-webkit-inner-spin-button, input[type="number"]::-webkit-outer-spin-button {
            height: auto
        }

        input[type="search"] {
            -webkit-appearance: textfield;
            -webkit-box-sizing: content-box;
            box-sizing: content-box
        }

        input[type="search"]::-webkit-search-cancel-button, input[type="search"]::-webkit-search-decoration {
            -webkit-appearance: none
        }

        fieldset {
            border: 1px solid #c0c0c0;
            margin: 0 2px;
            padding: 0.35em 0.625em 0.75em
        }

        legend {
            border: 0;
            padding: 0
        }

        textarea {
            overflow: auto
        }

        optgroup {
            font-weight: bold
        }

        table {
            border-collapse: collapse;
            border-spacing: 0
        }

        td, th {
            padding: 0
        }

        html {
            -webkit-box-sizing: border-box;
            box-sizing: border-box
        }

        *, *:before, *:after {
            -webkit-box-sizing: inherit;
            box-sizing: inherit
        }

        ul:not(.browser-default) {
            padding-left: 0;
            list-style-type: none
        }

        ul:not(.browser-default) li {
            list-style-type: none
        }

        a {
            color: #039be5;
            text-decoration: none;
            -webkit-tap-highlight-color: transparent
        }

        .valign-wrapper {
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -webkit-align-items: center;
            -ms-flex-align: center;
            align-items: center
        }

        .clearfix {
            clear: both
        }

        .z-depth-0 {
            -webkit-box-shadow: none !important;
            box-shadow: none !important
        }

        .z-depth-1, nav, .card-panel, .card, .toast, .btn, .btn-large, .btn-floating, .dropdown-content, .collapsible, .side-nav {
            -webkit-box-shadow: 0 2px 2px 0 rgba(0, 0, 0, 0.14), 0 1px 5px 0 rgba(0, 0, 0, 0.12), 0 3px 1px -2px rgba(0, 0, 0, 0.2);
            box-shadow: 0 2px 2px 0 rgba(0, 0, 0, 0.14), 0 1px 5px 0 rgba(0, 0, 0, 0.12), 0 3px 1px -2px rgba(0, 0, 0, 0.2)
        }

        .z-depth-1-half, .btn:hover, .btn-large:hover, .btn-floating:hover {
            -webkit-box-shadow: 0 3px 3px 0 rgba(0, 0, 0, 0.14), 0 1px 7px 0 rgba(0, 0, 0, 0.12), 0 3px 1px -1px rgba(0, 0, 0, 0.2);
            box-shadow: 0 3px 3px 0 rgba(0, 0, 0, 0.14), 0 1px 7px 0 rgba(0, 0, 0, 0.12), 0 3px 1px -1px rgba(0, 0, 0, 0.2)
        }

        .z-depth-2 {
            -webkit-box-shadow: 0 4px 5px 0 rgba(0, 0, 0, 0.14), 0 1px 10px 0 rgba(0, 0, 0, 0.12), 0 2px 4px -1px rgba(0, 0, 0, 0.3);
            box-shadow: 0 4px 5px 0 rgba(0, 0, 0, 0.14), 0 1px 10px 0 rgba(0, 0, 0, 0.12), 0 2px 4px -1px rgba(0, 0, 0, 0.3)
        }

        .z-depth-3 {
            -webkit-box-shadow: 0 6px 10px 0 rgba(0, 0, 0, 0.14), 0 1px 18px 0 rgba(0, 0, 0, 0.12), 0 3px 5px -1px rgba(0, 0, 0, 0.3);
            box-shadow: 0 6px 10px 0 rgba(0, 0, 0, 0.14), 0 1px 18px 0 rgba(0, 0, 0, 0.12), 0 3px 5px -1px rgba(0, 0, 0, 0.3)
        }

        .z-depth-4, .modal {
            -webkit-box-shadow: 0 8px 10px 1px rgba(0, 0, 0, 0.14), 0 3px 14px 2px rgba(0, 0, 0, 0.12), 0 5px 5px -3px rgba(0, 0, 0, 0.3);
            box-shadow: 0 8px 10px 1px rgba(0, 0, 0, 0.14), 0 3px 14px 2px rgba(0, 0, 0, 0.12), 0 5px 5px -3px rgba(0, 0, 0, 0.3)
        }

        .z-depth-5 {
            -webkit-box-shadow: 0 16px 24px 2px rgba(0, 0, 0, 0.14), 0 6px 30px 5px rgba(0, 0, 0, 0.12), 0 8px 10px -5px rgba(0, 0, 0, 0.3);
            box-shadow: 0 16px 24px 2px rgba(0, 0, 0, 0.14), 0 6px 30px 5px rgba(0, 0, 0, 0.12), 0 8px 10px -5px rgba(0, 0, 0, 0.3)
        }

        .hoverable {
            -webkit-transition: -webkit-box-shadow .25s;
            transition: -webkit-box-shadow .25s;
            transition: box-shadow .25s;
            transition: box-shadow .25s, -webkit-box-shadow .25s;
            -webkit-box-shadow: 0;
            box-shadow: 0
        }

        .hoverable:hover {
            -webkit-transition: -webkit-box-shadow .25s;
            transition: -webkit-box-shadow .25s;
            transition: box-shadow .25s;
            transition: box-shadow .25s, -webkit-box-shadow .25s;
            -webkit-box-shadow: 0 8px 17px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
            box-shadow: 0 8px 17px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19)
        }

        .divider {
            height: 1px;
            overflow: hidden;
            background-color: #e0e0e0
        }

        blockquote {
            margin: 20px 0;
            padding-left: 1.5rem;
            border-left: 5px solid #ee6e73
        }

        i {
            line-height: inherit
        }

        i.left {
            float: left;
            margin-right: 15px
        }

        i.right {
            float: right;
            margin-left: 15px
        }

        i.tiny {
            font-size: 1rem
        }

        i.small {
            font-size: 2rem
        }

        i.medium {
            font-size: 4rem
        }

        i.large {
            font-size: 6rem
        }

        img.responsive-img, video.responsive-video {
            max-width: 100%;
            height: auto
        }

        .pagination li {
            display: inline-block;
            border-radius: 2px;
            text-align: center;
            vertical-align: top;
            height: 30px
        }

        .pagination li a {
            color: #444;
            display: inline-block;
            font-size: 1.2rem;
            padding: 0 10px;
            line-height: 30px
        }

        .pagination li.active a {
            color: #fff
        }

        .pagination li.active {
            background-color: #ee6e73
        }

        .pagination li.disabled a {
            cursor: default;
            color: #999
        }

        .pagination li i {
            font-size: 2rem
        }

        .pagination li.pages ul li {
            display: inline-block;
            float: none
        }

        @media only screen and (max-width: 992px) {
            .pagination {
                width: 100%
            }

            .pagination li.prev, .pagination li.next {
                width: 10%
            }

            .pagination li.pages {
                width: 80%;
                overflow: hidden;
                white-space: nowrap
            }
        }

        .breadcrumb {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.7)
        }

        .breadcrumb i, .breadcrumb [class^="mdi-"], .breadcrumb [class*="mdi-"], .breadcrumb i.material-icons {
            display: inline-block;
            float: left;
            font-size: 24px
        }

        .breadcrumb:before {
            content: '\E5CC';
            color: rgba(255, 255, 255, 0.7);
            vertical-align: top;
            display: inline-block;
            font-family: 'Material Icons';
            font-weight: normal;
            font-style: normal;
            font-size: 25px;
            margin: 0 10px 0 8px;
            -webkit-font-smoothing: antialiased
        }

        .breadcrumb:first-child:before {
            display: none
        }

        .breadcrumb:last-child {
            color: #fff
        }

        .parallax-container {
            position: relative;
            overflow: hidden;
            height: 500px
        }

        .parallax-container .parallax {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1
        }

        .parallax-container .parallax img {
            display: none;
            position: absolute;
            left: 50%;
            bottom: 0;
            min-width: 100%;
            min-height: 100%;
            -webkit-transform: translate3d(0, 0, 0);
            transform: translate3d(0, 0, 0);
            -webkit-transform: translateX(-50%);
            transform: translateX(-50%)
        }

        .pin-top, .pin-bottom {
            position: relative
        }

        .pinned {
            position: fixed !important
        }

        ul.staggered-list li {
            opacity: 0
        }

        .fade-in {
            opacity: 0;
            -webkit-transform-origin: 0 50%;
            transform-origin: 0 50%
        }

        @media only screen and (max-width: 600px) {
            .hide-on-small-only, .hide-on-small-and-down {
                display: none !important
            }
        }

        @media only screen and (max-width: 992px) {
            .hide-on-med-and-down {
                display: none !important
            }
        }

        @media only screen and (min-width: 601px) {
            .hide-on-med-and-up {
                display: none !important
            }
        }

        @media only screen and (min-width: 600px) and (max-width: 992px) {
            .hide-on-med-only {
                display: none !important
            }
        }

        @media only screen and (min-width: 993px) {
            .hide-on-large-only {
                display: none !important
            }
        }

        @media only screen and (min-width: 993px) {
            .show-on-large {
                display: block !important
            }
        }

        @media only screen and (min-width: 600px) and (max-width: 992px) {
            .show-on-medium {
                display: block !important
            }
        }

        @media only screen and (max-width: 600px) {
            .show-on-small {
                display: block !important
            }
        }

        @media only screen and (min-width: 601px) {
            .show-on-medium-and-up {
                display: block !important
            }
        }

        @media only screen and (max-width: 992px) {
            .show-on-medium-and-down {
                display: block !important
            }
        }

        @media only screen and (max-width: 600px) {
            .center-on-small-only {
                text-align: center
            }
        }

        .page-footer {
            padding-top: 20px;
            color: #fff;
            background-color: #ee6e73
        }

        .page-footer .footer-copyright {
            overflow: hidden;
            min-height: 50px;
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -webkit-align-items: center;
            -ms-flex-align: center;
            align-items: center;
            padding: 10px 0px;
            color: rgba(255, 255, 255, 0.8);
            background-color: rgba(51, 51, 51, 0.08)
        }

        table, th, td {
            border: none
        }

        table {
            width: 100%;
            display: table
        }

        table.bordered > thead > tr, table.bordered > tbody > tr {
            border-bottom: 1px solid #d0d0d0
        }

        table.striped > tbody > tr:nth-child(odd) {
            background-color: #f2f2f2
        }

        table.striped > tbody > tr > td {
            border-radius: 0
        }

        table.highlight > tbody > tr {
            -webkit-transition: background-color .25s ease;
            transition: background-color .25s ease
        }

        table.highlight > tbody > tr:hover {
            background-color: #f2f2f2
        }

        table.centered thead tr th, table.centered tbody tr td {
            text-align: center
        }

        thead {
            border-bottom: 1px solid #d0d0d0
        }

        td, th {
            padding: 15px 5px;
            display: table-cell;
            text-align: left;
            vertical-align: middle;
            border-radius: 2px
        }

        @media only screen and (max-width: 992px) {
            table.responsive-table {
                width: 100%;
                border-collapse: collapse;
                border-spacing: 0;
                display: block;
                position: relative
            }

            table.responsive-table td:empty:before {
                content: '\00a0'
            }

            table.responsive-table th, table.responsive-table td {
                margin: 0;
                vertical-align: top
            }

            table.responsive-table th {
                text-align: left
            }

            table.responsive-table thead {
                display: block;
                float: left
            }

            table.responsive-table thead tr {
                display: block;
                padding: 0 10px 0 0
            }

            table.responsive-table thead tr th::before {
                content: "\00a0"
            }

            table.responsive-table tbody {
                display: block;
                width: auto;
                position: relative;
                overflow-x: auto;
                white-space: nowrap
            }

            table.responsive-table tbody tr {
                display: inline-block;
                vertical-align: top
            }

            table.responsive-table th {
                display: block;
                text-align: right
            }

            table.responsive-table td {
                display: block;
                min-height: 1.25em;
                text-align: left
            }

            table.responsive-table tr {
                padding: 0 10px
            }

            table.responsive-table thead {
                border: 0;
                border-right: 1px solid #d0d0d0
            }

            table.responsive-table.bordered th {
                border-bottom: 0;
                border-left: 0
            }

            table.responsive-table.bordered td {
                border-left: 0;
                border-right: 0;
                border-bottom: 0
            }

            table.responsive-table.bordered tr {
                border: 0
            }

            table.responsive-table.bordered tbody tr {
                border-right: 1px solid #d0d0d0
            }
        }

        .collection {
            margin: .5rem 0 1rem 0;
            border: 1px solid #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
            position: relative
        }

        .collection .collection-item {
            background-color: #fff;
            line-height: 1.5rem;
            padding: 10px 20px;
            margin: 0;
            border-bottom: 1px solid #e0e0e0
        }

        .collection .collection-item.avatar {
            min-height: 84px;
            padding-left: 72px;
            position: relative
        }

        .collection .collection-item.avatar .circle {
            position: absolute;
            width: 42px;
            height: 42px;
            overflow: hidden;
            left: 15px;
            display: inline-block;
            vertical-align: middle
        }

        .collection .collection-item.avatar i.circle {
            font-size: 18px;
            line-height: 42px;
            color: #fff;
            background-color: #999;
            text-align: center
        }

        .collection .collection-item.avatar .title {
            font-size: 16px
        }

        .collection .collection-item.avatar p {
            margin: 0
        }

        .collection .collection-item.avatar .secondary-content {
            position: absolute;
            top: 16px;
            right: 16px
        }

        .collection .collection-item:last-child {
            border-bottom: none
        }

        .collection .collection-item.active {
            background-color: #26a69a;
            color: #eafaf9
        }

        .collection .collection-item.active .secondary-content {
            color: #fff
        }

        .collection a.collection-item {
            display: block;
            -webkit-transition: .25s;
            transition: .25s;
            color: #26a69a
        }

        .collection a.collection-item:not(.active):hover {
            background-color: #ddd
        }

        .collection.with-header .collection-header {
            background-color: #fff;
            border-bottom: 1px solid #e0e0e0;
            padding: 10px 20px
        }

        .collection.with-header .collection-item {
            padding-left: 30px
        }

        .collection.with-header .collection-item.avatar {
            padding-left: 72px
        }

        .secondary-content {
            float: right;
            color: #26a69a
        }

        .collapsible .collection {
            margin: 0;
            border: none
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden
        }

        .video-container iframe, .video-container object, .video-container embed {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%
        }

        .progress {
            position: relative;
            height: 4px;
            display: block;
            width: 100%;
            background-color: #acece6;
            border-radius: 2px;
            margin: .5rem 0 1rem 0;
            overflow: hidden
        }

        .progress .determinate {
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            background-color: #26a69a;
            -webkit-transition: width .3s linear;
            transition: width .3s linear
        }

        .progress .indeterminate {
            background-color: #26a69a
        }

        .progress .indeterminate:before {
            content: '';
            position: absolute;
            background-color: inherit;
            top: 0;
            left: 0;
            bottom: 0;
            will-change: left, right;
            -webkit-animation: indeterminate 2.1s cubic-bezier(0.65, 0.815, 0.735, 0.395) infinite;
            animation: indeterminate 2.1s cubic-bezier(0.65, 0.815, 0.735, 0.395) infinite
        }

        .progress .indeterminate:after {
            content: '';
            position: absolute;
            background-color: inherit;
            top: 0;
            left: 0;
            bottom: 0;
            will-change: left, right;
            -webkit-animation: indeterminate-short 2.1s cubic-bezier(0.165, 0.84, 0.44, 1) infinite;
            animation: indeterminate-short 2.1s cubic-bezier(0.165, 0.84, 0.44, 1) infinite;
            -webkit-animation-delay: 1.15s;
            animation-delay: 1.15s
        }

        @-webkit-keyframes indeterminate {
            0% {
                left: -35%;
                right: 100%
            }
            60% {
                left: 100%;
                right: -90%
            }
            100% {
                left: 100%;
                right: -90%
            }
        }

        @keyframes indeterminate {
            0% {
                left: -35%;
                right: 100%
            }
            60% {
                left: 100%;
                right: -90%
            }
            100% {
                left: 100%;
                right: -90%
            }
        }

        @-webkit-keyframes indeterminate-short {
            0% {
                left: -200%;
                right: 100%
            }
            60% {
                left: 107%;
                right: -8%
            }
            100% {
                left: 107%;
                right: -8%
            }
        }

        @keyframes indeterminate-short {
            0% {
                left: -200%;
                right: 100%
            }
            60% {
                left: 107%;
                right: -8%
            }
            100% {
                left: 107%;
                right: -8%
            }
        }

        .hide {
            display: none !important
        }

        .left-align {
            text-align: left
        }

        .right-align {
            text-align: right
        }

        .center, .center-align {
            text-align: center
        }

        .left {
            float: left !important
        }

        .right {
            float: right !important
        }

        .no-select, input[type=range], input[type=range] + .thumb {
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none
        }

        .circle {
            border-radius: 50%
        }

        .center-block {
            display: block;
            margin-left: auto;
            margin-right: auto
        }

        .truncate {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .no-padding {
            padding: 0 !important
        }

        span.badge {
            min-width: 3rem;
            padding: 0 6px;
            margin-left: 14px;
            text-align: center;
            font-size: 1rem;
            line-height: 22px;
            height: 22px;
            color: #757575;
            float: right;
            -webkit-box-sizing: border-box;
            box-sizing: border-box
        }

        span.badge.new {
            font-weight: 300;
            font-size: 0.8rem;
            color: #fff;
            background-color: #26a69a;
            border-radius: 2px
        }

        span.badge.new:after {
            content: " new"
        }

        span.badge[data-badge-caption]::after {
            content: " " attr(data-badge-caption)
        }

        nav ul a span.badge {
            display: inline-block;
            float: none;
            margin-left: 4px;
            line-height: 22px;
            height: 22px
        }

        .collection-item span.badge {
            margin-top: calc(.75rem - 11px)
        }

        .collapsible span.badge {
            margin-top: calc(1.5rem - 11px)
        }

        .side-nav span.badge {
            margin-top: calc(24px - 11px)
        }

        .material-icons {
            text-rendering: optimizeLegibility;
            -webkit-font-feature-settings: 'liga';
            -moz-font-feature-settings: 'liga';
            font-feature-settings: 'liga'
        }

        .container {
            margin: 0 auto;
            max-width: 1280px;
            width: 90%
        }

        @media only screen and (min-width: 601px) {
            .container {
                width: 85%
            }
        }

        @media only screen and (min-width: 993px) {
            .container {
                width: 70%
            }
        }

        .container .row {
            margin-left: -.75rem;
            margin-right: -.75rem
        }

        .section {
            padding-top: 1rem;
            padding-bottom: 1rem
        }

        .section.no-pad {
            padding: 0
        }

        .section.no-pad-bot {
            padding-bottom: 0
        }

        .section.no-pad-top {
            padding-top: 0
        }

        .row {
            margin-left: auto;
            margin-right: auto;
            margin-bottom: 20px
        }

        .row:after {
            content: "";
            display: table;
            clear: both
        }

        .row .col {
            float: left;
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
            padding: 0 .75rem;
            min-height: 1px
        }

        .row .col[class*="push-"], .row .col[class*="pull-"] {
            position: relative
        }

        .row .col.s1 {
            width: 8.3333333333%;
            margin-left: auto;
            left: auto;
            right: auto
        }

        .row .col.s2 {
            width: 16.6666666667%;
            margin-left: auto;
            left: auto;
            right: auto
        }

        .row .col.s3 {
            width: 25%;
            margin-left: auto;
            left: auto;
            right: auto
        }

        .row .col.s4 {
            width: 33.3333333333%;
            margin-left: auto;
            left: auto;
            right: auto
        }

        .row .col.s5 {
            width: 41.6666666667%;
            margin-left: auto;
            left: auto;
            right: auto
        }

        .row .col.s6 {
            width: 50%;
            margin-left: auto;
            left: auto;
            right: auto
        }

        .row .col.s7 {
            width: 58.3333333333%;
            margin-left: auto;
            left: auto;
            right: auto
        }

        .row .col.s8 {
            width: 66.6666666667%;
            margin-left: auto;
            left: auto;
            right: auto
        }

        .row .col.s9 {
            width: 75%;
            margin-left: auto;
            left: auto;
            right: auto
        }

        .row .col.s10 {
            width: 83.3333333333%;
            margin-left: auto;
            left: auto;
            right: auto
        }

        .row .col.s11 {
            width: 91.6666666667%;
            margin-left: auto;
            left: auto;
            right: auto
        }

        .row .col.s12 {
            width: 100%;
            margin-left: auto;
            left: auto;
            right: auto
        }

        .row .col.offset-s1 {
            margin-left: 8.3333333333%
        }

        .row .col.pull-s1 {
            right: 8.3333333333%
        }

        .row .col.push-s1 {
            left: 8.3333333333%
        }

        .row .col.offset-s2 {
            margin-left: 16.6666666667%
        }

        .row .col.pull-s2 {
            right: 16.6666666667%
        }

        .row .col.push-s2 {
            left: 16.6666666667%
        }

        .row .col.offset-s3 {
            margin-left: 25%
        }

        .row .col.pull-s3 {
            right: 25%
        }

        .row .col.push-s3 {
            left: 25%
        }

        .row .col.offset-s4 {
            margin-left: 33.3333333333%
        }

        .row .col.pull-s4 {
            right: 33.3333333333%
        }

        .row .col.push-s4 {
            left: 33.3333333333%
        }

        .row .col.offset-s5 {
            margin-left: 41.6666666667%
        }

        .row .col.pull-s5 {
            right: 41.6666666667%
        }

        .row .col.push-s5 {
            left: 41.6666666667%
        }

        .row .col.offset-s6 {
            margin-left: 50%
        }

        .row .col.pull-s6 {
            right: 50%
        }

        .row .col.push-s6 {
            left: 50%
        }

        .row .col.offset-s7 {
            margin-left: 58.3333333333%
        }

        .row .col.pull-s7 {
            right: 58.3333333333%
        }

        .row .col.push-s7 {
            left: 58.3333333333%
        }

        .row .col.offset-s8 {
            margin-left: 66.6666666667%
        }

        .row .col.pull-s8 {
            right: 66.6666666667%
        }

        .row .col.push-s8 {
            left: 66.6666666667%
        }

        .row .col.offset-s9 {
            margin-left: 75%
        }

        .row .col.pull-s9 {
            right: 75%
        }

        .row .col.push-s9 {
            left: 75%
        }

        .row .col.offset-s10 {
            margin-left: 83.3333333333%
        }

        .row .col.pull-s10 {
            right: 83.3333333333%
        }

        .row .col.push-s10 {
            left: 83.3333333333%
        }

        .row .col.offset-s11 {
            margin-left: 91.6666666667%
        }

        .row .col.pull-s11 {
            right: 91.6666666667%
        }

        .row .col.push-s11 {
            left: 91.6666666667%
        }

        .row .col.offset-s12 {
            margin-left: 100%
        }

        .row .col.pull-s12 {
            right: 100%
        }

        .row .col.push-s12 {
            left: 100%
        }

        @media only screen and (min-width: 601px) {
            .row .col.m1 {
                width: 8.3333333333%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.m2 {
                width: 16.6666666667%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.m3 {
                width: 25%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.m4 {
                width: 33.3333333333%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.m5 {
                width: 41.6666666667%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.m6 {
                width: 50%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.m7 {
                width: 58.3333333333%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.m8 {
                width: 66.6666666667%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.m9 {
                width: 75%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.m10 {
                width: 83.3333333333%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.m11 {
                width: 91.6666666667%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.m12 {
                width: 100%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.offset-m1 {
                margin-left: 8.3333333333%
            }

            .row .col.pull-m1 {
                right: 8.3333333333%
            }

            .row .col.push-m1 {
                left: 8.3333333333%
            }

            .row .col.offset-m2 {
                margin-left: 16.6666666667%
            }

            .row .col.pull-m2 {
                right: 16.6666666667%
            }

            .row .col.push-m2 {
                left: 16.6666666667%
            }

            .row .col.offset-m3 {
                margin-left: 25%
            }

            .row .col.pull-m3 {
                right: 25%
            }

            .row .col.push-m3 {
                left: 25%
            }

            .row .col.offset-m4 {
                margin-left: 33.3333333333%
            }

            .row .col.pull-m4 {
                right: 33.3333333333%
            }

            .row .col.push-m4 {
                left: 33.3333333333%
            }

            .row .col.offset-m5 {
                margin-left: 41.6666666667%
            }

            .row .col.pull-m5 {
                right: 41.6666666667%
            }

            .row .col.push-m5 {
                left: 41.6666666667%
            }

            .row .col.offset-m6 {
                margin-left: 50%
            }

            .row .col.pull-m6 {
                right: 50%
            }

            .row .col.push-m6 {
                left: 50%
            }

            .row .col.offset-m7 {
                margin-left: 58.3333333333%
            }

            .row .col.pull-m7 {
                right: 58.3333333333%
            }

            .row .col.push-m7 {
                left: 58.3333333333%
            }

            .row .col.offset-m8 {
                margin-left: 66.6666666667%
            }

            .row .col.pull-m8 {
                right: 66.6666666667%
            }

            .row .col.push-m8 {
                left: 66.6666666667%
            }

            .row .col.offset-m9 {
                margin-left: 75%
            }

            .row .col.pull-m9 {
                right: 75%
            }

            .row .col.push-m9 {
                left: 75%
            }

            .row .col.offset-m10 {
                margin-left: 83.3333333333%
            }

            .row .col.pull-m10 {
                right: 83.3333333333%
            }

            .row .col.push-m10 {
                left: 83.3333333333%
            }

            .row .col.offset-m11 {
                margin-left: 91.6666666667%
            }

            .row .col.pull-m11 {
                right: 91.6666666667%
            }

            .row .col.push-m11 {
                left: 91.6666666667%
            }

            .row .col.offset-m12 {
                margin-left: 100%
            }

            .row .col.pull-m12 {
                right: 100%
            }

            .row .col.push-m12 {
                left: 100%
            }
        }

        @media only screen and (min-width: 993px) {
            .row .col.l1 {
                width: 8.3333333333%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.l2 {
                width: 16.6666666667%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.l3 {
                width: 25%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.l4 {
                width: 33.3333333333%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.l5 {
                width: 41.6666666667%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.l6 {
                width: 50%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.l7 {
                width: 58.3333333333%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.l8 {
                width: 66.6666666667%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.l9 {
                width: 75%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.l10 {
                width: 83.3333333333%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.l11 {
                width: 91.6666666667%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.l12 {
                width: 100%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.offset-l1 {
                margin-left: 8.3333333333%
            }

            .row .col.pull-l1 {
                right: 8.3333333333%
            }

            .row .col.push-l1 {
                left: 8.3333333333%
            }

            .row .col.offset-l2 {
                margin-left: 16.6666666667%
            }

            .row .col.pull-l2 {
                right: 16.6666666667%
            }

            .row .col.push-l2 {
                left: 16.6666666667%
            }

            .row .col.offset-l3 {
                margin-left: 25%
            }

            .row .col.pull-l3 {
                right: 25%
            }

            .row .col.push-l3 {
                left: 25%
            }

            .row .col.offset-l4 {
                margin-left: 33.3333333333%
            }

            .row .col.pull-l4 {
                right: 33.3333333333%
            }

            .row .col.push-l4 {
                left: 33.3333333333%
            }

            .row .col.offset-l5 {
                margin-left: 41.6666666667%
            }

            .row .col.pull-l5 {
                right: 41.6666666667%
            }

            .row .col.push-l5 {
                left: 41.6666666667%
            }

            .row .col.offset-l6 {
                margin-left: 50%
            }

            .row .col.pull-l6 {
                right: 50%
            }

            .row .col.push-l6 {
                left: 50%
            }

            .row .col.offset-l7 {
                margin-left: 58.3333333333%
            }

            .row .col.pull-l7 {
                right: 58.3333333333%
            }

            .row .col.push-l7 {
                left: 58.3333333333%
            }

            .row .col.offset-l8 {
                margin-left: 66.6666666667%
            }

            .row .col.pull-l8 {
                right: 66.6666666667%
            }

            .row .col.push-l8 {
                left: 66.6666666667%
            }

            .row .col.offset-l9 {
                margin-left: 75%
            }

            .row .col.pull-l9 {
                right: 75%
            }

            .row .col.push-l9 {
                left: 75%
            }

            .row .col.offset-l10 {
                margin-left: 83.3333333333%
            }

            .row .col.pull-l10 {
                right: 83.3333333333%
            }

            .row .col.push-l10 {
                left: 83.3333333333%
            }

            .row .col.offset-l11 {
                margin-left: 91.6666666667%
            }

            .row .col.pull-l11 {
                right: 91.6666666667%
            }

            .row .col.push-l11 {
                left: 91.6666666667%
            }

            .row .col.offset-l12 {
                margin-left: 100%
            }

            .row .col.pull-l12 {
                right: 100%
            }

            .row .col.push-l12 {
                left: 100%
            }
        }

        @media only screen and (min-width: 1201px) {
            .row .col.xl1 {
                width: 8.3333333333%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.xl2 {
                width: 16.6666666667%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.xl3 {
                width: 25%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.xl4 {
                width: 33.3333333333%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.xl5 {
                width: 41.6666666667%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.xl6 {
                width: 50%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.xl7 {
                width: 58.3333333333%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.xl8 {
                width: 66.6666666667%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.xl9 {
                width: 75%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.xl10 {
                width: 83.3333333333%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.xl11 {
                width: 91.6666666667%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.xl12 {
                width: 100%;
                margin-left: auto;
                left: auto;
                right: auto
            }

            .row .col.offset-xl1 {
                margin-left: 8.3333333333%
            }

            .row .col.pull-xl1 {
                right: 8.3333333333%
            }

            .row .col.push-xl1 {
                left: 8.3333333333%
            }

            .row .col.offset-xl2 {
                margin-left: 16.6666666667%
            }

            .row .col.pull-xl2 {
                right: 16.6666666667%
            }

            .row .col.push-xl2 {
                left: 16.6666666667%
            }

            .row .col.offset-xl3 {
                margin-left: 25%
            }

            .row .col.pull-xl3 {
                right: 25%
            }

            .row .col.push-xl3 {
                left: 25%
            }

            .row .col.offset-xl4 {
                margin-left: 33.3333333333%
            }

            .row .col.pull-xl4 {
                right: 33.3333333333%
            }

            .row .col.push-xl4 {
                left: 33.3333333333%
            }

            .row .col.offset-xl5 {
                margin-left: 41.6666666667%
            }

            .row .col.pull-xl5 {
                right: 41.6666666667%
            }

            .row .col.push-xl5 {
                left: 41.6666666667%
            }

            .row .col.offset-xl6 {
                margin-left: 50%
            }

            .row .col.pull-xl6 {
                right: 50%
            }

            .row .col.push-xl6 {
                left: 50%
            }

            .row .col.offset-xl7 {
                margin-left: 58.3333333333%
            }

            .row .col.pull-xl7 {
                right: 58.3333333333%
            }

            .row .col.push-xl7 {
                left: 58.3333333333%
            }

            .row .col.offset-xl8 {
                margin-left: 66.6666666667%
            }

            .row .col.pull-xl8 {
                right: 66.6666666667%
            }

            .row .col.push-xl8 {
                left: 66.6666666667%
            }

            .row .col.offset-xl9 {
                margin-left: 75%
            }

            .row .col.pull-xl9 {
                right: 75%
            }

            .row .col.push-xl9 {
                left: 75%
            }

            .row .col.offset-xl10 {
                margin-left: 83.3333333333%
            }

            .row .col.pull-xl10 {
                right: 83.3333333333%
            }

            .row .col.push-xl10 {
                left: 83.3333333333%
            }

            .row .col.offset-xl11 {
                margin-left: 91.6666666667%
            }

            .row .col.pull-xl11 {
                right: 91.6666666667%
            }

            .row .col.push-xl11 {
                left: 91.6666666667%
            }

            .row .col.offset-xl12 {
                margin-left: 100%
            }

            .row .col.pull-xl12 {
                right: 100%
            }

            .row .col.push-xl12 {
                left: 100%
            }
        }

        nav {
            color: #fff;
            background-color: #ee6e73;
            width: 100%;
            height: 56px;
            line-height: 56px
        }

        nav.nav-extended {
            height: auto
        }

        nav.nav-extended .nav-wrapper {
            min-height: 56px;
            height: auto
        }

        nav.nav-extended .nav-content {
            position: relative;
            line-height: normal
        }

        nav a {
            color: #fff
        }

        nav i, nav [class^="mdi-"], nav [class*="mdi-"], nav i.material-icons {
            display: block;
            font-size: 24px;
            height: 56px;
            line-height: 56px
        }

        nav .nav-wrapper {
            position: relative;
            height: 100%
        }

        @media only screen and (min-width: 993px) {
            nav a.button-collapse {
                display: none
            }
        }

        nav .button-collapse {
            float: left;
            position: relative;
            z-index: 1;
            height: 56px;
            margin: 0 18px
        }

        nav .button-collapse i {
            height: 56px;
            line-height: 56px
        }

        nav .brand-logo {
            position: absolute;
            color: #fff;
            display: inline-block;
            font-size: 2.1rem;
            padding: 0;
            white-space: nowrap
        }

        nav .brand-logo.center {
            left: 50%;
            -webkit-transform: translateX(-50%);
            transform: translateX(-50%)
        }

        @media only screen and (max-width: 992px) {
            nav .brand-logo {
                left: 50%;
                -webkit-transform: translateX(-50%);
                transform: translateX(-50%)
            }

            nav .brand-logo.left, nav .brand-logo.right {
                padding: 0;
                -webkit-transform: none;
                transform: none
            }

            nav .brand-logo.left {
                left: 0.5rem
            }

            nav .brand-logo.right {
                right: 0.5rem;
                left: auto
            }
        }

        nav .brand-logo.right {
            right: 0.5rem;
            padding: 0
        }

        nav .brand-logo i, nav .brand-logo [class^="mdi-"], nav .brand-logo [class*="mdi-"], nav .brand-logo i.material-icons {
            float: left;
            margin-right: 15px
        }

        nav .nav-title {
            display: inline-block;
            font-size: 32px;
            padding: 28px 0
        }

        nav ul {
            margin: 0
        }

        nav ul li {
            -webkit-transition: background-color .3s;
            transition: background-color .3s;
            float: left;
            padding: 0
        }

        nav ul li.active {
            background-color: rgba(0, 0, 0, 0.1)
        }

        nav ul a {
            -webkit-transition: background-color .3s;
            transition: background-color .3s;
            font-size: 1rem;
            color: #fff;
            display: block;
            padding: 0 15px;
            cursor: pointer
        }

        nav ul a.btn, nav ul a.btn-large, nav ul a.btn-large, nav ul a.btn-flat, nav ul a.btn-floating {
            margin-top: -2px;
            margin-left: 15px;
            margin-right: 15px
        }

        nav ul a.btn > .material-icons, nav ul a.btn-large > .material-icons, nav ul a.btn-large > .material-icons, nav ul a.btn-flat > .material-icons, nav ul a.btn-floating > .material-icons {
            height: inherit;
            line-height: inherit
        }

        nav ul a:hover {
            background-color: rgba(0, 0, 0, 0.1)
        }

        nav ul.left {
            float: left
        }

        nav form {
            height: 100%
        }

        nav .input-field {
            margin: 0;
            height: 100%
        }

        nav .input-field input {
            height: 100%;
            font-size: 1.2rem;
            border: none;
            padding-left: 2rem
        }

        nav .input-field input:focus, nav .input-field input[type=text]:valid, nav .input-field input[type=password]:valid, nav .input-field input[type=email]:valid, nav .input-field input[type=url]:valid, nav .input-field input[type=date]:valid {
            border: none;
            -webkit-box-shadow: none;
            box-shadow: none
        }

        nav .input-field label {
            top: 0;
            left: 0
        }

        nav .input-field label i {
            color: rgba(255, 255, 255, 0.7);
            -webkit-transition: color .3s;
            transition: color .3s
        }

        nav .input-field label.active i {
            color: #fff
        }

        .navbar-fixed {
            position: relative;
            height: 56px;
            z-index: 997
        }

        .navbar-fixed nav {
            position: fixed
        }

        @media only screen and (min-width: 601px) {
            nav.nav-extended .nav-wrapper {
                min-height: 64px
            }

            nav, nav .nav-wrapper i, nav a.button-collapse, nav a.button-collapse i {
                height: 64px;
                line-height: 64px
            }

            .navbar-fixed {
                height: 64px
            }
        }

        @font-face {
            font-family: "Roboto";
            src: local(Roboto Thin), url("../fonts/roboto/Roboto-Thin.woff2") format("woff2"), url("../fonts/roboto/Roboto-Thin.woff") format("woff");
            font-weight: 100
        }

        @font-face {
            font-family: "Roboto";
            src: local(Roboto Light), url("../fonts/roboto/Roboto-Light.woff2") format("woff2"), url("../fonts/roboto/Roboto-Light.woff") format("woff");
            font-weight: 300
        }

        @font-face {
            font-family: "Roboto";
            src: local(Roboto Regular), url("../fonts/roboto/Roboto-Regular.woff2") format("woff2"), url("../fonts/roboto/Roboto-Regular.woff") format("woff");
            font-weight: 400
        }

        @font-face {
            font-family: "Roboto";
            src: local(Roboto Medium), url("../fonts/roboto/Roboto-Medium.woff2") format("woff2"), url("../fonts/roboto/Roboto-Medium.woff") format("woff");
            font-weight: 500
        }

        @font-face {
            font-family: "Roboto";
            src: local(Roboto Bold), url("../fonts/roboto/Roboto-Bold.woff2") format("woff2"), url("../fonts/roboto/Roboto-Bold.woff") format("woff");
            font-weight: 700
        }

        a {
            text-decoration: none
        }

        html {
            line-height: 1.5;
            font-family: "Roboto", sans-serif;
            font-weight: normal;
            color: rgba(0, 0, 0, 0.87)
        }

        @media only screen and (min-width: 0) {
            html {
                font-size: 14px
            }
        }

        @media only screen and (min-width: 992px) {
            html {
                font-size: 14.5px
            }
        }

        @media only screen and (min-width: 1200px) {
            html {
                font-size: 15px
            }
        }

        h1, h2, h3, h4, h5, h6 {
            font-weight: 400;
            line-height: 1.1
        }

        h1 a, h2 a, h3 a, h4 a, h5 a, h6 a {
            font-weight: inherit
        }

        h1 {
            font-size: 4.2rem;
            line-height: 110%;
            margin: 2.1rem 0 1.68rem 0
        }

        h2 {
            font-size: 3.56rem;
            line-height: 110%;
            margin: 1.78rem 0 1.424rem 0
        }

        h3 {
            font-size: 2.92rem;
            line-height: 110%;
            margin: 1.46rem 0 1.168rem 0
        }

        h4 {
            font-size: 2.28rem;
            line-height: 110%;
            margin: 1.14rem 0 .912rem 0
        }

        h5 {
            font-size: 1.64rem;
            line-height: 110%;
            margin: .82rem 0 .656rem 0
        }

        h6 {
            font-size: 1rem;
            line-height: 110%;
            margin: .5rem 0 .4rem 0
        }

        em {
            font-style: italic
        }

        strong {
            font-weight: 500
        }

        small {
            font-size: 75%
        }

        .light, .page-footer .footer-copyright {
            font-weight: 300
        }

        .thin {
            font-weight: 200
        }

        .flow-text {
            font-weight: 300
        }

        @media only screen and (min-width: 360px) {
            .flow-text {
                font-size: 1.2rem
            }
        }

        @media only screen and (min-width: 390px) {
            .flow-text {
                font-size: 1.224rem
            }
        }

        @media only screen and (min-width: 420px) {
            .flow-text {
                font-size: 1.248rem
            }
        }

        @media only screen and (min-width: 450px) {
            .flow-text {
                font-size: 1.272rem
            }
        }

        @media only screen and (min-width: 480px) {
            .flow-text {
                font-size: 1.296rem
            }
        }

        @media only screen and (min-width: 510px) {
            .flow-text {
                font-size: 1.32rem
            }
        }

        @media only screen and (min-width: 540px) {
            .flow-text {
                font-size: 1.344rem
            }
        }

        @media only screen and (min-width: 570px) {
            .flow-text {
                font-size: 1.368rem
            }
        }

        @media only screen and (min-width: 600px) {
            .flow-text {
                font-size: 1.392rem
            }
        }

        @media only screen and (min-width: 630px) {
            .flow-text {
                font-size: 1.416rem
            }
        }

        @media only screen and (min-width: 660px) {
            .flow-text {
                font-size: 1.44rem
            }
        }

        @media only screen and (min-width: 690px) {
            .flow-text {
                font-size: 1.464rem
            }
        }

        @media only screen and (min-width: 720px) {
            .flow-text {
                font-size: 1.488rem
            }
        }

        @media only screen and (min-width: 750px) {
            .flow-text {
                font-size: 1.512rem
            }
        }

        @media only screen and (min-width: 780px) {
            .flow-text {
                font-size: 1.536rem
            }
        }

        @media only screen and (min-width: 810px) {
            .flow-text {
                font-size: 1.56rem
            }
        }

        @media only screen and (min-width: 840px) {
            .flow-text {
                font-size: 1.584rem
            }
        }

        @media only screen and (min-width: 870px) {
            .flow-text {
                font-size: 1.608rem
            }
        }

        @media only screen and (min-width: 900px) {
            .flow-text {
                font-size: 1.632rem
            }
        }

        @media only screen and (min-width: 930px) {
            .flow-text {
                font-size: 1.656rem
            }
        }

        @media only screen and (min-width: 960px) {
            .flow-text {
                font-size: 1.68rem
            }
        }

        @media only screen and (max-width: 360px) {
            .flow-text {
                font-size: 1.2rem
            }
        }

        .scale-transition {
            -webkit-transition: -webkit-transform 0.3s cubic-bezier(0.53, 0.01, 0.36, 1.63) !important;
            transition: -webkit-transform 0.3s cubic-bezier(0.53, 0.01, 0.36, 1.63) !important;
            transition: transform 0.3s cubic-bezier(0.53, 0.01, 0.36, 1.63) !important;
            transition: transform 0.3s cubic-bezier(0.53, 0.01, 0.36, 1.63), -webkit-transform 0.3s cubic-bezier(0.53, 0.01, 0.36, 1.63) !important
        }

        .scale-transition.scale-out {
            -webkit-transform: scale(0);
            transform: scale(0);
            -webkit-transition: -webkit-transform .2s !important;
            transition: -webkit-transform .2s !important;
            transition: transform .2s !important;
            transition: transform .2s, -webkit-transform .2s !important
        }

        .scale-transition.scale-in {
            -webkit-transform: scale(1);
            transform: scale(1)
        }

        .card-panel {
            -webkit-transition: -webkit-box-shadow .25s;
            transition: -webkit-box-shadow .25s;
            transition: box-shadow .25s;
            transition: box-shadow .25s, -webkit-box-shadow .25s;
            padding: 24px;
            margin: .5rem 0 1rem 0;
            border-radius: 2px;
            background-color: #fff
        }

        .card {
            position: relative;
            margin: .5rem 0 1rem 0;
            background-color: #fff;
            -webkit-transition: -webkit-box-shadow .25s;
            transition: -webkit-box-shadow .25s;
            transition: box-shadow .25s;
            transition: box-shadow .25s, -webkit-box-shadow .25s;
            border-radius: 2px
        }

        .card .card-title {
            font-size: 24px;
            font-weight: 300
        }

        .card .card-title.activator {
            cursor: pointer
        }

        .card.small, .card.medium, .card.large {
            position: relative
        }

        .card.small .card-image, .card.medium .card-image, .card.large .card-image {
            max-height: 60%;
            overflow: hidden
        }

        .card.small .card-image + .card-content, .card.medium .card-image + .card-content, .card.large .card-image + .card-content {
            max-height: 40%
        }

        .card.small .card-content, .card.medium .card-content, .card.large .card-content {
            max-height: 100%;
            overflow: hidden
        }

        .card.small .card-action, .card.medium .card-action, .card.large .card-action {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0
        }

        .card.small {
            height: 300px
        }

        .card.medium {
            height: 400px
        }

        .card.large {
            height: 500px
        }

        .card.horizontal {
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex
        }

        .card.horizontal.small .card-image, .card.horizontal.medium .card-image, .card.horizontal.large .card-image {
            height: 100%;
            max-height: none;
            overflow: visible
        }

        .card.horizontal.small .card-image img, .card.horizontal.medium .card-image img, .card.horizontal.large .card-image img {
            height: 100%
        }

        .card.horizontal .card-image {
            max-width: 50%
        }

        .card.horizontal .card-image img {
            border-radius: 2px 0 0 2px;
            max-width: 100%;
            width: auto
        }

        .card.horizontal .card-stacked {
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-orient: vertical;
            -webkit-box-direction: normal;
            -webkit-flex-direction: column;
            -ms-flex-direction: column;
            flex-direction: column;
            -webkit-box-flex: 1;
            -webkit-flex: 1;
            -ms-flex: 1;
            flex: 1;
            position: relative
        }

        .card.horizontal .card-stacked .card-content {
            -webkit-box-flex: 1;
            -webkit-flex-grow: 1;
            -ms-flex-positive: 1;
            flex-grow: 1
        }

        .card.sticky-action .card-action {
            z-index: 2
        }

        .card.sticky-action .card-reveal {
            z-index: 1;
            padding-bottom: 64px
        }

        .card .card-image {
            position: relative
        }

        .card .card-image img {
            display: block;
            border-radius: 2px 2px 0 0;
            position: relative;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            width: 100%
        }

        .card .card-image .card-title {
            color: #fff;
            position: absolute;
            bottom: 0;
            left: 0;
            max-width: 100%;
            padding: 24px
        }

        .card .card-content {
            padding: 24px;
            border-radius: 0 0 2px 2px
        }

        .card .card-content p {
            margin: 0;
            color: inherit
        }

        .card .card-content .card-title {
            display: block;
            line-height: 32px;
            margin-bottom: 8px
        }

        .card .card-content .card-title i {
            line-height: 32px
        }

        .card .card-action {
            position: relative;
            background-color: inherit;
            border-top: 1px solid rgba(160, 160, 160, 0.2);
            padding: 16px 24px
        }

        .card .card-action:last-child {
            border-radius: 0 0 2px 2px
        }

        .card .card-action a:not(.btn):not(.btn-large):not(.btn-large):not(.btn-floating) {
            color: #ffab40;
            margin-right: 24px;
            -webkit-transition: color .3s ease;
            transition: color .3s ease;
            text-transform: uppercase
        }

        .card .card-action a:not(.btn):not(.btn-large):not(.btn-large):not(.btn-floating):hover {
            color: #ffd8a6
        }

        .card .card-reveal {
            padding: 24px;
            position: absolute;
            background-color: #fff;
            width: 100%;
            overflow-y: auto;
            left: 0;
            top: 100%;
            height: 100%;
            z-index: 3;
            display: none
        }

        .card .card-reveal .card-title {
            cursor: pointer;
            display: block
        }

        #toast-container {
            display: block;
            position: fixed;
            z-index: 10000
        }

        @media only screen and (max-width: 600px) {
            #toast-container {
                min-width: 100%;
                bottom: 0%
            }
        }

        @media only screen and (min-width: 601px) and (max-width: 992px) {
            #toast-container {
                left: 5%;
                bottom: 7%;
                max-width: 90%
            }
        }

        @media only screen and (min-width: 993px) {
            #toast-container {
                top: 10%;
                right: 7%;
                max-width: 86%
            }
        }

        .toast {
            border-radius: 2px;
            top: 35px;
            width: auto;
            clear: both;
            margin-top: 10px;
            position: relative;
            max-width: 100%;
            height: auto;
            min-height: 48px;
            line-height: 1.5em;
            word-break: break-all;
            background-color: #323232;
            padding: 10px 25px;
            font-size: 1.1rem;
            font-weight: 300;
            color: #fff;
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -webkit-align-items: center;
            -ms-flex-align: center;
            align-items: center;
            -webkit-box-pack: justify;
            -webkit-justify-content: space-between;
            -ms-flex-pack: justify;
            justify-content: space-between
        }

        .toast .btn, .toast .btn-large, .toast .btn-flat {
            margin: 0;
            margin-left: 3rem
        }

        .toast.rounded {
            border-radius: 24px
        }

        @media only screen and (max-width: 600px) {
            .toast {
                width: 100%;
                border-radius: 0
            }
        }

        @media only screen and (min-width: 601px) and (max-width: 992px) {
            .toast {
                float: left
            }
        }

        @media only screen and (min-width: 993px) {
            .toast {
                float: right
            }
        }

        .tabs {
            position: relative;
            overflow-x: auto;
            overflow-y: hidden;
            height: 48px;
            width: 100%;
            background-color: #fff;
            margin: 0 auto;
            white-space: nowrap
        }

        .tabs.tabs-transparent {
            background-color: transparent
        }

        .tabs.tabs-transparent .tab a, .tabs.tabs-transparent .tab.disabled a, .tabs.tabs-transparent .tab.disabled a:hover {
            color: rgba(255, 255, 255, 0.7)
        }

        .tabs.tabs-transparent .tab a:hover, .tabs.tabs-transparent .tab a.active {
            color: #fff
        }

        .tabs.tabs-transparent .indicator {
            background-color: #fff
        }

        .tabs.tabs-fixed-width {
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex
        }

        .tabs.tabs-fixed-width .tab {
            -webkit-box-flex: 1;
            -webkit-flex-grow: 1;
            -ms-flex-positive: 1;
            flex-grow: 1
        }

        .tabs .tab {
            display: inline-block;
            text-align: center;
            line-height: 48px;
            height: 48px;
            padding: 0;
            margin: 0;
            text-transform: uppercase
        }

        .tabs .tab a {
            color: rgba(238, 110, 115, 0.7);
            display: block;
            width: 100%;
            height: 100%;
            padding: 0 24px;
            font-size: 14px;
            text-overflow: ellipsis;
            overflow: hidden;
            -webkit-transition: color .28s ease;
            transition: color .28s ease
        }

        .tabs .tab a:hover, .tabs .tab a.active {
            background-color: transparent;
            color: #ee6e73
        }

        .tabs .tab.disabled a, .tabs .tab.disabled a:hover {
            color: rgba(238, 110, 115, 0.7);
            cursor: default
        }

        .tabs .indicator {
            position: absolute;
            bottom: 0;
            height: 2px;
            background-color: #f6b2b5;
            will-change: left, right
        }

        @media only screen and (max-width: 992px) {
            .tabs {
                display: -webkit-box;
                display: -webkit-flex;
                display: -ms-flexbox;
                display: flex
            }

            .tabs .tab {
                -webkit-box-flex: 1;
                -webkit-flex-grow: 1;
                -ms-flex-positive: 1;
                flex-grow: 1
            }

            .tabs .tab a {
                padding: 0 12px
            }
        }

        .material-tooltip {
            padding: 10px 8px;
            font-size: 1rem;
            z-index: 2000;
            background-color: transparent;
            border-radius: 2px;
            color: #fff;
            min-height: 36px;
            line-height: 120%;
            opacity: 0;
            position: absolute;
            text-align: center;
            max-width: calc(100% - 4px);
            overflow: hidden;
            left: 0;
            top: 0;
            pointer-events: none;
            visibility: hidden
        }

        .backdrop {
            position: absolute;
            opacity: 0;
            height: 7px;
            width: 14px;
            border-radius: 0 0 50% 50%;
            background-color: #323232;
            z-index: -1;
            -webkit-transform-origin: 50% 0%;
            transform-origin: 50% 0%;
            visibility: hidden
        }

        .btn, .btn-large, .btn-flat {
            border: none;
            border-radius: 2px;
            display: inline-block;
            height: 36px;
            line-height: 36px;
            padding: 0 2rem;
            text-transform: uppercase;
            vertical-align: middle;
            -webkit-tap-highlight-color: transparent
        }

        .btn.disabled, .disabled.btn-large, .btn-floating.disabled, .btn-large.disabled, .btn-flat.disabled, .btn:disabled, .btn-large:disabled, .btn-floating:disabled, .btn-large:disabled, .btn-flat:disabled, .btn[disabled], [disabled].btn-large, .btn-floating[disabled], .btn-large[disabled], .btn-flat[disabled] {
            pointer-events: none;
            background-color: #DFDFDF !important;
            -webkit-box-shadow: none;
            box-shadow: none;
            color: #9F9F9F !important;
            cursor: default
        }

        .btn.disabled:hover, .disabled.btn-large:hover, .btn-floating.disabled:hover, .btn-large.disabled:hover, .btn-flat.disabled:hover, .btn:disabled:hover, .btn-large:disabled:hover, .btn-floating:disabled:hover, .btn-large:disabled:hover, .btn-flat:disabled:hover, .btn[disabled]:hover, [disabled].btn-large:hover, .btn-floating[disabled]:hover, .btn-large[disabled]:hover, .btn-flat[disabled]:hover {
            background-color: #DFDFDF !important;
            color: #9F9F9F !important
        }

        .btn, .btn-large, .btn-floating, .btn-large, .btn-flat {
            font-size: 1rem;
            outline: 0
        }

        .btn i, .btn-large i, .btn-floating i, .btn-large i, .btn-flat i {
            font-size: 1.3rem;
            line-height: inherit
        }

        .btn:focus, .btn-large:focus, .btn-floating:focus {
            background-color: #1d7d74
        }

        .btn, .btn-large {
            text-decoration: none;
            color: #fff;
            background-color: #26a69a;
            text-align: center;
            letter-spacing: .5px;
            -webkit-transition: .2s ease-out;
            transition: .2s ease-out;
            cursor: pointer
        }

        .btn:hover, .btn-large:hover {
            background-color: #2bbbad
        }

        .btn-floating {
            display: inline-block;
            color: #fff;
            position: relative;
            overflow: hidden;
            z-index: 1;
            width: 40px;
            height: 40px;
            line-height: 40px;
            padding: 0;
            background-color: #26a69a;
            border-radius: 50%;
            -webkit-transition: .3s;
            transition: .3s;
            cursor: pointer;
            vertical-align: middle
        }

        .btn-floating:hover {
            background-color: #26a69a
        }

        .btn-floating:before {
            border-radius: 0
        }

        .btn-floating.btn-large {
            width: 56px;
            height: 56px
        }

        .btn-floating.btn-large.halfway-fab {
            bottom: -28px
        }

        .btn-floating.btn-large i {
            line-height: 56px
        }

        .btn-floating.halfway-fab {
            position: absolute;
            right: 24px;
            bottom: -20px
        }

        .btn-floating.halfway-fab.left {
            right: auto;
            left: 24px
        }

        .btn-floating i {
            width: inherit;
            display: inline-block;
            text-align: center;
            color: #fff;
            font-size: 1.6rem;
            line-height: 40px
        }

        button.btn-floating {
            border: none
        }

        .fixed-action-btn {
            position: fixed;
            right: 23px;
            bottom: 23px;
            padding-top: 15px;
            margin-bottom: 0;
            z-index: 998
        }

        .fixed-action-btn.active ul {
            visibility: visible
        }

        .fixed-action-btn.horizontal {
            padding: 0 0 0 15px
        }

        .fixed-action-btn.horizontal ul {
            text-align: right;
            right: 64px;
            top: 50%;
            -webkit-transform: translateY(-50%);
            transform: translateY(-50%);
            height: 100%;
            left: auto;
            width: 500px
        }

        .fixed-action-btn.horizontal ul li {
            display: inline-block;
            margin: 15px 15px 0 0
        }

        .fixed-action-btn.toolbar {
            padding: 0;
            height: 56px
        }

        .fixed-action-btn.toolbar.active > a i {
            opacity: 0
        }

        .fixed-action-btn.toolbar ul {
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex;
            top: 0;
            bottom: 0;
            z-index: 1
        }

        .fixed-action-btn.toolbar ul li {
            -webkit-box-flex: 1;
            -webkit-flex: 1;
            -ms-flex: 1;
            flex: 1;
            display: inline-block;
            margin: 0;
            height: 100%;
            -webkit-transition: none;
            transition: none
        }

        .fixed-action-btn.toolbar ul li a {
            display: block;
            overflow: hidden;
            position: relative;
            width: 100%;
            height: 100%;
            background-color: transparent;
            -webkit-box-shadow: none;
            box-shadow: none;
            color: #fff;
            line-height: 56px;
            z-index: 1
        }

        .fixed-action-btn.toolbar ul li a i {
            line-height: inherit
        }

        .fixed-action-btn ul {
            left: 0;
            right: 0;
            text-align: center;
            position: absolute;
            bottom: 64px;
            margin: 0;
            visibility: hidden
        }

        .fixed-action-btn ul li {
            margin-bottom: 15px
        }

        .fixed-action-btn ul a.btn-floating {
            opacity: 0
        }

        .fixed-action-btn .fab-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            z-index: -1;
            width: 40px;
            height: 40px;
            background-color: #26a69a;
            border-radius: 50%;
            -webkit-transform: scale(0);
            transform: scale(0)
        }

        .btn-flat {
            -webkit-box-shadow: none;
            box-shadow: none;
            background-color: transparent;
            color: #343434;
            cursor: pointer;
            -webkit-transition: background-color .2s;
            transition: background-color .2s
        }

        .btn-flat:focus, .btn-flat:hover {
            -webkit-box-shadow: none;
            box-shadow: none
        }

        .btn-flat:focus {
            background-color: rgba(0, 0, 0, 0.1)
        }

        .btn-flat.disabled {
            background-color: transparent !important;
            color: #b3b2b2 !important;
            cursor: default
        }

        .btn-large {
            height: 54px;
            line-height: 54px
        }

        .btn-large i {
            font-size: 1.6rem
        }

        .btn-block {
            display: block
        }

        .dropdown-content {
            background-color: #fff;
            margin: 0;
            display: none;
            min-width: 100px;
            max-height: 650px;
            overflow-y: auto;
            opacity: 0;
            position: absolute;
            z-index: 999;
            will-change: width, height
        }

        .dropdown-content li {
            clear: both;
            color: rgba(0, 0, 0, 0.87);
            cursor: pointer;
            min-height: 50px;
            line-height: 1.5rem;
            width: 100%;
            text-align: left;
            text-transform: none
        }

        .dropdown-content li:hover, .dropdown-content li.active, .dropdown-content li.selected {
            background-color: #eee
        }

        .dropdown-content li.active.selected {
            background-color: #e1e1e1
        }

        .dropdown-content li.divider {
            min-height: 0;
            height: 1px
        }

        .dropdown-content li > a, .dropdown-content li > span {
            font-size: 16px;
            color: #26a69a;
            display: block;
            line-height: 22px;
            padding: 14px 16px
        }

        .dropdown-content li > span > label {
            top: 1px;
            left: 0;
            height: 18px
        }

        .dropdown-content li > a > i {
            height: inherit;
            line-height: inherit;
            float: left;
            margin: 0 24px 0 0;
            width: 24px
        }

        .input-field.col .dropdown-content [type="checkbox"] + label {
            top: 1px;
            left: 0;
            height: 18px
        }

        .waves-effect {
            position: relative;
            cursor: pointer;
            display: inline-block;
            overflow: hidden;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
            vertical-align: middle;
            z-index: 1;
            -webkit-transition: .3s ease-out;
            transition: .3s ease-out
        }

        .waves-effect .waves-ripple {
            position: absolute;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            margin-top: -10px;
            margin-left: -10px;
            opacity: 0;
            background: rgba(0, 0, 0, 0.2);
            -webkit-transition: all 0.7s ease-out;
            transition: all 0.7s ease-out;
            -webkit-transition-property: opacity, -webkit-transform;
            transition-property: opacity, -webkit-transform;
            transition-property: transform, opacity;
            transition-property: transform, opacity, -webkit-transform;
            -webkit-transform: scale(0);
            transform: scale(0);
            pointer-events: none
        }

        .waves-effect.waves-light .waves-ripple {
            background-color: rgba(255, 255, 255, 0.45)
        }

        .waves-effect.waves-red .waves-ripple {
            background-color: rgba(244, 67, 54, 0.7)
        }

        .waves-effect.waves-yellow .waves-ripple {
            background-color: rgba(255, 235, 59, 0.7)
        }

        .waves-effect.waves-orange .waves-ripple {
            background-color: rgba(255, 152, 0, 0.7)
        }

        .waves-effect.waves-purple .waves-ripple {
            background-color: rgba(156, 39, 176, 0.7)
        }

        .waves-effect.waves-green .waves-ripple {
            background-color: rgba(76, 175, 80, 0.7)
        }

        .waves-effect.waves-teal .waves-ripple {
            background-color: rgba(0, 150, 136, 0.7)
        }

        .waves-effect input[type="button"], .waves-effect input[type="reset"], .waves-effect input[type="submit"] {
            border: 0;
            font-style: normal;
            font-size: inherit;
            text-transform: inherit;
            background: none
        }

        .waves-effect img {
            position: relative;
            z-index: -1
        }

        .waves-notransition {
            -webkit-transition: none !important;
            transition: none !important
        }

        .waves-circle {
            -webkit-transform: translateZ(0);
            transform: translateZ(0);
            -webkit-mask-image: -webkit-radial-gradient(circle, white 100%, black 100%)
        }

        .waves-input-wrapper {
            border-radius: 0.2em;
            vertical-align: bottom
        }

        .waves-input-wrapper .waves-button-input {
            position: relative;
            top: 0;
            left: 0;
            z-index: 1
        }

        .waves-circle {
            text-align: center;
            width: 2.5em;
            height: 2.5em;
            line-height: 2.5em;
            border-radius: 50%;
            -webkit-mask-image: none
        }

        .waves-block {
            display: block
        }

        .waves-effect .waves-ripple {
            z-index: -1
        }

        .modal {
            display: none;
            position: fixed;
            left: 0;
            right: 0;
            background-color: #fafafa;
            padding: 0;
            max-height: 70%;
            width: 55%;
            margin: auto;
            overflow-y: auto;
            border-radius: 2px;
            will-change: top, opacity
        }

        @media only screen and (max-width: 992px) {
            .modal {
                width: 80%
            }
        }

        .modal h1, .modal h2, .modal h3, .modal h4 {
            margin-top: 0
        }

        .modal .modal-content {
            padding: 24px
        }

        .modal .modal-close {
            cursor: pointer
        }

        .modal .modal-footer {
            border-radius: 0 0 2px 2px;
            background-color: #fafafa;
            padding: 4px 6px;
            height: 56px;
            width: 100%;
            text-align: right
        }

        .modal .modal-footer .btn, .modal .modal-footer .btn-large, .modal .modal-footer .btn-flat {
            margin: 6px 0
        }

        .modal-overlay {
            position: fixed;
            z-index: 999;
            top: -25%;
            left: 0;
            bottom: 0;
            right: 0;
            height: 125%;
            width: 100%;
            background: #000;
            display: none;
            will-change: opacity
        }

        .modal.modal-fixed-footer {
            padding: 0;
            height: 70%
        }

        .modal.modal-fixed-footer .modal-content {
            position: absolute;
            height: calc(100% - 56px);
            max-height: 100%;
            width: 100%;
            overflow-y: auto
        }

        .modal.modal-fixed-footer .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            position: absolute;
            bottom: 0
        }

        .modal.bottom-sheet {
            top: auto;
            bottom: -100%;
            margin: 0;
            width: 100%;
            max-height: 45%;
            border-radius: 0;
            will-change: bottom, opacity
        }

        .collapsible {
            border-top: 1px solid #ddd;
            border-right: 1px solid #ddd;
            border-left: 1px solid #ddd;
            margin: .5rem 0 1rem 0
        }

        .collapsible-header {
            display: block;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            min-height: 3rem;
            line-height: 3rem;
            padding: 0 1rem;
            background-color: #fff;
            border-bottom: 1px solid #ddd
        }

        .collapsible-header i {
            width: 2rem;
            font-size: 1.6rem;
            line-height: 3rem;
            display: block;
            float: left;
            text-align: center;
            margin-right: 1rem
        }

        .collapsible-body {
            display: none;
            border-bottom: 1px solid #ddd;
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
            padding: 2rem
        }

        .side-nav .collapsible, .side-nav.fixed .collapsible {
            border: none;
            -webkit-box-shadow: none;
            box-shadow: none
        }

        .side-nav .collapsible li, .side-nav.fixed .collapsible li {
            padding: 0
        }

        .side-nav .collapsible-header, .side-nav.fixed .collapsible-header {
            background-color: transparent;
            border: none;
            line-height: inherit;
            height: inherit;
            padding: 0 16px
        }

        .side-nav .collapsible-header:hover, .side-nav.fixed .collapsible-header:hover {
            background-color: rgba(0, 0, 0, 0.05)
        }

        .side-nav .collapsible-header i, .side-nav.fixed .collapsible-header i {
            line-height: inherit
        }

        .side-nav .collapsible-body, .side-nav.fixed .collapsible-body {
            border: 0;
            background-color: #fff
        }

        .side-nav .collapsible-body li a, .side-nav.fixed .collapsible-body li a {
            padding: 0 23.5px 0 31px
        }

        .collapsible.popout {
            border: none;
            -webkit-box-shadow: none;
            box-shadow: none
        }

        .collapsible.popout > li {
            -webkit-box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.16), 0 2px 10px 0 rgba(0, 0, 0, 0.12);
            box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.16), 0 2px 10px 0 rgba(0, 0, 0, 0.12);
            margin: 0 24px;
            -webkit-transition: margin 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            transition: margin 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94)
        }

        .collapsible.popout > li.active {
            -webkit-box-shadow: 0 5px 11px 0 rgba(0, 0, 0, 0.18), 0 4px 15px 0 rgba(0, 0, 0, 0.15);
            box-shadow: 0 5px 11px 0 rgba(0, 0, 0, 0.18), 0 4px 15px 0 rgba(0, 0, 0, 0.15);
            margin: 16px 0
        }

        .chip {
            display: inline-block;
            height: 32px;
            font-size: 13px;
            font-weight: 500;
            color: rgba(0, 0, 0, 0.6);
            line-height: 32px;
            padding: 0 12px;
            border-radius: 16px;
            background-color: #e4e4e4;
            margin-bottom: 5px;
            margin-right: 5px
        }

        .chip > img {
            float: left;
            margin: 0 8px 0 -12px;
            height: 32px;
            width: 32px;
            border-radius: 50%
        }

        .chip .close {
            cursor: pointer;
            float: right;
            font-size: 16px;
            line-height: 32px;
            padding-left: 8px
        }

        .chips {
            border: none;
            border-bottom: 1px solid #9e9e9e;
            -webkit-box-shadow: none;
            box-shadow: none;
            margin: 0 0 20px 0;
            min-height: 45px;
            outline: none;
            -webkit-transition: all .3s;
            transition: all .3s
        }

        .chips.focus {
            border-bottom: 1px solid #26a69a;
            -webkit-box-shadow: 0 1px 0 0 #26a69a;
            box-shadow: 0 1px 0 0 #26a69a
        }

        .chips:hover {
            cursor: text
        }

        .chips .chip.selected {
            background-color: #26a69a;
            color: #fff
        }

        .chips .input {
            background: none;
            border: 0;
            color: rgba(0, 0, 0, 0.6);
            display: inline-block;
            font-size: 1rem;
            height: 3rem;
            line-height: 32px;
            outline: 0;
            margin: 0;
            padding: 0 !important;
            width: 120px !important
        }

        .chips .input:focus {
            border: 0 !important;
            -webkit-box-shadow: none !important;
            box-shadow: none !important
        }

        .chips .autocomplete-content {
            margin-top: 0
        }

        .prefix ~ .chips {
            margin-left: 3rem;
            width: 92%;
            width: calc(100% - 3rem)
        }

        .chips:empty ~ label {
            font-size: 0.8rem;
            -webkit-transform: translateY(-140%);
            transform: translateY(-140%)
        }

        .materialboxed {
            display: block;
            cursor: -webkit-zoom-in;
            cursor: zoom-in;
            position: relative;
            -webkit-transition: opacity .4s;
            transition: opacity .4s;
            -webkit-backface-visibility: hidden
        }

        .materialboxed:hover:not(.active) {
            opacity: .8
        }

        .materialboxed.active {
            cursor: -webkit-zoom-out;
            cursor: zoom-out
        }

        #materialbox-overlay {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background-color: #292929;
            z-index: 1000;
            will-change: opacity
        }

        .materialbox-caption {
            position: fixed;
            display: none;
            color: #fff;
            line-height: 50px;
            bottom: 0;
            left: 0;
            width: 100%;
            text-align: center;
            padding: 0% 15%;
            height: 50px;
            z-index: 1000;
            -webkit-font-smoothing: antialiased
        }

        select:focus {
            outline: 1px solid #c9f3ef
        }

        button:focus {
            outline: none;
            background-color: #2ab7a9
        }

        label {
            font-size: .8rem;
            color: #9e9e9e
        }

        ::-webkit-input-placeholder {
            color: #d1d1d1
        }

        :-moz-placeholder {
            color: #d1d1d1
        }

        ::-moz-placeholder {
            color: #d1d1d1
        }

        :-ms-input-placeholder {
            color: #d1d1d1
        }

        input:not([type]), input[type=text]:not(.browser-default), input[type=password]:not(.browser-default), input[type=email]:not(.browser-default), input[type=url]:not(.browser-default), input[type=time]:not(.browser-default), input[type=date]:not(.browser-default), input[type=datetime]:not(.browser-default), input[type=datetime-local]:not(.browser-default), input[type=tel]:not(.browser-default), input[type=number]:not(.browser-default), input[type=search]:not(.browser-default), textarea.materialize-textarea {
            background-color: transparent;
            border: none;
            border-bottom: 1px solid #9e9e9e;
            border-radius: 0;
            outline: none;
            height: 3rem;
            width: 100%;
            font-size: 1rem;
            margin: 0 0 20px 0;
            padding: 0;
            -webkit-box-shadow: none;
            box-shadow: none;
            -webkit-box-sizing: content-box;
            box-sizing: content-box;
            -webkit-transition: all 0.3s;
            transition: all 0.3s
        }

        input:not([type]):disabled, input:not([type])[readonly="readonly"], input[type=text]:not(.browser-default):disabled, input[type=text]:not(.browser-default)[readonly="readonly"], input[type=password]:not(.browser-default):disabled, input[type=password]:not(.browser-default)[readonly="readonly"], input[type=email]:not(.browser-default):disabled, input[type=email]:not(.browser-default)[readonly="readonly"], input[type=url]:not(.browser-default):disabled, input[type=url]:not(.browser-default)[readonly="readonly"], input[type=time]:not(.browser-default):disabled, input[type=time]:not(.browser-default)[readonly="readonly"], input[type=date]:not(.browser-default):disabled, input[type=date]:not(.browser-default)[readonly="readonly"], input[type=datetime]:not(.browser-default):disabled, input[type=datetime]:not(.browser-default)[readonly="readonly"], input[type=datetime-local]:not(.browser-default):disabled, input[type=datetime-local]:not(.browser-default)[readonly="readonly"], input[type=tel]:not(.browser-default):disabled, input[type=tel]:not(.browser-default)[readonly="readonly"], input[type=number]:not(.browser-default):disabled, input[type=number]:not(.browser-default)[readonly="readonly"], input[type=search]:not(.browser-default):disabled, input[type=search]:not(.browser-default)[readonly="readonly"], textarea.materialize-textarea:disabled, textarea.materialize-textarea[readonly="readonly"] {
            color: rgba(0, 0, 0, 0.26);
            border-bottom: 1px dotted rgba(0, 0, 0, 0.26)
        }

        input:not([type]):disabled + label, input:not([type])[readonly="readonly"] + label, input[type=text]:not(.browser-default):disabled + label, input[type=text]:not(.browser-default)[readonly="readonly"] + label, input[type=password]:not(.browser-default):disabled + label, input[type=password]:not(.browser-default)[readonly="readonly"] + label, input[type=email]:not(.browser-default):disabled + label, input[type=email]:not(.browser-default)[readonly="readonly"] + label, input[type=url]:not(.browser-default):disabled + label, input[type=url]:not(.browser-default)[readonly="readonly"] + label, input[type=time]:not(.browser-default):disabled + label, input[type=time]:not(.browser-default)[readonly="readonly"] + label, input[type=date]:not(.browser-default):disabled + label, input[type=date]:not(.browser-default)[readonly="readonly"] + label, input[type=datetime]:not(.browser-default):disabled + label, input[type=datetime]:not(.browser-default)[readonly="readonly"] + label, input[type=datetime-local]:not(.browser-default):disabled + label, input[type=datetime-local]:not(.browser-default)[readonly="readonly"] + label, input[type=tel]:not(.browser-default):disabled + label, input[type=tel]:not(.browser-default)[readonly="readonly"] + label, input[type=number]:not(.browser-default):disabled + label, input[type=number]:not(.browser-default)[readonly="readonly"] + label, input[type=search]:not(.browser-default):disabled + label, input[type=search]:not(.browser-default)[readonly="readonly"] + label, textarea.materialize-textarea:disabled + label, textarea.materialize-textarea[readonly="readonly"] + label {
            color: rgba(0, 0, 0, 0.26)
        }

        input:not([type]):focus:not([readonly]), input[type=text]:not(.browser-default):focus:not([readonly]), input[type=password]:not(.browser-default):focus:not([readonly]), input[type=email]:not(.browser-default):focus:not([readonly]), input[type=url]:not(.browser-default):focus:not([readonly]), input[type=time]:not(.browser-default):focus:not([readonly]), input[type=date]:not(.browser-default):focus:not([readonly]), input[type=datetime]:not(.browser-default):focus:not([readonly]), input[type=datetime-local]:not(.browser-default):focus:not([readonly]), input[type=tel]:not(.browser-default):focus:not([readonly]), input[type=number]:not(.browser-default):focus:not([readonly]), input[type=search]:not(.browser-default):focus:not([readonly]), textarea.materialize-textarea:focus:not([readonly]) {
            border-bottom: 1px solid #26a69a;
            -webkit-box-shadow: 0 1px 0 0 #26a69a;
            box-shadow: 0 1px 0 0 #26a69a
        }

        input:not([type]):focus:not([readonly]) + label, input[type=text]:not(.browser-default):focus:not([readonly]) + label, input[type=password]:not(.browser-default):focus:not([readonly]) + label, input[type=email]:not(.browser-default):focus:not([readonly]) + label, input[type=url]:not(.browser-default):focus:not([readonly]) + label, input[type=time]:not(.browser-default):focus:not([readonly]) + label, input[type=date]:not(.browser-default):focus:not([readonly]) + label, input[type=datetime]:not(.browser-default):focus:not([readonly]) + label, input[type=datetime-local]:not(.browser-default):focus:not([readonly]) + label, input[type=tel]:not(.browser-default):focus:not([readonly]) + label, input[type=number]:not(.browser-default):focus:not([readonly]) + label, input[type=search]:not(.browser-default):focus:not([readonly]) + label, textarea.materialize-textarea:focus:not([readonly]) + label {
            color: #26a69a
        }

        input:not([type]).valid, input:not([type]):focus.valid, input[type=text]:not(.browser-default).valid, input[type=text]:not(.browser-default):focus.valid, input[type=password]:not(.browser-default).valid, input[type=password]:not(.browser-default):focus.valid, input[type=email]:not(.browser-default).valid, input[type=email]:not(.browser-default):focus.valid, input[type=url]:not(.browser-default).valid, input[type=url]:not(.browser-default):focus.valid, input[type=time]:not(.browser-default).valid, input[type=time]:not(.browser-default):focus.valid, input[type=date]:not(.browser-default).valid, input[type=date]:not(.browser-default):focus.valid, input[type=datetime]:not(.browser-default).valid, input[type=datetime]:not(.browser-default):focus.valid, input[type=datetime-local]:not(.browser-default).valid, input[type=datetime-local]:not(.browser-default):focus.valid, input[type=tel]:not(.browser-default).valid, input[type=tel]:not(.browser-default):focus.valid, input[type=number]:not(.browser-default).valid, input[type=number]:not(.browser-default):focus.valid, input[type=search]:not(.browser-default).valid, input[type=search]:not(.browser-default):focus.valid, textarea.materialize-textarea.valid, textarea.materialize-textarea:focus.valid {
            border-bottom: 1px solid #4CAF50;
            -webkit-box-shadow: 0 1px 0 0 #4CAF50;
            box-shadow: 0 1px 0 0 #4CAF50
        }

        input:not([type]).valid + label:after, input:not([type]):focus.valid + label:after, input[type=text]:not(.browser-default).valid + label:after, input[type=text]:not(.browser-default):focus.valid + label:after, input[type=password]:not(.browser-default).valid + label:after, input[type=password]:not(.browser-default):focus.valid + label:after, input[type=email]:not(.browser-default).valid + label:after, input[type=email]:not(.browser-default):focus.valid + label:after, input[type=url]:not(.browser-default).valid + label:after, input[type=url]:not(.browser-default):focus.valid + label:after, input[type=time]:not(.browser-default).valid + label:after, input[type=time]:not(.browser-default):focus.valid + label:after, input[type=date]:not(.browser-default).valid + label:after, input[type=date]:not(.browser-default):focus.valid + label:after, input[type=datetime]:not(.browser-default).valid + label:after, input[type=datetime]:not(.browser-default):focus.valid + label:after, input[type=datetime-local]:not(.browser-default).valid + label:after, input[type=datetime-local]:not(.browser-default):focus.valid + label:after, input[type=tel]:not(.browser-default).valid + label:after, input[type=tel]:not(.browser-default):focus.valid + label:after, input[type=number]:not(.browser-default).valid + label:after, input[type=number]:not(.browser-default):focus.valid + label:after, input[type=search]:not(.browser-default).valid + label:after, input[type=search]:not(.browser-default):focus.valid + label:after, textarea.materialize-textarea.valid + label:after, textarea.materialize-textarea:focus.valid + label:after {
            content: attr(data-success);
            color: #4CAF50;
            opacity: 1
        }

        input:not([type]).invalid, input:not([type]):focus.invalid, input[type=text]:not(.browser-default).invalid, input[type=text]:not(.browser-default):focus.invalid, input[type=password]:not(.browser-default).invalid, input[type=password]:not(.browser-default):focus.invalid, input[type=email]:not(.browser-default).invalid, input[type=email]:not(.browser-default):focus.invalid, input[type=url]:not(.browser-default).invalid, input[type=url]:not(.browser-default):focus.invalid, input[type=time]:not(.browser-default).invalid, input[type=time]:not(.browser-default):focus.invalid, input[type=date]:not(.browser-default).invalid, input[type=date]:not(.browser-default):focus.invalid, input[type=datetime]:not(.browser-default).invalid, input[type=datetime]:not(.browser-default):focus.invalid, input[type=datetime-local]:not(.browser-default).invalid, input[type=datetime-local]:not(.browser-default):focus.invalid, input[type=tel]:not(.browser-default).invalid, input[type=tel]:not(.browser-default):focus.invalid, input[type=number]:not(.browser-default).invalid, input[type=number]:not(.browser-default):focus.invalid, input[type=search]:not(.browser-default).invalid, input[type=search]:not(.browser-default):focus.invalid, textarea.materialize-textarea.invalid, textarea.materialize-textarea:focus.invalid {
            border-bottom: 1px solid #F44336;
            -webkit-box-shadow: 0 1px 0 0 #F44336;
            box-shadow: 0 1px 0 0 #F44336
        }

        input:not([type]).invalid + label:after, input:not([type]):focus.invalid + label:after, input[type=text]:not(.browser-default).invalid + label:after, input[type=text]:not(.browser-default):focus.invalid + label:after, input[type=password]:not(.browser-default).invalid + label:after, input[type=password]:not(.browser-default):focus.invalid + label:after, input[type=email]:not(.browser-default).invalid + label:after, input[type=email]:not(.browser-default):focus.invalid + label:after, input[type=url]:not(.browser-default).invalid + label:after, input[type=url]:not(.browser-default):focus.invalid + label:after, input[type=time]:not(.browser-default).invalid + label:after, input[type=time]:not(.browser-default):focus.invalid + label:after, input[type=date]:not(.browser-default).invalid + label:after, input[type=date]:not(.browser-default):focus.invalid + label:after, input[type=datetime]:not(.browser-default).invalid + label:after, input[type=datetime]:not(.browser-default):focus.invalid + label:after, input[type=datetime-local]:not(.browser-default).invalid + label:after, input[type=datetime-local]:not(.browser-default):focus.invalid + label:after, input[type=tel]:not(.browser-default).invalid + label:after, input[type=tel]:not(.browser-default):focus.invalid + label:after, input[type=number]:not(.browser-default).invalid + label:after, input[type=number]:not(.browser-default):focus.invalid + label:after, input[type=search]:not(.browser-default).invalid + label:after, input[type=search]:not(.browser-default):focus.invalid + label:after, textarea.materialize-textarea.invalid + label:after, textarea.materialize-textarea:focus.invalid + label:after {
            content: attr(data-error);
            color: #F44336;
            opacity: 1
        }

        input:not([type]).validate + label, input[type=text]:not(.browser-default).validate + label, input[type=password]:not(.browser-default).validate + label, input[type=email]:not(.browser-default).validate + label, input[type=url]:not(.browser-default).validate + label, input[type=time]:not(.browser-default).validate + label, input[type=date]:not(.browser-default).validate + label, input[type=datetime]:not(.browser-default).validate + label, input[type=datetime-local]:not(.browser-default).validate + label, input[type=tel]:not(.browser-default).validate + label, input[type=number]:not(.browser-default).validate + label, input[type=search]:not(.browser-default).validate + label, textarea.materialize-textarea.validate + label {
            width: 100%;
            pointer-events: none
        }

        input:not([type]) + label:after, input[type=text]:not(.browser-default) + label:after, input[type=password]:not(.browser-default) + label:after, input[type=email]:not(.browser-default) + label:after, input[type=url]:not(.browser-default) + label:after, input[type=time]:not(.browser-default) + label:after, input[type=date]:not(.browser-default) + label:after, input[type=datetime]:not(.browser-default) + label:after, input[type=datetime-local]:not(.browser-default) + label:after, input[type=tel]:not(.browser-default) + label:after, input[type=number]:not(.browser-default) + label:after, input[type=search]:not(.browser-default) + label:after, textarea.materialize-textarea + label:after {
            display: block;
            content: "";
            position: absolute;
            top: 60px;
            left: 0;
            opacity: 0;
            -webkit-transition: .2s opacity ease-out, .2s color ease-out;
            transition: .2s opacity ease-out, .2s color ease-out
        }

        .input-field {
            position: relative;
            margin-top: 1rem
        }

        .input-field.inline {
            display: inline-block;
            vertical-align: middle;
            margin-left: 5px
        }

        .input-field.inline input, .input-field.inline .select-dropdown {
            margin-bottom: 1rem
        }

        .input-field.col label {
            left: .75rem
        }

        .input-field.col .prefix ~ label, .input-field.col .prefix ~ .validate ~ label {
            width: calc(100% - 3rem - 1.5rem)
        }

        .input-field label {
            color: #9e9e9e;
            position: absolute;
            top: 0.8rem;
            left: 0;
            font-size: 1rem;
            cursor: text;
            -webkit-transition: .2s ease-out;
            transition: .2s ease-out;
            text-align: initial
        }

        .input-field label:not(.label-icon).active {
            font-size: .8rem;
            -webkit-transform: translateY(-140%);
            transform: translateY(-140%)
        }

        .input-field .prefix {
            position: absolute;
            width: 3rem;
            font-size: 2rem;
            -webkit-transition: color .2s;
            transition: color .2s
        }

        .input-field .prefix.active {
            color: #26a69a
        }

        .input-field .prefix ~ input, .input-field .prefix ~ textarea, .input-field .prefix ~ label, .input-field .prefix ~ .validate ~ label, .input-field .prefix ~ .autocomplete-content {
            margin-left: 3rem;
            width: 92%;
            width: calc(100% - 3rem)
        }

        .input-field .prefix ~ label {
            margin-left: 3rem
        }

        @media only screen and (max-width: 992px) {
            .input-field .prefix ~ input {
                width: 86%;
                width: calc(100% - 3rem)
            }
        }

        @media only screen and (max-width: 600px) {
            .input-field .prefix ~ input {
                width: 80%;
                width: calc(100% - 3rem)
            }
        }

        .input-field input[type=search] {
            display: block;
            line-height: inherit;
            padding-left: 4rem;
            width: calc(100% - 4rem)
        }

        .input-field input[type=search]:focus {
            background-color: #fff;
            border: 0;
            -webkit-box-shadow: none;
            box-shadow: none;
            color: #444
        }

        .input-field input[type=search]:focus + label i, .input-field input[type=search]:focus ~ .mdi-navigation-close, .input-field input[type=search]:focus ~ .material-icons {
            color: #444
        }

        .input-field input[type=search] + label {
            left: 1rem
        }

        .input-field input[type=search] ~ .mdi-navigation-close, .input-field input[type=search] ~ .material-icons {
            position: absolute;
            top: 0;
            right: 1rem;
            color: transparent;
            cursor: pointer;
            font-size: 2rem;
            -webkit-transition: .3s color;
            transition: .3s color
        }

        textarea {
            width: 100%;
            height: 3rem;
            background-color: transparent
        }

        textarea.materialize-textarea {
            overflow-y: hidden;
            padding: .8rem 0 1.6rem 0;
            resize: none;
            min-height: 3rem
        }

        .hiddendiv {
            display: none;
            white-space: pre-wrap;
            word-wrap: break-word;
            overflow-wrap: break-word;
            padding-top: 1.2rem;
            position: absolute;
            top: 0
        }

        .autocomplete-content {
            margin-top: -20px;
            display: block;
            opacity: 1;
            position: static
        }

        .autocomplete-content li .highlight {
            color: #444
        }

        .autocomplete-content li img {
            height: 40px;
            width: 40px;
            margin: 5px 15px
        }

        [type="radio"]:not(:checked), [type="radio"]:checked {
            position: absolute;
            left: -9999px;
            opacity: 0
        }

        [type="radio"]:not(:checked) + label, [type="radio"]:checked + label {
            position: relative;
            padding-left: 35px;
            cursor: pointer;
            display: inline-block;
            height: 25px;
            line-height: 25px;
            font-size: 1rem;
            -webkit-transition: .28s ease;
            transition: .28s ease;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none
        }

        [type="radio"] + label:before, [type="radio"] + label:after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            margin: 4px;
            width: 16px;
            height: 16px;
            z-index: 0;
            -webkit-transition: .28s ease;
            transition: .28s ease
        }

        [type="radio"]:not(:checked) + label:before, [type="radio"]:not(:checked) + label:after, [type="radio"]:checked + label:before, [type="radio"]:checked + label:after, [type="radio"].with-gap:checked + label:before, [type="radio"].with-gap:checked + label:after {
            border-radius: 50%
        }

        [type="radio"]:not(:checked) + label:before, [type="radio"]:not(:checked) + label:after {
            border: 2px solid #5a5a5a
        }

        [type="radio"]:not(:checked) + label:after {
            -webkit-transform: scale(0);
            transform: scale(0)
        }

        [type="radio"]:checked + label:before {
            border: 2px solid transparent
        }

        [type="radio"]:checked + label:after, [type="radio"].with-gap:checked + label:before, [type="radio"].with-gap:checked + label:after {
            border: 2px solid #26a69a
        }

        [type="radio"]:checked + label:after, [type="radio"].with-gap:checked + label:after {
            background-color: #26a69a
        }

        [type="radio"]:checked + label:after {
            -webkit-transform: scale(1.02);
            transform: scale(1.02)
        }

        [type="radio"].with-gap:checked + label:after {
            -webkit-transform: scale(0.5);
            transform: scale(0.5)
        }

        [type="radio"].tabbed:focus + label:before {
            -webkit-box-shadow: 0 0 0 10px rgba(0, 0, 0, 0.1);
            box-shadow: 0 0 0 10px rgba(0, 0, 0, 0.1)
        }

        [type="radio"].with-gap:disabled:checked + label:before {
            border: 2px solid rgba(0, 0, 0, 0.26)
        }

        [type="radio"].with-gap:disabled:checked + label:after {
            border: none;
            background-color: rgba(0, 0, 0, 0.26)
        }

        [type="radio"]:disabled:not(:checked) + label:before, [type="radio"]:disabled:checked + label:before {
            background-color: transparent;
            border-color: rgba(0, 0, 0, 0.26)
        }

        [type="radio"]:disabled + label {
            color: rgba(0, 0, 0, 0.26)
        }

        [type="radio"]:disabled:not(:checked) + label:before {
            border-color: rgba(0, 0, 0, 0.26)
        }

        [type="radio"]:disabled:checked + label:after {
            background-color: rgba(0, 0, 0, 0.26);
            border-color: #BDBDBD
        }

        form p {
            margin-bottom: 10px;
            text-align: left
        }

        form p:last-child {
            margin-bottom: 0
        }

        [type="checkbox"]:not(:checked), [type="checkbox"]:checked {
            position: absolute;
            left: -9999px;
            opacity: 0
        }

        [type="checkbox"] + label {
            position: relative;
            padding-left: 35px;
            cursor: pointer;
            display: inline-block;
            height: 25px;
            line-height: 25px;
            font-size: 1rem;
            -webkit-user-select: none;
            -moz-user-select: none;
            -khtml-user-select: none;
            -ms-user-select: none
        }

        [type="checkbox"] + label:before, [type="checkbox"]:not(.filled-in) + label:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 18px;
            height: 18px;
            z-index: 0;
            border: 2px solid #5a5a5a;
            border-radius: 1px;
            margin-top: 2px;
            -webkit-transition: .2s;
            transition: .2s
        }

        [type="checkbox"]:not(.filled-in) + label:after {
            border: 0;
            -webkit-transform: scale(0);
            transform: scale(0)
        }

        [type="checkbox"]:not(:checked):disabled + label:before {
            border: none;
            background-color: rgba(0, 0, 0, 0.26)
        }

        [type="checkbox"].tabbed:focus + label:after {
            -webkit-transform: scale(1);
            transform: scale(1);
            border: 0;
            border-radius: 50%;
            -webkit-box-shadow: 0 0 0 10px rgba(0, 0, 0, 0.1);
            box-shadow: 0 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: rgba(0, 0, 0, 0.1)
        }

        [type="checkbox"]:checked + label:before {
            top: -4px;
            left: -5px;
            width: 12px;
            height: 22px;
            border-top: 2px solid transparent;
            border-left: 2px solid transparent;
            border-right: 2px solid #26a69a;
            border-bottom: 2px solid #26a69a;
            -webkit-transform: rotate(40deg);
            transform: rotate(40deg);
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            -webkit-transform-origin: 100% 100%;
            transform-origin: 100% 100%
        }

        [type="checkbox"]:checked:disabled + label:before {
            border-right: 2px solid rgba(0, 0, 0, 0.26);
            border-bottom: 2px solid rgba(0, 0, 0, 0.26)
        }

        [type="checkbox"]:indeterminate + label:before {
            top: -11px;
            left: -12px;
            width: 10px;
            height: 22px;
            border-top: none;
            border-left: none;
            border-right: 2px solid #26a69a;
            border-bottom: none;
            -webkit-transform: rotate(90deg);
            transform: rotate(90deg);
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            -webkit-transform-origin: 100% 100%;
            transform-origin: 100% 100%
        }

        [type="checkbox"]:indeterminate:disabled + label:before {
            border-right: 2px solid rgba(0, 0, 0, 0.26);
            background-color: transparent
        }

        [type="checkbox"].filled-in + label:after {
            border-radius: 2px
        }

        [type="checkbox"].filled-in + label:before, [type="checkbox"].filled-in + label:after {
            content: '';
            left: 0;
            position: absolute;
            -webkit-transition: border .25s, background-color .25s, width .20s .1s, height .20s .1s, top .20s .1s, left .20s .1s;
            transition: border .25s, background-color .25s, width .20s .1s, height .20s .1s, top .20s .1s, left .20s .1s;
            z-index: 1
        }

        [type="checkbox"].filled-in:not(:checked) + label:before {
            width: 0;
            height: 0;
            border: 3px solid transparent;
            left: 6px;
            top: 10px;
            -webkit-transform: rotateZ(37deg);
            transform: rotateZ(37deg);
            -webkit-transform-origin: 20% 40%;
            transform-origin: 100% 100%
        }

        [type="checkbox"].filled-in:not(:checked) + label:after {
            height: 20px;
            width: 20px;
            background-color: transparent;
            border: 2px solid #5a5a5a;
            top: 0px;
            z-index: 0
        }

        [type="checkbox"].filled-in:checked + label:before {
            top: 0;
            left: 1px;
            width: 8px;
            height: 13px;
            border-top: 2px solid transparent;
            border-left: 2px solid transparent;
            border-right: 2px solid #fff;
            border-bottom: 2px solid #fff;
            -webkit-transform: rotateZ(37deg);
            transform: rotateZ(37deg);
            -webkit-transform-origin: 100% 100%;
            transform-origin: 100% 100%
        }

        [type="checkbox"].filled-in:checked + label:after {
            top: 0;
            width: 20px;
            height: 20px;
            border: 2px solid #26a69a;
            background-color: #26a69a;
            z-index: 0
        }

        [type="checkbox"].filled-in.tabbed:focus + label:after {
            border-radius: 2px;
            border-color: #5a5a5a;
            background-color: rgba(0, 0, 0, 0.1)
        }

        [type="checkbox"].filled-in.tabbed:checked:focus + label:after {
            border-radius: 2px;
            background-color: #26a69a;
            border-color: #26a69a
        }

        [type="checkbox"].filled-in:disabled:not(:checked) + label:before {
            background-color: transparent;
            border: 2px solid transparent
        }

        [type="checkbox"].filled-in:disabled:not(:checked) + label:after {
            border-color: transparent;
            background-color: #BDBDBD
        }

        [type="checkbox"].filled-in:disabled:checked + label:before {
            background-color: transparent
        }

        [type="checkbox"].filled-in:disabled:checked + label:after {
            background-color: #BDBDBD;
            border-color: #BDBDBD
        }

        .switch, .switch * {
            -webkit-user-select: none;
            -moz-user-select: none;
            -khtml-user-select: none;
            -ms-user-select: none
        }

        .switch label {
            cursor: pointer
        }

        .switch label input[type=checkbox] {
            opacity: 0;
            width: 0;
            height: 0
        }

        .switch label input[type=checkbox]:checked + .lever {
            background-color: #84c7c1
        }

        .switch label input[type=checkbox]:checked + .lever:before, .switch label input[type=checkbox]:checked + .lever:after {
            left: 18px
        }

        .switch label input[type=checkbox]:checked + .lever:after {
            background-color: #26a69a
        }

        .switch label .lever {
            content: "";
            display: inline-block;
            position: relative;
            width: 36px;
            height: 14px;
            background-color: rgba(0, 0, 0, 0.38);
            border-radius: 15px;
            margin-right: 10px;
            -webkit-transition: background 0.3s ease;
            transition: background 0.3s ease;
            vertical-align: middle;
            margin: 0 16px
        }

        .switch label .lever:before, .switch label .lever:after {
            content: "";
            position: absolute;
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            left: 0;
            top: -3px;
            -webkit-transition: left 0.3s ease, background .3s ease, -webkit-box-shadow 0.1s ease, -webkit-transform .1s ease;
            transition: left 0.3s ease, background .3s ease, -webkit-box-shadow 0.1s ease, -webkit-transform .1s ease;
            transition: left 0.3s ease, background .3s ease, box-shadow 0.1s ease, transform .1s ease;
            transition: left 0.3s ease, background .3s ease, box-shadow 0.1s ease, transform .1s ease, -webkit-box-shadow 0.1s ease, -webkit-transform .1s ease
        }

        .switch label .lever:before {
            background-color: rgba(38, 166, 154, 0.15)
        }

        .switch label .lever:after {
            background-color: #F1F1F1;
            -webkit-box-shadow: 0px 3px 1px -2px rgba(0, 0, 0, 0.2), 0px 2px 2px 0px rgba(0, 0, 0, 0.14), 0px 1px 5px 0px rgba(0, 0, 0, 0.12);
            box-shadow: 0px 3px 1px -2px rgba(0, 0, 0, 0.2), 0px 2px 2px 0px rgba(0, 0, 0, 0.14), 0px 1px 5px 0px rgba(0, 0, 0, 0.12)
        }

        input[type=checkbox]:checked:not(:disabled) ~ .lever:active::before, input[type=checkbox]:checked:not(:disabled).tabbed:focus ~ .lever::before {
            -webkit-transform: scale(2.4);
            transform: scale(2.4);
            background-color: rgba(38, 166, 154, 0.15)
        }

        input[type=checkbox]:not(:disabled) ~ .lever:active:before, input[type=checkbox]:not(:disabled).tabbed:focus ~ .lever::before {
            -webkit-transform: scale(2.4);
            transform: scale(2.4);
            background-color: rgba(0, 0, 0, 0.08)
        }

        .switch input[type=checkbox][disabled] + .lever {
            cursor: default;
            background-color: rgba(0, 0, 0, 0.12)
        }

        .switch label input[type=checkbox][disabled] + .lever:after, .switch label input[type=checkbox][disabled]:checked + .lever:after {
            background-color: #BDBDBD
        }

        select {
            display: none
        }

        select.browser-default {
            display: block
        }

        select {
            background-color: rgba(255, 255, 255, 0.9);
            width: 100%;
            padding: 5px;
            border: 1px solid #f2f2f2;
            border-radius: 2px;
            height: 3rem
        }

        .select-label {
            position: absolute
        }

        .select-wrapper {
            position: relative
        }

        .select-wrapper input.select-dropdown {
            position: relative;
            cursor: pointer;
            background-color: transparent;
            border: none;
            border-bottom: 1px solid #9e9e9e;
            outline: none;
            height: 3rem;
            line-height: 3rem;
            width: 100%;
            font-size: 1rem;
            margin: 0 0 20px 0;
            padding: 0;
            display: block
        }

        .select-wrapper span.caret {
            color: initial;
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            height: 10px;
            margin: auto 0;
            font-size: 10px;
            line-height: 10px
        }

        .select-wrapper span.caret.disabled {
            color: rgba(0, 0, 0, 0.26)
        }

        .select-wrapper + label {
            position: absolute;
            top: -14px;
            font-size: .8rem
        }

        select:disabled {
            color: rgba(0, 0, 0, 0.3)
        }

        .select-wrapper input.select-dropdown:disabled {
            color: rgba(0, 0, 0, 0.3);
            cursor: default;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            border-bottom: 1px solid rgba(0, 0, 0, 0.3)
        }

        .select-wrapper i {
            color: rgba(0, 0, 0, 0.3)
        }

        .select-dropdown li.disabled, .select-dropdown li.disabled > span, .select-dropdown li.optgroup {
            color: rgba(0, 0, 0, 0.3);
            background-color: transparent
        }

        .prefix ~ .select-wrapper {
            margin-left: 3rem;
            width: 92%;
            width: calc(100% - 3rem)
        }

        .prefix ~ label {
            margin-left: 3rem
        }

        .select-dropdown li img {
            height: 40px;
            width: 40px;
            margin: 5px 15px;
            float: right
        }

        .select-dropdown li.optgroup {
            border-top: 1px solid #eee
        }

        .select-dropdown li.optgroup.selected > span {
            color: rgba(0, 0, 0, 0.7)
        }

        .select-dropdown li.optgroup > span {
            color: rgba(0, 0, 0, 0.4)
        }

        .select-dropdown li.optgroup ~ li.optgroup-option {
            padding-left: 1rem
        }

        .file-field {
            position: relative
        }

        .file-field .file-path-wrapper {
            overflow: hidden;
            padding-left: 10px
        }

        .file-field input.file-path {
            width: 100%
        }

        .file-field .btn, .file-field .btn-large {
            float: left;
            height: 3rem;
            line-height: 3rem
        }

        .file-field span {
            cursor: pointer
        }

        .file-field input[type=file] {
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            bottom: 0;
            width: 100%;
            margin: 0;
            padding: 0;
            font-size: 20px;
            cursor: pointer;
            opacity: 0;
            filter: alpha(opacity=0)
        }

        .range-field {
            position: relative
        }

        input[type=range], input[type=range] + .thumb {
            cursor: pointer
        }

        input[type=range] {
            position: relative;
            background-color: transparent;
            border: none;
            outline: none;
            width: 100%;
            margin: 15px 0;
            padding: 0
        }

        input[type=range]:focus {
            outline: none
        }

        input[type=range] + .thumb {
            position: absolute;
            top: 10px;
            left: 0;
            border: none;
            height: 0;
            width: 0;
            border-radius: 50%;
            background-color: #26a69a;
            margin-left: 7px;
            -webkit-transform-origin: 50% 50%;
            transform-origin: 50% 50%;
            -webkit-transform: rotate(-45deg);
            transform: rotate(-45deg)
        }

        input[type=range] + .thumb .value {
            display: block;
            width: 30px;
            text-align: center;
            color: #26a69a;
            font-size: 0;
            -webkit-transform: rotate(45deg);
            transform: rotate(45deg)
        }

        input[type=range] + .thumb.active {
            border-radius: 50% 50% 50% 0
        }

        input[type=range] + .thumb.active .value {
            color: #fff;
            margin-left: -1px;
            margin-top: 8px;
            font-size: 10px
        }

        input[type=range] {
            -webkit-appearance: none
        }

        input[type=range]::-webkit-slider-runnable-track {
            height: 3px;
            background: #c2c0c2;
            border: none
        }

        input[type=range]::-webkit-slider-thumb {
            -webkit-appearance: none;
            border: none;
            height: 14px;
            width: 14px;
            border-radius: 50%;
            background-color: #26a69a;
            -webkit-transform-origin: 50% 50%;
            transform-origin: 50% 50%;
            margin: -5px 0 0 0;
            -webkit-transition: .3s;
            transition: .3s
        }

        input[type=range]:focus::-webkit-slider-runnable-track {
            background: #ccc
        }

        input[type=range] {
            border: 1px solid white
        }

        input[type=range]::-moz-range-track {
            height: 3px;
            background: #ddd;
            border: none
        }

        input[type=range]::-moz-range-thumb {
            border: none;
            height: 14px;
            width: 14px;
            border-radius: 50%;
            background: #26a69a;
            margin-top: -5px
        }

        input[type=range]:-moz-focusring {
            outline: 1px solid #fff;
            outline-offset: -1px
        }

        input[type=range]:focus::-moz-range-track {
            background: #ccc
        }

        input[type=range]::-ms-track {
            height: 3px;
            background: transparent;
            border-color: transparent;
            border-width: 6px 0;
            color: transparent
        }

        input[type=range]::-ms-fill-lower {
            background: #777
        }

        input[type=range]::-ms-fill-upper {
            background: #ddd
        }

        input[type=range]::-ms-thumb {
            border: none;
            height: 14px;
            width: 14px;
            border-radius: 50%;
            background: #26a69a
        }

        input[type=range]:focus::-ms-fill-lower {
            background: #888
        }

        input[type=range]:focus::-ms-fill-upper {
            background: #ccc
        }

        .table-of-contents.fixed {
            position: fixed
        }

        .table-of-contents li {
            padding: 2px 0
        }

        .table-of-contents a {
            display: inline-block;
            font-weight: 300;
            color: #757575;
            padding-left: 20px;
            height: 1.5rem;
            line-height: 1.5rem;
            letter-spacing: .4;
            display: inline-block
        }

        .table-of-contents a:hover {
            color: #a8a8a8;
            padding-left: 19px;
            border-left: 1px solid #ee6e73
        }

        .table-of-contents a.active {
            font-weight: 500;
            padding-left: 18px;
            border-left: 2px solid #ee6e73
        }

        .side-nav {
            position: fixed;
            width: 300px;
            left: 0;
            top: 0;
            margin: 0;
            -webkit-transform: translateX(-100%);
            transform: translateX(-100%);
            height: 100%;
            height: calc(100% + 60px);
            height: -moz-calc(100%);
            padding-bottom: 60px;
            background-color: #fff;
            z-index: 999;
            overflow-y: auto;
            will-change: transform;
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            -webkit-transform: translateX(-105%);
            transform: translateX(-105%)
        }

        .side-nav.right-aligned {
            right: 0;
            -webkit-transform: translateX(105%);
            transform: translateX(105%);
            left: auto;
            -webkit-transform: translateX(100%);
            transform: translateX(100%)
        }

        .side-nav .collapsible {
            margin: 0
        }

        .side-nav li {
            float: none;
            line-height: 48px
        }

        .side-nav li.active {
            background-color: rgba(0, 0, 0, 0.05)
        }

        .side-nav li > a {
            color: rgba(0, 0, 0, 0.87);
            display: block;
            font-size: 14px;
            font-weight: 500;
            height: 48px;
            line-height: 48px;
            padding: 0 32px
        }

        .side-nav li > a:hover {
            background-color: rgba(0, 0, 0, 0.05)
        }

        .side-nav li > a.btn, .side-nav li > a.btn-large, .side-nav li > a.btn-large, .side-nav li > a.btn-flat, .side-nav li > a.btn-floating {
            margin: 10px 15px
        }

        .side-nav li > a.btn, .side-nav li > a.btn-large, .side-nav li > a.btn-large, .side-nav li > a.btn-floating {
            color: #fff
        }

        .side-nav li > a.btn-flat {
            color: #343434
        }

        .side-nav li > a.btn:hover, .side-nav li > a.btn-large:hover, .side-nav li > a.btn-large:hover {
            background-color: #2bbbad
        }

        .side-nav li > a.btn-floating:hover {
            background-color: #26a69a
        }

        .side-nav li > a > i, .side-nav li > a > [class^="mdi-"], .side-nav li > a li > a > [class*="mdi-"], .side-nav li > a > i.material-icons {
            float: left;
            height: 48px;
            line-height: 48px;
            margin: 0 32px 0 0;
            width: 24px;
            color: rgba(0, 0, 0, 0.54)
        }

        .side-nav .divider {
            margin: 8px 0 0 0
        }

        .side-nav .subheader {
            cursor: initial;
            pointer-events: none;
            color: rgba(0, 0, 0, 0.54);
            font-size: 14px;
            font-weight: 500;
            line-height: 48px
        }

        .side-nav .subheader:hover {
            background-color: transparent
        }

        .side-nav .user-view, .side-nav .userView {
            position: relative;
            padding: 32px 32px 0;
            margin-bottom: 8px
        }

        .side-nav .user-view > a, .side-nav .userView > a {
            height: auto;
            padding: 0
        }

        .side-nav .user-view > a:hover, .side-nav .userView > a:hover {
            background-color: transparent
        }

        .side-nav .user-view .background, .side-nav .userView .background {
            overflow: hidden;
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            z-index: -1
        }

        .side-nav .user-view .circle, .side-nav .user-view .name, .side-nav .user-view .email, .side-nav .userView .circle, .side-nav .userView .name, .side-nav .userView .email {
            display: block
        }

        .side-nav .user-view .circle, .side-nav .userView .circle {
            height: 64px;
            width: 64px
        }

        .side-nav .user-view .name, .side-nav .user-view .email, .side-nav .userView .name, .side-nav .userView .email {
            font-size: 14px;
            line-height: 24px
        }

        .side-nav .user-view .name, .side-nav .userView .name {
            margin-top: 16px;
            font-weight: 500
        }

        .side-nav .user-view .email, .side-nav .userView .email {
            padding-bottom: 16px;
            font-weight: 400
        }

        .drag-target {
            height: 100%;
            width: 10px;
            position: fixed;
            top: 0;
            z-index: 998
        }

        .side-nav.fixed {
            left: 0;
            -webkit-transform: translateX(0);
            transform: translateX(0);
            position: fixed
        }

        .side-nav.fixed.right-aligned {
            right: 0;
            left: auto
        }

        @media only screen and (max-width: 992px) {
            .side-nav.fixed {
                -webkit-transform: translateX(-105%);
                transform: translateX(-105%)
            }

            .side-nav.fixed.right-aligned {
                -webkit-transform: translateX(105%);
                transform: translateX(105%)
            }

            .side-nav a {
                padding: 0 16px
            }

            .side-nav .user-view, .side-nav .userView {
                padding: 16px 16px 0
            }
        }

        .side-nav .collapsible-body > ul:not(.collapsible) > li.active, .side-nav.fixed .collapsible-body > ul:not(.collapsible) > li.active {
            background-color: #ee6e73
        }

        .side-nav .collapsible-body > ul:not(.collapsible) > li.active a, .side-nav.fixed .collapsible-body > ul:not(.collapsible) > li.active a {
            color: #fff
        }

        .side-nav .collapsible-body {
            padding: 0
        }

        #sidenav-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 120vh;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 997;
            will-change: opacity
        }

        .preloader-wrapper {
            display: inline-block;
            position: relative;
            width: 50px;
            height: 50px
        }

        .preloader-wrapper.small {
            width: 36px;
            height: 36px
        }

        .preloader-wrapper.big {
            width: 64px;
            height: 64px
        }

        .preloader-wrapper.active {
            -webkit-animation: container-rotate 1568ms linear infinite;
            animation: container-rotate 1568ms linear infinite
        }

        @-webkit-keyframes container-rotate {
            to {
                -webkit-transform: rotate(360deg)
            }
        }

        @keyframes container-rotate {
            to {
                -webkit-transform: rotate(360deg);
                transform: rotate(360deg)
            }
        }

        .spinner-layer {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            border-color: #26a69a
        }

        .spinner-blue, .spinner-blue-only {
            border-color: #4285f4
        }

        .spinner-red, .spinner-red-only {
            border-color: #db4437
        }

        .spinner-yellow, .spinner-yellow-only {
            border-color: #f4b400
        }

        .spinner-green, .spinner-green-only {
            border-color: #0f9d58
        }

        .active .spinner-layer.spinner-blue {
            -webkit-animation: fill-unfill-rotate 5332ms cubic-bezier(0.4, 0, 0.2, 1) infinite both, blue-fade-in-out 5332ms cubic-bezier(0.4, 0, 0.2, 1) infinite both;
            animation: fill-unfill-rotate 5332ms cubic-bezier(0.4, 0, 0.2, 1) infinite both, blue-fade-in-out 5332ms cubic-bezier(0.4, 0, 0.2, 1) infinite both
        }

        .active .spinner-layer.spinner-red {
            -webkit-animation: fill-unfill-rotate 5332ms cubic-bezier(0.4, 0, 0.2, 1) infinite both, red-fade-in-out 5332ms cubic-bezier(0.4, 0, 0.2, 1) infinite both;
            animation: fill-unfill-rotate 5332ms cubic-bezier(0.4, 0, 0.2, 1) infinite both, red-fade-in-out 5332ms cubic-bezier(0.4, 0, 0.2, 1) infinite both
        }

        .active .spinner-layer.spinner-yellow {
            -webkit-animation: fill-unfill-rotate 5332ms cubic-bezier(0.4, 0, 0.2, 1) infinite both, yellow-fade-in-out 5332ms cubic-bezier(0.4, 0, 0.2, 1) infinite both;
            animation: fill-unfill-rotate 5332ms cubic-bezier(0.4, 0, 0.2, 1) infinite both, yellow-fade-in-out 5332ms cubic-bezier(0.4, 0, 0.2, 1) infinite both
        }

        .active .spinner-layer.spinner-green {
            -webkit-animation: fill-unfill-rotate 5332ms cubic-bezier(0.4, 0, 0.2, 1) infinite both, green-fade-in-out 5332ms cubic-bezier(0.4, 0, 0.2, 1) infinite both;
            animation: fill-unfill-rotate 5332ms cubic-bezier(0.4, 0, 0.2, 1) infinite both, green-fade-in-out 5332ms cubic-bezier(0.4, 0, 0.2, 1) infinite both
        }

        .active .spinner-layer, .active .spinner-layer.spinner-blue-only, .active .spinner-layer.spinner-red-only, .active .spinner-layer.spinner-yellow-only, .active .spinner-layer.spinner-green-only {
            opacity: 1;
            -webkit-animation: fill-unfill-rotate 5332ms cubic-bezier(0.4, 0, 0.2, 1) infinite both;
            animation: fill-unfill-rotate 5332ms cubic-bezier(0.4, 0, 0.2, 1) infinite both
        }

        @-webkit-keyframes fill-unfill-rotate {
            12.5% {
                -webkit-transform: rotate(135deg)
            }
            25% {
                -webkit-transform: rotate(270deg)
            }
            37.5% {
                -webkit-transform: rotate(405deg)
            }
            50% {
                -webkit-transform: rotate(540deg)
            }
            62.5% {
                -webkit-transform: rotate(675deg)
            }
            75% {
                -webkit-transform: rotate(810deg)
            }
            87.5% {
                -webkit-transform: rotate(945deg)
            }
            to {
                -webkit-transform: rotate(1080deg)
            }
        }

        @keyframes fill-unfill-rotate {
            12.5% {
                -webkit-transform: rotate(135deg);
                transform: rotate(135deg)
            }
            25% {
                -webkit-transform: rotate(270deg);
                transform: rotate(270deg)
            }
            37.5% {
                -webkit-transform: rotate(405deg);
                transform: rotate(405deg)
            }
            50% {
                -webkit-transform: rotate(540deg);
                transform: rotate(540deg)
            }
            62.5% {
                -webkit-transform: rotate(675deg);
                transform: rotate(675deg)
            }
            75% {
                -webkit-transform: rotate(810deg);
                transform: rotate(810deg)
            }
            87.5% {
                -webkit-transform: rotate(945deg);
                transform: rotate(945deg)
            }
            to {
                -webkit-transform: rotate(1080deg);
                transform: rotate(1080deg)
            }
        }

        @-webkit-keyframes blue-fade-in-out {
            from {
                opacity: 1
            }
            25% {
                opacity: 1
            }
            26% {
                opacity: 0
            }
            89% {
                opacity: 0
            }
            90% {
                opacity: 1
            }
            100% {
                opacity: 1
            }
        }

        @keyframes blue-fade-in-out {
            from {
                opacity: 1
            }
            25% {
                opacity: 1
            }
            26% {
                opacity: 0
            }
            89% {
                opacity: 0
            }
            90% {
                opacity: 1
            }
            100% {
                opacity: 1
            }
        }

        @-webkit-keyframes red-fade-in-out {
            from {
                opacity: 0
            }
            15% {
                opacity: 0
            }
            25% {
                opacity: 1
            }
            50% {
                opacity: 1
            }
            51% {
                opacity: 0
            }
        }

        @keyframes red-fade-in-out {
            from {
                opacity: 0
            }
            15% {
                opacity: 0
            }
            25% {
                opacity: 1
            }
            50% {
                opacity: 1
            }
            51% {
                opacity: 0
            }
        }

        @-webkit-keyframes yellow-fade-in-out {
            from {
                opacity: 0
            }
            40% {
                opacity: 0
            }
            50% {
                opacity: 1
            }
            75% {
                opacity: 1
            }
            76% {
                opacity: 0
            }
        }

        @keyframes yellow-fade-in-out {
            from {
                opacity: 0
            }
            40% {
                opacity: 0
            }
            50% {
                opacity: 1
            }
            75% {
                opacity: 1
            }
            76% {
                opacity: 0
            }
        }

        @-webkit-keyframes green-fade-in-out {
            from {
                opacity: 0
            }
            65% {
                opacity: 0
            }
            75% {
                opacity: 1
            }
            90% {
                opacity: 1
            }
            100% {
                opacity: 0
            }
        }

        @keyframes green-fade-in-out {
            from {
                opacity: 0
            }
            65% {
                opacity: 0
            }
            75% {
                opacity: 1
            }
            90% {
                opacity: 1
            }
            100% {
                opacity: 0
            }
        }

        .gap-patch {
            position: absolute;
            top: 0;
            left: 45%;
            width: 10%;
            height: 100%;
            overflow: hidden;
            border-color: inherit
        }

        .gap-patch .circle {
            width: 1000%;
            left: -450%
        }

        .circle-clipper {
            display: inline-block;
            position: relative;
            width: 50%;
            height: 100%;
            overflow: hidden;
            border-color: inherit
        }

        .circle-clipper .circle {
            width: 200%;
            height: 100%;
            border-width: 3px;
            border-style: solid;
            border-color: inherit;
            border-bottom-color: transparent !important;
            border-radius: 50%;
            -webkit-animation: none;
            animation: none;
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0
        }

        .circle-clipper.left .circle {
            left: 0;
            border-right-color: transparent !important;
            -webkit-transform: rotate(129deg);
            transform: rotate(129deg)
        }

        .circle-clipper.right .circle {
            left: -100%;
            border-left-color: transparent !important;
            -webkit-transform: rotate(-129deg);
            transform: rotate(-129deg)
        }

        .active .circle-clipper.left .circle {
            -webkit-animation: left-spin 1333ms cubic-bezier(0.4, 0, 0.2, 1) infinite both;
            animation: left-spin 1333ms cubic-bezier(0.4, 0, 0.2, 1) infinite both
        }

        .active .circle-clipper.right .circle {
            -webkit-animation: right-spin 1333ms cubic-bezier(0.4, 0, 0.2, 1) infinite both;
            animation: right-spin 1333ms cubic-bezier(0.4, 0, 0.2, 1) infinite both
        }

        @-webkit-keyframes left-spin {
            from {
                -webkit-transform: rotate(130deg)
            }
            50% {
                -webkit-transform: rotate(-5deg)
            }
            to {
                -webkit-transform: rotate(130deg)
            }
        }

        @keyframes left-spin {
            from {
                -webkit-transform: rotate(130deg);
                transform: rotate(130deg)
            }
            50% {
                -webkit-transform: rotate(-5deg);
                transform: rotate(-5deg)
            }
            to {
                -webkit-transform: rotate(130deg);
                transform: rotate(130deg)
            }
        }

        @-webkit-keyframes right-spin {
            from {
                -webkit-transform: rotate(-130deg)
            }
            50% {
                -webkit-transform: rotate(5deg)
            }
            to {
                -webkit-transform: rotate(-130deg)
            }
        }

        @keyframes right-spin {
            from {
                -webkit-transform: rotate(-130deg);
                transform: rotate(-130deg)
            }
            50% {
                -webkit-transform: rotate(5deg);
                transform: rotate(5deg)
            }
            to {
                -webkit-transform: rotate(-130deg);
                transform: rotate(-130deg)
            }
        }

        #spinnerContainer.cooldown {
            -webkit-animation: container-rotate 1568ms linear infinite, fade-out 400ms cubic-bezier(0.4, 0, 0.2, 1);
            animation: container-rotate 1568ms linear infinite, fade-out 400ms cubic-bezier(0.4, 0, 0.2, 1)
        }

        @-webkit-keyframes fade-out {
            from {
                opacity: 1
            }
            to {
                opacity: 0
            }
        }

        @keyframes fade-out {
            from {
                opacity: 1
            }
            to {
                opacity: 0
            }
        }

        .slider {
            position: relative;
            height: 400px;
            width: 100%
        }

        .slider.fullscreen {
            height: 100%;
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0
        }

        .slider.fullscreen ul.slides {
            height: 100%
        }

        .slider.fullscreen ul.indicators {
            z-index: 2;
            bottom: 30px
        }

        .slider .slides {
            background-color: #9e9e9e;
            margin: 0;
            height: 400px
        }

        .slider .slides li {
            opacity: 0;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
            width: 100%;
            height: inherit;
            overflow: hidden
        }

        .slider .slides li img {
            height: 100%;
            width: 100%;
            background-size: cover;
            background-position: center
        }

        .slider .slides li .caption {
            color: #fff;
            position: absolute;
            top: 15%;
            left: 15%;
            width: 70%;
            opacity: 0
        }

        .slider .slides li .caption p {
            color: #e0e0e0
        }

        .slider .slides li.active {
            z-index: 2
        }

        .slider .indicators {
            position: absolute;
            text-align: center;
            left: 0;
            right: 0;
            bottom: 0;
            margin: 0
        }

        .slider .indicators .indicator-item {
            display: inline-block;
            position: relative;
            cursor: pointer;
            height: 16px;
            width: 16px;
            margin: 0 12px;
            background-color: #e0e0e0;
            -webkit-transition: background-color .3s;
            transition: background-color .3s;
            border-radius: 50%
        }

        .slider .indicators .indicator-item.active {
            background-color: #4CAF50
        }

        .carousel {
            overflow: hidden;
            position: relative;
            width: 100%;
            height: 400px;
            -webkit-perspective: 500px;
            perspective: 500px;
            -webkit-transform-style: preserve-3d;
            transform-style: preserve-3d;
            -webkit-transform-origin: 0% 50%;
            transform-origin: 0% 50%
        }

        .carousel.carousel-slider {
            top: 0;
            left: 0;
            height: 0
        }

        .carousel.carousel-slider .carousel-fixed-item {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 20px;
            z-index: 1
        }

        .carousel.carousel-slider .carousel-fixed-item.with-indicators {
            bottom: 68px
        }

        .carousel.carousel-slider .carousel-item {
            width: 100%;
            height: 100%;
            min-height: 400px;
            position: absolute;
            top: 0;
            left: 0
        }

        .carousel.carousel-slider .carousel-item h2 {
            font-size: 24px;
            font-weight: 500;
            line-height: 32px
        }

        .carousel.carousel-slider .carousel-item p {
            font-size: 15px
        }

        .carousel .carousel-item {
            display: none;
            width: 200px;
            height: 200px;
            position: absolute;
            top: 0;
            left: 0
        }

        .carousel .carousel-item > img {
            width: 100%
        }

        .carousel .indicators {
            position: absolute;
            text-align: center;
            left: 0;
            right: 0;
            bottom: 0;
            margin: 0
        }

        .carousel .indicators .indicator-item {
            display: inline-block;
            position: relative;
            cursor: pointer;
            height: 8px;
            width: 8px;
            margin: 24px 4px;
            background-color: rgba(255, 255, 255, 0.5);
            -webkit-transition: background-color .3s;
            transition: background-color .3s;
            border-radius: 50%
        }

        .carousel .indicators .indicator-item.active {
            background-color: #fff
        }

        .carousel.scrolling .carousel-item .materialboxed, .carousel .carousel-item:not(.active) .materialboxed {
            pointer-events: none
        }

        .tap-target-wrapper {
            width: 800px;
            height: 800px;
            position: fixed;
            z-index: 1000;
            visibility: hidden;
            -webkit-transition: visibility 0s .3s;
            transition: visibility 0s .3s
        }

        .tap-target-wrapper.open {
            visibility: visible;
            -webkit-transition: visibility 0s;
            transition: visibility 0s
        }

        .tap-target-wrapper.open .tap-target {
            -webkit-transform: scale(1);
            transform: scale(1);
            opacity: .95;
            -webkit-transition: opacity 0.3s cubic-bezier(0.42, 0, 0.58, 1), -webkit-transform 0.3s cubic-bezier(0.42, 0, 0.58, 1);
            transition: opacity 0.3s cubic-bezier(0.42, 0, 0.58, 1), -webkit-transform 0.3s cubic-bezier(0.42, 0, 0.58, 1);
            transition: transform 0.3s cubic-bezier(0.42, 0, 0.58, 1), opacity 0.3s cubic-bezier(0.42, 0, 0.58, 1);
            transition: transform 0.3s cubic-bezier(0.42, 0, 0.58, 1), opacity 0.3s cubic-bezier(0.42, 0, 0.58, 1), -webkit-transform 0.3s cubic-bezier(0.42, 0, 0.58, 1)
        }

        .tap-target-wrapper.open .tap-target-wave::before {
            -webkit-transform: scale(1);
            transform: scale(1)
        }

        .tap-target-wrapper.open .tap-target-wave::after {
            visibility: visible;
            -webkit-animation: pulse-animation 1s cubic-bezier(0.24, 0, 0.38, 1) infinite;
            animation: pulse-animation 1s cubic-bezier(0.24, 0, 0.38, 1) infinite;
            -webkit-transition: opacity .3s, visibility 0s 1s, -webkit-transform .3s;
            transition: opacity .3s, visibility 0s 1s, -webkit-transform .3s;
            transition: opacity .3s, transform .3s, visibility 0s 1s;
            transition: opacity .3s, transform .3s, visibility 0s 1s, -webkit-transform .3s
        }

        .tap-target {
            position: absolute;
            font-size: 1rem;
            border-radius: 50%;
            background-color: #ee6e73;
            -webkit-box-shadow: 0 20px 20px 0 rgba(0, 0, 0, 0.14), 0 10px 50px 0 rgba(0, 0, 0, 0.12), 0 30px 10px -20px rgba(0, 0, 0, 0.2);
            box-shadow: 0 20px 20px 0 rgba(0, 0, 0, 0.14), 0 10px 50px 0 rgba(0, 0, 0, 0.12), 0 30px 10px -20px rgba(0, 0, 0, 0.2);
            width: 100%;
            height: 100%;
            opacity: 0;
            -webkit-transform: scale(0);
            transform: scale(0);
            -webkit-transition: opacity 0.3s cubic-bezier(0.42, 0, 0.58, 1), -webkit-transform 0.3s cubic-bezier(0.42, 0, 0.58, 1);
            transition: opacity 0.3s cubic-bezier(0.42, 0, 0.58, 1), -webkit-transform 0.3s cubic-bezier(0.42, 0, 0.58, 1);
            transition: transform 0.3s cubic-bezier(0.42, 0, 0.58, 1), opacity 0.3s cubic-bezier(0.42, 0, 0.58, 1);
            transition: transform 0.3s cubic-bezier(0.42, 0, 0.58, 1), opacity 0.3s cubic-bezier(0.42, 0, 0.58, 1), -webkit-transform 0.3s cubic-bezier(0.42, 0, 0.58, 1)
        }

        .tap-target-content {
            position: relative;
            display: table-cell
        }

        .tap-target-wave {
            position: absolute;
            border-radius: 50%;
            z-index: 10001
        }

        .tap-target-wave::before, .tap-target-wave::after {
            content: '';
            display: block;
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background-color: #ffffff
        }

        .tap-target-wave::before {
            -webkit-transform: scale(0);
            transform: scale(0);
            -webkit-transition: -webkit-transform .3s;
            transition: -webkit-transform .3s;
            transition: transform .3s;
            transition: transform .3s, -webkit-transform .3s
        }

        .tap-target-wave::after {
            visibility: hidden;
            -webkit-transition: opacity .3s, visibility 0s, -webkit-transform .3s;
            transition: opacity .3s, visibility 0s, -webkit-transform .3s;
            transition: opacity .3s, transform .3s, visibility 0s;
            transition: opacity .3s, transform .3s, visibility 0s, -webkit-transform .3s;
            z-index: -1
        }

        .tap-target-origin {
            top: 50%;
            left: 50%;
            -webkit-transform: translate(-50%, -50%);
            transform: translate(-50%, -50%);
            z-index: 10002;
            position: absolute !important
        }

        .tap-target-origin:not(.btn):not(.btn-large), .tap-target-origin:not(.btn):not(.btn-large):hover {
            background: none
        }

        @media only screen and (max-width: 600px) {
            .tap-target, .tap-target-wrapper {
                width: 600px;
                height: 600px
            }
        }

        .pulse {
            overflow: initial;
            position: relative
        }

        .pulse::before {
            content: '';
            display: block;
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-color: inherit;
            border-radius: inherit;
            -webkit-transition: opacity .3s, -webkit-transform .3s;
            transition: opacity .3s, -webkit-transform .3s;
            transition: opacity .3s, transform .3s;
            transition: opacity .3s, transform .3s, -webkit-transform .3s;
            -webkit-animation: pulse-animation 1s cubic-bezier(0.24, 0, 0.38, 1) infinite;
            animation: pulse-animation 1s cubic-bezier(0.24, 0, 0.38, 1) infinite;
            z-index: -1
        }

        @-webkit-keyframes pulse-animation {
            0% {
                opacity: 1;
                -webkit-transform: scale(1);
                transform: scale(1)
            }
            50% {
                opacity: 0;
                -webkit-transform: scale(1.5);
                transform: scale(1.5)
            }
            100% {
                opacity: 0;
                -webkit-transform: scale(1.5);
                transform: scale(1.5)
            }
        }

        @keyframes pulse-animation {
            0% {
                opacity: 1;
                -webkit-transform: scale(1);
                transform: scale(1)
            }
            50% {
                opacity: 0;
                -webkit-transform: scale(1.5);
                transform: scale(1.5)
            }
            100% {
                opacity: 0;
                -webkit-transform: scale(1.5);
                transform: scale(1.5)
            }
        }

        .picker {
            font-size: 16px;
            text-align: left;
            line-height: 1.2;
            color: #000000;
            position: absolute;
            z-index: 10000;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none
        }

        .picker__input {
            cursor: default
        }

        .picker__input.picker__input--active {
            border-color: #0089ec
        }

        .picker__holder {
            width: 100%;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch
        }

        .picker__holder, .picker__frame {
            bottom: 0;
            left: 0;
            right: 0;
            top: 100%
        }

        .picker__holder {
            position: fixed;
            -webkit-transition: background 0.15s ease-out, top 0s 0.15s;
            transition: background 0.15s ease-out, top 0s 0.15s;
            -webkit-backface-visibility: hidden
        }

        .picker__frame {
            position: absolute;
            margin: 0 auto;
            min-width: 256px;
            width: 300px;
            max-height: 350px;
            -ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=0)";
            filter: alpha(opacity=0);
            -moz-opacity: 0;
            opacity: 0;
            -webkit-transition: all 0.15s ease-out;
            transition: all 0.15s ease-out
        }

        @media (min-height: 28.875em) {
            .picker__frame {
                overflow: visible;
                top: auto;
                bottom: -100%;
                max-height: 80%
            }
        }

        @media (min-height: 40.125em) {
            .picker__frame {
                margin-bottom: 7.5%
            }
        }

        .picker__wrap {
            display: table;
            width: 100%;
            height: 100%
        }

        @media (min-height: 28.875em) {
            .picker__wrap {
                display: block
            }
        }

        .picker__box {
            background: #ffffff;
            display: table-cell;
            vertical-align: middle
        }

        @media (min-height: 28.875em) {
            .picker__box {
                display: block;
                border: 1px solid #777777;
                border-top-color: #898989;
                border-bottom-width: 0;
                border-radius: 5px 5px 0 0;
                -webkit-box-shadow: 0 12px 36px 16px rgba(0, 0, 0, 0.24);
                box-shadow: 0 12px 36px 16px rgba(0, 0, 0, 0.24)
            }
        }

        .picker--opened .picker__holder {
            top: 0;
            background: transparent;
            -ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColorstr=#1E000000,endColorstr=#1E000000)";
            zoom: 1;
            background: rgba(0, 0, 0, 0.32);
            -webkit-transition: background 0.15s ease-out;
            transition: background 0.15s ease-out
        }

        .picker--opened .picker__frame {
            top: 0;
            -ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=100)";
            filter: alpha(opacity=100);
            -moz-opacity: 1;
            opacity: 1
        }

        @media (min-height: 35.875em) {
            .picker--opened .picker__frame {
                top: 10%;
                bottom: auto
            }
        }

        .picker__input.picker__input--active {
            border-color: #E3F2FD
        }

        .picker__frame {
            margin: 0 auto;
            max-width: 325px
        }

        @media (min-height: 38.875em) {
            .picker--opened .picker__frame {
                top: 10%;
                bottom: auto
            }
        }

        @media only screen and (min-width: 601px) {
            .picker__box {
                display: -webkit-box;
                display: -webkit-flex;
                display: -ms-flexbox;
                display: flex
            }

            .picker__frame {
                width: 80%;
                max-width: 600px
            }
        }

        .picker__box {
            padding: 0;
            border-radius: 2px;
            overflow: hidden
        }

        .picker__header {
            text-align: center;
            position: relative;
            margin-top: .75em
        }

        .picker__month, .picker__year {
            display: inline-block;
            margin-left: .25em;
            margin-right: .25em
        }

        .picker__select--month, .picker__select--year {
            height: 2em;
            padding: 0;
            margin-left: .25em;
            margin-right: .25em
        }

        .picker__select--month.browser-default {
            display: inline;
            background-color: #FFFFFF;
            width: 40%
        }

        .picker__select--year.browser-default {
            display: inline;
            background-color: #FFFFFF;
            width: 26%
        }

        .picker__select--month:focus, .picker__select--year:focus {
            border-color: rgba(0, 0, 0, 0.05)
        }

        .picker__nav--prev, .picker__nav--next {
            position: absolute;
            padding: .5em 1.25em;
            width: 1em;
            height: 1em;
            -webkit-box-sizing: content-box;
            box-sizing: content-box;
            top: -0.25em
        }

        .picker__nav--prev {
            left: -1em;
            padding-right: 1.25em
        }

        .picker__nav--next {
            right: -1em;
            padding-left: 1.25em
        }

        .picker__nav--disabled, .picker__nav--disabled:hover, .picker__nav--disabled:before, .picker__nav--disabled:before:hover {
            cursor: default;
            background: none;
            border-right-color: #f5f5f5;
            border-left-color: #f5f5f5
        }

        .picker__table {
            text-align: center;
            border-collapse: collapse;
            border-spacing: 0;
            table-layout: fixed;
            font-size: 1rem;
            width: 100%;
            margin-top: .75em;
            margin-bottom: .5em
        }

        .picker__table th, .picker__table td {
            text-align: center
        }

        .picker__table td {
            margin: 0;
            padding: 0
        }

        .picker__weekday {
            width: 14.285714286%;
            font-size: .75em;
            padding-bottom: .25em;
            color: #999999;
            font-weight: 500
        }

        @media (min-height: 33.875em) {
            .picker__weekday {
                padding-bottom: .5em
            }
        }

        .picker__day--today {
            position: relative;
            color: #595959;
            letter-spacing: -.3;
            padding: .75rem 0;
            font-weight: 400;
            border: 1px solid transparent
        }

        .picker__day--disabled:before {
            border-top-color: #aaaaaa
        }

        .picker__day--infocus:hover {
            cursor: pointer;
            color: #000;
            font-weight: 500
        }

        .picker__day--outfocus {
            display: none;
            padding: .75rem 0;
            color: #fff
        }

        .picker__day--outfocus:hover {
            cursor: pointer;
            color: #dddddd;
            font-weight: 500
        }

        .picker__day--highlighted:hover, .picker--focused .picker__day--highlighted {
            cursor: pointer
        }

        .picker__day--selected, .picker__day--selected:hover, .picker--focused .picker__day--selected {
            border-radius: 50%;
            -webkit-transform: scale(0.75);
            transform: scale(0.75);
            background: #0089ec;
            color: #ffffff
        }

        .picker__day--disabled, .picker__day--disabled:hover, .picker--focused .picker__day--disabled {
            background: #f5f5f5;
            border-color: #f5f5f5;
            color: #dddddd;
            cursor: default
        }

        .picker__day--highlighted.picker__day--disabled, .picker__day--highlighted.picker__day--disabled:hover {
            background: #bbbbbb
        }

        .picker__footer {
            text-align: right
        }

        .picker__button--today, .picker__button--clear, .picker__button--close {
            border: 1px solid #ffffff;
            background: #ffffff;
            font-size: .8em;
            padding: .66em 0;
            font-weight: bold;
            width: 33%;
            display: inline-block;
            vertical-align: bottom
        }

        .picker__button--today:hover, .picker__button--clear:hover, .picker__button--close:hover {
            cursor: pointer;
            color: #000000;
            background: #b1dcfb;
            border-bottom-color: #b1dcfb
        }

        .picker__button--today:focus, .picker__button--clear:focus, .picker__button--close:focus {
            background: #b1dcfb;
            border-color: rgba(0, 0, 0, 0.05);
            outline: none
        }

        .picker__button--today:before, .picker__button--clear:before, .picker__button--close:before {
            position: relative;
            display: inline-block;
            height: 0
        }

        .picker__button--today:before, .picker__button--clear:before {
            content: " ";
            margin-right: .45em
        }

        .picker__button--today:before {
            top: -0.05em;
            width: 0;
            border-top: 0.66em solid #0059bc;
            border-left: .66em solid transparent
        }

        .picker__button--clear:before {
            top: -0.25em;
            width: .66em;
            border-top: 3px solid #ee2200
        }

        .picker__button--close:before {
            content: "\D7";
            top: -0.1em;
            vertical-align: top;
            font-size: 1.1em;
            margin-right: .35em;
            color: #777777
        }

        .picker__button--today[disabled], .picker__button--today[disabled]:hover {
            background: #f5f5f5;
            border-color: #f5f5f5;
            color: #dddddd;
            cursor: default
        }

        .picker__button--today[disabled]:before {
            border-top-color: #aaaaaa
        }

        .picker__date-display {
            text-align: left;
            background-color: #26a69a;
            color: #fff;
            padding: 18px;
            font-weight: 300
        }

        @media only screen and (min-width: 601px) {
            .picker__date-display {
                -webkit-box-flex: 1;
                -webkit-flex: 1;
                -ms-flex: 1;
                flex: 1
            }

            .picker__weekday-display {
                display: block
            }

            .picker__container__wrapper {
                -webkit-box-flex: 2;
                -webkit-flex: 2;
                -ms-flex: 2;
                flex: 2
            }
        }

        .picker__nav--prev:hover, .picker__nav--next:hover {
            cursor: pointer;
            color: #000000;
            background: #a1ded8
        }

        .picker__weekday-display {
            font-weight: 500;
            font-size: 2.8rem;
            margin-right: 5px;
            margin-top: 4px
        }

        .picker__month-display {
            font-size: 2.8rem;
            font-weight: 500
        }

        .picker__day-display {
            font-size: 2.8rem;
            font-weight: 500;
            margin-right: 5px
        }

        .picker__year-display {
            font-size: 1.5rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.7)
        }

        .picker__calendar-container {
            padding: 0 1rem
        }

        .picker__calendar-container thead {
            border: none
        }

        .picker__table {
            margin-top: 0;
            margin-bottom: .5em
        }

        .picker__day--infocus {
            color: rgba(0, 0, 0, 0.87);
            letter-spacing: -.3px;
            padding: 0.75rem 0;
            font-weight: 400;
            border: 1px solid transparent
        }

        @media only screen and (min-width: 601px) {
            .picker__day--infocus {
                padding: 1.1rem 0
            }
        }

        .picker__day.picker__day--today {
            color: #26a69a
        }

        .picker__day.picker__day--today.picker__day--selected {
            color: #fff
        }

        .picker__weekday {
            font-size: .9rem
        }

        .picker__day--selected, .picker__day--selected:hover, .picker--focused .picker__day--selected {
            border-radius: 50%;
            -webkit-transform: scale(0.9);
            transform: scale(0.9);
            background-color: #26a69a;
            color: #ffffff
        }

        .picker__day--selected.picker__day--outfocus, .picker__day--selected:hover.picker__day--outfocus, .picker--focused .picker__day--selected.picker__day--outfocus {
            background-color: #a1ded8
        }

        .picker__footer {
            text-align: right;
            padding: 5px 10px
        }

        .picker__close, .picker__today, .picker__clear {
            font-size: 1.1rem;
            padding: 0 1rem;
            color: #26a69a
        }

        .picker__clear {
            color: #f44336;
            float: left
        }

        .picker__nav--prev:before, .picker__nav--next:before {
            content: " ";
            border-top: .5em solid transparent;
            border-bottom: .5em solid transparent;
            border-right: 0.75em solid #676767;
            width: 0;
            height: 0;
            display: block;
            margin: 0 auto
        }

        .picker__nav--next:before {
            border-right: 0;
            border-left: 0.75em solid #676767
        }

        button.picker__today:focus, button.picker__clear:focus, button.picker__close:focus {
            background-color: #a1ded8
        }

        .picker__list {
            list-style: none;
            padding: 0.75em 0 4.2em;
            margin: 0
        }

        .picker__list-item {
            border-bottom: 1px solid #ddd;
            border-top: 1px solid #ddd;
            margin-bottom: -1px;
            position: relative;
            background: #fff;
            padding: .75em 1.25em
        }

        @media (min-height: 46.75em) {
            .picker__list-item {
                padding: .5em 1em
            }
        }

        .picker__list-item:hover {
            cursor: pointer;
            color: #000;
            background: #b1dcfb;
            border-color: #0089ec;
            z-index: 10
        }

        .picker__list-item--highlighted {
            border-color: #0089ec;
            z-index: 10
        }

        .picker__list-item--highlighted:hover, .picker--focused .picker__list-item--highlighted {
            cursor: pointer;
            color: #000;
            background: #b1dcfb
        }

        .picker__list-item--selected, .picker__list-item--selected:hover, .picker--focused .picker__list-item--selected {
            background: #0089ec;
            color: #fff;
            z-index: 10
        }

        .picker__list-item--disabled, .picker__list-item--disabled:hover, .picker--focused .picker__list-item--disabled {
            background: #f5f5f5;
            border-color: #f5f5f5;
            color: #ddd;
            cursor: default;
            border-color: #ddd;
            z-index: auto
        }

        .picker--time .picker__button--clear {
            display: block;
            width: 80%;
            margin: 1em auto 0;
            padding: 1em 1.25em;
            background: none;
            border: 0;
            font-weight: 500;
            font-size: .67em;
            text-align: center;
            text-transform: uppercase;
            color: rgba(0, 0, 0, 0.87)
        }

        .picker--time .picker__button--clear:hover, .picker--time .picker__button--clear:focus {
            color: #000;
            background: #b1dcfb;
            background: #ee2200;
            border-color: #ee2200;
            cursor: pointer;
            color: #fff;
            outline: none
        }

        .picker--time .picker__button--clear:before {
            top: -0.25em;
            color: rgba(0, 0, 0, 0.87);
            font-size: 1.25em;
            font-weight: bold
        }

        .picker--time .picker__button--clear:hover:before, .picker--time .picker__button--clear:focus:before {
            color: #fff
        }

        .picker--time .picker__frame {
            min-width: 256px;
            max-width: 320px
        }

        .picker--time .picker__box {
            font-size: 1em;
            background: #f2f2f2;
            padding: 0
        }

        @media (min-height: 40.125em) {
            .picker--time .picker__box {
                margin-bottom: 5em
            }
        }

        .clockpicker-display {
            font-size: 4rem;
            font-weight: bold;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 400;
            clear: both;
            position: relative
        }

        .clockpicker-span-am-pm {
            font-size: 1.3rem;
            position: absolute;
            right: 1rem;
            bottom: 0.3rem;
            line-height: 2rem;
            font-weight: 500
        }

        @media only screen and (min-width: 601px) {
            .clockpicker-display {
                top: 32%
            }

            .clockpicker-span-am-pm {
                position: relative;
                right: auto;
                bottom: auto;
                text-align: center;
                margin-top: 1.2rem
            }
        }

        .text-primary {
            color: #fff
        }

        .clockpicker-span-hours {
            margin-right: 3px
        }

        .clockpicker-span-minutes {
            margin-left: 3px
        }

        .clockpicker-span-hours, .clockpicker-span-minutes, .clockpicker-span-am-pm div {
            cursor: pointer
        }

        .clockpicker-moving {
            cursor: move
        }

        .clockpicker-plate {
            background-color: #eee;
            border-radius: 50%;
            width: 270px;
            height: 270px;
            overflow: visible;
            position: relative;
            margin: auto;
            margin-top: 25px;
            margin-bottom: 5px;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none
        }

        .clockpicker-canvas, .clockpicker-dial {
            width: 270px;
            height: 270px;
            position: absolute;
            left: -1px;
            top: -1px
        }

        .clockpicker-minutes {
            visibility: hidden
        }

        .clockpicker-tick {
            border-radius: 50%;
            color: rgba(0, 0, 0, 0.87);
            line-height: 40px;
            text-align: center;
            width: 40px;
            height: 40px;
            position: absolute;
            cursor: pointer
        }

        .clockpicker-tick.active, .clockpicker-tick:hover {
            background-color: rgba(38, 166, 154, 0.25)
        }

        .clockpicker-dial {
            -webkit-transition: -webkit-transform 350ms, opacity 350ms;
            -webkit-transition: opacity 350ms, -webkit-transform 350ms;
            transition: opacity 350ms, -webkit-transform 350ms;
            transition: transform 350ms, opacity 350ms;
            transition: transform 350ms, opacity 350ms, -webkit-transform 350ms
        }

        .clockpicker-dial-out {
            opacity: 0
        }

        .clockpicker-hours.clockpicker-dial-out {
            -webkit-transform: scale(1.2, 1.2);
            transform: scale(1.2, 1.2)
        }

        .clockpicker-minutes.clockpicker-dial-out {
            -webkit-transform: scale(0.8, 0.8);
            transform: scale(0.8, 0.8)
        }

        .clockpicker-canvas {
            -webkit-transition: opacity 175ms;
            transition: opacity 175ms
        }

        .clockpicker-canvas-out {
            opacity: 0.25
        }

        .clockpicker-canvas-bearing {
            stroke: none;
            fill: #26a69a
        }

        .clockpicker-canvas-bg {
            stroke: none;
            fill: #26a69a
        }

        .clockpicker-canvas-bg-trans {
            fill: #26a69a
        }

        .clockpicker-canvas line {
            stroke: #26a69a;
            stroke-width: 4;
            stroke-linecap: round
        }
    </style>
    <script>
        !function (a, b) {
            "use strict";
            "object" == typeof module && "object" == typeof module.exports ? module.exports = a.document ? b(a, !0) : function (a) {
                if (!a.document) throw new Error("jQuery requires a window with a document");
                return b(a)
            } : b(a)
        }("undefined" != typeof window ? window : this, function (a, b) {
            "use strict";
            var c = [], d = a.document, e = Object.getPrototypeOf, f = c.slice, g = c.concat, h = c.push, i = c.indexOf,
                j = {}, k = j.toString, l = j.hasOwnProperty, m = l.toString, n = m.call(Object), o = {};

            function p(a, b) {
                b = b || d;
                var c = b.createElement("script");
                c.text = a, b.head.appendChild(c).parentNode.removeChild(c)
            }

            var q = "3.2.1", r = function (a, b) {
                return new r.fn.init(a, b)
            }, s = /^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, t = /^-ms-/, u = /-([a-z])/g, v = function (a, b) {
                return b.toUpperCase()
            };
            r.fn = r.prototype = {
                jquery: q, constructor: r, length: 0, toArray: function () {
                    return f.call(this)
                }, get: function (a) {
                    return null == a ? f.call(this) : a < 0 ? this[a + this.length] : this[a]
                }, pushStack: function (a) {
                    var b = r.merge(this.constructor(), a);
                    return b.prevObject = this, b
                }, each: function (a) {
                    return r.each(this, a)
                }, map: function (a) {
                    return this.pushStack(r.map(this, function (b, c) {
                        return a.call(b, c, b)
                    }))
                }, slice: function () {
                    return this.pushStack(f.apply(this, arguments))
                }, first: function () {
                    return this.eq(0)
                }, last: function () {
                    return this.eq(-1)
                }, eq: function (a) {
                    var b = this.length, c = +a + (a < 0 ? b : 0);
                    return this.pushStack(c >= 0 && c < b ? [this[c]] : [])
                }, end: function () {
                    return this.prevObject || this.constructor()
                }, push: h, sort: c.sort, splice: c.splice
            }, r.extend = r.fn.extend = function () {
                var a, b, c, d, e, f, g = arguments[0] || {}, h = 1, i = arguments.length, j = !1;
                for ("boolean" == typeof g && (j = g, g = arguments[h] || {}, h++), "object" == typeof g || r.isFunction(g) || (g = {}), h === i && (g = this, h--); h < i; h++) if (null != (a = arguments[h])) for (b in a) c = g[b], d = a[b], g !== d && (j && d && (r.isPlainObject(d) || (e = Array.isArray(d))) ? (e ? (e = !1, f = c && Array.isArray(c) ? c : []) : f = c && r.isPlainObject(c) ? c : {}, g[b] = r.extend(j, f, d)) : void 0 !== d && (g[b] = d));
                return g
            }, r.extend({
                expando: "jQuery" + (q + Math.random()).replace(/\D/g, ""), isReady: !0, error: function (a) {
                    throw new Error(a)
                }, noop: function () {
                }, isFunction: function (a) {
                    return "function" === r.type(a)
                }, isWindow: function (a) {
                    return null != a && a === a.window
                }, isNumeric: function (a) {
                    var b = r.type(a);
                    return ("number" === b || "string" === b) && !isNaN(a - parseFloat(a))
                }, isPlainObject: function (a) {
                    var b, c;
                    return !(!a || "[object Object]" !== k.call(a)) && (!(b = e(a)) || (c = l.call(b, "constructor") && b.constructor, "function" == typeof c && m.call(c) === n))
                }, isEmptyObject: function (a) {
                    var b;
                    for (b in a) return !1;
                    return !0
                }, type: function (a) {
                    return null == a ? a + "" : "object" == typeof a || "function" == typeof a ? j[k.call(a)] || "object" : typeof a
                }, globalEval: function (a) {
                    p(a)
                }, camelCase: function (a) {
                    return a.replace(t, "ms-").replace(u, v)
                }, each: function (a, b) {
                    var c, d = 0;
                    if (w(a)) {
                        for (c = a.length; d < c; d++) if (b.call(a[d], d, a[d]) === !1) break
                    } else for (d in a) if (b.call(a[d], d, a[d]) === !1) break;
                    return a
                }, trim: function (a) {
                    return null == a ? "" : (a + "").replace(s, "")
                }, makeArray: function (a, b) {
                    var c = b || [];
                    return null != a && (w(Object(a)) ? r.merge(c, "string" == typeof a ? [a] : a) : h.call(c, a)), c
                }, inArray: function (a, b, c) {
                    return null == b ? -1 : i.call(b, a, c)
                }, merge: function (a, b) {
                    for (var c = +b.length, d = 0, e = a.length; d < c; d++) a[e++] = b[d];
                    return a.length = e, a
                }, grep: function (a, b, c) {
                    for (var d, e = [], f = 0, g = a.length, h = !c; f < g; f++) d = !b(a[f], f), d !== h && e.push(a[f]);
                    return e
                }, map: function (a, b, c) {
                    var d, e, f = 0, h = [];
                    if (w(a)) for (d = a.length; f < d; f++) e = b(a[f], f, c), null != e && h.push(e); else for (f in a) e = b(a[f], f, c), null != e && h.push(e);
                    return g.apply([], h)
                }, guid: 1, proxy: function (a, b) {
                    var c, d, e;
                    if ("string" == typeof b && (c = a[b], b = a, a = c), r.isFunction(a)) return d = f.call(arguments, 2), e = function () {
                        return a.apply(b || this, d.concat(f.call(arguments)))
                    }, e.guid = a.guid = a.guid || r.guid++, e
                }, now: Date.now, support: o
            }), "function" == typeof Symbol && (r.fn[Symbol.iterator] = c[Symbol.iterator]), r.each("Boolean Number String Function Array Date RegExp Object Error Symbol".split(" "), function (a, b) {
                j["[object " + b + "]"] = b.toLowerCase()
            });

            function w(a) {
                var b = !!a && "length" in a && a.length, c = r.type(a);
                return "function" !== c && !r.isWindow(a) && ("array" === c || 0 === b || "number" == typeof b && b > 0 && b - 1 in a)
            }

            var x = function (a) {
                var b, c, d, e, f, g, h, i, j, k, l, m, n, o, p, q, r, s, t, u = "sizzle" + 1 * new Date,
                    v = a.document, w = 0, x = 0, y = ha(), z = ha(), A = ha(), B = function (a, b) {
                        return a === b && (l = !0), 0
                    }, C = {}.hasOwnProperty, D = [], E = D.pop, F = D.push, G = D.push, H = D.slice, I = function (a, b) {
                        for (var c = 0, d = a.length; c < d; c++) if (a[c] === b) return c;
                        return -1
                    },
                    J = "checked|selected|async|autofocus|autoplay|controls|defer|disabled|hidden|ismap|loop|multiple|open|readonly|required|scoped",
                    K = "[\\x20\\t\\r\\n\\f]", L = "(?:\\\\.|[\\w-]|[^\0-\\xa0])+",
                    M = "\\[" + K + "*(" + L + ")(?:" + K + "*([*^$|!~]?=)" + K + "*(?:'((?:\\\\.|[^\\\\'])*)'|\"((?:\\\\.|[^\\\\\"])*)\"|(" + L + "))|)" + K + "*\\]",
                    N = ":(" + L + ")(?:\\((('((?:\\\\.|[^\\\\'])*)'|\"((?:\\\\.|[^\\\\\"])*)\")|((?:\\\\.|[^\\\\()[\\]]|" + M + ")*)|.*)\\)|)",
                    O = new RegExp(K + "+", "g"),
                    P = new RegExp("^" + K + "+|((?:^|[^\\\\])(?:\\\\.)*)" + K + "+$", "g"),
                    Q = new RegExp("^" + K + "*," + K + "*"), R = new RegExp("^" + K + "*([>+~]|" + K + ")" + K + "*"),
                    S = new RegExp("=" + K + "*([^\\]'\"]*?)" + K + "*\\]", "g"), T = new RegExp(N),
                    U = new RegExp("^" + L + "$"), V = {
                        ID: new RegExp("^#(" + L + ")"),
                        CLASS: new RegExp("^\\.(" + L + ")"),
                        TAG: new RegExp("^(" + L + "|[*])"),
                        ATTR: new RegExp("^" + M),
                        PSEUDO: new RegExp("^" + N),
                        CHILD: new RegExp("^:(only|first|last|nth|nth-last)-(child|of-type)(?:\\(" + K + "*(even|odd|(([+-]|)(\\d*)n|)" + K + "*(?:([+-]|)" + K + "*(\\d+)|))" + K + "*\\)|)", "i"),
                        bool: new RegExp("^(?:" + J + ")$", "i"),
                        needsContext: new RegExp("^" + K + "*[>+~]|:(even|odd|eq|gt|lt|nth|first|last)(?:\\(" + K + "*((?:-\\d)?\\d*)" + K + "*\\)|)(?=[^-]|$)", "i")
                    }, W = /^(?:input|select|textarea|button)$/i, X = /^h\d$/i, Y = /^[^{]+\{\s*\[native \w/,
                    Z = /^(?:#([\w-]+)|(\w+)|\.([\w-]+))$/, $ = /[+~]/,
                    _ = new RegExp("\\\\([\\da-f]{1,6}" + K + "?|(" + K + ")|.)", "ig"), aa = function (a, b, c) {
                        var d = "0x" + b - 65536;
                        return d !== d || c ? b : d < 0 ? String.fromCharCode(d + 65536) : String.fromCharCode(d >> 10 | 55296, 1023 & d | 56320)
                    }, ba = /([\0-\x1f\x7f]|^-?\d)|^-$|[^\0-\x1f\x7f-\uFFFF\w-]/g, ca = function (a, b) {
                        return b ? "\0" === a ? "\ufffd" : a.slice(0, -1) + "\\" + a.charCodeAt(a.length - 1).toString(16) + " " : "\\" + a
                    }, da = function () {
                        m()
                    }, ea = ta(function (a) {
                        return a.disabled === !0 && ("form" in a || "label" in a)
                    }, {dir: "parentNode", next: "legend"});
                try {
                    G.apply(D = H.call(v.childNodes), v.childNodes), D[v.childNodes.length].nodeType
                } catch (fa) {
                    G = {
                        apply: D.length ? function (a, b) {
                            F.apply(a, H.call(b))
                        } : function (a, b) {
                            var c = a.length, d = 0;
                            while (a[c++] = b[d++]) ;
                            a.length = c - 1
                        }
                    }
                }

                function ga(a, b, d, e) {
                    var f, h, j, k, l, o, r, s = b && b.ownerDocument, w = b ? b.nodeType : 9;
                    if (d = d || [], "string" != typeof a || !a || 1 !== w && 9 !== w && 11 !== w) return d;
                    if (!e && ((b ? b.ownerDocument || b : v) !== n && m(b), b = b || n, p)) {
                        if (11 !== w && (l = Z.exec(a))) if (f = l[1]) {
                            if (9 === w) {
                                if (!(j = b.getElementById(f))) return d;
                                if (j.id === f) return d.push(j), d
                            } else if (s && (j = s.getElementById(f)) && t(b, j) && j.id === f) return d.push(j), d
                        } else {
                            if (l[2]) return G.apply(d, b.getElementsByTagName(a)), d;
                            if ((f = l[3]) && c.getElementsByClassName && b.getElementsByClassName) return G.apply(d, b.getElementsByClassName(f)), d
                        }
                        if (c.qsa && !A[a + " "] && (!q || !q.test(a))) {
                            if (1 !== w) s = b, r = a; else if ("object" !== b.nodeName.toLowerCase()) {
                                (k = b.getAttribute("id")) ? k = k.replace(ba, ca) : b.setAttribute("id", k = u), o = g(a), h = o.length;
                                while (h--) o[h] = "#" + k + " " + sa(o[h]);
                                r = o.join(","), s = $.test(a) && qa(b.parentNode) || b
                            }
                            if (r) try {
                                return G.apply(d, s.querySelectorAll(r)), d
                            } catch (x) {
                            } finally {
                                k === u && b.removeAttribute("id")
                            }
                        }
                    }
                    return i(a.replace(P, "$1"), b, d, e)
                }

                function ha() {
                    var a = [];

                    function b(c, e) {
                        return a.push(c + " ") > d.cacheLength && delete b[a.shift()], b[c + " "] = e
                    }

                    return b
                }

                function ia(a) {
                    return a[u] = !0, a
                }

                function ja(a) {
                    var b = n.createElement("fieldset");
                    try {
                        return !!a(b)
                    } catch (c) {
                        return !1
                    } finally {
                        b.parentNode && b.parentNode.removeChild(b), b = null
                    }
                }

                function ka(a, b) {
                    var c = a.split("|"), e = c.length;
                    while (e--) d.attrHandle[c[e]] = b
                }

                function la(a, b) {
                    var c = b && a, d = c && 1 === a.nodeType && 1 === b.nodeType && a.sourceIndex - b.sourceIndex;
                    if (d) return d;
                    if (c) while (c = c.nextSibling) if (c === b) return -1;
                    return a ? 1 : -1
                }

                function ma(a) {
                    return function (b) {
                        var c = b.nodeName.toLowerCase();
                        return "input" === c && b.type === a
                    }
                }

                function na(a) {
                    return function (b) {
                        var c = b.nodeName.toLowerCase();
                        return ("input" === c || "button" === c) && b.type === a
                    }
                }

                function oa(a) {
                    return function (b) {
                        return "form" in b ? b.parentNode && b.disabled === !1 ? "label" in b ? "label" in b.parentNode ? b.parentNode.disabled === a : b.disabled === a : b.isDisabled === a || b.isDisabled !== !a && ea(b) === a : b.disabled === a : "label" in b && b.disabled === a
                    }
                }

                function pa(a) {
                    return ia(function (b) {
                        return b = +b, ia(function (c, d) {
                            var e, f = a([], c.length, b), g = f.length;
                            while (g--) c[e = f[g]] && (c[e] = !(d[e] = c[e]))
                        })
                    })
                }

                function qa(a) {
                    return a && "undefined" != typeof a.getElementsByTagName && a
                }

                c = ga.support = {}, f = ga.isXML = function (a) {
                    var b = a && (a.ownerDocument || a).documentElement;
                    return !!b && "HTML" !== b.nodeName
                }, m = ga.setDocument = function (a) {
                    var b, e, g = a ? a.ownerDocument || a : v;
                    return g !== n && 9 === g.nodeType && g.documentElement ? (n = g, o = n.documentElement, p = !f(n), v !== n && (e = n.defaultView) && e.top !== e && (e.addEventListener ? e.addEventListener("unload", da, !1) : e.attachEvent && e.attachEvent("onunload", da)), c.attributes = ja(function (a) {
                        return a.className = "i", !a.getAttribute("className")
                    }), c.getElementsByTagName = ja(function (a) {
                        return a.appendChild(n.createComment("")), !a.getElementsByTagName("*").length
                    }), c.getElementsByClassName = Y.test(n.getElementsByClassName), c.getById = ja(function (a) {
                        return o.appendChild(a).id = u, !n.getElementsByName || !n.getElementsByName(u).length
                    }), c.getById ? (d.filter.ID = function (a) {
                        var b = a.replace(_, aa);
                        return function (a) {
                            return a.getAttribute("id") === b
                        }
                    }, d.find.ID = function (a, b) {
                        if ("undefined" != typeof b.getElementById && p) {
                            var c = b.getElementById(a);
                            return c ? [c] : []
                        }
                    }) : (d.filter.ID = function (a) {
                        var b = a.replace(_, aa);
                        return function (a) {
                            var c = "undefined" != typeof a.getAttributeNode && a.getAttributeNode("id");
                            return c && c.value === b
                        }
                    }, d.find.ID = function (a, b) {
                        if ("undefined" != typeof b.getElementById && p) {
                            var c, d, e, f = b.getElementById(a);
                            if (f) {
                                if (c = f.getAttributeNode("id"), c && c.value === a) return [f];
                                e = b.getElementsByName(a), d = 0;
                                while (f = e[d++]) if (c = f.getAttributeNode("id"), c && c.value === a) return [f]
                            }
                            return []
                        }
                    }), d.find.TAG = c.getElementsByTagName ? function (a, b) {
                        return "undefined" != typeof b.getElementsByTagName ? b.getElementsByTagName(a) : c.qsa ? b.querySelectorAll(a) : void 0
                    } : function (a, b) {
                        var c, d = [], e = 0, f = b.getElementsByTagName(a);
                        if ("*" === a) {
                            while (c = f[e++]) 1 === c.nodeType && d.push(c);
                            return d
                        }
                        return f
                    }, d.find.CLASS = c.getElementsByClassName && function (a, b) {
                        if ("undefined" != typeof b.getElementsByClassName && p) return b.getElementsByClassName(a)
                    }, r = [], q = [], (c.qsa = Y.test(n.querySelectorAll)) && (ja(function (a) {
                        o.appendChild(a).innerHTML = "<a id='" + u + "'></a><select id='" + u + "-\r\\' msallowcapture=''><option selected=''></option></select>", a.querySelectorAll("[msallowcapture^='']").length && q.push("[*^$]=" + K + "*(?:''|\"\")"), a.querySelectorAll("[selected]").length || q.push("\\[" + K + "*(?:value|" + J + ")"), a.querySelectorAll("[id~=" + u + "-]").length || q.push("~="), a.querySelectorAll(":checked").length || q.push(":checked"), a.querySelectorAll("a#" + u + "+*").length || q.push(".#.+[+~]")
                    }), ja(function (a) {
                        a.innerHTML = "<a href='' disabled='disabled'></a><select disabled='disabled'><option/></select>";
                        var b = n.createElement("input");
                        b.setAttribute("type", "hidden"), a.appendChild(b).setAttribute("name", "D"), a.querySelectorAll("[name=d]").length && q.push("name" + K + "*[*^$|!~]?="), 2 !== a.querySelectorAll(":enabled").length && q.push(":enabled", ":disabled"), o.appendChild(a).disabled = !0, 2 !== a.querySelectorAll(":disabled").length && q.push(":enabled", ":disabled"), a.querySelectorAll("*,:x"), q.push(",.*:")
                    })), (c.matchesSelector = Y.test(s = o.matches || o.webkitMatchesSelector || o.mozMatchesSelector || o.oMatchesSelector || o.msMatchesSelector)) && ja(function (a) {
                        c.disconnectedMatch = s.call(a, "*"), s.call(a, "[s!='']:x"), r.push("!=", N)
                    }), q = q.length && new RegExp(q.join("|")), r = r.length && new RegExp(r.join("|")), b = Y.test(o.compareDocumentPosition), t = b || Y.test(o.contains) ? function (a, b) {
                        var c = 9 === a.nodeType ? a.documentElement : a, d = b && b.parentNode;
                        return a === d || !(!d || 1 !== d.nodeType || !(c.contains ? c.contains(d) : a.compareDocumentPosition && 16 & a.compareDocumentPosition(d)))
                    } : function (a, b) {
                        if (b) while (b = b.parentNode) if (b === a) return !0;
                        return !1
                    }, B = b ? function (a, b) {
                        if (a === b) return l = !0, 0;
                        var d = !a.compareDocumentPosition - !b.compareDocumentPosition;
                        return d ? d : (d = (a.ownerDocument || a) === (b.ownerDocument || b) ? a.compareDocumentPosition(b) : 1, 1 & d || !c.sortDetached && b.compareDocumentPosition(a) === d ? a === n || a.ownerDocument === v && t(v, a) ? -1 : b === n || b.ownerDocument === v && t(v, b) ? 1 : k ? I(k, a) - I(k, b) : 0 : 4 & d ? -1 : 1)
                    } : function (a, b) {
                        if (a === b) return l = !0, 0;
                        var c, d = 0, e = a.parentNode, f = b.parentNode, g = [a], h = [b];
                        if (!e || !f) return a === n ? -1 : b === n ? 1 : e ? -1 : f ? 1 : k ? I(k, a) - I(k, b) : 0;
                        if (e === f) return la(a, b);
                        c = a;
                        while (c = c.parentNode) g.unshift(c);
                        c = b;
                        while (c = c.parentNode) h.unshift(c);
                        while (g[d] === h[d]) d++;
                        return d ? la(g[d], h[d]) : g[d] === v ? -1 : h[d] === v ? 1 : 0
                    }, n) : n
                }, ga.matches = function (a, b) {
                    return ga(a, null, null, b)
                }, ga.matchesSelector = function (a, b) {
                    if ((a.ownerDocument || a) !== n && m(a), b = b.replace(S, "='$1']"), c.matchesSelector && p && !A[b + " "] && (!r || !r.test(b)) && (!q || !q.test(b))) try {
                        var d = s.call(a, b);
                        if (d || c.disconnectedMatch || a.document && 11 !== a.document.nodeType) return d
                    } catch (e) {
                    }
                    return ga(b, n, null, [a]).length > 0
                }, ga.contains = function (a, b) {
                    return (a.ownerDocument || a) !== n && m(a), t(a, b)
                }, ga.attr = function (a, b) {
                    (a.ownerDocument || a) !== n && m(a);
                    var e = d.attrHandle[b.toLowerCase()],
                        f = e && C.call(d.attrHandle, b.toLowerCase()) ? e(a, b, !p) : void 0;
                    return void 0 !== f ? f : c.attributes || !p ? a.getAttribute(b) : (f = a.getAttributeNode(b)) && f.specified ? f.value : null
                }, ga.escape = function (a) {
                    return (a + "").replace(ba, ca)
                }, ga.error = function (a) {
                    throw new Error("Syntax error, unrecognized expression: " + a)
                }, ga.uniqueSort = function (a) {
                    var b, d = [], e = 0, f = 0;
                    if (l = !c.detectDuplicates, k = !c.sortStable && a.slice(0), a.sort(B), l) {
                        while (b = a[f++]) b === a[f] && (e = d.push(f));
                        while (e--) a.splice(d[e], 1)
                    }
                    return k = null, a
                }, e = ga.getText = function (a) {
                    var b, c = "", d = 0, f = a.nodeType;
                    if (f) {
                        if (1 === f || 9 === f || 11 === f) {
                            if ("string" == typeof a.textContent) return a.textContent;
                            for (a = a.firstChild; a; a = a.nextSibling) c += e(a)
                        } else if (3 === f || 4 === f) return a.nodeValue
                    } else while (b = a[d++]) c += e(b);
                    return c
                }, d = ga.selectors = {
                    cacheLength: 50,
                    createPseudo: ia,
                    match: V,
                    attrHandle: {},
                    find: {},
                    relative: {
                        ">": {dir: "parentNode", first: !0},
                        " ": {dir: "parentNode"},
                        "+": {dir: "previousSibling", first: !0},
                        "~": {dir: "previousSibling"}
                    },
                    preFilter: {
                        ATTR: function (a) {
                            return a[1] = a[1].replace(_, aa), a[3] = (a[3] || a[4] || a[5] || "").replace(_, aa), "~=" === a[2] && (a[3] = " " + a[3] + " "), a.slice(0, 4)
                        }, CHILD: function (a) {
                            return a[1] = a[1].toLowerCase(), "nth" === a[1].slice(0, 3) ? (a[3] || ga.error(a[0]), a[4] = +(a[4] ? a[5] + (a[6] || 1) : 2 * ("even" === a[3] || "odd" === a[3])), a[5] = +(a[7] + a[8] || "odd" === a[3])) : a[3] && ga.error(a[0]), a
                        }, PSEUDO: function (a) {
                            var b, c = !a[6] && a[2];
                            return V.CHILD.test(a[0]) ? null : (a[3] ? a[2] = a[4] || a[5] || "" : c && T.test(c) && (b = g(c, !0)) && (b = c.indexOf(")", c.length - b) - c.length) && (a[0] = a[0].slice(0, b), a[2] = c.slice(0, b)), a.slice(0, 3))
                        }
                    },
                    filter: {
                        TAG: function (a) {
                            var b = a.replace(_, aa).toLowerCase();
                            return "*" === a ? function () {
                                return !0
                            } : function (a) {
                                return a.nodeName && a.nodeName.toLowerCase() === b
                            }
                        }, CLASS: function (a) {
                            var b = y[a + " "];
                            return b || (b = new RegExp("(^|" + K + ")" + a + "(" + K + "|$)")) && y(a, function (a) {
                                return b.test("string" == typeof a.className && a.className || "undefined" != typeof a.getAttribute && a.getAttribute("class") || "")
                            })
                        }, ATTR: function (a, b, c) {
                            return function (d) {
                                var e = ga.attr(d, a);
                                return null == e ? "!=" === b : !b || (e += "", "=" === b ? e === c : "!=" === b ? e !== c : "^=" === b ? c && 0 === e.indexOf(c) : "*=" === b ? c && e.indexOf(c) > -1 : "$=" === b ? c && e.slice(-c.length) === c : "~=" === b ? (" " + e.replace(O, " ") + " ").indexOf(c) > -1 : "|=" === b && (e === c || e.slice(0, c.length + 1) === c + "-"))
                            }
                        }, CHILD: function (a, b, c, d, e) {
                            var f = "nth" !== a.slice(0, 3), g = "last" !== a.slice(-4), h = "of-type" === b;
                            return 1 === d && 0 === e ? function (a) {
                                return !!a.parentNode
                            } : function (b, c, i) {
                                var j, k, l, m, n, o, p = f !== g ? "nextSibling" : "previousSibling", q = b.parentNode,
                                    r = h && b.nodeName.toLowerCase(), s = !i && !h, t = !1;
                                if (q) {
                                    if (f) {
                                        while (p) {
                                            m = b;
                                            while (m = m[p]) if (h ? m.nodeName.toLowerCase() === r : 1 === m.nodeType) return !1;
                                            o = p = "only" === a && !o && "nextSibling"
                                        }
                                        return !0
                                    }
                                    if (o = [g ? q.firstChild : q.lastChild], g && s) {
                                        m = q, l = m[u] || (m[u] = {}), k = l[m.uniqueID] || (l[m.uniqueID] = {}), j = k[a] || [], n = j[0] === w && j[1], t = n && j[2], m = n && q.childNodes[n];
                                        while (m = ++n && m && m[p] || (t = n = 0) || o.pop()) if (1 === m.nodeType && ++t && m === b) {
                                            k[a] = [w, n, t];
                                            break
                                        }
                                    } else if (s && (m = b, l = m[u] || (m[u] = {}), k = l[m.uniqueID] || (l[m.uniqueID] = {}), j = k[a] || [], n = j[0] === w && j[1], t = n), t === !1) while (m = ++n && m && m[p] || (t = n = 0) || o.pop()) if ((h ? m.nodeName.toLowerCase() === r : 1 === m.nodeType) && ++t && (s && (l = m[u] || (m[u] = {}), k = l[m.uniqueID] || (l[m.uniqueID] = {}), k[a] = [w, t]), m === b)) break;
                                    return t -= e, t === d || t % d === 0 && t / d >= 0
                                }
                            }
                        }, PSEUDO: function (a, b) {
                            var c,
                                e = d.pseudos[a] || d.setFilters[a.toLowerCase()] || ga.error("unsupported pseudo: " + a);
                            return e[u] ? e(b) : e.length > 1 ? (c = [a, a, "", b], d.setFilters.hasOwnProperty(a.toLowerCase()) ? ia(function (a, c) {
                                var d, f = e(a, b), g = f.length;
                                while (g--) d = I(a, f[g]), a[d] = !(c[d] = f[g])
                            }) : function (a) {
                                return e(a, 0, c)
                            }) : e
                        }
                    },
                    pseudos: {
                        not: ia(function (a) {
                            var b = [], c = [], d = h(a.replace(P, "$1"));
                            return d[u] ? ia(function (a, b, c, e) {
                                var f, g = d(a, null, e, []), h = a.length;
                                while (h--) (f = g[h]) && (a[h] = !(b[h] = f))
                            }) : function (a, e, f) {
                                return b[0] = a, d(b, null, f, c), b[0] = null, !c.pop()
                            }
                        }), has: ia(function (a) {
                            return function (b) {
                                return ga(a, b).length > 0
                            }
                        }), contains: ia(function (a) {
                            return a = a.replace(_, aa), function (b) {
                                return (b.textContent || b.innerText || e(b)).indexOf(a) > -1
                            }
                        }), lang: ia(function (a) {
                            return U.test(a || "") || ga.error("unsupported lang: " + a), a = a.replace(_, aa).toLowerCase(), function (b) {
                                var c;
                                do if (c = p ? b.lang : b.getAttribute("xml:lang") || b.getAttribute("lang")) return c = c.toLowerCase(), c === a || 0 === c.indexOf(a + "-"); while ((b = b.parentNode) && 1 === b.nodeType);
                                return !1
                            }
                        }), target: function (b) {
                            var c = a.location && a.location.hash;
                            return c && c.slice(1) === b.id
                        }, root: function (a) {
                            return a === o
                        }, focus: function (a) {
                            return a === n.activeElement && (!n.hasFocus || n.hasFocus()) && !!(a.type || a.href || ~a.tabIndex)
                        }, enabled: oa(!1), disabled: oa(!0), checked: function (a) {
                            var b = a.nodeName.toLowerCase();
                            return "input" === b && !!a.checked || "option" === b && !!a.selected
                        }, selected: function (a) {
                            return a.parentNode && a.parentNode.selectedIndex, a.selected === !0
                        }, empty: function (a) {
                            for (a = a.firstChild; a; a = a.nextSibling) if (a.nodeType < 6) return !1;
                            return !0
                        }, parent: function (a) {
                            return !d.pseudos.empty(a)
                        }, header: function (a) {
                            return X.test(a.nodeName)
                        }, input: function (a) {
                            return W.test(a.nodeName)
                        }, button: function (a) {
                            var b = a.nodeName.toLowerCase();
                            return "input" === b && "button" === a.type || "button" === b
                        }, text: function (a) {
                            var b;
                            return "input" === a.nodeName.toLowerCase() && "text" === a.type && (null == (b = a.getAttribute("type")) || "text" === b.toLowerCase())
                        }, first: pa(function () {
                            return [0]
                        }), last: pa(function (a, b) {
                            return [b - 1]
                        }), eq: pa(function (a, b, c) {
                            return [c < 0 ? c + b : c]
                        }), even: pa(function (a, b) {
                            for (var c = 0; c < b; c += 2) a.push(c);
                            return a
                        }), odd: pa(function (a, b) {
                            for (var c = 1; c < b; c += 2) a.push(c);
                            return a
                        }), lt: pa(function (a, b, c) {
                            for (var d = c < 0 ? c + b : c; --d >= 0;) a.push(d);
                            return a
                        }), gt: pa(function (a, b, c) {
                            for (var d = c < 0 ? c + b : c; ++d < b;) a.push(d);
                            return a
                        })
                    }
                }, d.pseudos.nth = d.pseudos.eq;
                for (b in{radio: !0, checkbox: !0, file: !0, password: !0, image: !0}) d.pseudos[b] = ma(b);
                for (b in{submit: !0, reset: !0}) d.pseudos[b] = na(b);

                function ra() {
                }

                ra.prototype = d.filters = d.pseudos, d.setFilters = new ra, g = ga.tokenize = function (a, b) {
                    var c, e, f, g, h, i, j, k = z[a + " "];
                    if (k) return b ? 0 : k.slice(0);
                    h = a, i = [], j = d.preFilter;
                    while (h) {
                        c && !(e = Q.exec(h)) || (e && (h = h.slice(e[0].length) || h), i.push(f = [])), c = !1, (e = R.exec(h)) && (c = e.shift(), f.push({
                            value: c,
                            type: e[0].replace(P, " ")
                        }), h = h.slice(c.length));
                        for (g in d.filter) !(e = V[g].exec(h)) || j[g] && !(e = j[g](e)) || (c = e.shift(), f.push({
                            value: c,
                            type: g,
                            matches: e
                        }), h = h.slice(c.length));
                        if (!c) break
                    }
                    return b ? h.length : h ? ga.error(a) : z(a, i).slice(0)
                };

                function sa(a) {
                    for (var b = 0, c = a.length, d = ""; b < c; b++) d += a[b].value;
                    return d
                }

                function ta(a, b, c) {
                    var d = b.dir, e = b.next, f = e || d, g = c && "parentNode" === f, h = x++;
                    return b.first ? function (b, c, e) {
                        while (b = b[d]) if (1 === b.nodeType || g) return a(b, c, e);
                        return !1
                    } : function (b, c, i) {
                        var j, k, l, m = [w, h];
                        if (i) {
                            while (b = b[d]) if ((1 === b.nodeType || g) && a(b, c, i)) return !0
                        } else while (b = b[d]) if (1 === b.nodeType || g) if (l = b[u] || (b[u] = {}), k = l[b.uniqueID] || (l[b.uniqueID] = {}), e && e === b.nodeName.toLowerCase()) b = b[d] || b; else {
                            if ((j = k[f]) && j[0] === w && j[1] === h) return m[2] = j[2];
                            if (k[f] = m, m[2] = a(b, c, i)) return !0
                        }
                        return !1
                    }
                }

                function ua(a) {
                    return a.length > 1 ? function (b, c, d) {
                        var e = a.length;
                        while (e--) if (!a[e](b, c, d)) return !1;
                        return !0
                    } : a[0]
                }

                function va(a, b, c) {
                    for (var d = 0, e = b.length; d < e; d++) ga(a, b[d], c);
                    return c
                }

                function wa(a, b, c, d, e) {
                    for (var f, g = [], h = 0, i = a.length, j = null != b; h < i; h++) (f = a[h]) && (c && !c(f, d, e) || (g.push(f), j && b.push(h)));
                    return g
                }

                function xa(a, b, c, d, e, f) {
                    return d && !d[u] && (d = xa(d)), e && !e[u] && (e = xa(e, f)), ia(function (f, g, h, i) {
                        var j, k, l, m = [], n = [], o = g.length, p = f || va(b || "*", h.nodeType ? [h] : h, []),
                            q = !a || !f && b ? p : wa(p, m, a, h, i), r = c ? e || (f ? a : o || d) ? [] : g : q;
                        if (c && c(q, r, h, i), d) {
                            j = wa(r, n), d(j, [], h, i), k = j.length;
                            while (k--) (l = j[k]) && (r[n[k]] = !(q[n[k]] = l))
                        }
                        if (f) {
                            if (e || a) {
                                if (e) {
                                    j = [], k = r.length;
                                    while (k--) (l = r[k]) && j.push(q[k] = l);
                                    e(null, r = [], j, i)
                                }
                                k = r.length;
                                while (k--) (l = r[k]) && (j = e ? I(f, l) : m[k]) > -1 && (f[j] = !(g[j] = l))
                            }
                        } else r = wa(r === g ? r.splice(o, r.length) : r), e ? e(null, g, r, i) : G.apply(g, r)
                    })
                }

                function ya(a) {
                    for (var b, c, e, f = a.length, g = d.relative[a[0].type], h = g || d.relative[" "], i = g ? 1 : 0, k = ta(function (a) {
                        return a === b
                    }, h, !0), l = ta(function (a) {
                        return I(b, a) > -1
                    }, h, !0), m = [function (a, c, d) {
                        var e = !g && (d || c !== j) || ((b = c).nodeType ? k(a, c, d) : l(a, c, d));
                        return b = null, e
                    }]; i < f; i++) if (c = d.relative[a[i].type]) m = [ta(ua(m), c)]; else {
                        if (c = d.filter[a[i].type].apply(null, a[i].matches), c[u]) {
                            for (e = ++i; e < f; e++) if (d.relative[a[e].type]) break;
                            return xa(i > 1 && ua(m), i > 1 && sa(a.slice(0, i - 1).concat({value: " " === a[i - 2].type ? "*" : ""})).replace(P, "$1"), c, i < e && ya(a.slice(i, e)), e < f && ya(a = a.slice(e)), e < f && sa(a))
                        }
                        m.push(c)
                    }
                    return ua(m)
                }

                function za(a, b) {
                    var c = b.length > 0, e = a.length > 0, f = function (f, g, h, i, k) {
                        var l, o, q, r = 0, s = "0", t = f && [], u = [], v = j, x = f || e && d.find.TAG("*", k),
                            y = w += null == v ? 1 : Math.random() || .1, z = x.length;
                        for (k && (j = g === n || g || k); s !== z && null != (l = x[s]); s++) {
                            if (e && l) {
                                o = 0, g || l.ownerDocument === n || (m(l), h = !p);
                                while (q = a[o++]) if (q(l, g || n, h)) {
                                    i.push(l);
                                    break
                                }
                                k && (w = y)
                            }
                            c && ((l = !q && l) && r--, f && t.push(l))
                        }
                        if (r += s, c && s !== r) {
                            o = 0;
                            while (q = b[o++]) q(t, u, g, h);
                            if (f) {
                                if (r > 0) while (s--) t[s] || u[s] || (u[s] = E.call(i));
                                u = wa(u)
                            }
                            G.apply(i, u), k && !f && u.length > 0 && r + b.length > 1 && ga.uniqueSort(i)
                        }
                        return k && (w = y, j = v), t
                    };
                    return c ? ia(f) : f
                }

                return h = ga.compile = function (a, b) {
                    var c, d = [], e = [], f = A[a + " "];
                    if (!f) {
                        b || (b = g(a)), c = b.length;
                        while (c--) f = ya(b[c]), f[u] ? d.push(f) : e.push(f);
                        f = A(a, za(e, d)), f.selector = a
                    }
                    return f
                }, i = ga.select = function (a, b, c, e) {
                    var f, i, j, k, l, m = "function" == typeof a && a, n = !e && g(a = m.selector || a);
                    if (c = c || [], 1 === n.length) {
                        if (i = n[0] = n[0].slice(0), i.length > 2 && "ID" === (j = i[0]).type && 9 === b.nodeType && p && d.relative[i[1].type]) {
                            if (b = (d.find.ID(j.matches[0].replace(_, aa), b) || [])[0], !b) return c;
                            m && (b = b.parentNode), a = a.slice(i.shift().value.length)
                        }
                        f = V.needsContext.test(a) ? 0 : i.length;
                        while (f--) {
                            if (j = i[f], d.relative[k = j.type]) break;
                            if ((l = d.find[k]) && (e = l(j.matches[0].replace(_, aa), $.test(i[0].type) && qa(b.parentNode) || b))) {
                                if (i.splice(f, 1), a = e.length && sa(i), !a) return G.apply(c, e), c;
                                break
                            }
                        }
                    }
                    return (m || h(a, n))(e, b, !p, c, !b || $.test(a) && qa(b.parentNode) || b), c
                }, c.sortStable = u.split("").sort(B).join("") === u, c.detectDuplicates = !!l, m(), c.sortDetached = ja(function (a) {
                    return 1 & a.compareDocumentPosition(n.createElement("fieldset"))
                }), ja(function (a) {
                    return a.innerHTML = "<a href='#'></a>", "#" === a.firstChild.getAttribute("href")
                }) || ka("type|href|height|width", function (a, b, c) {
                    if (!c) return a.getAttribute(b, "type" === b.toLowerCase() ? 1 : 2)
                }), c.attributes && ja(function (a) {
                    return a.innerHTML = "<input/>", a.firstChild.setAttribute("value", ""), "" === a.firstChild.getAttribute("value")
                }) || ka("value", function (a, b, c) {
                    if (!c && "input" === a.nodeName.toLowerCase()) return a.defaultValue
                }), ja(function (a) {
                    return null == a.getAttribute("disabled")
                }) || ka(J, function (a, b, c) {
                    var d;
                    if (!c) return a[b] === !0 ? b.toLowerCase() : (d = a.getAttributeNode(b)) && d.specified ? d.value : null
                }), ga
            }(a);
            r.find = x, r.expr = x.selectors, r.expr[":"] = r.expr.pseudos, r.uniqueSort = r.unique = x.uniqueSort, r.text = x.getText, r.isXMLDoc = x.isXML, r.contains = x.contains, r.escapeSelector = x.escape;
            var y = function (a, b, c) {
                var d = [], e = void 0 !== c;
                while ((a = a[b]) && 9 !== a.nodeType) if (1 === a.nodeType) {
                    if (e && r(a).is(c)) break;
                    d.push(a)
                }
                return d
            }, z = function (a, b) {
                for (var c = []; a; a = a.nextSibling) 1 === a.nodeType && a !== b && c.push(a);
                return c
            }, A = r.expr.match.needsContext;

            function B(a, b) {
                return a.nodeName && a.nodeName.toLowerCase() === b.toLowerCase()
            }

            var C = /^<([a-z][^\/\0>:\x20\t\r\n\f]*)[\x20\t\r\n\f]*\/?>(?:<\/\1>|)$/i, D = /^.[^:#\[\.,]*$/;

            function E(a, b, c) {
                return r.isFunction(b) ? r.grep(a, function (a, d) {
                    return !!b.call(a, d, a) !== c
                }) : b.nodeType ? r.grep(a, function (a) {
                    return a === b !== c
                }) : "string" != typeof b ? r.grep(a, function (a) {
                    return i.call(b, a) > -1 !== c
                }) : D.test(b) ? r.filter(b, a, c) : (b = r.filter(b, a), r.grep(a, function (a) {
                    return i.call(b, a) > -1 !== c && 1 === a.nodeType
                }))
            }

            r.filter = function (a, b, c) {
                var d = b[0];
                return c && (a = ":not(" + a + ")"), 1 === b.length && 1 === d.nodeType ? r.find.matchesSelector(d, a) ? [d] : [] : r.find.matches(a, r.grep(b, function (a) {
                    return 1 === a.nodeType
                }))
            }, r.fn.extend({
                find: function (a) {
                    var b, c, d = this.length, e = this;
                    if ("string" != typeof a) return this.pushStack(r(a).filter(function () {
                        for (b = 0; b < d; b++) if (r.contains(e[b], this)) return !0
                    }));
                    for (c = this.pushStack([]), b = 0; b < d; b++) r.find(a, e[b], c);
                    return d > 1 ? r.uniqueSort(c) : c
                }, filter: function (a) {
                    return this.pushStack(E(this, a || [], !1))
                }, not: function (a) {
                    return this.pushStack(E(this, a || [], !0))
                }, is: function (a) {
                    return !!E(this, "string" == typeof a && A.test(a) ? r(a) : a || [], !1).length
                }
            });
            var F, G = /^(?:\s*(<[\w\W]+>)[^>]*|#([\w-]+))$/, H = r.fn.init = function (a, b, c) {
                var e, f;
                if (!a) return this;
                if (c = c || F, "string" == typeof a) {
                    if (e = "<" === a[0] && ">" === a[a.length - 1] && a.length >= 3 ? [null, a, null] : G.exec(a), !e || !e[1] && b) return !b || b.jquery ? (b || c).find(a) : this.constructor(b).find(a);
                    if (e[1]) {
                        if (b = b instanceof r ? b[0] : b, r.merge(this, r.parseHTML(e[1], b && b.nodeType ? b.ownerDocument || b : d, !0)), C.test(e[1]) && r.isPlainObject(b)) for (e in b) r.isFunction(this[e]) ? this[e](b[e]) : this.attr(e, b[e]);
                        return this
                    }
                    return f = d.getElementById(e[2]), f && (this[0] = f, this.length = 1), this
                }
                return a.nodeType ? (this[0] = a, this.length = 1, this) : r.isFunction(a) ? void 0 !== c.ready ? c.ready(a) : a(r) : r.makeArray(a, this)
            };
            H.prototype = r.fn, F = r(d);
            var I = /^(?:parents|prev(?:Until|All))/, J = {children: !0, contents: !0, next: !0, prev: !0};
            r.fn.extend({
                has: function (a) {
                    var b = r(a, this), c = b.length;
                    return this.filter(function () {
                        for (var a = 0; a < c; a++) if (r.contains(this, b[a])) return !0
                    })
                }, closest: function (a, b) {
                    var c, d = 0, e = this.length, f = [], g = "string" != typeof a && r(a);
                    if (!A.test(a)) for (; d < e; d++) for (c = this[d]; c && c !== b; c = c.parentNode) if (c.nodeType < 11 && (g ? g.index(c) > -1 : 1 === c.nodeType && r.find.matchesSelector(c, a))) {
                        f.push(c);
                        break
                    }
                    return this.pushStack(f.length > 1 ? r.uniqueSort(f) : f)
                }, index: function (a) {
                    return a ? "string" == typeof a ? i.call(r(a), this[0]) : i.call(this, a.jquery ? a[0] : a) : this[0] && this[0].parentNode ? this.first().prevAll().length : -1
                }, add: function (a, b) {
                    return this.pushStack(r.uniqueSort(r.merge(this.get(), r(a, b))))
                }, addBack: function (a) {
                    return this.add(null == a ? this.prevObject : this.prevObject.filter(a))
                }
            });

            function K(a, b) {
                while ((a = a[b]) && 1 !== a.nodeType) ;
                return a
            }

            r.each({
                parent: function (a) {
                    var b = a.parentNode;
                    return b && 11 !== b.nodeType ? b : null
                }, parents: function (a) {
                    return y(a, "parentNode")
                }, parentsUntil: function (a, b, c) {
                    return y(a, "parentNode", c)
                }, next: function (a) {
                    return K(a, "nextSibling")
                }, prev: function (a) {
                    return K(a, "previousSibling")
                }, nextAll: function (a) {
                    return y(a, "nextSibling")
                }, prevAll: function (a) {
                    return y(a, "previousSibling")
                }, nextUntil: function (a, b, c) {
                    return y(a, "nextSibling", c)
                }, prevUntil: function (a, b, c) {
                    return y(a, "previousSibling", c)
                }, siblings: function (a) {
                    return z((a.parentNode || {}).firstChild, a)
                }, children: function (a) {
                    return z(a.firstChild)
                }, contents: function (a) {
                    return B(a, "iframe") ? a.contentDocument : (B(a, "template") && (a = a.content || a), r.merge([], a.childNodes))
                }
            }, function (a, b) {
                r.fn[a] = function (c, d) {
                    var e = r.map(this, b, c);
                    return "Until" !== a.slice(-5) && (d = c), d && "string" == typeof d && (e = r.filter(d, e)), this.length > 1 && (J[a] || r.uniqueSort(e), I.test(a) && e.reverse()), this.pushStack(e)
                }
            });
            var L = /[^\x20\t\r\n\f]+/g;

            function M(a) {
                var b = {};
                return r.each(a.match(L) || [], function (a, c) {
                    b[c] = !0
                }), b
            }

            r.Callbacks = function (a) {
                a = "string" == typeof a ? M(a) : r.extend({}, a);
                var b, c, d, e, f = [], g = [], h = -1, i = function () {
                    for (e = e || a.once, d = b = !0; g.length; h = -1) {
                        c = g.shift();
                        while (++h < f.length) f[h].apply(c[0], c[1]) === !1 && a.stopOnFalse && (h = f.length, c = !1)
                    }
                    a.memory || (c = !1), b = !1, e && (f = c ? [] : "")
                }, j = {
                    add: function () {
                        return f && (c && !b && (h = f.length - 1, g.push(c)), function d(b) {
                            r.each(b, function (b, c) {
                                r.isFunction(c) ? a.unique && j.has(c) || f.push(c) : c && c.length && "string" !== r.type(c) && d(c)
                            })
                        }(arguments), c && !b && i()), this
                    }, remove: function () {
                        return r.each(arguments, function (a, b) {
                            var c;
                            while ((c = r.inArray(b, f, c)) > -1) f.splice(c, 1), c <= h && h--
                        }), this
                    }, has: function (a) {
                        return a ? r.inArray(a, f) > -1 : f.length > 0
                    }, empty: function () {
                        return f && (f = []), this
                    }, disable: function () {
                        return e = g = [], f = c = "", this
                    }, disabled: function () {
                        return !f
                    }, lock: function () {
                        return e = g = [], c || b || (f = c = ""), this
                    }, locked: function () {
                        return !!e
                    }, fireWith: function (a, c) {
                        return e || (c = c || [], c = [a, c.slice ? c.slice() : c], g.push(c), b || i()), this
                    }, fire: function () {
                        return j.fireWith(this, arguments), this
                    }, fired: function () {
                        return !!d
                    }
                };
                return j
            };

            function N(a) {
                return a
            }

            function O(a) {
                throw a
            }

            function P(a, b, c, d) {
                var e;
                try {
                    a && r.isFunction(e = a.promise) ? e.call(a).done(b).fail(c) : a && r.isFunction(e = a.then) ? e.call(a, b, c) : b.apply(void 0, [a].slice(d))
                } catch (a) {
                    c.apply(void 0, [a])
                }
            }

            r.extend({
                Deferred: function (b) {
                    var c = [["notify", "progress", r.Callbacks("memory"), r.Callbacks("memory"), 2], ["resolve", "done", r.Callbacks("once memory"), r.Callbacks("once memory"), 0, "resolved"], ["reject", "fail", r.Callbacks("once memory"), r.Callbacks("once memory"), 1, "rejected"]],
                        d = "pending", e = {
                            state: function () {
                                return d
                            }, always: function () {
                                return f.done(arguments).fail(arguments), this
                            }, "catch": function (a) {
                                return e.then(null, a)
                            }, pipe: function () {
                                var a = arguments;
                                return r.Deferred(function (b) {
                                    r.each(c, function (c, d) {
                                        var e = r.isFunction(a[d[4]]) && a[d[4]];
                                        f[d[1]](function () {
                                            var a = e && e.apply(this, arguments);
                                            a && r.isFunction(a.promise) ? a.promise().progress(b.notify).done(b.resolve).fail(b.reject) : b[d[0] + "With"](this, e ? [a] : arguments)
                                        })
                                    }), a = null
                                }).promise()
                            }, then: function (b, d, e) {
                                var f = 0;

                                function g(b, c, d, e) {
                                    return function () {
                                        var h = this, i = arguments, j = function () {
                                            var a, j;
                                            if (!(b < f)) {
                                                if (a = d.apply(h, i), a === c.promise()) throw new TypeError("Thenable self-resolution");
                                                j = a && ("object" == typeof a || "function" == typeof a) && a.then, r.isFunction(j) ? e ? j.call(a, g(f, c, N, e), g(f, c, O, e)) : (f++, j.call(a, g(f, c, N, e), g(f, c, O, e), g(f, c, N, c.notifyWith))) : (d !== N && (h = void 0, i = [a]), (e || c.resolveWith)(h, i))
                                            }
                                        }, k = e ? j : function () {
                                            try {
                                                j()
                                            } catch (a) {
                                                r.Deferred.exceptionHook && r.Deferred.exceptionHook(a, k.stackTrace), b + 1 >= f && (d !== O && (h = void 0, i = [a]), c.rejectWith(h, i))
                                            }
                                        };
                                        b ? k() : (r.Deferred.getStackHook && (k.stackTrace = r.Deferred.getStackHook()), a.setTimeout(k))
                                    }
                                }

                                return r.Deferred(function (a) {
                                    c[0][3].add(g(0, a, r.isFunction(e) ? e : N, a.notifyWith)), c[1][3].add(g(0, a, r.isFunction(b) ? b : N)), c[2][3].add(g(0, a, r.isFunction(d) ? d : O))
                                }).promise()
                            }, promise: function (a) {
                                return null != a ? r.extend(a, e) : e
                            }
                        }, f = {};
                    return r.each(c, function (a, b) {
                        var g = b[2], h = b[5];
                        e[b[1]] = g.add, h && g.add(function () {
                            d = h
                        }, c[3 - a][2].disable, c[0][2].lock), g.add(b[3].fire), f[b[0]] = function () {
                            return f[b[0] + "With"](this === f ? void 0 : this, arguments), this
                        }, f[b[0] + "With"] = g.fireWith
                    }), e.promise(f), b && b.call(f, f), f
                }, when: function (a) {
                    var b = arguments.length, c = b, d = Array(c), e = f.call(arguments), g = r.Deferred(),
                        h = function (a) {
                            return function (c) {
                                d[a] = this, e[a] = arguments.length > 1 ? f.call(arguments) : c, --b || g.resolveWith(d, e)
                            }
                        };
                    if (b <= 1 && (P(a, g.done(h(c)).resolve, g.reject, !b), "pending" === g.state() || r.isFunction(e[c] && e[c].then))) return g.then();
                    while (c--) P(e[c], h(c), g.reject);
                    return g.promise()
                }
            });
            var Q = /^(Eval|Internal|Range|Reference|Syntax|Type|URI)Error$/;
            r.Deferred.exceptionHook = function (b, c) {
                a.console && a.console.warn && b && Q.test(b.name) && a.console.warn("jQuery.Deferred exception: " + b.message, b.stack, c)
            }, r.readyException = function (b) {
                a.setTimeout(function () {
                    throw b
                })
            };
            var R = r.Deferred();
            r.fn.ready = function (a) {
                return R.then(a)["catch"](function (a) {
                    r.readyException(a)
                }), this
            }, r.extend({
                isReady: !1, readyWait: 1, ready: function (a) {
                    (a === !0 ? --r.readyWait : r.isReady) || (r.isReady = !0, a !== !0 && --r.readyWait > 0 || R.resolveWith(d, [r]))
                }
            }), r.ready.then = R.then;

            function S() {
                d.removeEventListener("DOMContentLoaded", S),
                    a.removeEventListener("load", S), r.ready()
            }

            "complete" === d.readyState || "loading" !== d.readyState && !d.documentElement.doScroll ? a.setTimeout(r.ready) : (d.addEventListener("DOMContentLoaded", S), a.addEventListener("load", S));
            var T = function (a, b, c, d, e, f, g) {
                var h = 0, i = a.length, j = null == c;
                if ("object" === r.type(c)) {
                    e = !0;
                    for (h in c) T(a, b, h, c[h], !0, f, g)
                } else if (void 0 !== d && (e = !0, r.isFunction(d) || (g = !0), j && (g ? (b.call(a, d), b = null) : (j = b, b = function (a, b, c) {
                        return j.call(r(a), c)
                    })), b)) for (; h < i; h++) b(a[h], c, g ? d : d.call(a[h], h, b(a[h], c)));
                return e ? a : j ? b.call(a) : i ? b(a[0], c) : f
            }, U = function (a) {
                return 1 === a.nodeType || 9 === a.nodeType || !+a.nodeType
            };

            function V() {
                this.expando = r.expando + V.uid++
            }

            V.uid = 1, V.prototype = {
                cache: function (a) {
                    var b = a[this.expando];
                    return b || (b = {}, U(a) && (a.nodeType ? a[this.expando] = b : Object.defineProperty(a, this.expando, {
                        value: b,
                        configurable: !0
                    }))), b
                }, set: function (a, b, c) {
                    var d, e = this.cache(a);
                    if ("string" == typeof b) e[r.camelCase(b)] = c; else for (d in b) e[r.camelCase(d)] = b[d];
                    return e
                }, get: function (a, b) {
                    return void 0 === b ? this.cache(a) : a[this.expando] && a[this.expando][r.camelCase(b)]
                }, access: function (a, b, c) {
                    return void 0 === b || b && "string" == typeof b && void 0 === c ? this.get(a, b) : (this.set(a, b, c), void 0 !== c ? c : b)
                }, remove: function (a, b) {
                    var c, d = a[this.expando];
                    if (void 0 !== d) {
                        if (void 0 !== b) {
                            Array.isArray(b) ? b = b.map(r.camelCase) : (b = r.camelCase(b), b = b in d ? [b] : b.match(L) || []), c = b.length;
                            while (c--) delete d[b[c]]
                        }
                        (void 0 === b || r.isEmptyObject(d)) && (a.nodeType ? a[this.expando] = void 0 : delete a[this.expando])
                    }
                }, hasData: function (a) {
                    var b = a[this.expando];
                    return void 0 !== b && !r.isEmptyObject(b)
                }
            };
            var W = new V, X = new V, Y = /^(?:\{[\w\W]*\}|\[[\w\W]*\])$/, Z = /[A-Z]/g;

            function $(a) {
                return "true" === a || "false" !== a && ("null" === a ? null : a === +a + "" ? +a : Y.test(a) ? JSON.parse(a) : a)
            }

            function _(a, b, c) {
                var d;
                if (void 0 === c && 1 === a.nodeType) if (d = "data-" + b.replace(Z, "-$&").toLowerCase(), c = a.getAttribute(d), "string" == typeof c) {
                    try {
                        c = $(c)
                    } catch (e) {
                    }
                    X.set(a, b, c)
                } else c = void 0;
                return c
            }

            r.extend({
                hasData: function (a) {
                    return X.hasData(a) || W.hasData(a)
                }, data: function (a, b, c) {
                    return X.access(a, b, c)
                }, removeData: function (a, b) {
                    X.remove(a, b)
                }, _data: function (a, b, c) {
                    return W.access(a, b, c)
                }, _removeData: function (a, b) {
                    W.remove(a, b)
                }
            }), r.fn.extend({
                data: function (a, b) {
                    var c, d, e, f = this[0], g = f && f.attributes;
                    if (void 0 === a) {
                        if (this.length && (e = X.get(f), 1 === f.nodeType && !W.get(f, "hasDataAttrs"))) {
                            c = g.length;
                            while (c--) g[c] && (d = g[c].name, 0 === d.indexOf("data-") && (d = r.camelCase(d.slice(5)), _(f, d, e[d])));
                            W.set(f, "hasDataAttrs", !0)
                        }
                        return e
                    }
                    return "object" == typeof a ? this.each(function () {
                        X.set(this, a)
                    }) : T(this, function (b) {
                        var c;
                        if (f && void 0 === b) {
                            if (c = X.get(f, a), void 0 !== c) return c;
                            if (c = _(f, a), void 0 !== c) return c
                        } else this.each(function () {
                            X.set(this, a, b)
                        })
                    }, null, b, arguments.length > 1, null, !0)
                }, removeData: function (a) {
                    return this.each(function () {
                        X.remove(this, a)
                    })
                }
            }), r.extend({
                queue: function (a, b, c) {
                    var d;
                    if (a) return b = (b || "fx") + "queue", d = W.get(a, b), c && (!d || Array.isArray(c) ? d = W.access(a, b, r.makeArray(c)) : d.push(c)), d || []
                }, dequeue: function (a, b) {
                    b = b || "fx";
                    var c = r.queue(a, b), d = c.length, e = c.shift(), f = r._queueHooks(a, b), g = function () {
                        r.dequeue(a, b)
                    };
                    "inprogress" === e && (e = c.shift(), d--), e && ("fx" === b && c.unshift("inprogress"), delete f.stop, e.call(a, g, f)), !d && f && f.empty.fire()
                }, _queueHooks: function (a, b) {
                    var c = b + "queueHooks";
                    return W.get(a, c) || W.access(a, c, {
                        empty: r.Callbacks("once memory").add(function () {
                            W.remove(a, [b + "queue", c])
                        })
                    })
                }
            }), r.fn.extend({
                queue: function (a, b) {
                    var c = 2;
                    return "string" != typeof a && (b = a, a = "fx", c--), arguments.length < c ? r.queue(this[0], a) : void 0 === b ? this : this.each(function () {
                        var c = r.queue(this, a, b);
                        r._queueHooks(this, a), "fx" === a && "inprogress" !== c[0] && r.dequeue(this, a)
                    })
                }, dequeue: function (a) {
                    return this.each(function () {
                        r.dequeue(this, a)
                    })
                }, clearQueue: function (a) {
                    return this.queue(a || "fx", [])
                }, promise: function (a, b) {
                    var c, d = 1, e = r.Deferred(), f = this, g = this.length, h = function () {
                        --d || e.resolveWith(f, [f])
                    };
                    "string" != typeof a && (b = a, a = void 0), a = a || "fx";
                    while (g--) c = W.get(f[g], a + "queueHooks"), c && c.empty && (d++, c.empty.add(h));
                    return h(), e.promise(b)
                }
            });
            var aa = /[+-]?(?:\d*\.|)\d+(?:[eE][+-]?\d+|)/.source,
                ba = new RegExp("^(?:([+-])=|)(" + aa + ")([a-z%]*)$", "i"), ca = ["Top", "Right", "Bottom", "Left"],
                da = function (a, b) {
                    return a = b || a, "none" === a.style.display || "" === a.style.display && r.contains(a.ownerDocument, a) && "none" === r.css(a, "display")
                }, ea = function (a, b, c, d) {
                    var e, f, g = {};
                    for (f in b) g[f] = a.style[f], a.style[f] = b[f];
                    e = c.apply(a, d || []);
                    for (f in b) a.style[f] = g[f];
                    return e
                };

            function fa(a, b, c, d) {
                var e, f = 1, g = 20, h = d ? function () {
                        return d.cur()
                    } : function () {
                        return r.css(a, b, "")
                    }, i = h(), j = c && c[3] || (r.cssNumber[b] ? "" : "px"),
                    k = (r.cssNumber[b] || "px" !== j && +i) && ba.exec(r.css(a, b));
                if (k && k[3] !== j) {
                    j = j || k[3], c = c || [], k = +i || 1;
                    do f = f || ".5", k /= f, r.style(a, b, k + j); while (f !== (f = h() / i) && 1 !== f && --g)
                }
                return c && (k = +k || +i || 0, e = c[1] ? k + (c[1] + 1) * c[2] : +c[2], d && (d.unit = j, d.start = k, d.end = e)), e
            }

            var ga = {};

            function ha(a) {
                var b, c = a.ownerDocument, d = a.nodeName, e = ga[d];
                return e ? e : (b = c.body.appendChild(c.createElement(d)), e = r.css(b, "display"), b.parentNode.removeChild(b), "none" === e && (e = "block"), ga[d] = e, e)
            }

            function ia(a, b) {
                for (var c, d, e = [], f = 0, g = a.length; f < g; f++) d = a[f], d.style && (c = d.style.display, b ? ("none" === c && (e[f] = W.get(d, "display") || null, e[f] || (d.style.display = "")), "" === d.style.display && da(d) && (e[f] = ha(d))) : "none" !== c && (e[f] = "none", W.set(d, "display", c)));
                for (f = 0; f < g; f++) null != e[f] && (a[f].style.display = e[f]);
                return a
            }

            r.fn.extend({
                show: function () {
                    return ia(this, !0)
                }, hide: function () {
                    return ia(this)
                }, toggle: function (a) {
                    return "boolean" == typeof a ? a ? this.show() : this.hide() : this.each(function () {
                        da(this) ? r(this).show() : r(this).hide()
                    })
                }
            });
            var ja = /^(?:checkbox|radio)$/i, ka = /<([a-z][^\/\0>\x20\t\r\n\f]+)/i, la = /^$|\/(?:java|ecma)script/i,
                ma = {
                    option: [1, "<select multiple='multiple'>", "</select>"],
                    thead: [1, "<table>", "</table>"],
                    col: [2, "<table><colgroup>", "</colgroup></table>"],
                    tr: [2, "<table><tbody>", "</tbody></table>"],
                    td: [3, "<table><tbody><tr>", "</tr></tbody></table>"],
                    _default: [0, "", ""]
                };
            ma.optgroup = ma.option, ma.tbody = ma.tfoot = ma.colgroup = ma.caption = ma.thead, ma.th = ma.td;

            function na(a, b) {
                var c;
                return c = "undefined" != typeof a.getElementsByTagName ? a.getElementsByTagName(b || "*") : "undefined" != typeof a.querySelectorAll ? a.querySelectorAll(b || "*") : [], void 0 === b || b && B(a, b) ? r.merge([a], c) : c
            }

            function oa(a, b) {
                for (var c = 0, d = a.length; c < d; c++) W.set(a[c], "globalEval", !b || W.get(b[c], "globalEval"))
            }

            var pa = /<|&#?\w+;/;

            function qa(a, b, c, d, e) {
                for (var f, g, h, i, j, k, l = b.createDocumentFragment(), m = [], n = 0, o = a.length; n < o; n++) if (f = a[n], f || 0 === f) if ("object" === r.type(f)) r.merge(m, f.nodeType ? [f] : f); else if (pa.test(f)) {
                    g = g || l.appendChild(b.createElement("div")), h = (ka.exec(f) || ["", ""])[1].toLowerCase(), i = ma[h] || ma._default, g.innerHTML = i[1] + r.htmlPrefilter(f) + i[2], k = i[0];
                    while (k--) g = g.lastChild;
                    r.merge(m, g.childNodes), g = l.firstChild, g.textContent = ""
                } else m.push(b.createTextNode(f));
                l.textContent = "", n = 0;
                while (f = m[n++]) if (d && r.inArray(f, d) > -1) e && e.push(f); else if (j = r.contains(f.ownerDocument, f), g = na(l.appendChild(f), "script"), j && oa(g), c) {
                    k = 0;
                    while (f = g[k++]) la.test(f.type || "") && c.push(f)
                }
                return l
            }

            !function () {
                var a = d.createDocumentFragment(), b = a.appendChild(d.createElement("div")),
                    c = d.createElement("input");
                c.setAttribute("type", "radio"), c.setAttribute("checked", "checked"), c.setAttribute("name", "t"), b.appendChild(c), o.checkClone = b.cloneNode(!0).cloneNode(!0).lastChild.checked, b.innerHTML = "<textarea>x</textarea>", o.noCloneChecked = !!b.cloneNode(!0).lastChild.defaultValue
            }();
            var ra = d.documentElement, sa = /^key/, ta = /^(?:mouse|pointer|contextmenu|drag|drop)|click/,
                ua = /^([^.]*)(?:\.(.+)|)/;

            function va() {
                return !0
            }

            function wa() {
                return !1
            }

            function xa() {
                try {
                    return d.activeElement
                } catch (a) {
                }
            }

            function ya(a, b, c, d, e, f) {
                var g, h;
                if ("object" == typeof b) {
                    "string" != typeof c && (d = d || c, c = void 0);
                    for (h in b) ya(a, h, c, d, b[h], f);
                    return a
                }
                if (null == d && null == e ? (e = c, d = c = void 0) : null == e && ("string" == typeof c ? (e = d, d = void 0) : (e = d, d = c, c = void 0)), e === !1) e = wa; else if (!e) return a;
                return 1 === f && (g = e, e = function (a) {
                    return r().off(a), g.apply(this, arguments)
                }, e.guid = g.guid || (g.guid = r.guid++)), a.each(function () {
                    r.event.add(this, b, e, d, c)
                })
            }

            r.event = {
                global: {}, add: function (a, b, c, d, e) {
                    var f, g, h, i, j, k, l, m, n, o, p, q = W.get(a);
                    if (q) {
                        c.handler && (f = c, c = f.handler, e = f.selector), e && r.find.matchesSelector(ra, e), c.guid || (c.guid = r.guid++), (i = q.events) || (i = q.events = {}), (g = q.handle) || (g = q.handle = function (b) {
                            return "undefined" != typeof r && r.event.triggered !== b.type ? r.event.dispatch.apply(a, arguments) : void 0
                        }), b = (b || "").match(L) || [""], j = b.length;
                        while (j--) h = ua.exec(b[j]) || [], n = p = h[1], o = (h[2] || "").split(".").sort(), n && (l = r.event.special[n] || {}, n = (e ? l.delegateType : l.bindType) || n, l = r.event.special[n] || {}, k = r.extend({
                            type: n,
                            origType: p,
                            data: d,
                            handler: c,
                            guid: c.guid,
                            selector: e,
                            needsContext: e && r.expr.match.needsContext.test(e),
                            namespace: o.join(".")
                        }, f), (m = i[n]) || (m = i[n] = [], m.delegateCount = 0, l.setup && l.setup.call(a, d, o, g) !== !1 || a.addEventListener && a.addEventListener(n, g)), l.add && (l.add.call(a, k), k.handler.guid || (k.handler.guid = c.guid)), e ? m.splice(m.delegateCount++, 0, k) : m.push(k), r.event.global[n] = !0)
                    }
                }, remove: function (a, b, c, d, e) {
                    var f, g, h, i, j, k, l, m, n, o, p, q = W.hasData(a) && W.get(a);
                    if (q && (i = q.events)) {
                        b = (b || "").match(L) || [""], j = b.length;
                        while (j--) if (h = ua.exec(b[j]) || [], n = p = h[1], o = (h[2] || "").split(".").sort(), n) {
                            l = r.event.special[n] || {}, n = (d ? l.delegateType : l.bindType) || n, m = i[n] || [], h = h[2] && new RegExp("(^|\\.)" + o.join("\\.(?:.*\\.|)") + "(\\.|$)"), g = f = m.length;
                            while (f--) k = m[f], !e && p !== k.origType || c && c.guid !== k.guid || h && !h.test(k.namespace) || d && d !== k.selector && ("**" !== d || !k.selector) || (m.splice(f, 1), k.selector && m.delegateCount--, l.remove && l.remove.call(a, k));
                            g && !m.length && (l.teardown && l.teardown.call(a, o, q.handle) !== !1 || r.removeEvent(a, n, q.handle), delete i[n])
                        } else for (n in i) r.event.remove(a, n + b[j], c, d, !0);
                        r.isEmptyObject(i) && W.remove(a, "handle events")
                    }
                }, dispatch: function (a) {
                    var b = r.event.fix(a), c, d, e, f, g, h, i = new Array(arguments.length),
                        j = (W.get(this, "events") || {})[b.type] || [], k = r.event.special[b.type] || {};
                    for (i[0] = b, c = 1; c < arguments.length; c++) i[c] = arguments[c];
                    if (b.delegateTarget = this, !k.preDispatch || k.preDispatch.call(this, b) !== !1) {
                        h = r.event.handlers.call(this, b, j), c = 0;
                        while ((f = h[c++]) && !b.isPropagationStopped()) {
                            b.currentTarget = f.elem, d = 0;
                            while ((g = f.handlers[d++]) && !b.isImmediatePropagationStopped()) b.rnamespace && !b.rnamespace.test(g.namespace) || (b.handleObj = g, b.data = g.data, e = ((r.event.special[g.origType] || {}).handle || g.handler).apply(f.elem, i), void 0 !== e && (b.result = e) === !1 && (b.preventDefault(), b.stopPropagation()))
                        }
                        return k.postDispatch && k.postDispatch.call(this, b), b.result
                    }
                }, handlers: function (a, b) {
                    var c, d, e, f, g, h = [], i = b.delegateCount, j = a.target;
                    if (i && j.nodeType && !("click" === a.type && a.button >= 1)) for (; j !== this; j = j.parentNode || this) if (1 === j.nodeType && ("click" !== a.type || j.disabled !== !0)) {
                        for (f = [], g = {}, c = 0; c < i; c++) d = b[c], e = d.selector + " ", void 0 === g[e] && (g[e] = d.needsContext ? r(e, this).index(j) > -1 : r.find(e, this, null, [j]).length), g[e] && f.push(d);
                        f.length && h.push({elem: j, handlers: f})
                    }
                    return j = this, i < b.length && h.push({elem: j, handlers: b.slice(i)}), h
                }, addProp: function (a, b) {
                    Object.defineProperty(r.Event.prototype, a, {
                        enumerable: !0,
                        configurable: !0,
                        get: r.isFunction(b) ? function () {
                            if (this.originalEvent) return b(this.originalEvent)
                        } : function () {
                            if (this.originalEvent) return this.originalEvent[a]
                        },
                        set: function (b) {
                            Object.defineProperty(this, a, {enumerable: !0, configurable: !0, writable: !0, value: b})
                        }
                    })
                }, fix: function (a) {
                    return a[r.expando] ? a : new r.Event(a)
                }, special: {
                    load: {noBubble: !0}, focus: {
                        trigger: function () {
                            if (this !== xa() && this.focus) return this.focus(), !1
                        }, delegateType: "focusin"
                    }, blur: {
                        trigger: function () {
                            if (this === xa() && this.blur) return this.blur(), !1
                        }, delegateType: "focusout"
                    }, click: {
                        trigger: function () {
                            if ("checkbox" === this.type && this.click && B(this, "input")) return this.click(), !1
                        }, _default: function (a) {
                            return B(a.target, "a")
                        }
                    }, beforeunload: {
                        postDispatch: function (a) {
                            void 0 !== a.result && a.originalEvent && (a.originalEvent.returnValue = a.result)
                        }
                    }
                }
            }, r.removeEvent = function (a, b, c) {
                a.removeEventListener && a.removeEventListener(b, c)
            }, r.Event = function (a, b) {
                return this instanceof r.Event ? (a && a.type ? (this.originalEvent = a, this.type = a.type, this.isDefaultPrevented = a.defaultPrevented || void 0 === a.defaultPrevented && a.returnValue === !1 ? va : wa, this.target = a.target && 3 === a.target.nodeType ? a.target.parentNode : a.target, this.currentTarget = a.currentTarget, this.relatedTarget = a.relatedTarget) : this.type = a, b && r.extend(this, b), this.timeStamp = a && a.timeStamp || r.now(), void(this[r.expando] = !0)) : new r.Event(a, b)
            }, r.Event.prototype = {
                constructor: r.Event,
                isDefaultPrevented: wa,
                isPropagationStopped: wa,
                isImmediatePropagationStopped: wa,
                isSimulated: !1,
                preventDefault: function () {
                    var a = this.originalEvent;
                    this.isDefaultPrevented = va, a && !this.isSimulated && a.preventDefault()
                },
                stopPropagation: function () {
                    var a = this.originalEvent;
                    this.isPropagationStopped = va, a && !this.isSimulated && a.stopPropagation()
                },
                stopImmediatePropagation: function () {
                    var a = this.originalEvent;
                    this.isImmediatePropagationStopped = va, a && !this.isSimulated && a.stopImmediatePropagation(), this.stopPropagation()
                }
            }, r.each({
                altKey: !0,
                bubbles: !0,
                cancelable: !0,
                changedTouches: !0,
                ctrlKey: !0,
                detail: !0,
                eventPhase: !0,
                metaKey: !0,
                pageX: !0,
                pageY: !0,
                shiftKey: !0,
                view: !0,
                "char": !0,
                charCode: !0,
                key: !0,
                keyCode: !0,
                button: !0,
                buttons: !0,
                clientX: !0,
                clientY: !0,
                offsetX: !0,
                offsetY: !0,
                pointerId: !0,
                pointerType: !0,
                screenX: !0,
                screenY: !0,
                targetTouches: !0,
                toElement: !0,
                touches: !0,
                which: function (a) {
                    var b = a.button;
                    return null == a.which && sa.test(a.type) ? null != a.charCode ? a.charCode : a.keyCode : !a.which && void 0 !== b && ta.test(a.type) ? 1 & b ? 1 : 2 & b ? 3 : 4 & b ? 2 : 0 : a.which
                }
            }, r.event.addProp), r.each({
                mouseenter: "mouseover",
                mouseleave: "mouseout",
                pointerenter: "pointerover",
                pointerleave: "pointerout"
            }, function (a, b) {
                r.event.special[a] = {
                    delegateType: b, bindType: b, handle: function (a) {
                        var c, d = this, e = a.relatedTarget, f = a.handleObj;
                        return e && (e === d || r.contains(d, e)) || (a.type = f.origType, c = f.handler.apply(this, arguments), a.type = b), c
                    }
                }
            }), r.fn.extend({
                on: function (a, b, c, d) {
                    return ya(this, a, b, c, d)
                }, one: function (a, b, c, d) {
                    return ya(this, a, b, c, d, 1)
                }, off: function (a, b, c) {
                    var d, e;
                    if (a && a.preventDefault && a.handleObj) return d = a.handleObj, r(a.delegateTarget).off(d.namespace ? d.origType + "." + d.namespace : d.origType, d.selector, d.handler), this;
                    if ("object" == typeof a) {
                        for (e in a) this.off(e, b, a[e]);
                        return this
                    }
                    return b !== !1 && "function" != typeof b || (c = b, b = void 0), c === !1 && (c = wa), this.each(function () {
                        r.event.remove(this, a, c, b)
                    })
                }
            });
            var za = /<(?!area|br|col|embed|hr|img|input|link|meta|param)(([a-z][^\/\0>\x20\t\r\n\f]*)[^>]*)\/>/gi,
                Aa = /<script|<style|<link/i, Ba = /checked\s*(?:[^=]|=\s*.checked.)/i, Ca = /^true\/(.*)/,
                Da = /^\s*<!(?:\[CDATA\[|--)|(?:\]\]|--)>\s*$/g;

            function Ea(a, b) {
                return B(a, "table") && B(11 !== b.nodeType ? b : b.firstChild, "tr") ? r(">tbody", a)[0] || a : a
            }

            function Fa(a) {
                return a.type = (null !== a.getAttribute("type")) + "/" + a.type, a
            }

            function Ga(a) {
                var b = Ca.exec(a.type);
                return b ? a.type = b[1] : a.removeAttribute("type"), a
            }

            function Ha(a, b) {
                var c, d, e, f, g, h, i, j;
                if (1 === b.nodeType) {
                    if (W.hasData(a) && (f = W.access(a), g = W.set(b, f), j = f.events)) {
                        delete g.handle, g.events = {};
                        for (e in j) for (c = 0, d = j[e].length; c < d; c++) r.event.add(b, e, j[e][c])
                    }
                    X.hasData(a) && (h = X.access(a), i = r.extend({}, h), X.set(b, i))
                }
            }

            function Ia(a, b) {
                var c = b.nodeName.toLowerCase();
                "input" === c && ja.test(a.type) ? b.checked = a.checked : "input" !== c && "textarea" !== c || (b.defaultValue = a.defaultValue)
            }

            function Ja(a, b, c, d) {
                b = g.apply([], b);
                var e, f, h, i, j, k, l = 0, m = a.length, n = m - 1, q = b[0], s = r.isFunction(q);
                if (s || m > 1 && "string" == typeof q && !o.checkClone && Ba.test(q)) return a.each(function (e) {
                    var f = a.eq(e);
                    s && (b[0] = q.call(this, e, f.html())), Ja(f, b, c, d)
                });
                if (m && (e = qa(b, a[0].ownerDocument, !1, a, d), f = e.firstChild, 1 === e.childNodes.length && (e = f), f || d)) {
                    for (h = r.map(na(e, "script"), Fa), i = h.length; l < m; l++) j = e, l !== n && (j = r.clone(j, !0, !0), i && r.merge(h, na(j, "script"))), c.call(a[l], j, l);
                    if (i) for (k = h[h.length - 1].ownerDocument, r.map(h, Ga), l = 0; l < i; l++) j = h[l], la.test(j.type || "") && !W.access(j, "globalEval") && r.contains(k, j) && (j.src ? r._evalUrl && r._evalUrl(j.src) : p(j.textContent.replace(Da, ""), k))
                }
                return a
            }

            function Ka(a, b, c) {
                for (var d, e = b ? r.filter(b, a) : a, f = 0; null != (d = e[f]); f++) c || 1 !== d.nodeType || r.cleanData(na(d)), d.parentNode && (c && r.contains(d.ownerDocument, d) && oa(na(d, "script")), d.parentNode.removeChild(d));
                return a
            }

            r.extend({
                htmlPrefilter: function (a) {
                    return a.replace(za, "<$1></$2>")
                }, clone: function (a, b, c) {
                    var d, e, f, g, h = a.cloneNode(!0), i = r.contains(a.ownerDocument, a);
                    if (!(o.noCloneChecked || 1 !== a.nodeType && 11 !== a.nodeType || r.isXMLDoc(a))) for (g = na(h), f = na(a), d = 0, e = f.length; d < e; d++) Ia(f[d], g[d]);
                    if (b) if (c) for (f = f || na(a), g = g || na(h), d = 0, e = f.length; d < e; d++) Ha(f[d], g[d]); else Ha(a, h);
                    return g = na(h, "script"), g.length > 0 && oa(g, !i && na(a, "script")), h
                }, cleanData: function (a) {
                    for (var b, c, d, e = r.event.special, f = 0; void 0 !== (c = a[f]); f++) if (U(c)) {
                        if (b = c[W.expando]) {
                            if (b.events) for (d in b.events) e[d] ? r.event.remove(c, d) : r.removeEvent(c, d, b.handle);
                            c[W.expando] = void 0
                        }
                        c[X.expando] && (c[X.expando] = void 0)
                    }
                }
            }), r.fn.extend({
                detach: function (a) {
                    return Ka(this, a, !0)
                }, remove: function (a) {
                    return Ka(this, a)
                }, text: function (a) {
                    return T(this, function (a) {
                        return void 0 === a ? r.text(this) : this.empty().each(function () {
                            1 !== this.nodeType && 11 !== this.nodeType && 9 !== this.nodeType || (this.textContent = a)
                        })
                    }, null, a, arguments.length)
                }, append: function () {
                    return Ja(this, arguments, function (a) {
                        if (1 === this.nodeType || 11 === this.nodeType || 9 === this.nodeType) {
                            var b = Ea(this, a);
                            b.appendChild(a)
                        }
                    })
                }, prepend: function () {
                    return Ja(this, arguments, function (a) {
                        if (1 === this.nodeType || 11 === this.nodeType || 9 === this.nodeType) {
                            var b = Ea(this, a);
                            b.insertBefore(a, b.firstChild)
                        }
                    })
                }, before: function () {
                    return Ja(this, arguments, function (a) {
                        this.parentNode && this.parentNode.insertBefore(a, this)
                    })
                }, after: function () {
                    return Ja(this, arguments, function (a) {
                        this.parentNode && this.parentNode.insertBefore(a, this.nextSibling)
                    })
                }, empty: function () {
                    for (var a, b = 0; null != (a = this[b]); b++) 1 === a.nodeType && (r.cleanData(na(a, !1)), a.textContent = "");
                    return this
                }, clone: function (a, b) {
                    return a = null != a && a, b = null == b ? a : b, this.map(function () {
                        return r.clone(this, a, b)
                    })
                }, html: function (a) {
                    return T(this, function (a) {
                        var b = this[0] || {}, c = 0, d = this.length;
                        if (void 0 === a && 1 === b.nodeType) return b.innerHTML;
                        if ("string" == typeof a && !Aa.test(a) && !ma[(ka.exec(a) || ["", ""])[1].toLowerCase()]) {
                            a = r.htmlPrefilter(a);
                            try {
                                for (; c < d; c++) b = this[c] || {}, 1 === b.nodeType && (r.cleanData(na(b, !1)), b.innerHTML = a);
                                b = 0
                            } catch (e) {
                            }
                        }
                        b && this.empty().append(a)
                    }, null, a, arguments.length)
                }, replaceWith: function () {
                    var a = [];
                    return Ja(this, arguments, function (b) {
                        var c = this.parentNode;
                        r.inArray(this, a) < 0 && (r.cleanData(na(this)), c && c.replaceChild(b, this))
                    }, a)
                }
            }), r.each({
                appendTo: "append",
                prependTo: "prepend",
                insertBefore: "before",
                insertAfter: "after",
                replaceAll: "replaceWith"
            }, function (a, b) {
                r.fn[a] = function (a) {
                    for (var c, d = [], e = r(a), f = e.length - 1, g = 0; g <= f; g++) c = g === f ? this : this.clone(!0), r(e[g])[b](c), h.apply(d, c.get());
                    return this.pushStack(d)
                }
            });
            var La = /^margin/, Ma = new RegExp("^(" + aa + ")(?!px)[a-z%]+$", "i"), Na = function (b) {
                var c = b.ownerDocument.defaultView;
                return c && c.opener || (c = a), c.getComputedStyle(b)
            };
            !function () {
                function b() {
                    if (i) {
                        i.style.cssText = "box-sizing:border-box;position:relative;display:block;margin:auto;border:1px;padding:1px;top:1%;width:50%", i.innerHTML = "", ra.appendChild(h);
                        var b = a.getComputedStyle(i);
                        c = "1%" !== b.top, g = "2px" === b.marginLeft, e = "4px" === b.width, i.style.marginRight = "50%", f = "4px" === b.marginRight, ra.removeChild(h), i = null
                    }
                }

                var c, e, f, g, h = d.createElement("div"), i = d.createElement("div");
                i.style && (i.style.backgroundClip = "content-box", i.cloneNode(!0).style.backgroundClip = "", o.clearCloneStyle = "content-box" === i.style.backgroundClip, h.style.cssText = "border:0;width:8px;height:0;top:0;left:-9999px;padding:0;margin-top:1px;position:absolute", h.appendChild(i), r.extend(o, {
                    pixelPosition: function () {
                        return b(), c
                    }, boxSizingReliable: function () {
                        return b(), e
                    }, pixelMarginRight: function () {
                        return b(), f
                    }, reliableMarginLeft: function () {
                        return b(), g
                    }
                }))
            }();

            function Oa(a, b, c) {
                var d, e, f, g, h = a.style;
                return c = c || Na(a), c && (g = c.getPropertyValue(b) || c[b], "" !== g || r.contains(a.ownerDocument, a) || (g = r.style(a, b)), !o.pixelMarginRight() && Ma.test(g) && La.test(b) && (d = h.width, e = h.minWidth, f = h.maxWidth, h.minWidth = h.maxWidth = h.width = g, g = c.width, h.width = d, h.minWidth = e, h.maxWidth = f)), void 0 !== g ? g + "" : g
            }

            function Pa(a, b) {
                return {
                    get: function () {
                        return a() ? void delete this.get : (this.get = b).apply(this, arguments)
                    }
                }
            }

            var Qa = /^(none|table(?!-c[ea]).+)/, Ra = /^--/,
                Sa = {position: "absolute", visibility: "hidden", display: "block"},
                Ta = {letterSpacing: "0", fontWeight: "400"}, Ua = ["Webkit", "Moz", "ms"],
                Va = d.createElement("div").style;

            function Wa(a) {
                if (a in Va) return a;
                var b = a[0].toUpperCase() + a.slice(1), c = Ua.length;
                while (c--) if (a = Ua[c] + b, a in Va) return a
            }

            function Xa(a) {
                var b = r.cssProps[a];
                return b || (b = r.cssProps[a] = Wa(a) || a), b
            }

            function Ya(a, b, c) {
                var d = ba.exec(b);
                return d ? Math.max(0, d[2] - (c || 0)) + (d[3] || "px") : b
            }

            function Za(a, b, c, d, e) {
                var f, g = 0;
                for (f = c === (d ? "border" : "content") ? 4 : "width" === b ? 1 : 0; f < 4; f += 2) "margin" === c && (g += r.css(a, c + ca[f], !0, e)), d ? ("content" === c && (g -= r.css(a, "padding" + ca[f], !0, e)), "margin" !== c && (g -= r.css(a, "border" + ca[f] + "Width", !0, e))) : (g += r.css(a, "padding" + ca[f], !0, e), "padding" !== c && (g += r.css(a, "border" + ca[f] + "Width", !0, e)));
                return g
            }

            function $a(a, b, c) {
                var d, e = Na(a), f = Oa(a, b, e), g = "border-box" === r.css(a, "boxSizing", !1, e);
                return Ma.test(f) ? f : (d = g && (o.boxSizingReliable() || f === a.style[b]), "auto" === f && (f = a["offset" + b[0].toUpperCase() + b.slice(1)]), f = parseFloat(f) || 0, f + Za(a, b, c || (g ? "border" : "content"), d, e) + "px")
            }

            r.extend({
                cssHooks: {
                    opacity: {
                        get: function (a, b) {
                            if (b) {
                                var c = Oa(a, "opacity");
                                return "" === c ? "1" : c
                            }
                        }
                    }
                },
                cssNumber: {
                    animationIterationCount: !0,
                    columnCount: !0,
                    fillOpacity: !0,
                    flexGrow: !0,
                    flexShrink: !0,
                    fontWeight: !0,
                    lineHeight: !0,
                    opacity: !0,
                    order: !0,
                    orphans: !0,
                    widows: !0,
                    zIndex: !0,
                    zoom: !0
                },
                cssProps: {"float": "cssFloat"},
                style: function (a, b, c, d) {
                    if (a && 3 !== a.nodeType && 8 !== a.nodeType && a.style) {
                        var e, f, g, h = r.camelCase(b), i = Ra.test(b), j = a.style;
                        return i || (b = Xa(h)), g = r.cssHooks[b] || r.cssHooks[h], void 0 === c ? g && "get" in g && void 0 !== (e = g.get(a, !1, d)) ? e : j[b] : (f = typeof c, "string" === f && (e = ba.exec(c)) && e[1] && (c = fa(a, b, e), f = "number"), null != c && c === c && ("number" === f && (c += e && e[3] || (r.cssNumber[h] ? "" : "px")), o.clearCloneStyle || "" !== c || 0 !== b.indexOf("background") || (j[b] = "inherit"), g && "set" in g && void 0 === (c = g.set(a, c, d)) || (i ? j.setProperty(b, c) : j[b] = c)), void 0)
                    }
                },
                css: function (a, b, c, d) {
                    var e, f, g, h = r.camelCase(b), i = Ra.test(b);
                    return i || (b = Xa(h)), g = r.cssHooks[b] || r.cssHooks[h], g && "get" in g && (e = g.get(a, !0, c)), void 0 === e && (e = Oa(a, b, d)), "normal" === e && b in Ta && (e = Ta[b]), "" === c || c ? (f = parseFloat(e), c === !0 || isFinite(f) ? f || 0 : e) : e
                }
            }), r.each(["height", "width"], function (a, b) {
                r.cssHooks[b] = {
                    get: function (a, c, d) {
                        if (c) return !Qa.test(r.css(a, "display")) || a.getClientRects().length && a.getBoundingClientRect().width ? $a(a, b, d) : ea(a, Sa, function () {
                            return $a(a, b, d)
                        })
                    }, set: function (a, c, d) {
                        var e, f = d && Na(a), g = d && Za(a, b, d, "border-box" === r.css(a, "boxSizing", !1, f), f);
                        return g && (e = ba.exec(c)) && "px" !== (e[3] || "px") && (a.style[b] = c, c = r.css(a, b)), Ya(a, c, g)
                    }
                }
            }), r.cssHooks.marginLeft = Pa(o.reliableMarginLeft, function (a, b) {
                if (b) return (parseFloat(Oa(a, "marginLeft")) || a.getBoundingClientRect().left - ea(a, {marginLeft: 0}, function () {
                    return a.getBoundingClientRect().left
                })) + "px"
            }), r.each({margin: "", padding: "", border: "Width"}, function (a, b) {
                r.cssHooks[a + b] = {
                    expand: function (c) {
                        for (var d = 0, e = {}, f = "string" == typeof c ? c.split(" ") : [c]; d < 4; d++) e[a + ca[d] + b] = f[d] || f[d - 2] || f[0];
                        return e
                    }
                }, La.test(a) || (r.cssHooks[a + b].set = Ya)
            }), r.fn.extend({
                css: function (a, b) {
                    return T(this, function (a, b, c) {
                        var d, e, f = {}, g = 0;
                        if (Array.isArray(b)) {
                            for (d = Na(a), e = b.length; g < e; g++) f[b[g]] = r.css(a, b[g], !1, d);
                            return f
                        }
                        return void 0 !== c ? r.style(a, b, c) : r.css(a, b)
                    }, a, b, arguments.length > 1)
                }
            });

            function _a(a, b, c, d, e) {
                return new _a.prototype.init(a, b, c, d, e)
            }

            r.Tween = _a, _a.prototype = {
                constructor: _a, init: function (a, b, c, d, e, f) {
                    this.elem = a, this.prop = c, this.easing = e || r.easing._default, this.options = b, this.start = this.now = this.cur(), this.end = d, this.unit = f || (r.cssNumber[c] ? "" : "px")
                }, cur: function () {
                    var a = _a.propHooks[this.prop];
                    return a && a.get ? a.get(this) : _a.propHooks._default.get(this)
                }, run: function (a) {
                    var b, c = _a.propHooks[this.prop];
                    return this.options.duration ? this.pos = b = r.easing[this.easing](a, this.options.duration * a, 0, 1, this.options.duration) : this.pos = b = a, this.now = (this.end - this.start) * b + this.start, this.options.step && this.options.step.call(this.elem, this.now, this), c && c.set ? c.set(this) : _a.propHooks._default.set(this), this
                }
            }, _a.prototype.init.prototype = _a.prototype, _a.propHooks = {
                _default: {
                    get: function (a) {
                        var b;
                        return 1 !== a.elem.nodeType || null != a.elem[a.prop] && null == a.elem.style[a.prop] ? a.elem[a.prop] : (b = r.css(a.elem, a.prop, ""), b && "auto" !== b ? b : 0)
                    }, set: function (a) {
                        r.fx.step[a.prop] ? r.fx.step[a.prop](a) : 1 !== a.elem.nodeType || null == a.elem.style[r.cssProps[a.prop]] && !r.cssHooks[a.prop] ? a.elem[a.prop] = a.now : r.style(a.elem, a.prop, a.now + a.unit)
                    }
                }
            }, _a.propHooks.scrollTop = _a.propHooks.scrollLeft = {
                set: function (a) {
                    a.elem.nodeType && a.elem.parentNode && (a.elem[a.prop] = a.now)
                }
            }, r.easing = {
                linear: function (a) {
                    return a
                }, swing: function (a) {
                    return .5 - Math.cos(a * Math.PI) / 2
                }, _default: "swing"
            }, r.fx = _a.prototype.init, r.fx.step = {};
            var ab, bb, cb = /^(?:toggle|show|hide)$/, db = /queueHooks$/;

            function eb() {
                bb && (d.hidden === !1 && a.requestAnimationFrame ? a.requestAnimationFrame(eb) : a.setTimeout(eb, r.fx.interval), r.fx.tick())
            }

            function fb() {
                return a.setTimeout(function () {
                    ab = void 0
                }), ab = r.now()
            }

            function gb(a, b) {
                var c, d = 0, e = {height: a};
                for (b = b ? 1 : 0; d < 4; d += 2 - b) c = ca[d], e["margin" + c] = e["padding" + c] = a;
                return b && (e.opacity = e.width = a), e
            }

            function hb(a, b, c) {
                for (var d, e = (kb.tweeners[b] || []).concat(kb.tweeners["*"]), f = 0, g = e.length; f < g; f++) if (d = e[f].call(c, b, a)) return d
            }

            function ib(a, b, c) {
                var d, e, f, g, h, i, j, k, l = "width" in b || "height" in b, m = this, n = {}, o = a.style,
                    p = a.nodeType && da(a), q = W.get(a, "fxshow");
                c.queue || (g = r._queueHooks(a, "fx"), null == g.unqueued && (g.unqueued = 0, h = g.empty.fire, g.empty.fire = function () {
                    g.unqueued || h()
                }), g.unqueued++, m.always(function () {
                    m.always(function () {
                        g.unqueued--, r.queue(a, "fx").length || g.empty.fire()
                    })
                }));
                for (d in b) if (e = b[d], cb.test(e)) {
                    if (delete b[d], f = f || "toggle" === e, e === (p ? "hide" : "show")) {
                        if ("show" !== e || !q || void 0 === q[d]) continue;
                        p = !0
                    }
                    n[d] = q && q[d] || r.style(a, d)
                }
                if (i = !r.isEmptyObject(b), i || !r.isEmptyObject(n)) {
                    l && 1 === a.nodeType && (c.overflow = [o.overflow, o.overflowX, o.overflowY], j = q && q.display, null == j && (j = W.get(a, "display")), k = r.css(a, "display"), "none" === k && (j ? k = j : (ia([a], !0), j = a.style.display || j, k = r.css(a, "display"), ia([a]))), ("inline" === k || "inline-block" === k && null != j) && "none" === r.css(a, "float") && (i || (m.done(function () {
                        o.display = j
                    }), null == j && (k = o.display, j = "none" === k ? "" : k)), o.display = "inline-block")), c.overflow && (o.overflow = "hidden", m.always(function () {
                        o.overflow = c.overflow[0], o.overflowX = c.overflow[1], o.overflowY = c.overflow[2]
                    })), i = !1;
                    for (d in n) i || (q ? "hidden" in q && (p = q.hidden) : q = W.access(a, "fxshow", {display: j}), f && (q.hidden = !p), p && ia([a], !0), m.done(function () {
                        p || ia([a]), W.remove(a, "fxshow");
                        for (d in n) r.style(a, d, n[d])
                    })), i = hb(p ? q[d] : 0, d, m), d in q || (q[d] = i.start, p && (i.end = i.start, i.start = 0))
                }
            }

            function jb(a, b) {
                var c, d, e, f, g;
                for (c in a) if (d = r.camelCase(c), e = b[d], f = a[c], Array.isArray(f) && (e = f[1], f = a[c] = f[0]), c !== d && (a[d] = f, delete a[c]), g = r.cssHooks[d], g && "expand" in g) {
                    f = g.expand(f), delete a[d];
                    for (c in f) c in a || (a[c] = f[c], b[c] = e)
                } else b[d] = e
            }

            function kb(a, b, c) {
                var d, e, f = 0, g = kb.prefilters.length, h = r.Deferred().always(function () {
                    delete i.elem
                }), i = function () {
                    if (e) return !1;
                    for (var b = ab || fb(), c = Math.max(0, j.startTime + j.duration - b), d = c / j.duration || 0, f = 1 - d, g = 0, i = j.tweens.length; g < i; g++) j.tweens[g].run(f);
                    return h.notifyWith(a, [j, f, c]), f < 1 && i ? c : (i || h.notifyWith(a, [j, 1, 0]), h.resolveWith(a, [j]), !1)
                }, j = h.promise({
                    elem: a,
                    props: r.extend({}, b),
                    opts: r.extend(!0, {specialEasing: {}, easing: r.easing._default}, c),
                    originalProperties: b,
                    originalOptions: c,
                    startTime: ab || fb(),
                    duration: c.duration,
                    tweens: [],
                    createTween: function (b, c) {
                        var d = r.Tween(a, j.opts, b, c, j.opts.specialEasing[b] || j.opts.easing);
                        return j.tweens.push(d), d
                    },
                    stop: function (b) {
                        var c = 0, d = b ? j.tweens.length : 0;
                        if (e) return this;
                        for (e = !0; c < d; c++) j.tweens[c].run(1);
                        return b ? (h.notifyWith(a, [j, 1, 0]), h.resolveWith(a, [j, b])) : h.rejectWith(a, [j, b]), this
                    }
                }), k = j.props;
                for (jb(k, j.opts.specialEasing); f < g; f++) if (d = kb.prefilters[f].call(j, a, k, j.opts)) return r.isFunction(d.stop) && (r._queueHooks(j.elem, j.opts.queue).stop = r.proxy(d.stop, d)), d;
                return r.map(k, hb, j), r.isFunction(j.opts.start) && j.opts.start.call(a, j), j.progress(j.opts.progress).done(j.opts.done, j.opts.complete).fail(j.opts.fail).always(j.opts.always), r.fx.timer(r.extend(i, {
                    elem: a,
                    anim: j,
                    queue: j.opts.queue
                })), j
            }

            r.Animation = r.extend(kb, {
                tweeners: {
                    "*": [function (a, b) {
                        var c = this.createTween(a, b);
                        return fa(c.elem, a, ba.exec(b), c), c
                    }]
                }, tweener: function (a, b) {
                    r.isFunction(a) ? (b = a, a = ["*"]) : a = a.match(L);
                    for (var c, d = 0, e = a.length; d < e; d++) c = a[d], kb.tweeners[c] = kb.tweeners[c] || [], kb.tweeners[c].unshift(b)
                }, prefilters: [ib], prefilter: function (a, b) {
                    b ? kb.prefilters.unshift(a) : kb.prefilters.push(a)
                }
            }), r.speed = function (a, b, c) {
                var d = a && "object" == typeof a ? r.extend({}, a) : {
                    complete: c || !c && b || r.isFunction(a) && a,
                    duration: a,
                    easing: c && b || b && !r.isFunction(b) && b
                };
                return r.fx.off ? d.duration = 0 : "number" != typeof d.duration && (d.duration in r.fx.speeds ? d.duration = r.fx.speeds[d.duration] : d.duration = r.fx.speeds._default), null != d.queue && d.queue !== !0 || (d.queue = "fx"), d.old = d.complete, d.complete = function () {
                    r.isFunction(d.old) && d.old.call(this), d.queue && r.dequeue(this, d.queue)
                }, d
            }, r.fn.extend({
                fadeTo: function (a, b, c, d) {
                    return this.filter(da).css("opacity", 0).show().end().animate({opacity: b}, a, c, d)
                }, animate: function (a, b, c, d) {
                    var e = r.isEmptyObject(a), f = r.speed(b, c, d), g = function () {
                        var b = kb(this, r.extend({}, a), f);
                        (e || W.get(this, "finish")) && b.stop(!0)
                    };
                    return g.finish = g, e || f.queue === !1 ? this.each(g) : this.queue(f.queue, g)
                }, stop: function (a, b, c) {
                    var d = function (a) {
                        var b = a.stop;
                        delete a.stop, b(c)
                    };
                    return "string" != typeof a && (c = b, b = a, a = void 0), b && a !== !1 && this.queue(a || "fx", []), this.each(function () {
                        var b = !0, e = null != a && a + "queueHooks", f = r.timers, g = W.get(this);
                        if (e) g[e] && g[e].stop && d(g[e]); else for (e in g) g[e] && g[e].stop && db.test(e) && d(g[e]);
                        for (e = f.length; e--;) f[e].elem !== this || null != a && f[e].queue !== a || (f[e].anim.stop(c), b = !1, f.splice(e, 1));
                        !b && c || r.dequeue(this, a)
                    })
                }, finish: function (a) {
                    return a !== !1 && (a = a || "fx"), this.each(function () {
                        var b, c = W.get(this), d = c[a + "queue"], e = c[a + "queueHooks"], f = r.timers,
                            g = d ? d.length : 0;
                        for (c.finish = !0, r.queue(this, a, []), e && e.stop && e.stop.call(this, !0), b = f.length; b--;) f[b].elem === this && f[b].queue === a && (f[b].anim.stop(!0), f.splice(b, 1));
                        for (b = 0; b < g; b++) d[b] && d[b].finish && d[b].finish.call(this);
                        delete c.finish
                    })
                }
            }), r.each(["toggle", "show", "hide"], function (a, b) {
                var c = r.fn[b];
                r.fn[b] = function (a, d, e) {
                    return null == a || "boolean" == typeof a ? c.apply(this, arguments) : this.animate(gb(b, !0), a, d, e)
                }
            }), r.each({
                slideDown: gb("show"),
                slideUp: gb("hide"),
                slideToggle: gb("toggle"),
                fadeIn: {opacity: "show"},
                fadeOut: {opacity: "hide"},
                fadeToggle: {opacity: "toggle"}
            }, function (a, b) {
                r.fn[a] = function (a, c, d) {
                    return this.animate(b, a, c, d)
                }
            }), r.timers = [], r.fx.tick = function () {
                var a, b = 0, c = r.timers;
                for (ab = r.now(); b < c.length; b++) a = c[b], a() || c[b] !== a || c.splice(b--, 1);
                c.length || r.fx.stop(), ab = void 0
            }, r.fx.timer = function (a) {
                r.timers.push(a), r.fx.start()
            }, r.fx.interval = 13, r.fx.start = function () {
                bb || (bb = !0, eb())
            }, r.fx.stop = function () {
                bb = null
            }, r.fx.speeds = {slow: 600, fast: 200, _default: 400}, r.fn.delay = function (b, c) {
                return b = r.fx ? r.fx.speeds[b] || b : b, c = c || "fx", this.queue(c, function (c, d) {
                    var e = a.setTimeout(c, b);
                    d.stop = function () {
                        a.clearTimeout(e)
                    }
                })
            }, function () {
                var a = d.createElement("input"), b = d.createElement("select"),
                    c = b.appendChild(d.createElement("option"));
                a.type = "checkbox", o.checkOn = "" !== a.value, o.optSelected = c.selected, a = d.createElement("input"), a.value = "t", a.type = "radio", o.radioValue = "t" === a.value
            }();
            var lb, mb = r.expr.attrHandle;
            r.fn.extend({
                attr: function (a, b) {
                    return T(this, r.attr, a, b, arguments.length > 1)
                }, removeAttr: function (a) {
                    return this.each(function () {
                        r.removeAttr(this, a)
                    })
                }
            }), r.extend({
                attr: function (a, b, c) {
                    var d, e, f = a.nodeType;
                    if (3 !== f && 8 !== f && 2 !== f) return "undefined" == typeof a.getAttribute ? r.prop(a, b, c) : (1 === f && r.isXMLDoc(a) || (e = r.attrHooks[b.toLowerCase()] || (r.expr.match.bool.test(b) ? lb : void 0)), void 0 !== c ? null === c ? void r.removeAttr(a, b) : e && "set" in e && void 0 !== (d = e.set(a, c, b)) ? d : (a.setAttribute(b, c + ""), c) : e && "get" in e && null !== (d = e.get(a, b)) ? d : (d = r.find.attr(a, b),
                        null == d ? void 0 : d))
                }, attrHooks: {
                    type: {
                        set: function (a, b) {
                            if (!o.radioValue && "radio" === b && B(a, "input")) {
                                var c = a.value;
                                return a.setAttribute("type", b), c && (a.value = c), b
                            }
                        }
                    }
                }, removeAttr: function (a, b) {
                    var c, d = 0, e = b && b.match(L);
                    if (e && 1 === a.nodeType) while (c = e[d++]) a.removeAttribute(c)
                }
            }), lb = {
                set: function (a, b, c) {
                    return b === !1 ? r.removeAttr(a, c) : a.setAttribute(c, c), c
                }
            }, r.each(r.expr.match.bool.source.match(/\w+/g), function (a, b) {
                var c = mb[b] || r.find.attr;
                mb[b] = function (a, b, d) {
                    var e, f, g = b.toLowerCase();
                    return d || (f = mb[g], mb[g] = e, e = null != c(a, b, d) ? g : null, mb[g] = f), e
                }
            });
            var nb = /^(?:input|select|textarea|button)$/i, ob = /^(?:a|area)$/i;
            r.fn.extend({
                prop: function (a, b) {
                    return T(this, r.prop, a, b, arguments.length > 1)
                }, removeProp: function (a) {
                    return this.each(function () {
                        delete this[r.propFix[a] || a]
                    })
                }
            }), r.extend({
                prop: function (a, b, c) {
                    var d, e, f = a.nodeType;
                    if (3 !== f && 8 !== f && 2 !== f) return 1 === f && r.isXMLDoc(a) || (b = r.propFix[b] || b, e = r.propHooks[b]), void 0 !== c ? e && "set" in e && void 0 !== (d = e.set(a, c, b)) ? d : a[b] = c : e && "get" in e && null !== (d = e.get(a, b)) ? d : a[b]
                }, propHooks: {
                    tabIndex: {
                        get: function (a) {
                            var b = r.find.attr(a, "tabindex");
                            return b ? parseInt(b, 10) : nb.test(a.nodeName) || ob.test(a.nodeName) && a.href ? 0 : -1
                        }
                    }
                }, propFix: {"for": "htmlFor", "class": "className"}
            }), o.optSelected || (r.propHooks.selected = {
                get: function (a) {
                    var b = a.parentNode;
                    return b && b.parentNode && b.parentNode.selectedIndex, null
                }, set: function (a) {
                    var b = a.parentNode;
                    b && (b.selectedIndex, b.parentNode && b.parentNode.selectedIndex)
                }
            }), r.each(["tabIndex", "readOnly", "maxLength", "cellSpacing", "cellPadding", "rowSpan", "colSpan", "useMap", "frameBorder", "contentEditable"], function () {
                r.propFix[this.toLowerCase()] = this
            });

            function pb(a) {
                var b = a.match(L) || [];
                return b.join(" ")
            }

            function qb(a) {
                return a.getAttribute && a.getAttribute("class") || ""
            }

            r.fn.extend({
                addClass: function (a) {
                    var b, c, d, e, f, g, h, i = 0;
                    if (r.isFunction(a)) return this.each(function (b) {
                        r(this).addClass(a.call(this, b, qb(this)))
                    });
                    if ("string" == typeof a && a) {
                        b = a.match(L) || [];
                        while (c = this[i++]) if (e = qb(c), d = 1 === c.nodeType && " " + pb(e) + " ") {
                            g = 0;
                            while (f = b[g++]) d.indexOf(" " + f + " ") < 0 && (d += f + " ");
                            h = pb(d), e !== h && c.setAttribute("class", h)
                        }
                    }
                    return this
                }, removeClass: function (a) {
                    var b, c, d, e, f, g, h, i = 0;
                    if (r.isFunction(a)) return this.each(function (b) {
                        r(this).removeClass(a.call(this, b, qb(this)))
                    });
                    if (!arguments.length) return this.attr("class", "");
                    if ("string" == typeof a && a) {
                        b = a.match(L) || [];
                        while (c = this[i++]) if (e = qb(c), d = 1 === c.nodeType && " " + pb(e) + " ") {
                            g = 0;
                            while (f = b[g++]) while (d.indexOf(" " + f + " ") > -1) d = d.replace(" " + f + " ", " ");
                            h = pb(d), e !== h && c.setAttribute("class", h)
                        }
                    }
                    return this
                }, toggleClass: function (a, b) {
                    var c = typeof a;
                    return "boolean" == typeof b && "string" === c ? b ? this.addClass(a) : this.removeClass(a) : r.isFunction(a) ? this.each(function (c) {
                        r(this).toggleClass(a.call(this, c, qb(this), b), b)
                    }) : this.each(function () {
                        var b, d, e, f;
                        if ("string" === c) {
                            d = 0, e = r(this), f = a.match(L) || [];
                            while (b = f[d++]) e.hasClass(b) ? e.removeClass(b) : e.addClass(b)
                        } else void 0 !== a && "boolean" !== c || (b = qb(this), b && W.set(this, "__className__", b), this.setAttribute && this.setAttribute("class", b || a === !1 ? "" : W.get(this, "__className__") || ""))
                    })
                }, hasClass: function (a) {
                    var b, c, d = 0;
                    b = " " + a + " ";
                    while (c = this[d++]) if (1 === c.nodeType && (" " + pb(qb(c)) + " ").indexOf(b) > -1) return !0;
                    return !1
                }
            });
            var rb = /\r/g;
            r.fn.extend({
                val: function (a) {
                    var b, c, d, e = this[0];
                    {
                        if (arguments.length) return d = r.isFunction(a), this.each(function (c) {
                            var e;
                            1 === this.nodeType && (e = d ? a.call(this, c, r(this).val()) : a, null == e ? e = "" : "number" == typeof e ? e += "" : Array.isArray(e) && (e = r.map(e, function (a) {
                                return null == a ? "" : a + ""
                            })), b = r.valHooks[this.type] || r.valHooks[this.nodeName.toLowerCase()], b && "set" in b && void 0 !== b.set(this, e, "value") || (this.value = e))
                        });
                        if (e) return b = r.valHooks[e.type] || r.valHooks[e.nodeName.toLowerCase()], b && "get" in b && void 0 !== (c = b.get(e, "value")) ? c : (c = e.value, "string" == typeof c ? c.replace(rb, "") : null == c ? "" : c)
                    }
                }
            }), r.extend({
                valHooks: {
                    option: {
                        get: function (a) {
                            var b = r.find.attr(a, "value");
                            return null != b ? b : pb(r.text(a))
                        }
                    }, select: {
                        get: function (a) {
                            var b, c, d, e = a.options, f = a.selectedIndex, g = "select-one" === a.type,
                                h = g ? null : [], i = g ? f + 1 : e.length;
                            for (d = f < 0 ? i : g ? f : 0; d < i; d++) if (c = e[d], (c.selected || d === f) && !c.disabled && (!c.parentNode.disabled || !B(c.parentNode, "optgroup"))) {
                                if (b = r(c).val(), g) return b;
                                h.push(b)
                            }
                            return h
                        }, set: function (a, b) {
                            var c, d, e = a.options, f = r.makeArray(b), g = e.length;
                            while (g--) d = e[g], (d.selected = r.inArray(r.valHooks.option.get(d), f) > -1) && (c = !0);
                            return c || (a.selectedIndex = -1), f
                        }
                    }
                }
            }), r.each(["radio", "checkbox"], function () {
                r.valHooks[this] = {
                    set: function (a, b) {
                        if (Array.isArray(b)) return a.checked = r.inArray(r(a).val(), b) > -1
                    }
                }, o.checkOn || (r.valHooks[this].get = function (a) {
                    return null === a.getAttribute("value") ? "on" : a.value
                })
            });
            var sb = /^(?:focusinfocus|focusoutblur)$/;
            r.extend(r.event, {
                trigger: function (b, c, e, f) {
                    var g, h, i, j, k, m, n, o = [e || d], p = l.call(b, "type") ? b.type : b,
                        q = l.call(b, "namespace") ? b.namespace.split(".") : [];
                    if (h = i = e = e || d, 3 !== e.nodeType && 8 !== e.nodeType && !sb.test(p + r.event.triggered) && (p.indexOf(".") > -1 && (q = p.split("."), p = q.shift(), q.sort()), k = p.indexOf(":") < 0 && "on" + p, b = b[r.expando] ? b : new r.Event(p, "object" == typeof b && b), b.isTrigger = f ? 2 : 3, b.namespace = q.join("."), b.rnamespace = b.namespace ? new RegExp("(^|\\.)" + q.join("\\.(?:.*\\.|)") + "(\\.|$)") : null, b.result = void 0, b.target || (b.target = e), c = null == c ? [b] : r.makeArray(c, [b]), n = r.event.special[p] || {}, f || !n.trigger || n.trigger.apply(e, c) !== !1)) {
                        if (!f && !n.noBubble && !r.isWindow(e)) {
                            for (j = n.delegateType || p, sb.test(j + p) || (h = h.parentNode); h; h = h.parentNode) o.push(h), i = h;
                            i === (e.ownerDocument || d) && o.push(i.defaultView || i.parentWindow || a)
                        }
                        g = 0;
                        while ((h = o[g++]) && !b.isPropagationStopped()) b.type = g > 1 ? j : n.bindType || p, m = (W.get(h, "events") || {})[b.type] && W.get(h, "handle"), m && m.apply(h, c), m = k && h[k], m && m.apply && U(h) && (b.result = m.apply(h, c), b.result === !1 && b.preventDefault());
                        return b.type = p, f || b.isDefaultPrevented() || n._default && n._default.apply(o.pop(), c) !== !1 || !U(e) || k && r.isFunction(e[p]) && !r.isWindow(e) && (i = e[k], i && (e[k] = null), r.event.triggered = p, e[p](), r.event.triggered = void 0, i && (e[k] = i)), b.result
                    }
                }, simulate: function (a, b, c) {
                    var d = r.extend(new r.Event, c, {type: a, isSimulated: !0});
                    r.event.trigger(d, null, b)
                }
            }), r.fn.extend({
                trigger: function (a, b) {
                    return this.each(function () {
                        r.event.trigger(a, b, this)
                    })
                }, triggerHandler: function (a, b) {
                    var c = this[0];
                    if (c) return r.event.trigger(a, b, c, !0)
                }
            }), r.each("blur focus focusin focusout resize scroll click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select submit keydown keypress keyup contextmenu".split(" "), function (a, b) {
                r.fn[b] = function (a, c) {
                    return arguments.length > 0 ? this.on(b, null, a, c) : this.trigger(b)
                }
            }), r.fn.extend({
                hover: function (a, b) {
                    return this.mouseenter(a).mouseleave(b || a)
                }
            }), o.focusin = "onfocusin" in a, o.focusin || r.each({
                focus: "focusin",
                blur: "focusout"
            }, function (a, b) {
                var c = function (a) {
                    r.event.simulate(b, a.target, r.event.fix(a))
                };
                r.event.special[b] = {
                    setup: function () {
                        var d = this.ownerDocument || this, e = W.access(d, b);
                        e || d.addEventListener(a, c, !0), W.access(d, b, (e || 0) + 1)
                    }, teardown: function () {
                        var d = this.ownerDocument || this, e = W.access(d, b) - 1;
                        e ? W.access(d, b, e) : (d.removeEventListener(a, c, !0), W.remove(d, b))
                    }
                }
            });
            var tb = a.location, ub = r.now(), vb = /\?/;
            r.parseXML = function (b) {
                var c;
                if (!b || "string" != typeof b) return null;
                try {
                    c = (new a.DOMParser).parseFromString(b, "text/xml")
                } catch (d) {
                    c = void 0
                }
                return c && !c.getElementsByTagName("parsererror").length || r.error("Invalid XML: " + b), c
            };
            var wb = /\[\]$/, xb = /\r?\n/g, yb = /^(?:submit|button|image|reset|file)$/i,
                zb = /^(?:input|select|textarea|keygen)/i;

            function Ab(a, b, c, d) {
                var e;
                if (Array.isArray(b)) r.each(b, function (b, e) {
                    c || wb.test(a) ? d(a, e) : Ab(a + "[" + ("object" == typeof e && null != e ? b : "") + "]", e, c, d)
                }); else if (c || "object" !== r.type(b)) d(a, b); else for (e in b) Ab(a + "[" + e + "]", b[e], c, d)
            }

            r.param = function (a, b) {
                var c, d = [], e = function (a, b) {
                    var c = r.isFunction(b) ? b() : b;
                    d[d.length] = encodeURIComponent(a) + "=" + encodeURIComponent(null == c ? "" : c)
                };
                if (Array.isArray(a) || a.jquery && !r.isPlainObject(a)) r.each(a, function () {
                    e(this.name, this.value)
                }); else for (c in a) Ab(c, a[c], b, e);
                return d.join("&")
            }, r.fn.extend({
                serialize: function () {
                    return r.param(this.serializeArray())
                }, serializeArray: function () {
                    return this.map(function () {
                        var a = r.prop(this, "elements");
                        return a ? r.makeArray(a) : this
                    }).filter(function () {
                        var a = this.type;
                        return this.name && !r(this).is(":disabled") && zb.test(this.nodeName) && !yb.test(a) && (this.checked || !ja.test(a))
                    }).map(function (a, b) {
                        var c = r(this).val();
                        return null == c ? null : Array.isArray(c) ? r.map(c, function (a) {
                            return {name: b.name, value: a.replace(xb, "\r\n")}
                        }) : {name: b.name, value: c.replace(xb, "\r\n")}
                    }).get()
                }
            });
            var Bb = /%20/g, Cb = /#.*$/, Db = /([?&])_=[^&]*/, Eb = /^(.*?):[ \t]*([^\r\n]*)$/gm,
                Fb = /^(?:about|app|app-storage|.+-extension|file|res|widget):$/, Gb = /^(?:GET|HEAD)$/, Hb = /^\/\//,
                Ib = {}, Jb = {}, Kb = "*/".concat("*"), Lb = d.createElement("a");
            Lb.href = tb.href;

            function Mb(a) {
                return function (b, c) {
                    "string" != typeof b && (c = b, b = "*");
                    var d, e = 0, f = b.toLowerCase().match(L) || [];
                    if (r.isFunction(c)) while (d = f[e++]) "+" === d[0] ? (d = d.slice(1) || "*", (a[d] = a[d] || []).unshift(c)) : (a[d] = a[d] || []).push(c)
                }
            }

            function Nb(a, b, c, d) {
                var e = {}, f = a === Jb;

                function g(h) {
                    var i;
                    return e[h] = !0, r.each(a[h] || [], function (a, h) {
                        var j = h(b, c, d);
                        return "string" != typeof j || f || e[j] ? f ? !(i = j) : void 0 : (b.dataTypes.unshift(j), g(j), !1)
                    }), i
                }

                return g(b.dataTypes[0]) || !e["*"] && g("*")
            }

            function Ob(a, b) {
                var c, d, e = r.ajaxSettings.flatOptions || {};
                for (c in b) void 0 !== b[c] && ((e[c] ? a : d || (d = {}))[c] = b[c]);
                return d && r.extend(!0, a, d), a
            }

            function Pb(a, b, c) {
                var d, e, f, g, h = a.contents, i = a.dataTypes;
                while ("*" === i[0]) i.shift(), void 0 === d && (d = a.mimeType || b.getResponseHeader("Content-Type"));
                if (d) for (e in h) if (h[e] && h[e].test(d)) {
                    i.unshift(e);
                    break
                }
                if (i[0] in c) f = i[0]; else {
                    for (e in c) {
                        if (!i[0] || a.converters[e + " " + i[0]]) {
                            f = e;
                            break
                        }
                        g || (g = e)
                    }
                    f = f || g
                }
                if (f) return f !== i[0] && i.unshift(f), c[f]
            }

            function Qb(a, b, c, d) {
                var e, f, g, h, i, j = {}, k = a.dataTypes.slice();
                if (k[1]) for (g in a.converters) j[g.toLowerCase()] = a.converters[g];
                f = k.shift();
                while (f) if (a.responseFields[f] && (c[a.responseFields[f]] = b), !i && d && a.dataFilter && (b = a.dataFilter(b, a.dataType)), i = f, f = k.shift()) if ("*" === f) f = i; else if ("*" !== i && i !== f) {
                    if (g = j[i + " " + f] || j["* " + f], !g) for (e in j) if (h = e.split(" "), h[1] === f && (g = j[i + " " + h[0]] || j["* " + h[0]])) {
                        g === !0 ? g = j[e] : j[e] !== !0 && (f = h[0], k.unshift(h[1]));
                        break
                    }
                    if (g !== !0) if (g && a["throws"]) b = g(b); else try {
                        b = g(b)
                    } catch (l) {
                        return {state: "parsererror", error: g ? l : "No conversion from " + i + " to " + f}
                    }
                }
                return {state: "success", data: b}
            }

            r.extend({
                active: 0,
                lastModified: {},
                etag: {},
                ajaxSettings: {
                    url: tb.href,
                    type: "GET",
                    isLocal: Fb.test(tb.protocol),
                    global: !0,
                    processData: !0,
                    async: !0,
                    contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                    accepts: {
                        "*": Kb,
                        text: "text/plain",
                        html: "text/html",
                        xml: "application/xml, text/xml",
                        json: "application/json, text/javascript"
                    },
                    contents: {xml: /\bxml\b/, html: /\bhtml/, json: /\bjson\b/},
                    responseFields: {xml: "responseXML", text: "responseText", json: "responseJSON"},
                    converters: {"* text": String, "text html": !0, "text json": JSON.parse, "text xml": r.parseXML},
                    flatOptions: {url: !0, context: !0}
                },
                ajaxSetup: function (a, b) {
                    return b ? Ob(Ob(a, r.ajaxSettings), b) : Ob(r.ajaxSettings, a)
                },
                ajaxPrefilter: Mb(Ib),
                ajaxTransport: Mb(Jb),
                ajax: function (b, c) {
                    "object" == typeof b && (c = b, b = void 0), c = c || {};
                    var e, f, g, h, i, j, k, l, m, n, o = r.ajaxSetup({}, c), p = o.context || o,
                        q = o.context && (p.nodeType || p.jquery) ? r(p) : r.event, s = r.Deferred(),
                        t = r.Callbacks("once memory"), u = o.statusCode || {}, v = {}, w = {}, x = "canceled", y = {
                            readyState: 0, getResponseHeader: function (a) {
                                var b;
                                if (k) {
                                    if (!h) {
                                        h = {};
                                        while (b = Eb.exec(g)) h[b[1].toLowerCase()] = b[2]
                                    }
                                    b = h[a.toLowerCase()]
                                }
                                return null == b ? null : b
                            }, getAllResponseHeaders: function () {
                                return k ? g : null
                            }, setRequestHeader: function (a, b) {
                                return null == k && (a = w[a.toLowerCase()] = w[a.toLowerCase()] || a, v[a] = b), this
                            }, overrideMimeType: function (a) {
                                return null == k && (o.mimeType = a), this
                            }, statusCode: function (a) {
                                var b;
                                if (a) if (k) y.always(a[y.status]); else for (b in a) u[b] = [u[b], a[b]];
                                return this
                            }, abort: function (a) {
                                var b = a || x;
                                return e && e.abort(b), A(0, b), this
                            }
                        };
                    if (s.promise(y), o.url = ((b || o.url || tb.href) + "").replace(Hb, tb.protocol + "//"), o.type = c.method || c.type || o.method || o.type, o.dataTypes = (o.dataType || "*").toLowerCase().match(L) || [""], null == o.crossDomain) {
                        j = d.createElement("a");
                        try {
                            j.href = o.url, j.href = j.href, o.crossDomain = Lb.protocol + "//" + Lb.host != j.protocol + "//" + j.host
                        } catch (z) {
                            o.crossDomain = !0
                        }
                    }
                    if (o.data && o.processData && "string" != typeof o.data && (o.data = r.param(o.data, o.traditional)), Nb(Ib, o, c, y), k) return y;
                    l = r.event && o.global, l && 0 === r.active++ && r.event.trigger("ajaxStart"), o.type = o.type.toUpperCase(), o.hasContent = !Gb.test(o.type), f = o.url.replace(Cb, ""), o.hasContent ? o.data && o.processData && 0 === (o.contentType || "").indexOf("application/x-www-form-urlencoded") && (o.data = o.data.replace(Bb, "+")) : (n = o.url.slice(f.length), o.data && (f += (vb.test(f) ? "&" : "?") + o.data, delete o.data), o.cache === !1 && (f = f.replace(Db, "$1"), n = (vb.test(f) ? "&" : "?") + "_=" + ub++ + n), o.url = f + n), o.ifModified && (r.lastModified[f] && y.setRequestHeader("If-Modified-Since", r.lastModified[f]), r.etag[f] && y.setRequestHeader("If-None-Match", r.etag[f])), (o.data && o.hasContent && o.contentType !== !1 || c.contentType) && y.setRequestHeader("Content-Type", o.contentType), y.setRequestHeader("Accept", o.dataTypes[0] && o.accepts[o.dataTypes[0]] ? o.accepts[o.dataTypes[0]] + ("*" !== o.dataTypes[0] ? ", " + Kb + "; q=0.01" : "") : o.accepts["*"]);
                    for (m in o.headers) y.setRequestHeader(m, o.headers[m]);
                    if (o.beforeSend && (o.beforeSend.call(p, y, o) === !1 || k)) return y.abort();
                    if (x = "abort", t.add(o.complete), y.done(o.success), y.fail(o.error), e = Nb(Jb, o, c, y)) {
                        if (y.readyState = 1, l && q.trigger("ajaxSend", [y, o]), k) return y;
                        o.async && o.timeout > 0 && (i = a.setTimeout(function () {
                            y.abort("timeout")
                        }, o.timeout));
                        try {
                            k = !1, e.send(v, A)
                        } catch (z) {
                            if (k) throw z;
                            A(-1, z)
                        }
                    } else A(-1, "No Transport");

                    function A(b, c, d, h) {
                        var j, m, n, v, w, x = c;
                        k || (k = !0, i && a.clearTimeout(i), e = void 0, g = h || "", y.readyState = b > 0 ? 4 : 0, j = b >= 200 && b < 300 || 304 === b, d && (v = Pb(o, y, d)), v = Qb(o, v, y, j), j ? (o.ifModified && (w = y.getResponseHeader("Last-Modified"), w && (r.lastModified[f] = w), w = y.getResponseHeader("etag"), w && (r.etag[f] = w)), 204 === b || "HEAD" === o.type ? x = "nocontent" : 304 === b ? x = "notmodified" : (x = v.state, m = v.data, n = v.error, j = !n)) : (n = x, !b && x || (x = "error", b < 0 && (b = 0))), y.status = b, y.statusText = (c || x) + "", j ? s.resolveWith(p, [m, x, y]) : s.rejectWith(p, [y, x, n]), y.statusCode(u), u = void 0, l && q.trigger(j ? "ajaxSuccess" : "ajaxError", [y, o, j ? m : n]), t.fireWith(p, [y, x]), l && (q.trigger("ajaxComplete", [y, o]), --r.active || r.event.trigger("ajaxStop")))
                    }

                    return y
                },
                getJSON: function (a, b, c) {
                    return r.get(a, b, c, "json")
                },
                getScript: function (a, b) {
                    return r.get(a, void 0, b, "script")
                }
            }), r.each(["get", "post"], function (a, b) {
                r[b] = function (a, c, d, e) {
                    return r.isFunction(c) && (e = e || d, d = c, c = void 0), r.ajax(r.extend({
                        url: a,
                        type: b,
                        dataType: e,
                        data: c,
                        success: d
                    }, r.isPlainObject(a) && a))
                }
            }), r._evalUrl = function (a) {
                return r.ajax({url: a, type: "GET", dataType: "script", cache: !0, async: !1, global: !1, "throws": !0})
            }, r.fn.extend({
                wrapAll: function (a) {
                    var b;
                    return this[0] && (r.isFunction(a) && (a = a.call(this[0])), b = r(a, this[0].ownerDocument).eq(0).clone(!0), this[0].parentNode && b.insertBefore(this[0]), b.map(function () {
                        var a = this;
                        while (a.firstElementChild) a = a.firstElementChild;
                        return a
                    }).append(this)), this
                }, wrapInner: function (a) {
                    return r.isFunction(a) ? this.each(function (b) {
                        r(this).wrapInner(a.call(this, b))
                    }) : this.each(function () {
                        var b = r(this), c = b.contents();
                        c.length ? c.wrapAll(a) : b.append(a)
                    })
                }, wrap: function (a) {
                    var b = r.isFunction(a);
                    return this.each(function (c) {
                        r(this).wrapAll(b ? a.call(this, c) : a)
                    })
                }, unwrap: function (a) {
                    return this.parent(a).not("body").each(function () {
                        r(this).replaceWith(this.childNodes)
                    }), this
                }
            }), r.expr.pseudos.hidden = function (a) {
                return !r.expr.pseudos.visible(a)
            }, r.expr.pseudos.visible = function (a) {
                return !!(a.offsetWidth || a.offsetHeight || a.getClientRects().length)
            }, r.ajaxSettings.xhr = function () {
                try {
                    return new a.XMLHttpRequest
                } catch (b) {
                }
            };
            var Rb = {0: 200, 1223: 204}, Sb = r.ajaxSettings.xhr();
            o.cors = !!Sb && "withCredentials" in Sb, o.ajax = Sb = !!Sb, r.ajaxTransport(function (b) {
                var c, d;
                if (o.cors || Sb && !b.crossDomain) return {
                    send: function (e, f) {
                        var g, h = b.xhr();
                        if (h.open(b.type, b.url, b.async, b.username, b.password), b.xhrFields) for (g in b.xhrFields) h[g] = b.xhrFields[g];
                        b.mimeType && h.overrideMimeType && h.overrideMimeType(b.mimeType), b.crossDomain || e["X-Requested-With"] || (e["X-Requested-With"] = "XMLHttpRequest");
                        for (g in e) h.setRequestHeader(g, e[g]);
                        c = function (a) {
                            return function () {
                                c && (c = d = h.onload = h.onerror = h.onabort = h.onreadystatechange = null, "abort" === a ? h.abort() : "error" === a ? "number" != typeof h.status ? f(0, "error") : f(h.status, h.statusText) : f(Rb[h.status] || h.status, h.statusText, "text" !== (h.responseType || "text") || "string" != typeof h.responseText ? {binary: h.response} : {text: h.responseText}, h.getAllResponseHeaders()))
                            }
                        }, h.onload = c(), d = h.onerror = c("error"), void 0 !== h.onabort ? h.onabort = d : h.onreadystatechange = function () {
                            4 === h.readyState && a.setTimeout(function () {
                                c && d()
                            })
                        }, c = c("abort");
                        try {
                            h.send(b.hasContent && b.data || null)
                        } catch (i) {
                            if (c) throw i
                        }
                    }, abort: function () {
                        c && c()
                    }
                }
            }), r.ajaxPrefilter(function (a) {
                a.crossDomain && (a.contents.script = !1)
            }), r.ajaxSetup({
                accepts: {script: "text/javascript, application/javascript, application/ecmascript, application/x-ecmascript"},
                contents: {script: /\b(?:java|ecma)script\b/},
                converters: {
                    "text script": function (a) {
                        return r.globalEval(a), a
                    }
                }
            }), r.ajaxPrefilter("script", function (a) {
                void 0 === a.cache && (a.cache = !1), a.crossDomain && (a.type = "GET")
            }), r.ajaxTransport("script", function (a) {
                if (a.crossDomain) {
                    var b, c;
                    return {
                        send: function (e, f) {
                            b = r("<script>").prop({
                                charset: a.scriptCharset,
                                src: a.url
                            }).on("load error", c = function (a) {
                                b.remove(), c = null, a && f("error" === a.type ? 404 : 200, a.type)
                            }), d.head.appendChild(b[0])
                        }, abort: function () {
                            c && c()
                        }
                    }
                }
            });
            var Tb = [], Ub = /(=)\?(?=&|$)|\?\?/;
            r.ajaxSetup({
                jsonp: "callback", jsonpCallback: function () {
                    var a = Tb.pop() || r.expando + "_" + ub++;
                    return this[a] = !0, a
                }
            }), r.ajaxPrefilter("json jsonp", function (b, c, d) {
                var e, f, g,
                    h = b.jsonp !== !1 && (Ub.test(b.url) ? "url" : "string" == typeof b.data && 0 === (b.contentType || "").indexOf("application/x-www-form-urlencoded") && Ub.test(b.data) && "data");
                if (h || "jsonp" === b.dataTypes[0]) return e = b.jsonpCallback = r.isFunction(b.jsonpCallback) ? b.jsonpCallback() : b.jsonpCallback, h ? b[h] = b[h].replace(Ub, "$1" + e) : b.jsonp !== !1 && (b.url += (vb.test(b.url) ? "&" : "?") + b.jsonp + "=" + e), b.converters["script json"] = function () {
                    return g || r.error(e + " was not called"), g[0]
                }, b.dataTypes[0] = "json", f = a[e], a[e] = function () {
                    g = arguments
                }, d.always(function () {
                    void 0 === f ? r(a).removeProp(e) : a[e] = f, b[e] && (b.jsonpCallback = c.jsonpCallback, Tb.push(e)), g && r.isFunction(f) && f(g[0]), g = f = void 0
                }), "script"
            }), o.createHTMLDocument = function () {
                var a = d.implementation.createHTMLDocument("").body;
                return a.innerHTML = "<form></form><form></form>", 2 === a.childNodes.length
            }(), r.parseHTML = function (a, b, c) {
                if ("string" != typeof a) return [];
                "boolean" == typeof b && (c = b, b = !1);
                var e, f, g;
                return b || (o.createHTMLDocument ? (b = d.implementation.createHTMLDocument(""), e = b.createElement("base"), e.href = d.location.href, b.head.appendChild(e)) : b = d), f = C.exec(a), g = !c && [], f ? [b.createElement(f[1])] : (f = qa([a], b, g), g && g.length && r(g).remove(), r.merge([], f.childNodes))
            }, r.fn.load = function (a, b, c) {
                var d, e, f, g = this, h = a.indexOf(" ");
                return h > -1 && (d = pb(a.slice(h)), a = a.slice(0, h)), r.isFunction(b) ? (c = b, b = void 0) : b && "object" == typeof b && (e = "POST"), g.length > 0 && r.ajax({
                    url: a,
                    type: e || "GET",
                    dataType: "html",
                    data: b
                }).done(function (a) {
                    f = arguments, g.html(d ? r("<div>").append(r.parseHTML(a)).find(d) : a)
                }).always(c && function (a, b) {
                    g.each(function () {
                        c.apply(this, f || [a.responseText, b, a])
                    })
                }), this
            }, r.each(["ajaxStart", "ajaxStop", "ajaxComplete", "ajaxError", "ajaxSuccess", "ajaxSend"], function (a, b) {
                r.fn[b] = function (a) {
                    return this.on(b, a)
                }
            }), r.expr.pseudos.animated = function (a) {
                return r.grep(r.timers, function (b) {
                    return a === b.elem
                }).length
            }, r.offset = {
                setOffset: function (a, b, c) {
                    var d, e, f, g, h, i, j, k = r.css(a, "position"), l = r(a), m = {};
                    "static" === k && (a.style.position = "relative"), h = l.offset(), f = r.css(a, "top"), i = r.css(a, "left"), j = ("absolute" === k || "fixed" === k) && (f + i).indexOf("auto") > -1, j ? (d = l.position(), g = d.top, e = d.left) : (g = parseFloat(f) || 0, e = parseFloat(i) || 0), r.isFunction(b) && (b = b.call(a, c, r.extend({}, h))), null != b.top && (m.top = b.top - h.top + g), null != b.left && (m.left = b.left - h.left + e), "using" in b ? b.using.call(a, m) : l.css(m)
                }
            }, r.fn.extend({
                offset: function (a) {
                    if (arguments.length) return void 0 === a ? this : this.each(function (b) {
                        r.offset.setOffset(this, a, b)
                    });
                    var b, c, d, e, f = this[0];
                    if (f) return f.getClientRects().length ? (d = f.getBoundingClientRect(), b = f.ownerDocument, c = b.documentElement, e = b.defaultView, {
                        top: d.top + e.pageYOffset - c.clientTop,
                        left: d.left + e.pageXOffset - c.clientLeft
                    }) : {top: 0, left: 0}
                }, position: function () {
                    if (this[0]) {
                        var a, b, c = this[0], d = {top: 0, left: 0};
                        return "fixed" === r.css(c, "position") ? b = c.getBoundingClientRect() : (a = this.offsetParent(), b = this.offset(), B(a[0], "html") || (d = a.offset()), d = {
                            top: d.top + r.css(a[0], "borderTopWidth", !0),
                            left: d.left + r.css(a[0], "borderLeftWidth", !0)
                        }), {
                            top: b.top - d.top - r.css(c, "marginTop", !0),
                            left: b.left - d.left - r.css(c, "marginLeft", !0)
                        }
                    }
                }, offsetParent: function () {
                    return this.map(function () {
                        var a = this.offsetParent;
                        while (a && "static" === r.css(a, "position")) a = a.offsetParent;
                        return a || ra
                    })
                }
            }), r.each({scrollLeft: "pageXOffset", scrollTop: "pageYOffset"}, function (a, b) {
                var c = "pageYOffset" === b;
                r.fn[a] = function (d) {
                    return T(this, function (a, d, e) {
                        var f;
                        return r.isWindow(a) ? f = a : 9 === a.nodeType && (f = a.defaultView), void 0 === e ? f ? f[b] : a[d] : void(f ? f.scrollTo(c ? f.pageXOffset : e, c ? e : f.pageYOffset) : a[d] = e)
                    }, a, d, arguments.length)
                }
            }), r.each(["top", "left"], function (a, b) {
                r.cssHooks[b] = Pa(o.pixelPosition, function (a, c) {
                    if (c) return c = Oa(a, b), Ma.test(c) ? r(a).position()[b] + "px" : c
                })
            }), r.each({Height: "height", Width: "width"}, function (a, b) {
                r.each({padding: "inner" + a, content: b, "": "outer" + a}, function (c, d) {
                    r.fn[d] = function (e, f) {
                        var g = arguments.length && (c || "boolean" != typeof e),
                            h = c || (e === !0 || f === !0 ? "margin" : "border");
                        return T(this, function (b, c, e) {
                            var f;
                            return r.isWindow(b) ? 0 === d.indexOf("outer") ? b["inner" + a] : b.document.documentElement["client" + a] : 9 === b.nodeType ? (f = b.documentElement, Math.max(b.body["scroll" + a], f["scroll" + a], b.body["offset" + a], f["offset" + a], f["client" + a])) : void 0 === e ? r.css(b, c, h) : r.style(b, c, e, h)
                        }, b, g ? e : void 0, g)
                    }
                })
            }), r.fn.extend({
                bind: function (a, b, c) {
                    return this.on(a, null, b, c)
                }, unbind: function (a, b) {
                    return this.off(a, null, b)
                }, delegate: function (a, b, c, d) {
                    return this.on(b, a, c, d)
                }, undelegate: function (a, b, c) {
                    return 1 === arguments.length ? this.off(a, "**") : this.off(b, a || "**", c)
                }
            }), r.holdReady = function (a) {
                a ? r.readyWait++ : r.ready(!0)
            }, r.isArray = Array.isArray, r.parseJSON = JSON.parse, r.nodeName = B, "function" == typeof define && define.amd && define("jquery", [], function () {
                return r
            });
            var Vb = a.jQuery, Wb = a.$;
            return r.noConflict = function (b) {
                return a.$ === r && (a.$ = Wb), b && a.jQuery === r && (a.jQuery = Vb), r
            }, b || (a.jQuery = a.$ = r), r
        });


        function _classCallCheck(t, e) {
            if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
        }

        var _createClass = function () {
            function t(t, e) {
                for (var i = 0; i < e.length; i++) {
                    var n = e[i];
                    n.enumerable = n.enumerable || !1, n.configurable = !0, "value" in n && (n.writable = !0), Object.defineProperty(t, n.key, n)
                }
            }

            return function (e, i, n) {
                return i && t(e.prototype, i), n && t(e, n), e
            }
        }();
        if (void 0 === jQuery) {
            var jQuery;
            jQuery = "function" == typeof require ? $ = require("jquery") : $
        }
        !function (t) {
            "function" == typeof define && define.amd ? define(["jquery"], function (e) {
                return t(e)
            }) : "object" == typeof module && "object" == typeof module.exports ? exports = t(require("jquery")) : t(jQuery)
        }(function (t) {
            function e(t) {
                var e = 7.5625, i = 2.75;
                return t < 1 / i ? e * t * t : t < 2 / i ? e * (t -= 1.5 / i) * t + .75 : t < 2.5 / i ? e * (t -= 2.25 / i) * t + .9375 : e * (t -= 2.625 / i) * t + .984375
            }

            t.easing.jswing = t.easing.swing;
            var i = Math.pow, n = Math.sqrt, o = Math.sin, a = Math.cos, r = Math.PI, s = 1.70158, l = 1.525 * s,
                c = 2 * r / 3, u = 2 * r / 4.5;
            t.extend(t.easing, {
                def: "easeOutQuad", swing: function (e) {
                    return t.easing[t.easing.def](e)
                }, easeInQuad: function (t) {
                    return t * t
                }, easeOutQuad: function (t) {
                    return 1 - (1 - t) * (1 - t)
                }, easeInOutQuad: function (t) {
                    return t < .5 ? 2 * t * t : 1 - i(-2 * t + 2, 2) / 2
                }, easeInCubic: function (t) {
                    return t * t * t
                }, easeOutCubic: function (t) {
                    return 1 - i(1 - t, 3)
                }, easeInOutCubic: function (t) {
                    return t < .5 ? 4 * t * t * t : 1 - i(-2 * t + 2, 3) / 2
                }, easeInQuart: function (t) {
                    return t * t * t * t
                }, easeOutQuart: function (t) {
                    return 1 - i(1 - t, 4)
                }, easeInOutQuart: function (t) {
                    return t < .5 ? 8 * t * t * t * t : 1 - i(-2 * t + 2, 4) / 2
                }, easeInQuint: function (t) {
                    return t * t * t * t * t
                }, easeOutQuint: function (t) {
                    return 1 - i(1 - t, 5)
                }, easeInOutQuint: function (t) {
                    return t < .5 ? 16 * t * t * t * t * t : 1 - i(-2 * t + 2, 5) / 2
                }, easeInSine: function (t) {
                    return 1 - a(t * r / 2)
                }, easeOutSine: function (t) {
                    return o(t * r / 2)
                }, easeInOutSine: function (t) {
                    return -(a(r * t) - 1) / 2
                }, easeInExpo: function (t) {
                    return 0 === t ? 0 : i(2, 10 * t - 10)
                }, easeOutExpo: function (t) {
                    return 1 === t ? 1 : 1 - i(2, -10 * t)
                }, easeInOutExpo: function (t) {
                    return 0 === t ? 0 : 1 === t ? 1 : t < .5 ? i(2, 20 * t - 10) / 2 : (2 - i(2, -20 * t + 10)) / 2
                }, easeInCirc: function (t) {
                    return 1 - n(1 - i(t, 2))
                }, easeOutCirc: function (t) {
                    return n(1 - i(t - 1, 2))
                }, easeInOutCirc: function (t) {
                    return t < .5 ? (1 - n(1 - i(2 * t, 2))) / 2 : (n(1 - i(-2 * t + 2, 2)) + 1) / 2
                }, easeInElastic: function (t) {
                    return 0 === t ? 0 : 1 === t ? 1 : -i(2, 10 * t - 10) * o((10 * t - 10.75) * c)
                }, easeOutElastic: function (t) {
                    return 0 === t ? 0 : 1 === t ? 1 : i(2, -10 * t) * o((10 * t - .75) * c) + 1
                }, easeInOutElastic: function (t) {
                    return 0 === t ? 0 : 1 === t ? 1 : t < .5 ? -i(2, 20 * t - 10) * o((20 * t - 11.125) * u) / 2 : i(2, -20 * t + 10) * o((20 * t - 11.125) * u) / 2 + 1
                }, easeInBack: function (t) {
                    return 2.70158 * t * t * t - s * t * t
                }, easeOutBack: function (t) {
                    return 1 + 2.70158 * i(t - 1, 3) + s * i(t - 1, 2)
                }, easeInOutBack: function (t) {
                    return t < .5 ? i(2 * t, 2) * (7.189819 * t - l) / 2 : (i(2 * t - 2, 2) * ((l + 1) * (2 * t - 2) + l) + 2) / 2
                }, easeInBounce: function (t) {
                    return 1 - e(1 - t)
                }, easeOutBounce: e, easeInOutBounce: function (t) {
                    return t < .5 ? (1 - e(1 - 2 * t)) / 2 : (1 + e(2 * t - 1)) / 2
                }
            })
        }), jQuery.extend(jQuery.easing, {
            easeInOutMaterial: function (t, e, i, n, o) {
                return (e /= o / 2) < 1 ? n / 2 * e * e + i : n / 4 * ((e -= 2) * e * e + 2) + i
            }
        }), jQuery.Velocity ? console.log("Velocity is already loaded. You may be needlessly importing Velocity again; note that Materialize includes Velocity.") : (function (t) {
            function e(t) {
                var e = t.length, n = i.type(t);
                return "function" !== n && !i.isWindow(t) && (!(1 !== t.nodeType || !e) || ("array" === n || 0 === e || "number" == typeof e && e > 0 && e - 1 in t))
            }

            if (!t.jQuery) {
                var i = function (t, e) {
                    return new i.fn.init(t, e)
                };
                i.isWindow = function (t) {
                    return null != t && t == t.window
                }, i.type = function (t) {
                    return null == t ? t + "" : "object" == typeof t || "function" == typeof t ? o[r.call(t)] || "object" : typeof t
                }, i.isArray = Array.isArray || function (t) {
                    return "array" === i.type(t)
                }, i.isPlainObject = function (t) {
                    var e;
                    if (!t || "object" !== i.type(t) || t.nodeType || i.isWindow(t)) return !1;
                    try {
                        if (t.constructor && !a.call(t, "constructor") && !a.call(t.constructor.prototype, "isPrototypeOf")) return !1
                    } catch (t) {
                        return !1
                    }
                    for (e in t) ;
                    return void 0 === e || a.call(t, e)
                }, i.each = function (t, i, n) {
                    var o = 0, a = t.length, r = e(t);
                    if (n) {
                        if (r) for (; a > o && !1 !== i.apply(t[o], n); o++) ; else for (o in t) if (!1 === i.apply(t[o], n)) break
                    } else if (r) for (; a > o && !1 !== i.call(t[o], o, t[o]); o++) ; else for (o in t) if (!1 === i.call(t[o], o, t[o])) break;
                    return t
                }, i.data = function (t, e, o) {
                    if (void 0 === o) {
                        var a = (r = t[i.expando]) && n[r];
                        if (void 0 === e) return a;
                        if (a && e in a) return a[e]
                    } else if (void 0 !== e) {
                        var r = t[i.expando] || (t[i.expando] = ++i.uuid);
                        return n[r] = n[r] || {}, n[r][e] = o, o
                    }
                }, i.removeData = function (t, e) {
                    var o = t[i.expando], a = o && n[o];
                    a && i.each(e, function (t, e) {
                        delete a[e]
                    })
                }, i.extend = function () {
                    var t, e, n, o, a, r, s = arguments[0] || {}, l = 1, c = arguments.length, u = !1;
                    for ("boolean" == typeof s && (u = s, s = arguments[l] || {}, l++), "object" != typeof s && "function" !== i.type(s) && (s = {}), l === c && (s = this, l--); c > l; l++) if (null != (a = arguments[l])) for (o in a) t = s[o], s !== (n = a[o]) && (u && n && (i.isPlainObject(n) || (e = i.isArray(n))) ? (e ? (e = !1, r = t && i.isArray(t) ? t : []) : r = t && i.isPlainObject(t) ? t : {}, s[o] = i.extend(u, r, n)) : void 0 !== n && (s[o] = n));
                    return s
                }, i.queue = function (t, n, o) {
                    if (t) {
                        n = (n || "fx") + "queue";
                        var a = i.data(t, n);
                        return o ? (!a || i.isArray(o) ? a = i.data(t, n, function (t, i) {
                            var n = i || [];
                            return null != t && (e(Object(t)) ? function (t, e) {
                                for (var i = +e.length, n = 0, o = t.length; i > n;) t[o++] = e[n++];
                                if (i !== i) for (; void 0 !== e[n];) t[o++] = e[n++];
                                t.length = o
                            }(n, "string" == typeof t ? [t] : t) : [].push.call(n, t)), n
                        }(o)) : a.push(o), a) : a || []
                    }
                }, i.dequeue = function (t, e) {
                    i.each(t.nodeType ? [t] : t, function (t, n) {
                        e = e || "fx";
                        var o = i.queue(n, e), a = o.shift();
                        "inprogress" === a && (a = o.shift()), a && ("fx" === e && o.unshift("inprogress"), a.call(n, function () {
                            i.dequeue(n, e)
                        }))
                    })
                }, i.fn = i.prototype = {
                    init: function (t) {
                        if (t.nodeType) return this[0] = t, this;
                        throw new Error("Not a DOM node.")
                    }, offset: function () {
                        var e = this[0].getBoundingClientRect ? this[0].getBoundingClientRect() : {top: 0, left: 0};
                        return {
                            top: e.top + (t.pageYOffset || document.scrollTop || 0) - (document.clientTop || 0),
                            left: e.left + (t.pageXOffset || document.scrollLeft || 0) - (document.clientLeft || 0)
                        }
                    }, position: function () {
                        function t() {
                            for (var t = this.offsetParent || document; t && "html" === !t.nodeType.toLowerCase && "static" === t.style.position;) t = t.offsetParent;
                            return t || document
                        }

                        var e = this[0], t = t.apply(e), n = this.offset(),
                            o = /^(?:body|html)$/i.test(t.nodeName) ? {top: 0, left: 0} : i(t).offset();
                        return n.top -= parseFloat(e.style.marginTop) || 0, n.left -= parseFloat(e.style.marginLeft) || 0, t.style && (o.top += parseFloat(t.style.borderTopWidth) || 0, o.left += parseFloat(t.style.borderLeftWidth) || 0), {
                            top: n.top - o.top,
                            left: n.left - o.left
                        }
                    }
                };
                var n = {};
                i.expando = "velocity" + (new Date).getTime(), i.uuid = 0;
                for (var o = {}, a = o.hasOwnProperty, r = o.toString, s = "Boolean Number String Function Array Date RegExp Object Error".split(" "), l = 0; l < s.length; l++) o["[object " + s[l] + "]"] = s[l].toLowerCase();
                i.fn.init.prototype = i.fn, t.Velocity = {Utilities: i}
            }
        }(window), function (t) {
            "object" == typeof module && "object" == typeof module.exports ? module.exports = t() : "function" == typeof define && define.amd ? define(t) : t()
        }(function () {
            return function (t, e, i, n) {
                function o(t) {
                    for (var e = -1, i = t ? t.length : 0, n = []; ++e < i;) {
                        var o = t[e];
                        o && n.push(o)
                    }
                    return n
                }

                function a(t) {
                    return v.isWrapped(t) ? t = [].slice.call(t) : v.isNode(t) && (t = [t]), t
                }

                function r(t) {
                    var e = p.data(t, "velocity");
                    return null === e ? n : e
                }

                function s(t) {
                    return function (e) {
                        return Math.round(e * t) * (1 / t)
                    }
                }

                function l(t, i, n, o) {
                    function a(t, e) {
                        return 1 - 3 * e + 3 * t
                    }

                    function r(t, e) {
                        return 3 * e - 6 * t
                    }

                    function s(t) {
                        return 3 * t
                    }

                    function l(t, e, i) {
                        return ((a(e, i) * t + r(e, i)) * t + s(e)) * t
                    }

                    function c(t, e, i) {
                        return 3 * a(e, i) * t * t + 2 * r(e, i) * t + s(e)
                    }

                    function u(e, i) {
                        for (var o = 0; v > o; ++o) {
                            var a = c(i, t, n);
                            if (0 === a) return i;
                            i -= (l(i, t, n) - e) / a
                        }
                        return i
                    }

                    function d() {
                        for (var e = 0; b > e; ++e) C[e] = l(e * w, t, n)
                    }

                    function p(e, i, o) {
                        var a, r, s = 0;
                        do {
                            (a = l(r = i + (o - i) / 2, t, n) - e) > 0 ? o = r : i = r
                        } while (Math.abs(a) > g && ++s < y);
                        return r
                    }

                    function h(e) {
                        for (var i = 0, o = 1, a = b - 1; o != a && C[o] <= e; ++o) i += w;
                        var r = i + (e - C[--o]) / (C[o + 1] - C[o]) * w, s = c(r, t, n);
                        return s >= m ? u(e, r) : 0 == s ? r : p(e, i, i + w)
                    }

                    function f() {
                        T = !0, (t != i || n != o) && d()
                    }

                    var v = 4, m = .001, g = 1e-7, y = 10, b = 11, w = 1 / (b - 1), k = "Float32Array" in e;
                    if (4 !== arguments.length) return !1;
                    for (var x = 0; 4 > x; ++x) if ("number" != typeof arguments[x] || isNaN(arguments[x]) || !isFinite(arguments[x])) return !1;
                    t = Math.min(t, 1), n = Math.min(n, 1), t = Math.max(t, 0), n = Math.max(n, 0);
                    var C = k ? new Float32Array(b) : new Array(b), T = !1, S = function (e) {
                        return T || f(), t === i && n === o ? e : 0 === e ? 0 : 1 === e ? 1 : l(h(e), i, o)
                    };
                    S.getControlPoints = function () {
                        return [{x: t, y: i}, {x: n, y: o}]
                    };
                    var P = "generateBezier(" + [t, i, n, o] + ")";
                    return S.toString = function () {
                        return P
                    }, S
                }

                function c(t, e) {
                    var i = t;
                    return v.isString(t) ? b.Easings[t] || (i = !1) : i = v.isArray(t) && 1 === t.length ? s.apply(null, t) : v.isArray(t) && 2 === t.length ? w.apply(null, t.concat([e])) : !(!v.isArray(t) || 4 !== t.length) && l.apply(null, t), !1 === i && (i = b.Easings[b.defaults.easing] ? b.defaults.easing : y), i
                }

                function u(t) {
                    if (t) {
                        var e = (new Date).getTime(), i = b.State.calls.length;
                        i > 1e4 && (b.State.calls = o(b.State.calls));
                        for (var a = 0; i > a; a++) if (b.State.calls[a]) {
                            var s = b.State.calls[a], l = s[0], c = s[2], h = s[3], f = !!h, m = null;
                            h || (h = b.State.calls[a][3] = e - 16);
                            for (var g = Math.min((e - h) / c.duration, 1), y = 0, w = l.length; w > y; y++) {
                                var x = l[y], T = x.element;
                                if (r(T)) {
                                    var S = !1;
                                    if (c.display !== n && null !== c.display && "none" !== c.display) {
                                        if ("flex" === c.display) {
                                            var P = ["-webkit-box", "-moz-box", "-ms-flexbox", "-webkit-flex"];
                                            p.each(P, function (t, e) {
                                                k.setPropertyValue(T, "display", e)
                                            })
                                        }
                                        k.setPropertyValue(T, "display", c.display)
                                    }
                                    c.visibility !== n && "hidden" !== c.visibility && k.setPropertyValue(T, "visibility", c.visibility);
                                    for (var A in x) if ("element" !== A) {
                                        var O, _ = x[A], E = v.isString(_.easing) ? b.Easings[_.easing] : _.easing;
                                        if (1 === g) O = _.endValue; else {
                                            var M = _.endValue - _.startValue;
                                            if (O = _.startValue + M * E(g, c, M), !f && O === _.currentValue) continue
                                        }
                                        if (_.currentValue = O, "tween" === A) m = O; else {
                                            if (k.Hooks.registered[A]) {
                                                var I = k.Hooks.getRoot(A), D = r(T).rootPropertyValueCache[I];
                                                D && (_.rootPropertyValue = D)
                                            }
                                            var V = k.setPropertyValue(T, A, _.currentValue + (0 === parseFloat(O) ? "" : _.unitType), _.rootPropertyValue, _.scrollData);
                                            k.Hooks.registered[A] && (r(T).rootPropertyValueCache[I] = k.Normalizations.registered[I] ? k.Normalizations.registered[I]("extract", null, V[1]) : V[1]), "transform" === V[0] && (S = !0)
                                        }
                                    }
                                    c.mobileHA && r(T).transformCache.translate3d === n && (r(T).transformCache.translate3d = "(0px, 0px, 0px)", S = !0), S && k.flushTransformCache(T)
                                }
                            }
                            c.display !== n && "none" !== c.display && (b.State.calls[a][2].display = !1), c.visibility !== n && "hidden" !== c.visibility && (b.State.calls[a][2].visibility = !1), c.progress && c.progress.call(s[1], s[1], g, Math.max(0, h + c.duration - e), h, m), 1 === g && d(a)
                        }
                    }
                    b.State.isTicking && C(u)
                }

                function d(t, e) {
                    if (!b.State.calls[t]) return !1;
                    for (var i = b.State.calls[t][0], o = b.State.calls[t][1], a = b.State.calls[t][2], s = b.State.calls[t][4], l = !1, c = 0, u = i.length; u > c; c++) {
                        var d = i[c].element;
                        if (e || a.loop || ("none" === a.display && k.setPropertyValue(d, "display", a.display), "hidden" === a.visibility && k.setPropertyValue(d, "visibility", a.visibility)), !0 !== a.loop && (p.queue(d)[1] === n || !/\.velocityQueueEntryFlag/i.test(p.queue(d)[1])) && r(d)) {
                            r(d).isAnimating = !1, r(d).rootPropertyValueCache = {};
                            var h = !1;
                            p.each(k.Lists.transforms3D, function (t, e) {
                                var i = /^scale/.test(e) ? 1 : 0, o = r(d).transformCache[e];
                                r(d).transformCache[e] !== n && new RegExp("^\\(" + i + "[^.]").test(o) && (h = !0, delete r(d).transformCache[e])
                            }), a.mobileHA && (h = !0, delete r(d).transformCache.translate3d), h && k.flushTransformCache(d), k.Values.removeClass(d, "velocity-animating")
                        }
                        if (!e && a.complete && !a.loop && c === u - 1) try {
                            a.complete.call(o, o)
                        } catch (t) {
                            setTimeout(function () {
                                throw t
                            }, 1)
                        }
                        s && !0 !== a.loop && s(o), r(d) && !0 === a.loop && !e && (p.each(r(d).tweensContainer, function (t, e) {
                            /^rotate/.test(t) && 360 === parseFloat(e.endValue) && (e.endValue = 0, e.startValue = 360), /^backgroundPosition/.test(t) && 100 === parseFloat(e.endValue) && "%" === e.unitType && (e.endValue = 0, e.startValue = 100)
                        }), b(d, "reverse", {loop: !0, delay: a.delay})), !1 !== a.queue && p.dequeue(d, a.queue)
                    }
                    b.State.calls[t] = !1;
                    for (var f = 0, v = b.State.calls.length; v > f; f++) if (!1 !== b.State.calls[f]) {
                        l = !0;
                        break
                    }
                    !1 === l && (b.State.isTicking = !1, delete b.State.calls, b.State.calls = [])
                }

                var p, h = function () {
                    if (i.documentMode) return i.documentMode;
                    for (var t = 7; t > 4; t--) {
                        var e = i.createElement("div");
                        if (e.innerHTML = "\x3c!--[if IE " + t + "]><span></span><![endif]--\x3e", e.getElementsByTagName("span").length) return e = null, t
                    }
                    return n
                }(), f = function () {
                    var t = 0;
                    return e.webkitRequestAnimationFrame || e.mozRequestAnimationFrame || function (e) {
                        var i, n = (new Date).getTime();
                        return i = Math.max(0, 16 - (n - t)), t = n + i, setTimeout(function () {
                            e(n + i)
                        }, i)
                    }
                }(), v = {
                    isString: function (t) {
                        return "string" == typeof t
                    }, isArray: Array.isArray || function (t) {
                        return "[object Array]" === Object.prototype.toString.call(t)
                    }, isFunction: function (t) {
                        return "[object Function]" === Object.prototype.toString.call(t)
                    }, isNode: function (t) {
                        return t && t.nodeType
                    }, isNodeList: function (t) {
                        return "object" == typeof t && /^\[object (HTMLCollection|NodeList|Object)\]$/.test(Object.prototype.toString.call(t)) && t.length !== n && (0 === t.length || "object" == typeof t[0] && t[0].nodeType > 0)
                    }, isWrapped: function (t) {
                        return t && (t.jquery || e.Zepto && e.Zepto.zepto.isZ(t))
                    }, isSVG: function (t) {
                        return e.SVGElement && t instanceof e.SVGElement
                    }, isEmptyObject: function (t) {
                        for (var e in t) return !1;
                        return !0
                    }
                }, m = !1;
                if (t.fn && t.fn.jquery ? (p = t, m = !0) : p = e.Velocity.Utilities, 8 >= h && !m) throw new Error("Velocity: IE8 and below require jQuery to be loaded before Velocity.");
                {
                    if (!(7 >= h)) {
                        var g = 400, y = "swing", b = {
                            State: {
                                isMobile: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
                                isAndroid: /Android/i.test(navigator.userAgent),
                                isGingerbread: /Android 2\.3\.[3-7]/i.test(navigator.userAgent),
                                isChrome: e.chrome,
                                isFirefox: /Firefox/i.test(navigator.userAgent),
                                prefixElement: i.createElement("div"),
                                prefixMatches: {},
                                scrollAnchor: null,
                                scrollPropertyLeft: null,
                                scrollPropertyTop: null,
                                isTicking: !1,
                                calls: []
                            },
                            CSS: {},
                            Utilities: p,
                            Redirects: {},
                            Easings: {},
                            Promise: e.Promise,
                            defaults: {
                                queue: "",
                                duration: g,
                                easing: y,
                                begin: n,
                                complete: n,
                                progress: n,
                                display: n,
                                visibility: n,
                                loop: !1,
                                delay: !1,
                                mobileHA: !0,
                                _cacheValues: !0
                            },
                            init: function (t) {
                                p.data(t, "velocity", {
                                    isSVG: v.isSVG(t),
                                    isAnimating: !1,
                                    computedStyle: null,
                                    tweensContainer: null,
                                    rootPropertyValueCache: {},
                                    transformCache: {}
                                })
                            },
                            hook: null,
                            mock: !1,
                            version: {major: 1, minor: 2, patch: 2},
                            debug: !1
                        };
                        e.pageYOffset !== n ? (b.State.scrollAnchor = e, b.State.scrollPropertyLeft = "pageXOffset", b.State.scrollPropertyTop = "pageYOffset") : (b.State.scrollAnchor = i.documentElement || i.body.parentNode || i.body, b.State.scrollPropertyLeft = "scrollLeft", b.State.scrollPropertyTop = "scrollTop");
                        var w = function () {
                            function t(t) {
                                return -t.tension * t.x - t.friction * t.v
                            }

                            function e(e, i, n) {
                                var o = {
                                    x: e.x + n.dx * i,
                                    v: e.v + n.dv * i,
                                    tension: e.tension,
                                    friction: e.friction
                                };
                                return {dx: o.v, dv: t(o)}
                            }

                            function i(i, n) {
                                var o = {dx: i.v, dv: t(i)}, a = e(i, .5 * n, o), r = e(i, .5 * n, a), s = e(i, n, r),
                                    l = 1 / 6 * (o.dx + 2 * (a.dx + r.dx) + s.dx),
                                    c = 1 / 6 * (o.dv + 2 * (a.dv + r.dv) + s.dv);
                                return i.x = i.x + l * n, i.v = i.v + c * n, i
                            }

                            return function t(e, n, o) {
                                var a, r, s, l = {x: -1, v: 0, tension: null, friction: null}, c = [0], u = 0;
                                for (e = parseFloat(e) || 500, n = parseFloat(n) || 20, o = o || null, l.tension = e, l.friction = n, (a = null !== o) ? (u = t(e, n), r = u / o * .016) : r = .016; s = i(s || l, r), c.push(1 + s.x), u += 16, Math.abs(s.x) > 1e-4 && Math.abs(s.v) > 1e-4;) ;
                                return a ? function (t) {
                                    return c[t * (c.length - 1) | 0]
                                } : u
                            }
                        }();
                        b.Easings = {
                            linear: function (t) {
                                return t
                            }, swing: function (t) {
                                return .5 - Math.cos(t * Math.PI) / 2
                            }, spring: function (t) {
                                return 1 - Math.cos(4.5 * t * Math.PI) * Math.exp(6 * -t)
                            }
                        }, p.each([["ease", [.25, .1, .25, 1]], ["ease-in", [.42, 0, 1, 1]], ["ease-out", [0, 0, .58, 1]], ["ease-in-out", [.42, 0, .58, 1]], ["easeInSine", [.47, 0, .745, .715]], ["easeOutSine", [.39, .575, .565, 1]], ["easeInOutSine", [.445, .05, .55, .95]], ["easeInQuad", [.55, .085, .68, .53]], ["easeOutQuad", [.25, .46, .45, .94]], ["easeInOutQuad", [.455, .03, .515, .955]], ["easeInCubic", [.55, .055, .675, .19]], ["easeOutCubic", [.215, .61, .355, 1]], ["easeInOutCubic", [.645, .045, .355, 1]], ["easeInQuart", [.895, .03, .685, .22]], ["easeOutQuart", [.165, .84, .44, 1]], ["easeInOutQuart", [.77, 0, .175, 1]], ["easeInQuint", [.755, .05, .855, .06]], ["easeOutQuint", [.23, 1, .32, 1]], ["easeInOutQuint", [.86, 0, .07, 1]], ["easeInExpo", [.95, .05, .795, .035]], ["easeOutExpo", [.19, 1, .22, 1]], ["easeInOutExpo", [1, 0, 0, 1]], ["easeInCirc", [.6, .04, .98, .335]], ["easeOutCirc", [.075, .82, .165, 1]], ["easeInOutCirc", [.785, .135, .15, .86]]], function (t, e) {
                            b.Easings[e[0]] = l.apply(null, e[1])
                        });
                        var k = b.CSS = {
                            RegEx: {
                                isHex: /^#([A-f\d]{3}){1,2}$/i,
                                valueUnwrap: /^[A-z]+\((.*)\)$/i,
                                wrappedValueAlreadyExtracted: /[0-9.]+ [0-9.]+ [0-9.]+( [0-9.]+)?/,
                                valueSplit: /([A-z]+\(.+\))|(([A-z0-9#-.]+?)(?=\s|$))/gi
                            },
                            Lists: {
                                colors: ["fill", "stroke", "stopColor", "color", "backgroundColor", "borderColor", "borderTopColor", "borderRightColor", "borderBottomColor", "borderLeftColor", "outlineColor"],
                                transformsBase: ["translateX", "translateY", "scale", "scaleX", "scaleY", "skewX", "skewY", "rotateZ"],
                                transforms3D: ["transformPerspective", "translateZ", "scaleZ", "rotateX", "rotateY"]
                            },
                            Hooks: {
                                templates: {
                                    textShadow: ["Color X Y Blur", "black 0px 0px 0px"],
                                    boxShadow: ["Color X Y Blur Spread", "black 0px 0px 0px 0px"],
                                    clip: ["Top Right Bottom Left", "0px 0px 0px 0px"],
                                    backgroundPosition: ["X Y", "0% 0%"],
                                    transformOrigin: ["X Y Z", "50% 50% 0px"],
                                    perspectiveOrigin: ["X Y", "50% 50%"]
                                }, registered: {}, register: function () {
                                    for (a = 0; a < k.Lists.colors.length; a++) {
                                        var t = "color" === k.Lists.colors[a] ? "0 0 0 1" : "255 255 255 1";
                                        k.Hooks.templates[k.Lists.colors[a]] = ["Red Green Blue Alpha", t]
                                    }
                                    var e, i, n;
                                    if (h) for (e in k.Hooks.templates) {
                                        n = (i = k.Hooks.templates[e])[0].split(" ");
                                        var o = i[1].match(k.RegEx.valueSplit);
                                        "Color" === n[0] && (n.push(n.shift()), o.push(o.shift()), k.Hooks.templates[e] = [n.join(" "), o.join(" ")])
                                    }
                                    for (e in k.Hooks.templates) {
                                        n = (i = k.Hooks.templates[e])[0].split(" ");
                                        for (var a in n) {
                                            var r = e + n[a], s = a;
                                            k.Hooks.registered[r] = [e, s]
                                        }
                                    }
                                }, getRoot: function (t) {
                                    var e = k.Hooks.registered[t];
                                    return e ? e[0] : t
                                }, cleanRootPropertyValue: function (t, e) {
                                    return k.RegEx.valueUnwrap.test(e) && (e = e.match(k.RegEx.valueUnwrap)[1]), k.Values.isCSSNullValue(e) && (e = k.Hooks.templates[t][1]), e
                                }, extractValue: function (t, e) {
                                    var i = k.Hooks.registered[t];
                                    if (i) {
                                        var n = i[0], o = i[1];
                                        return (e = k.Hooks.cleanRootPropertyValue(n, e)).toString().match(k.RegEx.valueSplit)[o]
                                    }
                                    return e
                                }, injectValue: function (t, e, i) {
                                    var n = k.Hooks.registered[t];
                                    if (n) {
                                        var o, a = n[0], r = n[1];
                                        return i = k.Hooks.cleanRootPropertyValue(a, i), o = i.toString().match(k.RegEx.valueSplit), o[r] = e, o.join(" ")
                                    }
                                    return i
                                }
                            },
                            Normalizations: {
                                registered: {
                                    clip: function (t, e, i) {
                                        switch (t) {
                                            case"name":
                                                return "clip";
                                            case"extract":
                                                var n;
                                                return k.RegEx.wrappedValueAlreadyExtracted.test(i) ? n = i : (n = i.toString().match(k.RegEx.valueUnwrap), n = n ? n[1].replace(/,(\s+)?/g, " ") : i), n;
                                            case"inject":
                                                return "rect(" + i + ")"
                                        }
                                    }, blur: function (t, e, i) {
                                        switch (t) {
                                            case"name":
                                                return b.State.isFirefox ? "filter" : "-webkit-filter";
                                            case"extract":
                                                var n = parseFloat(i);
                                                if (!n && 0 !== n) {
                                                    var o = i.toString().match(/blur\(([0-9]+[A-z]+)\)/i);
                                                    n = o ? o[1] : 0
                                                }
                                                return n;
                                            case"inject":
                                                return parseFloat(i) ? "blur(" + i + ")" : "none"
                                        }
                                    }, opacity: function (t, e, i) {
                                        if (8 >= h) switch (t) {
                                            case"name":
                                                return "filter";
                                            case"extract":
                                                var n = i.toString().match(/alpha\(opacity=(.*)\)/i);
                                                return i = n ? n[1] / 100 : 1;
                                            case"inject":
                                                return e.style.zoom = 1, parseFloat(i) >= 1 ? "" : "alpha(opacity=" + parseInt(100 * parseFloat(i), 10) + ")"
                                        } else switch (t) {
                                            case"name":
                                                return "opacity";
                                            case"extract":
                                            case"inject":
                                                return i
                                        }
                                    }
                                }, register: function () {
                                    9 >= h || b.State.isGingerbread || (k.Lists.transformsBase = k.Lists.transformsBase.concat(k.Lists.transforms3D));
                                    for (t = 0; t < k.Lists.transformsBase.length; t++) !function () {
                                        var e = k.Lists.transformsBase[t];
                                        k.Normalizations.registered[e] = function (t, i, o) {
                                            switch (t) {
                                                case"name":
                                                    return "transform";
                                                case"extract":
                                                    return r(i) === n || r(i).transformCache[e] === n ? /^scale/i.test(e) ? 1 : 0 : r(i).transformCache[e].replace(/[()]/g, "");
                                                case"inject":
                                                    var a = !1;
                                                    switch (e.substr(0, e.length - 1)) {
                                                        case"translate":
                                                            a = !/(%|px|em|rem|vw|vh|\d)$/i.test(o);
                                                            break;
                                                        case"scal":
                                                        case"scale":
                                                            b.State.isAndroid && r(i).transformCache[e] === n && 1 > o && (o = 1), a = !/(\d)$/i.test(o);
                                                            break;
                                                        case"skew":
                                                            a = !/(deg|\d)$/i.test(o);
                                                            break;
                                                        case"rotate":
                                                            a = !/(deg|\d)$/i.test(o)
                                                    }
                                                    return a || (r(i).transformCache[e] = "(" + o + ")"), r(i).transformCache[e]
                                            }
                                        }
                                    }();
                                    for (var t = 0; t < k.Lists.colors.length; t++) !function () {
                                        var e = k.Lists.colors[t];
                                        k.Normalizations.registered[e] = function (t, i, o) {
                                            switch (t) {
                                                case"name":
                                                    return e;
                                                case"extract":
                                                    var a;
                                                    if (k.RegEx.wrappedValueAlreadyExtracted.test(o)) a = o; else {
                                                        var r, s = {
                                                            black: "rgb(0, 0, 0)",
                                                            blue: "rgb(0, 0, 255)",
                                                            gray: "rgb(128, 128, 128)",
                                                            green: "rgb(0, 128, 0)",
                                                            red: "rgb(255, 0, 0)",
                                                            white: "rgb(255, 255, 255)"
                                                        };
                                                        /^[A-z]+$/i.test(o) ? r = s[o] !== n ? s[o] : s.black : k.RegEx.isHex.test(o) ? r = "rgb(" + k.Values.hexToRgb(o).join(" ") + ")" : /^rgba?\(/i.test(o) || (r = s.black), a = (r || o).toString().match(k.RegEx.valueUnwrap)[1].replace(/,(\s+)?/g, " ")
                                                    }
                                                    return 8 >= h || 3 !== a.split(" ").length || (a += " 1"), a;
                                                case"inject":
                                                    return 8 >= h ? 4 === o.split(" ").length && (o = o.split(/\s+/).slice(0, 3).join(" ")) : 3 === o.split(" ").length && (o += " 1"), (8 >= h ? "rgb" : "rgba") + "(" + o.replace(/\s+/g, ",").replace(/\.(\d)+(?=,)/g, "") + ")"
                                            }
                                        }
                                    }()
                                }
                            },
                            Names: {
                                camelCase: function (t) {
                                    return t.replace(/-(\w)/g, function (t, e) {
                                        return e.toUpperCase()
                                    })
                                }, SVGAttribute: function (t) {
                                    var e = "width|height|x|y|cx|cy|r|rx|ry|x1|x2|y1|y2";
                                    return (h || b.State.isAndroid && !b.State.isChrome) && (e += "|transform"), new RegExp("^(" + e + ")$", "i").test(t)
                                }, prefixCheck: function (t) {
                                    if (b.State.prefixMatches[t]) return [b.State.prefixMatches[t], !0];
                                    for (var e = ["", "Webkit", "Moz", "ms", "O"], i = 0, n = e.length; n > i; i++) {
                                        var o;
                                        if (o = 0 === i ? t : e[i] + t.replace(/^\w/, function (t) {
                                                return t.toUpperCase()
                                            }), v.isString(b.State.prefixElement.style[o])) return b.State.prefixMatches[t] = o, [o, !0]
                                    }
                                    return [t, !1]
                                }
                            },
                            Values: {
                                hexToRgb: function (t) {
                                    var e, i = /^#?([a-f\d])([a-f\d])([a-f\d])$/i,
                                        n = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i;
                                    return t = t.replace(i, function (t, e, i, n) {
                                        return e + e + i + i + n + n
                                    }), e = n.exec(t), e ? [parseInt(e[1], 16), parseInt(e[2], 16), parseInt(e[3], 16)] : [0, 0, 0]
                                }, isCSSNullValue: function (t) {
                                    return 0 == t || /^(none|auto|transparent|(rgba\(0, ?0, ?0, ?0\)))$/i.test(t)
                                }, getUnitType: function (t) {
                                    return /^(rotate|skew)/i.test(t) ? "deg" : /(^(scale|scaleX|scaleY|scaleZ|alpha|flexGrow|flexHeight|zIndex|fontWeight)$)|((opacity|red|green|blue|alpha)$)/i.test(t) ? "" : "px"
                                }, getDisplayType: function (t) {
                                    var e = t && t.tagName.toString().toLowerCase();
                                    return /^(b|big|i|small|tt|abbr|acronym|cite|code|dfn|em|kbd|strong|samp|var|a|bdo|br|img|map|object|q|script|span|sub|sup|button|input|label|select|textarea)$/i.test(e) ? "inline" : /^(li)$/i.test(e) ? "list-item" : /^(tr)$/i.test(e) ? "table-row" : /^(table)$/i.test(e) ? "table" : /^(tbody)$/i.test(e) ? "table-row-group" : "block"
                                }, addClass: function (t, e) {
                                    t.classList ? t.classList.add(e) : t.className += (t.className.length ? " " : "") + e
                                }, removeClass: function (t, e) {
                                    t.classList ? t.classList.remove(e) : t.className = t.className.toString().replace(new RegExp("(^|\\s)" + e.split(" ").join("|") + "(\\s|$)", "gi"), " ")
                                }
                            },
                            getPropertyValue: function (t, i, o, a) {
                                function s(t, i) {
                                    function o() {
                                        c && k.setPropertyValue(t, "display", "none")
                                    }

                                    var l = 0;
                                    if (8 >= h) l = p.css(t, i); else {
                                        var c = !1;
                                        if (/^(width|height)$/.test(i) && 0 === k.getPropertyValue(t, "display") && (c = !0, k.setPropertyValue(t, "display", k.Values.getDisplayType(t))), !a) {
                                            if ("height" === i && "border-box" !== k.getPropertyValue(t, "boxSizing").toString().toLowerCase()) {
                                                var u = t.offsetHeight - (parseFloat(k.getPropertyValue(t, "borderTopWidth")) || 0) - (parseFloat(k.getPropertyValue(t, "borderBottomWidth")) || 0) - (parseFloat(k.getPropertyValue(t, "paddingTop")) || 0) - (parseFloat(k.getPropertyValue(t, "paddingBottom")) || 0);
                                                return o(), u
                                            }
                                            if ("width" === i && "border-box" !== k.getPropertyValue(t, "boxSizing").toString().toLowerCase()) {
                                                var d = t.offsetWidth - (parseFloat(k.getPropertyValue(t, "borderLeftWidth")) || 0) - (parseFloat(k.getPropertyValue(t, "borderRightWidth")) || 0) - (parseFloat(k.getPropertyValue(t, "paddingLeft")) || 0) - (parseFloat(k.getPropertyValue(t, "paddingRight")) || 0);
                                                return o(), d
                                            }
                                        }
                                        var f;
                                        f = r(t) === n ? e.getComputedStyle(t, null) : r(t).computedStyle ? r(t).computedStyle : r(t).computedStyle = e.getComputedStyle(t, null), "borderColor" === i && (i = "borderTopColor"), ("" === (l = 9 === h && "filter" === i ? f.getPropertyValue(i) : f[i]) || null === l) && (l = t.style[i]), o()
                                    }
                                    if ("auto" === l && /^(top|right|bottom|left)$/i.test(i)) {
                                        var v = s(t, "position");
                                        ("fixed" === v || "absolute" === v && /top|left/i.test(i)) && (l = p(t).position()[i] + "px")
                                    }
                                    return l
                                }

                                var l;
                                if (k.Hooks.registered[i]) {
                                    var c = i, u = k.Hooks.getRoot(c);
                                    o === n && (o = k.getPropertyValue(t, k.Names.prefixCheck(u)[0])), k.Normalizations.registered[u] && (o = k.Normalizations.registered[u]("extract", t, o)), l = k.Hooks.extractValue(c, o)
                                } else if (k.Normalizations.registered[i]) {
                                    var d, f;
                                    "transform" !== (d = k.Normalizations.registered[i]("name", t)) && (f = s(t, k.Names.prefixCheck(d)[0]), k.Values.isCSSNullValue(f) && k.Hooks.templates[i] && (f = k.Hooks.templates[i][1])), l = k.Normalizations.registered[i]("extract", t, f)
                                }
                                if (!/^[\d-]/.test(l)) if (r(t) && r(t).isSVG && k.Names.SVGAttribute(i)) if (/^(height|width)$/i.test(i)) try {
                                    l = t.getBBox()[i]
                                } catch (t) {
                                    l = 0
                                } else l = t.getAttribute(i); else l = s(t, k.Names.prefixCheck(i)[0]);
                                return k.Values.isCSSNullValue(l) && (l = 0), b.debug >= 2 && console.log("Get " + i + ": " + l), l
                            },
                            setPropertyValue: function (t, i, n, o, a) {
                                var s = i;
                                if ("scroll" === i) a.container ? a.container["scroll" + a.direction] = n : "Left" === a.direction ? e.scrollTo(n, a.alternateValue) : e.scrollTo(a.alternateValue, n); else if (k.Normalizations.registered[i] && "transform" === k.Normalizations.registered[i]("name", t)) k.Normalizations.registered[i]("inject", t, n), s = "transform", n = r(t).transformCache[i]; else {
                                    if (k.Hooks.registered[i]) {
                                        var l = i, c = k.Hooks.getRoot(i);
                                        o = o || k.getPropertyValue(t, c), n = k.Hooks.injectValue(l, n, o), i = c
                                    }
                                    if (k.Normalizations.registered[i] && (n = k.Normalizations.registered[i]("inject", t, n), i = k.Normalizations.registered[i]("name", t)), s = k.Names.prefixCheck(i)[0], 8 >= h) try {
                                        t.style[s] = n
                                    } catch (t) {
                                        b.debug && console.log("Browser does not support [" + n + "] for [" + s + "]")
                                    } else r(t) && r(t).isSVG && k.Names.SVGAttribute(i) ? t.setAttribute(i, n) : t.style[s] = n;
                                    b.debug >= 2 && console.log("Set " + i + " (" + s + "): " + n)
                                }
                                return [s, n]
                            },
                            flushTransformCache: function (t) {
                                function e(e) {
                                    return parseFloat(k.getPropertyValue(t, e))
                                }

                                var i = "";
                                if ((h || b.State.isAndroid && !b.State.isChrome) && r(t).isSVG) {
                                    var n = {
                                        translate: [e("translateX"), e("translateY")],
                                        skewX: [e("skewX")],
                                        skewY: [e("skewY")],
                                        scale: 1 !== e("scale") ? [e("scale"), e("scale")] : [e("scaleX"), e("scaleY")],
                                        rotate: [e("rotateZ"), 0, 0]
                                    };
                                    p.each(r(t).transformCache, function (t) {
                                        /^translate/i.test(t) ? t = "translate" : /^scale/i.test(t) ? t = "scale" : /^rotate/i.test(t) && (t = "rotate"), n[t] && (i += t + "(" + n[t].join(" ") + ") ", delete n[t])
                                    })
                                } else {
                                    var o, a;
                                    p.each(r(t).transformCache, function (e) {
                                        return o = r(t).transformCache[e], "transformPerspective" === e ? (a = o, !0) : (9 === h && "rotateZ" === e && (e = "rotate"), void(i += e + o + " "))
                                    }), a && (i = "perspective" + a + " " + i)
                                }
                                k.setPropertyValue(t, "transform", i)
                            }
                        };
                        k.Hooks.register(), k.Normalizations.register(), b.hook = function (t, e, i) {
                            var o = n;
                            return t = a(t), p.each(t, function (t, a) {
                                if (r(a) === n && b.init(a), i === n) o === n && (o = b.CSS.getPropertyValue(a, e)); else {
                                    var s = b.CSS.setPropertyValue(a, e, i);
                                    "transform" === s[0] && b.CSS.flushTransformCache(a), o = s
                                }
                            }), o
                        };
                        var x = function () {
                            function t() {
                                return s ? P.promise || null : l
                            }

                            function o() {
                                function t(t) {
                                    function d(t, e) {
                                        var i = n, o = n, r = n;
                                        return v.isArray(t) ? (i = t[0], !v.isArray(t[1]) && /^[\d-]/.test(t[1]) || v.isFunction(t[1]) || k.RegEx.isHex.test(t[1]) ? r = t[1] : (v.isString(t[1]) && !k.RegEx.isHex.test(t[1]) || v.isArray(t[1])) && (o = e ? t[1] : c(t[1], s.duration), t[2] !== n && (r = t[2]))) : i = t, e || (o = o || s.easing), v.isFunction(i) && (i = i.call(a, T, C)), v.isFunction(r) && (r = r.call(a, T, C)), [i || 0, o, r]
                                    }

                                    function h(t, e) {
                                        var i, n;
                                        return n = (e || "0").toString().toLowerCase().replace(/[%A-z]+$/, function (t) {
                                            return i = t, ""
                                        }), i || (i = k.Values.getUnitType(t)), [n, i]
                                    }

                                    if (s.begin && 0 === T) try {
                                        s.begin.call(f, f)
                                    } catch (t) {
                                        setTimeout(function () {
                                            throw t
                                        }, 1)
                                    }
                                    if ("scroll" === A) {
                                        var g, w, x, S = /^x$/i.test(s.axis) ? "Left" : "Top",
                                            O = parseFloat(s.offset) || 0;
                                        s.container ? v.isWrapped(s.container) || v.isNode(s.container) ? (s.container = s.container[0] || s.container, g = s.container["scroll" + S], x = g + p(a).position()[S.toLowerCase()] + O) : s.container = null : (g = b.State.scrollAnchor[b.State["scrollProperty" + S]], w = b.State.scrollAnchor[b.State["scrollProperty" + ("Left" === S ? "Top" : "Left")]], x = p(a).offset()[S.toLowerCase()] + O), l = {
                                            scroll: {
                                                rootPropertyValue: !1,
                                                startValue: g,
                                                currentValue: g,
                                                endValue: x,
                                                unitType: "",
                                                easing: s.easing,
                                                scrollData: {container: s.container, direction: S, alternateValue: w}
                                            }, element: a
                                        }, b.debug && console.log("tweensContainer (scroll): ", l.scroll, a)
                                    } else if ("reverse" === A) {
                                        if (!r(a).tweensContainer) return void p.dequeue(a, s.queue);
                                        "none" === r(a).opts.display && (r(a).opts.display = "auto"), "hidden" === r(a).opts.visibility && (r(a).opts.visibility = "visible"), r(a).opts.loop = !1, r(a).opts.begin = null, r(a).opts.complete = null, y.easing || delete s.easing, y.duration || delete s.duration, s = p.extend({}, r(a).opts, s);
                                        M = p.extend(!0, {}, r(a).tweensContainer);
                                        for (var _ in M) if ("element" !== _) {
                                            var E = M[_].startValue;
                                            M[_].startValue = M[_].currentValue = M[_].endValue, M[_].endValue = E, v.isEmptyObject(y) || (M[_].easing = s.easing), b.debug && console.log("reverse tweensContainer (" + _ + "): " + JSON.stringify(M[_]), a)
                                        }
                                        l = M
                                    } else if ("start" === A) {
                                        var M;
                                        r(a).tweensContainer && !0 === r(a).isAnimating && (M = r(a).tweensContainer), p.each(m, function (t, e) {
                                            if (RegExp("^" + k.Lists.colors.join("$|^") + "$").test(t)) {
                                                var i = d(e, !0), o = i[0], a = i[1], r = i[2];
                                                if (k.RegEx.isHex.test(o)) {
                                                    for (var s = ["Red", "Green", "Blue"], l = k.Values.hexToRgb(o), c = r ? k.Values.hexToRgb(r) : n, u = 0; u < s.length; u++) {
                                                        var p = [l[u]];
                                                        a && p.push(a), c !== n && p.push(c[u]), m[t + s[u]] = p
                                                    }
                                                    delete m[t]
                                                }
                                            }
                                        });
                                        for (var V in m) {
                                            var q = d(m[V]), z = q[0], H = q[1], L = q[2];
                                            V = k.Names.camelCase(V);
                                            var j = k.Hooks.getRoot(V), $ = !1;
                                            if (r(a).isSVG || "tween" === j || !1 !== k.Names.prefixCheck(j)[1] || k.Normalizations.registered[j] !== n) {
                                                (s.display !== n && null !== s.display && "none" !== s.display || s.visibility !== n && "hidden" !== s.visibility) && /opacity|filter/.test(V) && !L && 0 !== z && (L = 0), s._cacheValues && M && M[V] ? (L === n && (L = M[V].endValue + M[V].unitType), $ = r(a).rootPropertyValueCache[j]) : k.Hooks.registered[V] ? L === n ? ($ = k.getPropertyValue(a, j), L = k.getPropertyValue(a, V, $)) : $ = k.Hooks.templates[j][1] : L === n && (L = k.getPropertyValue(a, V));
                                                var N, W, F, Q = !1;
                                                if (N = h(V, L), L = N[0], F = N[1], N = h(V, z), z = N[0].replace(/^([+-\/*])=/, function (t, e) {
                                                        return Q = e, ""
                                                    }), W = N[1], L = parseFloat(L) || 0, z = parseFloat(z) || 0, "%" === W && (/^(fontSize|lineHeight)$/.test(V) ? (z /= 100, W = "em") : /^scale/.test(V) ? (z /= 100, W = "") : /(Red|Green|Blue)$/i.test(V) && (z = z / 100 * 255, W = "")), /[\/*]/.test(Q)) W = F; else if (F !== W && 0 !== L) if (0 === z) W = F; else {
                                                    o = o || function () {
                                                        var t = {
                                                                myParent: a.parentNode || i.body,
                                                                position: k.getPropertyValue(a, "position"),
                                                                fontSize: k.getPropertyValue(a, "fontSize")
                                                            },
                                                            n = t.position === I.lastPosition && t.myParent === I.lastParent,
                                                            o = t.fontSize === I.lastFontSize;
                                                        I.lastParent = t.myParent, I.lastPosition = t.position, I.lastFontSize = t.fontSize;
                                                        var s = 100, l = {};
                                                        if (o && n) l.emToPx = I.lastEmToPx, l.percentToPxWidth = I.lastPercentToPxWidth, l.percentToPxHeight = I.lastPercentToPxHeight; else {
                                                            var c = r(a).isSVG ? i.createElementNS("http://www.w3.org/2000/svg", "rect") : i.createElement("div");
                                                            b.init(c), t.myParent.appendChild(c), p.each(["overflow", "overflowX", "overflowY"], function (t, e) {
                                                                b.CSS.setPropertyValue(c, e, "hidden")
                                                            }), b.CSS.setPropertyValue(c, "position", t.position), b.CSS.setPropertyValue(c, "fontSize", t.fontSize), b.CSS.setPropertyValue(c, "boxSizing", "content-box"), p.each(["minWidth", "maxWidth", "width", "minHeight", "maxHeight", "height"], function (t, e) {
                                                                b.CSS.setPropertyValue(c, e, s + "%")
                                                            }), b.CSS.setPropertyValue(c, "paddingLeft", s + "em"), l.percentToPxWidth = I.lastPercentToPxWidth = (parseFloat(k.getPropertyValue(c, "width", null, !0)) || 1) / s, l.percentToPxHeight = I.lastPercentToPxHeight = (parseFloat(k.getPropertyValue(c, "height", null, !0)) || 1) / s, l.emToPx = I.lastEmToPx = (parseFloat(k.getPropertyValue(c, "paddingLeft")) || 1) / s, t.myParent.removeChild(c)
                                                        }
                                                        return null === I.remToPx && (I.remToPx = parseFloat(k.getPropertyValue(i.body, "fontSize")) || 16), null === I.vwToPx && (I.vwToPx = parseFloat(e.innerWidth) / 100, I.vhToPx = parseFloat(e.innerHeight) / 100), l.remToPx = I.remToPx, l.vwToPx = I.vwToPx, l.vhToPx = I.vhToPx, b.debug >= 1 && console.log("Unit ratios: " + JSON.stringify(l), a), l
                                                    }();
                                                    var X = /margin|padding|left|right|width|text|word|letter/i.test(V) || /X$/.test(V) || "x" === V ? "x" : "y";
                                                    switch (F) {
                                                        case"%":
                                                            L *= "x" === X ? o.percentToPxWidth : o.percentToPxHeight;
                                                            break;
                                                        case"px":
                                                            break;
                                                        default:
                                                            L *= o[F + "ToPx"]
                                                    }
                                                    switch (W) {
                                                        case"%":
                                                            L *= 1 / ("x" === X ? o.percentToPxWidth : o.percentToPxHeight);
                                                            break;
                                                        case"px":
                                                            break;
                                                        default:
                                                            L *= 1 / o[W + "ToPx"]
                                                    }
                                                }
                                                switch (Q) {
                                                    case"+":
                                                        z = L + z;
                                                        break;
                                                    case"-":
                                                        z = L - z;
                                                        break;
                                                    case"*":
                                                        z *= L;
                                                        break;
                                                    case"/":
                                                        z = L / z
                                                }
                                                l[V] = {
                                                    rootPropertyValue: $,
                                                    startValue: L,
                                                    currentValue: L,
                                                    endValue: z,
                                                    unitType: W,
                                                    easing: H
                                                }, b.debug && console.log("tweensContainer (" + V + "): " + JSON.stringify(l[V]), a)
                                            } else b.debug && console.log("Skipping [" + j + "] due to a lack of browser support.")
                                        }
                                        l.element = a
                                    }
                                    l.element && (k.Values.addClass(a, "velocity-animating"), D.push(l), "" === s.queue && (r(a).tweensContainer = l, r(a).opts = s), r(a).isAnimating = !0, T === C - 1 ? (b.State.calls.push([D, f, s, null, P.resolver]), !1 === b.State.isTicking && (b.State.isTicking = !0, u())) : T++)
                                }

                                var o, a = this, s = p.extend({}, b.defaults, y), l = {};
                                switch (r(a) === n && b.init(a), parseFloat(s.delay) && !1 !== s.queue && p.queue(a, s.queue, function (t) {
                                    b.velocityQueueEntryFlag = !0, r(a).delayTimer = {
                                        setTimeout: setTimeout(t, parseFloat(s.delay)),
                                        next: t
                                    }
                                }), s.duration.toString().toLowerCase()) {
                                    case"fast":
                                        s.duration = 200;
                                        break;
                                    case"normal":
                                        s.duration = g;
                                        break;
                                    case"slow":
                                        s.duration = 600;
                                        break;
                                    default:
                                        s.duration = parseFloat(s.duration) || 1
                                }
                                !1 !== b.mock && (!0 === b.mock ? s.duration = s.delay = 1 : (s.duration *= parseFloat(b.mock) || 1, s.delay *= parseFloat(b.mock) || 1)), s.easing = c(s.easing, s.duration), s.begin && !v.isFunction(s.begin) && (s.begin = null), s.progress && !v.isFunction(s.progress) && (s.progress = null), s.complete && !v.isFunction(s.complete) && (s.complete = null), s.display !== n && null !== s.display && (s.display = s.display.toString().toLowerCase(), "auto" === s.display && (s.display = b.CSS.Values.getDisplayType(a))), s.visibility !== n && null !== s.visibility && (s.visibility = s.visibility.toString().toLowerCase()), s.mobileHA = s.mobileHA && b.State.isMobile && !b.State.isGingerbread, !1 === s.queue ? s.delay ? setTimeout(t, s.delay) : t() : p.queue(a, s.queue, function (e, i) {
                                    return !0 === i ? (P.promise && P.resolver(f), !0) : (b.velocityQueueEntryFlag = !0, void t(e))
                                }), "" !== s.queue && "fx" !== s.queue || "inprogress" === p.queue(a)[0] || p.dequeue(a)
                            }

                            var s, l, h, f, m, y,
                                w = arguments[0] && (arguments[0].p || p.isPlainObject(arguments[0].properties) && !arguments[0].properties.names || v.isString(arguments[0].properties));
                            if (v.isWrapped(this) ? (s = !1, h = 0, f = this, l = this) : (s = !0, h = 1, f = w ? arguments[0].elements || arguments[0].e : arguments[0]), f = a(f)) {
                                w ? (m = arguments[0].properties || arguments[0].p, y = arguments[0].options || arguments[0].o) : (m = arguments[h], y = arguments[h + 1]);
                                var C = f.length, T = 0;
                                if (!/^(stop|finish)$/i.test(m) && !p.isPlainObject(y)) {
                                    y = {};
                                    for (var S = h + 1; S < arguments.length; S++) v.isArray(arguments[S]) || !/^(fast|normal|slow)$/i.test(arguments[S]) && !/^\d/.test(arguments[S]) ? v.isString(arguments[S]) || v.isArray(arguments[S]) ? y.easing = arguments[S] : v.isFunction(arguments[S]) && (y.complete = arguments[S]) : y.duration = arguments[S]
                                }
                                var P = {promise: null, resolver: null, rejecter: null};
                                s && b.Promise && (P.promise = new b.Promise(function (t, e) {
                                    P.resolver = t, P.rejecter = e
                                }));
                                var A;
                                switch (m) {
                                    case"scroll":
                                        A = "scroll";
                                        break;
                                    case"reverse":
                                        A = "reverse";
                                        break;
                                    case"finish":
                                    case"stop":
                                        p.each(f, function (t, e) {
                                            r(e) && r(e).delayTimer && (clearTimeout(r(e).delayTimer.setTimeout), r(e).delayTimer.next && r(e).delayTimer.next(), delete r(e).delayTimer)
                                        });
                                        var O = [];
                                        return p.each(b.State.calls, function (t, e) {
                                            e && p.each(e[1], function (i, o) {
                                                var a = y === n ? "" : y;
                                                return !0 !== a && e[2].queue !== a && (y !== n || !1 !== e[2].queue) || void p.each(f, function (i, n) {
                                                    n === o && ((!0 === y || v.isString(y)) && (p.each(p.queue(n, v.isString(y) ? y : ""), function (t, e) {
                                                        v.isFunction(e) && e(null, !0)
                                                    }), p.queue(n, v.isString(y) ? y : "", [])), "stop" === m ? (r(n) && r(n).tweensContainer && !1 !== a && p.each(r(n).tweensContainer, function (t, e) {
                                                        e.endValue = e.currentValue
                                                    }), O.push(t)) : "finish" === m && (e[2].duration = 1))
                                                })
                                            })
                                        }), "stop" === m && (p.each(O, function (t, e) {
                                            d(e, !0)
                                        }), P.promise && P.resolver(f)), t();
                                    default:
                                        if (!p.isPlainObject(m) || v.isEmptyObject(m)) {
                                            if (v.isString(m) && b.Redirects[m]) {
                                                var _ = (q = p.extend({}, y)).duration, E = q.delay || 0;
                                                return !0 === q.backwards && (f = p.extend(!0, [], f).reverse()), p.each(f, function (t, e) {
                                                    parseFloat(q.stagger) ? q.delay = E + parseFloat(q.stagger) * t : v.isFunction(q.stagger) && (q.delay = E + q.stagger.call(e, t, C)), q.drag && (q.duration = parseFloat(_) || (/^(callout|transition)/.test(m) ? 1e3 : g), q.duration = Math.max(q.duration * (q.backwards ? 1 - t / C : (t + 1) / C), .75 * q.duration, 200)), b.Redirects[m].call(e, e, q || {}, t, C, f, P.promise ? P : n)
                                                }), t()
                                            }
                                            var M = "Velocity: First argument (" + m + ") was not a property map, a known action, or a registered redirect. Aborting.";
                                            return P.promise ? P.rejecter(new Error(M)) : console.log(M), t()
                                        }
                                        A = "start"
                                }
                                var I = {
                                    lastParent: null,
                                    lastPosition: null,
                                    lastFontSize: null,
                                    lastPercentToPxWidth: null,
                                    lastPercentToPxHeight: null,
                                    lastEmToPx: null,
                                    remToPx: null,
                                    vwToPx: null,
                                    vhToPx: null
                                }, D = [];
                                p.each(f, function (t, e) {
                                    v.isNode(e) && o.call(e)
                                });
                                var V, q = p.extend({}, b.defaults, y);
                                if (q.loop = parseInt(q.loop), V = 2 * q.loop - 1, q.loop) for (var z = 0; V > z; z++) {
                                    var H = {delay: q.delay, progress: q.progress};
                                    z === V - 1 && (H.display = q.display, H.visibility = q.visibility, H.complete = q.complete), x(f, "reverse", H)
                                }
                                return t()
                            }
                        };
                        (b = p.extend(x, b)).animate = x;
                        var C = e.requestAnimationFrame || f;
                        return b.State.isMobile || i.hidden === n || i.addEventListener("visibilitychange", function () {
                            i.hidden ? (C = function (t) {
                                return setTimeout(function () {
                                    t(!0)
                                }, 16)
                            }, u()) : C = e.requestAnimationFrame || f
                        }), t.Velocity = b, t !== e && (t.fn.velocity = x, t.fn.velocity.defaults = b.defaults), p.each(["Down", "Up"], function (t, e) {
                            b.Redirects["slide" + e] = function (t, i, o, a, r, s) {
                                var l = p.extend({}, i), c = l.begin, u = l.complete, d = {
                                    height: "",
                                    marginTop: "",
                                    marginBottom: "",
                                    paddingTop: "",
                                    paddingBottom: ""
                                }, h = {};
                                l.display === n && (l.display = "Down" === e ? "inline" === b.CSS.Values.getDisplayType(t) ? "inline-block" : "block" : "none"), l.begin = function () {
                                    c && c.call(r, r);
                                    for (var i in d) {
                                        h[i] = t.style[i];
                                        var n = b.CSS.getPropertyValue(t, i);
                                        d[i] = "Down" === e ? [n, 0] : [0, n]
                                    }
                                    h.overflow = t.style.overflow, t.style.overflow = "hidden"
                                }, l.complete = function () {
                                    for (var e in h) t.style[e] = h[e];
                                    u && u.call(r, r), s && s.resolver(r)
                                }, b(t, d, l)
                            }
                        }), p.each(["In", "Out"], function (t, e) {
                            b.Redirects["fade" + e] = function (t, i, o, a, r, s) {
                                var l = p.extend({}, i), c = {opacity: "In" === e ? 1 : 0}, u = l.complete;
                                l.complete = o !== a - 1 ? l.begin = null : function () {
                                    u && u.call(r, r), s && s.resolver(r)
                                }, l.display === n && (l.display = "In" === e ? "auto" : "none"), b(this, c, l)
                            }
                        }), b
                    }
                    jQuery.fn.velocity = jQuery.fn.animate
                }
            }(window.jQuery || window.Zepto || window, window, document)
        })), function (t, e, i, n) {
            "use strict";

            function o(t, e, i) {
                return setTimeout(u(t, i), e)
            }

            function a(t, e, i) {
                return !!Array.isArray(t) && (r(t, i[e], i), !0)
            }

            function r(t, e, i) {
                var o;
                if (t) if (t.forEach) t.forEach(e, i); else if (t.length !== n) for (o = 0; o < t.length;) e.call(i, t[o], o, t), o++; else for (o in t) t.hasOwnProperty(o) && e.call(i, t[o], o, t)
            }

            function s(t, e, i) {
                for (var o = Object.keys(e), a = 0; a < o.length;) (!i || i && t[o[a]] === n) && (t[o[a]] = e[o[a]]), a++;
                return t
            }

            function l(t, e) {
                return s(t, e, !0)
            }

            function c(t, e, i) {
                var n, o = e.prototype;
                (n = t.prototype = Object.create(o)).constructor = t, n._super = o, i && s(n, i)
            }

            function u(t, e) {
                return function () {
                    return t.apply(e, arguments)
                }
            }

            function d(t, e) {
                return typeof t == ut ? t.apply(e ? e[0] || n : n, e) : t
            }

            function p(t, e) {
                return t === n ? e : t
            }

            function h(t, e, i) {
                r(g(e), function (e) {
                    t.addEventListener(e, i, !1)
                })
            }

            function f(t, e, i) {
                r(g(e), function (e) {
                    t.removeEventListener(e, i, !1)
                })
            }

            function v(t, e) {
                for (; t;) {
                    if (t == e) return !0;
                    t = t.parentNode
                }
                return !1
            }

            function m(t, e) {
                return t.indexOf(e) > -1
            }

            function g(t) {
                return t.trim().split(/\s+/g)
            }

            function y(t, e, i) {
                if (t.indexOf && !i) return t.indexOf(e);
                for (var n = 0; n < t.length;) {
                    if (i && t[n][i] == e || !i && t[n] === e) return n;
                    n++
                }
                return -1
            }

            function b(t) {
                return Array.prototype.slice.call(t, 0)
            }

            function w(t, e, i) {
                for (var n = [], o = [], a = 0; a < t.length;) {
                    var r = e ? t[a][e] : t[a];
                    y(o, r) < 0 && n.push(t[a]), o[a] = r, a++
                }
                return i && (n = e ? n.sort(function (t, i) {
                    return t[e] > i[e]
                }) : n.sort()), n
            }

            function k(t, e) {
                for (var i, o, a = e[0].toUpperCase() + e.slice(1), r = 0; r < lt.length;) {
                    if (i = lt[r], (o = i ? i + a : e) in t) return o;
                    r++
                }
                return n
            }

            function x() {
                return ft++
            }

            function C(t) {
                var e = t.ownerDocument;
                return e.defaultView || e.parentWindow
            }

            function T(t, e) {
                var i = this;
                this.manager = t, this.callback = e, this.element = t.element, this.target = t.options.inputTarget, this.domHandler = function (e) {
                    d(t.options.enable, [t]) && i.handler(e)
                }, this.init()
            }

            function S(t) {
                var e = t.options.inputClass;
                return new (e || (gt ? j : yt ? W : mt ? Q : L))(t, P)
            }

            function P(t, e, i) {
                var n = i.pointers.length, o = i.changedPointers.length, a = e & xt && 0 == n - o,
                    r = e & (Tt | St) && 0 == n - o;
                i.isFirst = !!a, i.isFinal = !!r, a && (t.session = {}), i.eventType = e, A(t, i), t.emit("hammer.input", i), t.recognize(i), t.session.prevInput = i
            }

            function A(t, e) {
                var i = t.session, n = e.pointers, o = n.length;
                i.firstInput || (i.firstInput = E(e)), o > 1 && !i.firstMultiple ? i.firstMultiple = E(e) : 1 === o && (i.firstMultiple = !1);
                var a = i.firstInput, r = i.firstMultiple, s = r ? r.center : a.center, l = e.center = M(n);
                e.timeStamp = ht(), e.deltaTime = e.timeStamp - a.timeStamp, e.angle = q(s, l), e.distance = V(s, l), O(i, e), e.offsetDirection = D(e.deltaX, e.deltaY), e.scale = r ? H(r.pointers, n) : 1, e.rotation = r ? z(r.pointers, n) : 0, _(i, e);
                var c = t.element;
                v(e.srcEvent.target, c) && (c = e.srcEvent.target), e.target = c
            }

            function O(t, e) {
                var i = e.center, n = t.offsetDelta || {}, o = t.prevDelta || {}, a = t.prevInput || {};
                (e.eventType === xt || a.eventType === Tt) && (o = t.prevDelta = {
                    x: a.deltaX || 0,
                    y: a.deltaY || 0
                }, n = t.offsetDelta = {x: i.x, y: i.y}), e.deltaX = o.x + (i.x - n.x), e.deltaY = o.y + (i.y - n.y)
            }

            function _(t, e) {
                var i, o, a, r, s = t.lastInterval || e, l = e.timeStamp - s.timeStamp;
                if (e.eventType != St && (l > kt || s.velocity === n)) {
                    var c = s.deltaX - e.deltaX, u = s.deltaY - e.deltaY, d = I(l, c, u);
                    o = d.x, a = d.y, i = pt(d.x) > pt(d.y) ? d.x : d.y, r = D(c, u), t.lastInterval = e
                } else i = s.velocity, o = s.velocityX, a = s.velocityY, r = s.direction;
                e.velocity = i, e.velocityX = o, e.velocityY = a, e.direction = r
            }

            function E(t) {
                for (var e = [], i = 0; i < t.pointers.length;) e[i] = {
                    clientX: dt(t.pointers[i].clientX),
                    clientY: dt(t.pointers[i].clientY)
                }, i++;
                return {timeStamp: ht(), pointers: e, center: M(e), deltaX: t.deltaX, deltaY: t.deltaY}
            }

            function M(t) {
                var e = t.length;
                if (1 === e) return {x: dt(t[0].clientX), y: dt(t[0].clientY)};
                for (var i = 0, n = 0, o = 0; e > o;) i += t[o].clientX, n += t[o].clientY, o++;
                return {x: dt(i / e), y: dt(n / e)}
            }

            function I(t, e, i) {
                return {x: e / t || 0, y: i / t || 0}
            }

            function D(t, e) {
                return t === e ? Pt : pt(t) >= pt(e) ? t > 0 ? At : Ot : e > 0 ? _t : Et
            }

            function V(t, e, i) {
                i || (i = Vt);
                var n = e[i[0]] - t[i[0]], o = e[i[1]] - t[i[1]];
                return Math.sqrt(n * n + o * o)
            }

            function q(t, e, i) {
                i || (i = Vt);
                var n = e[i[0]] - t[i[0]], o = e[i[1]] - t[i[1]];
                return 180 * Math.atan2(o, n) / Math.PI
            }

            function z(t, e) {
                return q(e[1], e[0], qt) - q(t[1], t[0], qt)
            }

            function H(t, e) {
                return V(e[0], e[1], qt) / V(t[0], t[1], qt)
            }

            function L() {
                this.evEl = Ht, this.evWin = Lt, this.allow = !0, this.pressed = !1, T.apply(this, arguments)
            }

            function j() {
                this.evEl = Nt, this.evWin = Wt, T.apply(this, arguments), this.store = this.manager.session.pointerEvents = []
            }

            function $() {
                this.evTarget = Qt, this.evWin = Xt, this.started = !1, T.apply(this, arguments)
            }

            function N(t, e) {
                var i = b(t.touches), n = b(t.changedTouches);
                return e & (Tt | St) && (i = w(i.concat(n), "identifier", !0)), [i, n]
            }

            function W() {
                this.evTarget = Yt, this.targetIds = {}, T.apply(this, arguments)
            }

            function F(t, e) {
                var i = b(t.touches), n = this.targetIds;
                if (e & (xt | Ct) && 1 === i.length) return n[i[0].identifier] = !0, [i, i];
                var o, a, r = b(t.changedTouches), s = [], l = this.target;
                if (a = i.filter(function (t) {
                        return v(t.target, l)
                    }), e === xt) for (o = 0; o < a.length;) n[a[o].identifier] = !0, o++;
                for (o = 0; o < r.length;) n[r[o].identifier] && s.push(r[o]), e & (Tt | St) && delete n[r[o].identifier], o++;
                return s.length ? [w(a.concat(s), "identifier", !0), s] : void 0
            }

            function Q() {
                T.apply(this, arguments);
                var t = u(this.handler, this);
                this.touch = new W(this.manager, t), this.mouse = new L(this.manager, t)
            }

            function X(t, e) {
                this.manager = t, this.set(e)
            }

            function R(t) {
                if (m(t, Kt)) return Kt;
                var e = m(t, te), i = m(t, ee);
                return e && i ? te + " " + ee : e || i ? e ? te : ee : m(t, Jt) ? Jt : Zt
            }

            function Y(t) {
                this.id = x(), this.manager = null, this.options = l(t || {}, this.defaults), this.options.enable = p(this.options.enable, !0), this.state = ie, this.simultaneous = {}, this.requireFail = []
            }

            function B(t) {
                return t & se ? "cancel" : t & ae ? "end" : t & oe ? "move" : t & ne ? "start" : ""
            }

            function U(t) {
                return t == Et ? "down" : t == _t ? "up" : t == At ? "left" : t == Ot ? "right" : ""
            }

            function G(t, e) {
                var i = e.manager;
                return i ? i.get(t) : t
            }

            function Z() {
                Y.apply(this, arguments)
            }

            function J() {
                Z.apply(this, arguments), this.pX = null, this.pY = null
            }

            function K() {
                Z.apply(this, arguments)
            }

            function tt() {
                Y.apply(this, arguments), this._timer = null, this._input = null
            }

            function et() {
                Z.apply(this, arguments)
            }

            function it() {
                Z.apply(this, arguments)
            }

            function nt() {
                Y.apply(this, arguments), this.pTime = !1, this.pCenter = !1, this._timer = null, this._input = null, this.count = 0
            }

            function ot(t, e) {
                return e = e || {}, e.recognizers = p(e.recognizers, ot.defaults.preset), new at(t, e)
            }

            function at(t, e) {
                e = e || {}, this.options = l(e, ot.defaults), this.options.inputTarget = this.options.inputTarget || t, this.handlers = {}, this.session = {}, this.recognizers = [], this.element = t, this.input = S(this), this.touchAction = new X(this, this.options.touchAction), rt(this, !0), r(e.recognizers, function (t) {
                    var e = this.add(new t[0](t[1]));
                    t[2] && e.recognizeWith(t[2]), t[3] && e.requireFailure(t[3])
                }, this)
            }

            function rt(t, e) {
                var i = t.element;
                r(t.options.cssProps, function (t, n) {
                    i.style[k(i.style, n)] = e ? t : ""
                })
            }

            function st(t, i) {
                var n = e.createEvent("Event");
                n.initEvent(t, !0, !0), n.gesture = i, i.target.dispatchEvent(n)
            }

            var lt = ["", "webkit", "moz", "MS", "ms", "o"], ct = e.createElement("div"), ut = "function",
                dt = Math.round, pt = Math.abs, ht = Date.now, ft = 1, vt = /mobile|tablet|ip(ad|hone|od)|android/i,
                mt = "ontouchstart" in t, gt = k(t, "PointerEvent") !== n, yt = mt && vt.test(navigator.userAgent),
                bt = "touch", wt = "mouse", kt = 25, xt = 1, Ct = 2, Tt = 4, St = 8, Pt = 1, At = 2, Ot = 4, _t = 8,
                Et = 16, Mt = At | Ot, It = _t | Et, Dt = Mt | It, Vt = ["x", "y"], qt = ["clientX", "clientY"];
            T.prototype = {
                handler: function () {
                }, init: function () {
                    this.evEl && h(this.element, this.evEl, this.domHandler), this.evTarget && h(this.target, this.evTarget, this.domHandler), this.evWin && h(C(this.element), this.evWin, this.domHandler)
                }, destroy: function () {
                    this.evEl && f(this.element, this.evEl, this.domHandler), this.evTarget && f(this.target, this.evTarget, this.domHandler), this.evWin && f(C(this.element), this.evWin, this.domHandler)
                }
            };
            var zt = {mousedown: xt, mousemove: Ct, mouseup: Tt}, Ht = "mousedown", Lt = "mousemove mouseup";
            c(L, T, {
                handler: function (t) {
                    var e = zt[t.type];
                    e & xt && 0 === t.button && (this.pressed = !0), e & Ct && 1 !== t.which && (e = Tt), this.pressed && this.allow && (e & Tt && (this.pressed = !1), this.callback(this.manager, e, {
                        pointers: [t],
                        changedPointers: [t],
                        pointerType: wt,
                        srcEvent: t
                    }))
                }
            });
            var jt = {pointerdown: xt, pointermove: Ct, pointerup: Tt, pointercancel: St, pointerout: St},
                $t = {2: bt, 3: "pen", 4: wt, 5: "kinect"}, Nt = "pointerdown",
                Wt = "pointermove pointerup pointercancel";
            t.MSPointerEvent && (Nt = "MSPointerDown", Wt = "MSPointerMove MSPointerUp MSPointerCancel"), c(j, T, {
                handler: function (t) {
                    var e = this.store, i = !1, n = t.type.toLowerCase().replace("ms", ""), o = jt[n],
                        a = $t[t.pointerType] || t.pointerType, r = a == bt, s = y(e, t.pointerId, "pointerId");
                    o & xt && (0 === t.button || r) ? 0 > s && (e.push(t), s = e.length - 1) : o & (Tt | St) && (i = !0), 0 > s || (e[s] = t, this.callback(this.manager, o, {
                        pointers: e,
                        changedPointers: [t],
                        pointerType: a,
                        srcEvent: t
                    }), i && e.splice(s, 1))
                }
            });
            var Ft = {touchstart: xt, touchmove: Ct, touchend: Tt, touchcancel: St}, Qt = "touchstart",
                Xt = "touchstart touchmove touchend touchcancel";
            c($, T, {
                handler: function (t) {
                    var e = Ft[t.type];
                    if (e === xt && (this.started = !0), this.started) {
                        var i = N.call(this, t, e);
                        e & (Tt | St) && 0 == i[0].length - i[1].length && (this.started = !1), this.callback(this.manager, e, {
                            pointers: i[0],
                            changedPointers: i[1],
                            pointerType: bt,
                            srcEvent: t
                        })
                    }
                }
            });
            var Rt = {touchstart: xt, touchmove: Ct, touchend: Tt, touchcancel: St},
                Yt = "touchstart touchmove touchend touchcancel";
            c(W, T, {
                handler: function (t) {
                    var e = Rt[t.type], i = F.call(this, t, e);
                    i && this.callback(this.manager, e, {
                        pointers: i[0],
                        changedPointers: i[1],
                        pointerType: bt,
                        srcEvent: t
                    })
                }
            }), c(Q, T, {
                handler: function (t, e, i) {
                    var n = i.pointerType == bt, o = i.pointerType == wt;
                    if (n) this.mouse.allow = !1; else if (o && !this.mouse.allow) return;
                    e & (Tt | St) && (this.mouse.allow = !0), this.callback(t, e, i)
                }, destroy: function () {
                    this.touch.destroy(), this.mouse.destroy()
                }
            });
            var Bt = k(ct.style, "touchAction"), Ut = Bt !== n, Gt = "compute", Zt = "auto", Jt = "manipulation",
                Kt = "none", te = "pan-x", ee = "pan-y";
            X.prototype = {
                set: function (t) {
                    t == Gt && (t = this.compute()), Ut && (this.manager.element.style[Bt] = t), this.actions = t.toLowerCase().trim()
                }, update: function () {
                    this.set(this.manager.options.touchAction)
                }, compute: function () {
                    var t = [];
                    return r(this.manager.recognizers, function (e) {
                        d(e.options.enable, [e]) && (t = t.concat(e.getTouchAction()))
                    }), R(t.join(" "))
                }, preventDefaults: function (t) {
                    if (!Ut) {
                        var e = t.srcEvent, i = t.offsetDirection;
                        if (this.manager.session.prevented) return void e.preventDefault();
                        var n = this.actions, o = m(n, Kt), a = m(n, ee), r = m(n, te);
                        return o || a && i & Mt || r && i & It ? this.preventSrc(e) : void 0
                    }
                }, preventSrc: function (t) {
                    this.manager.session.prevented = !0, t.preventDefault()
                }
            };
            var ie = 1, ne = 2, oe = 4, ae = 8, re = ae, se = 16;
            Y.prototype = {
                defaults: {}, set: function (t) {
                    return s(this.options, t), this.manager && this.manager.touchAction.update(), this
                }, recognizeWith: function (t) {
                    if (a(t, "recognizeWith", this)) return this;
                    var e = this.simultaneous;
                    return t = G(t, this), e[t.id] || (e[t.id] = t, t.recognizeWith(this)), this
                }, dropRecognizeWith: function (t) {
                    return a(t, "dropRecognizeWith", this) ? this : (t = G(t, this), delete this.simultaneous[t.id], this)
                }, requireFailure: function (t) {
                    if (a(t, "requireFailure", this)) return this;
                    var e = this.requireFail;
                    return t = G(t, this), -1 === y(e, t) && (e.push(t), t.requireFailure(this)), this
                }, dropRequireFailure: function (t) {
                    if (a(t, "dropRequireFailure", this)) return this;
                    t = G(t, this);
                    var e = y(this.requireFail, t);
                    return e > -1 && this.requireFail.splice(e, 1), this
                }, hasRequireFailures: function () {
                    return this.requireFail.length > 0
                }, canRecognizeWith: function (t) {
                    return !!this.simultaneous[t.id]
                }, emit: function (t) {
                    function e(e) {
                        i.manager.emit(i.options.event + (e ? B(n) : ""), t)
                    }

                    var i = this, n = this.state;
                    ae > n && e(!0), e(), n >= ae && e(!0)
                }, tryEmit: function (t) {
                    return this.canEmit() ? this.emit(t) : void(this.state = 32)
                }, canEmit: function () {
                    for (var t = 0; t < this.requireFail.length;) {
                        if (!(this.requireFail[t].state & (32 | ie))) return !1;
                        t++
                    }
                    return !0
                }, recognize: function (t) {
                    var e = s({}, t);
                    return d(this.options.enable, [this, e]) ? (this.state & (re | se | 32) && (this.state = ie), this.state = this.process(e), void(this.state & (ne | oe | ae | se) && this.tryEmit(e))) : (this.reset(), void(this.state = 32))
                }, process: function () {
                }, getTouchAction: function () {
                }, reset: function () {
                }
            }, c(Z, Y, {
                defaults: {pointers: 1}, attrTest: function (t) {
                    var e = this.options.pointers;
                    return 0 === e || t.pointers.length === e
                }, process: function (t) {
                    var e = this.state, i = t.eventType, n = e & (ne | oe), o = this.attrTest(t);
                    return n && (i & St || !o) ? e | se : n || o ? i & Tt ? e | ae : e & ne ? e | oe : ne : 32
                }
            }), c(J, Z, {
                defaults: {event: "pan", threshold: 10, pointers: 1, direction: Dt},
                getTouchAction: function () {
                    var t = this.options.direction, e = [];
                    return t & Mt && e.push(ee), t & It && e.push(te), e
                },
                directionTest: function (t) {
                    var e = this.options, i = !0, n = t.distance, o = t.direction, a = t.deltaX, r = t.deltaY;
                    return o & e.direction || (e.direction & Mt ? (o = 0 === a ? Pt : 0 > a ? At : Ot, i = a != this.pX, n = Math.abs(t.deltaX)) : (o = 0 === r ? Pt : 0 > r ? _t : Et, i = r != this.pY, n = Math.abs(t.deltaY))), t.direction = o, i && n > e.threshold && o & e.direction
                },
                attrTest: function (t) {
                    return Z.prototype.attrTest.call(this, t) && (this.state & ne || !(this.state & ne) && this.directionTest(t))
                },
                emit: function (t) {
                    this.pX = t.deltaX, this.pY = t.deltaY;
                    var e = U(t.direction);
                    e && this.manager.emit(this.options.event + e, t), this._super.emit.call(this, t)
                }
            }), c(K, Z, {
                defaults: {event: "pinch", threshold: 0, pointers: 2}, getTouchAction: function () {
                    return [Kt]
                }, attrTest: function (t) {
                    return this._super.attrTest.call(this, t) && (Math.abs(t.scale - 1) > this.options.threshold || this.state & ne)
                }, emit: function (t) {
                    if (this._super.emit.call(this, t), 1 !== t.scale) {
                        var e = t.scale < 1 ? "in" : "out";
                        this.manager.emit(this.options.event + e, t)
                    }
                }
            }), c(tt, Y, {
                defaults: {event: "press", pointers: 1, time: 500, threshold: 5},
                getTouchAction: function () {
                    return [Zt]
                },
                process: function (t) {
                    var e = this.options, i = t.pointers.length === e.pointers, n = t.distance < e.threshold,
                        a = t.deltaTime > e.time;
                    if (this._input = t, !n || !i || t.eventType & (Tt | St) && !a) this.reset(); else if (t.eventType & xt) this.reset(), this._timer = o(function () {
                        this.state = re, this.tryEmit()
                    }, e.time, this); else if (t.eventType & Tt) return re;
                    return 32
                },
                reset: function () {
                    clearTimeout(this._timer)
                },
                emit: function (t) {
                    this.state === re && (t && t.eventType & Tt ? this.manager.emit(this.options.event + "up", t) : (this._input.timeStamp = ht(), this.manager.emit(this.options.event, this._input)))
                }
            }), c(et, Z, {
                defaults: {event: "rotate", threshold: 0, pointers: 2}, getTouchAction: function () {
                    return [Kt]
                }, attrTest: function (t) {
                    return this._super.attrTest.call(this, t) && (Math.abs(t.rotation) > this.options.threshold || this.state & ne)
                }
            }), c(it, Z, {
                defaults: {event: "swipe", threshold: 10, velocity: .65, direction: Mt | It, pointers: 1},
                getTouchAction: function () {
                    return J.prototype.getTouchAction.call(this)
                },
                attrTest: function (t) {
                    var e, i = this.options.direction;
                    return i & (Mt | It) ? e = t.velocity : i & Mt ? e = t.velocityX : i & It && (e = t.velocityY), this._super.attrTest.call(this, t) && i & t.direction && t.distance > this.options.threshold && pt(e) > this.options.velocity && t.eventType & Tt
                },
                emit: function (t) {
                    var e = U(t.direction);
                    e && this.manager.emit(this.options.event + e, t), this.manager.emit(this.options.event, t)
                }
            }), c(nt, Y, {
                defaults: {
                    event: "tap",
                    pointers: 1,
                    taps: 1,
                    interval: 300,
                    time: 250,
                    threshold: 2,
                    posThreshold: 10
                }, getTouchAction: function () {
                    return [Jt]
                }, process: function (t) {
                    var e = this.options, i = t.pointers.length === e.pointers, n = t.distance < e.threshold,
                        a = t.deltaTime < e.time;
                    if (this.reset(), t.eventType & xt && 0 === this.count) return this.failTimeout();
                    if (n && a && i) {
                        if (t.eventType != Tt) return this.failTimeout();
                        var r = !this.pTime || t.timeStamp - this.pTime < e.interval,
                            s = !this.pCenter || V(this.pCenter, t.center) < e.posThreshold;
                        if (this.pTime = t.timeStamp, this.pCenter = t.center, s && r ? this.count += 1 : this.count = 1, this._input = t, 0 === this.count % e.taps) return this.hasRequireFailures() ? (this._timer = o(function () {
                            this.state = re, this.tryEmit()
                        }, e.interval, this), ne) : re
                    }
                    return 32
                }, failTimeout: function () {
                    return this._timer = o(function () {
                        this.state = 32
                    }, this.options.interval, this), 32
                }, reset: function () {
                    clearTimeout(this._timer)
                }, emit: function () {
                    this.state == re && (this._input.tapCount = this.count, this.manager.emit(this.options.event, this._input))
                }
            }), ot.VERSION = "2.0.4", ot.defaults = {
                domEvents: !1,
                touchAction: Gt,
                enable: !0,
                inputTarget: null,
                inputClass: null,
                preset: [[et, {enable: !1}], [K, {enable: !1}, ["rotate"]], [it, {direction: Mt}], [J, {direction: Mt}, ["swipe"]], [nt], [nt, {
                    event: "doubletap",
                    taps: 2
                }, ["tap"]], [tt]],
                cssProps: {
                    userSelect: "default",
                    touchSelect: "none",
                    touchCallout: "none",
                    contentZooming: "none",
                    userDrag: "none",
                    tapHighlightColor: "rgba(0,0,0,0)"
                }
            };
            at.prototype = {
                set: function (t) {
                    return s(this.options, t), t.touchAction && this.touchAction.update(), t.inputTarget && (this.input.destroy(), this.input.target = t.inputTarget, this.input.init()), this
                }, stop: function (t) {
                    this.session.stopped = t ? 2 : 1
                }, recognize: function (t) {
                    var e = this.session;
                    if (!e.stopped) {
                        this.touchAction.preventDefaults(t);
                        var i, n = this.recognizers, o = e.curRecognizer;
                        (!o || o && o.state & re) && (o = e.curRecognizer = null);
                        for (var a = 0; a < n.length;) i = n[a], 2 === e.stopped || o && i != o && !i.canRecognizeWith(o) ? i.reset() : i.recognize(t), !o && i.state & (ne | oe | ae) && (o = e.curRecognizer = i), a++
                    }
                }, get: function (t) {
                    if (t instanceof Y) return t;
                    for (var e = this.recognizers, i = 0; i < e.length; i++) if (e[i].options.event == t) return e[i];
                    return null
                }, add: function (t) {
                    if (a(t, "add", this)) return this;
                    var e = this.get(t.options.event);
                    return e && this.remove(e), this.recognizers.push(t), t.manager = this, this.touchAction.update(), t
                }, remove: function (t) {
                    if (a(t, "remove", this)) return this;
                    var e = this.recognizers;
                    return t = this.get(t), e.splice(y(e, t), 1), this.touchAction.update(), this
                }, on: function (t, e) {
                    var i = this.handlers;
                    return r(g(t), function (t) {
                        i[t] = i[t] || [], i[t].push(e)
                    }), this
                }, off: function (t, e) {
                    var i = this.handlers;
                    return r(g(t), function (t) {
                        e ? i[t].splice(y(i[t], e), 1) : delete i[t]
                    }), this
                }, emit: function (t, e) {
                    this.options.domEvents && st(t, e);
                    var i = this.handlers[t] && this.handlers[t].slice();
                    if (i && i.length) {
                        e.type = t, e.preventDefault = function () {
                            e.srcEvent.preventDefault()
                        };
                        for (var n = 0; n < i.length;) i[n](e), n++
                    }
                }, destroy: function () {
                    this.element && rt(this, !1), this.handlers = {}, this.session = {}, this.input.destroy(), this.element = null
                }
            }, s(ot, {
                INPUT_START: xt,
                INPUT_MOVE: Ct,
                INPUT_END: Tt,
                INPUT_CANCEL: St,
                STATE_POSSIBLE: ie,
                STATE_BEGAN: ne,
                STATE_CHANGED: oe,
                STATE_ENDED: ae,
                STATE_RECOGNIZED: re,
                STATE_CANCELLED: se,
                STATE_FAILED: 32,
                DIRECTION_NONE: Pt,
                DIRECTION_LEFT: At,
                DIRECTION_RIGHT: Ot,
                DIRECTION_UP: _t,
                DIRECTION_DOWN: Et,
                DIRECTION_HORIZONTAL: Mt,
                DIRECTION_VERTICAL: It,
                DIRECTION_ALL: Dt,
                Manager: at,
                Input: T,
                TouchAction: X,
                TouchInput: W,
                MouseInput: L,
                PointerEventInput: j,
                TouchMouseInput: Q,
                SingleTouchInput: $,
                Recognizer: Y,
                AttrRecognizer: Z,
                Tap: nt,
                Pan: J,
                Swipe: it,
                Pinch: K,
                Rotate: et,
                Press: tt,
                on: h,
                off: f,
                each: r,
                merge: l,
                extend: s,
                inherit: c,
                bindFn: u,
                prefixed: k
            }), typeof define == ut && define.amd ? define(function () {
                return ot
            }) : "undefined" != typeof module && module.exports ? module.exports = ot : t.Hammer = ot
        }(window, document), function (t) {
            "function" == typeof define && define.amd ? define(["jquery", "hammerjs"], t) : "object" == typeof exports ? t(require("jquery"), require("hammerjs")) : t(jQuery, Hammer)
        }(function (t, e) {
            function i(i, n) {
                var o = t(i);
                o.data("hammer") || o.data("hammer", new e(o[0], n))
            }

            t.fn.hammer = function (t) {
                return this.each(function () {
                    i(this, t)
                })
            }, e.Manager.prototype.emit = function (e) {
                return function (i, n) {
                    e.call(this, i, n), t(this.element).trigger({type: i, gesture: n})
                }
            }(e.Manager.prototype.emit)
        }), function (t) {
            t.Package ? Materialize = {} : t.Materialize = {}
        }(window), function (t) {
            for (var e = 0, i = ["webkit", "moz"], n = t.requestAnimationFrame, o = t.cancelAnimationFrame, a = i.length; --a >= 0 && !n;) n = t[i[a] + "RequestAnimationFrame"], o = t[i[a] + "CancelRequestAnimationFrame"];
            n && o || (n = function (t) {
                var i = +Date.now(), n = Math.max(e + 16, i);
                return setTimeout(function () {
                    t(e = n)
                }, n - i)
            }, o = clearTimeout), t.requestAnimationFrame = n, t.cancelAnimationFrame = o
        }(window), Materialize.objectSelectorString = function (t) {
            return ((t.prop("tagName") || "") + (t.attr("id") || "") + (t.attr("class") || "")).replace(/\s/g, "")
        }, Materialize.guid = function () {
            function t() {
                return Math.floor(65536 * (1 + Math.random())).toString(16).substring(1)
            }

            return function () {
                return t() + t() + "-" + t() + "-" + t() + "-" + t() + "-" + t() + t() + t()
            }
        }(), Materialize.escapeHash = function (t) {
            return t.replace(/(:|\.|\[|\]|,|=)/g, "\\$1")
        }, Materialize.elementOrParentIsFixed = function (t) {
            var e = $(t), i = !1;
            return e.add(e.parents()).each(function () {
                if ("fixed" === $(this).css("position")) return i = !0, !1
            }), i
        };
        var getTime = Date.now || function () {
            return (new Date).getTime()
        };
        Materialize.throttle = function (t, e, i) {
            var n, o, a, r = null, s = 0;
            i || (i = {});
            var l = function () {
                s = !1 === i.leading ? 0 : getTime(), r = null, a = t.apply(n, o), n = o = null
            };
            return function () {
                var c = getTime();
                s || !1 !== i.leading || (s = c);
                var u = e - (c - s);
                return n = this, o = arguments, u <= 0 ? (clearTimeout(r), r = null, s = c, a = t.apply(n, o), n = o = null) : r || !1 === i.trailing || (r = setTimeout(l, u)), a
            }
        };
        var Vel;
        Vel = jQuery ? jQuery.Velocity : $ ? $.Velocity : Velocity, function (t) {
            t.fn.collapsible = function (e, i) {
                var n = {accordion: void 0, onOpen: void 0, onClose: void 0}, o = e;
                return e = t.extend(n, e), this.each(function () {
                    function n(e) {
                        p = d.find("> li > .collapsible-header"), e.hasClass("active") ? e.parent().addClass("active") : e.parent().removeClass("active"), e.parent().hasClass("active") ? e.siblings(".collapsible-body").stop(!0, !1).slideDown({
                            duration: 350,
                            easing: "easeOutQuart",
                            queue: !1,
                            complete: function () {
                                t(this).css("height", "")
                            }
                        }) : e.siblings(".collapsible-body").stop(!0, !1).slideUp({
                            duration: 350,
                            easing: "easeOutQuart",
                            queue: !1,
                            complete: function () {
                                t(this).css("height", "")
                            }
                        }), p.not(e).removeClass("active").parent().removeClass("active"), p.not(e).parent().children(".collapsible-body").stop(!0, !1).each(function () {
                            t(this).is(":visible") && t(this).slideUp({
                                duration: 350,
                                easing: "easeOutQuart",
                                queue: !1,
                                complete: function () {
                                    t(this).css("height", ""), s(t(this).siblings(".collapsible-header"))
                                }
                            })
                        })
                    }

                    function a(e) {
                        e.hasClass("active") ? e.parent().addClass("active") : e.parent().removeClass("active"), e.parent().hasClass("active") ? e.siblings(".collapsible-body").stop(!0, !1).slideDown({
                            duration: 350,
                            easing: "easeOutQuart",
                            queue: !1,
                            complete: function () {
                                t(this).css("height", "")
                            }
                        }) : e.siblings(".collapsible-body").stop(!0, !1).slideUp({
                            duration: 350,
                            easing: "easeOutQuart",
                            queue: !1,
                            complete: function () {
                                t(this).css("height", "")
                            }
                        })
                    }

                    function r(t, i) {
                        i || t.toggleClass("active"), e.accordion || "accordion" === h || void 0 === h ? n(t) : a(t), s(t)
                    }

                    function s(t) {
                        t.hasClass("active") ? "function" == typeof e.onOpen && e.onOpen.call(this, t.parent()) : "function" == typeof e.onClose && e.onClose.call(this, t.parent())
                    }

                    function l(t) {
                        return c(t).length > 0
                    }

                    function c(t) {
                        return t.closest("li > .collapsible-header")
                    }

                    function u() {
                        d.off("click.collapse", "> li > .collapsible-header")
                    }

                    var d = t(this), p = t(this).find("> li > .collapsible-header"), h = d.data("collapsible");
                    if ("destroy" !== o) if (i >= 0 && i < p.length) {
                        var f = p.eq(i);
                        f.length && ("open" === o || "close" === o && f.hasClass("active")) && r(f)
                    } else u(), d.on("click.collapse", "> li > .collapsible-header", function (e) {
                        var i = t(e.target);
                        l(i) && (i = c(i)), r(i)
                    }), e.accordion || "accordion" === h || void 0 === h ? r(p.filter(".active").first(), !0) : p.filter(".active").each(function () {
                        r(t(this), !0)
                    }); else u()
                })
            }, t(document).ready(function () {
                t(".collapsible").collapsible()
            })
        }(jQuery), function (t) {
            t.fn.scrollTo = function (e) {
                return t(this).scrollTop(t(this).scrollTop() - t(this).offset().top + t(e).offset().top), this
            }, t.fn.dropdown = function (e) {
                var i = {
                    inDuration: 300,
                    outDuration: 225,
                    constrainWidth: !0,
                    hover: !1,
                    gutter: 0,
                    belowOrigin: !1,
                    alignment: "left",
                    stopPropagation: !1
                };
                return "open" === e ? (this.each(function () {
                    t(this).trigger("open")
                }), !1) : "close" === e ? (this.each(function () {
                    t(this).trigger("close")
                }), !1) : void this.each(function () {
                    function n() {
                        void 0 !== r.data("induration") && (s.inDuration = r.data("induration")), void 0 !== r.data("outduration") && (s.outDuration = r.data("outduration")), void 0 !== r.data("constrainwidth") && (s.constrainWidth = r.data("constrainwidth")), void 0 !== r.data("hover") && (s.hover = r.data("hover")), void 0 !== r.data("gutter") && (s.gutter = r.data("gutter")), void 0 !== r.data("beloworigin") && (s.belowOrigin = r.data("beloworigin")), void 0 !== r.data("alignment") && (s.alignment = r.data("alignment")), void 0 !== r.data("stoppropagation") && (s.stopPropagation = r.data("stoppropagation"))
                    }

                    function o(e) {
                        "focus" === e && (l = !0), n(), c.addClass("active"), r.addClass("active");
                        var i = r[0].getBoundingClientRect().width;
                        !0 === s.constrainWidth ? c.css("width", i) : c.css("white-space", "nowrap");
                        var o = window.innerHeight, u = r.innerHeight(), d = r.offset().left,
                            p = r.offset().top - t(window).scrollTop(), h = s.alignment, f = 0, v = 0, m = 0;
                        !0 === s.belowOrigin && (m = u);
                        var g = 0, y = 0, b = r.parent();
                        if (b.is("body") || (b[0].scrollHeight > b[0].clientHeight && (g = b[0].scrollTop), b[0].scrollWidth > b[0].clientWidth && (y = b[0].scrollLeft)), d + c.innerWidth() > t(window).width() ? h = "right" : d - c.innerWidth() + r.innerWidth() < 0 && (h = "left"), p + c.innerHeight() > o) if (p + u - c.innerHeight() < 0) {
                            var w = o - p - m;
                            c.css("max-height", w)
                        } else m || (m += u), m -= c.innerHeight();
                        "left" === h ? (f = s.gutter, v = r.position().left + f) : "right" === h && (c.stop(!0, !0).css({
                            opacity: 0,
                            left: 0
                        }), v = r.position().left + i - c.width() + (f = -s.gutter)), c.css({
                            position: "absolute",
                            top: r.position().top + m + g,
                            left: v + y
                        }), c.slideDown({
                            queue: !1,
                            duration: s.inDuration,
                            easing: "easeOutCubic",
                            complete: function () {
                                t(this).css("height", "")
                            }
                        }).animate({opacity: 1}, {
                            queue: !1,
                            duration: s.inDuration,
                            easing: "easeOutSine"
                        }), setTimeout(function () {
                            t(document).on("click." + c.attr("id"), function (e) {
                                a(), t(document).off("click." + c.attr("id"))
                            })
                        }, 0)
                    }

                    function a() {
                        l = !1, c.fadeOut(s.outDuration), c.removeClass("active"), r.removeClass("active"), t(document).off("click." + c.attr("id")), setTimeout(function () {
                            c.css("max-height", "")
                        }, s.outDuration)
                    }

                    var r = t(this), s = t.extend({}, i, e), l = !1, c = t("#" + r.attr("data-activates"));
                    if (n(), r.after(c), s.hover) {
                        var u = !1;
                        r.off("click." + r.attr("id")), r.on("mouseenter", function (t) {
                            !1 === u && (o(), u = !0)
                        }), r.on("mouseleave", function (e) {
                            var i = e.toElement || e.relatedTarget;
                            t(i).closest(".dropdown-content").is(c) || (c.stop(!0, !0), a(), u = !1)
                        }), c.on("mouseleave", function (e) {
                            var i = e.toElement || e.relatedTarget;
                            t(i).closest(".dropdown-button").is(r) || (c.stop(!0, !0), a(), u = !1)
                        })
                    } else r.off("click." + r.attr("id")), r.on("click." + r.attr("id"), function (e) {
                        l || (r[0] != e.currentTarget || r.hasClass("active") || 0 !== t(e.target).closest(".dropdown-content").length ? r.hasClass("active") && (a(), t(document).off("click." + c.attr("id"))) : (e.preventDefault(), s.stopPropagation && e.stopPropagation(), o("click")))
                    });
                    r.on("open", function (t, e) {
                        o(e)
                    }), r.on("close", a)
                })
            }, t(document).ready(function () {
                t(".dropdown-button").dropdown()
            })
        }(jQuery), function (t) {
            "use strict";
            var e = {
                opacity: .5,
                inDuration: 250,
                outDuration: 250,
                ready: void 0,
                complete: void 0,
                dismissible: !0,
                startingTop: "4%",
                endingTop: "10%"
            }, i = function () {
                function i(e, n) {
                    _classCallCheck(this, i), e[0].M_Modal && e[0].M_Modal.destroy(), this.$el = e, this.options = t.extend({}, i.defaults, n), this.isOpen = !1, this.$el[0].M_Modal = this, this.id = e.attr("id"), this.openingTrigger = void 0, this.$overlay = t('<div class="modal-overlay"></div>'), i._increment++, i._count++, this.$overlay[0].style.zIndex = 1e3 + 2 * i._increment, this.$el[0].style.zIndex = 1e3 + 2 * i._increment + 1, this.setupEventHandlers()
                }

                return _createClass(i, [{
                    key: "getInstance", value: function () {
                        return this
                    }
                }, {
                    key: "destroy", value: function () {
                        this.removeEventHandlers(), this.$el[0].removeAttribute("style"), this.$overlay[0].parentNode && this.$overlay[0].parentNode.removeChild(this.$overlay[0]), this.$el[0].M_Modal = void 0, i._count--
                    }
                }, {
                    key: "setupEventHandlers", value: function () {
                        this.handleOverlayClickBound = this.handleOverlayClick.bind(this), this.handleModalCloseClickBound = this.handleModalCloseClick.bind(this), 1 === i._count && document.addEventListener("click", this.handleTriggerClick), this.$overlay[0].addEventListener("click", this.handleOverlayClickBound), this.$el[0].addEventListener("click", this.handleModalCloseClickBound)
                    }
                }, {
                    key: "removeEventHandlers", value: function () {
                        0 === i._count && document.removeEventListener("click", this.handleTriggerClick), this.$overlay[0].removeEventListener("click", this.handleOverlayClickBound), this.$el[0].removeEventListener("click", this.handleModalCloseClickBound)
                    }
                }, {
                    key: "handleTriggerClick", value: function (e) {
                        var i = t(e.target).closest(".modal-trigger");
                        if (e.target && i.length) {
                            var n = i[0].getAttribute("href");
                            n = n ? n.slice(1) : i[0].getAttribute("data-target");
                            var o = document.getElementById(n).M_Modal;
                            o && o.open(i), e.preventDefault()
                        }
                    }
                }, {
                    key: "handleOverlayClick", value: function () {
                        this.options.dismissible && this.close()
                    }
                }, {
                    key: "handleModalCloseClick", value: function (e) {
                        var i = t(e.target).closest(".modal-close");
                        e.target && i.length && this.close()
                    }
                }, {
                    key: "handleKeydown", value: function (t) {
                        27 === t.keyCode && this.options.dismissible && this.close()
                    }
                }, {
                    key: "animateIn", value: function () {
                        var e = this;
                        t.extend(this.$el[0].style, {
                            display: "block",
                            opacity: 0
                        }), t.extend(this.$overlay[0].style, {
                            display: "block",
                            opacity: 0
                        }), Vel(this.$overlay[0], {opacity: this.options.opacity}, {
                            duration: this.options.inDuration,
                            queue: !1,
                            ease: "easeOutCubic"
                        });
                        var i = {
                            duration: this.options.inDuration, queue: !1, ease: "easeOutCubic", complete: function () {
                                "function" == typeof e.options.ready && e.options.ready.call(e, e.$el, e.openingTrigger)
                            }
                        };
                        this.$el[0].classList.contains("bottom-sheet") ? Vel(this.$el[0], {
                            bottom: 0,
                            opacity: 1
                        }, i) : (Vel.hook(this.$el[0], "scaleX", .7), this.$el[0].style.top = this.options.startingTop, Vel(this.$el[0], {
                            top: this.options.endingTop,
                            opacity: 1,
                            scaleX: 1
                        }, i))
                    }
                }, {
                    key: "animateOut", value: function () {
                        var t = this;
                        Vel(this.$overlay[0], {opacity: 0}, {
                            duration: this.options.outDuration,
                            queue: !1,
                            ease: "easeOutQuart"
                        });
                        var e = {
                            duration: this.options.outDuration,
                            queue: !1,
                            ease: "easeOutCubic",
                            complete: function () {
                                t.$el[0].style.display = "none", "function" == typeof t.options.complete && t.options.complete.call(t, t.$el), t.$overlay[0].remove()
                            }
                        };
                        this.$el[0].classList.contains("bottom-sheet") ? Vel(this.$el[0], {
                            bottom: "-100%",
                            opacity: 0
                        }, e) : Vel(this.$el[0], {top: this.options.startingTop, opacity: 0, scaleX: .7}, e)
                    }
                }, {
                    key: "open", value: function (t) {
                        if (!this.isOpen) {
                            this.isOpen = !0;
                            var e = document.body;
                            return e.style.overflow = "hidden", this.$el[0].classList.add("open"), e.appendChild(this.$overlay[0]), this.openingTrigger = t || void 0, this.options.dismissible && (this.handleKeydownBound = this.handleKeydown.bind(this), document.addEventListener("keydown", this.handleKeydownBound)), this.animateIn(), this
                        }
                    }
                }, {
                    key: "close", value: function () {
                        if (this.isOpen) return this.isOpen = !1, this.$el[0].classList.remove("open"), document.body.style.overflow = null, this.options.dismissible && document.removeEventListener("keydown", this.handleKeydownBound), this.animateOut(), this
                    }
                }], [{
                    key: "init", value: function (e, n) {
                        var o = [];
                        return e.each(function () {
                            o.push(new i(t(this), n))
                        }), o
                    }
                }, {
                    key: "defaults", get: function () {
                        return e
                    }
                }]), i
            }();
            i._increment = 0, i._count = 0, window.Materialize.Modal = i, t.fn.modal = function (e) {
                return i.prototype[e] ? "get" === e.slice(0, 3) ? this.first()[0].M_Modal[e]() : this.each(function () {
                    this.M_Modal[e]()
                }) : "object" != typeof e && e ? void t.error("Method " + e + " does not exist on jQuery.modal") : (i.init(this, arguments[0]), this)
            }
        }(jQuery), function (t) {
            t.fn.materialbox = function () {
                return this.each(function () {
                    function e() {
                        a = !1;
                        var e = s.parent(".material-placeholder"),
                            n = (window.innerWidth, window.innerHeight, s.data("width")), l = s.data("height");
                        s.velocity("stop", !0), t("#materialbox-overlay").velocity("stop", !0), t(".materialbox-caption").velocity("stop", !0), t(window).off("scroll.materialbox"), t(document).off("keyup.materialbox"), t(window).off("resize.materialbox"), t("#materialbox-overlay").velocity({opacity: 0}, {
                            duration: r,
                            queue: !1,
                            easing: "easeOutQuad",
                            complete: function () {
                                o = !1, t(this).remove()
                            }
                        }), s.velocity({width: n, height: l, left: 0, top: 0}, {
                            duration: r,
                            queue: !1,
                            easing: "easeOutQuad",
                            complete: function () {
                                e.css({
                                    height: "",
                                    width: "",
                                    position: "",
                                    top: "",
                                    left: ""
                                }), s.removeAttr("style"), s.attr("style", c), s.removeClass("active"), a = !0, i && i.css("overflow", "")
                            }
                        }), t(".materialbox-caption").velocity({opacity: 0}, {
                            duration: r,
                            queue: !1,
                            easing: "easeOutQuad",
                            complete: function () {
                                t(this).remove()
                            }
                        })
                    }

                    if (!t(this).hasClass("initialized")) {
                        t(this).addClass("initialized");
                        var i, n, o = !1, a = !0, r = 200, s = t(this),
                            l = t("<div></div>").addClass("material-placeholder"), c = s.attr("style");
                        s.wrap(l), s.on("click", function () {
                            var r = s.parent(".material-placeholder"), l = window.innerWidth, c = window.innerHeight,
                                u = s.width(), d = s.height();
                            if (!1 === a) return e(), !1;
                            if (o && !0 === a) return e(), !1;
                            a = !1, s.addClass("active"), o = !0, r.css({
                                width: r[0].getBoundingClientRect().width,
                                height: r[0].getBoundingClientRect().height,
                                position: "relative",
                                top: 0,
                                left: 0
                            }), i = void 0, n = r[0].parentNode;
                            for (; null !== n && !t(n).is(document);) {
                                var p = t(n);
                                "visible" !== p.css("overflow") && (p.css("overflow", "visible"), i = void 0 === i ? p : i.add(p)), n = n.parentNode
                            }
                            s.css({
                                position: "absolute",
                                "z-index": 1e3,
                                "will-change": "left, top, width, height"
                            }).data("width", u).data("height", d);
                            var h = t('<div id="materialbox-overlay"></div>').css({opacity: 0}).click(function () {
                                !0 === a && e()
                            });
                            s.before(h);
                            var f = h[0].getBoundingClientRect();
                            if (h.css({
                                    width: l,
                                    height: c,
                                    left: -1 * f.left,
                                    top: -1 * f.top
                                }), h.velocity({opacity: 1}, {
                                    duration: 275,
                                    queue: !1,
                                    easing: "easeOutQuad"
                                }), "" !== s.data("caption")) {
                                var v = t('<div class="materialbox-caption"></div>');
                                v.text(s.data("caption")), t("body").append(v), v.css({display: "inline"}), v.velocity({opacity: 1}, {
                                    duration: 275,
                                    queue: !1,
                                    easing: "easeOutQuad"
                                })
                            }
                            var m = 0, g = 0;
                            u / l > d / c ? (m = .9 * l, g = .9 * l * (d / u)) : (m = .9 * c * (u / d), g = .9 * c), s.hasClass("responsive-img") ? s.velocity({
                                "max-width": m,
                                width: u
                            }, {
                                duration: 0, queue: !1, complete: function () {
                                    s.css({left: 0, top: 0}).velocity({
                                        height: g,
                                        width: m,
                                        left: t(document).scrollLeft() + l / 2 - s.parent(".material-placeholder").offset().left - m / 2,
                                        top: t(document).scrollTop() + c / 2 - s.parent(".material-placeholder").offset().top - g / 2
                                    }, {
                                        duration: 275, queue: !1, easing: "easeOutQuad", complete: function () {
                                            a = !0
                                        }
                                    })
                                }
                            }) : s.css("left", 0).css("top", 0).velocity({
                                height: g,
                                width: m,
                                left: t(document).scrollLeft() + l / 2 - s.parent(".material-placeholder").offset().left - m / 2,
                                top: t(document).scrollTop() + c / 2 - s.parent(".material-placeholder").offset().top - g / 2
                            }, {
                                duration: 275, queue: !1, easing: "easeOutQuad", complete: function () {
                                    a = !0
                                }
                            }), t(window).on("scroll.materialbox", function () {
                                o && e()
                            }), t(window).on("resize.materialbox", function () {
                                o && e()
                            }), t(document).on("keyup.materialbox", function (t) {
                                27 === t.keyCode && !0 === a && o && e()
                            })
                        })
                    }
                })
            }, t(document).ready(function () {
                t(".materialboxed").materialbox()
            })
        }(jQuery), function (t) {
            t.fn.parallax = function () {
                var e = t(window).width();
                return this.each(function (i) {
                    function n(i) {
                        var n;
                        n = e < 601 ? o.height() > 0 ? o.height() : o.children("img").height() : o.height() > 0 ? o.height() : 500;
                        var a = o.children("img").first(), r = a.height() - n, s = o.offset().top + n,
                            l = o.offset().top, c = t(window).scrollTop(), u = window.innerHeight,
                            d = (c + u - l) / (n + u), p = Math.round(r * d);
                        i && a.css("display", "block"), s > c && l < c + u && a.css("transform", "translate3D(-50%," + p + "px, 0)")
                    }

                    var o = t(this);
                    o.addClass("parallax"), o.children("img").one("load", function () {
                        n(!0)
                    }).each(function () {
                        this.complete && t(this).trigger("load")
                    }), t(window).scroll(function () {
                        e = t(window).width(), n(!1)
                    }), t(window).resize(function () {
                        e = t(window).width(), n(!1)
                    })
                })
            }
        }(jQuery), function (t) {
            var e = {
                init: function (e) {
                    var i = {onShow: null, swipeable: !1, responsiveThreshold: 1 / 0};
                    e = t.extend(i, e);
                    var n = Materialize.objectSelectorString(t(this));
                    return this.each(function (i) {
                        var o, a, r, s, l, c = n + i, u = t(this), d = t(window).width(), p = u.find("li.tab a"),
                            h = u.width(), f = t(), v = Math.max(h, u[0].scrollWidth) / p.length, m = prev_index = 0,
                            g = !1, y = function (t) {
                                return Math.ceil(h - t.position().left - t[0].getBoundingClientRect().width - u.scrollLeft())
                            }, b = function (t) {
                                return Math.floor(t.position().left + u.scrollLeft())
                            }, w = function (t) {
                                m - t >= 0 ? (s.velocity({right: y(o)}, {
                                    duration: 300,
                                    queue: !1,
                                    easing: "easeOutQuad"
                                }), s.velocity({left: b(o)}, {
                                    duration: 300,
                                    queue: !1,
                                    easing: "easeOutQuad",
                                    delay: 90
                                })) : (s.velocity({left: b(o)}, {
                                    duration: 300,
                                    queue: !1,
                                    easing: "easeOutQuad"
                                }), s.velocity({right: y(o)}, {duration: 300, queue: !1, easing: "easeOutQuad", delay: 90}))
                            };
                        e.swipeable && d > e.responsiveThreshold && (e.swipeable = !1), 0 === (o = t(p.filter('[href="' + location.hash + '"]'))).length && (o = t(this).find("li.tab a.active").first()), 0 === o.length && (o = t(this).find("li.tab a").first()), o.addClass("active"), (m = p.index(o)) < 0 && (m = 0), void 0 !== o[0] && (a = t(o[0].hash)).addClass("active"), u.find(".indicator").length || u.append('<li class="indicator"></li>'), s = u.find(".indicator"), u.append(s), u.is(":visible") && setTimeout(function () {
                            s.css({right: y(o)}), s.css({left: b(o)})
                        }, 0), t(window).off("resize.tabs-" + c).on("resize.tabs-" + c, function () {
                            h = u.width(), v = Math.max(h, u[0].scrollWidth) / p.length, m < 0 && (m = 0), 0 !== v && 0 !== h && (s.css({right: y(o)}), s.css({left: b(o)}))
                        }), e.swipeable ? (p.each(function () {
                            var e = t(Materialize.escapeHash(this.hash));
                            e.addClass("carousel-item"), f = f.add(e)
                        }), r = f.wrapAll('<div class="tabs-content carousel"></div>'), f.css("display", ""), t(".tabs-content.carousel").carousel({
                            fullWidth: !0,
                            noWrap: !0,
                            onCycleTo: function (t) {
                                if (!g) {
                                    var i = m;
                                    m = r.index(t), o.removeClass("active"), (o = p.eq(m)).addClass("active"), w(i), "function" == typeof e.onShow && e.onShow.call(u[0], a)
                                }
                            }
                        })) : p.not(o).each(function () {
                            t(Materialize.escapeHash(this.hash)).hide()
                        }), u.off("click.tabs").on("click.tabs", "a", function (i) {
                            if (t(this).parent().hasClass("disabled")) i.preventDefault(); else if (!t(this).attr("target")) {
                                g = !0, h = u.width(), v = Math.max(h, u[0].scrollWidth) / p.length, o.removeClass("active");
                                var n = a;
                                o = t(this), a = t(Materialize.escapeHash(this.hash)), p = u.find("li.tab a");
                                o.position();
                                o.addClass("active"), prev_index = m, (m = p.index(t(this))) < 0 && (m = 0), e.swipeable ? f.length && f.carousel("set", m, function () {
                                    "function" == typeof e.onShow && e.onShow.call(u[0], a)
                                }) : (void 0 !== a && (a.show(), a.addClass("active"), "function" == typeof e.onShow && e.onShow.call(this, a)), void 0 === n || n.is(a) || (n.hide(), n.removeClass("active"))), l = setTimeout(function () {
                                    g = !1
                                }, 300), w(prev_index), i.preventDefault()
                            }
                        })
                    })
                }, select_tab: function (t) {
                    this.find('a[href="#' + t + '"]').trigger("click")
                }
            };
            t.fn.tabs = function (i) {
                return e[i] ? e[i].apply(this, Array.prototype.slice.call(arguments, 1)) : "object" != typeof i && i ? void t.error("Method " + i + " does not exist on jQuery.tabs") : e.init.apply(this, arguments)
            }, t(document).ready(function () {
                t("ul.tabs").tabs()
            })
        }(jQuery), function (t) {
            t.fn.tooltip = function (i) {
                var n = {delay: 350, tooltip: "", position: "bottom", html: !1};
                return "remove" === i ? (this.each(function () {
                    t("#" + t(this).attr("data-tooltip-id")).remove(), t(this).removeAttr("data-tooltip-id"), t(this).off("mouseenter.tooltip mouseleave.tooltip")
                }), !1) : (i = t.extend(n, i), this.each(function () {
                    var n = Materialize.guid(), o = t(this);
                    o.attr("data-tooltip-id") && t("#" + o.attr("data-tooltip-id")).remove(), o.attr("data-tooltip-id", n);
                    var a, r, s, l, c, u, d = function () {
                        a = o.attr("data-html") ? "true" === o.attr("data-html") : i.html, r = o.attr("data-delay"), r = void 0 === r || "" === r ? i.delay : r, s = o.attr("data-position"), s = void 0 === s || "" === s ? i.position : s, l = o.attr("data-tooltip"), l = void 0 === l || "" === l ? i.tooltip : l
                    };
                    d();
                    c = function () {
                        var e = t('<div class="material-tooltip"></div>');
                        return l = a ? t("<span></span>").html(l) : t("<span></span>").text(l), e.append(l).appendTo(t("body")).attr("id", n), (u = t('<div class="backdrop"></div>')).appendTo(e), e
                    }(), o.off("mouseenter.tooltip mouseleave.tooltip");
                    var p, h = !1;
                    o.on({
                        "mouseenter.tooltip": function (t) {
                            p = setTimeout(function () {
                                d(), h = !0, c.velocity("stop"), u.velocity("stop"), c.css({
                                    visibility: "visible",
                                    left: "0px",
                                    top: "0px"
                                });
                                var t, i, n, a = o.outerWidth(), r = o.outerHeight(), l = c.outerHeight(),
                                    p = c.outerWidth(), f = "0px", v = "0px", m = u[0].offsetWidth,
                                    g = u[0].offsetHeight, y = 8, b = 8, w = 0;
                                "top" === s ? (t = o.offset().top - l - 5, i = o.offset().left + a / 2 - p / 2, n = e(i, t, p, l), f = "-10px", u.css({
                                    bottom: 0,
                                    left: 0,
                                    borderRadius: "14px 14px 0 0",
                                    transformOrigin: "50% 100%",
                                    marginTop: l,
                                    marginLeft: p / 2 - m / 2
                                })) : "left" === s ? (t = o.offset().top + r / 2 - l / 2, i = o.offset().left - p - 5, n = e(i, t, p, l), v = "-10px", u.css({
                                    top: "-7px",
                                    right: 0,
                                    width: "14px",
                                    height: "14px",
                                    borderRadius: "14px 0 0 14px",
                                    transformOrigin: "95% 50%",
                                    marginTop: l / 2,
                                    marginLeft: p
                                })) : "right" === s ? (t = o.offset().top + r / 2 - l / 2, i = o.offset().left + a + 5, n = e(i, t, p, l), v = "+10px", u.css({
                                    top: "-7px",
                                    left: 0,
                                    width: "14px",
                                    height: "14px",
                                    borderRadius: "0 14px 14px 0",
                                    transformOrigin: "5% 50%",
                                    marginTop: l / 2,
                                    marginLeft: "0px"
                                })) : (t = o.offset().top + o.outerHeight() + 5, i = o.offset().left + a / 2 - p / 2, n = e(i, t, p, l), f = "+10px", u.css({
                                    top: 0,
                                    left: 0,
                                    marginLeft: p / 2 - m / 2
                                })), c.css({
                                    top: n.y,
                                    left: n.x
                                }), y = Math.SQRT2 * p / parseInt(m), b = Math.SQRT2 * l / parseInt(g), w = Math.max(y, b), c.velocity({
                                    translateY: f,
                                    translateX: v
                                }, {duration: 350, queue: !1}).velocity({opacity: 1}, {
                                    duration: 300,
                                    delay: 50,
                                    queue: !1
                                }), u.css({visibility: "visible"}).velocity({opacity: 1}, {
                                    duration: 55,
                                    delay: 0,
                                    queue: !1
                                }).velocity({scaleX: w, scaleY: w}, {
                                    duration: 300,
                                    delay: 0,
                                    queue: !1,
                                    easing: "easeInOutQuad"
                                })
                            }, r)
                        }, "mouseleave.tooltip": function () {
                            h = !1, clearTimeout(p), setTimeout(function () {
                                !0 !== h && (c.velocity({opacity: 0, translateY: 0, translateX: 0}, {
                                    duration: 225,
                                    queue: !1
                                }), u.velocity({opacity: 0, scaleX: 1, scaleY: 1}, {
                                    duration: 225,
                                    queue: !1,
                                    complete: function () {
                                        u.css({visibility: "hidden"}), c.css({visibility: "hidden"}), h = !1
                                    }
                                }))
                            }, 225)
                        }
                    })
                }))
            };
            var e = function (e, i, n, o) {
                var a = e, r = i;
                return a < 0 ? a = 4 : a + n > window.innerWidth && (a -= a + n - window.innerWidth), r < 0 ? r = 4 : r + o > window.innerHeight + t(window).scrollTop && (r -= r + o - window.innerHeight), {
                    x: a,
                    y: r
                }
            };
            t(document).ready(function () {
                t(".tooltipped").tooltip()
            })
        }(jQuery), function (t) {
            "use strict";

            function e(t) {
                return null !== t && t === t.window
            }

            function i(t) {
                return e(t) ? t : 9 === t.nodeType && t.defaultView
            }

            function n(t) {
                var e, n, o = {top: 0, left: 0}, a = t && t.ownerDocument;
                return e = a.documentElement, void 0 !== t.getBoundingClientRect && (o = t.getBoundingClientRect()), n = i(a), {
                    top: o.top + n.pageYOffset - e.clientTop,
                    left: o.left + n.pageXOffset - e.clientLeft
                }
            }

            function o(t) {
                var e = "";
                for (var i in t) t.hasOwnProperty(i) && (e += i + ":" + t[i] + ";");
                return e
            }

            function a(t) {
                if (!1 === u.allowEvent(t)) return null;
                for (var e = null, i = t.target || t.srcElement; null !== i.parentNode;) {
                    if (!(i instanceof SVGElement) && -1 !== i.className.indexOf("waves-effect")) {
                        e = i;
                        break
                    }
                    i = i.parentNode
                }
                return e
            }

            function r(e) {
                var i = a(e);
                null !== i && (c.show(e, i), "ontouchstart" in t && (i.addEventListener("touchend", c.hide, !1), i.addEventListener("touchcancel", c.hide, !1)), i.addEventListener("mouseup", c.hide, !1), i.addEventListener("mouseleave", c.hide, !1), i.addEventListener("dragend", c.hide, !1))
            }

            var s = s || {}, l = document.querySelectorAll.bind(document), c = {
                duration: 750, show: function (t, e) {
                    if (2 === t.button) return !1;
                    var i = e || this, a = document.createElement("div");
                    a.className = "waves-ripple", i.appendChild(a);
                    var r = n(i), s = t.pageY - r.top, l = t.pageX - r.left,
                        u = "scale(" + i.clientWidth / 100 * 10 + ")";
                    "touches" in t && (s = t.touches[0].pageY - r.top, l = t.touches[0].pageX - r.left), a.setAttribute("data-hold", Date.now()), a.setAttribute("data-scale", u), a.setAttribute("data-x", l), a.setAttribute("data-y", s);
                    var d = {top: s + "px", left: l + "px"};
                    a.className = a.className + " waves-notransition", a.setAttribute("style", o(d)), a.className = a.className.replace("waves-notransition", ""), d["-webkit-transform"] = u, d["-moz-transform"] = u, d["-ms-transform"] = u, d["-o-transform"] = u, d.transform = u, d.opacity = "1", d["-webkit-transition-duration"] = c.duration + "ms", d["-moz-transition-duration"] = c.duration + "ms", d["-o-transition-duration"] = c.duration + "ms", d["transition-duration"] = c.duration + "ms", d["-webkit-transition-timing-function"] = "cubic-bezier(0.250, 0.460, 0.450, 0.940)", d["-moz-transition-timing-function"] = "cubic-bezier(0.250, 0.460, 0.450, 0.940)", d["-o-transition-timing-function"] = "cubic-bezier(0.250, 0.460, 0.450, 0.940)", d["transition-timing-function"] = "cubic-bezier(0.250, 0.460, 0.450, 0.940)", a.setAttribute("style", o(d))
                }, hide: function (t) {
                    u.touchup(t);
                    var e = this, i = (e.clientWidth, null), n = e.getElementsByClassName("waves-ripple");
                    if (!(n.length > 0)) return !1;
                    var a = (i = n[n.length - 1]).getAttribute("data-x"), r = i.getAttribute("data-y"),
                        s = i.getAttribute("data-scale"), l = 350 - (Date.now() - Number(i.getAttribute("data-hold")));
                    l < 0 && (l = 0), setTimeout(function () {
                        var t = {
                            top: r + "px",
                            left: a + "px",
                            opacity: "0",
                            "-webkit-transition-duration": c.duration + "ms",
                            "-moz-transition-duration": c.duration + "ms",
                            "-o-transition-duration": c.duration + "ms",
                            "transition-duration": c.duration + "ms",
                            "-webkit-transform": s,
                            "-moz-transform": s,
                            "-ms-transform": s,
                            "-o-transform": s,
                            transform: s
                        };
                        i.setAttribute("style", o(t)), setTimeout(function () {
                            try {
                                e.removeChild(i)
                            } catch (t) {
                                return !1
                            }
                        }, c.duration)
                    }, l)
                }, wrapInput: function (t) {
                    for (var e = 0; e < t.length; e++) {
                        var i = t[e];
                        if ("input" === i.tagName.toLowerCase()) {
                            var n = i.parentNode;
                            if ("i" === n.tagName.toLowerCase() && -1 !== n.className.indexOf("waves-effect")) continue;
                            var o = document.createElement("i");
                            o.className = i.className + " waves-input-wrapper";
                            var a = i.getAttribute("style");
                            a || (a = ""), o.setAttribute("style", a), i.className = "waves-button-input", i.removeAttribute("style"), n.replaceChild(o, i), o.appendChild(i)
                        }
                    }
                }
            }, u = {
                touches: 0, allowEvent: function (t) {
                    var e = !0;
                    return "touchstart" === t.type ? u.touches += 1 : "touchend" === t.type || "touchcancel" === t.type ? setTimeout(function () {
                        u.touches > 0 && (u.touches -= 1)
                    }, 500) : "mousedown" === t.type && u.touches > 0 && (e = !1), e
                }, touchup: function (t) {
                    u.allowEvent(t)
                }
            };
            s.displayEffect = function (e) {
                "duration" in (e = e || {}) && (c.duration = e.duration), c.wrapInput(l(".waves-effect")), "ontouchstart" in t && document.body.addEventListener("touchstart", r, !1), document.body.addEventListener("mousedown", r, !1)
            }, s.attach = function (e) {
                "input" === e.tagName.toLowerCase() && (c.wrapInput([e]), e = e.parentNode), "ontouchstart" in t && e.addEventListener("touchstart", r, !1), e.addEventListener("mousedown", r, !1)
            }, t.Waves = s, document.addEventListener("DOMContentLoaded", function () {
                s.displayEffect()
            }, !1)
        }(window), function (t) {
            "use strict";
            var e = {
                displayLength: 1 / 0,
                inDuration: 300,
                outDuration: 375,
                className: void 0,
                completeCallback: void 0,
                activationPercent: .8
            }, i = function () {
                function i(e, n, o, a) {
                    if (_classCallCheck(this, i), e) {
                        this.options = {
                            displayLength: n,
                            className: o,
                            completeCallback: a
                        }, this.options = t.extend({}, i.defaults, this.options), this.message = e, this.panning = !1, this.timeRemaining = this.options.displayLength, 0 === i._toasts.length && i._createContainer(), i._toasts.push(this);
                        var r = this.createToast();
                        r.M_Toast = this, this.el = r, this._animateIn(), this.setTimer()
                    }
                }

                return _createClass(i, [{
                    key: "createToast", value: function () {
                        var e = document.createElement("div");
                        if (e.classList.add("toast"), this.options.className) {
                            var n = this.options.className.split(" "), o = void 0, a = void 0;
                            for (o = 0, a = n.length; o < a; o++) e.classList.add(n[o])
                        }
                        return ("object" == typeof HTMLElement ? this.message instanceof HTMLElement : this.message && "object" == typeof this.message && null !== this.message && 1 === this.message.nodeType && "string" == typeof this.message.nodeName) ? e.appendChild(this.message) : this.message instanceof jQuery ? t(e).append(this.message) : e.innerHTML = this.message, i._container.appendChild(e), e
                    }
                }, {
                    key: "_animateIn", value: function () {
                        Vel(this.el, {top: 0, opacity: 1}, {duration: 300, easing: "easeOutCubic", queue: !1})
                    }
                }, {
                    key: "setTimer", value: function () {
                        var t = this;
                        this.timeRemaining !== 1 / 0 && (this.counterInterval = setInterval(function () {
                            t.panning || (t.timeRemaining -= 20), t.timeRemaining <= 0 && t.remove()
                        }, 20))
                    }
                }, {
                    key: "remove", value: function () {
                        var t = this;
                        window.clearInterval(this.counterInterval);
                        var e = this.el.offsetWidth * this.options.activationPercent;
                        this.wasSwiped && (this.el.style.transition = "transform .05s, opacity .05s", this.el.style.transform = "translateX(" + e + "px)", this.el.style.opacity = 0), Vel(this.el, {
                            opacity: 0,
                            marginTop: "-40px"
                        }, {
                            duration: this.options.outDuration,
                            easing: "easeOutExpo",
                            queue: !1,
                            complete: function () {
                                "function" == typeof t.options.completeCallback && t.options.completeCallback(), t.el.parentNode.removeChild(t.el), i._toasts.splice(i._toasts.indexOf(t), 1), 0 === i._toasts.length && i._removeContainer()
                            }
                        })
                    }
                }], [{
                    key: "_createContainer", value: function () {
                        var t = document.createElement("div");
                        t.setAttribute("id", "toast-container"), t.addEventListener("touchstart", i._onDragStart), t.addEventListener("touchmove", i._onDragMove), t.addEventListener("touchend", i._onDragEnd), t.addEventListener("mousedown", i._onDragStart), document.addEventListener("mousemove", i._onDragMove), document.addEventListener("mouseup", i._onDragEnd), document.body.appendChild(t), i._container = t
                    }
                }, {
                    key: "_removeContainer", value: function () {
                        document.removeEventListener("mousemove", i._onDragMove), document.removeEventListener("mouseup", i._onDragEnd), i._container.parentNode.removeChild(i._container), i._container = null
                    }
                }, {
                    key: "_onDragStart", value: function (e) {
                        if (e.target && t(e.target).closest(".toast").length) {
                            var n = t(e.target).closest(".toast")[0].M_Toast;
                            n.panning = !0, i._draggedToast = n, n.el.classList.add("panning"), n.el.style.transition = null, n.startingXPos = i._xPos(e), n.time = Date.now(), n.xPos = i._xPos(e)
                        }
                    }
                }, {
                    key: "_onDragMove", value: function (t) {
                        if (i._draggedToast) {
                            t.preventDefault();
                            var e = i._draggedToast;
                            e.deltaX = Math.abs(e.xPos - i._xPos(t)), e.xPos = i._xPos(t), e.velocityX = e.deltaX / (Date.now() - e.time), e.time = Date.now();
                            var n = e.xPos - e.startingXPos, o = e.el.offsetWidth * e.options.activationPercent;
                            e.el.style.transform = "translateX(" + n + "px)", e.el.style.opacity = 1 - Math.abs(n / o)
                        }
                    }
                }, {
                    key: "_onDragEnd", value: function (t) {
                        if (i._draggedToast) {
                            var e = i._draggedToast;
                            e.panning = !1, e.el.classList.remove("panning");
                            var n = e.xPos - e.startingXPos, o = e.el.offsetWidth * e.options.activationPercent;
                            Math.abs(n) > o || e.velocityX > 1 ? (e.wasSwiped = !0, e.remove()) : (e.el.style.transition = "transform .2s, opacity .2s", e.el.style.transform = null, e.el.style.opacity = null), i._draggedToast = null
                        }
                    }
                }, {
                    key: "_xPos", value: function (t) {
                        return t.targetTouches && t.targetTouches.length >= 1 ? t.targetTouches[0].clientX : t.clientX
                    }
                }, {
                    key: "removeAll", value: function () {
                        for (var t in i._toasts) i._toasts[t].remove()
                    }
                }, {
                    key: "defaults", get: function () {
                        return e
                    }
                }]), i
            }();
            i._toasts = [], i._container = null, i._draggedToast = null, window.Materialize.Toast = i, window.Materialize.toast = function (t, e, n, o) {
                return new i(t, e, n, o)
            }
        }(jQuery), function (t) {
            var e = {
                init: function (e) {
                    var i = {
                        menuWidth: 300,
                        edge: "left",
                        closeOnClick: !1,
                        draggable: !0,
                        onOpen: null,
                        onClose: null
                    };
                    e = t.extend(i, e), t(this).each(function () {
                        var i = t(this), n = i.attr("data-activates"), o = t("#" + n);
                        300 != e.menuWidth && o.css("width", e.menuWidth);
                        var a = t('.drag-target[data-sidenav="' + n + '"]');
                        e.draggable ? (a.length && a.remove(), a = t('<div class="drag-target"></div>').attr("data-sidenav", n), t("body").append(a)) : a = t(), "left" == e.edge ? (o.css("transform", "translateX(-100%)"), a.css({left: 0})) : (o.addClass("right-aligned").css("transform", "translateX(100%)"), a.css({right: 0})), o.hasClass("fixed") && window.innerWidth > 992 && o.css("transform", "translateX(0)"), o.hasClass("fixed") && t(window).resize(function () {
                            window.innerWidth > 992 ? 0 !== t("#sidenav-overlay").length && l ? r(!0) : o.css("transform", "translateX(0%)") : !1 === l && ("left" === e.edge ? o.css("transform", "translateX(-100%)") : o.css("transform", "translateX(100%)"))
                        }), !0 === e.closeOnClick && o.on("click.itemclick", "a:not(.collapsible-header)", function () {
                            window.innerWidth > 992 && o.hasClass("fixed") || r()
                        });
                        var r = function (i) {
                            s = !1, l = !1, t("body").css({
                                overflow: "",
                                width: ""
                            }), t("#sidenav-overlay").velocity({opacity: 0}, {
                                duration: 200,
                                queue: !1,
                                easing: "easeOutQuad",
                                complete: function () {
                                    t(this).remove()
                                }
                            }), "left" === e.edge ? (a.css({
                                width: "",
                                right: "",
                                left: "0"
                            }), o.velocity({translateX: "-100%"}, {
                                duration: 200,
                                queue: !1,
                                easing: "easeOutCubic",
                                complete: function () {
                                    !0 === i && (o.removeAttr("style"), o.css("width", e.menuWidth))
                                }
                            })) : (a.css({
                                width: "",
                                right: "0",
                                left: ""
                            }), o.velocity({translateX: "100%"}, {
                                duration: 200,
                                queue: !1,
                                easing: "easeOutCubic",
                                complete: function () {
                                    !0 === i && (o.removeAttr("style"), o.css("width", e.menuWidth))
                                }
                            })), "function" == typeof e.onClose && e.onClose.call(this, o)
                        }, s = !1, l = !1;
                        e.draggable && (a.on("click", function () {
                            l && r()
                        }), a.hammer({prevent_default: !1}).on("pan", function (i) {
                            if ("touch" == i.gesture.pointerType) {
                                i.gesture.direction;
                                var n = i.gesture.center.x, a = i.gesture.center.y;
                                i.gesture.velocityX;
                                if (0 === n && 0 === a) return;
                                var s = t("body"), c = t("#sidenav-overlay"), u = s.innerWidth();
                                if (s.css("overflow", "hidden"), s.width(u), 0 === c.length && ((c = t('<div id="sidenav-overlay"></div>')).css("opacity", 0).click(function () {
                                        r()
                                    }), "function" == typeof e.onOpen && e.onOpen.call(this, o), t("body").append(c)), "left" === e.edge && (n > e.menuWidth ? n = e.menuWidth : n < 0 && (n = 0)), "left" === e.edge) n < e.menuWidth / 2 ? l = !1 : n >= e.menuWidth / 2 && (l = !0), o.css("transform", "translateX(" + (n - e.menuWidth) + "px)"); else {
                                    n < window.innerWidth - e.menuWidth / 2 ? l = !0 : n >= window.innerWidth - e.menuWidth / 2 && (l = !1);
                                    var d = n - e.menuWidth / 2;
                                    d < 0 && (d = 0), o.css("transform", "translateX(" + d + "px)")
                                }
                                var p;
                                "left" === e.edge ? (p = n / e.menuWidth, c.velocity({opacity: p}, {
                                    duration: 10,
                                    queue: !1,
                                    easing: "easeOutQuad"
                                })) : (p = Math.abs((n - window.innerWidth) / e.menuWidth), c.velocity({opacity: p}, {
                                    duration: 10,
                                    queue: !1,
                                    easing: "easeOutQuad"
                                }))
                            }
                        }).on("panend", function (i) {
                            if ("touch" == i.gesture.pointerType) {
                                var n = t("#sidenav-overlay"), r = i.gesture.velocityX, c = i.gesture.center.x,
                                    u = c - e.menuWidth, d = c - e.menuWidth / 2;
                                u > 0 && (u = 0), d < 0 && (d = 0), s = !1, "left" === e.edge ? l && r <= .3 || r < -.5 ? (0 !== u && o.velocity({translateX: [0, u]}, {
                                    duration: 300,
                                    queue: !1,
                                    easing: "easeOutQuad"
                                }), n.velocity({opacity: 1}, {
                                    duration: 50,
                                    queue: !1,
                                    easing: "easeOutQuad"
                                }), a.css({
                                    width: "50%",
                                    right: 0,
                                    left: ""
                                }), l = !0) : (!l || r > .3) && (t("body").css({
                                    overflow: "",
                                    width: ""
                                }), o.velocity({translateX: [-1 * e.menuWidth - 10, u]}, {
                                    duration: 200,
                                    queue: !1,
                                    easing: "easeOutQuad"
                                }), n.velocity({opacity: 0}, {
                                    duration: 200,
                                    queue: !1,
                                    easing: "easeOutQuad",
                                    complete: function () {
                                        "function" == typeof e.onClose && e.onClose.call(this, o), t(this).remove()
                                    }
                                }), a.css({
                                    width: "10px",
                                    right: "",
                                    left: 0
                                })) : l && r >= -.3 || r > .5 ? (0 !== d && o.velocity({translateX: [0, d]}, {
                                    duration: 300,
                                    queue: !1,
                                    easing: "easeOutQuad"
                                }), n.velocity({opacity: 1}, {
                                    duration: 50,
                                    queue: !1,
                                    easing: "easeOutQuad"
                                }), a.css({
                                    width: "50%",
                                    right: "",
                                    left: 0
                                }), l = !0) : (!l || r < -.3) && (t("body").css({
                                    overflow: "",
                                    width: ""
                                }), o.velocity({translateX: [e.menuWidth + 10, d]}, {
                                    duration: 200,
                                    queue: !1,
                                    easing: "easeOutQuad"
                                }), n.velocity({opacity: 0}, {
                                    duration: 200,
                                    queue: !1,
                                    easing: "easeOutQuad",
                                    complete: function () {
                                        "function" == typeof e.onClose && e.onClose.call(this, o), t(this).remove()
                                    }
                                }), a.css({width: "10px", right: 0, left: ""}))
                            }
                        })), i.off("click.sidenav").on("click.sidenav", function () {
                            if (!0 === l) l = !1, s = !1, r(); else {
                                var i = t("body"), n = t('<div id="sidenav-overlay"></div>'), c = i.innerWidth();
                                i.css("overflow", "hidden"), i.width(c), t("body").append(a), "left" === e.edge ? (a.css({
                                    width: "50%",
                                    right: 0,
                                    left: ""
                                }), o.velocity({translateX: [0, -1 * e.menuWidth]}, {
                                    duration: 300,
                                    queue: !1,
                                    easing: "easeOutQuad"
                                })) : (a.css({
                                    width: "50%",
                                    right: "",
                                    left: 0
                                }), o.velocity({translateX: [0, e.menuWidth]}, {
                                    duration: 300,
                                    queue: !1,
                                    easing: "easeOutQuad"
                                })), n.css("opacity", 0).click(function () {
                                    l = !1, s = !1, r(), n.velocity({opacity: 0}, {
                                        duration: 300,
                                        queue: !1,
                                        easing: "easeOutQuad",
                                        complete: function () {
                                            t(this).remove()
                                        }
                                    })
                                }), t("body").append(n), n.velocity({opacity: 1}, {
                                    duration: 300,
                                    queue: !1,
                                    easing: "easeOutQuad",
                                    complete: function () {
                                        l = !0, s = !1
                                    }
                                }), "function" == typeof e.onOpen && e.onOpen.call(this, o)
                            }
                            return !1
                        })
                    })
                }, destroy: function () {
                    var e = t("#sidenav-overlay"),
                        i = t('.drag-target[data-sidenav="' + t(this).attr("data-activates") + '"]');
                    e.trigger("click"), i.remove(), t(this).off("click"), e.remove()
                }, show: function () {
                    this.trigger("click")
                }, hide: function () {
                    t("#sidenav-overlay").trigger("click")
                }
            };
            t.fn.sideNav = function (i) {
                return e[i] ? e[i].apply(this, Array.prototype.slice.call(arguments, 1)) : "object" != typeof i && i ? void t.error("Method " + i + " does not exist on jQuery.sideNav") : e.init.apply(this, arguments)
            }
        }(jQuery), function (t) {
            function e(e, i, n, o) {
                var r = t();
                return t.each(a, function (t, a) {
                    if (a.height() > 0) {
                        var s = a.offset().top, l = a.offset().left, c = l + a.width(), u = s + a.height();
                        !(l > i || c < o || s > n || u < e) && r.push(a)
                    }
                }), r
            }

            function i(i) {
                ++l;
                var n = o.scrollTop(), a = o.scrollLeft(), s = a + o.width(), u = n + o.height(),
                    d = e(n + c.top + i || 200, s + c.right, u + c.bottom, a + c.left);
                t.each(d, function (t, e) {
                    "number" != typeof e.data("scrollSpy:ticks") && e.triggerHandler("scrollSpy:enter"), e.data("scrollSpy:ticks", l)
                }), t.each(r, function (t, e) {
                    var i = e.data("scrollSpy:ticks");
                    "number" == typeof i && i !== l && (e.triggerHandler("scrollSpy:exit"), e.data("scrollSpy:ticks", null))
                }), r = d
            }

            function n() {
                o.trigger("scrollSpy:winSize")
            }

            var o = t(window), a = [], r = [], s = !1, l = 0, c = {top: 0, right: 0, bottom: 0, left: 0};
            t.scrollSpy = function (e, n) {
                var r = {
                    throttle: 100, scrollOffset: 200, activeClass: "active", getActiveElement: function (t) {
                        return 'a[href="#' + t + '"]'
                    }
                };
                n = t.extend(r, n);
                var l = [];
                (e = t(e)).each(function (e, i) {
                    a.push(t(i)), t(i).data("scrollSpy:id", e), t('a[href="#' + t(i).attr("id") + '"]').click(function (e) {
                        e.preventDefault();
                        var i = t(Materialize.escapeHash(this.hash)).offset().top + 1;
                        t("html, body").animate({scrollTop: i - n.scrollOffset}, {
                            duration: 400,
                            queue: !1,
                            easing: "easeOutCubic"
                        })
                    })
                }), c.top = n.offsetTop || 0, c.right = n.offsetRight || 0, c.bottom = n.offsetBottom || 0, c.left = n.offsetLeft || 0;
                var u = Materialize.throttle(function () {
                    i(n.scrollOffset)
                }, n.throttle || 100), d = function () {
                    t(document).ready(u)
                };
                return s || (o.on("scroll", d), o.on("resize", d), s = !0), setTimeout(d, 0), e.on("scrollSpy:enter", function () {
                    l = t.grep(l, function (t) {
                        return 0 != t.height()
                    });
                    var e = t(this);
                    l[0] ? (t(n.getActiveElement(l[0].attr("id"))).removeClass(n.activeClass), e.data("scrollSpy:id") < l[0].data("scrollSpy:id") ? l.unshift(t(this)) : l.push(t(this))) : l.push(t(this)), t(n.getActiveElement(l[0].attr("id"))).addClass(n.activeClass)
                }), e.on("scrollSpy:exit", function () {
                    if ((l = t.grep(l, function (t) {
                            return 0 != t.height()
                        }))[0]) {
                        t(n.getActiveElement(l[0].attr("id"))).removeClass(n.activeClass);
                        var e = t(this);
                        (l = t.grep(l, function (t) {
                            return t.attr("id") != e.attr("id")
                        }))[0] && t(n.getActiveElement(l[0].attr("id"))).addClass(n.activeClass)
                    }
                }), e
            }, t.winSizeSpy = function (e) {
                return t.winSizeSpy = function () {
                    return o
                }, e = e || {throttle: 100}, o.on("resize", Materialize.throttle(n, e.throttle || 100))
            }, t.fn.scrollSpy = function (e) {
                return t.scrollSpy(t(this), e)
            }
        }(jQuery), function (t) {
            t(document).ready(function () {
                function e(e) {
                    var i = e.css("font-family"), o = e.css("font-size"), a = e.css("line-height"),
                        r = e.css("padding");
                    o && n.css("font-size", o), i && n.css("font-family", i), a && n.css("line-height", a), r && n.css("padding", r), e.data("original-height") || e.data("original-height", e.height()), "off" === e.attr("wrap") && n.css("overflow-wrap", "normal").css("white-space", "pre"), n.text(e.val() + "\n");
                    var s = n.html().replace(/\n/g, "<br>");
                    n.html(s), e.is(":visible") ? n.css("width", e.width()) : n.css("width", t(window).width() / 2), e.data("original-height") <= n.height() ? e.css("height", n.height()) : e.val().length < e.data("previous-length") && e.css("height", e.data("original-height")), e.data("previous-length", e.val().length)
                }

                Materialize.updateTextFields = function () {
                    t("input[type=text], input[type=password], input[type=email], input[type=url], input[type=tel], input[type=number], input[type=search], textarea").each(function (e, i) {
                        var n = t(this);
                        t(i).val().length > 0 || t(i).is(":focus") || i.autofocus || void 0 !== n.attr("placeholder") ? n.siblings("label").addClass("active") : t(i)[0].validity ? n.siblings("label").toggleClass("active", !0 === t(i)[0].validity.badInput) : n.siblings("label").removeClass("active")
                    })
                };
                var i = "input[type=text], input[type=password], input[type=email], input[type=url], input[type=tel], input[type=number], input[type=search], textarea";
                t(document).on("change", i, function () {
                    0 === t(this).val().length && void 0 === t(this).attr("placeholder") || t(this).siblings("label").addClass("active"), validate_field(t(this))
                }), t(document).ready(function () {
                    Materialize.updateTextFields()
                }), t(document).on("reset", function (e) {
                    var n = t(e.target);
                    n.is("form") && (n.find(i).removeClass("valid").removeClass("invalid"), n.find(i).each(function () {
                        "" === t(this).attr("value") && t(this).siblings("label").removeClass("active")
                    }), n.find("select.initialized").each(function () {
                        var t = n.find("option[selected]").text();
                        n.siblings("input.select-dropdown").val(t)
                    }))
                }), t(document).on("focus", i, function () {
                    t(this).siblings("label, .prefix").addClass("active")
                }), t(document).on("blur", i, function () {
                    var e = t(this), i = ".prefix";
                    0 === e.val().length && !0 !== e[0].validity.badInput && void 0 === e.attr("placeholder") && (i += ", label"), e.siblings(i).removeClass("active"), validate_field(e)
                }), window.validate_field = function (t) {
                    var e = void 0 !== t.attr("data-length"), i = parseInt(t.attr("data-length")), n = t.val().length;
                    0 !== t.val().length || !1 !== t[0].validity.badInput || t.is(":required") ? t.hasClass("validate") && (t.is(":valid") && e && n <= i || t.is(":valid") && !e ? (t.removeClass("invalid"), t.addClass("valid")) : (t.removeClass("valid"), t.addClass("invalid"))) : t.hasClass("validate") && (t.removeClass("valid"), t.removeClass("invalid"))
                };
                t(document).on("keyup.radio", "input[type=radio], input[type=checkbox]", function (e) {
                    if (9 === e.which) return t(this).addClass("tabbed"), void t(this).one("blur", function (e) {
                        t(this).removeClass("tabbed")
                    })
                });
                var n = t(".hiddendiv").first();
                n.length || (n = t('<div class="hiddendiv common"></div>'), t("body").append(n));
                t(".materialize-textarea").each(function () {
                    var e = t(this);
                    e.data("original-height", e.height()), e.data("previous-length", e.val().length)
                }), t("body").on("keyup keydown autoresize", ".materialize-textarea", function () {
                    e(t(this))
                }), t(document).on("change", '.file-field input[type="file"]', function () {
                    for (var e = t(this).closest(".file-field").find("input.file-path"), i = t(this)[0].files, n = [], o = 0; o < i.length; o++) n.push(i[o].name);
                    e.val(n.join(", ")), e.trigger("change")
                });
                var o = "input[type=range]", a = !1;
                t(o).each(function () {
                    var e = t('<span class="thumb"><span class="value"></span></span>');
                    t(this).after(e)
                });
                var r = function (t) {
                    var e = -7 + parseInt(t.parent().css("padding-left")) + "px";
                    t.velocity({height: "30px", width: "30px", top: "-30px", marginLeft: e}, {
                        duration: 300,
                        easing: "easeOutExpo"
                    })
                }, s = function (t) {
                    var e = t.width() - 15, i = parseFloat(t.attr("max")), n = parseFloat(t.attr("min"));
                    return (parseFloat(t.val()) - n) / (i - n) * e
                };
                t(document).on("change", o, function (e) {
                    var i = t(this).siblings(".thumb");
                    i.find(".value").html(t(this).val()), i.hasClass("active") || r(i);
                    var n = s(t(this));
                    i.addClass("active").css("left", n)
                }), t(document).on("mousedown touchstart", o, function (e) {
                    var i = t(this).siblings(".thumb");
                    if (i.length <= 0 && (i = t('<span class="thumb"><span class="value"></span></span>'), t(this).after(i)), i.find(".value").html(t(this).val()), a = !0, t(this).addClass("active"), i.hasClass("active") || r(i), "input" !== e.type) {
                        var n = s(t(this));
                        i.addClass("active").css("left", n)
                    }
                }), t(document).on("mouseup touchend", ".range-field", function () {
                    a = !1, t(this).removeClass("active")
                }), t(document).on("input mousemove touchmove", ".range-field", function (e) {
                    var i = t(this).children(".thumb"), n = t(this).find(o);
                    if (a) {
                        i.hasClass("active") || r(i);
                        var l = s(n);
                        i.addClass("active").css("left", l), i.find(".value").html(i.siblings(o).val())
                    }
                }), t(document).on("mouseout touchleave", ".range-field", function () {
                    if (!a) {
                        var e = t(this).children(".thumb"), i = 7 + parseInt(t(this).css("padding-left")) + "px";
                        e.hasClass("active") && e.velocity({
                            height: "0",
                            width: "0",
                            top: "10px",
                            marginLeft: i
                        }, {duration: 100}), e.removeClass("active")
                    }
                }), t.fn.autocomplete = function (e) {
                    var i = {data: {}, limit: 1 / 0, onAutocomplete: null, minLength: 1};
                    return e = t.extend(i, e), this.each(function () {
                        var i, n = t(this), o = e.data, a = 0, r = -1, s = n.closest(".input-field");
                        if (t.isEmptyObject(o)) n.off("keyup.autocomplete focus.autocomplete"); else {
                            var l, c = t('<ul class="autocomplete-content dropdown-content"></ul>');
                            s.length ? (l = s.children(".autocomplete-content.dropdown-content").first()).length || s.append(c) : (l = n.next(".autocomplete-content.dropdown-content")).length || n.after(c), l.length && (c = l);
                            var u = function (t, e) {
                                var i = e.find("img"), n = e.text().toLowerCase().indexOf("" + t.toLowerCase()),
                                    o = n + t.length - 1, a = e.text().slice(0, n), r = e.text().slice(n, o + 1),
                                    s = e.text().slice(o + 1);
                                e.html("<span>" + a + "<span class='highlight'>" + r + "</span>" + s + "</span>"), i.length && e.prepend(i)
                            }, d = function () {
                                r = -1, c.find(".active").removeClass("active")
                            }, p = function () {
                                c.empty(), d(), i = void 0
                            };
                            n.off("blur.autocomplete").on("blur.autocomplete", function () {
                                p()
                            }), n.off("keyup.autocomplete focus.autocomplete").on("keyup.autocomplete focus.autocomplete", function (r) {
                                a = 0;
                                var s = n.val().toLowerCase();
                                if (13 !== r.which && 38 !== r.which && 40 !== r.which) {
                                    if (i !== s && (p(), s.length >= e.minLength)) for (var l in o) if (o.hasOwnProperty(l) && -1 !== l.toLowerCase().indexOf(s)) {
                                        if (a >= e.limit) break;
                                        var d = t("<li></li>");
                                        o[l] ? d.append('<img src="' + o[l] + '" class="right circle"><span>' + l + "</span>") : d.append("<span>" + l + "</span>"), c.append(d), u(s, d), a++
                                    }
                                    i = s
                                }
                            }), n.off("keydown.autocomplete").on("keydown.autocomplete", function (t) {
                                var e, i = t.which, n = c.children("li").length, o = c.children(".active").first();
                                13 === i && r >= 0 ? (e = c.children("li").eq(r)).length && (e.trigger("mousedown.autocomplete"), t.preventDefault()) : 38 !== i && 40 !== i || (t.preventDefault(), 38 === i && r > 0 && r--, 40 === i && r < n - 1 && r++, o.removeClass("active"), r >= 0 && c.children("li").eq(r).addClass("active"))
                            }), c.off("mousedown.autocomplete touchstart.autocomplete").on("mousedown.autocomplete touchstart.autocomplete", "li", function () {
                                var i = t(this).text().trim();
                                n.val(i), n.trigger("change"), p(), "function" == typeof e.onAutocomplete && e.onAutocomplete.call(this, i)
                            })
                        }
                    })
                }
            }), t.fn.material_select = function (e) {
                function i(t, e, i) {
                    var o = t.indexOf(e), a = -1 === o;
                    return a ? t.push(e) : t.splice(o, 1), i.siblings("ul.dropdown-content").find("li:not(.optgroup)").eq(e).toggleClass("active"), i.find("option").eq(e).prop("selected", a), n(t, i), a
                }

                function n(t, e) {
                    for (var i = "", n = 0, o = t.length; n < o; n++) {
                        var a = e.find("option").eq(t[n]).text();
                        i += 0 === n ? a : ", " + a
                    }
                    "" === i && (i = e.find("option:disabled").eq(0).text()), e.siblings("input.select-dropdown").val(i)
                }

                t(this).each(function () {
                    var n = t(this);
                    if (!n.hasClass("browser-default")) {
                        var o = !!n.attr("multiple"), a = n.attr("data-select-id");
                        if (a && (n.parent().find("span.caret").remove(), n.parent().find("input").remove(), n.unwrap(), t("ul#select-options-" + a).remove()), "destroy" === e) return n.removeAttr("data-select-id").removeClass("initialized"), void t(window).off("click.select");
                        var r = Materialize.guid();
                        n.attr("data-select-id", r);
                        var s = t('<div class="select-wrapper"></div>');
                        s.addClass(n.attr("class")), n.is(":disabled") && s.addClass("disabled");
                        var l = t('<ul id="select-options-' + r + '" class="dropdown-content select-dropdown ' + (o ? "multiple-select-dropdown" : "") + '"></ul>'),
                            c = n.children("option, optgroup"), u = [], d = !1,
                            p = n.find("option:selected").html() || n.find("option:first").html() || "",
                            h = function (e, i, n) {
                                var a = i.is(":disabled") ? "disabled " : "",
                                    r = "optgroup-option" === n ? "optgroup-option " : "",
                                    s = o ? '<input type="checkbox"' + a + "/><label></label>" : "", c = i.data("icon"),
                                    u = i.attr("class");
                                if (c) {
                                    var d = "";
                                    return u && (d = ' class="' + u + '"'), l.append(t('<li class="' + a + r + '"><img alt="" src="' + c + '"' + d + "><span>" + s + i.html() + "</span></li>")), !0
                                }
                                l.append(t('<li class="' + a + r + '"><span>' + s + i.html() + "</span></li>"))
                            };
                        c.length && c.each(function () {
                            if (t(this).is("option")) o ? h(0, t(this), "multiple") : h(0, t(this)); else if (t(this).is("optgroup")) {
                                var e = t(this).children("option");
                                l.append(t('<li class="optgroup"><span>' + t(this).attr("label") + "</span></li>")), e.each(function () {
                                    h(0, t(this), "optgroup-option")
                                })
                            }
                        }), l.find("li:not(.optgroup)").each(function (a) {
                            t(this).click(function (r) {
                                if (!t(this).hasClass("disabled") && !t(this).hasClass("optgroup")) {
                                    var s = !0;
                                    o ? (t('input[type="checkbox"]', this).prop("checked", function (t, e) {
                                        return !e
                                    }), s = i(u, a, n), m.trigger("focus")) : (l.find("li").removeClass("active"), t(this).toggleClass("active"), m.val(t(this).text())), g(l, t(this)), n.find("option").eq(a).prop("selected", s), n.trigger("change"), void 0 !== e && e()
                                }
                                r.stopPropagation()
                            })
                        }), n.wrap(s);
                        var f = t('<span class="caret">&#9660;</span>'), v = p.replace(/"/g, "&quot;"),
                            m = t('<input type="text" class="select-dropdown" readonly="true" ' + (n.is(":disabled") ? "disabled" : "") + ' data-activates="select-options-' + r + '" value="' + v + '"/>');
                        n.before(m), m.before(f), m.after(l), n.is(":disabled") || m.dropdown({hover: !1}), n.attr("tabindex") && t(m[0]).attr("tabindex", n.attr("tabindex")), n.addClass("initialized"), m.on({
                            focus: function () {
                                if (t("ul.select-dropdown").not(l[0]).is(":visible") && (t("input.select-dropdown").trigger("close"), t(window).off("click.select")), !l.is(":visible")) {
                                    t(this).trigger("open", ["focus"]);
                                    var e = t(this).val();
                                    o && e.indexOf(",") >= 0 && (e = e.split(",")[0]);
                                    var i = l.find("li").filter(function () {
                                        return t(this).text().toLowerCase() === e.toLowerCase()
                                    })[0];
                                    g(l, i, !0), t(window).off("click.select").on("click.select", function () {
                                        o && (d || m.trigger("close")), t(window).off("click.select")
                                    })
                                }
                            }, click: function (t) {
                                t.stopPropagation()
                            }
                        }), m.on("blur", function () {
                            o || (t(this).trigger("close"), t(window).off("click.select")), l.find("li.selected").removeClass("selected")
                        }), l.hover(function () {
                            d = !0
                        }, function () {
                            d = !1
                        }), o && n.find("option:selected:not(:disabled)").each(function () {
                            var e = t(this).index();
                            i(u, e, n), l.find("li").eq(e).find(":checkbox").prop("checked", !0)
                        });
                        var g = function (e, i, n) {
                            if (i) {
                                e.find("li.selected").removeClass("selected");
                                var a = t(i);
                                a.addClass("selected"), o && !n || l.scrollTo(a)
                            }
                        }, y = [];
                        m.on("keydown", function (e) {
                            if (9 != e.which) if (40 != e.which || l.is(":visible")) {
                                if (13 != e.which || l.is(":visible")) {
                                    e.preventDefault();
                                    var i = String.fromCharCode(e.which).toLowerCase(), n = [9, 13, 27, 38, 40];
                                    if (i && -1 === n.indexOf(e.which)) {
                                        y.push(i);
                                        var a = y.join(""), r = l.find("li").filter(function () {
                                            return 0 === t(this).text().toLowerCase().indexOf(a)
                                        })[0];
                                        r && g(l, r)
                                    }
                                    if (13 == e.which) {
                                        var s = l.find("li.selected:not(.disabled)")[0];
                                        s && (t(s).trigger("click"), o || m.trigger("close"))
                                    }
                                    40 == e.which && (r = l.find("li.selected").length ? l.find("li.selected").next("li:not(.disabled)")[0] : l.find("li:not(.disabled)")[0], g(l, r)), 27 == e.which && m.trigger("close"), 38 == e.which && (r = l.find("li.selected").prev("li:not(.disabled)")[0]) && g(l, r), setTimeout(function () {
                                        y = []
                                    }, 1e3)
                                }
                            } else m.trigger("open"); else m.trigger("close")
                        })
                    }
                })
            }
        }(jQuery), function (t) {
            var e = {
                init: function (e) {
                    var i = {indicators: !0, height: 400, transition: 500, interval: 6e3};
                    return e = t.extend(i, e), this.each(function () {
                        function i(t, e) {
                            t.hasClass("center-align") ? t.velocity({opacity: 0, translateY: -100}, {
                                duration: e,
                                queue: !1
                            }) : t.hasClass("right-align") ? t.velocity({opacity: 0, translateX: 100}, {
                                duration: e,
                                queue: !1
                            }) : t.hasClass("left-align") && t.velocity({opacity: 0, translateX: -100}, {
                                duration: e,
                                queue: !1
                            })
                        }

                        function n(t) {
                            t >= c.length ? t = 0 : t < 0 && (t = c.length - 1), (u = l.find(".active").index()) != t && (o = c.eq(u), $caption = o.find(".caption"), o.removeClass("active"), o.velocity({opacity: 0}, {
                                duration: e.transition,
                                queue: !1,
                                easing: "easeOutQuad",
                                complete: function () {
                                    c.not(".active").velocity({opacity: 0, translateX: 0, translateY: 0}, {
                                        duration: 0,
                                        queue: !1
                                    })
                                }
                            }), i($caption, e.transition), e.indicators && a.eq(u).removeClass("active"), c.eq(t).velocity({opacity: 1}, {
                                duration: e.transition,
                                queue: !1,
                                easing: "easeOutQuad"
                            }), c.eq(t).find(".caption").velocity({
                                opacity: 1,
                                translateX: 0,
                                translateY: 0
                            }, {
                                duration: e.transition,
                                delay: e.transition,
                                queue: !1,
                                easing: "easeOutQuad"
                            }), c.eq(t).addClass("active"), e.indicators && a.eq(t).addClass("active"))
                        }

                        var o, a, r, s = t(this), l = s.find("ul.slides").first(), c = l.find("> li"),
                            u = l.find(".active").index();
                        -1 != u && (o = c.eq(u)), s.hasClass("fullscreen") || (e.indicators ? s.height(e.height + 40) : s.height(e.height), l.height(e.height)), c.find(".caption").each(function () {
                            i(t(this), 0)
                        }), c.find("img").each(function () {
                            var e = "data:image/gif;base64,R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==";
                            t(this).attr("src") !== e && (t(this).css("background-image", 'url("' + t(this).attr("src") + '")'), t(this).attr("src", e))
                        }), e.indicators && (a = t('<ul class="indicators"></ul>'), c.each(function (i) {
                            var o = t('<li class="indicator-item"></li>');
                            o.click(function () {
                                n(l.parent().find(t(this)).index()), clearInterval(r), r = setInterval(function () {
                                    u = l.find(".active").index(), c.length == u + 1 ? u = 0 : u += 1, n(u)
                                }, e.transition + e.interval)
                            }), a.append(o)
                        }), s.append(a), a = s.find("ul.indicators").find("li.indicator-item")), o ? o.show() : (c.first().addClass("active").velocity({opacity: 1}, {
                            duration: e.transition,
                            queue: !1,
                            easing: "easeOutQuad"
                        }), u = 0, o = c.eq(u), e.indicators && a.eq(u).addClass("active")), o.find("img").each(function () {
                            o.find(".caption").velocity({opacity: 1, translateX: 0, translateY: 0}, {
                                duration: e.transition,
                                queue: !1,
                                easing: "easeOutQuad"
                            })
                        }), r = setInterval(function () {
                            n((u = l.find(".active").index()) + 1)
                        }, e.transition + e.interval);
                        var d = !1, p = !1, h = !1;
                        s.hammer({prevent_default: !1}).on("pan", function (t) {
                            if ("touch" === t.gesture.pointerType) {
                                clearInterval(r);
                                var e = t.gesture.direction, i = t.gesture.deltaX, n = t.gesture.velocityX,
                                    o = t.gesture.velocityY;
                                $curr_slide = l.find(".active"), Math.abs(n) > Math.abs(o) && $curr_slide.velocity({translateX: i}, {
                                    duration: 50,
                                    queue: !1,
                                    easing: "easeOutQuad"
                                }), 4 === e && (i > s.innerWidth() / 2 || n < -.65) ? h = !0 : 2 === e && (i < -1 * s.innerWidth() / 2 || n > .65) && (p = !0);
                                var a;
                                p && (0 === (a = $curr_slide.next()).length && (a = c.first()), a.velocity({opacity: 1}, {
                                    duration: 300,
                                    queue: !1,
                                    easing: "easeOutQuad"
                                })), h && (0 === (a = $curr_slide.prev()).length && (a = c.last()), a.velocity({opacity: 1}, {
                                    duration: 300,
                                    queue: !1,
                                    easing: "easeOutQuad"
                                }))
                            }
                        }).on("panend", function (t) {
                            "touch" === t.gesture.pointerType && ($curr_slide = l.find(".active"), d = !1, curr_index = l.find(".active").index(), !h && !p || c.length <= 1 ? $curr_slide.velocity({translateX: 0}, {
                                duration: 300,
                                queue: !1,
                                easing: "easeOutQuad"
                            }) : p ? (n(curr_index + 1), $curr_slide.velocity({translateX: -1 * s.innerWidth()}, {
                                duration: 300,
                                queue: !1,
                                easing: "easeOutQuad",
                                complete: function () {
                                    $curr_slide.velocity({opacity: 0, translateX: 0}, {duration: 0, queue: !1})
                                }
                            })) : h && (n(curr_index - 1), $curr_slide.velocity({translateX: s.innerWidth()}, {
                                duration: 300,
                                queue: !1,
                                easing: "easeOutQuad",
                                complete: function () {
                                    $curr_slide.velocity({opacity: 0, translateX: 0}, {duration: 0, queue: !1})
                                }
                            })), p = !1, h = !1, clearInterval(r), r = setInterval(function () {
                                u = l.find(".active").index(), c.length == u + 1 ? u = 0 : u += 1, n(u)
                            }, e.transition + e.interval))
                        }), s.on("sliderPause", function () {
                            clearInterval(r)
                        }), s.on("sliderStart", function () {
                            clearInterval(r), r = setInterval(function () {
                                u = l.find(".active").index(), c.length == u + 1 ? u = 0 : u += 1, n(u)
                            }, e.transition + e.interval)
                        }), s.on("sliderNext", function () {
                            n((u = l.find(".active").index()) + 1)
                        }), s.on("sliderPrev", function () {
                            n((u = l.find(".active").index()) - 1)
                        })
                    })
                }, pause: function () {
                    t(this).trigger("sliderPause")
                }, start: function () {
                    t(this).trigger("sliderStart")
                }, next: function () {
                    t(this).trigger("sliderNext")
                }, prev: function () {
                    t(this).trigger("sliderPrev")
                }
            };
            t.fn.slider = function (i) {
                return e[i] ? e[i].apply(this, Array.prototype.slice.call(arguments, 1)) : "object" != typeof i && i ? void t.error("Method " + i + " does not exist on jQuery.tooltip") : e.init.apply(this, arguments)
            }
        }(jQuery), function (t) {
            t(document).ready(function () {
                t(document).on("click.card", ".card", function (e) {
                    if (t(this).find("> .card-reveal").length) {
                        var i = t(e.target).closest(".card");
                        void 0 === i.data("initialOverflow") && i.data("initialOverflow", void 0 === i.css("overflow") ? "" : i.css("overflow")), t(e.target).is(t(".card-reveal .card-title")) || t(e.target).is(t(".card-reveal .card-title i")) ? t(this).find(".card-reveal").velocity({translateY: 0}, {
                            duration: 225,
                            queue: !1,
                            easing: "easeInOutQuad",
                            complete: function () {
                                t(this).css({display: "none"}), i.css("overflow", i.data("initialOverflow"))
                            }
                        }) : (t(e.target).is(t(".card .activator")) || t(e.target).is(t(".card .activator i"))) && (i.css("overflow", "hidden"), t(this).find(".card-reveal").css({display: "block"}).velocity("stop", !1).velocity({translateY: "-100%"}, {
                            duration: 300,
                            queue: !1,
                            easing: "easeInOutQuad"
                        }))
                    }
                })
            })
        }(jQuery), function (t) {
            var e = {data: [], placeholder: "", secondaryPlaceholder: "", autocompleteOptions: {}};
            t(document).ready(function () {
                t(document).on("click", ".chip .close", function (e) {
                    t(this).closest(".chips").attr("data-initialized") || t(this).closest(".chip").remove()
                })
            }), t.fn.material_chip = function (i) {
                var n = this;
                if (this.$el = t(this), this.$document = t(document), this.SELS = {
                        CHIPS: ".chips",
                        CHIP: ".chip",
                        INPUT: "input",
                        DELETE: ".material-icons",
                        SELECTED_CHIP: ".selected"
                    }, "data" === i) return this.$el.data("chips");
                var o = t.extend({}, e, i);
                n.hasAutocomplete = !t.isEmptyObject(o.autocompleteOptions.data), this.init = function () {
                    var e = 0;
                    n.$el.each(function () {
                        var i = t(this), a = Materialize.guid();
                        n.chipId = a, o.data && o.data instanceof Array || (o.data = []), i.data("chips", o.data), i.attr("data-index", e), i.attr("data-initialized", !0), i.hasClass(n.SELS.CHIPS) || i.addClass("chips"), n.chips(i, a), e++
                    })
                }, this.handleEvents = function () {
                    var e = n.SELS;
                    n.$document.off("click.chips-focus", e.CHIPS).on("click.chips-focus", e.CHIPS, function (i) {
                        t(i.target).find(e.INPUT).focus()
                    }), n.$document.off("click.chips-select", e.CHIP).on("click.chips-select", e.CHIP, function (i) {
                        var o = t(i.target);
                        if (o.length) {
                            var a = o.hasClass("selected"), r = o.closest(e.CHIPS);
                            t(e.CHIP).removeClass("selected"), a || n.selectChip(o.index(), r)
                        }
                    }), n.$document.off("keydown.chips").on("keydown.chips", function (i) {
                        if (!t(i.target).is("input, textarea")) {
                            var o, a = n.$document.find(e.CHIP + e.SELECTED_CHIP), r = a.closest(e.CHIPS),
                                s = a.siblings(e.CHIP).length;
                            if (a.length) if (8 === i.which || 46 === i.which) {
                                i.preventDefault(), o = a.index(), n.deleteChip(o, r);
                                var l = null;
                                o + 1 < s ? l = o : o !== s && o + 1 !== s || (l = s - 1), l < 0 && (l = null), null !== l && n.selectChip(l, r), s || r.find("input").focus()
                            } else if (37 === i.which) {
                                if ((o = a.index() - 1) < 0) return;
                                t(e.CHIP).removeClass("selected"), n.selectChip(o, r)
                            } else if (39 === i.which) {
                                if (o = a.index() + 1, t(e.CHIP).removeClass("selected"), o > s) return void r.find("input").focus();
                                n.selectChip(o, r)
                            }
                        }
                    }), n.$document.off("focusin.chips", e.CHIPS + " " + e.INPUT).on("focusin.chips", e.CHIPS + " " + e.INPUT, function (i) {
                        var n = t(i.target).closest(e.CHIPS);
                        n.addClass("focus"), n.siblings("label, .prefix").addClass("active"), t(e.CHIP).removeClass("selected")
                    }), n.$document.off("focusout.chips", e.CHIPS + " " + e.INPUT).on("focusout.chips", e.CHIPS + " " + e.INPUT, function (i) {
                        var n = t(i.target).closest(e.CHIPS);
                        n.removeClass("focus"), void 0 !== n.data("chips") && n.data("chips").length || n.siblings("label").removeClass("active"), n.siblings(".prefix").removeClass("active")
                    }), n.$document.off("keydown.chips-add", e.CHIPS + " " + e.INPUT).on("keydown.chips-add", e.CHIPS + " " + e.INPUT, function (i) {
                        var o = t(i.target), a = o.closest(e.CHIPS), r = a.children(e.CHIP).length;
                        if (13 === i.which) {
                            if (n.hasAutocomplete && a.find(".autocomplete-content.dropdown-content").length && a.find(".autocomplete-content.dropdown-content").children().length) return;
                            return i.preventDefault(), n.addChip({tag: o.val()}, a), void o.val("")
                        }
                        if ((8 === i.keyCode || 37 === i.keyCode) && "" === o.val() && r) return i.preventDefault(), n.selectChip(r - 1, a), void o.blur()
                    }), n.$document.off("click.chips-delete", e.CHIPS + " " + e.DELETE).on("click.chips-delete", e.CHIPS + " " + e.DELETE, function (i) {
                        var o = t(i.target), a = o.closest(e.CHIPS), r = o.closest(e.CHIP);
                        i.stopPropagation(), n.deleteChip(r.index(), a), a.find("input").focus()
                    })
                }, this.chips = function (e, i) {
                    e.empty(), e.data("chips").forEach(function (t) {
                        e.append(n.renderChip(t))
                    }), e.append(t('<input id="' + i + '" class="input" placeholder="">')), n.setPlaceholder(e);
                    var a = e.next("label");
                    a.length && (a.attr("for", i), void 0 !== e.data("chips") && e.data("chips").length && a.addClass("active"));
                    var r = t("#" + i);
                    n.hasAutocomplete && (o.autocompleteOptions.onAutocomplete = function (t) {
                        n.addChip({tag: t}, e), r.val(""), r.focus()
                    }, r.autocomplete(o.autocompleteOptions))
                }, this.renderChip = function (e) {
                    if (e.tag) {
                        var i = t('<div class="chip"></div>');
                        return i.text(e.tag), e.image && i.prepend(t("<img />").attr("src", e.image)), i.append(t('<i class="material-icons close">close</i>')), i
                    }
                }, this.setPlaceholder = function (t) {
                    void 0 !== t.data("chips") && !t.data("chips").length && o.placeholder ? t.find("input").prop("placeholder", o.placeholder) : (void 0 === t.data("chips") || t.data("chips").length) && o.secondaryPlaceholder && t.find("input").prop("placeholder", o.secondaryPlaceholder)
                }, this.isValid = function (t, e) {
                    for (var i = t.data("chips"), n = !1, o = 0; o < i.length; o++) if (i[o].tag === e.tag) return void(n = !0);
                    return "" !== e.tag && !n
                }, this.addChip = function (t, e) {
                    if (n.isValid(e, t)) {
                        for (var i = n.renderChip(t), o = [], a = e.data("chips"), r = 0; r < a.length; r++) o.push(a[r]);
                        o.push(t), e.data("chips", o), i.insertBefore(e.find("input")), e.trigger("chip.add", t), n.setPlaceholder(e)
                    }
                }, this.deleteChip = function (t, e) {
                    var i = e.data("chips")[t];
                    e.find(".chip").eq(t).remove();
                    for (var o = [], a = e.data("chips"), r = 0; r < a.length; r++) r !== t && o.push(a[r]);
                    e.data("chips", o), e.trigger("chip.delete", i), n.setPlaceholder(e)
                }, this.selectChip = function (t, e) {
                    var i = e.find(".chip").eq(t);
                    i && !1 === i.hasClass("selected") && (i.addClass("selected"), e.trigger("chip.select", e.data("chips")[t]))
                }, this.getChipsElement = function (t, e) {
                    return e.eq(t)
                }, this.init(), this.handleEvents()
            }
        }(jQuery), function (t) {
            t.fn.pushpin = function (e) {
                var i = {top: 0, bottom: 1 / 0, offset: 0};
                return "remove" === e ? (this.each(function () {
                    (id = t(this).data("pushpin-id")) && (t(window).off("scroll." + id), t(this).removeData("pushpin-id").removeClass("pin-top pinned pin-bottom").removeAttr("style"))
                }), !1) : (e = t.extend(i, e), $index = 0, this.each(function () {
                    function i(t) {
                        t.removeClass("pin-top"), t.removeClass("pinned"), t.removeClass("pin-bottom")
                    }

                    function n(n, o) {
                        n.each(function () {
                            e.top <= o && e.bottom >= o && !t(this).hasClass("pinned") && (i(t(this)), t(this).css("top", e.offset), t(this).addClass("pinned")), o < e.top && !t(this).hasClass("pin-top") && (i(t(this)), t(this).css("top", 0), t(this).addClass("pin-top")), o > e.bottom && !t(this).hasClass("pin-bottom") && (i(t(this)), t(this).addClass("pin-bottom"), t(this).css("top", e.bottom - r))
                        })
                    }

                    var o = Materialize.guid(), a = t(this), r = t(this).offset().top;
                    t(this).data("pushpin-id", o), n(a, t(window).scrollTop()), t(window).on("scroll." + o, function () {
                        var i = t(window).scrollTop() + e.offset;
                        n(a, i)
                    })
                }))
            }
        }(jQuery), function (t) {
            t(document).ready(function () {
                t.fn.reverse = [].reverse, t(document).on("mouseenter.fixedActionBtn", ".fixed-action-btn:not(.click-to-toggle):not(.toolbar)", function (i) {
                    var n = t(this);
                    e(n)
                }), t(document).on("mouseleave.fixedActionBtn", ".fixed-action-btn:not(.click-to-toggle):not(.toolbar)", function (e) {
                    var n = t(this);
                    i(n)
                }), t(document).on("click.fabClickToggle", ".fixed-action-btn.click-to-toggle > a", function (n) {
                    var o = t(this).parent();
                    o.hasClass("active") ? i(o) : e(o)
                }), t(document).on("click.fabToolbar", ".fixed-action-btn.toolbar > a", function (e) {
                    var i = t(this).parent();
                    n(i)
                })
            }), t.fn.extend({
                openFAB: function () {
                    e(t(this))
                }, closeFAB: function () {
                    i(t(this))
                }, openToolbar: function () {
                    n(t(this))
                }, closeToolbar: function () {
                    o(t(this))
                }
            });
            var e = function (e) {
                var i = e;
                if (!1 === i.hasClass("active")) {
                    var n, o;
                    !0 === i.hasClass("horizontal") ? o = 40 : n = 40, i.addClass("active"), i.find("ul .btn-floating").velocity({
                        scaleY: ".4",
                        scaleX: ".4",
                        translateY: n + "px",
                        translateX: o + "px"
                    }, {duration: 0});
                    var a = 0;
                    i.find("ul .btn-floating").reverse().each(function () {
                        t(this).velocity({
                            opacity: "1",
                            scaleX: "1",
                            scaleY: "1",
                            translateY: "0",
                            translateX: "0"
                        }, {duration: 80, delay: a}), a += 40
                    })
                }
            }, i = function (t) {
                var e, i, n = t;
                !0 === n.hasClass("horizontal") ? i = 40 : e = 40, n.removeClass("active");
                n.find("ul .btn-floating").velocity("stop", !0), n.find("ul .btn-floating").velocity({
                    opacity: "0",
                    scaleX: ".4",
                    scaleY: ".4",
                    translateY: e + "px",
                    translateX: i + "px"
                }, {duration: 80})
            }, n = function (e) {
                if ("true" !== e.attr("data-open")) {
                    var i, n, a, r = window.innerWidth, s = window.innerHeight, l = e[0].getBoundingClientRect(),
                        c = e.find("> a").first(), u = e.find("> ul").first(),
                        d = t('<div class="fab-backdrop"></div>'), p = c.css("background-color");
                    c.append(d), i = l.left - r / 2 + l.width / 2, n = s - l.bottom, a = r / d.width(), e.attr("data-origin-bottom", l.bottom), e.attr("data-origin-left", l.left), e.attr("data-origin-width", l.width), e.addClass("active"), e.attr("data-open", !0), e.css({
                        "text-align": "center",
                        width: "100%",
                        bottom: 0,
                        left: 0,
                        transform: "translateX(" + i + "px)",
                        transition: "none"
                    }), c.css({
                        transform: "translateY(" + -n + "px)",
                        transition: "none"
                    }), d.css({"background-color": p}), setTimeout(function () {
                        e.css({
                            transform: "",
                            transition: "transform .2s cubic-bezier(0.550, 0.085, 0.680, 0.530), background-color 0s linear .2s"
                        }), c.css({
                            overflow: "visible",
                            transform: "",
                            transition: "transform .2s"
                        }), setTimeout(function () {
                            e.css({overflow: "hidden", "background-color": p}), d.css({
                                transform: "scale(" + a + ")",
                                transition: "transform .2s cubic-bezier(0.550, 0.055, 0.675, 0.190)"
                            }), u.find("> li > a").css({opacity: 1}), t(window).on("scroll.fabToolbarClose", function () {
                                o(e), t(window).off("scroll.fabToolbarClose"), t(document).off("click.fabToolbarClose")
                            }), t(document).on("click.fabToolbarClose", function (i) {
                                t(i.target).closest(u).length || (o(e), t(window).off("scroll.fabToolbarClose"), t(document).off("click.fabToolbarClose"))
                            })
                        }, 100)
                    }, 0)
                }
            }, o = function (t) {
                if ("true" === t.attr("data-open")) {
                    var e, i, n = window.innerWidth, o = window.innerHeight, a = t.attr("data-origin-width"),
                        r = t.attr("data-origin-bottom"), s = t.attr("data-origin-left"),
                        l = t.find("> .btn-floating").first(), c = t.find("> ul").first(), u = t.find(".fab-backdrop"),
                        d = l.css("background-color");
                    e = s - n / 2 + a / 2, i = o - r, n / u.width(), t.removeClass("active"), t.attr("data-open", !1), t.css({
                        "background-color": "transparent",
                        transition: "none"
                    }), l.css({transition: "none"}), u.css({
                        transform: "scale(0)",
                        "background-color": d
                    }), c.find("> li > a").css({opacity: ""}), setTimeout(function () {
                        u.remove(), t.css({
                            "text-align": "",
                            width: "",
                            bottom: "",
                            left: "",
                            overflow: "",
                            "background-color": "",
                            transform: "translate3d(" + -e + "px,0,0)"
                        }), l.css({overflow: "", transform: "translate3d(0," + i + "px,0)"}), setTimeout(function () {
                            t.css({
                                transform: "translate3d(0,0,0)",
                                transition: "transform .2s"
                            }), l.css({
                                transform: "translate3d(0,0,0)",
                                transition: "transform .2s cubic-bezier(0.550, 0.055, 0.675, 0.190)"
                            })
                        }, 20)
                    }, 200)
                }
            }
        }(jQuery), function (t) {
            Materialize.fadeInImage = function (e) {
                var i;
                if ("string" == typeof e) i = t(e); else {
                    if ("object" != typeof e) return;
                    i = e
                }
                i.css({opacity: 0}), t(i).velocity({opacity: 1}, {
                    duration: 650,
                    queue: !1,
                    easing: "easeOutSine"
                }), t(i).velocity({opacity: 1}, {
                    duration: 1300, queue: !1, easing: "swing", step: function (e, i) {
                        i.start = 100;
                        var n = e / 100, o = 150 - (100 - e) / 1.75;
                        o < 100 && (o = 100), e >= 0 && t(this).css({
                            "-webkit-filter": "grayscale(" + n + ")brightness(" + o + "%)",
                            filter: "grayscale(" + n + ")brightness(" + o + "%)"
                        })
                    }
                })
            }, Materialize.showStaggeredList = function (e) {
                var i;
                if ("string" == typeof e) i = t(e); else {
                    if ("object" != typeof e) return;
                    i = e
                }
                var n = 0;
                i.find("li").velocity({translateX: "-100px"}, {duration: 0}), i.find("li").each(function () {
                    t(this).velocity({opacity: "1", translateX: "0"}, {
                        duration: 800,
                        delay: n,
                        easing: [60, 10]
                    }), n += 120
                })
            }, t(document).ready(function () {
                var e = !1, i = !1;
                t(".dismissable").each(function () {
                    t(this).hammer({prevent_default: !1}).on("pan", function (n) {
                        if ("touch" === n.gesture.pointerType) {
                            var o = t(this), a = n.gesture.direction, r = n.gesture.deltaX, s = n.gesture.velocityX;
                            o.velocity({translateX: r}, {
                                duration: 50,
                                queue: !1,
                                easing: "easeOutQuad"
                            }), 4 === a && (r > o.innerWidth() / 2 || s < -.75) && (e = !0), 2 === a && (r < -1 * o.innerWidth() / 2 || s > .75) && (i = !0)
                        }
                    }).on("panend", function (n) {
                        if (Math.abs(n.gesture.deltaX) < t(this).innerWidth() / 2 && (i = !1, e = !1), "touch" === n.gesture.pointerType) {
                            var o = t(this);
                            if (e || i) {
                                var a;
                                a = e ? o.innerWidth() : -1 * o.innerWidth(), o.velocity({translateX: a}, {
                                    duration: 100,
                                    queue: !1,
                                    easing: "easeOutQuad",
                                    complete: function () {
                                        o.css("border", "none"), o.velocity({height: 0, padding: 0}, {
                                            duration: 200,
                                            queue: !1,
                                            easing: "easeOutQuad",
                                            complete: function () {
                                                o.remove()
                                            }
                                        })
                                    }
                                })
                            } else o.velocity({translateX: 0}, {duration: 100, queue: !1, easing: "easeOutQuad"});
                            e = !1, i = !1
                        }
                    })
                })
            })
        }(jQuery), function (t) {
            var e = !1;
            Materialize.scrollFire = function (t) {
                var i = function () {
                    for (var e = window.pageYOffset + window.innerHeight, i = 0; i < t.length; i++) {
                        var n = t[i], o = n.selector, a = n.offset, r = n.callback, s = document.querySelector(o);
                        null !== s && e > s.getBoundingClientRect().top + window.pageYOffset + a && !0 !== n.done && ("function" == typeof r ? r.call(this, s) : "string" == typeof r && new Function(r)(s), n.done = !0)
                    }
                }, n = Materialize.throttle(function () {
                    i()
                }, t.throttle || 100);
                e || (window.addEventListener("scroll", n), window.addEventListener("resize", n), e = !0), setTimeout(n, 0)
            }
        }(), function (t) {
            Materialize.Picker = t(jQuery)
        }(function (t) {
            function e(a, s, u, d) {
                function p() {
                    return e._.node("div", e._.node("div", e._.node("div", e._.node("div", T.component.nodes(b.open), k.box), k.wrap), k.frame), k.holder)
                }

                function h() {
                    x.data(s, T).addClass(k.input).attr("tabindex", -1).val(x.data("value") ? T.get("select", w.format) : a.value), w.editable || x.on("focus." + b.id + " click." + b.id, function (t) {
                        t.preventDefault(), T.$root.eq(0).focus()
                    }).on("keydown." + b.id, m), o(a, {haspopup: !0, expanded: !1, readonly: !1, owns: a.id + "_root"})
                }

                function f() {
                    T.$root.on({
                        keydown: m, focusin: function (t) {
                            T.$root.removeClass(k.focused), t.stopPropagation()
                        }, "mousedown click": function (e) {
                            var i = e.target;
                            i != T.$root.children()[0] && (e.stopPropagation(), "mousedown" != e.type || t(i).is("input, select, textarea, button, option") || (e.preventDefault(), T.$root.eq(0).focus()))
                        }
                    }).on({
                        focus: function () {
                            x.addClass(k.target)
                        }, blur: function () {
                            x.removeClass(k.target)
                        }
                    }).on("focus.toOpen", g).on("click", "[data-pick], [data-nav], [data-clear], [data-close]", function () {
                        var e = t(this), i = e.data(), n = e.hasClass(k.navDisabled) || e.hasClass(k.disabled), o = r();
                        o = o && (o.type || o.href), (n || o && !t.contains(T.$root[0], o)) && T.$root.eq(0).focus(), !n && i.nav ? T.set("highlight", T.component.item.highlight, {nav: i.nav}) : !n && "pick" in i ? (T.set("select", i.pick), w.closeOnSelect && T.close(!0)) : i.clear ? (T.clear(), w.closeOnSelect && T.close(!0)) : i.close && T.close(!0)
                    }), o(T.$root[0], "hidden", !0)
                }

                function v() {
                    var e;
                    !0 === w.hiddenName ? (e = a.name, a.name = "") : e = (e = ["string" == typeof w.hiddenPrefix ? w.hiddenPrefix : "", "string" == typeof w.hiddenSuffix ? w.hiddenSuffix : "_submit"])[0] + a.name + e[1], T._hidden = t('<input type=hidden name="' + e + '"' + (x.data("value") || a.value ? ' value="' + T.get("select", w.formatSubmit) + '"' : "") + ">")[0], x.on("change." + b.id, function () {
                        T._hidden.value = a.value ? T.get("select", w.formatSubmit) : ""
                    }), w.container ? t(w.container).append(T._hidden) : x.before(T._hidden)
                }

                function m(t) {
                    var e = t.keyCode, i = /^(8|46)$/.test(e);
                    if (27 == e) return T.close(), !1;
                    (32 == e || i || !b.open && T.component.key[e]) && (t.preventDefault(), t.stopPropagation(), i ? T.clear().close() : T.open())
                }

                function g(t) {
                    t.stopPropagation(), "focus" == t.type && T.$root.addClass(k.focused), T.open()
                }

                if (!a) return e;
                var y = !1, b = {id: a.id || "P" + Math.abs(~~(Math.random() * new Date))},
                    w = u ? t.extend(!0, {}, u.defaults, d) : d || {}, k = t.extend({}, e.klasses(), w.klass), x = t(a),
                    C = function () {
                        return this.start()
                    }, T = C.prototype = {
                        constructor: C, $node: x, start: function () {
                            return b && b.start ? T : (b.methods = {}, b.start = !0, b.open = !1, b.type = a.type, a.autofocus = a == r(), a.readOnly = !w.editable, a.id = a.id || b.id, "text" != a.type && (a.type = "text"), T.component = new u(T, w), T.$root = t(e._.node("div", p(), k.picker, 'id="' + a.id + '_root" tabindex="0"')), f(), w.formatSubmit && v(), h(), w.container ? t(w.container).append(T.$root) : x.before(T.$root), T.on({
                                start: T.component.onStart,
                                render: T.component.onRender,
                                stop: T.component.onStop,
                                open: T.component.onOpen,
                                close: T.component.onClose,
                                set: T.component.onSet
                            }).on({
                                start: w.onStart,
                                render: w.onRender,
                                stop: w.onStop,
                                open: w.onOpen,
                                close: w.onClose,
                                set: w.onSet
                            }), y = i(T.$root.children()[0]), a.autofocus && T.open(), T.trigger("start").trigger("render"))
                        }, render: function (t) {
                            return t ? T.$root.html(p()) : T.$root.find("." + k.box).html(T.component.nodes(b.open)), T.trigger("render")
                        }, stop: function () {
                            return b.start ? (T.close(), T._hidden && T._hidden.parentNode.removeChild(T._hidden), T.$root.remove(), x.removeClass(k.input).removeData(s), setTimeout(function () {
                                x.off("." + b.id)
                            }, 0), a.type = b.type, a.readOnly = !1, T.trigger("stop"), b.methods = {}, b.start = !1, T) : T
                        }, open: function (i) {
                            return b.open ? T : (x.addClass(k.active), o(a, "expanded", !0), setTimeout(function () {
                                T.$root.addClass(k.opened), o(T.$root[0], "hidden", !1)
                            }, 0), !1 !== i && (b.open = !0, y && c.css("overflow", "hidden").css("padding-right", "+=" + n()), T.$root.eq(0).focus(), l.on("click." + b.id + " focusin." + b.id, function (t) {
                                var e = t.target;
                                e != a && e != document && 3 != t.which && T.close(e === T.$root.children()[0])
                            }).on("keydown." + b.id, function (i) {
                                var n = i.keyCode, o = T.component.key[n], a = i.target;
                                27 == n ? T.close(!0) : a != T.$root[0] || !o && 13 != n ? t.contains(T.$root[0], a) && 13 == n && (i.preventDefault(), a.click()) : (i.preventDefault(), o ? e._.trigger(T.component.key.go, T, [e._.trigger(o)]) : T.$root.find("." + k.highlighted).hasClass(k.disabled) || (T.set("select", T.component.item.highlight), w.closeOnSelect && T.close(!0)))
                            })), T.trigger("open"))
                        }, close: function (t) {
                            return t && (T.$root.off("focus.toOpen").eq(0).focus(), setTimeout(function () {
                                T.$root.on("focus.toOpen", g)
                            }, 0)), x.removeClass(k.active), o(a, "expanded", !1), setTimeout(function () {
                                T.$root.removeClass(k.opened + " " + k.focused), o(T.$root[0], "hidden", !0)
                            }, 0), b.open ? (b.open = !1, y && c.css("overflow", "").css("padding-right", "-=" + n()), l.off("." + b.id), T.trigger("close")) : T
                        }, clear: function (t) {
                            return T.set("clear", null, t)
                        }, set: function (e, i, n) {
                            var o, a, r = t.isPlainObject(e), s = r ? e : {};
                            if (n = r && t.isPlainObject(i) ? i : n || {}, e) {
                                r || (s[e] = i);
                                for (o in s) a = s[o], o in T.component.item && (void 0 === a && (a = null), T.component.set(o, a, n)), "select" != o && "clear" != o || x.val("clear" == o ? "" : T.get(o, w.format)).trigger("change");
                                T.render()
                            }
                            return n.muted ? T : T.trigger("set", s)
                        }, get: function (t, i) {
                            if (t = t || "value", null != b[t]) return b[t];
                            if ("valueSubmit" == t) {
                                if (T._hidden) return T._hidden.value;
                                t = "value"
                            }
                            if ("value" == t) return a.value;
                            if (t in T.component.item) {
                                if ("string" == typeof i) {
                                    var n = T.component.get(t);
                                    return n ? e._.trigger(T.component.formats.toString, T.component, [i, n]) : ""
                                }
                                return T.component.get(t)
                            }
                        }, on: function (e, i, n) {
                            var o, a, r = t.isPlainObject(e), s = r ? e : {};
                            if (e) {
                                r || (s[e] = i);
                                for (o in s) a = s[o], n && (o = "_" + o), b.methods[o] = b.methods[o] || [], b.methods[o].push(a)
                            }
                            return T
                        }, off: function () {
                            var t, e, i = arguments;
                            for (t = 0, namesCount = i.length; t < namesCount; t += 1) (e = i[t]) in b.methods && delete b.methods[e];
                            return T
                        }, trigger: function (t, i) {
                            var n = function (t) {
                                var n = b.methods[t];
                                n && n.map(function (t) {
                                    e._.trigger(t, T, [i])
                                })
                            };
                            return n("_" + t), n(t), T
                        }
                    };
                return new C
            }

            function i(t) {
                var e;
                return t.currentStyle ? e = t.currentStyle.position : window.getComputedStyle && (e = getComputedStyle(t).position), "fixed" == e
            }

            function n() {
                if (c.height() <= s.height()) return 0;
                var e = t('<div style="visibility:hidden;width:100px" />').appendTo("body"), i = e[0].offsetWidth;
                e.css("overflow", "scroll");
                var n = t('<div style="width:100%" />').appendTo(e)[0].offsetWidth;
                return e.remove(), i - n
            }

            function o(e, i, n) {
                if (t.isPlainObject(i)) for (var o in i) a(e, o, i[o]); else a(e, i, n)
            }

            function a(t, e, i) {
                t.setAttribute(("role" == e ? "" : "aria-") + e, i)
            }

            function r() {
                try {
                    return document.activeElement
                } catch (t) {
                }
            }

            var s = t(window), l = t(document), c = t(document.documentElement);
            return e.klasses = function (t) {
                return t = t || "picker", {
                    picker: t,
                    opened: t + "--opened",
                    focused: t + "--focused",
                    input: t + "__input",
                    active: t + "__input--active",
                    target: t + "__input--target",
                    holder: t + "__holder",
                    frame: t + "__frame",
                    wrap: t + "__wrap",
                    box: t + "__box"
                }
            }, e._ = {
                group: function (t) {
                    for (var i, n = "", o = e._.trigger(t.min, t); o <= e._.trigger(t.max, t, [o]); o += t.i) i = e._.trigger(t.item, t, [o]), n += e._.node(t.node, i[0], i[1], i[2]);
                    return n
                }, node: function (e, i, n, o) {
                    return i ? (i = t.isArray(i) ? i.join("") : i, n = n ? ' class="' + n + '"' : "", o = o ? " " + o : "", "<" + e + n + o + ">" + i + "</" + e + ">") : ""
                }, lead: function (t) {
                    return (t < 10 ? "0" : "") + t
                }, trigger: function (t, e, i) {
                    return "function" == typeof t ? t.apply(e, i || []) : t
                }, digits: function (t) {
                    return /\d/.test(t[1]) ? 2 : 1
                }, isDate: function (t) {
                    return {}.toString.call(t).indexOf("Date") > -1 && this.isInteger(t.getDate())
                }, isInteger: function (t) {
                    return {}.toString.call(t).indexOf("Number") > -1 && t % 1 == 0
                }, ariaAttr: function (e, i) {
                    t.isPlainObject(e) || (e = {attribute: i}), i = "";
                    for (var n in e) {
                        var o = ("role" == n ? "" : "aria-") + n;
                        i += null == e[n] ? "" : o + '="' + e[n] + '"'
                    }
                    return i
                }
            }, e.extend = function (i, n) {
                t.fn[i] = function (o, a) {
                    var r = this.data(i);
                    return "picker" == o ? r : r && "string" == typeof o ? e._.trigger(r[o], r, [a]) : this.each(function () {
                        t(this).data(i) || new e(this, i, n, o)
                    })
                }, t.fn[i].defaults = n.defaults
            }, e
        }), function (t) {
            t(Materialize.Picker, jQuery)
        }(function (t, e) {
            function i(t, e) {
                var i = this, n = t.$node[0], o = n.value, a = t.$node.data("value"), r = a || o,
                    s = a ? e.formatSubmit : e.format, l = function () {
                        return n.currentStyle ? "rtl" == n.currentStyle.direction : "rtl" == getComputedStyle(t.$root[0]).direction
                    };
                i.settings = e, i.$node = t.$node, i.queue = {
                    min: "measure create",
                    max: "measure create",
                    now: "now create",
                    select: "parse create validate",
                    highlight: "parse navigate create validate",
                    view: "parse create validate viewset",
                    disable: "deactivate",
                    enable: "activate"
                }, i.item = {}, i.item.clear = null, i.item.disable = (e.disable || []).slice(0), i.item.enable = -function (t) {
                    return !0 === t[0] ? t.shift() : -1
                }(i.item.disable), i.set("min", e.min).set("max", e.max).set("now"), r ? i.set("select", r, {format: s}) : i.set("select", null).set("highlight", i.item.now), i.key = {
                    40: 7,
                    38: -7,
                    39: function () {
                        return l() ? -1 : 1
                    },
                    37: function () {
                        return l() ? 1 : -1
                    },
                    go: function (t) {
                        var e = i.item.highlight, n = new Date(e.year, e.month, e.date + t);
                        i.set("highlight", n, {interval: t}), this.render()
                    }
                }, t.on("render", function () {
                    t.$root.find("." + e.klass.selectMonth).on("change", function () {
                        var i = this.value;
                        i && (t.set("highlight", [t.get("view").year, i, t.get("highlight").date]), t.$root.find("." + e.klass.selectMonth).trigger("focus"))
                    }), t.$root.find("." + e.klass.selectYear).on("change", function () {
                        var i = this.value;
                        i && (t.set("highlight", [i, t.get("view").month, t.get("highlight").date]), t.$root.find("." + e.klass.selectYear).trigger("focus"))
                    })
                }, 1).on("open", function () {
                    var n = "";
                    i.disabled(i.get("now")) && (n = ":not(." + e.klass.buttonToday + ")"), t.$root.find("button" + n + ", select").attr("disabled", !1)
                }, 1).on("close", function () {
                    t.$root.find("button, select").attr("disabled", !0)
                }, 1)
            }

            var n = t._;
            i.prototype.set = function (t, e, i) {
                var n = this, o = n.item;
                return null === e ? ("clear" == t && (t = "select"), o[t] = e, n) : (o["enable" == t ? "disable" : "flip" == t ? "enable" : t] = n.queue[t].split(" ").map(function (o) {
                    return e = n[o](t, e, i)
                }).pop(), "select" == t ? n.set("highlight", o.select, i) : "highlight" == t ? n.set("view", o.highlight, i) : t.match(/^(flip|min|max|disable|enable)$/) && (o.select && n.disabled(o.select) && n.set("select", o.select, i), o.highlight && n.disabled(o.highlight) && n.set("highlight", o.highlight, i)), n)
            }, i.prototype.get = function (t) {
                return this.item[t]
            }, i.prototype.create = function (t, i, o) {
                var a, r = this;
                return i = void 0 === i ? t : i, i == -1 / 0 || i == 1 / 0 ? a = i : e.isPlainObject(i) && n.isInteger(i.pick) ? i = i.obj : e.isArray(i) ? (i = new Date(i[0], i[1], i[2]), i = n.isDate(i) ? i : r.create().obj) : i = n.isInteger(i) || n.isDate(i) ? r.normalize(new Date(i), o) : r.now(t, i, o), {
                    year: a || i.getFullYear(),
                    month: a || i.getMonth(),
                    date: a || i.getDate(),
                    day: a || i.getDay(),
                    obj: a || i,
                    pick: a || i.getTime()
                }
            }, i.prototype.createRange = function (t, i) {
                var o = this, a = function (t) {
                    return !0 === t || e.isArray(t) || n.isDate(t) ? o.create(t) : t
                };
                return n.isInteger(t) || (t = a(t)), n.isInteger(i) || (i = a(i)), n.isInteger(t) && e.isPlainObject(i) ? t = [i.year, i.month, i.date + t] : n.isInteger(i) && e.isPlainObject(t) && (i = [t.year, t.month, t.date + i]), {
                    from: a(t),
                    to: a(i)
                }
            }, i.prototype.withinRange = function (t, e) {
                return t = this.createRange(t.from, t.to), e.pick >= t.from.pick && e.pick <= t.to.pick
            }, i.prototype.overlapRanges = function (t, e) {
                var i = this;
                return t = i.createRange(t.from, t.to), e = i.createRange(e.from, e.to), i.withinRange(t, e.from) || i.withinRange(t, e.to) || i.withinRange(e, t.from) || i.withinRange(e, t.to)
            }, i.prototype.now = function (t, e, i) {
                return e = new Date, i && i.rel && e.setDate(e.getDate() + i.rel), this.normalize(e, i)
            }, i.prototype.navigate = function (t, i, n) {
                var o, a, r, s, l = e.isArray(i), c = e.isPlainObject(i), u = this.item.view;
                if (l || c) {
                    for (c ? (a = i.year, r = i.month, s = i.date) : (a = +i[0], r = +i[1], s = +i[2]), n && n.nav && u && u.month !== r && (a = u.year, r = u.month), a = (o = new Date(a, r + (n && n.nav ? n.nav : 0), 1)).getFullYear(), r = o.getMonth(); new Date(a, r, s).getMonth() !== r;) s -= 1;
                    i = [a, r, s]
                }
                return i
            }, i.prototype.normalize = function (t) {
                return t.setHours(0, 0, 0, 0), t
            }, i.prototype.measure = function (t, e) {
                var i = this;
                return e ? "string" == typeof e ? e = i.parse(t, e) : n.isInteger(e) && (e = i.now(t, e, {rel: e})) : e = "min" == t ? -1 / 0 : 1 / 0, e
            }, i.prototype.viewset = function (t, e) {
                return this.create([e.year, e.month, 1])
            }, i.prototype.validate = function (t, i, o) {
                var a, r, s, l, c = this, u = i, d = o && o.interval ? o.interval : 1, p = -1 === c.item.enable,
                    h = c.item.min, f = c.item.max, v = p && c.item.disable.filter(function (t) {
                        if (e.isArray(t)) {
                            var o = c.create(t).pick;
                            o < i.pick ? a = !0 : o > i.pick && (r = !0)
                        }
                        return n.isInteger(t)
                    }).length;
                if ((!o || !o.nav) && (!p && c.disabled(i) || p && c.disabled(i) && (v || a || r) || !p && (i.pick <= h.pick || i.pick >= f.pick))) for (p && !v && (!r && d > 0 || !a && d < 0) && (d *= -1); c.disabled(i) && (Math.abs(d) > 1 && (i.month < u.month || i.month > u.month) && (i = u, d = d > 0 ? 1 : -1), i.pick <= h.pick ? (s = !0, d = 1, i = c.create([h.year, h.month, h.date + (i.pick === h.pick ? 0 : -1)])) : i.pick >= f.pick && (l = !0, d = -1, i = c.create([f.year, f.month, f.date + (i.pick === f.pick ? 0 : 1)])), !s || !l);) i = c.create([i.year, i.month, i.date + d]);
                return i
            }, i.prototype.disabled = function (t) {
                var i = this, o = i.item.disable.filter(function (o) {
                    return n.isInteger(o) ? t.day === (i.settings.firstDay ? o : o - 1) % 7 : e.isArray(o) || n.isDate(o) ? t.pick === i.create(o).pick : e.isPlainObject(o) ? i.withinRange(o, t) : void 0
                });
                return o = o.length && !o.filter(function (t) {
                    return e.isArray(t) && "inverted" == t[3] || e.isPlainObject(t) && t.inverted
                }).length, -1 === i.item.enable ? !o : o || t.pick < i.item.min.pick || t.pick > i.item.max.pick
            }, i.prototype.parse = function (t, e, i) {
                var o = this, a = {};
                return e && "string" == typeof e ? (i && i.format || ((i = i || {}).format = o.settings.format), o.formats.toArray(i.format).map(function (t) {
                    var i = o.formats[t], r = i ? n.trigger(i, o, [e, a]) : t.replace(/^!/, "").length;
                    i && (a[t] = e.substr(0, r)), e = e.substr(r)
                }), [a.yyyy || a.yy, +(a.mm || a.m) - 1, a.dd || a.d]) : e
            }, i.prototype.formats = function () {
                function t(t, e, i) {
                    var n = t.match(/\w+/)[0];
                    return i.mm || i.m || (i.m = e.indexOf(n) + 1), n.length
                }

                function e(t) {
                    return t.match(/\w+/)[0].length
                }

                return {
                    d: function (t, e) {
                        return t ? n.digits(t) : e.date
                    }, dd: function (t, e) {
                        return t ? 2 : n.lead(e.date)
                    }, ddd: function (t, i) {
                        return t ? e(t) : this.settings.weekdaysShort[i.day]
                    }, dddd: function (t, i) {
                        return t ? e(t) : this.settings.weekdaysFull[i.day]
                    }, m: function (t, e) {
                        return t ? n.digits(t) : e.month + 1
                    }, mm: function (t, e) {
                        return t ? 2 : n.lead(e.month + 1)
                    }, mmm: function (e, i) {
                        var n = this.settings.monthsShort;
                        return e ? t(e, n, i) : n[i.month]
                    }, mmmm: function (e, i) {
                        var n = this.settings.monthsFull;
                        return e ? t(e, n, i) : n[i.month]
                    }, yy: function (t, e) {
                        return t ? 2 : ("" + e.year).slice(2)
                    }, yyyy: function (t, e) {
                        return t ? 4 : e.year
                    }, toArray: function (t) {
                        return t.split(/(d{1,4}|m{1,4}|y{4}|yy|!.)/g)
                    }, toString: function (t, e) {
                        var i = this;
                        return i.formats.toArray(t).map(function (t) {
                            return n.trigger(i.formats[t], i, [0, e]) || t.replace(/^!/, "")
                        }).join("")
                    }
                }
            }(), i.prototype.isDateExact = function (t, i) {
                var o = this;
                return n.isInteger(t) && n.isInteger(i) || "boolean" == typeof t && "boolean" == typeof i ? t === i : (n.isDate(t) || e.isArray(t)) && (n.isDate(i) || e.isArray(i)) ? o.create(t).pick === o.create(i).pick : !(!e.isPlainObject(t) || !e.isPlainObject(i)) && (o.isDateExact(t.from, i.from) && o.isDateExact(t.to, i.to))
            }, i.prototype.isDateOverlap = function (t, i) {
                var o = this, a = o.settings.firstDay ? 1 : 0;
                return n.isInteger(t) && (n.isDate(i) || e.isArray(i)) ? (t = t % 7 + a) === o.create(i).day + 1 : n.isInteger(i) && (n.isDate(t) || e.isArray(t)) ? (i = i % 7 + a) === o.create(t).day + 1 : !(!e.isPlainObject(t) || !e.isPlainObject(i)) && o.overlapRanges(t, i)
            }, i.prototype.flipEnable = function (t) {
                var e = this.item;
                e.enable = t || (-1 == e.enable ? 1 : -1)
            }, i.prototype.deactivate = function (t, i) {
                var o = this, a = o.item.disable.slice(0);
                return "flip" == i ? o.flipEnable() : !1 === i ? (o.flipEnable(1), a = []) : !0 === i ? (o.flipEnable(-1), a = []) : i.map(function (t) {
                    for (var i, r = 0; r < a.length; r += 1) if (o.isDateExact(t, a[r])) {
                        i = !0;
                        break
                    }
                    i || (n.isInteger(t) || n.isDate(t) || e.isArray(t) || e.isPlainObject(t) && t.from && t.to) && a.push(t)
                }), a
            }, i.prototype.activate = function (t, i) {
                var o = this, a = o.item.disable, r = a.length;
                return "flip" == i ? o.flipEnable() : !0 === i ? (o.flipEnable(1), a = []) : !1 === i ? (o.flipEnable(-1), a = []) : i.map(function (t) {
                    var i, s, l, c;
                    for (l = 0; l < r; l += 1) {
                        if (s = a[l], o.isDateExact(s, t)) {
                            i = a[l] = null, c = !0;
                            break
                        }
                        if (o.isDateOverlap(s, t)) {
                            e.isPlainObject(t) ? (t.inverted = !0, i = t) : e.isArray(t) ? (i = t)[3] || i.push("inverted") : n.isDate(t) && (i = [t.getFullYear(), t.getMonth(), t.getDate(), "inverted"]);
                            break
                        }
                    }
                    if (i) for (l = 0; l < r; l += 1) if (o.isDateExact(a[l], t)) {
                        a[l] = null;
                        break
                    }
                    if (c) for (l = 0; l < r; l += 1) if (o.isDateOverlap(a[l], t)) {
                        a[l] = null;
                        break
                    }
                    i && a.push(i)
                }), a.filter(function (t) {
                    return null != t
                })
            }, i.prototype.nodes = function (t) {
                var e = this, i = e.settings, o = e.item, a = o.now, r = o.select, s = o.highlight, l = o.view,
                    c = o.disable, u = o.min, d = o.max, p = function (t, e) {
                        return i.firstDay && (t.push(t.shift()), e.push(e.shift())), n.node("thead", n.node("tr", n.group({
                            min: 0,
                            max: 6,
                            i: 1,
                            node: "th",
                            item: function (n) {
                                return [t[n], i.klass.weekdays, 'scope=col title="' + e[n] + '"']
                            }
                        })))
                    }((i.showWeekdaysFull ? i.weekdaysFull : i.weekdaysLetter).slice(0), i.weekdaysFull.slice(0)),
                    h = function (t) {
                        return n.node("div", " ", i.klass["nav" + (t ? "Next" : "Prev")] + (t && l.year >= d.year && l.month >= d.month || !t && l.year <= u.year && l.month <= u.month ? " " + i.klass.navDisabled : ""), "data-nav=" + (t || -1) + " " + n.ariaAttr({
                            role: "button",
                            controls: e.$node[0].id + "_table"
                        }) + ' title="' + (t ? i.labelMonthNext : i.labelMonthPrev) + '"')
                    }, f = function (o) {
                        var a = i.showMonthsShort ? i.monthsShort : i.monthsFull;
                        return "short_months" == o && (a = i.monthsShort), i.selectMonths && void 0 == o ? n.node("select", n.group({
                            min: 0,
                            max: 11,
                            i: 1,
                            node: "option",
                            item: function (t) {
                                return [a[t], 0, "value=" + t + (l.month == t ? " selected" : "") + (l.year == u.year && t < u.month || l.year == d.year && t > d.month ? " disabled" : "")]
                            }
                        }), i.klass.selectMonth + " browser-default", (t ? "" : "disabled") + " " + n.ariaAttr({controls: e.$node[0].id + "_table"}) + ' title="' + i.labelMonthSelect + '"') : "short_months" == o ? null != r ? a[r.month] : a[l.month] : n.node("div", a[l.month], i.klass.month)
                    }, v = function (o) {
                        var a = l.year, r = !0 === i.selectYears ? 5 : ~~(i.selectYears / 2);
                        if (r) {
                            var s = u.year, c = d.year, p = a - r, h = a + r;
                            if (s > p && (h += s - p, p = s), c < h) {
                                var f = p - s, v = h - c;
                                p -= f > v ? v : f, h = c
                            }
                            if (i.selectYears && void 0 == o) return n.node("select", n.group({
                                min: p,
                                max: h,
                                i: 1,
                                node: "option",
                                item: function (t) {
                                    return [t, 0, "value=" + t + (a == t ? " selected" : "")]
                                }
                            }), i.klass.selectYear + " browser-default", (t ? "" : "disabled") + " " + n.ariaAttr({controls: e.$node[0].id + "_table"}) + ' title="' + i.labelYearSelect + '"')
                        }
                        return "raw" == o ? n.node("div", a) : n.node("div", a, i.klass.year)
                    };
                return createDayLabel = function () {
                    return null != r ? r.date : a.date
                }, createWeekdayLabel = function () {
                    var t;
                    return t = null != r ? r.day : a.day, i.weekdaysShort[t]
                }, n.node("div", n.node("div", v("raw"), i.klass.year_display) + n.node("span", createWeekdayLabel() + ", ", "picker__weekday-display") + n.node("span", f("short_months") + " ", i.klass.month_display) + n.node("span", createDayLabel(), i.klass.day_display), i.klass.date_display) + n.node("div", n.node("div", n.node("div", (i.selectYears, f() + v() + h() + h(1)), i.klass.header) + n.node("table", p + n.node("tbody", n.group({
                    min: 0,
                    max: 5,
                    i: 1,
                    node: "tr",
                    item: function (t) {
                        var o = i.firstDay && 0 === e.create([l.year, l.month, 1]).day ? -7 : 0;
                        return [n.group({
                            min: 7 * t - l.day + o + 1, max: function () {
                                return this.min + 7 - 1
                            }, i: 1, node: "td", item: function (t) {
                                t = e.create([l.year, l.month, t + (i.firstDay ? 1 : 0)]);
                                var o = r && r.pick == t.pick, p = s && s.pick == t.pick,
                                    h = c && e.disabled(t) || t.pick < u.pick || t.pick > d.pick,
                                    f = n.trigger(e.formats.toString, e, [i.format, t]);
                                return [n.node("div", t.date, function (e) {
                                    return e.push(l.month == t.month ? i.klass.infocus : i.klass.outfocus), a.pick == t.pick && e.push(i.klass.now), o && e.push(i.klass.selected), p && e.push(i.klass.highlighted), h && e.push(i.klass.disabled), e.join(" ")
                                }([i.klass.day]), "data-pick=" + t.pick + " " + n.ariaAttr({
                                    role: "gridcell",
                                    label: f,
                                    selected: !(!o || e.$node.val() !== f) || null,
                                    activedescendant: !!p || null,
                                    disabled: !!h || null
                                }) + " " + (h ? "" : 'tabindex="0"')), "", n.ariaAttr({role: "presentation"})]
                            }
                        })]
                    }
                })), i.klass.table, 'id="' + e.$node[0].id + '_table" ' + n.ariaAttr({
                    role: "grid",
                    controls: e.$node[0].id,
                    readonly: !0
                })), i.klass.calendar_container) + n.node("div", n.node("button", i.today, "btn-flat picker__today waves-effect", "type=button data-pick=" + a.pick + (t && !e.disabled(a) ? "" : " disabled") + " " + n.ariaAttr({controls: e.$node[0].id})) + n.node("button", i.clear, "btn-flat picker__clear waves-effect", "type=button data-clear=1" + (t ? "" : " disabled") + " " + n.ariaAttr({controls: e.$node[0].id})) + n.node("button", i.close, "btn-flat picker__close waves-effect", "type=button data-close=true " + (t ? "" : " disabled") + " " + n.ariaAttr({controls: e.$node[0].id})), i.klass.footer), "picker__container__wrapper")
            }, i.defaults = function (t) {
                return {
                    labelMonthNext: "Next month",
                    labelMonthPrev: "Previous month",
                    labelMonthSelect: "Select a month",
                    labelYearSelect: "Select a year",
                    monthsFull: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
                    monthsShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
                    weekdaysFull: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
                    weekdaysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
                    weekdaysLetter: ["S", "M", "T", "W", "T", "F", "S"],
                    today: "Today",
                    clear: "Clear",
                    close: "Ok",
                    closeOnSelect: !1,
                    format: "d mmmm, yyyy",
                    klass: {
                        table: t + "table",
                        header: t + "header",
                        date_display: t + "date-display",
                        day_display: t + "day-display",
                        month_display: t + "month-display",
                        year_display: t + "year-display",
                        calendar_container: t + "calendar-container",
                        navPrev: t + "nav--prev",
                        navNext: t + "nav--next",
                        navDisabled: t + "nav--disabled",
                        month: t + "month",
                        year: t + "year",
                        selectMonth: t + "select--month",
                        selectYear: t + "select--year",
                        weekdays: t + "weekday",
                        day: t + "day",
                        disabled: t + "day--disabled",
                        selected: t + "day--selected",
                        highlighted: t + "day--highlighted",
                        now: t + "day--today",
                        infocus: t + "day--infocus",
                        outfocus: t + "day--outfocus",
                        footer: t + "footer",
                        buttonClear: t + "button--clear",
                        buttonToday: t + "button--today",
                        buttonClose: t + "button--close"
                    }
                }
            }(t.klasses().picker + "__"), t.extend("pickadate", i)
        }), function () {
            function t(t) {
                return document.createElementNS(l, t)
            }

            function e(t) {
                return (t < 10 ? "0" : "") + t
            }

            function i(t) {
                var e = ++m + "";
                return t ? t + e : e
            }

            function n(n, r) {
                function l(t, e) {
                    var i = d.offset(), n = /^touch/.test(t.type), o = i.left + g, a = i.top + g,
                        l = (n ? t.originalEvent.touches[0] : t).pageX - o,
                        c = (n ? t.originalEvent.touches[0] : t).pageY - a, u = Math.sqrt(l * l + c * c), p = !1;
                    if (!e || !(u < y - w || u > y + w)) {
                        t.preventDefault();
                        var v = setTimeout(function () {
                            _.popover.addClass("clockpicker-moving")
                        }, 200);
                        _.setHand(l, c, !e, !0), s.off(h).on(h, function (t) {
                            t.preventDefault();
                            var e = /^touch/.test(t.type), i = (e ? t.originalEvent.touches[0] : t).pageX - o,
                                n = (e ? t.originalEvent.touches[0] : t).pageY - a;
                            (p || i !== l || n !== c) && (p = !0, _.setHand(i, n, !1, !0))
                        }), s.off(f).on(f, function (t) {
                            s.off(f), t.preventDefault();
                            var i = /^touch/.test(t.type), n = (i ? t.originalEvent.changedTouches[0] : t).pageX - o,
                                u = (i ? t.originalEvent.changedTouches[0] : t).pageY - a;
                            (e || p) && n === l && u === c && _.setHand(n, u), "hours" === _.currentView ? _.toggleView("minutes", x / 2) : r.autoclose && (_.minutesView.addClass("clockpicker-dial-out"), setTimeout(function () {
                                _.done()
                            }, x / 2)), d.prepend(q), clearTimeout(v), _.popover.removeClass("clockpicker-moving"), s.off(h)
                        })
                    }
                }

                var u = a(C), d = u.find(".clockpicker-plate"), v = u.find(".picker__holder"),
                    m = u.find(".clockpicker-hours"), T = u.find(".clockpicker-minutes"),
                    S = u.find(".clockpicker-am-pm-block"), P = "INPUT" === n.prop("tagName"),
                    A = P ? n : n.find("input"), O = a("label[for=" + A.attr("id") + "]"), _ = this;
                this.id = i("cp"), this.element = n, this.holder = v, this.options = r, this.isAppended = !1, this.isShown = !1, this.currentView = "hours", this.isInput = P, this.input = A, this.label = O, this.popover = u, this.plate = d, this.hoursView = m, this.minutesView = T, this.amPmBlock = S, this.spanHours = u.find(".clockpicker-span-hours"), this.spanMinutes = u.find(".clockpicker-span-minutes"), this.spanAmPm = u.find(".clockpicker-span-am-pm"), this.footer = u.find(".picker__footer"), this.amOrPm = "PM", r.twelvehour && (r.ampmclickable ? (this.spanAmPm.empty(), a('<div id="click-am">AM</div>').on("click", function () {
                    _.spanAmPm.children("#click-am").addClass("text-primary"), _.spanAmPm.children("#click-pm").removeClass("text-primary"), _.amOrPm = "AM"
                }).appendTo(this.spanAmPm), a('<div id="click-pm">PM</div>').on("click", function () {
                    _.spanAmPm.children("#click-pm").addClass("text-primary"), _.spanAmPm.children("#click-am").removeClass("text-primary"), _.amOrPm = "PM"
                }).appendTo(this.spanAmPm)) : (this.spanAmPm.empty(), a('<div id="click-am">AM</div>').appendTo(this.spanAmPm), a('<div id="click-pm">PM</div>').appendTo(this.spanAmPm))), a('<button type="button" class="btn-flat picker__clear" tabindex="' + (r.twelvehour ? "3" : "1") + '">' + r.cleartext + "</button>").click(a.proxy(this.clear, this)).appendTo(this.footer), a('<button type="button" class="btn-flat picker__close" tabindex="' + (r.twelvehour ? "3" : "1") + '">' + r.canceltext + "</button>").click(a.proxy(this.hide, this)).appendTo(this.footer), a('<button type="button" class="btn-flat picker__close" tabindex="' + (r.twelvehour ? "3" : "1") + '">' + r.donetext + "</button>").click(a.proxy(this.done, this)).appendTo(this.footer), this.spanHours.click(a.proxy(this.toggleView, this, "hours")), this.spanMinutes.click(a.proxy(this.toggleView, this, "minutes")), A.on("focus.clockpicker click.clockpicker", a.proxy(this.show, this));
                var E, M, I, D, V = a('<div class="clockpicker-tick"></div>');
                if (r.twelvehour) for (E = 1; E < 13; E += 1) M = V.clone(), I = E / 6 * Math.PI, D = y, M.css({
                    left: g + Math.sin(I) * D - w,
                    top: g - Math.cos(I) * D - w
                }), M.html(0 === E ? "00" : E), m.append(M), M.on(p, l); else for (E = 0; E < 24; E += 1) M = V.clone(), I = E / 6 * Math.PI, D = E > 0 && E < 13 ? b : y, M.css({
                    left: g + Math.sin(I) * D - w,
                    top: g - Math.cos(I) * D - w
                }), M.html(0 === E ? "00" : E), m.append(M), M.on(p, l);
                for (E = 0; E < 60; E += 5) M = V.clone(), I = E / 30 * Math.PI, M.css({
                    left: g + Math.sin(I) * y - w,
                    top: g - Math.cos(I) * y - w
                }), M.html(e(E)), T.append(M), M.on(p, l);
                if (d.on(p, function (t) {
                        0 === a(t.target).closest(".clockpicker-tick").length && l(t, !0)
                    }), c) {
                    var q = u.find(".clockpicker-canvas"), z = t("svg");
                    z.setAttribute("class", "clockpicker-svg"), z.setAttribute("width", k), z.setAttribute("height", k);
                    var H = t("g");
                    H.setAttribute("transform", "translate(" + g + "," + g + ")");
                    var L = t("circle");
                    L.setAttribute("class", "clockpicker-canvas-bearing"), L.setAttribute("cx", 0), L.setAttribute("cy", 0), L.setAttribute("r", 4);
                    var j = t("line");
                    j.setAttribute("x1", 0), j.setAttribute("y1", 0);
                    var $ = t("circle");
                    $.setAttribute("class", "clockpicker-canvas-bg"), $.setAttribute("r", w), H.appendChild(j), H.appendChild($), H.appendChild(L), z.appendChild(H), q.append(z), this.hand = j, this.bg = $, this.bearing = L, this.g = H, this.canvas = q
                }
                o(this.options.init)
            }

            function o(t) {
                t && "function" == typeof t && t()
            }

            var a = window.jQuery, r = a(window), s = a(document), l = "http://www.w3.org/2000/svg",
                c = "SVGAngle" in window && function () {
                    var t, e = document.createElement("div");
                    return e.innerHTML = "<svg/>", t = (e.firstChild && e.firstChild.namespaceURI) == l, e.innerHTML = "", t
                }(), u = function () {
                    var t = document.createElement("div").style;
                    return "transition" in t || "WebkitTransition" in t || "MozTransition" in t || "msTransition" in t || "OTransition" in t
                }(), d = "ontouchstart" in window, p = "mousedown" + (d ? " touchstart" : ""),
                h = "mousemove.clockpicker" + (d ? " touchmove.clockpicker" : ""),
                f = "mouseup.clockpicker" + (d ? " touchend.clockpicker" : ""),
                v = navigator.vibrate ? "vibrate" : navigator.webkitVibrate ? "webkitVibrate" : null, m = 0, g = 135,
                y = 105, b = 80, w = 20, k = 2 * g, x = u ? 350 : 1,
                C = ['<div class="clockpicker picker">', '<div class="picker__holder">', '<div class="picker__frame">', '<div class="picker__wrap">', '<div class="picker__box">', '<div class="picker__date-display">', '<div class="clockpicker-display">', '<div class="clockpicker-display-column">', '<span class="clockpicker-span-hours text-primary"></span>', ":", '<span class="clockpicker-span-minutes"></span>', "</div>", '<div class="clockpicker-display-column clockpicker-display-am-pm">', '<div class="clockpicker-span-am-pm"></div>', "</div>", "</div>", "</div>", '<div class="picker__container__wrapper">', '<div class="picker__calendar-container">', '<div class="clockpicker-plate">', '<div class="clockpicker-canvas"></div>', '<div class="clockpicker-dial clockpicker-hours"></div>', '<div class="clockpicker-dial clockpicker-minutes clockpicker-dial-out"></div>', "</div>", '<div class="clockpicker-am-pm-block">', "</div>", "</div>", '<div class="picker__footer">', "</div>", "</div>", "</div>", "</div>", "</div>", "</div>", "</div>"].join("");
            n.DEFAULTS = {
                default: "",
                fromnow: 0,
                donetext: "Ok",
                cleartext: "Clear",
                canceltext: "Cancel",
                autoclose: !1,
                ampmclickable: !0,
                darktheme: !1,
                twelvehour: !0,
                vibrate: !0
            }, n.prototype.toggle = function () {
                this[this.isShown ? "hide" : "show"]()
            }, n.prototype.locate = function () {
                var t = this.element, e = this.popover;
                t.offset(), t.outerWidth(), t.outerHeight(), this.options.align;
                e.show()
            }, n.prototype.show = function (t) {
                if (!this.isShown) {
                    o(this.options.beforeShow), a(":input").each(function () {
                        a(this).attr("tabindex", -1)
                    });
                    var i = this;
                    this.input.blur(), this.popover.addClass("picker--opened"), this.input.addClass("picker__input picker__input--active"), a(document.body).css("overflow", "hidden");
                    var n = ((this.input.prop("value") || this.options.default || "") + "").split(":");
                    if (this.options.twelvehour && void 0 !== n[1] && (n[1].indexOf("AM") > 0 ? this.amOrPm = "AM" : this.amOrPm = "PM", n[1] = n[1].replace("AM", "").replace("PM", "")), "now" === n[0]) {
                        var l = new Date(+new Date + this.options.fromnow);
                        n = [l.getHours(), l.getMinutes()], this.options.twelvehour && (this.amOrPm = n[0] >= 12 && n[0] < 24 ? "PM" : "AM")
                    }
                    this.hours = +n[0] || 0, this.minutes = +n[1] || 0, this.spanHours.html(this.hours), this.spanMinutes.html(e(this.minutes)), this.isAppended || (this.popover.insertAfter(this.input), this.options.twelvehour && ("PM" === this.amOrPm ? (this.spanAmPm.children("#click-pm").addClass("text-primary"), this.spanAmPm.children("#click-am").removeClass("text-primary")) : (this.spanAmPm.children("#click-am").addClass("text-primary"), this.spanAmPm.children("#click-pm").removeClass("text-primary"))), r.on("resize.clockpicker" + this.id, function () {
                        i.isShown && i.locate()
                    }), this.isAppended = !0), this.toggleView("hours"), this.locate(), this.isShown = !0, s.on("click.clockpicker." + this.id + " focusin.clockpicker." + this.id, function (t) {
                        var e = a(t.target);
                        0 === e.closest(i.popover.find(".picker__wrap")).length && 0 === e.closest(i.input).length && i.hide()
                    }), s.on("keyup.clockpicker." + this.id, function (t) {
                        27 === t.keyCode && i.hide()
                    }), o(this.options.afterShow)
                }
            }, n.prototype.hide = function () {
                o(this.options.beforeHide), this.input.removeClass("picker__input picker__input--active"), this.popover.removeClass("picker--opened"), a(document.body).css("overflow", "visible"), this.isShown = !1, a(":input").each(function (t) {
                    a(this).attr("tabindex", t + 1)
                }), s.off("click.clockpicker." + this.id + " focusin.clockpicker." + this.id), s.off("keyup.clockpicker." + this.id), this.popover.hide(), o(this.options.afterHide)
            }, n.prototype.toggleView = function (t, e) {
                var i = !1;
                "minutes" === t && "visible" === a(this.hoursView).css("visibility") && (o(this.options.beforeHourSelect), i = !0);
                var n = "hours" === t, r = n ? this.hoursView : this.minutesView,
                    s = n ? this.minutesView : this.hoursView;
                this.currentView = t, this.spanHours.toggleClass("text-primary", n), this.spanMinutes.toggleClass("text-primary", !n), s.addClass("clockpicker-dial-out"), r.css("visibility", "visible").removeClass("clockpicker-dial-out"), this.resetClock(e), clearTimeout(this.toggleViewTimer), this.toggleViewTimer = setTimeout(function () {
                    s.css("visibility", "hidden")
                }, x), i && o(this.options.afterHourSelect)
            }, n.prototype.resetClock = function (t) {
                var e = this.currentView, i = this[e], n = "hours" === e, o = i * (Math.PI / (n ? 6 : 30)),
                    a = n && i > 0 && i < 13 ? b : y, r = Math.sin(o) * a, s = -Math.cos(o) * a, l = this;
                c && t ? (l.canvas.addClass("clockpicker-canvas-out"), setTimeout(function () {
                    l.canvas.removeClass("clockpicker-canvas-out"), l.setHand(r, s)
                }, t)) : this.setHand(r, s)
            }, n.prototype.setHand = function (t, i, n, o) {
                var r, s = Math.atan2(t, -i), l = "hours" === this.currentView, u = Math.PI / (l || n ? 6 : 30),
                    d = Math.sqrt(t * t + i * i), p = this.options, h = l && d < (y + b) / 2, f = h ? b : y;
                if (p.twelvehour && (f = y), s < 0 && (s = 2 * Math.PI + s), r = Math.round(s / u), s = r * u, p.twelvehour ? l ? 0 === r && (r = 12) : (n && (r *= 5), 60 === r && (r = 0)) : l ? (12 === r && (r = 0), r = h ? 0 === r ? 12 : r : 0 === r ? 0 : r + 12) : (n && (r *= 5), 60 === r && (r = 0)), this[this.currentView] !== r && v && this.options.vibrate && (this.vibrateTimer || (navigator[v](10), this.vibrateTimer = setTimeout(a.proxy(function () {
                        this.vibrateTimer = null
                    }, this), 100))), this[this.currentView] = r, l ? this.spanHours.html(r) : this.spanMinutes.html(e(r)), c) {
                    var m = Math.sin(s) * (f - w), g = -Math.cos(s) * (f - w), k = Math.sin(s) * f,
                        x = -Math.cos(s) * f;
                    this.hand.setAttribute("x2", m), this.hand.setAttribute("y2", g), this.bg.setAttribute("cx", k), this.bg.setAttribute("cy", x)
                } else this[l ? "hoursView" : "minutesView"].find(".clockpicker-tick").each(function () {
                    var t = a(this);
                    t.toggleClass("active", r === +t.html())
                })
            }, n.prototype.done = function () {
                o(this.options.beforeDone), this.hide(), this.label.addClass("active");
                var t = this.input.prop("value"), i = e(this.hours) + ":" + e(this.minutes);
                this.options.twelvehour && (i += this.amOrPm), this.input.prop("value", i), i !== t && (this.input.triggerHandler("change"), this.isInput || this.element.trigger("change")), this.options.autoclose && this.input.trigger("blur"), o(this.options.afterDone)
            }, n.prototype.clear = function () {
                this.hide(), this.label.removeClass("active");
                var t = this.input.prop("value");
                this.input.prop("value", ""), "" !== t && (this.input.triggerHandler("change"), this.isInput || this.element.trigger("change")), this.options.autoclose && this.input.trigger("blur")
            }, n.prototype.remove = function () {
                this.element.removeData("clockpicker"), this.input.off("focus.clockpicker click.clockpicker"), this.isShown && this.hide(), this.isAppended && (r.off("resize.clockpicker" + this.id), this.popover.remove())
            }, a.fn.pickatime = function (t) {
                var e = Array.prototype.slice.call(arguments, 1);
                return this.each(function () {
                    var i = a(this), o = i.data("clockpicker");
                    if (o) "function" == typeof o[t] && o[t].apply(o, e); else {
                        var r = a.extend({}, n.DEFAULTS, i.data(), "object" == typeof t && t);
                        i.data("clockpicker", new n(i, r))
                    }
                })
            }
        }(), function (t) {
            function e() {
                var e = +t(this).attr("data-length"), i = +t(this).val().length, n = i <= e;
                t(this).parent().find('span[class="character-counter"]').html(i + "/" + e), o(n, t(this))
            }

            function i(e) {
                var i = e.parent().find('span[class="character-counter"]');
                i.length || (i = t("<span/>").addClass("character-counter").css("float", "right").css("font-size", "12px").css("height", 1), e.parent().append(i))
            }

            function n() {
                t(this).parent().find('span[class="character-counter"]').html("")
            }

            function o(t, e) {
                var i = e.hasClass("invalid");
                t && i ? e.removeClass("invalid") : t || i || (e.removeClass("valid"), e.addClass("invalid"))
            }

            t.fn.characterCounter = function () {
                return this.each(function () {
                    var o = t(this);
                    o.parent().find('span[class="character-counter"]').length || void 0 !== o.attr("data-length") && (o.on("input", e), o.on("focus", e), o.on("blur", n), i(o))
                })
            }, t(document).ready(function () {
                t("input, textarea").characterCounter()
            })
        }(jQuery), function (t) {
            var e = {
                init: function (e) {
                    var i = {
                        duration: 200,
                        dist: -100,
                        shift: 0,
                        padding: 0,
                        fullWidth: !1,
                        indicators: !1,
                        noWrap: !1,
                        onCycleTo: null
                    };
                    e = t.extend(i, e);
                    var n = Materialize.objectSelectorString(t(this));
                    return this.each(function (i) {
                        function o(t) {
                            return t.targetTouches && t.targetTouches.length >= 1 ? t.targetTouches[0].clientX : t.clientX
                        }

                        function a(t) {
                            return t.targetTouches && t.targetTouches.length >= 1 ? t.targetTouches[0].clientY : t.clientY
                        }

                        function r(t) {
                            return t >= C ? t % C : t < 0 ? r(C + t % C) : t
                        }

                        function s(i) {
                            _ = !0, j.hasClass("scrolling") || j.addClass("scrolling"), null != H && window.clearTimeout(H), H = window.setTimeout(function () {
                                _ = !1, j.removeClass("scrolling")
                            }, e.duration);
                            var n, o, a, s, l, c, u, d = w;
                            if (b = "number" == typeof i ? i : b, w = Math.floor((b + x / 2) / x), a = b - w * x, s = a < 0 ? 1 : -1, l = -s * a * 2 / x, o = C >> 1, e.fullWidth ? u = "translateX(0)" : (u = "translateX(" + (j[0].clientWidth - m) / 2 + "px) ", u += "translateY(" + (j[0].clientHeight - g) / 2 + "px)"), N) {
                                var p = w % C, h = z.find(".indicator-item.active");
                                h.index() !== p && (h.removeClass("active"), z.find(".indicator-item").eq(p).addClass("active"))
                            }
                            for ((!W || w >= 0 && w < C) && (c = v[r(w)], t(c).hasClass("active") || (j.find(".carousel-item").removeClass("active"), t(c).addClass("active")), c.style[E] = u + " translateX(" + -a / 2 + "px) translateX(" + s * e.shift * l * n + "px) translateZ(" + e.dist * l + "px)", c.style.zIndex = 0, e.fullWidth ? tweenedOpacity = 1 : tweenedOpacity = 1 - .2 * l, c.style.opacity = tweenedOpacity, c.style.display = "block"), n = 1; n <= o; ++n) e.fullWidth ? (zTranslation = e.dist, tweenedOpacity = n === o && a < 0 ? 1 - l : 1) : (zTranslation = e.dist * (2 * n + l * s), tweenedOpacity = 1 - .2 * (2 * n + l * s)), (!W || w + n < C) && ((c = v[r(w + n)]).style[E] = u + " translateX(" + (e.shift + (x * n - a) / 2) + "px) translateZ(" + zTranslation + "px)", c.style.zIndex = -n, c.style.opacity = tweenedOpacity, c.style.display = "block"), e.fullWidth ? (zTranslation = e.dist, tweenedOpacity = n === o && a > 0 ? 1 - l : 1) : (zTranslation = e.dist * (2 * n - l * s), tweenedOpacity = 1 - .2 * (2 * n - l * s)), (!W || w - n >= 0) && ((c = v[r(w - n)]).style[E] = u + " translateX(" + (-e.shift + (-x * n - a) / 2) + "px) translateZ(" + zTranslation + "px)", c.style.zIndex = -n, c.style.opacity = tweenedOpacity, c.style.display = "block");
                            if ((!W || w >= 0 && w < C) && ((c = v[r(w)]).style[E] = u + " translateX(" + -a / 2 + "px) translateX(" + s * e.shift * l + "px) translateZ(" + e.dist * l + "px)", c.style.zIndex = 0, e.fullWidth ? tweenedOpacity = 1 : tweenedOpacity = 1 - .2 * l, c.style.opacity = tweenedOpacity, c.style.display = "block"), d !== w && "function" == typeof e.onCycleTo) {
                                var f = j.find(".carousel-item").eq(r(w));
                                e.onCycleTo.call(this, f, V)
                            }
                            "function" == typeof L && (L.call(this, f, V), L = null)
                        }

                        function l() {
                            var t, e, i;
                            e = (t = Date.now()) - I, I = t, i = b - M, M = b, O = .8 * (1e3 * i / (1 + e)) + .2 * O
                        }

                        function c() {
                            var t, i;
                            P && (t = Date.now() - I, (i = P * Math.exp(-t / e.duration)) > 2 || i < -2 ? (s(A - i), requestAnimationFrame(c)) : s(A))
                        }

                        function u(i) {
                            if (V) return i.preventDefault(), i.stopPropagation(), !1;
                            if (!e.fullWidth) {
                                var n = t(i.target).closest(".carousel-item").index();
                                0 !== r(w) - n && (i.preventDefault(), i.stopPropagation()), d(n)
                            }
                        }

                        function d(t) {
                            var e = w % C - t;
                            W || (e < 0 ? Math.abs(e + C) < Math.abs(e) && (e += C) : e > 0 && Math.abs(e - C) < e && (e -= C)), e < 0 ? j.trigger("carouselNext", [Math.abs(e)]) : e > 0 && j.trigger("carouselPrev", [e])
                        }

                        function p(e) {
                            "mousedown" === e.type && t(e.target).is("img") && e.preventDefault(), k = !0, V = !1, q = !1, T = o(e), S = a(e), O = P = 0, M = b, I = Date.now(), clearInterval(D), D = setInterval(l, 100)
                        }

                        function h(t) {
                            var e, i;
                            if (k) if (e = o(t), y = a(t), i = T - e, Math.abs(S - y) < 30 && !q) (i > 2 || i < -2) && (V = !0, T = e, s(b + i)); else {
                                if (V) return t.preventDefault(), t.stopPropagation(), !1;
                                q = !0
                            }
                            if (V) return t.preventDefault(), t.stopPropagation(), !1
                        }

                        function f(t) {
                            if (k) return k = !1, clearInterval(D), A = b, (O > 10 || O < -10) && (A = b + (P = .9 * O)), A = Math.round(A / x) * x, W && (A >= x * (C - 1) ? A = x * (C - 1) : A < 0 && (A = 0)), P = A - b, I = Date.now(), requestAnimationFrame(c), V && (t.preventDefault(), t.stopPropagation()), !1
                        }

                        var v, m, g, b, w, k, x, C, T, S, P, A, O, _, E, M, I, D, V, q,
                            z = t('<ul class="indicators"></ul>'), H = null, L = null, j = t(this),
                            $ = j.find(".carousel-item").length > 1,
                            N = (j.attr("data-indicators") || e.indicators) && $,
                            W = j.attr("data-no-wrap") || e.noWrap || !$, F = j.attr("data-namespace") || n + i;
                        j.attr("data-namespace", F);
                        var Q = function (e) {
                            var i = j.find(".carousel-item.active").length ? j.find(".carousel-item.active").first() : j.find(".carousel-item").first(),
                                n = i.find("img").first();
                            if (n.length) if (n[0].complete) if (n.height() > 0) j.css("height", n.height()); else {
                                var o = n[0].naturalWidth, a = n[0].naturalHeight, r = j.width() / o * a;
                                j.css("height", r)
                            } else n.on("load", function () {
                                j.css("height", t(this).height())
                            }); else if (!e) {
                                var s = i.height();
                                j.css("height", s)
                            }
                        };
                        if (e.fullWidth && (e.dist = 0, Q(), N && j.find(".carousel-fixed-item").addClass("with-indicators")), j.hasClass("initialized")) return t(window).trigger("resize"), j.trigger("carouselNext", [1e-6]), !0;
                        j.addClass("initialized"), k = !1, b = A = 0, v = [], m = j.find(".carousel-item").first().innerWidth(), g = j.find(".carousel-item").first().innerHeight(), x = 2 * m + e.padding, j.find(".carousel-item").each(function (e) {
                            if (v.push(t(this)[0]), N) {
                                var i = t('<li class="indicator-item"></li>');
                                0 === e && i.addClass("active"), i.click(function (e) {
                                    e.stopPropagation(), d(t(this).index())
                                }), z.append(i)
                            }
                        }), N && j.append(z), C = v.length, E = "transform", ["webkit", "Moz", "O", "ms"].every(function (t) {
                            var e = t + "Transform";
                            return void 0 === document.body.style[e] || (E = e, !1)
                        });
                        var X = Materialize.throttle(function () {
                            if (e.fullWidth) {
                                m = j.find(".carousel-item").first().innerWidth();
                                j.find(".carousel-item.active").height();
                                x = 2 * m + e.padding, A = b = 2 * w * m, Q(!0)
                            } else s()
                        }, 200);
                        t(window).off("resize.carousel-" + F).on("resize.carousel-" + F, X), void 0 !== window.ontouchstart && (j.on("touchstart.carousel", p), j.on("touchmove.carousel", h), j.on("touchend.carousel", f)), j.on("mousedown.carousel", p), j.on("mousemove.carousel", h), j.on("mouseup.carousel", f), j.on("mouseleave.carousel", f), j.on("click.carousel", u), s(b), t(this).on("carouselNext", function (t, e, i) {
                            void 0 === e && (e = 1), "function" == typeof i && (L = i), A = x * Math.round(b / x) + x * e, b !== A && (P = A - b, I = Date.now(), requestAnimationFrame(c))
                        }), t(this).on("carouselPrev", function (t, e, i) {
                            void 0 === e && (e = 1), "function" == typeof i && (L = i), A = x * Math.round(b / x) - x * e, b !== A && (P = A - b, I = Date.now(), requestAnimationFrame(c))
                        }), t(this).on("carouselSet", function (t, e, i) {
                            void 0 === e && (e = 0), "function" == typeof i && (L = i), d(e)
                        })
                    })
                }, next: function (e, i) {
                    t(this).trigger("carouselNext", [e, i])
                }, prev: function (e, i) {
                    t(this).trigger("carouselPrev", [e, i])
                }, set: function (e, i) {
                    t(this).trigger("carouselSet", [e, i])
                }, destroy: function () {
                    var e = t(this).attr("data-namespace");
                    t(this).removeAttr("data-namespace"), t(this).removeClass("initialized"), t(this).find(".indicators").remove(), t(this).off("carouselNext carouselPrev carouselSet"), t(window).off("resize.carousel-" + e), void 0 !== window.ontouchstart && t(this).off("touchstart.carousel touchmove.carousel touchend.carousel"), t(this).off("mousedown.carousel mousemove.carousel mouseup.carousel mouseleave.carousel click.carousel")
                }
            };
            t.fn.carousel = function (i) {
                return e[i] ? e[i].apply(this, Array.prototype.slice.call(arguments, 1)) : "object" != typeof i && i ? void t.error("Method " + i + " does not exist on jQuery.carousel") : e.init.apply(this, arguments)
            }
        }(jQuery), function (t) {
            var e = {
                init: function (e) {
                    return this.each(function () {
                        var i = t("#" + t(this).attr("data-activates")), n = (t("body"), t(this)),
                            o = n.parent(".tap-target-wrapper"), a = o.find(".tap-target-wave"),
                            r = o.find(".tap-target-origin"), s = n.find(".tap-target-content");
                        o.length || (o = n.wrap(t('<div class="tap-target-wrapper"></div>')).parent()), s.length || (s = t('<div class="tap-target-content"></div>'), n.append(s)), a.length || (a = t('<div class="tap-target-wave"></div>'), r.length || ((r = i.clone(!0, !0)).addClass("tap-target-origin"), r.removeAttr("id"), r.removeAttr("style"), a.append(r)), o.append(a));
                        var l = function () {
                            o.is(".open") && (o.removeClass("open"), r.off("click.tapTarget"), t(document).off("click.tapTarget"), t(window).off("resize.tapTarget"))
                        }, c = function () {
                            var e = "fixed" === i.css("position");
                            if (!e) for (var r = i.parents(), l = 0; l < r.length && !(e = "fixed" == t(r[l]).css("position")); l++) ;
                            var c = i.outerWidth(), u = i.outerHeight(),
                                d = e ? i.offset().top - t(document).scrollTop() : i.offset().top,
                                p = e ? i.offset().left - t(document).scrollLeft() : i.offset().left,
                                h = t(window).width(), f = t(window).height(), v = h / 2, m = f / 2, g = p <= v,
                                y = p > v, b = d <= m, w = d > m, k = p >= .25 * h && p <= .75 * h, x = n.outerWidth(),
                                C = n.outerHeight(), T = d + u / 2 - C / 2, S = p + c / 2 - x / 2,
                                P = e ? "fixed" : "absolute", A = k ? x : x / 2 + c, O = C / 2, _ = b ? C / 2 : 0,
                                E = g && !k ? x / 2 - c : 0, M = c, I = w ? "bottom" : "top", D = 2 * c, V = D,
                                q = C / 2 - V / 2, z = x / 2 - D / 2, H = {};
                            H.top = b ? T : "", H.right = y ? h - S - x : "", H.bottom = w ? f - T - C : "", H.left = g ? S : "", H.position = P, o.css(H), s.css({
                                width: A,
                                height: O,
                                top: _,
                                right: 0,
                                bottom: 0,
                                left: E,
                                padding: M,
                                verticalAlign: I
                            }), a.css({top: q, left: z, width: D, height: V})
                        };
                        "open" == e && (c(), o.is(".open") || (o.addClass("open"), setTimeout(function () {
                            r.off("click.tapTarget").on("click.tapTarget", function (t) {
                                l(), r.off("click.tapTarget")
                            }), t(document).off("click.tapTarget").on("click.tapTarget", function (e) {
                                l(), t(document).off("click.tapTarget")
                            });
                            var e = Materialize.throttle(function () {
                                c()
                            }, 200);
                            t(window).off("resize.tapTarget").on("resize.tapTarget", e)
                        }, 0))), "close" == e && l()
                    })
                }, open: function () {
                }, close: function () {
                }
            };
            t.fn.tapTarget = function (i) {
                if (e[i] || "object" == typeof i) return e.init.apply(this, arguments);
                t.error("Method " + i + " does not exist on jQuery.tap-target")
            }
        }(jQuery);
    </script>
    <?php
}