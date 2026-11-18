<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if(!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$usuario_id = $_SESSION['usuario_id'];
$cep = $_POST['cep'] ?? '';
$logradouro = $_POST['logradouro'] ?? '';
$numero = $_POST['numero'] ?? '';
$complemento = $_POST['complemento'] ?? '';
$bairro = $_POST['bairro'] ?? '';
$cidade = $_POST['cidade'] ?? '';
$uf = $_POST['uf'] ?? '';
$tipo = $_POST['tipo'] ?? 'residencial';
$principal = isset($_POST['principal']) ? 1 : 0;

// Validações
if(empty($cep) || empty($logradouro) || empty($numero) || empty($bairro) || empty($cidade) || empty($uf)) {
    echo json_encode(['success' => false, 'message' => 'Preencha todos os campos obrigatórios']);
    exit();
}

// Se for endereço principal, remover principal dos outros
if($principal) {
    $query = "UPDATE enderecos SET principal = 0 WHERE usuario_id = :usuario_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->execute();
}

// Inserir novo endereço
$query = "INSERT INTO enderecos (usuario_id, cep, logradouro, numero, complemento, bairro, cidade, uf, tipo, principal) 
          VALUES (:usuario_id, :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :uf, :tipo, :principal)";
$stmt = $db->prepare($query);
$stmt->bindParam(':usuario_id', $usuario_id);
$stmt->bindParam(':cep', $cep);
$stmt->bindParam(':logradouro', $logradouro);
$stmt->bindParam(':numero', $numero);
$stmt->bindParam(':complemento', $complemento);
$stmt->bindParam(':bairro', $bairro);
$stmt->bindParam(':cidade', $cidade);
$stmt->bindParam(':uf', $uf);
$stmt->bindParam(':tipo', $tipo);
$stmt->bindParam(':principal', $principal);

if($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Endereço adicionado com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao adicionar endereço']);
}
?>