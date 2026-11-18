<?php
session_start();

require_once '../auth/check_admin.php';
requireAdmin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if(!isset($data['denuncia_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit();
}

$denuncia_id = intval($data['denuncia_id']);
$novo_status = $data['status'];
$observacao = $data['observacao'] ?? '';

// Status válidos
$status_validos = ['pendente', 'em_andamento', 'resolvido', 'falso_alarme'];

if(!in_array($novo_status, $status_validos)) {
    echo json_encode(['success' => false, 'message' => 'Status inválido']);
    exit();
}

try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Buscar status atual
    $query = "SELECT status FROM denuncias WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $denuncia_id);
    $stmt->execute();
    $status_atual = $stmt->fetchColumn();
    
    if($status_atual == $novo_status) {
        echo json_encode(['success' => true, 'message' => 'Status já está atualizado']);
        exit();
    }
    
    // Atualizar status
    $query = "UPDATE denuncias SET status = :status, updated_at = NOW() WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $novo_status);
    $stmt->bindParam(':id', $denuncia_id);
    
    if($stmt->execute()) {
        // Registrar no histórico
        $query_historico = "INSERT INTO historico_denuncia (denuncia_id, status_anterior, status_novo, alterado_por, observacao) 
                            VALUES (:denuncia_id, :status_anterior, :status_novo, :alterado_por, :observacao)";
        
        $stmt_historico = $db->prepare($query_historico);
        $stmt_historico->bindParam(':denuncia_id', $denuncia_id);
        $stmt_historico->bindParam(':status_anterior', $status_atual);
        $stmt_historico->bindParam(':status_novo', $novo_status);
        $stmt_historico->bindParam(':alterado_por', $_SESSION['usuario_id']);
        $stmt_historico->bindParam(':observacao', $observacao);
        $stmt_historico->execute();
        
        // Registrar em admin_logs
        $query_log = "INSERT INTO admin_logs (admin_id, acao, denuncia_id, detalhes, ip_address) 
                      VALUES (:admin_id, 'alterar_status', :denuncia_id, :detalhes, :ip)";
        
        $detalhes = "Status alterado de {$status_atual} para {$novo_status}";
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $stmt_log = $db->prepare($query_log);
        $stmt_log->bindParam(':admin_id', $_SESSION['usuario_id']);
        $stmt_log->bindParam(':denuncia_id', $denuncia_id);
        $stmt_log->bindParam(':detalhes', $detalhes);
        $stmt_log->bindParam(':ip', $ip);
        $stmt_log->execute();
        
        echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar']);
    }
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>