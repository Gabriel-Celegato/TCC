<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../validations/validate.php';
require_once 'csrf.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

// Valida o token CSRF antes de processar qualquer dado - aplicar essa linha
// logo no topo de TODO endpoint que recebe POST/PUT/DELETE
csrfRequire();

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$data_nascimento = $_POST['data_nascimento'] ?? '';
$cep = trim($_POST['cep'] ?? '');
$logradouro = trim($_POST['logradouro'] ?? '');
$numero = trim($_POST['numero'] ?? '');
$complemento = trim($_POST['complemento'] ?? '');
$bairro = trim($_POST['bairro'] ?? '');
$cidade = trim($_POST['cidade'] ?? '');
$uf = trim($_POST['uf'] ?? '');
$senha = $_POST['senha'] ?? '';

if(empty($nome) || empty($email) || empty($telefone) || empty($data_nascimento) ||
   empty($cep) || empty($logradouro) || empty($numero) || empty($bairro) ||
   empty($cidade) || empty($uf) || empty($senha)) {
    echo json_encode(['success' => false, 'message' => 'Preencha todos os campos obrigatórios']);
    exit();
}

if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit();
}

$telefoneLimpo = preg_replace('/[^0-9]/', '', $telefone);
if(strlen($telefoneLimpo) < 10 || strlen($telefoneLimpo) > 11) {
    echo json_encode(['success' => false, 'message' => 'Telefone inválido']);
    exit();
}

$cepLimpo = preg_replace('/[^0-9]/', '', $cep);
if(strlen($cepLimpo) != 8) {
    echo json_encode(['success' => false, 'message' => 'CEP inválido']);
    exit();
}

$ufs_validos = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS',
                'MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
if(!in_array(strtoupper($uf), $ufs_validos)) {
    echo json_encode(['success' => false, 'message' => 'UF inválida']);
    exit();
}

if(strlen($senha) < 6 || !preg_match('/[A-Z]/', $senha) || !preg_match('/[0-9]/', $senha)) {
    echo json_encode(['success' => false, 'message' => 'Senha deve ter 6+ caracteres, 1 maiúscula e 1 número']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id FROM usuarios WHERE email = :email";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $email);
$stmt->execute();

if($stmt->rowCount() > 0) {
    echo json_encode(['success' => false, 'message' => 'Email já cadastrado']);
    exit();
}

$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

$query = "INSERT INTO usuarios (
    nome, email, telefone, data_nascimento,
    cep, logradouro, numero, complemento, bairro, cidade, uf,
    senha, status
) VALUES (
    :nome, :email, :telefone, :data_nascimento,
    :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :uf,
    :senha, 'ativo'
)";

$stmt = $db->prepare($query);
$stmt->bindParam(':nome', $nome);
$stmt->bindParam(':email', $email);
$stmt->bindParam(':telefone', $telefoneLimpo);
$stmt->bindParam(':data_nascimento', $data_nascimento);
$stmt->bindParam(':cep', $cep);
$stmt->bindParam(':logradouro', $logradouro);
$stmt->bindParam(':numero', $numero);
$stmt->bindParam(':complemento', $complemento);
$stmt->bindParam(':bairro', $bairro);
$stmt->bindParam(':cidade', $cidade);
$stmt->bindParam(':uf', $uf);
$stmt->bindParam(':senha', $senha_hash);

try {
    if($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cadastro realizado com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar']);
    }
} catch(Exception $e) {
    error_log("Erro register_process: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar']);
}
?>