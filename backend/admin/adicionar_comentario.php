<?php
// ============================================================================
// ARQUIVO: adicionar_comentario.php
// DESCRIÇÃO: Admin adiciona comentário/observação em uma denúncia
// ============================================================================
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

require_once '../auth/check_admin.php';
requireAdmin();

$data = json_decode(file_get_contents('php://input'), true);

if(!isset($data['denuncia_id']) || !isset($data['comentario'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit();
}

$denuncia_id = intval($data['denuncia_id']);
$comentario = trim($data['comentario']);

if(empty($comentario)) {
    echo json_encode(['success' => false, 'message' => 'Comentário não pode estar vazio']);
    exit();
}

// Limite de tamanho - evita campo de texto livre sem restrição
if(mb_strlen($comentario) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Comentário muito longo (máx. 1000 caracteres)']);
    exit();
}

try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();

    $query_check = "SELECT id FROM denuncias WHERE id = :id";
    $stmt_check = $db->prepare($query_check);
    $stmt_check->bindParam(':id', $denuncia_id);
    $stmt_check->execute();

    if($stmt_check->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Denúncia não encontrada']);
        exit();
    }

    $query = "INSERT INTO comentarios_denuncia (denuncia_id, usuario_id, comentario, created_at)
              VALUES (:denuncia_id, :usuario_id, :comentario, NOW())";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':denuncia_id', $denuncia_id);
    $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
    $stmt->bindParam(':comentario', $comentario);

    if($stmt->execute()) {
        $query_log = "INSERT INTO admin_logs (admin_id, acao, denuncia_id, detalhes, ip_address)
                      VALUES (:admin_id, 'adicionar_comentario', :denuncia_id, :detalhes, :ip)";

        $detalhes = "Adicionou um comentário na denúncia";
        $ip = $_SERVER['REMOTE_ADDR'];

        $stmt_log = $db->prepare($query_log);
        $stmt_log->bindParam(':admin_id', $_SESSION['usuario_id']);
        $stmt_log->bindParam(':denuncia_id', $denuncia_id);
        $stmt_log->bindParam(':detalhes', $detalhes);
        $stmt_log->bindParam(':ip', $ip);
        $stmt_log->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Comentário adicionado com sucesso!',
            'comentario' => [
                'id' => $db->lastInsertId(),
                'comentario' => $comentario,
                'usuario_nome' => $_SESSION['usuario_nome'] ?? 'Admin',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar comentário']);
    }

} catch(Exception $e) {
    // Log interno - mensagem genérica pro cliente, sem vazar detalhes
    error_log("Erro adicionar_comentario: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao processar solicitação']);
}
?>