<?php
// ============================================================================
// ARQUIVO: csrf.php
// DESCRIÇÃO: Proteção CSRF - gera e valida tokens por sessão
// ============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Gera (ou reaproveita) o token CSRF da sessão atual.
 * Chamar isso ao renderizar qualquer formulário.
 */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida o token recebido contra o da sessão.
 * Chamar no início de todo endpoint que recebe POST/PUT/DELETE.
 * Aceita o token vindo de $_POST['csrf_token'] ou do header X-CSRF-Token (para requests JSON).
 */
function csrfValidate() {
    $tokenRecebido = $_POST['csrf_token']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? null;

    if (empty($_SESSION['csrf_token']) || empty($tokenRecebido)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $tokenRecebido);
}

/**
 * Atalho para usar no topo dos endpoints: mata a requisição com erro 403
 * se o token não bater.
 */
function csrfRequire() {
    if (!csrfValidate()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Token de segurança inválido. Recarregue a página.']);
        exit();
    }
}
?>