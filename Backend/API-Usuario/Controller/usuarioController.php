<?php

require_once __DIR__ . "/../Service/usuarioService.php";

class UsuarioController
{

    private $usuarioService;

    public function __construct()
    {
        $this->usuarioService = new UsuarioService();
    }

    public function listarUsuarios()
    {

        http_response_code(200);
        echo json_encode($this->usuarioService->listarUsuarios());
    }

    public function criarUsuario()
    {
        try {
          
            $usuarioDados = json_decode(file_get_contents("php://input"), true) ?? null;
            http_response_code(201);
            echo json_encode($this->usuarioService->criarUsuario($usuarioDados));
        } catch (Exception $e) {
            http_response_code($e->getCode());
            echo json_encode([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ]);
        }
    }

    public function fazerLogin()
    {
        try {
            http_response_code(200);
            $usuarioDados = json_decode(file_get_contents('php://input'), true) ?? null;
            $tokenJWT = null;

            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $tokenJWT = trim($_SERVER['HTTP_AUTHORIZATION']);
            }

            if (isset($_SERVER['AUTHORIZATION'])) {
                $tokenJWT = trim($_SERVER['AUTHORIZATION']);
            }

            echo json_encode($this->usuarioService->fazerLogin($usuarioDados, $tokenJWT));
        } catch (Exception $e) {
            http_response_code($e->getCode());
            echo json_encode([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ]);
            exit;
        }
    }

    public function atualizarUsuario()
    {
        try {
            http_response_code(200);
            $usuarioDados = json_decode(file_get_contents('php://input'), true) ?? null;
            $idUsuario = $_GET['id_usuario'];
            $tokenJWT = null;

            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $tokenJWT = trim($_SERVER['HTTP_AUTHORIZATION']);
            }

            if (isset($_SERVER['AUTHORIZATION'])) {
                $tokenJWT = trim($_SERVER['AUTHORIZATION']);
            }

            echo json_encode($this->usuarioService->atualizarUsuario($usuarioDados, $idUsuario, $tokenJWT));
        } catch (Exception $e) {
            http_response_code($e->getCode());
            echo json_encode([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ]);
            exit;
        }
    }

    public function deletarUsuario()
    {
        try {
            http_response_code(200);
            $idUsuario = $_GET['id_usuario'];
            $tokenJWT = null;

            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $tokenJWT = trim($_SERVER['HTTP_AUTHORIZATION']);
            }

            if (isset($_SERVER['AUTHORIZATION'])) {
                $tokenJWT = trim($_SERVER['AUTHORIZATION']);
            }

            echo json_encode($this->usuarioService->deletarUsuario($idUsuario, $tokenJWT));
        } catch (Exception $e) {
            http_response_code($e->getCode());
            echo json_encode([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ]);
            exit;
        }
    }
}
