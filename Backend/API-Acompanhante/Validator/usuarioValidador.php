<?php

use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;

class UsuarioValidador
{

    public static function validarUsuario($usuarioDados)
    {

        $cargosPermitidos = ['admin', 'ceremonialista'];
        $usuarioDados['cpf'] = str_replace([' ', '.', '-'], '', $usuarioDados['cpf']);

        $esquema = v::key('nome', v::stringVal()->length(5, 50)->notEmpty())
            ->key('email', v::email())
            ->key('senha', v::stringVal()->notEmpty()->length(8, 50)->regex('/\d/')->regex('/[!@#$%¨&*()]/'))
            ->key('cpf', v::cpf())
            ->key('cargo', v::in($cargosPermitidos));

        try {

            $esquema->assert($usuarioDados);
        } catch (NestedValidationException $e) {
            $mensagemPersonalizada = [
                'nome' => 'Nome inválido, mínimo 5 caracteres e máximo 50',
                'email' => 'Email inválido',
                'senha' => 'Senha inválida, mínimo 8 caracteres, máximo 50, pelo menos 1 digito e um caractere especial',
                'cpf' => 'CPF inválido',
                'cargo' => 'Cargo inválido, apenas é aceito: admin ou ceremonialista'
            ];
            $mensagemOriginal = $e->getMessages();
            $mensagensTraduzidas = [];

            foreach ($mensagemOriginal as $campo => $mensagem) {
                $mensagensTraduzidas[$campo] = $mensagemPersonalizada[$campo] ?? $mensagem;
            }

            http_response_code(400);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro de validação',
                'erros' => $mensagensTraduzidas
            ]);
            exit;
        }
    }
}
