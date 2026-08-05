<?php

class Database
{
    public static function connect($config)
    {
        $driver = isset($config['default']) ? strtolower($config['default']) : 'pdo';

        if ($driver === 'pdo') {
            return self::connectPdo($config);
        }

        if ($driver === 'mysqli') {
            return self::connectMysqli($config);
        }

        throw new Exception('Driver de banco inválido: ' . $driver);
    }

    private static function connectPdo($config)
    {
        $host    = $config['host'];
        $port    = $config['port'];
        $dbname  = $config['name'];
        $charset = isset($config['charset']) ? $config['charset'] : 'utf8mb4';
        $user    = $config['user'];
        $pass    = $config['pass'];

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        $options = array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        );

        return new PDO($dsn, $user, $pass, $options);
    }

    private static function connectMysqli($config)
    {
        $mysqli = new mysqli(
            $config['host'],
            $config['user'],
            $config['pass'],
            $config['name'],
            (int)$config['port']
        );

        if ($mysqli->connect_error) {
            throw new Exception('MySQLi connect error: ' . $mysqli->connect_error);
        }

        $charset = isset($config['charset']) ? $config['charset'] : 'utf8mb4';
        $mysqli->set_charset($charset);

        return $mysqli;
    }

    public static function applyTimezone($db, $config)
    {
        $timezone = isset($config['timezone']) ? $config['timezone'] : 'America/Sao_Paulo';

        $map = array(
            'America/Sao_Paulo' => '-03:00',
        );

        $offset = isset($map[$timezone]) ? $map[$timezone] : '-03:00';

        if ($db instanceof PDO) {
            $stmt = $db->prepare("SET time_zone = :tz");
            $stmt->execute(array('tz' => $offset));
            return;
        }

        if ($db instanceof mysqli) {
            $safeOffset = $db->real_escape_string($offset);
            $db->query("SET time_zone = '{$safeOffset}'");
        }
    }

    public static function isConnected($db)
    {
        try {
            if ($db instanceof PDO) {
                $db->query('SELECT 1');
                return true;
            }

            if ($db instanceof mysqli) {
                return $db->ping();
            }
        } catch (Exception $e) {
            return false;
        }

        return false;
    }
}