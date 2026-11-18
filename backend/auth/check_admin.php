<?php
// ============================================================================
// ARQUIVO: check_admin.php
// DESCRIÇÃO: Verifica se o usuário está logado e se é admin
// ============================================================================

// Iniciar sessão apenas se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================================
// FUNÇÃO PARA VERIFICAR SE É ADMIN
// ============================================================================
function requireAdmin() {
    // Verificar se usuário está logado
    if(!isset($_SESSION['usuario_id'])) {
        header("Location: /TCC/frontend/login.php");
        exit();
    }
    
    // Verificar se é admin no banco
    try {
        require_once __DIR__ . '/../config/database.php';
        
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT tipo FROM usuarios WHERE id = :id AND tipo = 'admin'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_SESSION['usuario_id']);
        $stmt->execute();
        
        $isAdmin = $stmt->rowCount() > 0;
        
        if(!$isAdmin) {
            header("Location: /TCC/frontend/index.php");
            exit();
        }
        
    } catch(Exception $e) {
        header("Location: /TCC/frontend/index.php");
        exit();
    }
}

// ============================================================================
// FUNÇÃO PARA VERIFICAR SE ESTÁ LOGADO (genérica)
// ============================================================================
function requireLogin() {
    if(!isset($_SESSION['usuario_id'])) {
        header("Location: /TCC/frontend/login.php");
        exit();
    }
}

// ============================================================================
// VERIFICAÇÃO RÁPIDA (se chamar diretamente este arquivo)
// ============================================================================
// Se quiser que verifique automaticamente, descomente a linha abaixo
// requireAdmin();
?>