<?php

declare(strict_types=1);

// Front Controller: ponto único de entrada da aplicação.
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Request;
use App\Core\Router;
use App\Core\Session;

Session::iniciar();

$router = new Router();
require BASE_PATH . '/config/rotas.php';

$router->despachar(Request::capturar());
