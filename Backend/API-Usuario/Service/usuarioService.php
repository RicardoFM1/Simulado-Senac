<?php
require_once __DIR__ . "/../Connection/usuarioConnection.php";
require_once __DIR__ . "/../Validator/usuarioValidador.php";

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT as JWT;
use Firebase\JWT\Key;
use Respect\Validation\Rules\Executable;

class UsuarioService
{
    private $usuarioDb;

    public function __construct()
    {
        $this->usuarioDb = dbUsuarioConnection();
    }

    public function listarUsuarios()
    {
        $query = $this->usuarioDb->query("SELECT * FROM usuario");
        $usuarios = $query->fetchAll(PDO::FETCH_ASSOC);

        return [
            'sucesso' => true,
            'dados' => $usuarios
        ];
    }

    public function buscarUsuarioPorId($usuarioDados)
    {
        $stmt = $this->usuarioDb->prepare("SELECT * FROM usuario WHERE id_usuario = :id_usuario");
        $stmt->execute([':id_usuario' => $usuarioDados['id_usuario']]);
        $usuario = $stmt->fetchAll();

        if (empty($usuario)) {
            http_response_code(404);
            return [
                'sucesso' => false,
                'mensagem' => 'Nenhum usuário encontrado pelo Id',
                'codigo' => 404
            ];
        }

        return [
            'sucesso' => true,
            'dados' => $usuario
        ];
    }

    public function buscarUsuarioPorEmail($usuarioDados)
    {
        $stmt = $this->usuarioDb->prepare("SELECT * FROM usuario WHERE email = :email");
        $stmt->execute([':email' => $usuarioDados['email']]);
        $usuario = $stmt->fetchAll();

        if (empty($usuario)) {
            http_response_code(404);
            return [
                'sucesso' => false,
                'mensagem' => 'Nenhum usuário encontrado pelo Email'
            ];
        }

        return [
            'sucesso' => true,
            'dados' => $usuario
        ];
    }

    public function buscarUsuarioPorCpf($usuarioDados)
    {
        $stmt = $this->usuarioDb->prepare("SELECT * FROM usuario WHERE cpf = :cpf");
        $stmt->execute([':cpf' => $usuarioDados['cpf']]);
        $usuario = $stmt->fetchAll();

        if (empty($usuario)) {
            http_response_code(404);
            return [
                'sucesso' => false,
                'mensagem' => 'Nenhum usuário encontrado pelo CPF'
            ];
        }

        return [
            'sucesso' => true,
            'dados' => $usuario
        ];
    }

    public function ValidarToken($tokenJWT)
    {
        if (empty($tokenJWT)) {
            throw new Exception('Sem dados do token', 400);
        }

        $chaveSecreta = $_ENV['JWT_SECRET_KEY'];
        $partesToken = explode(' ', $tokenJWT);

        if (count($partesToken) !== 2 || strcmp($partesToken[0], 'Bearer') !== 0) {
            throw new Exception('Formato de token inválido, apenas aceito: Bearer {token}', 401);
        }

        try {
            return JWT::decode($partesToken[1], new Key($chaveSecreta, 'HS256'));
        } catch (Exception $e) {
            throw new Exception('Token inválido, expirado ou sem permissão', 401);
        }
    }

