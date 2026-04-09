<?php

use Dotenv\Dotenv;

require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . "/../Controller/usuarioController.php";

$dotenv = Dotenv::createImmutable(__DIR__ . "/../../");
$dotenv->load();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$rotaRequisicao = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$metodoRequisicao = $_SERVER['REQUEST_METHOD'];

if($metodoRequisicao === "OPTIONS"){
    http_response_code(200);
    exit;
}

if ($rotaRequisicao === "/usuario") {
    $usuarioController = new UsuarioController();
    // UsuarioMiddleware::validarMiddlewareUsuario();

    if ($metodoRequisicao === "GET") {
        $usuarioController->listarUsuarios();
    }
    if ($metodoRequisicao === "POST") {
        $usuarioController->criarUsuario();
    }
    if ($metodoRequisicao === "PUT") {
        $usuarioController->atualizarUsuario();
    }
    if ($metodoRequisicao === "DELETE") {
        $usuarioController->deletarUsuario();
    }
}

if ($rotaRequisicao === "/usuario/login") {
    $usuarioController = new UsuarioController();

    if ($metodoRequisicao === "POST") {
        $usuarioController->fazerLogin();
    }
}
