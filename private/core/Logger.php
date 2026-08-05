<?php

class Logger
{
    private static $file;
    private static $env = 'production';

    public static function init($file, $env = 'production')
    {
        self::$file = $file;
        self::$env  = $env;

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    public static function info($message)
    {
        self::write('INFO', $message);
    }

    public static function error($message)
    {
        self::write('ERROR', $message);
    }

    private static function write($level, $message)
    {
        $line = sprintf("[%s] [%s] %s%s", date('Y-m-d H:i:s'), $level, $message, PHP_EOL);
        file_put_contents(self::$file, $line, FILE_APPEND);

        if (self::$env === 'development') {
            echo "\n<!-- " . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . " -->\n";
        }
    }
}