    public function criarUsuario($usuarioDados)
    {
        if (empty($usuarioDados)) {
            throw new Exception('Insira os dados válidos', 400);
        }

        UsuarioValidador::validarUsuario($usuarioDados);

        $usuarioExistentePorEmail = $this->buscarUsuarioPorEmail($usuarioDados);
        $usuarioExistentePorCpf = $this->buscarUsuarioPorCpf($usuarioDados);


        if (isset($usuarioExistentePorEmail) && $usuarioExistentePorEmail['sucesso'] === true) {
            throw new Exception('Email já cadastrado', 409);
        }

        if (isset($usuarioExistentePorCpf) && $usuarioExistentePorCpf['sucesso'] === true) {
            throw new Exception('Cpf já cadastrado', 409);
        }

        // Formatar cpf
        $usuarioDados['cpf'] = str_replace(['.', ' ', '-', '/'], '', $usuarioDados['cpf']);

        $stmt = $this->usuarioDb->prepare("INSERT INTO usuario (nome, email, senha, cpf, cargo)
        VALUES(:nome, :email, :senha, :cpf, :cargo");

        $stmt->execute([
            ':nome' => $usuarioDados['nome'],
            ':email' => $usuarioDados['email'],
            ':senha' => password_hash($usuarioDados['senha'], PASSWORD_DEFAULT),
            ':cpf' => $usuarioDados['cpf'],
            ':cargo' => $usuarioDados['cargo']
        ]);

        return [
            'sucesso' => true,
            'mensagem' => 'Usuario criado com sucesso'
        ];
    }


    public function fazerLogin($usuarioDados, $tokenJWT)
    {
        if (empty($usuarioDados)) {
            throw new Exception('Insira os dados válidos', 400);
        }

        $usuarioExistentePorEmail = $this->buscarUsuarioPorEmail($usuarioDados);


        if (isset($usuarioExistentePorEmail) && $usuarioExistentePorEmail['sucesso'] === false) {
            throw new Exception('Credenciais inválidas', 401);
        }

        $senhaCorreta = password_verify($usuarioDados['senha'], $usuarioExistentePorEmail['senha']);

        if (!$senhaCorreta) {
            throw new Exception('Credenciais inválidas', 401);
        }

        $chaveSecreta = $_ENV['JWT_SECRET_KEY'];
        $payload = [
            'exp' => time() + 3600,
            'dados' => [
                'id_usuario' => $usuarioExistentePorEmail['dados']['id_usuario'],
                'cargo_usuario' => $usuarioExistentePorEmail['dados']['cargo']
            ]
        ];

        $jwt = JWT::encode($payload, $chaveSecreta, 'HS256');

        return [
            'sucesso' => true,
            'mensagem' => 'Usuario logado com sucesso',
            'token' => $jwt
        ];
    }

    public function atualizarUsuario($usuarioDados, $idUsuario, $tokenJWT)
    {

        if (empty($usuarioDados)) {
            throw new Exception('Insira os dados válidos', 400);
        }

        if (empty($idUsuario)) {
            throw new Exception('Insira o id do usuario', 400);
        }


        UsuarioValidador::validarUsuario($usuarioDados);

        $usuarioExistentePorEmail = $this->buscarUsuarioPorEmail($usuarioDados);
        $usuarioExistentePorCpf = $this->buscarUsuarioPorCpf($usuarioDados);


        if (isset($usuarioExistentePorEmail) && $usuarioExistentePorEmail['sucesso'] === true) {
            throw new Exception('Email já cadastrado', 409);
        }

        if (isset($usuarioExistentePorCpf) && $usuarioExistentePorCpf['sucesso'] === true) {
            throw new Exception('Cpf já cadastrado', 409);
        }

        // Formatar cpf
        $usuarioDados['cpf'] = str_replace(['.', ' ', '-', '/'], '', $usuarioDados['cpf']);

        $tokenValidado = $this->ValidarToken($tokenJWT);

        if ($tokenValidado->dados->cargo_usuario !== 'admin' && $tokenValidado->dados->id_usuario !== $usuarioExistentePorEmail['dados']['id_usuario']) {
            throw new Exception('Sem permissão para editar esse usuário', 401);
        }


        $stmt = $this->usuarioDb->prepare("UPDATE usuario set nome = :nome, email = :email, 
        senha = :senha, cpf = :cpf, cargo = :cargo WHERE id_usuario = :id_usuario");

        $stmt->execute([
            ':nome' => $usuarioDados['nome'],
            ':email' => $usuarioDados['email'],
            ':senha' => password_hash($usuarioDados['senha'], PASSWORD_DEFAULT),
            ':cpf' => $usuarioDados['cpf'],
            ':cargo' => $usuarioDados['cargo'],
            ':id_usuario' => $idUsuario
        ]);

        return [
            'sucesso' => true,
            'mensagem' => 'Usuario atualizado com sucesso'
        ];
    }


    public function deletarUsuario($idUsuario, $tokenJWT)
    {


        if (empty($idUsuario)) {
            throw new Exception('Insira o id do usuario', 400);
        }



        $usuarioExistentePorId = $this->buscarUsuarioPorId($idUsuario);



        if (isset($usuarioExistentePorId) && $usuarioExistentePorId['sucesso'] === true) {
            throw new Exception($usuarioExistentePorId['mensagem'], $usuarioExistentePorId['codigo']);
        }


        $tokenValidado = $this->ValidarToken($tokenJWT);

        if ($tokenValidado->dados->cargo_usuario !== 'admin' && $tokenValidado->dados->id_usuario !== $usuarioExistentePorId['dados']['id_usuario']) {
            throw new Exception('Sem permissão para excluir esse usuário', 401);
        }


        $stmt = $this->usuarioDb->prepare("DELETE FROM usuario WHERE id_usuario = :id_usuario");

        $stmt->execute([
            ':id_usuario' => $idUsuario
        ]);

        return [
            'sucesso' => true,
            'mensagem' => 'Usuario deletado com sucesso'
        ];
    }
}
