<?php

define('BASE_PATH', dirname(__DIR__, 2));
define('PRIVATE_PATH', BASE_PATH . '/private');
define('PUBLIC_PATH', BASE_PATH);

$appConfig = require PRIVATE_PATH . '/config/app.php';
$dbConfig  = require PRIVATE_PATH . '/config/database.php';

require_once PRIVATE_PATH . '/core/helpers.php';
require_once PRIVATE_PATH . '/core/Logger.php';
require_once PRIVATE_PATH . '/core/Database.php';

// timezone do PHP/site
date_default_timezone_set(isset($appConfig['app']['timezone']) ? $appConfig['app']['timezone'] : 'America/Sao_Paulo');

// ambiente
define('APP_ENV', isset($appConfig['app']['env']) ? $appConfig['app']['env'] : 'production');
define('APP_DEBUG', !empty($appConfig['app']['debug']));

// erros e logger
if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}

Logger::init(PRIVATE_PATH . '/logs/app.log', APP_ENV);

// valida vínculo do site
if (empty($appConfig['app']['site_linked'])) {
    http_response_code(503);
    exit('O site não está vinculado ao sistema principal.');
}

// cria conexão global
try {
    $db = Database::connect($dbConfig);

    // ajusta timezone da sessão do banco
    Database::applyTimezone($db, $dbConfig);

} catch (Exception $e) {
    Logger::error('Falha ao conectar no banco: ' . $e->getMessage());

    http_response_code(500);
    require PUBLIC_PATH . '/errors/db.php';
    exit;
}