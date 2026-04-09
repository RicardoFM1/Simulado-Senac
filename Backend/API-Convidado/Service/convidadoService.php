<?php
require_once __DIR__ . "/../Connection/convidadoConnection.php";
require_once __DIR__ . "/../Validator/convidadoValidador.php";

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT as JWT;
use Firebase\JWT\Key;
use Respect\Validation\Rules\Executable;

class ConvidadoService
{
    private $convidadoDb;

    public function __construct()
    {
        $this->convidadoDb = dbConvidadoConnection();
    }

    public function listarConvidados()
    {
        $query = $this->convidadoDb->query("SELECT * FROM convidado");
        $convidados = $query->fetchAll();

        return [
            'sucesso' => true,
            'dados' => $convidados
        ];
    }

    public function buscarConvidadoPorId($convidadoDados)
    {
        $stmt = $this->convidadoDb->prepare("SELECT * FROM convidado WHERE id_convidado = :id_convidado");
        $stmt->execute([':id_convidado' => $convidadoDados['id_convidado']]);
        $convidado = $stmt->fetch();

        if (empty($convidado)) {
            http_response_code(404);
            return [
                'sucesso' => false,
                'mensagem' => 'Nenhum convidado encontrado pelo Id',
                'codigo' => 404
            ];
        }

        return [
            'sucesso' => true,
            'dados' => $convidado
        ];
    }

    public function buscarConvidadoPorEmail($emailConvidado)
    {
        if (empty($emailConvidado)) {

            return [
                'sucesso' => false,
                'mensagem' => 'Email do convidado não informado',
                'codigo' => 400
            ];
        }

        $acharConvidadoEmail = $this->convidadoDb->prepare("SELECT * FROM convidado WHERE email = :email");
        $acharConvidadoEmail->execute([':email' => $emailConvidado]);
        $convidado = $acharConvidadoEmail->fetch();

        if (empty($convidado)) {

            return [
                'sucesso' => false,
                'mensagem' => "Convidado não encontrado",
                'codigo' => 404
            ];
        }

        return [
            'sucesso' => true,
            'dados' => $convidado
        ];
    }

    public function buscarConvidadoPorCpf($cpfConvidado)
    {
        if (empty($cpfConvidado)) {

            return [
                'sucesso' => false,
                'mensagem' => 'CPF do convidado não informado',
                'codigo' => 400
            ];
        }

        $acharConvidadoCPF = $this->convidadoDb->prepare("SELECT id_convidado FROM convidado WHERE cpf = :cpf");
        $acharConvidadoCPF->execute([':cpf' => $cpfConvidado]);
        $convidado = $acharConvidadoCPF->fetch();

        if (empty($convidado)) {

            return [
                'sucesso' => false,
                'mensagem' => "Convidado não encontrado",
                'codigo' => 404
            ];
        }

        return [
            'sucesso' => true,
            'dados' => $convidado
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

    public function criarConvidado($convidadoDados)
    {
        ConvidadoValidador::validarConvidado($convidadoDados);
        // formatar cpf
        $convidadoDados['cpf'] = str_replace([' ', '.', '-'], '', $convidadoDados['cpf']);

        if ($this->buscarConvidadoPorCpf($convidadoDados['cpf'])['sucesso']) {
            throw new Exception("Este CPF já está cadastrado", 409);
        }

        if ($this->buscarConvidadoPorEmail($convidadoDados['email'])['sucesso']) {
            throw new Exception("Este Email já está cadastrado", 409);
        }




        $stmt = $this->convidadoDb->prepare("INSERT INTO convidado(nome, sobrenome, email, telefone, cpf, numero_mesa)
        VALUES (:nome, :email, :senha, :cargo, :cpf)");

        $stmt->execute([
            ':nome' => $convidadoDados['nome'],
            ':sobrenome' => $convidadoDados['sobrenome'],
            ':email' => $convidadoDados['email'],
            ':telefone' => $convidadoDados['telefone'],
            ':cpf' => $convidadoDados['cpf'],
            ':numero_mesa' => $convidadoDados['numero_mesa']
        ]);


        return [
            'sucesso' => true,
            'mensagem' => 'Convidado criado com sucesso'
        ];
    }



    public function atualizarConvidado($convidadoDados, $idConvidado, $tokenJWT)
    {

        if (empty($convidadoDados)) {
            throw new Exception('Insira os dados válidos', 400);
        }

        if (empty($idConvidado)) {
            throw new Exception('Insira o id do convidado', 400);
        }


        ConvidadoValidador::validarConvidado($convidadoDados);

        $convidadoExistentePorEmail = $this->buscarConvidadoPorEmail($convidadoDados);
        $convidadoExistentePorCpf = $this->buscarConvidadoPorCpf($convidadoDados);


        if (isset($convidadoExistentePorEmail) && $convidadoExistentePorEmail['sucesso'] === true) {
            throw new Exception('Email já cadastrado', 409);
        }

        if (isset($convidadoExistentePorCpf) && $convidadoExistentePorCpf['sucesso'] === true) {
            throw new Exception('Cpf já cadastrado', 409);
        }

        // Formatar cpf
        $convidadoDados['cpf'] = str_replace(['.', ' ', '-', '/'], '', $convidadoDados['cpf']);
        echo json_encode($convidadoDados['cpf']);

        $tokenValidado = $this->ValidarToken($tokenJWT);

        if ($tokenValidado->dados->cargo_usuario !== 'admin' && $tokenValidado->dados->id_usuario !== $convidadoExistentePorEmail['dados']['id_usuario']) {
            throw new Exception('Sem permissão para editar esse convidado', 401);
        }


        $stmt = $this->convidadoDb->prepare("UPDATE convidado set nome = :nome, sobrenome = :nome, email = :email, 
        telefone = :telefone, cpf = :cpf, numero_mesa = :numero_mesa WHERE id_convidado = :id_convidado");

        $stmt->execute([
           ':nome' => $convidadoDados['nome'],
            ':sobrenome' => $convidadoDados['sobrenome'],
            ':email' => $convidadoDados['email'],
            ':telefone' => $convidadoDados['telefone'],
            ':cpf' => $convidadoDados['cpf'],
            ':numero_mesa' => $convidadoDados['numero_mesa'],
            ':id_convidado' => $idConvidado
        ]); 

        return [
            'sucesso' => true,
            'mensagem' => 'Convidado atualizado com sucesso'
        ];
    }


    public function deletarConvidado($idConvidado, $tokenJWT)
    {


        if (empty($idConvidado)) {
            throw new Exception('Insira o id do convidado', 400);
        }



        $convidadoExistentePorId = $this->buscarConvidadoPorId($idConvidado);



        if (isset($convidadoExistentePorId) && $convidadoExistentePorId['sucesso'] === true) {
            throw new Exception($convidadoExistentePorId['mensagem'], $convidadoExistentePorId['codigo']);
        }


        $tokenValidado = $this->ValidarToken($tokenJWT);

        if ($tokenValidado->dados->cargo_usuario !== 'admin' && $tokenValidado->dados->id_usuario !== $convidadoExistentePorId['dados']['id_usuario']) {
            throw new Exception('Sem permissão para excluir esse convidado', 401);
        }


        $stmt = $this->convidadoDb->prepare("DELETE FROM convidado WHERE id_convidado = :id_convidado");

        $stmt->execute([
            ':id_convidado' => $idConvidado
        ]);

        return [
            'sucesso' => true,
            'mensagem' => 'Convidado deletado com sucesso'
        ];
    }
}
