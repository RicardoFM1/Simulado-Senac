<?php

use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;

class ConvidadoValidador
{

    public static function validarConvidado($convidadoDados)
    {

       
        $convidadoDados['cpf'] = str_replace([' ', '.', '-'], '', $convidadoDados['cpf']);

        $esquema = v::key('nome', v::stringVal()->length(5, 50)->notEmpty())
            ->key('sobrenome', v::stringVal()->length(5, 50)->notEmpty())
            ->key('email', v::email())
            ->key('telefone', v::phone())
            ->key('cpf', v::cpf())
            ->key('numero_mesa', v::intVal());

        try {

            $esquema->assert($convidadoDados);
        } catch (NestedValidationException $e) {
            $mensagemPersonalizada = [
                'nome' => 'Nome inválido, mínimo 5 caracteres e máximo 50',
                'sobrenome' => 'Sobrenome inválido, mínimo 5 caracteres e máximo 50 ',
                'email' => 'Email inválido',
                'telefone' => 'Telefone inválido',
                'cpf' => 'CPF inválido',
                'numero_mesa' => 'Numero da mesa inválido'
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
