<?php

use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;

class UsuarioValidador
{

    public static function validarUsuario($usuarioDados)
    {

        $cargosPermitidos = ['admin', 'ceremonialista'];
        $esquema = v::key('nome', v::stringVal()->notEmpty())
            ->key('email', v::email())
            ->key('senha', v::stringVal()->notEmpty())
            ->key('cpf', v::cpf())
            ->key('cargo', v::in($cargosPermitidos));

        try {
        } catch (NestedValidationException $e) {
            $mensagemPersonalizada = [
                'nome' => 'Nome inválido, mínimo 5 caracteres e máximo 50',
                'email' => 'Email inválido',
                'senha' => 'Senha inválida, mínimo 8 caracteres, máximo 50, pelo menos 1 digito e um caractere especial',
                'cpf' => 'CPF inválido',
                'cargo' => 'Cargo inválido, apenas é aceito: admin ou ceremonialista'
            ];

            $mensagemOriginal = $e->getMessages();
            $mensagemTraduzida = [];

            foreach ($mensagemOriginal as $campo => $mensagem) {
                $mensagemTraduzida[$campo] = $mensagemPersonalizada[$campo] ?? $mensagem;
            }

            http_response_code(400);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro de validação',
                'erros' => $mensagemTraduzida
            ]);
            exit;
        }
    }
}
