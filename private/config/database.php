<?php
return [
    'default' => 'pdo', // pdo | mysqli

    'engine' => 'mariadb', // mysql | mariadb

    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'l2jdb',
    'user' => 'root',
    'pass' => 'root',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'timezone' => 'America/Sao_Paulo',

    // opções extras
    'persistent' => false,
    'strict' => true,
];