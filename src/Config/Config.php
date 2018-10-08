<?php

class Config
{

    /**
     * Gera arquivo de configurações
     */
    public static function createConfig()
    {
        $path = defined("PATH_HOME") ? PATH_HOME : "../../../";
        $dados = json_decode(file_get_contents($path . "_config/config.json"), true);

        $conf = "<?php\n";
        foreach ($dados as $dado => $value) {
            $value = (is_bool($value) ? ($value ? 'true' : 'false') : "'{$value}'");
            $conf .= "define('" . strtoupper(trim($dado)) . "', {$value});\n";
        }

        $conf .= "\nrequire_once PATH_HOME . 'vendor/autoload.php';\nnew LinkControl\Sessao();";

        self::writeFile("_config/config.php", $conf);
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