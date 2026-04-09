<?php

use Dotenv\Dotenv;
require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . "/../Controller/convidadoController.php";
require_once __DIR__ . "/../Middleware/convidadoMiddleware.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


$dotenv = Dotenv::createImmutable(__DIR__ . "/../../");
$dotenv->load();

$caminhoRequisicao = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$metodoRequisicao = $_SERVER['REQUEST_METHOD'];




if($metodoRequisicao === "OPTIONS"){
    http_response_code(200);
    exit;
}

if($caminhoRequisicao === "/convidado"){
    $convidadoController = new ConvidadoController();
    // UsuarioMiddleware::validarMiddlewareUsuario();

    if($metodoRequisicao === "GET"){
        $convidadoController->listarConvidados();
    }
    
    if($metodoRequisicao === "POST"){
        $convidadoController->criarConvidado();
    }

    if($metodoRequisicao === "PUT"){
        $convidadoController->atualizarConvidado();
    }

    if($metodoRequisicao === "DELETE"){
        $convidadoController->deletarConvidado();
    }
}

