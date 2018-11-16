<?php

namespace Config;

use Helpers\Helper;

class Config
{

    /**
     * Gera arquivo de configurações
     * @param array $dados
     */
    public static function createConfig(array $dados = [])
    {
        $path = defined("PATH_HOME") ? PATH_HOME : "../../../";
        if(empty($dados))
            $dados = json_decode(file_get_contents($path . "_config/config.json"), true);
        else
            self::writeFile("_config/config.json", json_encode($dados));

        $conf = "<?php\n";
        foreach ($dados as $dado => $value) {
            $value = (is_bool($value) ? ($value ? 'true' : 'false') : "'{$value}'");
            $conf .= "define('" . strtoupper(trim($dado)) . "', {$value});\n";
        }

        $conf .= "\nrequire_once PATH_HOME . 'vendor/autoload.php';\nnew LinkControl\Sessao();";

        self::writeFile("_config/config.php", $conf);
    }

    /**
     * @param string $domain
     * @param string $www
     * @param string $protocol
     */
    public static function createHtaccess(string $domain = "", string $www = "", string $protocol = "")
    {
        if(!empty($domain) || defined("DOMINIO")) {
            if(empty($domain)) {
                $domain = DOMINIO;
                $www = WWW;
                $protocol = SSL;
                $path = PATH_HOME . VENDOR . "config/";
            } else {
                $path = "";
            }
            $dados = "RewriteCond %{HTTP_HOST} ^" . ($www ? "{$domain}\nRewriteRule ^ http" . ($protocol ? "s" : "") . "://www.{$domain}%{REQUEST_URI}" : "www.(.*) [NC]\nRewriteRule ^(.*) http" . ($protocol ? "s" : "") . "://%1/$1") . " [L,R=301]";
            self::writeFile(".htaccess", str_replace('{$dados}', $dados, file_get_contents("{$path}public/installTemplates/htaccess.txt")));
        }
    }

    /**
     * @param string $url
     * @param string $content
     */
    public static function writeFile(string $url, string $content)
    {
        try {
            if(defined("PATH_HOME") && !preg_match("/^" . preg_quote(PATH_HOME, '/') . "/i", $url))
                $url = PATH_HOME . (preg_match("/^\//i", $url) ? substr($url, 1) : $url);
            elseif(!defined("PATH_HOME"))
                $url = "../../../" . $url;

            $fp = fopen($url, "w+");
            fwrite($fp, $content);
            fclose($fp);

        } catch (Exception $e) {

        }
    }
    /**
     * Cria Diretório
     * @param string $dir
     * @return string
     */
    public static function createDir(string $dir)
    {
        $path = defined("PATH_HOME") ? PATH_HOME : "../../../";
        if (!file_exists("{$path}{$dir}"))
            mkdir("{$path}{$dir}", 0777);

        return "{$path}{$dir}";
    }

    /**
     * Retorna lista com entidades que não devem ser exibidas na dashboard
     */
    public static function getMenuNotAllow()
    {
        $file = [];
        $permission = json_decode(file_get_contents(PATH_HOME . "_config/menu_not_show.json"), true);
        if(!empty($_SESSION['userlogin']) && !empty($permission[$_SESSION['userlogin']['setor']]))
            $file = $permission[$_SESSION['userlogin']['setor']];
        elseif(empty($_SESSION['userlogin']) && !empty($permission[0]))
            $file = $permission[0];

        $path = "public/dash/-menu.json";
        if (!empty($_SESSION['userlogin']))
            $pathSession = "public/dash/{$_SESSION['userlogin']['setor']}/-menu.json";

        if (file_exists(PATH_HOME . $path))
            $file = self::addNotShow(PATH_HOME . $path, $file, PATH_HOME);

        if (isset($pathSession) && file_exists(PATH_HOME . $pathSession))
            $file = self::addNotShow(PATH_HOME . $pathSession, $file, PATH_HOME);

        foreach (Helper::listFolder(PATH_HOME . VENDOR) as $lib) {
            if (file_exists(PATH_HOME . VENDOR . "{$lib}/{$path}"))
                $file = self::addNotShow(PATH_HOME . VENDOR . "{$lib}/{$path}", $file, PATH_HOME . VENDOR . $lib);
            if (isset($pathSession) && file_exists(PATH_HOME . VENDOR . "{$lib}/{$pathSession}"))
                $file = self::addNotShow(PATH_HOME . VENDOR . "{$lib}/{$pathSession}", $file, PATH_HOME . VENDOR . $lib);
        }

        return $file;
    }

    /**
     * @param string $dir
     * @param array $file
     * @param string $dirPermission
     * @return array
     */
    private static function addNotShow(string $dir, array $file, string $dirPermission): array
    {
        $m = json_decode(file_get_contents($dir), true);
        if (!empty($m) && is_array($m)) {
            foreach ($m as $setor => $entity) {
                if(is_array($entity) && $setor == $_SESSION['userlogin']['setor']) {
                    foreach ($entity as $e) {
                        if (file_exists($dirPermission . "/public/entity/cache/{$e}.json") && !in_array($e, $file))
                            $file[] = $e;
                    }
                } elseif(is_string($entity)) {
                    if (file_exists($dirPermission . "/public/entity/cache/{$entity}.json") && !in_array($entity, $file))
                        $file[] = $entity;
                }
            }
        }

        return $file;
    }
}