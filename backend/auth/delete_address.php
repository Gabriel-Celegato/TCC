<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if(!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$usuario_id = $_SESSION['usuario_id'];
$endereco_id = intval($_GET['id'] ?? 0);

if($endereco_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit();
}

$query = "SELECT id FROM enderecos WHERE id = :id AND usuario_id = :usuario_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $endereco_id, PDO::PARAM_INT);
$stmt->bindParam(':usuario_id', $usuario_id);
$stmt->execute();

if($stmt->rowCount() == 0) {
    echo json_encode(['success' => false, 'message' => 'Endereço não encontrado']);
    exit();
}

$query = "DELETE FROM enderecos WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $endereco_id, PDO::PARAM_INT);

if($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Endereço excluído com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir endereço']);
}
?>