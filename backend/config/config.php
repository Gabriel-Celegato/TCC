<?php
// ============================================================================
// ARQUIVO: config.php (backend)
// DESCRIÇÃO: Configurações do backend
// ============================================================================

// Incluir configurações globais
require_once __DIR__ . '/../../config.php';

// Configurações específicas do backend
define('UPLOAD_PATH', BASE_PATH . 'frontend/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
?>