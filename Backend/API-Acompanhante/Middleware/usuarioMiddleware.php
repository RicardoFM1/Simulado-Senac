<?php

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class UsuarioMiddleware
{
    public static function validarMiddlewareUsuario()
    {

        $tokenJWT = null;

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $tokenJWT = trim($_SERVER['HTTP_AUTHORIZATION']);
        }

        if (isset($_SERVER['AUTHORIZATION'])) {
            $tokenJWT = trim($_SERVER['AUTHORIZATION']);
        }

        $chaveSecreta = $_ENV['JWT_SECRET_KEY'];
        $partesToken = explode(' ', $tokenJWT);

        if (empty($tokenJWT)) {
            http_response_code(401);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Usuário não autenticado'
            ]);
            exit;
        }

        if (count($partesToken) !== 2 || strcmp($partesToken[0], 'Bearer') !== 0) {
            http_response_code(401);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Formato token inválido, esperado: Bearer {token}'
            ]);
            exit;
        }

        try {
            $jwt = JWT::decode($partesToken[1], new Key($chaveSecreta, 'HS256'));

            if ($jwt->dados->cargo_usuario !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Sem permissão para acessar essa rota'
                ]);
                exit;
            }
        } catch (ExpiredException $e) {
            http_response_code(401);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Token expirado'
            ]);
            exit;
        }
    }
}
