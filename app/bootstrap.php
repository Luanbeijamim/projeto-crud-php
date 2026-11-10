<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

$autoloadComposer = BASE_PATH . '/vendor/autoload.php';
if (is_file($autoloadComposer)) {
    require $autoloadComposer;
} else {
    spl_autoload_register(static function (string $classe): void {
        $prefixo = 'App\\';
        if (strncmp($classe, $prefixo, strlen($prefixo)) !== 0) {
            return;
        }
        $relativo = str_replace('\\', '/', substr($classe, strlen($prefixo)));
        $arquivo = APP_PATH . '/' . $relativo . '.php';
        if (is_file($arquivo)) {
            require $arquivo;
        }
    });
}

require_once APP_PATH . '/Core/helpers.php';

App\Core\Config::carregar(require BASE_PATH . '/config/config.php');

date_default_timezone_set(App\Core\Config::get('timezone'));

App\Core\ErrorHandler::registrar();
