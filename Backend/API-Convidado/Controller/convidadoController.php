<?php

require_once __DIR__ . "/../Service/convidadoService.php";

class ConvidadoController
{

    private $convidadoService;

    public function __construct()
    {
        $this->convidadoService = new ConvidadoService();
    }

    public function listarConvidados()
    {

        http_response_code(200);
        echo json_encode($this->convidadoService->listarConvidados());
    }

    public function criarConvidado()
    {
        try {
          
            $convidadoDados = json_decode(file_get_contents("php://input"), true) ?? null;
            http_response_code(201);
            echo json_encode($this->convidadoService->criarConvidado($convidadoDados));
        } catch (Exception $e) {
            http_response_code($e->getCode());
            echo json_encode([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ]);
        }
    }


    public function atualizarConvidado()
    {
        try {
            http_response_code(200);
            $convidadoDados = json_decode(file_get_contents('php://input'), true);
            $idConvidado = $_GET['id_convidado'];
            $tokenJWT = null;

            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $tokenJWT = trim($_SERVER['HTTP_AUTHORIZATION']);
            }

            if (isset($_SERVER['AUTHORIZATION'])) {
                $tokenJWT = trim($_SERVER['AUTHORIZATION']);
            }

            echo json_encode($this->convidadoService->atualizarConvidado($convidadoDados, $idConvidado, $tokenJWT));
        } catch (Exception $e) {
            http_response_code($e->getCode());
            echo json_encode([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ]);
            exit;
        }
    }

    public function deletarConvidado()
    {
        try {
            http_response_code(200);
            $idConvidado = $_GET['id_convidado'];
            $tokenJWT = null;

            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $tokenJWT = trim($_SERVER['HTTP_AUTHORIZATION']);
            }

            if (isset($_SERVER['AUTHORIZATION'])) {
                $tokenJWT = trim($_SERVER['AUTHORIZATION']);
            }

            echo json_encode($this->convidadoService->deletarConvidado($idConvidado, $tokenJWT));
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
