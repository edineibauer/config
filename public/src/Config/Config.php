<?php

namespace Config;

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
            $path = defined("PATH_HOME") ? PATH_HOME : "../../../";
            $fp = fopen("{$path}{$url}", "w+");
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
}